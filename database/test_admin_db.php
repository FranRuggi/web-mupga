<?php
/**
 * Test de conexión a mupga_admin con el login mupga_web_svc.
 * Correr en el VPS (CLI solamente):
 *   C:\xampp\php\php.exe database\test_admin_db.php
 *
 * Verifica:
 *   1. Conexión PDO a mupga_admin.
 *   2. Presencia de las 5 tablas del ControlPanel.
 *   3. Lectura de la fila única de site_status (id=1).
 *   4. SELECT sobre dbo.vw_web_auth en la base del juego (mismo login).
 *
 * No escribe nada. Borrar o ignorar después de validar el setup.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo CLI.');
}

define('SRC_ROOT', dirname(__DIR__) . '/src');
define('PROJECT_ROOT', dirname(__DIR__));

require_once SRC_ROOT . '/config/env.php';
loadEnv(PROJECT_ROOT . '/.env');

require_once SRC_ROOT . '/config/admin_db.php';

echo "== Test conexión mupga_admin ==\n\n";

// 1. Conexión
try {
    $pdo = AdminDatabase::get();
    $db  = $pdo->query('SELECT DB_NAME() AS db')->fetch()['db'];
    echo "[OK] Conectado. Base actual: {$db}\n";
    if (strcasecmp($db, 'mupga_admin') !== 0) {
        echo "[!!] ATENCIÓN: la base no es mupga_admin. Revisar ADMIN_DB_NAME.\n";
    }
} catch (PDOException $e) {
    echo "[ERROR] No se pudo conectar: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Tablas esperadas
$esperadas = ['admins', 'site_status', 'status_presets', 'news', 'server_info', 'downloads'];
$stmt = $pdo->query(
    "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'dbo'"
);
$existentes = array_map('strtolower', array_column($stmt->fetchAll(), 'TABLE_NAME'));
foreach ($esperadas as $tabla) {
    $ok = in_array($tabla, $existentes, true);
    echo ($ok ? "[OK] " : "[!!] ") . "Tabla dbo.{$tabla}" . ($ok ? "\n" : " NO ENCONTRADA\n");
}

// 3. Fila única de site_status
try {
    $stmt = $pdo->prepare('SELECT id, is_active, mode FROM dbo.site_status WHERE id = 1');
    $stmt->execute();
    $fila = $stmt->fetch();
    if ($fila) {
        echo "[OK] site_status id=1 legible (is_active={$fila['is_active']}, mode=" . ($fila['mode'] ?? 'NULL') . ")\n";
    } else {
        echo "[!!] site_status no tiene fila id=1 — debería existir siempre.\n";
    }
} catch (PDOException $e) {
    echo "[ERROR] Leyendo site_status: " . $e->getMessage() . "\n";
}

// 3b. Queries exactas de los endpoints de la Etapa 1
echo "\n== Test queries Etapa 1 (server-info / downloads) ==\n\n";
try {
    $stmt = $pdo->prepare('SELECT config_value FROM dbo.server_info WHERE config_key = :k');
    $stmt->execute([':k' => 'secciones']);
    $raw = $stmt->fetchColumn();
    if ($raw === false || $raw === null || $raw === '') {
        echo "[!!] server_info: no hay fila 'secciones' — correr el seed.\n";
    } else {
        json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        echo "[OK] server_info 'secciones': " . strlen($raw) . " bytes, JSON válido.\n";
    }
} catch (Throwable $e) {
    echo "[ERROR] server_info: " . $e->getMessage() . "\n";
}

try {
    $rows = $pdo->query(
        'SELECT item_key, title, description, version, size, url
           FROM dbo.downloads WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
    )->fetchAll();
    echo "[OK] downloads activos: " . count($rows) . " fila(s).\n";
} catch (Throwable $e) {
    echo "[ERROR] downloads: " . $e->getMessage() . "\n";
}

// 4. vw_web_auth en la base del juego, con el mismo login mupga_web_svc
echo "\n== Test vw_web_auth (base del juego, mismo login) ==\n\n";
try {
    $host = $_ENV['ADMIN_DB_HOST'] ?? 'localhost';
    $port = $_ENV['ADMIN_DB_PORT'] ?? '';
    $game = $_ENV['DB_NAME'] ?? 'MuOnline';
    $server = ($port !== '') ? "{$host},{$port}" : $host;

    $pdoGame = new PDO(
        "sqlsrv:Server={$server};Database={$game}",
        $_ENV['ADMIN_DB_USER'] ?? '',
        $_ENV['ADMIN_DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $count = $pdoGame->query('SELECT COUNT(*) AS c FROM dbo.vw_web_auth')->fetch(PDO::FETCH_ASSOC)['c'];
    echo "[OK] SELECT sobre dbo.vw_web_auth funciona ({$count} cuentas visibles).\n";
} catch (PDOException $e) {
    echo "[ERROR] vw_web_auth: " . $e->getMessage() . "\n";
}

echo "\nListo.\n";
