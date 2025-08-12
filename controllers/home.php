<?php
require_once __DIR__ . '/../models/dream.php';
require_once __DIR__ . '/../app/auth.php';

// Try to load other board models if they exist.
// (No fatal if a file is missing; we fall back to a generic SQL query.)
$__models_base = __DIR__ . '/../models/';
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

        // dashboard/top pages don’t use the left board sidebar
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

        // Labels available to the view (used for the “Boards ?” and title text)
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
            // If your auth layer sets this differently, keep it—this is a safe default.
            $currentUserId = 1;
        }

        // Pull boards for the requested type + filter.
        // Keep the result in $dreams because the existing dashboard view expects that variable.
        $dreams = self::fetchBoards($db, $currentUserId, $type, $filter);
		foreach ($visions as &$v) {
			$v['anchors_summary'] = vision_model::getAnchorsSummary($db, (int)$v['id'], 4);
		}
		unset($v);

        // View vars
        $title     = ucfirst($type) . 's – ' . ucfirst($filter);
        $boardType = $type;

        // ---- render view into $content, then include layout ----
        ob_start();
        include __DIR__ . '/../views/dashboard.php';
        $content = ob_get_clean();

        // Dashboard pages don’t use the left board sidebar
        $noSidebar = true;
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Returns boards for the given $type and $filter.
     *  - If a model class like vision_model::listByType exists, we use it.
     *  - Otherwise we fall back to a generic SQL over the mapped table.
     */
    private static function fetchBoards(PDO $db, int $userId, string $type, string $filter): array
    {
        // model class if present (e.g., dream_model, vision_model…)
        $modelClass = $type . '_model';
        if (class_exists($modelClass) && method_exists($modelClass, 'listByType')) {
            return $modelClass::listByType($db, $userId, $type, $filter);
        }

        // Fallback: generic SQL by table
        $tableMap = [
            'dream'  => 'dreams',
            'vision' => 'visions',
            'mood'   => 'moods',
            'trip'   => 'trips',
        ];
        if (!isset($tableMap[$type])) {
            return [];
        }
        $table  = $tableMap[$type];


        $where  = 'user_id = ?';
        $params = [$userId];

        switch ($filter) {
            case 'active':
                $where .= ' AND archived = 0 AND deleted_at IS NULL';
                break;
            case 'archived':
                $where .= ' AND archived = 1 AND deleted_at IS NULL';
                break;
            case 'trash':
                $where .= ' AND deleted_at IS NOT NULL';
                break;
            default:
                $where .= ' AND archived = 0 AND deleted_at IS NULL';
                break;
        }

        // Prefer start_date if present; otherwise fallback to created_at
        $orderBy = 'created_at DESC';
        try {
            $colCheck = $db->prepare("SHOW COLUMNS FROM `$table` LIKE 'start_date'");
            $colCheck->execute();
            if ($colCheck->fetch(PDO::FETCH_ASSOC)) {
                $orderBy = "IFNULL(start_date, created_at) DESC, created_at DESC";
            }
        } catch (\Throwable $e) {
            // ignore and keep default
        }

        $sql = "SELECT * FROM `$table` WHERE $where ORDER BY $orderBy";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
