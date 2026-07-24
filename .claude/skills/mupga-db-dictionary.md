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
   hasta confirmarla en `database/script.sql`.
6. El Master Level/Points **no están en `Character`** — viven en `MasterSkillTree`
   (columnas `MasterLevel`, `MasterPoint`, `MasterExperience`, `MasterSkill`).
7. WCoin se acredita con el SP `sp_AddWCoinWithLog` (ver `src/db/CreditsRepository.php`), nunca
   escribiendo `CashShopData` a mano salvo dentro de una transacción con lock (ver `buyvip.php`
   como ejemplo). **Nunca** usar `WEBENGINE_CREDITS_CONFIG` ni ninguna tabla `WEBENGINE_*` —
   son del CMS reemplazado y no existen en producción (regla dura en `CLAUDE.md`).

## Referencia rápida de tablas críticas

- Cuentas: `MEMB_INFO` (PK: `memb___id`)
- Estado online: `MEMB_STAT` (PK: `memb___id`, columna `ConnectStat`)
- Personajes: `Character` (PK: `Name`)
- Personajes por cuenta: `AccountCharacter` (PK: `Id`)
- Guilds: `Guild` (PK: `G_Name`) + `GuildMember`
- WCoin: `CashShopData` (PK: `AccountID`)
- Tablas del CMS: prefijo `WEBENGINE_*`
