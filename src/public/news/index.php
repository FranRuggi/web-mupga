<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Noticias';
$extraJs   = 'news.js';
ob_start();
?>
<main class="site-main">
  <div class="page-hero">
    <h1 class="page-hero-title">Noticias</h1>
    <p class="page-hero-sub">Novedades, actualizaciones y mantenimientos del servidor.</p>
  </div>
  <section class="section">
    <div id="news-list"></div>
  </section>
</main>
<?php $content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
