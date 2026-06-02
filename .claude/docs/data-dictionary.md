# Diccionario de Datos — MuPGA / MuOnline Season 6

> Generado el 2026-06-01 a partir de `script.sql` (dump productivo) y del código PHP de `htdocs/`.
> Base de datos: **Microsoft SQL Server** — base `MuOnline` (juego) y misma base para tablas WebEngine.
> Driver PHP: `sqlsrv` / `PDO_SQLSRV`.

---

## Constantes PHP → nombre real (igcn.tables.php)

| Constante PHP         | Tabla / Columna real         |
|-----------------------|------------------------------|
| `_TBL_MI_`            | `MEMB_INFO`                  |
| `_CLMN_USERNM_`       | `memb___id`                  |
| `_CLMN_PASSWD_`       | `memb__pwd`                  |
| `_CLMN_MEMBID_`       | `memb_guid`                  |
| `_CLMN_EMAIL_`        | `mail_addr`                  |
| `_CLMN_BLOCCODE_`     | `bloc_code`                  |
| `_CLMN_CTLCODE_`      | `ctl1_code`                  |
| `_CLMN_SNONUMBER_`    | `sno__numb`                  |
| `_CLMN_MEMBNAME_`     | `memb_name`                  |
| `_TBL_MS_`            | `MEMB_STAT`                  |
| `_CLMN_CONNSTAT_`     | `ConnectStat`                |
| `_CLMN_MS_MEMBID_`    | `memb___id`                  |
| `_CLMN_MS_GS_`        | `ServerName`                 |
| `_CLMN_MS_IP_`        | `IP`                         |
| `_CLMN_MS_ONLINEHRS_` | `OnlineHours`                |
| `_TBL_AC_`            | `AccountCharacter`           |
| `_CLMN_AC_ID_`        | `Id`                         |
| `_CLMN_GAMEIDC_`      | `GameIDC`                    |
| `_TBL_CHR_`           | `Character`                  |
| `_CLMN_CHR_NAME_`     | `Name`                       |
| `_CLMN_CHR_ACCID_`    | `AccountID`                  |
| `_CLMN_CHR_CLASS_`    | `Class`                      |
| `_CLMN_CHR_ZEN_`      | `Money`                      |
| `_CLMN_CHR_LVL_`      | `cLevel`                     |
| `_CLMN_CHR_RSTS_`     | `ResetCount` (el PHP config dice `RESETS` pero la DB real usa `ResetCount`) |
| `_CLMN_CHR_GRSTS_`    | `MasterResetCount` (el PHP config dice `GrandResets` pero la DB real usa `MasterResetCount`) |
| `_CLMN_CHR_LVLUP_POINT_` | `LevelUpPoint`            |
| `_CLMN_CHR_STAT_STR_` | `Strength`                   |
| `_CLMN_CHR_STAT_AGI_` | `Dexterity`                  |
| `_CLMN_CHR_STAT_VIT_` | `Vitality`                   |
| `_CLMN_CHR_STAT_ENE_` | `Energy`                     |
| `_CLMN_CHR_STAT_CMD_` | `Leadership`                 |
| `_CLMN_CHR_PK_KILLS_` | `PkCount`                    |
| `_CLMN_CHR_PK_LEVEL_` | `PkLevel`                    |
| `_CLMN_CHR_PK_TIME_`  | `PkTime`                     |
| `_CLMN_CHR_MAP_`      | `MapNumber`                  |
| `_CLMN_CHR_MAP_X_`    | `MapPosX`                    |
| `_CLMN_CHR_MAP_Y_`    | `MapPosY`                    |
| `_CLMN_CHR_MAGIC_L_`  | `MagicList`                  |
| `_CLMN_CHR_INV_`      | `Inventory`                  |
| `_CLMN_CHR_QUEST_`    | `Quest`                      |
| `_TBL_MASTERLVL_`     | `Character` (misma tabla en IGCN S6) |
| `_CLMN_ML_LVL_`       | `mLevel`                     |
| `_CLMN_ML_POINT_`     | `mlPoint`                    |
| `_TBL_GUILD_`         | `Guild`                      |
| `_CLMN_GUILD_NAME_`   | `G_Name`                     |
| `_CLMN_GUILD_LOGO_`   | `G_Mark`                     |
| `_CLMN_GUILD_SCORE_`  | `G_Score`                    |
| `_CLMN_GUILD_MASTER_` | `G_Master`                   |
| `_TBL_GUILDMEMB_`     | `GuildMember`                |
| `_CLMN_GUILDMEMB_CHAR_` | `Name`                     |
| `_CLMN_GUILDMEMB_NAME_` | `G_Name`                   |
| `_TBL_GENS_`          | `IGC_Gens` (a verificar en dump) |
| `_CLMN_GENS_NAME_`    | `Name`                       |
| `_CLMN_GENS_TYPE_`    | `Influence`                  |
| `_CLMN_GENS_POINT_`   | `Points`                     |

---

## Tablas de juego (base MuOnline)

### MEMB_INFO — Cuentas de usuario
PK: `memb___id`

| Columna            | Tipo                  | Descripción                                  |
|--------------------|-----------------------|----------------------------------------------|
| `memb_guid`        | int IDENTITY          | ID numérico único (usado en sesiones web)    |
| `memb___id`        | varchar(10) NOT NULL  | Username / login de la cuenta                |
| `memb__pwd`        | varchar(10) NOT NULL  | Contraseña (MD5 via fn_md5 o plain según config) |
| `memb_name`        | varchar(10) NOT NULL  | Nombre real del miembro                      |
| `sno__numb`        | char(18) NOT NULL     | Número serial de la cuenta                   |
| `mail_addr`        | varchar(50) NULL      | Email                                        |
| `bloc_code`        | char(1) NOT NULL      | `0` = activa, `1` = bloqueada                |
| `ctl1_code`        | char(1) NOT NULL      | Código de control (0 = normal)               |
| `AccountLevel`     | int NOT NULL          | Nivel de VIP (0=normal, >0=VIP)              |
| `AccountExpireDate`| smalldatetime NOT NULL| Fecha expiración del VIP                     |
| `Lock`             | int NOT NULL          | Lock adicional de cuenta                     |
| `bloc_expire`      | smalldatetime NULL    | Expiración del bloqueo                       |
| `CreatedAt`        | datetime NOT NULL     | Fecha de creación                            |
| `WarehouseCount`   | tinyint NOT NULL      | Expansiones del almacén                      |
| `ShowBanner`       | smallint NOT NULL     | Flag de banner                               |
| `appl_days`        | datetime NULL         | Fecha de alta                                |
| `modi_days`        | datetime NULL         | Última modificación                          |

---

### MEMB_STAT — Estado de conexión
PK: `memb___id`

| Columna         | Tipo                | Descripción                                |
|-----------------|---------------------|--------------------------------------------|
| `memb___id`     | varchar(10) NOT NULL| Username (FK → MEMB_INFO)                 |
| `ConnectStat`   | tinyint NULL        | `1` = conectado, `0` = desconectado        |
| `ServerName`    | varchar(50) NULL    | Nombre del servidor (GameServer)           |
| `IP`            | varchar(15) NULL    | IP desde donde se conectó                 |
| `ConnectTM`     | smalldatetime NULL  | Hora de conexión                           |
| `DisConnectTM`  | smalldatetime NULL  | Hora de desconexión                        |
| `OnlineHours`   | int NULL            | Horas totales online (acumulado)           |

---

### Character — Personajes
PK: `Name`

| Columna           | Tipo                  | Descripción                                   |
|-------------------|-----------------------|-----------------------------------------------|
| `Name`            | varchar(10) NOT NULL  | Nombre del personaje (PK)                     |
| `AccountID`       | varchar(10) NOT NULL  | FK → MEMB_INFO.memb___id                      |
| `cLevel`          | int NULL              | Nivel actual                                  |
| `LevelUpPoint`    | int NULL              | Puntos de estadísticas disponibles            |
| `Class`           | tinyint NULL          | Clase del personaje (ver tabla de clases)     |
| `Experience`      | bigint NULL           | Experiencia acumulada                         |
| `Strength`        | int NULL              | Estadística Fuerza                            |
| `Dexterity`       | int NULL              | Estadística Agilidad                          |
| `Vitality`        | int NULL              | Estadística Vitalidad                         |
| `Energy`          | int NULL              | Estadística Energía                           |
| `Leadership`      | int NULL              | Estadística Liderazgo (solo Dark Lord y evol.)|
| `Inventory`       | varbinary(3776) NULL  | Inventario serializado (binario del juego)    |
| `MagicList`       | varbinary(180) NULL   | Lista de habilidades (binario del juego)      |
| `Money`           | int NULL              | Zen (moneda del juego)                        |
| `Life`            | real NULL             | HP actual                                     |
| `MaxLife`         | real NULL             | HP máximo                                     |
| `Mana`            | real NULL             | Mana actual                                   |
| `MaxMana`         | real NULL             | Mana máximo                                   |
| `Shield` / `MaxShield` | real NULL        | Shield actual/máximo                          |
| `BP` / `MaxBP`    | real NULL             | AG actual/máximo                              |
| `MapNumber`       | smallint NULL         | Mapa donde está el personaje                  |
| `MapPosX`         | smallint NULL         | Posición X en el mapa                         |
| `MapPosY`         | smallint NULL         | Posición Y en el mapa                         |
| `MapDir`          | tinyint NULL          | Dirección que mira                            |
| `PkCount`         | int NULL              | Contador de kills PK                          |
| `PkLevel`         | int NULL              | Nivel PK (0=Hero, 3=Normal, 4+=Murder)        |
| `PkTime`          | int NULL              | Tiempo de castigo PK                          |
| `ResetCount`      | int NOT NULL          | Cantidad de resets (el alias PHP `_CLMN_CHR_RSTS_` usa `RESETS`, pero el nombre real en DB es este) |
| `MasterResetCount`| int NOT NULL          | Cantidad de master resets (el alias PHP `_CLMN_CHR_GRSTS_` usa `GrandResets`) |
| `mLevel`          | int (a verificar en DDL completo) | Nivel Master (igcn.tables.php lo mapea en Character para IGCN S6) |
| `mlPoint`         | int (a verificar en DDL completo) | Puntos de árbol Master                  |
| `Quest`           | varbinary(50) NULL    | Estado de quests (binario)                    |
| `CtlCode`         | tinyint NULL          | Código de control del personaje               |
| `FruitPoint`      | int NULL              | Puntos de fruta                               |
| `ResetDay`        | int NOT NULL          | Resets del día                                |
| `ResetWek`        | int NOT NULL          | Resets de la semana                           |
| `ResetMon`        | int NOT NULL          | Resets del mes                                |
| `CustomFlag`      | int NOT NULL          | Flags custom del servidor                     |
| `Kills`           | int NOT NULL          | Kills totales                                 |
| `Deads`           | int NOT NULL          | Muertes totales                               |
| `ItemStart`       | tinyint NOT NULL      | Flag de ítems de inicio                       |
| `LevelUpType`     | tinyint NOT NULL      | Tipo de levelup                               |

---

### AccountCharacter — Personajes por cuenta
PK: `Id`

| Columna        | Tipo                 | Descripción                                     |
|----------------|----------------------|-------------------------------------------------|
| `Id`           | varchar(10) NOT NULL | Username (FK → MEMB_INFO.memb___id)             |
| `GameID1`–`10` | varchar(10) NULL     | Nombres de personajes en slots 1–10             |
| `GameIDC`      | varchar(10) NULL     | Personaje "principal" (IDC = selected character)|
| `MoveCnt`      | tinyint NULL         | Contador de movimientos                         |
| `ExtClass`     | int NOT NULL         | Extensión de clase                              |
| `ExtWarehouse` | int NOT NULL         | Extensión de almacén                            |

---

### Guild — Guilds
PK: `G_Name`

| Columna     | Tipo                | Descripción                              |
|-------------|---------------------|------------------------------------------|
| `G_Name`    | varchar(8) NOT NULL | Nombre del guild (PK)                    |
| `G_Mark`    | varbinary(32) NULL  | Logo del guild (binario)                 |
| `G_Score`   | int NULL            | Puntuación del guild                     |
| `G_Master`  | varchar(10) NULL    | Nombre del master del guild              |
| `G_Count`   | int NULL            | Cantidad de miembros                     |
| `G_Notice`  | varchar(60) NULL    | Mensaje del guild                        |
| `G_Type`    | int NOT NULL        | Tipo de guild                            |
| `G_Rival`   | int NOT NULL        | Guild rival                              |
| `G_Union`   | int NOT NULL        | Union del guild                          |
| `MemberCount`| int NULL           | Conteo de miembros (actualizado por GS)  |

---

### GuildMember — Miembros de guild
PK: `Name`

| Columna    | Tipo                 | Descripción                              |
|------------|----------------------|------------------------------------------|
| `Name`     | varchar(10) NOT NULL | Nombre del personaje (PK)                |
| `G_Name`   | varchar(8) NOT NULL  | Nombre del guild                         |
| `G_Level`  | tinyint NULL         | Nivel dentro del guild                   |
| `G_Status` | tinyint NOT NULL     | Estado en el guild (master, miembro, etc)|

---

### CashShopData — Saldo de WCoin
PK: `AccountID`

| Columna       | Tipo                | Descripción                               |
|---------------|---------------------|-------------------------------------------|
| `AccountID`   | varchar(10) NOT NULL| FK → MEMB_INFO.memb___id                 |
| `WCoinC`      | int NOT NULL        | WCoin (moneda premium tipo C)             |
| `WCoinP`      | int NOT NULL        | WCoinP (moneda premium tipo P/Gold)       |
| `GoblinPoint` | int NOT NULL        | Goblin Points                             |

---

### CashShopInventory — Inventario de tienda
PK: `BaseItemCode` (IDENTITY)

| Columna            | Tipo                 | Descripción                           |
|--------------------|----------------------|---------------------------------------|
| `AccountID`        | varchar(10) NULL     | FK → MEMB_INFO.memb___id             |
| `MainItemCode`     | int NULL             | Código principal del ítem             |
| `InventoryType`    | int NULL             | Tipo de inventario                    |
| `ProductBaseIndex` | int NULL             | Índice base del producto              |
| `CoinValue`        | float NULL           | Valor en coins                        |
| `GiftName`         | varchar(10) NULL     | Destinatario del regalo               |
| `GiftText`         | varchar(200) NULL    | Mensaje del regalo                    |

---

### CashLog — Log de WCoin
PK: `ID` (IDENTITY)

| Columna    | Tipo                 | Descripción                  |
|------------|----------------------|------------------------------|
| `ID`       | int IDENTITY         | ID del log                   |
| `UserID`   | varchar(10) NOT NULL | FK → MEMB_INFO.memb___id    |
| `Amount`   | money NULL           | Cantidad de WCoin             |
| `SentDate` | smalldatetime NULL   | Fecha de la transacción       |

---

### LOG_CREDITOS — Log de créditos del sitio
> a verificar columnas exactas desde el dump

---

### MUPGA_ACCOUNT_COUNTRY — País de registro (tabla propia del sitio)
> Creada por el sitio custom. Script: `db/schema/mupga_tables.sql`.
> **No es de WebEngine ni del GameServer.**

| Columna       | Tipo              | Descripción                                        |
|---------------|-------------------|----------------------------------------------------|
| `Username`    | VARCHAR(10) PK    | FK lógica → MEMB_INFO.memb___id                   |
| `CountryCode` | CHAR(2) NOT NULL  | Código ISO 3166-1 alpha-2 (ej: "AR", "BR", "US")  |
| `RegisteredAt`| DATETIME NOT NULL | Fecha/hora de inserción (DEFAULT GETDATE())        |

Usada en: `RankingsRepository` (LEFT JOIN para mostrar bandera en rankings).
Escrita en: `api/auth/register.php` via ip-api.com al crear la cuenta.

---

### Otras tablas de juego relevantes (solo lectura para el sitio)

| Tabla                    | Descripción                                    |
|--------------------------|------------------------------------------------|
| `Warehouse`              | Almacén del personaje (binario, solo GS)       |
| `WarehouseGuild`         | Almacén del guild (binario, solo GS)           |
| `MasterSkillTree`        | Árbol de habilidades Master (binario, solo GS) |
| `ItemLog`                | Log de ítems del juego                         |
| `MuCastle_DATA`          | Datos del Castillo de Mu (Castle Siege)        |
| `MuCastle_REG_SIEGE`     | Registro de guild para Castle Siege            |
| `MuCastle_SIEGE_GUILDLIST` | Lista de guilds en siege                     |
| `RankingBloodCastle`     | Ranking de Blood Castle (actualizado por GS)   |
| `RankingChaosCastle`     | Ranking de Chaos Castle                        |
| `RankingDevilSquare`     | Ranking de Devil Square                        |
| `RankingIllusionTemple`  | Ranking de Illusion Temple                     |
| `RankingPvpChampionship` | Ranking PvP Championship                       |
| `RankingBattleRoyale`    | Ranking Battle Royale                          |
| `RankingDuel`            | Ranking de Duelos                              |
| `RankingGvG`             | Ranking GvG                                    |
| `RankingKingGuild`       | Ranking de guild rey                           |
| `RankingKingPlayer`      | Ranking de jugador rey                         |
| `Gens_Duprian`           | Facción Duprian                                |
| `Gens_Varnert`           | Facción Varnert                                |
| `Gens_Rank`              | Rankings de Gens                               |
| `CustomAttack`           | Ataque custom (online) — solo GS               |
| `CustomAttackOffline`    | Ataque custom (offline) — solo GS              |
| `CustomStore` / `CustomStoreOffline` | Tienda custom                    |
| `CustomGift`             | Regalos custom                                 |
| `CustomItemBank`         | Banco de ítems custom                          |
| `CustomJewelBank`        | Banco de joyas custom                          |
| `GremoryCase`            | Gremory Case (items sin recoger)               |
| `Marry`                  | Sistema de matrimonio                          |
| `T_FriendList`/`T_FriendMain` | Lista de amigos                          |
| `T_FriendMail`           | Sistema de mensajes                            |

---

## Tablas del CMS WebEngine (solo web, no toca el GameServer)

| Tabla                        | Descripción                                         |
|------------------------------|-----------------------------------------------------|
| `WEBENGINE_BANS`             | Bans de cuentas emitidos desde el sitio             |
| `WEBENGINE_BAN_LOG`          | Historial de bans                                   |
| `WEBENGINE_BLOCKED_IP`       | IPs bloqueadas                                      |
| `WEBENGINE_CREDITS_CONFIG`   | Configuración del sistema de créditos (flexible)    |
| `WEBENGINE_CREDITS_LOGS`     | Log de transacciones de créditos                    |
| `WEBENGINE_CRON`             | Tareas programadas del CMS                          |
| `WEBENGINE_DOWNLOADS`        | Descargas publicadas en el sitio                    |
| `WEBENGINE_FLA`              | Failed Login Attempts (anti-brute force)            |
| `WEBENGINE_NEWS`             | Noticias del sitio                                  |
| `WEBENGINE_NEWS_TRANSLATIONS`| Traducciones de noticias                            |
| `WEBENGINE_PASSCHANGE_REQUEST`| Solicitudes de cambio de contraseña por email      |
| `WEBENGINE_PAYPAL_TRANSACTIONS`| Transacciones de PayPal                           |
| `WEBENGINE_PLUGINS`          | Plugins instalados en el CMS                        |
| `WEBENGINE_REGISTER_ACCOUNT` | Cuentas pendientes de verificación de email         |
| `WEBENGINE_VOTES`            | Votos activos de usuarios                           |
| `WEBENGINE_VOTE_LOGS`        | Historial de votos                                  |
| `WEBENGINE_VOTE_SITES`       | Sitios de votación configurados                     |
| `WEBENGINE_ACCOUNT_COUNTRY`  | País del usuario (geoIP)                            |

---

## Stored Procedures principales

### SPs del sitio custom MuPGA

| SP                          | Descripción                                              | Tablas que toca                          |
|-----------------------------|----------------------------------------------------------|------------------------------------------|
| `sp_AddWCoinWithLog`        | Agrega WCoin a una cuenta con log transaccional          | `CashShopData`, `CashLog`, `MEMB_INFO`   |
| `sp_SetAccountVIP`          | Activa VIP en una cuenta (nivel + fecha expiración)      | `MEMB_INFO`                              |
| `sp_SetAccountGOLDVIP`      | Activa VIP Gold en una cuenta                            | `MEMB_INFO`                              |

### SPs del GameServer (WebEngine los llama también)

| SP                          | Descripción                                              | Tablas que toca                               |
|-----------------------------|----------------------------------------------------------|-----------------------------------------------|
| `WZ_CREATE_ACCOUNT`         | Crear nueva cuenta                                       | `MEMB_INFO`, `AccountCharacter`               |
| `WZ_CONNECT_MEMB`           | Registra conexión de cuenta                              | `MEMB_STAT`                                   |
| `WZ_DISCONNECT_MEMB`        | Registra desconexión de cuenta                           | `MEMB_STAT`                                   |
| `WZ_SetCoin`                | Modifica saldo WCoin/WCoinP/GoblinPoint                 | `CashShopData`                                |
| `WZ_SetResetInfo`           | Actualiza contadores de reset del personaje              | `Character` (ResetCount, ResetDay/Wek/Mon)    |
| `WZ_GetResetInfo`           | Consulta info de resets                                  | `Character` (solo lectura)                    |
| `WZ_SetMasterResetInfo`     | Actualiza master reset                                   | `Character` (MasterResetCount)                |
| `WZ_GetMasterResetInfo`     | Consulta info de master reset                            | `Character` (solo lectura)                    |
| `WZ_SetPlayerKiller`        | Modifica estado de PK killer                             | `Character` (PkLevel, PkTime)                 |
| `WZ_GetPlayerKiller`        | Consulta estado PK                                       | `Character` (solo lectura)                    |
| `WZ_GetAccountLevel`        | Consulta nivel de cuenta (VIP)                           | `MEMB_INFO` (solo lectura)                    |
| `WZ_SetAccountLevel`        | Modifica nivel de cuenta (VIP)                           | `MEMB_INFO`                                   |
| `WZ_RankingAll`             | Ranking general de personajes                            | `Character` (solo lectura)                    |
| `WZ_CustomRanking`          | Ranking custom                                           | `Character` (solo lectura)                    |
| `WZ_CustomTop`              | Top custom                                               | `Character` (solo lectura)                    |
| `WZ_GvGRanking`             | Ranking GvG                                              | `RankingGvG`, `Character` (solo lectura)      |
| `WZ_RankingBloodCastle`     | Ranking Blood Castle                                     | `RankingBloodCastle` (solo lectura)           |
| `WZ_RankingChaosCastle`     | Ranking Chaos Castle                                     | `RankingChaosCastle` (solo lectura)           |
| `WZ_RankingDevilSquare`     | Ranking Devil Square                                     | `RankingDevilSquare` (solo lectura)           |
| `WZ_RankingIllusionTemple`  | Ranking Illusion Temple                                  | `RankingIllusionTemple` (solo lectura)        |
| `WZ_RankingPvpChampionship` | Ranking PvP Championship                                 | `RankingPvpChampionship` (solo lectura)       |
| `WZ_BattleRoyaleRanking`    | Ranking Battle Royale                                    | `RankingBattleRoyale`, `Character`            |
| `WZ_TvTRanking`             | Ranking TvT                                              | `RankingTvT`, `Character`                     |
| `WZ_DeleteCharacter`        | Eliminar personaje                                       | `Character`, `AccountCharacter` y otras       |
| `WZ_RenameCharacter`        | Renombrar personaje                                      | `Character`, `AccountCharacter`               |
| `WZ_CreateCharacter`        | Crear personaje nuevo                                    | `Character`, `AccountCharacter`               |
| `WZ_DesblocAccount`         | Desbloquear cuenta                                       | `MEMB_INFO`                                   |
| `WZ_SetReward` / `WZ_SetRewardAll` | Dar recompensas                               | `Character` (stats/zen)                       |
| `WZ_GremoryCase_AddItem`    | Agregar ítem al Gremory Case                            | `GremoryCase`                                 |
| `WZ_GuildCreate`            | Crear guild                                              | `Guild`, `GuildMember`, `AccountCharacter`    |
| `WZ_SetGuildDelete`         | Eliminar guild                                           | `Guild`, `GuildMember`                        |
| `WZ_SetMarryInfo` / `WZ_SetDivorceInfo` | Sistema de matrimonio                  | `Marry`                                       |
| `WZ_FriendAdd` / `WZ_FriendDel` | Gestión de amigos                               | `T_FriendList`, `T_FriendMain`               |
| `WZ_WriteMail` / `WZ_DelMail` | Sistema de mensajes internos                       | `T_FriendMail`                                |
| `MMK_ItemMakerInventory`    | Fabricar ítems en inventario (admin)                    | `Character` (Inventory — binario)             |
| `MMK_SkillMaker`            | Fabricar habilidades (admin)                            | `Character` (MagicList — binario)             |
| `WZ_CS_*`                   | Castle Siege (14 SPs)                                   | `MuCastle_DATA`, `MuCastle_*`, `Guild`        |
