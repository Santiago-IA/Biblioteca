<?php
declare(strict_types=1);

$DB_HOST = "127.0.0.1";
$DB_PORT = "5432";
$DB_NAME = "db_biblioteca";
$DB_USER = "postgres";
$DB_PASS = "2002";

try {
    $dsn = "pgsql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME}";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos. Por favor, contacte al administrador del sistema.");
}
