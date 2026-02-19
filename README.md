# Sistema Monolítico de Biblioteca Online

Sistema web completo para la gestión de una biblioteca, desarrollado en PHP 8+ con PostgreSQL. Permite gestionar libros, usuarios y préstamos de manera eficiente y segura.

## 📋 Características

- **Gestión de Libros**: CRUD completo con búsqueda avanzada
- **Gestión de Usuarios**: Administración de usuarios con roles (admin, bibliotecario, lector)
- **Gestión de Préstamos**: Control de préstamos con validación de disponibilidad
- **Interfaz Intuitiva**: Diseño limpio y responsive
- **Seguridad**: Prepared statements y sanitización de datos
- **Validaciones**: Control de reglas de negocio y validación de datos

## 🛠️ Requisitos

- PHP 8.0 o superior
- PostgreSQL 12 o superior
- Servidor web (Apache/Nginx) con PHP habilitado
- Extensiones PHP requeridas:
  - `pdo`
  - `pdo_pgsql`

## 📦 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/Santiago-IA/Biblioteca.git
cd Biblioteca
```

### 2. Configurar la base de datos

Asegúrate de tener PostgreSQL instalado y ejecuta el script SQL `db_biblioteca.sql` para crear las tablas necesarias:

```sql
-- Ejemplo de estructura (ajustar según tu esquema)
CREATE DATABASE db_biblioteca;

-- Tablas: usuarios, libros, prestamos
-- Vista: vw_libros_disponibilidad
```

### 3. Configurar conexión

El archivo `conexion.php` ya está configurado con las siguientes credenciales:

```php
$DB_HOST = "127.0.0.1";
$DB_PORT = "5432";
$DB_NAME = "db_biblioteca";
$DB_USER = "postgres";
$DB_PASS = "2002";
```

Si necesitas cambiar estas credenciales, edita el archivo `conexion.php`.

### 4. Configurar servidor web

Coloca los archivos en el directorio de tu servidor web (por ejemplo, `htdocs` en XAMPP o `/var/www/html` en Linux).

## 📁 Estructura de Archivos

```
biblioteca_online/
├── index.php          # Página principal con resumen
├── libros.php         # Gestión de libros (CRUD)
├── usuarios.php       # Gestión de usuarios (CRUD)
├── prestamos.php      # Gestión de préstamos
├── conexion.php       # Configuración de conexión PDO
├── estilos.css        # Estilos CSS del sistema
└── README.md          # Este archivo
```

## 🗄️ Estructura de Base de Datos

### Tablas Principales

#### `usuarios`
- `id` (PK)
- `documento`
- `nombre`
- `email`
- `rol` (admin, bibliotecario, lector)
- `fecha_creacion`

#### `libros`
- `id` (PK)
- `isbn`
- `titulo`
- `autor`
- `editorial`
- `anio`
- `total_ejemplares`

#### `prestamos`
- `id` (PK)
- `usuario_id` (FK)
- `libro_id` (FK)
- `fecha_prestamo`
- `fecha_vencimiento`
- `fecha_devolucion`
- `observacion`

#### Vista `vw_libros_disponibilidad`
- Calcula los ejemplares disponibles restando los préstamos activos del total de ejemplares
- Campo `disponibles`: `total_ejemplares - COUNT(prestamos activos)`

## 🚀 Funcionalidades

### Página Principal (`index.php`)

- **Resumen del sistema**: Muestra contadores de:
  - Total de libros
  - Total de usuarios
  - Préstamos activos
- **Navegación rápida**: Acceso directo a cada módulo

### Gestión de Libros (`libros.php`)

- **Listado**: Muestra todos los libros con información completa
- **Búsqueda**: Buscar por título, autor o ISBN (búsqueda case-insensitive)
- **Crear libro**: Formulario para agregar nuevos libros
- **Editar libro**: Modificar información de libros existentes
- **Eliminar libro**: Con validación (no permite eliminar si tiene préstamos asociados)
- **Validaciones**:
  - Campos requeridos: ISBN, Título, Autor, Editorial, Total Ejemplares
  - Año: Debe ser numérico positivo o vacío
  - Total Ejemplares: Debe ser >= 0

### Gestión de Usuarios (`usuarios.php`)

- **Listado**: Muestra todos los usuarios del sistema
- **Búsqueda**: Buscar por nombre, documento o email
- **Crear usuario**: Formulario para registrar nuevos usuarios
- **Editar usuario**: Modificar datos de usuarios existentes
- **Eliminar usuario**: Con validación (no permite eliminar si tiene préstamos)
- **Roles disponibles**:
  - `admin`: Administrador del sistema
  - `bibliotecario`: Personal de biblioteca
  - `lector`: Usuario final
- **Validaciones**:
  - Campos requeridos: Documento, Nombre, Rol
  - Email: Debe ser válido si se proporciona

### Gestión de Préstamos (`prestamos.php`)

#### Crear Préstamo

- **Selección de usuario**: Dropdown con formato "documento - nombre"
- **Selección de libro**: Dropdown mostrando "título - autor (Disponibles: X)"
- **Días de préstamo**: Campo numérico (default: 7 días)
- **Observación**: Campo opcional para notas
- **Validación automática**: No permite crear préstamo si `disponibles <= 0`
- **Cálculo de vencimiento**: `fecha_vencimiento = CURRENT_DATE + días`

#### Listado de Préstamos

- **Filtros por estado**:
  - Todos
  - Activos (fecha_devolucion IS NULL)
  - Vencidos (CURRENT_DATE > fecha_vencimiento AND fecha_devolucion IS NULL)
  - Devueltos (fecha_devolucion IS NOT NULL)
- **Información mostrada**:
  - ID del préstamo
  - Usuario (documento - nombre)
  - Libro (título - autor)
  - Fecha de préstamo
  - Fecha de vencimiento
  - Estado de devolución
  - Estado del préstamo (ACTIVO/VENCIDO/DEVUELTO)
- **Acción de devolución**: Botón "Devolver" solo visible para préstamos activos o vencidos
- **Orden**: Por fecha de préstamo descendente (más recientes primero)

## 🔒 Seguridad

- **Prepared Statements**: Todas las consultas SQL usan prepared statements para prevenir inyección SQL
- **Sanitización HTML**: Todas las salidas usan `htmlspecialchars()` para prevenir XSS
- **Validación de datos**: Validaciones tanto en cliente como en servidor
- **Manejo de errores**: Mensajes de error genéricos que no exponen información sensible

## 🎨 Interfaz

- **Diseño responsive**: Se adapta a diferentes tamaños de pantalla
- **Navegación intuitiva**: Menú superior en todas las páginas
- **Mensajes de estado**: Alertas visuales para éxito y errores
- **Tablas organizadas**: Información clara y fácil de leer
- **Estados visuales**: Colores diferenciados para estados de préstamos

## 📝 Reglas de Negocio

1. **Disponibilidad de libros**: Un préstamo solo se puede crear si hay ejemplares disponibles (`disponibles > 0`)
2. **Préstamos activos**: Un préstamo está activo cuando `fecha_devolucion IS NULL`
3. **Préstamos vencidos**: Un préstamo está vencido cuando `CURRENT_DATE > fecha_vencimiento AND fecha_devolucion IS NULL`
4. **Devolución**: Al devolver un libro, se actualiza `fecha_devolucion` con `CURRENT_DATE`
5. **Eliminación protegida**: No se pueden eliminar libros o usuarios que tengan préstamos asociados

## 🔧 Configuración Técnica

### PHP

- **Versión mínima**: PHP 8.0
- **Modo estricto**: `declare(strict_types=1)` en todos los archivos
- **PDO Configuration**:
  - `ATTR_ERRMODE`: `EXCEPTION`
  - `ATTR_DEFAULT_FETCH_MODE`: `ASSOC`
  - `ATTR_EMULATE_PREPARES`: `false`

### Base de Datos

- **Motor**: PostgreSQL
- **Conexión**: PDO con driver `pgsql`
- **Consultas**: Todas usan prepared statements
- **Búsquedas**: Usan `ILIKE` para búsquedas case-insensitive

## 📖 Uso del Sistema

1. **Acceder al sistema**: Abre `index.php` en tu navegador
2. **Gestionar libros**: Ve a "Libros" para agregar, editar o eliminar libros
3. **Gestionar usuarios**: Ve a "Usuarios" para administrar usuarios del sistema
4. **Gestionar préstamos**: Ve a "Préstamos" para crear préstamos y gestionar devoluciones

## 🐛 Solución de Problemas

### Error de conexión a la base de datos

- Verifica que PostgreSQL esté corriendo
- Confirma las credenciales en `conexion.php`
- Asegúrate de que la base de datos `db_biblioteca` exista

### No se muestran libros disponibles

- Verifica que la vista `vw_libros_disponibilidad` esté creada correctamente
- Revisa que los préstamos activos estén correctamente registrados

### No puedo eliminar un libro/usuario

- Verifica que no tenga préstamos asociados
- Si es necesario, primero devuelve todos los préstamos relacionados

## 📄 Licencia

Este proyecto es de código abierto y está disponible para uso educativo y comercial.

## 👤 Autor

Santiago-IA

## 🔗 Repositorio

https://github.com/Santiago-IA/Biblioteca.git

---

**Nota**: Este sistema está diseñado como aplicación monolítica sin frameworks externos, ideal para aprendizaje y proyectos pequeños/medianos.
