-- ============================================================
-- MuPGA Reclamos — Grant adicional: UPDATE puntual de imagenes_json
-- Ejecutar como sa (o DBA) contra la base mupga_admin, DESPUÉS de
-- reclamos_setup.sql.
--
-- Por qué hace falta: el número de reclamo (id) solo existe después
-- del INSERT, pero las imágenes se suben a una carpeta R2 nombrada
-- con ese id (reclamos/{id}/...). El flujo queda: INSERT (crea el id,
-- sin imágenes) → subir imágenes a la carpeta → UPDATE de
-- imagenes_json con las URLs finales.
--
-- Grant a nivel de COLUMNA (no toda la tabla) para mantener el
-- privilegio mínimo: mupga_reclamos_svc sigue sin poder tocar nick,
-- mensaje, estado, ip_hash ni created_at después del insert.
-- ============================================================
USE mupga_admin;
GO

GRANT UPDATE (imagenes_json) ON reclamos.reclamos TO mupga_reclamos_svc;
GO

-- Verificación (fn_my_permissions muestra los permisos de QUIEN corre la
-- query — para ver los de mupga_reclamos_svc, logueate con ese usuario
-- en SSMS y corré esto vos mismo):
-- SELECT * FROM fn_my_permissions('reclamos.reclamos', 'OBJECT')
-- WHERE permission_name IN ('SELECT', 'INSERT', 'UPDATE');
GO
