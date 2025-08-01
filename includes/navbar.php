<?php
// Nueva barra superior para dashboard moderno
if (!isset($user_info)) $user_info = null;
?>
<nav class="navbar-dashboard">
    <a class="brand" href="<?php echo APP_URL; ?>/index.php"><?php echo APP_NAME; ?></a>
    <div class="user">
        <i class="bi bi-person-circle"></i>
        <?php if ($user_info && isset($user_info['nombre'])): ?>
            <?php echo htmlspecialchars($user_info['nombre'] . ' ' . ($user_info['apellido'] ?? '')); ?>
            <span style="font-size:0.95em; color:#c7d2fe; margin-left:0.5em;">
                (<?php echo htmlspecialchars($user_info['nombre_rol'] ?? ''); ?>)
            </span>
        <?php endif; ?>
        <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm ms-3">Salir</a>
    </div>
</nav> 