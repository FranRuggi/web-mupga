-- ============================================================
-- ControlPanel — Módulo Eventos (torneos, actividades puntuales)
-- Correr en SSMS contra la base mupga_admin (con usuario admin, no
-- mupga_web_svc — este login no tiene DDL, ver admin_db.php).
--
-- Genérico a propósito: no es "el torneo PVP", es la tabla de
-- cualquier evento futuro (torneos, sorteos, actividades). El primer
-- evento (Torneo PVP) se carga desde el ControlPanel una vez corrido
-- este script, no por seed SQL.
--
-- mupga_web_svc ya tiene db_datareader + db_datawriter sobre toda
-- mupga_admin (admin_db.php), así que no hace falta ningún GRANT
-- extra — mismo criterio que wcoin_credits/vip_grants.
-- ============================================================
USE mupga_admin;
GO

IF OBJECT_ID('dbo.events', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.events (
        id             INT           NOT NULL IDENTITY(1,1),
        title          NVARCHAR(120) NOT NULL,
        description    NVARCHAR(2000) NULL,
        event_datetime DATETIME2     NULL,      -- UTC; NULL = hora a confirmar
        max_slots      INT           NULL,      -- NULL = sin límite de cupo
        is_active      BIT           NOT NULL CONSTRAINT df_events_active DEFAULT (1),
        created_by     NVARCHAR(10)  NOT NULL,  -- memb___id del admin
        created_at     DATETIME2     NOT NULL,  -- GETUTCDATE() explícito en PHP
        updated_at     DATETIME2     NOT NULL,
        CONSTRAINT pk_events PRIMARY KEY (id)
    );
    PRINT 'dbo.events creada.';
END
ELSE
    PRINT 'dbo.events ya existe, no se toca.';
GO

IF OBJECT_ID('dbo.event_registrations', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.event_registrations (
        id             INT           NOT NULL IDENTITY(1,1),
        event_id       INT           NOT NULL,
        account        NVARCHAR(10)  NOT NULL,  -- memb___id
        character_name NVARCHAR(10)  NOT NULL,
        created_at     DATETIME2     NOT NULL,  -- GETUTCDATE() explícito en PHP
        CONSTRAINT pk_event_registrations PRIMARY KEY (id),
        CONSTRAINT fk_event_registrations_event FOREIGN KEY (event_id)
            REFERENCES dbo.events(id),
        -- Una inscripción por cuenta por evento. Backstop además de la
        -- validación transaccional en EventsRepository::register().
        CONSTRAINT uq_event_registrations UNIQUE (event_id, account)
    );
    CREATE INDEX ix_event_registrations_event ON dbo.event_registrations(event_id);
    PRINT 'dbo.event_registrations creada.';
END
ELSE
    PRINT 'dbo.event_registrations ya existe, no se toca.';
GO

-- Verificación
SELECT TOP 10 * FROM dbo.events ORDER BY id DESC;
SELECT TOP 10 * FROM dbo.event_registrations ORDER BY id DESC;
GO
