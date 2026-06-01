<?php
/**
 * Singleton PDO para SQL Server vía PDO_SQLSRV.
 * Lee host, puerto, nombre de DB y credenciales desde $_ENV.
 */
class Database {

    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '1433';
            $name = $_ENV['DB_NAME'] ?? '';
            $user = $_ENV['DB_USER'] ?? '';
            $pass = $_ENV['DB_PASS'] ?? '';

            $dsn = "sqlsrv:Server={$host},{$port};Database={$name}";

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
