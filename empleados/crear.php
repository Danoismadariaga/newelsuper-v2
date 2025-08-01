<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('crear_empleados');

$error = '';
$success = '';

// Obtener roles disponibles
$db = getDBConnection();
$stmt = $db->query("SELECT id, nombre_rol FROM roles ORDER BY nombre_rol");
$roles = $stmt->fetchAll();

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Datos del empleado
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $apellido = sanitizeInput($_POST['apellido'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefono = sanitizeInput($_POST['telefono'] ?? '');
    $puesto = sanitizeInput($_POST['puesto'] ?? '');
    $fecha_contratacion = sanitizeInput($_POST['fecha_contratacion'] ?? '');
    
    // Datos del usuario
    $usuario = sanitizeInput($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $id_rol = (int)($_POST['id_rol'] ?? 0);

    // Validaciones
    if (empty($nombre) || empty($apellido) || empty($usuario) || empty($password) || empty($confirm_password) || empty($id_rol)) {
        $error = 'Todos los campos marcados con * son obligatorios.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        try {
            $db->beginTransaction();

            // Verificar si el email ya existe
            if (!empty($email)) {
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM empleados WHERE email = :email");
                $stmt->execute([':email' => $email]);
                if ($stmt->fetch()['total'] > 0) {
                    throw new Exception('El email ya está registrado.');
                }
            }

            // Verificar si el usuario ya existe
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE usuario = :usuario");
            $stmt->execute([':usuario' => $usuario]);
            if ($stmt->fetch()['total'] > 0) {
                throw new Exception('El nombre de usuario ya está en uso.');
            }

            // Crear usuario
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO usuarios (usuario, password, id_rol)
                VALUES (:usuario, :password, :id_rol)
            ");

            $stmt->execute([
                ':usuario' => $usuario,
                ':password' => $password_hash,
                ':id_rol' => $id_rol
            ]);

            $id_usuario = $db->lastInsertId();

            // Crear empleado
            $stmt = $db->prepare("
                INSERT INTO empleados (nombre, apellido, email, telefono, puesto, fecha_contratacion, id_usuario)
                VALUES (:nombre, :apellido, :email, :telefono, :puesto, :fecha_contratacion, :id_usuario)
            ");

            $stmt->execute([
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':email' => $email,
                ':telefono' => $telefono,
                ':puesto' => $puesto,
                ':fecha_contratacion' => $fecha_contratacion,
                ':id_usuario' => $id_usuario
            ]);

            $db->commit();
            registrarActividad('crear_empleado', "Empleado creado: $nombre $apellido");
            $success = 'Empleado creado exitosamente.';
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
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
    <title>Nuevo Empleado - <?php echo APP_NAME; ?></title>
    <link href="../css/modern.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include_once '../includes/navbar.php'; ?>
    <div class="dashboard-container">
        <?php include_once '../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="centered-card">
                <div class="card modern-card" style="max-width: 700px;">
                    <div class="card-header modern-card-header">
                        <h2 class="modern-title"><i class="bi bi-person-plus"></i> Nuevo Empleado</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger modern-alert"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success modern-alert">
                                <?php echo $success; ?>
                                <div class="modern-form-actions">
                                    <a href="consulta.php" class="modern-btn modern-btn-success"><i class="bi bi-people"></i> Ver Lista de Empleados</a>
                                    <a href="crear.php" class="modern-btn modern-btn-secondary"><i class="bi bi-person-plus"></i> Crear Otro Empleado</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" autocomplete="off">
                                <h4 class="modern-label" style="margin-bottom:1.2rem; font-size:1.2rem; border-bottom:1.5px solid #e0e7ef; padding-bottom:0.5rem;">Datos Personales</h4>
                                <div class="modern-form-group" style="display: flex; gap: 1rem;">
                                    <div style="flex:1;">
                                        <label for="nombre" class="modern-label">Nombre *</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-person"></i>
                                            <input type="text" class="modern-input" id="nombre" name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div style="flex:1;">
                                        <label for="apellido" class="modern-label">Apellido *</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-person"></i>
                                            <input type="text" class="modern-input" id="apellido" name="apellido" value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modern-form-group" style="display: flex; gap: 1rem;">
                                    <div style="flex:1;">
                                        <label for="email" class="modern-label">Email</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-envelope"></i>
                                            <input type="email" class="modern-input" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div style="flex:1;">
                                        <label for="telefono" class="modern-label">Teléfono</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-telephone"></i>
                                            <input type="tel" class="modern-input" id="telefono" name="telefono" value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="modern-form-group" style="display: flex; gap: 1rem;">
                                    <div style="flex:1;">
                                        <label for="puesto" class="modern-label">Puesto *</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-briefcase"></i>
                                            <input type="text" class="modern-input" id="puesto" name="puesto" value="<?php echo htmlspecialchars($_POST['puesto'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div style="flex:1;">
                                        <label for="fecha_contratacion" class="modern-label">Fecha de Contratación *</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-calendar"></i>
                                            <input type="date" class="modern-input" id="fecha_contratacion" name="fecha_contratacion" value="<?php echo htmlspecialchars($_POST['fecha_contratacion'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <h4 class="modern-label" style="margin-bottom:1.2rem; margin-top:2rem; font-size:1.2rem; border-bottom:1.5px solid #e0e7ef; padding-bottom:0.5rem;">Datos de Usuario</h4>
                                <div class="modern-form-group" style="display: flex; gap: 1rem;">
                                    <div style="flex:1;">
                                        <label for="usuario" class="modern-label">Usuario *</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-person-circle"></i>
                                            <input type="text" class="modern-input" id="usuario" name="usuario" value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div style="flex:1;">
                                        <label for="id_rol" class="modern-label">Rol *</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-people"></i>
                                            <select class="modern-input" id="id_rol" name="id_rol" required>
                                                <option value="">Seleccione un rol</option>
                                                <?php foreach ($roles as $rol): ?>
                                                    <option value="<?php echo htmlspecialchars($rol['id']); ?>" <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == $rol['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($rol['nombre_rol']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modern-form-group" style="display: flex; gap: 1rem;">
                                    <div style="flex:1;">
                                        <label for="password" class="modern-label">Contraseña *</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-lock"></i>
                                            <input type="password" class="modern-input" id="password" name="password" required>
                                        </div>
                                        <span class="modern-label" style="font-size:0.95em; color:var(--color-muted); font-weight:400;">La contraseña debe tener al menos 8 caracteres.</span>
                                    </div>
                                    <div style="flex:1;">
                                        <label for="confirm_password" class="modern-label">Confirmar Contraseña *</label>
                                        <div class="modern-input-icon">
                                            <i class="bi bi-lock-fill"></i>
                                            <input type="password" class="modern-input" id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modern-form-actions">
                                    <button type="submit" class="modern-btn modern-btn-success"><i class="bi bi-person-plus"></i> Crear Empleado</button>
                                    <a href="consulta.php" class="modern-btn modern-btn-secondary"><i class="bi bi-arrow-left-circle"></i> Cancelar</a>
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