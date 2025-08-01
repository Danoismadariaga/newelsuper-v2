<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('editar_clientes');

$error = '';
$success = '';
$cliente = null;

// Obtener ID del cliente
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect('consulta.php');
}

// Obtener datos del cliente
$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id");
$stmt->execute([':id' => $id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    redirect('consulta.php');
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $apellido = sanitizeInput($_POST['apellido'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefono = sanitizeInput($_POST['telefono'] ?? '');
    $direccion = sanitizeInput($_POST['direccion'] ?? '');

    // Validaciones
    if (empty($nombre) || empty($apellido)) {
        $error = 'El nombre y apellido son obligatorios.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } else {
        try {
            // Verificar si el email ya existe (excluyendo el cliente actual)
            if (!empty($email)) {
                $stmt = $db->prepare("
                    SELECT COUNT(*) as total 
                    FROM clientes 
                    WHERE email = :email AND id != :id
                ");
                $stmt->execute([
                    ':email' => $email,
                    ':id' => $id
                ]);
                if ($stmt->fetch()['total'] > 0) {
                    $error = 'El email ya está registrado por otro cliente.';
                }
            }

            if (empty($error)) {
                // Actualizar el cliente
                $stmt = $db->prepare("
                    UPDATE clientes 
                    SET nombre = :nombre,
                        apellido = :apellido,
                        email = :email,
                        telefono = :telefono,
                        direccion = :direccion
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':nombre' => $nombre,
                    ':apellido' => $apellido,
                    ':email' => $email,
                    ':telefono' => $telefono,
                    ':direccion' => $direccion,
                    ':id' => $id
                ]);

                registrarActividad('editar_cliente', "Cliente actualizado: $nombre $apellido");
                $success = 'Cliente actualizado exitosamente.';
                
                // Actualizar datos en la variable $cliente
                $cliente = [
                    'id' => $id,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'email' => $email,
                    'telefono' => $telefono,
                    'direccion' => $direccion
                ];
            }
        } catch (PDOException $e) {
            $error = 'Error al actualizar el cliente. Por favor, intente nuevamente.';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - <?php echo APP_NAME; ?></title>
    <link href="../css/modern.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include_once '../includes/navbar.php'; ?>
    <div class="dashboard-container">
        <?php include_once '../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="centered-card">
                <div class="card modern-card">
                    <div class="card-header modern-card-header">
                        <h2 class="modern-title"><i class="bi bi-person-lines-fill"></i> Editar Cliente</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger modern-alert"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success modern-alert">
                                <?php echo $success; ?>
                                <div class="modern-form-actions">
                                    <a href="consulta.php" class="modern-btn modern-btn-success"><i class="bi bi-arrow-left-circle"></i> Volver a la Lista</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" autocomplete="off">
                                <div class="modern-form-group" style="display: flex; gap: 1rem;">
                                    <div style="flex:1;">
                                        <label for="nombre" class="modern-label">Nombre *</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-person"></i>
                                            <input type="text" class="modern-input" id="nombre" name="nombre" value="<?php echo htmlspecialchars($cliente['nombre']); ?>" required>
                                        </div>
                                    </div>
                                    <div style="flex:1;">
                                        <label for="apellido" class="modern-label">Apellido *</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-person"></i>
                                            <input type="text" class="modern-input" id="apellido" name="apellido" value="<?php echo htmlspecialchars($cliente['apellido']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modern-form-group" style="display: flex; gap: 1rem;">
                                    <div style="flex:1;">
                                        <label for="email" class="modern-label">Email</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-envelope"></i>
                                            <input type="email" class="modern-input" id="email" name="email" value="<?php echo htmlspecialchars($cliente['email']); ?>">
                                        </div>
                                    </div>
                                    <div style="flex:1;">
                                        <label for="telefono" class="modern-label">Teléfono</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-telephone"></i>
                                            <input type="tel" class="modern-input" id="telefono" name="telefono" value="<?php echo htmlspecialchars($cliente['telefono']); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="modern-form-group">
                                    <label for="direccion" class="modern-label">Dirección</label>
                                    <div class="modern-input-icon">
                                        <i class="bi bi-geo-alt"></i>
                                        <textarea class="modern-input" id="direccion" name="direccion" rows="2" style="resize:vertical;"><?php echo htmlspecialchars($cliente['direccion']); ?></textarea>
                                    </div>
                                </div>
                                <div class="modern-form-actions">
                                    <button type="submit" class="modern-btn modern-btn-success"><i class="bi bi-save"></i> Guardar Cambios</button>
                                    <a href="consulta.php" class="modern-btn modern-btn-secondary"><i class="bi bi-x-circle"></i> Cancelar</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 