<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';

$threadId  = (int) ($_GET['id'] ?? 0);
$pageTitle = 'Foro';
$extraJs   = 'foro.js';

ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <p class="page-hero-sub"><a href="#" class="rank-name-link" id="foro-breadcrumb-back">← Volver a la categoría</a></p>
    <h1 class="page-hero-title" id="foro-hilo-titulo">Cargando…</h1>
    <p class="page-hero-sub" id="foro-hilo-meta"></p>
  </div>

  <section class="section">
    <div id="foro-admin-controls" class="cp-actions" style="margin-bottom:var(--gap-md)" hidden>
      <button class="btn btn-secondary btn-sm" id="foro-btn-pin">📌 Fijar</button>
      <button class="btn btn-secondary btn-sm" id="foro-btn-lock">🔒 Cerrar</button>
      <button class="btn btn-secondary btn-sm" id="foro-btn-mover">📂 Mover</button>
      <button class="btn btn-secondary btn-sm" id="foro-btn-editar-hilo">✏️ Editar hilo</button>
      <button class="btn btn-secondary btn-sm" id="foro-btn-borrar-hilo">🗑 Borrar hilo</button>
    </div>

    <div id="foro-hilo-container"><p class="state-message">Cargando…</p></div>

    <div class="cp-form" id="foro-respuesta-form" style="margin-top:var(--gap-lg)">
      <p class="account-card__title">Responder</p>
      <label class="cp-field"><span>Mensaje</span><textarea id="foro-respuesta-body" rows="4" maxlength="5000"></textarea></label>
      <div class="cp-actions">
        <button class="btn btn-primary" id="foro-respuesta-submit">Responder</button>
      </div>
      <p class="cp-feedback" id="foro-respuesta-feedback"></p>
    </div>
    <p class="state-message" id="foro-hilo-cerrado-msg" hidden>Este hilo está cerrado — no se puede responder.</p>
  </section>

</main>

<script>const FORO_THREAD_ID = <?= json_encode($threadId) ?>;</script>
<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
