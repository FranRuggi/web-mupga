<?php
/**
 * ForumBadges — distintivos de autor del foro (F-06.03): staff y VIP.
 *
 * Resuelve en lote para TODOS los autores de una página de una sola vez: nunca
 * una consulta por mensaje (F-13.04 — nada de N+1, y el foro no le pega a la
 * base de juego en cada render).
 *
 * De dónde sale cada cosa:
 * - **Staff**: dbo.admins, que vive en la MISMA base que el foro (mupga_admin).
 *   Se lee en vivo con AdminDatabase — es gratis y siempre está al día, así que
 *   no se cachea. Un admin recién agregado aparece marcado enseguida.
 * - **VIP**: MEMB_INFO de la base de juego (AccountLevel = 3 con
 *   AccountExpireDate futuro, mismo criterio que usercp.js y el ControlPanel).
 *   Esta sí se cachea en forum.author_badges con TTL corto: se refresca solo
 *   para los autores en pantalla cuyo dato venció.
 *
 * Degradación: si la base de juego no responde, se usa lo último cacheado (o
 * ningún distintivo) y los mensajes se muestran igual — nunca rompe el hilo.
 *
 * Privacidad: recibe cuentas pero devuelve solo banderas. La cuenta de login
 * no sale nunca en la respuesta de la API (eso lo garantizan los endpoints,
 * que borran author_account antes de responder).
 */
class ForumBadges {

    /** Minutos que vale un dato de VIP cacheado antes de volver a preguntar. */
    const VIP_CACHE_MINUTES = 60;

    /**
     * @param string[] $accounts cuentas (se deduplican solas)
     * @return array<string, array{staff:bool, vip:bool}> indexado por cuenta
     */
    public static function resolve(ForumRepository $repo, array $accounts): array {
        $accounts = array_values(array_unique(array_filter($accounts)));
        if (!$accounts) return [];

        $badges = [];
        foreach ($accounts as $acc) {
            $badges[$acc] = ['staff' => false, 'vip' => false];
        }

        // ── Staff: misma base, lectura en vivo ───────────────────────────
        try {
            $placeholders = implode(',', array_fill(0, count($accounts), '?'));
            $stmt = AdminDatabase::get()->prepare(
                "SELECT memb___id FROM dbo.admins WHERE active = 1 AND memb___id IN ($placeholders)"
            );
            $stmt->execute($accounts);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $acc) {
                if (isset($badges[$acc])) $badges[$acc]['staff'] = true;
            }
        } catch (Throwable $e) {
            // Sin distintivo de staff, pero el hilo se muestra igual
        }

        // ── VIP: caché propia + refresco de lo vencido ───────────────────
        $cache  = $repo->getCachedBadges($accounts);
        $vencidos = [];
        foreach ($accounts as $acc) {
            if (!isset($cache[$acc]) || self::cacheVencido($cache[$acc]['checked_at'])) {
                $vencidos[] = $acc;
            }
        }

        if ($vencidos) {
            try {
                $frescos = self::leerVipDelJuego($vencidos);
                foreach ($frescos as $acc => $hasta) {
                    $repo->saveBadgeCache($acc, $hasta !== null, $hasta);
                    $cache[$acc] = ['is_vip' => $hasta !== null, 'vip_until' => $hasta, 'checked_at' => null];
                }
            } catch (Throwable $e) {
                // La base de juego no respondió: seguimos con lo que había cacheado
            }
        }

        foreach ($accounts as $acc) {
            // vip_until se revalida contra ahora aunque el dato esté dentro del
            // TTL: un VIP que venció en el medio deja de mostrarse igual
            $badges[$acc]['vip'] = isset($cache[$acc])
                && $cache[$acc]['is_vip']
                && self::vigente($cache[$acc]['vip_until']);
        }

        return $badges;
    }

    private static function cacheVencido(?string $checkedAt): bool {
        if ($checkedAt === null) return false; // recién refrescado en esta misma request
        try {
            $ts = (new DateTime($checkedAt, new DateTimeZone('UTC')))->getTimestamp();
            return (time() - $ts) > self::VIP_CACHE_MINUTES * 60;
        } catch (Throwable $e) {
            return true; // fecha ilegible: mejor volver a preguntar
        }
    }

    private static function vigente(?string $hasta): bool {
        if ($hasta === null) return false;
        try {
            return (new DateTime($hasta, new DateTimeZone('UTC')))->getTimestamp() > time();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Única lectura contra la base de juego, en lote y de solo lectura.
     * AccountLevel = 3 es el único nivel VIP de MuPGA (mismo criterio que
     * usercp.js y ControlPanel).
     *
     * @param string[] $accounts
     * @return array<string, ?string> cuenta => vencimiento del VIP, o null si no tiene
     */
    private static function leerVipDelJuego(array $accounts): array {
        $placeholders = implode(',', array_fill(0, count($accounts), '?'));
        $stmt = Database::get()->prepare(
            "SELECT memb___id, AccountLevel, AccountExpireDate
             FROM MEMB_INFO WHERE memb___id IN ($placeholders)"
        );
        $stmt->execute($accounts);

        $out = [];
        foreach ($accounts as $acc) $out[$acc] = null; // cuenta inexistente = sin VIP
        foreach ($stmt->fetchAll() as $row) {
            $esVip = (int) $row['AccountLevel'] === 3 && $row['AccountExpireDate'] !== null;
            $out[$row['memb___id']] = $esVip ? $row['AccountExpireDate'] : null;
        }
        return $out;
    }
}
