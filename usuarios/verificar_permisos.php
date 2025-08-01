<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

echo "<h2>Verificación de Permisos - Módulo Usuarios</h2>";

// Verificar autenticación
if (!isAuthenticated()) {
    echo "❌ Debes estar autenticado para acceder a esta página.<br>";
    echo "<a href='../login.php'>Ir al login</a><br>";
    exit;
}

try {
    $db = getDBConnection();
    
    // Verificar permisos específicos del módulo usuarios
    $permisos_usuarios = ['gestionar_usuarios'];
    $usuario_actual = $_SESSION['user_id'];
    $rol_actual = $_SESSION['rol_id'];
    
    echo "<h3>Información del Usuario Actual</h3>";
    echo "Usuario ID: {$usuario_actual}<br>";
    echo "Rol ID: {$rol_actual}<br>";
    echo "Rol Nombre: {$_SESSION['rol_nombre']}<br><br>";
    
    echo "<h3>Verificación de Permisos</h3>";
    
    foreach ($permisos_usuarios as $permiso) {
        $stmt = $db->prepare("
            SELECT COUNT(*) as tiene_permiso
            FROM rol_permisos rp
            JOIN permisos p ON rp.id_permiso = p.id
            WHERE rp.id_rol = ? AND p.nombre_permiso = ?
        ");
        $stmt->execute([$rol_actual, $permiso]);
        $result = $stmt->fetch();
        
        if ($result['tiene_permiso'] > 0) {
            echo "✅ Permiso '{$permiso}': CONCEDIDO<br>";
        } else {
            echo "❌ Permiso '{$permiso}': DENEGADO<br>";
        }
    }
    
    // Verificar si el usuario puede acceder a las páginas del módulo
    echo "<h3>Prueba de Acceso a Páginas</h3>";
    
    $paginas = [
        'consulta.php' => 'gestionar_usuarios',
        'crear.php' => 'gestionar_usuarios',
        'editar.php' => 'gestionar_usuarios'
    ];
    
    foreach ($paginas as $pagina => $permiso_requerido) {
        $tiene_permiso = hasPermission($permiso_requerido);
        if ($tiene_permiso) {
            echo "✅ {$pagina}: Acceso permitido<br>";
        } else {
            echo "❌ {$pagina}: Acceso denegado<br>";
        }
    }
    
    // Mostrar enlaces de prueba
    echo "<h3>Enlaces de Prueba</h3>";
    echo "<ul>";
    echo "<li><a href='consulta.php'>Probar acceso a Consulta de Usuarios</a></li>";
    echo "<li><a href='crear.php'>Probar acceso a Crear Usuario</a></li>";
    echo "<li><a href='../debug_permisos.php'>Diagnóstico General de Permisos</a></li>";
    echo "<li><a href='../fix_permisos.php'>Solución Automática de Permisos</a></li>";
    echo "</ul>";
    
    // Recomendaciones
    echo "<h3>Recomendaciones</h3>";
    if (!hasPermission('gestionar_usuarios')) {
        echo "<div style='background-color: #ffe6e6; padding: 10px; border: 1px solid #ff9999;'>";
        echo "<strong>Problema:</strong> No tienes permisos para gestionar usuarios.<br>";
        echo "<strong>Soluciones:</strong><br>";
        echo "1. <a href='../fix_permisos.php'>Ejecutar solución automática de permisos</a><br>";
        echo "2. Contactar al administrador del sistema<br>";
        echo "3. Verificar que tu rol tenga los permisos correctos<br>";
        echo "</div>";
    } else {
        echo "<div style='background-color: #e6ffe6; padding: 10px; border: 1px solid #99ff99;'>";
        echo "✅ Tienes los permisos necesarios para gestionar usuarios.<br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='../index.php'>Volver al inicio</a>";
?> 