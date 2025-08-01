<?php
// Prevenir acceso directo al archivo
if (!defined('SECURE_ACCESS')) {
    header('Location: ../error.php');
    exit();
}

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_gestion');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Gestión El Super');
define('APP_URL', 'http://localhost/newelsuper');

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS

// Función para obtener la conexión PDO
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Error de conexión: " . $e->getMessage());
        die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
    }
}

// Función para sanitizar entrada de datos
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para redireccionar
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Función para mostrar mensajes de error
function showError($message) {
    return "<div class='alert alert-danger' role='alert'>{$message}</div>";
}

// Función para mostrar mensajes de éxito
function showSuccess($message) {
    return "<div class='alert alert-success' role='alert'>{$message}</div>";
} 