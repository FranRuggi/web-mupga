# Pasos manuales — Módulo Prode MuPGA

Instrucciones para activar el módulo en el VPS, en orden.
Ejecutar **después** de hacer deploy del código al servidor.

---

## 1. Crear usuario y schema en SQL Server

1. Abrir **SQL Server Management Studio** en el VPS (o conectarse remotamente).
2. Seleccionar la base de datos **MuOnline**.
3. Abrir el archivo `database/prode_setup.sql` del repositorio.
4. Reemplazar `{{PRODE_DB_PASSWORD}}` con la contraseña elegida para `prode_user`.
5. Ejecutar el script completo.
6. Verificar que aparecen las 4 tablas en el schema `prode`:

```sql
SELECT s.name AS [Schema], t.name AS [Tabla]
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE s.name = 'prode'
ORDER BY t.name;
-- Debe devolver: config, matches, predictions, scores
```

---

## 2. Configurar variables de entorno en el VPS

1. Abrir el archivo `.env` del proyecto en el VPS (ruta del deploy, ej. `C:\mupga\src\..\.env`).
2. Agregar las siguientes líneas (mismos valores que DB_HOST/DB_NAME, credenciales nuevas):

```
PRODE_DB_HOST=localhost\SQLEXPRESS01
PRODE_DB_PORT=
PRODE_DB_NAME=MuOnline
PRODE_DB_USER=prode_user
PRODE_DB_PASS=<la misma contraseña que en el paso 1>
ADMIN_TOKEN=<generar con: php -r "echo bin2hex(random_bytes(24));"> 
```

3. Reiniciar Apache desde **XAMPP Control Panel → Apache → Restart**.

---

## 3. Verificar permisos del usuario prode_user

Conectarse a SQL Server con el usuario `prode_user` y confirmar:

```sql
-- Debe funcionar (SELECT):
SELECT TOP 1 * FROM dbo.ACCOUNT_TBL;
SELECT TOP 1 * FROM dbo.Character;
SELECT TOP 1 * FROM dbo.MEMB_INFO;

-- Debe funcionar (INSERT/UPDATE en prode):
INSERT INTO prode.matches (team_home, team_away, match_datetime_utc, stage)
VALUES ('Test', 'Test', GETDATE(), 'Prueba');
DELETE FROM prode.matches WHERE team_home = 'Test';

-- NO debe funcionar (INSERT/UPDATE en dbo):
-- INSERT INTO dbo.ACCOUNT_TBL ... → debe fallar con error de permisos
```

---

## 4. Cargar el primer partido (prueba)

Hacer un POST al endpoint admin con curl o con cualquier cliente HTTP (ej. Insomnia, Postman):

```bash
curl -X POST https://api.mupga.com.ar/api/prode/admin_match.php \
  -H "Content-Type: application/json" \
  -H "X-Admin-Token: <ADMIN_TOKEN del .env>" \
  -d '{
    "team_home": "Argentina",
    "team_away": "Brasil",
    "match_datetime_utc": "2026-07-01T18:00:00",
    "stage": "Fase de Grupos"
  }'
```

Respuesta esperada: `{ "message": "Partido creado.", "id": 1 }`

Verificar que aparece en `https://mupga.com.ar/mudial/`.

---

## 5. Cargar resultado de un partido (workflow normal)

```bash
curl -X POST https://api.mupga.com.ar/api/prode/admin_result.php \
  -H "Content-Type: application/json" \
  -H "X-Admin-Token: <ADMIN_TOKEN del .env>" \
  -d '{ "match_id": 1, "score_home": 2, "score_away": 1 }'
```

Respuesta esperada: `{ "message": "Resultado cargado y premios aplicados.", ... }`

Los WCoins y días VIP se acreditan automáticamente a los jugadores con predicciones correctas.

---

## 6. Cierre al terminar el mundial

Cuando el mundial termine, ejecutar los siguientes pasos para limpiar el módulo:

**6.1 Exportar ranking final** (guardar para premios manuales si los hay):

```sql
SELECT account, total_points, exact_hits, winner_hits
FROM prode.scores
ORDER BY total_points DESC, exact_hits DESC;
```

**6.2 Eliminar el schema y el usuario**:

```sql
-- En SQL Server Management Studio como sa:
DROP TABLE prode.predictions;
DROP TABLE prode.scores;
DROP TABLE prode.matches;
DROP TABLE prode.config;
DROP SCHEMA prode;
DROP USER prode_user;
DROP LOGIN prode_user;
```

**6.3 Limpiar el .env** — eliminar las líneas `PRODE_*` y `ADMIN_TOKEN`.

**6.4 Eliminar del repo**:
```
git rm -r src/public/api/prode/
git rm -r src/public/mudial/
git rm src/config/prode_db.php
git rm src/db/ProdeRepository.php
git rm database/prode_setup.sql
git rm PASOS_MANUALES_PRODE.md
```

Y revertir los cambios en `layout.php` (quitar el link "Prode"), `CLAUDE.md` y `.env.example`.
