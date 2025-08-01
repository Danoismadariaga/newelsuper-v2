# Sistema de Gestión Empresarial

Sistema de gestión para pequeñas empresas que incluye módulos de Clientes, Empleados, Productos, Ventas e Inventario.

## Características Principales

- Sistema de autenticación y autorización basado en roles
- Gestión completa de clientes
- Administración de empleados
- Control de inventario
- Sistema de ventas
- Gestión de productos
- Registro de actividades

## Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP requeridas:
  - PDO
  - PDO_MySQL
  - bcrypt

## Instalación

1. Clonar el repositorio en el directorio del servidor web
2. Importar la base de datos usando el archivo `database/estructura.sql`
3. Configurar la conexión a la base de datos en `includes/config.php`
4. Acceder al sistema usando las credenciales por defecto:
   - Usuario: admin
   - Contraseña: password

## Estructura del Proyecto

```
├── index.html
├── login.php
├── logout.php
├── error.php
├── cambiar_password.php
├── includes/
│   ├── config.php
│   └── auth.php
├── css/
│   └── styles.css
├── js/
├── database/
│   └── estructura.sql
├── clientes/
├── empleados/
├── productos/
├── ventas/
└── inventario/
```

## Seguridad

- Contraseñas cifradas con bcrypt
- Protección contra inyección SQL usando PDO
- Control de acceso basado en roles
- Validación de entrada de datos
- Manejo seguro de sesiones

## Licencia

Este proyecto está bajo la Licencia MIT. 