<?php
class document_group_model {
    public static function listForVision(PDO $db, int $visionId): array {
        $st = $db->prepare("SELECT id, name, sort_order FROM vision_doc_groups WHERE vision_id=? ORDER BY sort_order ASC, name ASC");
        $st->execute([$visionId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(PDO $db, int $visionId, string $name): int {
        $st = $db->prepare("INSERT INTO vision_doc_groups (vision_id, name) VALUES (?, ?)");
        $st->execute([$visionId, $name]);
        return (int)$db->lastInsertId();
    }
}
