<?php
/**
 * /api/admin/promo.php  [requiere admin]
 *
 * GET  → estado actual del popup promocional (fila id=1).
 * POST → actualizar. Body JSON:
 *   { "is_active": 0|1, "eyebrow": "...", "title": "...", "highlight": "...",
 *     "description": "...", "image_url": "...", "cta_text": "...", "cta_link": "..." }
 *
 * Seguridad: POST-only para mutar; el Bearer token actúa como protección
 * CSRF (no viaja automáticamente como una cookie). Transacción con
 * UPDLOCK/HOLDLOCK — mismo patrón que site-status.php.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$admin = requireAdmin();
$db    = AdminDatabase::get();

// ── GET: estado actual ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $promo = $db->query(
            "SELECT is_active, eyebrow, title, highlight, description,
                    image_url, cta_text, cta_link,
                    updated_by, CONVERT(varchar(19), updated_at, 120) AS updated_at
               FROM dbo.promo_popup WHERE id = 1"
        )->fetch();

        echo json_encode(['promo' => $promo], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de base de datos']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

// ── POST: actualizar ─────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400); echo json_encode(['error' => 'Body JSON inválido']); exit;
}

$isActive    = (int) ($body['is_active'] ?? 0);
$eyebrow     = trim((string) ($body['eyebrow'] ?? ''));
$title       = trim((string) ($body['title'] ?? ''));
$highlight   = trim((string) ($body['highlight'] ?? ''));
$description = trim((string) ($body['description'] ?? ''));
$imageUrl    = trim((string) ($body['image_url'] ?? ''));
$ctaText     = trim((string) ($body['cta_text'] ?? ''));
$ctaLink     = trim((string) ($body['cta_link'] ?? ''));

if (!in_array($isActive, [0, 1], true)) {
    http_response_code(400); echo json_encode(['error' => 'is_active debe ser 0 o 1']); exit;
}
if ($isActive === 1 && ($title === '' || $highlight === '')) {
    http_response_code(400); echo json_encode(['error' => 'title y highlight son obligatorios para activar el popup']); exit;
}
// Evita esquemas peligrosos (javascript:, data:) en el href del CTA — mismo
// criterio de allowlist que renderRichText() usa para links de noticias.
if ($ctaLink !== '' && !preg_match('#^(/|https?://)#i', $ctaLink)) {
    http_response_code(400); echo json_encode(['error' => "cta_link inválido: debe empezar con '/', 'http://' o 'https://'"]); exit;
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare('SELECT id FROM dbo.promo_popup WITH (UPDLOCK, HOLDLOCK) WHERE id = 1');
    $stmt->execute();
    if (!$stmt->fetch()) {
        $db->rollBack();
        http_response_code(500); echo json_encode(['error' => 'No existe la fila promo_popup id=1']); exit;
    }

    $stmt = $db->prepare(
        'UPDATE dbo.promo_popup
            SET is_active = :a, eyebrow = :ey, title = :t, highlight = :h,
                description = :d, image_url = :img, cta_text = :ct, cta_link = :cl,
                updated_by = :by, updated_at = GETDATE()
          WHERE id = 1'
    );
    $stmt->execute([
        ':a' => $isActive, ':ey' => $eyebrow, ':t' => $title, ':h' => $highlight,
        ':d' => $description, ':img' => $imageUrl, ':ct' => $ctaText, ':cl' => $ctaLink,
        ':by' => $admin['usr'],
    ]);

    $db->commit();
    echo json_encode(['success' => true], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
