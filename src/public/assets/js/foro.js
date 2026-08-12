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
  foroInjectTools(); // buscador + campanita en todas las páginas del foro
  if (document.getElementById('foro-categorias-container')) initCategorias();
  if (document.getElementById('foro-hilos-container'))       initHilos();
  if (document.getElementById('foro-hilo-container'))        initHiloDetalle();
  if (document.getElementById('foro-buscar-container'))      initBuscar();
});

// ── Paginación (F-03.05 / F-10.02): links reales, la URL refleja la página ──
function foroPagerHtml(page, totalPages, baseParams) {
  if (totalPages <= 1) return '';
  const href = p => `?${new URLSearchParams({ ...baseParams, page: p })}`;
  let links = '';
  for (let p = 1; p <= totalPages; p++) {
    if (totalPages > 9 && Math.abs(p - page) > 2 && p !== 1 && p !== totalPages) {
      if (!links.endsWith('…')) links += '…';
      continue;
    }
    links += p === page
      ? `<span class="forum-pager__cur">${p}</span>`
      : `<a class="forum-pager__link" href="${href(p)}">${p}</a>`;
  }
  return `<nav class="forum-pager">${links}</nav>`;
}

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

  const page = Number(new URLSearchParams(window.location.search).get('page')) || 1;
  const data = await apiFetch(`forum/threads.php?category_id=${catId}&page=${page}`);
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
  foroInitEditor(document.getElementById('foro-nuevo-body'));

  renderHilos(data.threads ?? [], foroPagerHtml(data.page ?? 1, data.total_pages ?? 1, { id: catId }));
}

function renderHilos(threads, pagerHtml = '') {
  const container = document.getElementById('foro-hilos-container');
  if (!threads.length) {
    container.innerHTML = '<p class="state-message">Todavía no hay hilos en esta categoría — ¡sé el primero!</p>';
    return;
  }

  container.innerHTML = pagerHtml + threads.map(t => `
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
    </a>`).join('') + pagerHtml;
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
let _foroParticipantes = new Set(); // nombres visibles del hilo, para autocompletar @menciones

async function initHiloDetalle() {
  const id = FORO_THREAD_ID || Number(new URLSearchParams(window.location.search).get('id'));
  const container = document.getElementById('foro-hilo-container');

  if (!id) {
    container.innerHTML = '<p class="state-message">Hilo inválido.</p>';
    return;
  }

  // ?page=N o ?post=X (el server resuelve en qué página cae el post — F-03.05)
  const qs     = new URLSearchParams(window.location.search);
  const page   = Number(qs.get('page')) || 0;
  const postId = Number(qs.get('post')) || (window.location.hash.match(/^#post-(\d+)/)?.[1] ?? 0);
  let   url    = `forum/thread.php?id=${id}`;
  if (page)        url += `&page=${page}`;
  else if (postId) url += `&post=${postId}`;

  // El detalle se pide autenticado si hay sesión (para is_mine/reacciones propias)
  const data = isAuthenticated()
    ? await (async () => {
        const res = await authFetch(url);
        return res ? res.json().catch(() => null) : null;
      })()
    : await apiFetch(url);

  if (!data || data.error) {
    container.innerHTML = `<p class="state-message">${esc(data?.error ?? 'Error al cargar el hilo.')}</p>`;
    return;
  }

  _foroHiloActual = data.thread;
  document.getElementById('foro-breadcrumb-back').href = `${BASE}/foro/categoria/?id=${data.thread.category_id}`;
  document.getElementById('foro-hilo-titulo').textContent = data.thread.title;
  document.getElementById('foro-hilo-meta').textContent =
    `por ${data.thread.author_display_name} · ${foroFmtFecha(data.thread.created_at)}`;

  renderHiloDetalle(data.thread, data.posts ?? [], data);
  initAdminControls(data.thread);
  initFollowBtn(data.thread, data.following);
  initRespuestaForm(data.thread, data);

  // Permalink #post-{id}: resaltar y scrollear al post pedido
  if (postId) {
    const el = document.getElementById(`post-${postId}`);
    if (el) { el.classList.add('forum-post--highlight'); el.scrollIntoView({ block: 'start' }); }
  }
}

// ── Seguir hilo (F-07.01) ────────────────────────────────────
function initFollowBtn(thread, following) {
  if (!isAuthenticated()) return;
  let btn = document.getElementById('foro-btn-follow');
  if (!btn) {
    btn = document.createElement('button');
    btn.id = 'foro-btn-follow';
    btn.className = 'btn btn-secondary btn-sm';
    document.getElementById('foro-hilo-meta').after(btn);
  }
  const pintar = f => { btn.textContent = f ? '🔕 Dejar de seguir' : '🔔 Seguir hilo'; btn.dataset.following = f ? '1' : ''; };
  pintar(following);
  btn.onclick = async () => {
    const target = !btn.dataset.following;
    btn.disabled = true;
    const res = await authFetch('forum/follow.php', {
      method: 'POST',
      body: JSON.stringify({ thread_id: thread.id, follow: target }),
    });
    btn.disabled = false;
    if (res?.ok) pintar(target);
  };
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

  const puedeCitar = isAuthenticated() && !_foroHiloActual?.is_locked;

  return `
    <div class="forum-post" id="${targetType === 'post' ? `post-${row.id}` : 'post-op'}"
         data-target-type="${targetType}" data-id="${row.id}">
      <div class="forum-post__header">
        <strong>${esc(row.author_display_name)}</strong>
        <span>${foroFmtFecha(row.created_at)}${foroEditLabel(row)}
          ${targetType === 'post' ? `<a class="forum-post__anchor" href="?id=${_foroHiloActual?.id}&post=${row.id}#post-${row.id}" title="Link a esta respuesta">#</a>` : ''}
        </span>
      </div>
      <div class="forum-post__body" data-raw="${esc(row.body)}">${renderRichText(row.body)}</div>
      <div class="forum-post__footer">
        ${foroReactionBtn(targetType, row.id, row.reactions, row.is_mine)}
        ${puedeCitar ? `<button type="button" class="forum-post__action" data-action="citar">💬 Citar</button>` : ''}
        ${puedeEditar ? `<button type="button" class="forum-post__action" data-action="editar">✏️ Editar</button>` : ''}
        ${puedeBorrar ? `<button type="button" class="forum-post__action" data-action="borrar">🗑 Borrar</button>` : ''}
        ${puedeReportar ? `<button type="button" class="forum-post__action" data-action="reportar">🚩 Reportar</button>` : ''}
      </div>
      <div class="forum-report-form" hidden></div>
    </div>`;
}

function renderHiloDetalle(thread, posts, paging = {}) {
  const container = document.getElementById('foro-hilo-container');
  const page       = paging.page ?? 1;
  const totalPages = paging.total_pages ?? 1;
  const pagerHtml  = foroPagerHtml(page, totalPages, { id: thread.id });

  // Autocompletado de menciones (F-03.03): participantes visibles del hilo
  _foroParticipantes = new Set([thread.author_display_name, ...posts.filter(p => !p.is_deleted).map(p => p.author_display_name)]);

  let html = '';

  if (thread.is_locked) {
    html += `<div class="forum-locked-banner">🔒 Hilo cerrado por el staff${thread.locked_reason ? ` — ${esc(thread.locked_reason)}` : ''}</div>`;
  }

  // El mensaje de apertura solo va en la página 1 (F-03.05)
  if (page === 1) html += renderMensaje(thread, 'thread');
  html += pagerHtml;

  // Posts borrados: placeholder para no romper la lectura, salvo los que quedan
  // al final del hilo (esos se omiten — criterio F-03.04; solo en la última página)
  let lastVisible = posts.length - 1;
  if (page === totalPages) {
    lastVisible = -1;
    posts.forEach((p, i) => { if (!p.is_deleted) lastVisible = i; });
  }

  html += posts.slice(0, lastVisible + 1).map(p =>
    p.is_deleted
      ? `<div class="forum-post forum-post--deleted">🗑 Mensaje eliminado</div>`
      : renderMensaje(p, 'post')
  ).join('');

  html += pagerHtml;

  container.innerHTML = html;

  container.querySelectorAll('.forum-reaction-btn:not(.forum-reaction-btn--static)').forEach(btn =>
    btn.addEventListener('click', () => foroToggleReaction(btn))
  );
  container.querySelectorAll('[data-action="citar"]').forEach(btn =>
    btn.addEventListener('click', e => onCitarMensaje(e.target.closest('.forum-post')))
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

  foroInitEditor(document.getElementById('foro-respuesta-body'));

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
    // Ir a la página donde cayó la respuesta (F-03.05)
    window.location.href = `${BASE}/foro/hilo/?id=${_foroHiloActual.id}&page=${data.page ?? 1}&post=${data.id}#post-${data.id}`;
  };
}

// ================================================================
// Citar (F-03.02)
// ================================================================
function onCitarMensaje(postEl) {
  const autor = postEl.querySelector('.forum-post__header strong')?.textContent ?? '';
  const raw   = postEl.querySelector('.forum-post__body')?.dataset.raw ?? '';
  // Un solo nivel de anidamiento: al citar se descartan las citas previas
  const cuerpo = raw.split('\n').filter(l => !l.trim().startsWith('>')).join('\n').trim();
  const permalink = postEl.dataset.targetType === 'post'
    ? `${BASE}/foro/hilo/?id=${_foroHiloActual.id}&post=${postEl.dataset.id}#post-${postEl.dataset.id}`
    : `${BASE}/foro/hilo/?id=${_foroHiloActual.id}`;

  const cita = `> **${autor}** [dijo](https://mupga.com.ar${permalink}):\n`
    + cuerpo.split('\n').map(l => `> ${l}`).join('\n') + '\n\n';

  const textarea = document.getElementById('foro-respuesta-body');
  if (!textarea) return;
  textarea.value = (textarea.value ? textarea.value.replace(/\n*$/, '\n\n') : '') + cita;
  textarea.focus();
  textarea.setSelectionRange(textarea.value.length, textarea.value.length);
  textarea.scrollIntoView({ block: 'center' });
}

// ================================================================
// Editor: barra de formato (F-04.01), vista previa (F-01.04),
// imágenes (F-04.05) y autocompletado de @menciones (F-03.03)
// ================================================================
function foroInsertMd(ta, antes, despues, placeholder = '') {
  const [a, b] = [ta.selectionStart, ta.selectionEnd];
  const sel    = ta.value.slice(a, b);
  const dentro = sel || placeholder;

  // Toggle: si la selección ya está envuelta, se quita el formato
  const preA = ta.value.slice(Math.max(0, a - antes.length), a);
  const postB = ta.value.slice(b, b + despues.length);
  if (sel && preA === antes && postB === despues) {
    ta.value = ta.value.slice(0, a - antes.length) + sel + ta.value.slice(b + despues.length);
    ta.setSelectionRange(a - antes.length, b - antes.length);
  } else {
    ta.value = ta.value.slice(0, a) + antes + dentro + despues + ta.value.slice(b);
    ta.setSelectionRange(a + antes.length, a + antes.length + dentro.length);
  }
  ta.focus();
}

function foroInitEditor(ta) {
  if (!ta || ta.dataset.foroEditor) return;
  ta.dataset.foroEditor = '1';

  const barra = document.createElement('div');
  barra.className = 'forum-editor-bar';
  barra.innerHTML = `
    <button type="button" data-md="bold" title="Negrita (Ctrl+B)"><strong>B</strong></button>
    <button type="button" data-md="italic" title="Cursiva (Ctrl+I)"><em>I</em></button>
    <button type="button" data-md="under" title="Subrayado"><u>U</u></button>
    <button type="button" data-md="strike" title="Tachado"><s>S</s></button>
    <button type="button" data-md="link" title="Link (Ctrl+K)">🔗</button>
    <button type="button" data-md="quote" title="Cita">💬</button>
    <button type="button" data-md="img" title="Subir imagen (JPG/PNG/WebP, máx 5 MB)">🖼️</button>
    <button type="button" data-md="preview" title="Vista previa" class="forum-editor-bar__preview">👁 Vista previa</button>
    <input type="file" accept="image/jpeg,image/png,image/webp" hidden>`;
  ta.before(barra);

  const previewEl = document.createElement('div');
  previewEl.className = 'forum-post__body forum-editor-preview';
  previewEl.hidden = true;
  ta.after(previewEl);

  const acciones = {
    bold:   () => foroInsertMd(ta, '**', '**', 'texto'),
    italic: () => foroInsertMd(ta, '*', '*', 'texto'),
    under:  () => foroInsertMd(ta, '__', '__', 'texto'),
    strike: () => foroInsertMd(ta, '~~', '~~', 'texto'),
    link:   () => foroInsertMd(ta, '[', '](https://)', 'texto'),
    quote:  () => foroInsertMd(ta, '\n> ', '\n', 'texto citado'),
    img:    () => barra.querySelector('input[type=file]').click(),
    preview: () => {
      const btn = barra.querySelector('[data-md="preview"]');
      const activar = previewEl.hidden;
      previewEl.innerHTML = activar ? (renderRichText(ta.value) || '<p><em>Nada que previsualizar…</em></p>') : '';
      previewEl.hidden = !activar;
      ta.style.display = activar ? 'none' : ''; // el textarea conserva texto y cursor
      btn.textContent = activar ? '✏️ Editar' : '👁 Vista previa';
    },
  };
  barra.querySelectorAll('button').forEach(b =>
    b.addEventListener('click', () => acciones[b.dataset.md]?.())
  );

  // Atajos Ctrl/Cmd+B, +I, +K (F-04.01)
  ta.addEventListener('keydown', e => {
    if (!(e.ctrlKey || e.metaKey)) return;
    const k = e.key.toLowerCase();
    if (k === 'b') { e.preventDefault(); acciones.bold(); }
    if (k === 'i') { e.preventDefault(); acciones.italic(); }
    if (k === 'k') { e.preventDefault(); acciones.link(); }
  });

  // Subida de imagen (F-04.05): presigned URL → PUT directo a R2
  barra.querySelector('input[type=file]').addEventListener('change', async e => {
    const file = e.target.files[0];
    e.target.value = '';
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { alert('La imagen supera los 5 MB.'); return; }
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      alert('Solo JPG, PNG o WebP.'); return;
    }
    if (!isAuthenticated()) {
      window.location.href = `${BASE}/login/?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`;
      return;
    }

    // El hilo define la subcarpeta en R2; al crear un hilo todavía no hay id
    const res = await authFetch('forum/upload_url.php', {
      method: 'POST',
      body: JSON.stringify({ contentType: file.type, thread_id: _foroHiloActual?.id ?? 0 }),
    });
    if (!res) return;
    const data = await res.json().catch(() => ({}));
    if (!res.ok) { alert(data.error ?? 'No se pudo subir la imagen.'); return; }

    const put = await fetch(data.uploadUrl, {
      method: 'PUT',
      headers: { 'Content-Type': file.type },
      body: file,
    }).catch(() => null);
    if (!put || !put.ok) { alert('Falló la subida de la imagen — probá de nuevo.'); return; }

    foroInsertMd(ta, `![imagen](${data.publicUrl})\n`, '');
  });

  // Autocompletado de @menciones: solo participantes del hilo cargado
  const dd = document.createElement('div');
  dd.className = 'forum-mention-dd';
  dd.hidden = true;
  ta.after(dd);

  ta.addEventListener('input', () => {
    const antes = ta.value.slice(0, ta.selectionStart);
    const m = antes.match(/@([A-Za-z0-9_]{2,10})$/);
    if (!m || !_foroParticipantes.size) { dd.hidden = true; return; }
    const opciones = [..._foroParticipantes]
      .filter(n => n.toLowerCase().startsWith(m[1].toLowerCase()))
      .slice(0, 5);
    if (!opciones.length) { dd.hidden = true; return; }
    dd.innerHTML = opciones.map(n => `<button type="button">@${esc(n)}</button>`).join('');
    dd.hidden = false;
    dd.querySelectorAll('button').forEach(b =>
      b.addEventListener('click', () => {
        const pos = ta.selectionStart;
        ta.value = ta.value.slice(0, pos - m[1].length) + b.textContent.slice(1) + ' ' + ta.value.slice(pos);
        dd.hidden = true;
        ta.focus();
      })
    );
  });
  ta.addEventListener('blur', () => setTimeout(() => { dd.hidden = true; }, 250));
}

// ================================================================
// Buscador (F-11.01) + campanita de notificaciones (F-07.02)
// ================================================================
function foroInjectTools() {
  const hero = document.querySelector('.page-hero');
  if (!hero || !document.getElementById('foro-categorias-container')
      && !document.getElementById('foro-hilos-container')
      && !document.getElementById('foro-hilo-container')
      && !document.getElementById('foro-buscar-container')) return;

  const row = document.createElement('div');
  row.className = 'forum-tools';
  row.innerHTML = `
    <a class="btn btn-secondary btn-sm" href="${BASE}/foro/buscar/">🔍 Buscar</a>
    ${isAuthenticated() ? '<button type="button" class="btn btn-secondary btn-sm" id="foro-bell">🔔</button>' : ''}
    <div class="forum-notif-panel" id="foro-notif-panel" hidden></div>`;
  hero.appendChild(row);

  if (isAuthenticated()) initNotifBell();
}

const FORO_NOTIF_LABELS = {
  respuesta:  ['💬', 'Respuestas nuevas en'],
  mencion:    ['📣', 'Te mencionaron en'],
  gracias:    ['🙏', 'Agradecieron tu mensaje en'],
  moderacion: ['⚠️', 'El staff moderó tu contenido en'],
};

async function initNotifBell() {
  const bell  = document.getElementById('foro-bell');
  const panel = document.getElementById('foro-notif-panel');

  const res = await authFetch('forum/notifications.php');
  if (!res || !res.ok) return;
  let data = await res.json().catch(() => null);
  if (!data) return;

  const pintarBadge = () => {
    bell.textContent = data.unread > 0 ? `🔔 ${data.unread}` : '🔔';
    bell.classList.toggle('forum-bell--unread', data.unread > 0);
  };
  pintarBadge();

  const renderPanel = () => {
    if (!data.notifications.length) {
      panel.innerHTML = '<p class="forum-notif-panel__empty">Sin avisos por ahora.</p>';
      return;
    }
    panel.innerHTML = `
      <button type="button" class="forum-notif-panel__readall">Marcar todo como leído</button>
      ${data.notifications.map(n => {
        const [icono, texto] = FORO_NOTIF_LABELS[n.type] ?? ['🔔', 'Aviso en'];
        const quien = n.actor_display ? `<strong>${esc(n.actor_display)}</strong> · ` : '';
        return `<button type="button" class="forum-notif${n.is_read ? '' : ' forum-notif--unread'}" data-id="${n.id}"
                 data-thread="${n.thread_id ?? ''}" data-post="${n.post_id ?? ''}">
          ${icono} ${quien}${texto} <em>${esc(n.thread_title ?? 'un hilo borrado')}</em>
          <span class="forum-notif__fecha">${foroFmtFecha(n.created_at)}</span>
        </button>`;
      }).join('')}`;

    panel.querySelector('.forum-notif-panel__readall').addEventListener('click', async () => {
      const r = await authFetch('forum/notifications.php', { method: 'POST', body: JSON.stringify({ action: 'read_all' }) });
      if (r?.ok) {
        data.unread = 0;
        data.notifications.forEach(n => { n.is_read = true; });
        pintarBadge(); renderPanel();
      }
    });
    panel.querySelectorAll('.forum-notif').forEach(b =>
      b.addEventListener('click', async () => {
        await authFetch('forum/notifications.php', { method: 'POST', body: JSON.stringify({ action: 'read', id: Number(b.dataset.id) }) });
        if (b.dataset.thread) {
          const post = b.dataset.post ? `&post=${b.dataset.post}#post-${b.dataset.post}` : '';
          window.location.href = `${BASE}/foro/hilo/?id=${b.dataset.thread}${post}`;
        }
      })
    );
  };

  bell.addEventListener('click', () => {
    panel.hidden = !panel.hidden;
    if (!panel.hidden) renderPanel();
  });
}

// ================================================================
// /foro/buscar/ — página de búsqueda (F-11.01)
// ================================================================
function initBuscar() {
  const input     = document.getElementById('foro-buscar-input');
  const container = document.getElementById('foro-buscar-container');
  document.getElementById('foro-breadcrumb-back').href = `${BASE}/foro/`;

  const resaltar = (texto, term) => {
    const e = esc(texto);
    const re = new RegExp(`(${esc(term).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return e.replace(re, '<mark>$1</mark>');
  };

  const buscar = async () => {
    const q = input.value.trim();
    if (q.length < 3) {
      container.innerHTML = '<p class="state-message">Escribí al menos 3 caracteres para buscar.</p>';
      return;
    }
    container.innerHTML = '<p class="state-message">Buscando…</p>';
    history.replaceState(null, '', `?q=${encodeURIComponent(q)}`);

    const data = await apiFetch(`forum/search.php?q=${encodeURIComponent(q)}`);
    if (!data || data.error) {
      container.innerHTML = `<p class="state-message">${esc(data?.error ?? 'Error al buscar.')}</p>`;
      return;
    }
    if (!data.results.length) {
      container.innerHTML = `<p class="state-message">Sin resultados para "${esc(q)}" — si nadie lo preguntó todavía, <a class="rank-name-link" href="${BASE}/foro/">creá un hilo</a>.</p>`;
      return;
    }
    container.innerHTML = data.results.map(r => `
      <a class="forum-thread-row" href="${BASE}/foro/hilo/?id=${r.id}">
        <div class="forum-thread-row__main">
          <span class="forum-thread-row__title">${resaltar(r.title, q)}</span>
          <span class="forum-thread-row__meta">${esc(r.category_name)} · por ${esc(r.author_display_name)} · ${foroFmtFecha(r.last_post_at)}</span>
          <span class="forum-thread-row__meta">${resaltar(r.snippet, q)}</span>
        </div>
        <span class="forum-thread-row__replies">${r.reply_count} respuesta${Number(r.reply_count) === 1 ? '' : 's'}</span>
      </a>`).join('');
  };

  document.getElementById('foro-buscar-btn').addEventListener('click', buscar);
  input.addEventListener('keydown', e => { if (e.key === 'Enter') buscar(); });

  const q0 = new URLSearchParams(window.location.search).get('q');
  if (q0) { input.value = q0; buscar(); }
}
