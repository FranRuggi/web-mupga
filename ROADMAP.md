# MuPGA Web — Roadmap

> **Checklist vivo.** Claude Code lo actualiza al completar cada tarea: marcar `[x]`, y
> agregar una línea con fecha en "Registro de cambios" al final.

**Estado actual:** Fase 4 completada. Listo para revisión y ajustes finales.
**Última actualización:** 2026-06-01

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

## Fase 4 — Features por capacidad
- [x] Rankings (resets, nivel, master, PK, guilds) — completado en Fase 3
- [x] Registro de cuenta + login (token-based, HMAC-SHA256) — 2026-06-01
- [x] Panel de cuenta (VIP, WCoin, personajes, cambio password/email) — 2026-06-01
- [x] Página de donaciones (UI + placeholder DONATION_URL en .env) — 2026-06-01
- [x] CORS listo para Pages + VPS separados (`_cors.php`) — 2026-06-01
- [ ] Completar datos/info.json con valores reales del servidor

## Fase 5 — Deploy y testing
- [ ] Pipeline local → VPS
- [ ] Pruebas contra la DB real en el VPS
- [ ] Pasar `develop` a producción

---

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
