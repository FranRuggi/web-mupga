<?php
/**
 * Acceso a datos del foro (schema forum en mupga_admin, vía ForumDatabase).
 * Nunca toca datos de juego — el nombre de personaje se resuelve aparte
 * (CharacterRepository, conexión principal) y se guarda denormalizado acá
 * (author_display_name) al momento de postear, mismo criterio que
 * reclamos.reclamos.nick.
 *
 * Borrado: SIEMPRE soft delete (deleted_at/deleted_by) — el DELETE físico
 * no existe en este módulo desde la migración v2. Los listados públicos
 * filtran deleted_at IS NULL; la papelera del ControlPanel los recupera.
 */
class ForumRepository {

    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // -------------------------------------------------------------------------
    // Mapeo de tipos
    // -------------------------------------------------------------------------

    /**
     * SQL Server devuelve BIT vía sqlsrv como string ("0"/"1"), no bool — y "0" es
     * un string TRUTHY en JS. Sin este cast, cualquier checkbox lee como tildado
     * siempre en el frontend. Cast explícito acá, una sola vez, para que todo lo
     * que sale de este repositorio hacia el JSON tenga tipos reales.
     */
    private function mapCategoryRow(array $row): array {
        $row['id']              = (int) $row['id'];
        $row['sort_order']      = (int) $row['sort_order'];
        $row['admin_only_post'] = (bool) (int) $row['admin_only_post'];
        if (array_key_exists('is_hidden', $row))    $row['is_hidden']    = (bool) (int) $row['is_hidden'];
        if (array_key_exists('thread_count', $row)) $row['thread_count'] = (int) $row['thread_count'];
        return $row;
    }

    private function mapThreadRow(array $row): array {
        $row['id']          = (int) $row['id'];
        $row['category_id'] = (int) $row['category_id'];
        $row['is_pinned']   = (bool) (int) $row['is_pinned'];
        $row['is_locked']   = (bool) (int) $row['is_locked'];
        if (isset($row['reply_count'])) $row['reply_count'] = (int) $row['reply_count'];
        if (array_key_exists('edited_by_staff', $row)) $row['edited_by_staff'] = (bool) (int) $row['edited_by_staff'];
        if (array_key_exists('deleted_at', $row))      $row['is_deleted']      = $row['deleted_at'] !== null;
        return $row;
    }

    private function mapPostRow(array $row): array {
        $row['id']        = (int) $row['id'];
        $row['thread_id'] = (int) $row['thread_id'];
        if (array_key_exists('edited_by_staff', $row)) $row['edited_by_staff'] = (bool) (int) $row['edited_by_staff'];
        if (array_key_exists('deleted_at', $row))      $row['is_deleted']      = $row['deleted_at'] !== null;
        return $row;
    }

    // -------------------------------------------------------------------------
    // Categorías
    // -------------------------------------------------------------------------

    /**
     * Listado con conteo de hilos y última actividad (F-10.01). Las ocultas
     * solo se incluyen si $includeHidden (admins).
     */
    public function getCategories(bool $includeHidden = false): array {
        $where = $includeHidden ? '' : 'WHERE c.is_hidden = 0';
        $stmt = $this->pdo->query(
            "SELECT c.id, c.name, c.slug, c.description, c.sort_order,
                    c.admin_only_post, c.is_hidden,
                    (SELECT COUNT(*) FROM forum.threads t
                      WHERE t.category_id = c.id AND t.deleted_at IS NULL) AS thread_count,
                    (SELECT MAX(t.last_post_at) FROM forum.threads t
                      WHERE t.category_id = c.id AND t.deleted_at IS NULL) AS last_activity_at
             FROM forum.categories c
             {$where}
             ORDER BY c.sort_order ASC, c.name ASC"
        );
        return array_map([$this, 'mapCategoryRow'], $stmt->fetchAll());
    }

    public function getCategory(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM forum.categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->mapCategoryRow($row) : null;
    }

    public function createCategory(string $name, string $slug, ?string $description, int $sortOrder, bool $adminOnlyPost, bool $isHidden = false): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO forum.categories (name, slug, description, sort_order, admin_only_post, is_hidden)
             OUTPUT INSERTED.id
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $slug, $description, $sortOrder, $adminOnlyPost ? 1 : 0, $isHidden ? 1 : 0]);
        return (int) $stmt->fetchColumn();
    }

    public function updateCategory(int $id, string $name, ?string $description, int $sortOrder, bool $adminOnlyPost, bool $isHidden = false): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.categories
             SET name = ?, description = ?, sort_order = ?, admin_only_post = ?, is_hidden = ?
             WHERE id = ?'
        );
        $stmt->execute([$name, $description, $sortOrder, $adminOnlyPost ? 1 : 0, $isHidden ? 1 : 0, $id]);
        return (bool) $stmt->rowCount();
    }

    public function deleteCategory(int $id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM forum.categories WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->rowCount();
    }

    /**
     * Contenido de una categoría, separando lo visible de lo que está en la
     * papelera. Los dos cuentan para poder borrar la categoría: un hilo con
     * soft delete sigue siendo una fila y la FK contra categories lo defiende
     * igual (por eso "borré todos los hilos" no alcanzaba para borrarla).
     *
     * @return array{visible:int, deleted:int, total:int}
     */
    public function getCategoryContentCounts(int $id): array {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(CASE WHEN deleted_at IS NULL     THEN 1 END) AS visible,
                    COUNT(CASE WHEN deleted_at IS NOT NULL THEN 1 END) AS deleted
             FROM forum.threads WHERE category_id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $visible = (int) $row['visible'];
        $deleted = (int) $row['deleted'];
        return ['visible' => $visible, 'deleted' => $deleted, 'total' => $visible + $deleted];
    }

    /** Reasigna TODOS los hilos de una categoría a otra (papelera incluida). */
    public function moveAllThreads(int $fromCategoryId, int $toCategoryId): int {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.threads SET category_id = ? WHERE category_id = ?'
        );
        $stmt->execute([$toCategoryId, $fromCategoryId]);
        return $stmt->rowCount();
    }

    /**
     * Cascada real de una categoría: borra FÍSICAMENTE sus hilos, respuestas y
     * todo lo que cuelga de ellos, y después la categoría. Es la única
     * excepción al "siempre soft delete" del módulo, y a propósito: acá el
     * admin no está moderando contenido, está desarmando una sección entera
     * del foro (y ya se le ofreció mover los hilos antes de llegar acá).
     *
     * Las FK cubren solo una parte: posts y thread_follows caen por
     * ON DELETE CASCADE de threads, pero reactions y reports son polimórficas
     * (target_type + target_id, sin FK) y notifications tampoco tiene FK — esas
     * hay que limpiarlas a mano o quedan apuntando a ids que ya no existen.
     * forum.moderation_log NO se toca: la auditoría sobrevive al contenido.
     *
     * La FK threads → categories se deja SIN cascade a propósito: así un DELETE
     * suelto contra categories falla en vez de vaciar el foro en silencio.
     *
     * Lo que NO limpia: los objetos de las imágenes en R2 quedan huérfanos
     * (inaccesibles desde el foro, pero siguen ocupando lugar en el bucket).
     *
     * @return array{threads:int, posts:int} lo que se borró
     */
    public function purgeCategory(int $id): array {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM forum.threads WHERE category_id = ?');
        $stmt->execute([$id]);
        $threadCount = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM forum.posts p
             JOIN forum.threads t ON t.id = p.thread_id
             WHERE t.category_id = ?'
        );
        $stmt->execute([$id]);
        $postCount = (int) $stmt->fetchColumn();

        $subThreads = '(SELECT id FROM forum.threads WHERE category_id = :c1)';
        $subPosts   = '(SELECT p.id FROM forum.posts p
                          JOIN forum.threads t ON t.id = p.thread_id
                         WHERE t.category_id = :c2)';

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare(
                "DELETE FROM forum.reactions
                 WHERE (target_type = 'thread' AND target_id IN {$subThreads})
                    OR (target_type = 'post'   AND target_id IN {$subPosts})"
            )->execute([':c1' => $id, ':c2' => $id]);

            $this->pdo->prepare(
                "DELETE FROM forum.reports
                 WHERE (target_type = 'thread' AND target_id IN {$subThreads})
                    OR (target_type = 'post'   AND target_id IN {$subPosts})"
            )->execute([':c1' => $id, ':c2' => $id]);

            $this->pdo->prepare(
                "DELETE FROM forum.notifications WHERE thread_id IN {$subThreads}"
            )->execute([':c1' => $id]);

            // posts y thread_follows se van solos (ON DELETE CASCADE de threads)
            $this->pdo->prepare('DELETE FROM forum.threads WHERE category_id = ?')->execute([$id]);
            $this->pdo->prepare('DELETE FROM forum.categories WHERE id = ?')->execute([$id]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }

        return ['threads' => $threadCount, 'posts' => $postCount];
    }

    // -------------------------------------------------------------------------
    // Hilos
    // -------------------------------------------------------------------------

    /**
     * Paginado (F-10.02): fijados primero, después por actividad. $page arranca en 1.
     * Trae también un extracto del cuerpo y quién escribió la última respuesta, para
     * que el listado invite a leer antes de abrir un hilo nuevo. Sigue siendo una
     * sola query (la subconsulta usa IX_forum_posts_thread_created).
     */
    public function getThreadsByCategory(int $categoryId, int $page = 1, int $perPage = 25): array {
        $offset = max(0, ($page - 1) * $perPage);
        $stmt = $this->pdo->prepare(
            "SELECT t.id, t.category_id, t.title, t.author_account, t.author_display_name,
                    t.is_pinned, t.is_locked, t.locked_reason, t.reply_count,
                    t.created_at, t.last_post_at, t.edited_by_staff, t.deleted_at,
                    LEFT(t.body, 220) AS excerpt,
                    (SELECT TOP 1 p.author_display_name FROM forum.posts p
                      WHERE p.thread_id = t.id AND p.deleted_at IS NULL
                      ORDER BY p.created_at DESC, p.id DESC) AS last_post_author
             FROM forum.threads t
             WHERE t.category_id = ? AND t.deleted_at IS NULL
             ORDER BY t.is_pinned DESC, t.last_post_at DESC
             OFFSET {$offset} ROWS FETCH NEXT {$perPage} ROWS ONLY"
        );
        $stmt->execute([$categoryId]);
        return array_map([$this, 'mapThreadRow'], $stmt->fetchAll());
    }

    public function countThreadsByCategory(int $categoryId): int {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM forum.threads WHERE category_id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$categoryId]);
        return (int) $stmt->fetchColumn();
    }

    /** Devuelve el hilo incluso si está borrado — el caller decide qué hacer. */
    public function getThread(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM forum.threads WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->mapThreadRow($row) : null;
    }

    public function createThread(int $categoryId, string $title, string $body, string $account, string $displayName): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO forum.threads (category_id, title, body, author_account, author_display_name)
             OUTPUT INSERTED.id
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$categoryId, $title, $body, $account, $displayName]);
        return (int) $stmt->fetchColumn();
    }

    public function editThread(int $id, string $title, string $body, bool $byStaff = false): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.threads
             SET title = ?, body = ?, edited_at = SYSUTCDATETIME(), edited_by_staff = ?
             WHERE id = ?'
        );
        $stmt->execute([$title, $body, $byStaff ? 1 : 0, $id]);
        return (bool) $stmt->rowCount();
    }

    public function softDeleteThread(int $id, string $byAccount): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.threads
             SET deleted_at = SYSUTCDATETIME(), deleted_by = ?
             WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$byAccount, $id]);
        return (bool) $stmt->rowCount();
    }

    public function restoreThread(int $id): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.threads SET deleted_at = NULL, deleted_by = NULL WHERE id = ?'
        );
        $stmt->execute([$id]);
        return (bool) $stmt->rowCount();
    }

    public function getDeletedThreads(int $limit = 30): array {
        $stmt = $this->pdo->prepare(
            "SELECT TOP {$limit} id, category_id, title, author_display_name,
                    deleted_at, deleted_by, created_at, is_pinned, is_locked, reply_count
             FROM forum.threads
             WHERE deleted_at IS NOT NULL
             ORDER BY deleted_at DESC"
        );
        $stmt->execute();
        return array_map([$this, 'mapThreadRow'], $stmt->fetchAll());
    }

    public function setPinned(int $id, bool $pinned): bool {
        $stmt = $this->pdo->prepare('UPDATE forum.threads SET is_pinned = ? WHERE id = ?');
        $stmt->execute([$pinned ? 1 : 0, $id]);
        return (bool) $stmt->rowCount();
    }

    public function setLocked(int $id, bool $locked, ?string $reason = null): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.threads SET is_locked = ?, locked_reason = ? WHERE id = ?'
        );
        $stmt->execute([$locked ? 1 : 0, $locked ? $reason : null, $id]);
        return (bool) $stmt->rowCount();
    }

    public function moveThread(int $id, int $categoryId): bool {
        $stmt = $this->pdo->prepare('UPDATE forum.threads SET category_id = ? WHERE id = ?');
        $stmt->execute([$categoryId, $id]);
        return (bool) $stmt->rowCount();
    }

    /**
     * ¿El hilo tiene respuestas (visibles) de alguien que no sea $account?
     * Regla F-02.03: el autor solo puede borrar su hilo si nadie más participó.
     */
    public function threadHasRepliesByOthers(int $threadId, string $account): bool {
        $stmt = $this->pdo->prepare(
            'SELECT TOP 1 1 FROM forum.posts
             WHERE thread_id = ? AND author_account <> ? AND deleted_at IS NULL'
        );
        $stmt->execute([$threadId, $account]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Recalcula reply_count y last_post_at contando solo respuestas visibles.
     * last_post_at cae al created_at del hilo si no queda ninguna respuesta —
     * así borrar la última respuesta no deja el hilo "subido" artificialmente.
     */
    private function touchThreadActivity(int $threadId): void {
        $this->pdo->prepare(
            'UPDATE forum.threads
             SET reply_count = (SELECT COUNT(*) FROM forum.posts
                                WHERE thread_id = ? AND deleted_at IS NULL),
                 last_post_at = COALESCE(
                     (SELECT MAX(created_at) FROM forum.posts
                      WHERE thread_id = ? AND deleted_at IS NULL),
                     created_at)
             WHERE id = ?'
        )->execute([$threadId, $threadId, $threadId]);
    }

    // -------------------------------------------------------------------------
    // Posts (respuestas)
    // -------------------------------------------------------------------------

    /**
     * Incluye borradas (con is_deleted=true) — el endpoint decide cómo mostrarlas.
     * Paginado (F-03.05): $page arranca en 1; $perPage 0 = sin paginar (todo).
     */
    public function getPostsByThread(int $threadId, int $page = 1, int $perPage = 0): array {
        $paging = '';
        if ($perPage > 0) {
            $offset = max(0, ($page - 1) * $perPage);
            $paging = "OFFSET {$offset} ROWS FETCH NEXT {$perPage} ROWS ONLY";
        }
        $stmt = $this->pdo->prepare(
            "SELECT * FROM forum.posts WHERE thread_id = ? ORDER BY created_at ASC, id ASC {$paging}"
        );
        $stmt->execute([$threadId]);
        return array_map([$this, 'mapPostRow'], $stmt->fetchAll());
    }

    public function countPostsByThread(int $threadId): int {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM forum.posts WHERE thread_id = ?');
        $stmt->execute([$threadId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * En qué página (de $perPage) cae un post dentro de su hilo — para que los
     * permalinks #post-{id} resuelvan la página server-side (F-03.05).
     * Devuelve null si el post no es de ese hilo.
     */
    public function getPostPage(int $threadId, int $postId, int $perPage): ?int {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM forum.posts p
             WHERE p.thread_id = :t AND EXISTS (
                 SELECT 1 FROM forum.posts x
                 WHERE x.id = :p AND x.thread_id = :t2
                   AND (p.created_at < x.created_at OR (p.created_at = x.created_at AND p.id <= x.id)))'
        );
        $stmt->execute([':t' => $threadId, ':p' => $postId, ':t2' => $threadId]);
        $pos = (int) $stmt->fetchColumn();
        return $pos > 0 ? (int) ceil($pos / $perPage) : null;
    }

    public function getPost(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM forum.posts WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->mapPostRow($row) : null;
    }

    public function createPost(int $threadId, string $body, string $account, string $displayName): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO forum.posts (thread_id, author_account, author_display_name, body)
             OUTPUT INSERTED.id
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$threadId, $account, $displayName, $body]);
        $id = (int) $stmt->fetchColumn();
        $this->touchThreadActivity($threadId);
        return $id;
    }

    public function editPost(int $id, string $body, bool $byStaff = false): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE forum.posts
             SET body = ?, edited_at = SYSUTCDATETIME(), edited_by_staff = ?
             WHERE id = ?'
        );
        $stmt->execute([$body, $byStaff ? 1 : 0, $id]);
        return (bool) $stmt->rowCount();
    }

    public function softDeletePost(int $id, string $byAccount): bool {
        $post = $this->getPost($id);
        if (!$post || $post['is_deleted']) return false;

        $stmt = $this->pdo->prepare(
            'UPDATE forum.posts
             SET deleted_at = SYSUTCDATETIME(), deleted_by = ?
             WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$byAccount, $id]);
        $ok = (bool) $stmt->rowCount();
        if ($ok) $this->touchThreadActivity((int) $post['thread_id']);
        return $ok;
    }

    public function restorePost(int $id): bool {
        $post = $this->getPost($id);
        if (!$post) return false;
        $stmt = $this->pdo->prepare(
            'UPDATE forum.posts SET deleted_at = NULL, deleted_by = NULL WHERE id = ?'
        );
        $stmt->execute([$id]);
        $ok = (bool) $stmt->rowCount();
        if ($ok) $this->touchThreadActivity((int) $post['thread_id']);
        return $ok;
    }

    // -------------------------------------------------------------------------
    // Antiflood (F-09.01) — evaluado server-side contra la base
    // -------------------------------------------------------------------------

    /**
     * Actividad reciente de publicación de una cuenta (hilos + respuestas,
     * borradas incluidas — las publicó igual). Llamar DENTRO de una transacción:
     * los hints UPDLOCK/HOLDLOCK serializan dos requests simultáneos de la
     * misma cuenta (mismo patrón anti-TOCTOU que reclamos/create.php).
     *
     * @return array{seconds_since_last: ?int, count_last_hour: int}
     */
    public function getPostingThrottle(string $account): array {
        $stmt = $this->pdo->prepare(
            'SELECT
                (SELECT DATEDIFF(SECOND, MAX(x.c), GETUTCDATE()) FROM (
                    SELECT MAX(created_at) AS c FROM forum.threads WITH (UPDLOCK, HOLDLOCK)
                     WHERE author_account = :a1
                    UNION ALL
                    SELECT MAX(created_at) FROM forum.posts WITH (UPDLOCK, HOLDLOCK)
                     WHERE author_account = :a2
                ) x) AS seconds_since_last,
                (SELECT COUNT(*) FROM forum.threads
                  WHERE author_account = :a3 AND created_at > DATEADD(HOUR, -1, GETUTCDATE()))
              + (SELECT COUNT(*) FROM forum.posts
                  WHERE author_account = :a4 AND created_at > DATEADD(HOUR, -1, GETUTCDATE()))
                AS count_last_hour'
        );
        $stmt->execute([':a1' => $account, ':a2' => $account, ':a3' => $account, ':a4' => $account]);
        $row = $stmt->fetch();
        return [
            'seconds_since_last' => $row['seconds_since_last'] !== null ? (int) $row['seconds_since_last'] : null,
            'count_last_hour'    => (int) $row['count_last_hour'],
        ];
    }

    /** Publicaciones visibles totales de la cuenta (para F-09.03, links de cuentas nuevas). */
    public function countPostsByAccount(string $account): int {
        $stmt = $this->pdo->prepare(
            'SELECT (SELECT COUNT(*) FROM forum.threads WHERE author_account = :a1 AND deleted_at IS NULL)
                  + (SELECT COUNT(*) FROM forum.posts   WHERE author_account = :a2 AND deleted_at IS NULL)'
        );
        $stmt->execute([':a1' => $account, ':a2' => $account]);
        return (int) $stmt->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // Reacciones ("Agradecer" — un solo tipo, toggle on/off por cuenta)
    // -------------------------------------------------------------------------

    /** @return array{reacted: bool, count: int} */
    public function toggleReaction(string $targetType, int $targetId, string $account): array {
        $existing = $this->pdo->prepare(
            'SELECT id FROM forum.reactions WHERE target_type = ? AND target_id = ? AND account = ?'
        );
        $existing->execute([$targetType, $targetId, $account]);
        $row = $existing->fetch();

        if ($row) {
            $this->pdo->prepare('DELETE FROM forum.reactions WHERE id = ?')->execute([$row['id']]);
            $reacted = false;
        } else {
            $this->pdo->prepare(
                'INSERT INTO forum.reactions (target_type, target_id, account) VALUES (?, ?, ?)'
            )->execute([$targetType, $targetId, $account]);
            $reacted = true;
        }

        $count = $this->pdo->prepare(
            'SELECT COUNT(*) FROM forum.reactions WHERE target_type = ? AND target_id = ?'
        );
        $count->execute([$targetType, $targetId]);

        return ['reacted' => $reacted, 'count' => (int) $count->fetchColumn()];
    }

    /**
     * Cuenta de reacciones para varios targets del mismo tipo a la vez.
     * @param int[] $targetIds
     * @return array<int,int> targetId => cantidad
     */
    public function getReactionCounts(string $targetType, array $targetIds): array {
        if (!$targetIds) return [];
        $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT target_id, COUNT(*) AS c FROM forum.reactions
             WHERE target_type = ? AND target_id IN ($placeholders)
             GROUP BY target_id"
        );
        $stmt->execute([$targetType, ...$targetIds]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['target_id']] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * Qué targets ya reaccionó esta cuenta, de una lista dada.
     * @param int[] $targetIds
     * @return int[] subconjunto de $targetIds
     */
    public function getUserReactedTargets(string $targetType, array $targetIds, string $account): array {
        if (!$targetIds) return [];
        $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT target_id FROM forum.reactions
             WHERE target_type = ? AND account = ? AND target_id IN ($placeholders)"
        );
        $stmt->execute([$targetType, $account, ...$targetIds]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // -------------------------------------------------------------------------
    // Reportes (F-08.02 / F-08.03)
    // -------------------------------------------------------------------------

    public function createReport(string $targetType, int $targetId, string $reporter, string $reasonCode, ?string $comment): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO forum.reports (target_type, target_id, reporter_account, reason_code, comment)
             OUTPUT INSERTED.id
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$targetType, $targetId, $reporter, $reasonCode, $comment]);
        return (int) $stmt->fetchColumn();
    }

    public function hasReported(string $targetType, int $targetId, string $reporter): bool {
        $stmt = $this->pdo->prepare(
            'SELECT TOP 1 1 FROM forum.reports
             WHERE target_type = ? AND target_id = ? AND reporter_account = ?'
        );
        $stmt->execute([$targetType, $targetId, $reporter]);
        return (bool) $stmt->fetchColumn();
    }

    public function countRecentReportsByAccount(string $account): int {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM forum.reports
             WHERE reporter_account = ? AND created_at > DATEADD(HOUR, -1, GETUTCDATE())'
        );
        $stmt->execute([$account]);
        return (int) $stmt->fetchColumn();
    }

    /** Reportes pendientes, más viejos primero, con contexto del contenido reportado. */
    public function getPendingReports(int $limit = 50): array {
        $stmt = $this->pdo->prepare(
            "SELECT TOP {$limit} r.id, r.target_type, r.target_id, r.reporter_account,
                    r.reason_code, r.comment, r.created_at,
                    COALESCE(t.title, LEFT(p.body, 120))      AS target_excerpt,
                    COALESCE(t.author_display_name, p.author_display_name) AS target_author,
                    COALESCE(t.id, p.thread_id)               AS thread_id,
                    CASE WHEN COALESCE(t.deleted_at, p.deleted_at) IS NULL THEN 0 ELSE 1 END AS target_deleted
             FROM forum.reports r
             LEFT JOIN forum.threads t ON r.target_type = 'thread' AND t.id = r.target_id
             LEFT JOIN forum.posts   p ON r.target_type = 'post'   AND p.id = r.target_id
             WHERE r.status = 'pendiente'
             ORDER BY r.created_at ASC"
        );
        $stmt->execute();
        return array_map(function ($row) {
            $row['id']             = (int) $row['id'];
            $row['target_id']      = (int) $row['target_id'];
            $row['thread_id']      = $row['thread_id'] !== null ? (int) $row['thread_id'] : null;
            $row['target_deleted'] = (bool) (int) $row['target_deleted'];
            return $row;
        }, $stmt->fetchAll());
    }

    /**
     * Resuelve TODOS los reportes pendientes sobre el mismo contenido de una
     * (F-08.03: cerrar juntos). Devuelve cuántos cerró.
     */
    public function resolveReportsForTarget(string $targetType, int $targetId, string $status, string $resolvedBy, ?string $note): int {
        $stmt = $this->pdo->prepare(
            "UPDATE forum.reports
             SET status = ?, resolved_by = ?, resolved_note = ?, resolved_at = SYSUTCDATETIME()
             WHERE target_type = ? AND target_id = ? AND status = 'pendiente'"
        );
        $stmt->execute([$status, $resolvedBy, $note, $targetType, $targetId]);
        return $stmt->rowCount();
    }

    // -------------------------------------------------------------------------
    // Log de auditoría (F-08.05)
    // -------------------------------------------------------------------------

    public function logModeration(string $actor, string $action, string $targetType, string $targetId, ?string $reason = null, ?string $bodyBefore = null): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO forum.moderation_log (actor_account, action, target_type, target_id, reason, body_before)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$actor, $action, $targetType, $targetId, $reason, $bodyBefore]);
    }

    public function getModerationLog(int $limit = 100): array {
        $stmt = $this->pdo->prepare(
            "SELECT TOP {$limit} id, actor_account, action, target_type, target_id, reason, created_at
             FROM forum.moderation_log ORDER BY id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------------------------
    // Bans (acotados al foro — nunca tocan la cuenta de juego)
    // -------------------------------------------------------------------------

    /** Solo bans VIGENTES (sin vencimiento, o con vencimiento futuro). */
    public function getBan(string $account): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM forum.banned_accounts
             WHERE account = ? AND (expires_at IS NULL OR expires_at > GETUTCDATE())'
        );
        $stmt->execute([$account]);
        return $stmt->fetch() ?: null;
    }

    public function isBanned(string $account): bool {
        return $this->getBan($account) !== null;
    }

    /** @param int|null $expiresDays null = permanente */
    public function banAccount(string $account, string $bannedBy, ?string $reason, ?int $expiresDays = null): void {
        $update = $this->pdo->prepare(
            'UPDATE forum.banned_accounts
             SET banned_by = :by, reason = :reason, banned_at = SYSUTCDATETIME(),
                 expires_at = CASE WHEN :days IS NULL THEN NULL
                                   ELSE DATEADD(DAY, :days2, GETUTCDATE()) END
             WHERE account = :acc'
        );
        $update->execute([':by' => $bannedBy, ':reason' => $reason, ':days' => $expiresDays, ':days2' => $expiresDays, ':acc' => $account]);

        if ($update->rowCount() === 0) {
            $insert = $this->pdo->prepare(
                'INSERT INTO forum.banned_accounts (account, banned_by, reason, expires_at)
                 VALUES (:acc, :by, :reason,
                         CASE WHEN :days IS NULL THEN NULL
                              ELSE DATEADD(DAY, :days2, GETUTCDATE()) END)'
            );
            $insert->execute([':acc' => $account, ':by' => $bannedBy, ':reason' => $reason, ':days' => $expiresDays, ':days2' => $expiresDays]);
        }
    }

    public function unbanAccount(string $account): bool {
        $stmt = $this->pdo->prepare('DELETE FROM forum.banned_accounts WHERE account = ?');
        $stmt->execute([$account]);
        return (bool) $stmt->rowCount();
    }

    public function getBanHistory(int $limit = 50): array {
        $stmt = $this->pdo->prepare(
            "SELECT TOP {$limit} * FROM forum.banned_accounts ORDER BY banned_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------------------------
    // Seguir hilo (F-07.01)
    // -------------------------------------------------------------------------

    public function followThread(string $account, int $threadId): void {
        $this->pdo->prepare(
            'IF NOT EXISTS (SELECT 1 FROM forum.thread_follows WHERE account = :a AND thread_id = :t)
                 INSERT INTO forum.thread_follows (account, thread_id) VALUES (:a2, :t2)'
        )->execute([':a' => $account, ':t' => $threadId, ':a2' => $account, ':t2' => $threadId]);
    }

    public function unfollowThread(string $account, int $threadId): void {
        $this->pdo->prepare(
            'DELETE FROM forum.thread_follows WHERE account = ? AND thread_id = ?'
        )->execute([$account, $threadId]);
    }

    public function isFollowing(string $account, int $threadId): bool {
        $stmt = $this->pdo->prepare(
            'SELECT TOP 1 1 FROM forum.thread_follows WHERE account = ? AND thread_id = ?'
        );
        $stmt->execute([$account, $threadId]);
        return (bool) $stmt->fetchColumn();
    }

    /** @return string[] cuentas que siguen el hilo, sin $exclude (el que publicó) */
    public function getFollowerAccounts(int $threadId, string $exclude): array {
        $stmt = $this->pdo->prepare(
            'SELECT account FROM forum.thread_follows WHERE thread_id = ? AND account <> ?'
        );
        $stmt->execute([$threadId, $exclude]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // -------------------------------------------------------------------------
    // Notificaciones (F-07.02) — tipos: respuesta | mencion | gracias | moderacion
    // -------------------------------------------------------------------------

    /**
     * Crea un aviso. Con $groupByThread=true (tipo 'respuesta') NO inserta si el
     * destinatario ya tiene un aviso sin leer del mismo tipo para ese hilo —
     * así N respuestas nuevas generan UNA notificación agrupada (F-07.01).
     */
    public function addNotification(string $account, string $type, ?int $threadId, ?int $postId, ?string $actorDisplay, bool $groupByThread = false): void {
        if ($groupByThread && $threadId !== null) {
            $dup = $this->pdo->prepare(
                'SELECT TOP 1 1 FROM forum.notifications
                 WHERE account = ? AND type = ? AND thread_id = ? AND read_at IS NULL'
            );
            $dup->execute([$account, $type, $threadId]);
            if ($dup->fetchColumn()) return;
        }
        $this->pdo->prepare(
            'INSERT INTO forum.notifications (account, type, thread_id, post_id, actor_display)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$account, $type, $threadId, $postId, $actorDisplay]);
    }

    /** Últimos avisos con título del hilo (para armar el link en el panel). */
    public function getNotifications(string $account, int $limit = 20): array {
        $stmt = $this->pdo->prepare(
            "SELECT TOP {$limit} n.id, n.type, n.thread_id, n.post_id, n.actor_display,
                    n.created_at, n.read_at, t.title AS thread_title
             FROM forum.notifications n
             LEFT JOIN forum.threads t ON t.id = n.thread_id
             WHERE n.account = ?
             ORDER BY n.created_at DESC, n.id DESC"
        );
        $stmt->execute([$account]);
        return array_map(function ($row) {
            $row['id']        = (int) $row['id'];
            $row['thread_id'] = $row['thread_id'] !== null ? (int) $row['thread_id'] : null;
            $row['post_id']   = $row['post_id'] !== null ? (int) $row['post_id'] : null;
            $row['is_read']   = $row['read_at'] !== null;
            unset($row['read_at']);
            return $row;
        }, $stmt->fetchAll());
    }

    public function countUnreadNotifications(string $account): int {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM forum.notifications WHERE account = ? AND read_at IS NULL'
        );
        $stmt->execute([$account]);
        return (int) $stmt->fetchColumn();
    }

    /** Marca UN aviso propio como leído (el WHERE account impide tocar ajenos). */
    public function markNotificationRead(string $account, int $id): void {
        $this->pdo->prepare(
            'UPDATE forum.notifications SET read_at = SYSUTCDATETIME()
             WHERE id = ? AND account = ? AND read_at IS NULL'
        )->execute([$id, $account]);
    }

    public function markAllNotificationsRead(string $account): void {
        $this->pdo->prepare(
            'UPDATE forum.notifications SET read_at = SYSUTCDATETIME()
             WHERE account = ? AND read_at IS NULL'
        )->execute([$account]);
    }

    /** Purga oportunista (>60 días) — no hay SQL Agent en Express para un job. */
    public function purgeOldNotifications(): void {
        $this->pdo->query(
            'DELETE FROM forum.notifications WHERE created_at < DATEADD(DAY, -60, GETUTCDATE())'
        );
    }

    // -------------------------------------------------------------------------
    // Menciones (F-03.03) — resolución de @Personaje contra participantes del foro
    // -------------------------------------------------------------------------

    /**
     * Mapea nombres visibles (personajes) → cuenta, buscando SOLO entre quienes
     * ya publicaron en el foro (nunca contra la base de juego: no filtrar la
     * lista de cuentas del servidor). Case-insensitive.
     * @param string[] $names
     * @return array<string,string> nombre en minúsculas => cuenta
     */
    public function findAccountsByDisplayNames(array $names): array {
        if (!$names) return [];
        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT author_display_name, MAX(author_account) AS account FROM (
                 SELECT author_display_name, author_account FROM forum.threads WHERE author_display_name IN ($placeholders)
                 UNION ALL
                 SELECT author_display_name, author_account FROM forum.posts WHERE author_display_name IN ($placeholders)
             ) x GROUP BY author_display_name"
        );
        $stmt->execute([...$names, ...$names]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[mb_strtolower($row['author_display_name'])] = $row['account'];
        }
        return $out;
    }

    // -------------------------------------------------------------------------
    // Búsqueda (F-11.01) — LIKE acotado (Express sin Full-Text), TOP fijo
    // -------------------------------------------------------------------------

    /**
     * Busca en títulos y cuerpos (hilos + respuestas visibles). Devuelve hasta
     * $limit hilos ordenados por actividad; cantidad fija de queries (2), sin
     * N+1 (F-13.04). El término ya viene con los comodines LIKE escapados.
     */
    public function searchThreads(string $likeTerm, int $categoryId = 0, bool $includeHidden = false, int $limit = 30): array {
        $hiddenCond = $includeHidden ? '' : 'AND c.is_hidden = 0';
        $catCond    = $categoryId > 0 ? 'AND t.category_id = :cat' : '';
        // is_pinned/is_locked se seleccionan aunque el buscador no los muestre:
        // mapThreadRow() los castea siempre y sin ellos tira warnings que
        // ensucian el JSON de la respuesta.
        $stmt = $this->pdo->prepare(
            "SELECT TOP {$limit} t.id, t.category_id, t.title, t.author_display_name, t.body,
                    t.is_pinned, t.is_locked,
                    t.reply_count, t.created_at, t.last_post_at, c.name AS category_name
             FROM forum.threads t
             JOIN forum.categories c ON c.id = t.category_id
             WHERE t.deleted_at IS NULL {$hiddenCond} {$catCond}
               AND (t.title LIKE :q1 ESCAPE '!' OR t.body LIKE :q2 ESCAPE '!'
                    OR EXISTS (SELECT 1 FROM forum.posts p
                               WHERE p.thread_id = t.id AND p.deleted_at IS NULL
                                 AND p.body LIKE :q3 ESCAPE '!'))
             ORDER BY t.last_post_at DESC"
        );
        $params = [':q1' => $likeTerm, ':q2' => $likeTerm, ':q3' => $likeTerm];
        if ($categoryId > 0) $params[':cat'] = $categoryId;
        $stmt->execute($params);
        return array_map([$this, 'mapThreadRow'], $stmt->fetchAll());
    }

    /**
     * Para hilos donde el match está en una respuesta (no en el título/cuerpo),
     * trae UNA respuesta coincidente por hilo — una sola query para todos.
     * @param int[] $threadIds
     * @return array<int,string> threadId => cuerpo de la respuesta coincidente
     */
    public function getMatchingPostBodies(array $threadIds, string $likeTerm): array {
        if (!$threadIds) return [];
        $placeholders = implode(',', array_fill(0, count($threadIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT p.thread_id, p.body FROM forum.posts p
             WHERE p.id IN (SELECT MIN(p2.id) FROM forum.posts p2
                            WHERE p2.thread_id IN ($placeholders) AND p2.deleted_at IS NULL
                              AND p2.body LIKE ? ESCAPE '!'
                            GROUP BY p2.thread_id)"
        );
        $stmt->execute([...$threadIds, $likeTerm]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['thread_id']] = $row['body'];
        }
        return $out;
    }

    // -------------------------------------------------------------------------
    // Caché de distintivos de autor (F-06.03)
    // -------------------------------------------------------------------------
    // Solo la parte que vive en el schema forum. Quién es staff y quién tiene
    // VIP lo resuelve ForumBadges: este repositorio nunca toca la base de juego.

    /**
     * @param string[] $accounts
     * @return array<string, array{is_vip:bool, vip_until:?string, checked_at:string}>
     */
    public function getCachedBadges(array $accounts): array {
        if (!$accounts) return [];
        $placeholders = implode(',', array_fill(0, count($accounts), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT account, is_vip, vip_until, checked_at
             FROM forum.author_badges WHERE account IN ($placeholders)"
        );
        $stmt->execute($accounts);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['account']] = [
                'is_vip'     => (bool) (int) $row['is_vip'],
                'vip_until'  => $row['vip_until'],
                'checked_at' => $row['checked_at'],
            ];
        }
        return $out;
    }

    /** Upsert sin MERGE (mismo patrón que banAccount). */
    public function saveBadgeCache(string $account, bool $isVip, ?string $vipUntil): void {
        $update = $this->pdo->prepare(
            'UPDATE forum.author_badges
             SET is_vip = :vip, vip_until = :until, checked_at = SYSUTCDATETIME()
             WHERE account = :acc'
        );
        $update->execute([':vip' => $isVip ? 1 : 0, ':until' => $vipUntil, ':acc' => $account]);

        if ($update->rowCount() === 0) {
            $this->pdo->prepare(
                'INSERT INTO forum.author_badges (account, is_vip, vip_until)
                 VALUES (:acc, :vip, :until)'
            )->execute([':acc' => $account, ':vip' => $isVip ? 1 : 0, ':until' => $vipUntil]);
        }
    }

    // -------------------------------------------------------------------------
    // Imágenes (F-04.05) — cuota diaria de subidas a R2
    // -------------------------------------------------------------------------

    public function countImageUploadsToday(string $account): int {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM forum.image_uploads
             WHERE account = ? AND created_at > DATEADD(DAY, -1, GETUTCDATE())'
        );
        $stmt->execute([$account]);
        return (int) $stmt->fetchColumn();
    }

    public function logImageUpload(string $account, string $objectKey): void {
        $this->pdo->prepare(
            'INSERT INTO forum.image_uploads (account, object_key) VALUES (?, ?)'
        )->execute([$account, $objectKey]);
    }
}
