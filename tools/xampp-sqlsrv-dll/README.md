# Extensiones sqlsrv para XAMPP local

`php_pdo_sqlsrv_74_ts.dll` y `php_sqlsrv_74_ts.dll` — extensiones de PHP para conectar a SQL
Server (usadas en desarrollo local con XAMPP, ver `CLAUDE.md` — Desarrollo local).

Son para PHP **7.4** (thread-safe) — confirmado que es la versión que corre hoy el VPS de
producción, así que estas `.dll` son las correctas para reusar ahí o en un XAMPP local nuevo.

`runbooks/deploy.md` todavía recomienda migrar a PHP 8.2 (7.4 está EOL) — si en algún momento
se hace esa migración, estas `.dll` quedan obsoletas y hay que bajar las de 8.2 desde
[pecl.php.net/package/sqlsrv](https://pecl.php.net/package/sqlsrv).
