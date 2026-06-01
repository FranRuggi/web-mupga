# MuPGA Web — Roadmap

> **Checklist vivo.** Claude Code lo actualiza al completar cada tarea: marcar `[x]`, y
> agregar una línea con fecha en "Registro de cambios" al final.

**Estado actual:** Fase 1 — Completada y aprobada. Listo para Fase 2.
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
- [ ] Módulo de conexión a SQL Server (PDO/sqlsrv) con sentencias preparadas
- [ ] Funciones de solo-lectura seguras (rankings, online, info de cuenta/personaje)
- [ ] Funciones de escritura controlada (registro, reset password, créditos WCoin)
- [ ] Credenciales por variables de entorno (no hardcodeadas)

## Fase 3 — Frontend custom
- [ ] Migrar el diseño del template `mupga` a `src/` (sin dependencias de WebEngine)
- [ ] Layout base: header, footer, navegación
- [ ] Home + serverinfo (rates, Chaos Machine, comandos, eventos)

## Fase 4 — Features por capacidad
- [ ] Rankings (resets, PvP, guilds)
- [ ] Registro de cuenta + login
- [ ] Panel de cuenta (estado VIP, saldo WCoin)
- [ ] CashShop / WCoin
- [ ] Otros, según lo que habilite la matriz de capacidades

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
