<?php
/**
 * AdminAuth — Middleware de autorización para el ControlPanel.
 *
 * requireAdmin() valida la sesión normal del sitio (mismo token JWT que el
 * UserCP) y además exige que el memb___id esté en mupga_admin.dbo.admins
 * con active = 1. Si no, corta con 403.
 *
 * Uso en api/admin/*.php:
 *   $admin = requireAdmin();
 *   // $admin['usr']  => AccountID (memb___id)
 *   // $admin['role'] => rol en dbo.admins
 */

require_once SRC_ROOT . '/config/admin_db.php';

function requireAdmin(): array {
    $auth = requireAuth(); // 401 si el token falta o es inválido

    $stmt = AdminDatabase::get()->prepare(
        'SELECT role FROM dbo.admins WHERE memb___id = :id AND active = 1'
    );
    $stmt->execute([':id' => $auth['usr']]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(403);
        echo json_encode(['error' => 'No tenés permisos de administrador.'], JSON_THROW_ON_ERROR);
        exit;
    }

    $auth['role'] = $row['role'];
    return $auth;
}
