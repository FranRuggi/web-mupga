-- ============================================================
-- MuPGA Reclamos — Setup de schema, tabla y login de mínimo privilegio
-- Ejecutar como sa (o DBA con permisos suficientes).
--
-- ANTES DE EJECUTAR:
--   Reemplazar 'CAMBIAR_ESTA_PASSWORD' con la contraseña elegida
--   (o dejarla así y correr después: ALTER LOGIN mupga_reclamos_svc
--   WITH PASSWORD = '...';)
--
-- El script es re-ejecutable sin errores (usa IF NOT EXISTS).
--
-- El login accede a DOS bases distintas, cada una con su propio
-- CREATE USER y sus propios permisos puntuales:
--   - MuOnline (base del juego):   SELECT sobre dbo.vw_web_auth únicamente.
--   - mupga_admin (base del sitio): schema reclamos (SELECT + INSERT sobre
--     reclamos.reclamos, nada de UPDATE/DELETE, sin roles db_datareader/
--     db_datawriter).
-- ============================================================

-- ── Bloque A — Login a nivel de servidor (una sola vez) ───────
IF NOT EXISTS (
    SELECT 1 FROM sys.server_principals WHERE name = 'mupga_reclamos_svc'
)
BEGIN
    CREATE LOGIN mupga_reclamos_svc WITH PASSWORD = 'CAMBIAR_ESTA_PASSWORD';
END;
GO

-- ── Bloque B — Base del juego (MuOnline): solo lectura de vw_web_auth ──
USE MuOnline;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.database_principals WHERE name = 'mupga_reclamos_svc'
)
BEGIN
    CREATE USER mupga_reclamos_svc FOR LOGIN mupga_reclamos_svc;
END;
GO

-- Único permiso sobre esta base: SELECT en la view de autenticación.
GRANT SELECT ON dbo.vw_web_auth TO mupga_reclamos_svc;
GO

-- ── Bloque C — Base del sitio (mupga_admin): schema reclamos ──
USE mupga_admin;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.database_principals WHERE name = 'mupga_reclamos_svc'
)
BEGIN
    CREATE USER mupga_reclamos_svc FOR LOGIN mupga_reclamos_svc;
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.schemas WHERE name = 'reclamos'
)
BEGIN
    EXEC('CREATE SCHEMA reclamos AUTHORIZATION mupga_reclamos_svc');
END;
GO

-- Tabla de reclamos: un registro por reclamo enviado desde /reclamos.
IF OBJECT_ID('reclamos.reclamos', 'U') IS NULL
BEGIN
    CREATE TABLE reclamos.reclamos (
        id            INT             NOT NULL IDENTITY(1,1),
        nick          NVARCHAR(50)    NOT NULL,
        mensaje       NVARCHAR(MAX)   NOT NULL,
        imagenes_json NVARCHAR(MAX)   NULL,   -- array JSON de URLs, ej: ["https://files.../a.jpg"]
        estado        NVARCHAR(20)    NOT NULL DEFAULT 'nuevo',
        ip_hash       VARBINARY(32)   NULL,   -- HASHBYTES('SHA2_256', ip), nunca la IP en texto plano
        created_at    DATETIME2       NOT NULL DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_reclamos PRIMARY KEY (id)
    );
END;
GO

-- Permisos puntuales: SELECT + INSERT nada más. Sin UPDATE/DELETE,
-- sin roles db_datareader/db_datawriter (esos darían acceso a todo el schema dbo).
GRANT SELECT, INSERT ON reclamos.reclamos TO mupga_reclamos_svc;
GO

-- ── Verificación final ───────────────────────────────────────
SELECT
    s.name AS [Schema],
    t.name AS [Tabla],
    t.type_desc
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE s.name = 'reclamos'
ORDER BY t.name;
GO

-- Permisos efectivos del login sobre reclamos.reclamos, para confirmar
-- en SSMS que quedaron exactamente SELECT + INSERT (correr logueado como
-- mupga_reclamos_svc, o revisar con fn_my_permissions):
-- SELECT * FROM fn_my_permissions('reclamos.reclamos', 'OBJECT');
