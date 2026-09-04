/* ============================================================
   MuPGA — apertura.js
   Pantalla de cuenta regresiva de la gran apertura.

   Se carga SÍNCRONO al inicio del <body> (antes de .page-wrapper) para que
   la pantalla monte sin que se llegue a ver el sitio detrás.

   Config: window.MUPGA_APERTURA, inyectada por layout.php desde
   src/config/apertura.php (fuente única de verdad). Si no está o está
   apagada, este archivo no hace absolutamente nada (fail-open: preferimos
   sitio abierto de más que sitio tapado sin poder destaparlo).

   Horario: el objetivo es un instante UTC absoluto, así que cada visitante
   ve el mismo momento traducido a su zona horaria — mismo criterio que el
   prode. Encima se corrige contra el reloj del server (/api/site/hora.php)
   por si el visitante tiene mal el reloj del sistema.

   El bloqueo real es server-side (src/lib/AperturaGate.php); esta pantalla
   es la cara visible y la disuasión casual.
   ============================================================ */
(function () {
  'use strict';

  var CFG = window.MUPGA_APERTURA;
  if (!CFG || !CFG.activa || !CFG.objetivo_ms) return;

  var OBJETIVO = Number(CFG.objetivo_ms);
  if (!isFinite(OBJETIVO)) return;

  // Cortafuegos anti-bucle: una vez que esta pestaña vio la apertura, no
  // vuelve a tapar el sitio aunque el reloj del visitante diga otra cosa.
  var YA_ABRIO = 'mupga_apertura_abierta';
  try { if (sessionStorage.getItem(YA_ABRIO)) return; } catch (e) { /* modo privado */ }

  // ── Bases de URL ─────────────────────────────────────────────
  // apertura.js corre antes que app.js, así que no puede usar sus helpers.
  var phpBase = (document.documentElement.dataset.baseUrl || '').replace(/\/$/, '');
  var BASE    = phpBase;
  var API     = phpBase
    ? phpBase + '/api'
    : ((typeof MUPGA_CONFIG !== 'undefined' && MUPGA_CONFIG.api) ? MUPGA_CONFIG.api + '/api' : '/api');

  // ── Reloj: offset contra el server ───────────────────────────
  // Se cachea en la pestaña para que la primera decisión (¿tapo o no?) del
  // próximo pageview salga ya corregida, sin esperar el fetch.
  var OFFSET_KEY = 'mupga_apertura_offset';
  var offset = 0;
  try { offset = Number(sessionStorage.getItem(OFFSET_KEY)) || 0; } catch (e) {}

  function ahora()    { return Date.now() + offset; }
  function restante() { return OBJETIVO - ahora(); }

  // Ya pasó la hora → el sitio está abierto, no hay nada que hacer.
  if (restante() <= 0) return;

  function sincronizarReloj() {
    var t0 = Date.now();
    fetch(API + '/site/hora.php', { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d || !d.epoch_ms) return;
        var rtt = Date.now() - t0;
        offset = d.epoch_ms + rtt / 2 - Date.now();
        try { sessionStorage.setItem(OFFSET_KEY, String(Math.round(offset))); } catch (e) {}
        pintar();
      })
      .catch(function () { /* sin server: se usa el reloj del visitante */ });
  }

  // ── Formato ──────────────────────────────────────────────────
  function dos(n) { return n < 10 ? '0' + n : String(n); }

  function partes(ms) {
    var s = Math.max(0, Math.floor(ms / 1000));
    return {
      d: Math.floor(s / 86400),
      h: Math.floor(s % 86400 / 3600),
      m: Math.floor(s % 3600 / 60),
      s: s % 60
    };
  }

  // "jueves, 4 de septiembre 21:00" en la zona horaria del visitante
  function horaLocal() {
    try {
      return new Date(OBJETIVO).toLocaleString(undefined, {
        weekday: 'long', day: 'numeric', month: 'long',
        hour: '2-digit', minute: '2-digit'
      });
    } catch (e) {
      return new Date(OBJETIVO).toString();
    }
  }

  function zonaLocal() {
    try { return Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) { return ''; }
  }

  function escapar(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // ── Páginas de rescate ───────────────────────────────────────
  // /register/ es a dónde manda el botón, así que no puede estar tapada.
  // /login/ y /controlpanel/ quedan libres para poder administrar el sitio
  // (mismo criterio que el overlay de mantenimiento en app.js: si tapáramos
  // el panel, quedaría el candado cerrado con la llave adentro).
  var path      = window.location.pathname;
  var esRescate = path.indexOf('/register') !== -1
               || path.indexOf('/login') !== -1
               || path.indexOf('/controlpanel') !== -1;

  var nodos = {};

  // ── Franja para las páginas de rescate ───────────────────────
  function montarFranja() {
    var b = document.createElement('div');
    b.className = 'site-status-banner apertura-franja';
    b.innerHTML = '<strong>Gran apertura</strong> MuPGA abre en ' +
                  '<span class="apertura-franja__t">—</span>';
    document.body.insertBefore(b, document.body.firstChild);
    nodos.franja = b.querySelector('.apertura-franja__t');
  }

  // ── Pantalla completa ────────────────────────────────────────
  function montarPantalla() {
    document.documentElement.classList.add('apertura-bloqueada');

    var gate = document.createElement('div');
    gate.className = 'apertura-gate';
    gate.setAttribute('role', 'dialog');
    gate.setAttribute('aria-modal', 'true');
    gate.setAttribute('aria-label', 'Gran apertura de MuPGA');
    gate.innerHTML =
      '<div class="apertura-gate__bg" style="background-image:url(' + BASE + '/assets/img/slider/hero-apertura.jpg)"></div>' +
      '<div class="apertura-gate__veil"></div>' +
      '<div class="apertura-gate__inner">' +
        '<p class="apertura-gate__eyebrow">Gran apertura · Season 6</p>' +
        '<h1 class="apertura-gate__logo">Mu<span>PGA</span></h1>' +
        '<p class="apertura-gate__lead">El servidor abre en</p>' +
        '<div class="apertura-gate__clock">' +
          bloque('d', 'Días') + bloque('h', 'Horas') + bloque('m', 'Min') + bloque('s', 'Seg') +
        '</div>' +
        '<p class="apertura-gate__abierto" hidden>¡Estamos abiertos!</p>' +
        '<p class="apertura-gate__hora"></p>' +
        '<a class="apertura-gate__cta" href="' + BASE + '/register/">Registrate</a>' +
        '<p class="apertura-gate__nota">Creá tu cuenta ahora y entrá a jugar apenas abramos.</p>' +
        '<div class="apertura-gate__social">' +
          '<a href="https://discord.com/invite/xTxFHSmVhf" target="_blank" rel="noopener">Discord</a>' +
          '<a href="https://chat.whatsapp.com/DqaUqom63aFALaBsK2l7of" target="_blank" rel="noopener">WhatsApp</a>' +
        '</div>' +
      '</div>';

    document.body.appendChild(gate);

    nodos.gate    = gate;
    nodos.lead    = gate.querySelector('.apertura-gate__lead');
    nodos.horaTxt = gate.querySelector('.apertura-gate__hora');
    nodos.nota    = gate.querySelector('.apertura-gate__nota');
    nodos.d       = gate.querySelector('[data-u="d"]');
    nodos.h       = gate.querySelector('[data-u="h"]');
    nodos.m       = gate.querySelector('[data-u="m"]');
    nodos.s       = gate.querySelector('[data-u="s"]');
    nodos.dBox    = gate.querySelector('[data-box="d"]');
    nodos.clock   = gate.querySelector('.apertura-gate__clock');
    nodos.abierto = gate.querySelector('.apertura-gate__abierto');

    var tz = zonaLocal();
    gate.querySelector('.apertura-gate__hora').innerHTML =
      '<span>' + escapar(horaLocal()) + '</span>' +
      '<small>en tu zona horaria' + (tz ? ' (' + escapar(tz) + ')' : '') + ' · 21:00 en Argentina</small>';

    // Guardián: si borran el div desde DevTools, vuelve. Es disuasión casual
    // — el bloqueo real es server-side (src/lib/AperturaGate.php).
    var guard = new MutationObserver(function () {
      if (!document.body.contains(gate)) document.body.appendChild(gate);
      if (!document.documentElement.classList.contains('apertura-bloqueada')) {
        document.documentElement.classList.add('apertura-bloqueada');
      }
    });
    guard.observe(document.body, { childList: true });
    guard.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
  }

  function bloque(u, label) {
    return '<div class="apertura-block" data-box="' + u + '">' +
             '<span class="apertura-block__n" data-u="' + u + '">--</span>' +
             '<span class="apertura-block__l">' + label + '</span>' +
           '</div>';
  }

  // ── Tick ─────────────────────────────────────────────────────
  var timer = null;

  function pintar() {
    var ms = restante();
    if (ms <= 0) { abrir(); return; }

    var p = partes(ms);

    if (nodos.franja) {
      nodos.franja.textContent =
        (p.d > 0 ? p.d + 'd ' : '') + dos(p.h) + ':' + dos(p.m) + ':' + dos(p.s);
    }

    if (nodos.gate) {
      if (nodos.dBox) nodos.dBox.hidden = p.d === 0;
      nodos.d.textContent = dos(p.d);
      nodos.h.textContent = dos(p.h);
      nodos.m.textContent = dos(p.m);
      nodos.s.textContent = dos(p.s);
    }
  }

  // Llegó la hora: se abre solo, sin deploy ni intervención.
  function abrir() {
    if (timer) { clearInterval(timer); timer = null; }
    try { sessionStorage.setItem(YA_ABRIO, '1'); } catch (e) {}

    if (nodos.franja) nodos.franja.textContent = '¡ya!';
    if (!nodos.gate) return;

    // Estado abierto: se va todo lo que hable de espera y queda el anuncio
    // + el botón, hasta que la recarga muestre el sitio ya destapado.
    nodos.clock.hidden   = true;
    nodos.lead.hidden    = true;
    nodos.horaTxt.hidden = true;
    nodos.abierto.hidden = false;
    nodos.nota.textContent = 'Entrando al sitio…';

    // Recarga escalonada: si todos los que miran el contador recargan en el
    // mismo segundo, la API se come el pico entero. Un jitter de hasta 12s
    // lo reparte.
    setTimeout(function () { window.location.reload(); }, 1500 + Math.random() * 12000);
  }

  // ── Arranque ─────────────────────────────────────────────────
  function iniciar() {
    if (esRescate) montarFranja(); else montarPantalla();
    pintar();
    timer = setInterval(pintar, 1000);
    sincronizarReloj();
    // Resync periódico: una pestaña abierta horas puede quedar a la deriva
    // (suspensión del equipo, reloj corregido por NTP a mitad de camino).
    setInterval(sincronizarReloj, 10 * 60 * 1000);
  }

  if (document.body) iniciar();
  else document.addEventListener('DOMContentLoaded', iniciar);
})();
