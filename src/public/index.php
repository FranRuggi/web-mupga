<?php
require_once dirname(__DIR__) . '/bootstrap.php';

// Placeholder — el routing y las vistas se agregan en Fase 3

try {
    $db      = Database::get();
    $ranking = new RankingsRepository($db);
    $online  = $ranking->getOnlineCount();
} catch (PDOException $e) {
    $online = null;
    $dbError = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? $e->getMessage() : null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MuPGA — Capa de datos OK</title>
</head>
<body>
    <h1>MuPGA Web</h1>
    <p>Fase 2 completada — capa de acceso a datos lista.</p>
    <?php if ($online !== null): ?>
        <p>Jugadores online: <strong><?= $online ?></strong></p>
    <?php elseif (!empty($dbError)): ?>
        <p style="color:red">Error DB: <?= htmlspecialchars($dbError) ?></p>
    <?php else: ?>
        <p>Sin conexión a la base de datos.</p>
    <?php endif; ?>
</body>
</html>
