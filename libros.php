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
        <p>&copy; " . date('Y') . " Biblioteca de Andres. Todos los derechos reservados.</p>
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
        $stmt_check = $pdo->prepare("SELECT COUNT(*) as total FROM prestamos WHERE libro_id = :id");
        $stmt_check->execute(['id' => $id_eliminar]);
        $tiene_prestamos = $stmt_check->fetch()['total'] > 0;
        
        if ($tiene_prestamos) {
            $mensaje = "No se puede eliminar el libro porque tiene préstamos asociados.";
            $tipo_mensaje = 'error';
        } else {
            $stmt_delete = $pdo->prepare("DELETE FROM libros WHERE id = :id");
            $stmt_delete->execute(['id' => $id_eliminar]);
            $mensaje = "Libro eliminado correctamente.";
            $tipo_mensaje = 'success';
        }
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar el libro.";
        $tipo_mensaje = 'error';
    }
}

// Procesar creación/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $isbn = trim($_POST['isbn'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $autor = trim($_POST['autor'] ?? '');
    $editorial = trim($_POST['editorial'] ?? '');
    $anio = trim($_POST['anio'] ?? '');
    $total_ejemplares = isset($_POST['total_ejemplares']) ? (int)$_POST['total_ejemplares'] : 0;
    
    // Validaciones
    $errores = [];
    if (empty($isbn)) $errores[] = "ISBN es requerido";
    if (empty($titulo)) $errores[] = "Título es requerido";
    if (empty($autor)) $errores[] = "Autor es requerido";
    if (empty($editorial)) $errores[] = "Editorial es requerida";
    if (!empty($anio) && (!is_numeric($anio) || (int)$anio < 0)) {
        $errores[] = "Año debe ser un número positivo o vacío";
    }
    if ($total_ejemplares < 0) {
        $errores[] = "Total de ejemplares debe ser mayor o igual a 0";
    }
    
    if (empty($errores)) {
        try {
            $anio_valor = !empty($anio) ? (int)$anio : null;
            
            if ($id) {
                // Editar
                $stmt = $pdo->prepare("UPDATE libros SET isbn = :isbn, titulo = :titulo, autor = :autor, editorial = :editorial, anio = :anio, total_ejemplares = :total_ejemplares WHERE id = :id");
                $stmt->execute([
                    'id' => $id,
                    'isbn' => $isbn,
                    'titulo' => $titulo,
                    'autor' => $autor,
                    'editorial' => $editorial,
                    'anio' => $anio_valor,
                    'total_ejemplares' => $total_ejemplares
                ]);
                $mensaje = "Libro actualizado correctamente.";
            } else {
                // Crear
                $stmt = $pdo->prepare("INSERT INTO libros (isbn, titulo, autor, editorial, anio, total_ejemplares) VALUES (:isbn, :titulo, :autor, :editorial, :anio, :total_ejemplares)");
                $stmt->execute([
                    'isbn' => $isbn,
                    'titulo' => $titulo,
                    'autor' => $autor,
                    'editorial' => $editorial,
                    'anio' => $anio_valor,
                    'total_ejemplares' => $total_ejemplares
                ]);
                $mensaje = "Libro creado correctamente.";
            }
            $tipo_mensaje = 'success';
        } catch (PDOException $e) {
            $mensaje = "Error al guardar el libro.";
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = 'error';
    }
}

// Obtener libro para editar
$libro_editar = null;
$modo_edicion = false;
if (isset($_GET['edit'])) {
    $id_editar = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM libros WHERE id = :id");
        $stmt->execute(['id' => $id_editar]);
        $libro_editar = $stmt->fetch();
        if ($libro_editar) {
            $modo_edicion = true;
        }
    } catch (PDOException $e) {
        $mensaje = "Error al cargar el libro.";
        $tipo_mensaje = 'error';
    }
}

// Búsqueda
$busqueda = $_GET['q'] ?? '';
$where_busqueda = '';
$params_busqueda = [];
if (!empty($busqueda)) {
    $where_busqueda = "WHERE titulo ILIKE :busqueda OR autor ILIKE :busqueda OR isbn ILIKE :busqueda";
    $params_busqueda['busqueda'] = "%{$busqueda}%";
}

// Obtener listado
try {
    $sql = "SELECT l.*, v.disponibles 
            FROM libros l 
            LEFT JOIN vw_libros_disponibilidad v ON l.id = v.id 
            {$where_busqueda}
            ORDER BY l.titulo ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params_busqueda);
    $libros = $stmt->fetchAll();
} catch (PDOException $e) {
    $libros = [];
}

imprimirHeader("Gestión de Libros");
?>

<h1>Gestión de Libros</h1>

<?php if ($mensaje): ?>
    <div class="alert <?= htmlspecialchars($tipo_mensaje) ?>">
        <?= $mensaje ?>
    </div>
<?php endif; ?>

<div class="search-form">
    <form method="GET" action="libros.php">
        <input type="text" name="q" placeholder="Buscar por título, autor o ISBN..." value="<?= htmlspecialchars($busqueda) ?>">
        <button type="submit">Buscar</button>
        <?php if (!empty($busqueda)): ?>
            <a href="libros.php" class="button">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<div class="form-section">
    <h2><?= $modo_edicion ? 'Editar Libro' : 'Nuevo Libro' ?></h2>
    <form method="POST" action="libros.php">
        <?php if ($modo_edicion): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$libro_editar['id']) ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="isbn">ISBN *</label>
            <input type="text" id="isbn" name="isbn" required value="<?= htmlspecialchars($libro_editar['isbn'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="titulo">Título *</label>
            <input type="text" id="titulo" name="titulo" required value="<?= htmlspecialchars($libro_editar['titulo'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="autor">Autor *</label>
            <input type="text" id="autor" name="autor" required value="<?= htmlspecialchars($libro_editar['autor'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="editorial">Editorial *</label>
            <input type="text" id="editorial" name="editorial" required value="<?= htmlspecialchars($libro_editar['editorial'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="anio">Año</label>
            <input type="number" id="anio" name="anio" min="0" value="<?= htmlspecialchars($libro_editar['anio'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="total_ejemplares">Total Ejemplares *</label>
            <input type="number" id="total_ejemplares" name="total_ejemplares" min="0" required value="<?= htmlspecialchars($libro_editar['total_ejemplares'] ?? '0') ?>">
        </div>
        
        <div class="form-actions">
            <button type="submit"><?= $modo_edicion ? 'Actualizar' : 'Crear' ?></button>
            <?php if ($modo_edicion): ?>
                <a href="libros.php" class="button">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-section">
    <h2>Listado de Libros</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ISBN</th>
                <th>Título</th>
                <th>Autor</th>
                <th>Editorial</th>
                <th>Año</th>
                <th>Total</th>
                <th>Disponibles</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($libros)): ?>
                <tr>
                    <td colspan="9">No se encontraron libros.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($libros as $libro): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$libro['id']) ?></td>
                        <td><?= htmlspecialchars($libro['isbn']) ?></td>
                        <td><?= htmlspecialchars($libro['titulo']) ?></td>
                        <td><?= htmlspecialchars($libro['autor']) ?></td>
                        <td><?= htmlspecialchars($libro['editorial']) ?></td>
                        <td><?= htmlspecialchars((string)($libro['anio'] ?? '-')) ?></td>
                        <td><?= htmlspecialchars((string)$libro['total_ejemplares']) ?></td>
                        <td><?= htmlspecialchars((string)($libro['disponibles'] ?? 0)) ?></td>
                        <td>
                            <a href="libros.php?edit=<?= htmlspecialchars((string)$libro['id']) ?>" class="button button-small">Editar</a>
                            <a href="libros.php?delete=<?= htmlspecialchars((string)$libro['id']) ?>" class="button button-small button-danger" onclick="return confirm('¿Está seguro de eliminar este libro?')">Eliminar</a>
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
