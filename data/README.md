# data/ — shim temporal, no borrar

Estos JSON los lee en runtime `src/public/api/{infodata,downloadsdata,newsdata}.php` y
`src/public/donate2/index.php` vía `PROJECT_ROOT` (`src/bootstrap.php`). No están en `src/`
porque nunca fueron pensados para durar: es contenido que la Fase 7 del `ROADMAP.md`
(ControlPanel) está migrando a la base `mupga_admin` — cuando esa migración termine, esta
carpeta se elimina y estos endpoints pasan a leer de la DB.
