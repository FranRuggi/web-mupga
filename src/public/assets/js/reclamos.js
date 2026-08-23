/* ============================================================
   MuPGA — reclamos.js
   Página /reclamos: sistema de tickets con hilo de conversación.

   Tres vistas dentro de la misma página:
     - form    → crear un reclamo nuevo (mensaje + hasta 5 imágenes)
     - lista   → "Mis reclamos": historial de tickets del jugador
     - detalle → hilo completo de un ticket + caja para comentar
                 (comentar un ticket resuelto lo reabre)

   Flujo de envío (el id del ticket nombra la carpeta en R2; las imágenes
   se atan al MENSAJE, así que primero se crea la fila y después se sube):
     Nuevo:      create.php { mensaje } → { id, mensajeId }
     Comentario: reply.php { reclamoId, mensaje } → { mensajeId }
     Por imagen: upload_url.php { mensajeId, contentType } → PUT a R2
     Final:      finalize.php { mensajeId, imagenes } → notifica Discord

   Deep link: /reclamos/?ver=123 abre directo el hilo del ticket 123
   (lo usa el banner site-wide de app.js).
   Deep link: /reclamos/?mensaje=... pre-carga el textarea del form nuevo
   (lo usa /donate/transferencia/ para el reclamo de comprobante).

   Depende de: app.js (BASE, API, esc), auth.js (isAuthenticated, authFetch)
   ============================================================ */

const RECLAMOS_MAX_IMAGENES = 5;
const RECLAMOS_MAX_SIZE     = 5 * 1024 * 1024; // 5 MB
const RECLAMOS_TIPOS_OK     = ['image/jpeg', 'image/png', 'image/webp'];

let _archivos      = []; // File[] — form de reclamo nuevo
let _replyArchivos = []; // File[] — caja de comentario del detalle
let _ticketActual  = 0;  // id del ticket abierto en la vista detalle

let $alert, $form, $mensaje, $errMensaje,
    $dropzone, $dropzoneInput, $previews, $btnSubmit,
    $spinner, $btnText, $progress,
    $tabNuevo, $tabMios, $miosBadge,
    $lista, $listaItems,
    $detalle, $detalleTitulo, $detalleEstado, $hilo,
    $replyForm, $replyMensaje, $replyPreviews, $btnReply,
    $replySpinner, $replyBtnText, $replyProgress, $replyInput;

// Evita que un reload/cierre accidental a mitad del envío deje un reclamo
// a medio crear en la base (fila sin imágenes, sin poder reintentar).
function beforeUnloadGuard(e) {
  e.preventDefault();
  e.returnValue = '';
}

document.addEventListener('DOMContentLoaded', () => {
  if (!isAuthenticated()) {
    window.location.replace(
      `${BASE}/login/?redirect=${encodeURIComponent(window.location.pathname)}`
    );
    return;
  }

  $alert         = document.getElementById('reclamo-alert');
  $form          = document.getElementById('reclamo-form');
  $mensaje       = document.getElementById('reclamo-mensaje');
  $errMensaje    = document.getElementById('err-mensaje');
  $dropzone      = document.getElementById('reclamo-dropzone');
  $dropzoneInput = document.getElementById('reclamo-image-file');
  $previews      = document.getElementById('reclamo-previews');
  $btnSubmit     = document.getElementById('btn-reclamo-submit');
  $spinner       = document.getElementById('reclamo-spinner');
  $btnText       = document.getElementById('reclamo-btn-text');
  $progress      = document.getElementById('reclamo-progress');
  $tabNuevo      = document.getElementById('tab-nuevo');
  $tabMios       = document.getElementById('tab-mios');
  $miosBadge     = document.getElementById('mios-badge');
  $lista         = document.getElementById('reclamo-lista');
  $listaItems    = document.getElementById('reclamo-lista-items');
  $detalle       = document.getElementById('reclamo-detalle');
  $detalleTitulo = document.getElementById('detalle-titulo');
  $detalleEstado = document.getElementById('detalle-estado');
  $hilo          = document.getElementById('reclamo-hilo');
  $replyForm     = document.getElementById('reply-form');
  $replyMensaje  = document.getElementById('reply-mensaje');
  $replyPreviews = document.getElementById('reply-previews');
  $btnReply      = document.getElementById('btn-reply-submit');
  $replySpinner  = document.getElementById('reply-spinner');
  $replyBtnText  = document.getElementById('reply-btn-text');
  $replyProgress = document.getElementById('reply-progress');
  $replyInput    = document.getElementById('reply-image-file');

  initDropzone();
  initTabs();
  initReply();
  $form.addEventListener('submit', onSubmit);
  document.getElementById('btn-volver').addEventListener('click', () => showView('lista'));

  refreshMiosBadge();

  const params = new URLSearchParams(window.location.search);

  // Deep link del banner: /reclamos/?ver=123 abre el hilo directo
  const ver = Number(params.get('ver'));
  if (ver > 0) {
    abrirDetalle(ver);
  }

  // Deep link con mensaje pre-cargado: /reclamos/?mensaje=... (ej. desde
  // /donate/transferencia/ para no hacer escribir de cero el mismo texto)
  const mensajePrefill = params.get('mensaje');
  if (mensajePrefill) {
    $mensaje.value = mensajePrefill.slice(0, 2000);
  }
});

// ── Vistas y tabs ────────────────────────────────────────────
function showView(view) {
  clearAlert();
  $form.hidden    = view !== 'form';
  $lista.hidden   = view !== 'lista';
  $detalle.hidden = view !== 'detalle';
  $tabNuevo.classList.toggle('active', view === 'form');
  $tabMios.classList.toggle('active', view !== 'form');
  if (view === 'lista') loadMios();
}

function initTabs() {
  $tabNuevo.addEventListener('click', () => showView('form'));
  $tabMios.addEventListener('click', () => showView('lista'));
}

// Badge con la cantidad de tickets con novedades sin leer
async function refreshMiosBadge() {
  const res = await authFetch('reclamos/pending_notice.php');
  if (!res || !res.ok) return;
  const { pendientes } = await res.json().catch(() => ({ pendientes: [] }));
  const n = pendientes?.length ?? 0;
  $miosBadge.textContent = n;
  $miosBadge.hidden = n === 0;
}

// ── Mis reclamos (listado) ───────────────────────────────────
async function loadMios() {
  $listaItems.innerHTML = '<p class="state-message">Cargando…</p>';

  const res = await authFetch('reclamos/mios.php');
  if (!res || !res.ok) {
    $listaItems.innerHTML = '<p class="state-message">No se pudieron cargar tus reclamos.</p>';
    return;
  }

  const { items } = await res.json().catch(() => ({ items: [] }));

  if (!items?.length) {
    $listaItems.innerHTML = '<p class="state-message">Todavía no mandaste ningún reclamo.</p>';
    return;
  }

  $listaItems.innerHTML = items.map(t => {
    const resuelto = t.estado === 'resuelto';
    const noLeido  = Number(t.no_leido) === 1;
    return `
    <button type="button" class="reclamo-card${noLeido ? ' reclamo-card--nuevo' : ''}" data-ver="${t.id}">
      <div class="reclamo-card__top">
        <strong>#${esc(t.id)}</strong>
        <span class="reclamo-estado ${resuelto ? 'reclamo-estado--resuelto' : 'reclamo-estado--abierto'}">
          ${resuelto ? '✔ Resuelto' : '● Abierto'}
        </span>
        ${noLeido ? '<span class="reclamo-nuevo-badge">Respuesta nueva</span>' : ''}
        <span class="reclamo-card__fecha">${fmtFecha(t.ultimo_movimiento ?? t.created_at)}</span>
      </div>
      <p class="reclamo-card__extracto">${esc(t.extracto)}${(t.extracto ?? '').length >= 120 ? '…' : ''}</p>
      <span class="reclamo-card__meta">${esc(String(t.total_mensajes))} mensaje${Number(t.total_mensajes) === 1 ? '' : 's'}</span>
    </button>`;
  }).join('');

  $listaItems.querySelectorAll('[data-ver]').forEach(b =>
    b.addEventListener('click', () => abrirDetalle(Number(b.dataset.ver)))
  );
}

// ── Detalle (hilo) ───────────────────────────────────────────
async function abrirDetalle(id) {
  _ticketActual = id;
  showView('detalle');
  $detalleTitulo.textContent = `Reclamo #${id}`;
  $detalleEstado.innerHTML = '';
  $hilo.innerHTML = '<p class="state-message">Cargando…</p>';
  $replyMensaje.value = '';
  _replyArchivos = [];
  renderReplyPreviews();

  const res = await authFetch(`reclamos/detalle.php?id=${id}`);
  if (!res || !res.ok) {
    $hilo.innerHTML = '<p class="state-message">No se pudo cargar el reclamo.</p>';
    return;
  }

  const { reclamo, mensajes } = await res.json().catch(() => ({}));
  if (!reclamo) {
    $hilo.innerHTML = '<p class="state-message">Reclamo no encontrado.</p>';
    return;
  }

  renderDetalle(reclamo, mensajes ?? []);
  // Abrir el detalle marca lo no leído como leído en el server → refrescar badge
  refreshMiosBadge();
}

function renderDetalle(reclamo, mensajes) {
  const resuelto = reclamo.estado === 'resuelto';
  $detalleEstado.innerHTML =
    `<span class="reclamo-estado ${resuelto ? 'reclamo-estado--resuelto' : 'reclamo-estado--abierto'}">
       ${resuelto ? '✔ Resuelto' : '● Abierto'}
     </span>`;

  $hilo.innerHTML = mensajes.map(m => {
    const esAdmin = m.autor_tipo === 'admin';
    const imgs = (m.imagenes ?? []).map(u =>
      `<a href="${esc(u)}" target="_blank" rel="noopener"><img src="${esc(u)}" alt=""></a>`
    ).join('');
    return `
    <div class="reclamo-msg ${esAdmin ? 'reclamo-msg--admin' : 'reclamo-msg--jugador'}">
      <div class="reclamo-msg__head">
        <strong>${esAdmin ? '🛡 Staff' : esc(m.autor_nick)}</strong>
        <span>${fmtFecha(m.created_at)}</span>
      </div>
      <p>${esc(m.mensaje)}</p>
      ${imgs ? `<div class="reclamo-msg__imgs">${imgs}</div>` : ''}
    </div>`;
  }).join('') || '<p class="state-message">Sin mensajes.</p>';
}

// ── Caja de comentario (reply) ───────────────────────────────
function initReply() {
  document.getElementById('reply-attach').addEventListener('click', () => $replyInput.click());
  $replyInput.addEventListener('change', () => {
    addFiles(Array.from($replyInput.files), _replyArchivos, renderReplyPreviews);
    $replyInput.value = '';
  });
  $replyForm.addEventListener('submit', onReplySubmit);
}

function renderReplyPreviews() {
  renderPreviewsInto($replyPreviews, _replyArchivos, renderReplyPreviews);
}

async function onReplySubmit(e) {
  e.preventDefault();
  clearAlert();

  const mensaje = $replyMensaje.value.trim();
  if (!mensaje) {
    showAlert('Escribí un comentario antes de enviar.', true);
    return;
  }
  if (mensaje.length > 2000) {
    showAlert('El comentario supera los 2000 caracteres.', true);
    return;
  }

  setReplyLoading(true);
  setReplyProgress('Enviando comentario…');

  try {
    const replyRes = await authFetch('reclamos/reply.php', {
      method: 'POST',
      body: JSON.stringify({ reclamoId: _ticketActual, mensaje }),
    });

    if (!replyRes) { setReplyLoading(false); return; }

    if (!replyRes.ok) {
      const data = await replyRes.json().catch(() => ({}));
      showAlert(data.error ?? 'No se pudo enviar el comentario.', true);
      setReplyLoading(false);
      return;
    }

    const { mensajeId } = await replyRes.json();

    const imagenes = [];
    for (let i = 0; i < _replyArchivos.length; i++) {
      setReplyProgress(`Subiendo imagen ${i + 1} de ${_replyArchivos.length}…`);
      imagenes.push(await uploadImagen(mensajeId, _replyArchivos[i]));
    }

    setReplyProgress('Guardando…');
    await authFetch('reclamos/finalize.php', {
      method: 'POST',
      body: JSON.stringify({ mensajeId, imagenes }),
    });

    // Recargar el hilo con el comentario nuevo ya incluido
    await abrirDetalle(_ticketActual);
  } catch (err) {
    showAlert('Error al enviar el comentario: ' + esc(err.message), true);
  } finally {
    setReplyLoading(false);
  }
}

// ── Dropzone (form de reclamo nuevo) ─────────────────────────
function initDropzone() {
  $dropzone.addEventListener('click', () => $dropzoneInput.click());
  $dropzoneInput.addEventListener('change', () => {
    addFiles(Array.from($dropzoneInput.files), _archivos, renderPreviews);
    $dropzoneInput.value = '';
  });

  ['dragover', 'dragenter'].forEach(ev => $dropzone.addEventListener(ev, e => {
    e.preventDefault(); $dropzone.classList.add('cp-dropzone--over');
  }));
  ['dragleave', 'drop'].forEach(ev => $dropzone.addEventListener(ev, e => {
    e.preventDefault(); $dropzone.classList.remove('cp-dropzone--over');
  }));
  $dropzone.addEventListener('drop', e => {
    addFiles(Array.from(e.dataTransfer?.files ?? []), _archivos, renderPreviews);
  });
}

function addFiles(files, target, rerender) {
  clearAlert();

  for (const file of files) {
    if (target.length >= RECLAMOS_MAX_IMAGENES) {
      showAlert(`Máximo ${RECLAMOS_MAX_IMAGENES} imágenes.`, true);
      break;
    }
    if (!RECLAMOS_TIPOS_OK.includes(file.type)) {
      showAlert(`"${file.name}" no es JPG, PNG o WebP.`, true);
      continue;
    }
    if (file.size > RECLAMOS_MAX_SIZE) {
      showAlert(`"${file.name}" supera los 5 MB.`, true);
      continue;
    }
    target.push(file);
  }

  rerender();
}

function renderPreviews() {
  renderPreviewsInto($previews, _archivos, renderPreviews);
}

function renderPreviewsInto(container, target, rerender) {
  container.innerHTML = target.map((file, i) => `
    <div class="reclamo-preview-item">
      <img src="${URL.createObjectURL(file)}" alt="${esc(file.name)}">
      <button type="button" class="reclamo-preview-remove" data-idx="${i}" aria-label="Quitar imagen">✕</button>
    </div>
  `).join('');

  container.querySelectorAll('.reclamo-preview-remove').forEach(btn => {
    btn.addEventListener('click', () => {
      target.splice(Number(btn.dataset.idx), 1);
      rerender();
    });
  });
}

// ── Envío de reclamo nuevo ───────────────────────────────────
async function onSubmit(e) {
  e.preventDefault();
  clearAlert();
  $errMensaje.classList.remove('visible');
  $mensaje.classList.remove('error');

  const mensaje = $mensaje.value.trim();
  if (!mensaje) {
    $mensaje.classList.add('error');
    $errMensaje.textContent = 'Contanos qué pasó.';
    $errMensaje.classList.add('visible');
    return;
  }
  if (mensaje.length > 2000) {
    $mensaje.classList.add('error');
    $errMensaje.textContent = 'El mensaje supera los 2000 caracteres.';
    $errMensaje.classList.add('visible');
    return;
  }

  setLoading(true);
  setProgress('Creando tu reclamo…');

  try {
    // Paso 1: crear el ticket + primer mensaje (rate limit se valida acá)
    const createRes = await authFetch('reclamos/create.php', {
      method: 'POST',
      body: JSON.stringify({ mensaje }),
    });

    if (!createRes) { setLoading(false); return; }

    if (!createRes.ok) {
      const data = await createRes.json().catch(() => ({}));
      showAlert(data.error ?? 'No se pudo crear el reclamo.', true);
      setLoading(false);
      return;
    }

    const { id, mensajeId } = await createRes.json();

    // Paso 2: subir cada imagen a la carpeta reclamos/{id}/ del ticket
    const imagenes = [];
    for (let i = 0; i < _archivos.length; i++) {
      setProgress(`Subiendo imagen ${i + 1} de ${_archivos.length}…`);
      imagenes.push(await uploadImagen(mensajeId, _archivos[i]));
    }

    // Paso 3: sellar el mensaje y notificar Discord
    setProgress('Guardando y avisando al staff…');
    const res = await authFetch('reclamos/finalize.php', {
      method: 'POST',
      body: JSON.stringify({ mensajeId, imagenes }),
    });

    if (!res) { setLoading(false); return; }

    if (res.ok) {
      showAlert(`¡Reclamo #${id} enviado! Podés seguirlo desde "Mis reclamos".`, false);
      $form.reset();
      _archivos = [];
      renderPreviews();
    } else {
      const data = await res.json().catch(() => ({}));
      showAlert(data.error ?? 'No se pudo terminar de enviar el reclamo.', true);
    }
  } catch (err) {
    showAlert('Error al subir las imágenes: ' + esc(err.message), true);
  } finally {
    setLoading(false);
  }
}

// Pide la presigned URL para el mensaje y sube la imagen directo a R2.
async function uploadImagen(mensajeId, file) {
  const res = await authFetch('reclamos/upload_url.php', {
    method: 'POST',
    body: JSON.stringify({ mensajeId, contentType: file.type }),
  });

  if (!res || !res.ok) {
    const data = await res?.json().catch(() => ({})) ?? {};
    throw new Error(data.error ?? 'No se pudo generar la URL de subida.');
  }

  const { uploadUrl, publicUrl } = await res.json();

  const putRes = await fetch(uploadUrl, {
    method: 'PUT',
    headers: { 'Content-Type': file.type },
    body: file,
  });

  if (!putRes.ok) {
    throw new Error(`No se pudo subir "${file.name}" (HTTP ${putRes.status}).`);
  }

  return publicUrl;
}

// ── UI helpers ───────────────────────────────────────────────
function setLoading(loading) {
  $btnSubmit.disabled = loading;
  $btnSubmit.dataset.loading = loading ? 'true' : 'false';
  $spinner.hidden = !loading;
  $btnText.textContent = loading ? 'Enviando…' : 'Enviar reclamo';

  // Bloqueados mientras se envía: nada de tocar el mensaje o las imágenes
  // a mitad de camino (ya se está subiendo lo que había al momento del submit).
  $mensaje.disabled = loading;
  $dropzone.style.pointerEvents = loading ? 'none' : '';
  $dropzone.style.opacity = loading ? '0.5' : '';

  if (loading) {
    window.addEventListener('beforeunload', beforeUnloadGuard);
  } else {
    window.removeEventListener('beforeunload', beforeUnloadGuard);
    $progress.hidden = true;
  }
}

function setReplyLoading(loading) {
  $btnReply.disabled = loading;
  $btnReply.dataset.loading = loading ? 'true' : 'false';
  $replySpinner.hidden = !loading;
  $replyBtnText.textContent = loading ? 'Enviando…' : 'Comentar';
  $replyMensaje.disabled = loading;

  if (loading) {
    window.addEventListener('beforeunload', beforeUnloadGuard);
  } else {
    window.removeEventListener('beforeunload', beforeUnloadGuard);
    $replyProgress.hidden = true;
  }
}

function setProgress(text) {
  $progress.textContent = text;
  $progress.hidden = false;
}

function setReplyProgress(text) {
  $replyProgress.textContent = text;
  $replyProgress.hidden = false;
}

// '2026-07-18 09:51:44.9400000' (UTC, created_at = GETUTCDATE()) → '18/07 09:51 hs'
// en la hora local del navegador de quien lo mira — el sitio lo ven jugadores
// de distintos países, nunca hay que asumir la hora del servidor.
function fmtFecha(sql) {
  const s = String(sql ?? '');
  if (!s) return '';
  const d = new Date(s.slice(0, 19).replace(' ', 'T') + 'Z');
  if (isNaN(d.getTime())) return '';
  const pad = n => String(n).padStart(2, '0');
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)} ${pad(d.getHours())}:${pad(d.getMinutes())} hs`;
}

function showAlert(msg, isError) {
  $alert.textContent = msg;
  $alert.className = 'alert visible ' + (isError ? 'alert--error' : 'alert--success');
}

function clearAlert() {
  $alert.className = 'alert';
  $alert.textContent = '';
}
