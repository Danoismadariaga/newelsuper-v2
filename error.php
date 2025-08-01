<?php
define('SECURE_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Obtener el mensaje de error de la URL
$error_msg = $_GET['msg'] ?? 'unknown';

// Definir mensajes de error
$error_messages = [
    'unauthorized' => 'No tiene permisos para acceder a esta página.',
    'session_expired' => 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.',
    'invalid_request' => 'Solicitud inválida.',
    'unknown' => 'Ha ocurrido un error inesperado.'
];

// Obtener el mensaje correspondiente
$message = $error_messages[$error_msg] ?? $error_messages['unknown'];

// Información adicional para errores de permisos
$additional_info = '';
if ($error_msg === 'unauthorized' && isAuthenticated()) {
    $additional_info = '
        <div class="alert alert-info mt-3">
            <h5>Información de Diagnóstico:</h5>
            <p><strong>Usuario:</strong> ' . ($_SESSION['user_id'] ?? 'N/A') . '</p>
            <p><strong>Rol:</strong> ' . ($_SESSION['rol_nombre'] ?? 'N/A') . '</p>
            <p><strong>Rol ID:</strong> ' . ($_SESSION['rol_id'] ?? 'N/A') . '</p>
            <div class="mt-3">
                <a href="debug_permisos.php" class="btn btn-info btn-sm">Diagnóstico de Permisos</a>
                <a href="fix_permisos.php" class="btn btn-warning btn-sm">Solución Automática</a>
            </div>
        </div>
    ';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .error-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .error-icon {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h1 class="mb-4">Error</h1>
            <p class="mb-4"><?php echo $message; ?></p>
            <?php echo $additional_info; ?>
            <div class="d-grid gap-2">
                <?php if (isAuthenticated()): ?>
                    <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary">Volver al Inicio</a>
                <?php else: ?>
                    <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-primary">Iniciar Sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 