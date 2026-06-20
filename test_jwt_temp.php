<?php
require __DIR__ . '/src/bootstrap.php';
require __DIR__ . '/src/lib/TokenService.php';

$token = TokenService::generatePaymentJWT(308, 'ElAmante');
echo $token . PHP_EOL;

// Decodificar para verificar visualmente
[$h, $p] = explode('.', $token);
echo PHP_EOL . 'Header:  ' . base64_decode(strtr($h, '-_', '+/')) . PHP_EOL;
echo 'Payload: ' . base64_decode(strtr($p, '-_', '+/')) . PHP_EOL;
