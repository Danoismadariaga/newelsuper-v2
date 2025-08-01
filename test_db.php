<?php
define('SECURE_ACCESS', true);
require_once 'includes/config.php';

try {
    $db = getDBConnection();
    echo "Conexión exitosa a la base de datos.<br>";
    
    // Verificar si la base de datos existe
    $stmt = $db->query("SELECT DATABASE()");
    $database = $stmt->fetchColumn();
    echo "Base de datos actual: " . $database . "<br>";
    
    // Verificar si las tablas existen
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<br>Tablas encontradas:<br>";
    foreach ($tables as $table) {
        echo "- " . $table . "<br>";
    }
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?> 