# MuPGA Web — Roadmap

> **Checklist vivo.** Claude Code lo actualiza al completar cada tarea: marcar `[x]`, y
> agregar una línea con fecha en "Registro de cambios" al final.

**Estado actual:** Fase 4 completa ✅ — Fase 5 en curso + Tienda WCoin integrada.
**Última actualización:** 2026-06-09

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
- [x] config.js creado apuntando a `https://api.mupga.com.ar` — 2026-06-02
- [x] Completar `data/info.json` con valores reales del servidor — 2026-06-09
- [ ] Completar `data/downloads.json` con URLs reales del cliente (pendiente Franco)

## Tienda WCoin — Integración con API de pagos externa
- [x] Config: `PAYMENTS_API_URL` en `.env.example`; `paymentsApi` en `config.js`; `data-payments-url` inyectado en layout — 2026-06-09
- [x] Proxy PHP `api/donate/order.php`: valida JWT, extrae Account, reenvía a API externa — 2026-06-09
- [x] UI exchange: `donate/index.php` + `donate.js` (conversor estilo exchange con flujo completo) — 2026-06-09
- [x] Páginas post-pago: `donate/success/` y `donate/error/` — 2026-06-09
- [x] `build.php` actualizado con las 3 páginas nuevas (donate, success, error) — 2026-06-09
- [ ] Configurar `PAYMENTS_API_URL` en `.env` del VPS cuando la API externa esté lista
- [ ] Configurar CORS en la API externa para permitir el origen del frontend (para GETs directos)

## Fase 5 — Deploy y testing

> Guía completa paso a paso en `docs/deploy.md`.

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

### VPS — pasos manuales (seguir `docs/deploy.md`)
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
- [ ] Ejecutar `database/prode_setup.sql` en SQL Server del VPS (manual — ver PASOS_MANUALES_PRODE.md)
- [ ] Configurar variables PRODE_DB_* y ADMIN_TOKEN en el .env del VPS (manual)
- [ ] Cargar primeros partidos vía admin_match.php (manual)

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
