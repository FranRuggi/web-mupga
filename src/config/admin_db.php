<?php
/**
 * Conexión PDO para la base mupga_admin (login mupga_web_svc).
 * Mismo patrón que database.php / prode_db.php, pero apunta a una
 * BASE DISTINTA (mupga_admin), no a la base del juego.
 *
 * Permisos del login mupga_web_svc:
 *   - mupga_admin: db_datareader + db_datawriter (sin DDL).
 *   - Base del juego: solo SELECT sobre dbo.vw_web_auth (autenticación).
 *
 * Nunca usar esta conexión para tocar tablas del juego.
 */
class AdminDatabase {

    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $host = $_ENV['ADMIN_DB_HOST'] ?? 'localhost';
            $port = $_ENV['ADMIN_DB_PORT'] ?? '';
            $name = $_ENV['ADMIN_DB_NAME'] ?? 'mupga_admin';
            $user = $_ENV['ADMIN_DB_USER'] ?? '';
            $pass = $_ENV['ADMIN_DB_PASSWORD'] ?? '';

            $server = ($port !== '') ? "{$host},{$port}" : $host;
            $dsn    = "sqlsrv:Server={$server};Database={$name}";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // UTF-8 explícito: sin esto el driver usa el codepage del SO
                // y los emojis / caracteres fuera de Latin-1 se corrompen.
                PDO::SQLSRV_ATTR_ENCODING    => PDO::SQLSRV_ENCODING_UTF8,
            ]);
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
