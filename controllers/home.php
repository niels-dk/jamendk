<?php
require_once __DIR__ . '/../models/dream.php';
require_once __DIR__ . '/../app/auth.php';

class home_controller
{
    public static function index()
    {
        $title = 'Welcome to Jamen';
        ob_start();
        include __DIR__ . '/../views/home.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public static function dashboard()         { self::loadDashboard('dream', 'active'); }
    public static function archived()          { self::loadDashboard('dream', 'archived'); }
    public static function trash()             { self::loadDashboard('dream', 'trash'); }
    public static function dashboard_type(string $type)              { self::loadDashboard($type, 'active'); }
    public static function dashboard_type_archived(string $type)     { self::loadDashboard($type, 'archived'); }
    public static function dashboard_type_trash(string $type)        { self::loadDashboard($type, 'trash'); }
    public static function dashboard_type_filter(string $type, string $filter) { self::loadDashboard($type, $filter); }

    private static function loadDashboard(string $type, string $filter): void
    {
        global $db, $currentUserId;

        // Labels available to the view (and for the dropdowns)
        $boardTypes = [
            'dream'  => '?? Dreams',
            'vision' => '?? Visions',
            'mood'   => '?? Moods',
            'trip'   => '??? Trips'
        ];

        if (!isset($boardTypes[$type])) {
            http_response_code(404);
            echo 'Board type not found';
            return;
        }

        $title      = ucfirst($type) . 's – ' . ucfirst($filter);
        $boardType  = $type;
        $dreams     = dream_model::listByType($db, $currentUserId, $type, $filter);

        // ---- render view into $content, then include layout ----
        ob_start();
        include __DIR__ . '/../views/dashboard.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
		}

			// render
			$view = 'dashboard';
			include __DIR__.'/../views/layout.php';
		}

}
