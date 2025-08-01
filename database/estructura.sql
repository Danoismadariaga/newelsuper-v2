-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS sistema_gestion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_gestion;

-- Tabla de roles
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_rol VARCHAR(50) UNIQUE NOT NULL
) ENGINE=InnoDB;

-- Tabla de permisos
CREATE TABLE permisos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_permiso VARCHAR(100) UNIQUE NOT NULL
) ENGINE=InnoDB;

-- Tabla de relación roles-permisos
CREATE TABLE rol_permisos (
    id_rol INT,
    id_permiso INT,
    PRIMARY KEY (id_rol, id_permiso),
    FOREIGN KEY (id_rol) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (id_permiso) REFERENCES permisos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    id_rol INT NOT NULL,
    FOREIGN KEY (id_rol) REFERENCES roles(id)
) ENGINE=InnoDB;

-- Tabla de clientes
CREATE TABLE clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE,
    telefono VARCHAR(20),
    direccion VARCHAR(255),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de empleados
CREATE TABLE empleados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE,
    telefono VARCHAR(20),
    puesto VARCHAR(100),
    fecha_contratacion DATE,
    id_usuario INT UNIQUE NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de productos
CREATE TABLE productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de inventario
CREATE TABLE inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    tipo_movimiento ENUM('ENTRADA', 'SALIDA') NOT NULL,
    cantidad INT NOT NULL,
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_empleado_responsable INT NOT NULL,
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_empleado_responsable) REFERENCES empleados(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Tabla de ventas
CREATE TABLE ventas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_cliente INT NOT NULL,
    fecha_venta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10, 2) NOT NULL,
    estado ENUM('PENDIENTE', 'COMPLETADA', 'CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
    id_empleado INT NOT NULL,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Tabla de detalle de ventas
CREATE TABLE detalle_ventas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Tabla de registro de actividades
CREATE TABLE registro_actividades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    accion VARCHAR(255) NOT NULL,
    detalle TEXT,
    fecha_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insertar roles iniciales
INSERT INTO roles (nombre_rol) VALUES 
('Administrador'),
('Vendedor'),
('Inventario');

-- Insertar permisos iniciales
INSERT INTO permisos (nombre_permiso) VALUES 
-- Permisos de clientes
('ver_clientes'),
('crear_clientes'),
('editar_clientes'),
('eliminar_clientes'),
-- Permisos de empleados
('ver_empleados'),
('crear_empleados'),
('editar_empleados'),
('eliminar_empleados'),
-- Permisos de productos
('ver_productos'),
('crear_productos'),
('editar_productos'),
('eliminar_productos'),
-- Permisos de ventas
('ver_ventas'),
('crear_ventas'),
('editar_ventas'),
('eliminar_ventas'),
-- Permisos de inventario
('ver_inventario'),
('registrar_entrada_inventario'),
('registrar_salida_inventario'),
('ver_movimientos_inventario'),
-- Permisos de sistema
('gestionar_usuarios'),
('ver_actividad'),
('cambiar_password');

-- Asignar todos los permisos al rol Administrador
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT 1, id FROM permisos;

-- Asignar permisos al rol Vendedor
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT 2, id FROM permisos 
WHERE nombre_permiso IN (
    'ver_clientes', 'crear_clientes', 'editar_clientes',
    'ver_productos',
    'ver_ventas', 'crear_ventas', 'editar_ventas',
    'cambiar_password'
);

-- Asignar permisos al rol Inventario
INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT 3, id FROM permisos 
WHERE nombre_permiso IN (
    'ver_productos', 'crear_productos', 'editar_productos',
    'ver_inventario', 'registrar_entrada_inventario', 
    'registrar_salida_inventario', 'ver_movimientos_inventario',
    'cambiar_password'
);

-- Eliminar usuario administrador inicial 