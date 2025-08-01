<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('editar_ventas');

$db = getDBConnection();

$message = '';
$message_type = '';
$venta = null;
$detalles_venta = [];

// Obtener el ID de la venta de la URL
$id_venta = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

if (!$id_venta) {
    header('Location: consulta.php?message=Venta no especificada o inválida&type=danger');
    exit();
}

// Obtener clientes para el dropdown
$clientes = $db->query("SELECT id, nombre, apellido FROM clientes ORDER BY nombre ASC")->fetchAll();

// Obtener productos disponibles para el selector
$productos_disponibles = $db->query("SELECT id, nombre, precio, stock FROM productos ORDER BY nombre ASC")->fetchAll();

// Cargar datos de la venta existente y sus detalles
try {
    $stmt_venta = $db->prepare("
        SELECT v.id, v.id_cliente, c.nombre as cliente_nombre, c.apellido as cliente_apellido, v.fecha_venta, v.total
        FROM ventas v
        JOIN clientes c ON v.id_cliente = c.id
        WHERE v.id = :id_venta
    ");
    $stmt_venta->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
    $stmt_venta->execute();
    $venta = $stmt_venta->fetch();

    if (!$venta) {
        header('Location: consulta.php?message=Venta no encontrada&type=danger');
        exit();
    }

    $stmt_detalles = $db->prepare("
        SELECT dv.id_producto, p.nombre, dv.cantidad, dv.precio_unitario, p.stock as stock_actual_producto
        FROM detalle_ventas dv
        JOIN productos p ON dv.id_producto = p.id
        WHERE dv.id_venta = :id_venta
    ");
    $stmt_detalles->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
    $stmt_detalles->execute();
    $detalles_venta = $stmt_detalles->fetchAll();

} catch (PDOException $e) {
    registrarActividad('Error al cargar venta para edición', 'Venta ID: ' . $id_venta . ', Error: ' . $e->getMessage(), 'error');
    header('Location: consulta.php?message=Error de base de datos al cargar venta&type=danger');
    exit();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_id_cliente = filter_var($_POST['id_cliente'] ?? '', FILTER_VALIDATE_INT);
    $nuevos_items_json = $_POST['items_json'] ?? '[]';
    $nuevos_items = json_decode($nuevos_items_json, true);

    if (!$nuevo_id_cliente) {
        $message = 'Por favor, seleccione un cliente.';
        $message_type = 'danger';
    } elseif (empty($nuevos_items)) {
        $message = 'Debe añadir al menos un producto a la venta.';
        $message_type = 'danger';
    } else {
        $db->beginTransaction();
        try {
            $total_nueva_venta = 0;
            $productos_en_venta_actualizada = []; // Para llevar el control de los productos después de la actualización

            // Paso 1: Revertir stock de productos de la venta original
            foreach ($detalles_venta as $detalle_original) {
                $stmt_return_stock = $db->prepare("UPDATE productos SET stock = stock + :cantidad WHERE id = :id_producto");
                $stmt_return_stock->bindParam(':cantidad', $detalle_original['cantidad'], PDO::PARAM_INT);
                $stmt_return_stock->bindParam(':id_producto', $detalle_original['id_producto'], PDO::PARAM_INT);
                $stmt_return_stock->execute();
            }

            // Paso 2: Procesar los nuevos ítems, verificar stock y calcular nuevo total
            foreach ($nuevos_items as $item) {
                $id_producto = filter_var($item['id_producto'] ?? '', FILTER_VALIDATE_INT);
                $cantidad = filter_var($item['cantidad'] ?? '', FILTER_VALIDATE_INT);
                $precio_unitario = filter_var($item['precio_unitario'] ?? '', FILTER_VALIDATE_FLOAT);

                if (!$id_producto) {
                    throw new Exception('ID de producto inválido en los datos de la venta.');
                }
                if ($cantidad === false || $cantidad <= 0) {
                    throw new Exception('Cantidad inválida o negativa para el producto ID: ' . $id_producto . '.');
                }
                if ($precio_unitario === false || $precio_unitario <= 0) {
                    throw new Exception('Precio unitario inválido o negativo para el producto ID: ' . $id_producto . '.');
                }

                // Obtener stock actual del producto (después de haber devuelto el stock original)
                $stmt_current_stock = $db->prepare("SELECT stock FROM productos WHERE id = :id_producto");
                $stmt_current_stock->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                $stmt_current_stock->execute();
                $available_stock = $stmt_current_stock->fetchColumn();

                if ($available_stock < $cantidad) {
                    throw new Exception('Stock insuficiente para el producto ID: ' . $id_producto . '. Stock disponible: ' . $available_stock . '. Cantidad solicitada: ' . $cantidad);
                }

                $subtotal_item = $cantidad * $precio_unitario;
                $total_nueva_venta += $subtotal_item;
                $productos_en_venta_actualizada[] = [
                    'id_producto' => $id_producto,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio_unitario,
                    'subtotal' => $subtotal_item
                ];
            }
            
            // Paso 3: Actualizar la tabla de ventas principal
            $stmt_update_venta = $db->prepare("
                UPDATE ventas
                SET id_cliente = :id_cliente, total = :total
                WHERE id = :id_venta
            ");
            $stmt_update_venta->bindParam(':id_cliente', $nuevo_id_cliente, PDO::PARAM_INT);
            $stmt_update_venta->bindParam(':total', $total_nueva_venta, PDO::PARAM_STR);
            $stmt_update_venta->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
            $stmt_update_venta->execute();

            // Paso 4: Eliminar los detalles de venta antiguos
            $stmt_delete_detalles = $db->prepare("DELETE FROM detalle_ventas WHERE id_venta = :id_venta");
            $stmt_delete_detalles->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
            $stmt_delete_detalles->execute();

            // Paso 5: Insertar los nuevos detalles de venta y decrementar stock
            $stmt_insert_detalle = $db->prepare("
                INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal)
                VALUES (:id_venta, :id_producto, :cantidad, :precio_unitario, :subtotal)
            ");
            $stmt_decrement_stock = $db->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :id_producto");

            foreach ($productos_en_venta_actualizada as $detalle_nuevo) {
                $stmt_insert_detalle->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
                $stmt_insert_detalle->bindParam(':id_producto', $detalle_nuevo['id_producto'], PDO::PARAM_INT);
                $stmt_insert_detalle->bindParam(':cantidad', $detalle_nuevo['cantidad'], PDO::PARAM_INT);
                $stmt_insert_detalle->bindParam(':precio_unitario', $detalle_nuevo['precio_unitario'], PDO::PARAM_STR);
                $stmt_insert_detalle->bindParam(':subtotal', $detalle_nuevo['subtotal'], PDO::PARAM_STR);
                $stmt_insert_detalle->execute();

                $stmt_decrement_stock->bindParam(':cantidad', $detalle_nuevo['cantidad'], PDO::PARAM_INT);
                $stmt_decrement_stock->bindParam(':id_producto', $detalle_nuevo['id_producto'], PDO::PARAM_INT);
                $stmt_decrement_stock->execute();
            }

            $db->commit();
            $message = 'Venta ID ' . $id_venta . ' actualizada exitosamente.';
            $message_type = 'success';
            registrarActividad('Venta actualizada', 'Venta ID: ' . $id_venta . ', Nuevo Cliente ID: ' . $nuevo_id_cliente . ', Nuevo Total: ' . $total_nueva_venta);

            // Redirigir para limpiar el POST y mostrar el mensaje
            header('Location: consulta.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            $message = 'Error al actualizar la venta: ' . $e->getMessage();
            $message_type = 'danger';
            registrarActividad('Error al actualizar venta', 'Venta ID: ' . $id_venta . ', Error: ' . $e->getMessage(), 'error');
        }
    }
}

// Convertir detalles de venta a un formato que el JS pueda entender
$initial_selected_products = [];
foreach ($detalles_venta as $dp) {
    $initial_selected_products[$dp['id_producto']] = [
        'nombre' => $dp['nombre'],
        'precio_unitario' => (float)$dp['precio_unitario'],
        'cantidad' => (int)$dp['cantidad'],
        'stock_original' => (int)$dp['stock_actual_producto'] + (int)$dp['cantidad'] // Stock real en la DB + cantidad ya en la venta
    ];
}
$initial_selected_products_json = json_encode($initial_selected_products);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Venta - <?php echo APP_NAME; ?></title>
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
                        <h2 class="modern-title"><i class="bi bi-pencil-square"></i> Editar Venta ID: <?php echo htmlspecialchars($id_venta); ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> modern-alert" role="alert">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form id="ventaForm" action="editar.php?id=<?php echo $id_venta; ?>" method="POST" autocomplete="off">
                            <div class="modern-form-group">
                                <label for="id_cliente" class="modern-label">Cliente <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-person"></i>
                                    <select class="modern-input" id="id_cliente" name="id_cliente" required>
                                        <option value="">Seleccione un cliente</option>
                                        <?php foreach ($clientes as $cliente): ?>
                                            <option value="<?php echo htmlspecialchars($cliente['id']); ?>" <?php echo ($venta['id_cliente'] == $cliente['id']) ? 'selected' : ''; ?>>
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
                                    <label for="producto_selector" class="modern-label">Añadir o Modificar Producto</label>
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
                                    <button type="button" id="agregar_producto" class="modern-btn" style="width:100%; background:#06b6d4; color:#fff;"><i class="bi bi-plus-circle"></i> Añadir/Actualizar</button>
                                </div>
                            </div>
                            <div style="text-align:right; font-size:1.2rem; font-weight:600; margin:1.5rem 0 0.5rem 0;">
                                Total: <span id="total_venta">$0.00</span>
                            </div>
                            <input type="hidden" name="items_json" id="items_json">
                            <div class="modern-form-actions">
                                <button type="submit" class="modern-btn modern-btn-success"><i class="bi bi-save"></i> Guardar Cambios</button>
                                <a href="consulta.php" class="modern-btn modern-btn-secondary"><i class="bi bi-arrow-left-circle"></i> Volver</a>
                            </div>
                        </form>
                        <script>
                        // Inicializar productos seleccionados desde PHP
                        let selectedProducts = <?php echo $initial_selected_products_json; ?>;

                        document.addEventListener('DOMContentLoaded', function() {
                            renderSelectedProducts();
                            updateTotal();
                        });

                        document.getElementById('agregar_producto').addEventListener('click', function() {
                            const selector = document.getElementById('producto_selector');
                            const cantidadInput = document.getElementById('cantidad_producto');
                            
                            const productId = selector.value;
                            const cantidad = parseInt(cantidadInput.value);

                            if (!productId || cantidad <= 0) {
                                alert('Por favor, seleccione un producto y especifique una cantidad válida.');
                                return;
                            }

                            const selectedOption = selector.options[selector.selectedIndex];
                            const productName = selectedOption.getAttribute('data-nombre');
                            const productPrice = parseFloat(selectedOption.getAttribute('data-precio'));
                            const productStockDB = parseInt(selectedOption.getAttribute('data-stock')); // Stock actual en la DB

                            let currentQuantityInSale = selectedProducts[productId] ? selectedProducts[productId].cantidad : 0;
                            let stockAfterRemovingCurrent = productStockDB + currentQuantityInSale; // Stock disponible para este producto si lo quitáramos de la venta y lo volviéramos a añadir

                            if (cantidad > stockAfterRemovingCurrent) {
                                alert(`No hay suficiente stock para ${productName}. Disponible (real): ${productStockDB}. Total que podría usar considerando esta venta: ${stockAfterRemovingCurrent}.`);
                                return;
                            }

                            selectedProducts[productId] = {
                                nombre: productName,
                                precio_unitario: productPrice,
                                cantidad: cantidad,
                                stock_original: productStockDB // Para mostrar al usuario, aunque la lógica del backend es la importante
                            };

                            renderSelectedProducts();
                            updateTotal();
                            selector.value = ''; // Limpiar selección
                            cantidadInput.value = 1; // Resetear cantidad
                        });

                        function renderSelectedProducts() {
                            const container = document.getElementById('productos-container');
                            container.innerHTML = '';

                            for (const productId in selectedProducts) {
                                const product = selectedProducts[productId];
                                const itemTotal = (product.cantidad * product.precio_unitario).toFixed(2);
                                const productDiv = document.createElement('div');
                                productDiv.classList.add('d-flex', 'align-items-center', 'mb-2', 'p-2', 'border', 'rounded');
                                productDiv.innerHTML = `
                                    <div class="flex-grow-1">
                                        <strong>${product.nombre}</strong> <br/>
                                        Precio Unitario: $${product.precio_unitario.toFixed(2)} | Cantidad: ${product.cantidad} 
                                    </div>
                                    <div>
                                        Total Item: $${itemTotal}
                                        <button type="button" class="btn btn-sm btn-danger ms-2" data-product-id="${productId}">X</button>
                                    </div>
                                `;
                                container.appendChild(productDiv);
                            }

                            // Añadir listener para eliminar producto
                            container.querySelectorAll('button.btn-danger').forEach(button => {
                                button.addEventListener('click', function() {
                                    const productIdToDelete = this.getAttribute('data-product-id');
                                    delete selectedProducts[productIdToDelete];
                                    renderSelectedProducts();
                                    updateTotal();
                                });
                            });
                        }

                        function updateTotal() {
                            let total = 0;
                            for (const productId in selectedProducts) {
                                const product = selectedProducts[productId];
                                total += product.cantidad * product.precio_unitario;
                            }
                            document.getElementById('total_venta').textContent = total.toFixed(2);
                            document.getElementById('items_json').value = JSON.stringify(Object.keys(selectedProducts).map(id => ({
                                id_producto: id,
                                cantidad: selectedProducts[id].cantidad,
                                precio_unitario: selectedProducts[id].precio_unitario
                            })));
                        }

                        </script>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>