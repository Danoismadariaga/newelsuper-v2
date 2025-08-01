<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('ver_inventario');

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
    $search_query = " WHERE (p.nombre LIKE :search OR e.nombre LIKE :search_empleado OR e.apellido LIKE :search_apellido)";
    $search_params[':search'] = '%' . $search . '%';
    $search_params[':search_empleado'] = '%' . $search . '%';
    $search_params[':search_apellido'] = '%' . $search . '%';
}

// Obtener el total de movimientos para la paginación
$stmt_total = $db->prepare("
    SELECT COUNT(*) as total
    FROM inventario i
    JOIN productos p ON i.id_producto = p.id
    JOIN empleados e ON i.id_empleado_responsable = e.id
    " . $search_query
);

foreach ($search_params as $key => &$val) {
    $stmt_total->bindParam($key, $val, PDO::PARAM_STR);
}
$stmt_total->execute();
$total_records = $stmt_total->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Obtener movimientos de inventario con paginación y búsqueda
$stmt = $db->prepare("
    SELECT i.id, p.nombre as producto_nombre, i.tipo_movimiento, i.cantidad, i.fecha_movimiento,
           e.nombre as empleado_nombre, e.apellido as empleado_apellido
    FROM inventario i
    JOIN productos p ON i.id_producto = p.id
    JOIN empleados e ON i.id_empleado_responsable = e.id
    " . $search_query . "
    ORDER BY i.fecha_movimiento DESC
    LIMIT :limit OFFSET :offset
");

foreach ($search_params as $key => &$val) {
    $stmt->bindParam($key, $val, PDO::PARAM_STR);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$movimientos = $stmt->fetchAll();

if (!isset($user_info)) {
    $user_info = ['nombre' => 'Usuario', 'apellido' => '', 'nombre_rol' => ''];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - <?php echo APP_NAME; ?></title>
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
            <li><a href="../ventas/consulta.php"><i class="bi bi-cash-stack"></i> <span>Ventas</span></a></li>
            <li><a href="../inventario/consulta.php" class="active"><i class="bi bi-bar-chart"></i> <span>Inventario</span></a></li>
            <li><a href="../usuarios/consulta.php"><i class="bi bi-gear"></i> <span>Gestión de Usuarios</span></a></li>
            <li><a href="../actividad/consulta.php"><i class="bi bi-clipboard-data"></i> <span>Actividad</span></a></li>
        </ul>
    </aside>
    <main class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4"><i class="bi bi-bar-chart"></i> Gestión de Inventario</h2>
            <?php if (hasPermission('registrar_entrada_inventario')): ?>
                <a href="crear_entrada.php" class="btn btn-success mb-3"><i class="bi bi-plus-circle"></i> Registrar Entrada</a>
            <?php endif; ?>
            <?php if (hasPermission('registrar_salida_inventario')): ?>
                <a href="crear_salida.php" class="btn btn-warning mb-3"><i class="bi bi-dash-circle"></i> Registrar Salida</a>
            <?php endif; ?>
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="busqueda" placeholder="Buscar por producto o responsable" value="<?php echo htmlspecialchars($search); ?>">
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
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Fecha</th>
                                    <th>Responsable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movimientos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No se encontraron movimientos de inventario</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($movimientos as $mov): ?>
                                        <tr>
                                            <td><?php echo $mov['id']; ?></td>
                                            <td><?php echo htmlspecialchars($mov['producto_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($mov['tipo_movimiento']); ?></td>
                                            <td><?php echo $mov['cantidad']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])); ?></td>
                                            <td><?php echo htmlspecialchars($mov['empleado_nombre'] . ' ' . $mov['empleado_apellido']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Navegación de páginas">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Anterior</a>
                                    </li>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Siguiente</a>
                                    </li>
                                <?php endif; ?>
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