# Skill: mupga-php-conventions

Sos un agente trabajando en el sitio custom PHP de MuPGA (MU Online Season 6).
Este skill define las convenciones de código PHP para el sitio nuevo en `src/`.

## Stack y entorno

- **PHP** con driver **PDO_SQLSRV** para SQL Server
- **XAMPP** en desarrollo, **VPS Windows** en producción
- La conexión a la DB es **local dentro del VPS** (no desde máquina externa)
- Credenciales siempre por **variables de entorno** (nunca hardcodeadas)

## Convenciones de acceso a la base de datos

### Conexión
```php
// Ejemplo de conexión recomendada
$dsn = "sqlsrv:Server={$_ENV['DB_HOST']},{$_ENV['DB_PORT']};Database={$_ENV['DB_NAME']}";
$pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
```

### Queries — SIEMPRE sentencias preparadas
```php
// Correcto
$stmt = $pdo->prepare("SELECT * FROM MEMB_INFO WHERE memb___id = ?");
$stmt->execute([$username]);
$account = $stmt->fetch();

// PROHIBIDO
$result = $pdo->query("SELECT * FROM MEMB_INFO WHERE memb___id = '$username'");
```

### Sintaxis T-SQL (SQL Server)
- `TOP N` (no `LIMIT N`)
- `GETDATE()` (no `NOW()`)
- `CONVERT(varchar(max), columna, 2)` para binarios a hex
- `[dbo].[fn_md5](password, username)` para hashing de contraseñas (si está habilitado)
- `IDENTITY(1,1)` para auto-incremento

## Convenciones de código

### Verificación de estado online (obligatoria antes de escrituras en tablas de juego)
```php
function isAccountOnline(PDO $pdo, string $username): bool {
    $stmt = $pdo->prepare("SELECT ConnectStat FROM MEMB_STAT WHERE memb___id = ?");
    $stmt->execute([$username]);
    return (bool) $stmt->fetchColumn();
}

// Uso obligatorio antes de escribir en Character o MEMB_INFO crítico
if (isAccountOnline($pdo, $username)) {
    throw new Exception("El jugador debe estar desconectado.");
}
```

### Estructura de archivos del sitio nuevo (`src/`)
```
src/
  config/         — configuración (carga de .env, constantes)
  db/             — capa de acceso a datos (queries preparadas)
  modules/        — módulos del sitio (rankings, account, etc.)
  templates/      — vistas HTML
  public/         — punto de entrada (index.php, assets)
```

### Seguridad obligatoria
- Escapar siempre el output HTML con `htmlspecialchars()`
- Validar input antes de cualquier query
- Sesiones con `session_regenerate_id()` al autenticar
- Nunca exponer stack traces al usuario en producción

## Referencia a docs

- Schema y columnas: `.claude/docs/data-dictionary.md`
- Qué se puede escribir: `.claude/docs/capability-matrix.md`
- Código de referencia (solo lectura): `htdocs/includes/classes/`
