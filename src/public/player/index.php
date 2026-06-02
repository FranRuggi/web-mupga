<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$charName  = htmlspecialchars(trim($_GET['name'] ?? ''), ENT_QUOTES);
$pageTitle = $charName ? "Perfil de {$charName}" : 'Perfil de jugador';
$extraJs   = 'player.js';
ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title" id="profile-name"><?= $charName ?: '...' ?></h1>
    <p class="page-hero-sub" id="profile-class">Cargando...</p>
  </div>

  <div class="profile-layout" id="profile-container">
    <!-- Skeleton mientras carga -->
    <div class="profile-card skeleton-block">
      <div class="skeleton" style="height:280px;border-radius:var(--radius)"></div>
    </div>
    <div class="profile-card skeleton-block">
      <div class="skeleton" style="height:40px;margin-bottom:1rem;border-radius:var(--radius)"></div>
      <div class="skeleton" style="height:280px;border-radius:var(--radius)"></div>
    </div>
  </div>

</main>

<script>
  // Pasar el nombre al JS antes de que cargue player.js
  const PROFILE_CHAR = <?= json_encode($charName) ?>;
</script>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
