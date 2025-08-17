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

    /** Fetch all documents for a vision */
    public static function allForVision(PDO $db, int $visionId): array 
	{
    // name ASC, version DESC, created_at DESC
		$st = $db->prepare("
			SELECT *
			FROM vision_documents
			WHERE vision_id=?
			ORDER BY file_name ASC, version DESC, created_at DESC
		");
		$st->execute([$visionId]);
		return $st->fetchAll(PDO::FETCH_ASSOC);
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
