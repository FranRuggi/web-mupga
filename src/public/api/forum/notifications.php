<?php
/**
 * /api/forum/notifications.php  [requiere token] — centro de avisos (F-07.02)
 * GET  → { unread, notifications: [...] } (últimos 20, con título del hilo)
 * POST → { action: "read", id } marca uno · { action: "read_all" } marca todos
 *
 * La purga de avisos viejos (>60 días) corre oportunista en el GET — SQL
 * Server Express no tiene Agent para un job programado.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$auth = requireAuth();

try {
    $repo = new ForumRepository(ForumDatabase::get());

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body   = json_decode(file_get_contents('php://input'), true);
        $action = (string) ($body['action'] ?? '');

        if ($action === 'read') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Falta id.']); exit; }
            $repo->markNotificationRead($auth['usr'], $id);
        } elseif ($action === 'read_all') {
            $repo->markAllNotificationsRead($auth['usr']);
        } else {
            http_response_code(400); echo json_encode(['error' => 'Acción inválida.']); exit;
        }

        echo json_encode(['unread' => $repo->countUnreadNotifications($auth['usr'])], JSON_THROW_ON_ERROR);
        exit;
    }

    try { $repo->purgeOldNotifications(); } catch (Throwable $e) { /* nunca rompe el listado */ }

    echo json_encode([
        'unread'        => $repo->countUnreadNotifications($auth['usr']),
        'notifications' => $repo->getNotifications($auth['usr']),
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error interno.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
