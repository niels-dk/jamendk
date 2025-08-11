<?php
// models/vision.php

class vision_model
{
    /** Create and return the new ID */
    public static function create(PDO $db, int $userId, ?string $title, ?string $description): int
    {
        $slug = self::generateSlug($db);
        $sql  = "INSERT INTO visions (user_id, slug, title, description, status, created_at)
                 VALUES (:uid, :slug, :t, :d, 'active', NOW())";
        $db->prepare($sql)->execute([
            ':uid'  => $userId,
            ':slug' => $slug,
            ':t'    => $title,
            ':d'    => $description,
        ]);
        return (int)$db->lastInsertId();
    }

    /** Generate short slug like your dreams */
    private static function generateSlug(PDO $db): string
    {
        do {
            $slug = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
            $st   = $db->prepare("SELECT 1 FROM visions WHERE slug = ?");
            $st->execute([$slug]);
        } while ($st->fetchColumn());
        return $slug;
    }


    /** Fetch a single vision by slug (only non-deleted) */
    public static function get(PDO $db, string $slug): ?array
    {
        $st = $db->prepare("SELECT * FROM visions WHERE slug=? AND deleted_at IS NULL");
        $st->execute([$slug]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Update title/description */
    public static function update(PDO $db, int $id, string $title, string $desc): void
    {
        $sql = "UPDATE visions SET title=:t, description=:d, updated_at=NOW() WHERE id=:id";
        $db->prepare($sql)->execute([':t'=>$title, ':d'=>$desc, ':id'=>$id]);
    }

    /** Archive / Unarchive */
    public static function setArchived(PDO $db, int $id, bool $archived): void
    {
        $sql = "UPDATE visions SET archived=:a, updated_at=NOW() WHERE id=:id";
        $db->prepare($sql)->execute([':a'=>$archived ? 1 : 0, ':id'=>$id]);
    }

    /** Soft delete / restore */
    public static function softDelete(PDO $db, int $id): void
    {
        $db->prepare("UPDATE visions SET deleted_at=NOW(), updated_at=NOW() WHERE id=?")
           ->execute([$id]);
    }
    public static function restore(PDO $db, int $id): void
    {
        $db->prepare("UPDATE visions SET deleted_at=NULL, updated_at=NOW() WHERE id=?")
           ->execute([$id]);
    }

    /** List helpers for dashboard */
    public static function listActive(PDO $db, int $userId): array
    {
        $st = $db->prepare("SELECT id, slug, title, description, created_at
                            FROM visions
                            WHERE user_id=? AND archived=0 AND deleted_at IS NULL
                            ORDER BY created_at DESC");
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function listArchived(PDO $db, int $userId): array
    {
        $st = $db->prepare("SELECT id, slug, title, description, created_at
                            FROM visions
                            WHERE user_id=? AND archived=1 AND deleted_at IS NULL
                            ORDER BY created_at DESC");
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function listTrashed(PDO $db, int $userId): array
    {
        $st = $db->prepare("SELECT id, slug, title, description, created_at, deleted_at
                            FROM visions
                            WHERE user_id=? AND deleted_at IS NOT NULL
                            ORDER BY deleted_at DESC");
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Generic anchors: returns ['keyA'=>['v1','v2'], 'keyB'=>['...']] */
    public static function getAnchors(PDO $db, int $boardId): array
    {
        $st = $db->prepare("SELECT `key`, `value` FROM vision_anchors WHERE board_id=? ORDER BY id ASC");
        $st->execute([$boardId]);
        $res = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $res[$row['key']][] = $row['value'];
        }
        return $res;
    }

    /** Overwrite anchors (replace all) */
    public static function replaceAnchors(PDO $db, int $boardId, array $kvPairs): void
    {
        // $kvPairs format: [ ['key'=>'...', 'value'=>'...'], ... ]
        $db->prepare("DELETE FROM vision_anchors WHERE board_id=?")->execute([$boardId]);
        if (!$kvPairs) return;
        $ins = $db->prepare("INSERT INTO vision_anchors (board_id, `key`, `value`) VALUES (?, ?, ?)");
        foreach ($kvPairs as $p) {
            $k = trim((string)($p['key'] ?? ''));
            $v = trim((string)($p['value'] ?? ''));
            if ($k !== '' && $v !== '') $ins->execute([$boardId, $k, $v]);
        }
    }
	
	public static function createDraft(PDO $db, int $userId): array
    {
        $slug = self::generateSlug($db);
        // insert draft with no title/description
        $sql = "INSERT INTO visions (user_id, slug, title, description, status, created_at)
                VALUES (:uid, :slug, NULL, NULL, 'draft', NOW())";
        $db->prepare($sql)->execute([
            ':uid'  => $userId,
            ':slug' => $slug,
        ]);
        $id = (int)$db->lastInsertId();
        return ['id' => $id, 'slug' => $slug];
    }

}
