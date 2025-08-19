<?php
// controllers/document.php

// Make sure model classes are loaded (DreamHost is case-sensitive)
require_once __DIR__ . '/../models/vision.php';          // defines class vision_model
require_once __DIR__ . '/../models/document_model.php';  // your document model

class document_controller {

    /** GET /visions/{slug}/overlay/documents */
    public static function overlay(string $slug): void {
        global $db;

        // Fallback if class name ever changes
        if (!class_exists('vision_model')) {
            http_response_code(500);
            echo 'Internal error: vision_model not loaded';
            return;
        }

        $vision = vision_model::get($db, $slug);
        if (!$vision) { http_response_code(404); echo 'Vision not found'; return; }

        // Load docs
        $docs = document_model::allForVision($db, (int)$vision['id']);

        // Render the standard overlay partial
        $partial = __DIR__ . '/../views/partials/overlay_documents.php';
        if (!file_exists($partial)) { http_response_code(500); echo 'Overlay partial missing'; return; }
        include $partial;
    }

    /** POST /api/visions/{slug}/documents – upload (supports multiple files) */
	public static function upload(string $slug): void {
		header('Content-Type: application/json');
		global $db;

		if (!class_exists('vision_model')) {
			http_response_code(500);
			echo json_encode(['error' => 'vision_model not loaded']);
			return;
		}

		$vision = vision_model::get($db, $slug);
		if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }

		if (empty($_FILES['file'])) {
			http_response_code(422);
			echo json_encode(['error' => 'No files received']);
			return;
		}

		$isMulti = is_array($_FILES['file']['name']);
		$results = [];
		$errors  = [];

		if ($isMulti) {
			$count = count($_FILES['file']['name']);
			for ($i = 0; $i < $count; $i++) {
				$name  = $_FILES['file']['name'][$i] ?? '';
				$tmp   = $_FILES['file']['tmp_name'][$i] ?? '';
				$type  = $_FILES['file']['type'][$i] ?? '';
				$size  = (int)($_FILES['file']['size'][$i] ?? 0);
				$err   = (int)($_FILES['file']['error'][$i] ?? UPLOAD_ERR_NO_FILE);

				if ($err !== UPLOAD_ERR_OK) {
					$errors[] = ['file'=>$name, 'error'=>'Upload error code '.$err];
					continue;
				}

				$res = self::saveOne($db, (int)$vision['id'], $tmp, $name, $type, $size);
				if ($res['ok']) $results[] = $res['data']; else $errors[] = ['file'=>$name, 'error'=>$res['error']];
			}
		} else {
			$name = $_FILES['file']['name'] ?? '';
			$tmp  = $_FILES['file']['tmp_name'] ?? '';
			$type = $_FILES['file']['type'] ?? '';
			$size = (int)($_FILES['file']['size'] ?? 0);
			$err  = (int)($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);

			if ($err !== UPLOAD_ERR_OK) {
				http_response_code(422);
				echo json_encode(['error' => 'Upload error code '.$err]);
				return;
			}

			$res = self::saveOne($db, (int)$vision['id'], $tmp, $name, $type, $size);
			if ($res['ok']) $results[] = $res['data']; else $errors[] = ['file'=>$name, 'error'=>$res['error']];
		}

		if ($results) {
			echo json_encode([
				'success' => true,
				'files'   => $results,
				'errors'  => $errors
			]);
		} else {
			http_response_code(422);
			echo json_encode(['error' => 'All uploads failed', 'errors' => $errors]);
		}
	}

	
	/** Save a single file: validate, encrypt, write, DB insert */
	private static function saveOne(PDO $db, int $visionId, string $tmp, string $origName, string $clientType, int $size): array {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime  = $finfo ? finfo_file($finfo, $tmp) : null;
		if ($finfo) finfo_close($finfo);
		if (!$mime) $mime = $clientType ?: 'application/octet-stream';

		$allowed = [
			'application/pdf',
			'application/msword',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'image/jpeg','image/png'
		];
		if (!in_array($mime, $allowed, true)) {
			return ['ok'=>false, 'error'=>'Unsupported file type ('.$mime.')'];
		}

		$uuid = bin2hex(random_bytes(16));
		$key  = openssl_random_pseudo_bytes(32);
		$iv   = openssl_random_pseudo_bytes(16);

		$content = file_get_contents($tmp);
		if ($content === false) return ['ok'=>false, 'error'=>'Read failed'];

		$encrypted = openssl_encrypt($content, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
		if ($encrypted === false) return ['ok'=>false, 'error'=>'Encrypt failed'];

		$storageDir = __DIR__ . '/../storage/private';
		if (!is_dir($storageDir) && !mkdir($storageDir, 0700, true)) {
			return ['ok'=>false, 'error'=>'Storage path not writable'];
		}

		$payload  = $iv . $encrypted;
		$filepath = $storageDir . '/' . $uuid;
		if (file_put_contents($filepath, $payload) === false) {
			return ['ok'=>false, 'error'=>'Write failed'];
		}

		$version   = document_model::nextVersion($db, $visionId, basename($origName));
		$encKeyB64 = base64_encode($key);

		document_model::create(
			$db,
			$visionId,
			$uuid,
			basename($origName),
			$mime,
			$size,
			'draft',
			$version,
			$encKeyB64
		);

		return [
			'ok'   => true,
			'data' => [
				'uuid'         => $uuid,
				'file_name'    => basename($origName),
				'version'      => $version,
				'size'         => $size,
				'status'       => 'draft',
				'created_at'   => date('Y-m-d H:i:s'), // <— add this
				'download_url' => '/documents/'.$uuid.'/download'
			]
		];

	}
	
	// GET /api/visions/{slug}/groups  -> list groups for the vision
public static function groups_list(string $slug): void {
    header('Content-Type: application/json'); global $db;
    require_once __DIR__ . '/../models/vision.php';
    require_once __DIR__ . '/../models/document_group_model.php';

    $vision = vision_model::get($db, $slug);
    if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }

    $groups = document_group_model::listForVision($db, (int)$vision['id']);
    echo json_encode(['success'=>true, 'groups'=>$groups]);
}

// POST /api/visions/{slug}/groups  body: name=Travel
public static function groups_create(string $slug): void {
    header('Content-Type: application/json'); global $db;
    require_once __DIR__ . '/../models/vision.php';
    require_once __DIR__ . '/../models/document_group_model.php';

    $name = trim($_POST['name'] ?? '');
    if ($name === '') { http_response_code(422); echo json_encode(['error'=>'Name required']); return; }

    $vision = vision_model::get($db, $slug);
    if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }

    try {
        $id = document_group_model::create($db, (int)$vision['id'], $name);
        echo json_encode(['success'=>true, 'group'=>['id'=>$id,'name'=>$name]]);
    } catch (Throwable $e) {
        // likely unique constraint
        http_response_code(422);
        echo json_encode(['error'=>'Group name already exists']);
    }
}

	// POST /api/documents/{uuid}/group  body: group_id=123 (or empty to clear)
	public static function update_group(string $uuid): void {
		header('Content-Type: application/json'); global $db;

		$raw = trim($_POST['group_id'] ?? '');
		$groupId = ($raw === '') ? null : (int)$raw;

		// Optional: validate group belongs to the same vision as the document
		$doc = document_model::findByUuid($db, $uuid);
		if (!$doc) { http_response_code(404); echo json_encode(['error'=>'Document not found']); return; }

		if (!is_null($groupId)) {
			$st = $db->prepare("SELECT g.id FROM vision_doc_groups g WHERE g.id=? AND g.vision_id=?");
			$st->execute([$groupId, (int)$doc['vision_id']]);
			if (!$st->fetch()) { http_response_code(422); echo json_encode(['error'=>'Invalid group for this vision']); return; }
		}

		if (!document_model::updateGroup($db, $uuid, $groupId)) {
			http_response_code(500); echo json_encode(['error'=>'Update failed']); return;
		}
		echo json_encode(['success'=>true, 'group_id'=>$groupId]);
	}


	/** POST /api/documents/{uuid}/status  body: status=draft|waiting_brand|final|signed */
	public static function update_status(string $uuid): void {
		header('Content-Type: application/json');
		global $db;

		$allowed = ['draft','waiting_brand','final','signed'];
		$status  = strtolower(trim($_POST['status'] ?? ''));
		if (!in_array($status, $allowed, true)) {
			http_response_code(422);
			echo json_encode(['error'=>'Invalid status']);
			return;
		}

		// Optional: permission check based on vision_id of this doc
		$doc = document_model::findByUuid($db, $uuid);
		if (!$doc) { http_response_code(404); echo json_encode(['error'=>'Not found']); return; }
		// TODO: auth check: user can edit this vision

		if (!document_model::updateStatus($db, $uuid, $status)) {
			http_response_code(500);
			echo json_encode(['error'=>'Update failed']);
			return;
		}

		echo json_encode(['success'=>true, 'status'=>$status, 'updated_at'=>date('Y-m-d H:i:s')]);
	}


    /** GET /documents/{uuid}/download */
    public static function download(string $uuid): void {
        global $db;

        $doc = document_model::findByUuid($db, $uuid);
        if (!$doc) { http_response_code(404); echo 'File not found'; return; }

        // TODO: add permission check for the vision_id here

        $storageDir = __DIR__ . '/../storage/private';
        $path = $storageDir . '/' . $uuid;
        if (!file_exists($path)) { http_response_code(404); echo 'File missing'; return; }

        $data = file_get_contents($path);
        if ($data === false || strlen($data) < 17) {
            http_response_code(500);
            echo 'Corrupt file';
            return;
        }

        $iv     = substr($data, 0, 16);
        $cipher = substr($data, 16);
        $key    = base64_decode($doc['enc_key'], true);
        if ($key === false) { http_response_code(500); echo 'Invalid key'; return; }

        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) { http_response_code(500); echo 'Decrypt failed'; return; }

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . basename($doc['file_name']) . '"');
        header('Content-Length: ' . strlen($plain));
        echo $plain;
    }
}
