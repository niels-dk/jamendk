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
        $title = 'Welcome to Jamen';
        ob_start();
        include __DIR__ . '/../views/home.php';
        $content = ob_get_clean();

        // dashboard/top pages don�t use the left board sidebar
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

        if (!isset($boardTypes[$type])) {
            http_response_code(404);
            echo 'Board type not found';
            return;
        }

        if (empty($currentUserId)) {
            // If your auth layer sets this differently, keep it�this is a safe default.
            $currentUserId = 1;
        }

        // Pull boards for the requested type + filter.
        // Trips are derived from visions, so route them through fetchTripVisions.
        if ($type === 'trip') {
            $dreams = self::fetchTripVisions($db, (int)$currentUserId, $filter);
        } else {
            $dreams = self::fetchBoards($db, (int)$currentUserId, $type, $filter);
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

		// WHERE by filter
		$where  = "$table.user_id = ?";
		$params = [$userId];
		switch ($filter) {
			case 'active':   $where .= " AND $table.archived = 0 AND $table.deleted_at IS NULL"; break;
			case 'archived': $where .= " AND $table.archived = 1 AND $table.deleted_at IS NULL"; break;
			case 'trash':    $where .= " AND $table.deleted_at IS NOT NULL"; break;
			case 'promoted':
				// Dreams that have been promoted to a vision (derived state).
				// Only meaningful for type='dream' — for other types this
				// degrades into the active filter without throwing.
				if ($type === 'dream') {
					$where .= " AND $table.deleted_at IS NULL
								AND EXISTS (SELECT 1 FROM visions v
											 WHERE v.dream_id = $table.id
											   AND v.deleted_at IS NULL)";
				} else {
					$where .= " AND $table.archived = 0 AND $table.deleted_at IS NULL";
				}
				break;
			default:         $where .= " AND $table.archived = 0 AND $table.deleted_at IS NULL"; break;
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
		if ($type === 'dream') {
			$select .= ", (SELECT 1 FROM visions v
							WHERE v.dream_id = $table.id
							  AND v.deleted_at IS NULL
							LIMIT 1) AS is_promoted";
		}

		$sql = "SELECT $select FROM `$table` WHERE $where ORDER BY $orderBy";
		if ($limit !== null) {
			$sql .= " LIMIT " . (int)$limit;   // integer cast to avoid injection
		}

		$stmt = $db->prepare($sql);
		$stmt->execute($params);
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
			default:         $stateSql = 'v.archived = 0 AND v.deleted_at IS NULL'; break;
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
			 WHERE v.user_id = ?
			   AND $stateSql
			 ORDER BY v.updated_at DESC
		";
		$st = $db->prepare($sql);
		$st->execute([$userId]);
		return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}

	public static function dashboard_overview(): void
	{
		global $db, $currentUserId;

		if (empty($currentUserId)) { $currentUserId = 1; }

		// Sorting mode (latest|newest|favorites)
		$sort = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : 'latest';

		// Limit for each section: default 2, unless user sets ?limit_each=...
		$limitEachParam = isset($_GET['limit_each']) ? (int)$_GET['limit_each'] : 2;
		$limitEach = $limitEachParam > 0 ? $limitEachParam : null;

		$types  = ['dream','vision','mood','trip'];
		$boards = [];

		foreach ($types as $type) {
			if ($type === 'trip') {
				$list = self::fetchTripVisions($db, (int)$currentUserId);
			} else {
				$list = self::fetchBoards($db, (int)$currentUserId, $type, 'active');
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
