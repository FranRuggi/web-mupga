-- ============================================================
-- fn_md5 — Función de hash de contraseñas de MuEmu Louis
--
-- Esta función NO está en script.sql porque viene preinstalada
-- con MuEmu Louis y no se exporta en dumps de datos.
--
-- USO:
--   Ejecutar este script UNA SOLA VEZ en la base de datos local
--   de desarrollo (MuOnline) para replicar el comportamiento
--   del GameServer en cuanto al almacenamiento de contraseñas.
--
-- VERIFICACIÓN ANTES DE EJECUTAR:
--   Conectarse al VPS con SSMS y verificar la definición real:
--     SELECT OBJECT_DEFINITION(OBJECT_ID('dbo.fn_md5'));
--   Si difiere de la implementación aquí, actualizar este script
--   para que coincida EXACTAMENTE.
--
-- PRODUCCIÓN:
--   No ejecutar en la DB de producción — fn_md5 ya existe allí.
--   Si por alguna razón necesitás recrearla, usar DROP FUNCTION
--   primero y verificar que no haya dependencias.
-- ============================================================

USE MuOnline;
GO

IF OBJECT_ID('dbo.fn_md5', 'FN') IS NOT NULL
    DROP FUNCTION [dbo].[fn_md5];
GO

-- Implementación estándar de MuEmu Louis:
-- Toma la contraseña en texto plano y devuelve su hash MD5 en hex uppercase.
-- El segundo parámetro (@sAccount) existe en la firma pero no se usa en el hash
-- (es por compatibilidad con la llamada del GameServer).
CREATE FUNCTION [dbo].[fn_md5]
(
    @sText    NVARCHAR(50),
    @sAccount NVARCHAR(10)
)
RETURNS NVARCHAR(50)
AS
BEGIN
    RETURN CONVERT(NVARCHAR(50), HASHBYTES('MD5', @sText), 2);
END
GO

-- Verificar que la función se creó correctamente:
-- SELECT [dbo].[fn_md5]('test', 'user');
-- Resultado esperado: hash MD5 de 'test' en mayúsculas (32 chars hex)
