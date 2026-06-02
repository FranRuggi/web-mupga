<?php
/**
 * POST /api/account/resetml.php  [requiere token]
 * Resetea el árbol de habilidades Master del personaje.
 * Devuelve los puntos de maestría al contador disponible (mlPoint = mLevel).
 *
 * Body JSON: { "character": "NombrePersonaje" }
 *
 * SEGURIDAD: se verifica que el personaje pertenezca a la cuenta del token.
 * SAFE: escritura en Character.mlPoint — clasificado como seguro en capability-matrix.md.
 * NOTA: No borra las habilidades ya activadas en el árbol; solo devuelve el contador
 *       de puntos disponibles. Un reset completo requiere limpiar MasterSkillTree,
 *       operación pendiente de verificar en el schema real del servidor.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

$auth     = requireAuth();
$body     = json_decode(file_get_contents('php://input'), true);
$charName = trim($body['character'] ?? '');

if (!$charName) {
    http_response_code(400); echo json_encode(['error' => 'Nombre de personaje requerido.']); exit;
}

try {
    $db   = Database::get();
    $repo = new CharacterRepository($db);

    if (!$repo->belongsToAccount($charName, $auth['usr'])) {
        http_response_code(403);
        echo json_encode(['error' => 'El personaje no pertenece a tu cuenta.']);
        exit;
    }

    $char = $repo->getByName($charName);
    if (!$char) {
        http_response_code(404); echo json_encode(['error' => 'Personaje no encontrado.']); exit;
    }

    $mLevel = (int)($char['mLevel'] ?? 0);

    if ($mLevel === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'El personaje no tiene nivel maestro activo.']);
        exit;
    }

    // Devolver mlPoint = mLevel (1 punto por nivel maestro, fórmula estándar S6)
    $repo->updateMasterPoints($charName, $mLevel);

    echo json_encode([
        'message'    => "Árbol de habilidades de {$charName} reseteado. Puntos disponibles: {$mLevel}.",
        'new_points' => $mLevel,
    ], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error al resetear el árbol de habilidades.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
