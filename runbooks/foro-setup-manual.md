# Pasos manuales — Foro MuPGA (phpBB)

Instrucciones para levantar la Etapa 1 del foro: infraestructura base (base de datos,
subdominio, instalador de phpBB corriendo). Sin tema visual ni vinculación de cuenta de
juego todavía — eso son etapas siguientes, una vez que esto esté probado.

**Decisión de arquitectura (2026-08-11):** phpBB sobre SQL Server Express, reusando el
mismo driver `sqlsrv` que ya está instalado para el resto del sitio — así no hace falta
sumar MySQL/MariaDB al VPS (`runbooks/deploy.md` documenta que se evitó a propósito, "ya
tenemos SQL Server"). Corre como subdominio 100% independiente (`foro.mupga.com.ar`), con
su propia base de datos aislada (`mupga_forum`) — no comparte nada con `MuOnline` ni con
`mupga_admin`. Ver `Resumen Arquitectura login foro` (raíz del repo) para el plan completo
de cuenta propia + vinculación opcional a cuenta de juego (Etapa 3, más abajo).

---

## 1. Crear base de datos y login en SQL Server

1. Abrir **SQL Server Management Studio** conectado a la instancia del VPS (o la local
   para probar primero).
2. Abrir el archivo `database/forum_setup.sql` del repositorio.
3. Reemplazar `{{FORUM_DB_PASSWORD}}` con una contraseña elegida para `forum_admin`.
4. Ejecutar el script completo (crea la base `mupga_forum` + el login con `db_owner`
   **solo** dentro de esa base — no toca `MuOnline` ni `mupga_admin`).
5. Verificar:
   ```sql
   SELECT name AS [Login] FROM sys.server_principals WHERE name = 'forum_admin';
   SELECT name AS [Base]  FROM sys.databases         WHERE name = 'mupga_forum';
   ```

---

## 2. Descargar phpBB

1. Descargar la última versión estable de la rama 3.3.x desde https://www.phpbb.com/downloads/
2. Descomprimir en `C:\mupga-forum\` en el VPS (carpeta separada de `C:\mupga\`, que es el
   sitio principal — son dos aplicaciones PHP independientes).

---

## 3. Configurar el VirtualHost

Igual que el resto de los subdominios (ver `runbooks/deploy.md`, Paso 4), agregar en
`C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName foro.mupga.com.ar
    DocumentRoot "C:/mupga-forum"

    <Directory "C:/mupga-forum">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  "C:/xampp/apache/logs/mupga-forum-error.log"
    CustomLog "C:/xampp/apache/logs/mupga-forum-access.log" combined
</VirtualHost>
```

Reiniciar Apache. Agregar el registro DNS `foro` (tipo A, apuntando a la IP del VPS) en
Cloudflare — mismo criterio que `api.mupga.com.ar`.

---

## 4. Correr el instalador web de phpBB

1. Entrar a `http://foro.mupga.com.ar/install/` (o `http://<ip-del-vps>/install/` antes de
   que el DNS propague).
2. En el paso de base de datos, elegir **"MSSQL Server [ODBC]"** o el driver nativo
   `sqlsrv` si el instalador lo lista (depende de la versión de phpBB) — usar:
   - Host: `localhost\SQLEXPRESS01` (o el nombre de instancia real)
   - Base de datos: `mupga_forum`
   - Usuario: `forum_admin`
   - Contraseña: la elegida en el paso 1
3. Completar el resto del wizard (nombre del foro, cuenta de administrador del foro —
   **es una cuenta nueva, propia del foro, no una cuenta de juego**, ver la nota de
   arquitectura arriba).
4. Al terminar, **borrar la carpeta `install/`** (phpBB no arranca si la detecta —
   medida de seguridad propia del software).

---

## 5. Verificación

- `http://foro.mupga.com.ar/` carga la página de inicio de phpBB sin errores.
- Login con la cuenta de administrador creada en el wizard funciona.
- Panel de administración (`/adm/`) accesible.

---

## Etapas siguientes (no incluidas en este runbook)

- **Etapa 2 — Tema visual:** reskin de phpBB (motor de plantillas Twig desde 3.2) para que
  no se sienta "otro sitio" — paleta y tipografía de `src/public/assets/css/main.css`.
- **Etapa 3 — Vinculación de cuenta de juego:** extensión custom de phpBB (perfil del
  usuario del foro puede vincular una cuenta de un servidor de la tabla `servidores`,
  validando usuario/contraseña contra esa DB sin guardarla — ver
  `Resumen Arquitectura login foro`, punto 3). Requiere antes tener la tabla `servidores`
  y el selector multi-servidor de `mupga.com.ar` (punto 1 de ese mismo documento).
- **Etapa 4 — Moderación:** configurar grupos/permisos por foro, y decidir con Franco qué
  cuentas de `mupga_admin.dbo.admins` pasan a ser moderadores/admins del foro (son sistemas
  de cuentas separados — no hay sincronización automática de roles).
