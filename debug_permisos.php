<?php
define('SECURE_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/auth.php';

echo "<h2>Diagnóstico de Permisos - Módulo Usuarios</h2>";

// 1. Verificar conexión a la base de datos
echo "<h3>1. Verificación de Conexión</h3>";
try {
    $db = getDBConnection();
    echo "✅ Conexión a la base de datos exitosa<br>";
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Verificar si hay sesión activa
echo "<h3>2. Estado de la Sesión</h3>";
if (isAuthenticated()) {
    echo "✅ Usuario autenticado<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Rol ID: " . $_SESSION['rol_id'] . "<br>";
    echo "Rol Nombre: " . $_SESSION['rol_nombre'] . "<br>";
} else {
    echo "❌ Usuario NO autenticado<br>";
    echo "<a href='login.php'>Ir al login</a><br>";
    exit;
}

// 3. Verificar roles existentes
echo "<h3>3. Roles en el Sistema</h3>";
$stmt = $db->query("SELECT * FROM roles ORDER BY id");
$roles = $stmt->fetchAll();
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Nombre Rol</th></tr>";
foreach ($roles as $rol) {
    echo "<tr><td>{$rol['id']}</td><td>{$rol['nombre_rol']}</td></tr>";
}
echo "</table><br>";

// 4. Verificar permisos existentes
echo "<h3>4. Permisos en el Sistema</h3>";
$stmt = $db->query("SELECT * FROM permisos ORDER BY id");
$permisos = $stmt->fetchAll();
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Nombre Permiso</th></tr>";
foreach ($permisos as $permiso) {
    echo "<tr><td>{$permiso['id']}</td><td>{$permiso['nombre_permiso']}</td></tr>";
}
echo "</table><br>";

// 5. Verificar permisos del usuario actual
echo "<h3>5. Permisos del Usuario Actual</h3>";
$stmt = $db->prepare("
    SELECT p.nombre_permiso
    FROM rol_permisos rp
    JOIN permisos p ON rp.id_permiso = p.id
    WHERE rp.id_rol = :rol_id
    ORDER BY p.nombre_permiso
");
$stmt->execute([':rol_id' => $_SESSION['rol_id']]);
$permisos_usuario = $stmt->fetchAll();

echo "Permisos del usuario (Rol ID: {$_SESSION['rol_id']}):<br>";
if (empty($permisos_usuario)) {
    echo "❌ El usuario NO tiene permisos asignados<br>";
} else {
    echo "<ul>";
    foreach ($permisos_usuario as $permiso) {
        echo "<li>{$permiso['nombre_permiso']}</li>";
    }
    echo "</ul>";
}

// 6. Verificar específicamente el permiso 'gestionar_usuarios'
echo "<h3>6. Verificación del Permiso 'gestionar_usuarios'</h3>";
$stmt = $db->prepare("
    SELECT COUNT(*) as tiene_permiso
    FROM rol_permisos rp
    JOIN permisos p ON rp.id_permiso = p.id
    WHERE rp.id_rol = :rol_id AND p.nombre_permiso = 'gestionar_usuarios'
");
$stmt->execute([':rol_id' => $_SESSION['rol_id']]);
$result = $stmt->fetch();

if ($result['tiene_permiso'] > 0) {
    echo "✅ El usuario SÍ tiene el permiso 'gestionar_usuarios'<br>";
} else {
    echo "❌ El usuario NO tiene el permiso 'gestionar_usuarios'<br>";
}

// 7. Probar la función hasPermission
echo "<h3>7. Prueba de la Función hasPermission()</h3>";
$test_permission = 'gestionar_usuarios';
$has_permission = hasPermission($test_permission);
echo "Resultado de hasPermission('{$test_permission}'): " . ($has_permission ? '✅ TRUE' : '❌ FALSE') . "<br>";

// 8. Verificar usuarios existentes
echo "<h3>8. Usuarios en el Sistema</h3>";
$stmt = $db->query("
    SELECT u.id, u.usuario, r.nombre_rol
    FROM usuarios u
    JOIN roles r ON u.id_rol = r.id
    ORDER BY u.id
");
$usuarios = $stmt->fetchAll();
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Usuario</th><th>Rol</th></tr>";
foreach ($usuarios as $usuario) {
    echo "<tr><td>{$usuario['id']}</td><td>{$usuario['usuario']}</td><td>{$usuario['nombre_rol']}</td></tr>";
}
echo "</table><br>";

// 9. Recomendaciones
echo "<h3>9. Recomendaciones</h3>";
if (!$has_permission) {
    echo "<div style='background-color: #ffe6e6; padding: 10px; border: 1px solid #ff9999;'>";
    echo "<strong>Problema identificado:</strong> El usuario actual no tiene el permiso 'gestionar_usuarios'.<br>";
    echo "<strong>Soluciones:</strong><br>";
    echo "1. Asignar el permiso 'gestionar_usuarios' al rol del usuario actual.<br>";
    echo "2. Cambiar al usuario a un rol que tenga este permiso (como Administrador).<br>";
    echo "3. Verificar que el rol del usuario esté correctamente configurado en la base de datos.<br>";
    echo "</div>";
} else {
    echo "<div style='background-color: #e6ffe6; padding: 10px; border: 1px solid #99ff99;'>";
    echo "✅ El usuario tiene los permisos necesarios. El problema puede estar en otro lugar.<br>";
    echo "</div>";
}

echo "<br><a href='index.php'>Volver al inicio</a>";
?> 