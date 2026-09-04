# MuPGA Web — Roadmap

> **Checklist vivo.** Claude Code lo actualiza al completar cada tarea: marcar `[x]`, y
> agregar una línea con fecha en "Registro de cambios" al final.

**Estado actual:** Fase 4 completa ✅ — Fase 5 en curso + Tienda WCoin integrada (incluye Promociones).
Radar de Tiendas: implementado, probado contra datos reales y descartado (ver sección abajo).
Foro: phpBB probado y descartado (quedó parqueado en `foro.mupga.com.ar` sin usar). Pivot a
módulo nativo construido en el repo — falta correr `foro_setup.sql` en el VPS y cargar
categorías antes de anunciarlo a los jugadores.
Fase 9 (Eventos) recién construida — falta correr `controlpanel_events.sql` en el VPS y cargar
el Torneo PVP desde el ControlPanel.
**Apertura 04/09:** pantalla de cuenta regresiva ACTIVA hasta el 2026-09-05T00:00:00Z
(21:00 ARG del 04/09). Se apaga sola al llegar la hora. Para bajarla antes:
`APERTURA_ACTIVA = false` en `src/config/apertura.php` → push (frontend) + `git pull` en el
VPS (API). Ver `runbooks/apertura-gate.md`.

**Última actualización:** 2026-09-04

---

## Fase 0 — Setup
- [x] Repo de GitHub creado
- [x] WebEngine (`htdocs/`) en el repo
- [x] Dump productivo (`script.sql`) en el repo
- [x] `.gitignore` configurado
- [x] `CLAUDE.md` y `ROADMAP.md` creados
- [x] Estructura de carpetas scaffoldeada (`.claude/`, `db/schema/`, `src/`) — 2026-06-01
- [ ] SQL Express local con schema restaurado (entorno de desarrollo)

## Fase 1 — Ingeniería inversa (fundacional)
- [x] Extraer todas las queries SQL y llamadas a stored procedures de `htdocs/` — 2026-06-01
- [x] Mapear el schema desde `script.sql` (tablas, columnas, tipos, claves, SPs) — 2026-06-01
- [x] Generar `.claude/docs/data-dictionary.md` — 2026-06-01
- [x] Generar `.claude/docs/capability-matrix.md` (seguro / riesgoso / prohibido) — 2026-06-01
- [x] Crear skills: `mupga-db-dictionary`, `mupga-db-safety`, `mupga-php-conventions` — 2026-06-01
- [x] **Revisión de Franco** de la matriz de capacidades — aprobada 2026-06-01

## Fase 2 — Capa de acceso a datos
- [x] Módulo de conexión a SQL Server (PDO/sqlsrv) con sentencias preparadas — 2026-06-01
- [x] Funciones de solo-lectura seguras (rankings, online, info de cuenta/personaje) — 2026-06-01
- [x] Funciones de escritura controlada (registro, reset password, créditos WCoin) — 2026-06-01
- [x] Credenciales por variables de entorno (no hardcodeadas) — 2026-06-01

## Fase 3 — Frontend custom
El frontend es HTML + CSS + JS moderno. PHP sirve JSON desde /api/. Sin Bootstrap, sin jQuery.
- [x] Estructura de assets: src/public/assets/css/, js/, img/class/ — 2026-06-01
- [x] src/public/assets/css/main.css — sistema de diseño completo (dark fantasy luxury) — 2026-06-01
- [x] src/public/assets/js/app.js — fetch helpers, renderers, countdown, nav — 2026-06-01
- [x] src/templates/layout.php — header, nav, sidebar, footer (PHP puro, sin vars de juego) — 2026-06-01
- [x] src/public/index.php — home con hero, info cards, top 3 resets, news placeholder — 2026-06-01
- [x] src/public/api/online.php — GET → {count} — 2026-06-01
- [x] src/public/api/serverinfo.php — GET → {season, exp, drop, players_online, players_total} — 2026-06-01
- [x] src/public/api/rankings.php — GET ?type&limit → array de personajes/guilds — 2026-06-01
- [x] Avatares de clase en src/public/assets/img/class/ — hero-bg.jpg pendiente (poner imagen del juego)
- [x] Rankings page con tabs: Resets / Nivel / Master Resets / PK Killers / Guilds — 2026-06-01
- [x] Info del servidor: rates, Chaos Machine, comandos, eventos — 2026-06-01
- [x] Castle Siege removido del sidebar — 2026-06-01
- [x] Fix sidebar padding bottom — 2026-06-01
- [x] Fix base URL robusto para subdirectorios (rankings/, info/) — 2026-06-01

## Fase 4 — Features por capacidad ✅
- [x] Rankings (resets, nivel, master, PK, guilds) — completado en Fase 3
- [x] Registro de cuenta + login (token-based, HMAC-SHA256) — 2026-06-01
- [x] Panel de cuenta (VIP, WCoin, personajes, cambio password/email) — 2026-06-01
- [x] Página de donaciones (UI + placeholder DONATION_URL en .env) — 2026-06-01
- [x] CORS listo para Pages + VPS separados (`_cors.php`) — 2026-06-01
- [x] UserCP: Unstick, Clear PK, Reset Stats, Reset ML, Agregar Stats — 2026-06-02
- [x] Perfil público de jugador (`/player/?name=X`) — 2026-06-02
- [x] Página de descargas (`/downloads/`) con `data/downloads.json` — 2026-06-02
- [x] Descargas: sección de instrucciones "Si ya tenés el juego" / "Primera vez" con botones YouTube placeholder — 2026-06-15
- [x] Descargas: callout informativo "¿El launcher te pide actualizar?" — 2026-06-15
- [x] config.js creado apuntando a `https://api.mupga.com.ar` — 2026-06-02
- [x] Completar `data/info.json` con valores reales del servidor — 2026-06-09
- [ ] Completar `data/downloads.json` con URLs reales del cliente (pendiente Franco)

## Tienda WCoin — Integración con API de pagos externa
- [x] Config: `PAYMENTS_API_URL` en `.env.example`; `paymentsApi` en `config.js`; `data-payments-url` inyectado en layout — 2026-06-09
- [x] Proxy PHP `api/donate/order.php`: valida JWT, extrae Account, reenvía a API externa — 2026-06-09
- [x] UI exchange: `donate/index.php` + `donate.js` (conversor estilo exchange con flujo completo) — 2026-06-09
- [x] Páginas post-pago: `donate/success/` y `donate/error/` — 2026-06-09
- [x] `build.php` actualizado con las 3 páginas nuevas (donate, success, error) — 2026-06-09
- [x] Código de descuento (`discountCode`) en cotización y creación de orden + fix de mapeo `finalAmount`/`baseAmount` (antes leía un campo inexistente) — 2026-07-27
- [x] 401 en `/donate/` corregido: `GET /api/donate/payment_token.php` (emite JWT de pagos) + `donate.js` lo adjunta en los GETs directos a `quote`/`providers` — 2026-07-27
- [x] Página `/donate/transferencia/` para el medio de pago transferencia bancaria (comprobante manual vía WhatsApp o reclamo) — 2026-07-27
- [x] Flujo de Promociones: tabs "Compra personalizada"/"Promociones" en `donate/index.php`, `GET /api/promotions/active` + proxy `api/donate/promotion_order.php`, tarjetas con proveedor auto/selector según cantidad disponible. De paso, se agregó manejo del `409 Conflict` (orden activa) al flujo de compra personalizada, que no estaba cubierto — 2026-08-02
- [ ] Configurar `PAYMENTS_API_URL` en `.env` del VPS cuando la API externa esté lista
- [ ] Configurar CORS en la API externa para permitir el origen del frontend (para GETs directos)
- [x] ~~Configurar en la API externa el `paymentUrl` del proveedor "Transferencia Bancaria"~~ — ya no hace falta: desde 2026-08-02 `donate.js` detecta ese proveedor por nombre y redirige a `/donate/transferencia/` del lado del cliente, sin depender del `paymentUrl` que devuelva la API (ver `.claude/docs/payment_integration.md`, Paso 5 y Paso 6 "Fixes de UX")
- [x] Nav (`layout.php`) y `usercp/index.php` repuntados a `/donate/` — 2026-08-05
- [x] ~~Repunte a `/donate/`~~ — revertido a `/donate2/` en producción — 2026-08-05 (ver Registro de cambios)
- [ ] Limpieza final pendiente (Revertir /donate2, TEMPORAL): cuando la API de pagos esté confirmada estable en producción, repuntar `layout.php`/`usercp/index.php` a `/donate/` de nuevo y recién ahí eliminar `src/public/donate2/`, `src/public/assets/js/donate2.js`, `data/donate.json` y la entrada `donate2/index.html` de `build.php`

## Fase 5 — Deploy y testing

> Guía completa paso a paso en `runbooks/deploy.md`.

### Código (ya listo en el repo)
- [x] `.htaccess` con rewrite para Authorization header — 2026-06-02
- [x] `.env.example` con todas las variables documentadas — 2026-06-01
- [x] `config.js` con URL de producción (`https://api.mupga.com.ar`) — 2026-06-02
- [x] `docs/deploy.md` con el paso a paso completo — 2026-06-02
- [x] `build.php` + `build_runner.php` — generan `dist/` HTML estático para Cloudflare Pages — 2026-06-02
- [x] `layout.php` modo CLI: `data-base-url=""` para que config.js maneje la URL — 2026-06-02
- [x] `app.js` separación BASE (assets/nav) vs API (fetch al VPS) — 2026-06-02

### Cloudflare Pages
- [ ] Ejecutar `php build.php` y verificar `dist/` generado sin errores
- [ ] Subir `dist/` a Cloudflare Pages (o conectar el repo con output dir = `dist`)
- [ ] Configurar dominio custom en Pages (`mupga.com.ar`)

### VPS — pasos manuales (seguir `runbooks/deploy.md`)
- [ ] Clonar el repo en el VPS (`C:\mupga\`)
- [ ] Instalar extensión `pdo_sqlsrv` para la versión de PHP de XAMPP
- [ ] Habilitar `mod_rewrite` en `httpd.conf`
- [ ] Configurar VirtualHost en `httpd-vhosts.conf` con `AllowOverride All`
- [ ] Crear `.env` de producción con `APP_SECRET` generado, `APP_ENV=production`
- [ ] Reiniciar Apache
- [ ] Verificar DNS Cloudflare: registro A `api` apuntando a IP del VPS

### Testing en producción
- [ ] `/api/online.php` responde `{"count":N}`
- [ ] `/api/rankings.php?type=resets&limit=3` responde array JSON
- [ ] Login y panel de cuenta (`/usercp/`) funcionan
- [ ] Unstick / Clear PK / Agregar Stats funcionan contra DB real
- [ ] Rankings excluyen cuentas admin configuradas en `.env`

---

## Fase 6 — Módulo Prode MuPGA

- [x] Schema SQL `prode` con 4 tablas: config, matches, predictions, scores — 2026-06-12
- [x] `database/prode_setup.sql` — script re-ejecutable con usuario, permisos y tablas — 2026-06-12
- [x] `src/config/prode_db.php` — conexión PDO con prode_user (PRODE_DB_* env vars) — 2026-06-12
- [x] `src/db/ProdeRepository.php` — getConfig, getMatchesWithPredictions, savePrediction, getRanking, resolveMatch — 2026-06-12
- [x] `GET  /api/prode/matches.php` — partidos + predicción del usuario autenticado — 2026-06-12
- [x] `POST /api/prode/predict.php` — UPSERT predicción con validación 1h + estado del partido — 2026-06-12
- [x] `GET  /api/prode/ranking.php` — top 50 público — 2026-06-12
- [x] `POST /api/prode/admin_match.php` — crear partido (X-Admin-Token) — 2026-06-12
- [x] `POST /api/prode/admin_result.php` — cargar resultado + premios automáticos (X-Admin-Token) — 2026-06-12
- [x] `src/public/mudial/index.php` + `mudial.js` — página completa con tabs Partidos/Ranking — 2026-06-12
- [x] CSS del módulo prode agregado a main.css — 2026-06-12
- [x] Navbar: enlace "Prode" en layout.php — 2026-06-12
- [x] `.env.example`: variables PRODE_DB_* y ADMIN_TOKEN documentadas — 2026-06-12
- [x] `PASOS_MANUALES_PRODE.md` creado con instrucciones paso a paso para el deploy — 2026-06-12
- [x] Prode: mapa de banderas (emoji) para los 48 equipos del Mundial 2026 — 2026-06-12
- [x] Prode: badge "⏰ En Xh Ymin" cuando faltan menos de 3 horas y el partido sigue abierto — 2026-06-12
- [x] Prode: badge "🟢 EN VIVO" pulsante cuando el partido arrancó hace menos de 110 min — 2026-06-12
- [x] Prode: banderas de los 4 equipos en el encabezado de cada grupo (Grupo A–L) — 2026-06-12
- [x] Prode: partidos ordenados por fecha ASC dentro de cada grupo; grupos ordenados por fecha de su primer partido — 2026-06-12
- [x] Rankings: indicador de jugador online (punto cyan) via JOIN MEMB_STAT.ConnectStat — 2026-06-12
- [x] Prode: columna "Pred." en ranking (total_predictions via subquery en prode.predictions) — 2026-06-15
- [x] Prode: bloque de estadísticas personales encima de los partidos (calculado desde matches.php en JS) — 2026-06-15
- [x] **Seguridad:** cutoff de predicciones movido a SQL Server (`GETUTCDATE() < DATEADD(MINUTE, -60, match_datetime_utc)`); `is_locked` removido del enforcement temporal primario (Express edition: sin Agent); transacción con UPDLOCK/HOLDLOCK; `submitted_at = GETUTCDATE()` explícito en ambos paths del MERGE — 2026-06-19
- [ ] Auditar DB: predicciones con `submitted_at > match_datetime_utc` o dentro de los 60 min previos; evaluar anulación de puntos fraudulentos
- [ ] Ejecutar `database/prode_setup.sql` en SQL Server del VPS (manual — ver PASOS_MANUALES_PRODE.md)
- [ ] Configurar variables PRODE_DB_* y ADMIN_TOKEN en el .env del VPS (manual)
- [ ] Cargar primeros partidos vía admin_match.php (manual)

## Fase 7 — Migración a ControlPanel (contenido dinámico desde mupga_admin)

> Base `mupga_admin` + login `mupga_web_svc` ya creados en producción (no recrear).
> Implementación EN ETAPAS: cada etapa se completa, se avisa y se espera OK antes de seguir.

### Setup previo
- [x] `.env.example`: variables `ADMIN_DB_HOST/PORT/NAME/USER/PASSWORD` documentadas — 2026-07-12
- [x] `src/config/admin_db.php` — conexión PDO separada a `mupga_admin` (clase `AdminDatabase`) — 2026-07-12
- [x] `database/test_admin_db.php` — script CLI de verificación (conexión, tablas, site_status, vw_web_auth) — 2026-07-12
- [x] Agregar variables `ADMIN_DB_*` al `.env` del VPS (manual — Franco) — 2026-07-12
- [x] Correr `test_admin_db.php` en el VPS: conexión OK, 6 tablas OK, vw_web_auth OK (413 cuentas) — 2026-07-12

### Etapa 1 — Server Info + Downloads (solo lectura)
- [x] `database/controlpanel_etapa1_seed.sql` — seed re-ejecutable de `server_info` (blob JSON "secciones") y `downloads` (launcher) — 2026-07-12
- [x] `GET /api/site/server-info.php` — público, lee `server_info.config_key='secciones'`, devuelve `{secciones:[...]}` — 2026-07-12
- [x] `GET /api/site/downloads.php` — público, `is_active=1` orden `sort_order`, devuelve `{items:[...]}` con `item_key`→`id` — 2026-07-12
- [x] `info.js` y `downloads.js` apuntados a los endpoints nuevos — 2026-07-12
- [x] Correr el seed en SSMS del VPS (fix previo: `updated_by` es nvarchar(10), valor acortado a 'seed') — 2026-07-12
- [x] Endpoints funcionando en producción (el 500 inicial era por `ADMIN_DB_*` faltantes en el `.env` del VPS) — 2026-07-12
- [x] Fix: `Cache-Control: no-store` en paths de error de ambos endpoints (un 500 quedaba cacheado 5 min en el browser) — 2026-07-12
- [x] Verificar páginas Info y Descargas en el sitio (Cloudflare Pages, post-deploy) — validado por Franco 2026-07-12 ✅ **Etapa 1 cerrada**
- [ ] Cleanup posterior (cuando Etapa 1 esté validada): eliminar `api/infodata.php`, `api/downloadsdata.php`, `data/info.json`, `data/downloads.json`

### Etapa 2 — Noticias
- [x] `database/controlpanel_etapa2_seed.sql` — seed re-ejecutable de las 3 noticias (match por title) — 2026-07-12
- [x] `GET /api/site/news.php` — público, `is_published=1` orden `published_at DESC`, shape viejo (`body`→`content`, `published_at`→`date` YYYY-MM-DD) — 2026-07-12
- [x] `news.js` y `app.js` (home) apuntados al endpoint nuevo — 2026-07-12
- [ ] Correr el seed en SSMS del VPS + pull + push a Cloudflare (manual — Franco)
- [ ] Verificar página Noticias y bloque de noticias del home (manual — Franco)
### Etapa 3 — Site status (corte de canales)
- [x] `GET /api/site/status.php` — público, fila única id=1, `Cache-Control: no-store` (canal de emergencia) — 2026-07-12
- [x] `app.js`: `loadSiteStatus()` en todas las páginas — banner (franja no bloqueante) / overlay (tapa la página) según `mode`; `scheduled_end` como "Fin estimado" — 2026-07-12
- [x] CSS `.site-status-banner` / `.site-status-overlay` en main.css — 2026-07-12
- [ ] Probar con UPDATE manual en SSMS: banner on/off, overlay on/off, frontend reacciona (manual — Franco)
### Etapa 4 — Auth admin + ControlPanel de escritura
- [x] `src/lib/AdminAuth.php` — `requireAdmin()`: token JWT del sitio + memb___id en `dbo.admins` con `active=1`, si no 403 — 2026-07-12
- [x] `POST /api/admin/site-status.php` — activar/desactivar, allowlist banner/overlay, presets, transacción UPDLOCK/HOLDLOCK — 2026-07-12
- [x] `POST /api/admin/news.php` — create / update / set_published — 2026-07-12
- [x] `POST /api/admin/server-info.php` — edición del JSON con validación estricta (raíz `secciones`), UPDLOCK/HOLDLOCK — 2026-07-12
- [x] `POST /api/admin/downloads.php` — create / update / set_active, item_key con allowlist de formato — 2026-07-12
- [x] UI `/controlpanel/` — página + controlpanel.js con 4 secciones (guard 403, tabs, forms funcionales) + CSS — 2026-07-12
- [x] `build.php`: página controlpanel agregada — 2026-07-12
- [x] `database/controlpanel_etapa4_seed.sql` — alta de admin (placeholder TU_CUENTA) + 3 presets de status — 2026-07-12
- [x] Editar el seed con el memb___id real y correrlo en SSMS (manual — Franco) — 2026-07-12
- [x] Probar el ControlPanel end-to-end en producción — validado por Franco 2026-07-12 ✅
- [x] `GET /api/admin/check.php` + link "✦ Admin" dorado en la nav (solo visible para admins, cache en sessionStorage) — 2026-07-12
- [x] Polish visual del ControlPanel: tabs estilo dorado, chips de estado con pulso, inputs con focus dorado, grillas responsive, hover en filas — 2026-07-12

### Etapa 5 — Acreditación manual de WCoins
- [x] `POST /api/admin/wcoin.php` — `lookup` (existe la cuenta + saldo) y `credit` (sp_AddWCoinWithLog vía `Database::get()`, mismo patrón que Prode; auditoría en `mupga_admin` separada, un fallo ahí no reporta error falso porque el WCoin ya se acreditó) — 2026-07-19
- [x] `database/controlpanel_wcoin_credits.sql` — `CREATE TABLE dbo.wcoin_credits` (auditoría: admin, cuenta destino, monto, motivo, `created_at` explícito con `GETUTCDATE()`) — 2026-07-19
- [ ] Correr el script en SSMS contra `mupga_admin` (manual — Franco)
- [x] UI: tab "WCoins" en `/controlpanel/` — verificar cuenta antes de habilitar el botón, confirm() antes de enviar, historial de últimos créditos — 2026-07-19
- [ ] Probar end-to-end en producción (post-deploy + script SQL corrido)

### Etapa 6 — Popup promocional editable
- [x] `database/controlpanel_promo_popup.sql` — `CREATE TABLE dbo.promo_popup` (fila única id=1) — 2026-07-28
- [x] `GET /api/site/promo.php` — público, no-store, sujeto al lockdown de emergencia (no exento) — 2026-07-28
- [x] `POST /api/admin/promo.php` — get/update con `requireAdmin()`, UPDLOCK/HOLDLOCK, allowlist de esquema en `cta_link` — 2026-07-28
- [x] Tab "🎉 Promo" en `/controlpanel/` — form + dropzone de imagen (reusa `POST /api/admin/upload.php`) — 2026-07-28
- [x] `loadPromoPopup()` en `app.js` — modal una vez por sesión de browser (sessionStorage), cierre por ✕/overlay/Escape — 2026-07-28
- [x] CSS `.promo-modal` en `main.css` (calcado de `.donate2-modal`) — 2026-07-28
- [ ] Correr `controlpanel_promo_popup.sql` en SSMS (mirror local + VPS) — manual, Franco
- [ ] Cargar el contenido real (ej. "EXP +200% de 0 a 50 RR") desde el panel y activar — manual, Franco
- [ ] Probar end-to-end en producción (post-deploy + script SQL corrido)

## Fase 8 — Foro MuPGA

### Intento 1: phpBB — probado en producción, descartado (2026-08-11)

Se instaló phpBB 3.3.17 sobre SQL Server en `foro.mupga.com.ar` (VirtualHost `:80`+`:443`
con cert de Cloudflare, DNS proxied, SMTP con Gmail, login admin `ruggi` funcionando). Quedó
operativo de punta a punta, pero **Franco decidió discontinuarlo** tras usarlo: el ACP resultó
muy poco práctico para el día a día (edición de foros "recontra tosca"), y encima quedó un
lockout de cuenta que hubo que resolver a mano por SQL (`UPDATE phpbb_users SET user_password
= <hash bcrypt> ...`, ver Registro de cambios). La instalación **sigue levantada y sin usar**
en `foro.mupga.com.ar` — no molesta estando quieta, se da de baja el día que el módulo nuevo
esté validado (ver nota al final de `runbooks/foro-web-setup-manual.md`).

### Intento 2: módulo nativo de web-mupga ✅ (2026-08-11)

En vez de software de terceros, foro construido como un módulo más del sitio — mismo patrón
que Reclamos (usuarios crean contenido, admin modera), reusa 100% de la infraestructura ya
existente: login/sesión (JWT), gate de admin (`requireAdmin()`), el ControlPanel, el diseño
del sitio. Cuenta única: la misma del resto del sitio (no hay cuenta de foro separada — el
plan de "cuenta propia + vinculación opcional" de `Resumen Arquitectura login foro` dependía
de la idea de multi-servidor, que quedó descartada; sin eso, separar cuentas no tenía sentido).

**Alcance v1:**
- [x] `database/foro_setup.sql` — schema `forum` en `mupga_admin`, login `mupga_forum_svc`
      (mismo patrón que `reclamos_setup.sql`), tablas `categories`/`threads`/`posts`/
      `reactions`/`banned_accounts` — 2026-08-11
- [x] `src/config/forum_db.php` + `src/db/ForumRepository.php` — 2026-08-11
- [x] API pública/usuario (`src/public/api/forum/`): `categories`, `threads`, `thread`,
      `create_thread`, `reply`, `edit_post`, `delete_post`, `react` — 2026-08-11
- [x] API admin (`src/public/api/admin/`): `forum_categories` (CRUD), `forum_moderate`
      (pin/lock/delete), `forum_ban` (lookup/ban/unban, acotado al foro — nunca toca
      `MEMB_INFO.bloc_code`) — 2026-08-11
- [x] Frontend: `/foro/` (categorías), `/foro/categoria/?id=X` (hilos + nuevo hilo),
      `/foro/hilo/?id=X` (detalle, responder, reaccionar "🙏 Agradecer", moderación inline
      para admin/dueño sin pasar por ControlPanel) — 2026-08-11
- [x] ControlPanel: pestaña "💬 Foro" — CRUD de categorías + bans (mismo patrón
      lookup-then-act que WCoin/VIP) — 2026-08-11
- [x] Nav, `build.php`, `main.css`, `.env.example` (`FORUM_DB_*`) — 2026-08-11
- [ ] Ejecutar `database/foro_setup.sql` en el VPS + cargar categorías iniciales (manual —
      Franco, ver `runbooks/foro-web-setup-manual.md`). Las categorías iniciales están
      armadas en `database/foro_categorias_seed.sql` (7 categorías + un aviso fijado en
      cada una; requiere la migración v2 y editar la variable `@autor`)
- [ ] Activar CAPTCHA en el registro del sitio si no está ya (antispam — el foro hereda la
      cuenta del sitio, así que el antispam correcto es ahí, no en el foro en sí)

**Conscientemente afuera de v1** (sumar si hace falta más adelante): búsqueda, notificaciones/
suscripciones, editor WYSIWYG (usa el mismo markdown-lite que Noticias), avatares, firmas,
rangos, paginación real de hilos/posts (hoy corta en un TOP fijo, alcanza para la población
actual).

### Hardening v2 ✅ (2026-08-11) — Etapas 1-3 de `BACKLOG_FORO.md`

Auditoría previa en `GAP_FORO.md` (Etapa 0, refleja el estado ANTES de este batch).
Implementado sobre la v1, todo server-side-first:

- [x] `database/foro_migracion_v2.sql` — aditiva/idempotente: soft delete (`deleted_at`/
      `deleted_by` en threads y posts), `edited_by_staff`, `locked_reason`, `expires_at`
      en bans, `is_hidden` en categorías, tablas `forum.reports` y `forum.moderation_log`,
      índices (los FK no crean índice solo en SQL Server) — 2026-08-11
- [x] F-01.01: `src/lib/ForumValidation.php` — validador único (título 120 / hilo 10.000 /
      respuesta 5.000, status 422), sanitizado de caracteres de control/zero-width,
      colapso de saltos de línea — 2026-08-11
- [x] F-01.02: whitelist de esquema de links server-side (`javascript:`/`data:` se
      neutralizan a texto ANTES de guardar); contrato "API devuelve crudo, cliente escapa"
      documentado en `ForumValidation.php` — 2026-08-11
- [x] F-09.01: antiflood por cuenta (cooldown 30s + máx 10/hora), transaccional con
      UPDLOCK/HOLDLOCK (patrón de Reclamos), admins exentos; el texto rechazado nunca se
      pierde en el frontend — 2026-08-11
- [x] F-09.02: cuenta sin personaje no puede publicar (leer sí) — de paso deja de
      exponerse la cuenta de login como nombre visible — 2026-08-11
- [x] F-09.03: cuentas con <5 publicaciones: links externos fuera de whitelist
      (mupga/youtube/imgur/discord) se guardan como texto plano — 2026-08-11
- [x] F-08.02/03: reportes (`POST /api/forum/report.php`: motivo de allowlist, 1 por
      cuenta/contenido, 10/hora máx, webhook Discord opcional `DISCORD_WEBHOOK_FORO`) +
      cola en el ControlPanel (`/api/admin/forum_reports.php`, resolver cierra todos los
      del mismo contenido) — 2026-08-11
- [x] F-08.05: log de auditoría `forum.moderation_log` — toda acción de staff (editar/
      borrar/pin/lock/move/ban/resolver reporte) con actor, motivo y cuerpo previo;
      visible en el ControlPanel, sin UI de borrado a propósito — 2026-08-11
- [x] F-02.02/F-03.04: ventana de edición de 30 min para autores (admins siempre);
      "editado por el staff" cuando edita un admin contenido ajeno, sin exponer cuál — 2026-08-11
- [x] F-02.03/F-03.04: soft delete en todo el módulo (se eliminó el DELETE físico);
      autor no puede borrar su hilo si ya respondieron terceros; papelera con restore en
      el ControlPanel; placeholder "Mensaje eliminado" en el hilo (los borrados al final
      se omiten) — 2026-08-11
- [x] F-08.04: fix del lock (bloqueaba también a admins — ahora el staff responde en
      hilos cerrados), motivo de cierre visible en el hilo, mover hilo de categoría — 2026-08-11
- [x] F-08.06: ban con vencimiento opcional en días (vacío = permanente), expira solo,
      el mensaje al baneado muestra motivo y hasta cuándo — 2026-08-11
- [x] F-05.02: sin autoagradecimiento (403 server-side + botón estático en la UI) — 2026-08-11
- [x] F-10.01: índice de categorías con conteo de hilos y última actividad — 2026-08-11
- [x] F-13.01/02 parcial: categorías ocultas (solo admins las ven; sus hilos dan 404 a
      no-admins, no 403 — no revelar existencia) — 2026-08-11
- [x] Privacidad (riesgo del GAP): la API ya no expone `author_account` — la propiedad
      viaja como `is_mine` calculado server-side — 2026-08-11
- [ ] Correr `database/foro_migracion_v2.sql` en SSMS (mirror local + VPS) — manual, Franco
- [ ] (Opcional) `DISCORD_WEBHOOK_FORO` en el `.env` del VPS para avisos de reportes — manual, Franco

Nota del GAP que sigue vigente: el timestamp del backlog (`DATEADD(HOUR,3,GETDATE())`) NO se
usó — contradice la regla dura de CLAUDE.md (incidente timezone 2026-07-19); todo va con
`GETUTCDATE()`/`SYSUTCDATETIME()` como el resto del proyecto.

### Etapas 4-6 ✅ (2026-08-11) — comunidad, escritura y navegación

- [x] `database/foro_migracion_v3.sql` — aditiva/idempotente: `forum.thread_follows`,
      `forum.notifications`, `forum.image_uploads` + índices — 2026-08-11
- [x] F-03.02: citar — botón "💬 Citar" precarga `> **Autor** [dijo](permalink):` en el
      editor; render de bloques `> ` como `<blockquote>` en `renderRichText()` (cliente);
      un solo nivel de anidamiento garantizado al generar — 2026-08-11
- [x] F-03.03: menciones @Personaje — resolución server-side SOLO contra participantes del
      foro (`findAccountsByDisplayNames`, nunca contra la base de juego), una notificación
      por cuenta por mensaje; autocompletado client-side acotado a participantes del hilo
      cargado; render resaltado — 2026-08-11
- [x] F-07.01: seguir hilo — auto-follow al crear/responder, botón 🔔 Seguir/Dejar de
      seguir (`POST /api/forum/follow.php`), avisos agrupados (máx 1 sin leer por hilo) — 2026-08-11
- [x] F-07.02: centro de notificaciones — campanita con contador en todas las páginas del
      foro, panel con últimos 20, marcar leído / todo leído, purga >60 días oportunista
      (sin SQL Agent en Express); tipos: respuesta · mención · gracias · moderación
      (`/api/forum/notifications.php`) — 2026-08-11
- [x] F-04.01: barra de formato (B/I/U/S, link, cita, imagen) con toggle sobre selección
      y atajos Ctrl+B/I/K, en responder y nuevo hilo — 2026-08-11
- [x] F-01.04: vista previa con el mismo `renderRichText()` del render final; el textarea
      conserva texto y cursor al volver — 2026-08-11
- [x] F-04.05: imágenes — `POST /api/forum/upload_url.php` (presigned PUT a R2). **Reusa
      el bucket de Reclamos** (`RECLAMOS_R2_*`, sin bucket ni token nuevos), separado por
      carpeta: `foro/{id_hilo}/` (`foro/nuevos/` al crear el hilo, que todavía no tiene id).
      Solo verificados con personaje, cuota 20/día por cuenta (`forum.image_uploads`),
      5 MB máx (cliente), sintaxis `![alt](url)`; server-side `restrictImages()` degrada a
      link toda imagen que no cuelgue de `foro/` — así una URL de `reclamos/` (capturas de
      tickets ajenos) tampoco se puede empotrar en un post — 2026-08-11
- [x] F-03.05/F-10.02: paginación real — hilos 25/página, respuestas 20/página, la URL
      refleja la página; permalink `?post=X#post-X` resuelve la página server-side y
      resalta el post; al responder redirige a la página donde cayó — 2026-08-11
- [x] F-11.01: búsqueda — `GET /api/forum/search.php` (LIKE escapado sobre títulos +
      cuerpos + respuestas, TOP 30, 2 queries fijas, sin FTS en Express — límite
      documentado en el endpoint), página `/foro/buscar/` con resaltado y CTA de crear
      hilo si no hay resultados; categorías ocultas excluidas para no-admins — 2026-08-11
- [ ] Correr en SSMS del VPS, **en orden**: `database/foro_migracion_v2.sql` →
      `foro_migracion_v3.sql` → `foro_migracion_v4.sql` (el espejo local no tiene
      `mupga_admin` — solo existe en el VPS) — manual, Franco. Es el único paso manual:
      no hay variables de entorno nuevas, las imágenes salen por el bucket de Reclamos
      que ya está configurado en el VPS.

### Fix — borrado de categorías (2026-08-11)

Reportado por Franco: el ControlPanel no dejaba borrar ninguna categoría. La guarda
contaba los hilos **sin filtrar `deleted_at`**, así que una categoría "vacía" en pantalla
(todos sus hilos en la papelera) seguía dando 409 para siempre — y borrarlos de nuevo no
servía porque el soft delete no elimina la fila. Además la FK `threads → categories` no
tiene cascade, con lo cual ni un DELETE manual pasaba.

- [x] `getCategoryContentCounts()` separa visible / papelera; el 409 ahora devuelve los
      conteos para que el panel ofrezca salidas concretas en vez de un mensaje ciego
- [x] `move_to`: reasigna todos los hilos (papelera incluida) a otra categoría y borra
      la categoría — no se pierde nada, es el camino recomendado
- [x] `force`: cascada real (`purgeCategory()`) en transacción — hilos, respuestas,
      reacciones, reportes y avisos. `posts`/`thread_follows` caen por el
      `ON DELETE CASCADE` de threads; reactions/reports/notifications son polimórficas o
      sin FK y se limpian a mano. `forum.moderation_log` sobrevive a propósito
- [x] La FK se deja sin cascade a nivel DB adrede: un DELETE suelto contra `categories`
      tiene que fallar, no vaciar el foro en silencio
- [x] Toda variante queda registrada en `forum.moderation_log` como `delete_category`

### Fix — emojis, orden del listado y buscador (2026-08-11)

Tres cosas que aparecieron al probar el foro con las categorías reales cargadas:

- [x] **Emojis como silueta dorada**: los títulos usan degradado con
      `-webkit-text-fill-color: transparent`, que también pinta el emoji. Se agregaron
      las fuentes de emoji a los stacks (`--font-emoji`, antes del genérico — si no,
      Windows cae en Segoe UI Symbol monocromático) y el emoji inicial del nombre de
      categoría se renderiza en su propio `.forum-emoji`, que recupera fill y fuente
- [x] **`.cp-form[hidden]`**: `display:flex` le ganaba al atributo `hidden`, así que el
      form de "nuevo hilo" estaba SIEMPRE abierto — y por lo mismo el form de responder
      se veía también en hilos cerrados. Mismo patrón ya documentado en `.spinner[hidden]`
      y `.store-panel[hidden]`. Se agregó la misma guarda a `.forum-nuevo-hilo`
- [x] **Listado antes del editor**: en `/foro/categoria/` los hilos van arriba y el botón
      de publicar abajo, con un `scrollIntoView` + foco al abrirlo. Las filas ahora traen
      extracto del cuerpo, última actividad con autor y tiempo relativo ("hace 5 min"):
      el listado invita a leer antes de escribir
- [x] **Buscador roto**: `searchThreads()` no seleccionaba `is_pinned`/`is_locked` pero
      `mapThreadRow()` los castea siempre → warnings de PHP metidos en el body de la
      respuesta → el JSON no parseaba en el cliente

### F-06.03 — Distintivos de autor + tipografía del mensaje (2026-08-11)

- [x] `database/foro_migracion_v4.sql` — `forum.author_badges` (caché de VIP por cuenta
      con TTL) — 2026-08-11
- [x] `src/lib/ForumBadges.php` — resuelve staff y VIP **en lote** para todos los autores
      de la página (nunca N+1). **Staff** sale de `dbo.admins`, que está en la misma base
      que el foro: lectura en vivo con `AdminDatabase`, sin caché ni GRANT nuevo, siempre
      al día. **VIP** sale de `MEMB_INFO` (`AccountLevel = 3` + `AccountExpireDate`
      futuro, mismo criterio que `usercp.js`): se cachea con TTL de 60 min y solo se
      refresca para los autores en pantalla cuyo dato venció, así el foro no le pega a la
      base de juego en cada render (F-13.04). `vip_until` se revalida contra "ahora" en
      cada lectura, así un VIP que vence dentro del TTL deja de mostrarse igual. Si la
      base de juego no responde, se usa lo cacheado y el mensaje se muestra sin
      distintivo — nunca rompe el hilo — 2026-08-11
- [x] Privacidad: los distintivos viajan como banderas (`badges.staff` / `badges.vip`);
      `author_account` sigue sin salir nunca de la API — 2026-08-11
- [x] Frontend: etiqueta STAFF (dorada) y ⭐ VIP (cyan) al lado del nombre, en el hilo y
      en el listado de la categoría. El mensaje de staff además se resalta entero (borde
      izquierdo dorado + fondo), para que se note de un vistazo en un hilo largo — 2026-08-11
- [x] Tipografía del mensaje: cuerpo a 1rem con `line-height` 1.75 y `--text-bright`,
      negritas en blanco puro, `##` subtítulos en dorado con la fuente de títulos, viñetas
      con marcador dorado, links en cyan subrayados. La vista previa del editor usa las
      mismas reglas porque comparte la clase — 2026-08-11
- [ ] Distintivo de guild (parte de F-06.03): pendiente — requiere leer `GuildMember` de
      la base de juego y sumarlo al mismo lote/caché

### Fix — moderación visible para todos, buscador y edición en línea (2026-08-12)

- [x] **`[hidden]` global**: `display:none` de `[hidden]` tiene la especificidad más baja
      que existe, así que cualquier `.clase { display: flex }` lo pisaba. Los botones de
      moderación se le mostraban a todo el mundo (sin riesgo real — los endpoints validan
      `requireAdmin()` — pero se veía pésimo). Se venía parchando clase por clase
      (`.spinner`, `.store-panel`, `.cp-emoji-picker`, `.cp-form`, `.cp-actions`); ahora
      hay una regla global. De paso arregla el desplegable de menciones y el panel de
      notificaciones, que nunca se cerraban
- [x] **Buscador (500 en toda búsqueda)**: era el `ESCAPE '\'` del LIKE. El driver de SQL
      Server no soporta parámetros con nombre, así que PDO reescribe los `:nombre` a `?`
      parseando el SQL él mismo, y ese parser lee `\'` como comilla escapada: cree que el
      literal sigue abierto y los placeholders siguientes desaparecen. Carácter de escape
      cambiado a `!`. **Nunca poner un backslash dentro de un literal SQL en este proyecto**
- [x] **PHP 7.4 en el VPS** (verificado en la cabecera de Apache): `ForumValidation` usaba
      `str_ends_with()`, de PHP 8 — fatal en cuanto una cuenta nueva pegara un link.
      Reemplazado por `substr()`. Tenerlo presente para todo el código nuevo
- [x] Citas: la atribución se renderiza como encabezado del bloque (autor + "ver mensaje")
      en vez de un renglón suelto con un link; el permalink insertado perdió el `#post-N`
      redundante. Menciones: chip con fondo en vez de texto de color
- [x] **Edición en línea**: se eliminó el `prompt()` del navegador (una sola línea, aplastaba
      el markdown de cualquier mensaje con formato). Ahora se abre el mismo editor que para
      responder —barra de formato, vista previa, imágenes y menciones— dentro del propio
      mensaje, con el título aparte cuando es un hilo y los errores del server en línea en
      vez de un `alert()`. Los botones de editar/borrar hilo ya no rompen si estás en una
      página distinta de la 1: te llevan a la 1, donde vive el mensaje de apertura

**Siguientes etapas del backlog (no iniciadas):** Etapa 7 y P2 sueltos (F-10.03 URLs
legibles/slug, bloque de código F-04.02, spoiler F-04.03, embed YouTube F-04.04, no leídos
F-03.06/F-10.04, perfil F-06.02, métricas F-13.05, etc.) — según pida la comunidad.

## Fase 9 — Módulo Eventos

Página `/eventos/` donde jugadores logueados se anotan a torneos/actividades puntuales y ven
la lista de anotados. Genérico a propósito (no una tabla por evento) para no rehacer esto la
próxima vez — primer evento a cargar: Torneo PVP (viernes que viene, hora a confirmar).

- [x] `database/controlpanel_events.sql` — tablas `dbo.events` / `dbo.event_registrations`
      en `mupga_admin` (misma base que ControlPanel, sin base ni login nuevos — `mupga_web_svc`
      ya tiene datareader+datawriter ahí, mismo criterio que `wcoin_credits`/`vip_grants`) — 2026-08-23
- [x] `src/db/EventsRepository.php` — listActive, listRegistrations (pública, solo nombre de
      personaje), register/unregister (transacción UPDLOCK/HOLDLOCK, mismo patrón anti-TOCTOU
      que Prode/Reclamos), listAll/create/update/setActive/listRegistrationsAdmin/removeRegistration — 2026-08-23
- [x] `GET /api/events/list.php` — eventos activos + cupo + `my_registration` si hay token — 2026-08-23
- [x] `GET /api/events/registrations.php?event_id=X` — lista pública de anotados (solo personaje) — 2026-08-23
- [x] `POST /api/events/register.php` — valida personaje propio (`CharacterRepository::belongsToAccount`),
      cutoff por `GETUTCDATE() < event_datetime` (NULL = sin cutoff hasta que se cargue fecha), cupo — 2026-08-23
- [x] `POST /api/events/unregister.php` — cancelar inscripción propia — 2026-08-23
- [x] `POST /api/admin/events.php` — create/update/set_active/remove_registration, `requireAdmin()` — 2026-08-23
- [x] Frontend `/eventos/` + `eventos.js` — cards con fecha en hora local del navegador,
      form de inscripción con selector de personaje, "ver anotados" expandible — 2026-08-23
- [x] Tab "🏆 Eventos" en `/controlpanel/` — alta/edición, activar/desactivar, ver anotados y
      dar de baja una inscripción puntual — 2026-08-23
- [x] Nav (`layout.php`) y `build.php` — 2026-08-23
- [ ] Correr `database/controlpanel_events.sql` en SSMS (mirror local + VPS) — manual, Franco
- [ ] Cargar el Torneo PVP desde el ControlPanel (título, descripción, cupo si aplica; fecha
      cuando Franco la confirme) y probar el flujo end-to-end en producción — manual, Franco

## Radar de Tiendas — lectura de tiendas personales

Analizado a pedido de Franco (2026-08-11) si se podía llevar la tienda personal del juego
("abrir tienda") a la web, tipo marketplace P2P. Conclusión tras revisar el schema real
(`database/script.sql`) y el repo del GameServer (`mu-s6-server`, solo binarios, sin fuente
C++): **no es construible de forma segura solo desde la web hoy**. `CustomStore` no guarda
ítems (solo `Active`/`Type`/`StoreName`), `PShopItemValue` solo guarda slot+precio — el ítem
real vive en `Character.Inventory` (binario, propietario del GS). Escribir en esas tablas
está PROHIBIDO (`capability-matrix.md`). El único mecanismo de entrega probado (`GremoryCase`,
usado por la Tienda WCoin) fabrica ítems nuevos desde parámetros, no puede extraer un ítem
existente del inventario de un jugador. Haría falta un mecanismo de consignación del lado del
GameServer (otro repo, fuera de este alcance) — el motor ya demuestra que sabe manejar datos
de ítem estructurados (`GremoryCase`/`EventAuctionReward` tienen columnas por atributo), así
que no es descabellado, pero no es tarea de `web-mupga`.

Se implementó la parte que sí es 100% segura (todo lectura): `ShopRepository.php`,
`GET /api/shops.php`, página `/tiendas/` con auto-refresh, link en el nav, banner cruzado
con `/tienda/`. **Funcionó técnicamente** — probado contra el VPS con tiendas reales
abiertas, `Active=1` filtra correctamente las tiendas activas — pero **se descartó por
decisión de producto de Franco** (2026-08-11): la vista de solo precio/slot sin poder
identificar el ítem no se entendía y no resultaba útil en la práctica. Código eliminado
(`src/db/ShopRepository.php`, `GET /api/shops.php`, `/tiendas/`, `tiendas.js`, link de nav,
banner cruzado en `/tienda/`) — ver Registro de cambios. Se deja esta sección como
referencia de qué se probó y por qué no siguió, para no volver a evaluarlo desde cero si
se retoma la idea más adelante (por ejemplo, si algún día se resuelve la identificación del
ítem — ver limitación de `Character.Inventory` arriba — capaz cambia el veredicto de UX).

## Backlog — ideas pendientes
- [ ] Analytics de clicks/embudo de conversión en la web (frontend → endpoint propio
      de eventos, ej. `/api/analytics/track.php`). Complejo y amplio — evaluar recién
      después de cerrar las estadísticas de compras (WCoin) de la Tienda. Discutido
      con Franco 2026-07-24.

## Registro de cambios
<!-- Claude Code agrega acá una línea por tarea completada. Formato:
     - YYYY-MM-DD — [Fase X] qué se hizo -->
- 2026-06-01 — [Fase 0] Scaffolding de carpetas: `.claude/docs/`, `.claude/skills/`, `db/schema/`, `src/`
- 2026-06-01 — [Fase 1] Extracción de queries SQL y SPs de WebEngine (`htdocs/includes/classes/`)
- 2026-06-01 — [Fase 1] Mapeo del schema desde `script.sql` (76 tablas, 100+ stored procedures)
- 2026-06-01 — [Fase 1] Generado `.claude/docs/data-dictionary.md` con tablas, columnas, tipos y SPs
- 2026-06-01 — [Fase 1] Generado `.claude/docs/capability-matrix.md` con clasificación SEGURA/RIESGOSA/PROHIBIDA
- 2026-06-01 — [Fase 1] Creados skills: `mupga-db-dictionary`, `mupga-db-safety`, `mupga-php-conventions`
- 2026-06-01 — [Fase 1] Revisión aprobada: columna `ResetCount` (no `RESETS`), operaciones sobre Character seguras online en MuPGA
- 2026-06-01 — [Fase 2] Creados .env.example, src/config/env.php, src/config/database.php (PDO/sqlsrv singleton)
- 2026-06-01 — [Fase 2] Creados AccountRepository, CharacterRepository, RankingsRepository, CreditsRepository
- 2026-06-01 — [Fase 2] Creados src/bootstrap.php y src/public/index.php (entry point con test de conexión)
- 2026-06-01 — [Fase 2] Fix de conexión: DSN sin puerto (Browser service resuelve instancia SQLEXPRESS01); pdo_sqlsrv confirmado; test.php eliminado
- 2026-06-01 — [Fase 3] Layout base, home page, 3 API endpoints JSON, design system CSS completo, app.js vanilla
- 2026-06-01 — [Fase 3] Rankings con tabs + auto-refresh 2min, Info del servidor dinámica desde data/info.json
- 2026-06-01 — [Fase 3] Creado migration.md con guía paso a paso para Cloudflare Pages + VPS
- 2026-06-01 — [Fase 4] Auth token-based (HMAC-SHA256): TokenService, Auth middleware, login, register
- 2026-06-01 — [Fase 4] Panel de cuenta: profile, balance, changepassword, changeemail
- 2026-06-01 — [Fase 4] Página de donaciones: UI completa, DONATION_URL en .env (único punto de config)
- 2026-06-01 — [Fase 4] Nav dinámica según sesión (data-auth-show / data-guest-show)
- 2026-06-01 — [Fase 4] _cors.php listo para Pages + VPS separados; migration.md actualizado
- 2026-06-02 — [Fix] Authorization header en Apache: .htaccess + Auth.php multicadena; login funcionando
- 2026-06-02 — [Fix] changepassword devuelve 400 (no 401) cuando contraseña actual es incorrecta
- 2026-06-02 — [Fix] Rankings: exclusión de admins por AccountID (RANKINGS_EXCLUDED_ACCOUNTS en .env)
- 2026-06-02 — [Fix] Rankings: top 100, posición del jugador logueado con highlight cyan
- 2026-06-02 — [Fix] UserCP: sección "Opciones de personaje" con Unstick y Limpiar PK
- 2026-06-02 — [Fix] rankings.php: getPlayerCharacterRank aislado en try/catch para que columnas faltantes no maten toda la respuesta
- 2026-06-02 — [Fix] app.js loadTopPlayers: normaliza respuesta array vs {rows,player}
- 2026-06-02 — [Fix] CSS fondos más claros; cache-buster ?v= en layout para JS y CSS en dev
- 2026-06-02 — [Fix] UserCP botones: textos cortos, layout flex corregido (text-overflow)
- 2026-06-02 — [Feat] Rankings: exclusión por AccountID + Name (doble filtro admin)
- 2026-06-02 — [Feat] UserCP: endpoints resetstats.php y resetml.php + botones en UI
- 2026-06-02 — [Feat] Perfil público de jugador: api/player.php + player/index.php + player.js
- 2026-06-02 — [Feat] Rankings y home: nombres de personajes clickeables → perfil público
- 2026-06-02 — [Feat] Perfil público: diseño mejorado (neutro oscuro, separadores zebra, contraste)
- 2026-06-02 — [Feat] UserCP: VIP muestra "VIP activo / Sin VIP" (AccountLevel 0 vs 3)
- 2026-06-02 — [Fix] className() robusto: muestra código real si la clase no está mapeada
- 2026-06-02 — [Feat] UserCP: endpoint addstats.php + panel "Agregar puntos de estadística" con inputs por stat
- 2026-06-02 — [Feat] CharacterRepository.getByAccount incluye LevelUpPoint en la query
- 2026-06-02 — [Feat] Página de descargas: downloads/index.php + downloads.js + data/downloads.json (placeholders)
- 2026-06-02 — [Fix] UserCP addstats: panel ahora aparece al cargar (no solo al cambiar el select)
- 2026-06-02 — [Fix] UserCP addstats: si no hay personajes oculta el panel; si hay, lo muestra con el primero seleccionado
- 2026-06-02 — [Design] CSS: fondos profundizados hacia negro puro (#09080f/#0e0c1b/#131124)
- 2026-06-02 — [Design] CSS: purple más vibrante (#9147e8) para destacar sobre negro; --border definido
- 2026-06-02 — [Design] CSS: hero gradient reforzado (purple 42%), header usa variable de color, game-options-card usa --cyan-glow
- 2026-06-02 — [Fix] addstats.php: eliminado límite de 500 puntos por operación
- 2026-06-02 — [Fix] usercp.js: resetstats/resetml actualizan el contador "Puntos disponibles" en tiempo real usando new_points del response
- 2026-06-02 — [Fase 5] config.js creado (apunta a api.mupga.com.ar en producción); app.js usa config.js como fallback
- 2026-06-02 — [Fase 5] docs/deploy.md creado con guía completa de deploy en VPS Windows
- 2026-06-02 — [Fase 5] ROADMAP.md: Fase 4 marcada completa, Fase 5 expandida con ítems de código vs VPS
- 2026-06-02 — [Fase 5] build.php + build_runner.php: generador de dist/ HTML estático por subprocess PHP aislado
- 2026-06-02 — [Fase 5] layout.php: base vacía en CLI; app.js: BASE (assets) separado de API (VPS)
- 2026-06-02 — [Feat] Noticias: news/index.php + news.js + data/news.json (3 placeholders) + newsdata.php
- 2026-06-02 — [Feat] Rankings: caché en memoria 2min (cachedFetch); loadRanking y silentRefresh usan caché
- 2026-06-02 — [Feat] Guild profile: api/guild.php + guild/index.php + guild.js; nombres en ranking guild → links
- 2026-06-02 — [Feat] Registro: Cloudflare Turnstile integrado (widget + verificación server-side); deshabilitado si TURNSTILE_SECRET_KEY vacío
- 2026-06-02 — [Feat] UserCP VIP: muestra fecha de expiración (AccountExpireDate) solo cuando VIP activo
- 2026-06-02 — [Doc] CLAUDE.md: regla 6 — nunca usar tablas WEBENGINE_*
- 2026-06-02 — [Feat] Rankings país: tabla MUPGA_ACCOUNT_COUNTRY propia; ip-api.com al registrar; emoji bandera en ranking
- 2026-06-02 — [Feat] Navbar: enlace a Noticias agregado
- 2026-06-02 — [Design] Tipografía: --text #e8e4f4, --text-dim #a099be, --text-bright #f5f2ff
- 2026-06-09 — [Feat] /donate guardado detrás de login: guard en donate.js + redirect post-login con ?redirect= (anti open-redirect)
- 2026-06-09 — [Feat] UserCP addstats: stats actuales (Fue/Agi/Vit/Ene/Lid) visibles antes de distribuir puntos; se actualizan en tiempo real tras submit exitoso
- 2026-06-09 — [Backend] CharacterRepository.getByAccount expone Strength/Dexterity/Vitality/Energy/Leadership; profile.php los mapea como str/agi/vit/ene/cmd
- 2026-06-09 — [Design] CSS: .current-stats-display con .current-stat, .current-stat__label, .current-stat__val
- 2026-06-09 — [Seguridad] Online check en los 5 endpoints de escritura (unstick/clearpk/resetstats/resetml/addstats): HTTP 409 si ConnectStat=1
- 2026-06-09 — [Feat] resetchar.php: reset de personaje (nivel 400→1, ResetCount+1, stats base); config vía RESET_* en .env
- 2026-06-09 — [Fix] resetstats.php: devuelve base_stats en la respuesta; getBaseStats() centralizado en CharacterRepository
- 2026-06-09 — [Fix] usercp.js: post-mutación re-fetch de loadProfile() para sincronizar stats/puntos desde DB; populateCharSelect preserva selección
- 2026-06-09 — [Doc] capability-matrix.md: política de online check actualizada
- 2026-06-09 — [Fix] app.js CLASS_NAMES: agregadas 18 clases faltantes (Magic Knight, Dimension Summoner, Fist Blazer, Shining Lancer, Grand Rune Master, Majestic Rune Wizard, Master Slayer, Slaughterer, Master Gun Breaker, Heist Gun Crusher, Light Wizard family, Lemuria Mage family)
- 2026-06-09 — [Fix] CharacterRepository.getBaseStats: agregados todos los códigos de clase faltantes con stats base correctos
- 2026-06-09 — [Feat] Tienda WCoin: UI exchange (donate/index.php + donate.js), proxy PHP api/donate/order.php, páginas post-pago donate/success + donate/error, config paymentsApi en config.js + data-payments-url en layout.php, build.php actualizado
- 2026-06-12 — [Fase 6] Módulo Prode MuPGA completo: schema SQL, ProdeRepository, 5 endpoints, página /mudial/, CSS, navbar, docs
- 2026-06-12 — [Prode] Banderas emoji por equipo, badges "EN VIVO" y "se juega pronto", banderas en encabezado de grupo, orden por fecha
- 2026-06-12 — [Rankings] Indicador online (punto cyan) en todos los rankings de personaje vía JOIN MEMB_STAT
- 2026-06-12 — [Ajuste] Rankings: indicador "En línea" con texto pulsante (rank-online-badge) reemplaza el punto cyan
- 2026-06-12 — [Ajuste] Prode: banderas reemplazadas por imágenes flagcdn.com (24x18px, ISO 3166-1 alpha-2, gb-eng/gb-sct para Ing/Esco)
- 2026-06-12 — [Ajuste] Prode: orden de grupos por STAGE_ORDER canónico (A→L, luego fases elim.) en vez de por fecha
- 2026-06-12 — [Ajuste] Creado database/resultados_jornada1.sql con UPDATEs directos para los partidos ya jugados (sin resolveMatch, sin premios)
- 2026-06-13 — [Prode] Sección de reglamento colapsable en /mudial/: toggle nativo <details>/<summary>, premios, puntos, reglas
- 2026-06-13 — [Feat] /donate2/: página informativa estática de compra de WCoins; data/donate.json configurable; nav y usercp redirigen a /donate2/
- 2026-06-13 — [Build] minifyJs() en build.php revertida — JS se copia sin modificar; minificación pendiente para más adelante
- 2026-06-15 — [Prode] Ranking: columna "Pred." (total_predictions via subquery); cabecera abreviada con abbr; grid 6 columnas
- 2026-06-15 — [Prode] Estadísticas personales: bloque .prode-user-stats sobre partidos, calculado en JS desde respuesta matches.php (pts, exactos, ganadores, predicciones, sin predecir)
- 2026-06-15 — [Descargas] Sección de instrucciones paso a paso (dos flujos: ya tenés/primera vez) + callout de actualización del launcher; estilos nuevos en main.css
- 2026-06-19 — [Seguridad] Prode: cutoff server-side con DATEADD(HOUR,3,GETDATE()) [GETUTCDATE() unreliable en este VPS: devuelve UTC-5], is_locked removido del enforcement temporal (SQL Server Express sin Agent), UPDLOCK/HOLDLOCK, submitted_at en ambos paths del MERGE, frontend isMatchOpen() con prioridad temporal sobre is_locked
- 2026-06-19 — [Fix] Prode: badge "⏰ En Xmin" restaurado para partidos a ≤60 min del inicio; muestra independiente del estado de predicciones (is_locked / cutoff)
- 2026-06-20 — [Feat] Integración VPS de pagos: JWT estándar HS256 (RFC 7519) con claims iss/aud/uid/usr/role en api/donate/order.php; TTL sesión reducido de 7 días a 24h; PAYMENT_JWT_SECRET + PAYMENT_JWT_ISS/AUD en .env.example
- 2026-07-17 — [Prode] Semifinal colapsada (mismo renderCollapsible que grupos/R32/R16/cuartos); visibles solo Tercer Puesto y Final
- 2026-07-13 — [Fase 7] Lockdown server-side: con overlay activo toda la API responde 503 (src/lib/Lockdown.php via _cors.php; exentos status/admin/login; fail-open si mupga_admin cae) + MutationObserver que reinserta el overlay si lo borran de DevTools
- 2026-07-13 — [Fase 7] Overlay: en /controlpanel/ y /login/ se degrada a banner (evita lockout del admin); datetime-local con color-scheme dark + showPicker()
- 2026-07-12 — [Fase 7 · Etapa 4d] Noticias con página propia (/news/?id=N) e imágenes: columna image_url (controlpanel_news_image.sql — correr en SSMS), upload drag & drop en el CP (POST /api/admin/upload.php, finfo MIME + 3MB máx, guarda en src/public/uploads/news/ del VPS, gitignoreado), miniaturas en listado, vista de artículo completa, polish estético de la página Info (secciones como cards, zebra, acentos dorados)
- 2026-07-12 — [Fase 7 · Etapa 4c] Fix botón Admin: cache-buster de assets por deploy en layout.php (antes ?v=1 fijo → JS viejo cacheado post-deploy); updateAdminNav no cachea errores del check. Polish visual global: shine sweep en botones, elevación de cards, focus dorado en inputs, degradé en títulos, scrollbar y selection dorados, footer con borde degradé
- 2026-07-12 — [Fase 7 · Etapa 4b] Nav admin: /api/admin/check.php, link "✦ Admin" dorado con data-admin-show (auth.js con cache sessionStorage), polish visual completo del ControlPanel
- 2026-07-12 — [Fase 7 · Etapa 4] ControlPanel: AdminAuth (requireAdmin), 4 endpoints admin POST-only (site-status con presets y UPDLOCK/HOLDLOCK, news, server-info con validación JSON, downloads), página /controlpanel/ con UI funcional, seed de admin+presets, build.php actualizado
- 2026-07-12 — [Fase 7 · Etapa 3] Site status: endpoint público /api/site/status.php (no-store), loadSiteStatus() en app.js con modos banner/overlay, estilos en main.css
- 2026-07-12 — [Fase 7 · Etapa 2] Noticias dinámicas: seed SQL de 3 noticias, endpoint público /api/site/news.php (is_published=1, published_at DESC, shape compatible), news.js y app.js migrados
- 2026-07-12 — [Fase 7 · Etapa 1] server_info y downloads dinámicos: seed SQL, endpoints públicos /api/site/server-info.php y /api/site/downloads.php (conexión AdminDatabase, prepared statements), info.js y downloads.js migrados
- 2026-07-12 — [Fase 7] Setup previo ControlPanel: admin_db.php (conexión PDO separada a mupga_admin), variables ADMIN_DB_* en .env.example, script CLI test_admin_db.php para validar en el VPS
- 2026-07-01 — [Feat] Compra de VIP con WCoins en /usercp/: endpoint api/account/buyvip.php (transacción PDO con UPDLOCK/HOLDLOCK, descuento WCoinC, sp_SetAccountGOLDVIP, log CashLog); sección "VIP Oro" en usercp con confirmación, feedback en tiempo real y actualización del balance
- 2026-07-14 — [Fase 7] Editor de formato en ControlPanel (noticias): toolbar markdown-lite (negrita/cursiva/subrayado/tachado/subtítulo/lista/link), picker de emojis y vista previa en vivo; renderRichText() en app.js (escapa antes de formatear — whitelist de tags, links solo http/https), news.js renderiza el cuerpo con formato; SQLSRV_ENCODING_UTF8 en admin_db.php para emojis/tildes
- 2026-07-22 — [Chore] Orden de repo: se elimina `htdocs/` (WebEngine, ~1.350 archivos) — su contenido ya estaba capturado en `.claude/docs/data-dictionary.md`/`capability-matrix.md`; se resolvieron ahí mismo los 3 "a verificar" pendientes (Gens_Duprian/Varnert en vez de IGC_Gens, Master Level vive en MasterSkillTree y no en Character, columnas de LOG_CREDITOS) contra `database/script.sql`. Se eliminan también el prototipo estático pre-`src/` (index.html/info.html/css/js en la raíz), migration.md, el .svg de roadmap, el `gitignore` duplicado y test_donate_temp.php. Se consolida `db/schema/` dentro de `database/schema/`, y `docs/` + PASOS_MANUALES_PRODE.md + la colección Postman de Prode se mueven a `runbooks/` nuevo (para no colisionar con `.claude/docs/`). Las .dll de sqlsrv se mudan a `tools/xampp-sqlsrv-dll/` (se conservan, no se usan para nada crítico). `data/` se deja igual con un README aclarando que es un shim temporal de la Fase 7.
- 2026-07-22 — [Feat] Tienda de ítems WCoin (Etapa 1, catálogo): schema `webshop` en la base principal con login propio `webshop_user` (CONTROL solo sobre ese schema, sin acceso a CashShopData); `src/config/webshop_db.php`; `tienda_import.php` reconectado a `WebshopDatabase` en vez de la conexión principal; vars `WEBSHOP_DB_*` en `.env.example`; runbook `runbooks/tienda-setup-manual.md` con los pasos para probar en local; sección "Módulo Tienda de Ítems (WCoin)" agregada a `CLAUDE.md`.
- 2026-07-22 — [Feat] Tienda de ítems WCoin (Etapa 2 + 3, cierre): página pública `/tienda/` con catálogo agrupado por categoría (fix: category_id sin castear rompía el agrupado, mostraba todo bajo "Otros") y variantes de un mismo ítem (1/7/30 días, etc.) agrupadas en una sola card con selector; `GET /api/tienda/catalog.php` público; `POST /api/tienda/buy.php` con transacción atómica UPDLOCK/HOLDLOCK igual que `buyvip.php` (descuenta WCoinC, entrega vía INSERT en CashShopInventory con InventoryType/ProductType=83/80 confirmados constantes, log en CashLog); saldo del usuario visible en la página vía `/api/account/balance.php` existente; `database/webshop_setup.sql` con Bloque C (GRANT SELECT a DB_USER para que la compra lea el precio en la misma transacción). Página todavía sin link en el nav (pendiente que Franco la revise). Íconos animados (frames 3D de alas/equipo especial) evaluados y descartados por ahora — ningún ítem del catálogo actual los tiene.
- 2026-07-22 — [Feat] Tienda: UI/UX — saldo movido al widget del sidebar global (layout.php, oculto salvo en /tienda/ con sesión iniciada), color blanco fuerte para destacarlo; nuevo widget "Pendientes de reclamar" con las compras sin reclamar (GET /api/tienda/mis_compras.php, join CashShopInventory + webshop.products); mensaje de compra exitosa ahora nombra el ítem comprado.
- 2026-07-22 — [Feat] Tienda: link "Tienda" agregado al nav principal (layout.php, entre WCoin y Wiki) — la página deja de ser oculta/no-linkeada.
- 2026-07-22 — [Fix] Tienda: saldo/pendientes sacado del sidebar global (quedaba cortado por la altura fija del panel) — ahora es un banner propio arriba del catálogo en /tienda/, con los pendientes como chips horizontales en vez de lista vertical con scroll. Revertido el hook de body class / reorder de grid que se había agregado para mobile, ya no hace falta.
- 2026-07-19 — [Fix] Timezone del SO del VPS volvió a cambiar (ART UTC-3 → UTC+2), rompiendo el cutoff de predicciones de la final: reemplazado `DATEADD(HOUR, 3, GETDATE())` hardcodeado por `GETUTCDATE()` puro en `ProdeRepository::savePrediction()` y en Reclamos (`create.php`, `reply.php`, `admin/reclamos.php`); incidente y diagnóstico repetible documentados en CLAUDE.md
- 2026-07-19 — [Fase 7 · Etapa 5] Acreditación manual de WCoins desde el ControlPanel: `POST /api/admin/wcoin.php` (lookup + credit, reusa `CreditsRepository::addWCoin()` y `AccountRepository::usernameExists()` vía `Database::get()`), auditoría propia en `mupga_admin.dbo.wcoin_credits` (`database/controlpanel_wcoin_credits.sql`, a correr en SSMS), tab "WCoins" nuevo en `/controlpanel/`
- 2026-07-24 — [Feat] Tienda: estadísticas de compras (en qué gastan los jugadores su WCoin). Tabla `webshop.purchases` (`database/webshop_purchases_setup.sql`, sin GRANT nuevo porque `webshop_user` ya tiene CONTROL sobre el schema) con `product_name`/`category_name` denormalizados a propósito (el catálogo se trunca entero en cada reimport, un FK a `webshop.products` quedaría huérfano). `buy.php` ahora hace un INSERT ahí después del `COMMIT`, por la conexión separada `WebshopDatabase` (no la de `Database::get()`, que solo tiene SELECT sobre `webshop`) — un fallo en esa auditoría no revierte ni bloquea la compra, mismo criterio que `wcoin_credits`. Nuevo endpoint `GET /api/admin/tienda_stats.php` (resumen, top ítems, top compradores, gasto por día) y tab "Estadísticas" en `/controlpanel/`. Solo junta datos desde que se sumó esta tabla — no hay forma de reconstruir compras anteriores. Pendiente: correr `webshop_purchases_setup.sql` en SSMS (mirror local + VPS).
- 2026-07-27 — [Feat] Tienda WCoin: código de descuento (`discountCode`) opcional en `/donate/` — input nuevo, se envía en `GET /api/currencies/quote` y (solo si `applyDiscount` vino true) en `POST /api/donate/order.php`; UI muestra precio original tachado + badge `-N%` cuando aplica. [Fix] de acompañamiento: `donate.js` leía un campo `ConvertedAmount` inexistente en la respuesta real de la API (son `baseAmount`/`finalAmount`) — corregido en todos los usos; se sacó `QuoteCurrencyAmount` del body de `CreateOrder` (la API recalcula el precio, no hay que enviarlo); parseo de errores ahora soporta el formato real de la API externa (`{title, errors:[...]}`) además del propio del proxy PHP (`{Message, Details}`). Detalle completo en `.claude/docs/payment_integration.md`.
- 2026-07-27 — [Fix, pendiente de confirmar en VPS] 401 al crear órdenes de pago: el token de sesión del sitio (`TokenService::generate()`, 2 segmentos, sin role/iss/aud, TTL 24h) se estaba confundiendo con el JWT real que viaja a la API de pagos (`TokenService::generatePaymentJWT()`, 3 segmentos, HS256, con iss/aud/uid/usr/role — ya correcto desde el 20/06). Hipótesis del 401 real: `generatePaymentJWT()` calcula `iat`/`exp` con `utcNow()` (NTP con fallback a `time()`), y si el UDP 123 de NTP está bloqueado en el VPS (frecuente en hosting), cae al reloj propio del VPS — ya demostrado inestable (ver incidentes de timezone arriba). Se agregó una tercera fuente antes de `time()`: el header HTTP `Date` de la propia API de pagos (incluida ahora en `utcNow()`), que sincroniza directo contra el sistema que valida el JWT sin depender del reloj local. Diagnóstico repetible: `tools/debug_jwt_clock.php` (correr en el VPS, compara las 3 fuentes contra la hora real). Pendiente: Franco corre el diagnóstico + prueba una compra real en el VPS para confirmar que el 401 se resuelve.
- 2026-07-27 — [Fix] Causa real del 401 encontrada (la hipótesis del reloj de la entrada anterior quedó descartada: NTP coincidió exacto con `time()` en el VPS de Franco, diferencia 0). Con `tools/debug_payment_jwt.php` (nuevo, prueba un JWT recién generado contra `GET /api/currencies/quote` con headers completos) se confirmó que el JWT en sí es válido (200 OK) — el error real es que `donate.js` nunca mandó **ningún** `Authorization` en los GETs directos a la API externa (`/api/currencies/quote`, `/api/payments/providers`). Cuando se diseñó esta integración (09/06) esos GETs eran públicos; en algún momento la API externa empezó a exigir rol `Player` ahí también y el frontend no se actualizó. Fix: nuevo endpoint `GET /api/donate/payment_token.php` (mismo `TokenService::generatePaymentJWT()` que ya usa `order.php`, detrás de `requireAuth()`) que le entrega al browser un JWT de pagos; `donate.js` (`getPaymentToken()`) lo pide una vez por click en "Calcular" y lo reusa para `quote` + `providers` de ese mismo cálculo. El POST de creación de orden no se tocó — ya generaba su propio JWT correctamente. Detalle completo en `.claude/docs/payment_integration.md` (Paso 2.5). Pendiente: confirmar en el VPS que la compra completa funciona de punta a punta.
- 2026-07-27 — [Feat] Nueva página `/donate/transferencia/` para el medio de pago "Transferencia Bancaria": explica que la acreditación es manual (no se acredita solo) y ofrece dos botones — WhatsApp (mismo link de comunidad que ya usaba `/donate/error/`) y "Generar reclamo de compra" (a `/reclamos/?mensaje=...` con un texto pre-armado, incluyendo el id de la orden si la API lo pasa por query param). Requirió agregar soporte a `?mensaje=` en `reclamos.js` (antes solo existía `?ver=id`). Estilo nuevo `.payment-result--pending` (acento violeta, ni éxito ni error) en `main.css`. Agregada a `build.php`. Pendiente: coordinar con la API externa que el `paymentUrl` del proveedor "Transferencia Bancaria" apunte a esta URL — sin eso la página no se muestra nunca (ver `.claude/docs/payment_integration.md`, Paso 5).
- 2026-07-22 — [Feat] ControlPanel: tab VIP para otorgar días de VIP Oro manualmente — mismo patrón que la acreditación de WCoin ya existente (lookup + grant, auditoría propia en mupga_admin.dbo.vip_grants vía database/controlpanel_vip_grants.sql). Reusa AccountRepository::getVIPStatus()/setVIP() (sp_SetAccountGOLDVIP) por la conexión principal (Database::get()) — no hizo falta ningún GRANT nuevo, el login DB_USER ya ejecuta ese SP desde usercp/buyvip.php.
- 2026-08-05 — [Feat] API de pagos externa confirmada activa por Franco: se repuntaron los links a WCoin en `layout.php` (nav) y `usercp/index.php` de `/donate2/` a `/donate/`. `/donate2/` (página estática temporal) queda sin linkear pero todavía en el repo — no se borró para poder volver atrás rápido si algo falla en producción; borrado final queda pendiente en Fase Tienda WCoin.
- 2026-08-05 — [Revert] Se pidió volver atrás el repunte de arriba: `layout.php` y `usercp/index.php` en `main` vuelven a apuntar a `/donate2/` (WCoin queda productivo en la página estática temporal, no en el flujo de la API externa). Sin cambios en `/donate2/` ni `/donate/` en sí — solo los 2 links de navegación.
- 2026-07-28 — [Feat] Popup promocional editable (ej. "EXP +200% de 0 a 50 RR"), módulo nuevo e independiente de `site_status` (ese está enganchado al lockdown de emergencia — no servía para esto). Tabla `mupga_admin.dbo.promo_popup` (fila única id=1, `database/controlpanel_promo_popup.sql`); `GET /api/site/promo.php` público (no-store, sí queda sujeto al 503 de `Lockdown.php` al no estar exento — correcto, no debe mostrarse en mantenimiento); `POST /api/admin/promo.php` con `requireAdmin()` + UPDLOCK/HOLDLOCK, valida que `cta_link` empiece con `/`, `http://` o `https://` (mismo criterio anti-`javascript:` que `renderRichText()`). Tab "🎉 Promo" en `/controlpanel/` reusa el dropzone/uploader de noticias (`POST /api/admin/upload.php`, sin modificarlo) para la imagen opcional. `loadPromoPopup()` en `app.js`: aparece una vez por sesión de browser (sessionStorage, por pestaña) al cargar cualquier página, cierre por ✕/click afuera/Escape, CTA como link o como botón que solo cierra si no se configuró `cta_link`. CSS `.promo-modal` calcado de `.donate2-modal`. Pendiente: correr el script SQL (local + VPS) y que Franco cargue el contenido real y lo active desde el panel.
- 2026-08-02 — [Feat] Tienda WCoin: flujo de Promociones implementado en `/donate/` a partir del contrato documentado por el equipo de la API de pagos (`.claude/docs/payment_integration.md`, Anexo). `donate/index.php` ahora tiene un selector de dos pestañas ("Compra personalizada" / "Promociones") dentro de `.store-shell`; el campo de email se movió fuera de `#exchange-main` para quedar compartido entre ambos flujos. Pestaña nueva carga `GET /api/promotions/active` (lazy, vía el mismo `payment_token.php` que ya usaban `quote`/`providers`) y renderiza tarjetas con proveedor auto-seleccionado (1 proveedor), selector (2+) o deshabilitadas (0). Compra vía proxy nuevo `src/public/api/donate/promotion_order.php` — mismo patrón que `order.php`: `requireAuth()` + `account` forzado desde el JWT, nunca desde el body del cliente; el `promotionId` va en el path de la API externa (`POST /api/promotions/{id}/orders`), no en el body. De paso se agregó manejo del `409 Conflict` ("cuenta con orden activa") documentado en el Anexo pero no implementado hasta ahora — mensaje específico compartido (`buildOrderErrorHtml()`) entre el flujo personalizado y el de promociones. `build.php` no necesitó cambios (no hay página nueva, todo vive dentro de `donate/index.php`). Detalle completo del Paso 6 en `.claude/docs/payment_integration.md`. Pendiente: probar contra la API externa real cuando `PAYMENTS_API_URL` esté configurada en el VPS — no se pudo probar end-to-end en esta sesión.
- 2026-08-02 — [UX] Tienda WCoin: ajuste de la implementación de Promociones del mismo día a pedido de Franco. El email dejó de ser un campo compartido siempre visible arriba de las dos pestañas — cada panel ahora pide solo lo que necesita: `#panel-personalizada` recuperó su propio `#inp-email` (vuelve a ser el primer campo del formulario, como antes de Promociones) y `#panel-promociones` tiene un `#inp-email-promo` propio y más simple (único campo antes de la grilla, sin selector de moneda/monto/proveedor/descuento). `switchTab()` ya alternaba paneles completos, así que el cambio fue solo de estructura (mover el email adentro de cada panel) — sin tocar la lógica de tabs. Nuevo estilo `.store-tab--promo` en `main.css`: borde y texto dorados en reposo, gradiente dorado (en vez del violeta de personalizada) cuando está activo, para que la pestaña de promociones resalte y llame la atención.
- 2026-08-02 — [Fix] Tienda WCoin: la pestaña de Promociones no ocultaba la de compra personalizada — ambas quedaban visibles y apiladas ("las promociones aparecen abajo de todo"), pese a que `switchTab()` seteaba `hidden` correctamente en JS. Causa: `.store-panel { display: flex; }` pisaba el `display:none` del atributo `hidden` (mismo bug que ya había pasado 4 veces antes en `main.css` — `.spinner[hidden]`, `.donate2-modal[hidden]`, `.cp-emoji-picker[hidden]`, `.reclamo-tab-badge[hidden]`); se agregó `.store-panel[hidden] { display: none; }` siguiendo el mismo patrón ya establecido. [Feat] De paso, comprar con "Transferencia Bancaria" ya no depende de que la API externa tenga configurado su `paymentUrl` — `donate.js` detecta ese proveedor por nombre (`isTransferProvider()`) y redirige siempre a `/donate/transferencia/` del lado del cliente (`goToPaymentDestination()`, usada por `onBuy()` y `onBuyPromotion()`), pasando `?orderId=` cuando la API lo devuelve. `/donate/transferencia/` se completó con lo que le faltaba: caja de alias `MUPGA.MP` con botón copiar, y un tercer botón de contacto por Discord (antes solo WhatsApp + reclamo). Detalle completo en `.claude/docs/payment_integration.md`, Paso 6 "Fixes de UX".
- 2026-08-02 — [Design] Tienda WCoin: contraste de texto secundario subido en todas las páginas de `/donate` (index, transferencia, success, error) a pedido de Franco. Labels, hints y texto de tarjetas que usaban `--text-dim` (#a099be, bajo contraste) pasan a `--text` (#e8e4f4): `.exchange-label`, `.exchange-email-hint`, `.exchange-quote-result`/`.quote-equals`/`.quote-discount-original`, `.promo-loading`/`.promo-card__arrow`/`.promo-card__provider-static`/`.promo-card__unavailable`, `.currency-picker__placeholder`/`.currency-option__code`, `.payment-result-note`, `.transfer-alias-label`, `.donate-hero-sub`, y el tab inactivo (`.store-tab`, con su hover subido a `--text-bright`). Se dejaron sin tocar el placeholder real del input de email (`::placeholder`, convención estándar de baja prominencia) y el ícono del chevron del picker (decorativo, no es texto). Cambio scopeado — las clases tocadas solo se usan en páginas de `/donate` (verificado con grep), no afecta el resto del sitio.
- 2026-08-11 — [Análisis] A pedido de Franco, se evaluaron dos iniciativas nuevas (foro + trade vía web) antes de tocar código. Foro: se encontró un plan de arquitectura ya commiteado (`Resumen Arquitectura login foro`) que no había avanzado — se decidió el motor (phpBB sobre SQL Server, ver Fase 8) comparando contra Flarum/Discourse/custom. Trade: se revisó el schema real de `CustomStore`/`CustomStoreOffline`/`PShopItemValue` (`database/script.sql`) y el repo del GameServer (`mu-s6-server`, sin fuente C++ disponible) — se confirmó que un marketplace P2P real no es viable solo desde la web (ver sección "Radar de Tiendas" arriba) y se acotó el alcance a una feature de solo lectura.
- 2026-08-11 — [Feat] Radar de Tiendas (`/tiendas/`): `ShopRepository.php` (lectura `CustomStore`+`PShopItemValue`), `GET /api/shops.php`, página con auto-refresh cada 30s mostrando personaje/nombre de tienda/slot/precio de cada tienda personal abierta. 100% lectura, sin escritura a ninguna tabla de juego. No identifica el ítem en sí (ver limitación en la sección de arriba). Nav: link "Radar" agregado.
- 2026-08-11 — [Feat] Foro (Fase 8, Etapa 1): `database/forum_setup.sql` (base `mupga_forum` dedicada + login `forum_admin` con `db_owner` acotado a esa base) y `runbooks/foro-setup-manual.md` (instalación de phpBB paso a paso: SQL, VirtualHost `foro.mupga.com.ar`, DNS, wizard de phpBB). Preparado para que Franco lo ejecute en el VPS — no se instaló nada todavía, esta sesión no tiene acceso al VPS.
- 2026-08-11 — [UX] Banner cruzado entre `/tienda/` (WCoin, oficial) y `/tiendas/` (Radar, tiendas de jugadores por Zen) — cada página muestra un banner con acento de color distinto (dorado = Tienda WCoin, cian = Radar) explicando la diferencia y linkeando a la otra, para que no se confundan. `.cross-link-banner` nueva en `main.css`; `renderTiendaCrossLink()`/`renderRadarCrossLink()` en sus respectivos JS.
- 2026-08-11 — [Revert] Radar de Tiendas eliminado por decisión de producto de Franco: funcionaba técnicamente (probado en el VPS contra tiendas reales, `CustomStore.Active=1` confirmado), pero la vista de solo slot/precio sin poder identificar el ítem no se entendía y no resultaba útil. Se borraron `src/db/ShopRepository.php`, `src/public/api/shops.php`, `src/public/tiendas/`, `src/public/assets/js/tiendas.js`, el link "Radar" del nav, la entrada en `build.php`, el require en `bootstrap.php`, el banner cruzado en `/tienda/` (código + CSS `.cross-link-banner`/`.shop-card`) y las menciones en `capability-matrix.md`. Se dejó en `data-dictionary.md` el schema real de `CustomStore`/`PShopItemValue` (documentación de tabla válida más allá de esta feature) y quedó registrado en la sección de arriba por qué no se siguió, para no re-evaluarlo desde cero si se retoma.
- 2026-08-11 — [Feat] Foro (Fase 8, Etapa 1) ejecutado en el VPS por Franco: `forum_setup.sql` corrido, phpBB 3.3.17 instalado sobre `mupga_forum` (driver SQL Server nativo, no ODBC), VirtualHost con bloques `:80` y `:443` (cert de Cloudflare, mismo patrón que `api.mupga.com.ar`), DNS proxied en Cloudflare, SMTP configurado con Gmail (`ssl://smtp.gmail.com:465`, auth LOGIN, contraseña de aplicación), carpeta `install/` borrada post-instalación. Troubleshooting del camino: fix de barras invertidas en el `DocumentRoot`/`Directory` del vhost (inconsistente con el resto del archivo), aclarado que Apache corre como servicio de Windows (no por XAMPP Control Panel) por lo que los reinicios son `Restart-Service`, y que el primer `curl` fallido fue por probarse contra `localhost` de la PC de Franco en vez del VPS. Board accesible en `https://foro.mupga.com.ar`, login de admin confirmado. Quedan pendientes de Etapa 1.5 los ítems de contenido/hardening (borrar demo, CAPTCHA, activación por email, estructura de categorías) antes de anunciarlo a los jugadores.
- 2026-08-11 — [Incidente] Franco quedó bloqueado del ACP de phpBB tras cambiar su contraseña de admin (ni la nueva clave ni "olvidé mi contraseña" funcionaban). El comando de CLI documentado por phpBB para este caso (`user:reset-password`) no existe en la build 3.3.17 instalada. Resuelto sin depender del mail: hash bcrypt generado con `php.exe -r "echo password_hash(...)"` y `UPDATE phpbb_users SET user_password = '<hash>' WHERE username_clean = 'ruggi'` directo en `mupga_forum` vía SSMS. Sirvió para confirmar en producción que el ACP de phpBB era más fricción de la que valía — fue parte de la decisión de descartarlo (ver Fase 8 arriba).
- 2026-08-11 — [Feat] Foro hardening v2 (Etapas 1-3 de `BACKLOG_FORO.md`, auditoría previa en `GAP_FORO.md`): migración SQL aditiva (`foro_migracion_v2.sql`), validador único server-side con 422 (`ForumValidation.php`), whitelist de esquema de links en el server, antiflood transaccional 30s/10 por hora (patrón Reclamos), personaje requerido para publicar, links restringidos a cuentas nuevas, sistema de reportes con cola en el ControlPanel + webhook Discord opcional, log de auditoría inmutable (`forum.moderation_log`), soft delete con papelera y restore (se eliminó el DELETE físico del módulo), ventana de edición 30 min con marca "editado por el staff", fix del lock que bloqueaba a admins, motivo de cierre visible, mover hilos, ban con vencimiento en días, sin autoagradecimiento, categorías con conteo/última actividad y categorías ocultas (404 a no-admins), y la API dejó de exponer `author_account` (`is_mine` server-side). Pendiente manual: correr la migración en SSMS y (opcional) `DISCORD_WEBHOOK_FORO`.
- 2026-08-11 — [Feat] Pivot de Fase 8: en vez de seguir invirtiendo en phpBB, foro reconstruido como módulo nativo de `web-mupga` (mismo patrón que Reclamos — usuarios crean, admin modera — reusando JWT/ControlPanel/diseño ya existentes). Backend: `database/foro_setup.sql` (schema `forum` en `mupga_admin`, login `mupga_forum_svc`, tablas categories/threads/posts/reactions/banned_accounts), `ForumDatabase`, `ForumRepository`, 8 endpoints públicos/usuario (`src/public/api/forum/`) y 3 admin (`forum_categories`, `forum_moderate`, `forum_ban`). El nombre mostrado en los posts es el personaje principal de la cuenta (`CharacterRepository::getMainCharacterName()`), resuelto y denormalizado al postear — igual criterio que `reclamos.reclamos.nick`. Se agregaron `optionalAuth()` a `Auth.php` (para que `/foro/hilo/` marque reacciones propias sin exigir sesión) e `isAdminAccount()` a `AdminAuth.php` (chequeo "dueño o admin" no bloqueante, reusado en editar/borrar). Frontend: `/foro/`, `/foro/categoria/`, `/foro/hilo/` + `foro.js` — moderación (fijar/cerrar/editar/borrar) inline en la página para admin y para el dueño del contenido, sin depender del ControlPanel para el día a día. ControlPanel: pestaña "💬 Foro" para CRUD de categorías y bans (acotados al foro, nunca tocan `MEMB_INFO.bloc_code` — no es lo mismo que banear la cuenta de juego). Reacciones: un solo tipo ("🙏 Agradecer"), toggle on/off, sin publicar. Bugs propios encontrados y corregidos antes de terminar: el id de categoría/hilo no llegaba en el build estático de Cloudflare Pages (faltaba el fallback a `URLSearchParams` client-side, mismo patrón que `guild.js`), y el botón de responder acumulaba listeners duplicados en cada re-render (cambiado de `addEventListener` a asignación `.onclick`). Pendiente: correr `foro_setup.sql` en el VPS y cargar las categorías iniciales (`runbooks/foro-web-setup-manual.md`).
- 2026-08-11 — [Feat] Foro Etapas 4-6 del backlog: migración `foro_migracion_v3.sql` (thread_follows, notifications, image_uploads), citar con permalink y un nivel de anidamiento, menciones @Personaje resueltas server-side solo contra participantes del foro + autocompletado acotado al hilo, seguir hilo con auto-follow y avisos agrupados, centro de notificaciones con campanita/panel/purga oportunista (respuesta·mención·gracias·moderación), barra de formato con toggle y atajos, vista previa, imágenes vía presigned PUT a bucket R2 separado (`FORO_R2_*`, cuota 20/día, `restrictImages()` degrada hosts ajenos), paginación real de hilos (25) y respuestas (20) con permalink `?post=X` resuelto server-side, y búsqueda `/foro/buscar/` (LIKE escapado, TOP 30, 2 queries fijas). `renderRichText()` ganó blockquote, imagen y mención (compartido con Noticias). Pendiente manual VPS: migraciones v2+v3 en SSMS y bucket `mupga-foro`.
- 2026-08-23 — [Fix] Auditoría de todos los horarios mostrados en el sitio a pedido de Franco (el sitio lo ven jugadores de varios países, la hora tiene que ajustarse sola al navegador de cada uno). Encontrado el mismo bug duplicado en 3 módulos: `fmtFecha()` (reclamos.js), `foroFmtFecha()` (foro.js) y `fmtFechaCp()` (controlpanel.js) formateaban el `created_at`/`banned_at`/etc. crudo (UTC, vía `GETUTCDATE()`) con un regex que solo recorta dígitos del string — nunca pasaba por un objeto `Date`, así que mostraba la hora UTC tal cual, rotulada como si fuera la hora de quien mira. Corregido: las tres funciones ahora arman un `Date` válido (`+ 'Z'` sobre el string normalizado) y leen los componentes en hora local del navegador — mismo patrón ya usado correctamente en `mudial.js`/`eventos.js` para los horarios de partidos/eventos, que no tenían este bug. `foroMsDesde()` (el "hace X min" relativo del foro) tampoco lo tenía — ya usaba `Date.UTC()` explícito. **Pendiente de verificar, no tocado:** `AccountExpireDate`/`CreatedAt` de `MEMB_INFO` (vencimiento VIP y fecha de alta de cuenta, mostrados en `/usercp/` y en la pestaña VIP del ControlPanel) son columnas del juego escritas por `sp_SetAccountGOLDVIP`/WebEngine, no por código propio — no está confirmado si ese SP usa `GETDATE()` o `GETUTCDATE()` internamente, y la hora local del SO del VPS ya demostró ser inestable dos veces (ver incidentes de timezone arriba). Antes de aplicarles el mismo fix hay que correr el diagnóstico ya documentado (`SELECT GETDATE(), GETUTCDATE(), SYSDATETIMEOFFSET()`) para no arriesgarse a introducir un desfase nuevo sobre un campo que hoy puede estar "casualmente" bien para un admin en Argentina.
- 2026-08-23 — [Fix] Editor de Eventos igualado al de Noticias, a pedido de Franco (el texto pegado desde una noticia se veía distinto en `/eventos/` porque la descripción no pasaba por `renderRichText()`, solo `esc()` + `<br>` manual — sin negrita/links/listas/banderas). La descripción del evento ahora tiene la misma barra de formato que Noticias (B/I/U/tachado, subtítulo, lista, link, emojis, vista previa — `initEvtEditor()`/`updateEvtPreview()` en `controlpanel.js`, calcadas de `initNewsEditor()`/`updateNewsPreview()`, reusando los mismos helpers `mdWrap`/`mdLinePrefix`/`mdLink`/`replaceSelection`/`NEWS_EMOJIS`). Del lado público, `eventos.js` ahora renderiza la descripción con `renderRichText()` dentro de un contenedor con la clase `news-article__body` (la misma que usa el artículo de Noticias) — mismo motor de render, misma tipografía, así el contenido se ve igual sin importar en qué módulo se escribió. `.evento-desc` se simplificó para solo controlar ancho/margen dentro de la card, la tipografía interna (párrafos/subtítulos/listas/links) la pone `news-article__body`. De paso, el título del evento también pasa por `renderFlags()` como el de Noticias.
- 2026-08-23 — [Fix] Banderas emoji invisibles en Noticias. Causa: Windows no dibuja banderas emoji — `Segoe UI Emoji` nunca incluyó esos glifos (política de Microsoft, no un bug del sitio), así que cualquier 🇦🇷 tipeado en una noticia se veía roto/vacío para cualquiera en Windows (la mayoría de los visitantes). Ya se había resuelto este mismo problema en el Prode reemplazando el emoji por imágenes de flagcdn.com (`TEAM_FLAGS` en `mudial.js`); acá se generalizó sin tabla: `renderFlags()` (nueva, en `app.js`) detecta cualquier par de "regional indicator symbols" (U+1F1E6–U+1F1FF, son 2 por bandera) y saca el código ISO de 2 letras directo de la aritmética de los codepoints, sin necesitar mapear banderas una por una. Aplica automáticamente al cuerpo de la noticia (ya pasaba por `renderRichText()`, que ahora la llama) y se sumó también a título/resumen (`news.js`, que antes solo hacía `esc()`). Como el resto de los módulos ya comparten `renderRichText()` (Foro, popup de Promo), las banderas también empiezan a andar ahí de rebote, sin tocar nada más.
- 2026-08-23 — [Design] Polish visual de `/eventos/` a pedido de Franco: el título de cada evento usaba `.account-card__title` (pensado para micro-labels tipo "WCoins" del ControlPanel, 0.68rem apagado) — reemplazado por `.evento-card__title` con degradé dorado y `--font-display`, mismo tratamiento que `.news-article__title` a escala de card. Descripción pasó de `--text-dim` a `--text` con más line-height para mejor lectura. Meta (fecha/cupo) ahora son chips en vez de texto plano; barra de cupo visual cuando hay `max_slots`; badge "⏰ Empieza en Xh Ym" reusando `.prode-badge--soon` sin CSS nuevo. La lista de anotados dejó de ser un `<ul>` pelado: ahora es un acordeón (mismo lenguaje que `.prode-rules-toggle`, chevron rotando) con un roster de chips con avatar circular de inicial + color determinístico por nombre (hash simple, 6 variantes dentro de la paleta del sitio) y entrada escalonada (`fadeUp` + `animation-delay` creciente por fila). Estado vacío con copy más invitador. CSS scopeado a `.evento-*`, sin tocar clases compartidas.
- 2026-08-23 — [Feat] `renderRichText()` (app.js, compartido por Noticias/Foro/ControlPanel) admite links internos: `[texto](/eventos/)` además de `[texto](https://url)`. Regla: arranca con `/` pero no `//` (bloquea URLs protocol-relative tipo `//evil.com`, que quedarían como texto plano sin matchear). Los externos se abren en pestaña nueva (`target="_blank"`), los internos en la misma pestaña. Sin cambios server-side — el body de Noticias ya era contenido 100% admin-trusted sin validación de esquema, mismo modelo de confianza que antes, solo se sumó una opción más al render. Hint del editor de Noticias en `/controlpanel/` actualizado para mostrar la sintaxis nueva.
- 2026-08-23 — [Feat] Módulo Eventos (Fase 9), a pedido de Franco para anotarse al Torneo PVP del viernes que viene (hora aún sin confirmar) y sumar cualquier evento futuro sin rehacer el módulo. Tablas genéricas `dbo.events`/`dbo.event_registrations` en `mupga_admin` (`database/controlpanel_events.sql`, sin base ni login nuevos — mismo criterio que `wcoin_credits`/`vip_grants`, `mupga_web_svc` ya tiene datareader+datawriter ahí). `EventsRepository.php` + endpoints públicos (`list`, `registrations`, `register`, `unregister`) y admin (`admin/events.php`: create/update/set_active/remove_registration, `requireAdmin()`). `register()` valida que el personaje pertenezca a la cuenta (`CharacterRepository::belongsToAccount()` contra la base del juego) y corre en transacción con `UPDLOCK/HOLDLOCK` sobre la fila del evento — mismo patrón anti-TOCTOU que `ProdeRepository::savePrediction()`/rate-limit de Reclamos — para serializar inscripciones concurrentes contra cupo y duplicados; cutoff temporal con `GETUTCDATE() < event_datetime` (nunca offsets fijos, ver incidentes de timezone arriba), `event_datetime` nullable ("a confirmar") deja las inscripciones abiertas hasta que se cargue una fecha. La lista pública de anotados solo expone el nombre de personaje, nunca la cuenta (mismo criterio de privacidad que el foro); el admin sí ve la cuenta, para poder dar de baja una inscripción puntual. Frontend `/eventos/` + `eventos.js` (fecha convertida a hora local del navegador igual que Prode, formulario de inscripción con selector de personajes de la cuenta vía `/api/account/profile.php`, lista de anotados expandible) y tab "🏆 Eventos" en `/controlpanel/` (alta/edición con `datetime-local`, activar/desactivar, ver anotados, quitar inscripción). Nav y `build.php` actualizados. Pendiente manual: correr `controlpanel_events.sql` en SSMS (mirror local + VPS) y cargar el Torneo PVP real desde el ControlPanel.
- 2026-09-01 — [Design] Pergamino "GRAN APERTURA MUPGA 04/09" flotando sobre el hero del inicio, para la apertura del 04/09. Es un elemento propio (`.hero-scroll`, hermano de `.hero-slider`), independiente del slider: `hero-apertura-scroll.png` (PNG con alfa) con animación CSS de respiración (float ±12px + balanceo ±0.7° en 5s, más un halo dorado/violeta que pulsa en sincro; respeta `prefers-reduced-motion`) y doble `drop-shadow` — la corta le hace contorno para que no se funda con los slides claros (img-3 nieve, img-5 cyan, img-6 gris). El slider rotativo se mantiene completo, ahora encabezado por `hero-apertura.jpg` (key art generado con Gemini, reescalado 2752x1536 → 2560x1429, JPG q85, 341 KB); `hero-slider.js` no se tocó. **Posición: siempre arriba a la derecha** (`top: 6%; right: 1.5rem`, ancho `clamp(140px, 14vw, 210px)`). Una primera versión lo ponía en el hueco entre el texto y el personaje en pantallas ≥1850px, pero ahí caía justo encima del "MUPGA Season 6" que los 10 slides viejos traen quemado en la imagen — anclarlo a la esquina lo mantiene lejos de ese título y del `.hero-content`, y de paso saca el breakpoint de 1850px (ojo con ese número si alguna vez se vuelve: un monitor de 1920 con Windows al 125% de escala reporta 1536px CSS). En ≤768px el pergamino baja a `top: 14%` y se le da `padding-right` al `.hero-tagline` (120px, 105px en ≤480px): es el único elemento que queda a su altura y, como el `.hero-content` va después en el DOM, sin ese hueco el párrafo le pasaba por encima. Validado con capturas headless en 360/390/768/1024/1280/1440/1600/1920 sobre el key art y sobre los slides claros. **Pendiente estético conocido** (preexistente, ajeno a este cambio): en los 10 slides viejos el "MUPGA Season 6" quemado se pisa con el `<h1>MuPGA</h1>` del hero.
- 2026-09-04 — [Feat] Pantalla de apertura: cuenta regresiva a pantalla completa que tapa todo el sitio hasta las 21:00 ARG del 04/09 (`2026-09-05T00:00:00Z`), dejando como única acción el botón REGISTRATE. Dos capas, mismo criterio que el modo overlay de mantenimiento ya existente: (1) visual — `assets/js/apertura.js` monta la pantalla (logo MUPGA, contador D/H/M/S, hora traducida a la zona del visitante, CTA, links de Discord/WhatsApp) y la sostiene con un `MutationObserver` si la borran desde DevTools; (2) real — `src/lib/AperturaGate.php`, enganchado en `_cors.php` igual que `Lockdown.php`, hace que TODA la API responda 503 salvo `auth/register.php`, `auth/login.php`, `site/status.php`, `site/hora.php` y `/api/admin/*`, así que borrar el div no sirve de nada. `/register/`, `/login/` y `/controlpanel/` no se tapan (el botón tiene que llevar a algún lado y el panel tiene que seguir siendo administrable — si no, queda el candado cerrado con la llave adentro); ahí se muestra una franja con el mismo contador. **Sincronización horaria:** el objetivo es un instante UTC absoluto (`src/config/apertura.php`, fuente única de verdad, inyectada al HTML por `layout.php` y horneada en el build de Cloudflare) — cada visitante lo ve traducido a su zona, mismo criterio que el Prode. Encima el contador se corrige contra el reloj del server con `GET /api/site/hora.php` (nuevo, sin DB, epoch UTC vía `time()` — nada de offsets fijos sobre la hora local del VPS, ver incidentes de timezone arriba), por si el visitante tiene mal el reloj del sistema. **Se apaga sola:** pasada la hora objetivo ni la pantalla ni el 503 se activan, sin deploy; los navegadores que estén mirando el contador recargan con un jitter de hasta 12s para no clavarle a la API un pico simultáneo, y un flag en `sessionStorage` evita bucles de recarga en clientes con el reloj atrasado. De paso `config.js` subió al `<head>` (apertura.js corre al abrir el `<body>` y necesita `MUPGA_CONFIG.api`) y `site/hora.php` quedó exenta también del lockdown de mantenimiento. Verificado con capturas headless en 360/390/768/1200/1440 y en el estado "llegó a cero". Para bajarla antes de hora: `runbooks/apertura-gate.md`.
- 2026-09-04 — [Fix] **Caída de la API entera** al deployar la pantalla de apertura: `AperturaGate.php` se escribió con `str_replace('\', '/', ...)` en lugar de `str_replace('\\', '/', ...)`. En PHP `'\'` es un string sin cerrar → parse error, y como `_cors.php` incluye ese archivo en el choke point de TODOS los endpoints, cada request de la API devolvía 500 sin cuerpo JSON. Síntoma en el sitio: "No se pudo conectar con el servidor" en login y registro (el `res.json()` de `login.js`/`register.js` tiraba excepción y caía al catch de red) — y solo ahí, porque el resto del sitio estaba tapado por la pantalla. Ninguna verificación previa lo agarró: el build de Cloudflare solo compila `layout.php` + `apertura.php`, `AperturaGate.php` nunca se ejecuta fuera del VPS, y en esta PC no hay PHP para correr `php -l`. Corregido (bytes verificados con `od -c` contra `Lockdown.php`) y blindado para que no vuelva a poder tumbar la API: `enforceApertura()` envuelto en try/catch fail-open, `_cors.php` chequea `is_file()` de config y lib antes de incluirlos (un deploy a medias ya no deja el sitio sin login), y las exenciones ahora se comparan contra `SCRIPT_NAME`/`SCRIPT_FILENAME`/`PHP_SELF` con sufijos cortos (`/auth/login.php` en vez de `/api/auth/login.php`) para no depender de dónde esté montada la app en Apache. Agregado a `runbooks/apertura-gate.md` el paso obligatorio de `php -l` + `curl` a login tras cada pull en el VPS.
- 2026-09-04 — [Feat] Pantalla de apertura, ajustes pedidos por Franco sobre la marcha: **/downloads/ destapada** (franja con el contador en vez de la pantalla completa) y `site/downloads.php` exento del 503, para que puedan bajar el launcher antes de la apertura; y **nadie queda logueado durante la cuenta regresiva** — `apertura.js` borra `mupga_token`/`mupga_user`/`mupga_admin` del navegador en cada carga, salvo en `/controlpanel/` (el admin necesita su sesión). Esto último además destrabó una ratonera real: `login.js` y `register.js` redirigen a `/usercp/` si encuentran un token y `/usercp/` está tapado, así que cualquiera con una sesión vieja quedaba encerrado en la pantalla sin poder llegar al registro; el borrado corre al abrir el `<body>`, antes de que esos scripts miren el localStorage en DOMContentLoaded. Para entrar al panel durante la ventana: `/login/?redirect=/controlpanel/`. Las dos cosas siguen atadas a la misma condición horaria, así que se levantan solas a las 21:00 sin deploy.
- 2026-09-04 — [Feat] Pantalla de apertura: segundo botón **DESCARGAR LAUNCHER** al lado de REGISTRATE (linkea a `/downloads/`, que ya está destapada). Va en una fila flex (`.apertura-gate__ctas`) que se apila sola en móvil; el secundario (`.apertura-gate__cta--alt`) usa caja oscura con borde dorado y sin el latido, para que el dorado lleno de REGISTRATE siga siendo el que se lleva la mirada. Cambio solo de frontend (no hace falta pull en el VPS). Verificado con capturas en 360/390/768/1440.
- 2026-09-04 — [Fix] El login parecía roto durante la cuenta regresiva: la limpieza de sesión del cambio anterior borraba el token en CADA carga, así que al loguearte te mandaba a `/usercp/`, ahí se borraba el token recién creado y `usercp.js` te rebotaba al formulario (loop). Rehecho con el criterio que pidió Franco — la sesión dura lo que dura la ventana abierta: `authStore()` en `auth.js` devuelve `sessionStorage` mientras la apertura esté en curso y `localStorage` después, y `getToken`/`getUser`/`setAuth` pasan por ahí (`clearAuth` limpia los dos almacenes, para que un logout no deje un token esperando en el otro). `apertura.js` ya no usa marca de purga: solo barre en cada carga lo que haya en `localStorage` (lo único que sobrevive a cerrar el navegador) y no toca `sessionStorage`, así el que se loguea a propósito sigue adentro. Al llegar a cero, `migrarSesionAlAbrir()` pasa la sesión de la ventana a `localStorage` para que nadie se caiga justo a las 21:00, cuando `auth.js` vuelve al modo normal. `login.js`: durante la ventana el destino por defecto post-login es `/downloads/` en vez de `/usercp/` (que está tapado y con su API en 503, así que caer ahí parecía que el login había fallado); el `?redirect=` sigue teniendo prioridad. Verificado con 9 chequeos en Edge headless (token viejo barrido, login nuevo en sessionStorage y no en localStorage, lectura correcta, vuelta a localStorage pasada la hora, y clearAuth limpiando ambos).
- 2026-09-04 — [Feat] Pantalla de apertura: **navbar recortado** mientras dura la cuenta regresiva, a pedido de Franco (los usuarios clickeaban Rankings/Foro/Tienda y caían en la pared). `filtrarNav()` en `apertura.js` deja solo Descargas, Login, Registrarme y ✦ Admin (más 'Salir', que maneja `auth.js`); los demás se **borran del DOM**, no se esconden con `hidden`, porque `updateNav()` de `auth.js` administra ese atributo en `[data-guest-show]`/`[data-auth-show]` y los volvería a mostrar. Corre en `DOMContentLoaded` (apertura.js se ejecuta al abrir el `<body>`, cuando el `<nav>` todavía no existe). [Fix] De paso, regresión del commit anterior detectada por el preview headless: al reescribir el bloque de sesiones se borró `montarFranja()` sin querer, y como `iniciar()` la llama, tiraba `ReferenceError` en las cuatro páginas destapadas — sin franja de contador y sin tick ni sync de reloj ahí (la pantalla completa del resto del sitio no estaba afectada, usa `montarPantalla()`). Restaurada y verificado que toda función llamada esté definida.
- 2026-09-04 — [Feat] Pantalla de apertura: `online.php` y `serverinfo.php` exentos del 503, a pedido de Franco, para que los widgets del sidebar (jugadores conectados y estadísticas del server: temporada, exp, drop, online, registrados) funcionen en las páginas destapadas — antes mostraban '—' y 'Sin datos'. Los dos son de solo lectura y datos públicos: `getOnlineCount()` y un `COUNT(*)` sobre `MEMB_INFO`, sin escrituras ni info de cuentas. Cambio solo de API: requiere `git pull` en el VPS, no toca el frontend.
