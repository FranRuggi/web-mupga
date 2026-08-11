/* ============================================================
   MuPGA — tiendas.js
   Radar de Tiendas: lista de personajes con tienda personal abierta
   ahora mismo (solo lectura). Depende de app.js (apiFetch, esc,
   className, BASE).
   ============================================================ */

const TIENDAS_REFRESH_MS = 30000;

function tiendasFmtZen(n) {
  return Number(n).toLocaleString('es-AR');
}

function renderShopCard(shop) {
  const items = shop.items.length
    ? `<ul class="shop-card__items">
        ${shop.items.map(it => `
          <li class="shop-card__item">
            <span class="shop-card__slot">Slot ${it.slot}</span>
            <span class="shop-card__price">${tiendasFmtZen(it.price)} Zen</span>
          </li>`).join('')}
      </ul>`
    : '<p class="shop-card__empty">Sin ítems publicados.</p>';

  return `
    <div class="shop-card">
      <div class="shop-card__header">
        <a class="rank-name-link shop-card__name" href="${BASE}/player/?name=${encodeURIComponent(shop.name)}">${esc(shop.name)}</a>
        <span class="shop-card__meta">${esc(className(shop.class))} · Nv${shop.level} · ${shop.resets} RST</span>
      </div>
      ${shop.store_name ? `<p class="shop-card__title">"${esc(shop.store_name)}"</p>` : ''}
      ${items}
    </div>`;
}

function renderShops(shops) {
  const container = document.getElementById('tiendas-radar-container');

  if (!shops || !shops.length) {
    container.innerHTML = '<p class="state-message">No hay tiendas abiertas en este momento.</p>';
    return;
  }

  container.innerHTML = `<div class="card-grid card-grid--3">${shops.map(renderShopCard).join('')}</div>`;
}

// Cruce hacia la Tienda WCoin (/tienda/) — esto es el Radar (solo lectura, lo que venden
// otros jugadores por Zen); la Tienda es el canje oficial contra WCoin. Se marcan bien
// distintas para que no se confundan.
function renderRadarCrossLink() {
  const el = document.getElementById('tiendas-cross-link');
  if (!el) return;
  el.innerHTML = `
    <div class="cross-link-banner cross-link-banner--gold">
      <span class="cross-link-banner__icon">🪙</span>
      <div class="cross-link-banner__text">
        <p class="cross-link-banner__title">Tienda WCoin</p>
        <p class="cross-link-banner__sub">Esto es el Radar de Tiendas de jugadores (por Zen,
          se compra in-game). Si buscás canjear tu saldo WCoin por ítems del CashShop, andá
          a la Tienda oficial.</p>
      </div>
      <a class="btn btn-primary btn-sm cross-link-banner__btn" href="${BASE}/tienda/">Ir a la Tienda →</a>
    </div>`;
}

async function loadShops() {
  const data = await apiFetch('shops.php');
  if (!data || data.error) {
    document.getElementById('tiendas-radar-container').innerHTML =
      '<p class="state-message">Error al cargar el radar de tiendas.</p>';
    return;
  }
  renderShops(data.shops ?? []);
}

document.addEventListener('DOMContentLoaded', () => {
  renderRadarCrossLink();
  loadShops();
  setInterval(loadShops, TIENDAS_REFRESH_MS);
});
