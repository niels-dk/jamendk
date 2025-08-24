<?php
// controllers/media_meta.php
require_once __DIR__ . '/../models/media_meta.php';

class media_meta_controller
{
  static function user_id() {
    // adapt to your auth
    if (!isset($_SESSION['user_id'])) throw new Exception('Not signed in');
    return intval($_SESSION['user_id']);
  }

  static function ok($data=[]) {
    header('Content-Type: application/json');
    echo json_encode(['success'=>true] + $data); exit;
  }
  static function err($msg) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'error'=>$msg]); exit;
  }

  /* ---------- TAGS ---------- */
  static function tags_index(PDO $db) {
    try { self::ok(['tags'=> media_meta::list_tags($db, self::user_id())]); }
    catch (Throwable $e) { self::err($e->getMessage()); }
  }
  static function tags_upsert(PDO $db) {
    try {
      $name  = trim($_POST['name'] ?? '');
      $color = $_POST['color'] ?? null;
      $tag = media_meta::upsert_tag($db, self::user_id(), $name, $color);
      self::ok(['tag'=>$tag]);
    } catch (Throwable $e) { self::err($e->getMessage()); }
  }
  static function tags_set_for_media(PDO $db, $media_id) {
    try {
      $ids = $_POST['tag_ids'] ?? [];
      if (is_string($ids)) $ids = array_filter(array_map('intval', explode(',', $ids)));
      media_meta::set_media_tags($db, intval($media_id), (array)$ids, self::user_id());
      self::ok();
    } catch (Throwable $e) { self::err($e->getMessage()); }
  }

  /* --------- GROUPS --------- */
  static function groups_index(PDO $db) {
    try { self::ok(['groups'=> media_meta::list_groups($db, self::user_id())]); }
    catch (Throwable $e) { self::err($e->getMessage()); }
  }
  static function groups_create(PDO $db) {
    try {
      $name  = trim($_POST['name'] ?? '');
      $color = $_POST['color'] ?? null;
      $g = media_meta::create_group($db, self::user_id(), $name, $color);
      self::ok(['group'=>$g]);
    } catch (Throwable $e) { self::err($e->getMessage()); }
  }
  static function groups_rename(PDO $db, $id) {
    try { media_meta::rename_group($db, self::user_id(), intval($id), trim($_POST['name']??'')); self::ok(); }
    catch (Throwable $e) { self::err($e->getMessage()); }
  }
  static function groups_delete(PDO $db, $id) {
    try { media_meta::delete_group($db, self::user_id(), intval($id)); self::ok(); }
    catch (Throwable $e) { self::err($e->getMessage()); }
  }
  static function groups_set_for_media(PDO $db, $media_id) {
    try {
      $ids = $_POST['group_ids'] ?? [];
      if (is_string($ids)) $ids = array_filter(array_map('intval', explode(',', $ids)));
      media_meta::set_media_groups($db, intval($media_id), (array)$ids, self::user_id());
      self::ok();
    } catch (Throwable $e) { self::err($e->getMessage()); }
  }
}

class media_meta
{
    public static function tags_for_media(PDO $db, int $mediaId): array
    {
        $st = $db->prepare("
            SELECT t.id, t.name
            FROM media_tags mt
            JOIN tags t ON mt.tag_id = t.id
            WHERE mt.media_id = ?
            ORDER BY t.name
        ");
        $st->execute([$mediaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function groups_for_media(PDO $db, int $mediaId): array
    {
        $st = $db->prepare("
            SELECT g.id, g.name
            FROM media_groups mg
            JOIN groups g ON mg.group_id = g.id
            WHERE mg.media_id = ?
            ORDER BY g.name
        ");
        $st->execute([$mediaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

