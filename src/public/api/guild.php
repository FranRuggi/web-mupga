<?php
/**
 * GET /api/guild.php?name=NombreGuild
 * Devuelve perfil público de un guild: info + lista de miembros.
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
    $db = Database::get();

    $guild = $db->prepare(
        "SELECT G_Name, G_Master, G_Score, G_Count,
                CONVERT(varchar(max), G_Mark, 2) AS G_Mark_Hex
         FROM Guild WHERE G_Name = ?"
    );
    $guild->execute([$name]);
    $g = $guild->fetch();

    if (!$g) {
        http_response_code(404);
        echo json_encode(['error' => 'Guild no encontrado.']);
        exit;
    }

    $members = $db->prepare(
        "SELECT gm.Name, gm.G_Level, gm.G_Status,
                c.Class, c.cLevel, ISNULL(c.ResetCount,0) AS ResetCount
         FROM GuildMember gm
         LEFT JOIN Character c ON c.Name = gm.Name
         WHERE gm.G_Name = ?
         ORDER BY gm.G_Status ASC, c.ResetCount DESC"
    );
    $members->execute([$name]);

    echo json_encode([
        'name'    => $g['G_Name'],
        'master'  => $g['G_Master'],
        'score'   => (int) $g['G_Score'],
        'count'   => (int) $g['G_Count'],
        'members' => array_map(fn($m) => [
            'name'    => $m['Name'],
            'class'   => (int) ($m['Class'] ?? 0),
            'level'   => (int) ($m['cLevel'] ?? 0),
            'resets'  => (int) ($m['ResetCount'] ?? 0),
            'rank'    => (int) ($m['G_Level'] ?? 0),
            'status'  => (int) ($m['G_Status'] ?? 0),
        ], $members->fetchAll()),
    ], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    $r = ['error' => 'Error interno.'];
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') $r['debug'] = $e->getMessage();
    echo json_encode($r);
}
