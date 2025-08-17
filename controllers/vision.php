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
        global $db, $currentUserId;
        $userId = $currentUserId ?: 1;
        $draft  = vision_model::createDraft($db, $userId);
        header("Location: /visions/{$draft['slug']}/edit");
        exit;
    }

    /** POST /visions/store – legacy create (active) */
    public static function store(): void
    {
        global $db, $currentUserId;
        $userId = $currentUserId ?: 1;
        $title = trim($_POST['title'] ?? '');
        $desc  = $_POST['description'] ?? '';
        $id    = vision_model::create($db, $userId, $title ?: null, $desc ?: null);
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

    /** GET /visions/{slug} – show vision */
    public static function show(string $slug): void
    {
        global $db;
        $vision = vision_model::get($db, $slug);
        if (!$vision) { http_response_code(404); echo 'Vision not found'; return; }
        // fetch flags
        $st = $db->prepare("SELECT * FROM vision_presentation WHERE vision_id=?");
        $st->execute([(int)$vision['id']]);
        $presentationFlags = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        // flatten anchors
        $kv = [];
        $map = vision_model::getAnchors($db, (int)$vision['id']);
        foreach ($map as $k => $vals) {
            foreach ($vals as $v) $kv[] = ['key'=>$k,'value'=>$v];
        }
        // include view
        ob_start();
        include __DIR__.'/../views/vision_show.php';
        $content = ob_get_clean();
        $boardType = 'vision';
        include __DIR__.'/../views/layout.php';
    }

    /** GET /visions/{slug}/edit – edit form */
    public static function edit(string $slug): void
    {
        global $db;
        $vision = vision_model::get($db, $slug);
        if (!$vision) { http_response_code(404); echo 'Vision not found'; return; }
        $st = $db->prepare("SELECT * FROM vision_presentation WHERE vision_id=?");
        $st->execute([(int)$vision['id']]);
        $presentationFlags = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        // flatten anchors
        $kv = [];
        $map = vision_model::getAnchors($db, (int)$vision['id']);
        foreach ($map as $k => $vals) {
            foreach ($vals as $v) $kv[] = ['key'=>$k,'value'=>$v];
        }
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
        global $db;
        $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::setArchived($db, (int)$v['id'], true);
        header('Location: /dashboard/vision'); exit;
    }
    public static function unarchive(string $slug): void
    {
        global $db;
        $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::setArchived($db, (int)$v['id'], false);
        header('Location: /dashboard/vision/archived'); exit;
    }
    public static function destroy(string $slug): void
    {
        global $db;
        $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::softDelete($db, (int)$v['id']);
        header('Location: /dashboard/vision'); exit;
    }
    public static function restore(string $slug): void
    {
        global $db;
        $v = vision_model::get($db, $slug);
        if (!$v) { http_response_code(404); echo 'Vision not found'; return; }
        vision_model::restore($db, (int)$v['id']);
        header('Location: /dashboard/vision/trash'); exit;
    }

    /** POST /api/visions/update-basics (legacy basics save) */
    public static function updateBasics(): void
	{
		// Supports both AJAX (JSON) and normal form post (redirect kept as-is if you already do that).
		header('Content-Type: application/json');

		try {
			global $db;

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
        $vision = vision_model::get($db, $slug);
        if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }
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
    }

    /** GET /visions/{slug}/overlay/{section} – return HTML partial */
    public static function overlay(string $slug, string $section): void
    {
        global $db;
        $vision = vision_model::get($db, $slug);
        if (!$vision) { http_response_code(404); echo 'Vision not found'; return; }
        // load presentation flags so basics overlay can pre-check toggles
        $st = $db->prepare("SELECT * FROM vision_presentation WHERE vision_id=?");
        $st->execute([(int)$vision['id']]);
        $presentationFlags = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        // Pass anchor summary if needed (not used in overlays)
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
        if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }
        $id=(int)$vision['id'];
        // differentiate by section
        switch ($section) {
            case 'basics':
                // Reuse updateBasics for date/flags
                $_POST['vision_id']=$id;
                // accept JSON body too
                if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
                    $body=json_decode(file_get_contents('php://input'),true) ?: [];
                    $_POST['start_date'] = $body['start_date'] ?? null;
                    $_POST['end_date']   = $body['end_date'] ?? null;
                    foreach (['relations','goals','budget','roles','contacts','documents','workflow'] as $flag) {
                        if (isset($body[$flag])) {
                            $_POST['show_'.$flag] = $body[$flag] ? '1' : null;
                        }
                    }
                }
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
		header('Content-Type: application/json');
		try {
			global $db;
			$q = trim((string)($_GET['q'] ?? ''));
			if ($q === '') { echo json_encode([]); return; }

			// Adjust table/columns if yours differ
			$st = $db->prepare("SELECT slug AS id, title FROM moods
								 WHERE title LIKE ? OR slug LIKE ?
								 ORDER BY title LIMIT 10");
			$like = '%' . $q . '%';
			$st->execute([$like, $like]);
			echo json_encode($st->fetchAll(PDO::FETCH_ASSOC));
		} catch (Throwable $e) {
			http_response_code(500);
			echo json_encode(['error' => 'Search failed']);
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
			if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }

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

			if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }

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
		if (!$vision) { http_response_code(404); echo json_encode(['error' => 'Vision not found']); return; }

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
		if (!$vision) { http_response_code(404); echo json_encode(['error' => 'Vision not found']); return; }

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
		if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }

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
		if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }

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
		if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }

		// Expected arrays: keys[], values[]; flags: is_current, is_main, show_on_dashboard, show_on_trip
		$keys = $_POST['keys']   ?? [];
		$vals = $_POST['values'] ?? [];
		if (!$keys || !$vals || count($keys) != count($vals)) {
			http_response_code(400); echo json_encode(['error'=>'Invalid fields']); return;
		}
		// Validate a Name
		$hasName = false;
		foreach ($keys as $k) { if (trim($k)==='Name') $hasName = true; }
		if (!$hasName) { http_response_code(422); echo json_encode(['error'=>'Name is required']); return; }

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
		if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }

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
		$hasName = false;
		foreach ($keys as $k) { if (trim($k)==='Name') $hasName = true; }
		if (!$hasName) { http_response_code(422); echo json_encode(['error'=>'Name is required']); return; }

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
		if (!$vision) { http_response_code(404); echo json_encode(['error'=>'Vision not found']); return; }

		$vcIdNum = (int)$vcId;
		// Ensure contact belongs to this vision
		$chk = $db->prepare("SELECT id FROM vision_contacts WHERE id=? AND vision_id=?");
		$chk->execute([$vcIdNum, (int)$vision['id']]);
		if (!$chk->fetch()) { http_response_code(404); echo json_encode(['error'=>'Contact not found']); return; }

		$db->prepare("DELETE FROM vision_contacts WHERE id=?")->execute([$vcIdNum]);
		echo json_encode(['success'=>true]);
	}


}
