<?php
/**
 * /api/admin/events.php  [requiere admin]
 *
 * GET              → todos los eventos (incluidos inactivos).
 * GET ?id=X         → un evento + su lista completa de anotados.
 * POST → mutaciones. Body JSON con "action":
 *   - create:               { action, title, description, event_datetime_utc, max_slots }
 *   - update:                { action, id, title, description, event_datetime_utc, max_slots }
 *   - set_active:            { action, id, is_active: 0|1 }
 *   - remove_registration:   { action, id, account }
 *
 * event_datetime_utc es opcional (null = hora a confirmar), ISO 8601 sin
 * timezone (ej. "2026-08-29T21:00:00") ya convertido a UTC por el cliente.
 *
 * Seguridad: POST-only para mutar; el Bearer token actúa como protección CSRF.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once SRC_ROOT . '/db/EventsRepository.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$admin = requireAdmin();
$repo  = new EventsRepository(AdminDatabase::get());

// ── GET: listado / detalle ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id > 0) {
            echo json_encode(
                ['registrations' => $repo->listRegistrationsAdmin($id)],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            );
            exit;
        }

        echo json_encode(['events' => $repo->listAll()], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400); echo json_encode(['error' => 'Body JSON inválido']); exit;
}

$action = $body['action'] ?? '';

/**
 * Devuelve string ISO validada, null si no vino fecha, o false si es inválida.
 */
function validarEventDatetime($value) {
    $value = trim((string) ($value ?? ''));
    if ($value === '') return null;
    if (!strtotime($value)) return false;
    return $value;
}

try {
    switch ($action) {

        case 'create': {
            $title       = trim((string) ($body['title'] ?? ''));
            $description = trim((string) ($body['description'] ?? ''));
            $maxSlots    = isset($body['max_slots']) && $body['max_slots'] !== '' ? (int) $body['max_slots'] : null;
            $datetime    = validarEventDatetime($body['event_datetime_utc'] ?? null);

            if ($title === '') {
                http_response_code(400); echo json_encode(['error' => 'title es obligatorio']); exit;
            }
            if ($datetime === false) {
                http_response_code(400); echo json_encode(['error' => 'event_datetime_utc inválida']); exit;
            }
            if ($maxSlots !== null && $maxSlots <= 0) {
                http_response_code(400); echo json_encode(['error' => 'max_slots debe ser un entero positivo']); exit;
            }

            $id = $repo->create($title, $description !== '' ? $description : null, $datetime, $maxSlots, $admin['usr']);
            echo json_encode(['success' => true, 'id' => $id], JSON_THROW_ON_ERROR);
            break;
        }

        case 'update': {
            $id          = (int) ($body['id'] ?? 0);
            $title       = trim((string) ($body['title'] ?? ''));
            $description = trim((string) ($body['description'] ?? ''));
            $maxSlots    = isset($body['max_slots']) && $body['max_slots'] !== '' ? (int) $body['max_slots'] : null;
            $datetime    = validarEventDatetime($body['event_datetime_utc'] ?? null);

            if ($id <= 0 || $title === '') {
                http_response_code(400); echo json_encode(['error' => 'id y title son obligatorios']); exit;
            }
            if ($datetime === false) {
                http_response_code(400); echo json_encode(['error' => 'event_datetime_utc inválida']); exit;
            }
            if ($maxSlots !== null && $maxSlots <= 0) {
                http_response_code(400); echo json_encode(['error' => 'max_slots debe ser un entero positivo']); exit;
            }

            $ok = $repo->update($id, $title, $description !== '' ? $description : null, $datetime, $maxSlots);
            if (!$ok) {
                http_response_code(404); echo json_encode(['error' => 'Evento no encontrado']); exit;
            }
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        case 'set_active': {
            $id     = (int) ($body['id'] ?? 0);
            $active = (int) ($body['is_active'] ?? -1);

            if ($id <= 0 || !in_array($active, [0, 1], true)) {
                http_response_code(400); echo json_encode(['error' => 'id e is_active (0|1) son obligatorios']); exit;
            }

            $ok = $repo->setActive($id, $active === 1);
            if (!$ok) {
                http_response_code(404); echo json_encode(['error' => 'Evento no encontrado']); exit;
            }
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        case 'remove_registration': {
            $id      = (int) ($body['id'] ?? 0);
            $account = trim((string) ($body['account'] ?? ''));

            if ($id <= 0 || $account === '') {
                http_response_code(400); echo json_encode(['error' => 'id y account son obligatorios']); exit;
            }

            $ok = $repo->removeRegistration($id, $account);
            if (!$ok) {
                http_response_code(404); echo json_encode(['error' => 'Esa cuenta no estaba anotada']); exit;
            }
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción inválida: create, update, set_active o remove_registration']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
