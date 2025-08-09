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

    public static function dashboard()
    {
        self::loadDashboard('dream', 'active');
    }

    public static function archived()
    {
        self::loadDashboard('dream', 'archived');
    }

    public static function trash()
    {
        self::loadDashboard('dream', 'trash');
    }

    public static function dashboard_type(string $type): void
    {
        self::loadDashboard($type, 'active');
    }

    public static function dashboard_type_archived(string $type): void
    {
        self::loadDashboard($type, 'archived');
    }

    public static function dashboard_type_trash(string $type): void
    {
        self::loadDashboard($type, 'trash');
    }

    public static function dashboard_type_filter(string $type, string $filter): void
    {
        self::loadDashboard($type, $filter);
    }

    // controllers/home.php (inside loadDashboard)
	private static function loadDashboard(string $type, string $filter): void
	{
		global $db, $currentUserId;

		$boardTypes = [
		  'dream'  => '?? Dreams',
		  'vision' => '?? Visions',
		  'mood'   => '?? Moods',
		  'trip'   => '??? Trips'
		];
		if (!isset($boardTypes[$type])) { http_response_code(404); echo 'Board type not found'; return; }

		$title     = ucfirst($type).'s – '.ucfirst($filter);
		$boardType = $type;

		if ($type === 'vision') {
			// list from visions table
			require_once __DIR__.'/../models/vision.php';
			if ($filter === 'archived')      $raw = vision_model::listArchived($db, $currentUserId);
			elseif ($filter === 'trash')     $raw = vision_model::listTrashed($db, $currentUserId);
			else                              $raw = vision_model::listActive($db, $currentUserId);

			// attach anchors
			$dreams = [];
			foreach ($raw as $r) {
				$r['anchors'] = vision_model::getAnchors($db, (int)$r['id']);
				$dreams[] = $r;
			}
		} else {
			// existing dream flow (what you already have today)
			require_once __DIR__.'/../models/dream.php';
			if ($filter === 'archived')      $raw = dream_model::listArchived($db, $currentUserId);
			elseif ($filter === 'trash')     $raw = dream_model::listTrashed($db, $currentUserId);
			else                              $raw = dream_model::listActive($db, $currentUserId);

			$dreams = [];
			foreach ($raw as $r) {
				$r['anchors'] = dream_model::getAnchors($db, (int)$r['id']);
				$dreams[] = $r;
			}
		}

		// render
		$view = 'dashboard';
		include __DIR__.'/../views/layout.php';
	}

}
