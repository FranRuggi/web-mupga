<?php
/**
 * Acceso de solo lectura a tiendas personales (CustomStore / PShopItemValue).
 *
 * Radar de Tiendas: lista qué personajes tienen la tienda abierta ahora mismo y a qué
 * precio (Zen) publicaron cada slot. Todo lectura — sin riesgo, no toca datos de juego.
 *
 * IMPORTANTE — a verificar contra datos reales antes de confiar ciegamente:
 * - El significado exacto de CustomStore.Active/Type (¿1 = tienda abierta online? ¿Type
 *   distingue tienda normal de tienda offline?) no está confirmado contra el motor, solo
 *   inferido del nombre de columnas — no hay entorno con una tienda abierta para probar
 *   en esta sesión. Revisar con una tienda real abierta antes de confiar en el filtro.
 * - No identifica QUÉ ítem hay en cada slot (nombre, nivel, opciones, excelencias): eso
 *   vive en el blob binario Character.Inventory, que solo el GameServer sabe serializar.
 *   Ver .claude/docs/capability-matrix.md — no se escribe ahí nunca, y leerlo para mostrar
 *   el ítem real queda pendiente como una fase futura (parser del formato binario S6,
 *   necesita verificarse contra bytes reales antes de mostrarse a jugadores).
 */
class ShopRepository {

    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Devuelve las tiendas personales activas con sus ítems publicados (slot + precio).
     * @return array<int, array{name:string,store_name:?string,type:int,class:int,level:int,resets:int,items:array}>
     */
    public function getActiveShops(): array {
        $stmt = $this->pdo->query(
            "SELECT cs.Name, cs.StoreName, cs.Type,
                    ISNULL(c.Class, 0)      AS Class,
                    ISNULL(c.cLevel, 0)     AS cLevel,
                    ISNULL(c.ResetCount, 0) AS ResetCount
             FROM CustomStore cs
             LEFT JOIN Character c ON c.Name = cs.Name
             WHERE cs.Active = 1
             ORDER BY cs.Name"
        );
        $shops = $stmt->fetchAll();
        if (!$shops) {
            return [];
        }

        $names = array_column($shops, 'Name');
        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $itemStmt = $this->pdo->prepare(
            "SELECT Name, Slot, Serial, Value
             FROM PShopItemValue
             WHERE Name IN ($placeholders)
             ORDER BY Name, Slot"
        );
        $itemStmt->execute($names);

        $itemsByName = [];
        foreach ($itemStmt->fetchAll() as $row) {
            $itemsByName[$row['Name']][] = [
                'slot'   => (int) $row['Slot'],
                'serial' => (int) $row['Serial'],
                'price'  => (int) $row['Value'],
            ];
        }

        return array_map(function ($s) use ($itemsByName) {
            return [
                'name'       => $s['Name'],
                'store_name' => $s['StoreName'] !== null ? trim($s['StoreName']) : null,
                'type'       => (int) $s['Type'],
                'class'      => (int) $s['Class'],
                'level'      => (int) $s['cLevel'],
                'resets'     => (int) $s['ResetCount'],
                'items'      => $itemsByName[$s['Name']] ?? [],
            ];
        }, $shops);
    }
}
