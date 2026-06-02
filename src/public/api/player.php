<?php
/**
 * GET /api/player.php?name=NombrePersonaje
 * Devuelve el perfil público de un personaje (solo lectura).
 *
 * Respuesta: {
 *   name, class, level, master_level, resets, master_resets,
 *   str, agi, vit, ene, cmd, pk_count, pk_level,
 *   guild, guild_level, map, is_online, account_created
 * }
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

$name = trim($_GET['name'] ?? '');

if ($name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro name requerido.']);
    exit;
}

try {
    $db        = Database::get();
    $charRepo  = new CharacterRepository($db);
    $acctRepo  = new AccountRepository($db);

    $char = $charRepo->getByName($name);
    if (!$char) {
        http_response_code(404);
        echo json_encode(['error' => 'Personaje no encontrado.']);
        exit;
    }

    // Guild membership
    $guildStmt = $db->prepare(
        'SELECT G_Name, G_Level FROM GuildMember WHERE Name = ?'
    );
    $guildStmt->execute([$name]);
    $guild = $guildStmt->fetch();

    // Online status (vía AccountID del personaje)
    $accountId = $char['AccountID'] ?? '';
    $isOnline  = $accountId ? $acctRepo->isOnline($accountId) : false;

    // Fecha de creación de la cuenta (puede no existir en backups viejos)
    $accountStmt = $db->prepare(
        'SELECT CreatedAt FROM MEMB_INFO WHERE memb___id = ?'
    );
    $accountStmt->execute([$accountId]);
    $accountRow = $accountStmt->fetch();

    echo json_encode([
        'name'           => $char['Name']                    ?? '',
        'class'          => (int)($char['Class']             ?? 0),
        'level'          => (int)($char['cLevel']            ?? 0),
        'master_level'   => (int)($char['mLevel']            ?? 0),
        'resets'         => (int)($char['ResetCount']        ?? 0),
        'master_resets'  => (int)($char['MasterResetCount']  ?? 0),
        'str'            => (int)($char['Strength']          ?? 0),
        'agi'            => (int)($char['Dexterity']         ?? 0),
        'vit'            => (int)($char['Vitality']          ?? 0),
        'ene'            => (int)($char['Energy']            ?? 0),
        'cmd'            => (int)($char['Leadership']        ?? 0),
        'pk_count'       => (int)($char['PkCount']           ?? 0),
        'pk_level'       => (int)($char['PkLevel']           ?? 3),
        'map'            => (int)($char['MapNumber']         ?? 0),
        'guild'          => $guild['G_Name']  ?? null,
        'guild_rank'     => (int)($guild['G_Level']          ?? 0),
        'is_online'      => $isOnline,
        'account_created'=> $accountRow['CreatedAt'] ?? null,
    ], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    $payload = ['error' => 'Error interno.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $payload['debug'] = $e->getMessage();
    echo json_encode($payload);
}
