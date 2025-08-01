<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar permisos
verificarAcceso('eliminar_clientes');

$error = '';
$success = '';

// Obtener ID del cliente
$id_cliente = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_cliente) {
    header('Location: consulta.php');
    exit;
}

$db = getDBConnection();

// Verificar si el cliente existe
$stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id");
$stmt->execute([':id' => $id_cliente]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header('Location: consulta.php');
    exit;
}

// Verificar si el cliente tiene ventas asociadas
$stmt = $db->prepare("SELECT COUNT(*) as total FROM ventas WHERE id_cliente = :id");
$stmt->execute([':id' => $id_cliente]);
if ($stmt->fetch()['total'] > 0) {
    $error = 'No se puede eliminar el cliente porque tiene ventas asociadas.';
} else {
    try {
        $db->beginTransaction();

        // Eliminar el cliente
        $stmt = $db->prepare("DELETE FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $id_cliente]);

        $db->commit();
        registrarActividad('eliminar_cliente', "Cliente eliminado: {$cliente['nombre']} {$cliente['apellido']}");
        $success = 'Cliente eliminado exitosamente.';
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error al eliminar el cliente: ' . $e->getMessage();
        error_log($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Cliente - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="consulta.php">Clientes</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Eliminar Cliente</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                                <div class="mt-3">
                                    <a href="consulta.php" class="btn btn-primary">Volver a la Lista</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                                <div class="mt-3">
                                    <a href="consulta.php" class="btn btn-primary">Volver a la Lista</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 