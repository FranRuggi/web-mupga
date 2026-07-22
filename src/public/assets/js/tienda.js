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

// "Spirit of Guardian (7 Days)" → { title: "Spirit of Guardian", variant: "7 Days" }
function tiendaSplitName(name) {
  const m = String(name ?? '').match(/^(.*?)\s*\(([^)]*)\)\s*$/);
  return m ? { title: m[1], variant: m[2] } : { title: name, variant: '' };
}

function tiendaFmtPrice(n) {
  return Number(n).toLocaleString('es-AR');
}

// Una tarjeta por product_base_index — si tiene más de 1 variante
// (ej. duraciones distintas del mismo ítem), se muestran en un <select>
// que actualiza el precio mostrado.
function renderTiendaCard(variants) {
  const first = variants[0];
  const { title } = tiendaSplitName(first.name);
  const lines = tiendaDescLines(first.description);
  const desc = lines.length
    ? `<ul class="tienda-card__desc">${lines.map(l => `<li>${esc(l)}</li>`).join('')}</ul>`
    : '';

  const select = variants.length > 1
    ? `<select class="exchange-select tienda-card__variant">
        ${variants.map((v, i) => `<option value="${i}">${esc(tiendaSplitName(v.name).variant || v.name)}</option>`).join('')}
      </select>`
    : '';

  return `
    <div class="tienda-card" data-prices="${variants.map(v => v.price_wcoin).join(',')}">
      <img class="tienda-card__icon" src="${BASE}/assets/img/${esc(first.icon_path)}" alt="" loading="lazy">
      <div class="tienda-card__title">${esc(title)}</div>
      ${desc}
      ${select}
      <div class="tienda-card__footer">
        <span class="tienda-card__price">🪙 ${tiendaFmtPrice(first.price_wcoin)}</span>
        <button class="btn btn-primary btn-sm" disabled title="Próximamente">Comprar</button>
      </div>
    </div>`;
}

// El <select> de variante actualiza el precio mostrado en su propia card
function initTiendaVariantSelects(container) {
  container.querySelectorAll('.tienda-card').forEach(card => {
    const select = card.querySelector('.tienda-card__variant');
    if (!select) return;
    const prices = card.dataset.prices.split(',').map(Number);
    const priceEl = card.querySelector('.tienda-card__price');
    select.addEventListener('change', () => {
      priceEl.textContent = `🪙 ${tiendaFmtPrice(prices[Number(select.value)])}`;
    });
  });
}

function renderTienda(categories, products) {
  const container = document.getElementById('tienda-container');

  if (!products.length) {
    container.innerHTML = '<p class="state-message">No hay productos disponibles todavía.</p>';
    return;
  }

  const catName = new Map(categories.map(c => [c.category_id, c.name]));

  // category_id → product_base_index → [variantes]
  const byCategory = new Map();
  for (const p of products) {
    const catKey = p.category_id ?? 0;
    if (!byCategory.has(catKey)) byCategory.set(catKey, new Map());
    const byBase = byCategory.get(catKey);
    if (!byBase.has(p.product_base_index)) byBase.set(p.product_base_index, []);
    byBase.get(p.product_base_index).push(p);
  }

  let html = '';
  for (const [catId, byBase] of byCategory) {
    html += `<h2 class="tienda-category">${esc(catName.get(catId) ?? 'Otros')}</h2>`;
    html += `<div class="card-grid card-grid--3">`;
    html += [...byBase.values()].map(renderTiendaCard).join('');
    html += `</div>`;
  }
  container.innerHTML = html;
  initTiendaVariantSelects(container);
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
