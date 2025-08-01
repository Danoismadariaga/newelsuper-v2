<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
$db = getDBConnection();

$message = '';
$message_type = '';

// Paginación
$registros_por_pagina = 10;
$pagina = isset($_GET['pagina']) ? filter_var($_GET['pagina'], FILTER_VALIDATE_INT) : 1;
$offset = ($pagina - 1) * $registros_por_pagina;

// Filtro de búsqueda
$search_query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$search_condition = '';
$params = [];

if (!empty($search_query)) {
    $search_condition = " WHERE u.usuario LIKE :search_query OR r.nombre_rol LIKE :search_query ";
    $params[':search_query'] = '%' . $search_query . '%';
}

// Consulta total de registros para paginación
$stmt_count = $db->prepare("SELECT COUNT(*) FROM usuarios u JOIN roles r ON u.id_rol = r.id" . $search_condition);
$stmt_count->execute($params);
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta de usuarios
$stmt_usuarios = $db->prepare("
    SELECT u.id, u.usuario, r.nombre_rol
    FROM usuarios u
    JOIN roles r ON u.id_rol = r.id
    " . $search_condition . "
    ORDER BY u.usuario ASC
    LIMIT :limit OFFSET :offset
");
$stmt_usuarios->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt_usuarios->bindParam(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => &$val) {
    $stmt_usuarios->bindParam($key, $val, PDO::PARAM_STR);
}
$stmt_usuarios->execute();
$usuarios = $stmt_usuarios->fetchAll();

// Obtener información del usuario actual para el navbar
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("
        SELECT e.nombre, e.apellido, r.nombre_rol
        FROM usuarios u
        LEFT JOIN empleados e ON u.id = e.id_usuario
        JOIN roles r ON u.id_rol = r.id
        WHERE u.id = :user_id
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user_info = $stmt->fetch();

    if (!$user_info) {
        $stmt = $db->prepare("
            SELECT u.usuario as nombre, r.nombre_rol
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id
            WHERE u.id = :user_id
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user_info = $stmt->fetch();
        $user_info['apellido'] = '';
    }
}

if (!isset($user_info)) {
    $user_info = ['nombre' => 'Usuario', 'apellido' => '', 'nombre_rol' => ''];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?php echo APP_NAME; ?></title>
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
            <li><a href="../inventario/consulta.php"><i class="bi bi-bar-chart"></i> <span>Inventario</span></a></li>
            <li><a href="../usuarios/consulta.php" class="active"><i class="bi bi-gear"></i> <span>Gestión de Usuarios</span></a></li>
            <li><a href="../actividad/consulta.php"><i class="bi bi-clipboard-data"></i> <span>Actividad</span></a></li>
        </ul>
    </aside>
    <main class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4"><i class="bi bi-gear"></i> Gestión de Usuarios</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <form action="consulta.php" method="GET" class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="q" placeholder="Buscar por usuario o rol..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
                            <a href="consulta.php" class="btn btn-secondary ms-2"><i class="bi bi-arrow-counterclockwise"></i> Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>
            <a href="crear.php" class="btn btn-success mb-3"><i class="bi bi-person-plus"></i> Crear Nuevo Usuario</a>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($usuarios)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No se encontraron usuarios registrados.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['nombre_rol']); ?></td>
                                            <td>
                                                <a href="editar.php?id=<?php echo htmlspecialchars($usuario['id']); ?>" class="btn btn-sm btn-warning me-1"><i class="bi bi-pencil"></i> Editar</a>
                                                <a href="eliminar.php?id=<?php echo htmlspecialchars($usuario['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar este usuario?');"><i class="bi bi-trash"></i> Eliminar</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Paginación -->
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?><?php echo !empty($search_query) ? '&q=' . urlencode($search_query) : ''; ?>">Anterior</a>
                                </li>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo !empty($search_query) ? '&q=' . urlencode($search_query) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($pagina < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?><?php echo !empty($search_query) ? '&q=' . urlencode($search_query) : ''; ?>">Siguiente</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 