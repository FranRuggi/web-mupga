<?php
/**
 * GET /api/account/profile.php  [requiere token]
 * Devuelve info de la cuenta, estado VIP y personajes.
 */
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/_cors.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$auth    = requireAuth();
$db      = Database::get();
$account = (new AccountRepository($db))->getById($auth['uid']);

if (!$account) {
    http_response_code(404);
    echo json_encode(['error' => 'Cuenta no encontrada.']);
    exit;
}

$characters = (new CharacterRepository($db))->getByAccount($auth['usr']);
$vip        = (new AccountRepository($db))->getVIPStatus($auth['usr']);

echo json_encode([
    'username'      => $account['memb___id'],
    'email'         => $account['mail_addr'],
    'account_level' => (int) $account['AccountLevel'],
    'expire_date'   => $account['AccountExpireDate'],
    'is_vip'        => (int) $account['AccountLevel'] > 0,
    'created_at'    => $account['CreatedAt'] ?? null,
    'is_online'     => (new AccountRepository($db))->isOnline($auth['usr']),
    'characters'    => array_map(fn($c) => [
        'name'           => $c['Name'],
        'class'          => (int) $c['Class'],
        'level'          => (int) $c['cLevel'],
        'resets'         => (int) $c['ResetCount'],
        'master_resets'  => (int) ($c['MasterResetCount'] ?? 0),
        'map'            => (int) $c['MapNumber'],
        'level_up_point' => (int) ($c['LevelUpPoint'] ?? 0),
        'str'            => (int) ($c['Strength']   ?? 0),
        'agi'            => (int) ($c['Dexterity']  ?? 0),
        'vit'            => (int) ($c['Vitality']   ?? 0),
        'ene'            => (int) ($c['Energy']     ?? 0),
        'cmd'            => (int) ($c['Leadership'] ?? 0),
        'zen'            => (int) ($c['Money']      ?? 0),
    ], $characters),
], JSON_THROW_ON_ERROR);
