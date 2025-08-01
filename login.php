<?php
define('SECURE_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Si ya está autenticado, redirigir al index
if (isAuthenticated()) {
    redirect(APP_URL . '/index.php');
}

$error = '';

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitizeInput($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        if (iniciarSesion($usuario, $password)) {
            redirect(APP_URL . '/index.php');
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Montserrat', Arial, sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 40px auto;
            padding: 32px 28px 24px 28px;
            background-color: rgba(255,255,255,0.97);
            border-radius: 18px;
            box-shadow: 0 8px 32px 0 rgba(31,38,135,0.2);
            animation: fadeIn 1s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .login-header h1 {
            font-size: 2rem;
            color: #2575fc;
            font-family: 'Montserrat', Arial, sans-serif;
            font-weight: 700;
            margin-bottom: 0.2em;
        }
        .login-logo {
            width: 60px;
            height: 60px;
            margin-bottom: 10px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #fff;
            box-shadow: 0 2px 8px rgba(31,38,135,0.15);
            margin-left: auto;
            margin-right: auto;
        }
        .form-control:focus {
            border-color: #2575fc;
            box-shadow: 0 0 0 0.2rem rgba(37,117,252,0.15);
        }
        .show-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #2575fc;
            font-size: 1.1rem;
            cursor: pointer;
        }
        .footer {
            text-align: center;
            margin-top: 24px;
            color: #888;
            font-size: 0.95em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <div class="login-logo">
                    <span>🔒</span>
                </div>
                <h1><?php echo APP_NAME; ?></h1>
                <p>Iniciar Sesión</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" required autofocus>
                </div>
                <div class="mb-3 position-relative">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button type="button" class="show-password" tabindex="-1" onclick="togglePassword()" aria-label="Mostrar/Ocultar contraseña">
                        <span id="eyeIcon">👁️</span>
                    </button>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                </div>
            </form>
            <div class="footer mt-4">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos los derechos reservados.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const eye = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                eye.textContent = '🙈';
            } else {
                pwd.type = 'password';
                eye.textContent = '👁️';
            }
        }
    </script>
</body>
</html> 