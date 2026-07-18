<?php
/**
 * /api/admin/reclamos.php  [requiere admin]
 *
 * GET        → últimos 50 tickets (los 'nuevo' primero, después por último
 *              movimiento desc), con extracto y contador de mensajes.
 * GET ?id=N  → detalle de un ticket: cabecera + hilo completo de mensajes.
 * POST       → mutaciones. Body JSON con "action":
 *   - responder:  { action, id, mensaje, resolver: 0|1 }
 *       Agrega un mensaje del staff al hilo y prende no_leido (dispara el
 *       banner del jugador). Con resolver=1 además marca estado='resuelto'.
 *   - set_estado: { action, id, estado: 'nuevo'|'resuelto' }
 *       Cambia el estado sin comentar (cerrar sin respuesta / reabrir).
 *
 * Usa AdminDatabase (mupga_web_svc), igual que el resto de /api/admin/*:
 * ese login tiene db_datawriter sobre toda mupga_admin, llega sin problema
 * al schema reclamos.
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

// ── GET ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    try {
        if ($id > 0) {
            // Detalle: cabecera + hilo
            $ticket = $db->prepare(
                'SELECT id, nick, estado, no_leido, created_at
                 FROM reclamos.reclamos WHERE id = :id'
            );
            $ticket->execute([':id' => $id]);
            $reclamo = $ticket->fetch();

            if ($reclamo === false) {
                http_response_code(404); echo json_encode(['error' => 'Reclamo no encontrado']); exit;
            }

            $mensajes = $db->prepare(
                'SELECT id, autor_tipo, autor_nick, mensaje, imagenes_json, created_at
                 FROM reclamos.mensajes WHERE reclamo_id = :id ORDER BY id ASC'
            );
            $mensajes->execute([':id' => $id]);
            $hilo = $mensajes->fetchAll();

            foreach ($hilo as &$m) {
                $m['imagenes'] = $m['imagenes_json'] ? (json_decode($m['imagenes_json'], true) ?: []) : [];
                unset($m['imagenes_json']);
            }
            unset($m);

            echo json_encode(['reclamo' => $reclamo, 'mensajes' => $hilo], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } else {
            // Listado
            $rows = $db->query(
                "SELECT TOP 50 r.id, r.nick, r.estado, r.created_at,
                        (SELECT MAX(m.created_at) FROM reclamos.mensajes m
                          WHERE m.reclamo_id = r.id)                   AS ultimo_movimiento,
                        (SELECT COUNT(*) FROM reclamos.mensajes m
                          WHERE m.reclamo_id = r.id)                   AS total_mensajes,
                        (SELECT TOP 1 m.mensaje FROM reclamos.mensajes m
                          WHERE m.reclamo_id = r.id ORDER BY m.id ASC) AS primer_mensaje
                 FROM reclamos.reclamos r
                 ORDER BY CASE WHEN r.estado = 'nuevo' THEN 0 ELSE 1 END,
                          ultimo_movimiento DESC"
            )->fetchAll();

            foreach ($rows as &$r) {
                $r['extracto'] = mb_substr((string) $r['primer_mensaje'], 0, 120);
                unset($r['primer_mensaje']);
            }
            unset($r);

            echo json_encode(['items' => $rows], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
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

try {
    switch ($action) {

        case 'responder': {
            $id       = (int) ($body['id'] ?? 0);
            $mensaje  = trim((string) ($body['mensaje'] ?? ''));
            $resolver = (int) ($body['resolver'] ?? 0) === 1;

            if ($id <= 0 || $mensaje === '') {
                http_response_code(400); echo json_encode(['error' => 'id y mensaje son obligatorios']); exit;
            }
            if (mb_strlen($mensaje) > 2000) {
                http_response_code(400); echo json_encode(['error' => 'La respuesta supera los 2000 caracteres']); exit;
            }

            $existe = $db->prepare('SELECT TOP 1 1 FROM reclamos.reclamos WHERE id = :id');
            $existe->execute([':id' => $id]);
            if ($existe->fetchColumn() === false) {
                http_response_code(404); echo json_encode(['error' => 'Reclamo no encontrado']); exit;
            }

            $db->beginTransaction();

            $insert = $db->prepare(
                "INSERT INTO reclamos.mensajes (reclamo_id, autor_tipo, autor_nick, mensaje, created_at)
                 VALUES (:id, 'admin', :nick, :mensaje, DATEADD(HOUR, 3, GETDATE()))"
            );
            $insert->execute([':id' => $id, ':nick' => $admin['usr'], ':mensaje' => $mensaje]);

            // no_leido=1 → el jugador ve el banner en su próxima visita.
            $update = $db->prepare(
                $resolver
                    ? "UPDATE reclamos.reclamos SET no_leido = 1, estado = 'resuelto' WHERE id = :id"
                    : "UPDATE reclamos.reclamos SET no_leido = 1 WHERE id = :id"
            );
            $update->execute([':id' => $id]);

            $db->commit();
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        case 'set_estado': {
            $id     = (int) ($body['id'] ?? 0);
            $estado = (string) ($body['estado'] ?? '');

            // Allowlist estricta, mismo criterio que el modo de site_status.
            if ($id <= 0 || !in_array($estado, ['nuevo', 'resuelto'], true)) {
                http_response_code(400); echo json_encode(['error' => "id y estado ('nuevo'|'resuelto') son obligatorios"]); exit;
            }

            $stmt = $db->prepare('UPDATE reclamos.reclamos SET estado = :e WHERE id = :id');
            $stmt->execute([':e' => $estado, ':id' => $id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404); echo json_encode(['error' => 'Reclamo no encontrado']); exit;
            }
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción inválida: responder o set_estado']);
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
