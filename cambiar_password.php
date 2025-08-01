<?php
define('SECURE_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Verificar que el usuario esté autenticado
if (!isAuthenticated()) {
    redirect(APP_URL . '/login.php');
}

$error = '';
$success = '';

// Procesar el formulario de cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Por favor, complete todos los campos.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Las contraseñas nuevas no coinciden.';
    } elseif (strlen($new_password) < 8) {
        $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } else {
        // Verificar la contraseña actual
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT password FROM usuarios WHERE id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (password_verify($current_password, $user['password'])) {
            // Actualizar la contraseña
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET password = :password WHERE id = :user_id");
            $stmt->execute([
                ':password' => $new_password_hash,
                ':user_id' => $_SESSION['user_id']
            ]);

            registrarActividad('cambiar_password', 'Contraseña actualizada exitosamente');
            $success = 'Contraseña actualizada exitosamente.';
        } else {
            $error = 'La contraseña actual es incorrecta.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo APP_NAME; ?></a>
        </div>
    </nav>

    <div class="container">
        <div class="password-container">
            <h2 class="text-center mb-4">Cambiar Contraseña</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="current_password" class="form-label">Contraseña Actual</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">Nueva Contraseña</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                    <div class="form-text">La contraseña debe tener al menos 8 caracteres.</div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                    <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 