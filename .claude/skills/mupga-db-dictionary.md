# Skill: mupga-db-dictionary

Sos un agente trabajando en el sitio custom PHP de MuPGA (MU Online Season 6).
Antes de generar o modificar cualquier query SQL, leé `.claude/docs/data-dictionary.md`
para conocer el nombre exacto de tablas, columnas, tipos y stored procedures.

## Instrucciones

1. **Leé siempre** `.claude/docs/data-dictionary.md` antes de escribir cualquier SQL.
2. Usá los nombres reales de las columnas (no los alias PHP `_CLMN_*_`).
   Ejemplo: `memb___id` en vez de `_CLMN_USERNM_`, `ConnectStat` en vez de `_CLMN_CONNSTAT_`.
3. La base de datos es **Microsoft SQL Server** — usá sintaxis T-SQL:
   - `TOP N` en vez de `LIMIT N`
   - `GETDATE()` en vez de `NOW()`
   - Tipos: `varchar`, `int`, `smalldatetime`, `varbinary`, etc.
4. Driver PHP: `PDO_SQLSRV`. Parámetros con `?` o `:nombre`. Nunca concatenar input del usuario.
5. Si una tabla o columna no está en el diccionario, marcala como **"a verificar"** y no la uses
   hasta confirmarla en `script.sql` o `htdocs/`.
6. La tabla `Character` en IGCN S6 contiene tanto datos de personaje como de Master Level
   (columnas `mLevel`, `mlPoint`, `mlExperience`).
7. El sistema de créditos es dinámico: la tabla y columna objetivo están en `WEBENGINE_CREDITS_CONFIG`,
   no hardcodeadas — usá el sistema de la clase `CreditSystem` de WebEngine como referencia.

## Referencia rápida de tablas críticas

- Cuentas: `MEMB_INFO` (PK: `memb___id`)
- Estado online: `MEMB_STAT` (PK: `memb___id`, columna `ConnectStat`)
- Personajes: `Character` (PK: `Name`)
- Personajes por cuenta: `AccountCharacter` (PK: `Id`)
- Guilds: `Guild` (PK: `G_Name`) + `GuildMember`
- WCoin: `CashShopData` (PK: `AccountID`)
- Tablas del CMS: prefijo `WEBENGINE_*`
