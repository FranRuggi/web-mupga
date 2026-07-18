<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Reclamos';
$extraJs   = 'reclamos.js';
ob_start();
?>

<main class="site-main">
  <div class="form-page">
    <div class="form-card form-card--wide">

      <h1 class="form-title">Reclamos</h1>
      <p class="form-subtitle">Contanos qué pasó, lo revisamos lo antes posible.</p>

      <!-- Tabs: nuevo reclamo / historial -->
      <div class="reclamo-tabs">
        <button type="button" class="reclamo-tab active" id="tab-nuevo">Nuevo reclamo</button>
        <button type="button" class="reclamo-tab" id="tab-mios">Mis reclamos <span class="reclamo-tab-badge" id="mios-badge" hidden></span></button>
      </div>

      <div id="reclamo-alert" class="alert" role="alert"></div>

      <!-- ── Vista: nuevo reclamo ── -->
      <form id="reclamo-form" novalidate>

        <div class="form-group">
          <label class="form-label" for="reclamo-mensaje">Mensaje</label>
          <textarea class="form-input" id="reclamo-mensaje" rows="6" maxlength="2000"
                    placeholder="Contanos qué pasó..." required></textarea>
          <span class="field-error" id="err-mensaje"></span>
        </div>

        <div class="form-group">
          <label class="form-label">Imágenes (opcional, hasta 5)</label>

          <div id="reclamo-dropzone" class="cp-dropzone">
            <div id="reclamo-dropzone-idle">
              📷 Arrastrá imágenes acá o <u>hacé click para elegir</u><br>
              <small>JPG · PNG · WebP — hasta 5 imágenes</small>
            </div>
            <input type="file" id="reclamo-image-file" accept="image/jpeg,image/png,image/webp" multiple hidden>
          </div>

          <div id="reclamo-previews" class="reclamo-preview-grid"></div>
        </div>

        <div class="form-actions">
          <button class="btn btn-primary btn-full" type="submit" id="btn-reclamo-submit">
            <span class="spinner" id="reclamo-spinner" hidden></span>
            <span id="reclamo-btn-text">Enviar reclamo</span>
          </button>
          <p class="form-hint" id="reclamo-progress" hidden></p>
        </div>
      </form>

      <!-- ── Vista: mis reclamos (listado) ── -->
      <div id="reclamo-lista" hidden>
        <div id="reclamo-lista-items"><p class="state-message">Cargando…</p></div>
      </div>

      <!-- ── Vista: detalle de un ticket (hilo) ── -->
      <div id="reclamo-detalle" hidden>
        <div class="reclamo-detalle-head">
          <button type="button" class="btn btn-secondary btn-sm" id="btn-volver">← Volver</button>
          <span id="detalle-titulo"></span>
          <span id="detalle-estado"></span>
        </div>

        <div id="reclamo-hilo"><p class="state-message">Cargando…</p></div>

        <!-- Caja de respuesta del jugador -->
        <form id="reply-form" class="reclamo-reply-box" novalidate>
          <textarea class="form-input" id="reply-mensaje" rows="3" maxlength="2000"
                    placeholder="Agregar un comentario…"></textarea>

          <div class="reclamo-reply-actions">
            <button type="button" class="btn btn-secondary btn-sm" id="reply-attach">📷 Adjuntar</button>
            <input type="file" id="reply-image-file" accept="image/jpeg,image/png,image/webp" multiple hidden>
            <button class="btn btn-primary btn-sm" type="submit" id="btn-reply-submit">
              <span class="spinner" id="reply-spinner" hidden></span>
              <span id="reply-btn-text">Comentar</span>
            </button>
          </div>
          <div id="reply-previews" class="reclamo-preview-grid"></div>
          <p class="form-hint" id="reply-progress" hidden></p>
          <p class="form-hint">Si el reclamo estaba resuelto, comentar lo vuelve a abrir.</p>
        </form>
      </div>

    </div>
  </div>
</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
