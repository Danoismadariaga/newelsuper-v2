<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarAcceso('gestionar_usuarios'); // Verificar permisos


$db = getDBConnection();

$message = '';
$message_type = '';

// Obtener roles para el dropdown
$roles = $db->query("SELECT id, nombre_rol FROM roles ORDER BY nombre_rol ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitizeInput($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $id_rol = filter_var($_POST['id_rol'] ?? '', FILTER_VALIDATE_INT);

    if (empty($usuario) || empty($password) || empty($confirm_password) || !$id_rol) {
        $message = 'Todos los campos marcados con * son obligatorios.';
        $message_type = 'danger';
    } elseif ($password !== $confirm_password) {
        $message = 'Las contraseñas no coinciden.';
        $message_type = 'danger';
    } elseif (strlen($password) < 6) {
        $message = 'La contraseña debe tener al menos 6 caracteres.';
        $message_type = 'danger';
    } else {
        try {
            // Verificar si el nombre de usuario ya existe
            $stmt_check_user = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario");
            $stmt_check_user->bindParam(':usuario', $usuario, PDO::PARAM_STR);
            $stmt_check_user->execute();
            if ($stmt_check_user->fetchColumn() > 0) {
                throw new Exception('El nombre de usuario ya está en uso.');
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO usuarios (usuario, password, id_rol) VALUES (:usuario, :password, :id_rol)");
            $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
            $stmt->execute();

            $message = 'Usuario creado exitosamente.';
            $message_type = 'success';
            registrarActividad('Creación de Usuario', 'Usuario: ' . $usuario . ', Rol ID: ' . $id_rol);
            // Limpiar campos
            $_POST = [];
        } catch (PDOException $e) {
            $message = 'Error de base de datos: ' . $e->getMessage();
            $message_type = 'danger';
            registrarActividad('Error al crear usuario', 'Error: ' . $e->getMessage(), 'error');
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
            registrarActividad('Error al crear usuario', 'Error: ' . $e->getMessage(), 'error');
        }
    }
}

// Obtener información del usuario actual para el navbar
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("
        SELECT e.nombre, e.apellido, r.nombre_rol
        FROM usuarios u
        LEFT JOIN empleados e ON u.id = e.id_usuario
        JOIN roles r ON u.id_rol = r.id
        WHERE u.id = :user_id
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user_info = $stmt->fetch();

    if (!$user_info) {
        $stmt = $db->prepare("
            SELECT u.usuario as nombre, r.nombre_rol
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id
            WHERE u.id = :user_id
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user_info = $stmt->fetch();
        $user_info['apellido'] = '';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario - <?php echo APP_NAME; ?></title>
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
                        <h2 class="modern-title"><i class="bi bi-person-plus"></i> Crear Nuevo Usuario</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> modern-alert" role="alert">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form action="crear.php" method="POST" autocomplete="off">
                            <div class="modern-form-group">
                                <label for="usuario" class="modern-label">Nombre de Usuario <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-person"></i>
                                    <input type="text" class="modern-input" id="usuario" name="usuario" value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="modern-form-group">
                                <label for="password" class="modern-label">Contraseña <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-lock"></i>
                                    <input type="password" class="modern-input" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="modern-form-group">
                                <label for="confirm_password" class="modern-label">Confirmar Contraseña <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-lock-fill"></i>
                                    <input type="password" class="modern-input" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            <div class="modern-form-group">
                                <label for="id_rol" class="modern-label">Rol <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-people"></i>
                                    <select class="modern-input" id="id_rol" name="id_rol" required>
                                        <option value="">Seleccione un rol</option>
                                        <?php foreach ($roles as $rol): ?>
                                            <option value="<?php echo htmlspecialchars($rol['id']); ?>"
                                                <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == $rol['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modern-form-actions">
                                <button type="submit" class="modern-btn modern-btn-success"><i class="bi bi-person-plus"></i> Crear Usuario</button>
                                <a href="consulta.php" class="modern-btn modern-btn-secondary"><i class="bi bi-arrow-left-circle"></i> Volver a Usuarios</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 