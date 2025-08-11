<?php
    // models/vision.php

    class vision_model
    {
        /** Create and return the new ID (active status) */
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

        /** Generate a short slug using base62 characters */
        private static function generateSlug(PDO $db): string
        {
            do {
                $slug = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12);
                $st   = $db->prepare("SELECT 1 FROM visions WHERE slug = ?");
                $st->execute([$slug]);
            } while ($st->fetchColumn());
            return $slug;
        }

        /** Fetch a single vision by slug (non-deleted) */
        public static function get(PDO $db, string $slug): ?array
        {
            $st = $db->prepare("SELECT * FROM visions WHERE slug=? AND deleted_at IS NULL");
            $st->execute([$slug]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        }

        /** Legacy update for title/description */
        public static function update(PDO $db, int $id, string $title, string $desc): void
        {
            $sql = "UPDATE visions SET title=:t, description=:d, updated_at=NOW() WHERE id=:id";
            $db->prepare($sql)->execute([':t'=>$title, ':d'=>$desc, ':id'=>$id]);
        }

        /** Archive/unarchive */
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

        /** Dashboard list helpers */
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

        /** Generic anchors accessor */
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

        /** Replace all anchors */
        public static function replaceAnchors(PDO $db, int $boardId, array $kvPairs): void
        {
            $db->prepare("DELETE FROM vision_anchors WHERE board_id=?")->execute([$boardId]);
            if (!$kvPairs) return;
            $ins = $db->prepare("INSERT INTO vision_anchors (board_id, `key`, `value`) VALUES (?, ?, ?)");
            foreach ($kvPairs as $p) {
                $k = trim((string)($p['key'] ?? ''));
                $v = trim((string)($p['value'] ?? ''));
                if ($k !== '' && $v !== '') $ins->execute([$boardId, $k, $v]);
            }
        }

        /** Create a draft (status='draft') */
        public static function createDraft(PDO $db, int $userId): array
        {
            $slug = self::generateSlug($db);
            $sql = "INSERT INTO visions (user_id, slug, title, description, status, created_at)
                    VALUES (:uid, :slug, NULL, NULL, 'draft', NOW())";
            $db->prepare($sql)->execute([
                ':uid'  => $userId,
                ':slug' => $slug,
            ]);
            $id = (int)$db->lastInsertId();
            return ['id' => $id, 'slug' => $slug];
        }

        /**
         * Partially update a vision.  Only fields in the whitelist are updated.
         * Accepts an associative array of column => value pairs.
         */
        public static function partialUpdate(PDO $db, int $id, array $fields): void
        {
            if (!$fields) return;
            $allowed = ['title','description','status','start_date','end_date'];
            $set = [];
            $vals = [];
            foreach ($fields as $k => $v) {
                if (!in_array($k, $allowed, true)) continue;
                $set[] = "$k = :$k";
                $vals[":$k"] = $v;
            }
            if (!$set) return;
            $sql = "UPDATE visions SET " . implode(',', $set) . ", updated_at = NOW() WHERE id = :id";
            $vals[':id'] = $id;
            $stmt = $db->prepare($sql);
            $stmt->execute($vals);
        }
		
	/**
	 * Return a flattened list of up to $limit anchor rows for a board.
	 * Each entry is an associative array with 'key' and 'value'.  Useful
	 * for summary displays (e.g. dashboard cards).
	 */
	public static function getAnchorsSummary(PDO $db, int $boardId, int $limit = 4): array
	{
		$map  = self::getAnchors($db, $boardId);
		$rows = [];
		foreach ($map as $k => $vals) {
			foreach ($vals as $v) {
				$rows[] = ['key' => $k, 'value' => $v];
				if (count($rows) >= $limit) {
					return $rows;
				}
			}
		}
		return $rows;
	}

   }
