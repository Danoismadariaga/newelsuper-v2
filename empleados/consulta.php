<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('ver_empleados');

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
    $search_query = " WHERE (e.nombre LIKE :search OR e.apellido LIKE :search OR e.email LIKE :search OR u.usuario LIKE :search)";
    $search_params[':search'] = '%' . $search . '%';
}

// Obtener el total de empleados para la paginación
$stmt_total = $db->prepare("SELECT COUNT(*) as total FROM empleados e" . $search_query);
$stmt_total->execute($search_params);
$total_records = $stmt_total->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Obtener empleados con paginación y búsqueda
$stmt = $db->prepare("
    SELECT e.id, e.nombre, e.apellido, e.email, e.telefono, e.puesto, e.fecha_contratacion, u.usuario, r.nombre_rol
    FROM empleados e
    JOIN usuarios u ON e.id_usuario = u.id
    JOIN roles r ON u.id_rol = r.id
    " . $search_query . "
    ORDER BY e.nombre ASC
    LIMIT :limit OFFSET :offset
");

foreach ($search_params as $key => &$val) {
    $stmt->bindParam($key, $val, PDO::PARAM_STR);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$empleados = $stmt->fetchAll();

if (!isset($user_info)) {
    $user_info = ['nombre' => 'Usuario', 'apellido' => '', 'nombre_rol' => ''];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados - <?php echo APP_NAME; ?></title>
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
            <li><a href="../empleados/consulta.php" class="active"><i class="bi bi-person-badge"></i> <span>Empleados</span></a></li>
            <li><a href="../productos/consulta.php"><i class="bi bi-box"></i> <span>Productos</span></a></li>
            <li><a href="../ventas/consulta.php"><i class="bi bi-cash-stack"></i> <span>Ventas</span></a></li>
            <li><a href="../inventario/consulta.php"><i class="bi bi-bar-chart"></i> <span>Inventario</span></a></li>
            <li><a href="../usuarios/consulta.php"><i class="bi bi-gear"></i> <span>Gestión de Usuarios</span></a></li>
            <li><a href="../actividad/consulta.php"><i class="bi bi-clipboard-data"></i> <span>Actividad</span></a></li>
        </ul>
    </aside>
    <main class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4"><i class="bi bi-person-badge"></i> Gestión de Empleados</h2>
            <?php if (hasPermission('crear_empleados')): ?>
                <a href="crear.php" class="btn btn-success mb-3"><i class="bi bi-person-plus"></i> Crear Nuevo Empleado</a>
            <?php endif; ?>
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="busqueda" placeholder="Buscar por nombre, apellido o email" value="<?php echo htmlspecialchars($search); ?>">
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
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Puesto</th>
                                    <th>Fecha Contratación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($empleados)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No se encontraron empleados</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($empleados as $empleado): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($empleado['id']); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['apellido']); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['telefono'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['puesto'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['fecha_contratacion']); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if (hasPermission('editar_empleados')): ?>
                                                        <a href="editar.php?id=<?php echo $empleado['id']; ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                    <?php endif; ?>
                                                    <?php if (hasPermission('eliminar_empleados')): ?>
                                                        <a href="eliminar.php?id=<?php echo $empleado['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de que desea eliminar este empleado?');"><i class="bi bi-trash"></i></a>
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