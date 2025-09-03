<?php
/**
 * mood_canvas_model
 *
 * Encapsulates CRUD operations for the `canvas_items` table.
 */
class MoodCanvas {
    public static function deleteItem($slug, $id) {
        $db = db();
        return $db->prepare("1DELETE FROM mood_board_items WHERE id=? AND board_id=(SELECT id FROM mood_boards WHERE slug=?)")
                  ->execute([$id, $slug]);
    }

    public static function deleteArrow($slug, $id) {
        $db = db();
        return $db->prepare("DELETE FROM mood_board_arrows WHERE id=? AND board_id=(SELECT id FROM mood_boards WHERE slug=?)")
                  ->execute([$id, $slug]);
    }
}

class mood_canvas_model
{
    public static function listItems(PDO $db, int $boardId): array {
		$sql = "
		  SELECT
			ci.id, ci.kind, ci.x, ci.y, ci.w, ci.h, ci.z, ci.rotation, ci.locked, ci.hidden,
			ci.payload_json, ci.style_json, ci.media_id,
			vm.uuid        AS media_uuid,
			vm.file_name   AS media_file_name,
			vm.mime_type   AS media_mime_type,
			vm.provider    AS media_provider,
			vm.provider_id AS media_provider_id
		  FROM canvas_items ci
		  LEFT JOIN vision_media vm ON vm.id = ci.media_id
		  WHERE ci.board_id = ? AND ci.hidden = 0
		  ORDER BY ci.z ASC, ci.id ASC
		";
		$st = $db->prepare($sql);
		$st->execute([$boardId]);
		$rows = $st->fetchAll(PDO::FETCH_ASSOC);

		foreach ($rows as &$row) {
			$row['payload'] = $row['payload_json'] ? json_decode($row['payload_json'], true) : null;
			$row['style']   = $row['style_json']   ? json_decode($row['style_json'],   true) : null;

			// NEW: pack a media object if present
			if (!empty($row['media_id'])) {
				$row['media'] = [
				  'id'          => (int)$row['media_id'],
				  'uuid'        => $row['media_uuid'],
				  'file_name'   => $row['media_file_name'],
				  'mime_type'   => $row['media_mime_type'],
				  'provider'    => $row['media_provider'],
				  'provider_id' => $row['media_provider_id'],
				];
			} else {
				$row['media'] = null;
			}

			unset($row['payload_json'], $row['style_json'],
				  $row['media_uuid'], $row['media_file_name'], $row['media_mime_type'],
				  $row['media_provider'], $row['media_provider_id']);
			$row['id']       = (int)$row['id'];
			$row['x']        = (int)$row['x'];
			$row['y']        = (int)$row['y'];
			$row['w']        = (int)$row['w'];
			$row['h']        = (int)$row['h'];
			$row['z']        = (int)$row['z'];
			$row['rotation'] = (int)$row['rotation'];
			$row['locked']   = (bool)$row['locked'];
			$row['hidden']   = (bool)$row['hidden'];
		}
		return $rows;
	}



    public static function createItem(PDO $db, int $boardId, string $kind, int $x, int $y, int $w, int $h, $payload): array {
        $payloadJson = $payload !== null ? json_encode($payload) : null;
        $sql = "INSERT INTO canvas_items (board_id, kind, x, y, w, h, z, rotation, locked, hidden, payload_json, created_at, updated_at)
                VALUES (:board_id, :kind, :x, :y, :w, :h, 0, 0, 0, 0, :payload_json, NOW(), NOW())";
        $st  = $db->prepare($sql);
        $st->execute([
            ':board_id'     => $boardId,
            ':kind'         => $kind,
            ':x'            => $x,
            ':y'            => $y,
            ':w'            => $w,
            ':h'            => $h,
            ':payload_json' => $payloadJson,
        ]);
        $id = (int)$db->lastInsertId();
        return [
            'id'       => $id,
            'kind'     => $kind,
            'x'        => $x,
            'y'        => $y,
            'w'        => $w,
            'h'        => $h,
            'z'        => 0,
            'rotation' => 0,
            'locked'   => 0,
            'hidden'   => 0,
            'payload'  => $payload,
        ];
    }

    public static function updateItem(PDO $db, int $itemId, array $fields): void {
		if (!$fields) return;

		$allowed = ['x','y','w','h','z','rotation','locked','hidden','payload_json','style_json','media_id'];
		$set     = [];
		$vals    = [];

		foreach ($fields as $k => $v) {
			if (!in_array($k, $allowed, true)) {
				// alias: payload → payload_json (merge)
				if ($k === 'payload') {
					// read current
					$cur = $db->prepare("SELECT payload_json FROM canvas_items WHERE id = ?");
					$cur->execute([$itemId]);
					$row = $cur->fetch(PDO::FETCH_ASSOC);
					$current = $row && $row['payload_json'] ? json_decode($row['payload_json'], true) : [];
					if (is_array($v)) $current = array_merge($current ?: [], $v);

					$set[] = 'payload_json = :payload_json';
					$vals[':payload_json'] = json_encode($current, JSON_UNESCAPED_SLASHES);
				}
				// alias: style → style_json (merge)
				else if ($k === 'style') {
					$cur = $db->prepare("SELECT style_json FROM canvas_items WHERE id = ?");
					$cur->execute([$itemId]);
					$row = $cur->fetch(PDO::FETCH_ASSOC);
					$current = $row && $row['style_json'] ? json_decode($row['style_json'], true) : [];
					if (is_array($v)) {
						foreach ($v as $sk => $sv) {
							if ($sv === null || $sv === '') unset($current[$sk]); else $current[$sk] = $sv;
						}
					}
					$set[] = 'style_json = :style_json';
					$vals[':style_json'] = json_encode($current, JSON_UNESCAPED_SLASHES);
				}
				// alias: media → media_id (by id or uuid)
				else if ($k === 'media') {
					$mediaId = null;
					if (is_array($v)) {
						if (!empty($v['id'])) {
							$mediaId = (int)$v['id'];
						} else if (!empty($v['uuid'])) {
							$q = $db->prepare("SELECT id FROM vision_media WHERE uuid = ?");
							$q->execute([$v['uuid']]);
							$found = $q->fetch(PDO::FETCH_ASSOC);
							if ($found) $mediaId = (int)$found['id'];
						}
					}
					$set[] = 'media_id = :media_id';
					$vals[':media_id'] = $mediaId; // can be null to clear
				}
				continue;
			}

			// standard scalar fields
			$set[]       = "$k = :$k";
			$vals[":$k"] = $v;
		}

		if (!$set) return;

		$sql  = "UPDATE canvas_items SET " . implode(',', $set) . ", updated_at = NOW() WHERE id = :id";
		$vals[':id'] = $itemId;
		$st   = $db->prepare($sql);
		$st->execute($vals);
	}



    public static function deleteItem(PDO $db, int $itemId): void {
        // Soft-delete items by marking them hidden rather than removing them completely.
        // This preserves the record so that bulk operations and related connectors can
        // reference it if needed. It also avoids race conditions where a client
        // deletes an item that another client is still editing.
        $st = $db->prepare("UPDATE canvas_items SET hidden=1, updated_at=NOW() WHERE id=?");
        $st->execute([$itemId]);
    }

    public static function bulkUpdate(PDO $db, int $boardId, array $moves): void {
        foreach ($moves as $move) {
            if (!is_array($move) || !isset($move['id'])) continue;
            $id = (int)$move['id'];
            $fields = $move;
            unset($fields['id']);
            self::updateItem($db, $id, $fields);
        }
    }
	
	public function saveArrow($slug, $data) {
		$stmt = $this->db->prepare("INSERT INTO mood_board_arrows 
			(board_id, from_item_id, to_item_id, style, color, label, created_at, updated_at) 
			VALUES ((SELECT id FROM mood_boards WHERE slug=?),?,?,?,?,?,NOW(),NOW())");
		$stmt->execute([
			$slug,
			$data['from_item_id'] ?? null,
			$data['to_item_id'] ?? null,
			$data['style'] ?? 'solid',
			$data['color'] ?? null,
			$data['label'] ?? null,
		]);
		$id = $this->db->lastInsertId();
		return ['id'=>$id] + $data;
	}

	public static function deleteArrow(PDO $db, int $arrowId): void
	{
        $db->prepare("DELETE FROM mood_board_arrows WHERE id=?")->execute([$arrowId]);
    }
	
	public static function createArrow(PDO $db, int $boardId, int $from, int $to, string $style = 'solid'): array {
			$stmt = $db->prepare("INSERT INTO mood_board_arrows 
				 (board_id, from_item_id, to_item_id, style, created_at, updated_at)
				 VALUES (:board_id, :from_item, :to_item, :style, NOW(), NOW())");
			$stmt->execute([
				':board_id' => $boardId,
				':from_item' => $from,
				':to_item' => $to,
				':style' => $style,
			]);
			$id = (int)$db->lastInsertId();
			return [
				'id' => $id,
				'board_id' => $boardId,
				'from_item_id' => $from,
				'to_item_id' => $to,
				'style' => $style,
			];
		}


	
	public function deleteItemById(int $id): void {
    // Look up the item to know its kind and payload
		$stmt = $this->db->prepare("SELECT id, kind, payload FROM mood_board_items WHERE id=? LIMIT 1");
		$stmt->execute([$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$row) return;

		$kind = $row['kind'];

		if ($kind === 'connector') {
			// Remove ONLY the connector
			$del = $this->db->prepare("DELETE FROM mood_board_items WHERE id=?");
			$del->execute([$id]);
			return;
		}

		// Non-connector (text, frame, etc.): delete the item…
		$delItem = $this->db->prepare("DELETE FROM mood_board_items WHERE id=?");
		$delItem->execute([$id]);

		// …and cascade: remove any connectors referencing it (payload->a.item or payload->b.item)
		// JSON_EXTRACT works on MySQL 5.7+ / MariaDB 10.2+. If older, decode in PHP and loop.
		$delCon = $this->db->prepare("
			DELETE FROM mood_board_items
			WHERE kind='connector'
			  AND (
				JSON_EXTRACT(payload, '$.a.item') = ?
				OR JSON_EXTRACT(payload, '$.b.item') = ?
			  )
		");
		try {
			$delCon->execute([$id, $id]);
		} catch (\Throwable $e) {
			// Fallback for DBs without JSON_EXTRACT: do a PHP-side cleanup
			$sel = $this->db->query("SELECT id, payload FROM mood_board_items WHERE kind='connector'");
			while ($c = $sel->fetch(PDO::FETCH_ASSOC)) {
				$p = json_decode($c['payload'], true) ?: [];
				$a = $p['a']['item'] ?? null; $b = $p['b']['item'] ?? null;
				if ($a == $id || $b == $id) {
					$this->db->prepare("DELETE FROM mood_board_items WHERE id=?")->execute([$c['id']]);
				}
			}
		}
	}

}
