<?php
/**
 * Demo project seeder — "Load example project" on the empty dashboard.
 *
 * Builds one complete, worked example in the user's own account so the
 * Dream → Vision → Trip model is learned by clicking rather than by reading:
 * a Dream, the Vision it grew into (anchors, goals, budget, contacts,
 * itinerary, shot list), a linked Mood board, and a published Trip page.
 *
 * It's theirs — they can edit it, break it, and delete it.
 *
 * Dates are relative to today so the example never reads as stale.
 */
require_once __DIR__ . '/../app/helpers.php';

class demo_controller
{
    /** POST /demo/load */
    public static function load(): void
    {
        require_login();
        global $db, $currentUserId;

        if (!csrf_check($_POST['csrf_token'] ?? null)) {
            redirect('/');
        }

        $uid = (int)$currentUserId;

        // Guard against a double-click (and against burying a real board under
        // example data): only seed an account that has nothing yet.
        if (self::hasBoards($db, $uid)) {
            redirect('/dashboard');
        }

        try {
            $slug = self::seed($db, $uid);
            redirect('/visions/' . $slug . '/edit');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Back to the dashboard — where sign-in lands, so where the
            // button is actually clicked from.
            $_SESSION['flash_home'] = 'Could not build the example project: '
                                    . $e->getMessage();
            redirect('/dashboard');
        }
    }

    private static function hasBoards(PDO $db, int $uid): bool
    {
        $sql = "SELECT
                  (SELECT COUNT(*) FROM dream_boards WHERE user_id=? AND deleted_at IS NULL)
                + (SELECT COUNT(*) FROM visions      WHERE user_id=? AND deleted_at IS NULL)
                + (SELECT COUNT(*) FROM mood_boards  WHERE user_id=? AND deleted_at IS NULL)";
        $st = $db->prepare($sql);
        $st->execute([$uid, $uid, $uid]);
        return (int)$st->fetchColumn() > 0;
    }

    /** Build the whole example. Returns the vision slug. */
    public static function seed(PDO $db, int $uid): string
    {
        $d = fn(string $mod) => date('Y-m-d', strtotime($mod));
        $now = date('Y-m-d H:i:s');

        $db->beginTransaction();

        /* ── Mood board ─────────────────────────────────────────────────── */
        $moodSlug = make_slug(12);
        $db->prepare("INSERT INTO mood_boards (user_id, slug, title, description)
                      VALUES (?,?,?,?)")
           ->execute([$uid, $moodSlug, 'Hilux · Brazil — look & feel',
                      'Dust, low sun, and a lot of red road. Drop your reference '
                    . 'frames onto this canvas, then pin them to individual shots.']);
        $moodId = (int)$db->lastInsertId();

        // Canvas: notes and labels only — this seeds without any image assets.
        // Add your own frames from the Mood board to make it sing.
        $canvas = [
            ['label', 60,  40,  280, 46,  'THE LOOK'],
            ['note',  60,  110, 240, 130, "Golden hour or nothing.\nIf the light is flat,\nwe drive and shoot tomorrow."],
            ['note',  330, 110, 240, 130, "Dust on everything.\nThe truck should look\nused, not shiny."],
            ['label', 60,  280, 280, 46,  'MOVEMENT'],
            ['note',  60,  350, 240, 130, "Long lens, slow pans.\nLet the car leave frame\nbefore we cut."],
            ['note',  330, 350, 240, 130, "Drone: one big reveal\nper location. Don't\noveruse it."],
            ['label', 620, 40,  280, 46,  'DROP FRAMES HERE →'],
            ['note',  620, 110, 280, 180, "Add images to this board,\nthen open Shots on the Vision\nand pin them to a shot as\nreference."],
        ];
        $ci = $db->prepare("INSERT INTO canvas_items
              (board_id, kind, x, y, w, h, z, rotation, payload_json, style_json,
               created_at, updated_at)
              VALUES (?,?,?,?,?,?,?,0,?,NULL,?,?)");
        $z = 1;
        foreach ($canvas as [$kind, $x, $y, $w, $h, $text]) {
            $ci->execute([$moodId, $kind, $x, $y, $w, $h, $z++,
                          json_encode(['text' => $text]), $now, $now]);
        }

        /* ── Dream — the one-line wish it all started as ─────────────────── */
        $dreamSlug = make_slug(10);
        $db->prepare("INSERT INTO dream_boards (slug, user_id, title, description, type)
                      VALUES (?,?,?,?,'dream')")
           ->execute([$dreamSlug, $uid,
               'Get sponsored by Toyota and drive a Hilux across Brazil',
               'Caught on a roadside with no signal. That is all a Dream needs to be — '
             . 'one sentence, before it evaporates. Everything below grew out of this.']);
        $dreamId = (int)$db->lastInsertId();

        /* ── Vision — what the Dream became ─────────────────────────────── */
        $visionSlug = make_slug(12);
        $tripToken  = bin2hex(random_bytes(16));
        $db->prepare("INSERT INTO visions
              (user_id, slug, title, description, start_date, end_date,
               mood_id, show_mood_on_dashboard, show_mood_on_trip,
               status, workflow_status, workflow_notes, dream_id,
               trip_enabled, trip_token, show_on_dashboard)
              VALUES (?,?,?,?,?,?,?,1,1,'active','in_progress',?,?,1,?,1)")
           ->execute([
               $uid, $visionSlug, 'Hilux · Brazil',
               '<p>Three weeks, one truck, one road film. Toyota Brasil provides the '
             . 'vehicle; we deliver a documentary short plus a cutdown for their socials.</p>'
             . '<p><em>This is an example project — click into every section, change '
             . 'anything, and delete it when you are done.</em></p>',
               $d('+30 days'), $d('+51 days'),
               $moodSlug,
               "Contract out with Toyota Brasil, waiting on signature.\n"
             . "Route locked as far as Lençóis. Second half still open.",
               $dreamId, $tripToken,
           ]);
        $visionId = (int)$db->lastInsertId();

        // Close the loop: the mood board points back at its vision
        $db->prepare("UPDATE mood_boards SET vision_id=? WHERE id=?")
           ->execute([$visionId, $moodId]);

        /* ── Anchors — the who/where/what this touches ──────────────────── */
        $anchors = [
            ['locations', 'Rio de Janeiro'],
            ['locations', 'Arpoador'],
            ['locations', 'Chapada Diamantina'],
            ['brands',    'Toyota'],
            ['people',    'Ana Ribeiro'],
            ['people',    'Mike Sandberg'],
            ['seasons',   'Dry season'],
        ];
        $aq = $db->prepare("INSERT INTO vision_anchors (board_id, `key`, `value`) VALUES (?,?,?)");
        foreach ($anchors as [$k, $v]) $aq->execute([$visionId, $k, $v]);

        /* ── Goals + milestones ─────────────────────────────────────────── */
        $goals = [
            [
                'Sign the Toyota sponsorship', 1, 'in_progress', $d('+14 days'),
                'Vehicle, fuel and insurance covered in exchange for a 90s cutdown.',
                [['Send the treatment', 1], ['Agree deliverables', 1],
                 ['Legal review', 0], ['Countersign', 0]],
            ],
            [
                'Lock the route and permits', 2, 'in_progress', $d('+21 days'),
                'Drone permits for the national park take about two weeks.',
                [['Map the drive days', 1], ['Apply for park drone permit', 0],
                 ['Book the Lençóis hotel', 0]],
            ],
            [
                'Crew confirmed', 3, 'not_started', $d('+25 days'),
                'One AC, one producer on the ground.',
                [['Confirm Mike (1st AC)', 0], ['Local fixer in Salvador', 0]],
            ],
        ];
        $gq = $db->prepare("INSERT INTO vision_goals
              (vision_id, title, description, status, priority, due_date, show_on_trip)
              VALUES (?,?,?,?,?,?,1)");
        $mq = $db->prepare("INSERT INTO vision_goal_milestones (goal_id, text, done, sort_order)
                            VALUES (?,?,?,?)");
        foreach ($goals as [$title, $prio, $status, $due, $desc, $miles]) {
            $gq->execute([$visionId, $title, $desc, $status, $prio, $due]);
            $gid = (int)$db->lastInsertId();
            $o = 0;
            foreach ($miles as [$text, $done]) $mq->execute([$gid, $text, $done, $o++]);
        }

        /* ── Budget: a manual total, with the lines planned so far ──────── */
        $db->prepare("INSERT INTO vision_budget
              (vision_id, currency, amount_cents, show_on_dashboard, show_on_trip)
              VALUES (?,?,?,1,1)")
           ->execute([$visionId, 'DKK', 12000000]); // 120,000.00
        $items = [
            ['Flights (2 crew)',        1450000, 1],
            ['Insurance & permits',      380000, 1],
            ['Fuel — 4,200 km',          620000, 0],
            ['Hotels, 19 nights',       2100000, 0],
            ['Local fixer, 6 days',      900000, 0],
            ['Drone batteries & media',  240000, 1],
        ];
        $bq = $db->prepare("INSERT INTO vision_budget_items
              (vision_id, label, amount_cents, paid, sort_order, show_on_trip)
              VALUES (?,?,?,?,?,1)");
        $o = 0;
        foreach ($items as [$label, $cents, $paid]) {
            $bq->execute([$visionId, $label, $cents, $paid, $o++]);
        }

        /* ── Contacts — the people behind the deal ──────────────────────── */
        $contacts = [
            [1, 1, [['Name','Ana Ribeiro'], ['Company','Toyota Brasil'],
                    ['Email','ana.ribeiro@example.com'], ['Mobile','+55 21 90000 0000'],
                    ['Country','Brazil']]],
            [1, 0, [['Name','Mike Sandberg'], ['Company','Freelance — 1st AC'],
                    ['Email','mike@example.com'], ['Country','Denmark']]],
            [1, 0, [['Name','Pousada Lençóis'], ['Company','Hotel — 19 nights'],
                    ['Email','stay@example.com'], ['Address',"Rua das Pedras 12\nLençóis, BA"]]],
        ];
        $cq = $db->prepare("INSERT INTO vision_contacts
              (vision_id, is_current, is_main, show_on_dashboard, show_on_trip)
              VALUES (?,?,?,1,1)");
        $fq = $db->prepare("INSERT INTO vision_contact_fields
              (vision_contact_id, field_key, field_value, field_order) VALUES (?,?,?,?)");
        foreach ($contacts as [$cur, $main, $fields]) {
            $cq->execute([$visionId, $cur, $main]);
            $cid = (int)$db->lastInsertId();
            $o = 0;
            foreach ($fields as [$k, $v]) $fq->execute([$cid, $k, $v, $o++]);
        }

        /* ── Itinerary — where you'll be ────────────────────────────────── */
        $itin = [
            [$d('+30 days'), '10:45', 'Land in Rio · pick up the Hilux',    'Galeão Airport, Rio de Janeiro', 'Toyota hands over at the airport desk. Check the tyres before we sign.'],
            [$d('+30 days'), '16:00', 'Recce Arpoador for the sunrise',      'Arpoador, Rio de Janeiro',       'Find the parking spot for the drone launch.'],
            [$d('+31 days'), '06:20', 'Sunrise shoot',                        'Arpoador, Rio de Janeiro',       'Be set up 30 min before. Light goes fast.'],
            [$d('+31 days'), '14:00', 'Drive north — 380 km',                 'BR-101 north',                   null],
            [$d('+33 days'), '09:00', 'Chapada Diamantina — park day',        'Chapada Diamantina, Bahia',      'Drone permit must be printed and in the glovebox.'],
        ];
        $iq = $db->prepare("INSERT INTO vision_itinerary
              (vision_id, day_date, start_time, title, location, notes, show_on_trip)
              VALUES (?,?,?,?,?,?,1)");
        foreach ($itin as [$day, $time, $title, $loc, $notes]) {
            $iq->execute([$visionId, $day, $time, $title, $loc, $notes]);
        }

        /* ── Shots — what you came to capture ───────────────────────────── */
        $shots = [
            [$d('+31 days'), 'Sunrise drone reveal over Arpoador', 'drone', 'sunrise',
             'Arpoador, Rio de Janeiro', 1, 'planned',
             "Low pass south to north, rise on the rock, end with the Hilux parked on the road below.\nOne battery only — get it first take."],
            [$d('+31 days'), 'Piece to camera — why this road', 'interview', 'golden',
             'Arpoador, Rio de Janeiro', 1, 'planned',
             "Sitting on the tailgate, ocean behind.\nSay the sponsor name once, naturally. Don't sell it."],
            [$d('+31 days'), 'Hilux leaving frame, long lens', 'broll', 'golden',
             'BR-101 north', 0, 'captured',
             'Let it fully exit before cutting. 200mm, heat haze is a feature.'],
            [$d('+30 days'), 'Handover at the airport desk', 'broll', null,
             'Galeão Airport, Rio de Janeiro', 0, 'captured',
             'Keys into hand, close. Toyota will want this one.'],
            [$d('+33 days'), 'Waterfall wide with truck for scale', 'drone', 'midday',
             'Chapada Diamantina, Bahia', 1, 'planned',
             'Truck must be visible but tiny. Scale is the whole point of the shot.'],
            [$d('+33 days'), 'Night timelapse, stars over the truck', 'timelapse', 'night',
             'Chapada Diamantina, Bahia', 0, 'planned',
             "25s exposures, 4h run. Kill every light.\nBring the spare battery grip."],
            [null, 'Refuel stop — dust, hands, pump', 'broll', null, null, 0, 'planned',
             'Any gas station. The grubbier the better.'],
            [null, 'Red dust building on the paintwork', 'photo', null, null, 1, 'planned',
             'Shoot this every few days so we can cut a progression.'],
            [null, 'Anything that makes you brake suddenly', 'pov', null, null, 0, 'planned',
             "The whole reason this app exists. Pull over, add a Dream, keep driving."],
        ];
        $sq = $db->prepare("INSERT INTO vision_shots
              (vision_id, day_date, title, shot_type, light, location, priority,
               status, how_notes, captured_at, sort_order, show_on_trip)
              VALUES (?,?,?,?,?,?,?,?,?,?,?,1)");
        $o = 0;
        foreach ($shots as [$day, $title, $type, $light, $loc, $prio, $status, $how]) {
            $sq->execute([$visionId, $day, $title, $type, $light, $loc, $prio,
                          $status, $how, $status === 'captured' ? $now : null, $o++]);
        }

        /* ── What the Trip page shows ───────────────────────────────────── */
        // Roles stays 0: the collaborator list isn't for a public trip page.
        $db->prepare("INSERT INTO vision_presentation
              (vision_id, relations, anchors, itinerary, shots, goals, budget,
               roles, contacts, documents, workflow)
              VALUES (?,1,1,1,1,1,1,0,1,1,1)")
           ->execute([$visionId]);

        $db->commit();
        return $visionSlug;
    }
}
