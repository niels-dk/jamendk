<?php
/**
 * AccountTransfer — moves everything one creator OWNS to another.
 *
 * Reassigns the four ownership columns (dream_boards, visions, mood_boards,
 * teams) in a single transaction. Content shared WITH the departing user is
 * left untouched — it belongs to someone else. The child rows (goals, shots,
 * media, canvas items, …) follow their parent board automatically, since they
 * hang off board ids, not user ids.
 */
class AccountTransfer
{
    /** What a user owns, for a confirmation dialog / recipient notice. */
    public static function summary(PDO $db, int $userId): array
    {
        $count = function (string $sql) use ($db, $userId): int {
            try { $st = $db->prepare($sql); $st->execute([$userId]); return (int)$st->fetchColumn(); }
            catch (\Throwable $e) { return 0; }
        };
        return [
            'dreams'  => $count("SELECT COUNT(*) FROM dream_boards WHERE user_id=? AND deleted_at IS NULL"),
            'visions' => $count("SELECT COUNT(*) FROM visions      WHERE user_id=? AND deleted_at IS NULL"),
            'moods'   => $count("SELECT COUNT(*) FROM mood_boards  WHERE user_id=? AND deleted_at IS NULL"),
            'teams'   => $count("SELECT COUNT(*) FROM teams        WHERE owner_user_id=?"),
        ];
    }

    /** One-line English summary: "3 dreams, 2 visions, 1 mood board, 1 team". */
    public static function summaryText(array $s): string
    {
        $bits = [];
        $plural = fn(int $n, string $one, string $many) => $n . ' ' . ($n === 1 ? $one : $many);
        if ($s['dreams'])  $bits[] = $plural($s['dreams'],  'dream', 'dreams');
        if ($s['visions']) $bits[] = $plural($s['visions'], 'vision', 'visions');
        if ($s['moods'])   $bits[] = $plural($s['moods'],   'mood board', 'mood boards');
        if ($s['teams'])   $bits[] = $plural($s['teams'],   'team', 'teams');
        if (!$bits) return 'an empty account';
        if (count($bits) === 1) return $bits[0];
        $last = array_pop($bits);
        return implode(', ', $bits) . ' and ' . $last;
    }

    /**
     * Move ownership from $fromId to $toId. Returns the counts moved.
     * Manages its own transaction — callers must not already be in one.
     * @throws \Throwable on failure (rolled back).
     */
    public static function perform(PDO $db, int $fromId, int $toId): array
    {
        if ($fromId === $toId) throw new \RuntimeException('Cannot transfer to the same account.');

        $moved = self::summary($db, $fromId);

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE dream_boards SET user_id=?       WHERE user_id=?")->execute([$toId, $fromId]);
            $db->prepare("UPDATE visions      SET user_id=?       WHERE user_id=?")->execute([$toId, $fromId]);
            $db->prepare("UPDATE mood_boards  SET user_id=?       WHERE user_id=?")->execute([$toId, $fromId]);
            $db->prepare("UPDATE teams        SET owner_user_id=? WHERE owner_user_id=?")->execute([$toId, $fromId]);

            // The new owner may have held a shared ROLE on a board they now own,
            // or been a MEMBER of a team they now own — those rows are now
            // redundant (ownership is implicit). Clean them up so they don't
            // show as "also shared with themselves".
            $db->prepare("DELETE vr FROM vision_roles vr
                            JOIN visions v ON v.id = vr.vision_id
                           WHERE v.user_id = ? AND vr.user_id = ?")->execute([$toId, $toId]);
            $db->prepare("DELETE tm FROM team_members tm
                            JOIN teams t ON t.id = tm.team_id
                           WHERE t.owner_user_id = ? AND tm.user_id = ?")->execute([$toId, $toId]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
        return $moved;
    }
}
