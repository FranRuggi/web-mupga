# Skill: mupga-db-safety

Sos un agente trabajando en el sitio custom PHP de MuPGA (MU Online Season 6).
Antes de escribir **cualquier operación de escritura** en la base de datos, leé
`.claude/docs/capability-matrix.md` para verificar si la operación es segura.

## Instrucciones críticas

1. **Leé siempre** `.claude/docs/capability-matrix.md` antes de escribir cualquier INSERT,
   UPDATE o DELETE sobre tablas de juego.

2. **Regla de oro:** si el campo es `varbinary` (Inventory, MagicList, Quest, Warehouse, etc.)
   → NUNCA escribir desde la web. Estos son formatos binarios propietarios del GameServer.

3. **En MuPGA NO se requiere verificar estado offline** antes de escribir en `Character` o
   `MEMB_INFO`. Las operaciones como reset de stats, clear PK, unstick, WCoin, VIP son seguras
   con el jugador online en el servidor MuEmu Louis v31 (verificado en producción).

4. **Operaciones PROHIBIDAS** (nunca implementar desde la web):
   - Escribir en `Character.Inventory`, `Character.MagicList`, `Character.Quest` (binarios propietarios)
   - Escribir en `Warehouse`, `WarehouseGuild` (binarios propietarios)
   - Llamar SPs de Castle Siege (`WZ_CS_*`) desde la web
   - Escribir en tablas de eventos activos (`RankingBloodCastle`, etc.) — el GS las gestiona en tiempo real

5. **Operaciones SEGURAS** (cualquier escritura no binaria):
   - Todas las tablas `WEBENGINE_*` — sin restricciones
   - `Character` (campos no binarios): stats, resets, level, PK, mapa, zen, etc.
   - `MEMB_INFO`: password, email, bloc_code, AccountLevel, etc.
   - `CashShopData`: WCoinC, WCoinP, GoblinPoint (preferir SP `sp_AddWCoinWithLog`)
   - `MEMB_INFO` para VIP (preferir SP `sp_SetAccountVIP`)

6. Usá siempre **PDO con sentencias preparadas**. Nunca concatenar input del usuario en SQL.

7. Usá los **nombres reales de columnas de la DB**: `ResetCount` (no `RESETS`),
   `MasterResetCount` (no `GrandResets`). Ver `.claude/docs/data-dictionary.md`.

## Checklist express antes de cualquier escritura

- [ ] ¿Es un campo varbinary? → Prohibido.
- [ ] ¿Son SPs de Castle Siege o tablas de eventos? → Prohibido.
- [ ] ¿Usás sentencias preparadas? → Obligatorio.
- [ ] ¿Usás el nombre real de columna de la DB? → Verificar en data-dictionary.md.
