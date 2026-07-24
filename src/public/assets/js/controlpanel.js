/* ============================================================
   MuPGA — controlpanel.js
   Panel de administración de contenido (mupga_admin).
   Depende de app.js (esc, BASE) y auth.js (authFetch, isAuthenticated).

   Guard: requiere sesión + estar en dbo.admins (el servidor valida
   con requireAdmin(); acá solo se maneja la UX del 403).
   ============================================================ */

// ── Helpers ───────────────────────────────────────────────────

async function adminFetch(endpoint, options = {}) {
  const res = await authFetch(`admin/${endpoint}`, options);
  if (!res) return { ok: false, status: 0, data: null };
  let data = null;
  try { data = await res.json(); } catch { /* respuesta sin body */ }
  return { ok: res.ok, status: res.status, data };
}

function feedback(id, msg, isError = false) {
  const el = document.getElementById(id);
  el.textContent = msg;
  el.style.color = isError ? '#e05555' : 'var(--cyan)';
  if (msg) setTimeout(() => { el.textContent = ''; }, 6000);
}

// ── Tabs ──────────────────────────────────────────────────────

function initTabs() {
  document.querySelectorAll('.cp-tab').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.cp-tab').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      document.querySelectorAll('.cp-section').forEach(s => s.hidden = true);
      document.getElementById(`cp-tab-${btn.dataset.tab}`).hidden = false;
    });
  });
}

// ── Estado del sitio ──────────────────────────────────────────

let statusPresets = [];

async function loadStatus() {
  const { ok, data } = await adminFetch('site-status.php');
  if (!ok || !data) return;

  const st = data.status;
  statusPresets = data.presets ?? [];

  const sel = document.getElementById('status-preset');
  sel.innerHTML = '<option value="">— Sin preset —</option>' +
    statusPresets.map(p => `<option value="${esc(p.preset_key)}">${esc(p.preset_key)}: ${esc(p.title)}</option>`).join('');

  const activo = st && Number(st.is_active);
  document.getElementById('status-current').innerHTML = st
    ? `<span class="cp-chip ${activo ? 'cp-chip--on' : 'cp-chip--off'}">${activo ? '● AVISO ACTIVO' : '○ Sin aviso'}</span>
       ${activo ? `<span class="cp-status-detail">modo <strong>${esc(st.mode)}</strong> — "${esc(st.title ?? '')}"</span>` : ''}
       <span class="cp-dim">Últ. cambio: ${esc(st.updated_by ?? '—')} · ${esc(st.updated_at ?? '')}</span>`
    : 'No se pudo leer el estado.';

  if (st) {
    document.getElementById('status-mode').value  = ['banner', 'overlay'].includes(st.mode) ? st.mode : 'banner';
    document.getElementById('status-title').value   = st.title ?? '';
    document.getElementById('status-message').value = st.message ?? '';
    document.getElementById('status-end').value     = st.scheduled_end ? st.scheduled_end.replace(' ', 'T') : '';
  }
}

function initStatus() {
  // Elegir un preset completa título y mensaje en el form (editables antes de enviar)
  document.getElementById('status-preset').addEventListener('change', (e) => {
    const p = statusPresets.find(x => x.preset_key === e.target.value);
    if (p) {
      document.getElementById('status-title').value   = p.title;
      document.getElementById('status-message').value = p.message;
    }
  });

  // Abrir el calendario nativo clickeando en cualquier parte del campo
  const endInput = document.getElementById('status-end');
  endInput.addEventListener('click', () => {
    try { endInput.showPicker(); } catch { /* navegadores sin showPicker: cae al comportamiento default */ }
  });

  document.getElementById('status-activate').addEventListener('click', () => saveStatus(1));
  document.getElementById('status-deactivate').addEventListener('click', () => saveStatus(0));
}

async function saveStatus(isActive) {
  const body = {
    is_active:     isActive,
    mode:          document.getElementById('status-mode').value,
    title:         document.getElementById('status-title').value.trim(),
    message:       document.getElementById('status-message').value.trim(),
    scheduled_end: document.getElementById('status-end').value || null,
  };
  const { ok, data } = await adminFetch('site-status.php', { method: 'POST', body: JSON.stringify(body) });
  feedback('status-feedback', ok ? (isActive ? 'Aviso activado ✔' : 'Aviso desactivado ✔') : (data?.error ?? 'Error'), !ok);
  if (ok) loadStatus();
}

// ── Imagen de noticia: drag & drop + upload ──────────────────

function setNewsImage(url) {
  document.getElementById('news-image-url').value = url || '';
  document.getElementById('news-dropzone-idle').hidden    = !!url;
  document.getElementById('news-dropzone-preview').hidden = !url;
  if (url) document.getElementById('news-image-preview').src = url;
}

async function uploadNewsImage(file) {
  if (!file || !file.type.startsWith('image/')) {
    feedback('news-feedback', 'El archivo no es una imagen.', true); return;
  }
  if (file.size > 3 * 1024 * 1024) {
    feedback('news-feedback', 'La imagen supera los 3 MB.', true); return;
  }

  const idle = document.getElementById('news-dropzone-idle');
  idle.textContent = 'Subiendo…';

  // fetch directo (no authFetch): multipart necesita que el browser
  // arme el Content-Type con boundary — no se puede fijar a mano.
  const form = new FormData();
  form.append('image', file);

  try {
    const res  = await fetch(`${API}/admin/upload.php`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${getToken()}` },
      body: form,
    });
    const data = await res.json();
    if (!res.ok || !data.url) {
      feedback('news-feedback', data?.error ?? 'Error al subir la imagen', true);
      resetDropzoneIdle();
      return;
    }
    setNewsImage(data.url);
    feedback('news-feedback', 'Imagen subida ✔');
  } catch {
    feedback('news-feedback', 'Error de red al subir la imagen', true);
  }
  resetDropzoneIdle();
}

function resetDropzoneIdle() {
  document.getElementById('news-dropzone-idle').innerHTML =
    '📷 Arrastrá una imagen acá o <u>hacé click para elegir</u><br><small>JPG · PNG · WebP · GIF — máx 3 MB</small>';
}

function initNewsDropzone() {
  const dz    = document.getElementById('news-dropzone');
  const input = document.getElementById('news-image-file');

  dz.addEventListener('click', (e) => {
    if (e.target.id !== 'news-image-remove') input.click();
  });
  input.addEventListener('change', () => {
    if (input.files[0]) uploadNewsImage(input.files[0]);
    input.value = '';
  });

  ['dragover', 'dragenter'].forEach(ev => dz.addEventListener(ev, e => {
    e.preventDefault(); dz.classList.add('cp-dropzone--over');
  }));
  ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
    e.preventDefault(); dz.classList.remove('cp-dropzone--over');
  }));
  dz.addEventListener('drop', e => {
    const file = e.dataTransfer?.files?.[0];
    if (file) uploadNewsImage(file);
  });

  document.getElementById('news-image-remove').addEventListener('click', () => setNewsImage(''));
}

// ── Editor de contenido (markdown-lite) ──────────────────────
// La barra inserta marcadores en el textarea; el render seguro
// lo hace renderRichText() (app.js) tanto en la vista previa
// como en la página pública de noticias.

const NEWS_EMOJIS = [
  '😀','😄','😎','🤩','😱','😈','🔥','⚡','✨','⭐','💥','❄️',
  '⚔️','🛡️','🏹','🐉','☠️','👑','🏆','🎁','🎉','💎','💰','🪙',
  '📢','📅','⏰','🕹️','🎮','🌎','✅','❌','⚠️','ℹ️','➡️','👉',
  '💪','🙌','🤝','❤️','🧡','💜',
];

// Reemplaza la selección preservando el historial de deshacer (Ctrl+Z)
function replaceSelection(ta, text) {
  ta.focus();
  if (!document.execCommand('insertText', false, text)) {
    ta.setRangeText(text, ta.selectionStart, ta.selectionEnd, 'end');
  }
}

// Envuelve la selección con un marcador inline (**, *, __, ~~)
function mdWrap(ta, mark) {
  const s = ta.selectionStart, e = ta.selectionEnd;
  const sel = ta.value.slice(s, e) || 'texto';
  replaceSelection(ta, mark + sel + mark);
  ta.setSelectionRange(s + mark.length, s + mark.length + sel.length);
}

// Prefija cada línea de la selección (subtítulos y listas)
function mdLinePrefix(ta, prefix) {
  let s = ta.selectionStart;
  const e = ta.selectionEnd;
  s = ta.value.lastIndexOf('\n', s - 1) + 1;          // arranque de la primera línea
  const sel = ta.value.slice(s, e) || 'texto';
  ta.setSelectionRange(s, e);
  replaceSelection(ta, sel.split('\n').map(l => l.trim() ? prefix + l : l).join('\n'));
}

function mdLink(ta) {
  const s = ta.selectionStart, e = ta.selectionEnd;
  const sel = ta.value.slice(s, e) || 'texto';
  const url = 'https://';
  replaceSelection(ta, `[${sel}](${url})`);
  const urlStart = s + sel.length + 3;                 // posiciona el cursor en la URL
  ta.setSelectionRange(urlStart, urlStart + url.length);
}

function updateNewsPreview() {
  const prev = document.getElementById('news-preview');
  if (prev.hidden) return;
  prev.innerHTML = renderRichText(document.getElementById('news-body').value)
    || '<p class="cp-dim">Nada que previsualizar…</p>';
}

function initNewsEditor() {
  const ta = document.getElementById('news-body');

  const actions = {
    bold:      () => mdWrap(ta, '**'),
    italic:    () => mdWrap(ta, '*'),
    underline: () => mdWrap(ta, '__'),
    strike:    () => mdWrap(ta, '~~'),
    heading:   () => mdLinePrefix(ta, '## '),
    list:      () => mdLinePrefix(ta, '- '),
    link:      () => mdLink(ta),
  };

  document.querySelectorAll('#news-toolbar [data-md]').forEach(btn => {
    btn.addEventListener('click', () => { actions[btn.dataset.md](); updateNewsPreview(); });
  });

  // Atajos de teclado dentro del textarea
  ta.addEventListener('keydown', (e) => {
    if (!(e.ctrlKey || e.metaKey)) return;
    const key = { b: 'bold', i: 'italic', u: 'underline' }[e.key.toLowerCase()];
    if (key) { e.preventDefault(); actions[key](); updateNewsPreview(); }
  });
  ta.addEventListener('input', updateNewsPreview);

  // Picker de emojis
  const picker = document.getElementById('news-emoji-picker');
  picker.innerHTML = NEWS_EMOJIS.map(em => `<button type="button">${em}</button>`).join('');
  picker.addEventListener('click', (e) => {
    if (e.target.tagName !== 'BUTTON') return;
    replaceSelection(ta, e.target.textContent);
    updateNewsPreview();
  });
  document.getElementById('news-emoji-btn').addEventListener('click', (e) => {
    e.stopPropagation();
    picker.hidden = !picker.hidden;
  });
  document.addEventListener('click', (e) => {
    if (!picker.hidden && !picker.contains(e.target)) picker.hidden = true;
  });

  // Vista previa en vivo
  document.getElementById('news-preview-toggle').addEventListener('click', () => {
    const prev = document.getElementById('news-preview');
    prev.hidden = !prev.hidden;
    updateNewsPreview();
  });
}

// ── Noticias ──────────────────────────────────────────────────

async function loadNewsAdmin() {
  const { ok, data } = await adminFetch('news.php');
  const el = document.getElementById('news-admin-list');
  if (!ok || !data?.news) { el.innerHTML = '<p class="state-message">Error al cargar.</p>'; return; }

  el.innerHTML = data.news.map(n => `
    <div class="cp-row">
      <div class="cp-row__info">
        <strong>${esc(n.title)}</strong>
        <span class="cp-dim">${esc(n.category)} · ${esc(n.published_at)} · ${Number(n.is_published) ? 'publicada' : '<em>despublicada</em>'}</span>
      </div>
      <div class="cp-row__actions">
        <button class="btn btn-secondary btn-sm" data-news-edit="${n.id}">Editar</button>
        <button class="btn btn-secondary btn-sm" data-news-pub="${n.id}" data-pub="${Number(n.is_published) ? 0 : 1}">
          ${Number(n.is_published) ? 'Despublicar' : 'Publicar'}
        </button>
      </div>
    </div>`).join('') || '<p class="state-message">Sin noticias.</p>';

  el.querySelectorAll('[data-news-edit]').forEach(b => b.addEventListener('click', () => {
    const n = data.news.find(x => String(x.id) === b.dataset.newsEdit);
    document.getElementById('news-id').value       = n.id;
    document.getElementById('news-title').value    = n.title;
    document.getElementById('news-category').value = n.category;
    document.getElementById('news-summary').value  = n.summary;
    document.getElementById('news-body').value     = n.body;
    updateNewsPreview();
    setNewsImage(n.image_url ?? '');
    document.getElementById('news-form-title').textContent = `Editando noticia #${n.id}`;
    document.getElementById('news-cancel').hidden = false;
  }));

  el.querySelectorAll('[data-news-pub]').forEach(b => b.addEventListener('click', async () => {
    const { ok: ok2, data: d2 } = await adminFetch('news.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'set_published', id: Number(b.dataset.newsPub), is_published: Number(b.dataset.pub) }),
    });
    feedback('news-feedback', ok2 ? 'Actualizado ✔' : (d2?.error ?? 'Error'), !ok2);
    if (ok2) loadNewsAdmin();
  }));
}

function resetNewsForm() {
  ['news-id', 'news-title', 'news-category', 'news-summary', 'news-body'].forEach(id => document.getElementById(id).value = '');
  updateNewsPreview();
  setNewsImage('');
  document.getElementById('news-form-title').textContent = 'Nueva noticia';
  document.getElementById('news-cancel').hidden = true;
}

function initNews() {
  initNewsDropzone();
  initNewsEditor();
  document.getElementById('news-cancel').addEventListener('click', resetNewsForm);
  document.getElementById('news-save').addEventListener('click', async () => {
    const id   = document.getElementById('news-id').value;
    const body = {
      action:    id ? 'update' : 'create',
      title:     document.getElementById('news-title').value.trim(),
      category:  document.getElementById('news-category').value.trim(),
      summary:   document.getElementById('news-summary').value.trim(),
      body:      document.getElementById('news-body').value.trim(),
      image_url: document.getElementById('news-image-url').value.trim(),
    };
    if (id) body.id = Number(id); else body.publish = 1;

    const { ok, data } = await adminFetch('news.php', { method: 'POST', body: JSON.stringify(body) });
    feedback('news-feedback', ok ? 'Guardado ✔' : (data?.error ?? 'Error'), !ok);
    if (ok) { resetNewsForm(); loadNewsAdmin(); }
  });
}

// ── Info del servidor ─────────────────────────────────────────

async function loadServerInfoAdmin() {
  const { ok, data } = await adminFetch('server-info.php');
  if (ok && data?.config_value) {
    // Pretty-print para editar cómodo
    try {
      document.getElementById('serverinfo-json').value = JSON.stringify(JSON.parse(data.config_value), null, 2);
    } catch {
      document.getElementById('serverinfo-json').value = data.config_value;
    }
  }
}

function validarServerInfoJson() {
  const raw = document.getElementById('serverinfo-json').value;
  try {
    const parsed = JSON.parse(raw);
    if (!parsed.secciones || !Array.isArray(parsed.secciones)) {
      return { ok: false, msg: 'Falta la raíz {"secciones": [...]}' };
    }
    return { ok: true, msg: `JSON válido ✔ (${parsed.secciones.length} secciones)` };
  } catch (e) {
    return { ok: false, msg: `JSON inválido: ${e.message}` };
  }
}

function initServerInfo() {
  document.getElementById('serverinfo-validate').addEventListener('click', () => {
    const v = validarServerInfoJson();
    feedback('serverinfo-feedback', v.msg, !v.ok);
  });

  document.getElementById('serverinfo-save').addEventListener('click', async () => {
    const v = validarServerInfoJson();
    if (!v.ok) { feedback('serverinfo-feedback', v.msg + ' — no se envió nada.', true); return; }

    const { ok, data } = await adminFetch('server-info.php', {
      method: 'POST',
      body: JSON.stringify({ config_value: document.getElementById('serverinfo-json').value }),
    });
    feedback('serverinfo-feedback', ok ? `Guardado ✔ (${data.secciones} secciones)` : (data?.error ?? 'Error'), !ok);
  });
}

// ── Descargas ─────────────────────────────────────────────────

async function loadDownloadsAdmin() {
  const { ok, data } = await adminFetch('downloads.php');
  const el = document.getElementById('downloads-admin-list');
  if (!ok || !data?.items) { el.innerHTML = '<p class="state-message">Error al cargar.</p>'; return; }

  el.innerHTML = data.items.map(d => `
    <div class="cp-row">
      <div class="cp-row__info">
        <strong>${esc(d.title)}</strong>
        <span class="cp-dim">${esc(d.item_key)} · v${esc(d.version ?? '')} · orden ${esc(String(d.sort_order))} · ${Number(d.is_active) ? 'activa' : '<em>inactiva</em>'}</span>
      </div>
      <div class="cp-row__actions">
        <button class="btn btn-secondary btn-sm" data-dl-edit="${d.id}">Editar</button>
        <button class="btn btn-secondary btn-sm" data-dl-act="${d.id}" data-act="${Number(d.is_active) ? 0 : 1}">
          ${Number(d.is_active) ? 'Desactivar' : 'Activar'}
        </button>
      </div>
    </div>`).join('') || '<p class="state-message">Sin descargas.</p>';

  el.querySelectorAll('[data-dl-edit]').forEach(b => b.addEventListener('click', () => {
    const d = data.items.find(x => String(x.id) === b.dataset.dlEdit);
    document.getElementById('dl-id').value      = d.id;
    document.getElementById('dl-key').value     = d.item_key;
    document.getElementById('dl-key').disabled  = true; // la key no se edita
    document.getElementById('dl-title').value   = d.title;
    document.getElementById('dl-desc').value    = d.description ?? '';
    document.getElementById('dl-version').value = d.version ?? '';
    document.getElementById('dl-size').value    = d.size ?? '';
    document.getElementById('dl-url').value     = d.url;
    document.getElementById('dl-order').value   = d.sort_order;
    document.getElementById('dl-form-title').textContent = `Editando descarga #${d.id}`;
    document.getElementById('dl-cancel').hidden = false;
  }));

  el.querySelectorAll('[data-dl-act]').forEach(b => b.addEventListener('click', async () => {
    const { ok: ok2, data: d2 } = await adminFetch('downloads.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'set_active', id: Number(b.dataset.dlAct), is_active: Number(b.dataset.act) }),
    });
    feedback('dl-feedback', ok2 ? 'Actualizado ✔' : (d2?.error ?? 'Error'), !ok2);
    if (ok2) loadDownloadsAdmin();
  }));
}

function resetDlForm() {
  ['dl-id', 'dl-key', 'dl-title', 'dl-desc', 'dl-version', 'dl-size', 'dl-url'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('dl-order').value = 1;
  document.getElementById('dl-key').disabled = false;
  document.getElementById('dl-form-title').textContent = 'Nueva descarga';
  document.getElementById('dl-cancel').hidden = true;
}

function initDownloads() {
  document.getElementById('dl-cancel').addEventListener('click', resetDlForm);
  document.getElementById('dl-save').addEventListener('click', async () => {
    const id   = document.getElementById('dl-id').value;
    const body = {
      action:      id ? 'update' : 'create',
      title:       document.getElementById('dl-title').value.trim(),
      description: document.getElementById('dl-desc').value.trim(),
      version:     document.getElementById('dl-version').value.trim(),
      size:        document.getElementById('dl-size').value.trim(),
      url:         document.getElementById('dl-url').value.trim(),
      sort_order:  Number(document.getElementById('dl-order').value) || 1,
    };
    if (id) body.id = Number(id); else body.item_key = document.getElementById('dl-key').value.trim();

    const { ok, data } = await adminFetch('downloads.php', { method: 'POST', body: JSON.stringify(body) });
    feedback('dl-feedback', ok ? 'Guardado ✔' : (data?.error ?? 'Error'), !ok);
    if (ok) { resetDlForm(); loadDownloadsAdmin(); }
  });
}

// ── Reclamos (tickets con hilo) ───────────────────────────────

let reclamoAdmActual = 0;       // id del ticket abierto en el detalle
let reclamoAdmEstado = 'nuevo'; // estado del ticket abierto

// '2026-07-18 09:51:44.940' → '18/07 09:51 hs'
function fmtFechaCp(sql) {
  const m = String(sql ?? '').match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
  return m ? `${m[3]}/${m[2]} ${m[4]}:${m[5]} hs` : '';
}

async function loadReclamosAdmin() {
  const { ok, data } = await adminFetch('reclamos.php');
  const el = document.getElementById('reclamos-admin-list');
  if (!ok || !data?.items) { el.innerHTML = '<p class="state-message">Error al cargar.</p>'; return; }

  el.innerHTML = data.items.map(r => {
    const resuelto = r.estado === 'resuelto';
    return `
    <div class="cp-row" style="align-items:flex-start">
      <div class="cp-row__info">
        <strong>#${esc(r.id)} — ${esc(r.nick)}</strong>
        <span class="cp-chip ${resuelto ? 'cp-chip--on' : 'cp-chip--off'}">${resuelto ? '✔ resuelto' : '● abierto'}</span>
        <span class="cp-dim">${fmtFechaCp(r.ultimo_movimiento ?? r.created_at)} · ${esc(String(r.total_mensajes))} msj</span>
        <p style="margin:0.4rem 0">${esc(r.extracto)}${(r.extracto ?? '').length >= 120 ? '…' : ''}</p>
      </div>
      <div class="cp-row__actions">
        <button class="btn btn-secondary btn-sm" data-reclamo-ver="${r.id}">Ver hilo</button>
      </div>
    </div>`;
  }).join('') || '<p class="state-message">Sin reclamos.</p>';

  el.querySelectorAll('[data-reclamo-ver]').forEach(b =>
    b.addEventListener('click', () => abrirReclamoAdm(Number(b.dataset.reclamoVer)))
  );
}

async function abrirReclamoAdm(id) {
  reclamoAdmActual = id;
  document.getElementById('reclamos-admin-list').hidden    = true;
  document.getElementById('reclamos-admin-detalle').hidden = false;
  document.getElementById('reclamo-adm-titulo').textContent = `Reclamo #${id}`;
  document.getElementById('reclamo-adm-hilo').innerHTML = '<p class="state-message">Cargando…</p>';
  document.getElementById('reclamo-resp-text').value = '';

  const { ok, data } = await adminFetch(`reclamos.php?id=${id}`);
  if (!ok || !data?.reclamo) {
    document.getElementById('reclamo-adm-hilo').innerHTML = '<p class="state-message">Error al cargar el hilo.</p>';
    return;
  }

  reclamoAdmEstado = data.reclamo.estado;
  const resuelto = reclamoAdmEstado === 'resuelto';

  document.getElementById('reclamo-adm-titulo').textContent = `Reclamo #${id} — ${data.reclamo.nick}`;
  document.getElementById('reclamo-adm-estado').innerHTML =
    `<span class="cp-chip ${resuelto ? 'cp-chip--on' : 'cp-chip--off'}">${resuelto ? '✔ resuelto' : '● abierto'}</span>`;
  document.getElementById('reclamo-adm-toggle-estado').textContent =
    resuelto ? 'Reabrir sin comentar' : 'Cerrar sin comentar';

  document.getElementById('reclamo-adm-hilo').innerHTML = (data.mensajes ?? []).map(m => {
    const esAdmin = m.autor_tipo === 'admin';
    const imgs = (m.imagenes ?? []).map(u =>
      `<a href="${esc(u)}" target="_blank" rel="noopener"><img src="${esc(u)}" alt=""></a>`
    ).join('');
    return `
    <div class="reclamo-msg ${esAdmin ? 'reclamo-msg--admin' : 'reclamo-msg--jugador'}">
      <div class="reclamo-msg__head">
        <strong>${esAdmin ? `🛡 ${esc(m.autor_nick)}` : esc(m.autor_nick)}</strong>
        <span>${fmtFechaCp(m.created_at)}</span>
      </div>
      <p>${esc(m.mensaje)}</p>
      ${imgs ? `<div class="reclamo-msg__imgs">${imgs}</div>` : ''}
    </div>`;
  }).join('') || '<p class="state-message">Sin mensajes.</p>';
}

function volverListaReclamosAdm() {
  document.getElementById('reclamos-admin-detalle').hidden = true;
  document.getElementById('reclamos-admin-list').hidden    = false;
  loadReclamosAdmin();
}

function initReclamos() {
  document.getElementById('reclamo-adm-volver').addEventListener('click', volverListaReclamosAdm);

  document.getElementById('reclamo-resp-send').addEventListener('click', async () => {
    const mensaje  = document.getElementById('reclamo-resp-text').value.trim();
    const resolver = document.getElementById('reclamo-resp-resolver').checked ? 1 : 0;

    if (!reclamoAdmActual || !mensaje) {
      feedback('reclamos-feedback', 'Escribí una respuesta.', true);
      return;
    }

    const { ok, data } = await adminFetch('reclamos.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'responder', id: reclamoAdmActual, mensaje, resolver }),
    });
    feedback('reclamos-feedback', ok ? 'Respuesta enviada ✔' : (data?.error ?? 'Error'), !ok);
    if (ok) abrirReclamoAdm(reclamoAdmActual); // recargar el hilo con la respuesta
  });

  document.getElementById('reclamo-adm-toggle-estado').addEventListener('click', async () => {
    const nuevoEstado = reclamoAdmEstado === 'resuelto' ? 'nuevo' : 'resuelto';
    const { ok, data } = await adminFetch('reclamos.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'set_estado', id: reclamoAdmActual, estado: nuevoEstado }),
    });
    feedback('reclamos-feedback', ok ? 'Estado actualizado ✔' : (data?.error ?? 'Error'), !ok);
    if (ok) abrirReclamoAdm(reclamoAdmActual);
  });
}

// ── WCoins ────────────────────────────────────────────────────

let wcoinVerifiedAccount = ''; // cuenta ya validada por 'lookup' — habilita el botón Acreditar

async function loadWcoinHistory() {
  const { ok, data } = await adminFetch('wcoin.php');
  const el = document.getElementById('wcoin-history');
  if (!ok || !data?.items) { el.innerHTML = '<p class="state-message">Error al cargar.</p>'; return; }

  el.innerHTML = data.items.map(c => `
    <div class="cp-row">
      <div class="cp-row__info">
        <strong>${esc(c.target_account)}</strong> +${esc(String(c.amount))} WCoin
        <span class="cp-dim">por ${esc(c.admin_id)} · ${fmtFechaCp(c.created_at)}${c.reason ? ' · ' + esc(c.reason) : ''}</span>
      </div>
    </div>`).join('') || '<p class="state-message">Sin créditos manuales todavía.</p>';
}

function resetWcoinCreditGate() {
  wcoinVerifiedAccount = '';
  document.getElementById('wcoin-credit').disabled = true;
}

function initWcoin() {
  const accountInput = document.getElementById('wcoin-account');

  accountInput.addEventListener('input', () => {
    document.getElementById('wcoin-lookup-result').hidden = true;
    resetWcoinCreditGate();
  });

  document.getElementById('wcoin-lookup').addEventListener('click', async () => {
    const account = accountInput.value.trim();
    resetWcoinCreditGate();
    if (!account) { feedback('wcoin-feedback', 'Ingresá una cuenta.', true); return; }

    const { ok, data } = await adminFetch('wcoin.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'lookup', account }),
    });

    const box = document.getElementById('wcoin-lookup-result');
    box.hidden = false;
    if (!ok) {
      box.innerHTML = `<span class="cp-chip cp-chip--on">✕ ${esc(data?.error ?? 'Error')}</span>`;
      return;
    }
    const b = data.balance;
    box.innerHTML = `<span class="cp-chip cp-chip--off">✔ Cuenta encontrada</span>
      <span class="cp-status-detail">Saldo actual: ${esc(String(b?.WCoinC ?? 0))} WCoin</span>`;
    wcoinVerifiedAccount = account;
    document.getElementById('wcoin-credit').disabled = false;
  });

  document.getElementById('wcoin-credit').addEventListener('click', async () => {
    const account = accountInput.value.trim();
    const amount  = Number(document.getElementById('wcoin-amount').value);
    const reason  = document.getElementById('wcoin-reason').value.trim();

    if (account !== wcoinVerifiedAccount) {
      feedback('wcoin-feedback', 'Verificá la cuenta de nuevo antes de acreditar.', true);
      return;
    }
    if (!Number.isInteger(amount) || amount <= 0) {
      feedback('wcoin-feedback', 'El monto tiene que ser un entero positivo.', true);
      return;
    }
    if (!confirm(`¿Acreditar ${amount} WCoin a la cuenta "${account}"? Esta acción no se puede deshacer.`)) return;

    const { ok, data } = await adminFetch('wcoin.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'credit', account, amount, reason }),
    });
    const okMsg = data?.audit_log === false
      ? `Acreditado ✔ — nuevo saldo: ${data.balance?.WCoinC ?? '?'} WCoin (⚠ no se pudo guardar en la auditoría del panel)`
      : `Acreditado ✔ — nuevo saldo: ${data?.balance?.WCoinC ?? '?'} WCoin`;
    feedback('wcoin-feedback', ok ? okMsg : (data?.error ?? 'Error'), !ok);
    if (ok) {
      document.getElementById('wcoin-amount').value = '';
      document.getElementById('wcoin-reason').value = '';
      resetWcoinCreditGate();
      document.getElementById('wcoin-lookup-result').hidden = true;
      loadWcoinHistory();
    }
  });
}

// ── Init + guard ──────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', async () => {
  if (!isAuthenticated()) {
    window.location.href = `${BASE}/login/?redirect=${encodeURIComponent('/controlpanel/')}`;
    return;
  }

  // El primer GET valida el permiso de admin server-side (403 si no está en dbo.admins)
  const { ok, status } = await adminFetch('site-status.php');
  if (!ok) {
    document.getElementById('cp-guard-msg').textContent = status === 403
      ? 'No tenés permisos de administrador para acceder a esta sección.'
      : 'Error verificando permisos. Probá de nuevo más tarde.';
    return;
  }

  document.getElementById('cp-guard').hidden = true;
  document.getElementById('cp-panel').hidden = false;

  initTabs();
  initStatus();
  initNews();
  initServerInfo();
  initDownloads();
  initReclamos();
  initWcoin();

  loadStatus();
  loadNewsAdmin();
  loadServerInfoAdmin();
  loadDownloadsAdmin();
  loadReclamosAdmin();
  loadWcoinHistory();
});
