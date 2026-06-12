-- ============================================================
-- MuPGA Prode — Setup de schema, usuario y tablas
-- Ejecutar como sa (o DBA con permisos suficientes) en MuOnline
--
-- ANTES DE EJECUTAR:
--   Reemplazar {{PRODE_DB_PASSWORD}} con la contraseña elegida
--
-- El script es re-ejecutable sin errores (usa IF NOT EXISTS).
-- ============================================================

-- ── Bloque A — Login, usuario y schema ───────────────────────
IF NOT EXISTS (
    SELECT 1 FROM sys.server_principals WHERE name = 'prode_user'
)
BEGIN
    CREATE LOGIN prode_user WITH PASSWORD = '{{PRODE_DB_PASSWORD}}';
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.database_principals WHERE name = 'prode_user'
)
BEGIN
    CREATE USER prode_user FOR LOGIN prode_user;
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.schemas WHERE name = 'prode'
)
BEGIN
    EXEC('CREATE SCHEMA prode AUTHORIZATION prode_user');
END;

-- ── Bloque B — Permisos sobre dbo (solo lectura + SPs de premios) ──
GRANT SELECT ON dbo.ACCOUNT_TBL TO prode_user;
GRANT SELECT ON dbo.Character   TO prode_user;
GRANT SELECT ON dbo.MEMB_INFO   TO prode_user;

-- Nombres exactos de los SPs confirmados en el código del proyecto:
GRANT EXECUTE ON dbo.sp_AddWCoinWithLog TO prode_user;
GRANT EXECUTE ON dbo.sp_SetAccountVIP   TO prode_user;

-- ── Bloque C — Control total del schema prode ────────────────
GRANT CONTROL ON SCHEMA::prode TO prode_user;

-- ── Bloque D — Tablas ────────────────────────────────────────

-- Parámetros configurables (premios, umbrales, etc.)
IF OBJECT_ID('prode.config', 'U') IS NULL
BEGIN
    CREATE TABLE prode.config (
        config_key   VARCHAR(50)  NOT NULL,
        config_value VARCHAR(100) NOT NULL,
        CONSTRAINT pk_config PRIMARY KEY (config_key)
    );

    INSERT INTO prode.config (config_key, config_value) VALUES
        ('reward_exact_wcoins',   '1000'),
        ('reward_exact_vip_days', '3'),
        ('reward_winner_wcoins',  '500'),
        ('reward_winner_vip_days','1');
END;

-- Partidos del mundial
IF OBJECT_ID('prode.matches', 'U') IS NULL
BEGIN
    CREATE TABLE prode.matches (
        id                 INT          NOT NULL IDENTITY(1,1),
        team_home          VARCHAR(50)  NOT NULL,
        team_away          VARCHAR(50)  NOT NULL,
        match_datetime_utc DATETIME     NOT NULL,
        stage              VARCHAR(30)  NOT NULL,
        is_locked          BIT          NOT NULL DEFAULT 0,
        score_home         INT          NULL,
        score_away         INT          NULL,
        status             VARCHAR(20)  NOT NULL DEFAULT 'pending',
        created_at         DATETIME     NOT NULL DEFAULT GETDATE(),
        CONSTRAINT pk_matches PRIMARY KEY (id)
    );
END;

-- Predicciones: una por cuenta por partido
IF OBJECT_ID('prode.predictions', 'U') IS NULL
BEGIN
    CREATE TABLE prode.predictions (
        id              INT         NOT NULL IDENTITY(1,1),
        account         VARCHAR(50) NOT NULL,
        match_id        INT         NOT NULL,
        pred_score_home INT         NOT NULL,
        pred_score_away INT         NOT NULL,
        submitted_at    DATETIME    NOT NULL DEFAULT GETDATE(),
        points_earned   INT         NULL,
        reward_applied  BIT         NOT NULL DEFAULT 0,
        CONSTRAINT pk_predictions      PRIMARY KEY (id),
        CONSTRAINT fk_pred_match       FOREIGN KEY (match_id) REFERENCES prode.matches(id),
        CONSTRAINT uq_pred_acct_match  UNIQUE (account, match_id)
    );
END;

-- Puntuación acumulada por jugador
IF OBJECT_ID('prode.scores', 'U') IS NULL
BEGIN
    CREATE TABLE prode.scores (
        account      VARCHAR(50) NOT NULL,
        total_points INT         NOT NULL DEFAULT 0,
        exact_hits   INT         NOT NULL DEFAULT 0,
        winner_hits  INT         NOT NULL DEFAULT 0,
        last_updated DATETIME    NOT NULL DEFAULT GETDATE(),
        CONSTRAINT pk_scores PRIMARY KEY (account)
    );
END;

-- ── Verificación final ───────────────────────────────────────
SELECT
    s.name  AS [Schema],
    t.name  AS [Tabla],
    t.type_desc
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE s.name = 'prode'
ORDER BY t.name;
