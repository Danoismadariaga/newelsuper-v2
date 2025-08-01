<?php
define('SECURE_ACCESS', true);
require_once 'includes/config.php';

try {
    $db = getDBConnection();
    echo "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><title>Debug DB</title><link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\"></head><body><div class=\"container mt-4\"><h2>Contenido de la Base de Datos</h2>";

    echo "<h3>Tabla Usuarios:</h3>";
    $stmt_usuarios = $db->query("SELECT id, usuario, id_rol FROM usuarios");
    if ($stmt_usuarios->rowCount() > 0) {
        echo "<table class=\"table table-bordered table-striped\"><thead><tr><th>ID</th><th>Usuario</th><th>ID Rol</th></tr></thead><tbody>";
        while ($row = $stmt_usuarios->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['usuario']) . "</td><td>" . htmlspecialchars($row['id_rol']) . "</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No hay usuarios en la base de datos.</p>";
    }

    echo "<h3>Tabla Empleados:</h3>";
    $stmt_empleados = $db->query("SELECT id, nombre, apellido, id_usuario FROM empleados");
    if ($stmt_empleados->rowCount() > 0) {
        echo "<table class=\"table table-bordered table-striped\"><thead><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>ID Usuario</th></tr></thead><tbody>";
        while ($row = $stmt_empleados->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['nombre']) . "</td><td>" . htmlspecialchars($row['apellido']) . "</td><td>" . htmlspecialchars($row['id_usuario']) . "</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No hay empleados en la base de datos.</p>";
    }

    echo "</div><script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script></body></html>";

} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?> 