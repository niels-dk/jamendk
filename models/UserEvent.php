<?php
/**
 * The per-user support timeline.
 *
 * Append-only by design: there is add() and there is history(), and nothing
 * that edits or removes one entry. An entry that can be quietly rewritten is
 * not history — and "why was this done, and by whom?" is exactly the question
 * a rewrite destroys.
 *
 * purge() is the one exception, and only for account deletion: the entries
 * describe a person, so they are that person's data and must leave with them.
 *
 * Every method swallows its own errors. Writing an entry must never break the
 * action it describes — a failed note is a lost note, not a failed
 * deactivation.
 */
class UserEvent
{
    /** Entry types the application writes itself. */
    const DEACTIVATED = 'deactivated';
    const REACTIVATED = 'reactivated';
    const NOTE        = 'note';

    /** Generous for a note, bounded so one entry cannot fill a page. */
    const MAX_BODY = 4000;

    /**
     * @param int|null $authorId The admin writing this; null means the system.
     */
    public static function add(int $userId, string $type, ?string $body, ?int $authorId): bool
    {
        global $db;
        if ($userId <= 0 || $type === '') return false;

        $body = trim((string)$body);
        try {
            $db->prepare("INSERT INTO user_events (user_id, author_id, type, body)
                          VALUES (?,?,?,?)")
               ->execute([
                   $userId,
                   $authorId > 0 ? $authorId : null,
                   mb_substr($type, 0, 40),
                   $body === '' ? null : mb_substr($body, 0, self::MAX_BODY),
               ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Newest first. The author's name is resolved here so the view stays dumb.
     *
     * @return array<int,array{id:int,type:string,body:?string,created_at:string,author_name:?string,author_email:?string}>
     */
    public static function history(int $userId, int $limit = 200): array
    {
        global $db;
        if ($userId <= 0) return [];
        try {
            // Inlined after an int cast and clamp: MySQL wants a literal in LIMIT.
            $limit = max(1, min(500, $limit));
            $st = $db->prepare(
                "SELECT e.id, e.type, e.body, e.created_at,
                        a.name AS author_name, a.email AS author_email
                   FROM user_events e
              LEFT JOIN users a ON a.id = e.author_id
                  WHERE e.user_id = ?
               ORDER BY e.created_at DESC, e.id DESC
                  LIMIT $limit"
            );
            $st->execute([$userId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Entry count per user, for the (i) badge on the user list. One query for
     * the whole table rather than a subquery per row.
     *
     * @return array<int,int> user_id => count
     */
    public static function counts(): array
    {
        global $db;
        try {
            $rows = $db->query("SELECT user_id, COUNT(*) AS n
                                  FROM user_events
                              GROUP BY user_id")->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $out = [];
            foreach ($rows as $r) $out[(int)$r['user_id']] = (int)$r['n'];
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Account deletion only. The entries describe a person; they go with them. */
    public static function purge(int $userId): void
    {
        global $db;
        try {
            $db->prepare("DELETE FROM user_events WHERE user_id = ?")->execute([$userId]);
        } catch (\Throwable $e) { /* nothing left to clean up */ }
    }
}
