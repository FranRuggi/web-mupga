<?php
/**
 * /api/admin/wcoin.php  [requiere admin]
 *
 * GET  → últimos créditos manuales de WCoin (auditoría, mupga_admin.dbo.wcoin_credits).
 * POST → mutaciones. Body JSON con "action":
 *   - lookup: { action, account }              → existe la cuenta + saldo actual
 *   - credit: { action, account, amount, reason } → acredita WCoin y audita el movimiento
 *
 * sp_AddWCoinWithLog se ejecuta por Database::get() (conexión principal, no
 * AdminDatabase/mupga_web_svc) — mismo patrón que ProdeRepository::resolveMatch(),
 * documentado en CLAUDE.md: los SPs de premios corren por la conexión principal.
 * La auditoría propia (quién acreditó, a quién, cuánto, por qué) se guarda en
 * mupga_admin vía AdminDatabase, separada del log interno del juego (CashLog).
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

// ── GET: historial de créditos manuales ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $rows = $db->query(
            'SELECT TOP 100 id, admin_id, target_account, amount, reason, created_at
               FROM dbo.wcoin_credits ORDER BY id DESC'
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
            $balance = (new CreditsRepository(Database::get()))->getBalance($account);
            echo json_encode(['exists' => true, 'balance' => $balance], JSON_THROW_ON_ERROR);
            break;
        }

        case 'credit': {
            $amount = (int) ($body['amount'] ?? 0);
            $reason = trim((string) ($body['reason'] ?? ''));

            if ($amount <= 0) {
                http_response_code(400); echo json_encode(['error' => 'amount debe ser un entero positivo.']); exit;
            }
            if (mb_strlen($reason) > 300) {
                http_response_code(400); echo json_encode(['error' => 'reason supera los 300 caracteres.']); exit;
            }

            $accRepo = new AccountRepository(Database::get());
            if (!$accRepo->usernameExists($account)) {
                http_response_code(404); echo json_encode(['error' => 'La cuenta no existe.']); exit;
            }

            // A partir de acá el WCoin YA se acreditó (sp_AddWCoinWithLog no es
            // reversible de forma simple) — un fallo en la auditoría propia no
            // debe reportarse como error general, o un admin podría reintentar
            // y acreditar dos veces creyendo que la primera vez falló.
            (new CreditsRepository(Database::get()))->addWCoin($account, (float) $amount);

            $auditOk = true;
            try {
                $log = $db->prepare(
                    'INSERT INTO dbo.wcoin_credits (admin_id, target_account, amount, reason, created_at)
                     VALUES (:admin_id, :account, :amount, :reason, GETUTCDATE())'
                );
                $log->execute([
                    ':admin_id' => $admin['usr'],
                    ':account'  => $account,
                    ':amount'   => $amount,
                    ':reason'   => $reason !== '' ? $reason : null,
                ]);
            } catch (Throwable $e) {
                $auditOk = false;
            }

            $balance = (new CreditsRepository(Database::get()))->getBalance($account);
            echo json_encode([
                'success'   => true,
                'balance'   => $balance,
                'audit_log' => $auditOk,
            ], JSON_THROW_ON_ERROR);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción inválida: lookup o credit']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error al acreditar WCoin.']);
}
