/* ============================================================
   MuPGA — tienda.js
   Catálogo + compra real de la Tienda WCoin.
   Depende de app.js (apiFetch, esc, BASE) y auth.js (authFetch,
   isAuthenticated).
   ============================================================ */

let tiendaBalance = 0; // saldo en memoria, se sincroniza tras cada compra

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
// que actualiza precio + id del producto a comprar.
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
    <div class="tienda-card" data-ids="${variants.map(v => v.id).join(',')}" data-prices="${variants.map(v => v.price_wcoin).join(',')}">
      <img class="tienda-card__icon" src="${BASE}/assets/img/${esc(first.icon_path)}" alt="" loading="lazy">
      <div class="tienda-card__title">${esc(title)}</div>
      ${desc}
      ${select}
      <div class="tienda-card__footer">
        <span class="tienda-card__price">🪙 ${tiendaFmtPrice(first.price_wcoin)}</span>
        <button class="btn btn-primary btn-sm tienda-buy-btn" data-name="${esc(title)}">Comprar</button>
      </div>
    </div>`;
}

// Selector de variante: actualiza precio mostrado. Botón Comprar: usa
// siempre el id/precio de la variante seleccionada en ese momento.
function initTiendaCards(container) {
  container.querySelectorAll('.tienda-card').forEach(card => {
    const ids     = card.dataset.ids.split(',').map(Number);
    const prices  = card.dataset.prices.split(',').map(Number);
    const select  = card.querySelector('.tienda-card__variant');
    const priceEl = card.querySelector('.tienda-card__price');
    const btn     = card.querySelector('.tienda-buy-btn');

    const idx = () => select ? Number(select.value) : 0;

    if (select) {
      select.addEventListener('change', () => {
        priceEl.textContent = `🪙 ${tiendaFmtPrice(prices[idx()])}`;
      });
    }

    btn.addEventListener('click', () => buyTiendaProduct(ids[idx()], prices[idx()], btn.dataset.name, btn));
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
  initTiendaCards(container);
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

// ── Saldo + compras pendientes (banner arriba del catálogo) ───
// El contenedor vive en tienda/index.php, oculto hasta confirmar sesión.

function tiendaBalanceFeedback(msg, isError) {
  const el = document.getElementById('tienda-balance-feedback');
  if (!el) return;
  el.textContent = msg;
  el.style.color = isError ? '#e05555' : 'var(--cyan)';
}

function renderTiendaPendingItem(item) {
  return `
    <div class="tienda-pending-item">
      <img class="tienda-pending-icon" src="${BASE}/assets/img/${esc(item.icon_path)}" alt="" loading="lazy">
      <span>${esc(tiendaSplitName(item.name).title)}</span>
    </div>`;
}

async function loadTiendaMisCompras() {
  const el = document.getElementById('tienda-mis-compras');
  if (!el) return;

  const res = await authFetch('tienda/mis_compras.php');
  if (!res) return;
  const data = await res.json();
  if (!res.ok || !data.items) return;

  el.innerHTML = data.items.length
    ? `<p class="tienda-pending-title">Pendientes de reclamar (tecla "X")</p>
       <div class="tienda-pending-list">${data.items.map(renderTiendaPendingItem).join('')}</div>`
    : '';
}

async function loadTiendaBalance() {
  const banner = document.getElementById('tienda-balance');
  if (!banner || !isAuthenticated()) return;

  const res = await authFetch('account/balance.php');
  if (!res) return; // authFetch ya redirigió a login si hacía falta

  const data = await res.json();
  if (!res.ok) return;

  tiendaBalance = data.WCoinC ?? 0;
  banner.innerHTML = `
    <div class="tienda-balance-row">
      <div>
        <p class="widget-title" style="margin-bottom:0.3rem">🪙 Tu saldo WCoin</p>
        <div class="tienda-balance-amount" id="tienda-balance-amount">${tiendaFmtPrice(tiendaBalance)}</div>
      </div>
      <div class="tienda-pending-wrap" id="tienda-mis-compras"></div>
    </div>
    <p class="cp-feedback" id="tienda-balance-feedback"></p>`;
  banner.hidden = false;

  loadTiendaMisCompras();
}

async function buyTiendaProduct(productId, price, name, btn) {
  if (!isAuthenticated()) {
    window.location.href = `${BASE}/login/?redirect=${encodeURIComponent('/tienda/')}`;
    return;
  }
  if (!confirm(`¿Comprar "${name}" por ${tiendaFmtPrice(price)} WCoin?`)) return;

  btn.disabled = true;
  const original = btn.textContent;
  btn.textContent = 'Comprando…';

  const res = await authFetch('tienda/buy.php', {
    method: 'POST',
    body: JSON.stringify({ product_id: productId }),
  });

  btn.disabled = false;
  btn.textContent = original;

  if (!res) return; // authFetch ya redirigió si hacía falta

  const data = await res.json();
  if (!res.ok || !data.success) {
    tiendaBalanceFeedback(data?.error ?? 'Error al comprar.', true);
    return;
  }

  tiendaBalance = data.nuevo_balance;
  document.getElementById('tienda-balance-amount').textContent = tiendaFmtPrice(tiendaBalance);
  tiendaBalanceFeedback(data.message, false);
  loadTiendaMisCompras();
}

// Cruce hacia el Radar de Tiendas (/tiendas/) — esto es la Tienda WCoin (canje contra
// el CashShop oficial); el Radar es un catálogo aparte de lo que venden otros jugadores
// en sus tiendas personales. Se marcan bien distintas para que no se confundan.
function renderTiendaCrossLink() {
  const el = document.getElementById('tienda-cross-link');
  if (!el) return;
  el.innerHTML = `
    <div class="cross-link-banner cross-link-banner--cyan">
      <span class="cross-link-banner__icon">🔎</span>
      <div class="cross-link-banner__text">
        <p class="cross-link-banner__title">Radar de Tiendas</p>
        <p class="cross-link-banner__sub">Esto es la Tienda WCoin. Si buscás lo que venden
          otros jugadores en sus tiendas personales (Zen, no WCoin), mirá el Radar.</p>
      </div>
      <a class="btn btn-secondary btn-sm cross-link-banner__btn" href="${BASE}/tiendas/">Ver Radar →</a>
    </div>`;
}

document.addEventListener('DOMContentLoaded', () => {
  renderTiendaCrossLink();
  loadTiendaBalance();
  loadTiendaCatalog();
});
