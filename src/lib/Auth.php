<?php
/**
 * Auth — Middleware de autenticación para endpoints protegidos.
 *
 * Uso en cualquier api/account/*.php:
 *   $auth = requireAuth();
 *   // $auth['uid'] => user_id (int)
 *   // $auth['usr'] => username (string)
 */

function requireAuth(): array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
        http_response_code(401);
        echo json_encode(['error' => 'Token de autenticación requerido.'], JSON_THROW_ON_ERROR);
        exit;
    }

    $payload = TokenService::verify($m[1]);

    if ($payload === null) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado. Iniciá sesión nuevamente.'], JSON_THROW_ON_ERROR);
        exit;
    }

    return $payload;
}
