<?php
define('SECURE_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/auth.php';
 
// Cerrar la sesión y redirigir al login
cerrarSesion(); 