-- ============================================================
-- ControlPanel — Acreditación manual de VIP (auditoría propia)
-- Correr en SSMS contra la base mupga_admin (con usuario admin, no
-- mupga_web_svc — este login no tiene DDL, ver admin_db.php).
--
-- Esta tabla NO es el registro del juego (eso ya lo hace
-- sp_SetAccountGOLDVIP sobre MEMB_INFO). Es la auditoría propia del
-- ControlPanel: qué admin otorgó VIP, a qué cuenta, cuántos días y por qué.
-- mupga_web_svc ya tiene db_datareader + db_datawriter sobre toda
-- mupga_admin (admin_db.php), así que no hace falta ningún GRANT extra.
-- ============================================================
USE mupga_admin;
GO

IF OBJECT_ID('dbo.vip_grants', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.vip_grants (
        id             INT           NOT NULL IDENTITY(1,1),
        admin_id       NVARCHAR(10)  NOT NULL,  -- memb___id del admin que otorgó
        target_account NVARCHAR(10)  NOT NULL,  -- memb___id de la cuenta acreditada
        days           INT           NOT NULL,
        reason         NVARCHAR(300) NULL,
        created_at     DATETIME2     NOT NULL,  -- seteado explícito en PHP con GETUTCDATE(), sin DEFAULT
        CONSTRAINT pk_vip_grants PRIMARY KEY (id)
    );
    PRINT 'dbo.vip_grants creada.';
END
ELSE
    PRINT 'dbo.vip_grants ya existe, no se toca.';
GO

-- Verificación
SELECT TOP 10 * FROM dbo.vip_grants ORDER BY id DESC;
GO
