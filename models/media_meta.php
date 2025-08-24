<?php
// models/media_meta.php

class media_meta
{
  /** slugify */
  static function slugify($s) {
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = preg_replace('~[^\\pL\\d]+~u', '-', $s);
    $s = trim($s, '-');
    $s = strtolower($s);
    return $s ?: bin2hex(random_bytes(4));
  }

  /** Ensure the caller owns the media (by user_id on media row). */
  static function assert_media_owner(PDO $db, int $media_id, int $user_id) {
    // adapt table name if yours is different
    $stmt = $db->prepare("SELECT id FROM media WHERE id=? AND user_id=? LIMIT 1");
    $stmt->execute([$media_id, $user_id]);
    if (!$stmt->fetchColumn()) throw new Exception('Not allowed');
  }

  /* ---------------- TAGS ---------------- */

  static function list_tags(PDO $db, int $user_id) {
    $stmt = $db->prepare("SELECT id,name,slug,color FROM media_tags_master WHERE user_id=? ORDER BY name");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static function upsert_tag(PDO $db, int $user_id, string $name, ?string $color=null) {
    $name = trim($name);
    if ($name==='') throw new Exception('Tag name required');
    $slug = self::slugify($name);
    // try existing
    $sel = $db->prepare("SELECT id,name,slug,color FROM media_tags_master WHERE user_id=? AND slug=?");
    $sel->execute([$user_id, $slug]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if ($row) return $row;

    $ins = $db->prepare("INSERT INTO media_tags_master (user_id,name,slug,color) VALUES (?,?,?,?)");
    $ins->execute([$user_id, $name, $slug, $color]);
    return ['id'=>$db->lastInsertId(), 'name'=>$name, 'slug'=>$slug, 'color'=>$color];
  }

  static function set_media_tags(PDO $db, int $media_id, array $tag_ids, int $user_id) {
    self::assert_media_owner($db, $media_id, $user_id);
    $db->prepare("DELETE mt FROM media_tags_map mt
                  JOIN media tMedia ON tMedia.id=mt.media_id
                  WHERE mt.media_id=?")->execute([$media_id]);
    if (!$tag_ids) return true;
    $ins = $db->prepare("INSERT IGNORE INTO media_tags_map (media_id, tag_id) VALUES (?,?)");
    foreach ($tag_ids as $tid) $ins->execute([$media_id, intval($tid)]);
    return true;
  }

  static function tags_for_media(PDO $db, int $media_id) {
    $stmt = $db->prepare("SELECT t.id,t.name,t.slug,t.color
                          FROM media_tags_map m
                          JOIN media_tags_master t ON t.id=m.tag_id
                          WHERE m.media_id=? ORDER BY t.name");
    $stmt->execute([$media_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /* --------------- GROUPS --------------- */

  static function list_groups(PDO $db, int $user_id) {
    $stmt = $db->prepare("SELECT id,name,slug,color,sort FROM media_groups_master WHERE user_id=? ORDER BY sort,name");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static function create_group(PDO $db, int $user_id, string $name, ?string $color=null) {
    $name = trim($name);
    if ($name==='') throw new Exception('Group name required');
    $slug = self::slugify($name);
    $sel = $db->prepare("SELECT id,name,slug,color,sort FROM media_groups_master WHERE user_id=? AND slug=?");
    $sel->execute([$user_id, $slug]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if ($row) return $row;

    $ins = $db->prepare("INSERT INTO media_groups_master (user_id,name,slug,color,sort) VALUES (?,?,?,?,0)");
    $ins->execute([$user_id, $name, $slug, $color]);
    return ['id'=>$db->lastInsertId(), 'name'=>$name, 'slug'=>$slug, 'color'=>$color, 'sort'=>0];
  }

  static function rename_group(PDO $db, int $user_id, int $group_id, string $name) {
    $stmt = $db->prepare("UPDATE media_groups_master SET name=?, slug=? WHERE id=? AND user_id=?");
    $stmt->execute([trim($name), self::slugify($name), $group_id, $user_id]);
    return true;
  }

  static function delete_group(PDO $db, int $user_id, int $group_id) {
    $db->prepare("DELETE FROM media_groups_map WHERE group_id=?")->execute([$group_id]);
    $db->prepare("DELETE FROM media_groups_master WHERE id=? AND user_id=?")->execute([$group_id, $user_id]);
    return true;
  }

  static function set_media_groups(PDO $db, int $media_id, array $group_ids, int $user_id) {
    self::assert_media_owner($db, $media_id, $user_id);
    $db->prepare("DELETE FROM media_groups_map WHERE media_id=?")->execute([$media_id]);
    if (!$group_ids) return true;
    $ins = $db->prepare("INSERT IGNORE INTO media_groups_map (media_id, group_id) VALUES (?,?)");
    foreach ($group_ids as $gid) $ins->execute([$media_id, intval($gid)]);
    return true;
  }

  static function groups_for_media(PDO $db, int $media_id) {
    $stmt = $db->prepare("SELECT g.id,g.name,g.slug,g.color
                          FROM media_groups_map m
                          JOIN media_groups_master g ON g.id=m.group_id
                          WHERE m.media_id=? ORDER BY g.sort,g.name");
    $stmt->execute([$media_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
