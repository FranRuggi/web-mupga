<?php
/**
 * POST /api/reclamos/mark_read.php  [requiere token]
 * El jugador confirmó que vio la respuesta de su reclamo (botón "Entendido"
 * del banner) — deja de aparecer en pending_notice.php.
 *
 * Body JSON: { "id": 123 }
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/reclamos_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);
$id   = isset($body['id']) ? (int) $body['id'] : 0;

if ($id <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Falta id.']); exit;
}

try {
    $stmt = ReclamosDatabase::get()->prepare(
        "UPDATE reclamos.reclamos SET leido = 1 WHERE id = :id AND nick = :nick"
    );
    $stmt->execute([':id' => $id, ':nick' => $auth['usr']]);

    echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo actualizar.']);
}
