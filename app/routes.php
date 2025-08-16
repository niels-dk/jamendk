<?php
/**
 * Tiny router that maps path-regex => [controller, method]
 * ─────────────────────────────────────────────────────────
 *  • Add new routes by appending regex patterns to $routes.
 *  • Regex delimiters are added automatically by preg_match in the loop.
 */

function route(string $uri): void
{
    $routes = [
        // ── Home / Auth ────────────────────────────────────────────────────────
        ''                    => ['home', 'index'],
        '/'                   => ['home', 'index'],
        '/index\.php'         => ['home', 'index'],

        '/login'              => ['user', 'login'],
        '/register'           => ['user', 'register'],
        '/logout'             => ['user', 'logout'],

		'/api/moods/search'                                  => ['vision','searchMoods'],
		'/api/visions/([A-Za-z0-9]{6,16})/relations'         => ['vision','saveRelations'],
		'/api/visions/([A-Za-z0-9]{6,16})/relations/mood'    => ['vision','removeMood'],
		'/api/visions/([A-Za-z0-9]{6,16})/budget'            => ['vision','saveBudget'],
		'/api/currencies'                                    => ['vision','currencies'],
		'/api/visions/([A-Za-z0-9]{6,16})/contacts'          => ['vision','listContacts'],
		'/api/visions/([A-Za-z0-9]{6,16})/contacts/create'   => ['vision','createContact'],
		'/api/visions/([A-Za-z0-9]{6,16})/contacts/([0-9]+)' => ['vision','updateContact'],
		'/api/visions/([A-Za-z0-9]{6,16})/contacts/([0-9]+)/delete' => ['vision','deleteContact'],
		'/api/visions/([A-Za-z0-9]{6,16})/contacts/([0-9]+)/get'    => ['vision','getContact'],

        // ── General Dashboard ─────────────────────────────────────────────────
        '/dashboard'          => ['home', 'dashboard'],

        // New structure: pluralized board dashboards under /dashboard/<type>
        // e.g. /dashboard/dreams, /dashboard/visions, /dashboard/moods, /dashboard/trips
        '/dashboard/(dreams|visions|moods|trips)/(archived|trash)'
                               => ['home', 'dashboard_type_filter'],
        '/dashboard/(dreams|visions|moods|trips)'
                               => ['home', 'dashboard_type'],

        // Keep global buckets (if used by UI)
        '/dashboard/archived' => ['home', 'archived'],
        '/dashboard/trash'    => ['home', 'trash'],

        // Backward-compat: accept old singular paths (e.g. /dashboard/vision)
        // Controller can treat singular/plural the same.
        '/dashboard/(dream|vision|mood|trip)/(archived|trash)'
                               => ['home', 'dashboard_type_filter'],
        '/dashboard/(dream|vision|mood|trip)'
                               => ['home', 'dashboard_type'],

        // Also keep a generic fallback capture for any other board types
        '/dashboard/([a-z]+)/([a-z]+)'
                               => ['home', 'dashboard_type_filter'], // e.g. /dashboard/unknown/archived
        '/dashboard/([a-z]+)'  => ['home', 'dashboard_type'],

        // ── Dreams CRUD ───────────────────────────────────────────────────────
        // (paths unchanged; only the dashboard listing moved under /dashboard/dreams)
        '/dreams/new'                              => ['dream', 'create'],
        '/dreams/store'                            => ['dream', 'store'],
        '/dreams/update'                           => ['dream', 'update'],   // legacy non-AJAX fallback
        '/dreams/([A-Za-z0-9]{6,16})'              => ['dream', 'show'],
        '/dreams/([A-Za-z0-9]{6,16})/edit'         => ['dream', 'edit'],
        '/dreams/([A-Za-z0-9]{6,16})/archive'      => ['dream', 'archive'],
        '/dreams/([A-Za-z0-9]{6,16})/unarchive'    => ['dream', 'unarchive'],
        '/dreams/([A-Za-z0-9]{6,16})/delete'       => ['dream', 'destroy'],
        '/dreams/([A-Za-z0-9]{6,16})/restore'      => ['dream', 'restore'],

        // ── Visions CRUD ──────────────────────────────────────────────────────
        '/visions/new'                             => ['vision', 'create'],
        '/visions/store'                           => ['vision', 'store'],
        '/visions/update'                          => ['vision', 'update'],
        '/visions/([A-Za-z0-9]{6,16})'             => ['vision', 'show'],
        '/visions/([A-Za-z0-9]{6,16})/edit'        => ['vision', 'edit'],
        '/visions/([A-Za-z0-9]{6,16})/archive'     => ['vision', 'archive'],
        '/visions/([A-Za-z0-9]{6,16})/unarchive'   => ['vision', 'unarchive'],
        '/visions/([A-Za-z0-9]{6,16})/delete'      => ['vision', 'destroy'],
        '/visions/([A-Za-z0-9]{6,16})/restore'     => ['vision', 'restore'],

        // ── Vision AJAX (kept as-is) ──────────────────────────────────────────
        '/api/visions/([A-Za-z0-9]{6,16})/save'    => ['vision', 'ajax_save'],
        '/api/visions/update-basics'               => ['vision', 'updateBasics'],

        // Overlay: return HTML partial for section
        '/visions/([A-Za-z0-9]{6,16})/overlay/([a-z]+)'
                                                   => ['vision', 'overlay'],

        // AJAX save for overlay sections (e.g. basics, relations, goals…)
        '/api/visions/([A-Za-z0-9]{6,16})/([a-z]+)'
                                                   => ['vision', 'saveSection'],
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
