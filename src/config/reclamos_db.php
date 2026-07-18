<?php
/**
 * Conexión PDO para el schema reclamos en mupga_admin (usuario mupga_reclamos_svc).
 * Mismo patrón que prode_db.php / admin_db.php: credenciales de mínimo
 * privilegio propias del módulo, conexión separada de Database::get().
 */
class ReclamosDatabase {

    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $host = $_ENV['RECLAMOS_DB_HOST'] ?? 'localhost';
            $port = $_ENV['RECLAMOS_DB_PORT'] ?? '';
            $name = $_ENV['RECLAMOS_DB_NAME'] ?? 'mupga_admin';
            $user = $_ENV['RECLAMOS_DB_USER'] ?? '';
            $pass = $_ENV['RECLAMOS_DB_PASS'] ?? '';

            $server = ($port !== '') ? "{$host},{$port}" : $host;
            $dsn    = "sqlsrv:Server={$server};Database={$name}";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::SQLSRV_ATTR_ENCODING    => PDO::SQLSRV_ENCODING_UTF8,
            ]);
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
