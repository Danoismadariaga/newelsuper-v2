<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('eliminar_ventas');

$db = getDBConnection();

$message = '';
$message_type = '';

// Obtener el ID de la venta de la URL
$id_venta = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

if (!$id_venta) {
    $message = 'Venta no especificada o inválida.';
    $message_type = 'danger';
} else {
    $db->beginTransaction();
    try {
        // Paso 1: Obtener los productos y cantidades de la venta a eliminar para revertir el stock
        $stmt_detalles = $db->prepare("SELECT id_producto, cantidad FROM detalle_ventas WHERE id_venta = :id_venta");
        $stmt_detalles->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
        $stmt_detalles->execute();
        $productos_en_venta = $stmt_detalles->fetchAll();

        // Paso 2: Revertir el stock de cada producto
        $stmt_update_stock = $db->prepare("UPDATE productos SET stock = stock + :cantidad WHERE id = :id_producto");
        foreach ($productos_en_venta as $producto_venta) {
            $stmt_update_stock->bindParam(':cantidad', $producto_venta['cantidad'], PDO::PARAM_INT);
            $stmt_update_stock->bindParam(':id_producto', $producto_venta['id_producto'], PDO::PARAM_INT);
            $stmt_update_stock->execute();
        }

        // Paso 3: Eliminar los detalles de la venta
        $stmt_delete_detalles = $db->prepare("DELETE FROM detalle_ventas WHERE id_venta = :id_venta");
        $stmt_delete_detalles->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
        $stmt_delete_detalles->execute();

        // Paso 4: Eliminar la venta principal
        $stmt_delete_venta = $db->prepare("DELETE FROM ventas WHERE id = :id_venta");
        $stmt_delete_venta->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
        $stmt_delete_venta->execute();

        if ($stmt_delete_venta->rowCount() > 0) {
            $db->commit();
            $message = 'Venta ID ' . $id_venta . ' eliminada exitosamente. Stock de productos revertido.';
            $message_type = 'success';
            registrarActividad('Venta eliminada', 'Venta ID: ' . $id_venta . '. Stock revertido.');
        } else {
            $db->rollBack();
            $message = 'La venta no fue encontrada o ya ha sido eliminada.';
            $message_type = 'warning';
        }
    } catch (PDOException $e) {
        $db->rollBack();
        $message = 'Error de base de datos al eliminar la venta: ' . $e->getMessage();
        $message_type = 'danger';
        registrarActividad('Error al eliminar venta', 'Venta ID: ' . $id_venta . ', Error: ' . $e->getMessage(), 'error');
    } catch (Exception $e) {
        $db->rollBack();
        $message = 'Error inesperado al eliminar la venta: ' . $e->getMessage();
        $message_type = 'danger';
        registrarActividad('Error inesperado al eliminar venta', 'Venta ID: ' . $id_venta . ', Error: ' . $e->getMessage(), 'error');
    }
}

// Redirigir a la página de consulta con el mensaje
header('Location: consulta.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
exit();
?> 