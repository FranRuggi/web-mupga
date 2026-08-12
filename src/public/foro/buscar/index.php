<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';

$pageTitle = 'Foro — Buscar';
$extraJs   = 'foro.js';

ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <p class="page-hero-sub"><a href="#" class="rank-name-link" id="foro-breadcrumb-back">← Volver al foro</a></p>
    <h1 class="page-hero-title">Buscar en el foro</h1>
    <p class="page-hero-sub">Buscá antes de preguntar — capaz ya lo respondieron.</p>
  </div>

  <section class="section">
    <div class="cp-actions" style="margin-bottom:var(--gap-md)">
      <input type="text" id="foro-buscar-input" class="forum-search-input" maxlength="100"
             placeholder="Buscar en títulos y mensajes (mínimo 3 caracteres)…">
      <button class="btn btn-primary" id="foro-buscar-btn">🔍 Buscar</button>
    </div>

    <div id="foro-buscar-container"><p class="state-message">Escribí un término y buscá.</p></div>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
