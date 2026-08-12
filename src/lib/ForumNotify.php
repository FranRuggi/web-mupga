<?php
/**
 * ForumNotify — efectos secundarios de publicar en el foro (F-03.03 / F-07.01):
 * auto-follow del autor, avisos a menciones y a seguidores del hilo.
 *
 * SIEMPRE llamar DESPUÉS del commit del contenido y dentro de un try/catch
 * propio del caller: si los avisos fallan, la publicación no se revierte
 * (misma regla que el webhook de Discord en Reclamos).
 */
class ForumNotify {

    /**
     * Resuelve @menciones del cuerpo y avisa a cada cuenta mencionada (una vez
     * por mensaje aunque la mencionen 5 veces — el array de cuentas ya viene
     * único del repo). Devuelve las cuentas avisadas, para que el caller no
     * las duplique con el aviso de "respuesta en hilo seguido".
     * @return string[]
     */
    public static function notifyMentions(ForumRepository $repo, string $body, string $authorAccount, string $authorDisplay, int $threadId, ?int $postId): array {
        $names = ForumValidation::extractMentions($body);
        if (!$names) return [];

        $accounts = $repo->findAccountsByDisplayNames($names);
        $notified = [];
        foreach (array_unique($accounts) as $account) {
            if ($account === $authorAccount) continue; // automención: sin aviso
            $repo->addNotification($account, 'mencion', $threadId, $postId, $authorDisplay);
            $notified[] = $account;
        }
        return $notified;
    }

    /** Respuesta nueva: auto-follow del autor + avisos (menciones primero, después seguidores). */
    public static function afterNewPost(ForumRepository $repo, int $threadId, int $postId, string $authorAccount, string $authorDisplay, string $body): void {
        $repo->followThread($authorAccount, $threadId); // responder = seguir (F-07.01)

        $mentioned = self::notifyMentions($repo, $body, $authorAccount, $authorDisplay, $threadId, $postId);

        foreach ($repo->getFollowerAccounts($threadId, $authorAccount) as $follower) {
            if (in_array($follower, $mentioned, true)) continue; // ya avisado como mención
            // Agrupada: si ya tiene un aviso sin leer de este hilo, no se apila otro
            $repo->addNotification($follower, 'respuesta', $threadId, $postId, $authorDisplay, true);
        }
    }

    /** Hilo nuevo: auto-follow del autor + avisos de menciones del cuerpo. */
    public static function afterNewThread(ForumRepository $repo, int $threadId, string $authorAccount, string $authorDisplay, string $body): void {
        $repo->followThread($authorAccount, $threadId);
        self::notifyMentions($repo, $body, $authorAccount, $authorDisplay, $threadId, null);
    }
}
