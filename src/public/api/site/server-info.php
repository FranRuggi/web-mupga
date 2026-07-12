<?php
/**
 * GET /api/site/server-info.php  (público, sin auth)
 * Info del servidor desde mupga_admin.dbo.server_info (config_key = 'secciones').
 * Reemplaza a /api/infodata.php (que servía data/info.json estático).
 *
 * Respuesta: { "secciones": [...] } — el config_value parseado como JSON.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/admin_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

try {
    $stmt = AdminDatabase::get()->prepare(
        'SELECT config_value FROM dbo.server_info WHERE config_key = :k'
    );
    $stmt->execute([':k' => 'secciones']);
    $raw = $stmt->fetchColumn();

    if ($raw === false || $raw === null || $raw === '') {
        http_response_code(404);
        echo json_encode(['error' => 'Info del servidor no configurada']);
        exit;
    }

    // Parsear para validar y devolver como JSON real (no como string)
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch (JsonException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'El contenido guardado no es JSON válido']);
} catch (PDOException $e) {
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
