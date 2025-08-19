<?php
class document_model {
    /** Insert a new document record */
    public static function create(PDO $db, int $visionId, string $uuid, string $fileName,
                                  string $mimeType, int $fileSize, string $status,
                                  int $version, string $encKey): void {
        $st = $db->prepare("INSERT INTO vision_documents 
            (vision_id, uuid, file_name, mime_type, file_size, status, version, enc_key) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $st->execute([$visionId, $uuid, $fileName, $mimeType, $fileSize, $status,
                      $version, $encKey]);
    }

	public static function updateStatus(PDO $db, string $uuid, string $status): bool {
		$st = $db->prepare("UPDATE vision_documents SET status=?, updated_at=NOW() WHERE uuid=? LIMIT 1");
		return $st->execute([$status, $uuid]);
	}


    /** Fetch all documents for a vision */
    public static function allForVision(PDO $db, int $visionId): array
	{
		$st = $db->prepare("
			SELECT v.*,
				   g.id   AS group_id,
				   g.name AS group_name
			FROM vision_documents v
			LEFT JOIN vision_doc_groups g ON g.id = v.group_id
			WHERE v.vision_id = ?
			ORDER BY 
			  (v.group_id IS NULL) ASC,       -- grouped first
			  g.sort_order ASC, g.name ASC,
			  v.file_name ASC,
			  v.created_at DESC
		");
		$st->execute([$visionId]);
		return $st->fetchAll(PDO::FETCH_ASSOC);
	}


	public static function updateGroup(PDO $db, string $uuid, ?int $groupId): bool {
		$st = $db->prepare("UPDATE vision_documents SET group_id=?, updated_at=NOW() WHERE uuid=? LIMIT 1");
		return $st->execute([$groupId, $uuid]);
	}

    /** Get the next version number for a file name within a vision */
    public static function nextVersion(PDO $db, int $visionId, string $fileName): int {
        $st = $db->prepare("SELECT MAX(version) FROM vision_documents
                             WHERE vision_id=? AND file_name=?");
        $st->execute([$visionId, $fileName]);
        $max = (int)$st->fetchColumn();
        return $max + 1;
    }

    /** Find a document by UUID */
    public static function findByUuid(PDO $db, string $uuid): ?array {
        $st = $db->prepare("SELECT * FROM vision_documents WHERE uuid=? LIMIT 1");
        $st->execute([$uuid]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
