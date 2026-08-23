<?php
/**
 * GET /api/events/registrations.php?event_id=X  [público]
 * Lista de anotados a un evento activo (solo nombre de personaje).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/admin_db.php';
require_once SRC_ROOT . '/db/EventsRepository.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
if ($eventId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta event_id.']);
    exit;
}

try {
    $repo  = new EventsRepository(AdminDatabase::get());
    $event = $repo->getActiveById($eventId);

    if (!$event) {
        http_response_code(404);
        echo json_encode(['error' => 'Evento no encontrado.']);
        exit;
    }

    $regs = array_map(
        fn($r) => ['character_name' => $r['character_name']],
        $repo->listRegistrations($eventId)
    );

    echo json_encode(['registrations' => $regs], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al cargar los anotados.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
