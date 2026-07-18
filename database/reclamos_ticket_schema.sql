-- ============================================================
-- MuPGA Reclamos — Rediseño a modelo de ticket con hilo de mensajes
-- Ejecutar como sa (o DBA) contra mupga_admin, DESPUÉS de reclamos_setup.sql
-- (ese script ya creó el login/schema — sigue vigente, no lo toques).
--
-- Reemplaza el modelo viejo (1 mensaje + 1 respuesta como columnas de
-- reclamos.reclamos) por un modelo de ticket:
--   reclamos.reclamos  → cabecera del ticket (quién, estado, rate limit)
--   reclamos.mensajes  → el hilo completo (N mensajes, jugador o admin)
--
-- SEGURO DE CORRER: confirmado con Franco que no hay reclamos cargados
-- todavía, así que se puede DROP + CREATE limpio en vez de migrar datos.
-- Si en algún momento esto se vuelve a correr CON datos reales adentro,
-- HAY QUE CAMBIAR el DROP TABLE por una migración — no correr tal cual.
-- ============================================================
USE mupga_admin;
GO

IF OBJECT_ID('reclamos.mensajes', 'U') IS NOT NULL DROP TABLE reclamos.mensajes;
GO
IF OBJECT_ID('reclamos.reclamos', 'U') IS NOT NULL DROP TABLE reclamos.reclamos;
GO

-- Cabecera del ticket.
CREATE TABLE reclamos.reclamos (
    id         INT           NOT NULL IDENTITY(1,1),
    nick       NVARCHAR(50)  NOT NULL,
    estado     NVARCHAR(20)  NOT NULL DEFAULT 'nuevo',  -- 'nuevo' | 'resuelto'
    -- El jugador tiene novedades del staff sin ver todavía (dispara el
    -- banner en app.js). Se prende cuando el admin responde, se apaga
    -- cuando el jugador abre el detalle del ticket (detalle.php).
    no_leido   BIT           NOT NULL DEFAULT 0,
    ip_hash    VARBINARY(32) NULL,   -- HASHBYTES('SHA2_256', ip), nunca la IP en texto plano
    created_at DATETIME2     NOT NULL,
    CONSTRAINT pk_reclamos PRIMARY KEY (id)
);
GO

-- Hilo de mensajes: el primer mensaje del jugador YA vive acá (no hay
-- columna "mensaje" en reclamos.reclamos), igual que cualquier respuesta
-- del staff o comentario de seguimiento del jugador.
CREATE TABLE reclamos.mensajes (
    id            INT           NOT NULL IDENTITY(1,1),
    reclamo_id    INT           NOT NULL,
    autor_tipo    NVARCHAR(10)  NOT NULL,  -- 'jugador' | 'admin'
    autor_nick    NVARCHAR(50)  NOT NULL,
    mensaje       NVARCHAR(MAX) NOT NULL,
    imagenes_json NVARCHAR(MAX) NULL,      -- array JSON de URLs, se completa después del insert (ver finalize.php)
    created_at    DATETIME2     NOT NULL,
    CONSTRAINT pk_mensajes PRIMARY KEY (id),
    CONSTRAINT fk_mensajes_reclamo FOREIGN KEY (reclamo_id) REFERENCES reclamos.reclamos(id)
);
GO

-- Redundante (mupga_reclamos_svc es dueño del schema reclamos → control
-- total ya incluido), se deja como documentación de qué usa la app.
GRANT SELECT, INSERT, UPDATE ON reclamos.reclamos TO mupga_reclamos_svc;
GRANT SELECT, INSERT, UPDATE ON reclamos.mensajes TO mupga_reclamos_svc;
GO

-- Verificación
SELECT s.name AS [Schema], t.name AS [Tabla]
FROM sys.tables t JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE s.name = 'reclamos'
ORDER BY t.name;
GO
