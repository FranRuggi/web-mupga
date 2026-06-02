<?php
/**
 * GET /api/downloadsdata.php
 * Sirve data/downloads.json al frontend.
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

$file = SRC_ROOT . '/public/data/downloads.json';

if (!file_exists($file)) {
    echo json_encode(['items' => []]);
    exit;
}

echo file_get_contents($file);
