<?php
/**
 * GET /api/site/servidores.php  (público, sin auth)
 * Servidores activos desde mupga_admin.dbo.servidores — alimenta el selector
 * de la landing (mupga.com.ar). Agregar un servidor es una fila nueva acá,
 * no un deploy de HTML.
 *
 * Respuesta: { "servidores": [ { slug, nombre, descripcion, version, experiencia,
 *              drop, sistema_reset, limite_resets, tienda_items, estado,
 *              fecha_lanzamiento, web_url, api_url, imagen_url }, ... ] }
 *
 * NOTA SOBRE EL LOCKDOWN (ver src/lib/Lockdown.php):
 *   Este endpoint NO está exento — con el overlay de mantenimiento activo
 *   responde 503 y la landing no lista nada, por decisión explícita (los
 *   avisos de mantenimiento van por Discord/WhatsApp, no por la web).
 *   A REVISAR cuando exista un segundo servidor: el lockdown se lee del
 *   site_status de ESTA API, así que un mantenimiento del servidor 1
 *   dejaría al selector sin mostrar el servidor 2, que estaría sano.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once SRC_ROOT . '/config/admin_db.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Método no permitido']); exit;
}

try {
    $stmt = AdminDatabase::get()->query(
        'SELECT slug, nombre, descripcion, version, experiencia, drop_rate,
                sistema_reset, limite_resets, tienda_items, estado,
                fecha_lanzamiento, web_url, api_url, imagen_url
           FROM dbo.servidores
          WHERE activo = 1
          ORDER BY orden ASC, id ASC'
    );

    $servidores = [];
    foreach ($stmt->fetchAll() as $row) {
        // fecha_lanzamiento sale como DATETIME2 en UTC; se normaliza a ISO 8601
        // con sufijo Z para que el countdown del navegador la interprete bien.
        $lanzamiento = null;
        if (!empty($row['fecha_lanzamiento'])) {
            $ts = strtotime($row['fecha_lanzamiento']);
            if ($ts !== false) $lanzamiento = gmdate('Y-m-d\TH:i:s\Z', $ts);
        }

        $servidores[] = [
            'slug'              => $row['slug'],
            'nombre'            => $row['nombre'],
            'descripcion'       => $row['descripcion'],
            'version'           => $row['version'],
            'experiencia'       => $row['experiencia'],
            'drop'              => $row['drop_rate'],   // en la DB es drop_rate (palabra reservada en T-SQL)
            'sistema_reset'     => $row['sistema_reset'],
            'limite_resets'     => $row['limite_resets'] === null ? null : (int) $row['limite_resets'],
            'tienda_items'      => (bool) $row['tienda_items'],
            'estado'            => $row['estado'],
            'fecha_lanzamiento' => $lanzamiento,
            'web_url'           => $row['web_url'],
            'api_url'           => $row['api_url'],
            'imagen_url'        => $row['imagen_url'],
        ];
    }

    echo json_encode(['servidores' => $servidores], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    header('Cache-Control: no-store'); // nunca cachear errores (un 500 cacheado 5 min ya nos pasó)
    http_response_code(500);
    $dev = ($_ENV['APP_ENV'] ?? '') === 'development';
    echo json_encode(['error' => $dev ? $e->getMessage() : 'Error de base de datos']);
}
