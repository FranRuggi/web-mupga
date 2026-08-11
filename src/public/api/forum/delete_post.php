<?php
/**
 * POST /api/forum/delete_post.php  [requiere token]
 * Body JSON: { target_type: "thread"|"post", id }
 *
 * Soft delete siempre (F-02.03 / F-03.04) — nada de DELETE físico:
 * - Respuesta propia: el autor la borra cuando quiera (queda placeholder
 *   si hay respuestas posteriores; el render del cliente decide).
 * - Hilo propio: solo si nadie más participó — si ya hay respuestas de
 *   terceros, tiene que pedirlo al staff.
 * - Admin: borra cualquier cosa; el contenido previo va al log de auditoría
 *   y el hilo/post aparece en la papelera del ControlPanel (restaurable).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$targetType = (string) ($body['target_type'] ?? '');
$id         = (int) ($body['id'] ?? 0);
$reason     = trim((string) ($body['reason'] ?? '')) ?: null;

if (!in_array($targetType, ['thread', 'post'], true) || $id <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Parámetros inválidos.']); exit;
}

try {
    $repo    = new ForumRepository(ForumDatabase::get());
    $isAdmin = isAdminAccount($auth['usr']);

    $row = $targetType === 'thread' ? $repo->getThread($id) : $repo->getPost($id);
    if (!$row || $row['is_deleted']) {
        http_response_code(404); echo json_encode(['error' => 'No encontrado.']); exit;
    }

    $esDueno = $row['author_account'] === $auth['usr'];
    if (!$esDueno && !$isAdmin) {
        http_response_code(403); echo json_encode(['error' => 'No podés borrar este mensaje.']); exit;
    }

    if ($targetType === 'thread') {
        if ($esDueno && !$isAdmin && $repo->threadHasRepliesByOthers($id, $auth['usr'])) {
            http_response_code(403);
            echo json_encode(['error' => 'El hilo ya tiene respuestas de otros jugadores — pedile al staff que lo borre.']);
            exit;
        }
        if ($isAdmin && !$esDueno) {
            $repo->logModeration($auth['usr'], 'delete_thread', 'thread', (string) $id, $reason,
                'TÍTULO: ' . $row['title'] . "\n\n" . $row['body']);
        }
        $repo->softDeleteThread($id, $auth['usr']);
    } else {
        if ($isAdmin && !$esDueno) {
            $repo->logModeration($auth['usr'], 'delete_post', 'post', (string) $id, $reason, $row['body']);
        }
        $repo->softDeletePost($id, $auth['usr']);
    }

    echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo borrar.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
