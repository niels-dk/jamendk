<?php
/**
 * Pricing — the shadow-pricing engine.
 *
 * The whole tier model is live: seats are counted, a tier is resolved, and a
 * "would-be" monthly price is calculated for every account. But nothing
 * charges — it's free while in beta. When payments switch on later, a checkout
 * bolts onto numbers that already exist and have been tested on real teams.
 *
 * The meter is PEOPLE, never features. A solo creator has everything, forever.
 * You only move up a band when people work WITH you:
 *   seats = the owner + everyone in the owner's Teams + everyone given a role
 *           on the owner's boards (distinct humans).
 * Public Trip-page viewers are unlimited and never counted — that's the whole
 * point of a Trip page.
 *
 * ── To change prices: edit TIERS below. That's the only place. ──
 */
class Pricing
{
    /** ISO currency + display symbol. */
    const CURRENCY = 'EUR';
    const SYMBOL   = '€';

    /**
     * Bands, cheapest first. Each: [key, label, min_people, max_people(or null),
     * monthly_cents, yearly_cents]. Yearly is 10× monthly = two months free.
     *
     * Free ceiling is 4 (a creator plus three) so the jump to paid lands on a
     * real fifth teammate, not a cliff at the first collaborator. Shift the
     * Crew max / Studio min together if you want strict "3 free, 4 paid".
     */
    const TIERS = [
        ['solo',       'Solo',        1,  1,    0,     0],
        ['crew',       'Crew',        2,  4,    0,     0],
        ['studio',     'Studio',      5,  8,    900,   9000],
        ['production', 'Production',  9,  20,   1900,  19000],
        ['network',    'Network',     21, null, 4900,  49000],
    ];

    /** Format cents in the configured currency: 1500 → "€15". */
    public static function money(int $cents): string
    {
        $whole = $cents / 100;
        // Whole euros show without decimals; anything else keeps two.
        $n = ($cents % 100 === 0)
            ? number_format($whole, 0, '.', ',')
            : number_format($whole, 2, '.', ',');
        return self::SYMBOL . $n;
    }

    /**
     * Distinct people who work with this owner, counting the owner.
     * Positional params (the app runs EMULATE_PREPARES=false, so a named
     * placeholder can't be reused across the query).
     */
    public static function seatCount(PDO $db, int $ownerId): int
    {
        if ($ownerId <= 0) return 1;
        try {
            $sql = "
                SELECT COUNT(*) FROM (
                    SELECT tm.user_id AS uid
                      FROM teams t
                      JOIN team_members tm ON tm.team_id = t.id
                     WHERE t.owner_user_id = ? AND tm.user_id <> ?
                    UNION
                    SELECT vr.user_id AS uid
                      FROM visions v
                      JOIN vision_roles vr ON vr.vision_id = v.id
                     WHERE v.user_id = ? AND vr.user_id <> ? AND v.deleted_at IS NULL
                ) AS collaborators";
            $st = $db->prepare($sql);
            $st->execute([$ownerId, $ownerId, $ownerId, $ownerId]);
            return 1 + (int)$st->fetchColumn();   // + the owner themselves
        } catch (\Throwable $e) {
            return 1; // teams/roles not migrated — treat as solo
        }
    }

    /** Index of a headcount's tier within TIERS (0 = cheapest). */
    public static function tierIndex(int $seats): int
    {
        foreach (self::TIERS as $i => [$key, $label, $min, $max]) {
            if ($seats >= $min && ($max === null || $seats <= $max)) return $i;
        }
        return count(self::TIERS) - 1;
    }

    /**
     * Is this user already counted toward the owner's seats? (Already in one of
     * the owner's Teams, or holding a role on another of the owner's boards.)
     * Used to tell a genuinely new teammate from a re-share of someone already
     * on the account — only the former can move a tier.
     */
    public static function isCountedFor(PDO $db, int $ownerId, int $userId): bool
    {
        if ($userId === $ownerId) return true; // the owner is always "counted"
        try {
            $sql = "
                SELECT 1 FROM team_members tm
                  JOIN teams t ON t.id = tm.team_id
                 WHERE t.owner_user_id = ? AND tm.user_id = ?
                UNION
                SELECT 1 FROM vision_roles vr
                  JOIN visions v ON v.id = vr.vision_id
                 WHERE v.user_id = ? AND vr.user_id = ? AND v.deleted_at IS NULL
                LIMIT 1";
            $st = $db->prepare($sql);
            $st->execute([$ownerId, $userId, $ownerId, $userId]);
            return (bool)$st->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * If the owner adds $newPeople genuinely-new collaborators, does that cross
     * into a higher tier? Returns the crossing details (for a heads-up prompt)
     * or null when it doesn't move a band. All prices are €0 today — this is a
     * courtesy notice and a bit of pre-education, never a paywall.
     */
    public static function crossingIfAdding(PDO $db, int $ownerId, int $newPeople = 1): ?array
    {
        if ($newPeople < 1) return null;
        $current = self::seatCount($db, $ownerId);
        $fromIdx = self::tierIndex($current);
        $toIdx   = self::tierIndex($current + $newPeople);
        if ($toIdx <= $fromIdx) return null;

        $from = self::tierForSeats($current);
        $to   = self::tierForSeats($current + $newPeople);
        return [
            'from'          => $from,
            'to'            => $to,
            'seats_now'     => $current,
            'seats_after'   => $current + $newPeople,
            'monthly_cents' => $to['monthly_cents'],
        ];
    }

    /** The tier a given headcount falls into. */
    public static function tierForSeats(int $seats): array
    {
        foreach (self::TIERS as [$key, $label, $min, $max, $mCents, $yCents]) {
            if ($seats >= $min && ($max === null || $seats <= $max)) {
                return [
                    'key' => $key, 'label' => $label,
                    'min' => $min, 'max' => $max,
                    'monthly_cents' => $mCents, 'yearly_cents' => $yCents,
                    'is_paid' => $mCents > 0,
                ];
            }
        }
        // Above every ceiling → the top band.
        [$key, $label, $min, $max, $mCents, $yCents] = self::TIERS[count(self::TIERS) - 1];
        return ['key'=>$key,'label'=>$label,'min'=>$min,'max'=>$max,
                'monthly_cents'=>$mCents,'yearly_cents'=>$yCents,'is_paid'=>$mCents>0];
    }

    /** Does this account carry the Founding Creator promise? */
    public static function isFounder(PDO $db, int $userId): bool
    {
        try {
            $st = $db->prepare("SELECT founding_creator_at FROM users WHERE id = ?");
            $st->execute([$userId]);
            return (bool)$st->fetchColumn();
        } catch (\Throwable $e) { return false; }
    }

    /** Human heads-up for a tier crossing. Free-now framing, never a paywall. */
    public static function crossingMessage(array $c, bool $isFounder, string $subject = 'this person'): string
    {
        $price = self::money((int)$c['monthly_cents']);
        $msg = "Adding $subject makes it {$c['seats_after']} people — that moves you "
             . "to the {$c['to']['label']} plan (normally {$price}/mo). It's free right now";
        $msg .= $isFounder
              ? ", and it stays free for you as a Founding Creator. Add them?"
              : ". Add them?";
        return $msg;
    }

    /** Whole months between a datetime and now (never negative). */
    private static function monthsSince(?string $dt): int
    {
        if (!$dt) return 0;
        try {
            $then = new DateTime($dt);
            $now  = new DateTime('now');
            if ($then >= $now) return 0;
            $d = $then->diff($now);
            return $d->y * 12 + $d->m;
        } catch (\Throwable $e) { return 0; }
    }

    /**
     * Full picture for one account: seats, tier, would-be price, founder
     * status, and the cumulative gift ("saved since you joined").
     */
    public static function resolveForUser(PDO $db, int $userId): array
    {
        $seats = self::seatCount($db, $userId);
        $tier  = self::tierForSeats($seats);

        $foundingAt = null;
        try {
            $st = $db->prepare("SELECT founding_creator_at FROM users WHERE id = ?");
            $st->execute([$userId]);
            $foundingAt = $st->fetchColumn() ?: null;
        } catch (\Throwable $e) { /* column not migrated yet */ }

        // Cumulative value handed over: months on the product × the current
        // would-be monthly price. An approximation (their team may have grown
        // recently), but it's a warm number, not an invoice — directional is fine.
        $months     = self::monthsSince($foundingAt);
        $savedCents = $months * $tier['monthly_cents'];

        return [
            'seats'         => $seats,
            'tier'          => $tier,
            'is_founder'    => $foundingAt !== null,
            'founding_at'   => $foundingAt,
            'months'        => $months,
            'monthly_cents' => $tier['monthly_cents'],
            'yearly_cents'  => $tier['yearly_cents'],
            'saved_cents'   => $savedCents,
            // Everything is free right now, whatever the tier says.
            'charged_cents' => 0,
            'beta_free'     => true,
        ];
    }

    /**
     * Site-admin view: what the whole base would bill today.
     * Walks every account that owns a team or a vision (plus solo accounts),
     * so it's O(users) — fine at this size; revisit if the base gets large.
     */
    public static function shadowStats(PDO $db): array
    {
        $rows = [];
        $totalMonthlyCents = 0;
        $dist = [];
        foreach (self::TIERS as [$key, $label]) $dist[$key] = 0;

        try {
            $users = $db->query("SELECT id, name, email, founding_creator_at
                                   FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return ['rows'=>[], 'total_monthly_cents'=>0, 'distribution'=>$dist,
                    'paying'=>0, 'error'=>true];
        }

        $paying = 0;
        foreach ($users as $u) {
            $seats = self::seatCount($db, (int)$u['id']);
            $tier  = self::tierForSeats($seats);
            $dist[$tier['key']] = ($dist[$tier['key']] ?? 0) + 1;
            if ($tier['is_paid']) {
                $totalMonthlyCents += $tier['monthly_cents'];
                $paying++;
            }
            $rows[] = [
                'id'    => (int)$u['id'],
                'name'  => $u['name'] ?: '(no name)',
                'email' => $u['email'] ?: '',
                'seats' => $seats,
                'tier'  => $tier,
                'is_founder' => !empty($u['founding_creator_at']),
                // A free team one person away from a paid band — a future customer.
                'near_boundary' => (!$tier['is_paid'] && $tier['max'] !== null
                                    && $seats === $tier['max']),
            ];
        }

        // Biggest would-be bills first.
        usort($rows, fn($a, $b) =>
            $b['tier']['monthly_cents'] <=> $a['tier']['monthly_cents']
            ?: $b['seats'] <=> $a['seats']);

        return [
            'rows'                => $rows,
            'total_monthly_cents' => $totalMonthlyCents,
            'total_yearly_cents'  => $totalMonthlyCents * 12,
            'distribution'        => $dist,
            'paying'              => $paying,
            'accounts'            => count($users),
        ];
    }
}
