# Solución de Problemas de Permisos - Sistema de Gestión

## Problema Común: Error de Permisos en Módulo Usuarios

Si estás experimentando problemas de permisos al acceder al módulo de usuarios, sigue esta guía paso a paso.

## 🔍 Diagnóstico Rápido

### 1. Ejecutar Diagnóstico Automático
Accede a: `http://localhost/newelsuper/debug_permisos.php`

Este script te mostrará:
- Estado de tu sesión
- Roles disponibles en el sistema
- Permisos disponibles
- Permisos que tienes asignados
- Verificación específica del permiso `gestionar_usuarios`

### 2. Verificar desde el Módulo Usuarios
Accede a: `http://localhost/newelsuper/usuarios/verificar_permisos.php`

Este script verifica específicamente los permisos del módulo de usuarios.

## 🛠️ Soluciones

### Solución Automática (Recomendada)
1. Accede a: `http://localhost/newelsuper/fix_permisos.php`
2. Este script:
   - Verifica que existan todos los roles necesarios
   - Verifica que existan todos los permisos necesarios
   - Asigna los permisos correctos a cada rol
   - Verifica tu usuario actual
   - Te permite cambiar automáticamente a rol Administrador si es necesario

### Solución Manual

#### Opción 1: Cambiar tu usuario a Administrador
```sql
-- Ejecutar en phpMyAdmin o MySQL
UPDATE usuarios SET id_rol = 1 WHERE id = [TU_USER_ID];
```

#### Opción 2: Asignar permiso específico a tu rol
```sql
-- Verificar que el permiso existe
SELECT id FROM permisos WHERE nombre_permiso = 'gestionar_usuarios';

-- Asignar el permiso a tu rol
INSERT INTO rol_permisos (id_rol, id_permiso) 
SELECT [TU_ROL_ID], id FROM permisos WHERE nombre_permiso = 'gestionar_usuarios';
```

## 📋 Roles y Permisos del Sistema

### Rol: Administrador (ID: 1)
**Permisos completos:**
- Todos los permisos del sistema
- `gestionar_usuarios` ✅
- `ver_actividad` ✅
- `cambiar_password` ✅

### Rol: Vendedor (ID: 2)
**Permisos limitados:**
- `ver_clientes`, `crear_clientes`, `editar_clientes`
- `ver_productos`
- `ver_ventas`, `crear_ventas`, `editar_ventas`
- `cambiar_password`
- ❌ **NO tiene** `gestionar_usuarios`

### Rol: Inventario (ID: 3)
**Permisos limitados:**
- `ver_productos`, `crear_productos`, `editar_productos`
- `ver_inventario`, `registrar_entrada_inventario`, `registrar_salida_inventario`
- `ver_movimientos_inventario`
- `cambiar_password`
- ❌ **NO tiene** `gestionar_usuarios`

## 🔧 Verificación de Base de Datos

### Estructura Requerida
```sql
-- Verificar que existan las tablas
SHOW TABLES;

-- Verificar roles
SELECT * FROM roles;

-- Verificar permisos
SELECT * FROM permisos;

-- Verificar asignaciones de permisos
SELECT r.nombre_rol, p.nombre_permiso 
FROM roles r 
JOIN rol_permisos rp ON r.id = rp.id_rol 
JOIN permisos p ON rp.id_permiso = p.id 
ORDER BY r.nombre_rol, p.nombre_permiso;
```

## 🚨 Problemas Comunes

### 1. "No tiene permisos para acceder a esta página"
**Causa:** El usuario no tiene el permiso `gestionar_usuarios`
**Solución:** 
- Ejecutar `fix_permisos.php`
- O cambiar el usuario a rol Administrador

### 2. "Usuario NO autenticado"
**Causa:** Sesión expirada o no iniciada
**Solución:** 
- Ir a `login.php` e iniciar sesión
- Verificar que las cookies estén habilitadas

### 3. "Error de conexión a la base de datos"
**Causa:** Problemas de configuración de MySQL
**Solución:**
- Verificar que XAMPP esté ejecutándose
- Verificar credenciales en `includes/config.php`
- Ejecutar `database/setup.php` para recrear la base de datos

## 📞 Soporte

Si los problemas persisten:

1. **Ejecutar diagnóstico completo:**
   ```
   http://localhost/newelsuper/debug_permisos.php
   ```

2. **Verificar logs de error:**
   - Revisar logs de PHP en XAMPP
   - Verificar logs de MySQL

3. **Recrear base de datos:**
   ```
   http://localhost/newelsuper/database/setup.php
   ```

## 🔐 Credenciales por Defecto

**Usuario Administrador:**
- Usuario: `admin`
- Contraseña: `password`

**Nota:** Cambia estas credenciales después de la primera configuración por seguridad. 