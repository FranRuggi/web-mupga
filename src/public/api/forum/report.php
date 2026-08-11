<?php
/**
 * POST /api/forum/report.php  [requiere token]
 * Body JSON: { target_type: "thread"|"post", target_id, reason, comment? }
 *
 * Reporta contenido al staff (F-08.02). El autor del contenido NO se entera.
 * - reason: allowlist en ForumValidation::REPORT_REASONS
 * - Un reporte por cuenta y contenido (409 si ya reportó)
 * - Máx REPORTS_PER_HOUR_MAX por hora por cuenta (429)
 * - Aviso a Discord opcional (DISCORD_WEBHOOK_FORO) en try/catch independiente:
 *   si el webhook falla, el reporte queda guardado igual.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$targetType = (string) ($body['target_type'] ?? '');
$targetId   = (int) ($body['target_id'] ?? 0);
$reason     = (string) ($body['reason'] ?? '');
$comment    = trim(ForumValidation::sanitizeBody((string) ($body['comment'] ?? ''))) ?: null;

if (!in_array($targetType, ['thread', 'post'], true) || $targetId <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Parámetros inválidos.']); exit;
}
if (!in_array($reason, ForumValidation::REPORT_REASONS, true)) {
    http_response_code(400); echo json_encode(['error' => 'Motivo inválido.']); exit;
}
if ($comment !== null && mb_strlen($comment) > 500) {
    http_response_code(422); echo json_encode(['error' => 'El comentario supera los 500 caracteres.']); exit;
}

try {
    $repo = new ForumRepository(ForumDatabase::get());

    if ($repo->isBanned($auth['usr'])) {
        http_response_code(403); echo json_encode(['error' => 'Estás baneado del foro.']); exit;
    }

    $target = $targetType === 'thread' ? $repo->getThread($targetId) : $repo->getPost($targetId);
    if (!$target || $target['is_deleted']) {
        http_response_code(404); echo json_encode(['error' => 'No encontrado.']); exit;
    }
    if ($target['author_account'] === $auth['usr']) {
        http_response_code(400); echo json_encode(['error' => 'No podés reportar tu propio mensaje.']); exit;
    }
    if ($repo->hasReported($targetType, $targetId, $auth['usr'])) {
        http_response_code(409); echo json_encode(['error' => 'Ya reportaste este mensaje.']); exit;
    }
    if ($repo->countRecentReportsByAccount($auth['usr']) >= ForumValidation::REPORTS_PER_HOUR_MAX) {
        http_response_code(429); echo json_encode(['error' => 'Demasiados reportes en poco tiempo. Probá más tarde.']); exit;
    }

    $repo->createReport($targetType, $targetId, $auth['usr'], $reason, $comment);

    // Aviso a Discord del staff — nunca aborta la respuesta si falla
    try {
        $webhook = $_ENV['DISCORD_WEBHOOK_FORO'] ?? '';
        if ($webhook !== '') {
            $threadId = $targetType === 'thread' ? $targetId : (int) $target['thread_id'];
            $excerpt  = mb_substr($targetType === 'thread' ? $target['title'] : $target['body'], 0, 120);
            $payload  = json_encode(['embeds' => [[
                'title'       => '🚩 Reporte en el foro',
                'description' => "**Motivo:** {$reason}\n**Contenido:** {$excerpt}\n**Autor:** {$target['author_display_name']}"
                               . ($comment ? "\n**Comentario:** {$comment}" : ''),
                'url'         => "https://mupga.com.ar/foro/hilo/?id={$threadId}",
                'color'       => 15158332,
            ]]], JSON_UNESCAPED_UNICODE);

            $ch = curl_init($webhook);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 3,
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
    } catch (Throwable $e) { /* el reporte ya está guardado — seguir */ }

    echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo enviar el reporte.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
