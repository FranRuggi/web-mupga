<?php
/**
 * GET /api/reclamos/mios.php  [requiere token]
 * Lista los tickets del jugador logueado (más nuevos primero), con datos
 * para pintar el listado de "Mis reclamos": estado, si tiene respuesta
 * sin leer, fecha del último movimiento, cantidad de mensajes y un
 * extracto del primer mensaje como título de la tarjeta.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/reclamos_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$auth = requireAuth();

try {
    $stmt = ReclamosDatabase::get()->prepare(
        "SELECT r.id, r.estado, r.no_leido, r.created_at,
                (SELECT MAX(m.created_at) FROM reclamos.mensajes m
                  WHERE m.reclamo_id = r.id)                       AS ultimo_movimiento,
                (SELECT COUNT(*) FROM reclamos.mensajes m
                  WHERE m.reclamo_id = r.id)                       AS total_mensajes,
                (SELECT TOP 1 m.mensaje FROM reclamos.mensajes m
                  WHERE m.reclamo_id = r.id ORDER BY m.id ASC)     AS primer_mensaje
         FROM reclamos.reclamos r
         WHERE r.nick = :nick
         ORDER BY r.id DESC"
    );
    $stmt->execute([':nick' => $auth['usr']]);
    $rows = $stmt->fetchAll();

    // Extracto corto para la tarjeta del listado — el texto completo se ve
    // en el detalle del ticket, acá no hace falta mandar mensajes enteros.
    foreach ($rows as &$r) {
        $r['extracto'] = mb_substr((string) $r['primer_mensaje'], 0, 120);
        unset($r['primer_mensaje']);
    }
    unset($r);

    echo json_encode(['items' => $rows], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'No se pudieron cargar tus reclamos.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
