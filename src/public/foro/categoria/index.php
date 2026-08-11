<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';

$categoryId = (int) ($_GET['id'] ?? 0);
$pageTitle  = 'Foro';
$extraJs    = 'foro.js';

ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <p class="page-hero-sub"><a href="#" class="rank-name-link" id="foro-breadcrumb-back">← Volver al foro</a></p>
    <h1 class="page-hero-title" id="foro-cat-nombre">Cargando…</h1>
    <p class="page-hero-sub" id="foro-cat-desc"></p>
  </div>

  <section class="section">
    <div class="cp-form" id="foro-nuevo-hilo-form" hidden>
      <p class="account-card__title">Nuevo hilo</p>
      <label class="cp-field"><span>Título</span><input type="text" id="foro-nuevo-titulo" maxlength="200"></label>
      <label class="cp-field"><span>Mensaje</span><textarea id="foro-nuevo-body" rows="5" maxlength="5000"></textarea></label>
      <div class="cp-actions">
        <button class="btn btn-primary" id="foro-nuevo-submit">Publicar</button>
        <button class="btn btn-secondary" id="foro-nuevo-cancelar">Cancelar</button>
      </div>
      <p class="cp-feedback" id="foro-nuevo-feedback"></p>
    </div>

    <div class="cp-actions" id="foro-nuevo-hilo-toggle-wrap" style="margin-bottom:var(--gap-md)">
      <button class="btn btn-primary" id="foro-nuevo-hilo-toggle">✏️ Nuevo hilo</button>
    </div>

    <div id="foro-hilos-container"><p class="state-message">Cargando…</p></div>
  </section>

</main>

<script>const FORO_CATEGORY_ID = <?= json_encode($categoryId) ?>;</script>
<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
