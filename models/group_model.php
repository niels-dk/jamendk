<?php
// models/group_model.php
class group_model {
    public static function allForVision(PDO $db, string $visionSlug, int $userId): array {
        $st = $db->prepare("SELECT g.id,g.name
                              FROM groups g
                              JOIN visions v ON g.vision_id=v.id
                             WHERE v.slug=? AND v.user_id=? ORDER BY g.name ASC");
        $st->execute([$visionSlug,$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(PDO $db, string $visionSlug, int $userId, string $name): ?int {
        $db->beginTransaction();
        $visionId = $db->prepare("SELECT id FROM visions WHERE slug=? AND user_id=?");
        $visionId->execute([$visionSlug,$userId]);
        $vid = $visionId->fetchColumn();
        if (!$vid) { $db->rollBack(); return null; }
        $st = $db->prepare("INSERT INTO groups(vision_id,user_id,name,created_at)
                            VALUES(?,?,?,NOW())");
        $ok = $st->execute([$vid,$userId,$name]);
        $id = $ok ? (int)$db->lastInsertId() : null;
        $db->commit();
        return $id;
    }
}
