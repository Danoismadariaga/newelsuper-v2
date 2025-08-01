<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('ver_clientes');

// Configuración de paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Búsqueda
$busqueda = isset($_GET['busqueda']) ? sanitizeInput($_GET['busqueda']) : '';
$where = '';
$params = [];

if (!empty($busqueda)) {
    $where = "WHERE nombre LIKE :busqueda OR apellido LIKE :busqueda OR email LIKE :busqueda";
    $params[':busqueda'] = "%$busqueda%";
}

// Obtener total de registros
$db = getDBConnection();
$stmt = $db->prepare("SELECT COUNT(*) as total FROM clientes $where");
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$total_registros = $stmt->fetch()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener clientes
$stmt = $db->prepare("
    SELECT * FROM clientes 
    $where 
    ORDER BY fecha_registro DESC 
    LIMIT :offset, :limit
");

$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
if (!empty($params)) {
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$clientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/modern.css" rel="stylesheet">
</head>
<body>
    <?php
    if (!isset($user_info)) {
        $user_info = ['nombre' => 'Usuario', 'apellido' => '', 'nombre_rol' => ''];
    }
    ?>
    <?php include_once '../includes/navbar.php'; ?>
    <aside class="sidebar">
        <div class="sidebar-title">Menú</div>
        <ul class="sidebar-nav">
            <li><a href="../clientes/consulta.php" class="active"><i class="bi bi-people"></i> <span>Clientes</span></a></li>
            <li><a href="../empleados/consulta.php"><i class="bi bi-person-badge"></i> <span>Empleados</span></a></li>
            <li><a href="../productos/consulta.php"><i class="bi bi-box"></i> <span>Productos</span></a></li>
            <li><a href="../ventas/consulta.php"><i class="bi bi-cash-stack"></i> <span>Ventas</span></a></li>
            <li><a href="../inventario/consulta.php"><i class="bi bi-bar-chart"></i> <span>Inventario</span></a></li>
            <li><a href="../usuarios/consulta.php"><i class="bi bi-gear"></i> <span>Gestión de Usuarios</span></a></li>
            <li><a href="../actividad/consulta.php"><i class="bi bi-clipboard-data"></i> <span>Actividad</span></a></li>
        </ul>
    </aside>
    <main class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4"><i class="bi bi-people"></i> Gestión de Clientes</h2>
            <?php if (hasPermission('crear_clientes')): ?>
                <a href="crear.php" class="btn btn-success mb-3"><i class="bi bi-person-plus"></i> Crear Nuevo Cliente</a>
            <?php endif; ?>
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="busqueda" placeholder="Buscar por nombre, apellido o email" value="<?php echo htmlspecialchars($busqueda); ?>">
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
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clientes)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No se encontraron clientes</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <tr>
                                            <td><?php echo $cliente['id']; ?></td>
                                            <td><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($cliente['apellido']); ?></td>
                                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                            <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($cliente['fecha_registro'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if (hasPermission('editar_clientes')): ?>
                                                        <a href="editar.php?id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                    <?php endif; ?>
                                                    <?php if (hasPermission('eliminar_clientes')): ?>
                                                        <a href="eliminar.php?id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de que desea eliminar este cliente?');"><i class="bi bi-trash"></i></a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_paginas > 1): ?>
                        <nav aria-label="Navegación de páginas">
                            <ul class="pagination justify-content-center">
                                <?php if ($pagina_actual > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>">Anterior</a>
                                    </li>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <li class="page-item <?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                                        <a class="page-link" href="?pagina=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($pagina_actual < $total_paginas): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>">Siguiente</a>
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