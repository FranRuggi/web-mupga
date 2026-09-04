<?php
/**
 * Lockdown — enforcement SERVER-SIDE del modo overlay.
 *
 * El overlay del frontend es solo visual: cualquiera puede borrar el div
 * desde DevTools. El bloqueo real es este: con site_status en
 * is_active=1 + mode='overlay', TODA la API responde 503 y el sitio queda
 * efectivamente inutilizable aunque manipulen el HTML.
 *
 * Exentos (los mínimos para poder salir del lockdown):
 *   - /api/site/status.php   → el frontend necesita leer el estado para dibujar el aviso
 *   - /api/site/hora.php     → reloj del server (sin DB) para el contador de apertura
 *   - /api/admin/*           → el panel para apagarlo (cada endpoint valida admin igual)
 *   - /api/auth/login.php    → el admin necesita loguearse para entrar al panel
 *
 * Fail-open: si mupga_admin no responde, NO se bloquea — una caída de esa
 * base no puede tumbar toda la API del juego.
 *
 * Se ejecuta automáticamente al incluir _cors.php (choke point de todos
 * los endpoints), después del manejo del preflight OPTIONS.
 */

function enforceLockdown(): void {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    $exento = static function (string $sufijo) use ($script): bool {
        return substr($script, -strlen($sufijo)) === $sufijo;
    };

    if (strpos($script, '/api/admin/') !== false
        || $exento('/api/site/status.php')
        || $exento('/api/site/hora.php')
        || $exento('/api/auth/login.php')) {
        return;
    }

    try {
        require_once SRC_ROOT . '/config/admin_db.php';

        $row = AdminDatabase::get()
            ->query('SELECT is_active, mode FROM dbo.site_status WHERE id = 1')
            ->fetch();

        if ($row && (int) $row['is_active'] === 1 && $row['mode'] === 'overlay') {
            http_response_code(503);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            echo json_encode(['error' => 'Sitio en mantenimiento.', 'lockdown' => true]);
            exit;
        }
    } catch (Throwable $e) {
        // Fail-open intencional (ver docblock)
    }
}
