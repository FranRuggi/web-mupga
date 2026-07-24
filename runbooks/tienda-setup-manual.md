# Pasos manuales — Módulo Tienda de Ítems (WCoin)

Instrucciones para probar la Etapa 1 (catálogo + import) en local con XAMPP.
Todavía no hay nada para desplegar al VPS — esta etapa es solo el catálogo, sin compra real.

---

## 1. Crear usuario y schema en SQL Server (local)

1. Abrir **SQL Server Management Studio**, conectado a la instancia local
   (`localhost\SQLEXPRESS01` o la que uses).
2. Seleccionar la base de datos **MuOnline**.
3. Abrir el archivo `database/webshop_setup.sql` del repositorio.
4. Reemplazar `{{WEBSHOP_DB_PASSWORD}}` con una contraseña elegida para `webshop_user`.
5. Ejecutar el script completo.
6. Verificar que aparecen las 2 tablas del schema `webshop`:

```sql
SELECT s.name AS [Schema], t.name AS [Tabla]
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE s.name = 'webshop'
ORDER BY t.name;
-- Debe devolver: categories, products
```

---

## 2. Configurar variables de entorno

1. Abrir el `.env` local del proyecto (raíz del repo, al lado de `.env.example`).
2. Agregar (misma password que elegiste en el paso 1):

```
WEBSHOP_DB_HOST=localhost\SQLEXPRESS01
WEBSHOP_DB_PORT=
WEBSHOP_DB_NAME=MuOnline
WEBSHOP_DB_USER=webshop_user
WEBSHOP_DB_PASS=<la misma contraseña que en el paso 1>
```

3. Reiniciar Apache desde **XAMPP Control Panel → Apache → Restart**.

---

## 3. Verificar permisos de webshop_user

Conectarse a SQL Server (SSMS → New Query, cambiar autenticación a SQL Server con
`webshop_user` / la password elegida) y confirmar:

```sql
-- Debe funcionar:
SELECT TOP 1 * FROM webshop.products;
INSERT INTO webshop.categories (category_id, name) VALUES (999, 'Test');
DELETE FROM webshop.categories WHERE category_id = 999;

-- NO debe funcionar (debe fallar con error de permisos):
-- SELECT TOP 1 * FROM dbo.CashShopData;
```

---

## 4. Probar el import desde ControlPanel

1. Con XAMPP corriendo, entrar a `http://localhost/controlpanel/` (o la ruta local que uses)
   logueado con una cuenta que esté en `mupga_admin.dbo.admins` con `active=1`.
2. Ir al tab **Tienda**.
3. Arrastrá los 5 archivos (se identifican solos por nombre, no importa el orden), todos
   desde la carpeta `tiendaweb/` del repo:
   - `tiendaweb/Server/CashShopPackage.txt`
   - `tiendaweb/Server/CashShopProduct.txt`
   - `tiendaweb/Client/IBSCategory.txt`
   - `tiendaweb/Client/IBSPackage.txt`
   - `tiendaweb/Client/IBSProduct.txt`
4. Confirmar que el checklist marca los 5 con ✔ y click **Reimportar catálogo**.

**Resultado esperado:** "Catálogo reimportado ✔ (10 categorías, 27 productos)" y la lista de
íconos faltantes debe mostrar únicamente Silver Key (ItemID 7280) y Gold Key (ItemID 7281).

---

## 5. Verificar el catálogo en la DB

```sql
SELECT name, price_wcoin, item_id, icon_group, icon_index, has_icon
FROM webshop.products
ORDER BY category_id, product_main_index;

-- Chequeo puntual: Spirit of Guardian (7 Days) debe tener price_wcoin=2500, item_id=6721
SELECT * FROM webshop.products WHERE product_base_index = 1060 AND product_main_index = 1061;
```

Si algo no matchea (nombre vacío, precio en 0, categoría NULL en productos que sí tienen
paquete), el problema está en el parseo — revisar `src/lib/CashShopImport.php` contra el
archivo fuente correspondiente antes de tocar la DB a mano.
