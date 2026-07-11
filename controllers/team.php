<?php
/**
 * Team controller — private collaborator groups per user.
 *
 * Teams are the owner's address book: only the owner sees and manages
 * their teams. Each member has a default_role that is applied when the
 * whole team is added to a board (snapshot copy into vision_roles —
 * later team changes do NOT propagate to boards).
 */

class team_controller
{
    /** Resolve a team owned by the current user (admins pass), or emit 404 JSON. */
    private static function ownTeamOr404(PDO $db, string $teamId): ?array
    {
        global $currentUserId;
        $st = $db->prepare("SELECT * FROM teams WHERE id = ? LIMIT 1");
        $st->execute([(int)$teamId]);
        $team = $st->fetch(PDO::FETCH_ASSOC);
        if (!$team || ((int)$team['owner_user_id'] !== (int)$currentUserId && !is_admin())) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Team not found']);
            return null;
        }
        return $team;
    }

    /** GET /teams — my teams page */
    public static function index(): void
    {
        require_login();
        global $db, $currentUserId;

        $teams = [];
        try {
            $ts = $db->prepare("SELECT * FROM teams WHERE owner_user_id = ? ORDER BY name ASC");
            $ts->execute([(int)$currentUserId]);
            $teams = $ts->fetchAll(PDO::FETCH_ASSOC);

            if ($teams) {
                $teamIds = array_map('intval', array_column($teams, 'id'));
                $in = implode(',', array_fill(0, count($teamIds), '?'));

                // Members with profile info (visible here because they're on your team)
                $ms = $db->prepare("
                    SELECT tm.id, tm.team_id, tm.user_id, tm.default_role,
                           u.name, u.email, u.company, u.organisation, u.last_login_at
                      FROM team_members tm
                      JOIN users u ON u.id = tm.user_id
                     WHERE tm.team_id IN ($in)
                     ORDER BY u.name ASC
                ");
                $ms->execute($teamIds);
                $membersByTeam = [];
                $memberUserIds = [];
                foreach ($ms->fetchAll(PDO::FETCH_ASSOC) as $m) {
                    $membersByTeam[(int)$m['team_id']][] = $m;
                    $memberUserIds[(int)$m['user_id']] = true;
                }
                foreach ($teams as &$t) {
                    $t['members'] = $membersByTeam[(int)$t['id']] ?? [];
                }
                unset($t);

                // Board audit: my visions each member has a role on
                $boardsByUser = [];
                if ($memberUserIds) {
                    $uids = array_keys($memberUserIds);
                    $uin  = implode(',', array_fill(0, count($uids), '?'));
                    $bs = $db->prepare("
                        SELECT vr.user_id, vr.role, v.slug, v.title
                          FROM vision_roles vr
                          JOIN visions v ON v.id = vr.vision_id
                         WHERE v.user_id = ? AND v.deleted_at IS NULL
                           AND vr.user_id IN ($uin)
                         ORDER BY v.updated_at DESC
                    ");
                    $bs->execute(array_merge([(int)$currentUserId], $uids));
                    foreach ($bs->fetchAll(PDO::FETCH_ASSOC) as $b) {
                        $boardsByUser[(int)$b['user_id']][] = $b;
                    }
                }
            }
        } catch (\Throwable $e) {
            $migrationMissing = true; // teams tables not created yet
        }

        $boardsByUser = $boardsByUser ?? [];
        $pageTitle = 'My teams';
        $noSidebar = true;
        ob_start();
        include __DIR__ . '/../views/teams.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** GET /api/teams — JSON: my teams + members (feeds the roles overlay) */
    public static function listMine(): void
    {
        api_require_login();
        header('Content-Type: application/json');
        global $db, $currentUserId;
        try {
            $ts = $db->prepare("SELECT id, name FROM teams WHERE owner_user_id = ? ORDER BY name ASC");
            $ts->execute([(int)$currentUserId]);
            $teams = $ts->fetchAll(PDO::FETCH_ASSOC);
            foreach ($teams as &$t) {
                $ms = $db->prepare("
                    SELECT tm.user_id, tm.default_role, u.name, u.email
                      FROM team_members tm
                      JOIN users u ON u.id = tm.user_id
                     WHERE tm.team_id = ?
                     ORDER BY u.name ASC
                ");
                $ms->execute([(int)$t['id']]);
                $t['members'] = $ms->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($t);
            echo json_encode(['success' => true, 'teams' => $teams]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => true, 'teams' => []]); // not migrated yet
        }
    }

    /** POST /api/teams/create  body: name */
    public static function create(): void
    {
        api_require_login();
        header('Content-Type: application/json');
        global $db, $currentUserId;

        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') { http_response_code(422); echo json_encode(['error' => 'Team name required']); return; }
        if (mb_strlen($name) > 120) $name = mb_substr($name, 0, 120);

        $db->prepare("INSERT INTO teams (owner_user_id, name) VALUES (?, ?)")
           ->execute([(int)$currentUserId, $name]);
        echo json_encode(['success' => true, 'team_id' => (int)$db->lastInsertId()]);
    }

    /** POST /api/teams/{id}/rename  body: name */
    public static function rename(string $teamId): void
    {
        api_require_login();
        header('Content-Type: application/json');
        global $db;
        $team = self::ownTeamOr404($db, $teamId);
        if (!$team) return;

        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') { http_response_code(422); echo json_encode(['error' => 'Team name required']); return; }
        $db->prepare("UPDATE teams SET name = ? WHERE id = ?")->execute([mb_substr($name, 0, 120), (int)$team['id']]);
        echo json_encode(['success' => true]);
    }

    /** POST /api/teams/{id}/delete — members cascade via FK */
    public static function deleteTeam(string $teamId): void
    {
        api_require_login();
        header('Content-Type: application/json');
        global $db;
        $team = self::ownTeamOr404($db, $teamId);
        if (!$team) return;

        $db->prepare("DELETE FROM teams WHERE id = ?")->execute([(int)$team['id']]);
        echo json_encode(['success' => true]);
    }

    /** POST /api/teams/{id}/members/add  body: email, role */
    public static function addMember(string $teamId): void
    {
        api_require_login();
        header('Content-Type: application/json');
        global $db, $currentUserId;
        $team = self::ownTeamOr404($db, $teamId);
        if (!$team) return;

        $email = trim((string)($_POST['email'] ?? ''));
        $role  = (string)($_POST['role'] ?? 'viewer');
        $allowed = ['co_owner','editor','viewer','delegate'];
        if (!in_array($role, $allowed, true)) $role = 'viewer';
        if ($email === '') { http_response_code(422); echo json_encode(['error' => 'Email required']); return; }

        $us = $db->prepare("SELECT id, name, email FROM users WHERE email = ? LIMIT 1");
        $us->execute([$email]);
        $user = $us->fetch(PDO::FETCH_ASSOC);
        if (!$user) { http_response_code(404); echo json_encode(['error' => 'No user with that email']); return; }
        if ((int)$user['id'] === (int)$currentUserId) {
            http_response_code(422); echo json_encode(['error' => "You're the team owner — no need to add yourself"]); return;
        }

        $db->prepare("INSERT INTO team_members (team_id, user_id, default_role)
                      VALUES (?,?,?)
                      ON DUPLICATE KEY UPDATE default_role = VALUES(default_role)")
           ->execute([(int)$team['id'], (int)$user['id'], $role]);
        echo json_encode(['success' => true]);
    }

    /** POST /api/teams/{id}/members/{memberId}/role  body: role */
    public static function setMemberRole(string $teamId, string $memberId): void
    {
        api_require_login();
        header('Content-Type: application/json');
        global $db;
        $team = self::ownTeamOr404($db, $teamId);
        if (!$team) return;

        $role = (string)($_POST['role'] ?? '');
        $allowed = ['co_owner','editor','viewer','delegate'];
        if (!in_array($role, $allowed, true)) {
            http_response_code(422); echo json_encode(['error' => 'Invalid role']); return;
        }
        $db->prepare("UPDATE team_members SET default_role = ? WHERE id = ? AND team_id = ?")
           ->execute([$role, (int)$memberId, (int)$team['id']]);
        echo json_encode(['success' => true]);
    }

    /** POST /api/teams/{id}/members/{memberId}/delete */
    public static function removeMember(string $teamId, string $memberId): void
    {
        api_require_login();
        header('Content-Type: application/json');
        global $db;
        $team = self::ownTeamOr404($db, $teamId);
        if (!$team) return;

        $db->prepare("DELETE FROM team_members WHERE id = ? AND team_id = ?")
           ->execute([(int)$memberId, (int)$team['id']]);
        echo json_encode(['success' => true]);
    }
}
