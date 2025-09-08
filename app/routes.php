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

        // Upload API (POST)
        '/api/visions/([A-Za-z0-9]{6,16})/documents'        => ['document','upload'],

        '/api/documents/([a-f0-9]{32})/status' => ['document','update_status'],
        '/api/visions/([A-Za-z0-9]{6,16})/groups' => ['document','groups_list'],   // GET
        '/api/visions/([A-Za-z0-9]{6,16})/groups:create' => ['document','groups_create'], // POST
        '/api/documents/([a-f0-9]{32})/group'     => ['document','update_group'],  // POST
		
		// Tags & Groups (global � not under /visions/{slug})
		'/api/tags'                    => ['media','tags_list'],      // GET
		//'/api/media/([0-9]+)/tags'     => ['media','update_tags'],    // POST
		//'/api/groups'                  => ['media','groups_list'],    // GET
		//'/api/media/([0-9]+)/group'    => ['media','update_group'],   // POST
		//'/api/media/([0-9]+)/group'    => ['media','group'],   // POST

		// Groups (creator/mood scoped)
		'/api/moods/([A-Za-z0-9]{6,16})/groups' => ['media','groups_list'],
		
		// Global groups list/create
		'/api/groups'            => ['group', 'list'],      // GET
		'/api/groups/create'     => ['group', 'create'],    // POST

		// Assign or create group for a media item
		'/api/media/([0-9]+)/group' => ['media', 'setGroup'], // POST


		// Media tags (GET returns tags, POST saves tags)
		'/api/media/([0-9]+)/tags' => ['media','tags'],
		
		// Canvas API endpoints (list, create, update, delete and bulk update)
		// These endpoints manage items on the canvas associated with a mood board.
		'/api/moods/([A-Za-z0-9]{6,16})/canvas/items'                 => ['canvas','listItems'],
		'/api/moods/([A-Za-z0-9]{6,16})/canvas/items/create'          => ['canvas','createItem'],
		'/api/moods/([A-Za-z0-9]{6,16})/canvas/items/([0-9]+)'        => ['canvas','updateItem'],
		'/api/moods/([A-Za-z0-9]{6,16})/canvas/items/([0-9]+)/delete' => ['canvas','deleteItem'],
		'/api/moods/([A-Za-z0-9]{6,16})/canvas/items/bulk'            => ['canvas','bulkUpdate'],
		//'/api/moods/([A-Za-z0-9]{6,16})/arrows'             		  => ['canvas','saveArrow'], 
		//'/api/moods/([A-Za-z0-9]{6,16})/arrows/([0-9]+):delete' 	  => ['canvas','deleteArrow'],
		'/api/moods/([A-Za-z0-9]{6,16})/arrows'                       => ['canvas','createArrow'], // POST
		'/api/moods/([A-Za-z0-9]{6,16})/arrows/([0-9]+):delete'       => ['mood_arrows','delete'], // POST
		'/api/moods/([A-Za-z0-9]{6,16})/items/([0-9]+):delete' 		  => ['mood_items','delete'],

        // Budget endpoints
        '/api/visions/([A-Za-z0-9]{6,16})/budget' => ['vision','getBudget'],  // GET
        '/api/visions/([A-Za-z0-9]{6,16})/budget/get' => ['vision','getBudget'],  // GET prefill
        '/api/visions/([A-Za-z0-9]{6,16})/budget' => ['vision','saveBudget'], // POST

        // Media library endpoints
        '/api/visions/([A-Za-z0-9]{6,16})/media:upload'   => ['media','upload'],
        '/api/visions/([A-Za-z0-9]{6,16})/media:link'     => ['media','link'],
        '/api/visions/([A-Za-z0-9]{6,16})/media'          => ['media','list'],   // GET
        '/api/visions/([A-Za-z0-9]{6,16})/media:delete'   => ['media','delete'],
        '/api/moods/([A-Za-z0-9]{6,16})/library:attach'   => ['media','attach'],
        '/api/moods/([A-Za-z0-9]{6,16})/library:detach'   => ['media','detach'],

        // 🔹 NEW: global tags list + set tags on a media
        '/api/tags'                                => ['media','tags_list'],     // GET
        //'/api/media/([0-9]+)/tags'                 => ['media','update_tags'],   // POST

		// Global Media API (search/list) � used by mood-canvas-media.js overlay
		'/api/media'                   => ['media','listAll'],   // GET  ?q=&limit=&offset=&type=
		
        // ── General Dashboard ─────────────────────────────────────────────────
        '/dashboard'          => ['home', 'dashboard_overview'],

        // New structure: pluralized board dashboards under /dashboard/<type>
        '/dashboard/(dreams|visions|moods|trips)/(archived|trash)'
                               => ['home', 'dashboard_type_filter'],
        '/dashboard/(dreams|visions|moods|trips)'
                               => ['home', 'dashboard_type'],

        // Keep global buckets (if used by UI)
        '/dashboard/archived' => ['home', 'archived'],
        '/dashboard/trash'    => ['home', 'trash'],

        // Backward-compat: accept old singular paths
        '/dashboard/(dream|vision|mood|trip)/(archived|trash)'
                               => ['home', 'dashboard_type_filter'],
        '/dashboard/(dream|vision|mood|trip)'
                               => ['home', 'dashboard_type'],
        '/dashboard/([a-z]+)/([a-z]+)' => ['home', 'dashboard_type_filter'],
        '/dashboard/([a-z]+)'          => ['home', 'dashboard_type'],

        // ── Dreams CRUD ───────────────────────────────────────────────────────
        '/dreams/new'                              => ['dream', 'create'],
        '/dreams/store'                            => ['dream', 'store'],
        '/dreams/update'                           => ['dream', 'update'],
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

        // Download endpoint (GET)
        '/documents/([a-f0-9]{32})/download'                => ['document','download'],

        // Overlay view for documents
        '/visions/([A-Za-z0-9]{6,16})/overlay/documents'   => ['document','overlay'],

        // ── Mood Boards CRUD ─────────────────────────────────────────────────
        '/moods/new'                               => ['mood', 'create'],
        '/moods/update'                            => ['mood', 'update'],
        '/moods/([A-Za-z0-9]{6,16})'               => ['mood', 'show'],
		
		'/moods/([A-Za-z0-9]{6,16})/canvas'        => ['mood', 'canvas'],
		'/moods/([A-Za-z0-9]{6,16})/media'         => ['mood', 'editMedia'],
        '/moods/([A-Za-z0-9]{6,16})/edit'          => ['mood', 'edit'],
        '/moods/([A-Za-z0-9]{6,16})/archive'       => ['mood', 'archive'],
        '/moods/([A-Za-z0-9]{6,16})/unarchive'     => ['mood', 'unarchive'],
        '/moods/([A-Za-z0-9]{6,16})/delete'        => ['mood', 'destroy'],
        '/moods/([A-Za-z0-9]{6,16})/restore'       => ['mood', 'restore'],
		
		/*
        '/moods/([A-Za-z0-9]{6,16})/edit'          => ['mood', 'edit'],
        '/moods/([A-Za-z0-9]{6,16})/archive'       => ['mood', 'archive'],
        '/moods/([A-Za-z0-9]{6,16})/unarchive'     => ['mood', 'unarchive'],
        '/moods/([A-Za-z0-9]{6,16})/delete'        => ['mood', 'destroy'],
        '/moods/([A-Za-z0-9]{6,16})/restore'       => ['mood', 'restore'],
		*/
        // ── Vision AJAX (kept as-is) ──────────────────────────────────────────
        '/api/visions/([A-Za-z0-9]{6,16})/save'    => ['vision', 'ajax_save'],
        '/api/visions/update-basics'               => ['vision', 'updateBasics'],
        '/visions/([A-Za-z0-9]{6,16})/overlay/([a-z]+)'
                                                   => ['vision', 'overlay'],
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
