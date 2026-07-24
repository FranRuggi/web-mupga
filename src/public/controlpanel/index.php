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
      <button class="cp-tab active" data-tab="status">🔔 Estado del sitio</button>
      <button class="cp-tab" data-tab="news">📰 Noticias</button>
      <button class="cp-tab" data-tab="serverinfo">🗂️ Info del servidor</button>
      <button class="cp-tab" data-tab="downloads">📥 Descargas</button>
      <button class="cp-tab" data-tab="reclamos">🎫 Reclamos</button>
      <button class="cp-tab" data-tab="tienda">🛒 Tienda</button>
      <button class="cp-tab" data-tab="wcoin">🪙 WCoins</button>
      <button class="cp-tab" data-tab="estadisticas">📊 Estadísticas</button>
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
        <div class="cp-field">
          <span>Contenido</span>
          <div class="cp-editor">
            <div class="cp-editor__toolbar" id="news-toolbar">
              <button type="button" data-md="bold"      title="Negrita (Ctrl+B)"><strong>B</strong></button>
              <button type="button" data-md="italic"    title="Cursiva (Ctrl+I)"><em>I</em></button>
              <button type="button" data-md="underline" title="Subrayado (Ctrl+U)"><u>S</u></button>
              <button type="button" data-md="strike"    title="Tachado"><s>T</s></button>
              <span class="cp-editor__sep"></span>
              <button type="button" data-md="heading" title="Subtítulo">H</button>
              <button type="button" data-md="list"    title="Lista">≡ Lista</button>
              <button type="button" data-md="link"    title="Enlace">🔗</button>
              <span class="cp-editor__sep"></span>
              <button type="button" id="news-emoji-btn" title="Insertar emoji">😀</button>
              <button type="button" id="news-preview-toggle" title="Vista previa">👁 Vista previa</button>
            </div>
            <div class="cp-emoji-picker" id="news-emoji-picker" hidden></div>
            <textarea id="news-body" rows="8"></textarea>
            <div class="cp-editor__preview news-article__body" id="news-preview" hidden></div>
          </div>
          <small class="cp-hint" style="margin:0">
            **negrita** · *cursiva* · __subrayado__ · ~~tachado~~ · ## subtítulo · "- " lista · [texto](https://url)
          </small>
        </div>
        <div class="cp-field">
          <span>Imagen (opcional)</span>
          <div class="cp-dropzone" id="news-dropzone">
            <input type="file" id="news-image-file" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
            <div class="cp-dropzone__idle" id="news-dropzone-idle">
              📷 Arrastrá una imagen acá o <u>hacé click para elegir</u><br>
              <small>JPG · PNG · WebP · GIF — máx 3 MB</small>
            </div>
            <div class="cp-dropzone__preview" id="news-dropzone-preview" hidden>
              <img id="news-image-preview" alt="">
              <button type="button" class="btn btn-secondary btn-sm" id="news-image-remove">✕ Quitar imagen</button>
            </div>
          </div>
          <input type="hidden" id="news-image-url" value="">
        </div>
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

    <!-- ── Reclamos ── -->
    <section class="cp-section account-card" id="cp-tab-reclamos" hidden>
      <p class="account-card__title">Reclamos</p>

      <!-- Vista listado -->
      <div id="reclamos-admin-list"><p class="state-message">Cargando…</p></div>

      <!-- Vista detalle (hilo) -->
      <div id="reclamos-admin-detalle" hidden>
        <div class="reclamo-detalle-head">
          <button type="button" class="btn btn-secondary btn-sm" id="reclamo-adm-volver">← Volver</button>
          <span id="reclamo-adm-titulo"></span>
          <span id="reclamo-adm-estado"></span>
        </div>

        <div id="reclamo-adm-hilo"><p class="state-message">Cargando…</p></div>

        <div class="cp-form reclamo-reply-box">
          <label class="cp-field">
            <span>Respuesta del staff</span>
            <textarea id="reclamo-resp-text" rows="4" maxlength="2000"></textarea>
          </label>
          <label class="cp-field" style="flex-direction:row;align-items:center;gap:0.5rem">
            <input type="checkbox" id="reclamo-resp-resolver" checked style="width:auto">
            <span style="margin:0">Marcar como resuelto al responder</span>
          </label>
          <div class="cp-actions">
            <button class="btn btn-primary" id="reclamo-resp-send">Enviar respuesta</button>
            <button class="btn btn-secondary" id="reclamo-adm-toggle-estado"></button>
          </div>
          <p class="cp-feedback" id="reclamos-feedback"></p>
        </div>
      </div>
    </section>

    <!-- ── Tienda WCoin ── -->
    <section class="cp-section account-card" id="cp-tab-tienda" hidden>
      <p class="account-card__title">Tienda WCoin — Reimportar catálogo</p>
      <p class="cp-hint">
        Arrastrá los 5 archivos de config del CashShop in-game — se identifican solos por
        nombre, podés soltarlos todos juntos o de a uno. Deben estar sincronizados entre sí:
        esto reemplaza el catálogo completo de la tienda web.
      </p>
      <div class="cp-form">
        <div class="cp-dropzone" id="tienda-dropzone">
          <input type="file" id="tienda-file-input" accept=".txt" multiple hidden>
          <div id="tienda-dropzone-idle">
            📂 Arrastrá acá los 5 archivos o <u>hacé click para elegirlos</u>
            <br><small>Se reconocen automáticamente por su nombre de archivo</small>
          </div>
        </div>

        <ul class="cp-checklist" id="tienda-checklist"></ul>

        <div class="cp-actions">
          <button class="btn btn-primary" id="tienda-import" disabled>Reimportar catálogo</button>
        </div>
        <p class="cp-feedback" id="tienda-feedback"></p>
        <div id="tienda-import-result"></div>
      </div>
    </section>

    <!-- ── WCoins ── -->
    <section class="cp-section account-card" id="cp-tab-wcoin" hidden>
      <p class="account-card__title">Acreditar WCoins</p>
      <p class="cp-hint">Usa el mismo stored procedure que el resto del sitio (sp_AddWCoinWithLog). Verificá la cuenta antes de acreditar.</p>

      <div class="cp-form">
        <label class="cp-field">
          <span>Cuenta (memb___id)</span>
          <input type="text" id="wcoin-account" maxlength="10" autocomplete="off">
        </label>
        <div class="cp-actions">
          <button class="btn btn-secondary" id="wcoin-lookup">Verificar cuenta</button>
        </div>
        <div class="cp-status-box" id="wcoin-lookup-result" hidden></div>

        <label class="cp-field">
          <span>Monto (WCoin, entero positivo)</span>
          <input type="number" id="wcoin-amount" min="1" step="1">
        </label>
        <label class="cp-field">
          <span>Motivo (opcional, se guarda en la auditoría)</span>
          <input type="text" id="wcoin-reason" maxlength="300">
        </label>
        <div class="cp-actions">
          <button class="btn btn-primary" id="wcoin-credit" disabled>Acreditar</button>
        </div>
        <p class="cp-feedback" id="wcoin-feedback"></p>
      </div>

      <p class="account-card__title" style="margin-top:var(--gap-md)">Últimos créditos manuales</p>
      <div id="wcoin-history"><p class="state-message">Cargando…</p></div>
    </section>

    <!-- ── Estadísticas de compras (Tienda WCoin) ── -->
    <section class="cp-section account-card" id="cp-tab-estadisticas" hidden>
      <p class="account-card__title">Estadísticas de la Tienda WCoin</p>
      <p class="cp-hint">
        En qué gastan los jugadores su WCoin. Solo cuenta compras hechas desde la Tienda
        web a partir de que se sumó esta auditoría — no incluye compras viejas ni gasto
        de WCoin dentro del CashShop in-game.
      </p>

      <div class="cp-grid-3" id="stats-resumen">
        <p class="state-message">Cargando…</p>
      </div>

      <p class="account-card__title" style="margin-top:var(--gap-md)">Ítems más comprados</p>
      <div id="stats-top-items"><p class="state-message">Cargando…</p></div>

      <p class="account-card__title" style="margin-top:var(--gap-md)">Jugadores que más gastan</p>
      <div id="stats-top-compradores"><p class="state-message">Cargando…</p></div>

      <p class="account-card__title" style="margin-top:var(--gap-md)">Gasto por día (últimos 30 días)</p>
      <div id="stats-por-dia"><p class="state-message">Cargando…</p></div>
    </section>

  </div>
</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
