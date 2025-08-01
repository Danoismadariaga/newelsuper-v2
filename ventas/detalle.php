<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ahora puedes usar verificarAcceso()
verificarAcceso('ver_ventas');

$db = getDBConnection();

$venta = null;
$detalles_venta = [];

// Obtener el ID de la venta de la URL
$id_venta = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

if (!$id_venta) {
    header('Location: consulta.php?message=Venta no especificada o inválida&type=danger');
    exit();
}

// Cargar datos de la venta principal
try {
    $stmt_venta = $db->prepare("
        SELECT v.id, v.id_cliente, c.nombre as cliente_nombre, c.apellido as cliente_apellido, v.fecha_venta, v.total
        FROM ventas v
        JOIN clientes c ON v.id_cliente = c.id
        WHERE v.id = :id_venta
    ");
    $stmt_venta->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
    $stmt_venta->execute();
    $venta = $stmt_venta->fetch();

    if (!$venta) {
        header('Location: consulta.php?message=Venta no encontrada&type=danger');
        exit();
    }

    // Cargar detalles de los productos en esta venta
    $stmt_detalles = $db->prepare("
        SELECT dv.id_producto, p.nombre as producto_nombre, dv.cantidad, dv.precio_unitario, dv.subtotal
        FROM detalle_ventas dv
        JOIN productos p ON dv.id_producto = p.id
        WHERE dv.id_venta = :id_venta
    ");
    $stmt_detalles->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
    $stmt_detalles->execute();
    $detalles_venta = $stmt_detalles->fetchAll();

} catch (PDOException $e) {
    registrarActividad('Error al cargar detalles de venta', 'Venta ID: ' . $id_venta . ', Error: ' . $e->getMessage(), 'error');
    header('Location: consulta.php?message=Error de base de datos al cargar detalles de venta&type=danger');
    exit();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Venta - <?php echo APP_NAME; ?></title>
    <link href="../css/modern.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
@media print {
    .modern-btn, .navbar-dashboard, .sidebar, .no-print, .pagination, footer {
        display: none !important;
    }
    body {
        background: #fff !important;
    }
}
    </style>
</head>
<body>
    <?php include_once '../includes/navbar.php'; ?>
    <div class="dashboard-container">
        <?php include_once '../includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="centered-card">
                <div class="card modern-card" style="max-width: 900px;">
                    <div class="card-header modern-card-header">
                        <h2 class="modern-title"><i class="bi bi-receipt"></i> Detalles de Venta #<?php echo htmlspecialchars($venta['id']); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="modern-alert" style="background:#06b6d4; color:#fff; font-weight:600;">
                            Información General de la Venta
                        </div>
                        <div class="mb-4" style="background:#f8fafc; border-radius:var(--radius); padding:1.2rem 1.5rem;">
                            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($venta['cliente_nombre'] . ' ' . $venta['cliente_apellido']); ?></p>
                            <p><strong>Fecha de Venta:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($venta['fecha_venta']))); ?></p>
                            <p><strong>Total de la Venta:</strong> <span style="color:var(--color-primary); font-size:1.3em; font-weight:700;">$<?php echo htmlspecialchars(number_format($venta['total'], 2)); ?></span></p>
                        </div>
                        <h4 class="modern-label" style="margin-bottom:1.2rem; margin-top:2rem; font-size:1.2rem;">Productos de la Venta</h4>
                        <?php if (empty($detalles_venta)): ?>
                            <div class="modern-alert">No se encontraron productos para esta venta.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table modern-table table-hover table-striped border">
                                    <thead>
                                        <tr>
                                            <th>ID Producto</th>
                                            <th>Producto</th>
                                            <th>Cantidad</th>
                                            <th>Precio Unitario</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detalles_venta as $detalle): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($detalle['id_producto']); ?></td>
                                            <td><?php echo htmlspecialchars($detalle['producto_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($detalle['cantidad']); ?></td>
                                            <td>$<?php echo htmlspecialchars(number_format($detalle['precio_unitario'], 2)); ?></td>
                                            <td>$<?php echo htmlspecialchars(number_format($detalle['subtotal'], 2)); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        <div class="modern-form-actions" style="margin-top:2rem;">
                            <a href="recibo.php?id=<?php echo $venta['id']; ?>" class="modern-btn modern-btn-success" target="_blank">
                                <i class="bi bi-receipt"></i> Ver/Imprimir Recibo
                            </a>
                            <a href="consulta.php" class="modern-btn modern-btn-secondary"><i class="bi bi-arrow-left-circle"></i> Volver a Ventas</a>
                            <button class="modern-btn modern-btn-secondary" onclick="window.print()">
                                <i class="bi bi-printer"></i> Imprimir Recibo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 