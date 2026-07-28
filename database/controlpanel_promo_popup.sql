-- ============================================================
-- ControlPanel — Popup promocional (fila única, editable)
-- Correr en SSMS contra la base mupga_admin (con usuario admin, no
-- mupga_web_svc — este login no tiene DDL, ver admin_db.php).
--
-- Mismo patrón que dbo.site_status: una sola fila (id=1) que el
-- ControlPanel actualiza. mupga_web_svc ya tiene db_datareader +
-- db_datawriter sobre toda mupga_admin, así que no hace falta ningún
-- GRANT extra.
-- ============================================================
USE mupga_admin;
GO

IF OBJECT_ID('dbo.promo_popup', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.promo_popup (
        id          INT            NOT NULL,
        is_active   BIT            NOT NULL CONSTRAINT df_promo_popup_active DEFAULT (0),
        eyebrow     NVARCHAR(100)  NULL,  -- texto chico arriba, ej. "DE 0 A 50 RESETS"
        title       NVARCHAR(150)  NULL,  -- ej. "¡TU MOMENTO LLEGÓ!"
        highlight   NVARCHAR(50)   NULL,  -- texto grande destacado, ej. "EXP +200%"
        description NVARCHAR(300)  NULL,  -- ej. "Aprovechá y subí rápido de nivel"
        image_url   NVARCHAR(500)  NULL,
        cta_text    NVARCHAR(50)   NULL,  -- texto del botón, ej. "Entrar ahora"
        cta_link    NVARCHAR(300)  NULL,  -- vacío/NULL = el botón solo cierra el popup
        updated_by  NVARCHAR(10)   NULL,
        updated_at  DATETIME       NULL,
        CONSTRAINT pk_promo_popup PRIMARY KEY (id),
        CONSTRAINT ck_promo_popup_single_row CHECK (id = 1)
    );
    PRINT 'dbo.promo_popup creada.';
END
ELSE
    PRINT 'dbo.promo_popup ya existe, no se toca.';
GO

IF NOT EXISTS (SELECT 1 FROM dbo.promo_popup WHERE id = 1)
BEGIN
    INSERT INTO dbo.promo_popup (id, is_active)
    VALUES (1, 0);
    PRINT 'Fila id=1 insertada (is_active=0).';
END
ELSE
    PRINT 'Fila id=1 ya existe, no se toca.';
GO

-- Verificación
SELECT * FROM dbo.promo_popup WHERE id = 1;
GO
