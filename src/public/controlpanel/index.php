<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'ControlPanel';
$extraJs   = 'controlpanel.js';
ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">ControlPanel</h1>
    <p class="page-hero-sub">Administración del contenido del sitio</p>
  </div>

  <!-- Guard: se muestra mientras se verifica permiso de admin, o si no lo hay -->
  <div id="cp-guard" class="account-card">
    <p class="state-message" id="cp-guard-msg">Verificando permisos…</p>
  </div>

  <div id="cp-panel" hidden>

    <!-- Menú de secciones -->
    <div class="cp-tabs" role="tablist">
      <button class="cp-tab active" data-tab="status">Estado del sitio</button>
      <button class="cp-tab" data-tab="news">Noticias</button>
      <button class="cp-tab" data-tab="serverinfo">Info del servidor</button>
      <button class="cp-tab" data-tab="downloads">Descargas</button>
    </div>

    <!-- ── Estado del sitio ── -->
    <section class="cp-section account-card" id="cp-tab-status">
      <p class="account-card__title">Estado del sitio (banner / overlay)</p>
      <div class="cp-status-box" id="status-current">Cargando…</div>

      <div class="cp-form">
        <label class="cp-field">
          <span>Preset (opcional — completa título y mensaje)</span>
          <select id="status-preset"><option value="">— Sin preset —</option></select>
        </label>
        <div class="cp-grid-2">
          <label class="cp-field">
            <span>Modo</span>
            <select id="status-mode">
              <option value="banner">banner — franja arriba, no bloquea</option>
              <option value="overlay">overlay — tapa toda la página</option>
            </select>
          </label>
          <label class="cp-field">
            <span>Fin estimado (opcional)</span>
            <input type="datetime-local" id="status-end">
          </label>
        </div>
        <label class="cp-field">
          <span>Título</span>
          <input type="text" id="status-title" maxlength="200">
        </label>
        <label class="cp-field">
          <span>Mensaje</span>
          <textarea id="status-message" rows="3"></textarea>
        </label>
        <div class="cp-actions">
          <button class="btn btn-primary" id="status-activate">Activar aviso</button>
          <button class="btn btn-secondary" id="status-deactivate">Desactivar</button>
        </div>
        <p class="cp-feedback" id="status-feedback"></p>
      </div>
    </section>

    <!-- ── Noticias ── -->
    <section class="cp-section account-card" id="cp-tab-news" hidden>
      <p class="account-card__title">Noticias</p>
      <div id="news-admin-list"><p class="state-message">Cargando…</p></div>

      <p class="account-card__title" style="margin-top:var(--gap-md)" id="news-form-title">Nueva noticia</p>
      <div class="cp-form">
        <input type="hidden" id="news-id" value="">
        <label class="cp-field"><span>Título</span><input type="text" id="news-title" maxlength="200"></label>
        <label class="cp-field"><span>Categoría</span><input type="text" id="news-category" maxlength="50" placeholder="Anuncio / Info / Mantenimiento"></label>
        <label class="cp-field"><span>Resumen</span><input type="text" id="news-summary" maxlength="500"></label>
        <label class="cp-field"><span>Contenido</span><textarea id="news-body" rows="5"></textarea></label>
        <div class="cp-actions">
          <button class="btn btn-primary" id="news-save">Guardar</button>
          <button class="btn btn-secondary" id="news-cancel" hidden>Cancelar edición</button>
        </div>
        <p class="cp-feedback" id="news-feedback"></p>
      </div>
    </section>

    <!-- ── Info del servidor ── -->
    <section class="cp-section account-card" id="cp-tab-serverinfo" hidden>
      <p class="account-card__title">Info del servidor — JSON de secciones</p>
      <p class="cp-hint">Editá el JSON completo. Se valida antes de guardar: si está roto, no se guarda.</p>
      <div class="cp-form">
        <textarea id="serverinfo-json" rows="22" spellcheck="false" style="font-family:monospace;font-size:0.8rem"></textarea>
        <div class="cp-actions">
          <button class="btn btn-secondary" id="serverinfo-validate">Validar JSON</button>
          <button class="btn btn-primary" id="serverinfo-save">Guardar</button>
        </div>
        <p class="cp-feedback" id="serverinfo-feedback"></p>
      </div>
    </section>

    <!-- ── Descargas ── -->
    <section class="cp-section account-card" id="cp-tab-downloads" hidden>
      <p class="account-card__title">Descargas</p>
      <div id="downloads-admin-list"><p class="state-message">Cargando…</p></div>

      <p class="account-card__title" style="margin-top:var(--gap-md)" id="dl-form-title">Nueva descarga</p>
      <div class="cp-form">
        <input type="hidden" id="dl-id" value="">
        <label class="cp-field"><span>item_key (solo al crear — minúsculas y guiones)</span><input type="text" id="dl-key" maxlength="50"></label>
        <label class="cp-field"><span>Título</span><input type="text" id="dl-title" maxlength="200"></label>
        <label class="cp-field"><span>Descripción</span><textarea id="dl-desc" rows="2"></textarea></label>
        <label class="cp-field"><span>Versión</span><input type="text" id="dl-version" maxlength="20"></label>
        <label class="cp-field"><span>Tamaño</span><input type="text" id="dl-size" maxlength="20" placeholder="15 MB"></label>
        <label class="cp-field"><span>URL</span><input type="text" id="dl-url" maxlength="1000"></label>
        <label class="cp-field"><span>Orden</span><input type="number" id="dl-order" value="1" min="1"></label>
        <div class="cp-actions">
          <button class="btn btn-primary" id="dl-save">Guardar</button>
          <button class="btn btn-secondary" id="dl-cancel" hidden>Cancelar edición</button>
        </div>
        <p class="cp-feedback" id="dl-feedback"></p>
      </div>
    </section>

  </div>
</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
