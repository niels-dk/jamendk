<?php
// models/media_model.php

class media_model
{
    public static function create(PDO $db, ?int $visionId, string $uuid, string $fileName,
                                  string $mime, int $size, ?string $provider,
                                  ?string $providerId, ?string $providerUrl, ?string $embedHtml): ?int
    {
        $sql = "INSERT INTO vision_media
                (vision_id, uuid, file_name, mime_type, file_size, provider, provider_id, provider_url, embed_html, created_at, updated_at)
                VALUES (:vid, :uuid, :name, :mime, :size, :prov, :pid, :purl, :embed, NOW(), NOW())";
        $st = $db->prepare($sql);
        $st->execute([
            ':vid'   => $visionId,
            ':uuid'  => $uuid,
            ':name'  => $fileName,
            ':mime'  => $mime,
            ':size'  => $size,
            ':prov'  => $provider,
            ':pid'   => $providerId,
            ':purl'  => $providerUrl,
            ':embed' => $embedHtml
        ]);
        $id = $db->lastInsertId();
        return $id ? (int)$id : null;
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $st = $db->prepare("SELECT * FROM vision_media WHERE id=?");
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public static function attachToBoard(PDO $db, int $boardId, int $mediaId): bool
    {
        try {
            $st = $db->prepare("INSERT INTO mood_board_media (board_id, media_id, added_at) VALUES (?,?,NOW())");
            $st->execute([$boardId, $mediaId]);
            return true;
        } catch (PDOException $e) {
            return false; // duplicate or other constraint
        }
    }

    // Filtering helpers
    private static function filterWhere(string $alias, string $q, string $type): array
    {
        $clauses = [];
        $params  = [];

        if ($q !== '') {
            $clauses[] = "$alias.file_name LIKE :q";
            $params[':q'] = "%{$q}%";
        }

        if ($type !== '') {
            switch ($type) {
                case 'image': $clauses[] = "($alias.mime_type LIKE 'image/%' AND $alias.mime_type <> 'image/gif')"; break;
                case 'gif':   $clauses[] = "$alias.mime_type = 'image/gif'"; break;
                case 'video': $clauses[] = "($alias.provider='youtube' OR $alias.mime_type LIKE 'video/%')"; break;
                case 'doc':   $clauses[] = "$alias.mime_type = 'application/pdf'"; break;
            }
        }

        $where = $clauses ? (' AND ' . implode(' AND ', $clauses)) : '';
        return [$where, $params];
    }

    private static function orderBy(string $alias, string $sort): string
    {
        switch ($sort) {
            case 'name': return "$alias.file_name ASC";
            case 'type': return "$alias.mime_type ASC";
            case 'size': return "$alias.file_size DESC";
            case 'date':
            default:     return "$alias.created_at DESC";
        }
    }

    public static function allForVisionFiltered(PDO $db, int $visionId, ?int $boardCtxId, string $q, string $type, string $sort): array
    {
        [$where, $params] = self::filterWhere('m', $q, $type);
        $order = self::orderBy('m', $sort);

        $sql = "SELECT m.*" .
               ($boardCtxId ? ", CASE WHEN mb.board_id IS NULL THEN 0 ELSE 1 END AS attached_to_board" : "") .
               " FROM vision_media m " .
               ($boardCtxId ? "LEFT JOIN mood_board_media mb ON mb.media_id=m.id AND mb.board_id=:bid " : "") .
               " WHERE m.vision_id=:vid {$where} ORDER BY {$order}";
        $st = $db->prepare($sql);
        $bind = [':vid'=>$visionId] + $params;
        if ($boardCtxId) $bind[':bid'] = $boardCtxId;
        $st->execute($bind);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allForBoardFiltered(PDO $db, int $boardId, string $q, string $type, string $sort, ?int $groupId = null)
	{
		$sql = "SELECT vm.* FROM vision_media vm
				JOIN mood_board_media mbm ON vm.id = mbm.media_id
				WHERE mbm.board_id=?";
		$params = [$boardId];

		if ($q !== '') {
			$sql .= " AND (vm.file_name LIKE ? OR vm.mime_type LIKE ?)";
			$params[] = "%$q%";
			$params[] = "%$q%";
		}
		if ($type !== '') {
			$sql .= " AND vm.mime_type LIKE ?";
			$params[] = "$type%";
		}
		if ($groupId) {
			$sql .= " AND vm.group_id=?";
			$params[] = $groupId;
		}

		$sql .= " ORDER BY vm.created_at " . ($sort === 'name' ? 'ASC' : 'DESC');
		$st = $db->prepare($sql);
		$st->execute($params);
		return $st->fetchAll(PDO::FETCH_ASSOC);
	}

	
	public static function setMediaGroup(PDO $db, int $mediaId, ?int $groupId): void {
		$db->prepare("UPDATE vision_media SET group_id = ? WHERE id = ?")->execute([$groupId, $mediaId]);
	}
	
	public static function ensureGroup(PDO $db, int $userId, string $name): int {
		$name = trim($name);
		if ($name === '') return 0;
		$st = $db->prepare("SELECT id FROM media_groups WHERE user_id=? AND name=? LIMIT 1");
		$st->execute([$userId, $name]);
		$id = $st->fetchColumn();
		if (!$id) {
			$ins = $db->prepare("INSERT INTO media_groups(user_id,name) VALUES(?,?)");
			$ins->execute([$userId, $name]);
			$id = (int)$db->lastInsertId();
		}
		return (int)$id;
	}
	
	public static function listUserGroups(PDO $db, int $userId): array {
		$st = $db->prepare("SELECT id, name FROM media_groups WHERE user_id = ? ORDER BY name ASC");
		$st->execute([$userId]);
		return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}
	
	/** Fetch tags for a media (for future use / debugging) */
	public static function tagsForMedia(PDO $db, int $mediaId): array {
		$sql = "SELECT t.id, t.name
				FROM media_tags mt
				JOIN tags t ON t.id = mt.tag_id
				WHERE mt.media_id = ?
				ORDER BY t.name ASC";
		$st = $db->prepare($sql);
		$st->execute([$mediaId]);
		return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}
	
	/** Replace a media's tag set with $tagIds */
	public static function setMediaTags(PDO $db, int $mediaId, array $tagIds): void {
		$db->prepare("DELETE FROM media_tags WHERE media_id=?")->execute([$mediaId]);
		if (!$tagIds) return;
		$ins = $db->prepare("INSERT IGNORE INTO media_tags(media_id, tag_id) VALUES(?,?)");
		foreach ($tagIds as $tid) $ins->execute([$mediaId, (int)$tid]);
	}
	
	public static function ensureTags(PDO $db, int $userId, array $names): array {
		$ids = [];
		foreach ($names as $name) {
			$name = trim($name);
			if ($name === '') continue;
			$st = $db->prepare("SELECT id FROM tags WHERE user_id=? AND name=? LIMIT 1");
			$st->execute([$userId, $name]);
			$id = $st->fetchColumn();
			if (!$id) {
				$ins = $db->prepare("INSERT INTO tags(user_id,name) VALUES(?,?)");
				$ins->execute([$userId, $name]);
				$id = $db->lastInsertId();
			}
			$ids[] = (int)$id;
		}
		return array_values(array_unique($ids));
	}
	
	public static function listUserTags(PDO $db, int $userId): array {
		$st = $db->prepare("SELECT id, name FROM tags WHERE user_id = ? ORDER BY name ASC");
		$st->execute([$userId]);
		return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}
	
}
