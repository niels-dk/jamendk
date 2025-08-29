<?php
/**
 * mood_canvas_model
 *
 * Encapsulates CRUD operations for the `canvas_items` table.
 */
class mood_canvas_model
{
    public static function listItems(PDO $db, int $boardId): array {
        $st = $db->prepare("SELECT id, kind, x, y, w, h, z, rotation, locked, hidden, payload_json
                            FROM canvas_items WHERE board_id=? ORDER BY z ASC, id ASC");
        $st->execute([$boardId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['payload'] = $row['payload_json'] ? json_decode($row['payload_json'], true) : null;
            unset($row['payload_json']);
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
        $allowed = ['x','y','w','h','z','rotation','locked','hidden','payload_json'];
        $set     = [];
        $vals    = [];
        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) {
                if ($k === 'payload') {
                    $set[]       = 'payload_json = :payload_json';
                    $vals[':payload_json'] = is_array($v) ? json_encode($v) : $v;
                }
                continue;
            }
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
        $db->prepare("DELETE FROM canvas_items WHERE id=?")->execute([$itemId]);
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
	
	public static function createArrow(PDO $db, int $boardId, int $fromItem, int $toItem, string $style = 'solid'): array
    {
        $stmt = $db->prepare("INSERT INTO mood_board_arrows 
            (board_id, from_item_id, to_item_id, style, created_at, updated_at) 
            VALUES (:board_id, :from_item_id, :to_item_id, :style, NOW(), NOW())");
        $stmt->execute([
            ':board_id'     => $boardId,
            ':from_item_id' => $fromItem,
            ':to_item_id'   => $toItem,
            ':style'        => $style,
        ]);
        $id = (int)$db->lastInsertId();
        return [
            'id'            => $id,
            'board_id'      => $boardId,
            'from_item_id'  => $fromItem,
            'to_item_id'    => $toItem,
            'style'         => $style,
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
