/* ============================================================
   MuPGA — foro.js
   Maneja las 3 páginas del foro: /foro/ (categorías),
   /foro/categoria/ (hilos) y /foro/hilo/ (detalle + responder).
   Depende de app.js (BASE, API, esc, renderRichText, apiFetch) y
   auth.js (isAuthenticated, authFetch, getUser).
   ============================================================ */

// '2026-07-18 09:51:44.9400000' → '18/07 09:51 hs'
function foroFmtFecha(sql) {
  const m = String(sql ?? '').match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
  return m ? `${m[3]}/${m[2]} ${m[4]}:${m[5]} hs` : '';
}

function foroIsAdmin() {
  return sessionStorage.getItem('mupga_admin') === '1';
}

function foroEsDueño(authorAccount) {
  const user = getUser();
  return !!(user && user.username === authorAccount);
}

function foroReactionBtn(targetType, targetId, reactions) {
  const r = reactions ?? { count: 0, reacted: false };
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
  const targetType = btn.dataset.targetType;
  const targetId   = Number(btn.dataset.targetId);
  btn.disabled = true;

  const res = await authFetch('forum/react.php', {
    method: 'POST',
    body: JSON.stringify({ target_type: targetType, target_id: targetId }),
  });
  btn.disabled = false;
  if (!res || !res.ok) return;

  const data = await res.json();
  btn.classList.toggle('forum-reaction-btn--active', data.reacted);
  btn.textContent = `🙏 Agradecer${data.count > 0 ? ` (${data.count})` : ''}`;
}

function foroInitReactionButtons(container) {
  container.querySelectorAll('.forum-reaction-btn').forEach(btn => {
    btn.addEventListener('click', () => foroToggleReaction(btn));
  });
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
      ${c.admin_only_post ? '<span class="forum-badge forum-badge--admin">Solo staff publica</span>' : ''}
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

  // Botón "Nuevo hilo": oculto si la categoría es solo-staff y no sos admin.
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

  const data = await apiFetch(`forum/thread.php?id=${id}`);
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

function renderMensaje({ id, targetType, author, fecha, body, edited, reactions, esDueño }) {
  const puedeModerar = foroIsAdmin() || esDueño;
  return `
    <div class="forum-post" data-target-type="${targetType}" data-id="${id}">
      <div class="forum-post__header">
        <strong>${esc(author)}</strong>
        <span>${foroFmtFecha(fecha)}${edited ? ' · editado' : ''}</span>
      </div>
      <div class="forum-post__body" data-raw="${esc(body)}">${renderRichText(body)}</div>
      <div class="forum-post__footer">
        ${foroReactionBtn(targetType, id, reactions)}
        ${puedeModerar ? `
          <button type="button" class="forum-post__action" data-action="editar">✏️ Editar</button>
          <button type="button" class="forum-post__action" data-action="borrar">🗑 Borrar</button>` : ''}
      </div>
    </div>`;
}

function renderHiloDetalle(thread, posts) {
  const container = document.getElementById('foro-hilo-container');

  let html = renderMensaje({
    id: thread.id, targetType: 'thread', author: thread.author_display_name,
    fecha: thread.created_at, body: thread.body, edited: !!thread.edited_at,
    reactions: thread.reactions, esDueño: foroEsDueño(thread.author_account),
  });

  html += posts.map(p => renderMensaje({
    id: p.id, targetType: 'post', author: p.author_display_name,
    fecha: p.created_at, body: p.body, edited: !!p.edited_at,
    reactions: p.reactions, esDueño: foroEsDueño(p.author_account),
  })).join('');

  container.innerHTML = html;
  foroInitReactionButtons(container);

  container.querySelectorAll('[data-action="editar"]').forEach(btn =>
    btn.addEventListener('click', e => onEditarMensaje(e.target.closest('.forum-post')))
  );
  container.querySelectorAll('[data-action="borrar"]').forEach(btn =>
    btn.addEventListener('click', e => onBorrarMensaje(e.target.closest('.forum-post')))
  );

  document.getElementById('foro-respuesta-form').hidden = !!thread.is_locked;
  document.getElementById('foro-hilo-cerrado-msg').hidden = !thread.is_locked;
}

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
  if (!confirm(esHilo ? '¿Borrar todo el hilo y sus respuestas?' : '¿Borrar este mensaje?')) return;

  const res = await authFetch('forum/delete_post.php', {
    method: 'POST',
    body: JSON.stringify({ target_type: targetType, id }),
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

function initAdminControls(thread) {
  const wrap = document.getElementById('foro-admin-controls');
  if (!foroIsAdmin()) { wrap.hidden = true; return; }
  wrap.hidden = false;

  const btnPin = document.getElementById('foro-btn-pin');
  const btnLock = document.getElementById('foro-btn-lock');
  btnPin.textContent = thread.is_pinned ? '📌 Quitar fijado' : '📌 Fijar';
  btnLock.textContent = thread.is_locked ? '🔓 Reabrir' : '🔒 Cerrar';

  btnPin.onclick = () => moderarHilo(thread.is_pinned ? 'unpin' : 'pin');
  btnLock.onclick = () => moderarHilo(thread.is_locked ? 'unlock' : 'lock');
  document.getElementById('foro-btn-editar-hilo').onclick = () =>
    onEditarMensaje(document.querySelector('.forum-post[data-target-type="thread"]'));
  document.getElementById('foro-btn-borrar-hilo').onclick = () =>
    onBorrarMensaje(document.querySelector('.forum-post[data-target-type="thread"]'));
}

async function moderarHilo(action) {
  const res = await authFetch('admin/forum_moderate.php', {
    method: 'POST',
    body: JSON.stringify({ action, id: _foroHiloActual.id }),
  });
  if (!res) return;
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    alert(data.error ?? 'No se pudo aplicar la acción.');
    return;
  }
  initHiloDetalle();
}

function initRespuestaForm(thread) {
  if (thread.is_locked) return;

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
      feedback.textContent = data.error ?? 'No se pudo enviar la respuesta.';
      feedback.style.color = '#e05555';
      return;
    }

    textarea.value = '';
    initHiloDetalle();
  };
}
