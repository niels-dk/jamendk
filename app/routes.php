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
        '/api/documents/([a-f0-9]{32})/trip'   => ['document','update_trip'],
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
		
		// (Removed dead '/api/groups' routes — controllers/group.php never
		//  existed, so they 404'd; group creation happens via
		//  document.groups_create and media.setGroup instead.)

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
		// Arrows/connectors are stored as canvas_items (kind='connector'),
		// so delete uses the standard /canvas/items/{id}/delete route above.
		'/api/moods/([A-Za-z0-9]{6,16})/arrows'                       => ['canvas','createArrow'], // POST

		// Board-scoped media (Mood canvas)
		'/api/moods/([A-Za-z0-9]{6,16})/media'						  => ['media','listForMood'], // GET

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
        // Sharing filters (hyphens don't match the generic [a-z]+ catch-alls)
        '/dashboard/(dream|dreams|vision|visions|mood|moods|trip|trips)/(shared-with-me|shared-by-me)'
                               => ['home', 'dashboard_type_filter'],
        // Mark "new shares" notice as seen
        '/api/shares/seen'     => ['user', 'sharesSeen'],
        // Handoffs: collaborator sends a board back to its owner
        '/api/visions/([A-Za-z0-9]{6,16})/handoff' => ['vision', 'handoff'],
        '/api/handoffs/([0-9]+)/ack'               => ['vision', 'ackHandoff'],

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
        '/dreams/([A-Za-z0-9]{6,16})/promote'      => ['dream', 'promote'],

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
        // ── Account (own profile) ─────────────────────────────────────────────
        '/account'                                 => ['user', 'account'],

        // ── Teams (private collaborator groups) ───────────────────────────────
        '/teams'                                   => ['team', 'index'],
        '/api/teams'                               => ['team', 'listMine'],
        '/api/teams/create'                        => ['team', 'create'],
        '/api/teams/([0-9]+)/rename'               => ['team', 'rename'],
        '/api/teams/([0-9]+)/delete'               => ['team', 'deleteTeam'],
        '/api/teams/([0-9]+)/leave'                => ['team', 'leave'],
        '/api/teams/([0-9]+)/members/add'          => ['team', 'addMember'],
        '/api/teams/([0-9]+)/members/([0-9]+)/role'   => ['team', 'setMemberRole'],
        '/api/teams/([0-9]+)/members/([0-9]+)/delete' => ['team', 'removeMember'],

        // ── Admin (site administration, require_admin inside) ────────────────
        '/admin/users'                             => ['admin', 'users'],
        '/admin/users/([0-9]+)/role'               => ['admin', 'setRole'],
        '/admin/users/([0-9]+)/password'           => ['admin', 'setPassword'],
        '/admin/users/([0-9]+)/delete'             => ['admin', 'deleteUser'],
        '/admin/users/([0-9]+)/impersonate'        => ['admin', 'impersonate'],
        '/admin/return'                            => ['admin', 'stopImpersonate'],

        // ── Trips ─────────────────────────────────────────────────────────────
        // /trips/{slug}   authenticated preview (owner + collaborators)
        // /t/{token}      public share URL (unguessable, optional expiry)
        '/trips/([A-Za-z0-9]{6,16})'               => ['trip', 'show'],
        '/trips/([A-Za-z0-9]{6,16})/download'      => ['trip', 'download'],
        '/t/([A-Za-z0-9]{16,64})'                  => ['trip', 'showByToken'],
        '/t/([A-Za-z0-9]{16,64})/download'         => ['trip', 'downloadByToken'],

        // ── Vision AJAX (kept as-is) ──────────────────────────────────────────
        '/api/visions/([A-Za-z0-9]{6,16})/save'    => ['vision', 'ajax_save'],
        '/api/visions/update-basics'               => ['vision', 'updateBasics'],
        '/visions/([A-Za-z0-9]{6,16})/overlay/([a-z]+)'
                                                   => ['vision', 'overlay'],
        // Goals & Milestones (must come before generic /api/visions/{slug}/{section})
        '/api/visions/([A-Za-z0-9]{6,16})/goals'                 => ['vision', 'listGoals'],
        // Trip share-link management (token + expiry; hyphen keeps it out of
        // the generic {section} catch-all anyway, but be explicit)
        '/api/visions/([A-Za-z0-9]{6,16})/trip-share'            => ['vision', 'tripShare'],

        // Itinerary (day-by-day trip schedule)
        '/api/visions/([A-Za-z0-9]{6,16})/itinerary'                 => ['vision', 'listItinerary'],
        '/api/visions/([A-Za-z0-9]{6,16})/itinerary/create'          => ['vision', 'createItineraryItem'],
        '/api/visions/([A-Za-z0-9]{6,16})/itinerary/([0-9]+)'        => ['vision', 'updateItineraryItem'],
        '/api/visions/([A-Za-z0-9]{6,16})/itinerary/([0-9]+)/delete' => ['vision', 'deleteItineraryItem'],

        // Shots (capture list — what to film, where, when, how)
        '/api/visions/([A-Za-z0-9]{6,16})/shots'                 => ['vision', 'listShots'],
        '/api/visions/([A-Za-z0-9]{6,16})/shots/create'          => ['vision', 'createShot'],
        '/api/visions/([A-Za-z0-9]{6,16})/shots/([0-9]+)'        => ['vision', 'updateShot'],
        '/api/visions/([A-Za-z0-9]{6,16})/shots/([0-9]+)/status' => ['vision', 'setShotStatus'],
        '/api/visions/([A-Za-z0-9]{6,16})/shots/([0-9]+)/delete' => ['vision', 'deleteShot'],

        // Roles & sharing (must come before the generic {section} catch-all)
        '/api/visions/([A-Za-z0-9]{6,16})/roles'                 => ['vision', 'listRoles'],
        '/api/visions/([A-Za-z0-9]{6,16})/roles/add'             => ['vision', 'addRole'],
        '/api/visions/([A-Za-z0-9]{6,16})/roles/add-team'        => ['vision', 'addTeamRoles'],
        '/api/visions/([A-Za-z0-9]{6,16})/roles/([0-9]+)'        => ['vision', 'updateRole'],
        '/api/visions/([A-Za-z0-9]{6,16})/roles/([0-9]+)/delete' => ['vision', 'removeRole'],
        '/api/visions/([A-Za-z0-9]{6,16})/goals/create'          => ['vision', 'createGoal'],
        '/api/visions/([A-Za-z0-9]{6,16})/goals/([0-9]+)'        => ['vision', 'updateGoal'],
        '/api/visions/([A-Za-z0-9]{6,16})/goals/([0-9]+)/get'    => ['vision', 'getGoal'],
        '/api/visions/([A-Za-z0-9]{6,16})/goals/([0-9]+)/delete' => ['vision', 'deleteGoal'],
        '/api/visions/([A-Za-z0-9]{6,16})/goals/([0-9]+)/resolve' => ['vision', 'resolveGoal'],
        '/api/visions/([A-Za-z0-9]{6,16})/goals/([0-9]+)/return'  => ['vision', 'returnGoal'],
        '/api/visions/([A-Za-z0-9]{6,16})/goals/([0-9]+)/reopen'  => ['vision', 'reopenGoal'],
        '/api/visions/([A-Za-z0-9]{6,16})/goals/([0-9]+)/comments' => ['vision', 'listGoalComments'],
        '/api/visions/([A-Za-z0-9]{6,16})/goals/([0-9]+)/comments/add' => ['vision', 'addGoalComment'],
        '/api/notifications/([0-9]+)/ack'                        => ['user', 'ackNotification'],
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
