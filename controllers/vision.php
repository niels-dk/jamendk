<?php
// controllers/vision.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../models/vision.php';

class vision_controller
{
    /** GET /visions/new */
    public static function create(): void
    {
        $title = 'Create a Vision';
        // $anchors generic pairs for the form
        $kv = []; // empty by default
        ob_start();
        include __DIR__ . '/../views/vision_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** POST /visions/store */
    public static function store(): void
    {
        global $db, $currentUserId;

        $title = trim($_POST['title'] ?? '');
        $desc  = $_POST['description'] ?? '';

        $id = vision_model::create($db, $currentUserId ?: 1, $title, $desc);

        // anchors (key[] + value[] arrays)
        $keys = $_POST['vkey']  ?? [];
        $vals = $_POST['vval']  ?? [];
        $kv   = [];
        for ($i=0; $i<count($keys); $i++) {
            $kv[] = ['key'=>$keys[$i] ?? '', 'value'=>$vals[$i] ?? ''];
        }
        vision_model::replaceAnchors($db, $id, $kv);

        // redirect to show
        $st = $db->prepare("SELECT slug FROM visions WHERE id=?");
        $st->execute([$id]);
        $slug = (string)$st->fetchColumn();
        header("Location: /visions/$slug"); exit;
    }

    /** GET /visions/{slug} */
    public static function show(string $slug): void
    {
        global $db;

        $vision = vision_model::get($db, $slug);
        if (!$vision) { http_response_code(404); echo 'Vision not found'; return; }

        $anchors = vision_model::getAnchors($db, (int)$vision['id']);
        $title   = htmlspecialchars($vision['title']);

        // view buffers content; controller includes layout
        include __DIR__ . '/../views/vision_show.php';
        include __DIR__ . '/../views/layout.php';
    }

    /** GET /visions/{slug}/edit */
    public static function edit(string $slug): void
    {
        global $db;

        $vision = vision_model::get($db, $slug);
        if (!$vision) { http_response_code(404); echo 'Vision not found'; return; }

        $kv = [];
        foreach (vision_model::getAnchors($db, (int)$vision['id']) as $k => $vals) {
            foreach ($vals as $v) $kv[] = ['key'=>$k, 'value'=>$v];
        }

        $title = 'Edit Vision';
        ob_start();
        include __DIR__ . '/../views/vision_form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** POST /visions/update */
    public static function update(): void
    {
        global $db;

        $id    = (int)($_POST['vision_id'] ?? 0);
        if (!$id) { http_response_code(400); echo 'Missing ID'; return; }

        $title = trim($_POST['title'] ?? '');
        $desc  = $_POST['description'] ?? '';

        vision_model::update($db, $id, $title, $desc);

        // anchors
        $keys = $_POST['vkey'] ?? [];
        $vals = $_POST['vval'] ?? [];
        $kv   = [];
        for ($i=0; $i<count($keys); $i++) {
            $kv[] = ['key'=>$keys[$i] ?? '', 'value'=>$vals[$i] ?? ''];
        }
        vision_model::replaceAnchors($db, $id, $kv);

        // fetch slug
        $slug = '';
        $st = $db->prepare("SELECT slug FROM visions WHERE id=?");
        $st->execute([$id]); $slug = (string)$st->fetchColumn();

        header("Location: /visions/$slug"); exit;
    }

    /** State actions */
    public static function archive(string $slug): void
    {
        global $db; $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::setArchived($db, (int)$v['id'], true);
        header('Location: /dashboard/vision'); exit;
    }
    public static function unarchive(string $slug): void
    {
        global $db; $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::setArchived($db, (int)$v['id'], false);
        header('Location: /dashboard/vision/archived'); exit;
    }
    public static function destroy(string $slug): void
    {
        global $db; $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::softDelete($db, (int)$v['id']);
        header('Location: /dashboard/vision'); exit;
    }
    public static function restore(string $slug): void
    {
        global $db; $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::restore($db, (int)$v['id']);
        header('Location: /dashboard/vision/trash'); exit;
    }
}
