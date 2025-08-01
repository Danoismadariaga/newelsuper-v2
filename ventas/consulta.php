<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('ver_ventas');

$db = getDBConnection();

// Paginación
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Búsqueda
$search = sanitizeInput($_GET['search'] ?? '');
$search_query = '';
$search_params = [];

if (!empty($search)) {
    $search_query = " WHERE (c.nombre LIKE :search_nombre OR c.apellido LIKE :search_apellido OR v.id LIKE :search_id)";
    $search_params[':search_nombre'] = '%' . $search . '%';
    $search_params[':search_apellido'] = '%' . $search . '%';
    $search_params[':search_id'] = '%' . $search . '%';
}

// Obtener el total de ventas para la paginación
$stmt_total = $db->prepare("
    SELECT COUNT(*) as total
    FROM ventas v
    JOIN clientes c ON v.id_cliente = c.id
    " . $search_query
);

foreach ($search_params as $key => &$val) {
    $stmt_total->bindParam($key, $val, PDO::PARAM_STR);
}
$stmt_total->execute();
$total_records = $stmt_total->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Obtener ventas con paginación y búsqueda
$stmt = $db->prepare("
    SELECT v.id, c.nombre as cliente_nombre, c.apellido as cliente_apellido, 
           e.nombre as empleado_nombre, v.fecha_venta, v.total, v.estado
    FROM ventas v
    JOIN clientes c ON v.id_cliente = c.id
    JOIN empleados e ON v.id_empleado = e.id
    " . $search_query . "
    ORDER BY v.fecha_venta DESC
    LIMIT :limit OFFSET :offset
");

foreach ($search_params as $key => &$val) {
    $stmt->bindParam($key, $val, PDO::PARAM_STR);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$ventas = $stmt->fetchAll();

if (!isset($user_info)) {
    $user_info = ['nombre' => 'Usuario', 'apellido' => '', 'nombre_rol' => ''];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/modern.css" rel="stylesheet">
</head>
<body>
    <?php include_once '../includes/navbar.php'; ?>
    <aside class="sidebar">
        <div class="sidebar-title">Menú</div>
        <ul class="sidebar-nav">
            <li><a href="../clientes/consulta.php"><i class="bi bi-people"></i> <span>Clientes</span></a></li>
            <li><a href="../empleados/consulta.php"><i class="bi bi-person-badge"></i> <span>Empleados</span></a></li>
            <li><a href="../productos/consulta.php"><i class="bi bi-box"></i> <span>Productos</span></a></li>
            <li><a href="../ventas/consulta.php" class="active"><i class="bi bi-cash-stack"></i> <span>Ventas</span></a></li>
            <li><a href="../inventario/consulta.php"><i class="bi bi-bar-chart"></i> <span>Inventario</span></a></li>
            <li><a href="../usuarios/consulta.php"><i class="bi bi-gear"></i> <span>Gestión de Usuarios</span></a></li>
            <li><a href="../actividad/consulta.php"><i class="bi bi-clipboard-data"></i> <span>Actividad</span></a></li>
        </ul>
    </aside>
    <main class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4"><i class="bi bi-cash-stack"></i> Gestión de Ventas</h2>
            <?php if (hasPermission('crear_ventas')): ?>
                <a href="crear.php" class="btn btn-success mb-3"><i class="bi bi-plus-circle"></i> Registrar Nueva Venta</a>
            <?php endif; ?>
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="busqueda" placeholder="Buscar por cliente, empleado o estado" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Empleado</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ventas)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No se encontraron ventas</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ventas as $venta): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($venta['id']); ?></td>
                                            <td><?php echo htmlspecialchars($venta['cliente_nombre'] . ' ' . $venta['cliente_apellido']); ?></td>
                                            <td><?php echo htmlspecialchars($venta['empleado_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($venta['fecha_venta']))); ?></td>
                                            <td><?php echo htmlspecialchars(number_format($venta['total'], 2)); ?></td>
                                            <td><?php echo htmlspecialchars($venta['estado']); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="detalle.php?id=<?php echo $venta['id']; ?>" class="btn btn-sm btn-info" title="Ver Detalle"><i class="bi bi-eye"></i></a>
                                                    <?php if (hasPermission('editar_ventas')): ?>
                                                        <a href="editar.php?id=<?php echo $venta['id']; ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                    <?php endif; ?>
                                                    <?php if (hasPermission('eliminar_ventas')): ?>
                                                        <a href="eliminar.php?id=<?php echo $venta['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de que desea eliminar esta venta?');"><i class="bi bi-trash"></i></a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Navegación de páginas">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo htmlspecialchars($search); ?>">Anterior</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo htmlspecialchars($search); ?>">Siguiente</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 