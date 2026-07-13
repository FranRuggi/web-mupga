<?php
/**
 * GET /api/site/status.php  (público, sin auth)
 * Estado del sitio desde mupga_admin.dbo.site_status (fila única id=1).
 * Lo consume el frontend en cada carga de página:
 *   - is_active = 0 → no se muestra nada.
 *   - mode = 'banner'  → franja no bloqueante arriba de la página.
 *   - mode = 'overlay' → tapa toda la página con title + message.
 *
 * Sin cache: es el canal de avisos de emergencia, tiene que reaccionar
 * al toque cuando el admin lo prende o lo apaga.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/admin_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

try {
    // CONVERT(..., 126) → ISO 8601 (yyyy-mm-ddThh:mi:ss)
    $stmt = AdminDatabase::get()->prepare(
        'SELECT is_active, mode, title, message,
                CONVERT(varchar(19), scheduled_end, 126) AS scheduled_end
           FROM dbo.site_status
          WHERE id = 1'
    );
    $stmt->execute();
    $row = $stmt->fetch();

    if (!$row) {
        // La fila id=1 debería existir siempre; si no está, comportarse como inactivo
        echo json_encode(['is_active' => 0]);
        exit;
    }

    echo json_encode([
        'is_active'     => (int) $row['is_active'],
        'mode'          => $row['mode'],
        'title'         => $row['title'],
        'message'       => $row['message'],
        'scheduled_end' => $row['scheduled_end'],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
