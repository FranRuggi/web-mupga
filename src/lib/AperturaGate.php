<?php
/**
 * AperturaGate — enforcement SERVER-SIDE de la pantalla de apertura.
 *
 * Mismo criterio que Lockdown.php: la pantalla del frontend es solo visual
 * (cualquiera borra el div desde DevTools). El bloqueo real es este: hasta
 * APERTURA_OBJETIVO_UTC toda la API responde 503, así que aunque manipulen
 * el HTML no hay datos que ver.
 *
 * Exentos (lo mínimo para que la pantalla cumpla su función y podamos operar):
 *   - /api/auth/register.php → el botón REGISTRATE tiene que funcionar
 *   - /api/auth/login.php    → los que ya tienen cuenta pueden entrar; el admin también
 *   - /api/site/status.php   → aviso de emergencia del ControlPanel
 *   - /api/site/hora.php     → reloj del server que sincroniza el contador
 *   - /api/admin/*           → el panel sigue operativo (cada endpoint valida admin igual)
 *
 * Se apaga solo: pasada la hora objetivo, aperturaEnCurso() da false y esta
 * función no hace nada. No hay que deployar para "abrir".
 *
 * Se ejecuta automáticamente al incluir _cors.php (choke point de todos los
 * endpoints), después del preflight OPTIONS y de enforceLockdown().
 */

function enforceApertura(): void
{
    require_once SRC_ROOT . '/config/apertura.php';

    if (!aperturaEnCurso()) {
        return;
    }

    $script = str_replace('\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    $exento = static function (string $sufijo) use ($script): bool {
        return substr($script, -strlen($sufijo)) === $sufijo;
    };

    if (strpos($script, '/api/admin/') !== false
        || $exento('/api/auth/register.php')
        || $exento('/api/auth/login.php')
        || $exento('/api/site/status.php')
        || $exento('/api/site/hora.php')) {
        return;
    }

    $faltan = aperturaObjetivoEpoch() - time();

    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('Retry-After: ' . max(1, $faltan));
    echo json_encode([
        'error'        => 'MuPGA abre en breve. Registrate para entrar apenas abramos.',
        'apertura'     => true,
        'objetivo_utc' => APERTURA_OBJETIVO_UTC,
    ]);
    exit;
}
