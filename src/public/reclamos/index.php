<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Reclamos';
$extraJs   = 'reclamos.js';
ob_start();
?>

<main class="site-main">
  <div class="form-page">
    <div class="form-card">

      <h1 class="form-title">Reclamos</h1>
      <p class="form-subtitle">Contanos qué pasó, lo revisamos lo antes posible.</p>

      <div id="reclamo-alert" class="alert" role="alert"></div>

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
          <button class="btn btn-primary btn-full" type="submit" id="btn-reclamo-submit">Enviar reclamo</button>
        </div>
      </form>

    </div>
  </div>
</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
