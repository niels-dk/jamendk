<?php
// controllers/vision.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../models/vision.php';

class vision_controller
{
    /** GET /visions/new – create draft and redirect */
    public static function create(): void
    {
        global $db, $currentUserId;
        $userId = $currentUserId ?: 1;
        $draft  = vision_model::createDraft($db, $userId);
        header("Location: /visions/{$draft['slug']}/edit");
        exit;
    }

    /** POST /visions/store – legacy create (active) */
    public static function store(): void
    {
        global $db, $currentUserId;
        $userId = $currentUserId ?: 1;
        $title = trim($_POST['title'] ?? '');
        $desc  = $_POST['description'] ?? '';
        $id    = vision_model::create($db, $userId, $title ?: null, $desc ?: null);
        // save anchors (legacy)
        $anchors = $_POST['anchors'] ?? [];
        $kv = [];
        foreach ($anchors as $row) {
            $kv[] = ['key' => $row['key'] ?? '', 'value' => $row['value'] ?? ''];
        }
        vision_model::replaceAnchors($db, $id, $kv);
        $slug = (string)$db->query("SELECT slug FROM visions WHERE id=$id")->fetchColumn();
        header("Location: /visions/$slug");
        exit;
    }

    /** GET /visions/{slug} – show vision */
    public static function show(string $slug): void
    {
        global $db;
        $vision = vision_model::get($db, $slug);
        if (!$vision) { http_response_code(404); echo 'Vision not found'; return; }
        // fetch flags
        $st = $db->prepare("SELECT * FROM vision_presentation WHERE vision_id=?");
        $st->execute([(int)$vision['id']]);
        $presentationFlags = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        // flatten anchors
        $kv = [];
        $map = vision_model::getAnchors($db, (int)$vision['id']);
        foreach ($map as $k => $vals) {
            foreach ($vals as $v) $kv[] = ['key'=>$k,'value'=>$v];
        }
        // include view
        ob_start();
        include __DIR__.'/../views/vision_show.php';
        $content = ob_get_clean();
        $boardType = 'vision';
        include __DIR__.'/../views/layout.php';
    }

    /** GET /visions/{slug}/edit – edit form */
    public static function edit(string $slug): void
    {
        global $db;
        $vision = vision_model::get($db, $slug);
        if (!$vision) { http_response_code(404); echo 'Vision not found'; return; }
        $st = $db->prepare("SELECT * FROM vision_presentation WHERE vision_id=?");
        $st->execute([(int)$vision['id']]);
        $presentationFlags = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        // flatten anchors
        $kv = [];
        $map = vision_model::getAnchors($db, (int)$vision['id']);
        foreach ($map as $k => $vals) {
            foreach ($vals as $v) $kv[] = ['key'=>$k,'value'=>$v];
        }
        ob_start();
        include __DIR__.'/../views/vision_form.php';
        $content = ob_get_clean();
        $boardType = 'vision';
        include __DIR__.'/../views/layout.php';
    }

    /** POST /visions/update – legacy update */
    public static function update(): void
    {
        global $db;
        $id = (int)($_POST['vision_id'] ?? 0);
        if (!$id) { http_response_code(400); echo 'Missing ID'; return; }
        $title = trim($_POST['title'] ?? '');
        $desc  = $_POST['description'] ?? '';
        vision_model::update($db, $id, $title, $desc);
        // update anchors
        $anchors = $_POST['anchors'] ?? [];
        $kv=[];
        foreach ($anchors as $row) {
            $kv[] = ['key'=>$row['key'] ?? '', 'value'=>$row['value'] ?? ''];
        }
        vision_model::replaceAnchors($db, $id, $kv);
        $slug = (string)$db->query("SELECT slug FROM visions WHERE id=$id")->fetchColumn();
        header("Location: /visions/$slug");
        exit;
    }

    /** archive/unarchive/delete/restore */
    public static function archive(string $slug): void
    {
        global $db;
        $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::setArchived($db, (int)$v['id'], true);
        header('Location: /dashboard/vision'); exit;
    }
    public static function unarchive(string $slug): void
    {
        global $db;
        $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::setArchived($db, (int)$v['id'], false);
        header('Location: /dashboard/vision/archived'); exit;
    }
    public static function destroy(string $slug): void
    {
        global $db;
        $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::softDelete($db, (int)$v['id']);
        header('Location: /dashboard/vision'); exit;
    }
    public static function restore(string $slug): void
    {
        global $db;
        $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::restore($db, (int)$v['id']);
        header('Location: /dashboard/vision/trash'); exit;
    }

    /** POST /api/visions/update-basics (legacy basics save) */
    public static function updateBasics(): void
    {
        global $db;
        $id = (int)($_POST['vision_id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'Missing Vision ID']); return; }
        $start = $_POST['start_date'] ?? null;
        $end   = $_POST['end_date'] ?? null;
        $stmt  = $db->prepare("UPDATE visions SET start_date=?, end_date=? WHERE id=?");
        $stmt->execute([$start, $end, $id]);
        // flags
        $defaults = ['relations'=>1,'goals'=>1,'budget'=>1,'roles'=>0,'contacts'=>1,'documents'=>1,'workflow'=>1];
        $flags=[];
        foreach ($defaults as $k=>$v) {
            $flags[$k] = isset($_POST["show_$k"]) ? 1 : 0;
        }
        $fields=array_keys($defaults);
        $columns=implode(',', $fields);
        $place=implode(',', array_fill(0, count($fields), '?'));
        $updates=implode(',', array_map(fn($f) => "$f = VALUES($f)", $fields));
        $stmt = $db->prepare("INSERT INTO vision_presentation (vision_id,$columns) VALUES (?, $place) ON DUPLICATE KEY UPDATE $updates");
        $stmt->execute(array_merge([$id], array_values($flags)));
        echo json_encode(['success'=>true]);
    }

    /** POST /api/visions/{slug}/save – save title, desc, anchors */
    public static function ajax_save(string $slug): void
    {
        header('Content-Type: application/json');
        global $db;
        $vision = vision_model::get($db, $slug);
        if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $update=[];
        $result=['title'=>false,'description'=>false,'anchors'=>false,'statusChanged'=>false];

        // Title
        if (array_key_exists('title', $payload)) {
            $t=trim((string)$payload['title']);
            $update['title'] = $t === '' ? null : $t;
            $result['title']=true;
        }
        // Description
        if (array_key_exists('description',$payload)) {
            $desc=trim((string)$payload['description']);
            if ($desc==='') { $update['description']=null; }
            else {
                $allowed='<b><i><ul><ol><li><a><p><h1><h2><h3><br>';
                $update['description'] = strip_tags($desc,$allowed);
            }
            $result['description']=true;
        }
        if ($update) vision_model::partialUpdate($db, (int)$vision['id'], $update);

        // Anchors
        if (isset($payload['anchors']) && is_array($payload['anchors'])) {
            $kv=[];
            foreach ($payload['anchors'] as $row) {
                $key=trim((string)($row['key'] ?? ''));
                $val=trim((string)($row['value'] ?? ''));
                if ($key!=='' && $val!=='') $kv[]=['key'=>$key,'value'=>$val];
            }
            vision_model::replaceAnchors($db, (int)$vision['id'], $kv);
            $result['anchors']=true;
        }
        // Draft -> active flip
        if (($vision['status'] ?? 'draft')==='draft') {
            $newTitle = $update['title'] ?? $vision['title'];
            $newDesc  = $update['description'] ?? $vision['description'];
            if ($newTitle || $newDesc) {
                vision_model::partialUpdate($db, (int)$vision['id'], ['status'=>'active']);
                $result['statusChanged']=true;
            }
        }
        echo json_encode(['ok'=>true,'result'=>$result]);
    }

    /** GET /visions/{slug}/overlay/{section} – return HTML partial */
    public static function overlay(string $slug, string $section): void
    {
        global $db;
        $vision = vision_model::get($db, $slug);
        if (!$vision) { http_response_code(404); echo 'Vision not found'; return; }
        // load presentation flags so basics overlay can pre-check toggles
        $st = $db->prepare("SELECT * FROM vision_presentation WHERE vision_id=?");
        $st->execute([(int)$vision['id']]);
        $presentationFlags = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        // Pass anchor summary if needed (not used in overlays)
        $partial = __DIR__.'/../views/partials/overlay_'.$section.'.php';
        if (!file_exists($partial)) { http_response_code(404); echo 'Overlay not found'; return; }
        // include partial; it will echo HTML
        include $partial;
    }

    /** POST /api/visions/{slug}/{section} – save overlay fields */
    public static function saveSection(string $slug, string $section): void
    {
        header('Content-Type: application/json');
        global $db;
        $vision = vision_model::get($db, $slug);
        if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }
        $id=(int)$vision['id'];
        // differentiate by section
        switch ($section) {
            case 'basics':
                // Reuse updateBasics for date/flags
                $_POST['vision_id']=$id;
                // accept JSON body too
                if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
                    $body=json_decode(file_get_contents('php://input'),true) ?: [];
                    $_POST['start_date'] = $body['start_date'] ?? null;
                    $_POST['end_date']   = $body['end_date'] ?? null;
                    foreach (['relations','goals','budget','roles','contacts','documents','workflow'] as $flag) {
                        if (isset($body[$flag])) {
                            $_POST['show_'.$flag] = $body[$flag] ? '1' : null;
                        }
                    }
                }
                self::updateBasics();
                break;
            // you can add cases for 'relations','goals','budget','roles','contacts','documents','workflow'
            // For now, just acknowledge success; client sends JSON or form-data
            default:
                echo json_encode(['ok'=>true]);
        }
    }
}
