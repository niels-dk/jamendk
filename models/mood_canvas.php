<?php
/**
 * mood_canvas_model
 *
 * Encapsulates CRUD operations for the `canvas_items` table.
 */
class MoodCanvas {
	private static function db() { return db(); } // your existing helper
	
    public static function deleteItem($slug, $id) {
        $db = db();
        return $db->prepare("DELETE FROM mood_board_items WHERE id=? AND board_id=(SELECT id FROM mood_boards WHERE slug=?)")
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
    // Return all visible items for a board
	  public static function listItems($slug) {
		$sql = "SELECT i.* FROM mood_board_items i
				JOIN mood_boards b ON b.id = i.board_id
				WHERE b.slug = ? AND COALESCE(i.hidden,0) = 0
				ORDER BY i.z ASC, i.id ASC";
		$stm = self::db()->prepare($sql);
		$stm->execute([$slug]);
		return $stm->fetchAll(PDO::FETCH_ASSOC);
	  }

    public static function createItem($slug, array $data) {
		$db = self::db();
		$db->beginTransaction();
		try {
		  $boardId = self::boardId($slug);
		  $sql = "INSERT INTO mood_board_items
					(board_id, kind, x, y, w, h, z, rotation, locked, hidden, payload)
				  VALUES (?,?,?,?,?,?,?,?,?,?,?)";
		  $stm = $db->prepare($sql);
		  $stm->execute([
			$boardId,
			$data['kind'] ?? 'note',
			(int)($data['x'] ?? 0), (int)($data['y'] ?? 0),
			(int)($data['w'] ?? 0), (int)($data['h'] ?? 0),
			(int)($data['z'] ?? 0),
			(int)($data['rotation'] ?? 0),
			(int)($data['locked'] ?? 0),
			(int)($data['hidden'] ?? 0),
			json_encode($data['payload'] ?? (object)[])
		  ]);
		  $id = (int)$db->lastInsertId();
		  $db->commit();
		  return self::getItem($boardId, $id);
		} catch(\Throwable $e) { $db->rollBack(); throw $e; }
	  }

    public static function updateItem($slug, int $id, array $data)
	{
		$boardId = self::boardId($slug);
		// allow x,y,w,h, z, rotation, locked, hidden, payload
		$fields = [];
		$args   = [];
		foreach (['x','y','w','h','z','rotation','locked','hidden'] as $k) {
		  if (array_key_exists($k, $data)) { $fields[] = "$k=?"; $args[] = (int)$data[$k]; }
		}
		if (array_key_exists('payload', $data)) { $fields[] = "payload=?"; $args[] = json_encode($data['payload']); }
		if (!$fields) return true;
		$args[] = $boardId; $args[] = $id;
		$sql = "UPDATE mood_board_items SET ".implode(',', $fields)." WHERE board_id=? AND id=?";
		return self::db()->prepare($sql)->execute($args);
	  }
	
	public static function deleteHardOrSoft($slug, int $id, bool $hardDelete=false) {
		$boardId = self::boardId($slug);
		if ($hardDelete) {
		  $sql = "DELETE FROM mood_board_items WHERE board_id=? AND id=?";
		  return self::db()->prepare($sql)->execute([$boardId, $id]);
		}
		// soft: hidden=1
		$sql = "UPDATE mood_board_items SET hidden=1 WHERE board_id=? AND id=?";
		return self::db()->prepare($sql)->execute([$boardId, $id]);
	  }

    public static function deleteItem(PDO $db, int $itemId): void {
        $db->prepare("DELETE FROM canvas_items WHERE id=?")->execute([$itemId]);
    }

    public static function bulkUpdate($slug, array $body) {
		$ops = $body['ops'] ?? [];
		$boardId = self::boardId($slug);
		$db = self::db(); $db->beginTransaction();
		try {
		  foreach ($ops as $op) {
			$type = $op['op'] ?? '';
			$id   = isset($op['id']) ? (int)$op['id'] : 0;
			if ($type === 'delete' && $id) {
			  // soft delete so the frontend won’t see it on next listItems()
			  $db->prepare("UPDATE mood_board_items SET hidden=1 WHERE board_id=? AND id=?")->execute([$boardId,$id]);
			} elseif ($type === 'update' && $id) {
			  // allow position/size updates
			  $x = (int)($op['x'] ?? 0); $y = (int)($op['y'] ?? 0);
			  $w = (int)($op['w'] ?? 0); $h = (int)($op['h'] ?? 0);
			  $db->prepare("UPDATE mood_board_items SET x=?,y=?,w=?,h=? WHERE board_id=? AND id=?")->execute([$x,$y,$w,$h,$boardId,$id]);
			}
		  }
		  $db->commit();
		  return true;
		} catch(\Throwable $e) { $db->rollBack(); return false; }
	  }
	
	 // helpers
  private static function boardId($slug) {
    $stm = self::db()->prepare("SELECT id FROM mood_boards WHERE slug=?");
    $stm->execute([$slug]);
    $id = (int)$stm->fetchColumn();
    if (!$id) throw new RuntimeException('Board not found');
    return $id;
  }

  private static function getItem($boardId, $id) {
    $stm = self::db()->prepare("SELECT * FROM mood_board_items WHERE board_id=? AND id=?");
    $stm->execute([$boardId, $id]);
    return $stm->fetch(PDO::FETCH_ASSOC);
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
