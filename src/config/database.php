<?php
/**
 * Singleton PDO para SQL Server vía PDO_SQLSRV.
 * Lee host, puerto, nombre de DB y credenciales desde $_ENV.
 *
 * DSN resultante:
 *   - Con DB_PORT definido : sqlsrv:Server=host,puerto;Database=nombre
 *   - Sin DB_PORT          : sqlsrv:Server=host;Database=nombre
 *
 * Para SQL Server Express (instancia nombrada sin puerto fijo), dejá DB_PORT
 * vacío y el SQL Server Browser Service resuelve el puerto automáticamente.
 * Si SQL Server Browser no corre, especificá el puerto real de la instancia
 * (SQL Server Config Manager → IP Addresses → IPAll → TCP Dynamic Ports).
 */
class Database {

    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '';
            $name = $_ENV['DB_NAME'] ?? '';
            $user = $_ENV['DB_USER'] ?? '';
            $pass = $_ENV['DB_PASS'] ?? '';

            // Construir el fragmento Server: con o sin puerto
            $server = ($port !== '') ? "{$host},{$port}" : $host;
            $dsn    = "sqlsrv:Server={$server};Database={$name}";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
