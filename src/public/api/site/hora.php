<?php
/**
 * GET /api/site/hora.php  (público, sin auth, sin DB)
 * Reloj del servidor en epoch UTC. Lo usa assets/js/apertura.js para
 * sincronizar la cuenta regresiva de la apertura: el visitante puede tener
 * el reloj del sistema mal, así que el contador se corrige contra este valor.
 *
 * epoch_ms es UTC absoluto (independiente de la timezone del SO del VPS, que
 * en este server ya cambió dos veces — ver CLAUDE.md, Incidentes de Seguridad).
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode([
    'epoch_ms' => (int) round(microtime(true) * 1000),
    'iso'      => gmdate('Y-m-d\TH:i:s\Z'),
]);
