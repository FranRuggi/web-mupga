<?php
/**
 * /api/admin/forum_ban.php  [requiere admin]
 *
 * GET  → historial de bans/unbans del foro (forum.banned_accounts).
 * POST → mutaciones. Body JSON con "action":
 *   - lookup: { action, account }          → existe la cuenta + si está baneada
 *   - ban:    { action, account, reason? } → banea (o actualiza motivo si ya estaba)
 *   - unban:  { action, account }          → saca el ban
 *
 * Ban acotado al foro únicamente (forum.banned_accounts) — nunca toca
 * MEMB_INFO.bloc_code, que bloquearía la cuenta del juego entero.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$admin = requireAdmin();
$repo  = new ForumRepository(ForumDatabase::get());

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        echo json_encode(['items' => $repo->getBanHistory()], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$action  = $body['action'] ?? '';
$account = trim((string) ($body['account'] ?? ''));

if ($account === '') {
    http_response_code(400); echo json_encode(['error' => 'Falta account.']); exit;
}

try {
    switch ($action) {

        case 'lookup': {
            $accRepo = new AccountRepository(Database::get());
            if (!$accRepo->usernameExists($account)) {
                http_response_code(404); echo json_encode(['error' => 'La cuenta no existe.']); exit;
            }
            $ban = $repo->getBan($account);
            echo json_encode(['exists' => true, 'banned' => (bool) $ban, 'ban' => $ban], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            break;
        }

        case 'ban': {
            $reason = trim((string) ($body['reason'] ?? '')) ?: null;

            $accRepo = new AccountRepository(Database::get());
            if (!$accRepo->usernameExists($account)) {
                http_response_code(404); echo json_encode(['error' => 'La cuenta no existe.']); exit;
            }

            $repo->banAccount($account, $admin['usr'], $reason);
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        case 'unban': {
            $ok = $repo->unbanAccount($account);
            echo json_encode(['success' => true, 'was_banned' => $ok], JSON_THROW_ON_ERROR);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción inválida: lookup, ban o unban']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al procesar el ban.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
