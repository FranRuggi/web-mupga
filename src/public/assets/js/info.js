/* ============================================================
   MuPGA — info.js
   Renderiza la página Info del servidor desde /api/infodata.php
   (que a su vez sirve data/info.json del repo)

   Para agregar o quitar secciones: editá data/info.json.
   Tipos de sección soportados: "tabla"
   ============================================================ */

// ── Renderers por tipo de sección ────────────────────────────

function renderTabla(section) {
  const columnas  = section.columnas ?? [];
  const cols      = columnas.length || 2;
  const vipCol    = section.vip_col ?? -1;
  const isCommandSection = section.id === 'comandos';

  // Encabezado solo para tablas comparativas (3+ columnas)
  const header = cols >= 3 ? `
    <div class="cmd-item cmd-header">
      ${columnas.map((col, i) =>
        i === vipCol
          ? `<span class="col-vip">${esc('✦ ' + col)}</span>`
          : `<span>${esc(col)}</span>`
      ).join('')}
    </div>` : '';

  const rows = section.filas.map(fila => {
    const cells = Array.from({ length: cols }, (_, i) => {
      const val = fila[i] ?? '';
      let cls;
      if (i === 0) {
        cls = isCommandSection || String(val).startsWith('/') ? 'cmd-name' : 'info-key';
      } else if (i === vipCol) {
        cls = 'col-vip-val';
      } else {
        cls = 'cmd-desc';
      }
      return `<span class="${cls}">${esc(val)}</span>`;
    }).join('');
    return `<div class="cmd-item">${cells}</div>`;
  }).join('');

  return `<div class="cmd-list" style="--cmd-cols:${cols}">${header}${rows}</div>`;
}

// ── Renderer principal ────────────────────────────────────────

function renderSection(section) {
  let body = '';

  switch (section.tipo) {
    case 'tabla':
      body = renderTabla(section);
      break;
    default:
      body = `<p class="state-message">Tipo de sección desconocido: ${esc(section.tipo)}</p>`;
  }

  return `
    <div class="info-section animate-in" id="section-${esc(section.id)}">
      <p class="info-eyebrow">${esc(section.eyebrow)}</p>
      <h2 class="info-section-title">${esc(section.titulo)}</h2>
      ${body}
    </div>`;
}

// ── Carga y renderiza ─────────────────────────────────────────

async function loadInfoData() {
  const container = document.getElementById('info-content');
  if (!container) return;

  // Skeleton mientras carga
  container.innerHTML = `
    <div class="info-section">
      <div class="skeleton" style="height:1rem;width:80px;margin-bottom:0.5rem;border-radius:4px"></div>
      <div class="skeleton" style="height:1.5rem;width:200px;margin-bottom:var(--gap-md);border-radius:4px"></div>
      ${Array.from({length: 5}, () =>
        '<div class="skeleton" style="height:2.5rem;margin-bottom:0.5rem;border-radius:8px"></div>'
      ).join('')}
    </div>`;

  const data = await apiFetch('infodata.php');

  if (!data || !data.secciones) {
    container.innerHTML = '<p class="state-message">No se pudo cargar la información del servidor.</p>';
    return;
  }

  container.innerHTML = data.secciones.map(renderSection).join('');
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', loadInfoData);
