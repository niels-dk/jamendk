<?php
/**
 * Trip controller — read-only, shareable aggregated view of a Vision.
 *
 * A Trip is auto-generated from a Vision's data plus its linked Mood board.
 * Visibility is controlled by:
 *   - vision_presentation.{section} = 1   → the section appears
 *   - visions.show_mood_on_trip = 1       → mood board is included
 *   - vision_budget.show_on_trip = 1      → budget row is visible
 *   - vision_contacts.show_on_trip = 1    → contact row is visible
 *
 * URL: /trips/{vision_slug}  (no auth — meant to be shareable)
 */

require_once __DIR__ . '/../models/vision.php';
require_once __DIR__ . '/../models/mood.php';
require_once __DIR__ . '/../models/document_model.php';

class trip_controller
{
    /** GET /trips/{slug}/download — self-contained offline HTML copy. */
    public static function download(string $slug): void
    {
        self::show($slug, true);
    }

    public static function show(string $slug, bool $export = false): void
    {
        global $db;

        // Resolve the Vision
        $vision = vision_model::get($db, $slug);
        if (!$vision) {
            http_response_code(404);
            echo 'Trip not found';
            return;
        }
        $visionId = (int)$vision['id'];

        // Master switch: if the owner hasn't published the trip, short-circuit
        // with a friendly "not published" page rather than rendering content.
        $tripEnabled = !empty($vision['trip_enabled']);
        if (!$tripEnabled) {
            $title = $vision['title'] ?: 'Trip';
            include __DIR__ . '/../views/trip_disabled.php';
            return;
        }

        // Source dream lineage (visions promoted from a dream)
        $sourceDream = null;
        if (!empty($vision['dream_id'])) {
            $ds = $db->prepare("SELECT slug, title FROM dream_boards
                                  WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $ds->execute([(int)$vision['dream_id']]);
            $sourceDream = $ds->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        // Section-level visibility (vision_presentation row, with sensible defaults)
        $defaults = [
            'relations' => 1, 'goals' => 1, 'budget' => 1, 'roles' => 0,
            'contacts'  => 1, 'documents' => 1, 'workflow' => 1,
        ];
        $st = $db->prepare("SELECT * FROM vision_presentation WHERE vision_id=?");
        $st->execute([$visionId]);
        $vp = $st->fetch(PDO::FETCH_ASSOC) ?: $defaults;
        $sectionVisible = function (string $k) use ($vp, $defaults): bool {
            return (int)($vp[$k] ?? $defaults[$k] ?? 0) === 1;
        };

        // Anchors (no per-anchor flag — included whenever the vision has any)
        $anchors = vision_model::getAnchors($db, $visionId);

        // Linked mood board + canvas snapshot
        $mood = null;
        $moodMedia = [];
        $canvasItems = [];      // visible items keyed by id
        $canvasBounds = null;   // ['minX','minY','cw','ch']
        if ($sectionVisible('relations')
            && !empty($vision['mood_id'])
            && !empty($vision['show_mood_on_trip'])) {
            $ms = $db->prepare("SELECT id, slug, title, description
                                  FROM mood_boards
                                 WHERE slug=? AND deleted_at IS NULL
                                 LIMIT 1");
            $ms->execute([$vision['mood_id']]);
            $mood = $ms->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($mood) {
                // Media library (used as fallback / supplemental gallery)
                $mm = $db->prepare("
                    SELECT vm.id, vm.uuid, vm.file_name, vm.mime_type,
                           vm.provider, vm.provider_id
                      FROM vision_media vm
                      INNER JOIN mood_board_media mbm ON mbm.media_id = vm.id
                     WHERE mbm.board_id = ?
                     ORDER BY mbm.added_at DESC, vm.id DESC
                ");
                $mm->execute([(int)$mood['id']]);
                $moodMedia = $mm->fetchAll(PDO::FETCH_ASSOC);

                // Canvas items (for the snapshot section)
                $cs = $db->prepare("
                    SELECT ci.id, ci.kind, ci.x, ci.y, ci.w, ci.h, ci.z, ci.rotation,
                           ci.payload_json, ci.style_json,
                           ci.media_id,
                           vm.uuid AS media_uuid, vm.mime_type AS media_mime,
                           vm.provider AS media_provider, vm.provider_id AS media_provider_id
                      FROM canvas_items ci
                      LEFT JOIN vision_media vm ON vm.id = ci.media_id
                     WHERE ci.board_id = ? AND ci.hidden = 0
                     ORDER BY ci.z ASC, ci.id ASC
                ");
                $cs->execute([(int)$mood['id']]);
                $rawItems = $cs->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rawItems as $row) {
                    $payload = $row['payload_json'] ? json_decode($row['payload_json'], true) : [];
                    $style   = $row['style_json']   ? json_decode($row['style_json'],   true) : [];
                    $canvasItems[(int)$row['id']] = [
                        'id'       => (int)$row['id'],
                        'kind'     => $row['kind'],
                        'x'        => (int)$row['x'],
                        'y'        => (int)$row['y'],
                        'w'        => (int)$row['w'],
                        'h'        => (int)$row['h'],
                        'z'        => (int)$row['z'],
                        'rotation' => (int)$row['rotation'],
                        'payload'  => is_array($payload) ? $payload : [],
                        'style'    => is_array($style)   ? $style   : [],
                        'media'    => !empty($row['media_id']) ? [
                            'uuid'        => $row['media_uuid'],
                            'mime_type'   => $row['media_mime'],
                            'provider'    => $row['media_provider'],
                            'provider_id' => $row['media_provider_id'],
                        ] : null,
                    ];
                }
                // Compute bounding box across non-connector items
                $minX = PHP_INT_MAX; $minY = PHP_INT_MAX;
                $maxX = PHP_INT_MIN; $maxY = PHP_INT_MIN;
                foreach ($canvasItems as $ci) {
                    if ($ci['kind'] === 'connector') continue;
                    $minX = min($minX, $ci['x']);
                    $minY = min($minY, $ci['y']);
                    $maxX = max($maxX, $ci['x'] + max(1, $ci['w']));
                    $maxY = max($maxY, $ci['y'] + max(1, $ci['h']));
                }
                if ($minX !== PHP_INT_MAX) {
                    $cw = max(1, $maxX - $minX);
                    $ch = max(1, $maxY - $minY);
                    $canvasBounds = ['minX' => $minX, 'minY' => $minY, 'cw' => $cw, 'ch' => $ch];
                }
            }
        }

        // Goals + milestones (skip cancelled; sort by active → priority → due date)
        // Per-goal Show on Trip flag filters items out at this point.
        $goals = [];
        if ($sectionVisible('goals')) {
            $gs = $db->prepare("
                SELECT id, title, description, status, priority, due_date, completed_at
                  FROM vision_goals
                 WHERE vision_id = ?
                   AND status != 'cancelled'
                   AND show_on_trip = 1
                 ORDER BY (status='done') ASC,
                          priority ASC,
                          (due_date IS NULL) ASC,
                          due_date ASC,
                          id ASC
            ");
            $gs->execute([$visionId]);
            $goals = $gs->fetchAll(PDO::FETCH_ASSOC);
            if ($goals) {
                $gIds = array_map('intval', array_column($goals, 'id'));
                $in   = implode(',', array_fill(0, count($gIds), '?'));
                $ms2  = $db->prepare("
                    SELECT goal_id, text, done
                      FROM vision_goal_milestones
                     WHERE goal_id IN ($in)
                     ORDER BY goal_id, sort_order, id
                ");
                $ms2->execute($gIds);
                $msByGoal = [];
                foreach ($ms2->fetchAll(PDO::FETCH_ASSOC) as $m) {
                    $msByGoal[(int)$m['goal_id']][] = $m;
                }
                foreach ($goals as &$g) {
                    $g['milestones'] = $msByGoal[(int)$g['id']] ?? [];
                }
                unset($g);
            }
        }

        // Budget (only if marked visible on trip)
        $budget = null;
        if ($sectionVisible('budget')) {
            $bs = $db->prepare("
                SELECT currency, amount_cents, show_on_trip
                  FROM vision_budget
                 WHERE vision_id = ? AND show_on_trip = 1
                 LIMIT 1
            ");
            $bs->execute([$visionId]);
            $budget = $bs->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        // Contacts (per-contact show_on_trip)
        $contacts = [];
        if ($sectionVisible('contacts')) {
            $cs = $db->prepare("
                SELECT vc.id, vc.is_current, vc.is_main,
                       MAX(CASE WHEN f.field_key='Name'    THEN f.field_value END) AS name,
                       MAX(CASE WHEN f.field_key='Company' THEN f.field_value END) AS company,
                       MAX(CASE WHEN f.field_key='Email'   THEN f.field_value END) AS email,
                       MAX(CASE WHEN f.field_key='Mobile'  THEN f.field_value END) AS mobile,
                       MAX(CASE WHEN f.field_key='Address' THEN f.field_value END) AS address
                  FROM vision_contacts vc
                  JOIN vision_contact_fields f ON f.vision_contact_id = vc.id
                 WHERE vc.vision_id = ? AND vc.show_on_trip = 1
                 GROUP BY vc.id, vc.is_current, vc.is_main
                 ORDER BY vc.is_main DESC, vc.id ASC
            ");
            $cs->execute([$visionId]);
            $contacts = $cs->fetchAll(PDO::FETCH_ASSOC);
        }

        // Documents — section flag gates the block, per-doc show_on_trip
        // (default 1) filters which rows appear.
        $documents = [];
        if ($sectionVisible('documents')) {
            $all = document_model::allForVision($db, $visionId);
            $documents = array_values(array_filter($all, function ($d) {
                // Default visible if the column doesn't exist or is null
                return !array_key_exists('show_on_trip', $d) || (int)$d['show_on_trip'] === 1;
            }));
        }

        // Workflow (status + notes)
        $workflow = null;
        if ($sectionVisible('workflow')) {
            $st = (string)($vision['workflow_status'] ?? 'not_started');
            $nt = (string)($vision['workflow_notes']  ?? '');
            if ($st !== 'not_started' || $nt !== '') {
                $workflow = ['status' => $st, 'notes' => $nt];
            }
        }

        // Empty-state check — if literally nothing is visible
        $hasAnyContent = !empty($vision['description'])
            || !empty($anchors)
            || $mood
            || !empty($goals)
            || $budget
            || !empty($contacts)
            || !empty($documents)
            || $workflow;

        if (!$export) {
            include __DIR__ . '/../views/trip_show.php';
            return;
        }

        // ── Offline export ────────────────────────────────────────────────
        // Embed documents as data: URIs so the download links work with no
        // internet. Files are AES-encrypted at rest — decrypt like download().
        // Caps keep the HTML manageable: ≤8 MB per doc, ≤25 MB total (plain).
        $docEmbeds  = [];  // uuid => data URI (or absent when too big / unreadable)
        $embedTotal = 0;
        $perDocCap  = 8  * 1024 * 1024;
        $totalCap   = 25 * 1024 * 1024;
        $storageDir = __DIR__ . '/../storage/private';
        foreach ($documents as $doc) {
            $uuid = $doc['uuid'] ?? '';
            $size = (int)($doc['file_size'] ?? 0);
            if ($uuid === '' || empty($doc['enc_key'])) continue;
            if ($size > $perDocCap || ($embedTotal + $size) > $totalCap) continue;
            $path = $storageDir . '/' . $uuid;
            if (!is_file($path)) continue;
            $data = @file_get_contents($path);
            if ($data === false || strlen($data) < 17) continue;
            $iv     = substr($data, 0, 16);
            $cipher = substr($data, 16);
            $key    = base64_decode($doc['enc_key'], true);
            if ($key === false) continue;
            $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            if ($plain === false) continue;
            $embedTotal += strlen($plain);
            $mime = $doc['mime_type'] ?: 'application/octet-stream';
            $docEmbeds[$uuid] = 'data:' . $mime . ';base64,' . base64_encode($plain);
        }

        ob_start();
        include __DIR__ . '/../views/trip_show.php';
        $html = ob_get_clean();

        // Attachment filename: trip-{title}-{date}.html
        $safe = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $vision['title'] ?: 'trip'), '-'));
        if ($safe === '') $safe = 'trip';
        $filename = 'trip-' . substr($safe, 0, 60) . '-' . date('Y-m-d') . '.html';

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($html));
        echo $html;
    }
}
