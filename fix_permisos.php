<?php
define('SECURE_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/auth.php';

echo "<h2>Solución Automática de Permisos</h2>";

// Verificar que el usuario esté autenticado
if (!isAuthenticated()) {
    echo "❌ Debes estar autenticado para ejecutar este script.<br>";
    echo "<a href='login.php'>Ir al login</a><br>";
    exit;
}

try {
    $db = getDBConnection();
    echo "✅ Conexión a la base de datos exitosa<br><br>";
    
    // 1. Verificar que existan los roles básicos
    echo "<h3>1. Verificando roles básicos...</h3>";
    $roles_requeridos = ['Administrador', 'Vendedor', 'Inventario'];
    
    foreach ($roles_requeridos as $rol_nombre) {
        $stmt = $db->prepare("SELECT id FROM roles WHERE nombre_rol = ?");
        $stmt->execute([$rol_nombre]);
        $rol = $stmt->fetch();
        
        if (!$rol) {
            $stmt = $db->prepare("INSERT INTO roles (nombre_rol) VALUES (?)");
            $stmt->execute([$rol_nombre]);
            echo "✅ Rol '{$rol_nombre}' creado<br>";
        } else {
            echo "✅ Rol '{$rol_nombre}' ya existe (ID: {$rol['id']})<br>";
        }
    }
    
    // 2. Verificar que existan los permisos básicos
    echo "<h3>2. Verificando permisos básicos...</h3>";
    $permisos_requeridos = [
        'ver_clientes', 'crear_clientes', 'editar_clientes', 'eliminar_clientes',
        'ver_empleados', 'crear_empleados', 'editar_empleados', 'eliminar_empleados',
        'ver_productos', 'crear_productos', 'editar_productos', 'eliminar_productos',
        'ver_ventas', 'crear_ventas', 'editar_ventas', 'eliminar_ventas',
        'ver_inventario', 'registrar_entrada_inventario', 'registrar_salida_inventario', 'ver_movimientos_inventario',
        'gestionar_usuarios', 'ver_actividad', 'cambiar_password'
    ];
    
    foreach ($permisos_requeridos as $permiso_nombre) {
        $stmt = $db->prepare("SELECT id FROM permisos WHERE nombre_permiso = ?");
        $stmt->execute([$permiso_nombre]);
        $permiso = $stmt->fetch();
        
        if (!$permiso) {
            $stmt = $db->prepare("INSERT INTO permisos (nombre_permiso) VALUES (?)");
            $stmt->execute([$permiso_nombre]);
            echo "✅ Permiso '{$permiso_nombre}' creado<br>";
        } else {
            echo "✅ Permiso '{$permiso_nombre}' ya existe (ID: {$permiso['id']})<br>";
        }
    }
    
    // 3. Asignar todos los permisos al rol Administrador
    echo "<h3>3. Configurando permisos del Administrador...</h3>";
    $stmt = $db->prepare("SELECT id FROM roles WHERE nombre_rol = 'Administrador'");
    $stmt->execute();
    $admin_rol = $stmt->fetch();
    
    if ($admin_rol) {
        // Eliminar permisos existentes del administrador
        $stmt = $db->prepare("DELETE FROM rol_permisos WHERE id_rol = ?");
        $stmt->execute([$admin_rol['id']]);
        
        // Asignar todos los permisos al administrador
        $stmt = $db->prepare("INSERT INTO rol_permisos (id_rol, id_permiso) SELECT ?, id FROM permisos");
        $stmt->execute([$admin_rol['id']]);
        
        echo "✅ Todos los permisos asignados al rol Administrador<br>";
    }
    
    // 4. Configurar permisos del Vendedor
    echo "<h3>4. Configurando permisos del Vendedor...</h3>";
    $stmt = $db->prepare("SELECT id FROM roles WHERE nombre_rol = 'Vendedor'");
    $stmt->execute();
    $vendedor_rol = $stmt->fetch();
    
    if ($vendedor_rol) {
        // Eliminar permisos existentes del vendedor
        $stmt = $db->prepare("DELETE FROM rol_permisos WHERE id_rol = ?");
        $stmt->execute([$vendedor_rol['id']]);
        
        // Permisos específicos para vendedor
        $permisos_vendedor = [
            'ver_clientes', 'crear_clientes', 'editar_clientes',
            'ver_productos',
            'ver_ventas', 'crear_ventas', 'editar_ventas',
            'cambiar_password'
        ];
        
        foreach ($permisos_vendedor as $permiso_nombre) {
            $stmt = $db->prepare("
                INSERT INTO rol_permisos (id_rol, id_permiso) 
                SELECT ?, p.id FROM permisos p WHERE p.nombre_permiso = ?
            ");
            $stmt->execute([$vendedor_rol['id'], $permiso_nombre]);
        }
        
        echo "✅ Permisos asignados al rol Vendedor<br>";
    }
    
    // 5. Configurar permisos del Inventario
    echo "<h3>5. Configurando permisos del Inventario...</h3>";
    $stmt = $db->prepare("SELECT id FROM roles WHERE nombre_rol = 'Inventario'");
    $stmt->execute();
    $inventario_rol = $stmt->fetch();
    
    if ($inventario_rol) {
        // Eliminar permisos existentes del inventario
        $stmt = $db->prepare("DELETE FROM rol_permisos WHERE id_rol = ?");
        $stmt->execute([$inventario_rol['id']]);
        
        // Permisos específicos para inventario
        $permisos_inventario = [
            'ver_productos', 'crear_productos', 'editar_productos',
            'ver_inventario', 'registrar_entrada_inventario', 
            'registrar_salida_inventario', 'ver_movimientos_inventario',
            'cambiar_password'
        ];
        
        foreach ($permisos_inventario as $permiso_nombre) {
            $stmt = $db->prepare("
                INSERT INTO rol_permisos (id_rol, id_permiso) 
                SELECT ?, p.id FROM permisos p WHERE p.nombre_permiso = ?
            ");
            $stmt->execute([$inventario_rol['id'], $permiso_nombre]);
        }
        
        echo "✅ Permisos asignados al rol Inventario<br>";
    }
    
    // 6. Verificar el usuario actual
    echo "<h3>6. Verificando usuario actual...</h3>";
    $stmt = $db->prepare("
        SELECT u.id, u.usuario, r.nombre_rol, r.id as rol_id
        FROM usuarios u
        JOIN roles r ON u.id_rol = r.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario_actual = $stmt->fetch();
    
    if ($usuario_actual) {
        echo "Usuario actual: {$usuario_actual['usuario']} (Rol: {$usuario_actual['nombre_rol']})<br>";
        
        // Verificar si el usuario actual tiene el permiso gestionar_usuarios
        $stmt = $db->prepare("
            SELECT COUNT(*) as tiene_permiso
            FROM rol_permisos rp
            JOIN permisos p ON rp.id_permiso = p.id
            WHERE rp.id_rol = ? AND p.nombre_permiso = 'gestionar_usuarios'
        ");
        $stmt->execute([$usuario_actual['rol_id']]);
        $result = $stmt->fetch();
        
        if ($result['tiene_permiso'] > 0) {
            echo "✅ El usuario actual SÍ tiene permiso para gestionar usuarios<br>";
        } else {
            echo "❌ El usuario actual NO tiene permiso para gestionar usuarios<br>";
            echo "💡 Recomendación: Cambiar al usuario al rol Administrador<br>";
            
            // Opción para cambiar automáticamente al rol administrador
            if (isset($_GET['fix_user'])) {
                $stmt = $db->prepare("UPDATE usuarios SET id_rol = ? WHERE id = ?");
                $stmt->execute([$admin_rol['id'], $_SESSION['user_id']]);
                
                // Actualizar la sesión
                $_SESSION['rol_id'] = $admin_rol['id'];
                $_SESSION['rol_nombre'] = 'Administrador';
                
                echo "✅ Usuario cambiado al rol Administrador<br>";
                echo "🔄 Por favor, recarga la página para aplicar los cambios<br>";
            } else {
                echo "<a href='?fix_user=1' class='btn btn-warning'>Cambiar a Administrador</a><br>";
            }
        }
    }
    
    echo "<h3>✅ Configuración completada</h3>";
    echo "<p>Los permisos han sido configurados correctamente. Ahora puedes:</p>";
    echo "<ul>";
    echo "<li><a href='debug_permisos.php'>Verificar el diagnóstico de permisos</a></li>";
    echo "<li><a href='usuarios/consulta.php'>Acceder al módulo de usuarios</a></li>";
    echo "<li><a href='index.php'>Volver al inicio</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?> 