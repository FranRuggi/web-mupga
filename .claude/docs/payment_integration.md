# Integración con API de Pagos — MuPGA Tienda WCoin

> Documentación técnica completa de la feature `/donate` rediseñada como conversor exchange.
> **Fecha de implementación:** 2026-06-09

---

## Resumen del sistema

La tienda WCoin conecta el frontend con una API externa de pagos mediante un flujo en dos partes:

- **GETs directos** (currencies, quote, providers): el browser llama a la API externa sin pasar por PHP — pero `quote` y `providers` requieren rol `Player` (JWT), así que antes de cada uno el browser pide un JWT de pagos corto al sitio (ver "Paso 2.5" más abajo). El GET de `currencies` (lista base, sin `/quote`) no lo requiere.
- **POST de orden**: el browser llama a un **proxy PHP** (`/api/donate/order.php`) que inyecta `Account` desde el JWT y reenvía a la API externa. El cliente nunca puede falsificar el campo `Account`.

```
Browser → GET /api/currencies                          → API Externa (sin auth)
Browser → GET /api/donate/payment_token.php             → PHP (arma un JWT de pagos)
Browser → GET /api/currencies/quote    (Bearer JWT)     → API Externa
Browser → GET /api/payments/providers  (Bearer JWT)     → API Externa
Browser → POST /api/donate/order.php   → PHP Proxy (arma su propio JWT) → POST /api/orders → API Externa
```

**Importante:** el JWT que usa el browser para `quote`/`providers` y el que arma `order.php` para el POST son **dos tokens distintos**, generados por separado (cada uno con su propio `iat`/`exp` de 15 min) — no hay que asumir que es "el mismo token reusado".

---

## Archivos creados / modificados

| Archivo | Tipo | Descripción |
|---|---|---|
| `.env.example` | Config | Nueva variable `PAYMENTS_API_URL` |
| `src/templates/layout.php` | Backend | Inyecta `data-payments-url` en `<html>` |
| `src/public/assets/js/config.js` | Frontend | `MUPGA_CONFIG.paymentsApi` |
| `src/public/api/donate/order.php` | Backend | Proxy POST de órdenes |
| `src/public/api/donate/payment_token.php` | Backend | Emite JWT de pagos para los GETs directos (2026-07-27) |
| `src/public/donate/index.php` | Frontend | UI exchange rediseñada |
| `src/public/assets/js/donate.js` | Frontend | Lógica completa del exchange |
| `src/public/assets/css/main.css` | CSS | Estilos exchange + post-pago |
| `src/public/donate/success/index.php` | Frontend | Página pago exitoso |
| `src/public/donate/error/index.php` | Frontend | Página pago fallido |
| `build.php` | Build | success/error agregadas al build estático |

---

## Paso 1 — Configuración (`PAYMENTS_API_URL`)

### Backend (VPS)
En `.env` del VPS, agregar:
```
PAYMENTS_API_URL=https://pagos-api.mupga.com.ar
```
- Sin barra final.
- Usado por el proxy PHP (`order.php`) para reenviar POSTs.
- Inyectado en `data-payments-url` del `<html>` para que el JS haga los GETs directos.

### Frontend (Cloudflare Pages)
`config.js` intenta leer `data-payments-url` primero (VPS all-in-one).
Si no está (HTML estático en Pages), usa la URL hardcodeada:
```js
return 'https://pagos-api.mupga.com.ar'; // editar antes del push a Pages
```
Editar esa línea antes de buildear para Pages.

---

## Paso 2 — Proxy PHP `/api/donate/order.php`

**Seguridad clave:** el campo `Account` nunca viene del cliente. Se extrae del JWT vía `requireAuth()` (`$auth['usr']` = `memb___id` de la cuenta).

### Flujo interno
1. Verifica que sea POST.
2. Llama `requireAuth()` → valida JWT, extrae `$auth['usr']`.
3. Parsea el body JSON del cliente.
4. Sobrescribe `body['Account'] = $auth['usr']`.
5. Hace curl POST a `PAYMENTS_API_URL/api/orders` con timeout 15s.
6. Devuelve la respuesta de la API externa tal cual (código HTTP + body).

### Errores propios del proxy
| Código | Situación |
|---|---|
| 405 | No es POST |
| 400 | Body inválido |
| 401 | JWT ausente o inválido (devuelto por `requireAuth()`) |
| 503 | `PAYMENTS_API_URL` vacío en `.env` o curl falló |

---

## Paso 2.5 — `/api/donate/payment_token.php` (agregado 2026-07-27)

**Por qué existe:** `GET /api/currencies/quote` y `GET /api/payments/providers` requieren rol
`Player` (JWT) en la API externa. Como son GETs que el browser llama directo (sin pasar por
PHP), el browser no tiene forma de firmar un JWT él solo — necesita que el sitio le entregue
uno ya firmado. Sin esto, ambos GETs devuelven 401 (bug real detectado el 27/07: el frontend
nunca mandaba ningún `Authorization` en esas llamadas).

- Endpoint propio, `requireAuth()` (token de sesión del sitio) + `TokenService::generatePaymentJWT()`
  — el mismo generador que ya usaba `order.php`, con el mismo TTL de 15 min.
- Devuelve `{ "token": "<jwt>" }`.
- `donate.js` (`getPaymentToken()`) lo pide una vez por cada click en "Calcular" y reusa el
  mismo token para el GET de `quote` y el de `providers` inmediatamente después (no hace
  falta pedir uno nuevo para cada llamada dentro del mismo cálculo).
- El JWT que arma `order.php` para el POST final es **otro**, generado por separado en ese
  mismo momento — no hay que asumir que es el mismo token reusado de punta a punta.
- `GET /api/currencies` (la lista base, sin `/quote`) no requiere este token — no está
  documentada como protegida y no mostró el bug.

---

## Paso 3 — UI Exchange (`donate/index.php` + `donate.js`)

### Flujo de estados

```
CARGANDO monedas
    ↓ error o colección vacía
  ──→ [TIENDA NO DISPONIBLE] (mensaje amigable, form oculto)

    ↓ ok: se pueblan ambos desplegables
  [LISTO]
    → usuario elige From + To + Cantidad
    → se habilita botón "Calcular"

    ↓ click Calcular
  [COTIZANDO]
    → GET /api/currencies/quote
    → muestra resultado en card To
    → GET /api/payments/providers
    → muestra desplegable de proveedores
    
    ↓ usuario cambia moneda o monto → vuelve a [LISTO] (cotización invalidada)

  [CON COTIZACIÓN]
    → usuario selecciona proveedor
    → si ConvertedAmount > MaxAmount: warning, Comprar deshabilitado
    → si ConvertedAmount <= MaxAmount: Comprar habilitado

    ↓ click Comprar
  [COMPRANDO]
    → POST /api/donate/order.php

    ↓ 201  → redirect a redirectionUrl
    ↓ 4XX  → muestra Message + Details (mensajes de la API)
    ↓ 5XX  → "intentá más tarde"
```

### Constante `PAYMENTS_API`
```js
const PAYMENTS_API = (MUPGA_CONFIG?.paymentsApi ?? '').replace(/\/$/, '');
```
Si está vacía, la tienda muestra "no disponible" y no hace ninguna llamada.

### Invalidación de cotización
Cualquier cambio en `sel-from`, `sel-to` o `inp-amount` llama a `invalidateQuote()` que:
- Resetea `_quote = null` y `_providers = []`
- Oculta sección de proveedores, warning y error de compra
- Deshabilita botón Comprar
- Limpia `quoted-amount` a `—`

---

## Paso 3.5 — Código de descuento (`discountCode`)

Campo opcional agregado el 2026-07-27, siguiendo el contrato documentado por el equipo de la
API externa (`GetPaymentProviders`/`QuoteCurrency`/`CreateOrder`).

- **Input:** `#inp-discount` en `donate/index.php`. Cambiar el valor invalida la cotización
  vigente (mismo patrón que moneda/monto — `invalidateQuote()`).
- **Cotización:** si hay código cargado, se agrega `&discountCode=` al `GET
  /api/currencies/quote`. La respuesta trae `baseAmount`, `finalAmount`, `applyDiscount` y
  `discountPercentage`. Si `applyDiscount` es `true`, la UI muestra el precio original
  tachado + el final + un badge `-N%`. Si el usuario cargó un código pero `applyDiscount`
  vino en `false`, se muestra un aviso (`#discount-hint`) sin bloquear la compra (simplemente
  no se aplica descuento).
- **Orden:** `discountCode` sólo se agrega al body de `POST /api/donate/order.php` (y de ahí
  al proxy hacia la API externa) si la última cotización tuvo `applyDiscount: true`. Si el
  código no aplicó, se omite el campo por completo — la API espera que se omita cuando no
  se va a aplicar un descuento.
- **Importante:** un código de descuento privado se valida contra la cuenta del JWT y no
  puede reutilizarse en una orden que no haya sido cancelada (409 `Resource Conflict` si ya
  se usó). El proxy PHP no valida nada de esto — es responsabilidad de la API externa.

### Fix de acompañamiento — mapeo de campos de la cotización

Al implementar el descuento se detectó que `donate.js` leía `ConvertedAmount`/`convertedAmount`
de la respuesta de `/api/currencies/quote`, campo que **no existe** en el contrato real de la
API (los campos reales son `baseAmount`/`finalAmount`). Se corrigió el mapeo completo
(`_quote.FinalAmount` reemplaza a `_quote.ConvertedAmount` en todos los usos: `quoted-amount`,
`quote-result`, `canBuy()`, `onProviderChange()`). También se sacó `QuoteCurrencyAmount` del
body de `CreateOrder` — la API recalcula el importe y el contrato pide explícitamente no
enviar un precio calculado localmente. Y se agregó parseo de errores en formato
`{title, statusCode, errors: [...]}` (el real de la API externa) además del `{Message,
Details}` propio del proxy PHP, para que los mensajes de error (incluidos los de descuento
inválido/ya usado) se muestren correctamente en vez de caer siempre al mensaje genérico.

## Paso 4 — Manejo de errores

| Escenario | Comportamiento |
|---|---|
| GET /currencies falla o devuelve vacío | Mensaje "tienda no disponible", form oculto |
| GET /quote devuelve 4XX/5XX | Error bajo el botón Calcular |
| GET /providers devuelve vacío | "No hay medios de pago disponibles para esta moneda" |
| ConvertedAmount > MaxAmount del proveedor | Warning naranja/dorado bajo el desplegable; Comprar deshabilitado |
| POST /order → 201 | Redirect a `redirectionUrl` |
| POST /order → 4XX | Muestra `Message` + lista `Details` de la API |
| POST /order → 5XX | "No se pudo procesar la compra. Intentá nuevamente más tarde." |
| JWT expirado en POST | `authFetch` redirige a `/login/?expired=1` automáticamente |

---

## Paso 5 — Páginas post-pago

Las URLs deben estar configuradas en la API externa como `successUrl` y `errorUrl` al momento de crear la orden. Estas páginas sólo necesitan existir y ser accesibles.

| URL | Archivo | Descripción |
|---|---|---|
| `/donate/success/` | `donate/success/index.php` | Pago procesado, WCoin en camino, contacto si tarda >30min |
| `/donate/error/` | `donate/error/index.php` | Pago fallido, contactar admins por Discord/WhatsApp |
| `/donate/transferencia/` | `donate/transferencia/index.php` | Medio de pago = transferencia bancaria (agregado 2026-07-27, ver detalle abajo) |

Ambas (success/error) usan el layout estándar del sitio. El CTA "Ver mi cuenta" / "Volver a la tienda" construye el href con `data-base-url` para ser compatible con Cloudflare Pages.

Aplican también al flujo de Promociones (Paso 6) — no hay páginas post-pago separadas para
promociones, comparten `successUrl`/`errorUrl` con la compra personalizada.

### `/donate/transferencia/` — pago por transferencia bancaria

No es un resultado de pago (no confirma ni rechaza nada) — es una pantalla intermedia que le
pide al jugador que mande el comprobante, porque la transferencia se acredita manualmente.

- **Pendiente de configurar en la API externa:** el `paymentUrl` que devuelve `POST
  /api/orders` cuando el `PaymentProviderId` elegido es el de "Transferencia Bancaria" tiene
  que apuntar acá (`https://mupga.com.ar/donate/transferencia/`), igual que `successUrl`/
  `errorUrl` para el resto de los medios de pago. Sin esa configuración del lado de la API,
  esta página nunca se muestra.
- Si la API agrega el id de la orden como query param (`?orderId=...`, o variantes
  `OrderId`/`id`), la página lo toma con JS y lo usa para: (a) mostrarlo en pantalla como
  referencia, y (b) incluirlo en el mensaje pre-cargado del botón de reclamo. Si no llega
  ningún id, la página funciona igual, solo que sin esa referencia.
- Dos botones, ninguno bloquea al otro (el jugador elige el que le resulte más cómodo):
  - **WhatsApp** — mismo link de comunidad que ya usa `/donate/error/`
    (`https://chat.whatsapp.com/DqaUqom63aFALaBsK2l7of`).
  - **Generar reclamo de compra** — arma el link a `/reclamos/?mensaje=...` con un texto
    pre-cargado ("Hola, hice una compra de WCoins por transferencia bancaria..."). Requiere
    el fix de `reclamos.js` que agrega soporte al query param `?mensaje=` (pre-carga el
    textarea del form nuevo; antes solo existía `?ver=id` para abrir un ticket existente).

---

## Paso 6 — Promociones (agregado 2026-08-02)

Segunda modalidad de compra: paquetes con precio fijo (ej. "6.000 WC por 5.000 ARS"), sin
cotización ni código de descuento. Ver contrato completo en el Anexo más abajo.

### UI (`donate/index.php` + `donate.js`)

`store-shell` agrupa un selector de dos pestañas (`store-tabs`: "Compra personalizada" /
"Promociones") arriba de los paneles existentes. El campo de email (`#inp-email`) se movió
fuera de `#exchange-main` para quedar **compartido** entre ambas modalidades — ambos flujos
mandan `userEmail` y no tenía sentido duplicar el input.

- `#panel-personalizada`: contiene exactamente lo que ya existía (`#store-status` +
  `#exchange-main`), sin cambios de lógica.
- `#panel-promociones`: nuevo — `#promo-status` (mensajes de carga/vacío/error, mismo patrón
  que `#store-status`) + `#promo-grid` (grilla de tarjetas, poblada por JS).
- `switchTab()` alterna `hidden` entre paneles. Las promociones se cargan **lazy**: recién al
  entrar por primera vez a la pestaña (`_promotionsLoaded` evita refetch en cada click de tab).

### GET /api/promotions/active

Mismo mecanismo de auth que `quote`/`providers`: pide un JWT corto vía `getPaymentToken()`
(`payment_token.php`, ya existía — no hizo falta un endpoint nuevo para esto) y lo adjunta como
Bearer. `normalizePromotion()` acepta camelCase/PascalCase indistintamente (mismo patrón
defensivo que `loadCurrencies()`/`loadProviders()`), incluyendo el array anidado
`paymentProviders`.

### Tarjetas (`buildPromoCard()`)

Por cada promoción se decide el estado inicial del botón "Comprar" según la cantidad de
proveedores permitidos (regla del contrato):

| `Providers.length` | Render | Botón inicial |
|---|---|---|
| 0 | texto "no hay medios de pago disponibles" | deshabilitado (permanente, no hay forma de habilitarlo) |
| 1 | texto estático con el nombre del proveedor | habilitado |
| 2+ | `<select>` de proveedores | deshabilitado hasta elegir uno (`onPromoProviderChange`) |

El grid usa **delegación de eventos** (`$promoGrid.addEventListener('change'/'click', ...)`)
en lugar de listeners por tarjeta, porque `promo-grid.innerHTML` se re-genera completo en cada
`loadPromotions()`.

### POST — proxy `src/public/api/donate/promotion_order.php`

Mismo patrón de seguridad que `order.php`: `requireAuth()` + `account` forzado desde
`$auth['usr']`, nunca desde el body del cliente. Diferencia con `order.php`: el `promotionId`
viaja en el **path** de la API externa (`POST /api/promotions/{promotionId}/orders`), no en el
body — el proxy lo toma del body del cliente, lo usa para armar la URL con
`rawurlencode()` y lo **remueve** del body antes de reenviar (`unset($body['promotionId'])`),
porque el contrato de la API no lo espera ahí.

Body que manda `donate.js` al proxy: `{ promotionId, paymentProviderId, userEmail }`. Nada de
`baseCurrency`, `baseCurrencyAmount`, `quoteCurrency` ni `discountCode` — el contrato de
promociones no los usa.

### Manejo de errores y 409 (mejora que también alcanza al flujo personalizado)

Al escribir esto se detectó que ni `order.php`/`onBuy()` ni el nuevo flujo manejaban el
`409 Conflict` documentado en el Anexo ("una cuenta no puede tener más de una orden activa").
Se agregó `buildOrderErrorHtml(status, errData)` en `donate.js`, compartido entre `onBuy()` y
`onBuyPromotion()`, con mensaje específico para 409. Es la única modificación que este paso
hizo sobre el flujo de compra personalizada — el resto de `onBuy()` quedó igual.

Además, ante un error de negocio (4XX que no sea 409) al crear una orden de promoción, se
resetea `_promotionsLoaded = false` para forzar un refetch la próxima vez que se entre a la
pestaña — la promoción o el proveedor pudieron cambiar de estado entre que se cargó la grilla y
que se apretó "Comprar", tal como pide el contrato ("ante error de proveedor o promoción,
refrescar el listado activo antes de permitir reintento"). No se re-renderiza la grilla en el
momento para no taparle al usuario el mensaje de error que se acaba de mostrar en la tarjeta.

### Pendiente

- No hay panel Admin para crear/editar/deshabilitar promociones en este repo — el contrato lo
  reserva explícitamente para un panel Admin separado (ver Anexo). Las promociones se gestionan
  del lado de la API externa.
- Sin endpoint Player de estado de orden (ver Anexo, "Estados de orden") — mismo límite que ya
  tenía la compra personalizada, no es específico de promociones.

---

## CORS

Los GETs directos (`/api/currencies`, `/api/currencies/quote`, `/api/payments/providers`, `/api/promotions/active`) van desde el browser a la API externa. **La API externa debe tener CORS habilitado** para el origen del frontend:

- Desarrollo: `http://localhost`
- Producción: `https://mupga.com.ar` (o el dominio de Pages)

El POST a `/api/donate/order.php` va al VPS (mismo origen o con CORS del VPS), no a la API externa directamente.

---

## Pendientes al activar la integración

1. Setear `PAYMENTS_API_URL` en `.env` del VPS.
2. Confirmar con el proveedor de la API que CORS está habilitado para el frontend.
3. Configurar en la API externa las URLs de redirección post-pago:
   - Success: `https://mupga.com.ar/donate/success/`
   - Error:   `https://mupga.com.ar/donate/error/`
4. Si se usa Cloudflare Pages, actualizar la URL hardcodeada en `config.js` antes de buildear.

---

## Anexo — Contrato oficial de la API externa de pagos (doc recibida 2026-08-02)

> Documento entregado por el equipo de la API de pagos, dirigido originalmente a un agente de
> frontend genérico ("página principal"). Se incorpora acá porque describe el contrato completo
> que ya consume `/donate` (Pasos 1 a 3.5) y el que agregó el flujo de **promociones**
> (Paso 6, implementado el 2026-08-02).

### Convenciones generales de la API

- Serializa en **camelCase**, usa **UUID** como identificadores, JSON en request/response.
- Valores monetarios son **decimales**: no redondear, no recalcular cotizaciones ni alterar
  importes recibidos en el frontend.
- Endpoints marcados **Player** requieren `Authorization: Bearer <JWT>` — en este sitio ese JWT
  se pide vía `payment_token.php` (ver Paso 2.5), nunca se firma en el browser.
- El valor de `account` enviado al crear una orden debe pertenecer al JWT; la API vuelve a
  validarlo del lado del servidor. `order.php` va más estricto: ni siquiera deja que el body del
  cliente influya — sobrescribe el campo directamente desde `$auth['usr']`.
- Los mensajes de error de la API son reglas de negocio y se deben mostrar al usuario tal cual
  (`extractApiErrors()` en `donate.js` ya contempla el formato `{title, statusCode, errors}`).
- **Una cuenta no puede tener más de una orden activa** (`Pending` o `Approved`). Ante
  `409 Conflict` hay que bloquear la compra nueva e informar que la orden existente debe
  terminarse/cancelarse — **no implementado hoy**: ni `donate.js` ni `order.php` manejan un 409
  de forma especial, cae al mensaje genérico de error de compra vía `extractApiErrors()`.

### Flujo 1 — Compra personalizada (ya implementado)

| Endpoint | Auth | Uso | Implementado en |
|---|---|---|---|
| `GET /api/currencies` | ninguna | listar monedas Game/Fiat/Crypto | `donate.js` → `loadCurrencies()` |
| `GET /api/payments/providers?currency=` | Player | proveedores para la moneda de pago elegida | `donate.js` → `loadProviders()` |
| `GET /api/currencies/quote` | Player | cotizar monto + validar código de descuento | `donate.js` → `onCalculate()` |
| `POST /api/orders` | Player | crear orden personalizada | proxy `src/public/api/donate/order.php` |

**Diferencias detectadas contra el código actual — a verificar, no corregidas acá:**

- El contrato documenta los query params de `/api/currencies/quote` como `baseCurrency` /
  `quoteCurrency` (camelCase); `donate.js:323-326` los manda en minúsculas
  (`basecurrency`, `quotecurrency`). Probablemente tolerado por binding case-insensitive del
  lado .NET (viene funcionando en producción), pero no confirmado — si se toca ese código,
  alinear el casing de una.
- El contrato documenta el body de `POST /api/orders` en camelCase (`account`, `baseCurrency`,
  `baseCurrencyAmount`, `quoteCurrency`, `paymentProviderId`, `discountCode`); el código actual
  (`donate.js:465-474` y `order.php:43`) manda `Account`, `BaseCurrency`, `BaseCurrencyAmount`,
  `QuoteCurrency`, `PaymentProviderId` en PascalCase, con `userEmail`/`discountCode` sí en
  camelCase. Mismo caso: probablemente tolerado por deserialización case-insensitive, pero es
  una inconsistencia real dentro del propio código, no solo contra el doc.
- El contrato indica `baseCurrencyAmount` entre `1000` y `1000000`; el frontend impone un tope
  más chico de `100000` (`donate.js:238` y `onCalculate()`). Puede ser una restricción de
  producto intencional (UI más conservadora) — confirmar con Franco si el límite de 1.000.000
  debería habilitarse o si el de 100.000 es a propósito antes de tocarlo.

### Flujo 2 — Promociones (implementado 2026-08-02 — ver Paso 6 arriba)

Paquete reutilizable con precio fijo (ej. "6.000 WC por 5.000 ARS"), no depende de la
cotización vigente ni de códigos de descuento.

| Endpoint | Auth | Uso | Implementado en |
|---|---|---|---|
| `GET /api/promotions/active` | Player | listar promociones habilitadas + proveedores que pueden procesarlas ahora mismo | `donate.js` → `loadPromotions()` |
| `POST /api/promotions/{promotionId}/orders` | Player | crear orden desde una promoción (solo `account` + `paymentProviderId` + `userEmail` en el body) | proxy `src/public/api/donate/promotion_order.php` |

Reglas de negocio respetadas por la implementación:

- Precio y cantidad salen **solo** de la respuesta de la API — nunca calculados ni cacheados en
  el frontend.
- Si la promoción tiene un único proveedor habilitado, se preselecciona; si tiene varios, se
  muestra un `<select>`; si no tiene ninguno, la tarjeta queda sin botón habilitable.
- El body de creación de orden **no** lleva `baseCurrency`, `baseCurrencyAmount`,
  `quoteCurrency` ni `discountCode` — solo `userEmail`, `account` (forzado por el proxy desde el
  JWT, igual que en el flujo personalizado) y `paymentProviderId`.
- Ante error de proveedor/promoción (4XX que no sea 409), se invalida el caché local
  (`_promotionsLoaded = false`) para refrescar `GET /api/promotions/active` la próxima vez que
  se entre a la pestaña, en vez de permitir reintentar sobre datos vencidos.
- La creación de la orden va por el proxy PHP `src/public/api/donate/promotion_order.php`, que
  inyecta `account` desde el JWT — calcado del patrón de `order.php`, el cliente nunca puede
  falsificar ese campo.

### Estados de orden

`Pending`, `Approved`, `Rejected`, `Cancelled`, `Delivered`, `Expired`. La aprobación del pago y
la acreditación de moneda ocurren de forma asíncrona (webhook del proveedor → workers de la API
externa) — el frontend nunca debe declarar "moneda acreditada" solo por haber creado la orden o
abierto `paymentUrl`.

**No existe hoy un endpoint Player para consultar detalle/estado de una orden por `orderId`.**
Por eso `/donate/success/` y `/donate/error/` son pantallas estáticas que no confirman nada — si
en algún momento se necesita seguimiento real del estado, hay que pedirle al equipo de la API un
endpoint Player nuevo validado contra la cuenta del JWT. **Nunca** reusar los endpoints
administrativos de abajo para eso.

### Endpoints que este sitio NO debe invocar desde el frontend público

Reservados para un futuro panel Admin — no usar desde `/donate` ni desde ningún proxy PHP
público de este repo:

- `GET /api/orders`, `GET /api/orders/{id}`, `POST /api/orders/{id}/cancel`,
  `POST /api/orders/{id}/payments/retry`
- `POST /api/payments/manual`, `GET /api/payments`, `GET /api/payments/status`
- `POST /api/payments/webhook/mercado-pago` (callback del proveedor, no del navegador)
- CRUD administrativo de promociones (crear/editar/deshabilitar paquetes)
