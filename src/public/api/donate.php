<?php
/**
 * GET /api/donate.php
 * Devuelve la URL de donación y los paquetes disponibles.
 *
 * ============================================================
 * ENLACE DE PAGO — ÚNICO PUNTO DE CONFIGURACIÓN
 * ============================================================
 * Para cambiar la URL de la plataforma de pagos:
 *   1. Editar DONATION_URL en el archivo .env del VPS.
 *   2. No hay que tocar código PHP ni JS.
 *
 * MIGRACIÓN: Asegurarse de que DONATION_URL esté en el .env
 * del VPS antes de deployar.
 * ============================================================
 *
 * Para agregar/quitar paquetes: editar el array $packages abajo.
 * Los precios son solo informativos — la plataforma de pagos
 * maneja los valores reales y el procesamiento.
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=300');

// ── URL de la plataforma de pagos ─────────────────────────────
// MODIFICAR SOLO A TRAVÉS DE .env → DONATION_URL
$donationUrl = $_ENV['DONATION_URL'] ?? null;

// ── Paquetes de WCoin disponibles ────────────────────────────
// Modificar acá para agregar, quitar o cambiar paquetes.
// "price_display" es solo visual — el precio real lo maneja la plataforma de pagos.
$packages = [
    [
        'id'            => 1,
        'name'          => 'Starter',
        'wcoin'         => 1000,
        'price_display' => 'USD 5',
        'popular'       => false,
        'badge'         => null,
    ],
    [
        'id'            => 2,
        'name'          => 'Avanzado',
        'wcoin'         => 5000,
        'price_display' => 'USD 20',
        'popular'       => true,
        'badge'         => 'Más popular',
    ],
    [
        'id'            => 3,
        'name'          => 'Pro',
        'wcoin'         => 12000,
        'price_display' => 'USD 40',
        'popular'       => false,
        'badge'         => '+20% bonus',
    ],
    [
        'id'            => 4,
        'name'          => 'Elite',
        'wcoin'         => 30000,
        'price_display' => 'USD 90',
        'popular'       => false,
        'badge'         => '+50% bonus',
    ],
];

echo json_encode([
    'donation_url'     => $donationUrl,
    'has_payment_link' => $donationUrl !== null,
    'packages'         => $packages,
], JSON_THROW_ON_ERROR);
