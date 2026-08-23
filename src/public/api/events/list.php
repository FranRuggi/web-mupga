<?php
/**
 * GET /api/events/list.php  [público, token opcional]
 * Devuelve los eventos activos con su cupo actual.
 * Si viene un token válido, agrega "my_registration" (nombre del
 * personaje con el que el usuario ya está anotado, o null).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/admin_db.php';
require_once SRC_ROOT . '/db/EventsRepository.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$auth = optionalAuth();

try {
    $repo   = new EventsRepository(AdminDatabase::get());
    $events = $repo->listActive();

    foreach ($events as &$e) {
        $e['id']               = (int) $e['id'];
        $e['max_slots']        = $e['max_slots'] !== null ? (int) $e['max_slots'] : null;
        $e['registered_count'] = (int) $e['registered_count'];
        $e['my_registration']  = null;

        if ($auth) {
            $reg = $repo->getUserRegistration($e['id'], $auth['usr']);
            $e['my_registration'] = $reg ? $reg['character_name'] : null;
        }
    }
    unset($e);

    echo json_encode(['events' => $events], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al cargar los eventos.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
