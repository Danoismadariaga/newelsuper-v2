<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('editar_productos');

$db = getDBConnection();

$message = '';
$message_type = '';
$producto = null;

// Obtener el ID del producto de la URL
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: consulta.php?message=Producto no especificado o inválido&type=danger');
    exit();
}

// Cargar datos del producto existente
try {
    $stmt = $db->prepare("SELECT id, nombre, descripcion, precio, stock FROM productos WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $producto = $stmt->fetch();

    if (!$producto) {
        header('Location: consulta.php?message=Producto no encontrado&type=danger');
        exit();
    }
} catch (PDOException $e) {
    registrarActividad('Error al cargar producto para edición', 'ID: ' . $id . ', Error: ' . $e->getMessage(), 'error');
    header('Location: consulta.php?message=Error de base de datos al cargar producto&type=danger');
    exit();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
    $precio = filter_var($_POST['precio'] ?? '', FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT);

    // Validaciones básicas
    if (empty($nombre) || $precio === false || $precio < 0 || $stock === false || $stock < 0) {
        $message = 'Todos los campos obligatorios (Nombre, Precio, Stock) deben ser válidos y no negativos.';
        $message_type = 'danger';
    } else {
        try {
            // Verificar si ya existe un producto con el mismo nombre (excluyendo el producto actual)
            $stmt_check = $db->prepare("SELECT COUNT(*) FROM productos WHERE nombre = :nombre AND id != :id");
            $stmt_check->bindParam(':nombre', $nombre);
            $stmt_check->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt_check->execute();
            if ($stmt_check->fetchColumn() > 0) {
                $message = 'Ya existe otro producto con este nombre.';
                $message_type = 'warning';
            } else {
                $stmt_update = $db->prepare("
                    UPDATE productos
                    SET nombre = :nombre, descripcion = :descripcion, precio = :precio, stock = :stock
                    WHERE id = :id
                ");
                $stmt_update->bindParam(':nombre', $nombre);
                $stmt_update->bindParam(':descripcion', $descripcion);
                $stmt_update->bindParam(':precio', $precio);
                $stmt_update->bindParam(':stock', $stock);
                $stmt_update->bindParam(':id', $id, PDO::PARAM_INT);

                if ($stmt_update->execute()) {
                    $message = 'Producto actualizado exitosamente.';
                    $message_type = 'success';
                    registrarActividad('Producto actualizado', 'Producto ID: ' . $id . ', Nuevo Nombre: ' . $nombre);

                    // Actualizar los datos del producto en la variable $producto para reflejar los cambios
                    $producto['nombre'] = $nombre;
                    $producto['descripcion'] = $descripcion;
                    $producto['precio'] = $precio;
                    $producto['stock'] = $stock;
                } else {
                    $message = 'Error al actualizar el producto.';
                    $message_type = 'danger';
                }
            }
        } catch (PDOException $e) {
            $message = 'Error de base de datos: ' . $e->getMessage();
            $message_type = 'danger';
            registrarActividad('Error al actualizar producto', 'Producto ID: ' . $id . ', Error: ' . $e->getMessage(), 'error');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - <?php echo APP_NAME; ?></title>
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
                        <h2 class="modern-title"><i class="bi bi-pencil-square"></i> Editar Producto: <?php echo htmlspecialchars($producto['nombre']); ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> modern-alert" role="alert">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form action="editar.php?id=<?php echo $id; ?>" method="POST" autocomplete="off">
                            <div class="modern-form-group">
                                <label for="nombre" class="modern-label">Nombre del Producto <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-box"></i>
                                    <input type="text" class="modern-input" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                                </div>
                            </div>
                            <div class="modern-form-group">
                                <label for="descripcion" class="modern-label">Descripción</label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-card-text"></i>
                                    <textarea class="modern-input" id="descripcion" name="descripcion" rows="3" style="resize:vertical;"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="modern-form-group">
                                <label for="precio" class="modern-label">Precio <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-currency-dollar"></i>
                                    <input type="number" class="modern-input" id="precio" name="precio" step="0.01" value="<?php echo htmlspecialchars($producto['precio']); ?>" required>
                                </div>
                            </div>
                            <div class="modern-form-group">
                                <label for="stock" class="modern-label">Stock <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-123"></i>
                                    <input type="number" class="modern-input" id="stock" name="stock" value="<?php echo htmlspecialchars($producto['stock']); ?>" required>
                                </div>
                            </div>
                            <div class="modern-form-actions">
                                <button type="submit" class="modern-btn modern-btn-success"><i class="bi bi-save"></i> Guardar Cambios</button>
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