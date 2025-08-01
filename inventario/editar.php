<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('editar_movimientos_inventario');

$db = getDBConnection();

$message = '';
$message_type = '';

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: consulta.php?message=ID de movimiento de inventario no válido.&type=danger');
    exit();
}

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

// Obtener datos del movimiento de inventario a editar
$stmt = $db->prepare("SELECT id_producto, tipo_movimiento, cantidad FROM inventario WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$movimiento_original = $stmt->fetch();

if (!$movimiento_original) {
    header('Location: consulta.php?message=Movimiento de inventario no encontrado.&type=danger');
    exit();
}

$id_producto_original = $movimiento_original['id_producto'];
$tipo_movimiento_original = $movimiento_original['tipo_movimiento'];
$cantidad_original = $movimiento_original['cantidad'];

// Obtener productos para el dropdown
$productos = $db->query("SELECT id, nombre, stock FROM productos ORDER BY nombre ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto_new = filter_var($_POST['id_producto'] ?? '', FILTER_VALIDATE_INT);
    $tipo_movimiento_new = sanitizeInput($_POST['tipo_movimiento'] ?? '');
    $cantidad_new = filter_var($_POST['cantidad'] ?? '', FILTER_VALIDATE_INT);

    if (!$id_producto_new) {
        $message = 'Por favor, seleccione un producto.';
        $message_type = 'danger';
    } elseif (!in_array($tipo_movimiento_new, ['ENTRADA', 'SALIDA'])) {
        $message = 'Tipo de movimiento no válido.';
        $message_type = 'danger';
    } elseif ($cantidad_new === false || $cantidad_new <= 0) {
        $message = 'La cantidad debe ser un número entero positivo.';
        $message_type = 'danger';
    } else {
        $db->beginTransaction();
        try {
            // 1. Revertir el stock original
            $stmt_revert_stock = $db->prepare("UPDATE productos SET stock = stock " . ($tipo_movimiento_original == 'ENTRADA' ? '- ' : '+ ') . ":cantidad_original WHERE id = :id_producto_original");
            $stmt_revert_stock->bindParam(':cantidad_original', $cantidad_original, PDO::PARAM_INT);
            $stmt_revert_stock->bindParam(':id_producto_original', $id_producto_original, PDO::PARAM_INT);
            $stmt_revert_stock->execute();

            // 2. Aplicar el nuevo stock
            // Si el producto cambió, necesitamos verificar el stock del nuevo producto también.
            // Si el producto no cambia, el stock ya fue revertido y ahora se aplica el nuevo.
            if ($id_producto_new != $id_producto_original || $tipo_movimiento_new == 'SALIDA') {
                $stmt_check_stock_new = $db->prepare("SELECT stock FROM productos WHERE id = :id_producto_new");
                $stmt_check_stock_new->bindParam(':id_producto_new', $id_producto_new, PDO::PARAM_INT);
                $stmt_check_stock_new->execute();
                $current_stock_new_product = $stmt_check_stock_new->fetchColumn();

                if ($tipo_movimiento_new == 'SALIDA' && $current_stock_new_product < $cantidad_new) {
                    throw new Exception('Stock insuficiente para la nueva salida. Solo hay ' . $current_stock_new_product . ' unidades disponibles para el producto seleccionado.');
                }
            }
            
            $stmt_apply_new_stock = $db->prepare("UPDATE productos SET stock = stock " . ($tipo_movimiento_new == 'ENTRADA' ? '+ ' : '- ') . ":cantidad_new WHERE id = :id_producto_new");
            $stmt_apply_new_stock->bindParam(':cantidad_new', $cantidad_new, PDO::PARAM_INT);
            $stmt_apply_new_stock->bindParam(':id_producto_new', $id_producto_new, PDO::PARAM_INT);
            $stmt_apply_new_stock->execute();

            // 3. Actualizar el registro del movimiento de inventario
            $stmt_update_movimiento = $db->prepare("
                UPDATE inventario
                SET id_producto = :id_producto_new,
                    tipo_movimiento = :tipo_movimiento_new,
                    cantidad = :cantidad_new,
                    id_empleado_responsable = :id_empleado_responsable,
                    fecha_movimiento = NOW()
                WHERE id = :id
            ");
            $stmt_update_movimiento->bindParam(':id_producto_new', $id_producto_new, PDO::PARAM_INT);
            $stmt_update_movimiento->bindParam(':tipo_movimiento_new', $tipo_movimiento_new, PDO::PARAM_STR);
            $stmt_update_movimiento->bindParam(':cantidad_new', $cantidad_new, PDO::PARAM_INT);
            $stmt_update_movimiento->bindParam(':id_empleado_responsable', $id_empleado_responsable, PDO::PARAM_INT);
            $stmt_update_movimiento->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt_update_movimiento->execute();

            $db->commit();
            $message = 'Movimiento de inventario actualizado exitosamente.';
            $message_type = 'success';
            registrarActividad('Edición de Movimiento de Inventario', 'Movimiento ID: ' . $id . ', Nuevo Producto ID: ' . $id_producto_new . ', Nuevo Tipo: ' . $tipo_movimiento_new . ', Nueva Cantidad: ' . $cantidad_new);
            
            // Actualizar variables para mostrar en el formulario
            $id_producto_original = $id_producto_new;
            $tipo_movimiento_original = $tipo_movimiento_new;
            $cantidad_original = $cantidad_new;

        } catch (Exception $e) {
            $db->rollBack();
            $message = 'Error al actualizar el movimiento de inventario: ' . $e->getMessage();
            $message_type = 'danger';
            registrarActividad('Error al editar movimiento de inventario', 'Error: ' . $e->getMessage(), 'error');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Movimiento de Inventario - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="consulta.php">Inventario</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Movimiento
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="crear_entrada.php">Registrar Entrada</a></li>
                            <li><a class="dropdown-item" href="crear_salida.php">Registrar Salida</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="mb-4">Editar Movimiento de Inventario #<?php echo htmlspecialchars($id); ?></h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form action="editar.php?id=<?php echo htmlspecialchars($id); ?>" method="POST">
                    <div class="mb-3">
                        <label for="id_producto" class="form-label">Producto <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_producto" name="id_producto" required>
                            <option value="">Seleccione un producto</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?php echo htmlspecialchars($producto['id']); ?>" <?php echo ($producto['id'] == $id_producto_original) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($producto['nombre'] . ' (Stock actual: ' . $producto['stock'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tipo_movimiento" class="form-label">Tipo de Movimiento <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipo_movimiento" name="tipo_movimiento" required>
                            <option value="ENTRADA" <?php echo ($tipo_movimiento_original == 'ENTRADA') ? 'selected' : ''; ?>>Entrada</option>
                            <option value="SALIDA" <?php echo ($tipo_movimiento_original == 'SALIDA') ? 'selected' : ''; ?>>Salida</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" value="<?php echo htmlspecialchars($cantidad_original); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Cambios</button>
                    <a href="consulta.php" class="btn btn-secondary ms-2"><i class="bi bi-arrow-left-circle"></i> Volver</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 