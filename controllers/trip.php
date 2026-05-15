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
    public static function show(string $slug): void
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

        // Linked mood board + media gallery
        $mood = null;
        $moodMedia = [];
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
            }
        }

        // Goals + milestones (skip cancelled; sort by active → priority → due date)
        $goals = [];
        if ($sectionVisible('goals')) {
            $gs = $db->prepare("
                SELECT id, title, description, status, priority, due_date, completed_at
                  FROM vision_goals
                 WHERE vision_id = ? AND status != 'cancelled'
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

        // Documents (section flag only — no per-doc trip flag yet)
        $documents = [];
        if ($sectionVisible('documents')) {
            $documents = document_model::allForVision($db, $visionId);
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

        include __DIR__ . '/../views/trip_show.php';
    }
}
