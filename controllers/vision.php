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
        $slug = (string)$db->query("SELECT slug FROM visions WHERE id=$id")->fetchColumn();
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
		require_owner($vision);

		$anchors = vision_model::getAnchors($db, (int)$vision['id']);

		// Source dream, if this vision was promoted from one
		$sourceDream = null;
		if (!empty($vision['dream_id'])) {
			$ds = $db->prepare("SELECT slug, title FROM dream_boards
								  WHERE id = ? AND deleted_at IS NULL LIMIT 1");
			$ds->execute([(int)$vision['dream_id']]);
			$sourceDream = $ds->fetch(PDO::FETCH_ASSOC) ?: null;
		}

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
        require_owner($vision);
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
            $sidebarBadges['goals']     = (int)$db->query("SELECT COUNT(*) FROM vision_goals WHERE vision_id=$vid AND status != 'cancelled'")->fetchColumn();
            $sidebarBadges['contacts']  = (int)$db->query("SELECT COUNT(*) FROM vision_contacts WHERE vision_id=$vid")->fetchColumn();
            $sidebarBadges['documents'] = (int)$db->query("SELECT COUNT(*) FROM vision_documents WHERE vision_id=$vid")->fetchColumn();
            $sidebarBadges['budget']    = (int)$db->query("SELECT COUNT(*) FROM vision_budget WHERE vision_id=$vid")->fetchColumn();
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
        global $db;
        $id = (int)($_POST['vision_id'] ?? 0);
        if (!$id) { http_response_code(400); echo 'Missing ID'; return; }
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
        $slug = (string)$db->query("SELECT slug FROM visions WHERE id=$id")->fetchColumn();
        header("Location: /visions/$slug");
        exit;
    }

    /** archive/unarchive/delete/restore */
    public static function archive(string $slug): void
    {
        require_login();
        global $db;
        $v = vision_model::get($db, $slug);
        require_owner($v);
        vision_model::setArchived($db, (int)$v['id'], true);
        header('Location: /dashboard/vision'); exit;
    }
    public static function unarchive(string $slug): void
    {
        require_login();
        global $db;
        $v = vision_model::get($db, $slug);
        require_owner($v);
        vision_model::setArchived($db, (int)$v['id'], false);
        header('Location: /dashboard/vision/archived'); exit;
    }
    public static function destroy(string $slug): void
    {
        require_login();
        global $db;
        $v = vision_model::get($db, $slug);
        require_owner($v);
        vision_model::softDelete($db, (int)$v['id']);
        header('Location: /dashboard/vision'); exit;
    }
    public static function restore(string $slug): void
    {
        require_login();
        global $db;
        $v = vision_model::get($db, $slug);
        require_owner($v);
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

			// Verify ownership before any mutation
			$own = $db->prepare("SELECT user_id FROM visions WHERE id = ? LIMIT 1");
			$own->execute([$visionId]);
			$ownerId = (int)($own->fetchColumn() ?: 0);
			if ($ownerId !== (int)$currentUserId) {
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
            api_require_owner($vision);
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
        global $db;
        $vision = vision_model::get($db, $slug);
        require_owner($vision);
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
        // For budget overlay: load existing budget row
        $budget = null;
        if ($section === 'budget') {
            $bs = $db->prepare("SELECT currency, amount_cents, show_on_dashboard, show_on_trip
                                FROM vision_budget WHERE vision_id = ? LIMIT 1");
            $bs->execute([(int)$vision['id']]);
            $budget = $bs->fetch(PDO::FETCH_ASSOC) ?: null;
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
        api_require_owner($vision);
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
                // Master switch: enables/disables the entire trip view
                if ($flag === 'trip_enabled') {
                    $db->prepare("UPDATE visions SET trip_enabled=? WHERE id=?")
                       ->execute([$enabled ? 1 : 0, $id]);
                    echo json_encode(['success' => true]);
                    return;
                }
                $allowed = ['relations','goals','budget','roles','contacts','documents','workflow'];
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
	
	/** Helper: resolve a vision by slug (fallback) */
	private static function findVisionBySlug(PDO $db, string $slug): ?array
	{
		$st = $db->prepare("SELECT id, slug FROM visions WHERE slug=? LIMIT 1");
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
			api_require_owner($vision);

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

			api_require_owner($vision);

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
		api_require_owner($vision);

		$st = $db->prepare("SELECT currency, amount_cents, show_on_dashboard, show_on_trip
							FROM vision_budget WHERE vision_id = ?");
		$st->execute([(int)$vision['id']]);
		$row = $st->fetch(PDO::FETCH_ASSOC);

		// Return empty defaults instead of 404 so the overlay can render cleanly.
		echo json_encode($row ?: [
			'currency' => null,
			'amount_cents' => null,
			'show_on_dashboard' => 0,
			'show_on_trip' => 0,
		]);
	}

	/** POST /api/visions/{slug}/budget */
	public static function saveBudget(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;

		$vision = vision_model::get($db, $slug);
		api_require_owner($vision);

		$cur   = strtoupper(trim($_POST['currency'] ?? ''));
		$cents = (int)($_POST['amount_cents'] ?? -1);
		$dash  = !empty($_POST['show_on_dashboard']) ? 1 : 0;
		$trip  = !empty($_POST['show_on_trip'])      ? 1 : 0;

		if ($cur === '' || !preg_match('/^[A-Z]{3}$/', $cur) || $cents < 0) {
			http_response_code(422);
			echo json_encode(['error' => 'Invalid currency or amount']);
			return;
		}

		$sql = "INSERT INTO vision_budget (vision_id, currency, amount_cents, show_on_dashboard, show_on_trip)
				VALUES (?,?,?,?,?)
				ON DUPLICATE KEY UPDATE
				  currency = VALUES(currency),
				  amount_cents = VALUES(amount_cents),
				  show_on_dashboard = VALUES(show_on_dashboard),
				  show_on_trip = VALUES(show_on_trip)";
		$ok = $db->prepare($sql)->execute([(int)$vision['id'], $cur, $cents, $dash, $trip]);

		echo json_encode(['success' => (bool)$ok]);
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
		api_require_owner($vision);

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
		api_require_owner($vision);

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
		api_require_owner($vision);

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
		api_require_owner($vision);

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
		api_require_owner($vision);

		$vcIdNum = (int)$vcId;
		// Ensure contact belongs to this vision
		$chk = $db->prepare("SELECT id FROM vision_contacts WHERE id=? AND vision_id=?");
		$chk->execute([$vcIdNum, (int)$vision['id']]);
		if (!$chk->fetch()) { http_response_code(404); echo json_encode(['error'=>'Contact not found']); return; }

		$db->prepare("DELETE FROM vision_contacts WHERE id=?")->execute([$vcIdNum]);
		echo json_encode(['success'=>true]);
	}

	/* ──────────────────────────  Goals & Milestones  ────────────────────────── */

	private static function normalizeGoalStatus($status): string
	{
		$allowed = ['not_started','in_progress','awaiting','done','cancelled'];
		return in_array($status, $allowed, true) ? $status : 'not_started';
	}

	private static function saveMilestones(PDO $db, int $goalId, array $texts, array $dones): void
	{
		$db->prepare("DELETE FROM vision_goal_milestones WHERE goal_id=?")->execute([$goalId]);
		if (!$texts) return;
		$st = $db->prepare("INSERT INTO vision_goal_milestones (goal_id, text, done, sort_order) VALUES (?,?,?,?)");
		foreach ($texts as $i => $t) {
			$text = trim((string)$t);
			if ($text === '') continue;
			$done = !empty($dones[$i]) ? 1 : 0;
			$st->execute([$goalId, $text, $done, $i]);
		}
	}

	/** GET /api/visions/{slug}/goals */
	public static function listGoals(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_owner($vision);

		$sql = "SELECT g.id, g.title, g.description, g.status, g.priority,
					   g.due_date, g.completed_at, g.created_at,
					   COUNT(m.id) AS milestone_total,
					   COALESCE(SUM(m.done), 0) AS milestone_done
				  FROM vision_goals g
				  LEFT JOIN vision_goal_milestones m ON m.goal_id = g.id
				 WHERE g.vision_id = ?
				 GROUP BY g.id
				 ORDER BY (g.status IN ('done','cancelled')) ASC,
						  g.priority ASC,
						  (g.due_date IS NULL) ASC,
						  g.due_date ASC,
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
		api_require_owner($vision);

		$gid = (int)$goalId;
		$g = $db->prepare("SELECT * FROM vision_goals WHERE id=? AND vision_id=?");
		$g->execute([$gid, (int)$vision['id']]);
		$goal = $g->fetch(PDO::FETCH_ASSOC);
		if (!$goal) { http_response_code(404); echo json_encode(['error'=>'Goal not found']); return; }

		$m = $db->prepare("SELECT id, text, done FROM vision_goal_milestones
							WHERE goal_id=? ORDER BY sort_order, id");
		$m->execute([$gid]);
		$goal['milestones'] = $m->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode($goal);
	}

	/** POST /api/visions/{slug}/goals/create */
	public static function createGoal(string $slug): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_owner($vision);

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

		$db->beginTransaction();
		try {
			$st = $db->prepare("INSERT INTO vision_goals
				(vision_id, title, description, status, priority, due_date, completed_at, show_on_trip)
				VALUES (?,?,?,?,?,?,?,?)");
			$st->execute([(int)$vision['id'], $title, $description, $status, $priority, $due, $completedAt, $showOnTrip]);
			$goalId = (int)$db->lastInsertId();
			self::saveMilestones(
				$db, $goalId,
				$_POST['milestone_texts'] ?? [],
				$_POST['milestone_dones'] ?? []
			);
			$db->commit();
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
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_owner($vision);

		$gid = (int)$goalId;
		$chk = $db->prepare("SELECT status, completed_at FROM vision_goals WHERE id=? AND vision_id=?");
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

		// Manage completed_at on transitions
		$completedAt = $existing['completed_at'];
		if ($status === 'done' && $existing['status'] !== 'done')      $completedAt = date('Y-m-d H:i:s');
		elseif ($status !== 'done' && $existing['status'] === 'done')  $completedAt = null;

		$db->beginTransaction();
		try {
			$st = $db->prepare("UPDATE vision_goals
				   SET title=?, description=?, status=?, priority=?, due_date=?, completed_at=?, show_on_trip=?
				 WHERE id=?");
			$st->execute([$title, $description, $status, $priority, $due, $completedAt, $showOnTrip, $gid]);
			self::saveMilestones(
				$db, $gid,
				$_POST['milestone_texts'] ?? [],
				$_POST['milestone_dones'] ?? []
			);
			$db->commit();
			echo json_encode(['success'=>true]);
		} catch (\Throwable $e) {
			$db->rollBack();
			http_response_code(500); echo json_encode(['error'=>$e->getMessage()]);
		}
	}

	/** DELETE /api/visions/{slug}/goals/{id}/delete */
	public static function deleteGoal(string $slug, string $goalId): void
	{
		header('Content-Type: application/json');
		global $db;
		$vision = vision_model::get($db, $slug);
		api_require_owner($vision);

		$gid = (int)$goalId;
		$chk = $db->prepare("SELECT id FROM vision_goals WHERE id=? AND vision_id=?");
		$chk->execute([$gid, (int)$vision['id']]);
		if (!$chk->fetch()) { http_response_code(404); echo json_encode(['error'=>'Goal not found']); return; }

		$db->prepare("DELETE FROM vision_goals WHERE id=?")->execute([$gid]); // milestones cascade
		echo json_encode(['success'=>true]);
	}

}
