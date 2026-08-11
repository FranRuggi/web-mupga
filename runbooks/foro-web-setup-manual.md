# Pasos manuales — Foro MuPGA (módulo nativo)

Instrucciones para activar el foro nuevo, nativo del sitio (reemplaza el intento con
phpBB — ver nota en `ROADMAP.md`, Fase 8). No hace falta VPS aparte, subdominio nuevo,
ni instalador de terceros: es una sección más de `web-mupga`, mismo dominio, misma cuenta
que ya usás para loguearte al sitio.

---

## 1. Crear el login y el schema en SQL Server

1. Abrir **SQL Server Management Studio**, conectado a la instancia (local o del VPS).
2. Abrir `database/foro_setup.sql` del repositorio.
3. Reemplazar `CAMBIAR_ESTA_PASSWORD` con una contraseña elegida para `mupga_forum_svc`.
4. Ejecutar el script completo (crea el schema `forum` dentro de `mupga_admin` — la
   misma base que ya usa el ControlPanel — con 5 tablas: `categories`, `threads`,
   `posts`, `reactions`, `banned_accounts`).
5. Verificar que aparecen las 5 tablas (el script ya lo hace al final, o corré):
   ```sql
   SELECT s.name AS [Schema], t.name AS [Tabla]
   FROM sys.tables t JOIN sys.schemas s ON s.schema_id = t.schema_id
   WHERE s.name = 'forum' ORDER BY t.name;
   ```

---

## 2. Variables de entorno

Agregar al `.env` (local y/o VPS, misma contraseña que el paso 1):

```
FORUM_DB_HOST=localhost\SQLEXPRESS01
FORUM_DB_PORT=
FORUM_DB_NAME=mupga_admin
FORUM_DB_USER=mupga_forum_svc
FORUM_DB_PASS=<la contraseña del paso 1>
```

Reiniciar Apache (o el servicio, según corresponda — ver `runbooks/deploy.md`).

---

## 3. Crear las categorías iniciales

1. Entrar a `/controlpanel/` logueado con una cuenta admin.
2. Pestaña **💬 Foro**.
3. Cargar las categorías desde el formulario "Nueva categoría". Sugeridas:
   - Anuncios (tildar "Solo staff puede publicar acá")
   - Comunidad General
   - Ayuda y Bugs
   - Guías y Builds
   - Guilds
   - Compra y Venta

---

## 4. Probar el flujo completo

1. Entrar a `/foro/` — deben verse las categorías creadas.
2. Entrar a una categoría, crear un hilo de prueba (logueado con una cuenta de jugador).
3. Responder el hilo, reaccionar ("🙏 Agradecer") a un mensaje.
4. Como admin: fijar/cerrar el hilo, editar o borrar un mensaje ajeno (los botones de
   moderación aparecen inline en `/foro/hilo/` para cuentas admin, sin pasar por el
   ControlPanel — solo la gestión de categorías y bans vive ahí).
5. Probar un ban de prueba desde `/controlpanel/` → Foro → "Banear cuenta del foro":
   verificar que esa cuenta ya no puede publicar/responder/reaccionar, y que el resto
   del sitio (login, juego) le sigue funcionando normal — el ban es exclusivo del foro,
   nunca toca `MEMB_INFO.bloc_code`.

---

## ¿Y la instalación de phpBB?

Quedó levantada en `foro.mupga.com.ar` pero sin usar. No hace falta tocarla para activar
este módulo (URLs distintas). Cuando este módulo esté validado en producción, dar de baja
esa instalación: parar/desinstalar el sitio de phpBB, borrar el VirtualHost de
`foro.mupga.com.ar` en `httpd-vhosts.conf`, y opcionalmente borrar la base `mupga_forum`
y el login `forum_admin` (los del phpBB, **no confundir** con `mupga_forum_svc` de este
módulo). No es urgente — no consume recursos relevantes estando quieta.
