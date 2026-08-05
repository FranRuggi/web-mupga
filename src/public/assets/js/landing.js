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

// Subruta de una URL ya validada (ej. '/downloads/' del sitio del servidor).
// No es un campo nuevo en la DB: se deriva de web_url para el botón secundario.
function subUrl(path, base) {
  try {
    const b = new URL(String(base), window.location.origin);
    if (b.protocol !== 'http:' && b.protocol !== 'https:') return null;
    return new URL(path, b).href;
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
// Milisegundos restantes hasta una fecha ISO UTC, o null si ya pasó / es inválida.
function msRestantes(isoUtc) {
  const destino = Date.parse(isoUtc);
  if (Number.isNaN(destino)) return null;
  const diff = destino - Date.now();
  return diff > 0 ? diff : null;
}

function pad2(n) { return String(n).padStart(2, '0'); }

// Descompone un diff en ms en {dias, horas, mins, segs} para las fichas del countdown.
function partesTiempo(ms) {
  return {
    dias:  Math.floor(ms / 86400000),
    horas: Math.floor((ms % 86400000) / 3600000),
    mins:  Math.floor((ms % 3600000) / 60000),
    segs:  Math.floor((ms % 60000) / 1000),
  };
}

// ── Render de una card ───────────────────────────────────────
function renderServidor(s) {
  const estado  = ESTADOS[s.estado] ?? ESTADOS.activo;
  const url     = safeUrl(s.web_url);
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
    const restante = s.fecha_lanzamiento ? msRestantes(s.fecha_lanzamiento) : null;
    if (restante) {
      const p = partesTiempo(restante);
      lanzamientoHtml = `
        <div class="servidor-countdown" data-lanzamiento="${esc(s.fecha_lanzamiento)}">
          <span class="servidor-countdown__label">Abre en</span>
          <div class="servidor-countdown__tiles">
            <div class="servidor-countdown__tile"><span class="servidor-countdown__num" data-unit="d">${pad2(p.dias)}</span><span class="servidor-countdown__unit">Días</span></div>
            <div class="servidor-countdown__tile"><span class="servidor-countdown__num" data-unit="h">${pad2(p.horas)}</span><span class="servidor-countdown__unit">Hs</span></div>
            <div class="servidor-countdown__tile"><span class="servidor-countdown__num" data-unit="m">${pad2(p.mins)}</span><span class="servidor-countdown__unit">Min</span></div>
            <div class="servidor-countdown__tile"><span class="servidor-countdown__num" data-unit="s">${pad2(p.segs)}</span><span class="servidor-countdown__unit">Seg</span></div>
          </div>
        </div>`;
    } else {
      lanzamientoHtml = `
        <div class="servidor-countdown">
          <span class="servidor-countdown__label">Lanzamiento inminente</span>
        </div>`;
    }
  }

  const imagenUrl  = s.imagen_url ? safeUrl(s.imagen_url) : null;
  const mediaClase = imagenUrl ? '' : ' servidor-card__media--noimg';
  const mediaStyle = imagenUrl ? ` style="background-image:url('${esc(imagenUrl)}')"` : '';

  const dot = estado.clase === 'is-activo' ? '<span class="servidor-badge__dot"></span>' : '';

  const descargasUrl = (!proximo && s.web_url) ? subUrl('downloads/', s.web_url) : null;

  const entrarBtn = (proximo || !url)
    ? `<span class="btn btn-primary servidor-card__cta is-disabled" aria-disabled="true">Próximamente</span>`
    : `<a href="${esc(url)}" class="btn btn-primary servidor-card__cta">Entrar</a>`;

  const descargasBtn = descargasUrl
    ? `<a href="${esc(descargasUrl)}" class="btn btn-secondary servidor-card__cta">Descargas</a>`
    : '';

  return `
    <article class="servidor-card ${estado.clase}">
      <div class="servidor-card__media${mediaClase}">
        <div class="servidor-card__media-bg"${mediaStyle}></div>
        <span class="servidor-badge ${estado.clase}">${dot}${esc(estado.label)}</span>
        <h3 class="servidor-card__name">${esc(s.nombre)}</h3>
      </div>
      <div class="servidor-card__body">
        ${s.descripcion ? `<p class="servidor-card__desc">${esc(s.descripcion)}</p>` : ''}
        ${lanzamientoHtml}
        <div class="servidor-specs">${specsHtml}</div>
        <div class="servidor-card__actions">${entrarBtn}${descargasBtn}</div>
      </div>
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

// Tick por segundo: son a lo sumo un par de cards en "próximo lanzamiento"
// a la vez, así que el costo de refrescar 4 fichas por segundo es nulo.
let countdownTimer = null;
function iniciarCountdowns() {
  if (countdownTimer) clearInterval(countdownTimer);

  const nodos = document.querySelectorAll('[data-lanzamiento]');
  if (nodos.length === 0) return;

  countdownTimer = setInterval(() => {
    let activos = 0;
    document.querySelectorAll('[data-lanzamiento]').forEach(nodo => {
      const restante = msRestantes(nodo.dataset.lanzamiento);
      if (!restante) return;
      activos++;
      const p = partesTiempo(restante);
      const set = (unit, val) => {
        const el = nodo.querySelector(`[data-unit="${unit}"]`);
        if (el) el.textContent = pad2(val);
      };
      set('d', p.dias);
      set('h', p.horas);
      set('m', p.mins);
      set('s', p.segs);
    });
    if (activos === 0) clearInterval(countdownTimer);
  }, 1000);
}

document.addEventListener('DOMContentLoaded', () => {
  loadServidores();
  document.getElementById('servidor-retry')?.addEventListener('click', loadServidores);
});
