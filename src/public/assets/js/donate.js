/* ============================================================
   MuPGA — donate.js
   Tienda WCoin — conversor estilo exchange.

   GETs  → API de pagos directa (currencies, quote, providers)
   POST  → proxy PHP /api/donate/order.php (inyecta JWT y Account)

   Depende de: config.js (MUPGA_CONFIG.paymentsApi)
               app.js    (BASE, API, esc)
               auth.js   (isAuthenticated, authFetch)
   ============================================================ */

const PAYMENTS_API = (MUPGA_CONFIG?.paymentsApi ?? '').replace(/\/$/, '');

const PAYMENTS_HEADERS = {
  Accept: 'application/json',
};

// ── Estado ───────────────────────────────────────────────────
let _quote     = null;   // { CurrencyCode, BaseAmount, FinalAmount, ApplyDiscount, DiscountPercentage, DiscountCode }
let _providers = [];
let _promotions       = [];  // [{ Id, Name, GameCurrencyCode, GameCurrencyAmount, PaymentCurrencyCode, PaymentAmount, Providers:[...] }]
let _promotionsLoaded = false;

// ── Refs DOM (se resuelven en DOMContentLoaded) ──────────────
let $status, $exchangeMain,
    $selFrom, $inpAmount, $selTo, $quotedAmt,
    $btnCalc, $quoteResult,
    $provSection, $selProvider, $provWarn,
    $btnBuy, $buyError,
    $inpEmail, $amountLimitWarn,
    $inpDiscount, $discountHint,
    $tabPersonalizada, $tabPromociones,
    $panelPersonalizada, $panelPromociones,
    $promoStatus, $promoGrid, $inpEmailPromo;

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
  $inpDiscount     = document.getElementById('inp-discount');
  $discountHint    = document.getElementById('discount-hint');
  $tabPersonalizada   = document.getElementById('tab-personalizada');
  $tabPromociones     = document.getElementById('tab-promociones');
  $panelPersonalizada = document.getElementById('panel-personalizada');
  $panelPromociones   = document.getElementById('panel-promociones');
  $promoStatus        = document.getElementById('promo-status');
  $promoGrid          = document.getElementById('promo-grid');
  $inpEmailPromo      = document.getElementById('inp-email-promo');

  $selFrom.addEventListener('change', onCurrencyChange);
  $selTo.addEventListener('change', onCurrencyChange);
  $inpAmount.addEventListener('input', onAmountInput);
  $btnCalc.addEventListener('click', onCalculate);
  $selProvider.addEventListener('change', onProviderChange);
  $btnBuy.addEventListener('click', onBuy);
  $inpEmail.addEventListener('input', onEmailInput);
  $inpDiscount.addEventListener('input', onDiscountInput);
  $tabPersonalizada.addEventListener('click', () => switchTab('personalizada'));
  $tabPromociones.addEventListener('click', () => switchTab('promociones'));
  $promoGrid.addEventListener('change', onPromoProviderChange);
  $promoGrid.addEventListener('click', onPromoGridClick);

  await loadCurrencies();
});

// ── Selector de modalidad (tabs) ───────────────────────────────
function switchTab(tab) {
  const isPersonal = tab === 'personalizada';
  $tabPersonalizada.classList.toggle('active', isPersonal);
  $tabPromociones.classList.toggle('active', !isPersonal);
  $tabPersonalizada.setAttribute('aria-selected', String(isPersonal));
  $tabPromociones.setAttribute('aria-selected', String(!isPersonal));
  $panelPersonalizada.hidden = !isPersonal;
  $panelPromociones.hidden = isPersonal;

  if (!isPersonal && !_promotionsLoaded) {
    loadPromotions();
  }
}

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
  $discountHint.hidden = true;
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
  if (parseFloat(_quote.FinalAmount) > parseFloat(provider.MaxAmount)) return false;
  return isValidEmail(($inpEmail?.value || '').trim());
}

function isValidEmail(v) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
}

// Normaliza el formato de error de la API externa ({title, errors:[...]})
// y el del proxy PHP propio ({Message, Details:[...]}) a una sola lista de mensajes.
function extractApiErrors(errData) {
  if (Array.isArray(errData?.errors) && errData.errors.length) {
    return errData.errors;
  }
  if (errData?.Message) {
    return [errData.Message, ...(Array.isArray(errData.Details) ? errData.Details : [])];
  }
  return [];
}

// GET /api/currencies/quote y /api/payments/providers requieren rol Player —
// piden un JWT de pagos corto (mismo mecanismo que usa order.php) para adjuntar
// como Bearer en esos GETs directos a la API externa.
async function getPaymentToken() {
  const res = await authFetch('donate/payment_token.php');
  if (!res || !res.ok) return null;
  const data = await res.json().catch(() => ({}));
  return data.token ?? null;
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

function onDiscountInput() {
  invalidateQuote();
  updateCalcBtn();
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

  const discountCode = ($inpDiscount?.value || '').trim();
  $discountHint.hidden = true;

  try {
    const paymentToken = await getPaymentToken();
    if (!paymentToken) {
      throw new Error('No se pudo autenticar contra la API de pagos. Volvé a intentar.');
    }

    let url = PAYMENTS_API + '/api/currencies/quote' +
      '?basecurrency=' + encodeURIComponent(from) +
      '&amount='       + amount +
      '&quotecurrency=' + encodeURIComponent(to);
    if (discountCode) {
      url += '&discountCode=' + encodeURIComponent(discountCode);
    }

    const res = await fetch(url, {
      headers: { ...PAYMENTS_HEADERS, Authorization: 'Bearer ' + paymentToken },
    });

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      const msgs = extractApiErrors(err);
      throw new Error(msgs[0] || ('Error ' + res.status));
    }

    const raw = await res.json();
    _quote = {
      FinalAmount:        raw.finalAmount ?? raw.FinalAmount,
      BaseAmount:         raw.baseAmount  ?? raw.BaseAmount,
      CurrencyCode:       raw.currencyCode ?? raw.CurrencyCode,
      ApplyDiscount:      raw.applyDiscount ?? raw.ApplyDiscount ?? false,
      DiscountPercentage: raw.discountPercentage ?? raw.DiscountPercentage ?? null,
      DiscountCode:       discountCode || null,
    };

    $quotedAmt.textContent = fmtAmount(_quote.FinalAmount, _quote.CurrencyCode);

    $quoteResult.hidden = false;
    let resultHtml =
      '<span>' + amount.toLocaleString('es-AR') + ' ' + esc(from) + '</span>' +
      '<span class="quote-equals">=</span>';
    if (_quote.ApplyDiscount) {
      resultHtml +=
        '<span class="quote-discount-original">' + fmtAmount(_quote.BaseAmount, _quote.CurrencyCode) + '</span>' +
        '<strong>' + fmtAmount(_quote.FinalAmount, _quote.CurrencyCode) + '</strong>' +
        '<span class="quote-discount-badge">-' + esc(_quote.DiscountPercentage) + '%</span>';
    } else {
      resultHtml += '<strong>' + fmtAmount(_quote.FinalAmount, _quote.CurrencyCode) + '</strong>';
      if (discountCode) {
        $discountHint.hidden = false;
        $discountHint.textContent = 'El código ingresado no pudo aplicarse a esta compra.';
      }
    }
    $quoteResult.innerHTML = resultHtml;

    await loadProviders(to, paymentToken);

  } catch (err) {
    showBuyError('No se pudo obtener la cotización: ' + esc(err.message));
    $quotedAmt.textContent = '—';
  } finally {
    $btnCalc.disabled    = false;
    $btnCalc.textContent = 'Calcular';
  }
}

// ── Paso 5 — Cargar proveedores de pago ───────────────────────
async function loadProviders(currency, paymentToken) {
  try {
    const res = await fetch(
      PAYMENTS_API + '/api/payments/providers?currency=' + encodeURIComponent(currency),
      { headers: { ...PAYMENTS_HEADERS, Authorization: 'Bearer ' + paymentToken } }
    );

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      const msgs = extractApiErrors(err);
      throw new Error(msgs[0] || ('Error ' + res.status));
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

  if (parseFloat(_quote.FinalAmount) > parseFloat(provider.MaxAmount)) {
    $provWarn.hidden = false;
    $provWarn.textContent =
      'El monto a abonar (' + fmtAmount(_quote.FinalAmount, _quote.CurrencyCode) + ') ' +
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
    BaseCurrency:       $selFrom.value,
    BaseCurrencyAmount: parseInt($inpAmount.value, 10),
    QuoteCurrency:      $selTo.value,
    PaymentProviderId:  providerId,
    userEmail:          email,
    // QuoteCurrencyAmount NO se envía: la API recalcula el importe al crear
    // la orden y no hay que enviar/confiar en un precio calculado localmente.
    // Account es inyectado por el proxy PHP desde el JWT
  };

  if (_quote.ApplyDiscount && _quote.DiscountCode) {
    body.discountCode = _quote.DiscountCode;
  }

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
    const url = data.paymentUrl ?? data.redirectionUrl ?? data.PaymentUrl ?? data.RedirectionUrl;
    if (url) {
      window.location.href = url;
    }
    return;
  }

  const errData = await res.json().catch(() => ({}));
  showBuyError(buildOrderErrorHtml(res.status, errData), true);

  $btnBuy.disabled    = false;
  $btnBuy.textContent = 'Comprar';
}

// Mensaje de error para POST /api/orders y POST /api/promotions/{id}/orders.
// 409 = ya existe una orden Pending/Approved en la cuenta (ver CLAUDE.md, contrato API pagos).
function buildOrderErrorHtml(status, errData) {
  if (status === 409) {
    return esc('Ya tenés una orden de compra activa. Terminala o esperá a que se cancele antes de generar una nueva.');
  }
  if (status >= 500) {
    return esc('No se pudo procesar la compra. Intentá nuevamente más tarde.');
  }
  const msgs = extractApiErrors(errData);
  let html = esc(msgs[0] || 'La compra no pudo procesarse correctamente.');
  if (msgs.length > 1) {
    html += '<ul>' + msgs.slice(1).map(d => '<li>' + esc(d) + '</li>').join('') + '</ul>';
  }
  return html;
}

// ── Promociones ─────────────────────────────────────────────
async function loadPromotions() {
  if (!PAYMENTS_API) {
    showPromoUnavailable('La tienda no está disponible en este momento. Volvé pronto.');
    return;
  }

  $promoStatus.hidden = true;
  $promoGrid.innerHTML = '<p class="promo-loading">Cargando promociones…</p>';

  try {
    const paymentToken = await getPaymentToken();
    if (!paymentToken) {
      throw new Error('No se pudo autenticar contra la API de pagos.');
    }

    const res = await fetch(PAYMENTS_API + '/api/promotions/active', {
      headers: { ...PAYMENTS_HEADERS, Authorization: 'Bearer ' + paymentToken },
    });

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      const msgs = extractApiErrors(err);
      throw new Error(msgs[0] || ('Error ' + res.status));
    }

    const raw = await res.json();
    const rawPromos = Array.isArray(raw) ? raw : (raw.promotions ?? raw.Promotions ?? []);
    _promotions = rawPromos.map(normalizePromotion);
    _promotionsLoaded = true;

    if (!_promotions.length) {
      showPromoUnavailable('No hay promociones activas en este momento.');
      return;
    }

    $promoGrid.innerHTML = _promotions.map(buildPromoCard).join('');

  } catch (err) {
    showPromoUnavailable('No se pudieron cargar las promociones: ' + err.message);
  }
}

function normalizePromotion(p) {
  const rawProviders = p.paymentProviders ?? p.PaymentProviders ?? [];
  return {
    Id:                  p.id ?? p.Id,
    Name:                p.name ?? p.Name,
    GameCurrencyCode:    p.gameCurrencyCode ?? p.GameCurrencyCode,
    GameCurrencyAmount:  p.gameCurrencyAmount ?? p.GameCurrencyAmount,
    PaymentCurrencyCode: p.paymentCurrencyCode ?? p.PaymentCurrencyCode,
    PaymentAmount:       p.paymentAmount ?? p.PaymentAmount,
    Providers: rawProviders.map(pr => ({
      Id:           pr.id           ?? pr.Id,
      Name:         pr.name         ?? pr.Name,
      CurrencyCode: pr.currencyCode ?? pr.CurrencyCode,
      MaxAmount:    pr.maxAmount    ?? pr.MaxAmount,
    })),
  };
}

function showPromoUnavailable(msg) {
  $promoStatus.textContent = msg;
  $promoStatus.hidden = false;
  $promoGrid.innerHTML = '';
}

function buildPromoCard(p) {
  const gameAmt  = fmtPlain(p.GameCurrencyAmount) + ' ' + esc(p.GameCurrencyCode);
  const priceAmt = fmtAmount(p.PaymentAmount, p.PaymentCurrencyCode);

  let providerHtml;
  if (p.Providers.length === 0) {
    providerHtml = '<p class="promo-card__unavailable">No hay medios de pago disponibles para esta promoción.</p>';
  } else if (p.Providers.length === 1) {
    providerHtml =
      '<p class="promo-card__provider-static">Medio de pago: <strong>' + esc(p.Providers[0].Name) + '</strong></p>';
  } else {
    providerHtml =
      '<select class="exchange-select exchange-select--full promo-card__provider-select">' +
      '<option value="">Seleccioná un medio de pago...</option>' +
      p.Providers.map(pr => '<option value="' + esc(pr.Id) + '">' + esc(pr.Name) + '</option>').join('') +
      '</select>';
  }

  // Habilitado de entrada solo si hay exactamente un proveedor (no requiere elegir nada más);
  // con 0 queda deshabilitado para siempre, con 2+ se habilita al elegir uno (onPromoProviderChange).
  const enabledNow = p.Providers.length === 1;

  return (
    '<div class="promo-card" data-promotion-id="' + esc(p.Id) + '">' +
      '<h3 class="promo-card__name">' + esc(p.Name) + '</h3>' +
      '<div class="promo-card__amounts">' +
        '<span class="promo-card__game">' + gameAmt + '</span>' +
        '<span class="promo-card__arrow">&#8594;</span>' +
        '<span class="promo-card__price">' + priceAmt + '</span>' +
      '</div>' +
      '<div class="promo-card__provider">' + providerHtml + '</div>' +
      '<button type="button" class="btn btn-primary promo-card__buy"' + (enabledNow ? '' : ' disabled') + '>Comprar</button>' +
      '<div class="promo-card__error exchange-error" hidden></div>' +
    '</div>'
  );
}

function onPromoProviderChange(e) {
  const sel = e.target.closest('.promo-card__provider-select');
  if (!sel) return;
  const card = sel.closest('.promo-card');
  card.querySelector('.promo-card__buy').disabled = !sel.value;
  card.querySelector('.promo-card__error').hidden = true;
}

function onPromoGridClick(e) {
  const btn = e.target.closest('.promo-card__buy');
  if (btn) onBuyPromotion(btn);
}

async function onBuyPromotion(btn) {
  const card = btn.closest('.promo-card');
  const promotionId = card.dataset.promotionId;
  const promo = _promotions.find(p => p.Id === promotionId);
  const errBox = card.querySelector('.promo-card__error');
  errBox.hidden = true;
  if (!promo) return;

  const email = ($inpEmailPromo?.value || '').trim();
  if (!isValidEmail(email)) {
    errBox.textContent = 'Ingresá un email válido en el campo de arriba para continuar.';
    errBox.hidden = false;
    return;
  }

  let providerId;
  if (promo.Providers.length === 1) {
    providerId = promo.Providers[0].Id;
  } else {
    providerId = card.querySelector('.promo-card__provider-select')?.value;
    if (!providerId) return;
  }

  btn.disabled = true;
  const originalText = btn.textContent;
  btn.textContent = 'Procesando…';

  const res = await authFetch('donate/promotion_order.php', {
    method: 'POST',
    body: JSON.stringify({
      promotionId,
      paymentProviderId: providerId,
      userEmail: email,
    }),
  });

  if (!res) {
    btn.disabled    = false;
    btn.textContent = originalText;
    return;
  }

  if (res.status === 201) {
    const data = await res.json().catch(() => ({}));
    const url = data.paymentUrl ?? data.redirectionUrl ?? data.PaymentUrl ?? data.RedirectionUrl;
    if (url) {
      window.location.href = url;
    }
    return;
  }

  const errData = await res.json().catch(() => ({}));
  errBox.innerHTML = buildOrderErrorHtml(res.status, errData);
  errBox.hidden = false;

  // La promoción/proveedor pudo cambiar entretanto (deshabilitada, precio distinto, etc.) —
  // forzar un recálculo la próxima vez que se entre a la pestaña, como pide el contrato de la API.
  if (res.status !== 409 && res.status < 500) {
    _promotionsLoaded = false;
  }

  btn.disabled    = false;
  btn.textContent = originalText;
}

// ── Utilidades ────────────────────────────────────────────────
function showBuyError(msg, isHtml) {
  if (isHtml) $buyError.innerHTML = msg;
  else        $buyError.textContent = msg;
  $buyError.hidden = false;
}

function fmtPlain(amount) {
  const n = parseFloat(amount);
  if (isNaN(n)) return String(amount);
  return n.toLocaleString('es-AR');
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
