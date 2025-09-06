<?php
/**
 * mood_model
 *
 * This model encapsulates data access for Mood Boards.  It mirrors the
 * structure used by vision_model but operates on the `mood_boards` table and
 * associated columns.  Functions here should remain simple and side‑effect
 * free – no JSON encoding/decoding or HTML generation.  All queries are
 * parameterised to prevent SQL injection.
 */
class mood_model
{
    /**
     * Generate a short slug using base62 characters.  Slugs are 12
     * characters long and collision‑checked.  Mirrors vision_model::generateSlug().
     */
    private static function generateSlug(PDO $db): string
    {
        do {
            $slug = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
            $st   = $db->prepare("SELECT 1 FROM mood_boards WHERE slug = ?");
            $st->execute([$slug]);
        } while ($st->fetchColumn());
        return $slug;
    }

    /**
     * Create a blank draft mood board for the given user.  Returns the new
     * board’s ID and slug.  A draft record has a NULL title until the user
     * supplies one.  `vision_id` is optional; pass null for standalone boards.
     */
    public static function createDraft(PDO $db, int $userId, ?int $visionId = null): array
    {
        $slug = self::generateSlug($db);
        $sql  = "INSERT INTO mood_boards (user_id, vision_id, slug, title, created_at, updated_at) "
              . "VALUES (:uid, :vid, :slug, NULL, NOW(), NOW())";
        $db->prepare($sql)->execute([
            ':uid'  => $userId,
            ':vid'  => $visionId,
            ':slug' => $slug,
        ]);
        $id = (int)$db->lastInsertId();
        return ['id' => $id, 'slug' => $slug];
    }

    /**
     * Fetch a single mood board by slug (non‑deleted).  Returns null if
     * not found or soft‑deleted.  Includes all columns from mood_boards.
     */
    public static function get(PDO $db, string $slug): ?array
    {
        $st = $db->prepare("SELECT * FROM mood_boards WHERE slug=? AND deleted_at IS NULL");
        $st->execute([$slug]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Update basic fields on a mood board.  Accepts an associative array of
     * column => value pairs.  Only the whitelisted columns are updated.
     */
    public static function partialUpdate(PDO $db, int $id, array $fields): void
    {
        if (!$fields) return;
        $allowed = ['title','description','vision_id'];
        $set     = [];
        $vals    = [];
        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $set[]       = "$k = :$k";
            $vals[":$k"] = $v;
        }
        if (!$set) return;
        $sql  = "UPDATE mood_boards SET " . implode(',', $set) . ", updated_at = NOW() WHERE id = :id";
        $vals[':id'] = $id;
        $stmt = $db->prepare($sql);
        $stmt->execute($vals);
    }

    /**
     * Soft delete the board.  Sets deleted_at and updated_at but retains
     * the record for potential restore.
     */
    public static function softDelete(PDO $db, int $id): void
    {
        $db->prepare("UPDATE mood_boards SET deleted_at = NOW(), updated_at = NOW() WHERE id=?")
           ->execute([$id]);
    }

    /** Restore a previously deleted board. */
    public static function restore(PDO $db, int $id): void
    {
        $db->prepare("UPDATE mood_boards SET deleted_at = NULL, updated_at = NOW() WHERE id=?")
           ->execute([$id]);
    }

    /** Archive or unarchive a board. */
    public static function setArchived(PDO $db, int $id, bool $archived): void
    {
        $db->prepare("UPDATE mood_boards SET archived = :a, updated_at = NOW() WHERE id=:id")
           ->execute([':a' => $archived ? 1 : 0, ':id' => $id]);
    }

    /**
     * Return lists of boards for dashboard sections.  These methods
     * filter by the current user and the board’s state.  Sorting by
     * creation date descending for consistency with visions/dreams.
     */
    public static function listActive(PDO $db, int $userId): array
    {
        $st = $db->prepare("SELECT id, slug, title, created_at
                            FROM mood_boards
                            WHERE user_id=? AND archived=0 AND deleted_at IS NULL
                            ORDER BY created_at DESC");
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function listArchived(PDO $db, int $userId): array
    {
        $st = $db->prepare("SELECT id, slug, title, created_at
                            FROM mood_boards
                            WHERE user_id=? AND archived=1 AND deleted_at IS NULL
                            ORDER BY created_at DESC");
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function listTrashed(PDO $db, int $userId): array
    {
        $st = $db->prepare("SELECT id, slug, title, created_at, deleted_at
                            FROM mood_boards
                            WHERE user_id=? AND deleted_at IS NOT NULL
                            ORDER BY deleted_at DESC");
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}