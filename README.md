# Sistema Monolítico de Biblioteca Online

Sistema web para la gestión de una biblioteca: libros, usuarios y préstamos. Desarrollado en PHP 8+ con PostgreSQL.

Esta guía explica cómo desplegar el proyecto **en Mac (macOS)** paso a paso.

---

## 📋 Contenido de esta guía

1. [Requisitos previos](#-requisitos-previos)
2. [Despliegue paso a paso](#-despliegue-paso-a-paso)
3. [Verificar que todo funciona](#-verificar-que-todo-funciona)
4. [Estructura del proyecto](#-estructura-del-proyecto)
5. [Funcionalidades del sistema](#-funcionalidades-del-sistema)
6. [Solución de problemas](#-solución-de-problemas)

---

## 🛠️ Requisitos previos

Antes de empezar necesitas:

| Requisito | Versión mínima | Para qué sirve |
|-----------|----------------|-----------------|
| **PHP**   | 8.0            | Ejecutar la aplicación |
| **PostgreSQL** | 12  | Base de datos |
| **Extensiones PHP** | `pdo` y `pdo_pgsql` | Conectar PHP con PostgreSQL |

Si no tienes nada instalado, sigue la guía desde el Paso 1. Si ya tienes PHP y PostgreSQL en tu Mac, ve directo al [Paso 4](#paso-4-crear-la-base-de-datos).

---

## 🚀 Despliegue paso a paso

Sigue los pasos **en orden**. No te saltes ninguno.

---

### Paso 1: Instalar PostgreSQL en Mac

1. Abre **Terminal** (Aplicaciones → Utilidades → Terminal, o Cmd+Espacio y escribe "Terminal").
2. Si no tienes **Homebrew**, instálalo primero:
   ```bash
   /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   ```
   Sigue las instrucciones en pantalla (te pedirá tu contraseña de Mac).
3. Instala PostgreSQL:
   ```bash
   brew install postgresql@16
   brew services start postgresql@16
   ```
   (Si prefieres otra versión, usa `postgresql@15` o solo `postgresql` para la última.)
4. Añade PostgreSQL al PATH:
   - **Mac con chip Apple (M1/M2/M3):** `/opt/homebrew/opt/postgresql@16/bin`
   - **Mac con Intel:** `/usr/local/opt/postgresql@16/bin`
   ```bash
   echo 'export PATH="/opt/homebrew/opt/postgresql@16/bin:$PATH"' >> ~/.zshrc
   source ~/.zshrc
   ```
   En Intel cambia `/opt/homebrew` por `/usr/local`.
5. Crea el usuario `postgres` con contraseña para que coincida con `conexion.php`:
   ```bash
   psql -d postgres -c "CREATE USER postgres WITH PASSWORD '2002' SUPERUSER CREATEDB CREATEROLE LOGIN;"
   ```
   Si sale "role postgres already exists", solo ponle la contraseña:
   ```bash
   psql -d postgres -c "ALTER USER postgres WITH PASSWORD '2002';"
   ```
6. Comprueba la conexión:
   ```bash
   psql -U postgres -h 127.0.0.1 -d postgres
   ```
   Introduce la contraseña `2002`. Si entras al prompt de `psql`, está bien. Escribe `\q` y Enter para salir.

---

### Paso 2: Instalar PHP en Mac

1. Abre **Terminal**.
2. Instala PHP con Homebrew (si no tienes Homebrew, instálalo en el Paso 1):
   ```bash
   brew install php
   ```
3. Comprueba la versión:
   ```bash
   php -v
   ```
   Debe ser 8.x. Si sale 7.x, instala la versión 8:
   ```bash
   brew install php@8.3
   echo 'export PATH="/opt/homebrew/opt/php@8.3/bin:$PATH"' >> ~/.zshrc
   source ~/.zshrc
   ```
   (En Intel usa `/usr/local/opt/php@8.3/bin`.)
4. Comprueba que tengas la extensión para PostgreSQL:
   ```bash
   php -m | grep -i pdo_pgsql
   ```
   Si no sale nada, prueba: `pecl install pdo_pgsql` o `brew reinstall php`.

---

### Paso 3: Obtener el proyecto

**Opción A: Con Git**

1. Abre Terminal y ve a la carpeta donde quieras el proyecto, por ejemplo:
   ```bash
   cd ~/proyectos
   ```
   o `cd ~/Desktop` si lo quieres en el Escritorio.
2. Clona el repositorio:
   ```bash
   git clone https://github.com/Santiago-IA/Biblioteca.git
   cd Biblioteca
   ```

**Opción B: Sin Git**

1. Entra en **https://github.com/Santiago-IA/Biblioteca**
2. Pulsa **Code** → **Download ZIP**.
3. Descomprime el ZIP donde quieras (por ejemplo `~/Desktop/Biblioteca` o `~/proyectos/Biblioteca`).

Al final debes tener una carpeta `Biblioteca` con estos archivos dentro:

- `index.php`, `libros.php`, `usuarios.php`, `prestamos.php`
- `conexion.php`, `estilos.css`, `db_biblioteca.sql`

---

### Paso 4: Crear la base de datos

1. **Crear la base de datos** en PostgreSQL. En Terminal:
   ```bash
   psql -U postgres -h 127.0.0.1 -c "CREATE DATABASE db_biblioteca;"
   ```
   (Te pedirá la contraseña del usuario `postgres`: `2002`.)

   **Alternativa con interfaz gráfica:** Instala pgAdmin desde **https://www.pgadmin.org/download/** (versión Mac). Abre pgAdmin, conéctate al servidor (contraseña `postgres`), clic derecho en **Databases** → **Create** → **Database...**, nombre: `db_biblioteca`, **Save**.

2. **Ejecutar el script SQL** que crea tablas y vista. Sustituye la ruta por la de tu carpeta del proyecto:
   ```bash
   psql -U postgres -h 127.0.0.1 -d db_biblioteca -f "$HOME/proyectos/Biblioteca/db_biblioteca.sql"
   ```
   Ejemplo si está en el Escritorio: `-f "$HOME/Desktop/Biblioteca/db_biblioteca.sql"`

   **Con pgAdmin:** Clic derecho en `db_biblioteca` → **Query Tool** → **File** → **Open** → selecciona `db_biblioteca.sql` → **Execute** (▶).

3. **Comprobar:** En pgAdmin, en `db_biblioteca` → **Schemas** → **public** → **Tables** deberías ver: `usuarios`, `libros`, `prestamos`. En **Views**: `vw_libros_disponibilidad`.

---

### Paso 5: Configurar la conexión

1. Abre el archivo **`conexion.php`** del proyecto con un editor de texto.
2. Comprueba que coincidan estas líneas (por defecto ya están así):
   ```php
   $DB_HOST = "127.0.0.1";
   $DB_PORT = "5432";
   $DB_NAME = "db_biblioteca";
   $DB_USER = "postgres";
   $DB_PASS = "2002";
   ```
3. Si en el Paso 1 pusiste otra contraseña para `postgres`, edita `$DB_PASS`.
4. Guarda el archivo.

---

### Paso 6: Levantar la aplicación

1. Abre **Terminal**.
2. Entra en la carpeta del proyecto, por ejemplo:
   ```bash
   cd ~/proyectos/Biblioteca
   ```
   (o `cd ~/Desktop/Biblioteca` según donde lo tengas.)
3. Arranca el servidor de PHP:
   ```bash
   php -S 127.0.0.1:8000
   ```
4. Debe aparecer: `Development Server (http://127.0.0.1:8000) started`.
5. **No cierres esta ventana** mientras uses la aplicación. Para parar el servidor: **Ctrl+C**.

**Alternativa con MAMP:** Si usas MAMP (https://www.mamp.info/), copia la carpeta del proyecto en `Applications/MAMP/htdocs/`. Inicia los servidores en MAMP y abre `http://localhost:8888/Biblioteca/` (o el puerto que muestre MAMP). Recuerda tener PostgreSQL instalado y configurado aparte (Paso 1) y habilitar `pdo_pgsql` en el `php.ini` de MAMP.

---

### Paso 7: Abrir la aplicación en el navegador

1. Abre Safari, Chrome o el navegador que uses.
2. En la barra de direcciones escribe: **http://127.0.0.1:8000**
3. Pulsa Enter.

**Qué deberías ver:**

- Página con título **"Biblioteca Online"**.
- Tres tarjetas: Gestión de Libros, Gestión de Usuarios, Préstamos.
- Un resumen con tres números: Total Libros, Total Usuarios, Préstamos Activos (al principio pueden ser 0).

**Si ves "Error de conexión a la base de datos":**

- Revisa el [Paso 5](#paso-5-configurar-la-conexión) (usuario, contraseña, nombre de base).
- Comprueba que PostgreSQL esté en marcha: en Terminal `brew services list` y que `postgresql@16` esté "started". Si no: `brew services start postgresql@16`.

---

### Paso 8: Datos iniciales (primera vez)

Para poder hacer préstamos necesitas al menos **un usuario** y **un libro**.

1. En el menú superior, haz clic en **Usuarios**.
2. Rellena el formulario "Nuevo Usuario":
   - Documento: por ejemplo `12345678`
   - Nombre: tu nombre o "Admin"
   - Email: opcional
   - Rol: **Admin** (o Bibliotecario/Lector)
3. Pulsa **Crear**.

4. En el menú, haz clic en **Libros**.
5. Rellena el formulario "Nuevo Libro":
   - ISBN: por ejemplo `978000000001`
   - Título: por ejemplo "Mi primer libro"
   - Autor, Editorial: lo que quieras
   - Año: opcional
   - Total Ejemplares: por ejemplo **2**
6. Pulsa **Crear**.

7. Ve a **Préstamos**, elige el usuario y el libro, deja 7 días y pulsa **Crear Préstamo**. Debe mostrarse "Préstamo creado correctamente."

Con esto el despliegue está completo.

---

## ✅ Verificar que todo funciona

| Prueba | Dónde | Qué hacer |
|--------|--------|-----------|
| 1 | Inicio | Abres la URL y ves "Biblioteca Online" y los 3 contadores. |
| 2 | Usuarios | Creas un usuario y aparece en la tabla. |
| 3 | Libros | Creas un libro y aparece en la tabla con "Disponibles" correcto. |
| 4 | Préstamos | Creas un préstamo y ves mensaje de éxito. |
| 5 | Préstamos | En el listado aparece el préstamo como ACTIVO. |
| 6 | Préstamos | Pulsas "Devolver" y el estado pasa a DEVUELTO. |
| 7 | Libros | Buscas por título/autor y se filtra la lista. |

Si todo eso funciona, el sistema está bien desplegado.

---

## 📁 Estructura del proyecto

```
Biblioteca/
├── index.php          # Página principal (resumen y enlaces)
├── libros.php         # CRUD de libros
├── usuarios.php       # CRUD de usuarios
├── prestamos.php      # Crear préstamos y listar (filtros y devolver)
├── conexion.php       # Conexión PDO a PostgreSQL (editar credenciales aquí)
├── estilos.css        # Estilos de la interfaz
├── db_biblioteca.sql  # Script para crear tablas y vista (ejecutar una vez)
└── README.md          # Esta guía
```

---

## 🗄️ Base de datos (resumen)

- **Base de datos:** `db_biblioteca`
- **Tablas:** `usuarios`, `libros`, `prestamos`
- **Vista:** `vw_libros_disponibilidad` (campo `disponibles`)

Credenciales por defecto en `conexion.php`: host `127.0.0.1`, puerto `5432`, usuario `postgres`, contraseña `2002`.

---

## 📖 Funcionalidades del sistema

- **Inicio:** Resumen (total libros, usuarios, préstamos activos) y enlaces a cada módulo.
- **Libros:** Alta, edición, eliminación y búsqueda por título, autor o ISBN. Listado con total y disponibles.
- **Usuarios:** Alta, edición, eliminación y búsqueda. Roles: admin, bibliotecario, lector.
- **Préstamos:** Crear préstamo (usuario, libro, días, observación), listar con filtros (activos/vencidos/devueltos) y botón "Devolver". No permite crear préstamo si no hay ejemplares disponibles.

---

## 🐛 Solución de problemas (Mac)

### "Error de conexión a la base de datos"

- PostgreSQL en marcha: `brew services start postgresql@16`. Comprueba con `brew services list`.
- Revisa `conexion.php`: usuario `postgres`, contraseña, nombre `db_biblioteca`, host y puerto.
- La base de datos `db_biblioteca` existe (creada en el Paso 4).

### "No se muestran libros" o "disponibles" raro

- Ejecutaste todo el contenido de `db_biblioteca.sql` (tablas **y** vista `vw_libros_disponibilidad`).

### No aparece la extensión pdo_pgsql

- Con Homebrew, `php.ini` suele estar en `/opt/homebrew/etc/php/8.x/php.ini` (Apple Silicon) o `/usr/local/etc/php/8.x/php.ini` (Intel). Comprueba con `php --ini`.
- Las líneas deben ser `extension=pdo_pgsql` y `extension=pgsql` (sin `;` al inicio).
- Cierra la terminal donde corre `php -S` y vuelve a ejecutar `php -S 127.0.0.1:8000`.

### No puedo eliminar un libro o usuario

- Solo se pueden eliminar si no tienen préstamos asociados. Primero devuelve esos préstamos en "Préstamos".

### La página en blanco o error 500

- Revisa que todos los archivos del proyecto estén en la misma carpeta y que `conexion.php` no tenga errores de sintaxis.
- Revisa el mensaje de error en la terminal donde corre `php -S` o en los logs.

---

## 📄 Licencia y repositorio

- Proyecto de código abierto para uso educativo y comercial.
- Repositorio: **https://github.com/Santiago-IA/Biblioteca.git**
- Autor: Santiago-IA

---

**Resumen rápido (Mac):** Instalar Homebrew → PostgreSQL (`brew install postgresql@16`) → PHP (`brew install php`) → Clonar/descargar proyecto → Crear base `db_biblioteca` y ejecutar `db_biblioteca.sql` → Ajustar `conexion.php` si hace falta → `cd` a la carpeta del proyecto y `php -S 127.0.0.1:8000` → Abrir http://127.0.0.1:8000 y crear un usuario y un libro para usar préstamos.
