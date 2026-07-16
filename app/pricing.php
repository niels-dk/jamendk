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
        ['studio',     'Studio',      5,  8,    1500,  15000],
        ['production', 'Production',  9,  20,   3900,  39000],
        ['network',    'Network',     21, null, 7900,  79000],
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
