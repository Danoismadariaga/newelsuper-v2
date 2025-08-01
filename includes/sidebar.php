<?php
// Sidebar moderno para el dashboard
$modulos = [
    ["Inicio", "../index.php", "bi-house"],
    ["Clientes", "../clientes/consulta.php", "bi-people"],
    ["Empleados", "../empleados/consulta.php", "bi-person-badge"],
    ["Productos", "../productos/consulta.php", "bi-box-seam"],
    ["Ventas", "../ventas/consulta.php", "bi-cart"],
    ["Inventario", "../inventario/consulta.php", "bi-archive"],
    ["Usuarios", "../usuarios/consulta.php", "bi-person-lines-fill"],
    ["Actividad", "../actividad/consulta.php", "bi-graph-up"],
];
$current = $_SERVER['REQUEST_URI'];
?>
<aside class="sidebar">
    <div class="sidebar-title">Menú</div>
    <ul class="sidebar-nav">
        <?php foreach ($modulos as $mod): ?>
            <li>
                <a href="<?php echo $mod[1]; ?>" class="<?php echo strpos($current, basename($mod[1], '.php')) !== false ? 'active' : ''; ?>">
                    <i class="bi <?php echo $mod[2]; ?>"></i>
                    <span><?php echo $mod[0]; ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</aside> 