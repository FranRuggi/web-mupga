<?php
/**
 * Apertura — pantalla de cuenta regresiva de la gran apertura del servidor.
 *
 * FUENTE ÚNICA DE VERDAD de la apertura. Este archivo lo consumen:
 *   - src/lib/AperturaGate.php      → bloqueo server-side de la API
 *   - src/templates/layout.php      → inyecta la config al HTML (window.MUPGA_APERTURA)
 *   - assets/js/apertura.js         → dibuja la pantalla y el contador
 *
 * ── CÓMO SE APAGA ────────────────────────────────────────────
 * Se apaga SOLA al llegar la hora: pasado APERTURA_OBJETIVO_UTC, ni la
 * pantalla ni el bloqueo de la API se activan. No hay que tocar nada.
 *
 * Para bajarla ANTES de la hora: poner APERTURA_ACTIVA en false, y
 *   1. push a main   → GitHub Actions rebuildea Cloudflare Pages (frontend).
 *   2. git pull en el VPS → apaga el bloqueo de la API.
 * (El paso 1 solo saca la pantalla; el 2 solo abre la API. Hacen falta los dos.)
 *
 * ── HORARIOS ─────────────────────────────────────────────────
 * El objetivo se define en UTC absoluto a propósito: cada visitante ve el
 * contador correcto en su zona horaria sin conversiones del lado del server.
 * NUNCA reemplazarlo por un offset fijo sobre la hora local del VPS — esa
 * timezone ya cambió dos veces y rompió el prode (ver "Incidentes de
 * Seguridad" en CLAUDE.md). time() y gmdate() son epoch UTC, inmunes a eso.
 */

// Interruptor manual. false → no hay pantalla ni bloqueo, sin importar la hora.
const APERTURA_ACTIVA = true;

// Instante exacto de la apertura, en UTC (ISO-8601 con Z).
// 2026-09-05T00:00:00Z == viernes 04/09/2026 21:00 hora de Argentina (UTC-3).
const APERTURA_OBJETIVO_UTC = '2026-09-05T00:00:00Z';

/** Epoch UTC del objetivo. */
function aperturaObjetivoEpoch(): int
{
    static $epoch = null;
    if ($epoch === null) {
        $epoch = (int) strtotime(APERTURA_OBJETIVO_UTC);
    }
    return $epoch;
}

/** ¿Estamos antes de la apertura y con la pantalla habilitada? */
function aperturaEnCurso(): bool
{
    return APERTURA_ACTIVA && aperturaObjetivoEpoch() > time();
}

/**
 * Config que layout.php inyecta al HTML. Se resuelve en tiempo de BUILD
 * para Cloudflare Pages (php build.php) y por request en local — en ambos
 * casos son constantes del repo, así que el valor horneado es el correcto.
 */
function aperturaConfigFront(): array
{
    return [
        'activa'       => APERTURA_ACTIVA,
        'objetivo_utc' => APERTURA_OBJETIVO_UTC,
        'objetivo_ms'  => aperturaObjetivoEpoch() * 1000,
    ];
}
