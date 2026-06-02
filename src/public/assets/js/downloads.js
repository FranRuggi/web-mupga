/* ============================================================
   MuPGA — downloads.js
   Renderiza las tarjetas de descarga desde api/downloadsdata.php.
   Depende de app.js (apiFetch, esc)
   ============================================================ */

document.addEventListener('DOMContentLoaded', async () => {
  const container = document.getElementById('downloads-content');
  if (!container) return;

  const data = await apiFetch('downloadsdata.php');

  if (!data || !data.items?.length) {
    container.innerHTML = '<p class="state-message">No hay descargas disponibles por el momento.</p>';
    return;
  }

  container.innerHTML = `<div class="downloads-grid">${data.items.map(renderCard).join('')}</div>`;
});

function renderCard(item) {
  const isPending = !item.url || item.url === 'PENDIENTE';
  const sizePending = !item.size || item.size === 'PENDIENTE';

  return `
    <div class="download-card">
      <div class="download-card__icon">⬇</div>
      <div class="download-card__body">
        <h3 class="download-card__title">${esc(item.title)}</h3>
        <p class="download-card__desc">${esc(item.description)}</p>
        <div class="download-card__meta">
          ${item.version ? `<span class="download-meta-tag">v${esc(item.version)}</span>` : ''}
          ${!sizePending ? `<span class="download-meta-tag">${esc(item.size)}</span>` : ''}
        </div>
      </div>
      <div class="download-card__action">
        ${isPending
          ? `<span class="btn btn-secondary" style="opacity:0.5;cursor:not-allowed">Próximamente</span>`
          : `<a class="btn btn-primary" href="${esc(item.url)}" target="_blank" rel="noopener noreferrer">Descargar</a>`
        }
      </div>
    </div>`;
}
