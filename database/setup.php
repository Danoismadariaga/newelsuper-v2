<?php
// Configuración de la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Conectar a MySQL sin seleccionar base de datos
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conexión exitosa a MySQL.<br>";
    
    // Crear la base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS sistema_gestion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Base de datos 'sistema_gestion' creada o ya existente.<br>";
    
    // Seleccionar la base de datos
    $pdo->exec("USE sistema_gestion");

    // Desactivar temporalmente las comprobaciones de clave foránea
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Eliminar todas las tablas existentes para asegurar una base de datos limpia
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
    }
    echo "Tablas existentes eliminadas.<br>";
    
    // Leer el archivo SQL
    $sql = file_get_contents(__DIR__ . '/estructura.sql');
    
    // Eliminar la línea de CREATE DATABASE y USE
    $sql = preg_replace('/CREATE DATABASE.*?;/', '', $sql);
    $sql = preg_replace('/USE.*?;/', '', $sql);
    
    // Ejecutar el script SQL
    $pdo->exec($sql);
    echo "Estructura de la base de datos creada exitosamente.<br>";

    // Insertar usuario administrador inicial y empleado asociado
    $admin_usuario = 'admin';
    $admin_password = password_hash('password', PASSWORD_DEFAULT); // Contraseña: password
    $admin_rol_id = 1; // ID del rol 'Administrador'

    // Eliminar el usuario y empleado si ya existen (para re-ejecuciones)
    // Esto es redundante si las tablas se borran, pero se mantiene por seguridad en caso de futuras modificaciones
    $pdo->exec("DELETE FROM usuarios WHERE usuario = '{$admin_usuario}'");

    // Insertar el usuario administrador
    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password, id_rol) VALUES (:usuario, :password, :id_rol)");
    $stmt->execute([
        ':usuario' => $admin_usuario,
        ':password' => $admin_password,
        ':id_rol' => $admin_rol_id
    ]);
    $id_usuario_admin = $pdo->lastInsertId();

    // Insertar el empleado asociado al usuario administrador
    $stmt = $pdo->prepare("INSERT INTO empleados (nombre, apellido, email, telefono, puesto, fecha_contratacion, id_usuario) VALUES (:nombre, :apellido, :email, :telefono, :puesto, :fecha_contratacion, :id_usuario)");
    $stmt->execute([
        ':nombre' => 'Admin',
        ':apellido' => 'General',
        ':email' => 'admin@example.com',
        ':telefono' => NULL,
        ':puesto' => 'Administrador del Sistema',
        ':fecha_contratacion' => date('Y-m-d'),
        ':id_usuario' => $id_usuario_admin
    ]);

    echo "<br>Usuario 'admin' y empleado asociado creados exitosamente.<br>";
    echo "<br>Credenciales de administrador:";
    echo "<ul><li>Usuario: <b>admin</b></li><li>Contraseña: <b>password</b></li></ul>";
    
    // Volver a activar las comprobaciones de clave foránea
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Verificar las tablas creadas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<br>Tablas creadas:<br>";
    foreach ($tables as $table) {
        echo "- $table<br>";
    }
    
    echo "<br>Configuración completada exitosamente.<br>";
    echo "<a href='../test_db.php'>Verificar conexión</a>";
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?> 