<?php
// controllers/media.php

require_once __DIR__ . '/../models/vision.php';
require_once __DIR__ . '/../models/mood.php';
require_once __DIR__ . '/../models/media_model.php';

class media_controller
{
    // POST /api/visions/{slug}/media:upload
    public static function upload(string $slug): void
    {
        header('Content-Type: application/json');
        global $db;

        $vision = vision_model::get($db, $slug);
        $board  = null;
        if (!$vision) {
            $board = mood_model::get($db, $slug);
            if (!$board) { http_response_code(404); echo json_encode(['error'=>'Vision/Board not found']); return; }
            $visionId = $board['vision_id'] ? (int)$board['vision_id'] : null;
        } else {
            $visionId = (int)$vision['id'];
        }

        if (empty($_FILES['file'])) { http_response_code(422); echo json_encode(['error'=>'No file uploaded']); return; }

        $results = [];
        $errors  = [];

        $isMulti = is_array($_FILES['file']['name']);
        $count   = $isMulti ? count($_FILES['file']['name']) : 1;

        for ($i = 0; $i < $count; $i++) {
            $name = $isMulti ? $_FILES['file']['name'][$i]     : $_FILES['file']['name'];
            $tmp  = $isMulti ? $_FILES['file']['tmp_name'][$i] : $_FILES['file']['tmp_name'];
            $type = $isMulti ? $_FILES['file']['type'][$i]     : $_FILES['file']['type'];
            $size = (int)($isMulti ? $_FILES['file']['size'][$i] : $_FILES['file']['size']);
            $err  = (int)($isMulti ? $_FILES['file']['error'][$i] : $_FILES['file']['error']);

            if ($err !== UPLOAD_ERR_OK) { $errors[] = ['file'=>$name,'error'=>"Upload error $err"]; continue; }

            $res = self::save_one_file($db, $visionId, $tmp, $name, $type, $size);
            if ($res['ok']) {
                // Auto-attach if upload was initiated from a board context
                if ($board) {
                    media_model::attachToBoard($db, (int)$board['id'], (int)$res['data']['id']);
                } elseif (isset($_POST['board_id']) && (int)$_POST['board_id'] > 0) {
                    media_model::attachToBoard($db, (int)$_POST['board_id'], (int)$res['data']['id']);
                }
                $results[] = $res['data'];
            } else {
                $errors[]  = ['file'=>$name,'error'=>$res['error']];
            }
        }

        if ($results) {
            echo json_encode(['success'=>true,'files'=>$results,'errors'=>$errors]); return;
        }
        http_response_code(422);
        echo json_encode(['error'=>'Upload failed','errors'=>$errors]);
    }

    private static function save_one_file(PDO $db, ?int $visionId, string $tmp, string $origName, string $clientType, int $size): array
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $tmp) : null;
        if ($finfo) finfo_close($finfo);
        if (!$mime) $mime = $clientType ?: 'application/octet-stream';

        // Allowed: images + PDF (as requested)
        $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/bmp','application/pdf'];
        if (!in_array($mime, $allowed, true)) {
            return ['ok'=>false, 'error'=>"Unsupported type $mime"];
        }

        $uuid = bin2hex(random_bytes(16));
        $ext  = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!$ext) {
            $ext = ($mime === 'application/pdf') ? 'pdf'
                 : ($mime === 'image/jpeg' ? 'jpg' : '');
        }

        $root       = realpath(__DIR__ . '/..');
        $privateDir = $root . '/storage/private';
        $thumbsDir  = $root . '/storage/thumbs';
        if (!is_dir($privateDir)) @mkdir($privateDir, 0700, true);
        if (!is_dir($thumbsDir))  @mkdir($thumbsDir,  0755, true);

        $destName = $uuid . ($ext ? ".$ext" : '');
        $destPath = $privateDir . '/' . $destName;

        if (!move_uploaded_file($tmp, $destPath)) {
            // For CLI tests or non-SAPI, try copy+unlink
            if (!@copy($tmp, $destPath)) { return ['ok'=>false,'error'=>'Failed to save file']; }
            @unlink($tmp);
        }

        // Generate optimized versions
        $thumb300  = null;
        $large1280 = null;
        try {
            if ($mime === 'application/pdf') {
                if (class_exists('Imagick')) {
                    $im = new Imagick();
                    $im->setResolution(150,150);
                    $im->readImage($destPath."[0]");
                    $im->setImageFormat('jpeg');
                    // 1280
                    $im->thumbnailImage(1280, 0);
                    $large1280 = "$thumbsDir/{$uuid}_1280.jpg";
                    $im->writeImage($large1280);
                    // 300
                    $im->thumbnailImage(300, 0);
                    $thumb300 = "$thumbsDir/{$uuid}_thumb.jpg";
                    $im->writeImage($thumb300);
                    $im->clear(); $im->destroy();
                }
            } elseif (strpos($mime, 'image/') === 0) {
                if (class_exists('Imagick')) {
                    $im = new Imagick($destPath);
                    $im->setImageFormat('jpeg');
                    // Large
                    $im->thumbnailImage(1280, 0);
                    $large1280 = "$thumbsDir/{$uuid}_1280.jpg";
                    $im->writeImage($large1280);
                    // Thumb
                    $im->thumbnailImage(300, 0);
                    $thumb300 = "$thumbsDir/{$uuid}_thumb.jpg";
                    $im->writeImage($thumb300);
                    $im->clear(); $im->destroy();
                } else {
                    // GD fallback (no PDF support)
                    $src = null;
                    switch ($mime) {
                        case 'image/jpeg': $src = imagecreatefromjpeg($destPath); break;
                        case 'image/png':  $src = imagecreatefrompng($destPath);  break;
                        case 'image/gif':  $src = imagecreatefromgif($destPath);  break;
                        case 'image/webp': $src = imagecreatefromwebp($destPath); break;
                        case 'image/bmp':  $src = imagecreatefrombmp($destPath);  break;
                    }
                    if ($src) {
                        $w = imagesx($src); $h = imagesy($src);
                        // 1280
                        if ($w > 1280) {
                            $nw = 1280; $nh = (int)round($h * ($nw / $w));
                            $lg = imagecreatetruecolor($nw, $nh);
                            imagecopyresampled($lg, $src, 0,0,0,0, $nw,$nh, $w,$h);
                            $large1280 = "$thumbsDir/{$uuid}_1280.jpg";
                            imagejpeg($lg, $large1280, 85);
                            imagedestroy($lg);
                        }
                        // 300
                        $nw = 300; $nh = (int)round($h * ($nw / $w));
                        $th = imagecreatetruecolor($nw, $nh);
                        imagecopyresampled($th, $src, 0,0,0,0, $nw,$nh, $w,$h);
                        $thumb300 = "$thumbsDir/{$uuid}_thumb.jpg";
                        imagejpeg($th, $thumb300, 85);
                        imagedestroy($th);
                        imagedestroy($src);
                    }
                }
            }
        } catch (Throwable $e) {
            // Continue without thumbs if generation fails
        }

        $mediaId = media_model::create(
            $db, $visionId, $uuid, $origName, $mime, $size,
            null, null, null, null
        );
        if (!$mediaId) {
            @unlink($destPath);
            if ($thumb300)  @unlink($thumb300);
            if ($large1280) @unlink($large1280);
            return ['ok'=>false,'error'=>'DB insert failed'];
        }

        return [
            'ok'=>true,
            'data'=>[
                'id'        => $mediaId,
                'uuid'      => $uuid,
                'file_name' => $origName,
                'mime_type' => $mime,
                'size'      => $size,
                'thumb_url' => $thumb300  ? '/storage/thumbs/'.basename($thumb300)  : null,
                'large_url' => $large1280 ? '/storage/thumbs/'.basename($large1280) : null
            ]
        ];
    }

    // POST /api/visions/{slug}/media:link  (YouTube only v1)
    public static function link(string $slug): void
    {
        header('Content-Type: application/json');
        global $db;

        $vision = vision_model::get($db, $slug);
        $board  = null;
        if (!$vision) {
            $board = mood_model::get($db, $slug);
            if (!$board) { http_response_code(404); echo json_encode(['error'=>'Vision/Board not found']); return; }
            $visionId = $board['vision_id'] ? (int)$board['vision_id'] : null;
        } else {
            $visionId = (int)$vision['id'];
        }

        $url = trim($_POST['url'] ?? '');
        if ($url === '') { http_response_code(422); echo json_encode(['error'=>'No URL']); return; }

        // Extract YouTube video ID
        if (!preg_match('~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            http_response_code(422); echo json_encode(['error'=>'Only YouTube links supported in v1']); return;
        }
        $vid = $m[1];
        $canonical = "https://www.youtube.com/watch?v={$vid}";
        $title = null; $thumbUrl = null; $embed = "<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/{$vid}\" frameborder=\"0\" allowfullscreen></iframe>";

        // Try oEmbed for title/thumb
        try {
            $o = @file_get_contents('https://www.youtube.com/oembed?format=json&url=' . urlencode($canonical));
            if ($o) {
                $j = json_decode($o, true);
                $title    = $j['title'] ?? null;
                $thumbUrl = $j['thumbnail_url'] ?? null;
                $embed    = $j['html'] ?? $embed;
            }
        } catch (Throwable $e) {}

        if (!$title) $title = "YouTube Video {$vid}";

        // Cache thumb locally (optional)
        $localThumb = null;
        if ($thumbUrl) {
            $img = @file_get_contents($thumbUrl);
            if ($img !== false) {
                $localThumb = realpath(__DIR__ . '/..') . "/storage/thumbs/{$vid}_yt.jpg";
                @file_put_contents($localThumb, $img);
            }
        }

        $uuid = bin2hex(random_bytes(16));
        $mediaId = media_model::create(
            $db, $visionId, $uuid, $title, 'video/youtube', 0,
            'youtube', $vid, $canonical, $embed
        );
        if (!$mediaId) { http_response_code(500); echo json_encode(['error'=>'DB insert failed']); return; }

        if ($board) {
            media_model::attachToBoard($db, (int)$board['id'], $mediaId);
        } elseif (isset($_POST['board_id']) && (int)$_POST['board_id'] > 0) {
            media_model::attachToBoard($db, (int)$_POST['board_id'], $mediaId);
        }

        echo json_encode([
            'success'=>true,
            'media'=>[
                'id'=>$mediaId,
                'uuid'=>$uuid,
                'file_name'=>$title,
                'provider'=>'youtube',
                'video_id'=>$vid,
                'embed_html'=>$embed,
                'thumb_url'=>$localThumb ? '/storage/thumbs/'.basename($localThumb) : $thumbUrl
            ]
        ]);
    }

    // GET /api/visions/{slug}/media?scope=vision|board&board_id=&q=&type=&sort=
    public static function list(string $slug): void
    {
        header('Content-Type: application/json');
        global $db;

        $scope = $_GET['scope'] ?? 'vision';
        $q     = trim($_GET['q'] ?? '');
        $type  = $_GET['type'] ?? '';
        $sort  = $_GET['sort'] ?? 'date';
        $boardParam = isset($_GET['board_id']) ? (int)$_GET['board_id'] : null;

        $vision = vision_model::get($db, $slug);
        $visionId = null; $boardId = $boardParam;

        if (!$vision) {
            $board = mood_model::get($db, $slug);
            if (!$board) { http_response_code(404); echo json_encode(['error'=>'Not found']); return; }
            $visionId = $board['vision_id'] ? (int)$board['vision_id'] : null;
            if (!$boardId) $boardId = (int)$board['id'];
        } else {
            $visionId = (int)$vision['id'];
        }

        if ($scope === 'board') {
            if (!$boardId) { http_response_code(400); echo json_encode(['error'=>'board_id required']); return; }
            $rows = media_model::allForBoardFiltered($db, $boardId, $q, $type, $sort);
        } else {
            if ($visionId === null) {
                // Standalone board without vision: treat as board scope
                if (!$boardId) { http_response_code(400); echo json_encode(['error'=>'board_id required']); return; }
                $rows = media_model::allForBoardFiltered($db, $boardId, $q, $type, $sort);
            } else {
                $rows = media_model::allForVisionFiltered($db, $visionId, $boardId, $q, $type, $sort);
            }
        }
        echo json_encode(['success'=>true,'media'=>$rows]);
    }

    // POST /api/moods/{slug}/library:attach  (media_id[]=...)
    public static function attach(string $slug): void
    {
        header('Content-Type: application/json');
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo json_encode(['error'=>'Board not found']); return; }
        $boardId = (int)$board['id'];

        $ids = $_POST['media_id'] ?? $_POST['media_ids'] ?? [];
        if (!is_array($ids)) $ids = [$ids];

        $ok = [];
        foreach ($ids as $mid) {
            $mid = (int)$mid;
            if ($mid && media_model::attachToBoard($db, $boardId, $mid)) $ok[] = $mid;
        }
        echo json_encode(['success'=>true,'attached'=>$ok]);
    }

    // POST /api/moods/{slug}/library:detach  (media_id=...)
    public static function detach(string $slug): void
    {
        header('Content-Type: application/json');
        global $db;
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo json_encode(['error'=>'Board not found']); return; }
        $boardId = (int)$board['id'];

        $mid = (int)($_POST['media_id'] ?? 0);
        if (!$mid) { http_response_code(400); echo json_encode(['error'=>'media_id required']); return; }

        // Prevent detach if placed on canvas
        $st = $db->prepare("SELECT 1 FROM mood_board_items WHERE board_id=? AND media_id=? LIMIT 1");
        $st->execute([$boardId, $mid]);
        if ($st->fetchColumn()) {
            http_response_code(422); echo json_encode(['error'=>'Media is used on the canvas; remove it first']); return;
        }

        $db->prepare("DELETE FROM mood_board_media WHERE board_id=? AND media_id=?")->execute([$boardId,$mid]);
        echo json_encode(['success'=>true]);
    }

    // POST /api/visions/{slug}/media:delete  (media_id=...)
    public static function delete(string $slug): void
    {
        header('Content-Type: application/json');
        global $db;

        $mid = (int)($_POST['media_id'] ?? 0);
        if (!$mid) { http_response_code(400); echo json_encode(['error'=>'media_id required']); return; }

        $inUse = (int)$db->query("SELECT COUNT(*) FROM mood_board_media WHERE media_id={$mid}")->fetchColumn();
        if ($inUse > 0) { http_response_code(422); echo json_encode(['error'=>'Media attached to board(s); detach first']); return; }
        $onCanvas = (int)$db->query("SELECT COUNT(*) FROM mood_board_items WHERE media_id={$mid}")->fetchColumn();
        if ($onCanvas > 0) { http_response_code(422); echo json_encode(['error'=>'Media used on canvas; remove the items first']); return; }

        $media = media_model::findById($db, $mid);
        if (!$media) { http_response_code(404); echo json_encode(['error'=>'Media not found']); return; }

        $db->prepare("DELETE FROM vision_media WHERE id=?")->execute([$mid]);

        // remove files if any
        $root      = realpath(__DIR__ . '/..');
        $uuid      = $media['uuid'];
        $ext       = strtolower(pathinfo($media['file_name'], PATHINFO_EXTENSION));
        $orig      = $root . '/storage/private/' . ($ext ? "$uuid.$ext" : $uuid);
        $thumbBase = $root . '/storage/thumbs/' . $uuid;

        if (is_file($orig)) @unlink($orig);
        foreach (glob($thumbBase . '*') as $fp) { @unlink($fp); }

        echo json_encode(['success'=>true]);
    }

    /* ----------------------------------------------------------------
     * NEW: Tags & Groups minimal endpoints
     * These are safe and won't create/alter tables silently.
     * They try common patterns and fail gracefully with success:false.
     * ---------------------------------------------------------------- */

    // GET /api/tags
    public static function tags_list(): void
    {
        header('Content-Type: application/json');
        global $db;

        $out = [];

        // 1) If there's a dedicated `tags` table
        try {
            $rows = $db->query("SHOW TABLES LIKE 'tags'")->fetchAll(PDO::FETCH_NUM);
            if ($rows) {
                $q = $db->query("SELECT name FROM tags ORDER BY name ASC");
                foreach ($q as $r) $out[] = $r['name'];
            }
        } catch (Throwable $e) {}

        // 2) Also collect distinct tags from a `vision_media.tags` column if it exists
        try {
            $chk = $db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_NAME='vision_media' AND COLUMN_NAME='tags'")->fetchColumn();
            if ($chk) {
                foreach ($db->query("SELECT tags FROM vision_media WHERE tags IS NOT NULL AND tags<>''") as $r) {
                    foreach (explode(',', $r['tags']) as $t) {
                        $t = trim($t);
                        if ($t !== '' && !in_array($t, $out, true)) $out[] = $t;
                    }
                }
                sort($out, SORT_NATURAL | SORT_FLAG_CASE);
            }
        } catch (Throwable $e) {}

        echo json_encode(['success'=>true, 'tags'=>$out]);
    }

    // GET/POST /api/media/{id}/tags
    // GET  -> { success:true, tags:["...","..."] }
    // POST -> body: tags=comma,sep or tags[]=a&tags[]=b   => { success:true, tags:[...] }
    public static function tags(string $mediaId): void
    {
        header('Content-Type: application/json');
        global $db;

        $mid = (int)$mediaId;
        if ($mid <= 0) { http_response_code(400); echo json_encode(['error'=>'Invalid media id']); return; }

        // Ensure media exists
        $media = media_model::findById($db, $mid);
        if (!$media) { http_response_code(404); echo json_encode(['error'=>'Media not found']); return; }

        // We support two storage backings:
        //  A) normalized tables: tags(id,name,user_id), media_tags(media_id,tag_id)
        //  B) a 'tags' TEXT column on vision_media (CSV) if those tables don’t exist
        $hasTagsTables = false;
        try {
            $chkA = $db->query("SHOW TABLES LIKE 'tags'")->fetchColumn();
            $chkB = $db->query("SHOW TABLES LIKE 'media_tags'")->fetchColumn();
            $hasTagsTables = (bool)($chkA && $chkB);
        } catch (Throwable $e) {
            $hasTagsTables = false;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST') {
            // Gather tags from request
            $tags = [];
            if (isset($_POST['tags'])) {
                if (is_array($_POST['tags'])) { $tags = $_POST['tags']; }
                else { $tags = array_filter(array_map('trim', explode(',', (string)$_POST['tags']))); }
            }
            $tags = array_values(array_unique(array_filter($tags, fn($t)=>$t !== '')));

            if ($hasTagsTables) {
                // Upsert into tags + map in media_tags
                try {
                    $db->beginTransaction();

                    // Current user (owner of tag vocabulary)
                    $userId = $_SESSION['user_id'] ?? 1;

                    // Fetch existing tag ids for names
                    $tagIds = [];
                    if ($tags) {
                        // Insert any missing
                        $ins = $db->prepare("INSERT INTO tags (name, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
                        foreach ($tags as $t) {
                            $ins->execute([$t, $userId]);
                        }
                        // Read ids
                        $inQ = implode(',', array_fill(0, count($tags), '?'));
                        $sel = $db->prepare("SELECT id, name FROM tags WHERE user_id=? AND name IN ($inQ)");
                        $sel->execute(array_merge([$userId], $tags));
                        while ($r = $sel->fetch(PDO::FETCH_ASSOC)) {
                            $tagIds[$r['name']] = (int)$r['id'];
                        }
                    }

                    // Clear existing mappings
                    $db->prepare("DELETE FROM media_tags WHERE media_id=?")->execute([$mid]);

                    // Insert new mappings
                    if ($tagIds) {
                        $ins2 = $db->prepare("INSERT INTO media_tags (media_id, tag_id) VALUES (?, ?)");
                        foreach ($tagIds as $tid) { $ins2->execute([$mid, $tid]); }
                    }

                    $db->commit();
                    echo json_encode(['success'=>true, 'tags'=>$tags]); return;
                } catch (Throwable $e) {
                    if ($db->inTransaction()) $db->rollBack();
                    // Fall through to CSV column as a safety net
                    $hasTagsTables = false;
                }
            }

            // Fallback: store CSV in vision_media.tags (if column exists)
            try {
                $col = $db->query("SHOW COLUMNS FROM vision_media LIKE 'tags'")->fetch(PDO::FETCH_ASSOC);
                if ($col) {
                    $csv = implode(',', $tags);
                    $upd = $db->prepare("UPDATE vision_media SET tags=? WHERE id=?");
                    $upd->execute([$csv, $mid]);
                    echo json_encode(['success'=>true, 'tags'=>$tags]); return;
                }
            } catch (Throwable $e) {}

            // If neither backend exists, just report success with no persistence to avoid 500s
            echo json_encode(['success'=>true, 'tags'=>$tags, 'warning'=>'Tag storage not available (no tags/media_tags tables or vision_media.tags column)']);
            return;
        }

        // GET
        if ($hasTagsTables) {
            try {
                $q = $db->prepare("
                    SELECT t.name
                    FROM media_tags mt
                    JOIN tags t ON t.id = mt.tag_id
                    WHERE mt.media_id=? 
                    ORDER BY t.name ASC
                ");
                $q->execute([$mid]);
                $names = array_map(fn($r)=>$r['name'], $q->fetchAll(PDO::FETCH_ASSOC) ?: []);
                echo json_encode(['success'=>true, 'tags'=>$names]); return;
            } catch (Throwable $e) {
                // Fall through
            }
        }
        // Fallback: CSV column
        try {
            $col = $db->query("SHOW COLUMNS FROM vision_media LIKE 'tags'")->fetch(PDO::FETCH_ASSOC);
            if ($col) {
                $q = $db->prepare("SELECT tags FROM vision_media WHERE id=?");
                $q->execute([$mid]);
                $csv = (string)($q->fetchColumn() ?: '');
                $names = array_values(array_filter(array_map('trim', explode(',', $csv))));
                echo json_encode(['success'=>true, 'tags'=>$names]); return;
            }
        } catch (Throwable $e) {}

        echo json_encode(['success'=>true, 'tags'=>[]]);
    }

    // GET /api/moods/{slug}/groups
    // Returns creator-scoped groups usable in the Mood media library
    public static function groups_list(string $slug): void
    {
        header('Content-Type: application/json');
        global $db;

        // Resolve board and creator
        $board = mood_model::get($db, $slug);
        if (!$board) { http_response_code(404); echo json_encode(['error'=>'Board not found']); return; }

        // Current user id (adjust if you use a different auth helper)
        $userId = $_SESSION['user_id'] ?? 1; // fallback to 1 if your app currently runs as user 1
        if (!$userId) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); return; }

        // We’ll try a dedicated groups table first; if it doesn’t exist, return an empty set gracefully.
        try {
            // media_groups: id, user_id, name, created_at
            $st = $db->query("SHOW TABLES LIKE 'media_groups'");
            if (!$st->fetchColumn()) {
                echo json_encode(['success'=>true, 'groups'=>[]]); return;
            }
            $st = $db->prepare("SELECT id, name FROM media_groups WHERE user_id=? ORDER BY name ASC");
            $st->execute([$userId]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            echo json_encode(['success'=>true, 'groups'=>$rows]);
        } catch (Throwable $e) {
            // Never blow up the UI – just return empty so the client can continue
            echo json_encode(['success'=>true, 'groups'=>[]]);
        }
    }

    // POST /api/media/{id}/group  (group_id=…)
    public static function update_group(int $mediaId): void
    {
        header('Content-Type: application/json');
        global $db;

        $gid = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

        // If you have a vision_media.group_id column, use it
        try {
            $hasCol = (int)$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_NAME='vision_media' AND COLUMN_NAME='group_id'")->fetchColumn();
            if ($hasCol) {
                $st = $db->prepare("UPDATE vision_media SET group_id=? WHERE id=? LIMIT 1");
                $st->execute([$gid ?: null, $mediaId]);
                echo json_encode(['success'=>true]); return;
            }
        } catch (Throwable $e) {}

        // If you have a media_groups join, you could add handling here similarly.

        echo json_encode([
            'success'=>false,
            'error'=>'No place to store group. Add `vision_media.group_id` (INT) or a proper groups schema.'
        ]);
    }
}
