/* ============================================================
   MuPGA — foro.js
   Maneja las 3 páginas del foro: /foro/ (categorías),
   /foro/categoria/ (hilos) y /foro/hilo/ (detalle + responder).
   Depende de app.js (BASE, API, esc, renderRichText, apiFetch) y
   auth.js (isAuthenticated, authFetch).

   Nota de privacidad: la API no expone author_account (cuenta de
   login) — la propiedad del contenido viaja como is_mine, calculada
   server-side contra la sesión.
   ============================================================ */

const FORO_EDIT_WINDOW_MIN = 30; // espejo de ForumValidation::EDIT_WINDOW_MINUTES (el server manda)
const FORO_REPORT_REASONS = [
  ['spam',        'Spam / publicidad'],
  ['insultos',    'Insultos o agresiones'],
  ['estafa',      'Estafa o engaño'],
  ['inapropiado', 'Contenido inapropiado'],
  ['otro',        'Otro'],
];

// '2026-07-18 09:51:44.9400000' → '18/07 09:51 hs'
function foroFmtFecha(sql) {
  const m = String(sql ?? '').match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
  return m ? `${m[3]}/${m[2]} ${m[4]}:${m[5]} hs` : '';
}

// created_at viene en UTC → milisegundos transcurridos hasta ahora
function foroMsDesde(sql) {
  const m = String(sql ?? '').match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2}):(\d{2})/);
  if (!m) return Infinity;
  return Date.now() - Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]);
}

function foroIsAdmin() {
  return sessionStorage.getItem('mupga_admin') === '1';
}

function foroReactionBtn(targetType, targetId, reactions, isMine) {
  const r = reactions ?? { count: 0, reacted: false };
  if (isMine) {
    // F-05.02: sin autoagradecimiento — se muestra el contador, no el botón
    return r.count > 0
      ? `<span class="forum-reaction-btn forum-reaction-btn--static" title="No podés agradecerte a vos mismo">🙏 ${r.count}</span>`
      : '';
  }
  return `
    <button type="button" class="forum-reaction-btn${r.reacted ? ' forum-reaction-btn--active' : ''}"
            data-target-type="${targetType}" data-target-id="${targetId}">
      🙏 Agradecer${r.count > 0 ? ` (${r.count})` : ''}
    </button>`;
}

async function foroToggleReaction(btn) {
  if (!isAuthenticated()) {
    window.location.href = `${BASE}/login/?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`;
    return;
  }
  btn.disabled = true;
  const res = await authFetch('forum/react.php', {
    method: 'POST',
    body: JSON.stringify({ target_type: btn.dataset.targetType, target_id: Number(btn.dataset.targetId) }),
  });
  btn.disabled = false;
  if (!res || !res.ok) return;

  const data = await res.json();
  btn.classList.toggle('forum-reaction-btn--active', data.reacted);
  btn.textContent = `🙏 Agradecer${data.count > 0 ? ` (${data.count})` : ''}`;
}

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('foro-categorias-container')) initCategorias();
  if (document.getElementById('foro-hilos-container'))       initHilos();
  if (document.getElementById('foro-hilo-container'))        initHiloDetalle();
});

// ================================================================
// /foro/ — lista de categorías
// ================================================================
async function initCategorias() {
  const container = document.getElementById('foro-categorias-container');
  const data = await apiFetch('forum/categories.php');

  if (!data || !data.categories) {
    container.innerHTML = '<p class="state-message">Error al cargar el foro.</p>';
    return;
  }
  if (!data.categories.length) {
    container.innerHTML = '<p class="state-message">Todavía no hay categorías creadas.</p>';
    return;
  }

  container.innerHTML = `<div class="card-grid card-grid--3">${data.categories.map(c => `
    <a class="forum-category-card" href="${BASE}/foro/categoria/?id=${c.id}">
      <p class="forum-category-card__name">${esc(c.name)}</p>
      ${c.description ? `<p class="forum-category-card__desc">${esc(c.description)}</p>` : ''}
      <p class="forum-category-card__meta">
        ${c.thread_count} hilo${c.thread_count === 1 ? '' : 's'}
        ${c.last_activity_at ? ` · última actividad ${foroFmtFecha(c.last_activity_at)}` : ''}
      </p>
      <span>
        ${c.admin_only_post ? '<span class="forum-badge forum-badge--admin">Solo staff publica</span>' : ''}
        ${c.is_hidden ? '<span class="forum-badge forum-badge--locked">Oculta</span>' : ''}
      </span>
    </a>`).join('')}</div>`;
}

// ================================================================
// /foro/categoria/ — lista de hilos
// ================================================================
async function initHilos() {
  document.getElementById('foro-breadcrumb-back').href = `${BASE}/foro/`;

  const container = document.getElementById('foro-hilos-container');
  const catId = FORO_CATEGORY_ID || Number(new URLSearchParams(window.location.search).get('id'));

  if (!catId) {
    container.innerHTML = '<p class="state-message">Categoría inválida.</p>';
    return;
  }

  const data = await apiFetch(`forum/threads.php?category_id=${catId}`);
  if (!data || data.error) {
    container.innerHTML = `<p class="state-message">${esc(data?.error ?? 'Error al cargar la categoría.')}</p>`;
    return;
  }

  document.getElementById('foro-cat-nombre').textContent = data.category.name;
  document.getElementById('foro-cat-desc').textContent = data.category.description ?? '';

  const toggleWrap = document.getElementById('foro-nuevo-hilo-toggle-wrap');
  const puedeCrear = !data.category.admin_only_post || foroIsAdmin();
  toggleWrap.hidden = !puedeCrear;

  document.getElementById('foro-nuevo-hilo-toggle').addEventListener('click', () => {
    if (!isAuthenticated()) {
      window.location.href = `${BASE}/login/?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`;
      return;
    }
    document.getElementById('foro-nuevo-hilo-form').hidden = false;
    toggleWrap.hidden = true;
  });
  document.getElementById('foro-nuevo-cancelar').addEventListener('click', () => {
    document.getElementById('foro-nuevo-hilo-form').hidden = true;
    toggleWrap.hidden = false;
  });
  document.getElementById('foro-nuevo-submit').addEventListener('click', () => onCrearHilo(catId));

  renderHilos(data.threads ?? []);
}

function renderHilos(threads) {
  const container = document.getElementById('foro-hilos-container');
  if (!threads.length) {
    container.innerHTML = '<p class="state-message">Todavía no hay hilos en esta categoría — ¡sé el primero!</p>';
    return;
  }

  container.innerHTML = threads.map(t => `
    <a class="forum-thread-row" href="${BASE}/foro/hilo/?id=${t.id}">
      <div class="forum-thread-row__main">
        <span class="forum-thread-row__title">
          ${t.is_pinned ? '<span class="forum-badge forum-badge--pinned">📌 Fijado</span>' : ''}
          ${t.is_locked ? '<span class="forum-badge forum-badge--locked">🔒 Cerrado</span>' : ''}
          ${esc(t.title)}
        </span>
        <span class="forum-thread-row__meta">por ${esc(t.author_display_name)} · ${foroFmtFecha(t.created_at)}</span>
      </div>
      <span class="forum-thread-row__replies">${t.reply_count} respuesta${Number(t.reply_count) === 1 ? '' : 's'}</span>
    </a>`).join('');
}

async function onCrearHilo(catId) {
  const titulo = document.getElementById('foro-nuevo-titulo').value.trim();
  const cuerpo = document.getElementById('foro-nuevo-body').value.trim();
  const feedback = document.getElementById('foro-nuevo-feedback');
  feedback.textContent = '';

  if (!titulo || !cuerpo) {
    feedback.textContent = 'Completá el título y el mensaje.';
    feedback.style.color = '#e05555';
    return;
  }

  const btn = document.getElementById('foro-nuevo-submit');
  btn.disabled = true;

  const res = await authFetch('forum/create_thread.php', {
    method: 'POST',
    body: JSON.stringify({ category_id: catId, title: titulo, body: cuerpo }),
  });
  btn.disabled = false;
  if (!res) return;

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    // El texto escrito nunca se pierde: queda en el textarea (F-09.01)
    feedback.textContent = data.error ?? 'No se pudo crear el hilo.';
    feedback.style.color = '#e05555';
    return;
  }

  window.location.href = `${BASE}/foro/hilo/?id=${data.id}`;
}

// ================================================================
// /foro/hilo/ — detalle del hilo
// ================================================================
let _foroHiloActual = null;

async function initHiloDetalle() {
  const id = FORO_THREAD_ID || Number(new URLSearchParams(window.location.search).get('id'));
  const container = document.getElementById('foro-hilo-container');

  if (!id) {
    container.innerHTML = '<p class="state-message">Hilo inválido.</p>';
    return;
  }

  // El detalle se pide autenticado si hay sesión (para is_mine/reacciones propias)
  const data = isAuthenticated()
    ? await (async () => {
        const res = await authFetch(`forum/thread.php?id=${id}`);
        return res ? res.json().catch(() => null) : null;
      })()
    : await apiFetch(`forum/thread.php?id=${id}`);

  if (!data || data.error) {
    container.innerHTML = `<p class="state-message">${esc(data?.error ?? 'Error al cargar el hilo.')}</p>`;
    return;
  }

  _foroHiloActual = data.thread;
  document.getElementById('foro-breadcrumb-back').href = `${BASE}/foro/categoria/?id=${data.thread.category_id}`;
  document.getElementById('foro-hilo-titulo').textContent = data.thread.title;
  document.getElementById('foro-hilo-meta').textContent =
    `por ${data.thread.author_display_name} · ${foroFmtFecha(data.thread.created_at)}`;

  renderHiloDetalle(data.thread, data.posts ?? []);
  initAdminControls(data.thread);
  initRespuestaForm(data.thread);
}

function foroEditLabel(row) {
  if (!row.edited_at) return '';
  return row.edited_by_staff ? ' · editado por el staff' : ' · editado';
}

function renderMensaje(row, targetType) {
  const admin  = foroIsAdmin();
  const enVentana = foroMsDesde(row.created_at) < FORO_EDIT_WINDOW_MIN * 60 * 1000;
  const puedeEditar = admin || (row.is_mine && enVentana);
  const puedeBorrar = admin || row.is_mine;
  const puedeReportar = isAuthenticated() && !row.is_mine;

  return `
    <div class="forum-post" data-target-type="${targetType}" data-id="${row.id}">
      <div class="forum-post__header">
        <strong>${esc(row.author_display_name)}</strong>
        <span>${foroFmtFecha(row.created_at)}${foroEditLabel(row)}</span>
      </div>
      <div class="forum-post__body" data-raw="${esc(row.body)}">${renderRichText(row.body)}</div>
      <div class="forum-post__footer">
        ${foroReactionBtn(targetType, row.id, row.reactions, row.is_mine)}
        ${puedeEditar ? `<button type="button" class="forum-post__action" data-action="editar">✏️ Editar</button>` : ''}
        ${puedeBorrar ? `<button type="button" class="forum-post__action" data-action="borrar">🗑 Borrar</button>` : ''}
        ${puedeReportar ? `<button type="button" class="forum-post__action" data-action="reportar">🚩 Reportar</button>` : ''}
      </div>
      <div class="forum-report-form" hidden></div>
    </div>`;
}

function renderHiloDetalle(thread, posts) {
  const container = document.getElementById('foro-hilo-container');

  let html = '';

  if (thread.is_locked) {
    html += `<div class="forum-locked-banner">🔒 Hilo cerrado por el staff${thread.locked_reason ? ` — ${esc(thread.locked_reason)}` : ''}</div>`;
  }

  html += renderMensaje(thread, 'thread');

  // Posts borrados: placeholder para no romper la lectura, salvo los que quedan
  // al final del hilo (esos se omiten — criterio F-03.04)
  let lastVisible = -1;
  posts.forEach((p, i) => { if (!p.is_deleted) lastVisible = i; });

  html += posts.slice(0, lastVisible + 1).map(p =>
    p.is_deleted
      ? `<div class="forum-post forum-post--deleted">🗑 Mensaje eliminado</div>`
      : renderMensaje(p, 'post')
  ).join('');

  container.innerHTML = html;

  container.querySelectorAll('.forum-reaction-btn:not(.forum-reaction-btn--static)').forEach(btn =>
    btn.addEventListener('click', () => foroToggleReaction(btn))
  );
  container.querySelectorAll('[data-action="editar"]').forEach(btn =>
    btn.addEventListener('click', e => onEditarMensaje(e.target.closest('.forum-post')))
  );
  container.querySelectorAll('[data-action="borrar"]').forEach(btn =>
    btn.addEventListener('click', e => onBorrarMensaje(e.target.closest('.forum-post')))
  );
  container.querySelectorAll('[data-action="reportar"]').forEach(btn =>
    btn.addEventListener('click', e => onReportarMensaje(e.target.closest('.forum-post')))
  );

  const admin = foroIsAdmin();
  document.getElementById('foro-respuesta-form').hidden = thread.is_locked && !admin;
  document.getElementById('foro-hilo-cerrado-msg').hidden = !thread.is_locked || admin;
}

// ── Reportar (F-08.02) ───────────────────────────────────────
function onReportarMensaje(postEl) {
  const formEl = postEl.querySelector('.forum-report-form');
  if (!formEl.hidden) { formEl.hidden = true; formEl.innerHTML = ''; return; }

  formEl.innerHTML = `
    <select class="exchange-select forum-report-form__reason">
      ${FORO_REPORT_REASONS.map(([v, l]) => `<option value="${v}">${l}</option>`).join('')}
    </select>
    <input type="text" class="forum-report-form__comment" maxlength="500" placeholder="Comentario (opcional)">
    <div class="cp-actions">
      <button type="button" class="btn btn-primary btn-sm forum-report-form__send">Enviar reporte</button>
      <button type="button" class="btn btn-secondary btn-sm forum-report-form__cancel">Cancelar</button>
    </div>`;
  formEl.hidden = false;

  formEl.querySelector('.forum-report-form__cancel').addEventListener('click', () => {
    formEl.hidden = true;
    formEl.innerHTML = '';
  });

  formEl.querySelector('.forum-report-form__send').addEventListener('click', async () => {
    const sendBtn = formEl.querySelector('.forum-report-form__send');
    sendBtn.disabled = true;

    const res = await authFetch('forum/report.php', {
      method: 'POST',
      body: JSON.stringify({
        target_type: postEl.dataset.targetType,
        target_id:   Number(postEl.dataset.id),
        reason:      formEl.querySelector('.forum-report-form__reason').value,
        comment:     formEl.querySelector('.forum-report-form__comment').value.trim(),
      }),
    });
    sendBtn.disabled = false;
    if (!res) return;

    const data = await res.json().catch(() => ({}));
    if (!res.ok) { alert(data.error ?? 'No se pudo enviar el reporte.'); return; }

    formEl.innerHTML = '<p class="forum-report-form__done">✔ Reporte enviado — el staff lo va a revisar.</p>';
  });
}

// ── Editar / borrar ──────────────────────────────────────────
async function onEditarMensaje(postEl) {
  const targetType = postEl.dataset.targetType;
  const id = Number(postEl.dataset.id);
  const bodyEl = postEl.querySelector('.forum-post__body');
  const actual = bodyEl.dataset.raw ?? bodyEl.textContent;

  const nuevo = prompt('Editar mensaje:', actual);
  if (nuevo === null || nuevo.trim() === '') return;

  const payload = { target_type: targetType, id, body: nuevo.trim() };
  if (targetType === 'thread') {
    const nuevoTitulo = prompt('Título del hilo:', document.getElementById('foro-hilo-titulo').textContent);
    if (nuevoTitulo === null || nuevoTitulo.trim() === '') return;
    payload.title = nuevoTitulo.trim();
  }

  const res = await authFetch('forum/edit_post.php', { method: 'POST', body: JSON.stringify(payload) });
  if (!res) return;
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    alert(data.error ?? 'No se pudo editar.');
    return;
  }
  initHiloDetalle();
}

async function onBorrarMensaje(postEl) {
  const targetType = postEl.dataset.targetType;
  const id = Number(postEl.dataset.id);
  const esHilo = targetType === 'thread';

  if (!confirm(esHilo ? '¿Borrar todo el hilo?' : '¿Borrar este mensaje?')) return;

  const payload = { target_type: targetType, id };
  // Admin: motivo opcional que queda en la auditoría (el server lo ignora
  // si quien borra es el dueño del contenido)
  if (foroIsAdmin()) {
    const motivo = prompt('Motivo (opcional, queda en la auditoría):', '');
    if (motivo === null) return;
    if (motivo.trim()) payload.reason = motivo.trim();
  }

  const res = await authFetch('forum/delete_post.php', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
  if (!res) return;
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    alert(data.error ?? 'No se pudo borrar.');
    return;
  }

  if (esHilo) {
    window.location.href = `${BASE}/foro/categoria/?id=${_foroHiloActual.category_id}`;
  } else {
    initHiloDetalle();
  }
}

// ── Controles de admin del hilo ──────────────────────────────
function initAdminControls(thread) {
  const wrap = document.getElementById('foro-admin-controls');
  if (!foroIsAdmin()) { wrap.hidden = true; return; }
  wrap.hidden = false;

  const btnPin  = document.getElementById('foro-btn-pin');
  const btnLock = document.getElementById('foro-btn-lock');
  btnPin.textContent  = thread.is_pinned ? '📌 Quitar fijado' : '📌 Fijar';
  btnLock.textContent = thread.is_locked ? '🔓 Reabrir' : '🔒 Cerrar';

  btnPin.onclick = () => moderarHilo(thread.is_pinned ? 'unpin' : 'pin');
  btnLock.onclick = () => {
    if (thread.is_locked) { moderarHilo('unlock'); return; }
    const motivo = prompt('Motivo del cierre (visible en el hilo, opcional):', '');
    if (motivo === null) return;
    moderarHilo('lock', { reason: motivo.trim() || undefined });
  };
  document.getElementById('foro-btn-editar-hilo').onclick = () =>
    onEditarMensaje(document.querySelector('.forum-post[data-target-type="thread"]'));
  document.getElementById('foro-btn-borrar-hilo').onclick = () =>
    onBorrarMensaje(document.querySelector('.forum-post[data-target-type="thread"]'));

  const btnMover = document.getElementById('foro-btn-mover');
  if (btnMover) btnMover.onclick = onMoverHilo;
}

async function onMoverHilo() {
  const data = await apiFetch('forum/categories.php');
  if (!data?.categories?.length) { alert('No se pudieron cargar las categorías.'); return; }

  const opciones = data.categories
    .filter(c => c.id !== _foroHiloActual.category_id)
    .map(c => `${c.id} = ${c.name}`)
    .join('\n');
  const destino = prompt(`¿A qué categoría mover el hilo? Ingresá el número:\n\n${opciones}`);
  if (destino === null) return;

  const categoryId = Number(destino.trim());
  if (!categoryId) return;

  moderarHilo('move', { category_id: categoryId });
}

async function moderarHilo(action, extra = {}) {
  const res = await authFetch('admin/forum_moderate.php', {
    method: 'POST',
    body: JSON.stringify({ action, id: _foroHiloActual.id, ...extra }),
  });
  if (!res) return;
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    alert(data.error ?? 'No se pudo aplicar la acción.');
    return;
  }
  initHiloDetalle();
}

// ── Responder ────────────────────────────────────────────────
function initRespuestaForm(thread) {
  if (thread.is_locked && !foroIsAdmin()) return;

  document.getElementById('foro-respuesta-submit').onclick = async () => {
    if (!isAuthenticated()) {
      window.location.href = `${BASE}/login/?redirect=${encodeURIComponent(window.location.pathname)}`;
      return;
    }

    const textarea = document.getElementById('foro-respuesta-body');
    const feedback = document.getElementById('foro-respuesta-feedback');
    const mensaje = textarea.value.trim();
    feedback.textContent = '';

    if (!mensaje) {
      feedback.textContent = 'Escribí una respuesta.';
      feedback.style.color = '#e05555';
      return;
    }

    const btn = document.getElementById('foro-respuesta-submit');
    btn.disabled = true;

    const res = await authFetch('forum/reply.php', {
      method: 'POST',
      body: JSON.stringify({ thread_id: _foroHiloActual.id, body: mensaje }),
    });
    btn.disabled = false;
    if (!res) return;

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      // El texto queda en el textarea — no se pierde (F-09.01)
      feedback.textContent = data.error ?? 'No se pudo enviar la respuesta.';
      feedback.style.color = '#e05555';
      return;
    }

    textarea.value = '';
    initHiloDetalle();
  };
}
