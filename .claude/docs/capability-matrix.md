# Matriz de Capacidades — MuPGA Web

> Clasifica cada operación posible desde el sitio web como SEGURA, RIESGOSA o PROHIBIDA.
> Generado el 2026-06-01 a partir del análisis del código WebEngine y el schema de la DB.
>
> **Principio rector:** el GameServer mantiene el estado de los personajes CONECTADOS en memoria
> y lo escribe en la DB al desconectar. En la mayoría de los CMS de MU esto lleva a verificar
> offline antes de escribir; sin embargo, en el servidor MuPGA (MuEmu Louis v31) las operaciones
> sobre `Character` y `MEMB_INFO` son seguras incluso con el jugador conectado — verificado en
> producción sin casos de corrupción. La única excepción son los campos binarios propietarios
> (Inventory, MagicList, Quest, Warehouse) que solo el GameServer puede serializar correctamente.

---

## Semáforo de referencia rápida

| Color    | Significado                                                                        |
|----------|------------------------------------------------------------------------------------|
| SEGURA   | Se puede hacer sin restricciones (lectura o escritura, online u offline)           |
| PROHIBIDA| No hacer nunca desde la web, independientemente del estado del jugador             |

---

## OPERACIONES DE LECTURA (todas SEGURAS)

| Operación                                 | Tabla(s)                             | Notas                                           |
|-------------------------------------------|--------------------------------------|-------------------------------------------------|
| Consultar información de cuenta           | `MEMB_INFO`                          | Solo lectura, sin riesgo                        |
| Verificar si cuenta existe                | `MEMB_INFO`                          | Solo lectura                                    |
| Verificar si email existe                 | `MEMB_INFO`                          | Solo lectura                                    |
| Consultar estado online/offline           | `MEMB_STAT` (ConnectStat = 1)        | Fuente de verdad para verificar antes de escribir |
| Ver lista de personajes de una cuenta     | `AccountCharacter`, `Character`      | Solo lectura                                    |
| Ver datos de un personaje (nivel, clase, mapa) | `Character`                     | Solo lectura; datos pueden estar desactualizados si está online |
| Rankings de nivel, resets, master, kills  | `Character`                          | Solo lectura; data puede estar desfasada si el GS no persistió aún |
| Rankings de guilds                        | `Guild`, `GuildMember`, `Character`  | Solo lectura                                    |
| Rankings de eventos (BC, CC, DS, IT, PvP) | `RankingBloodCastle`, etc.           | Solo lectura; los actualiza el GS               |
| Rankings GvG, TvT, Battle Royale          | `RankingGvG`, `RankingTvT`, etc.     | Solo lectura                                    |
| Rankings Gens                             | `Gens_Duprian`, `Gens_Varnert`, `Gens_Rank` | Solo lectura                           |
| Ver saldo WCoin / GoblinPoint             | `CashShopData`                       | Solo lectura; puede estar desfasado si está online |
| Ver historial de WCoin                    | `CashLog`                            | Solo lectura                                    |
| Conteo de jugadores online                | `MEMB_STAT` (COUNT WHERE ConnectStat=1) | Operación segura y frecuente                 |
| Info del Castle Siege (propietario, tax)  | `MuCastle_DATA`                      | Solo lectura                                    |
| Info de noticias del sitio                | `mupga_admin.dbo.news`               | Solo lectura; reemplaza a `WEBENGINE_NEWS`      |
| Ver descargas                             | `mupga_admin.dbo.downloads`          | Solo lectura; reemplaza a `WEBENGINE_DOWNLOADS` |
| Ver votos mensuales / logs y config de créditos | — | **Pendiente**: existían en WebEngine (`WEBENGINE_VOTE_LOGS`, `WEBENGINE_CREDITS_LOGS`, `WEBENGINE_CREDITS_CONFIG`) pero esas tablas están prohibidas (regla dura "nunca usar tablas `WEBENGINE_*`" en `CLAUDE.md`) y `src/` no tiene reemplazo propio todavía — no implementar hasta crear tabla propia |

---

## OPERACIONES DE ESCRITURA — SEGURAS

Estas escrituras son seguras tanto si el jugador está online como offline en MuPGA (MuEmu Louis v31).
Algunas afectan tablas propias del sitio nuevo (`mupga_admin.dbo.*`, `MUPGA_ACCOUNT_COUNTRY`),
otras escriben directamente sobre datos de juego — verificado en producción sin casos de
corrupción. Ninguna usa tablas `WEBENGINE_*` (prohibidas, ver `CLAUDE.md`).

> **Nota sobre el chequeo online (actualizado 2026-06-09):** todos los endpoints de escritura sobre
> `Character` verifican `MEMB_STAT.ConnectStat` antes de ejecutar. Si la cuenta está conectada
> devuelven HTTP 409 con el mensaje "Cuenta en línea. Desconectate del servidor para continuar."
> Esto sigue el mismo criterio que WebEngine y es la política adoptada para MuPGA.

| Operación                                          | Tabla(s)                                        |
|----------------------------------------------------|-------------------------------------------------|
| Registrar nueva cuenta                             | `MEMB_INFO`, `AccountCharacter`, `CashShopData` |
| Cambiar contraseña                                 | `MEMB_INFO` (`memb__pwd`)                       |
| Cambiar email                                      | `MEMB_INFO` (`mail_addr`)                       |
| Bloquear / desbloquear cuenta                      | `MEMB_INFO` (`bloc_code`)                       |
| Activar / modificar VIP (`sp_SetAccountGOLDVIP`)   | `MEMB_INFO` (`AccountLevel`, `AccountExpireDate`)|
| Agregar WCoin (`sp_AddWCoinWithLog`)               | `CashShopData` (`WCoinC`), `CashLog`            |
| Modificar WCoinP / GoblinPoint (`WZ_SetCoin`)      | `CashShopData` (`WCoinP`, `GoblinPoint`)        |
| Unstick (mover personaje al mapa de Lorencia)      | `Character` (`MapNumber`, `MapPosX`, `MapPosY`) |
| Limpiar estado PK                                  | `Character` (`PkLevel`, `PkTime`)               |
| Reset de stats                                     | `Character` (`Strength`, `Dexterity`, `Vitality`, `Energy`, `Leadership`, `LevelUpPoint`) |
| Agregar puntos de stats                            | `Character` (idem)                              |
| Reset de árbol Master                              | `MasterSkillTree` (`MasterLevel`, `MasterPoint`) — **no** `Character.mlPoint`, esa columna no existe |
| Reset de personaje (nivel, clase, stats, resets)   | `Character` (`cLevel`, `Class`, `Strength`, etc.) |
| Actualizar contadores de reset (`WZ_SetResetInfo`) | `Character` (`ResetCount`, `ResetDay`, `ResetWek`, `ResetMon`) |
| Actualizar master reset (`WZ_SetMasterResetInfo`)  | `Character` (`MasterResetCount`)                |
| Crear/editar/eliminar noticias                     | `mupga_admin.dbo.news`                          |
| Crear/editar descargas                             | `mupga_admin.dbo.downloads`                     |
| Actualizar país del usuario                        | `MUPGA_ACCOUNT_COUNTRY` (tabla propia; ver `src/public/api/auth/{login,register}.php`) |

**Pendiente / no implementado** (existía en WebEngine, no tiene reemplazo propio en `src/`
todavía — no usar las tablas `WEBENGINE_*` originales, están prohibidas):
verificación de email al registrarse, IPs bloqueadas, configuración/log de créditos, sistema
de votos, log de transacciones PayPal (ahora hay una API de pagos externa aparte, ver Tienda
WCoin en `ROADMAP.md`), solicitud de cambio de contraseña por email (reset sin sesión),
gestión de bans del sitio.

**Riesgoso / roto en producción:** `src/public/api/auth/login.php` intenta loguear intentos
fallidos de login en `WEBENGINE_FLA` para anti-brute-force, envuelto en `try/catch` "por si la
tabla no existe". Como esa tabla está prohibida y no existe en producción, **el catch siempre
se dispara y el anti-brute-force queda desactivado silenciosamente** — no hay límite de
intentos de login activo hoy. Hace falta una tabla propia (o un mecanismo distinto) para
recuperar esta protección.

---

## OPERACIONES DE ESCRITURA — PROHIBIDAS

Estas operaciones **NUNCA** deben hacerse desde la web mientras el GameServer está corriendo,
independientemente del estado online/offline del jugador. El riesgo de corrupción de datos
es demasiado alto o el impacto en el GameServer sería impredecible.

| Operación                                       | Tabla(s)                         | Por qué está prohibida                                                                          |
|-------------------------------------------------|----------------------------------|-------------------------------------------------------------------------------------------------|
| Escribir directamente al inventario             | `Character.Inventory` (varbinary)| Formato binario propietario del GS; cualquier byte mal escrito corrompe el inventario. Solo el GS sabe cómo serializar/deserializar esto. |
| Escribir a la lista de habilidades              | `Character.MagicList` (varbinary)| Igual que Inventory. El SP `MMK_SkillMaker` es el único acceso seguro (herramienta de admin interna). |
| Escribir a `Character.Quest` (varbinary)        | `Character.Quest`                | Formato binario del GS; corrupción irreversible de quests.                                      |
| Modificar XP/Experiencia directamente           | `Character.Experience`           | El GS recalcula stats derivados de XP; modificar directamente puede desincronizar el estado.    |
| Escribir en tablas de almacén (Warehouse, WarehouseGuild) | `Warehouse`, `WarehouseGuild` | Formato binario propietario del GS.                                                   |
| Crear/eliminar ítems en inventario desde web    | `Character.Inventory`            | Ver arriba. Usar `WZ_GremoryCase_AddItem` para entregar ítems de forma controlada.              |
| Modificar datos de Guild mientras están en Castle Siege | `Guild`, `MuCastle_*`    | El GS gestiona el estado del CS en tiempo real; escrituras web causarían inconsistencias graves.|
| Escribir en tablas de eventos activos (BC, CC, DS, IT) | `RankingBloodCastle`, etc.| El GS escribe estos rankings en tiempo real; las escrituras web serían sobreescritas de inmediato. |
| Ejecutar SPs de Castle Siege (`WZ_CS_*`) desde la web | `MuCastle_DATA`, etc.      | Estos SPs están diseñados para ser llamados por el GameServer con su lógica interna.            |
| Modificar `Character.CtlCode` directamente     | `Character`                      | Puede afectar el estado de control del GS sobre el personaje.                                   |
| Modificar tablas Custom del servidor (CustomAttack, CustomStore, etc.) | Varias | Estas tablas son gestionadas exclusivamente por el GS durante la partida.           |
| Modificar Gens directamente                     | `Gens_Duprian`, `Gens_Varnert`   | El GS actualiza Gens en tiempo real durante el juego.                                           |
| Modificar saldo de matrimonio / amigos / correo interno | `Marry`, `T_FriendList`, etc. | El GS gestiona estas relaciones en tiempo real.                                      |

---

## Resumen de features posibles para el sitio custom

### Features SEGURAS (se pueden implementar)
- **Rankings** (nivel, resets, master resets, kills/PK, guilds, Gens) — solo lectura
- **Conteo de jugadores online** — solo lectura de MEMB_STAT
- **Info de personaje** (nivel, clase, mapa, stats, resets) — solo lectura; aclarar que puede estar desactualizado si está online
- **Info de cuenta** (username, email, estado VIP, saldo WCoin) — solo lectura
- **Registro de cuenta** — escritura solo en MEMB_INFO de cuenta nueva
- **Login y gestión de sesión** — validación en MEMB_INFO; el anti-brute-force por IP está
  **roto en producción** (ver nota en la sección de escritura más arriba) — pendiente de
  arreglar con una tabla propia
- **Cambio de contraseña** (con verificación offline) — escritura en MEMB_INFO
- **Noticias y descargas** — tablas propias `mupga_admin.dbo.news` / `dbo.downloads`
- **Sistema de votos** (reward en WCoin o créditos) — **pendiente**, requiere tabla propia
  (no hay reemplazo de `WEBENGINE_VOTES` todavía)
- **Página de información del servidor** (rates, comandos, eventos) — datos estáticos

### Features de escritura sobre datos de juego (SEGURAS, online u offline)
- **Recarga de WCoin** (donaciones) — vía `sp_AddWCoinWithLog`
- **Activación de VIP** — vía `sp_SetAccountGOLDVIP`
- **Reset de personaje** — escritura en `Character`
- **Reset de stats** — escritura en `Character`
- **Add stats** — escritura en `Character`
- **Clear PK** — escritura en `Character`
- **Unstick** — escritura en `Character`

### Features NO implementables desde la web
- Gestión de inventario / ítems
- Sistema de Castle Siege
- Cualquier escritura de datos binarios del GS

---

## Checklist de seguridad antes de cualquier escritura en tablas de juego

1. ¿La tabla está en la lista PROHIBIDA? → No escribir, punto.
2. ¿La escritura es en un campo binario (`varbinary`)? → Nunca escribir directamente desde la web.
3. Usar siempre PDO con sentencias preparadas. Nunca concatenar input del usuario.
4. Usar los nombres reales de columnas de la DB (ej. `ResetCount`, no el alias PHP `RESETS`).
