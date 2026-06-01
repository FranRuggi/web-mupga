# MuPGA Web — sitio custom

Sitio web propio para el servidor privado de MU Online Season 6 **MuPGA** (MuEmu Louis v31).
Reemplaza progresivamente a WebEngine con un sitio PHP a medida, sin las limitaciones del CMS.

Idioma de trabajo: **español (Rioplatense)**. Comentarios de código y mensajes de commit en español.

## Estado actual

**Leé `ROADMAP.md` siempre al iniciar sesión** — es la fuente de verdad del progreso.
Fase activa: **Fase 1 — Ingeniería inversa** (ver roadmap).

## Arquitectura

- **Desarrollo (PC de Franco):** sitio servido con XAMPP, base de datos espejo en SQL Server
  Express (schema restaurado desde el dump de producción).
- **Producción (VPS):** el sitio se despliega en el mismo VPS donde ya viven el SQL Server y el
  GameServer. La conexión sitio → base de datos es **local dentro del VPS**, nunca desde una
  máquina externa.
- Base de datos: **Microsoft SQL Server** (no MySQL). Driver: `sqlsrv` / `PDO_SQLSRV`.

## Reglas duras (no negociables)

1. **Nunca** escribir en tablas de juego (Character, inventario, ítems, zen, stats) de un
   personaje conectado mientras el GameServer corre: el server mantiene el estado en memoria y
   pisa los cambios → corrupción/duplicación de datos. Antes de cualquier escritura, consultar
   `.claude/docs/capability-matrix.md`.
2. **Siempre** PDO/sqlsrv con **sentencias preparadas**. Nunca concatenar input del usuario en
   SQL. (Este proyecto tiene foco fuerte en anti-cheat y prevención de abuso.)
3. **No inventar el schema.** Toda estructura de DB sale de leer `htdocs/` (código WebEngine) y
   el dump `script.sql`. Si algo no está confirmado ahí, marcarlo como "a verificar".
4. `htdocs/` es **solo referencia / lectura**. No modificar WebEngine. El sitio nuevo vive en `src/`.
5. Nunca commitear credenciales ni datos de jugadores (ya cubierto en `.gitignore`).
   Credenciales del sitio → variables de entorno, jamás hardcodeadas.

## Flujo de trabajo (importante)

- **Al iniciar cada sesión:** leé `ROADMAP.md` para saber en qué fase y tarea estás.
- **Al completar cualquier tarea:** actualizá `ROADMAP.md` — marcá el ítem con `[x]`, agregá la
  fecha y una línea en "Registro de cambios" con lo que se hizo. El roadmap es un checklist vivo.
- **Antes de avanzar de fase:** la fase anterior tiene que estar completa y revisada por Franco.
- Mantené este `CLAUDE.md` corto. El detalle pesado va en `.claude/docs/` (se lee on-demand para
  no gastar tokens en cada sesión).

## Estructura del repo

- `CLAUDE.md` — este archivo (reglas del proyecto).
- `ROADMAP.md` — checklist vivo de fases y tareas.
- `.claude/skills/` — skills del proyecto (se crean en Fase 1).
- `.claude/docs/` — referencia pesada que leen los skills on-demand:
  - `data-dictionary.md` — tablas, columnas, stored procedures (se genera en Fase 1).
  - `capability-matrix.md` — qué es seguro / riesgoso / prohibido en la DB (Fase 1).
- `script.sql` — dump productivo en la raíz (gitignoreado, solo lectura local).
- `db/schema/` — exports de schema-only, versionables (opcional).
- `htdocs/` — código WebEngine actual (referencia, solo lectura).
- `src/` — el sitio custom nuevo.

## Objetivo del producto

Con base en la matriz de capacidades, definir qué features se le pueden ofrecer al cliente de
forma segura (rankings, conteo de online, info de cuentas/personajes, registro, reset de
password, créditos de WCoin/CashShop, estado VIP) y dejar documentado qué NO se puede hacer
desde la web por riesgo de corromper datos del juego. Esa matriz decide si el sitio custom
reemplaza a WebEngine o no.
