<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('gestionar_usuarios');

$db = getDBConnection();

$message = '';
$message_type = '';

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: consulta.php?message=ID de usuario no válido.&type=danger');
    exit();
}

// Obtener datos del usuario a editar
$stmt_user = $db->prepare("SELECT id, usuario, id_rol FROM usuarios WHERE id = :id");
$stmt_user->bindParam(':id', $id, PDO::PARAM_INT);
$stmt_user->execute();
$usuario_actual = $stmt_user->fetch();

if (!$usuario_actual) {
    header('Location: consulta.php?message=Usuario no encontrado.&type=danger');
    exit();
}

// Obtener roles para el dropdown
$roles = $db->query("SELECT id, nombre_rol FROM roles ORDER BY nombre_rol ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_new = sanitizeInput($_POST['usuario'] ?? '');
    $id_rol_new = filter_var($_POST['id_rol'] ?? '', FILTER_VALIDATE_INT);
    $password_new = $_POST['password'] ?? '';
    $confirm_password_new = $_POST['confirm_password'] ?? '';

    if (empty($usuario_new) || !$id_rol_new) {
        $message = 'Todos los campos marcados con * son obligatorios.';
        $message_type = 'danger';
    } elseif (!empty($password_new) && $password_new !== $confirm_password_new) {
        $message = 'Las nuevas contraseñas no coinciden.';
        $message_type = 'danger';
    } elseif (!empty($password_new) && strlen($password_new) < 6) {
        $message = 'La nueva contraseña debe tener al menos 6 caracteres.';
        $message_type = 'danger';
    } else {
        try {
            // Verificar si el nombre de usuario ya existe (excluyendo al usuario actual)
            $stmt_check_user = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario AND id != :id");
            $stmt_check_user->bindParam(':usuario', $usuario_new, PDO::PARAM_STR);
            $stmt_check_user->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt_check_user->execute();
            if ($stmt_check_user->fetchColumn() > 0) {
                throw new Exception('El nombre de usuario ya está en uso.');
            }

            $sql = "UPDATE usuarios SET usuario = :usuario, id_rol = :id_rol";
            $params = [
                ':usuario' => $usuario_new,
                ':id_rol' => $id_rol_new,
                ':id' => $id
            ];

            if (!empty($password_new)) {
                $hashed_password = password_hash($password_new, PASSWORD_DEFAULT);
                $sql .= ", password = :password";
                $params[':password'] = $hashed_password;
            }

            $sql .= " WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $message = 'Usuario actualizado exitosamente.';
            $message_type = 'success';
            registrarActividad('Edición de Usuario', 'Usuario ID: ' . $id . ', Nuevo Usuario: ' . $usuario_new . ', Nuevo Rol ID: ' . $id_rol_new);
            
            // Actualizar datos del usuario para mostrar en el formulario
            $usuario_actual['usuario'] = $usuario_new;
            $usuario_actual['id_rol'] = $id_rol_new;

        } catch (PDOException $e) {
            $message = 'Error de base de datos: ' . $e->getMessage();
            $message_type = 'danger';
            registrarActividad('Error al editar usuario', 'Error: ' . $e->getMessage(), 'error');
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
            registrarActividad('Error al editar usuario', 'Error: ' . $e->getMessage(), 'error');
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
    <title>Editar Usuario - <?php echo APP_NAME; ?></title>
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
                        <h2 class="modern-title"><i class="bi bi-person-gear"></i> Editar Usuario: <?php echo htmlspecialchars($usuario_actual['usuario']); ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> modern-alert" role="alert">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form action="editar.php?id=<?php echo htmlspecialchars($id); ?>" method="POST" autocomplete="off">
                            <div class="modern-form-group">
                                <label for="usuario" class="modern-label">Nombre de Usuario <span class="required">*</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-person"></i>
                                    <input type="text" class="modern-input" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario_actual['usuario']); ?>" required>
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
                                                <?php echo ($usuario_actual['id_rol'] == $rol['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modern-form-group">
                                <label for="password" class="modern-label">Nueva Contraseña <span class="modern-label" style="font-weight:400; color:var(--color-muted);">(dejar en blanco para no cambiar)</span></label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-lock"></i>
                                    <input type="password" class="modern-input" id="password" name="password">
                                </div>
                            </div>
                            <div class="modern-form-group">
                                <label for="confirm_password" class="modern-label">Confirmar Nueva Contraseña</label>
                                <div class="modern-input-icon">
                                    <i class="bi bi-lock-fill"></i>
                                    <input type="password" class="modern-input" id="confirm_password" name="confirm_password">
                                </div>
                            </div>
                            <div class="modern-form-actions">
                                <button type="submit" class="modern-btn modern-btn-success"><i class="bi bi-save"></i> Guardar Cambios</button>
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