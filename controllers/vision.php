<?php
// controllers/vision.php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../models/vision.php';

class vision_controller
{
    /** GET /visions/new – create draft and redirect */
    public static function create(): void
    {
        require_login();
        global $db, $currentUserId;
        $draft  = vision_model::createDraft($db, (int)$currentUserId);
        header("Location: /visions/{$draft['slug']}/edit");
        exit;
    }

    /** POST /visions/store – legacy create (active) */
    public static function store(): void
    {
        require_login();
        global $db, $currentUserId;
        $title = trim($_POST['title'] ?? '');
        $desc  = $_POST['description'] ?? '';
        $id    = vision_model::create($db, (int)$currentUserId, $title ?: null, $desc ?: null);
        // save anchors (legacy)
        $anchors = $_POST['anchors'] ?? [];
        $kv = [];
        foreach ($anchors as $row) {
            $kv[] = ['key' => $row['key'] ?? '', 'value' => $row['value'] ?? ''];
        }
        vision_model::replaceAnchors($db, $id, $kv);
        $s = $db->prepare("SELECT slug FROM visions WHERE id=?");
        $s->execute([(int)$id]);
        $slug = (string)$s->fetchColumn();
        header("Location: /visions/$slug");
        exit;
    }

    /** GET /visions/{slug} – show vision (owner-only) */
    public static function show(string $slug): void
	{
		require_login();
		global $db, $currentUserId;

		// Fetch by slug (exclude deleted)
		$stmt = $db->prepare("
			SELECT id, slug, title, description, created_at, updated_at, archived, deleted_at, dream_id, user_id
			FROM visions
			WHERE slug = ? AND deleted_at IS NULL
			LIMIT 1
		");
		$stmt->execute([$slug]);
		$vision = $stmt->fetch(PDO::FETCH_ASSOC);
		require_vision($db, $vision, 'view');

		$anchors = vision_model::getAnchors($db, (int)$vision['id']);

		// Source dream, if this vision was promoted from one
		$sourceDream = null;
		if (!empty($vision['dream_id'])) {
			$ds = $db->prepare("SELECT slug, title FROM dream_boards
								  WHERE id = ? AND deleted_at IS NULL LIMIT 1");
			$ds->execute([(int)$vision['dream_id']]);
			$sourceDream = $ds->fetch(PDO::FETCH_ASSOC) ?: null;
		}

		// Goals assigned to the current user on this board (open + returned),
		// so collaborators — including Viewers — can resolve or send back
		// right from the show page.
		$myTasks = [];
		try {
			$ts = $db->prepare("
				SELECT id, title, description, status, priority, due_date, assignment_status
				  FROM vision_goals
				 WHERE vision_id = ? AND assigned_user_id = ?
				   AND status NOT IN ('done','cancelled')
				 ORDER BY (assignment_status='returned') ASC,
						  priority ASC, (due_date IS NULL) ASC, due_date ASC, id ASC
			");
			$ts->execute([(int)$vision['id'], (int)$currentUserId]);
			$myTasks = $ts->fetchAll(PDO::FETCH_ASSOC);
			if ($myTasks) {
				$gIds = array_map('intval', array_column($myTasks, 'id'));
				$in   = implode(',', array_fill(0, count($gIds), '?'));
				$mm = $db->prepare("SELECT goal_id, text, done, due_date
									  FROM vision_goal_milestones
									 WHERE goal_id IN ($in)
									 ORDER BY sort_order, id");
				$mm->execute($gIds);
				$msByGoal = [];
				foreach ($mm->fetchAll(PDO::FETCH_ASSOC) as $m) {
					$msByGoal[(int)$m['goal_id']][] = $m;
				}
				foreach ($myTasks as &$t) { $t['milestones'] = $msByGoal[(int)$t['id']] ?? []; }
				unset($t);
			}
		} catch (\Throwable $e) { /* assignment columns not migrated yet */ }

		// Page vars for the layout
		$pageTitle = $vision['title'] ?: 'Vision';
		$pageDescription = $vision['description'] ?: 'Vision';
		$noSidebar = true; // matches your Dream “show” layout

		// Render view into $content then include the site layout
		ob_start();
		include __DIR__ . '/../views/vision_show.php';
		$content = ob_get_clean();
		include __DIR__ . '/../views/layout.php';
	}


    /** GET /visions/{slug}/edit – edit form */
    public static function edit(string $slug): void
    {
        require_login();
        global $db;
        $vision = vision_model::get($db, $slug);
        require_vision($db, $vision, 'edit');
        $st = $db->prepare("SELECT * FROM vision_presentation WHERE vision_id=?");
        $st->execute([(int)$vision['id']]);
        $presentationFlags = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        // flatten anchors
        $kv = [];
        $map = vision_model::getAnchors($db, (int)$vision['id']);
        foreach ($map as $k => $vals) {
            foreach ($vals as $v) $kv[] = ['key'=>$k,'value'=>$v];
        }

        // Sidebar counts/state — surfaced as small badges next to each item so
        // users can see what's populated without opening every overlay.
        $vid = (int)$vision['id'];
        $sidebarBadges = [
            'relations' => !empty($vision['mood_id']) ? 1 : 0,
            'goals'     => 0,
            'budget'    => 0,
            'roles'     => 0,
            'contacts'  => 0,
            'documents' => 0,
            'workflow'  => (!empty($vision['workflow_status']) && $vision['workflow_status'] !== 'not_started') ? 1 : 0,
        ];
        try {
            $cnt = function (string $sql) use ($db, $vid): int {
                $st = $db->prepare($sql);
                $st->execute([$vid]);
                return (int)$st->fetchColumn();
            };
            $sidebarBadges['goals']     = $cnt("SELECT COUNT(*) FROM vision_goals WHERE vision_id=? AND status != 'cancelled'");
            $sidebarBadges['itinerary'] = $cnt("SELECT COUNT(*) FROM vision_itinerary WHERE vision_id=?");
            $sidebarBadges['contacts']  = $cnt("SELECT COUNT(*) FROM vision_contacts WHERE vision_id=?");
            $sidebarBadges['documents'] = $cnt("SELECT COUNT(*) FROM vision_documents WHERE vision_id=?");
            $sidebarBadges['budget']    = $cnt("SELECT COUNT(*) FROM vision_budget WHERE vision_id=?");
        } catch (\Throwable $e) { /* tables may not exist yet — keep zeros */ }

        ob_start();
        include __DIR__.'/../views/vision_form.php';
        $content = ob_get_clean();
        $boardType = 'vision';
        include __DIR__.'/../views/layout.php';
    }

    /** POST /visions/update – legacy update */
    public static function update(): void
    {
        require_login();
        global $db;
        $id = (int)($_POST['vision_id'] ?? 0);
        if (!$id) { http_response_code(400); echo 'Missing ID'; return; }
        $vq = $db->prepare("SELECT * FROM visions WHERE id = ? LIMIT 1");
        $vq->execute([$id]);
        require_vision($db, $vq->fetch(PDO::FETCH_ASSOC) ?: null, 'edit');
        $title = trim($_POST['title'] ?? '');
        $desc  = $_POST['description'] ?? '';
        vision_model::update($db, $id, $title, $desc);
        // update anchors
        $anchors = $_POST['anchors'] ?? [];
        $kv=[];
        foreach ($anchors as $row) {
            $kv[] = ['key'=>$row['key'] ?? '', 'value'=>$row['value'] ?? ''];
        }
        vision_model::replaceAnchors($db, $id, $kv);
        $s = $db->prepare("SELECT slug FROM visions WHERE id=?");
        $s->execute([(int)$id]);
        $slug = (string)$s->fetchColumn();
        header("Location: /visions/$slug");
        exit;
    }

    /** archive/unarchive/delete/restore */
    public static function archive(string $slug): void
    {
        require_login();
        global $db;
        $v = vision_model::get($db, $slug);
        require_vision($db, $v, 'manage');
        vision_model::setArchived($db, (int)$v['id'], true);
        header('Location: /dashboard/vision'); exit;
    }
    public static function unarchive(string $slug): void
    {
        require_login();
        global $db;
        $v = vision_model::get($db, $slug);
        require_vision($db, $v, 'manage');
        vision_model::setArchived($db, (int)$v['id'], false);
        header('Location: /dashboard/vision/archived'); exit;
    }
    public static function destroy(string $slug): void
    {
        require_login();
        global $db;
        $v = vision_model::get($db, $slug);
        require_vision($db, $v, 'manage');
        vision_model::softDelete($db, (int)$v['id']);
        header('Location: /dashboard/vision'); exit;
    }
    public static function restore(string $slug): void
    {
        require_login();
        global $db;
        $v = vision_model::get($db, $slug);
        require_vision($db, $v, 'manage');
        vision_model::restore($db, (int)$v['id']);
        header('Location: /dashboard/vision/trash'); exit;
    }

    /** POST /api/visions/update-basics (legacy basics save) */
    public static function updateBasics(): void
	{
		api_require_login();
		// Supports both AJAX (JSON) and normal form post (redirect kept as-is if you already do that).
		header('Content-Type: application/json');

		try {
			global $db, $currentUserId;

			// Accept either vision_id (int) or slug (string)
			$visionId = isset($_POST['vision_id']) ? (int)$_POST['vision_id'] : 0;
			$slug     = isset($_POST['slug']) ? trim($_POST['slug']) : '';

			if (!$visionId && $slug !== '') {
				$s = $db->prepare("SELECT id FROM visions WHERE slug=? LIMIT 1");
				$s->execute([$slug]);
				$visionId = (int)($s->fetchColumn() ?: 0);
			}
			if (!$visionId) {
				http_response_code(400);
				echo json_encode(['success' => false, 'error' => 'Missing Vision ID/slug']);
				return;
			}

			// Verify edit permission before any mutation (owner, shared editor, or admin)
			$own = $db->prepare("SELECT * FROM visions WHERE id = ? LIMIT 1");
			$own->execute([$visionId]);
			$visionRow = $own->fetch(PDO::FETCH_ASSOC) ?: null;
			if (!$visionRow || !vision_can($db, $visionRow, 'edit')) {
				http_response_code(404);
				echo json_encode(['success' => false, 'error' => 'Not found']);
				return;
			}

			// Dates (nullable)
			$start = trim((string)($_POST['start_date'] ?? ''));
			$end   = trim((string)($_POST['end_date']   ?? ''));

			$start = ($start === '') ? null : $start; // expect YYYY-MM-DD
			$end   = ($end   === '') ? null : $end;

			if ($start !== null && $end !== null && strcmp($end, $start) < 0) {
				http_response_code(422);
				echo json_encode(['success' => false, 'error' => 'End date cannot be before start date']);
				return;
			}

			// Visibility flags (checkboxes)
			$showOnBoardDefault = isset($_POST['show_on_board_default']) ? 1 : 0;
			$showOnDashboard    = isset($_POST['show_on_dashboard'])      ? 1 : 0;
			$showOnTrip         = isset($_POST['show_on_trip'])           ? 1 : 0;

			// Update statement (keeps other columns untouched)
			$q = $db->prepare("
				UPDATE visions
				   SET start_date = :start_date,
					   end_date   = :end_date,
					   show_on_board_default = :board_default,
					   show_on_dashboard      = :dash,
					   show_on_trip           = :trip
				 WHERE id = :id
				 LIMIT 1
			");
			$q->execute([
				':start_date'    => $start,
				':end_date'      => $end,
				':board_default' => $showOnBoardDefault,
				':dash'          => $showOnDashboard,
				':trip'          => $showOnTrip,
				':id'            => $visionId,
			]);

			echo json_encode(['success' => true]);
		} catch (Throwable $e) {
			http_response_code(500);
			echo json_encode(['success' => false, 'error' => 'Update failed', 'detail' => $e->getMessage()]);
		}
	}


    /** POST /api/visions/{slug}/save – save title, desc, anchors */
    public static function ajax_save(string $slug): void
    {
        header('Content-Type: application/json');
        global $db;
        try {
            $vision = vision_model::get($db, $slug);
            api_require_vision($db, $vision, 'edit');
            $payload = json_decode(file_get_contents('php://input'), true) ?: [];
            $update=[];
            $result=['title'=>false,'description'=>false,'anchors'=>false,'statusChanged'=>false];

            // Title
            if (array_key_exists('title', $payload)) {
                $t=trim((string)$payload['title']);
                $update['title'] = $t === '' ? null : $t;
                $result['title']=true;
            }
            // Description
            if (array_key_exists('description',$payload)) {
                $desc=trim((string)$payload['description']);
                if ($desc==='') { $update['description']=null; }
                else {
                    $allowed='<b><i><ul><ol><li><a><p><h1><h2><h3><br>';
                    $update['description'] = strip_tags($desc,$allowed);
                }
                $result['description']=true;
            }
            if ($update) vision_model::partialUpdate($db, (int)$vision['id'], $update);

            // Anchors
            if (isset($payload['anchors']) && is_array($payload['anchors'])) {
                $kv=[];
                foreach ($payload['anchors'] as $row) {
                    $key=trim((string)($row['key'] ?? ''));
                    $val=trim((string)($row['value'] ?? ''));
                    if ($key!=='' && $val!=='') $kv[]=['key'=>$key,'value'=>$val];
                }
                vision_model::replaceAnchors($db, (int)$vision['id'], $kv);
                $result['anchors']=true;
            }
            // Draft -> active flip
            if (($vision['status'] ?? 'draft')==='draft') {
                $newTitle = $update['title'] ?? $vision['title'];
                $newDesc  = $update['description'] ?? $vision['description'];
                if ($newTitle || $newDesc) {
                    vision_model::partialUpdate($db, (int)$vision['id'], ['status'=>'active']);
                    $result['statusChanged']=true;
                }
            }
            echo json_encode(['ok'=>true,'result'=>$result]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
        }
    }

    /** GET /visions/{slug}/overlay/{section} – return HTML partial */
    public static function overlay(string $slug, string $section): void
    {
        require_login();
        global $db, $currentUserId;
        $vision = vision_model::get($db, $slug);
        require_vision($db, $vision, 'edit');
        // load presentation flags so basics overlay can pre-check toggles
        $st = $db->prepare("SELECT * FROM vision_presentation WHERE vision_id=?");
        $st->execute([(int)$vision['id']]);
        $presentationFlags = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        // For relations overlay: resolve linked mood title (mood_id stores the slug)
        $linkedMood = null;
        if (!empty($vision['mood_id'])) {
            $ms = $db->prepare("SELECT slug, title FROM mood_boards WHERE slug=? LIMIT 1");
            $ms->execute([$vision['mood_id']]);
            $linkedMood = $ms->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        // For budget overlay: load existing budget row + line items
        $budget = null;
        $budgetItems = [];
        if ($section === 'budget') {
            $bs = $db->prepare("SELECT currency, amount_cents, show_on_dashboard, show_on_trip
                                FROM vision_budget WHERE vision_id = ? LIMIT 1");
            $bs->execute([(int)$vision['id']]);
            $budget = $bs->fetch(PDO::FETCH_ASSOC) ?: null;
            try {
                $bi = $db->prepare("SELECT id, label, amount_cents, paid
                                      FROM vision_budget_items
                                     WHERE vision_id = ? ORDER BY sort_order, id");
                $bi->execute([(int)$vision['id']]);
                $budgetItems = $bi->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) { /* not migrated yet */ }
        }
        $partial = __DIR__.'/../views/partials/overlay_'.$section.'.php';
        if (!file_exists($partial)) { http_response_code(404); echo 'Overlay not found'; return; }
        // include partial; it will echo HTML
		//print "loaded";
        include $partial;
    }

    /** POST /api/visions/{slug}/{section} – save overlay fields */
    public static function saveSection(string $slug, string $section): void
    {
        header('Content-Type: application/json');
        global $db;
        $vision = vision_model::get($db, $slug);
        api_require_vision($db, $vision, 'edit');
        $id=(int)$vision['id'];
        // differentiate by section
        switch ($section) {
            case 'workflow':
                $status = trim((string)($_POST['status'] ?? ''));
                $notes  = (string)($_POST['notes']  ?? '');
                $show   = !empty($_POST['show_workflow']) ? 1 : 0;
                $allowedStatus = ['not_started','in_progress','complete'];
                if (!in_array($status, $allowedStatus, true)) $status = 'not_started';
                try {
                    $db->prepare("UPDATE visions SET workflow_status=?, workflow_notes=?, updated_at=NOW() WHERE id=?")
                       ->execute([$status, $notes, $id]);
                    // mirror visibility flag into vision_presentation.workflow
                    $st = $db->prepare("SELECT vision_id FROM vision_presentation WHERE vision_id=?");
                    $st->execute([$id]);
                    if ($st->fetch()) {
                        $db->prepare("UPDATE vision_presentation SET `workflow`=? WHERE vision_id=?")
                           ->execute([$show, $id]);
                    } else {
                        $db->prepare("INSERT INTO vision_presentation
                            (vision_id, relations, goals, budget, roles, contacts, documents, workflow)
                            VALUES (?, 1, 1, 1, 1, 1, 1, ?)")
                           ->execute([$id, $show]);
                    }
                    echo json_encode(['success' => true]);
                } catch (\Throwable $e) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                return;
            case 'basics':
                $flag    = trim((string)($_POST['flag'] ?? ''));
                $enabled = (int)($_POST['enabled'] ?? 0);
                // Master switch: enables/disables the entire trip view.
                // First publish mints the share token so the link exists
                // the moment the switch flips on.
                if ($flag === 'trip_enabled') {
                    $db->prepare("UPDATE visions SET trip_enabled=? WHERE id=?")
                       ->execute([$enabled ? 1 : 0, $id]);
                    $share = null;
                    if ($enabled) {
                        try {
                            $tq = $db->prepare("SELECT trip_token, trip_token_expires_at FROM visions WHERE id=?");
                            $tq->execute([$id]);
                            $row = $tq->fetch(PDO::FETCH_ASSOC);
                            $token = $row['trip_token'] ?? null;
                            if (!$token) {
                                $token = bin2hex(random_bytes(16));
                                $db->prepare("UPDATE visions SET trip_token=? WHERE id=?")
                                   ->execute([$token, $id]);
                            }
                            $share = [
                                'token'      => $token,
                                'url'        => self::absoluteUrl('/t/' . $token),
                                'expires_at' => $row['trip_token_expires_at'] ?? null,
                            ];
                        } catch (\Throwable $e) { /* token columns not migrated yet */ }
                    }
                    echo json_encode(['success' => true, 'share' => $share]);
                    return;
                }
                $allowed = ['relations','itinerary','goals','budget','roles','contacts','documents','workflow'];
                if ($flag !== '' && in_array($flag, $allowed, true)) {
                    $st = $db->prepare("SELECT vision_id FROM vision_presentation WHERE vision_id=?");
                    $st->execute([$id]);
                    if ($st->fetch()) {
                        $db->prepare("UPDATE vision_presentation SET `$flag`=? WHERE vision_id=?")
                           ->execute([$enabled, $id]);
                    } else {
                        $cols = ['vision_id' => $id];
                        foreach ($allowed as $f) $cols[$f] = ($f === $flag) ? $enabled : 1;
                        $placeholders = implode(',', array_fill(0, count($cols), '?'));
                        $colNames = implode(',', array_map(fn($c) => "`$c`", array_keys($cols)));
                        $db->prepare("INSERT INTO vision_presentation ($colNames) VALUES ($placeholders)")
                           ->execute(array_values($cols));
                    }
                    echo json_encode(['success' => true]);
                    return;
                }
                $_POST['vision_id'] = $id;
                self::updateBasics();
                break;
			case 'documents':
				// no generic autosave; handled via upload API
				echo json_encode(['ok' => true]);
				break;

            // you can add cases for 'relations','goals','budget','roles','contacts','documents','workflow'
            // For now, just acknowledge success; client sends JSON or form-data
            default:
                echo json_encode(['ok'=>true]);
        }
    }
	
	/** Helper: resolve a vision by slug (fallback).
	 *  Must return the FULL row — the permission layer reads user_id, and a
	 *  partial row made every ownership check fail (contacts 404'd as empty). */
	private static function findVisionBySlug(PDO $db, string $slug): ?array
	{
		$st = $db->prepare("SELECT * FROM visions WHERE slug=? AND deleted_at IS NULL LIMIT 1");
		$st->execute([$slug]);
		return $st->fetch(PDO::FETCH_ASSOC) ?: null;
	}

	/** GET /api/moods/search?q=... */
	public static function searchMoods(): void
	{
		api_require_login();
		header('Content-Type: application/json');
		try {
			global $db, $currentUserId;
			$q = trim((string)($_GET['q'] ?? ''));
			if ($q === '') { echo json_encode([]); return; }

			$st = $db->prepare("SELECT slug AS id, title FROM mood_boards
								 WHERE user_id = ?
								   AND deleted_at IS NULL
								   AND (title LIKE ? OR slug LIKE ?)
								 ORDER BY title LIMIT 10");
			$like = '%' . $q . '%';
			$st->execute([(int)$currentUserId, $like, $like]);
			echo json_encode($st->fetchAll(PDO::FETCH_ASSOC));
		} catch (Throwable $e) {
			http_response_code(500);
			echo json_encode(['error' => $e->getMessage()]);
		}
	}

	/** POST /api/visions/{slug}/relations */
	public static function saveRelations(string $slug): void
	{
		header('Content-Type: application/json');
		try {
			global $db;

			// Prefer your model if present
			if (class_exists('vision_model') && method_exists('vision_model', 'get')) {
				$vision = vision_model::get($db, $slug);
			} else {
				$vision = self::findVisionBySlug($db, $slug);
			}
			api_require_vision($db, $vision, 'edit');

			$data   = $_POST ?: json_decode(file_get_contents('php://input'), true) ?: [];
			$moodId = isset($data['mood_id']) ? trim((string)$data['mood_id']) : null;
			$dash   = !empty($data['show_mood_on_dashboard']) ? 1 : 0;
			$trip   = !empty($data['show_mood_on_trip'])      ? 1 : 0;

			$st = $db->prepare("UPDATE visions
								   SET mood_id = :mood_id,
									   show_mood_on_dashboard = :dash,
									   show_mood_on_trip = :trip
								 WHERE id = :id LIMIT 1");
			$st->execute([
				':mood_id' => ($moodId !== null && $moodId !== '') ? $moodId : null,
				':dash'    => $dash,
				':trip'    => $trip,
				':id'      => (int)$vision['id'],
			]);

			echo json_encode(['success' => true]);
		} catch (Throwable $e) {
			http_response_code(500);
			echo json_encode(['error' => 'Save failed']);
		}
	}

	/** DELETE /api/visions/{slug}/relations/mood */
	public static function removeMood(string $slug): void
	{
		header('Content-Type: application/json');
		try {
			global $db;
			$vision = class_exists('vision_model') && method_exists('vision_model','get')
				? vision_model::get($db, $slug)
				: self::findVisionBySlug($db, $slug);

			api_require_vision($db, $vision, 'edit');

			$st = $db->prepare("UPDATE visions
								   SET mood_id=NULL, show_mood_on_dashboard=0, show_mood_on_trip=0
								 WHERE id=? LIMIT 1");
			$st->execute([(int)$vision['id']]);

			echo json_encode(['success' => true]);
		} catch (Throwable $e) {
			http_response_code(500);
			echo json_encode(['error' => 'Remove failed']);
		}
	}
	
	/** GET /api/visions/{slug}/budget */
	public static function getBudget(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;

		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'view');

		$st = $db->prepare("SELECT currency, amount_cents, show_on_dashboard, show_on_trip
							FROM vision_budget WHERE vision_id = ?");
		$st->execute([(int)$vision['id']]);
		$row = $st->fetch(PDO::FETCH_ASSOC);

		// Return empty defaults instead of 404 so the overlay can render cleanly.
		$out = $row ?: [
			'currency' => null,
			'amount_cents' => null,
			'show_on_dashboard' => 0,
			'show_on_trip' => 0,
		];
		$out['items'] = [];
		try {
			$it = $db->prepare("SELECT id, label, amount_cents, paid, show_on_trip
								  FROM vision_budget_items
								 WHERE vision_id = ? ORDER BY sort_order, id");
			$it->execute([(int)$vision['id']]);
			$out['items'] = $it->fetchAll(PDO::FETCH_ASSOC);
		} catch (\Throwable $e) { /* table not migrated yet */ }
		echo json_encode($out);
	}

	/** POST /api/visions/{slug}/budget */
	public static function saveBudget(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;

		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'edit');

		$cur   = strtoupper(trim($_POST['currency'] ?? ''));
		$cents = (int)($_POST['amount_cents'] ?? -1);
		$dash  = !empty($_POST['show_on_dashboard']) ? 1 : 0;
		$trip  = !empty($_POST['show_on_trip'])      ? 1 : 0;

		if ($cur === '' || !preg_match('/^[A-Z]{3}$/', $cur) || $cents < 0) {
			http_response_code(422);
			echo json_encode(['error' => 'Invalid currency or amount']);
			return;
		}

		// Optional line items: parallel arrays replace the whole set (same
		// pattern as goal milestones). When items exist, the stored total
		// becomes their sum so the two can never drift apart.
		$labels  = $_POST['item_labels']  ?? null;
		$amounts = $_POST['item_amounts'] ?? [];
		$paids   = $_POST['item_paids']   ?? [];

		if (is_array($labels)) {
			try {
				$db->prepare("DELETE FROM vision_budget_items WHERE vision_id = ?")
				   ->execute([(int)$vision['id']]);
				$ins = $db->prepare("INSERT INTO vision_budget_items
					(vision_id, label, amount_cents, paid, sort_order) VALUES (?,?,?,?,?)");
				$sum = 0; $order = 0;
				foreach ($labels as $i => $label) {
					$label = trim((string)$label);
					$amt   = (int)($amounts[$i] ?? 0);
					if ($label === '' || $amt < 0) continue;
					$ins->execute([(int)$vision['id'], mb_substr($label, 0, 150), $amt,
								   !empty($paids[$i]) ? 1 : 0, $order++]);
					$sum += $amt;
				}
				// The manual total stays authoritative (it's the overall budget);
				// if the user left it empty/zero, fall back to the items sum.
				if ($order > 0 && $cents <= 0) $cents = $sum;
			} catch (\Throwable $e) { /* items table not migrated yet */ }
		}

		$sql = "INSERT INTO vision_budget (vision_id, currency, amount_cents, show_on_dashboard, show_on_trip)
				VALUES (?,?,?,?,?)
				ON DUPLICATE KEY UPDATE
				  currency = VALUES(currency),
				  amount_cents = VALUES(amount_cents),
				  show_on_dashboard = VALUES(show_on_dashboard),
				  show_on_trip = VALUES(show_on_trip)";
		$ok = $db->prepare($sql)->execute([(int)$vision['id'], $cur, $cents, $dash, $trip]);

		echo json_encode(['success' => (bool)$ok, 'amount_cents' => $cents]);
	}

	/** GET /api/currencies[?q=...] */
	public static function currencies(): void
	{
		header('Content-Type: application/json');
		// Lightweight static list; you can later read from a table or service.
		$all = [
			['code'=>'DKK','name'=>'Danish Krone'],
			['code'=>'EUR','name'=>'Euro'],
			['code'=>'USD','name'=>'US Dollar'],
			['code'=>'GBP','name'=>'British Pound'],
			['code'=>'SEK','name'=>'Swedish Krona'],
			['code'=>'NOK','name'=>'Norwegian Krone'],
			['code'=>'CHF','name'=>'Swiss Franc'],
			['code'=>'JPY','name'=>'Japanese Yen'],
			['code'=>'CAD','name'=>'Canadian Dollar'],
			['code'=>'AUD','name'=>'Australian Dollar'],
		];
		$q = strtoupper(trim((string)($_GET['q'] ?? '')));
		if ($q === '') { echo json_encode($all); return; }
		$out = array_values(array_filter($all, function($c) use ($q) {
			return str_contains($c['code'], $q) || str_contains(strtoupper($c['name']), $q);
		}));
		echo json_encode($out);
	}

	/** GET /api/visions/{slug}/contacts */
	/** GET /api/visions/{slug}/contacts */
	public static function listContacts(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = self::findVisionBySlug($db, $slug);
		api_require_vision($db, $vision, 'view');

		// Aggregate Name, Company, Email into columns for list view.  Other keys remain unaggregated.
		$sql = "
		  SELECT vc.id AS vc_id,
				 MAX(CASE WHEN f.field_key='Name'    THEN f.field_value END) AS name,
				 MAX(CASE WHEN f.field_key='Company' THEN f.field_value END) AS company,
				 MAX(CASE WHEN f.field_key='Email'   THEN f.field_value END) AS email,
				 vc.is_current, vc.is_main, vc.show_on_dashboard, vc.show_on_trip
			FROM vision_contacts vc
			JOIN vision_contact_fields f ON f.vision_contact_id = vc.id
		   WHERE vc.vision_id = ?
		   GROUP BY vc.id, vc.is_current, vc.is_main, vc.show_on_dashboard, vc.show_on_trip
		   ORDER BY vc.is_main DESC, vc.id ASC
		";
		$st = $db->prepare($sql);
		$st->execute([(int)$vision['id']]);
		echo json_encode($st->fetchAll(PDO::FETCH_ASSOC));
	}

	/** GET /api/visions/{slug}/contacts/{contactId} */
	public static function getContact(string $slug, string $vcId): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = self::findVisionBySlug($db, $slug);
		api_require_vision($db, $vision, 'view');

		$vcIdNum = (int)$vcId;
		$pivot = $db->prepare("SELECT id FROM vision_contacts WHERE id=? AND vision_id=?");
		$pivot->execute([$vcIdNum, (int)$vision['id']]);
		if (!$pivot->fetch()) { http_response_code(404); echo json_encode(['error'=>'Contact not found']); return; }

		$fields = $db->prepare("SELECT id, field_key, field_value FROM vision_contact_fields WHERE vision_contact_id=? ORDER BY field_order");
		$fields->execute([$vcIdNum]);

		$pivotFlags = $db->prepare("SELECT is_current, is_main, show_on_dashboard, show_on_trip FROM vision_contacts WHERE id=?");
		$pivotFlags->execute([$vcIdNum]);
		$flags = $pivotFlags->fetch(PDO::FETCH_ASSOC) ?: [];

		echo json_encode([
			'id'     => $vcIdNum,
			'fields' => $fields->fetchAll(PDO::FETCH_ASSOC),
			'flags'  => $flags,
		]);
	}


	/** POST /api/visions/{slug}/contacts/create */
	public static function createContact(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;

		$vision = self::findVisionBySlug($db, $slug);
		api_require_vision($db, $vision, 'edit');

		// Expected arrays: keys[], values[]; flags: is_current, is_main, show_on_dashboard, show_on_trip
		$keys = $_POST['keys']   ?? [];
		$vals = $_POST['values'] ?? [];
		if (!$keys || !$vals || count($keys) != count($vals)) {
			http_response_code(400); echo json_encode(['error'=>'Invalid fields']); return;
		}
		// At least one field must have a value
		$hasAny = false;
		foreach ($vals as $v) { if (trim((string)$v) !== '') { $hasAny = true; break; } }
		if (!$hasAny) { http_response_code(422); echo json_encode(['error'=>'At least one field must have a value']); return; }

		$db->beginTransaction();

		try {
			$isCurrent = isset($_POST['is_current']) ? 1 : 0;
			$isMain    = isset($_POST['is_main'])    ? 1 : 0;
			$showDash  = isset($_POST['show_on_dashboard']) ? 1 : 0;
			$showTrip  = isset($_POST['show_on_trip'])      ? 1 : 0;

			// Only one main per vision
			if ($isMain) {
				$db->prepare("UPDATE vision_contacts SET is_main=0 WHERE vision_id=?")->execute([(int)$vision['id']]);
			}

			$insPivot = $db->prepare("INSERT INTO vision_contacts
			  (vision_id, is_current, is_main, show_on_dashboard, show_on_trip)
			  VALUES (?,?,?,?,?)");
			$insPivot->execute([(int)$vision['id'], $isCurrent, $isMain, $showDash, $showTrip]);
			$vcId = (int)$db->lastInsertId();

			$insField = $db->prepare("INSERT INTO vision_contact_fields
			  (vision_contact_id, field_key, field_value, field_order)
			  VALUES (?,?,?,?)");

			$order = 0;
			foreach ($keys as $i => $k) {
				$key   = trim((string)$k);
				$value = trim((string)($vals[$i] ?? ''));
				if ($key === '' || $value === '') continue;
				$insField->execute([$vcId, $key, $value, $order++]);
			}

			$db->commit();
			echo json_encode(['success'=>true, 'vc_id'=>$vcId]);
		} catch (Throwable $e) {
			$db->rollBack();
			http_response_code(500); echo json_encode(['error'=>'Save failed']);
		}
	}

	/** POST /api/visions/{slug}/contacts/{vcId} (update) */
	public static function updateContact(string $slug, string $vcId): void
	{
		header('Content-Type: application/json');
		global $db;

		$vision = self::findVisionBySlug($db, $slug);
		api_require_vision($db, $vision, 'edit');

		$vcIdNum = (int)$vcId;
		$check   = $db->prepare("SELECT vision_id FROM vision_contacts WHERE id=?");
		$check->execute([$vcIdNum]);
		if ($check->fetchColumn() != $vision['id']) {
			http_response_code(404); echo json_encode(['error'=>'Contact not found']); return;
		}

		$keys = $_POST['keys']   ?? [];
		$vals = $_POST['values'] ?? [];
		if (!$keys || count($keys) != count($vals)) {
			http_response_code(400); echo json_encode(['error'=>'Invalid fields']); return;
		}
		$hasAny = false;
		foreach ($vals as $v) { if (trim((string)$v) !== '') { $hasAny = true; break; } }
		if (!$hasAny) { http_response_code(422); echo json_encode(['error'=>'At least one field must have a value']); return; }

		$db->beginTransaction();
		try {
			// flags
			$isCurrent = isset($_POST['is_current']) ? 1 : 0;
			$isMain    = isset($_POST['is_main'])    ? 1 : 0;
			$showDash  = isset($_POST['show_on_dashboard']) ? 1 : 0;
			$showTrip  = isset($_POST['show_on_trip'])      ? 1 : 0;

			if ($isMain) {
				$db->prepare("UPDATE vision_contacts SET is_main=0 WHERE vision_id=?")->execute([$vision['id']]);
			}

			$upd = $db->prepare("UPDATE vision_contacts
				SET is_current=?, is_main=?, show_on_dashboard=?, show_on_trip=?
				WHERE id=?");
			$upd->execute([$isCurrent, $isMain, $showDash, $showTrip, $vcIdNum]);

			// Delete old fields
			$db->prepare("DELETE FROM vision_contact_fields WHERE vision_contact_id=?")->execute([$vcIdNum]);

			// Insert new fields
			$ins = $db->prepare("INSERT INTO vision_contact_fields
			  (vision_contact_id, field_key, field_value, field_order)
			  VALUES (?,?,?,?)");

			$order=0;
			foreach ($keys as $i => $k) {
				$key = trim((string)$k);
				$val = trim((string)($vals[$i] ?? ''));
				if ($key === '' || $val === '') continue;
				$ins->execute([$vcIdNum, $key, $val, $order++]);
			}

			$db->commit();
			echo json_encode(['success'=>true]);
		} catch (Throwable $e) {
			$db->rollBack();
			http_response_code(500); echo json_encode(['error'=>'Update failed']);
		}
	}

	/** DELETE /api/visions/{slug}/contacts/{vcId} */
	public static function deleteContact(string $slug, string $vcId): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = self::findVisionBySlug($db, $slug);
		api_require_vision($db, $vision, 'edit');

		$vcIdNum = (int)$vcId;
		// Ensure contact belongs to this vision
		$chk = $db->prepare("SELECT id FROM vision_contacts WHERE id=? AND vision_id=?");
		$chk->execute([$vcIdNum, (int)$vision['id']]);
		if (!$chk->fetch()) { http_response_code(404); echo json_encode(['error'=>'Contact not found']); return; }

		$db->prepare("DELETE FROM vision_contacts WHERE id=?")->execute([$vcIdNum]);
		echo json_encode(['success'=>true]);
	}

	/* ──────────────────────────  Itinerary  ────────────────────────── */

	/** GET /api/visions/{slug}/itinerary */
	public static function listItinerary(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'view');
		try {
			$st = $db->prepare("SELECT id, day_date, start_time, title, location, notes, show_on_trip
								  FROM vision_itinerary
								 WHERE vision_id = ?
								 ORDER BY day_date ASC, (start_time IS NULL) ASC, start_time ASC, id ASC");
			$st->execute([(int)$vision['id']]);
			echo json_encode(['success' => true, 'entries' => $st->fetchAll(PDO::FETCH_ASSOC)]);
		} catch (\Throwable $e) {
			echo json_encode(['success' => true, 'entries' => [], 'migration_missing' => true]);
		}
	}

	/** Shared field parsing for itinerary create/update. */
	private static function itineraryFields(): ?array
	{
		$day   = trim((string)($_POST['day_date'] ?? ''));
		$title = trim((string)($_POST['title'] ?? ''));
		if ($day === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) || $title === '') return null;
		$time = trim((string)($_POST['start_time'] ?? ''));
		return [
			'day_date'     => $day,
			'start_time'   => preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time) ? $time : null,
			'title'        => mb_substr($title, 0, 255),
			'location'     => mb_substr(trim((string)($_POST['location'] ?? '')), 0, 255) ?: null,
			'notes'        => mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 2000) ?: null,
			'show_on_trip' => !empty($_POST['show_on_trip']) ? 1 : 0,
		];
	}

	/** POST /api/visions/{slug}/itinerary/create */
	public static function createItineraryItem(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'edit');

		$f = self::itineraryFields();
		if (!$f) { http_response_code(422); echo json_encode(['error' => 'Date and title required']); return; }

		$db->prepare("INSERT INTO vision_itinerary
			(vision_id, day_date, start_time, title, location, notes, show_on_trip)
			VALUES (?,?,?,?,?,?,?)")
		   ->execute([(int)$vision['id'], $f['day_date'], $f['start_time'], $f['title'],
					  $f['location'], $f['notes'], $f['show_on_trip']]);
		echo json_encode(['success' => true, 'entry_id' => (int)$db->lastInsertId()]);
	}

	/** POST /api/visions/{slug}/itinerary/{id} */
	public static function updateItineraryItem(string $slug, string $entryId): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'edit');

		$f = self::itineraryFields();
		if (!$f) { http_response_code(422); echo json_encode(['error' => 'Date and title required']); return; }

		$db->prepare("UPDATE vision_itinerary
						 SET day_date=?, start_time=?, title=?, location=?, notes=?, show_on_trip=?
					   WHERE id=? AND vision_id=?")
		   ->execute([$f['day_date'], $f['start_time'], $f['title'], $f['location'],
					  $f['notes'], $f['show_on_trip'], (int)$entryId, (int)$vision['id']]);
		echo json_encode(['success' => true]);
	}

	/** POST/DELETE /api/visions/{slug}/itinerary/{id}/delete */
	public static function deleteItineraryItem(string $slug, string $entryId): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'edit');

		$db->prepare("DELETE FROM vision_itinerary WHERE id=? AND vision_id=?")
		   ->execute([(int)$entryId, (int)$vision['id']]);
		echo json_encode(['success' => true]);
	}

	/* ──────────────────────────  Goals & Milestones  ────────────────────────── */

	private static function normalizeGoalStatus($status): string
	{
		$allowed = ['not_started','in_progress','awaiting','done','cancelled'];
		return in_array($status, $allowed, true) ? $status : 'not_started';
	}

	private static function saveMilestones(PDO $db, int $goalId, array $texts, array $dones,
										   array $dues = [], array $assignees = []): void
	{
		$db->prepare("DELETE FROM vision_goal_milestones WHERE goal_id=?")->execute([$goalId]);
		if (!$texts) return;
		$st = $db->prepare("INSERT INTO vision_goal_milestones
							(goal_id, text, done, sort_order, due_date, assigned_user_id)
							VALUES (?,?,?,?,?,?)");
		foreach ($texts as $i => $t) {
			$text = trim((string)$t);
			if ($text === '') continue;
			$done = !empty($dones[$i]) ? 1 : 0;
			$due  = trim((string)($dues[$i] ?? ''));
			$aid  = (int)($assignees[$i] ?? 0);
			$st->execute([$goalId, $text, $done, $i, $due !== '' ? $due : null, $aid > 0 ? $aid : null]);
		}
	}

	/** Is this user the board owner or a collaborator (any role) on the vision? */
	private static function isOnBoard(PDO $db, array $vision, int $userId): bool
	{
		if ($userId <= 0) return false;
		if ((int)$vision['user_id'] === $userId) return true;
		$st = $db->prepare("SELECT 1 FROM vision_roles WHERE vision_id = ? AND user_id = ? LIMIT 1");
		$st->execute([(int)$vision['id'], $userId]);
		return (bool)$st->fetchColumn();
	}

	/**
	 * Assignment target check. If the user isn't on the board but IS a member
	 * of one of the assigner's teams, auto-add them with their team default
	 * role (the vision_roles insert also triggers their new-share notice).
	 * Returns true when the user ends up on the board.
	 */
	private static function ensureAssignableOnBoard(PDO $db, array $vision, int $userId): bool
	{
		global $currentUserId;
		if (self::isOnBoard($db, $vision, $userId)) return true;
		try {
			$tm = $db->prepare("SELECT tm.default_role
								  FROM team_members tm
								  JOIN teams t ON t.id = tm.team_id
								 WHERE t.owner_user_id = ? AND tm.user_id = ?
								 LIMIT 1");
			$tm->execute([(int)$currentUserId, $userId]);
			$role = $tm->fetchColumn();
			if ($role) {
				$db->prepare("INSERT INTO vision_roles (vision_id, user_id, role)
							  VALUES (?,?,?)
							  ON DUPLICATE KEY UPDATE role = role")
				   ->execute([(int)$vision['id'], $userId, $role]);
				return true;
			}
		} catch (\Throwable $e) { /* teams not migrated */ }
		return false;
	}

	/** GET /api/visions/{slug}/goals */
	public static function listGoals(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'view');

		$sql = "SELECT g.id, g.title, g.description, g.status, g.priority,
					   g.due_date, g.completed_at, g.created_at,
					   g.assigned_user_id, g.assignment_status,
					   au.name AS assignee_name, au.email AS assignee_email,
					   COUNT(m.id) AS milestone_total,
					   COALESCE(SUM(m.done), 0) AS milestone_done,
					   MIN(CASE WHEN m.done = 0 THEN m.due_date END) AS next_milestone_due
				  FROM vision_goals g
				  LEFT JOIN vision_goal_milestones m ON m.goal_id = g.id
				  LEFT JOIN users au ON au.id = g.assigned_user_id
				 WHERE g.vision_id = ?
				 GROUP BY g.id, au.name, au.email
				 ORDER BY (g.status IN ('done','cancelled')) ASC,  -- finished sink to the bottom
						  (g.due_date IS NULL) ASC,                -- dated goals first
						  g.due_date ASC,                          -- soonest due first
						  FIELD(g.status,'awaiting','in_progress','not_started'),
						  g.priority ASC,
						  g.id ASC";
		$st = $db->prepare($sql);
		$st->execute([(int)$vision['id']]);
		echo json_encode($st->fetchAll(PDO::FETCH_ASSOC));
	}

	/** GET /api/visions/{slug}/goals/{id}/get */
	public static function getGoal(string $slug, string $goalId): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'view');

		$gid = (int)$goalId;
		$g = $db->prepare("SELECT * FROM vision_goals WHERE id=? AND vision_id=?");
		$g->execute([$gid, (int)$vision['id']]);
		$goal = $g->fetch(PDO::FETCH_ASSOC);
		if (!$goal) { http_response_code(404); echo json_encode(['error'=>'Goal not found']); return; }

		$m = $db->prepare("SELECT id, text, done, due_date, assigned_user_id
							 FROM vision_goal_milestones
							WHERE goal_id=? ORDER BY sort_order, id");
		$m->execute([$gid]);
		$goal['milestones'] = $m->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode($goal);
	}

	/** POST /api/visions/{slug}/goals/create */
	public static function createGoal(string $slug): void
	{
		header('Content-Type: application/json');
		global $db, $currentUserId;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'edit');

		$title = trim((string)($_POST['title'] ?? ''));
		if ($title === '') { http_response_code(422); echo json_encode(['error'=>'Title required']); return; }

		$status      = self::normalizeGoalStatus($_POST['status'] ?? 'not_started');
		$priority    = max(1, min(5, (int)($_POST['priority'] ?? 3)));
		$description = (string)($_POST['description'] ?? '');
		$due         = trim((string)($_POST['due_date'] ?? ''));
		$due         = $due === '' ? null : $due;
		$completedAt = $status === 'done' ? date('Y-m-d H:i:s') : null;
		$showOnTrip  = array_key_exists('show_on_trip', $_POST)
			? (!empty($_POST['show_on_trip']) ? 1 : 0)
			: 1; // default visible

		// Assignee must actually be on the board
		$assignedTo = (int)($_POST['assigned_user_id'] ?? 0) ?: null;
		if ($assignedTo && !self::ensureAssignableOnBoard($db, $vision, $assignedTo)) {
			http_response_code(422); echo json_encode(['error'=>'Assignee is not on this board or in your teams']); return;
		}

		$db->beginTransaction();
		try {
			$st = $db->prepare("INSERT INTO vision_goals
				(vision_id, title, description, status, priority, due_date, completed_at, show_on_trip,
				 assigned_user_id, assigned_by_user_id, assignment_status)
				VALUES (?,?,?,?,?,?,?,?,?,?,'open')");
			$st->execute([(int)$vision['id'], $title, $description, $status, $priority, $due, $completedAt, $showOnTrip,
						  $assignedTo, $assignedTo ? (int)$currentUserId : null]);
			$goalId = (int)$db->lastInsertId();
			self::saveMilestones(
				$db, $goalId,
				$_POST['milestone_texts'] ?? [],
				$_POST['milestone_dones'] ?? [],
				$_POST['milestone_dues'] ?? [],
				$_POST['milestone_assignees'] ?? []
			);
			$db->commit();
			if ($assignedTo && $assignedTo !== (int)$currentUserId) {
				add_notification($db, $assignedTo, 'goal_assigned', (int)$vision['id'], $goalId, (int)$currentUserId);
			}
			echo json_encode(['success'=>true, 'goal_id'=>$goalId]);
		} catch (\Throwable $e) {
			$db->rollBack();
			http_response_code(500); echo json_encode(['error'=>$e->getMessage()]);
		}
	}

	/** POST /api/visions/{slug}/goals/{id} */
	public static function updateGoal(string $slug, string $goalId): void
	{
		header('Content-Type: application/json');
		global $db, $currentUserId;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'edit');

		$gid = (int)$goalId;
		$chk = $db->prepare("SELECT status, completed_at, assigned_user_id, assigned_by_user_id, assignment_status
							   FROM vision_goals WHERE id=? AND vision_id=?");
		$chk->execute([$gid, (int)$vision['id']]);
		$existing = $chk->fetch(PDO::FETCH_ASSOC);
		if (!$existing) { http_response_code(404); echo json_encode(['error'=>'Goal not found']); return; }

		$title = trim((string)($_POST['title'] ?? ''));
		if ($title === '') { http_response_code(422); echo json_encode(['error'=>'Title required']); return; }

		$status      = self::normalizeGoalStatus($_POST['status'] ?? 'not_started');
		$priority    = max(1, min(5, (int)($_POST['priority'] ?? 3)));
		$description = (string)($_POST['description'] ?? '');
		$due         = trim((string)($_POST['due_date'] ?? ''));
		$due         = $due === '' ? null : $due;
		$showOnTrip  = array_key_exists('show_on_trip', $_POST)
			? (!empty($_POST['show_on_trip']) ? 1 : 0)
			: 1;

		// Assignment: keep existing values unless the form sends a change
		$prevAssignee = (int)($existing['assigned_user_id'] ?? 0) ?: null;
		$newAssignee  = array_key_exists('assigned_user_id', $_POST)
			? ((int)$_POST['assigned_user_id'] ?: null)
			: $prevAssignee;
		if ($newAssignee && !self::ensureAssignableOnBoard($db, $vision, $newAssignee)) {
			http_response_code(422); echo json_encode(['error'=>'Assignee is not on this board or in your teams']); return;
		}
		$assignedBy       = $existing['assigned_by_user_id'];
		$assignmentStatus = $existing['assignment_status'] ?: 'open';
		$assigneeChanged  = $newAssignee !== $prevAssignee;
		if ($assigneeChanged) {
			$assignedBy       = $newAssignee ? (int)$currentUserId : null;
			$assignmentStatus = 'open'; // fresh assignment resets resolved/returned
		}

		// Manage completed_at on transitions
		$completedAt = $existing['completed_at'];
		if ($status === 'done' && $existing['status'] !== 'done')      $completedAt = date('Y-m-d H:i:s');
		elseif ($status !== 'done' && $existing['status'] === 'done')  $completedAt = null;

		$db->beginTransaction();
		try {
			$st = $db->prepare("UPDATE vision_goals
				   SET title=?, description=?, status=?, priority=?, due_date=?, completed_at=?, show_on_trip=?,
					   assigned_user_id=?, assigned_by_user_id=?, assignment_status=?
				 WHERE id=?");
			$st->execute([$title, $description, $status, $priority, $due, $completedAt, $showOnTrip,
						  $newAssignee, $assignedBy, $assignmentStatus, $gid]);
			self::saveMilestones(
				$db, $gid,
				$_POST['milestone_texts'] ?? [],
				$_POST['milestone_dones'] ?? [],
				$_POST['milestone_dues'] ?? [],
				$_POST['milestone_assignees'] ?? []
			);
			$db->commit();
			if ($assigneeChanged && $newAssignee && $newAssignee !== (int)$currentUserId) {
				add_notification($db, $newAssignee, 'goal_assigned', (int)$vision['id'], $gid, (int)$currentUserId);
			}
			echo json_encode(['success'=>true]);
		} catch (\Throwable $e) {
			$db->rollBack();
			http_response_code(500); echo json_encode(['error'=>$e->getMessage()]);
		}
	}

	/** Shared guts of resolve/return: permission + goal lookup. */
	private static function goalForAssigneeAction(string $slug, string $goalId): ?array
	{
		global $db, $currentUserId;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'view');

		$g = $db->prepare("SELECT * FROM vision_goals WHERE id=? AND vision_id=?");
		$g->execute([(int)$goalId, (int)$vision['id']]);
		$goal = $g->fetch(PDO::FETCH_ASSOC);
		if (!$goal) { http_response_code(404); echo json_encode(['error'=>'Goal not found']); return null; }

		$isAssignee = (int)($goal['assigned_user_id'] ?? 0) === (int)$currentUserId;
		if (!$isAssignee && !vision_can($db, $vision, 'edit')) {
			http_response_code(403); echo json_encode(['error'=>'Only the assignee can do that']); return null;
		}
		return ['vision' => $vision, 'goal' => $goal];
	}

	/** POST /api/visions/{slug}/goals/{id}/resolve  body: note (optional) */
	public static function resolveGoal(string $slug, string $goalId): void
	{
		header('Content-Type: application/json');
		global $db, $currentUserId;
		$ctx = self::goalForAssigneeAction($slug, $goalId);
		if (!$ctx) return;
		$goal = $ctx['goal']; $vision = $ctx['vision'];

		$note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 2000);
		$db->prepare("UPDATE vision_goals
						 SET status='done', completed_at=NOW(), assignment_status='resolved'
					   WHERE id=?")->execute([(int)$goal['id']]);

		$recipient = (int)($goal['assigned_by_user_id'] ?: $vision['user_id']);
		if ($recipient && $recipient !== (int)$currentUserId) {
			add_notification($db, $recipient, 'goal_resolved', (int)$vision['id'], (int)$goal['id'], (int)$currentUserId, $note);
		}
		echo json_encode(['success'=>true]);
	}

	/** POST /api/visions/{slug}/goals/{id}/return  body: note (optional) */
	public static function returnGoal(string $slug, string $goalId): void
	{
		header('Content-Type: application/json');
		global $db, $currentUserId;
		$ctx = self::goalForAssigneeAction($slug, $goalId);
		if (!$ctx) return;
		$goal = $ctx['goal']; $vision = $ctx['vision'];

		$note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 2000);
		$db->prepare("UPDATE vision_goals SET assignment_status='returned' WHERE id=?")
		   ->execute([(int)$goal['id']]);

		$recipient = (int)($goal['assigned_by_user_id'] ?: $vision['user_id']);
		if ($recipient && $recipient !== (int)$currentUserId) {
			add_notification($db, $recipient, 'goal_returned', (int)$vision['id'], (int)$goal['id'], (int)$currentUserId, $note);
		}
		echo json_encode(['success'=>true]);
	}

	/** POST /api/visions/{slug}/goals/{id}/reopen — owner/editor puts it back to open. */
	public static function reopenGoal(string $slug, string $goalId): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'edit');
		$db->prepare("UPDATE vision_goals
						 SET status='in_progress', completed_at=NULL, assignment_status='open'
					   WHERE id=? AND vision_id=?")
		   ->execute([(int)$goalId, (int)$vision['id']]);
		echo json_encode(['success'=>true]);
	}

	/* ──────────────────────────  Goal comments  ────────────────────────── */

	/** GET /api/visions/{slug}/goals/{id}/comments */
	public static function listGoalComments(string $slug, string $goalId): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'view');

		$chk = $db->prepare("SELECT id FROM vision_goals WHERE id=? AND vision_id=?");
		$chk->execute([(int)$goalId, (int)$vision['id']]);
		if (!$chk->fetch()) { http_response_code(404); echo json_encode(['error'=>'Goal not found']); return; }

		$st = $db->prepare("
			SELECT c.id, c.body, c.created_at, c.user_id, u.name AS author
			  FROM goal_comments c
			  JOIN users u ON u.id = c.user_id
			 WHERE c.goal_id = ?
			 ORDER BY c.created_at ASC, c.id ASC
		");
		$st->execute([(int)$goalId]);
		echo json_encode(['comments' => $st->fetchAll(PDO::FETCH_ASSOC)]);
	}

	/** POST /api/visions/{slug}/goals/{id}/comments  body: body */
	public static function addGoalComment(string $slug, string $goalId): void
	{
		header('Content-Type: application/json');
		global $db, $currentUserId;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'view');

		$g = $db->prepare("SELECT * FROM vision_goals WHERE id=? AND vision_id=?");
		$g->execute([(int)$goalId, (int)$vision['id']]);
		$goal = $g->fetch(PDO::FETCH_ASSOC);
		if (!$goal) { http_response_code(404); echo json_encode(['error'=>'Goal not found']); return; }

		$body = trim((string)($_POST['body'] ?? ''));
		if ($body === '') { http_response_code(422); echo json_encode(['error'=>'Empty comment']); return; }
		$body = mb_substr($body, 0, 4000);

		$db->prepare("INSERT INTO goal_comments (goal_id, user_id, body) VALUES (?,?,?)")
		   ->execute([(int)$goalId, (int)$currentUserId, $body]);
		$cid = (int)$db->lastInsertId();

		// Notify the other involved parties (assignee, assigner, owner) — not myself
		$targets = array_unique(array_filter([
			(int)($goal['assigned_user_id'] ?? 0),
			(int)($goal['assigned_by_user_id'] ?? 0),
			(int)$vision['user_id'],
		]));
		foreach ($targets as $uid) {
			if ($uid && $uid !== (int)$currentUserId) {
				add_notification($db, $uid, 'goal_comment', (int)$vision['id'], (int)$goalId, (int)$currentUserId, $body);
			}
		}

		$me = $db->prepare("SELECT name FROM users WHERE id=?");
		$me->execute([(int)$currentUserId]);
		echo json_encode(['success'=>true, 'comment'=>[
			'id'=>$cid, 'body'=>$body, 'user_id'=>(int)$currentUserId,
			'author'=>(string)$me->fetchColumn(), 'created_at'=>date('Y-m-d H:i:s'),
		]]);
	}

	/** DELETE /api/visions/{slug}/goals/{id}/delete */
	public static function deleteGoal(string $slug, string $goalId): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'edit');

		$gid = (int)$goalId;
		$chk = $db->prepare("SELECT id FROM vision_goals WHERE id=? AND vision_id=?");
		$chk->execute([$gid, (int)$vision['id']]);
		if (!$chk->fetch()) { http_response_code(404); echo json_encode(['error'=>'Goal not found']); return; }

		$db->prepare("DELETE FROM vision_goals WHERE id=?")->execute([$gid]); // milestones cascade
		echo json_encode(['success'=>true]);
	}

	/** Absolute URL for share links (scheme + host + path). */
	private static function absoluteUrl(string $path): string
	{
		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
		return $scheme . '://' . $host . $path;
	}

	/** GET/POST /api/visions/{slug}/trip-share
	 *  GET  → current share state
	 *  POST action=regenerate      → mint a new token (old link dies)
	 *  POST action=expiry&days=N   → expire N days from now (0 = never) */
	public static function tripShare(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'manage');
		$id = (int)$vision['id'];

		try {
			$action = (string)($_POST['action'] ?? '');

			if ($action === 'regenerate') {
				$token = bin2hex(random_bytes(16));
				$db->prepare("UPDATE visions SET trip_token=? WHERE id=?")->execute([$token, $id]);
			} elseif ($action === 'expiry') {
				$days = max(0, (int)($_POST['days'] ?? 0));
				$expires = $days > 0 ? date('Y-m-d H:i:s', strtotime("+{$days} days")) : null;
				$db->prepare("UPDATE visions SET trip_token_expires_at=? WHERE id=?")->execute([$expires, $id]);
			}

			// Ensure a token exists whenever the trip is published
			$tq = $db->prepare("SELECT trip_enabled, trip_token, trip_token_expires_at FROM visions WHERE id=?");
			$tq->execute([$id]);
			$row = $tq->fetch(PDO::FETCH_ASSOC);
			$token = $row['trip_token'] ?? null;
			if (!$token && !empty($row['trip_enabled'])) {
				$token = bin2hex(random_bytes(16));
				$db->prepare("UPDATE visions SET trip_token=? WHERE id=?")->execute([$token, $id]);
			}

			echo json_encode([
				'success'    => true,
				'enabled'    => (bool)($row['trip_enabled'] ?? false),
				'token'      => $token,
				'url'        => $token ? self::absoluteUrl('/t/' . $token) : null,
				'expires_at' => $row['trip_token_expires_at'] ?? null,
			]);
		} catch (\Throwable $e) {
			http_response_code(500);
			echo json_encode(['error' => 'Share columns missing — run the trip-share migration']);
		}
	}

	/* ──────────────────────  Roles & sharing (board-level)  ────────────────────── */

	private static function roleVisionOr404(PDO $db, string $slug, string $ability): ?array
	{
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, $ability);
		return $vision;
	}

	/** GET /api/visions/{slug}/roles — members incl. the implicit owner */
	public static function listRoles(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = self::roleVisionOr404($db, $slug, 'view');

		$out = [];
		// Implicit owner first
		$ow = $db->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
		$ow->execute([(int)$vision['user_id']]);
		if ($o = $ow->fetch(PDO::FETCH_ASSOC)) {
			$out[] = ['id'=>null, 'user_id'=>(int)$o['id'], 'name'=>$o['name'],
					  'email'=>$o['email'], 'role'=>'owner'];
		}
		$st = $db->prepare("
			SELECT vr.id, vr.user_id, vr.role, u.name, u.email
			  FROM vision_roles vr
			  JOIN users u ON u.id = vr.user_id
			 WHERE vr.vision_id = ?
			 ORDER BY FIELD(vr.role,'co_owner','delegate','editor','viewer'), u.name
		");
		$st->execute([(int)$vision['id']]);
		foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
			$out[] = ['id'=>(int)$r['id'], 'user_id'=>(int)$r['user_id'],
					  'name'=>$r['name'], 'email'=>$r['email'], 'role'=>$r['role']];
		}
		// Tell the client whether the current user may manage this list
		echo json_encode([
			'members'   => $out,
			'can_manage'=> vision_can($db, $vision, 'manage'),
		]);
	}

	/** POST /api/visions/{slug}/roles/add  body: email, role */
	public static function addRole(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = self::roleVisionOr404($db, $slug, 'manage');

		$email = trim((string)($_POST['email'] ?? ''));
		$role  = (string)($_POST['role'] ?? 'viewer');
		$allowed = ['co_owner','editor','viewer','delegate'];
		if (!in_array($role, $allowed, true)) $role = 'viewer';
		if ($email === '') { http_response_code(422); echo json_encode(['error'=>'Email required']); return; }

		$us = $db->prepare("SELECT id, name, email FROM users WHERE email = ? LIMIT 1");
		$us->execute([$email]);
		$user = $us->fetch(PDO::FETCH_ASSOC);
		if (!$user) {
			// Deliberately ambiguous — don't confirm whether an account exists,
			// so the form can't be used to probe who is registered here.
			echo json_encode(['success' => true, 'unknown' => true]);
			return;
		}
		if ((int)$user['id'] === (int)$vision['user_id']) {
			http_response_code(422); echo json_encode(['error'=>'That user is already the owner']); return;
		}

		$ins = $db->prepare("INSERT INTO vision_roles (vision_id, user_id, role)
							 VALUES (?,?,?)
							 ON DUPLICATE KEY UPDATE role = VALUES(role)");
		$ins->execute([(int)$vision['id'], (int)$user['id'], $role]);

		echo json_encode(['success'=>true, 'user'=>[
			'user_id'=>(int)$user['id'], 'name'=>$user['name'], 'email'=>$user['email'], 'role'=>$role,
		]]);
	}

	/** POST /api/visions/{slug}/roles/{roleId}  body: role */
	public static function updateRole(string $slug, string $roleId): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = self::roleVisionOr404($db, $slug, 'manage');

		$role = (string)($_POST['role'] ?? '');
		$allowed = ['co_owner','editor','viewer','delegate'];
		if (!in_array($role, $allowed, true)) {
			http_response_code(422); echo json_encode(['error'=>'Invalid role']); return;
		}
		$st = $db->prepare("UPDATE vision_roles SET role=? WHERE id=? AND vision_id=?");
		$st->execute([$role, (int)$roleId, (int)$vision['id']]);
		echo json_encode(['success'=>true]);
	}

	/** DELETE (or POST) /api/visions/{slug}/roles/{roleId}/delete */
	public static function removeRole(string $slug, string $roleId): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = self::roleVisionOr404($db, $slug, 'manage');

		$db->prepare("DELETE FROM vision_roles WHERE id=? AND vision_id=?")
		   ->execute([(int)$roleId, (int)$vision['id']]);
		echo json_encode(['success'=>true]);
	}

	/* ──────────────────────  Handoffs ("send back to owner")  ────────────────── */

	/** POST /api/visions/{slug}/handoff  body: note (optional) */
	public static function handoff(string $slug): void
	{
		header('Content-Type: application/json');
		global $db, $currentUserId;
		$vision = vision_model::get($db, $slug);
		api_require_vision($db, $vision, 'view');

		// Only collaborators hand back — the owner has nobody to send to.
		if ((int)$vision['user_id'] === (int)$currentUserId) {
			http_response_code(422);
			echo json_encode(['error' => 'You own this board — nothing to hand back.']);
			return;
		}

		$note = trim((string)($_POST['note'] ?? ''));
		if (mb_strlen($note) > 2000) $note = mb_substr($note, 0, 2000);

		$db->prepare("INSERT INTO vision_handoffs (vision_id, from_user_id, to_user_id, note)
					  VALUES (?,?,?,?)")
		   ->execute([(int)$vision['id'], (int)$currentUserId, (int)$vision['user_id'], $note ?: null]);
		echo json_encode(['success' => true]);
	}

	/** POST /api/visions/{slug}/roles/add-team  body: team_id
	 *  Snapshot-copies the team's members (with their default roles) into
	 *  vision_roles. Members who already have a role on the board keep it. */
	public static function addTeamRoles(string $slug): void
	{
		header('Content-Type: application/json');
		global $db, $currentUserId;
		$vision = self::roleVisionOr404($db, $slug, 'manage');

		$teamId = (int)($_POST['team_id'] ?? 0);
		$ts = $db->prepare("SELECT * FROM teams WHERE id = ? LIMIT 1");
		$ts->execute([$teamId]);
		$team = $ts->fetch(PDO::FETCH_ASSOC);
		if (!$team || ((int)$team['owner_user_id'] !== (int)$currentUserId && !is_admin())) {
			http_response_code(404); echo json_encode(['error' => 'Team not found']); return;
		}

		$ms = $db->prepare("SELECT user_id, default_role FROM team_members WHERE team_id = ?");
		$ms->execute([$teamId]);
		$members = $ms->fetchAll(PDO::FETCH_ASSOC);
		if (!$members) { echo json_encode(['success' => true, 'added' => 0, 'skipped' => 0]); return; }

		$ins = $db->prepare("INSERT INTO vision_roles (vision_id, user_id, role)
							 VALUES (?,?,?)
							 ON DUPLICATE KEY UPDATE role = role"); // keep existing custom roles
		$added = 0; $skipped = 0;
		foreach ($members as $m) {
			// The board owner never needs a role row
			if ((int)$m['user_id'] === (int)$vision['user_id']) { $skipped++; continue; }
			$ins->execute([(int)$vision['id'], (int)$m['user_id'], $m['default_role']]);
			if ($ins->rowCount() === 1) $added++; else $skipped++;
		}
		echo json_encode(['success' => true, 'added' => $added, 'skipped' => $skipped]);
	}

	/** POST /api/handoffs/{id}/ack — recipient checks a returned item off. */
	public static function ackHandoff(string $handoffId): void
	{
		header('Content-Type: application/json');
		api_require_login();
		global $db, $currentUserId;

		$st = $db->prepare("SELECT id, to_user_id FROM vision_handoffs WHERE id = ? LIMIT 1");
		$st->execute([(int)$handoffId]);
		$h = $st->fetch(PDO::FETCH_ASSOC);
		if (!$h || ((int)$h['to_user_id'] !== (int)$currentUserId && !is_admin())) {
			http_response_code(404);
			echo json_encode(['error' => 'Not found']);
			return;
		}
		$db->prepare("UPDATE vision_handoffs SET acknowledged_at = NOW() WHERE id = ?")
		   ->execute([(int)$h['id']]);
		echo json_encode(['success' => true]);
	}

}
