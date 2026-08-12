<?php
/**
 * POST /api/forum/edit_post.php  [requiere token]
 * Body JSON: { target_type: "thread"|"post", id, title? (solo thread), body }
 *
 * Reglas (F-02.02 / F-03.04):
 * - El autor puede editar lo suyo solo dentro de la ventana de
 *   ForumValidation::EDIT_WINDOW_MINUTES (30 min).
 * - El admin puede editar cualquier cosa, siempre. Cuando edita contenido
 *   ajeno queda marcado edited_by_staff (el público ve "editado por el staff",
 *   sin exponer qué admin fue) y el cuerpo anterior va al log de auditoría.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/forum_db.php';
require_once SRC_ROOT . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth = requireAuth();
$body = json_decode(file_get_contents('php://input'), true);

$targetType = (string) ($body['target_type'] ?? '');
$id         = (int) ($body['id'] ?? 0);
$content    = ForumValidation::sanitizeBody((string) ($body['body'] ?? ''));

if (!in_array($targetType, ['thread', 'post'], true) || $id <= 0) {
    http_response_code(400); echo json_encode(['error' => 'Parámetros inválidos.']); exit;
}
$bodyMax = $targetType === 'thread' ? ForumValidation::THREAD_BODY_MAX : ForumValidation::POST_BODY_MAX;
if ($err = ForumValidation::validateBody($content, $bodyMax)) {
    http_response_code(422); echo json_encode(['error' => $err]); exit;
}

/** ¿La fila sigue dentro de la ventana de edición del autor? (created_at es UTC) */
function foroDentroDeVentana(string $createdAt): bool {
    try {
        $creado = new DateTime($createdAt, new DateTimeZone('UTC'));
        $ahora  = new DateTime('now', new DateTimeZone('UTC'));
        return ($ahora->getTimestamp() - $creado->getTimestamp()) < ForumValidation::EDIT_WINDOW_MINUTES * 60;
    } catch (Throwable $e) {
        return false; // fecha ilegible: mejor negar la ventana que regalarla
    }
}

try {
    $repo    = new ForumRepository(ForumDatabase::get());
    $isAdmin = isAdminAccount($auth['usr']);

    $row = $targetType === 'thread' ? $repo->getThread($id) : $repo->getPost($id);
    if (!$row || $row['is_deleted']) {
        http_response_code(404); echo json_encode(['error' => 'No encontrado.']); exit;
    }

    $esDueno = $row['author_account'] === $auth['usr'];
    if (!$esDueno && !$isAdmin) {
        http_response_code(403); echo json_encode(['error' => 'No podés editar este mensaje.']); exit;
    }
    if ($esDueno && !$isAdmin && !foroDentroDeVentana($row['created_at'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Pasó la ventana de edición de ' . ForumValidation::EDIT_WINDOW_MINUTES . ' minutos. Pedile la corrección al staff.']);
        exit;
    }

    $content = ForumValidation::neutralizeUnsafeLinks($content);
    $content = ForumValidation::restrictImages($content);
    if (!$isAdmin && $repo->countPostsByAccount($auth['usr']) < ForumValidation::NEW_ACCOUNT_LINK_THRESHOLD) {
        $content = ForumValidation::restrictExternalLinks($content);
    }

    $byStaff = $isAdmin && !$esDueno;

    if ($targetType === 'thread') {
        $title = trim(ForumValidation::sanitizeBody((string) ($body['title'] ?? '')));
        if ($err = ForumValidation::validateTitle($title)) {
            http_response_code(422); echo json_encode(['error' => $err]); exit;
        }
        if ($byStaff) {
            $repo->logModeration($auth['usr'], 'edit_thread', 'thread', (string) $id, null,
                'TÍTULO: ' . $row['title'] . "\n\n" . $row['body']);
        }
        $repo->editThread($id, $title, $content, $byStaff);
    } else {
        if ($byStaff) {
            $repo->logModeration($auth['usr'], 'edit_post', 'post', (string) $id, null, $row['body']);
        }
        $repo->editPost($id, $content, $byStaff);
    }

    // Aviso al autor cuando el staff edita su contenido (F-07.02) — sin
    // exponer qué admin fue (actor_display null)
    if ($byStaff) {
        try {
            $threadId = $targetType === 'thread' ? $id : (int) $row['thread_id'];
            $repo->addNotification($row['author_account'], 'moderacion', $threadId,
                $targetType === 'post' ? $id : null, null);
        } catch (Throwable $e) { /* el aviso nunca rompe la edición */ }
    }

    echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudo editar.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
