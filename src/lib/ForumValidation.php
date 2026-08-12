<?php
/**
 * ForumValidation — validación y saneamiento server-side del contenido del foro.
 * Un solo lugar para límites y reglas (F-01.01): create_thread, reply y edit_post
 * usan esto, nunca copias locales.
 *
 * Contrato de renderizado (F-01.02, documentado acá porque no había dónde):
 * la API devuelve el markdown-lite CRUDO tal como se guardó; el cliente escapa
 * SIEMPRE antes de renderizar (renderRichText() en app.js llama esc() primero).
 * Todo consumidor nuevo (bot de Discord, mail, app) debe escapar por su cuenta.
 * Lo que sí se garantiza server-side: la sintaxis de link [texto](url) solo
 * sobrevive con esquema http/https — cualquier otro esquema (javascript:,
 * data:, etc.) se neutraliza a texto plano ANTES de guardar.
 */
class ForumValidation {

    const TITLE_MAX       = 120;
    const THREAD_BODY_MAX = 10000;
    const POST_BODY_MAX   = 5000;

    // Ventana en la que el autor puede editar lo suyo (admins: siempre).
    const EDIT_WINDOW_MINUTES = 30;

    // Antiflood (F-09.01) — server-side contra la base, admins exentos.
    const POST_COOLDOWN_SECONDS = 30;
    const POSTS_PER_HOUR_MAX    = 10;

    // Reportes (F-08.02)
    const REPORTS_PER_HOUR_MAX = 10;
    const REPORT_REASONS = ['spam', 'insultos', 'estafa', 'inapropiado', 'otro'];

    // Búsqueda (F-11.01)
    const SEARCH_MIN_LEN = 3;

    // Imágenes (F-04.05) — la cuota se evalúa server-side al emitir la
    // presigned URL; el tamaño solo puede validarlo el cliente (la firma
    // PUT de R2 no fija content-length, mismo límite que en Reclamos).
    const IMAGES_PER_DAY_MAX = 20;

    // Paginación (F-03.05 / F-10.02)
    const THREADS_PER_PAGE = 25;
    const POSTS_PER_PAGE   = 20;

    // Cuentas nuevas: hasta este número de publicaciones, los links externos
    // fuera de la whitelist se guardan como texto plano (F-09.03).
    const NEW_ACCOUNT_LINK_THRESHOLD = 5;
    const LINK_DOMAIN_WHITELIST = [
        'mupga.com.ar', 'youtube.com', 'youtu.be', 'imgur.com',
        'discord.gg', 'discord.com',
    ];

    /**
     * Limpia el cuerpo antes de validar/guardar: caracteres de control
     * (salvo \n y \t), caracteres invisibles de padding (zero-width),
     * saltos de línea normalizados y colapsados a máximo 2 seguidos.
     */
    public static function sanitizeBody(string $s): string {
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);
        $s = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{2060}]/u', '', $s);
        $s = preg_replace("/\n{3,}/", "\n\n", $s);
        return trim($s);
    }

    /** @return string|null mensaje de error, o null si es válido */
    public static function validateTitle(string $title): ?string {
        if ($title === '') return 'El título no puede estar vacío.';
        if (mb_strlen($title) > self::TITLE_MAX) {
            return 'El título supera los ' . self::TITLE_MAX . ' caracteres.';
        }
        return null;
    }

    /** @return string|null mensaje de error, o null si es válido */
    public static function validateBody(string $body, int $max): ?string {
        if ($body === '') return 'El mensaje no puede estar vacío.';
        if (mb_strlen($body) > $max) {
            return 'El mensaje supera los ' . $max . ' caracteres.';
        }
        return null;
    }

    /**
     * Neutraliza sintaxis de link con esquema no-http (javascript:, data:, etc.):
     * queda solo el texto del link. El cliente jamás linkifica esos esquemas,
     * pero la garantía tiene que vivir server-side, no en la regex del browser.
     */
    public static function neutralizeUnsafeLinks(string $s): string {
        return preg_replace('/\[([^\]]+)\]\(\s*(?!https?:\/\/)[^)]*\)/i', '$1', $s);
    }

    /**
     * Para cuentas nuevas: des-linkifica los links externos fuera de la
     * whitelist (quedan como texto plano, visibles pero no clickeables).
     */
    public static function restrictExternalLinks(string $s): string {
        // El lookbehind (?<!\!) evita degradar la parte [alt](url) de una
        // imagen ![alt](url) — las imágenes ya pasaron por restrictImages().
        return preg_replace_callback(
            '/(?<!\!)\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/i',
            function ($m) {
                $host = strtolower(parse_url($m[2], PHP_URL_HOST) ?? '');
                foreach (self::LINK_DOMAIN_WHITELIST as $dom) {
                    if ($host === $dom || str_ends_with($host, '.' . $dom)) {
                        return $m[0]; // whitelisted: el link queda intacto
                    }
                }
                return $m[1] . ' (' . $m[2] . ')';
            },
            $s
        );
    }

    /**
     * Prefijo público de las imágenes del foro. El foro NO tiene bucket propio:
     * reusa el de Reclamos (RECLAMOS_R2_*) bajo la carpeta foro/, un solo token
     * y un solo dominio que mantener. Vacío = R2 sin configurar.
     */
    public static function imageUrlPrefix(): string {
        $base = rtrim(trim($_ENV['RECLAMOS_R2_PUBLIC_URL'] ?? ''), '/');
        if ($base === '') return '';
        if (!preg_match('#^https?://#i', $base)) $base = 'https://' . $base;
        return $base . '/foro/';
    }

    /**
     * Imágenes ![alt](url): solo sobreviven las que viven bajo la carpeta foro/
     * del bucket. Cualquier otra se degrada a link normal [alt](url) — y de ahí
     * siguen las reglas de links. El prefijo incluye la carpeta a propósito: así
     * una URL de reclamos/ (capturas de tickets de otros jugadores) tampoco se
     * puede empotrar como imagen en un post. Sin R2 configurado se degradan todas.
     */
    public static function restrictImages(string $s): string {
        $prefix = self::imageUrlPrefix();
        return preg_replace_callback(
            '/!\[([^\]]*)\]\((https?:\/\/[^\s)]+)\)/i',
            function ($m) use ($prefix) {
                if ($prefix !== '' && stripos($m[2], $prefix) === 0) {
                    return $m[0];
                }
                return '[' . ($m[1] !== '' ? $m[1] : 'imagen') . '](' . $m[2] . ')';
            },
            $s
        );
    }

    /**
     * Extrae los @menciones del cuerpo (F-03.03): nombres de personaje MU
     * (alfanuméricos, 2-10 chars), únicos, máximo 10 por mensaje. La
     * resolución nombre→cuenta la hace el repo contra participantes del foro.
     * @return string[]
     */
    public static function extractMentions(string $s): array {
        preg_match_all('/(?<![\w@])@([A-Za-z0-9_]{2,10})\b/u', $s, $m);
        return array_slice(array_values(array_unique($m[1])), 0, 10);
    }

    /** Escapa los comodines de LIKE para búsquedas (usar con ESCAPE '\'). */
    public static function escapeLike(string $term): string {
        return addcslashes($term, '\\%_[');
    }
}
