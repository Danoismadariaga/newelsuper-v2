<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('registrar_salida_inventario');

$db = getDBConnection();

$message = '';
$message_type = '';

// Obtener el ID del empleado logueado
$id_empleado_responsable = null;
if (isset($_SESSION['user_id'])) {
    $stmt_empleado = $db->prepare("SELECT id FROM empleados WHERE id_usuario = :user_id");
    $stmt_empleado->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt_empleado->execute();
    $id_empleado_responsable = $stmt_empleado->fetchColumn();
}

if (!$id_empleado_responsable) {
    registrarActividad('Error en Inventario', 'No se pudo obtener id_empleado para user_id: ' . ($_SESSION['user_id'] ?? 'N/A'), 'error');
    header('Location: consulta.php?message=Error: No se pudo identificar al empleado responsable.&type=danger');
    exit();
}

// Obtener productos para el dropdown (solo los que tienen stock > 0 para evitar errores obvios)
$productos = $db->query("SELECT id, nombre, stock FROM productos ORDER BY nombre ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = filter_var($_POST['id_producto'] ?? '', FILTER_VALIDATE_INT);
    $cantidad = filter_var($_POST['cantidad'] ?? '', FILTER_VALIDATE_INT);

    if (!$id_producto) {
        $message = 'Por favor, seleccione un producto.';
        $message_type = 'danger';
    } elseif ($cantidad === false || $cantidad <= 0) {
        $message = 'La cantidad debe ser un número entero positivo.';
        $message_type = 'danger';
    } else {
        $db->beginTransaction();
        try {
            // Verificar stock actual del producto
            $stmt_check_stock = $db->prepare("SELECT stock FROM productos WHERE id = :id_producto");
            $stmt_check_stock->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmt_check_stock->execute();
            $current_stock = $stmt_check_stock->fetchColumn();

            if ($current_stock < $cantidad) {
                throw new Exception('Stock insuficiente. Solo hay ' . $current_stock . ' unidades disponibles para este producto.');
            }

            // Actualizar stock del producto (restar)
            $stmt_update_stock = $db->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :id_producto");
            $stmt_update_stock->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt_update_stock->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmt_update_stock->execute();

            // Registrar movimiento de inventario (Salida)
            $stmt_movimiento = $db->prepare("
                INSERT INTO inventario (id_producto, tipo_movimiento, cantidad, id_empleado_responsable, fecha_movimiento)
                VALUES (:id_producto, 'SALIDA', :cantidad, :id_empleado_responsable, NOW())
            ");
            $stmt_movimiento->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmt_movimiento->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt_movimiento->bindParam(':id_empleado_responsable', $id_empleado_responsable, PDO::PARAM_INT);
            $stmt_movimiento->execute();

            $db->commit();
            $message = 'Salida de inventario registrada exitosamente.';
            $message_type = 'success';
            registrarActividad('Salida de Inventario', 'Producto ID: ' . $id_producto . ', Cantidad: ' . $cantidad);
            // Limpiar campos
            $_POST = [];
        } catch (Exception $e) {
            $db->rollBack();
            $message = 'Error al registrar la salida de inventario: ' . $e->getMessage();
            $message_type = 'danger';
            registrarActividad('Error al registrar salida de inventario', 'Error: ' . $e->getMessage(), 'error');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Salida de Inventario - <?php echo APP_NAME; ?></title>
    <link href="../css/modern.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include_once '../includes/navbar.php'; ?>
    <div class="dashboard-container">
        <?php include_once '../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="centered-card">
                <div class="card modern-card" style="max-width: 600px;">
                    <div class="card-header modern-card-header">
                        <h2 class="modern-title"><i class="bi bi-box-arrow-out-right"></i> Registrar Salida de Inventario</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> modern-alert" role="alert">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form action="crear_salida.php" method="POST" autocomplete="off">
                            <div class="modern-form-group">
                                <label for="id_producto" class="modern-label">Producto <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-box"></i>
                                    <select class="modern-input" id="id_producto" name="id_producto" required>
                                        <option value="">Seleccione un producto</option>
                                        <?php foreach ($productos as $producto): ?>
                                            <option value="<?php echo htmlspecialchars($producto['id']); ?>">
                                                <?php echo htmlspecialchars($producto['nombre'] . ' (Stock actual: ' . $producto['stock'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modern-form-group">
                                <label for="cantidad" class="modern-label">Cantidad de Salida <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-dash-square"></i>
                                    <input type="number" class="modern-input" id="cantidad" name="cantidad" min="1" value="<?php echo htmlspecialchars($_POST['cantidad'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="modern-form-actions">
                                <button type="submit" class="modern-btn" style="background:var(--color-danger); color:#fff;"><i class="bi bi-box-arrow-out-right"></i> Registrar Salida</button>
                                <a href="consulta.php" class="modern-btn modern-btn-secondary"><i class="bi bi-arrow-left-circle"></i> Volver</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 