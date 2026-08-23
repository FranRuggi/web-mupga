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
    el.innerHTML = '<p class="state-message">No hay eventos activos por ahora.</p>';
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

  let actionHtml;
  if (!isAuthenticated()) {
    actionHtml = `<a class="btn btn-secondary btn-sm" href="${BASE}/login/?redirect=${encodeURIComponent('/eventos/')}">Iniciá sesión para anotarte</a>`;
  } else if (registered) {
    actionHtml = `
      <span class="badge badge--vip">Anotado como ${esc(ev.my_registration)}</span>
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
      <p class="account-card__title">${esc(ev.title)}</p>
      ${ev.description ? `<p class="evento-desc">${esc(ev.description).replace(/\n/g, '<br>')}</p>` : ''}
      <div class="evento-meta">
        <span>🗓️ ${esc(formatEventDate(ev.event_datetime))}</span>
        <span>👥 ${esc(cupoTxt)}</span>
      </div>
      <div class="evento-actions">${actionHtml}</div>
      <button type="button" class="btn btn-secondary btn-sm evento-toggle-regs" data-toggle-regs="${ev.id}">Ver anotados</button>
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

  if (!box.hidden) { box.hidden = true; btn.textContent = 'Ver anotados'; return; }

  box.hidden = false;
  btn.textContent = 'Ocultar anotados';
  box.innerHTML = '<p class="state-message">Cargando…</p>';

  const data = await apiFetch(`events/registrations.php?event_id=${eventId}`);
  if (!data || !data.registrations) {
    box.innerHTML = '<p class="state-message">Error al cargar.</p>';
    return;
  }
  if (!data.registrations.length) {
    box.innerHTML = '<p class="state-message">Todavía nadie se anotó.</p>';
    return;
  }
  box.innerHTML = '<ul class="evento-regs-ul">' +
    data.registrations.map((r, i) => `<li><span class="evento-regs-num">${i + 1}.</span> ${esc(r.character_name)}</li>`).join('') +
    '</ul>';
}

document.addEventListener('DOMContentLoaded', () => {
  $alert = document.getElementById('eventos-alert');
  loadEvents();
});
