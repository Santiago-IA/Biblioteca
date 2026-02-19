<?php
declare(strict_types=1);

require_once 'conexion.php';

// Obtener resumen de datos
try {
    $stmt_libros = $pdo->query("SELECT COUNT(*) as total FROM libros");
    $total_libros = $stmt_libros->fetch()['total'];
    
    $stmt_usuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total_usuarios = $stmt_usuarios->fetch()['total'];
    
    $stmt_prestamos = $pdo->query("SELECT COUNT(*) as total FROM prestamos WHERE fecha_devolucion IS NULL");
    $total_prestamos_activos = $stmt_prestamos->fetch()['total'];
} catch (PDOException $e) {
    $total_libros = 0;
    $total_usuarios = 0;
    $total_prestamos_activos = 0;
}

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

imprimirHeader("Biblioteca Online");
?>

<h1>Biblioteca Online</h1>

<div class="cards">
    <div class="card">
        <h2>Gestión de Libros</h2>
        <p>Administra el catálogo de libros de la biblioteca</p>
        <a href="libros.php" class="button">Ir a Libros</a>
    </div>
    
    <div class="card">
        <h2>Gestión de Usuarios</h2>
        <p>Administra los usuarios del sistema</p>
        <a href="usuarios.php" class="button">Ir a Usuarios</a>
    </div>
    
    <div class="card">
        <h2>Préstamos</h2>
        <p>Gestiona los préstamos de libros</p>
        <a href="prestamos.php" class="button">Ir a Préstamos</a>
    </div>
</div>

<div class="summary">
    <h2>Resumen</h2>
    <div class="counters">
        <div class="counter">
            <span class="counter-value"><?= htmlspecialchars((string)$total_libros) ?></span>
            <span class="counter-label">Total Libros</span>
        </div>
        <div class="counter">
            <span class="counter-value"><?= htmlspecialchars((string)$total_usuarios) ?></span>
            <span class="counter-label">Total Usuarios</span>
        </div>
        <div class="counter">
            <span class="counter-value"><?= htmlspecialchars((string)$total_prestamos_activos) ?></span>
            <span class="counter-label">Préstamos Activos</span>
        </div>
    </div>
</div>

<?php
imprimirFooter();
?>
