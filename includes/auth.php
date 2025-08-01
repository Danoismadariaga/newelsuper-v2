<?php
// Prevenir acceso directo al archivo
if (!defined('SECURE_ACCESS')) {
    header('Location: ../error.php');
    exit();
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está autenticado
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para verificar si el usuario tiene un permiso específico
function hasPermission($permission) {
    error_log("DEBUG: [hasPermission] Checking permission: " . $permission);
    if (!isAuthenticated()) {
        error_log("DEBUG: [hasPermission] User not authenticated.");
        return false;
    }

    // Debugging: Log session user and role ID
    error_log("DEBUG: [hasPermission] SESSION user_id: " . ($_SESSION['user_id'] ?? 'N/A'));
    error_log("DEBUG: [hasPermission] SESSION rol_id: " . ($_SESSION['rol_id'] ?? 'N/A'));

    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) as has_permission
        FROM rol_permisos rp
        JOIN permisos p ON rp.id_permiso = p.id
        WHERE rp.id_rol = :rol_id AND p.nombre_permiso = :permission
    ");

    $stmt->execute([
        ':rol_id' => $_SESSION['rol_id'],
        ':permission' => $permission
    ]);

    $result = $stmt->fetch();
    error_log("DEBUG: [hasPermission] Query result (has_permission): " . $result['has_permission']);
    return $result['has_permission'] > 0;
}

// Función para verificar acceso a una página
function verificarAcceso($permission) {
    error_log("DEBUG: [verificarAcceso] Verifying access for permission: " . $permission);
    if (!isAuthenticated()) {
        error_log("DEBUG: [verificarAcceso] User not authenticated, redirecting to login.");
        redirect(APP_URL . '/login.php');
    }

    if (!hasPermission($permission)) {
        error_log("DEBUG: [verificarAcceso] User does NOT have permission: " . $permission . ", redirecting to error page.");
        redirect(APP_URL . '/error.php?msg=unauthorized');
    }
    error_log("DEBUG: [verificarAcceso] User HAS permission: " . $permission .". Access granted.");
}

// Función para iniciar sesión
function iniciarSesion($usuario, $password) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT u.id, u.password, u.id_rol, r.nombre_rol
        FROM usuarios u
        JOIN roles r ON u.id_rol = r.id
        WHERE u.usuario = :usuario
    ");

    $stmt->execute([':usuario' => $usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['rol_id'] = $user['id_rol'];
        $_SESSION['rol_nombre'] = $user['nombre_rol'];
        $_SESSION['last_activity'] = time();
        
        registrarActividad('login', 'Inicio de sesión exitoso');
        return true;
    }

    return false;
}

// Función para cerrar sesión
function cerrarSesion() {
    if (isAuthenticated()) {
        registrarActividad('logout', 'Cierre de sesión');
    }
    
    session_unset();
    session_destroy();
    redirect(APP_URL . '/login.php');
}

// Función para registrar actividad
function registrarActividad($accion, $detalle = '') {
    if (!isAuthenticated()) {
        return;
    }

    $db = getDBConnection();
    $stmt = $db->prepare("
        INSERT INTO registro_actividades (id_usuario, accion, detalle)
        VALUES (:user_id, :accion, :detalle)
    ");

    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':accion' => $accion,
        ':detalle' => $detalle
    ]);
}

// Función para verificar timeout de sesión (30 minutos)
function checkSessionTimeout() {
    if (isAuthenticated()) {
        $timeout = 30 * 60; // 30 minutos en segundos
        if (time() - $_SESSION['last_activity'] > $timeout) {
            cerrarSesion();
        }
        $_SESSION['last_activity'] = time();
    }
}

// Verificar timeout en cada carga de página
checkSessionTimeout(); 