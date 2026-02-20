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

// Procesar devolución
if (isset($_GET['devolver'])) {
    $id_devolver = (int)$_GET['devolver'];
    try {
        $stmt = $pdo->prepare("UPDATE prestamos SET fecha_devolucion = CURRENT_DATE WHERE id = :id AND fecha_devolucion IS NULL");
        $stmt->execute(['id' => $id_devolver]);
        if ($stmt->rowCount() > 0) {
            $mensaje = "Préstamo devuelto correctamente.";
            $tipo_mensaje = 'success';
        } else {
            $mensaje = "No se pudo procesar la devolución.";
            $tipo_mensaje = 'error';
        }
    } catch (PDOException $e) {
        $mensaje = "Error al procesar la devolución.";
        $tipo_mensaje = 'error';
    }
}

// Procesar creación de préstamo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_prestamo'])) {
    $usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
    $libro_id = isset($_POST['libro_id']) ? (int)$_POST['libro_id'] : 0;
    $dias_prestamo = isset($_POST['dias_prestamo']) ? (int)$_POST['dias_prestamo'] : 7;
    $observacion = trim($_POST['observacion'] ?? '');
    
    // Validaciones
    $errores = [];
    if ($usuario_id <= 0) $errores[] = "Usuario es requerido";
    if ($libro_id <= 0) $errores[] = "Libro es requerido";
    if ($dias_prestamo < 1) $errores[] = "Días de préstamo debe ser mayor a 0";
    
    if (empty($errores)) {
        try {
            // Verificar disponibilidad
            $stmt_disponibilidad = $pdo->prepare("SELECT disponibles FROM vw_libros_disponibilidad WHERE id = :id");
            $stmt_disponibilidad->execute(['id' => $libro_id]);
            $libro_info = $stmt_disponibilidad->fetch();
            
            if (!$libro_info || (int)$libro_info['disponibles'] <= 0) {
                $mensaje = "No hay ejemplares disponibles de este libro.";
                $tipo_mensaje = 'error';
            } else {
                $observacion_valor = !empty($observacion) ? $observacion : null;
                $fecha_vencimiento = date('Y-m-d', strtotime("+{$dias_prestamo} days"));

                // Insertar préstamo con fecha_vencimiento calculada en PHP
                $stmt = $pdo->prepare("INSERT INTO prestamos (usuario_id, libro_id, fecha_prestamo, fecha_vencimiento, observacion) VALUES (:usuario_id, :libro_id, CURRENT_DATE, :fecha_vencimiento, :observacion)");
                $stmt->execute([
                    'usuario_id' => $usuario_id,
                    'libro_id' => $libro_id,
                    'fecha_vencimiento' => $fecha_vencimiento,
                    'observacion' => $observacion_valor
                ]);
                $mensaje = "Préstamo creado correctamente.";
                $tipo_mensaje = 'success';
            }
        } catch (PDOException $e) {
            $mensaje = "Error al crear el préstamo.";
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = 'error';
    }
}

// Obtener usuarios para el select
try {
    $stmt_usuarios = $pdo->query("SELECT id, documento, nombre FROM usuarios ORDER BY nombre ASC");
    $usuarios = $stmt_usuarios->fetchAll();
} catch (PDOException $e) {
    $usuarios = [];
}

// Obtener libros disponibles para el select
try {
    $stmt_libros = $pdo->query("SELECT id, titulo, autor, disponibles FROM vw_libros_disponibilidad WHERE disponibles > 0 ORDER BY titulo ASC");
    $libros_disponibles = $stmt_libros->fetchAll();
} catch (PDOException $e) {
    $libros_disponibles = [];
}

// Filtro de estado
$estado_filtro = $_GET['estado'] ?? 'todos';
$where_estado = '';
$params_listado = [];

switch ($estado_filtro) {
    case 'activos':
        $where_estado = "WHERE p.fecha_devolucion IS NULL";
        break;
    case 'vencidos':
        $where_estado = "WHERE p.fecha_devolucion IS NULL AND CURRENT_DATE > p.fecha_vencimiento";
        break;
    case 'devueltos':
        $where_estado = "WHERE p.fecha_devolucion IS NOT NULL";
        break;
    default:
        $where_estado = "";
        break;
}

// Obtener listado de préstamos
try {
    $sql = "SELECT p.*, 
                   u.documento as usuario_documento, u.nombre as usuario_nombre,
                   l.titulo as libro_titulo, l.autor as libro_autor
            FROM prestamos p
            INNER JOIN usuarios u ON p.usuario_id = u.id
            INNER JOIN libros l ON p.libro_id = l.id
            {$where_estado}
            ORDER BY p.fecha_prestamo DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params_listado);
    $prestamos = $stmt->fetchAll();
} catch (PDOException $e) {
    $prestamos = [];
}

imprimirHeader("Gestión de Préstamos");
?>

<h1>Gestión de Préstamos</h1>

<?php if ($mensaje): ?>
    <div class="alert <?= htmlspecialchars($tipo_mensaje) ?>">
        <?= $mensaje ?>
    </div>
<?php endif; ?>

<div class="form-section">
    <h2>Nuevo Préstamo</h2>
    <form method="POST" action="prestamos.php">
        <input type="hidden" name="crear_prestamo" value="1">
        
        <div class="form-group">
            <label for="usuario_id">Usuario *</label>
            <select id="usuario_id" name="usuario_id" required>
                <option value="">Seleccione un usuario</option>
                <?php foreach ($usuarios as $usuario): ?>
                    <option value="<?= htmlspecialchars((string)$usuario['id']) ?>">
                        <?= htmlspecialchars($usuario['documento']) ?> - <?= htmlspecialchars($usuario['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="libro_id">Libro *</label>
            <select id="libro_id" name="libro_id" required>
                <option value="">Seleccione un libro</option>
                <?php foreach ($libros_disponibles as $libro): ?>
                    <option value="<?= htmlspecialchars((string)$libro['id']) ?>">
                        <?= htmlspecialchars($libro['titulo']) ?> - <?= htmlspecialchars($libro['autor']) ?> (Disponibles: <?= htmlspecialchars((string)$libro['disponibles']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="dias_prestamo">Días de Préstamo</label>
            <input type="number" id="dias_prestamo" name="dias_prestamo" min="1" value="7">
        </div>
        
        <div class="form-group">
            <label for="observacion">Observación</label>
            <textarea id="observacion" name="observacion" rows="3"><?= htmlspecialchars($_POST['observacion'] ?? '') ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit">Crear Préstamo</button>
        </div>
    </form>
</div>

<div class="table-section">
    <h2>Listado de Préstamos</h2>
    
    <div class="filters">
        <a href="prestamos.php?estado=todos" class="button <?= $estado_filtro === 'todos' ? 'active' : '' ?>">Todos</a>
        <a href="prestamos.php?estado=activos" class="button <?= $estado_filtro === 'activos' ? 'active' : '' ?>">Activos</a>
        <a href="prestamos.php?estado=vencidos" class="button <?= $estado_filtro === 'vencidos' ? 'active' : '' ?>">Vencidos</a>
        <a href="prestamos.php?estado=devueltos" class="button <?= $estado_filtro === 'devueltos' ? 'active' : '' ?>">Devueltos</a>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Libro</th>
                <th>Fecha Préstamo</th>
                <th>Vencimiento</th>
                <th>Devuelto</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($prestamos)): ?>
                <tr>
                    <td colspan="8">No se encontraron préstamos.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($prestamos as $prestamo): ?>
                    <?php
                    $fecha_devolucion = $prestamo['fecha_devolucion'];
                    $fecha_vencimiento = $prestamo['fecha_vencimiento'];
                    $esta_devuelto = !is_null($fecha_devolucion);
                    $esta_vencido = !$esta_devuelto && strtotime($fecha_vencimiento) < time();
                    $esta_activo = !$esta_devuelto && !$esta_vencido;
                    
                    $estado_texto = 'DEVUELTO';
                    if ($esta_activo) {
                        $estado_texto = 'ACTIVO';
                    } elseif ($esta_vencido) {
                        $estado_texto = 'VENCIDO';
                    }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$prestamo['id']) ?></td>
                        <td><?= htmlspecialchars($prestamo['usuario_documento']) ?> - <?= htmlspecialchars($prestamo['usuario_nombre']) ?></td>
                        <td><?= htmlspecialchars($prestamo['libro_titulo']) ?> - <?= htmlspecialchars($prestamo['libro_autor']) ?></td>
                        <td><?= htmlspecialchars($prestamo['fecha_prestamo']) ?></td>
                        <td><?= htmlspecialchars($fecha_vencimiento) ?></td>
                        <td><?= $esta_devuelto ? 'Sí (' . htmlspecialchars($fecha_devolucion) . ')' : 'No' ?></td>
                        <td>
                            <span class="estado estado-<?= strtolower($estado_texto) ?>">
                                <?= htmlspecialchars($estado_texto) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($esta_activo || $esta_vencido): ?>
                                <a href="prestamos.php?devolver=<?= htmlspecialchars((string)$prestamo['id']) ?>" class="button button-small">Devolver</a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
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
