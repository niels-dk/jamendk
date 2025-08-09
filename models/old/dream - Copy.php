<?php
// models/dream.php

class dream_model
{
	public static function listActive(PDO $db, int $userId): array
	{
		$stmt = $db->prepare("
			SELECT id, slug, title, description, created_at
			FROM dream_boards
			WHERE user_id = ?
			  AND archived = 0
			  AND deleted_at IS NULL
			ORDER BY created_at DESC
		");
		$stmt->execute([$userId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public static function create(PDO $db, int $userId, string $title, string $description): int
	{
		$stmt = $db->prepare("
			INSERT INTO dreams (user_id, title, description, created_at)
			VALUES (?, ?, ?, NOW())
		");
		$stmt->execute([$userId, $title, $description]);
		return (int)$db->lastInsertId();
	}

	private static function slugExists(PDO $db, string $slug): bool
	{
		$st = $db->prepare('SELECT 1 FROM dream_boards WHERE slug = ?');
		$st->execute([$slug]);
		return (bool)$st->fetchColumn();
	}

	public static function findBySlug(PDO $db, string $slug)
	{
		$st = $db->prepare('SELECT * FROM dream_boards WHERE slug = ?');
		$st->execute([$slug]);
		return $st->fetch(PDO::FETCH_ASSOC);
	}

	public static function addAnchors(PDO $db, int $dreamId, string $type, array $values): void
	{
		$stmt = $db->prepare("
			INSERT INTO dream_anchors (dream_id, type, value)
			VALUES (?, ?, ?)
		");
		foreach ($values as $val) {
			$stmt->execute([$dreamId, $type, $val]);
		}
	}

	public static function listForUser(PDO $db, int $userId): array
	{
		$stmt = $db->prepare("
			SELECT id, slug, title, description, created_at
			FROM dreams
			WHERE user_id = ? AND deleted_at IS NULL
			ORDER BY created_at DESC
		");
		$stmt->execute([$userId]);
		$dreams = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$stmtA = $db->prepare("
			SELECT type, value
			FROM dream_anchors
			WHERE dream_id = ?
		");

		foreach ($dreams as &$d) {
			$stmtA->execute([$d['id']]);
			$anchors = $stmtA->fetchAll(PDO::FETCH_ASSOC);
			$d['anchors'] = [];
			foreach ($anchors as $a) {
				$d['anchors'][$a['type']][] = $a['value'];
			}
		}

		return $dreams;
	}

	public static function getAnchors(PDO $db, int $boardId): array
	{
		$map = [
			'locations' => ['table' => 'dream_locations', 'col' => 'location'],
			'brands'    => ['table' => 'dream_brands',    'col' => 'brand'],
			'people'    => ['table' => 'dream_people',    'col' => 'person'],
			'seasons'   => ['table' => 'dream_seasons',   'col' => 'season'],
		];

		$anchors = [];

		foreach ($map as $type => $info) {
			$sql  = "SELECT {$info['col']} AS val FROM {$info['table']} WHERE dream_id = ?";
			$stmt = $db->prepare($sql);
			$stmt->execute([$boardId]);
			$anchors[$type] = $stmt->fetchAll(PDO::FETCH_COLUMN);
		}
		return $anchors;
	}

	public static function listArchived(PDO $db, int $userId): array
	{
		$stmt = $db->prepare("
			SELECT id, slug, title, description, created_at
			FROM dream_boards
			WHERE user_id = ?
			  AND archived = 1
			  AND deleted_at IS NULL
			ORDER BY created_at DESC
		");
		$stmt->execute([$userId]);
		$boards = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($boards as &$b) {
			$b['anchors'] = self::getAnchors($db, (int)$b['id']);
		}
		return $boards;
	}

	public static function listTrashed(PDO $db, int $userId): array
	{
		$stmt = $db->prepare("
			SELECT id, slug, title, description, created_at
			FROM dream_boards
			WHERE user_id = ?
			  AND deleted_at IS NOT NULL
			ORDER BY deleted_at DESC
		");
		$stmt->execute([$userId]);
		$boards = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($boards as &$b) {
			$b['anchors'] = self::getAnchors($db, (int)$b['id']);
		}
		return $boards;
	}

	public static function show(string $slug): void
	{
		global $db;
		$dream = dream_model::get($db, $slug);

		if (!$dream) {
			echo "DEBUG: slug {$slug} not found in DB";
			return;
		}

		var_dump($dream);   // temporary
		include __DIR__.'/../views/dream/show.php';
	}

	public static function get(PDO $db, string $slug): ?array
	{
		$stmt = $db->prepare("
			SELECT id, slug, title, description, created_at
			FROM dream_boards
			WHERE slug = ?
			  AND deleted_at IS NULL
		");
		$stmt->execute([$slug]);
		$board = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$board) return null;

		$board['anchors'] = self::getAnchors($db, (int)$board['id']);
		return $board;
	}

	public static function update(PDO $db, int $dreamId, string $title, string $desc): void
	{
		$sql = 'UPDATE dream_boards
				   SET title=:t, description=:d, updated_at=NOW()
				 WHERE id=:id';
		$db->prepare($sql)->execute([
			':t' => $title,
			':d' => $desc,
			':id'=> $dreamId
		]);
	}

	public static function clearAnchors(PDO $db, int $dreamId): void
	{
		foreach (['dream_locations','dream_brands','dream_people','dream_seasons'] as $tbl) {
			$db->prepare("DELETE FROM {$tbl} WHERE dream_id=?")
			   ->execute([$dreamId]);
		}
	}

	public static function setArchived(PDO $db, int $dreamId, bool $arch): void
	{
		$sql = 'UPDATE dream_boards SET archived=:a, updated_at=NOW() WHERE id=:id';
		$db->prepare($sql)->execute([
			':a'  => $arch ? 1 : 0,
			':id' => $dreamId
		]);
	}

	public static function softDelete(PDO $db, int $dreamId): void
	{
		$sql = 'UPDATE dream_boards
				   SET deleted_at=NOW(), updated_at=NOW()
				 WHERE id=:id';
		$db->prepare($sql)->execute([':id'=>$dreamId]);
	}

	public static function restore(PDO $db, int $dreamId): void
	{
		$sql = 'UPDATE dream_boards
				   SET deleted_at=NULL, updated_at=NOW()
				 WHERE id=:id';
		$db->prepare($sql)->execute([':id'=>$dreamId]);
	}
}
