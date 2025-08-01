<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('crear_clientes');

$error = '';
$success = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizeInput($_POST['nombre'] ?? '');
    $apellido = sanitizeInput($_POST['apellido'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefono = sanitizeInput($_POST['telefono'] ?? '');
    $direccion = sanitizeInput($_POST['direccion'] ?? '');

    // Validaciones
    if (empty($nombre) || empty($apellido)) {
        $error = 'El nombre y apellido son obligatorios.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } else {
        try {
            $db = getDBConnection();
            
            // Verificar si el email ya existe
            if (!empty($email)) {
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM clientes WHERE email = :email");
                $stmt->execute([':email' => $email]);
                if ($stmt->fetch()['total'] > 0) {
                    $error = 'El email ya está registrado.';
                }
            }

            if (empty($error)) {
                // Insertar el cliente
                $stmt = $db->prepare("
                    INSERT INTO clientes (nombre, apellido, email, telefono, direccion)
                    VALUES (:nombre, :apellido, :email, :telefono, :direccion)
                ");

                $stmt->execute([
                    ':nombre' => $nombre,
                    ':apellido' => $apellido,
                    ':email' => $email,
                    ':telefono' => $telefono,
                    ':direccion' => $direccion
                ]);

                registrarActividad('crear_cliente', "Cliente creado: $nombre $apellido");
                $success = 'Cliente creado exitosamente.';
            }
        } catch (PDOException $e) {
            $error = 'Error al crear el cliente. Por favor, intente nuevamente.';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cliente - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/modern.css" rel="stylesheet">
</head>
<body>
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
            <h2 class="mb-4"><i class="bi bi-person-plus"></i> Crear Nuevo Cliente</h2>
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre" class="form-label">Nombre *</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" 
                                               value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor, ingrese el nombre.
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="apellido" class="form-label">Apellido *</label>
                                        <input type="text" class="form-control" id="apellido" name="apellido" 
                                               value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor, ingrese el apellido.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        <div class="invalid-feedback">
                                            Por favor, ingrese un email válido.
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                                               value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="direccion" class="form-label">Dirección</label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="3"><?php 
                                        echo htmlspecialchars($_POST['direccion'] ?? ''); 
                                    ?></textarea>
                                </div>

                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                            <a href="consulta.php" class="btn btn-secondary">Volver a Clientes</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 