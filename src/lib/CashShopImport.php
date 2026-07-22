<?php
/**
 * Parsers puros para los archivos de configuración del CashShop in-game
 * (formato "Zythe CashShop Editor" / MuEmu Louis), usados para reconstruir
 * el catálogo de la tienda web (webshop.products).
 *
 * Sin acceso a DB ni al filesystem — solo transforman texto en arrays.
 * Ver database/webshop_setup.sql para el schema destino y
 * src/public/api/admin/tienda_import.php para cómo se usan.
 *
 * Archivos esperados:
 *   Server/CashShopPackageMuEmu.txt — agrupa productos bajo una categoría/paquete
 *   Server/CashShopProductMuEmu.txt — precio + stats reales del ítem por variante
 *   Client/IBSCategory.txt          — nombre de cada categoría
 *   Client/IBSPackage.txt           — descripción larga por paquete
 *   Client/IBSProduct.txt           — nombre visible por variante (base+main)
 */

/** Ítem real = ItemID; ícono en item.img/{grupo}/{índice}.gif */
function cashShopIconPath(int $itemId): array {
    return [intdiv($itemId, 512), $itemId % 512];
}

/** Líneas no vacías, sin comentarios de header ni el marcador final "end" */
function cashShopSplitLines(string $content): array {
    $lines = preg_split('/\r\n|\r|\n/', $content);
    return array_values(array_filter($lines, function (string $line): bool {
        $t = trim($line);
        return $t !== '' && !str_starts_with($t, '//') && strcasecmp($t, 'end') !== 0;
    }));
}

/** IBSCategory.txt → [category_id => name] */
function parseIbsCategory(string $content): array {
    $out = [];
    foreach (cashShopSplitLines($content) as $line) {
        $f = explode('@', $line);
        if (count($f) < 2 || !is_numeric($f[0])) continue;
        $out[(int) $f[0]] = trim($f[1]);
    }
    return $out;
}

/** IBSPackage.txt → [package_main_index => description] */
function parseIbsPackage(string $content): array {
    $out = [];
    foreach (cashShopSplitLines($content) as $line) {
        $f = explode('@', $line);
        if (count($f) < 7 || !is_numeric($f[2])) continue;
        $out[(int) $f[2]] = trim($f[6]);
    }
    return $out;
}

/** IBSProduct.txt → ["base:main" => ['name'=>..,'item_id'=>..]], primera ocurrencia gana */
function parseIbsProduct(string $content): array {
    $out = [];
    foreach (cashShopSplitLines($content) as $line) {
        $f = explode('@', $line);
        if (count($f) < 14 || !is_numeric($f[0]) || !is_numeric($f[6])) continue;
        $key = ((int) $f[0]) . ':' . ((int) $f[6]);
        if (isset($out[$key])) continue; // el archivo repite filas (menú vs detalle)
        $out[$key] = ['name' => trim($f[1]), 'item_id' => (int) $f[13]];
    }
    return $out;
}

/**
 * CashShopPackageMuEmu.txt → [product_base_index => ['package_main_index'=>, 'category_id'=>]]
 * Columnas: Cat ID Main Item Coin Price Bonus PBase1..10 PMain1..10 // Comment
 */
function parseCashShopPackage(string $content): array {
    $out = [];
    foreach (cashShopSplitLines($content) as $line) {
        $data = trim(explode('//', $line, 2)[0]);
        $f = preg_split('/\s+/', $data);
        if (count($f) < 17 || !is_numeric($f[0]) || !is_numeric($f[1])) continue;

        $categoryId = (int) $f[0];
        $packageMainIndex = (int) $f[1];

        // PBase1..10 están en las columnas 7..16 — puede haber varios no-cero
        for ($i = 7; $i <= 16; $i++) {
            $base = (int) ($f[$i] ?? 0);
            if ($base <= 0) continue;
            $out[$base] = ['package_main_index' => $packageMainIndex, 'category_id' => $categoryId];
        }
    }
    return $out;
}

/**
 * CashShopProductMuEmu.txt → lista de variantes de producto (una por fila)
 * Columnas: ID Number Value Item Level Skill Luck Option ExOpt AncOp JOH Oexe
 *           Socket1..5 Qtd Duration // Comment
 */
function parseCashShopProduct(string $content): array {
    $out = [];
    foreach (cashShopSplitLines($content) as $line) {
        $data = trim(explode('//', $line, 2)[0]);
        $f = preg_split('/\s+/', $data);
        if (count($f) < 19 || !is_numeric($f[0]) || !is_numeric($f[1])) continue;

        $out[] = [
            'product_base_index' => (int) $f[0],
            'product_main_index' => (int) $f[1],
            'price_wcoin'        => (int) $f[2],
            'item_id'            => (int) $f[3],
            'item_level'         => (int) $f[4],
            'item_skill'         => (int) $f[5],
            'item_luck'          => (int) $f[6],
            'item_option'        => (int) $f[7],
            'item_exc_opt'       => (int) $f[8],
            'item_set_opt'       => (int) $f[9],
            'item_joh'           => (int) $f[10],
            'item_oex'           => (int) $f[11],
            'item_socket1'       => (int) $f[12],
            'item_socket2'       => (int) $f[13],
            'item_socket3'       => (int) $f[14],
            'item_socket4'       => (int) $f[15],
            'item_socket5'       => (int) $f[16],
            'duration_seconds'   => (int) $f[18],
        ];
    }
    return $out;
}

/**
 * Combina los 5 archivos parseados en filas listas para INSERT en
 * webshop.products. $iconExists(group, index) decide has_icon.
 */
function buildCashShopCatalog(
    array $ibsPackages,
    array $ibsProducts,
    array $packagesByBase,
    array $products,
    callable $iconExists
): array {
    $rows = [];
    foreach ($products as $p) {
        $base = $p['product_base_index'];
        $main = $p['product_main_index'];
        $pkg  = $packagesByBase[$base] ?? null;

        $categoryId       = $pkg['category_id'] ?? null;
        $packageMainIndex = $pkg['package_main_index'] ?? null;
        $description      = $packageMainIndex !== null ? ($ibsPackages[$packageMainIndex] ?? '') : '';

        $ibs  = $ibsProducts["{$base}:{$main}"] ?? null;
        $name = $ibs['name'] ?? "Producto {$base}-{$main}";

        [$iconGroup, $iconIndex] = cashShopIconPath($p['item_id']);

        $rows[] = array_merge($p, [
            'category_id'        => $categoryId,
            'package_main_index' => $packageMainIndex,
            'name'               => $name,
            'description'        => $description,
            'icon_group'         => $iconGroup,
            'icon_index'         => $iconIndex,
            'has_icon'           => $iconExists($iconGroup, $iconIndex) ? 1 : 0,
        ]);
    }
    return $rows;
}
