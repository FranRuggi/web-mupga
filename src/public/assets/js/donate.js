/* ============================================================
   MuPGA — donate.js
   Tienda WCoin — conversor estilo exchange.

   GETs  → API externa directa (currencies, quote, providers)
   POST  → proxy PHP /api/donate/order.php (inyecta Account del JWT)

   Depende de: config.js (MUPGA_CONFIG.paymentsApi)
               app.js    (BASE, API, esc)
               auth.js   (isAuthenticated, authFetch)
   ============================================================ */

const PAYMENTS_API = (MUPGA_CONFIG?.paymentsApi ?? '').replace(/\/$/, '');

// ── Estado ───────────────────────────────────────────────────
let _quote     = null;   // { CurrencyCode, ConvertedAmount }
let _providers = [];

// ── Refs DOM (se resuelven en DOMContentLoaded) ──────────────
let $status, $exchangeMain,
    $selFrom, $inpAmount, $selTo, $quotedAmt,
    $btnCalc, $quoteResult,
    $provSection, $selProvider, $provWarn,
    $btnBuy, $buyError;

// ── Entrada ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  if (!isAuthenticated()) {
    window.location.replace(
      `${BASE}/login/?redirect=${encodeURIComponent(window.location.pathname)}`
    );
    return;
  }

  $status       = document.getElementById('store-status');
  $exchangeMain = document.getElementById('exchange-main');
  $selFrom      = document.getElementById('sel-from');
  $inpAmount    = document.getElementById('inp-amount');
  $selTo        = document.getElementById('sel-to');
  $quotedAmt    = document.getElementById('quoted-amount');
  $btnCalc      = document.getElementById('btn-calculate');
  $quoteResult  = document.getElementById('quote-result');
  $provSection  = document.getElementById('providers-section');
  $selProvider  = document.getElementById('sel-provider');
  $provWarn     = document.getElementById('provider-warning');
  $btnBuy       = document.getElementById('btn-buy');
  $buyError     = document.getElementById('buy-error');

  $selFrom.addEventListener('change', onCurrencyChange);
  $selTo.addEventListener('change', onCurrencyChange);
  $inpAmount.addEventListener('input', onAmountInput);
  $btnCalc.addEventListener('click', onCalculate);
  $selProvider.addEventListener('change', onProviderChange);
  $btnBuy.addEventListener('click', onBuy);

  await loadCurrencies();
});

// Headers comunes para todos los GETs a la API externa.
// ngrok-skip-browser-warning evita la interstitial de ngrok (ignorado por APIs reales).
const PAYMENTS_HEADERS = {
  Accept: 'application/json',
  'ngrok-skip-browser-warning': 'true',
};

// ── Paso 1 — Cargar monedas ───────────────────────────────────
async function loadCurrencies() {
  if (!PAYMENTS_API) {
    showStoreUnavailable('La tienda no está disponible en este momento. Volvé pronto.');
    return;
  }

  let data;
  try {
    const res = await fetch(`${PAYMENTS_API}/api/currencies`, {
      headers: PAYMENTS_HEADERS,
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    data = await res.json();
  } catch {
    showStoreUnavailable('La tienda no está disponible en este momento. Intentá más tarde.');
    return;
  }

  // Paso 1.1 — colección vacía o error
  if (!Array.isArray(data) || !data.length) {
    showStoreUnavailable('La tienda no está disponible en este momento. Intentá más tarde.');
    return;
  }

  const gameCurrencies = data.filter(c => c.Type === 'Game');
  const fiatCurrencies = data.filter(c => c.Type === 'Fiat' || c.Type === 'Crypto');

  // Paso 2.2 — sin monedas suficientes para ambos desplegables
  if (!gameCurrencies.length || !fiatCurrencies.length) {
    showStoreUnavailable('La tienda no está disponible en este momento. Intentá más tarde.');
    return;
  }

  // Poblar desplegables
  $selFrom.innerHTML = '<option value="">Seleccioná moneda...</option>' +
    gameCurrencies.map(c =>
      `<option value="${esc(c.Code)}">${esc(c.Name)} (${esc(c.Code)})</option>`
    ).join('');

  $selTo.innerHTML = '<option value="">Seleccioná destino...</option>' +
    fiatCurrencies.map(c =>
      `<option value="${esc(c.Code)}">${esc(c.Name)} (${esc(c.Code)})</option>`
    ).join('');

  $selFrom.disabled  = false;
  $selTo.disabled    = false;
  $inpAmount.disabled = false;
}

// ── Helpers de estado ─────────────────────────────────────────
function showStoreUnavailable(msg) {
  $status.textContent = msg;
  $status.hidden = false;
  $exchangeMain.hidden = true;
}

function invalidateQuote() {
  if (!_quote) return;
  _quote = null;
  _providers = [];
  $quotedAmt.textContent = '—';
  $quoteResult.hidden = true;
  $provSection.hidden = true;
  $provWarn.hidden = true;
  $btnBuy.disabled = true;
  $buyError.hidden = true;
  $selProvider.innerHTML = '<option value="">Seleccioná un medio de pago...</option>';
}

function updateCalcBtn() {
  $btnCalc.disabled = !(
    $selFrom.value &&
    $selTo.value &&
    parseInt($inpAmount.value, 10) > 0
  );
}

// ── Paso 3 — Cambio de moneda o monto invalida cotización ─────
function onCurrencyChange() {
  invalidateQuote();
  updateCalcBtn();
}

function onAmountInput() {
  invalidateQuote();
  updateCalcBtn();
}

// ── Paso 3 — Calcular cotización ──────────────────────────────
async function onCalculate() {
  const from   = $selFrom.value;
  const to     = $selTo.value;
  const amount = parseInt($inpAmount.value, 10);
  if (!from || !to || amount <= 0) return;

  $btnCalc.disabled    = true;
  $btnCalc.textContent = 'Calculando…';
  $quotedAmt.textContent = '…';
  $buyError.hidden = true;

  try {
    const url = `${PAYMENTS_API}/api/currencies/quote` +
      `?basecurrency=${encodeURIComponent(from)}` +
      `&amount=${amount}` +
      `&quotecurrency=${encodeURIComponent(to)}`;

    const res = await fetch(url, { headers: PAYMENTS_HEADERS });

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.Message || `Error ${res.status}`);
    }

    const data = await res.json();
    _quote = data;

    // Paso 4 — mostrar monto cotizado
    $quotedAmt.textContent = fmtAmount(data.ConvertedAmount, data.CurrencyCode);

    $quoteResult.hidden = false;
    $quoteResult.innerHTML =
      `<span>${amount.toLocaleString('es-AR')} ${esc(from)}</span>` +
      `<span class="quote-equals">=</span>` +
      `<strong>${fmtAmount(data.ConvertedAmount, data.CurrencyCode)}</strong>`;

    // Paso 5 — cargar proveedores
    await loadProviders(to);

  } catch (err) {
    showBuyError(`No se pudo obtener la cotización: ${esc(err.message)}`);
    $quotedAmt.textContent = '—';
  } finally {
    $btnCalc.disabled    = false;
    $btnCalc.textContent = 'Calcular';
  }
}

// ── Paso 5 — Cargar proveedores de pago ───────────────────────
async function loadProviders(currency) {
  try {
    const res = await fetch(
      `${PAYMENTS_API}/api/payments/providers?currency=${encodeURIComponent(currency)}`,
      { headers: PAYMENTS_HEADERS }
    );

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.Message || `Error ${res.status}`);
    }

    _providers = await res.json();

    if (!_providers.length) {
      showBuyError('No hay medios de pago disponibles para esta moneda.');
      return;
    }

    $selProvider.innerHTML =
      '<option value="">Seleccioná un medio de pago...</option>' +
      _providers.map(p =>
        `<option value="${esc(p.Id)}">${esc(p.Name)}</option>`
      ).join('');

    $provSection.hidden = true; // se muestra en onProviderChange vía CSS, pero la sección ya aparece
    $provSection.hidden = false;
    $btnBuy.disabled = true;

  } catch (err) {
    showBuyError(`No se pudieron cargar los medios de pago: ${esc(err.message)}`);
  }
}

// ── Paso 6 — Validar proveedor y MaxAmount ────────────────────
function onProviderChange() {
  $provWarn.hidden = true;
  $btnBuy.disabled = true;
  $buyError.hidden = true;

  const providerId = $selProvider.value;
  if (!providerId || !_quote) return;

  const provider = _providers.find(p => p.Id === providerId);
  if (!provider) return;

  // Paso 6.1 — supera el máximo del proveedor
  if (parseFloat(_quote.ConvertedAmount) > parseFloat(provider.MaxAmount)) {
    $provWarn.hidden = false;
    $provWarn.textContent =
      `El monto a abonar (${fmtAmount(_quote.ConvertedAmount, _quote.CurrencyCode)}) ` +
      `supera el máximo aceptado por este medio de pago ` +
      `(${fmtAmount(provider.MaxAmount, _quote.CurrencyCode)}). ` +
      `Reducí el monto o elegí otro medio de pago.`;
    return;
  }

  $btnBuy.disabled = false;
}

// ── Paso 6 — Comprar ─────────────────────────────────────────
async function onBuy() {
  const providerId = $selProvider.value;
  const provider   = _providers.find(p => p.Id === providerId);
  if (!provider || !_quote) return;

  $btnBuy.disabled    = true;
  $btnBuy.textContent = 'Procesando…';
  $buyError.hidden    = true;

  const body = {
    BaseCurrency:        $selFrom.value,
    BaseCurrencyAmount:  parseInt($inpAmount.value, 10),
    QuoteCurrency:       $selTo.value,
    QuoteCurrencyAmount: parseFloat(_quote.ConvertedAmount),
    PaymentProviderId:   providerId,
    // Account es inyectado por el proxy PHP desde el JWT
  };

  const res = await authFetch('donate/order.php', {
    method: 'POST',
    body: JSON.stringify(body),
  });

  // authFetch devuelve null si hubo 401 (ya redirigió a login)
  if (!res) {
    $btnBuy.disabled    = false;
    $btnBuy.textContent = 'Comprar';
    return;
  }

  // Paso 7 — 201 Created → redirigir
  if (res.status === 201) {
    const data = await res.json().catch(() => ({}));
    if (data.redirectionUrl) {
      window.location.href = data.redirectionUrl;
    }
    return;
  }

  const errData = await res.json().catch(() => ({}));

  // Paso 7.2 — error 5XX
  if (res.status >= 500) {
    showBuyError('No se pudo procesar la compra. Intentá nuevamente más tarde.');
  } else {
    // Paso 7.1 — error 4XX: mostrar Message + Details
    let html = esc(errData.Message || 'La compra no pudo procesarse correctamente.');
    if (Array.isArray(errData.Details) && errData.Details.length) {
      html += '<ul>' + errData.Details.map(d => `<li>${esc(d)}</li>`).join('') + '</ul>';
    }
    showBuyError(html, true);
  }

  $btnBuy.disabled    = false;
  $btnBuy.textContent = 'Comprar';
}

// ── Utilidades ────────────────────────────────────────────────
function showBuyError(msg, isHtml = false) {
  if (isHtml) $buyError.innerHTML = msg;
  else        $buyError.textContent = msg;
  $buyError.hidden = false;
}

function fmtAmount(amount, code) {
  const n = parseFloat(amount);
  if (isNaN(n)) return '—';
  const decimals = n < 0.01 ? 8 : n < 1 ? 4 : 2;
  return n.toLocaleString('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: decimals,
  }) + ' ' + esc(code);
}
