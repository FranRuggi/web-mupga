/* ============================================================
   MuPGA — eventos.js
   Página pública /eventos/: listado, inscripción, lista de anotados.
   Depende de app.js (esc, apiFetch, API, BASE) y auth.js
   (isAuthenticated, authFetch), cargado en todas las páginas.
   ============================================================ */

let $alert;
let eventsCache      = [];
let charactersCache  = null; // se carga una sola vez, al primer intento de anotarse

function showAlert(msg, isError) {
  $alert.textContent = msg;
  $alert.className = 'alert visible ' + (isError ? 'alert--error' : 'alert--success');
}

// 'YYYY-MM-DDTHH:MM:SS...' (UTC, sin sufijo Z) → hora local del navegador
function formatEventDate(utcStr) {
  if (!utcStr) return 'Hora a confirmar';
  return new Date(utcStr + 'Z').toLocaleString(undefined, {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

// Display only — el servidor revalida el cutoff real con GETUTCDATE().
function isEventOpen(ev) {
  if (!ev.event_datetime) return true;
  return new Date(ev.event_datetime + 'Z').getTime() > Date.now();
}

// Badge "⏰ Empieza en Xh Ym" cuando falta menos de 24hs — mismo lenguaje
// visual que ya usa el Prode para partidos próximos (.prode-badge--soon).
function startsSoonBadge(ev) {
  if (!ev.event_datetime) return '';
  const diffMs = new Date(ev.event_datetime + 'Z').getTime() - Date.now();
  if (diffMs <= 0 || diffMs > 24 * 3600 * 1000) return '';
  const h = Math.floor(diffMs / 3600000);
  const m = Math.floor((diffMs % 3600000) / 60000);
  const txt = h > 0 ? `Empieza en ${h}h ${m}m` : `Empieza en ${m}m`;
  return `<span class="prode-badge prode-badge--soon">⏰ ${esc(txt)}</span>`;
}

// Hash simple y estable del nombre → 1 de 6 variantes de color para el
// avatar circular del roster. Mismo personaje siempre cae en el mismo color.
function nameHash(str) {
  let h = 0;
  for (let i = 0; i < str.length; i++) h = (h * 31 + str.charCodeAt(i)) >>> 0;
  return h % 6;
}

async function getCharacters() {
  if (charactersCache) return charactersCache;
  const res = await authFetch('account/profile.php');
  if (!res || !res.ok) return [];
  const data = await res.json().catch(() => null);
  charactersCache = data?.characters ?? [];
  return charactersCache;
}

async function loadEvents() {
  const el = document.getElementById('eventos-list');

  let data;
  if (isAuthenticated()) {
    const res = await authFetch('events/list.php');
    data = res && res.ok ? await res.json().catch(() => null) : null;
  } else {
    data = await apiFetch('events/list.php');
  }

  if (!data || !data.events) {
    el.innerHTML = '<p class="state-message">Error al cargar los eventos.</p>';
    return;
  }

  eventsCache = data.events;

  if (!eventsCache.length) {
    el.innerHTML = '<p class="state-message">No hay eventos activos por ahora. Volvé pronto ⚔️</p>';
    return;
  }

  el.innerHTML = eventsCache.map(renderEventCard).join('');
  eventsCache.forEach(wireEventCard);
}

function renderEventCard(ev) {
  const open       = isEventOpen(ev);
  const full       = ev.max_slots !== null && ev.registered_count >= ev.max_slots;
  const cupoTxt    = ev.max_slots !== null
    ? `${ev.registered_count}/${ev.max_slots} anotados`
    : `${ev.registered_count} anotado${ev.registered_count === 1 ? '' : 's'}`;
  const registered = !!ev.my_registration;

  const capacityBar = ev.max_slots !== null ? `
      <div class="evento-capacity">
        <div class="evento-capacity__fill${full ? ' evento-capacity__fill--full' : ''}"
             style="width:${Math.min(100, Math.round(ev.registered_count / ev.max_slots * 100))}%"></div>
      </div>` : '';

  let actionHtml;
  if (!isAuthenticated()) {
    actionHtml = `<a class="btn btn-secondary btn-sm" href="${BASE}/login/?redirect=${encodeURIComponent('/eventos/')}">Iniciá sesión para anotarte</a>`;
  } else if (registered) {
    actionHtml = `
      <span class="badge badge--vip">✓ Anotado como ${esc(ev.my_registration)}</span>
      <button class="btn btn-secondary btn-sm" data-unregister="${ev.id}">Cancelar inscripción</button>`;
  } else if (!open) {
    actionHtml = `<span class="state-message">Inscripciones cerradas</span>`;
  } else if (full) {
    actionHtml = `<span class="state-message">Cupo completo</span>`;
  } else {
    actionHtml = `
      <div class="evento-register">
        <select id="evento-char-${ev.id}"><option value="">Elegí tu personaje…</option></select>
        <button class="btn btn-primary btn-sm" data-register="${ev.id}">Anotarme</button>
      </div>`;
  }

  return `
    <div class="account-card evento-card" data-event="${ev.id}">
      <div class="evento-card__head">
        <p class="evento-card__title">${esc(ev.title)}</p>
        ${startsSoonBadge(ev)}
      </div>
      ${ev.description ? `<p class="evento-desc">${esc(ev.description).replace(/\n/g, '<br>')}</p>` : ''}
      <div class="evento-meta">
        <span class="evento-meta__item${ev.event_datetime ? '' : ' evento-meta__item--tbd'}">🗓️ <strong>${esc(formatEventDate(ev.event_datetime))}</strong></span>
        <span class="evento-meta__item">👥 <strong>${esc(cupoTxt)}</strong></span>
      </div>
      ${capacityBar}
      <div class="evento-actions">${actionHtml}</div>
      <button type="button" class="evento-regs-toggle" data-toggle-regs="${ev.id}">
        <span class="evento-regs-toggle__chevron">›</span>
        Anotados <span class="evento-regs-toggle__count">(${ev.registered_count})</span>
      </button>
      <div class="evento-regs-list" id="evento-regs-${ev.id}" hidden></div>
    </div>`;
}

function wireEventCard(ev) {
  const root = document.querySelector(`[data-event="${ev.id}"]`);
  if (!root) return;

  const regBtn = root.querySelector(`[data-register="${ev.id}"]`);
  if (regBtn) {
    populateCharacterSelect(ev.id);
    regBtn.addEventListener('click', () => handleRegister(ev.id));
  }

  const unregBtn = root.querySelector(`[data-unregister="${ev.id}"]`);
  if (unregBtn) unregBtn.addEventListener('click', () => handleUnregister(ev.id));

  const toggleBtn = root.querySelector(`[data-toggle-regs="${ev.id}"]`);
  if (toggleBtn) toggleBtn.addEventListener('click', () => toggleRegistrations(ev.id, toggleBtn));
}

async function populateCharacterSelect(eventId) {
  const sel = document.getElementById(`evento-char-${eventId}`);
  if (!sel) return;

  const chars = await getCharacters();
  if (chars.length) {
    sel.innerHTML = '<option value="">Elegí tu personaje…</option>' +
      chars.map(c => `<option value="${esc(c.name)}">${esc(c.name)} (Lv. ${c.level})</option>`).join('');
  } else {
    sel.innerHTML = '<option value="">No tenés personajes creados</option>';
    sel.disabled = true;
  }
}

async function handleRegister(eventId) {
  const sel            = document.getElementById(`evento-char-${eventId}`);
  const characterName  = sel ? sel.value : '';
  if (!characterName) { showAlert('Elegí un personaje para anotarte.', true); return; }

  const res = await authFetch('events/register.php', {
    method: 'POST',
    body: JSON.stringify({ event_id: eventId, character_name: characterName }),
  });
  if (!res) return;
  const data = await res.json().catch(() => null);

  if (!res.ok) { showAlert(data?.error ?? 'No se pudo completar la inscripción.', true); return; }

  showAlert('¡Listo, quedaste anotado!', false);
  loadEvents();
}

async function handleUnregister(eventId) {
  if (!confirm('¿Cancelar tu inscripción a este evento?')) return;

  const res = await authFetch('events/unregister.php', {
    method: 'POST',
    body: JSON.stringify({ event_id: eventId }),
  });
  if (!res) return;
  const data = await res.json().catch(() => null);

  if (!res.ok) { showAlert(data?.error ?? 'No se pudo cancelar la inscripción.', true); return; }

  showAlert('Inscripción cancelada.', false);
  loadEvents();
}

async function toggleRegistrations(eventId, btn) {
  const box = document.getElementById(`evento-regs-${eventId}`);
  if (!box) return;

  if (!box.hidden) { box.hidden = true; btn.classList.remove('is-open'); return; }

  box.hidden = false;
  btn.classList.add('is-open');
  box.innerHTML = '<p class="state-message">Cargando…</p>';

  const data = await apiFetch(`events/registrations.php?event_id=${eventId}`);
  if (!data || !data.registrations) {
    box.innerHTML = '<p class="state-message">Error al cargar.</p>';
    return;
  }
  if (!data.registrations.length) {
    box.innerHTML = '<p class="evento-regs-empty">👻 Todavía nadie se anotó. ¡Sé el primero!</p>';
    return;
  }

  box.innerHTML = '<div class="evento-roster">' +
    data.registrations.map((r, i) => {
      const name    = r.character_name;
      const initial = esc(name.charAt(0).toUpperCase());
      const variant = nameHash(name);
      return `
        <div class="evento-roster-item" style="animation-delay:${Math.min(i * 0.04, 0.6)}s">
          <span class="evento-avatar evento-avatar--${variant}">${initial}</span>
          <span class="evento-roster-name">${esc(name)}</span>
        </div>`;
    }).join('') +
    '</div>';
}

document.addEventListener('DOMContentLoaded', () => {
  $alert = document.getElementById('eventos-alert');
  loadEvents();
});
