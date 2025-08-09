<?php
require_once __DIR__.'/../../app/config.php';
require_once __DIR__.'/../../app/auth.php';
require_once __DIR__.'/../../models/dream.php';

header('Content-Type: application/json');

try {
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');

    if ($title === '') throw new Exception('Title required');

    $slug  = dream_model::create($db, $currentUserId, $title, $desc);
    $dream = dream_model::findBySlug($db, $slug);

    /* anchors */
    dream_model::addAnchors($db, $dream['id'], $_POST['location'] ?? [], 'dream_locations','location');
    dream_model::addAnchors($db, $dream['id'], $_POST['brand']    ?? [], 'dream_brands','brand');
    dream_model::addAnchors($db, $dream['id'], $_POST['person']   ?? [], 'dream_people','person');
    dream_model::addAnchors($db, $dream['id'], $_POST['season']   ?? [], 'dream_seasons','season');

    echo json_encode(['ok'=>true,'slug'=>$slug]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
