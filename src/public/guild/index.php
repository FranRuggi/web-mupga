<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$guildName = htmlspecialchars(trim($_GET['name'] ?? ''), ENT_QUOTES);
$pageTitle  = $guildName ? "Guild {$guildName}" : 'Guild';
$extraJs    = 'guild.js';
ob_start();
?>
<main class="site-main">
  <div class="page-hero">
    <h1 class="page-hero-title" id="guild-name"><?= $guildName ?: '...' ?></h1>
    <p class="page-hero-sub" id="guild-meta">Cargando...</p>
  </div>
  <div class="profile-layout" id="guild-container">
    <div class="profile-card skeleton-block">
      <div class="skeleton" style="height:200px;border-radius:var(--radius)"></div>
    </div>
    <div class="profile-card skeleton-block">
      <div class="skeleton" style="height:300px;border-radius:var(--radius)"></div>
    </div>
  </div>
</main>
<script>const GUILD_NAME = <?= json_encode($guildName) ?>;</script>
<?php $content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
