# Integración con API de Pagos — MuPGA Tienda WCoin

> Documentación técnica completa de la feature `/donate` rediseñada como conversor exchange.
> **Fecha de implementación:** 2026-06-09

---

## Resumen del sistema

La tienda WCoin conecta el frontend con una API externa de pagos mediante un flujo en dos partes:

- **GETs directos** (currencies, quote, providers): el browser llama a la API externa sin pasar por PHP.
- **POST de orden**: el browser llama a un **proxy PHP** (`/api/donate/order.php`) que inyecta `Account` desde el JWT y reenvía a la API externa. El cliente nunca puede falsificar el campo `Account`.

```
Browser → GET /api/currencies          → API Externa
Browser → GET /api/currencies/quote    → API Externa
Browser → GET /api/payments/providers  → API Externa
Browser → POST /api/donate/order.php   → PHP Proxy → POST /api/orders → API Externa
```

---

## Archivos creados / modificados

| Archivo | Tipo | Descripción |
|---|---|---|
| `.env.example` | Config | Nueva variable `PAYMENTS_API_URL` |
| `src/templates/layout.php` | Backend | Inyecta `data-payments-url` en `<html>` |
| `src/public/assets/js/config.js` | Frontend | `MUPGA_CONFIG.paymentsApi` |
| `src/public/api/donate/order.php` | Backend | Proxy POST de órdenes |
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

Ambas usan el layout estándar del sitio. El CTA "Ver mi cuenta" / "Volver a la tienda" construye el href con `data-base-url` para ser compatible con Cloudflare Pages.

---

## CORS

Los GETs directos (`/api/currencies`, `/api/currencies/quote`, `/api/payments/providers`) van desde el browser a la API externa. **La API externa debe tener CORS habilitado** para el origen del frontend:

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
