<?php
// Muestra todos los errores de PHP (útil para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Establece el tipo de contenido de la respuesta como JSON
header('Content-Type: application/json');

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'sistema_gestion';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    // Crea el DSN (Data Source Name) para la conexión PDO
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    // Opciones para la conexión PDO
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en caso de error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Devuelve los resultados como arrays asociativos
        PDO::ATTR_EMULATE_PREPARES => false, // Desactiva la emulación de sentencias preparadas
    ];
    // Crea una nueva instancia de PDO para conectarse a la base de datos
    $db = new PDO($dsn, $user, $pass, $options);

    // Ejecuta una consulta SQL para obtener los últimos 50 movimientos de inventario,
    // incluyendo información del producto y del empleado responsable
    $stmt = $db->query("
        SELECT i.id, p.nombre as producto_nombre, i.tipo_movimiento, i.cantidad, i.fecha_movimiento,
               e.nombre as empleado_nombre, e.apellido as empleado_apellido
        FROM inventario i
        JOIN productos p ON i.id_producto = p.id
        JOIN empleados e ON i.id_empleado_responsable = e.id
        ORDER BY i.fecha_movimiento DESC
        LIMIT 50
    ");
    // Devuelve los resultados de la consulta en formato JSON
    echo json_encode($stmt->fetchAll());
} catch (Exception $e) {
    // Si ocurre un error, devuelve el mensaje de error en formato JSON
    echo json_encode(['error' => $e->getMessage()]);
}