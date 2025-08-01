<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('crear_productos');

$db = getDBConnection();

$message = '';
$message_type = '';

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
            // Verificar si ya existe un producto con el mismo nombre
            $stmt_check = $db->prepare("SELECT COUNT(*) FROM productos WHERE nombre = :nombre");
            $stmt_check->bindParam(':nombre', $nombre);
            $stmt_check->execute();
            if ($stmt_check->fetchColumn() > 0) {
                $message = 'Ya existe un producto con este nombre.';
                $message_type = 'warning';
            } else {
                $stmt = $db->prepare("
                    INSERT INTO productos (nombre, descripcion, precio, stock, fecha_registro)
                    VALUES (:nombre, :descripcion, :precio, :stock, NOW())
                ");
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':precio', $precio);
                $stmt->bindParam(':stock', $stock);

                if ($stmt->execute()) {
                    $message = 'Producto creado exitosamente.';
                    $message_type = 'success';
                    registrarActividad('Producto creado', 'Producto ID: ' . $db->lastInsertId() . ', Nombre: ' . $nombre);
                    // Limpiar campos después de un éxito
                    $nombre = '';
                    $descripcion = '';
                    $precio = '';
                    $stock = '';
                } else {
                    $message = 'Error al crear el producto.';
                    $message_type = 'danger';
                }
            }
        } catch (PDOException $e) {
            $message = 'Error de base de datos: ' . $e->getMessage();
            $message_type = 'danger';
            registrarActividad('Error al crear producto', 'Error: ' . $e->getMessage(), 'error');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Producto - <?php echo APP_NAME; ?></title>
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
                        <h2 class="modern-title"><i class="bi bi-box-seam"></i> Crear Nuevo Producto</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> modern-alert" role="alert">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form action="crear.php" method="POST" autocomplete="off">
                            <div class="modern-form-group">
                                <label for="nombre" class="modern-label">Nombre del Producto <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-box"></i>
                                    <input type="text" class="modern-input" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="modern-form-group">
                                <label for="descripcion" class="modern-label">Descripción</label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-card-text"></i>
                                    <textarea class="modern-input" id="descripcion" name="descripcion" rows="3" style="resize:vertical;"><?php echo htmlspecialchars($descripcion ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="modern-form-group">
                                <label for="precio" class="modern-label">Precio <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-currency-dollar"></i>
                                    <input type="number" class="modern-input" id="precio" name="precio" step="0.01" value="<?php echo htmlspecialchars($precio ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="modern-form-group">
                                <label for="stock" class="modern-label">Stock <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-123"></i>
                                    <input type="number" class="modern-input" id="stock" name="stock" value="<?php echo htmlspecialchars($stock ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="modern-form-actions">
                                <button type="submit" class="modern-btn modern-btn-success"><i class="bi bi-plus-circle"></i> Crear Producto</button>
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