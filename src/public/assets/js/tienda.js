/* ============================================================
   MuPGA — tienda.js
   Catálogo público de la Tienda WCoin (Etapa 2 — solo lectura,
   el botón Comprar todavía no hace nada, falta el endpoint de compra).
   Depende de app.js (apiFetch, esc, BASE).
   ============================================================ */

// La descripción viene con "#" como separador de línea (formato del
// CashShop in-game) — se parte en items en vez de mostrar todo junto.
function tiendaDescLines(desc) {
  return String(desc ?? '')
    .split('#')
    .map(s => s.trim())
    .filter(Boolean);
}

function renderTiendaCard(p) {
  const lines = tiendaDescLines(p.description);
  const desc = lines.length
    ? `<ul class="tienda-card__desc">${lines.map(l => `<li>${esc(l)}</li>`).join('')}</ul>`
    : '';

  return `
    <div class="tienda-card">
      <img class="tienda-card__icon" src="${BASE}/assets/img/${esc(p.icon_path)}" alt="" loading="lazy">
      <div class="tienda-card__title">${esc(p.name)}</div>
      ${desc}
      <div class="tienda-card__footer">
        <span class="tienda-card__price">🪙 ${p.price_wcoin.toLocaleString('es-AR')}</span>
        <button class="btn btn-primary btn-sm" disabled title="Próximamente">Comprar</button>
      </div>
    </div>`;
}

function renderTienda(categories, products) {
  const container = document.getElementById('tienda-container');

  if (!products.length) {
    container.innerHTML = '<p class="state-message">No hay productos disponibles todavía.</p>';
    return;
  }

  const catName = new Map(categories.map(c => [c.category_id, c.name]));
  const byCategory = new Map();
  for (const p of products) {
    const key = p.category_id ?? 0;
    if (!byCategory.has(key)) byCategory.set(key, []);
    byCategory.get(key).push(p);
  }

  let html = '';
  for (const [catId, items] of byCategory) {
    html += `<h2 class="tienda-category">${esc(catName.get(catId) ?? 'Otros')}</h2>`;
    html += `<div class="card-grid card-grid--3">${items.map(renderTiendaCard).join('')}</div>`;
  }
  container.innerHTML = html;
}

async function loadTiendaCatalog() {
  const data = await apiFetch('tienda/catalog.php');
  const container = document.getElementById('tienda-container');

  if (!data || !data.products) {
    container.innerHTML = '<p class="state-message">Error al cargar el catálogo.</p>';
    return;
  }

  renderTienda(data.categories ?? [], data.products);
}

document.addEventListener('DOMContentLoaded', loadTiendaCatalog);
