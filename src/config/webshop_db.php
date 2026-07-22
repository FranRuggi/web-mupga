<?php
/**
 * Conexión PDO para el schema webshop (usuario webshop_user).
 * Mismo patrón que prode_db.php — mismo SQL Server / base de datos que
 * Database::get(), pero credenciales separadas que solo tienen CONTROL
 * sobre el schema webshop (nada de CashShopData ni ninguna otra tabla
 * del juego). Crear el usuario con database/webshop_setup.sql.
 */
class WebshopDatabase {

    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $host = $_ENV['WEBSHOP_DB_HOST'] ?? 'localhost';
            $port = $_ENV['WEBSHOP_DB_PORT'] ?? '';
            $name = $_ENV['WEBSHOP_DB_NAME'] ?? 'MuOnline';
            $user = $_ENV['WEBSHOP_DB_USER'] ?? '';
            $pass = $_ENV['WEBSHOP_DB_PASS'] ?? '';

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
