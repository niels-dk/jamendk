	<?php
require_once __DIR__ . '/../models/dream.php';
require_once __DIR__ . '/../models/vision.php';
require_once __DIR__ . '/../models/mood.php';
//require_once __DIR__ . '/../models/trip.php';
require_once __DIR__ . '/../app/auth.php';

// Try to load other board models if they exist.
// (No fatal if a file is missing; we fall back to a generic SQL query.)
$__models_base = __DIR__ . '/models/';
foreach (['vision','mood','trip'] as $__m) {
    $__p = $__models_base . $__m . '.php';
    if (file_exists($__p)) {	
        require_once $__p;
    }
}

class home_controller
{
    public static function index()
    {
        global $db, $currentUserId, $isAuthenticated;
        $uid = $isAuthenticated ? (int)$currentUserId : 0;

        // Counts (active only, with the same dream-promotion exclusion as the dashboard)
        $stats = [
            'dreams'  => 0, 'visions' => 0, 'moods' => 0, 'trips' => 0,
        ];
        if (!$uid) {
            // Anonymous: skip the queries entirely
            $recentBoards = [];
            $hasActivity  = false;
            $title = 'Welcome to Jamen';
            ob_start();
            include __DIR__ . '/../views/home.php';
            $content = ob_get_clean();
            $noSidebar = true;
            include __DIR__ . '/../views/layout.php';
            return;
        }
        try {
            $st = $db->prepare("
                SELECT
                  (SELECT COUNT(*) FROM dream_boards d
                    WHERE d.user_id=? AND d.archived=0 AND d.deleted_at IS NULL
                      AND NOT EXISTS (SELECT 1 FROM visions v
                                       WHERE v.dream_id = d.id AND v.deleted_at IS NULL)
                  ) AS dreams,
                  (SELECT COUNT(*) FROM visions v
                    WHERE v.user_id=? AND v.archived=0 AND v.deleted_at IS NULL
                  ) AS visions,
                  (SELECT COUNT(*) FROM mood_boards m
                    WHERE m.user_id=? AND m.archived=0 AND m.deleted_at IS NULL
                  ) AS moods,
                  (SELECT COUNT(*) FROM visions v
                    WHERE v.user_id=? AND v.archived=0 AND v.deleted_at IS NULL
                      AND v.trip_enabled = 1
                  ) AS trips
            ");
            $st->execute([$uid, $uid, $uid, $uid]);
            $r = $st->fetch(PDO::FETCH_ASSOC) ?: [];
            $stats = [
                'dreams'  => (int)($r['dreams']  ?? 0),
                'visions' => (int)($r['visions'] ?? 0),
                'moods'   => (int)($r['moods']   ?? 0),
                'trips'   => (int)($r['trips']   ?? 0),
            ];
        } catch (\Throwable $e) { /* keep zeros */ }

        // Recent boards (top 5 across dream/vision/mood, latest touched first).
        // Explicit COLLATE on every text column + the type literal — the three
        // tables use different collations, and a bare UNION ALL across them
        // throws "Illegal mix of collations" (which previously got swallowed,
        // leaving the section empty). COALESCE(updated_at, created_at) so rows
        // with a null updated_at still sort sensibly.
        $C = 'COLLATE utf8mb4_general_ci';
        $recentBoards = [];
        try {
            $rb = $db->prepare("
                SELECT * FROM (
                    SELECT 'dream'  $C AS type,
                           slug  $C AS slug,
                           title $C AS title,
                           COALESCE(updated_at, created_at) AS ts
                      FROM dream_boards
                     WHERE user_id=? AND archived=0 AND deleted_at IS NULL
                       AND NOT EXISTS (SELECT 1 FROM visions v
                                        WHERE v.dream_id = dream_boards.id AND v.deleted_at IS NULL)
                    UNION ALL
                    SELECT 'vision' $C, slug $C, title $C, COALESCE(updated_at, created_at)
                      FROM visions
                     WHERE user_id=? AND archived=0 AND deleted_at IS NULL
                    UNION ALL
                    SELECT 'mood'   $C, slug $C, title $C, COALESCE(updated_at, created_at)
                      FROM mood_boards
                     WHERE user_id=? AND archived=0 AND deleted_at IS NULL
                ) AS recents
                ORDER BY ts DESC
                LIMIT 5
            ");
            $rb->execute([$uid, $uid, $uid]);
            $recentBoards = $rb->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) { /* keep empty */ }

        // Upcoming / overdue Vision dates — anything starting or ending within
        // the next ~10 days, plus end dates already overdue (not yet complete).
        $upcoming = [];
        try {
            $uq = $db->prepare("
                SELECT slug, title, start_date, end_date, workflow_status
                  FROM visions
                 WHERE user_id = ? AND archived = 0 AND deleted_at IS NULL
                   AND (
                        (start_date IS NOT NULL AND start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 10 DAY))
                     OR (end_date   IS NOT NULL AND end_date   BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 10 DAY))
                     OR (end_date   IS NOT NULL AND end_date < CURDATE() AND workflow_status <> 'complete')
                   )
                 ORDER BY COALESCE(end_date, start_date) ASC
                 LIMIT 6
            ");
            $uq->execute([$uid]);
            $upcoming = $uq->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) { /* keep empty */ }

        $hasActivity = ($stats['dreams'] + $stats['visions'] + $stats['moods']) > 0;
        $userName = (is_array($currentUser ?? null) && !empty($currentUser['name']))
            ? $currentUser['name'] : '';

        $title = 'Welcome to Jamen';
        ob_start();
        include __DIR__ . '/../views/home.php';
        $content = ob_get_clean();

        // dashboard/top pages don't use the left board sidebar
        $noSidebar = true;
        include __DIR__ . '/../views/layout.php';
    }

    // Routes preserved
    public static function dashboard()                               { self::loadDashboard('dream', 'active'); }
    public static function archived()                                { self::loadDashboard('dream', 'archived'); }
    public static function trash()                                   { self::loadDashboard('dream', 'trash'); }
    public static function dashboard_type(string $type)              { self::loadDashboard($type, 'active'); }
    public static function dashboard_type_archived(string $type)     { self::loadDashboard($type, 'archived'); }
    public static function dashboard_type_trash(string $type)        { self::loadDashboard($type, 'trash'); }
    public static function dashboard_type_filter(string $type, string $filter) { self::loadDashboard($type, $filter); }

    private static function loadDashboard(string $type, string $filter): void
    {
        require_login();
        global $db, $currentUserId;

        // Labels available to the view (used for the �Boards ?� and title text)
        $boardTypes = [
            'dream'  => '?? Dreams',
            'vision' => '?? Visions',
            'mood'   => '?? Moods',
            'trip'   => '??? Trips',
        ];

        $type   = strtolower(trim($type ?: 'dream'));
        $filter = strtolower(trim($filter ?: 'active'));
        // Accept plural type slugs (/dashboard/visions/…)
        if (!isset($boardTypes[$type]) && str_ends_with($type, 's')) {
            $type = substr($type, 0, -1);
        }

        if (!isset($boardTypes[$type])) {
            http_response_code(404);
            echo 'Board type not found';
            return;
        }

        // Pull boards for the requested type + filter.
        // Trips are derived from visions, so route them through fetchTripVisions.
        // Site admins see every user's boards (uid 0 = no user filter)
        $uid = is_admin() ? 0 : (int)$currentUserId;
        if ($type === 'trip') {
            $dreams = self::fetchTripVisions($db, $uid, $filter);
        } else {
            $dreams = self::fetchBoards($db, $uid, $type, $filter);
        }

        // View vars
        $title     = ucfirst($type) . 's � ' . ucfirst($filter);
        $boardType = $type;

        // ---- render view into $content, then include layout ----
        ob_start();
        include __DIR__ . '/../views/dashboard.php';
        $content = ob_get_clean();

        // Dashboard pages don�t use the left board sidebar
        $noSidebar = true;
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Returns boards for the given $type and $filter.
     *  - If a model class like vision_model::listByType exists, we use it.
     *  - Otherwise we fall back to a generic SQL over the mapped table.
     */
    //function fetchBoards($type, $filter = 'active', $limit = null, $orderBy = null, $includeFavorites = false)
	public static function fetchBoards(
		PDO $db,
		int $userId,
		string $type,
		string $filter = 'active',
		?int $limit = null,          // <� NEW (null = no limit)
		?string $orderBy = null      // <� optional external sort
	): array
	{
		// map type ? table (kept from your code)
		$tableMap = [
			'dream'  => 'dream_boards',
			'vision' => 'visions',
			'mood'   => 'mood_boards',
			// 'trip' => 'trips',
		];
		if (!isset($tableMap[$type])) return [];
		$table = $tableMap[$type];

		// Sharing filters — dedicated queries since they invert the usual
		// ownership logic. Only meaningful for visions (the sharing unit)
		// and moods (which inherit the parent vision's sharing).
		if ($filter === 'shared-with-me' || $filter === 'shared-by-me') {
			if ($userId <= 0) return []; // admin sees everything under Active anyway
			try {
				if ($type === 'vision') {
					if ($filter === 'shared-with-me') {
						$sql = "SELECT v.*, vr.role AS my_shared_role FROM visions v
								  JOIN vision_roles vr ON vr.vision_id = v.id AND vr.user_id = ?
								 WHERE v.archived = 0 AND v.deleted_at IS NULL
								 ORDER BY v.updated_at DESC";
					} else {
						$sql = "SELECT v.*,
								   (SELECT GROUP_CONCAT(u2.name ORDER BY u2.name SEPARATOR ', ')
									  FROM vision_roles vr2
									  JOIN users u2 ON u2.id = vr2.user_id
									 WHERE vr2.vision_id = v.id) AS shared_with_names
								  FROM visions v
								 WHERE v.user_id = ?
								   AND EXISTS (SELECT 1 FROM vision_roles vr WHERE vr.vision_id = v.id)
								   AND v.archived = 0 AND v.deleted_at IS NULL
								 ORDER BY v.updated_at DESC";
					}
				} elseif ($type === 'mood') {
					if ($filter === 'shared-with-me') {
						$sql = "SELECT DISTINCT m.*, vr.role AS my_shared_role FROM mood_boards m
								  JOIN visions v2
									ON (v2.id = m.vision_id
										OR v2.mood_id COLLATE utf8mb4_general_ci
										   = m.slug COLLATE utf8mb4_general_ci)
								   AND v2.deleted_at IS NULL
								  JOIN vision_roles vr ON vr.vision_id = v2.id AND vr.user_id = ?
								 WHERE m.archived = 0 AND m.deleted_at IS NULL
								 ORDER BY m.updated_at DESC";
					} else {
						$sql = "SELECT m.* FROM mood_boards m
								 WHERE m.user_id = ?
								   AND EXISTS (SELECT 1 FROM vision_roles vr
											   JOIN visions v2 ON vr.vision_id = v2.id
											  WHERE (v2.id = m.vision_id
													 OR v2.mood_id COLLATE utf8mb4_general_ci
														= m.slug COLLATE utf8mb4_general_ci)
												AND v2.deleted_at IS NULL)
								   AND m.archived = 0 AND m.deleted_at IS NULL
								 ORDER BY m.updated_at DESC";
					}
				} else {
					return []; // dreams/trips have no direct sharing
				}
				$st = $db->prepare($sql);
				$st->execute([$userId]);
				return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
			} catch (\Throwable $e) {
				return [];
			}
		}

		// WHERE by filter. $userId <= 0 means "all users" (site admin view).
		// Visions include boards shared with me via vision_roles; moods inherit
		// the parent vision's sharing (either linkage direction).
		if ($userId > 0) {
			if ($type === 'vision') {
				$where  = "($table.user_id = ? OR EXISTS (
							  SELECT 1 FROM vision_roles vr
							   WHERE vr.vision_id = $table.id AND vr.user_id = ?))";
				$params = [$userId, $userId];
			} elseif ($type === 'mood') {
				$where  = "($table.user_id = ? OR EXISTS (
							  SELECT 1 FROM vision_roles vr
							  JOIN visions v2
								ON (v2.id = $table.vision_id
									OR v2.mood_id COLLATE utf8mb4_general_ci
									   = $table.slug COLLATE utf8mb4_general_ci)
							   WHERE vr.vision_id = v2.id
								 AND vr.user_id = ?
								 AND v2.deleted_at IS NULL))";
				$params = [$userId, $userId];
			} else {
				$where  = "$table.user_id = ?";
				$params = [$userId];
			}
		} else {
			$where  = "1=1";
			$params = [];
		}

		// Dreams that have been promoted to a vision are excluded from Active
		// — they've "graduated" and live in the Promoted filter (and under
		// Visions). Archived/Trash still include them since those are
		// orthogonal lifecycle states.
		$dreamPromotedFilter = ($type === 'dream')
			? " AND NOT EXISTS (SELECT 1 FROM visions v
								WHERE v.dream_id = $table.id
								  AND v.deleted_at IS NULL)"
			: '';

		switch ($filter) {
			case 'active':
				$where .= " AND $table.archived = 0 AND $table.deleted_at IS NULL"
					   . $dreamPromotedFilter;
				break;
			case 'archived':
				$where .= " AND $table.archived = 1 AND $table.deleted_at IS NULL";
				break;
			case 'trash':
				$where .= " AND $table.deleted_at IS NOT NULL";
				break;
			case 'promoted':
				// Dreams with a linked vision (derived). Only meaningful for
				// type='dream'; otherwise degrade to plain Active.
				if ($type === 'dream') {
					$where .= " AND $table.deleted_at IS NULL
								AND EXISTS (SELECT 1 FROM visions v
											 WHERE v.dream_id = $table.id
											   AND v.deleted_at IS NULL)";
				} else {
					$where .= " AND $table.archived = 0 AND $table.deleted_at IS NULL";
				}
				break;
			default:
				$where .= " AND $table.archived = 0 AND $table.deleted_at IS NULL"
					   . $dreamPromotedFilter;
				break;
		}

		// Resolve ORDER BY only if not provided from the caller
		if (!$orderBy) {
			$orderBy = 'created_at DESC';
			try {
				$colCheck = $db->prepare("SHOW COLUMNS FROM `$table` LIKE 'start_date'");
				$colCheck->execute();
				if ($colCheck->fetch(PDO::FETCH_NUM)) {
					$orderBy = "IFNULL(start_date, created_at) DESC, created_at DESC";
				}
			} catch (\Throwable $e) {
				// keep default
			}
		}

		// Build SELECT — for dreams, attach a derived is_promoted flag so the
		// dashboard cards can show a "Promoted ✓" badge without an N+1 query.
		$select = "`$table`.*";
		$selectParams = []; // positional params bind in SQL order: SELECT first
		if ($type === 'dream') {
			$select .= ", (SELECT 1 FROM visions v
							WHERE v.dream_id = $table.id
							  AND v.deleted_at IS NULL
							LIMIT 1) AS is_promoted";
		}
		// Sharing context for the cards: my role on boards shared with me,
		// and the collaborator names on boards I've shared out.
		if ($userId > 0 && $type === 'vision') {
			$select .= ", (SELECT vr.role FROM vision_roles vr
							WHERE vr.vision_id = $table.id AND vr.user_id = ?
							LIMIT 1) AS my_shared_role";
			$selectParams[] = $userId;
			$select .= ", (SELECT GROUP_CONCAT(u2.name ORDER BY u2.name SEPARATOR ', ')
							 FROM vision_roles vr2
							 JOIN users u2 ON u2.id = vr2.user_id
							WHERE vr2.vision_id = $table.id) AS shared_with_names";
		} elseif ($userId > 0 && $type === 'mood') {
			$select .= ", (SELECT vr.role FROM vision_roles vr
							 JOIN visions v3 ON vr.vision_id = v3.id
							WHERE (v3.id = $table.vision_id
								   OR v3.mood_id COLLATE utf8mb4_general_ci
									  = $table.slug COLLATE utf8mb4_general_ci)
							  AND v3.deleted_at IS NULL
							  AND vr.user_id = ?
							LIMIT 1) AS my_shared_role";
			$selectParams[] = $userId;
		}

		$sql = "SELECT $select FROM `$table` WHERE $where ORDER BY $orderBy";
		if ($limit !== null) {
			$sql .= " LIMIT " . (int)$limit;   // integer cast to avoid injection
		}

		$stmt = $db->prepare($sql);
		$stmt->execute(array_merge($selectParams, $params));
		return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}



	
	/**
	 * Trip-ready visions: every non-archived, non-deleted vision is treated
	 * as a trip. The trip page itself renders only what's flagged visible
	 * via the per-section Show on Trip layer toggles, so empty trips
	 * degrade gracefully.
	 *
	 * Filter mirrors the vision's lifecycle:
	 *   active   → archived=0, deleted_at IS NULL  (default)
	 *   archived → archived=1, deleted_at IS NULL
	 *   trash    → deleted_at IS NOT NULL
	 */
	private static function fetchTripVisions(PDO $db, int $userId, string $filter = 'active'): array
	{
		switch ($filter) {
			case 'archived': $stateSql = 'v.archived = 1 AND v.deleted_at IS NULL'; break;
			case 'trash':    $stateSql = 'v.deleted_at IS NOT NULL'; break;
			// Active trips: published master switch must be on
			default:         $stateSql = 'v.archived = 0 AND v.deleted_at IS NULL AND v.trip_enabled = 1'; break;
		}

		// NOTE: explicit COLLATE on both sides of the slug join — mood_boards.slug
		// and visions.mood_id were created with different collations on some
		// installs, which would otherwise raise "Illegal mix of collations".
		$sql = "
			SELECT v.id, v.slug, v.title, v.description,
				   v.start_date, v.end_date,
				   v.created_at, v.updated_at, v.deleted_at,
				   v.workflow_status,
				   v.mood_id, v.show_mood_on_trip,
				   mb.title AS mood_title,
				   (SELECT COUNT(*) FROM vision_contacts
					 WHERE vision_id = v.id AND show_on_trip = 1) AS contact_count,
				   (SELECT 1 FROM vision_budget
					 WHERE vision_id = v.id AND show_on_trip = 1
					 LIMIT 1) AS has_budget
			  FROM visions v
			  LEFT JOIN mood_boards mb
					 ON mb.slug COLLATE utf8mb4_general_ci
						= v.mood_id COLLATE utf8mb4_general_ci
					AND mb.deleted_at IS NULL
			 WHERE " . ($userId > 0 ? "v.user_id = ?" : "1=1") . "
			   AND $stateSql
			 ORDER BY v.updated_at DESC
		";
		$st = $db->prepare($sql);
		$st->execute($userId > 0 ? [$userId] : []);
		return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}

	public static function dashboard_overview(): void
	{
		require_login();
		global $db, $currentUserId;

		// Sorting mode (latest|newest|favorites)
		$sort = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : 'latest';

		// Limit for each section: default 2, unless user sets ?limit_each=...
		$limitEachParam = isset($_GET['limit_each']) ? (int)$_GET['limit_each'] : 2;
		$limitEach = $limitEachParam > 0 ? $limitEachParam : null;

		$types  = ['dream','vision','mood','trip'];
		$boards = [];

		// Site admins see every user's boards (uid 0 = no user filter)
		$uid = is_admin() ? 0 : (int)$currentUserId;

		foreach ($types as $type) {
			if ($type === 'trip') {
				$list = self::fetchTripVisions($db, $uid);
			} else {
				$list = self::fetchBoards($db, $uid, $type, 'active');
			}

			// Sorting
			if ($sort === 'newest') {
				usort($list, fn($a,$b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
			} elseif ($sort === 'favorites') {
				usort($list, function($a,$b){
					$fa = (int)($a['is_favorite'] ?? 0);
					$fb = (int)($b['is_favorite'] ?? 0);
					if ($fa !== $fb) return $fb <=> $fa;
					return strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? '');
				});
			} else {
				usort($list, fn($a,$b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));
			}

			// Apply limit only if not null
			if ($limitEach !== null) {
				$list = array_slice($list, 0, $limitEach);
			}

			$boards[$type] = $list;
		}

		// One-time notice: boards shared with me since I last dismissed it.
		$newShares = [];
		try {
			$ns = $db->prepare("
				SELECT vr.role, vr.created_at, v.slug, v.title, u.name AS owner_name
				  FROM vision_roles vr
				  JOIN visions v ON v.id = vr.vision_id AND v.deleted_at IS NULL
				  JOIN users u   ON u.id = v.user_id
				 WHERE vr.user_id = ?
				   AND vr.created_at > COALESCE(
						 (SELECT shares_seen_at FROM users WHERE id = ?), '1970-01-01')
				 ORDER BY vr.created_at DESC
				 LIMIT 20
			");
			$ns->execute([(int)$currentUserId, (int)$currentUserId]);
			$newShares = $ns->fetchAll(PDO::FETCH_ASSOC) ?: [];
		} catch (\Throwable $e) { /* column/table not migrated yet */ }

		// Returned work: handoffs to me that I haven't checked off yet.
		// These persist in the notice until each one is acknowledged.
		$handoffs = [];
		try {
			$hs = $db->prepare("
				SELECT h.id, h.note, h.created_at,
					   v.slug, v.title,
					   u.name AS from_name
				  FROM vision_handoffs h
				  JOIN visions v ON v.id = h.vision_id AND v.deleted_at IS NULL
				  JOIN users u   ON u.id = h.from_user_id
				 WHERE h.to_user_id = ? AND h.acknowledged_at IS NULL
				 ORDER BY h.created_at DESC
				 LIMIT 30
			");
			$hs->execute([(int)$currentUserId]);
			$handoffs = $hs->fetchAll(PDO::FETCH_ASSOC) ?: [];
		} catch (\Throwable $e) { /* table not migrated yet */ }

		// Generic check-to-dismiss notices (goal assignments, resolutions, returns…)
		$notices = [];
		try {
			$nt = $db->prepare("
				SELECT n.id, n.type, n.note, n.created_at,
					   v.slug  AS vision_slug,
					   v.title AS vision_title,
					   g.title AS goal_title,
					   u.name  AS from_name
				  FROM notifications n
				  LEFT JOIN visions v ON v.id = n.vision_id
				  LEFT JOIN vision_goals g ON g.id = n.goal_id
				  LEFT JOIN users u ON u.id = n.from_user_id
				 WHERE n.user_id = ? AND n.acknowledged_at IS NULL
				 ORDER BY n.created_at DESC
				 LIMIT 30
			");
			$nt->execute([(int)$currentUserId]);
			$notices = $nt->fetchAll(PDO::FETCH_ASSOC) ?: [];
		} catch (\Throwable $e) { /* table not migrated yet */ }

		// View vars
		$pageTitle  = 'Dashboard';
		$boardSets  = $boards;
		$noSidebar  = true;
		$sortValue  = $sort;
		$limitValue = $limitEachParam;

		// Render
		ob_start();
		include __DIR__ . '/../views/dashboard_overview.php';
		$content = ob_get_clean();
		include __DIR__ . '/../views/layout.php';
	}



}
