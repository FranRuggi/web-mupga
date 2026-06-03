<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/_cors.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
$file = SRC_ROOT . '/public/data/news.json';
echo file_exists($file) ? file_get_contents($file) : '[]';
