<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('crear_ventas');

$db = getDBConnection();

$message = '';
$message_type = '';

// Obtener el ID del empleado logueado
$id_empleado_logueado = null;
if (isset($_SESSION['user_id'])) {
    $stmt_empleado = $db->prepare("SELECT id FROM empleados WHERE id_usuario = :user_id");
    $stmt_empleado->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt_empleado->execute();
    $id_empleado_logueado = $stmt_empleado->fetchColumn();
}

if (!$id_empleado_logueado) {
    // Si no se encuentra el ID del empleado, es un error crítico para registrar una venta
    registrarActividad('Error al crear venta', 'No se pudo obtener id_empleado para user_id: ' . ($_SESSION['user_id'] ?? 'N/A'), 'error');
    header('Location: consulta.php?message=Error: No se pudo identificar al empleado que registra la venta.&type=danger');
    exit();
}

// Obtener clientes para el dropdown
$clientes = $db->query("SELECT id, nombre, apellido FROM clientes ORDER BY nombre ASC")->fetchAll();

// Obtener productos para el selector de productos
$productos_disponibles = $db->query("SELECT id, nombre, precio, stock FROM productos WHERE stock > 0 ORDER BY nombre ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = filter_var($_POST['id_cliente'] ?? '', FILTER_VALIDATE_INT);
    $items = json_decode($_POST['items_json'] ?? '[]', true);

    if (!$id_cliente) {
        $message = 'Por favor, seleccione un cliente.';
        $message_type = 'danger';
    } elseif (empty($items)) {
        $message = 'Debe añadir al menos un producto a la venta.';
        $message_type = 'danger';
    } else {
        $db->beginTransaction();
        try {
            $total_venta = 0;
            $detalles_para_insertar = [];

            foreach ($items as $item) {
                $producto_id = filter_var($item['producto_id'] ?? '', FILTER_VALIDATE_INT);
                $cantidad = filter_var($item['cantidad'] ?? '', FILTER_VALIDATE_INT);
                $precio_unitario = filter_var($item['precio_unitario'] ?? '', FILTER_VALIDATE_FLOAT);

                if (!$producto_id || $cantidad === false || $cantidad <= 0 || $precio_unitario === false || $precio_unitario <= 0) {
                    throw new Exception('Datos de producto inválidos en la venta.');
                }

                // Verificar stock disponible
                $stmt_stock = $db->prepare("SELECT stock FROM productos WHERE id = :producto_id");
                $stmt_stock->bindParam(':producto_id', $producto_id, PDO::PARAM_INT);
                $stmt_stock->execute();
                $current_stock = $stmt_stock->fetchColumn();

                if ($current_stock < $cantidad) {
                    throw new Exception('Stock insuficiente para el producto ID: ' . $producto_id . '. Stock disponible: ' . $current_stock);
                }

                $subtotal_item = $cantidad * $precio_unitario;
                $total_venta += $subtotal_item;
                $detalles_para_insertar[] = [
                    'producto_id' => $producto_id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio_unitario,
                    'subtotal' => $subtotal_item
                ];
            }

            // Insertar la venta principal, incluyendo id_empleado
            $stmt_venta = $db->prepare("
                INSERT INTO ventas (id_cliente, id_empleado, fecha_venta, total)
                VALUES (:id_cliente, :id_empleado, NOW(), :total)
            ");
            $stmt_venta->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
            $stmt_venta->bindParam(':id_empleado', $id_empleado_logueado, PDO::PARAM_INT);
            $stmt_venta->bindParam(':total', $total_venta, PDO::PARAM_STR); // Usar STR para FLOAT
            $stmt_venta->execute();
            $venta_id = $db->lastInsertId();

            // Insertar detalles de la venta y actualizar stock
            $stmt_detalle = $db->prepare("
                INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal)
                VALUES (:id_venta, :id_producto, :cantidad, :precio_unitario, :subtotal)
            ");
            $stmt_update_stock = $db->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :id_producto");

            foreach ($detalles_para_insertar as $detalle) {
                $stmt_detalle->bindParam(':id_venta', $venta_id, PDO::PARAM_INT);
                $stmt_detalle->bindParam(':id_producto', $detalle['producto_id'], PDO::PARAM_INT);
                $stmt_detalle->bindParam(':cantidad', $detalle['cantidad'], PDO::PARAM_INT);
                $stmt_detalle->bindParam(':precio_unitario', $detalle['precio_unitario'], PDO::PARAM_STR);
                $stmt_detalle->bindParam(':subtotal', $detalle['subtotal'], PDO::PARAM_STR);
                $stmt_detalle->execute();

                $stmt_update_stock->bindParam(':cantidad', $detalle['cantidad'], PDO::PARAM_INT);
                $stmt_update_stock->bindParam(':id_producto', $detalle['producto_id'], PDO::PARAM_INT);
                $stmt_update_stock->execute();
            }

            $db->commit();
            $message = 'Venta registrada exitosamente con ID: ' . $venta_id . '.';
            $message_type = 'success';
            registrarActividad('Venta creada', 'Venta ID: ' . $venta_id . ', Cliente ID: ' . $id_cliente . ', Empleado ID: ' . $id_empleado_logueado . ', Total: ' . $total_venta);
            // Redirigir para limpiar el POST y mostrar el mensaje
            header('Location: consulta.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            $message = 'Error al registrar la venta: ' . $e->getMessage();
            $message_type = 'danger';
            registrarActividad('Error al crear venta', 'Error: ' . $e->getMessage(), 'error');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Venta - <?php echo APP_NAME; ?></title>
    <link href="../css/modern.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include_once '../includes/navbar.php'; ?>
    <div class="dashboard-container">
        <?php include_once '../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="centered-card">
                <div class="card modern-card" style="max-width: 900px;">
                    <div class="card-header modern-card-header">
                        <h2 class="modern-title"><i class="bi bi-cart-plus"></i> Registrar Nueva Venta</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> modern-alert" role="alert">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form id="ventaForm" action="crear.php" method="POST" autocomplete="off">
                            <div class="modern-form-group">
                                <label for="id_cliente" class="modern-label">Cliente <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-person"></i>
                                    <select class="modern-input" id="id_cliente" name="id_cliente" required>
                                        <option value="">Seleccione un cliente</option>
                                        <?php foreach ($clientes as $cliente): ?>
                                            <option value="<?php echo htmlspecialchars($cliente['id']); ?>">
                                                <?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <h4 class="modern-label" style="margin-bottom:1.2rem; margin-top:2rem; font-size:1.2rem; border-bottom:1.5px solid #e0e7ef; padding-bottom:0.5rem;">Detalles de la Venta</h4>
                            <div id="productos-container" class="mb-3">
                                <!-- Los productos se añadirán aquí dinámicamente -->
                            </div>
                            <div class="modern-form-group" style="display: flex; gap: 1rem; align-items: flex-end;">
                                <div style="flex:2;">
                                    <label for="producto_selector" class="modern-label">Añadir Producto</label>
                                    <div class="modern-input-icon">
                                        <i class="bi bi-box"></i>
                                        <select class="modern-input" id="producto_selector">
                                            <option value="">Seleccione un producto</option>
                                            <?php foreach ($productos_disponibles as $prod): ?>
                                                <option 
                                                    value="<?php echo htmlspecialchars($prod['id']); ?>"
                                                    data-nombre="<?php echo htmlspecialchars($prod['nombre']); ?>"
                                                    data-precio="<?php echo htmlspecialchars($prod['precio']); ?>"
                                                    data-stock="<?php echo htmlspecialchars($prod['stock']); ?>"
                                                >
                                                    <?php echo htmlspecialchars($prod['nombre']); ?> (Stock: <?php echo $prod['stock']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div style="flex:1;">
                                    <label for="cantidad_producto" class="modern-label">Cantidad</label>
                                    <div class="modern-input-icon">
                                        <i class="bi bi-123"></i>
                                        <input type="number" class="modern-input" id="cantidad_producto" min="1" value="1">
                                    </div>
                                </div>
                                <div style="flex:1;">
                                    <button type="button" id="agregar_producto" class="modern-btn" style="width:100%; background:#06b6d4; color:#fff;"><i class="bi bi-plus-circle"></i> Añadir</button>
                                </div>
                            </div>
                            <div style="text-align:right; font-size:1.2rem; font-weight:600; margin:1.5rem 0 0.5rem 0;">
                                Total: <span id="total_venta">$0.00</span>
                            </div>
                            <input type="hidden" name="items_json" id="items_json">
                            <div class="modern-form-actions">
                                <button type="submit" class="modern-btn modern-btn-success"><i class="bi bi-currency-dollar"></i> Registrar Venta</button>
                                <a href="consulta.php" class="modern-btn modern-btn-secondary"><i class="bi bi-arrow-left-circle"></i> Volver</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <!-- Aquí iría el JS de gestión dinámica de productos y total -->
</body>
</html>