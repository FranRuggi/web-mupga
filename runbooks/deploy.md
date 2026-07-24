# MuPGA Web — Guía de deploy en el VPS

> Deploy objetivo: **todo en un mismo VPS Windows** (Apache + PHP + SQL Server Express).
> El sitio se sirve en `https://api.mupga.com.ar` con Cloudflare como terminador SSL.

---


## Requisitos previos en el VPS

- Windows Server con SQL Server Express corriendo (instancia `SQLEXPRESS01` o similar).
- Git instalado (`git --version`).
- XAMPP instalado (Apache + PHP; sin MySQL/MariaDB — ya tenemos SQL Server).
- Acceso a `httpd-vhosts.conf` de Apache.

---

## Paso 1 — Clonar el repositorio

```bat
cd C:\
git clone https://github.com/<usuario>/web-mupga.git mupga
```

La estructura queda en `C:\mupga\`.

---

## Paso 2 — Instalar la extensión PHP para SQL Server

1. Verificar versión de PHP:
   ```bat
   php --version
   ```
   Ejemplo: `PHP 8.2.x (ts) x64`.

   > **PHP 7.4 está EOL (fin de vida).** Si el VPS tiene 7.4, actualizar XAMPP
   > a la versión con PHP 8.2 antes de continuar. El código del sitio es compatible.

2. Descargar las DLL que coincidan con la versión (TS = Thread Safe, x64):
   - URL: https://github.com/microsoft/msphpsql/releases
   - Archivos: `php_sqlsrv_82_ts_x64.dll` y `php_pdo_sqlsrv_82_ts_x64.dll`
     (reemplazar `82` con el número de versión de PHP).

3. Copiar los dos archivos a:
   ```
   C:\xampp\php\ext\
   ```

4. Abrir `C:\xampp\php\php.ini` y agregar al final de la sección `[ExtensionList]`:
   ```ini
   extension=php_sqlsrv.dll
   extension=php_pdo_sqlsrv.dll
   ```

5. También instalar el driver ODBC si no está:
   - Descargar "Microsoft ODBC Driver 17 for SQL Server" desde Microsoft.
   - Instalar y reiniciar.

6. Verificar que PHP carga la extensión:
   ```bat
   php -m | findstr sqlsrv
   ```
   Debe mostrar `pdo_sqlsrv` y `sqlsrv`.

---

## Paso 3 — Habilitar mod_rewrite en Apache

1. Abrir `C:\xampp\apache\conf\httpd.conf`.

2. Verificar que esta línea **no** está comentada:
   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   ```

3. Si estaba comentada (con `#`), quitarle el `#` y guardar.

---

## Paso 4 — Configurar el VirtualHost

Abrir `C:\xampp\apache\conf\extra\httpd-vhosts.conf` y agregar al final:

```apache
<VirtualHost *:80>
    ServerName api.mupga.com.ar
    DocumentRoot "C:/mupga/src/public"

    <Directory "C:/mupga/src/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  "C:/xampp/apache/logs/mupga-error.log"
    CustomLog "C:/xampp/apache/logs/mupga-access.log" combined
</VirtualHost>
```

> **Importante:** `AllowOverride All` es obligatorio para que el `.htaccess`
> que reinyecta el header `Authorization` funcione.

---

## Paso 5 — Habilitar el VirtualHost en httpd.conf

Asegurarse de que en `httpd.conf` la siguiente línea **no** está comentada:

```apache
Include conf/extra/httpd-vhosts.conf
```

---

## Paso 6 — Crear el `.env` de producción

En `C:\mupga\.env` (nunca commitear este archivo):

```env
# Base de datos
DB_HOST=localhost\SQLEXPRESS01
DB_PORT=
DB_NAME=MuOnline
DB_USER=sa
DB_PASS=<contraseña real de SQL Server>
DB_USE_MD5=true

# Autenticación — generar con:
#   php -r "echo bin2hex(random_bytes(32));"
APP_SECRET=<cadena aleatoria de 64 hex chars>

# CORS
# Si el frontend está en el mismo dominio (todo en VPS), dejar vacío.
# Si el frontend está en Cloudflare Pages (dominio separado), agregar aquí.
CORS_ALLOWED_ORIGINS=

# Donaciones
DONATION_URL=https://pagos.mupga.com.ar

# Rankings
RANKINGS_LIMIT=100
RANKINGS_EXCLUDED_ACCOUNTS=ruggi,Demonioo,V3rgud0

# Entorno
APP_ENV=production
APP_BASE_URL=https://api.mupga.com.ar/
```

> **Generar APP_SECRET** (correr en el VPS una sola vez):
> ```bat
> php -r "echo bin2hex(random_bytes(32));"
> ```
> Copiar el resultado y pegarlo en `APP_SECRET=`.

---

## Paso 7 — Reiniciar Apache

Desde el panel de XAMPP Control Panel: **Stop** → **Start** en Apache.

O desde consola como administrador:
```bat
net stop Apache2.4
net start Apache2.4
```

---

## Paso 8 — Verificar DNS

En Cloudflare (panel de DNS del dominio `mupga.com.ar`):

| Tipo | Nombre | Valor | Proxy |
|------|--------|-------|-------|
| A    | api    | `<IP del VPS>` | Sí (orange cloud) |
| A    | @      | `<IP del VPS>` | Sí (o CNAME a Pages si se separa) |

Con la nube naranja activada, Cloudflare termina el SSL automáticamente.
El VPS recibe tráfico HTTP en puerto 80; Cloudflare lo sirve como HTTPS al usuario.

---

## Paso 9 — Verificar que los endpoints responden

Abrir en el browser o usar curl (desde el VPS o cualquier PC):

```
https://api.mupga.com.ar/api/online.php
→ {"count":N}

https://api.mupga.com.ar/api/serverinfo.php
→ {"season":"Season 6",...}

https://api.mupga.com.ar/api/rankings.php?type=resets&limit=3
→ [{...},{...},{...}]
```

Si alguno falla:
- Ver logs en `C:\xampp\apache\logs\mupga-error.log`.
- Verificar que `AllowOverride All` está activo (sin esto el login devuelve 401 siempre).
- Verificar que `pdo_sqlsrv` está cargado: `php -m | findstr sqlsrv`.
- Verificar que SQL Server Browser Service está corriendo en Servicios de Windows.

---

## Paso 10 — Primer login de prueba

1. Ir a `https://api.mupga.com.ar/register/` y crear una cuenta de prueba.
2. Iniciar sesión.
3. Verificar que el panel de cuenta carga correctamente.
4. Probar Unstick, Clear PK, Agregar Stats con un personaje de prueba.

---

## Checklist final

- [ ] PHP carga `pdo_sqlsrv` (paso 2)
- [ ] `mod_rewrite` habilitado (paso 3)
- [ ] VirtualHost con `AllowOverride All` (paso 4)
- [ ] `.env` creado con `APP_SECRET` real y `APP_ENV=production` (paso 6)
- [ ] Apache reiniciado (paso 7)
- [ ] DNS Cloudflare apunta al VPS (paso 8)
- [ ] `/api/online.php` responde JSON (paso 9)
- [ ] Login y panel de cuenta funcionan (paso 10)

---

## Notas de mantenimiento

- **Actualizar el sitio:** `git pull` en `C:\mupga\` y reiniciar Apache.
- **Agregar cuentas admin al .env:** editar `RANKINGS_EXCLUDED_ACCOUNTS` y reiniciar Apache.
- **Rotar APP_SECRET:** genera uno nuevo con `php -r "echo bin2hex(random_bytes(32));"`,
  actualizalo en `.env` y reiniciá Apache. **Invalida todas las sesiones activas.**
- **Logs de Apache:** `C:\xampp\apache\logs\mupga-error.log`
- **Logs de PHP:** configurado en `C:\xampp\php\php.ini` → `error_log`
