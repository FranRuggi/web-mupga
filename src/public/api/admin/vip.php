<?php
/**
 * /api/admin/vip.php  [requiere admin]
 *
 * GET  → últimos otorgamientos manuales de VIP (auditoría, mupga_admin.dbo.vip_grants).
 * POST → mutaciones. Body JSON con "action":
 *   - lookup: { action, account }            → existe la cuenta + estado VIP actual
 *   - grant:  { action, account, days, reason } → otorga VIP y audita el movimiento
 *
 * sp_SetAccountGOLDVIP se ejecuta por Database::get() (conexión principal, no
 * AdminDatabase/mupga_web_svc) — mismo patrón que wcoin.php / ProdeRepository::resolveMatch(),
 * documentado en CLAUDE.md: los SPs de premios corren por la conexión principal.
 * Ya lo usa hoy usercp/buyvip.php con ese mismo login, así que no hace falta
 * ningún GRANT nuevo en la DB.
 *
 * Seguridad: POST-only para mutar; el Bearer token actúa como protección CSRF.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$admin = requireAdmin();
$db    = AdminDatabase::get();

// ── GET: historial de otorgamientos manuales ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $rows = $db->query(
            'SELECT TOP 100 id, admin_id, target_account, days, reason, created_at
               FROM dbo.vip_grants ORDER BY id DESC'
        )->fetchAll();
        echo json_encode(['items' => $rows], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
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
            $vip = $accRepo->getVIPStatus($account);
            echo json_encode(['exists' => true, 'vip' => $vip], JSON_THROW_ON_ERROR);
            break;
        }

        case 'grant': {
            $days   = (int) ($body['days'] ?? 0);
            $reason = trim((string) ($body['reason'] ?? ''));

            if ($days <= 0) {
                http_response_code(400); echo json_encode(['error' => 'days debe ser un entero positivo.']); exit;
            }
            if (mb_strlen($reason) > 300) {
                http_response_code(400); echo json_encode(['error' => 'reason supera los 300 caracteres.']); exit;
            }

            $accRepo = new AccountRepository(Database::get());
            if (!$accRepo->usernameExists($account)) {
                http_response_code(404); echo json_encode(['error' => 'La cuenta no existe.']); exit;
            }

            // A partir de acá el VIP YA se otorgó (sp_SetAccountGOLDVIP no es
            // reversible de forma simple) — un fallo en la auditoría propia no
            // debe reportarse como error general, o un admin podría reintentar
            // y otorgar VIP dos veces creyendo que la primera vez falló.
            $accRepo->setVIP($account, $days);

            $auditOk = true;
            try {
                $log = $db->prepare(
                    'INSERT INTO dbo.vip_grants (admin_id, target_account, days, reason, created_at)
                     VALUES (:admin_id, :account, :days, :reason, GETUTCDATE())'
                );
                $log->execute([
                    ':admin_id' => $admin['usr'],
                    ':account'  => $account,
                    ':days'     => $days,
                    ':reason'   => $reason !== '' ? $reason : null,
                ]);
            } catch (Throwable $e) {
                $auditOk = false;
            }

            $vip = $accRepo->getVIPStatus($account);
            echo json_encode([
                'success'   => true,
                'vip'       => $vip,
                'audit_log' => $auditOk,
            ], JSON_THROW_ON_ERROR);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción inválida: lookup o grant']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error al otorgar VIP.']);
}
