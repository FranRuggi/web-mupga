<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle = 'Info del servidor';
$extraJs   = 'info.js';

ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Info del servidor</h1>
    <p class="page-hero-sub">Todo lo que necesitás saber antes de entrar a jugar.</p>
  </div>

  <section class="section">
    <div id="info-content">
      <!-- info.js carga data/info.json via /api/infodata.php y renderiza acá -->
    </div>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
