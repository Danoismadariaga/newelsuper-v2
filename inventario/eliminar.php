<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('eliminar_movimientos_inventario');

$db = getDBConnection();

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: consulta.php?message=ID de movimiento de inventario no válido.&type=danger');
    exit();
}

$db->beginTransaction();
try {
    // 1. Obtener detalles del movimiento a eliminar
    $stmt_movimiento = $db->prepare("SELECT id_producto, tipo_movimiento, cantidad FROM inventario WHERE id = :id");
    $stmt_movimiento->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_movimiento->execute();
    $movimiento = $stmt_movimiento->fetch();

    if (!$movimiento) {
        throw new Exception('Movimiento de inventario no encontrado.');
    }

    $id_producto = $movimiento['id_producto'];
    $tipo_movimiento = $movimiento['tipo_movimiento'];
    $cantidad = $movimiento['cantidad'];

    // 2. Revertir el impacto en el stock del producto
    if ($tipo_movimiento == 'ENTRADA') {
        // Si era una entrada, restar la cantidad del stock
        $stmt_update_stock = $db->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :id_producto");
    } elseif ($tipo_movimiento == 'SALIDA') {
        // Si era una salida, sumar la cantidad al stock
        $stmt_update_stock = $db->prepare("UPDATE productos SET stock = stock + :cantidad WHERE id = :id_producto");
    } else {
        throw new Exception('Tipo de movimiento desconocido.');
    }

    $stmt_update_stock->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
    $stmt_update_stock->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
    $stmt_update_stock->execute();

    // 3. Eliminar el registro del movimiento de inventario
    $stmt_delete_movimiento = $db->prepare("DELETE FROM inventario WHERE id = :id");
    $stmt_delete_movimiento->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_delete_movimiento->execute();

    $db->commit();
    registrarActividad('Eliminación de Movimiento de Inventario', 'Movimiento ID: ' . $id . ', Producto ID: ' . $id_producto . ', Cantidad: ' . $cantidad . ', Tipo: ' . $tipo_movimiento . '. Stock revertido.');
    header('Location: consulta.php?message=Movimiento de inventario eliminado y stock revertido exitosamente.&type=success');
    exit();

} catch (Exception $e) {
    $db->rollBack();
    registrarActividad('Error al eliminar movimiento de inventario', 'Movimiento ID: ' . $id . ', Error: ' . $e->getMessage(), 'error');
    header('Location: consulta.php?message=Error al eliminar el movimiento de inventario: ' . urlencode($e->getMessage()) . '&type=danger');
    exit();
}

?> 