<?php
declare(strict_types=1);

require_once 'conexion.php';

function imprimirHeader(string $titulo = "Biblioteca Online"): void {
    echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>" . htmlspecialchars($titulo) . "</title>
    <link rel='stylesheet' href='estilos.css'>
</head>
<body>
    <header>
        <nav>
            <a href='index.php'>Inicio</a>
            <a href='libros.php'>Libros</a>
            <a href='usuarios.php'>Usuarios</a>
            <a href='prestamos.php'>Préstamos</a>
        </nav>
    </header>
    <main class='container'>";
}

function imprimirFooter(): void {
    echo "    </main>
    <footer>
        <p>&copy; " . date('Y') . " Biblioteca Online. Todos los derechos reservados.</p>
    </footer>
</body>
</html>";
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar eliminación
if (isset($_GET['delete'])) {
    $id_eliminar = (int)$_GET['delete'];
    try {
        // Verificar si tiene préstamos asociados
        $stmt_check = $pdo->prepare("SELECT COUNT(*) as total FROM prestamos WHERE usuario_id = :id");
        $stmt_check->execute(['id' => $id_eliminar]);
        $tiene_prestamos = $stmt_check->fetch()['total'] > 0;
        
        if ($tiene_prestamos) {
            $mensaje = "No se puede eliminar el usuario porque tiene préstamos asociados.";
            $tipo_mensaje = 'error';
        } else {
            $stmt_delete = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
            $stmt_delete->execute(['id' => $id_eliminar]);
            $mensaje = "Usuario eliminado correctamente.";
            $tipo_mensaje = 'success';
        }
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar el usuario.";
        $tipo_mensaje = 'error';
    }
}

// Procesar creación/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $documento = trim($_POST['documento'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = trim($_POST['rol'] ?? '');
    
    // Validaciones
    $errores = [];
    if (empty($documento)) $errores[] = "Documento es requerido";
    if (empty($nombre)) $errores[] = "Nombre es requerido";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Email inválido";
    }
    if (empty($rol) || !in_array($rol, ['admin', 'bibliotecario', 'lector'])) {
        $errores[] = "Rol inválido";
    }
    
    if (empty($errores)) {
        try {
            $email_valor = !empty($email) ? $email : null;
            
            if ($id) {
                // Editar
                $stmt = $pdo->prepare("UPDATE usuarios SET documento = :documento, nombre = :nombre, email = :email, rol = :rol WHERE id = :id");
                $stmt->execute([
                    'id' => $id,
                    'documento' => $documento,
                    'nombre' => $nombre,
                    'email' => $email_valor,
                    'rol' => $rol
                ]);
                $mensaje = "Usuario actualizado correctamente.";
            } else {
                // Crear
                $stmt = $pdo->prepare("INSERT INTO usuarios (documento, nombre, email, rol) VALUES (:documento, :nombre, :email, :rol)");
                $stmt->execute([
                    'documento' => $documento,
                    'nombre' => $nombre,
                    'email' => $email_valor,
                    'rol' => $rol
                ]);
                $mensaje = "Usuario creado correctamente.";
            }
            $tipo_mensaje = 'success';
        } catch (PDOException $e) {
            $mensaje = "Error al guardar el usuario.";
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = 'error';
    }
}

// Obtener usuario para editar
$usuario_editar = null;
$modo_edicion = false;
if (isset($_GET['edit'])) {
    $id_editar = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id_editar]);
        $usuario_editar = $stmt->fetch();
        if ($usuario_editar) {
            $modo_edicion = true;
        }
    } catch (PDOException $e) {
        $mensaje = "Error al cargar el usuario.";
        $tipo_mensaje = 'error';
    }
}

// Búsqueda
$busqueda = $_GET['q'] ?? '';
$where_busqueda = '';
$params_busqueda = [];
if (!empty($busqueda)) {
    $where_busqueda = "WHERE nombre ILIKE :busqueda OR documento ILIKE :busqueda OR email ILIKE :busqueda";
    $params_busqueda['busqueda'] = "%{$busqueda}%";
}

// Obtener listado
try {
    $sql = "SELECT * FROM usuarios {$where_busqueda} ORDER BY nombre ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params_busqueda);
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $usuarios = [];
}

imprimirHeader("Gestión de Usuarios");
?>

<h1>Gestión de Usuarios</h1>

<?php if ($mensaje): ?>
    <div class="alert <?= htmlspecialchars($tipo_mensaje) ?>">
        <?= $mensaje ?>
    </div>
<?php endif; ?>

<div class="search-form">
    <form method="GET" action="usuarios.php">
        <input type="text" name="q" placeholder="Buscar por nombre, documento o email..." value="<?= htmlspecialchars($busqueda) ?>">
        <button type="submit">Buscar</button>
        <?php if (!empty($busqueda)): ?>
            <a href="usuarios.php" class="button">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<div class="form-section">
    <h2><?= $modo_edicion ? 'Editar Usuario' : 'Nuevo Usuario' ?></h2>
    <form method="POST" action="usuarios.php">
        <?php if ($modo_edicion): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$usuario_editar['id']) ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="documento">Documento *</label>
            <input type="text" id="documento" name="documento" required value="<?= htmlspecialchars($usuario_editar['documento'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="nombre">Nombre *</label>
            <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($usuario_editar['nombre'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario_editar['email'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="rol">Rol *</label>
            <select id="rol" name="rol" required>
                <option value="">Seleccione un rol</option>
                <option value="admin" <?= ($usuario_editar['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="bibliotecario" <?= ($usuario_editar['rol'] ?? '') === 'bibliotecario' ? 'selected' : '' ?>>Bibliotecario</option>
                <option value="lector" <?= ($usuario_editar['rol'] ?? '') === 'lector' ? 'selected' : '' ?>>Lector</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit"><?= $modo_edicion ? 'Actualizar' : 'Crear' ?></button>
            <?php if ($modo_edicion): ?>
                <a href="usuarios.php" class="button">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-section">
    <h2>Listado de Usuarios</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Documento</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Creado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="7">No se encontraron usuarios.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$usuario['id']) ?></td>
                        <td><?= htmlspecialchars($usuario['documento']) ?></td>
                        <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                        <td><?= htmlspecialchars($usuario['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars(ucfirst($usuario['rol'])) ?></td>
                        <td><?= htmlspecialchars($usuario['fecha_creacion'] ?? '-') ?></td>
                        <td>
                            <a href="usuarios.php?edit=<?= htmlspecialchars((string)$usuario['id']) ?>" class="button button-small">Editar</a>
                            <a href="usuarios.php?delete=<?= htmlspecialchars((string)$usuario['id']) ?>" class="button button-small button-danger" onclick="return confirm('¿Está seguro de eliminar este usuario?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
imprimirFooter();
?>
