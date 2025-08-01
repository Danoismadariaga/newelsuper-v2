<?php
define('SECURE_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Verificar que el usuario esté autenticado
if (!isAuthenticated()) {
    redirect(APP_URL . '/login.php');
}

// Obtener información del usuario actual
$user_info = null;
$db = getDBConnection();

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
        // Si no se encuentra información del empleado asociada, intentar obtener solo de usuario y rol
        $stmt = $db->prepare("
            SELECT u.usuario as nombre, r.nombre_rol
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id
            WHERE u.id = :user_id
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user_info = $stmt->fetch();
        $user_info['apellido'] = ''; // Para evitar errores si se espera el apellido
    }
}

// Si por alguna razón la información del usuario no se pudo obtener, forzar logout
if (!$user_info) {
    error_log("Error crítico: No se pudo obtener la información del usuario para user_id: " . ($_SESSION['user_id'] ?? 'No definido') . ". Forzando logout.");
    cerrarSesion();
    exit();
}

// Definir los módulos disponibles
$modulos = [
    'clientes' => [
        'nombre' => 'Clientes',
        'icono' => '👥',
        'permiso' => 'ver_clientes',
        'url' => 'clientes/consulta.php'
    ],
    'empleados' => [
        'nombre' => 'Empleados',
        'icono' => '👨‍💼',
        'permiso' => 'ver_empleados',
        'url' => 'empleados/consulta.php'
    ],
    'productos' => [
        'nombre' => 'Productos',
        'icono' => '📦',
        'permiso' => 'ver_productos',
        'url' => 'productos/consulta.php'
    ],
    'ventas' => [
        'nombre' => 'Ventas',
        'icono' => '💰',
        'permiso' => 'ver_ventas',
        'url' => 'ventas/consulta.php'
    ],
    'inventario' => [
        'nombre' => 'Inventario',
        'icono' => '📊',
        'permiso' => 'ver_inventario',
        'url' => 'inventario/consulta.php'
    ],
    'reportes' => [
        'nombre' => 'Reportes',
        'icono' => '📈',
        'permiso' => 'ver_reportes',
        'url' => 'reportes/index.php'
    ],
    'gestion_usuarios' => [
        'nombre' => 'Gestión de Usuarios',
        'icono' => '⚙️',
        'permiso' => 'gestionar_usuarios',
        'url' => 'usuarios/consulta.php'
    ],
    'registro_actividad' => [
        'nombre' => 'Registro de Actividad',
        'icono' => '📋',
        'permiso' => 'ver_actividad',
        'url' => 'actividad/consulta.php'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/modern.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-title">Menú</div>
        <ul class="sidebar-nav">
            <li><a href="clientes/consulta.php" class="<?php if (isset($modulos['clientes']) && hasPermission($modulos['clientes']['permiso'])) echo 'active'; ?>"><i class="bi bi-people"></i> <span>Clientes</span></a></li>
            <li><a href="empleados/consulta.php" class="<?php if (isset($modulos['empleados']) && hasPermission($modulos['empleados']['permiso'])) echo 'active'; ?>"><i class="bi bi-person-badge"></i> <span>Empleados</span></a></li>
            <li><a href="productos/consulta.php" class="<?php if (isset($modulos['productos']) && hasPermission($modulos['productos']['permiso'])) echo 'active'; ?>"><i class="bi bi-box"></i> <span>Productos</span></a></li>
            <li><a href="ventas/consulta.php" class="<?php if (isset($modulos['ventas']) && hasPermission($modulos['ventas']['permiso'])) echo 'active'; ?>"><i class="bi bi-cash-stack"></i> <span>Ventas</span></a></li>
            <li><a href="inventario/consulta.php" class="<?php if (isset($modulos['inventario']) && hasPermission($modulos['inventario']['permiso'])) echo 'active'; ?>"><i class="bi bi-bar-chart"></i> <span>Inventario</span></a></li>
            <li><a href="usuarios/consulta.php" class="<?php if (isset($modulos['gestion_usuarios']) && hasPermission($modulos['gestion_usuarios']['permiso'])) echo 'active'; ?>"><i class="bi bi-gear"></i> <span>Gestión de Usuarios</span></a></li>
            <li><a href="actividad/consulta.php" class="<?php if (isset($modulos['registro_actividad']) && hasPermission($modulos['registro_actividad']['permiso'])) echo 'active'; ?>"><i class="bi bi-clipboard-data"></i> <span>Actividad</span></a></li>
        </ul>
    </aside>

    <!-- Main content -->
    <main class="main-content">
        <div class="container-fluid">
            <h2 class="mb-2">Bienvenido, <?php echo htmlspecialchars($user_info['nombre'] ?? 'Usuario'); ?></h2>
            <p class="text-muted mb-4">Rol: <?php echo htmlspecialchars($user_info['nombre_rol'] ?? 'Desconocido'); ?></p>
            <div class="row g-4">
                <?php foreach ($modulos as $key => $modulo): ?>
                    <?php if (hasPermission($modulo['permiso'])): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 d-flex flex-column align-items-center justify-content-center text-center">
                                <div class="module-icon mb-2" style="font-size:2.5rem; color:var(--color-primary);">
                                    <?php echo $modulo['icono']; ?>
                                </div>
                                <h5 class="card-title mb-2"><?php echo $modulo['nombre']; ?></h5>
                                <a href="<?php echo $modulo['url']; ?>" class="btn btn-primary mt-auto">Acceder</a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 