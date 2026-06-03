<?php
// TEMPORAL - borrar después de debuggear
require_once dirname(__DIR__) . '/config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');

$host = $_ENV['DB_HOST'] ?? 'NO DEFINIDO';
$name = $_ENV['DB_NAME'] ?? 'NO DEFINIDO';
$user = $_ENV['DB_USER'] ?? 'NO DEFINIDO';
$pass = $_ENV['DB_PASS'] ?? 'NO DEFINIDO';

echo "Host: $host | DB: $name | User: $user | Pass: $pass";
echo "<br><br>";

$dsn = "sqlsrv:Server={$host};Database={$name}";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Conexión OK";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
