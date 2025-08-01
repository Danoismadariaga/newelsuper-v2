<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('eliminar_productos');

$db = getDBConnection();

$message = '';
$message_type = '';

// Obtener el ID del producto de la URL
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

if (!$id) {
    $message = 'Producto no especificado o inválido.';
    $message_type = 'danger';
} else {
    try {
        // Verificar si el producto tiene ventas asociadas
        $stmt_check_sales = $db->prepare("SELECT COUNT(*) FROM detalles_venta WHERE producto_id = :id");
        $stmt_check_sales->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_check_sales->execute();
        $sales_count = $stmt_check_sales->fetchColumn();

        if ($sales_count > 0) {
            $message = 'No se puede eliminar el producto porque tiene ventas asociadas.';
            $message_type = 'warning';
            registrarActividad('Intento fallido de eliminar producto', 'Producto ID: ' . $id . ' con ventas asociadas.', 'warning');
        } else {
            // Si no tiene ventas asociadas, proceder con la eliminación
            $stmt = $db->prepare("DELETE FROM productos WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $message = 'Producto eliminado exitosamente.';
                    $message_type = 'success';
                    registrarActividad('Producto eliminado', 'Producto ID: ' . $id);
                } else {
                    $message = 'El producto no fue encontrado o ya ha sido eliminado.';
                    $message_type = 'warning';
                }
            } else {
                $message = 'Error al eliminar el producto.';
                $message_type = 'danger';
            }
        }
    } catch (PDOException $e) {
        $message = 'Error de base de datos: ' . $e->getMessage();
        $message_type = 'danger';
        registrarActividad('Error al eliminar producto', 'Producto ID: ' . $id . ', Error: ' . $e->getMessage(), 'error');
    }
}

// Redirigir a la página de consulta con el mensaje
header('Location: consulta.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
exit();
?> 