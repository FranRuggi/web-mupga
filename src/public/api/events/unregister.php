<?php
/**
 * POST /api/events/unregister.php  [requiere token]
 * Cancela la inscripción propia a un evento.
 *
 * Body JSON: { "event_id": int }
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/admin_db.php';
require_once SRC_ROOT . '/db/EventsRepository.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$eventId = isset($body['event_id']) ? (int) $body['event_id'] : 0;
if ($eventId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta event_id.']);
    exit;
}

try {
    $repo = new EventsRepository(AdminDatabase::get());
    $ok   = $repo->unregister($eventId, $auth['usr']);

    if (!$ok) {
        http_response_code(404);
        echo json_encode(['error' => 'No estabas anotado a este evento.']);
        exit;
    }

    echo json_encode(['message' => 'Inscripción cancelada.'], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al cancelar la inscripción.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
