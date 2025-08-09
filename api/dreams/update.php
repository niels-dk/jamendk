<?php
require_once __DIR__.'/../../app/config.php';
require_once __DIR__.'/../../app/auth.php';
require_once __DIR__.'/../../models/dream.php';

header('Content-Type: application/json');

try {
    $id   = (int)($_POST['dream_id'] ?? 0);
    $slug = trim($_POST['slug']     ?? '');
    $title= trim($_POST['title']    ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (!$id || $title==='') throw new Exception('Invalid data');

    $dream = dream_model::findBySlug($db, $slug);
    if (!$dream || $dream['user_id'] !== $currentUserId)
        throw new Exception('Permission denied');

    dream_model::update($db, $id, $title, $desc);
    dream_model::clearAnchors($db, $id);
    dream_model::addAnchors($db, $id, $_POST['location'] ?? [], 'dream_locations','location');
    dream_model::addAnchors($db, $id, $_POST['brand']    ?? [], 'dream_brands','brand');
    dream_model::addAnchors($db, $id, $_POST['person']   ?? [], 'dream_people','person');
    dream_model::addAnchors($db, $id, $_POST['season']   ?? [], 'dream_seasons','season');

    echo json_encode(['ok'=>true,'slug'=>$slug]);
} catch(Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
