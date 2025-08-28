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
}
