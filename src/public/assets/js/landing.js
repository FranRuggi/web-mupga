/* ============================================================
   MuPGA — landing.js
   Selector de servidores de mupga.com.ar.

   Autónomo a propósito: NO depende de app.js ni de auth.js (ver el
   docblock de layout_landing.php). Solo necesita config.js cargado antes.
   ============================================================ */

// API que sirve el contenido del sitio (mupga_admin). No es la API de un
// servidor de juego puntual — la landing no pertenece a ninguno.
const SITE_API = (typeof MUPGA_CONFIG !== 'undefined' && MUPGA_CONFIG.siteApi)
  ? MUPGA_CONFIG.siteApi
  : '';

// ── Utilidades ───────────────────────────────────────────────
function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// Solo http/https en href. Los datos salen de nuestra propia DB, pero un
// href se construye por concatenación: nunca dejar pasar javascript:.
function safeUrl(url) {
  try {
    const u = new URL(String(url), window.location.origin);
    return (u.protocol === 'http:' || u.protocol === 'https:') ? u.href : null;
  } catch { return null; }
}

// ── Estados ──────────────────────────────────────────────────
const ESTADOS = {
  activo:              { label: 'Online',            clase: 'is-activo' },
  proximo_lanzamiento: { label: 'Próximamente',      clase: 'is-proximo' },
  mantenimiento:       { label: 'En mantenimiento',  clase: 'is-mantenimiento' },
  cerrado:             { label: 'Cerrado',           clase: 'is-cerrado' },
};

// ── Countdown ────────────────────────────────────────────────
// Devuelve el texto restante hasta una fecha ISO UTC, o null si ya pasó.
function tiempoRestante(isoUtc) {
  const destino = Date.parse(isoUtc);
  if (Number.isNaN(destino)) return null;

  const diff = destino - Date.now();
  if (diff <= 0) return null;

  const dias  = Math.floor(diff / 86400000);
  const horas = Math.floor((diff % 86400000) / 3600000);
  const mins  = Math.floor((diff % 3600000) / 60000);

  if (dias  > 0) return `${dias}d ${horas}h`;
  if (horas > 0) return `${horas}h ${mins}m`;
  return `${mins}m`;
}

// ── Render de una card ───────────────────────────────────────
function renderServidor(s) {
  const estado = ESTADOS[s.estado] ?? ESTADOS.activo;
  const url    = safeUrl(s.web_url);
  const proximo = s.estado === 'proximo_lanzamiento';

  // Specs: solo las que tienen valor, para que una fila incompleta no
  // deje filas vacías en la card.
  const specs = [
    ['Versión',     s.version],
    ['Experiencia', s.experiencia],
    ['Drop',        s.drop],
    ['Resets',      s.sistema_reset],
    ['Límite',      s.limite_resets === null || s.limite_resets === undefined
                      ? null
                      : (s.limite_resets === 0 ? 'Sin límite' : `${s.limite_resets} resets`)],
    ['Tienda web',  s.tienda_items ? 'Sí' : null],
  ].filter(([, valor]) => valor !== null && valor !== undefined && valor !== '');

  const specsHtml = specs.map(([k, v]) => `
    <div class="servidor-spec">
      <span class="servidor-spec__k">${esc(k)}</span>
      <span class="servidor-spec__v">${esc(v)}</span>
    </div>`).join('');

  // Countdown solo si falta tiempo; si la fecha ya pasó pero el estado sigue
  // en proximo_lanzamiento, mostramos un aviso neutro en vez de un contador roto.
  let lanzamientoHtml = '';
  if (proximo) {
    const restante = s.fecha_lanzamiento ? tiempoRestante(s.fecha_lanzamiento) : null;
    lanzamientoHtml = restante
      ? `<div class="servidor-countdown" data-lanzamiento="${esc(s.fecha_lanzamiento)}">
           <span class="servidor-countdown__label">Lanza en</span>
           <span class="servidor-countdown__val">${esc(restante)}</span>
         </div>`
      : `<div class="servidor-countdown">
           <span class="servidor-countdown__label">Lanzamiento inminente</span>
         </div>`;
  }

  const banner = s.imagen_url && safeUrl(s.imagen_url)
    ? `<div class="servidor-card__banner" style="background-image:url('${esc(safeUrl(s.imagen_url))}')"></div>`
    : '';

  const accion = (proximo || !url)
    ? `<span class="btn btn-secondary servidor-card__cta is-disabled" aria-disabled="true">Próximamente</span>`
    : `<a href="${esc(url)}" class="btn btn-primary servidor-card__cta">Entrar</a>`;

  return `
    <article class="servidor-card ${estado.clase}">
      ${banner}
      <div class="servidor-card__head">
        <h3 class="servidor-card__name">${esc(s.nombre)}</h3>
        <span class="servidor-badge ${estado.clase}">${esc(estado.label)}</span>
      </div>
      ${s.descripcion ? `<p class="servidor-card__desc">${esc(s.descripcion)}</p>` : ''}
      ${lanzamientoHtml}
      <div class="servidor-specs">${specsHtml}</div>
      ${accion}
    </article>`;
}

// ── Carga ────────────────────────────────────────────────────
async function loadServidores() {
  const grid  = document.getElementById('servidor-grid');
  const error = document.getElementById('servidor-error');
  if (!grid) return;

  try {
    const res = await fetch(`${SITE_API}/api/site/servidores.php`, {
      headers: { 'Accept': 'application/json' },
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const data = await res.json();
    const servidores = Array.isArray(data?.servidores) ? data.servidores : [];

    if (servidores.length === 0) {
      grid.innerHTML = '<p class="servidor-empty">No hay servidores disponibles en este momento.</p>';
      return;
    }

    grid.innerHTML = servidores.map(renderServidor).join('');
    if (error) error.hidden = true;

    iniciarCountdowns();

  } catch (err) {
    console.warn('[landing] servidores:', err.message);
    grid.innerHTML = '';
    if (error) error.hidden = false;
  }
}

// Refresca los contadores cada minuto (el countdown es en días/horas: no
// hace falta segundo a segundo, y evita un timer corriendo en vano).
let countdownTimer = null;
function iniciarCountdowns() {
  if (countdownTimer) clearInterval(countdownTimer);

  const nodos = document.querySelectorAll('[data-lanzamiento]');
  if (nodos.length === 0) return;

  countdownTimer = setInterval(() => {
    let activos = 0;
    document.querySelectorAll('[data-lanzamiento]').forEach(nodo => {
      const restante = tiempoRestante(nodo.dataset.lanzamiento);
      const val = nodo.querySelector('.servidor-countdown__val');
      if (restante && val) { val.textContent = restante; activos++; }
    });
    if (activos === 0) clearInterval(countdownTimer);
  }, 60000);
}

document.addEventListener('DOMContentLoaded', () => {
  loadServidores();
  document.getElementById('servidor-retry')?.addEventListener('click', loadServidores);
});
