<?php
/**
 * Conexión PDO para el schema prode (usuario prode_user).
 * Mismo patrón que database.php, pero usa credenciales PRODE_*.
 *
 * Ambas conexiones apuntan al mismo SQL Server / base de datos;
 * las credenciales distintas limitan los permisos a lo estrictamente
 * necesario para el módulo prode.
 */
class ProdeDatabase {

    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $host = $_ENV['PRODE_DB_HOST'] ?? 'localhost';
            $port = $_ENV['PRODE_DB_PORT'] ?? '';
            $name = $_ENV['PRODE_DB_NAME'] ?? 'MuOnline';
            $user = $_ENV['PRODE_DB_USER'] ?? '';
            $pass = $_ENV['PRODE_DB_PASS'] ?? '';

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
