-- ============================================================
-- MuPGA — Tabla de servidores (selector dinámico de mupga.com.ar)
-- Ejecutar en la base mupga_admin, como sa o DBA.
--
-- POR QUÉ ACÁ Y NO EN UNA BASE NUEVA:
--   `servidores` es configuración del SITIO, exactamente la misma
--   naturaleza que dbo.downloads / dbo.server_info / dbo.site_status,
--   que ya viven en mupga_admin y ya se editan desde el ControlPanel.
--   Reusa la conexión AdminDatabase (mupga_web_svc) — cero credenciales
--   nuevas, cero variables de .env nuevas, cero superficie de ataque nueva.
--
-- NO HACE FALTA NINGÚN GRANT:
--   mupga_web_svc tiene db_datareader + db_datawriter sobre mupga_admin.
--   Esos roles fijos aplican a toda la base, incluidas las tablas creadas
--   después — así que la lectura del endpoint público y la escritura futura
--   desde el ControlPanel ya están cubiertas. Sin DDL, como corresponde.
--
-- NUNCA se toca ninguna tabla del juego desde este módulo.
--
-- El script es re-ejecutable sin errores (usa OBJECT_ID / IF NOT EXISTS).
-- ============================================================
USE mupga_admin;
GO

-- ── Tabla ────────────────────────────────────────────────────
IF OBJECT_ID('dbo.servidores', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.servidores (
        id                INT           NOT NULL IDENTITY(1,1),

        -- slug define el subdominio: 'servidor1' → servidor1.mupga.com.ar
        slug              NVARCHAR(30)  NOT NULL,
        nombre            NVARCHAR(60)  NOT NULL,
        descripcion       NVARCHAR(500) NULL,

        -- Rates y reglas. Todo texto libre a propósito: soportan valores
        -- como '1000x', 'dinámica' o 'x50 hasta lvl 300' sin cambiar el schema.
        version           NVARCHAR(40)  NULL,   -- 'Season 6 Ep. 3'
        experiencia       NVARCHAR(40)  NULL,   -- '1000x'
        drop_rate         NVARCHAR(20)  NULL,   -- OJO: 'drop' es palabra reservada en T-SQL
        sistema_reset     NVARCHAR(200) NULL,
        limite_resets     INT           NULL,   -- NULL = sin límite
        tienda_items      BIT           NOT NULL CONSTRAINT df_servidores_tienda DEFAULT 0,

        -- Allowlist en la DB, no solo en PHP — mismo criterio que site_status.mode
        estado            NVARCHAR(20)  NOT NULL CONSTRAINT df_servidores_estado DEFAULT 'activo',
        fecha_lanzamiento DATETIME2     NULL,   -- countdown en la landing si estado='proximo_lanzamiento'

        -- URLs: sin esto la landing no sabe a dónde linkear y la vinculación
        -- de cuentas (Etapa 3) no sabe contra qué API validar credenciales.
        web_url           NVARCHAR(200) NOT NULL,  -- https://servidor1.mupga.com.ar
        api_url           NVARCHAR(200) NOT NULL,  -- https://api.mupga.com.ar
        imagen_url        NVARCHAR(300) NULL,      -- banner de la card

        orden             INT           NOT NULL CONSTRAINT df_servidores_orden  DEFAULT 0,
        activo            BIT           NOT NULL CONSTRAINT df_servidores_activo DEFAULT 1,

        -- GETUTCDATE() y nunca DATEADD con offset fijo: la timezone del SO de
        -- este VPS ya cambió dos veces (ver incidentes en CLAUDE.md).
        created_at        DATETIME2     NOT NULL CONSTRAINT df_servidores_created DEFAULT GETUTCDATE(),
        updated_at        DATETIME2     NOT NULL CONSTRAINT df_servidores_updated DEFAULT GETUTCDATE(),
        updated_by        NVARCHAR(10)  NULL,   -- nvarchar(10) como el resto de mupga_admin: valores cortos

        CONSTRAINT pk_servidores     PRIMARY KEY (id),
        CONSTRAINT uq_servidores_slug UNIQUE (slug),
        CONSTRAINT ck_servidores_estado CHECK (
            estado IN ('activo', 'proximo_lanzamiento', 'mantenimiento', 'cerrado')
        )
    );

    -- Orden de la landing: el endpoint público filtra activo=1 y ordena por orden, id.
    CREATE INDEX ix_servidores_listado ON dbo.servidores (activo, orden, id);

    PRINT 'dbo.servidores creada.';
END
ELSE
    PRINT 'dbo.servidores ya existe, no se toca.';
GO

-- ── Carga del servidor actual ────────────────────────────────
-- DESCOMENTAR y completar con los valores reales antes de correr.
-- El IF NOT EXISTS lo hace re-ejecutable: si ya cargaste la fila, no la duplica
-- ni la pisa (para editar valores, usá el UPDATE de más abajo o el ControlPanel).
--
-- IF NOT EXISTS (SELECT 1 FROM dbo.servidores WHERE slug = 'servidor1')
-- BEGIN
--     INSERT INTO dbo.servidores
--         (slug, nombre, descripcion, version, experiencia, drop_rate,
--          sistema_reset, limite_resets, tienda_items, estado, fecha_lanzamiento,
--          web_url, api_url, imagen_url, orden, activo, updated_by)
--     VALUES
--         ('servidor1',
--          'MuPGA',
--          'La experiencia clásica de MU Online, renovada. Resets, Castle Siege y comunidad activa.',
--          'Season 6 Ep. 3',
--          '1000x',                                  -- experiencia
--          '30%',                                    -- drop_rate
--          'Reset a nivel 400 · 350 puntos de bonus', -- sistema_reset
--          300,                                      -- limite_resets (NULL = sin límite)
--          1,                                        -- tienda_items
--          'activo',
--          NULL,                                     -- fecha_lanzamiento (solo si es proximo_lanzamiento)
--          'https://servidor1.mupga.com.ar',
--          'https://api.mupga.com.ar',
--          NULL,                                     -- imagen_url
--          1,                                        -- orden
--          1,                                        -- activo
--          'seed');
--     PRINT 'servidor1 cargado.';
-- END
-- GO

-- ── Ejemplo de servidor "próximo lanzamiento" (referencia) ───
-- La landing muestra un countdown a fecha_lanzamiento y desactiva el botón
-- de entrar cuando estado = 'proximo_lanzamiento'.
--
-- INSERT INTO dbo.servidores
--     (slug, nombre, descripcion, version, experiencia, drop_rate, sistema_reset,
--      limite_resets, tienda_items, estado, fecha_lanzamiento, web_url, api_url, orden, activo, updated_by)
-- VALUES
--     ('servidor2', 'MuPGA Hard', 'Rates bajos, economía cerrada, para veteranos.',
--      'Season 6 Ep. 3', '50x', '10%', 'Reset a nivel 400', NULL, 1,
--      'proximo_lanzamiento', '2026-09-01T21:00:00',
--      'https://servidor2.mupga.com.ar', 'https://api2.mupga.com.ar', 2, 1, 'seed');
-- GO

-- ── Para editar una fila ya cargada ──────────────────────────
-- UPDATE dbo.servidores
--    SET experiencia = '1500x',
--        updated_at  = GETUTCDATE(),
--        updated_by  = 'manual'
--  WHERE slug = 'servidor1';
-- GO

-- ── Verificación ─────────────────────────────────────────────
SELECT id, slug, nombre, estado, activo, orden, web_url, api_url, created_at
  FROM dbo.servidores
 ORDER BY orden ASC, id ASC;
GO
