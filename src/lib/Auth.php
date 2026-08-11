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
    // Apache suele eliminar el header Authorization. Se busca en tres lugares:
    // 1. $_SERVER['HTTP_AUTHORIZATION']         — Apache con mod_rewrite pasándolo
    // 2. $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] — Apache con FastCGI/suexec
    // 3. getallheaders()['Authorization']        — fallback universal
    $header = $_SERVER['HTTP_AUTHORIZATION']
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
           ?? (function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? '') : '')
           ?? '';

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

/**
 * Igual que requireAuth() pero no corta la ejecución si no hay sesión —
 * devuelve null en vez de 401. Para endpoints públicos que igual quieren
 * enriquecer la respuesta si el visitante está logueado (ej. "¿ya reaccioné
 * a este post?" en el foro).
 */
function optionalAuth(): ?array {
    $header = $_SERVER['HTTP_AUTHORIZATION']
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
           ?? (function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? '') : '')
           ?? '';

    if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
        return null;
    }

    return TokenService::verify($m[1]);
}
