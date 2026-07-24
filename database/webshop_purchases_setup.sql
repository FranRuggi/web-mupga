-- ============================================================
-- MuPGA Tienda WCoin — Auditoría de compras (para estadísticas)
-- Ejecutar en MuOnline (mismo login que webshop_setup.sql: sa o DBA).
--
-- webshop_user ya tiene CONTROL sobre el schema webshop completo (ver
-- webshop_setup.sql, Bloque A) — esta tabla no necesita ningún GRANT nuevo.
--
-- No reemplaza a CashLog (log interno del juego, auditoría genérica de
-- WCoin) ni es el catálogo (webshop.products/categories, que se truncan y
-- reconstruyen enteros en cada reimport vía tienda_import.php). Es el
-- registro propio de QUÉ se compró, para poder armar estadísticas —
-- product_name/category_name van DENORMALIZADOS (snapshot al momento de
-- la compra) a propósito: un FK a webshop.products quedaría huérfano o
-- apuntando a un producto distinto después de un reimport.
--
-- Se escribe DESPUÉS de que buy.php confirma la compra (transacción de
-- Database::get(), que solo tiene SELECT sobre el schema webshop — ver
-- Bloque C de webshop_setup.sql) usando la conexión separada
-- WebshopDatabase (webshop_user, con escritura). Un fallo acá NO revierte
-- la compra: mismo criterio que wcoin_credits — la plata/ítem ya se
-- movió, perder la fila de auditoría es tolerable, bloquear al jugador no.
--
-- El script es re-ejecutable sin errores (usa OBJECT_ID).
-- ============================================================
USE MuOnline;
GO

IF OBJECT_ID('webshop.purchases', 'U') IS NULL
BEGIN
    CREATE TABLE webshop.purchases (
        id            INT           NOT NULL IDENTITY(1,1),
        account_id    VARCHAR(10)   NOT NULL,  -- FK lógica → MEMB_INFO.memb___id
        product_id    INT           NULL,      -- snapshot de webshop.products.id, SIN FK (se trunca en cada import)
        product_name  VARCHAR(200)  NOT NULL,
        category_name VARCHAR(100)  NULL,
        price_wcoin   INT           NOT NULL,
        purchased_at  DATETIME2     NOT NULL,  -- GETUTCDATE() explícito desde PHP, sin DEFAULT
        CONSTRAINT pk_webshop_purchases PRIMARY KEY (id)
    );
    CREATE INDEX ix_webshop_purchases_account ON webshop.purchases (account_id);
    CREATE INDEX ix_webshop_purchases_date ON webshop.purchases (purchased_at);
    PRINT 'webshop.purchases creada.';
END
ELSE
    PRINT 'webshop.purchases ya existe, no se toca.';
GO

-- Verificación
SELECT TOP 10 * FROM webshop.purchases ORDER BY id DESC;
GO
