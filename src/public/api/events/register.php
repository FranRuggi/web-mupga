<?php
/**
 * POST /api/events/register.php  [requiere token]
 * Anota a la cuenta autenticada a un evento con uno de sus personajes.
 *
 * Body JSON: { "event_id": int, "character_name": "..." }
 *
 * Validaciones (backend es la fuente de verdad):
 * - El personaje pertenece a la cuenta autenticada.
 * - El evento existe, está activo y no arrancó (GETUTCDATE() < event_datetime,
 *   o sin fecha confirmada todavía).
 * - Cupo disponible (si el evento tiene max_slots).
 * - La cuenta no estaba ya anotada.
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

$eventId       = isset($body['event_id']) ? (int) $body['event_id'] : 0;
$characterName = trim((string) ($body['character_name'] ?? ''));

if ($eventId <= 0 || $characterName === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan campos requeridos: event_id, character_name.']);
    exit;
}

$owns = (new CharacterRepository(Database::get()))->belongsToAccount($characterName, $auth['usr']);
if (!$owns) {
    http_response_code(403);
    echo json_encode(['error' => 'Ese personaje no pertenece a tu cuenta.']);
    exit;
}

try {
    $repo = new EventsRepository(AdminDatabase::get());
    $repo->register($eventId, $auth['usr'], $characterName);

    echo json_encode([
        'message'        => 'Inscripción registrada.',
        'event_id'       => $eventId,
        'character_name' => $characterName,
    ], JSON_THROW_ON_ERROR);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al procesar la inscripción.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
