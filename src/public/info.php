<?php
// TEMPORAL - borrar después de debuggear
$host = 'localhost\SQLEXPRESS';
$dsn  = "sqlsrv:Server={$host};Database=MuOnline";

try {
    $pdo = new PDO($dsn, 'sa', 'fr_58#R_4fw$%olZs!455', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Conexión OK";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
