<?php
/**
 * Opciones de personaje del usercp — interruptor único.
 *
 * FUENTE ÚNICA DE VERDAD de qué acciones de la tarjeta "⚔ Opciones de personaje"
 * están habilitadas. Este archivo lo consumen:
 *   - src/public/usercp/index.php        → renderiza el botón grisado y su leyenda
 *   - src/public/api/account/*.php       → responde 403 antes de tocar la DB
 *
 * ── CÓMO SE REHABILITA UNA OPCIÓN ────────────────────────────
 * Sacarle la línea al array de abajo y deployar las dos capas:
 *   1. push a main   → GitHub Actions rebuildea Cloudflare Pages (frontend).
 *   2. git pull en el VPS → levanta el bloqueo de la API.
 * (El paso 1 solo desgrisa el botón; el 2 solo abre el endpoint. Van los dos.)
 *
 * ── POR QUÉ EL BLOQUEO TAMBIÉN VA EN EL BACKEND ──────────────
 * Mismo criterio que Lockdown.php y AperturaGate.php: el botón grisado es solo
 * visual — cualquiera le saca el `disabled` desde DevTools o llama al endpoint
 * con curl. El bloqueo real es enforceCharAction() en cada api/account/*.php.
 *
 * A diferencia de la apertura, acá NO hay fail-open: si este archivo falta, el
 * require del endpoint tira 500 y la acción queda cerrada. Un deploy a medias
 * no puede reabrir una opción que decidimos tener cerrada.
 */

/**
 * Acciones bloqueadas: clave = id de la acción (el mismo del endpoint y del
 * botón `btn-<accion>`), valor = leyenda que muestra el botón grisado.
 */
const CHAR_ACTIONS_BLOQUEADAS = [
    'unstick'    => 'Próximamente disponible',
    'clearpk'    => 'Próximamente disponible',
    'resetstats' => 'Próximamente disponible',
    'resetml'    => 'Próximamente disponible',
    'resetchar'  => 'Próximamente disponible',
];

/** Mensaje del 403 para quien llame al endpoint a mano. */
const CHAR_ACTION_MENSAJE_API = 'Esta opción no está disponible por el momento.';

/**
 * ¿La acción está bloqueada?
 *
 * array_key_exists() y no isset(): PHP no acepta isset() sobre el índice de una
 * constante ("Cannot use isset() on the result of an expression").
 */
function charActionBloqueada(string $accion): bool
{
    return array_key_exists($accion, CHAR_ACTIONS_BLOQUEADAS);
}

/**
 * Atributo que marca el botón como bloqueado de forma permanente.
 * usercp.js lo lee para no engancharle listener ni rehabilitarlo al elegir
 * personaje — así el JS no necesita saber qué opciones están cerradas.
 */
function charActionAttr(string $accion): string
{
    return charActionBloqueada($accion) ? ' data-locked="1"' : '';
}

/** Leyenda del botón: la del bloqueo si está cerrado, si no la descripción normal. */
function charActionLeyenda(string $accion, string $descripcion): string
{
    return charActionBloqueada($accion)
        ? CHAR_ACTIONS_BLOQUEADAS[$accion]
        : $descripcion;
}

/** Corta el request con 403 si la acción está bloqueada. */
function enforceCharAction(string $accion): void
{
    if (!charActionBloqueada($accion)) {
        return;
    }

    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode([
        'error'     => CHAR_ACTION_MENSAJE_API,
        'bloqueada' => true,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
