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

// Headers comunes para todos los GETs a la API externa.
// ngrok-skip-browser-warning evita la interstitial de ngrok (ignorado por APIs reales).
const PAYMENTS_HEADERS = {
  Accept: 'application/json',
  'ngrok-skip-browser-warning': 'true',
};

// ── Estado ───────────────────────────────────────────────────
let _quote     = null;   // { CurrencyCode, ConvertedAmount }
let _providers = [];

// ── Refs DOM (se resuelven en DOMContentLoaded) ──────────────
let $status, $exchangeMain,
    $selFrom, $inpAmount, $selTo, $quotedAmt,
    $btnCalc, $quoteResult,
    $provSection, $selProvider, $provWarn,
    $btnBuy, $buyError,
    $inpEmail, $amountLimitWarn;

// ── Íconos de monedas ─────────────────────────────────────────
const KNOWN_ICONS = ['wc', 'ars', 'usdt'];

function currencyIconHtml(code, size) {
  const s   = size || 24;
  const key = (code || '').toLowerCase();
  if (KNOWN_ICONS.includes(key)) {
    return '<img src="' + BASE + '/assets/img/currencies/' + key + '.svg" ' +
           'alt="' + esc(code) + '" class="currency-icon" width="' + s + '" height="' + s + '">';
  }
  return '<span class="currency-icon currency-icon--initial" ' +
         'style="width:' + s + 'px;height:' + s + 'px">' +
         esc((code || '?').charAt(0).toUpperCase()) + '</span>';
}

// ── Custom picker (reemplaza <select> con íconos) ─────────────
const CHEVRON_SVG =
  '<svg class="currency-picker__chevron" viewBox="0 0 12 8" fill="none" ' +
  'xmlns="http://www.w3.org/2000/svg">' +
  '<path d="M1 1l5 5 5-5" stroke="currentColor" stroke-width="1.5" ' +
  'stroke-linecap="round" stroke-linejoin="round"/></svg>';

function buildPickerContent(code, name) {
  return currencyIconHtml(code, 24) +
    '<span class="currency-picker__name">' + esc(name) + '</span>';
}

function buildPickerOption(c) {
  return '<button type="button" class="currency-option" ' +
    'data-code="' + esc(c.code) + '" data-name="' + esc(c.name) + '">' +
    currencyIconHtml(c.code, 28) +
    '<span class="currency-option__info">' +
    '<span class="currency-option__name">' + esc(c.name) + '</span>' +
    '<span class="currency-option__code">' + esc(c.code) + '</span>' +
    '</span></button>';
}

function setupPicker(btnId, contentId, dropdownId, hiddenId, currencies) {
  const btn      = document.getElementById(btnId);
  const content  = document.getElementById(contentId);
  const dropdown = document.getElementById(dropdownId);
  const hidden   = document.getElementById(hiddenId);

  dropdown.innerHTML = currencies.map(buildPickerOption).join('');
  btn.disabled = false;

  btn.addEventListener('click', () => {
    const opening = dropdown.hidden;
    closeAllPickers();
    if (opening) {
      dropdown.hidden = false;
      btn.setAttribute('aria-expanded', 'true');
    }
  });

  dropdown.addEventListener('click', e => {
    const opt = e.target.closest('.currency-option');
    if (!opt) return;
    const code = opt.dataset.code;
    const name = opt.dataset.name;
    content.innerHTML = buildPickerContent(code, name);
    hidden.value = code;
    hidden.dispatchEvent(new Event('change'));
    dropdown.hidden = true;
    btn.removeAttribute('aria-expanded');
  });
}

function closeAllPickers() {
  document.querySelectorAll('.currency-picker__dropdown').forEach(d => {
    d.hidden = true;
  });
  document.querySelectorAll('.currency-picker__btn').forEach(b => {
    b.removeAttribute('aria-expanded');
  });
}

// Cerrar al hacer click fuera de cualquier picker
document.addEventListener('click', e => {
  if (!e.target.closest('.currency-picker')) closeAllPickers();
}, true);

// ── Entrada ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  if (!isAuthenticated()) {
    window.location.replace(
      BASE + '/login/?redirect=' + encodeURIComponent(window.location.pathname)
    );
    return;
  }

  $status          = document.getElementById('store-status');
  $exchangeMain    = document.getElementById('exchange-main');
  $selFrom         = document.getElementById('sel-from');
  $inpAmount       = document.getElementById('inp-amount');
  $selTo           = document.getElementById('sel-to');
  $quotedAmt       = document.getElementById('quoted-amount');
  $btnCalc         = document.getElementById('btn-calculate');
  $quoteResult     = document.getElementById('quote-result');
  $provSection     = document.getElementById('providers-section');
  $selProvider     = document.getElementById('sel-provider');
  $provWarn        = document.getElementById('provider-warning');
  $btnBuy          = document.getElementById('btn-buy');
  $buyError        = document.getElementById('buy-error');
  $inpEmail        = document.getElementById('inp-email');
  $amountLimitWarn = document.getElementById('amount-limit-warn');

  $selFrom.addEventListener('change', onCurrencyChange);
  $selTo.addEventListener('change', onCurrencyChange);
  $inpAmount.addEventListener('input', onAmountInput);
  $btnCalc.addEventListener('click', onCalculate);
  $selProvider.addEventListener('change', onProviderChange);
  $btnBuy.addEventListener('click', onBuy);
  $inpEmail.addEventListener('input', onEmailInput);

  await loadCurrencies();
});

// ── Paso 1 — Cargar monedas ───────────────────────────────────
async function loadCurrencies() {
  if (!PAYMENTS_API) {
    showStoreUnavailable('La tienda no está disponible en este momento. Volvé pronto.');
    return;
  }

  let data;
  try {
    const res = await fetch(PAYMENTS_API + '/api/currencies', {
      headers: PAYMENTS_HEADERS,
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const raw = await res.json();
    data = Array.isArray(raw) ? raw : (raw.currencies ?? raw.Currencies ?? []);
  } catch {
    showStoreUnavailable('La tienda no está disponible en este momento. Intentá más tarde.');
    return;
  }

  if (!Array.isArray(data) || !data.length) {
    showStoreUnavailable('La tienda no está disponible en este momento. Intentá más tarde.');
    return;
  }

  // Normalizar: acepta type/Type, code/Code, name/Name
  const norm = arr => arr.map(c => ({
    type: c.type ?? c.Type ?? '',
    code: c.code ?? c.Code ?? '',
    name: c.name ?? c.Name ?? '',
  }));
  data = norm(data);

  const gameCurrencies = data.filter(c => c.type === 'Game');
  const fiatCurrencies = data.filter(c => c.type === 'Fiat' || c.type === 'Crypto');

  if (!gameCurrencies.length || !fiatCurrencies.length) {
    showStoreUnavailable('La tienda no está disponible en este momento. Intentá más tarde.');
    return;
  }

  // Inicializar custom pickers en lugar de poblar <select>
  setupPicker('btn-picker-from', 'picker-from-content', 'dropdown-from', 'sel-from', gameCurrencies);
  setupPicker('btn-picker-to',   'picker-to-content',   'dropdown-to',   'sel-to',   fiatCurrencies);

  $inpAmount.disabled = false;

  // Si hay una sola opción en cada lado, pre-seleccionarla
  if (gameCurrencies.length === 1) {
    const c = gameCurrencies[0];
    document.getElementById('picker-from-content').innerHTML = buildPickerContent(c.code, c.name);
    $selFrom.value = c.code;
    $selFrom.dispatchEvent(new Event('change'));
  }
  if (fiatCurrencies.length === 1) {
    const c = fiatCurrencies[0];
    document.getElementById('picker-to-content').innerHTML = buildPickerContent(c.code, c.name);
    $selTo.value = c.code;
    $selTo.dispatchEvent(new Event('change'));
  }
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
  const amount = parseInt($inpAmount.value, 10);
  const over   = amount > 100000;
  $amountLimitWarn.hidden = !(over && $inpAmount.value !== '');
  $btnCalc.disabled = !(
    $selFrom.value &&
    $selTo.value   &&
    amount > 0     &&
    !over
  );
}

function canBuy() {
  if (!$selProvider.value || !_quote) return false;
  const provider = _providers.find(p => p.Id === $selProvider.value);
  if (!provider) return false;
  if (parseFloat(_quote.ConvertedAmount) > parseFloat(provider.MaxAmount)) return false;
  return isValidEmail(($inpEmail?.value || '').trim());
}

function isValidEmail(v) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
}

// ── Cambios de moneda / monto / email ─────────────────────────
function onCurrencyChange() {
  invalidateQuote();
  updateCalcBtn();
}

function onAmountInput() {
  invalidateQuote();
  updateCalcBtn();
}

function onEmailInput() {
  $btnBuy.disabled = !canBuy();
}

// ── Paso 3 — Calcular cotización ──────────────────────────────
async function onCalculate() {
  const from   = $selFrom.value;
  const to     = $selTo.value;
  const amount = parseInt($inpAmount.value, 10);
  if (!from || !to || amount <= 0 || amount > 100000) return;

  $btnCalc.disabled    = true;
  $btnCalc.textContent = 'Calculando…';
  $quotedAmt.textContent = '…';
  $buyError.hidden = true;

  try {
    const url = PAYMENTS_API + '/api/currencies/quote' +
      '?basecurrency=' + encodeURIComponent(from) +
      '&amount='       + amount +
      '&quotecurrency=' + encodeURIComponent(to);

    const res = await fetch(url, { headers: PAYMENTS_HEADERS });

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.Message || 'Error ' + res.status);
    }

    const raw = await res.json();
    _quote = {
      ConvertedAmount: raw.ConvertedAmount ?? raw.convertedAmount,
      CurrencyCode:    raw.CurrencyCode    ?? raw.currencyCode,
    };

    $quotedAmt.textContent = fmtAmount(_quote.ConvertedAmount, _quote.CurrencyCode);

    $quoteResult.hidden = false;
    $quoteResult.innerHTML =
      '<span>' + amount.toLocaleString('es-AR') + ' ' + esc(from) + '</span>' +
      '<span class="quote-equals">=</span>' +
      '<strong>' + fmtAmount(_quote.ConvertedAmount, _quote.CurrencyCode) + '</strong>';

    await loadProviders(to);

  } catch (err) {
    showBuyError('No se pudo obtener la cotización: ' + esc(err.message));
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
      PAYMENTS_API + '/api/payments/providers?currency=' + encodeURIComponent(currency),
      { headers: PAYMENTS_HEADERS }
    );

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.Message || 'Error ' + res.status);
    }

    const rawRes = await res.json();
    const rawProviders = Array.isArray(rawRes) ? rawRes : (rawRes.providers ?? rawRes.Providers ?? []);
    _providers = rawProviders.map(p => ({
      Id:           p.Id           ?? p.id,
      Name:         p.Name         ?? p.name,
      CurrencyCode: p.CurrencyCode ?? p.currencyCode,
      MaxAmount:    p.MaxAmount    ?? p.maxAmount,
    }));

    if (!_providers.length) {
      showBuyError('No hay medios de pago disponibles para esta moneda.');
      return;
    }

    $selProvider.innerHTML =
      '<option value="">Seleccioná un medio de pago...</option>' +
      _providers.map(p =>
        '<option value="' + esc(p.Id) + '">' + esc(p.Name) + '</option>'
      ).join('');

    $provSection.hidden = false;
    $btnBuy.disabled = true;

  } catch (err) {
    showBuyError('No se pudieron cargar los medios de pago: ' + esc(err.message));
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

  if (parseFloat(_quote.ConvertedAmount) > parseFloat(provider.MaxAmount)) {
    $provWarn.hidden = false;
    $provWarn.textContent =
      'El monto a abonar (' + fmtAmount(_quote.ConvertedAmount, _quote.CurrencyCode) + ') ' +
      'supera el máximo aceptado por este medio de pago ' +
      '(' + fmtAmount(provider.MaxAmount, _quote.CurrencyCode) + '). ' +
      'Reducí el monto o elegí otro medio de pago.';
    return;
  }

  $btnBuy.disabled = !canBuy();
}

// ── Paso 7 — Comprar ─────────────────────────────────────────
async function onBuy() {
  const providerId = $selProvider.value;
  const provider   = _providers.find(p => p.Id === providerId);
  if (!provider || !_quote) return;

  const email = ($inpEmail?.value || '').trim();
  if (!isValidEmail(email)) {
    showBuyError('Ingresá un email válido para continuar.');
    return;
  }

  $btnBuy.disabled    = true;
  $btnBuy.textContent = 'Procesando…';
  $buyError.hidden    = true;

  const body = {
    BaseCurrency:        $selFrom.value,
    BaseCurrencyAmount:  parseInt($inpAmount.value, 10),
    QuoteCurrency:       $selTo.value,
    QuoteCurrencyAmount: parseFloat(_quote.ConvertedAmount),
    PaymentProviderId:   providerId,
    Email:               email,
    // Account es inyectado por el proxy PHP desde el JWT
  };

  const res = await authFetch('donate/order.php', {
    method: 'POST',
    body: JSON.stringify(body),
  });

  if (!res) {
    $btnBuy.disabled    = false;
    $btnBuy.textContent = 'Comprar';
    return;
  }

  if (res.status === 201) {
    const data = await res.json().catch(() => ({}));
    if (data.redirectionUrl) {
      window.location.href = data.redirectionUrl;
    }
    return;
  }

  const errData = await res.json().catch(() => ({}));

  if (res.status >= 500) {
    showBuyError('No se pudo procesar la compra. Intentá nuevamente más tarde.');
  } else {
    let html = esc(errData.Message || 'La compra no pudo procesarse correctamente.');
    if (Array.isArray(errData.Details) && errData.Details.length) {
      html += '<ul>' + errData.Details.map(d => '<li>' + esc(d) + '</li>').join('') + '</ul>';
    }
    showBuyError(html, true);
  }

  $btnBuy.disabled    = false;
  $btnBuy.textContent = 'Comprar';
}

// ── Utilidades ────────────────────────────────────────────────
function showBuyError(msg, isHtml) {
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
