<?php
/**
 * /api/admin/reclamos.php  [requiere admin]
 *
 * GET  → últimos 50 reclamos (nuevos primero, después por fecha desc).
 * POST → { action: 'responder', id, respuesta }
 *   Guarda la respuesta, marca estado='resuelto' y leido=0 (para que el
 *   banner le aparezca al jugador en su próxima visita al sitio).
 *
 * Usa AdminDatabase (mupga_web_svc), igual que el resto de /api/admin/*:
 * ese login ya tiene db_datawriter sobre toda mupga_admin, así que llega
 * sin problema al schema reclamos.
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

// ── GET: listado ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $rows = $db->query(
            "SELECT TOP 50 id, nick, mensaje, imagenes_json, estado, created_at,
                    respuesta, respondido_en, respondido_por
             FROM reclamos.reclamos
             ORDER BY CASE WHEN estado = 'nuevo' THEN 0 ELSE 1 END, created_at DESC"
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

$action = $body['action'] ?? '';

try {
    switch ($action) {

        case 'responder': {
            $id        = (int) ($body['id'] ?? 0);
            $respuesta = trim((string) ($body['respuesta'] ?? ''));

            if ($id <= 0 || $respuesta === '') {
                http_response_code(400); echo json_encode(['error' => 'id y respuesta son obligatorios']); exit;
            }
            if (mb_strlen($respuesta) > 2000) {
                http_response_code(400); echo json_encode(['error' => 'La respuesta supera los 2000 caracteres']); exit;
            }

            $stmt = $db->prepare(
                "UPDATE reclamos.reclamos
                    SET respuesta = :r, estado = 'resuelto', leido = 0,
                        respondido_en = DATEADD(HOUR, 3, GETDATE()), respondido_por = :by
                  WHERE id = :id"
            );
            $stmt->execute([':r' => $respuesta, ':by' => $admin['usr'], ':id' => $id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404); echo json_encode(['error' => 'Reclamo no encontrado']); exit;
            }
            echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción inválida: responder']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
