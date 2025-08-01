<?php
// recibo.php - Recibo imprimible de una venta

define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('ver_ventas');

// Obtener ID de la venta
$venta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($venta_id <= 0) {
    die('ID de venta inválido.');
}

$db = getDBConnection();

// Obtener datos de la venta
$stmt = $db->prepare("
    SELECT v.*, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido, c.email AS cliente_email,
           e.nombre AS empleado_nombre, e.apellido AS empleado_apellido
    FROM ventas v
    JOIN clientes c ON v.id_cliente = c.id
    JOIN empleados e ON v.id_empleado = e.id
    WHERE v.id = :id
");
$stmt->execute([':id' => $venta_id]);
$venta = $stmt->fetch();
if (!$venta) {
    die('Venta no encontrada.');
}

// Obtener detalles de productos
$stmt = $db->prepare("
    SELECT dv.*, p.nombre AS producto_nombre
    FROM detalle_ventas dv
    JOIN productos p ON dv.id_producto = p.id
    WHERE dv.id_venta = :id_venta
");
$stmt->execute([':id_venta' => $venta_id]);
$productos = $stmt->fetchAll();

$fecha = date('d/m/Y H:i', strtotime($venta['fecha_venta']));
$total = number_format($venta['total'], 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Venta #<?php echo $venta_id; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .recibo-box { box-shadow: none !important; border: none !important; }
        }
        body {
            background: #f8f9fa;
        }
        .recibo-box {
            max-width: 500px;
            margin: 30px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 30px 25px;
        }
        .recibo-titulo {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        .recibo-datos {
            font-size: 1rem;
            margin-bottom: 10px;
        }
        .recibo-total {
            font-size: 1.2rem;
            font-weight: bold;
            text-align: right;
            margin-top: 10px;
        }
        .recibo-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 1rem;
            color: #555;
        }
    </style>
</head>
<body>
<div class="recibo-box">
    <div class="recibo-titulo">Recibo de Venta</div>
    <div class="text-center mb-2" style="font-size:1.1rem;"><strong><?php echo APP_NAME; ?></strong></div>
    <div class="recibo-datos">
        <strong>N° Venta:</strong> <?php echo $venta_id; ?><br>
        <strong>Fecha:</strong> <?php echo $fecha; ?><br>
        <strong>Cliente:</strong> <?php echo htmlspecialchars($venta['cliente_nombre'] . ' ' . $venta['cliente_apellido']); ?><br>
        <strong>Empleado:</strong> <?php echo htmlspecialchars($venta['empleado_nombre'] . ' ' . $venta['empleado_apellido']); ?><br>
    </div>
    <table class="table table-sm table-bordered mb-2">
        <thead>
            <tr>
                <th>Cant.</th>
                <th>Producto</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($productos as $prod): ?>
            <tr>
                <td><?php echo $prod['cantidad']; ?></td>
                <td><?php echo htmlspecialchars($prod['producto_nombre']); ?></td>
                <td>$<?php echo number_format($prod['precio_unitario'], 2); ?></td>
                <td>$<?php echo number_format($prod['subtotal'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="recibo-total">
        Total: $<?php echo $total; ?>
    </div>
    <div class="recibo-footer">
        ¡Gracias por su compra!
    </div>
    <div class="text-center mt-3 no-print">
        <button class="btn btn-success" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir
        </button>
        <a href="detalle.php?id=<?php echo $venta_id; ?>" class="btn btn-secondary">Volver</a>
    </div>
</div>
</body>
</html> 