<?php
/**
 * POST /api/forum/react.php  [requiere token]
 * Body JSON: { target_type: "thread"|"post", target_id }
 * Toggle: si ya habías reaccionado, la saca; si no, la agrega.
 * Un solo tipo de reacción en v1 ("Agradecer").
 * No se puede reaccionar al contenido propio (F-05.02).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$targetType = (string) ($body['target_type'] ?? '');
$targetId   = (int) ($body['target_id'] ?? 0);

if (!in_array($targetType, ['thread', 'post'], true) || $targetId <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Parámetros inválidos.']); exit;
}

try {
    $repo = new ForumRepository(ForumDatabase::get());

    if ($repo->isBanned($auth['usr'])) {
        http_response_code(403); echo json_encode(['error' => 'Estás baneado del foro.']); exit;
    }

    $target = $targetType === 'thread' ? $repo->getThread($targetId) : $repo->getPost($targetId);
    if (!$target || $target['is_deleted']) {
        http_response_code(404); echo json_encode(['error' => 'No encontrado.']); exit;
    }
    if ($target['author_account'] === $auth['usr']) {
        http_response_code(403); echo json_encode(['error' => 'No podés agradecerte a vos mismo.']); exit;
    }

    $result = $repo->toggleReaction($targetType, $targetId, $auth['usr']);

    echo json_encode($result, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo reaccionar.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
