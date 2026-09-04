<?php
/**
 * AperturaGate — enforcement SERVER-SIDE de la pantalla de apertura.
 *
 * Mismo criterio que Lockdown.php: la pantalla del frontend es solo visual
 * (cualquiera borra el div desde DevTools). El bloqueo real es este: hasta
 * la hora de apertura toda la API responde 503, así que aunque manipulen el
 * HTML no hay datos que ver.
 *
 * Exentos (lo mínimo para que la pantalla cumpla su función y podamos operar):
 *   - auth/register.php → el botón REGISTRATE tiene que funcionar
 *   - auth/login.php    → los que ya tienen cuenta pueden entrar; el admin también
 *   - site/status.php   → aviso de emergencia del ControlPanel
 *   - site/hora.php     → reloj del server que sincroniza el contador
 *   - /admin/*          → el panel sigue operativo (cada endpoint valida admin igual)
 *
 * Los sufijos van SIN el prefijo /api para no atarse a dónde esté montada la
 * app en Apache, y se comparan contra varias fuentes ($_SERVER) por lo mismo.
 *
 * Se apaga solo: pasada la hora objetivo, aperturaEnCurso() da false y esta
 * función no hace nada. No hay que deployar para "abrir".
 *
 * FAIL-OPEN a propósito: cualquier error acá deja pasar el request. Esta
 * pantalla es marketing, no seguridad — no puede tumbar la API. El 2026-09-04
 * un backslash perdido en este archivo (parse error) dejó login y registro
 * caídos horas antes de la apertura; de ahí el try/catch y los guardas de
 * _cors.php.
 *
 * Se ejecuta al incluir _cors.php (choke point de todos los endpoints),
 * después del preflight OPTIONS y de enforceLockdown().
 */

function enforceApertura(): void
{
    try {
        // Si la config no se cargó, no bloquear nada.
        if (!function_exists('aperturaEnCurso') || !aperturaEnCurso()) {
            return;
        }

        $exentos = [
            '/auth/register.php',
            '/auth/login.php',
            '/site/status.php',
            '/site/hora.php',
        ];

        $rutas = [
            $_SERVER['SCRIPT_NAME']     ?? '',
            $_SERVER['SCRIPT_FILENAME'] ?? '',
            $_SERVER['PHP_SELF']        ?? '',
        ];

        foreach ($rutas as $ruta) {
            if ($ruta === '') {
                continue;
            }

            $ruta = str_replace('\\', '/', $ruta);

            if (strpos($ruta, '/admin/') !== false) {
                return;
            }

            foreach ($exentos as $sufijo) {
                if (substr($ruta, -strlen($sufijo)) === $sufijo) {
                    return;
                }
            }
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

    } catch (Throwable $e) {
        // Fail-open intencional (ver docblock).
        return;
    }
}
