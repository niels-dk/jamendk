<?php
    /**
     * Tiny router that maps path‑regex => [controller, method]
     * ─────────────────────────────────────────────────────────
     *  • Add new routes by appending regex patterns to $routes.
     *  • Regex delimiters are added automatically by preg_match in the loop.
     */

    function route(string $uri): void
    {
        $routes = [
        ''                => ['home',  'index'],
        '/'               => ['home',  'index'],
        '/index\.php'     => ['home',  'index'],

        '/login'          => ['user',  'login'],
        '/register'       => ['user',  'register'],
        '/logout'         => ['user',  'logout'],
        '/dashboard'      => ['home',  'dashboard'],

        // Clean URLs for dashboard by board type and filter
        '/dashboard/([a-z]+)/([a-z]+)' => ['home', 'dashboard_type_filter'],  // e.g. /dashboard/dream/archived
        '/dashboard/([a-z]+)'          => ['home', 'dashboard_type'],         // e.g. /dashboard/vision

        '/dreams/new'     => ['dream', 'create'],
        '/dreams/store'   => ['dream', 'store'],
        '/dreams/([A-Za-z0-9]{6,10})' => ['dream', 'show'],
        '/dreams/([A-Za-z0-9]{6,16})/edit' => ['dream', 'edit'],
        '/dreams/update'                   => ['dream', 'update'],   // legacy non-AJAX fallback
        '/dashboard/archived'   => ['home','archived'],
        '/dashboard/trash'      => ['home','trash'],

        // Clean URLs for board-type dashboards
        '/dashboard'                                => ['home', 'dashboard'], // default = dream + active
        '/dashboard/([a-z]+)'                       => ['home', 'dashboard_type'],
        '/dashboard/([a-z]+)/archived'              => ['home', 'dashboard_type_archived'],
        '/dashboard/([a-z]+)/trash'                 => ['home', 'dashboard_type_trash'],

        '/dreams/new'           => ['dream','create'],
        '/dreams/store'         => ['dream','store'],
        '/dreams/([A-Za-z0-9]{6,16})'             => ['dream','show'],
        '/dreams/([A-Za-z0-9]{6,16})/edit'        => ['dream','edit'],
        '/dreams/([A-Za-z0-9]{6,16})/archive'     => ['dream','archive'],
        '/dreams/([A-Za-z0-9]{6,16})/unarchive'   => ['dream','unarchive'],
        '/dreams/([A-Za-z0-9]{6,16})/delete'      => ['dream','destroy'],
        '/dreams/([A-Za-z0-9]{6,16})/restore'     => ['dream','restore'],

        // Vision routes
        '/visions/new'                        => ['vision', 'create'],
        '/visions/store'                      => ['vision', 'store'],
        '/visions/([A-Za-z0-9]{6,16})'        => ['vision', 'show'],
        '/visions/([A-Za-z0-9]{6,16})/edit'   => ['vision', 'edit'],
        '/visions/update'                     => ['vision', 'update'],
        '/visions/([A-Za-z0-9]{6,16})/archive'   => ['vision', 'archive'],
        '/visions/([A-Za-z0-9]{6,16})/unarchive' => ['vision', 'unarchive'],
        '/visions/([A-Za-z0-9]{6,16})/delete'    => ['vision', 'destroy'],
        '/visions/([A-Za-z0-9]{6,16})/restore'   => ['vision', 'restore'],

        // New AJAX endpoints for visions
        '/api/visions/([A-Za-z0-9]{6,16})/save'    => ['vision', 'ajax_save'],
        '/api/visions/update-basics'               => ['vision', 'updateBasics'],
		// Overlay: return HTML partial for section
        '/visions/([A-Za-z0-9]{6,16})/overlay/([a-z]+)' => ['vision','overlay'],
        // AJAX save for overlay sections (e.g. basics, relations, goals…)
        '/api/visions/([A-Za-z0-9]{6,16})/([a-z]+)'     => ['vision','saveSection'],

    ];

        foreach ($routes as $pattern => $target) {
            if (preg_match('@^' . $pattern . '$@', $uri, $matches)) {

                // Locate controller file
                $ctrlFile = __DIR__ . '/../controllers/' . $target[0] . '.php';
                if (!file_exists($ctrlFile)) {
                    break; // fall through to 404
                }

                require_once $ctrlFile;
                $class = $target[0] . '_controller';

                // Call the mapped method with any captured params
                if (is_callable([$class, $target[1]])) {
                    call_user_func_array(
                        [$class, $target[1]],
                        array_slice($matches, 1) // pass regex captures
                    );
                    return;
                }
            }
        }

        // No match → 404
        http_response_code(404);
        echo '404 - Not Found';
    }
