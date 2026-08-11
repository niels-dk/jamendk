<?php
/**
 * Analytics — first-party, cookie-free, and entirely self-contained.
 *
 * Two halves, answering two different questions:
 *
 *   TRAFFIC  (analytics_visits) — "is anyone arriving, and from where?"
 *            Recorded per page view. No cookies, no third-party requests, no
 *            IP stored: uniqueness comes from sha256(secret salt + date + ip
 *            + user agent), so it cannot be reversed to a person and cannot
 *            be followed from one day to the next.
 *
 *   PRODUCT  (existing tables) — "is anyone actually USING it?"
 *            Derived live from users/dreams/visions/shots/etc. Nothing to
 *            instrument, and it works retroactively over all history.
 *
 * Never throws: analytics must not be able to take a page down.
 */
class Analytics
{
    /** Paths that are never page views (assets, APIs, machine endpoints). */
    private const SKIP_PREFIXES = ['/api/', '/public/', '/storage/', '/admin/'];

    /** Crawlers, uptime checks, previewers — noise, not visitors. */
    private static function isBot(string $ua): bool
    {
        if ($ua === '') return true;
        return (bool)preg_match(
            '~bot|crawl|spider|slurp|bing|yandex|baidu|duckduck|facebookexternalhit|'
          . 'embedly|quora|pinterest|slackbot|vkshare|whatsapp|telegram|preview|'
          . 'headless|curl|wget|python-requests|monitor|uptime|pingdom|lighthouse~i',
            $ua
        );
    }

    /**
     * Collapse dynamic segments so reports group ("/t/*" beats 400 rows of one
     * view each) — and, more importantly, so secrets never land in this table.
     * A password-reset or verification token stored in analytics would be a
     * genuine security problem; share tokens are capability URLs and don't
     * belong here either.
     */
    private static function normalisePath(string $path): string
    {
        $map = [
            '~^/t/[A-Za-z0-9]+~'                => '/t/*',
            '~^/trips/[A-Za-z0-9]+~'            => '/trips/*',
            '~^/verify/[a-f0-9]+~'              => '/verify/*',
            '~^/reset/[a-f0-9]+~'               => '/reset/*',
            '~^/dreams/[A-Za-z0-9]{6,16}~'      => '/dreams/*',
            '~^/visions/[A-Za-z0-9]{6,16}~'     => '/visions/*',
            '~^/moods/[A-Za-z0-9]{6,16}~'       => '/moods/*',
            '~^/documents/[a-f0-9]{32}~'        => '/documents/*',
        ];
        foreach ($map as $re => $to) {
            if (preg_match($re, $path)) return $to;
        }
        return $path;
    }

    private static function device(string $ua): string
    {
        if (preg_match('~iPad|Tablet~i', $ua))              return 'tablet';
        if (preg_match('~Mobi|Android|iPhone|iPod~i', $ua)) return 'phone';
        return 'desktop';
    }

    /** The rotating salt, cached per request. */
    private static function salt(PDO $db): string
    {
        static $salt = null;
        if ($salt !== null) return $salt;
        try {
            $st = $db->prepare("SELECT v FROM analytics_meta WHERE k='salt' LIMIT 1");
            $st->execute();
            $salt = (string)$st->fetchColumn();
        } catch (\Throwable $e) { $salt = ''; }
        return $salt;
    }

    /**
     * Record one page view. Call once per request, before rendering.
     * Silently does nothing if the migration hasn't run.
     */
    public static function record(string $path): void
    {
        global $db, $currentUserId;
        if (!isset($db) || !($db instanceof PDO)) return;

        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
            foreach (self::SKIP_PREFIXES as $p) {
                if (strncmp($path, $p, strlen($p)) === 0) return;
            }
            // Static files that slipped through (favicon, manifest, sw.js…)
            if (preg_match('~\.(css|js|json|png|jpe?g|gif|svg|webp|ico|map|txt|xml)$~i', $path)) return;

            $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
            if (self::isBot($ua)) return;

            // Don't let the admin's own browsing distort the numbers.
            if (function_exists('is_admin') && is_admin()) return;

            $salt = self::salt($db);
            if ($salt === '') return;   // migration not run yet

            $ip   = (string)($_SERVER['REMOTE_ADDR'] ?? '');
            $day  = date('Y-m-d');
            // Salt + day means the same visitor hashes differently tomorrow:
            // daily uniqueness, zero long-term tracking.
            $hash = hash('sha256', $salt . '|' . $day . '|' . $ip . '|' . $ua);

            // Referrer: host only. We never store the full referring URL —
            // it can contain search terms and other personal detail.
            $refHost = null;
            $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
            if ($ref !== '') {
                $h = parse_url($ref, PHP_URL_HOST);
                $self = $_SERVER['HTTP_HOST'] ?? '';
                if ($h && stripos($h, (string)$self) === false) {
                    $refHost = mb_substr(preg_replace('~^www\.~i', '', $h), 0, 190);
                }
            }

            $clean = fn(?string $v) => ($v === null || $v === '')
                ? null : mb_substr(preg_replace('~[^\w \-\.]~u', '', $v), 0, 80);

            $st = $db->prepare(
                "INSERT INTO analytics_visits
                 (day, path, ref_host, utm_source, utm_medium, utm_campaign,
                  visitor_hash, is_auth, device)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            );
            $st->execute([
                $day,
                mb_substr(self::normalisePath($path === '' ? '/' : $path), 0, 190),
                $refHost,
                $clean($_GET['utm_source']   ?? null),
                $clean($_GET['utm_medium']   ?? null),
                $clean($_GET['utm_campaign'] ?? null),
                $hash,
                !empty($currentUserId) ? 1 : 0,
                self::device($ua),
            ]);

            // Keep the table bounded without needing another cron job.
            if (random_int(1, 400) === 1) {
                $db->exec("DELETE FROM analytics_visits
                            WHERE day < (CURDATE() - INTERVAL 400 DAY)");
            }
        } catch (\Throwable $e) {
            // Analytics failing must never break a page.
        }
    }

    /* ───────────────────────── Reading: traffic ───────────────────────── */

    private static function rows(PDO $db, string $sql, array $args = []): array
    {
        try { $st = $db->prepare($sql); $st->execute($args); return $st->fetchAll(PDO::FETCH_ASSOC) ?: []; }
        catch (\Throwable $e) { return []; }
    }
    private static function one(PDO $db, string $sql, array $args = [], $default = 0)
    {
        try { $st = $db->prepare($sql); $st->execute($args); $v = $st->fetchColumn(); return $v === false ? $default : $v; }
        catch (\Throwable $e) { return $default; }
    }

    public static function traffic(PDO $db, int $days = 30): array
    {
        $days = max(1, min(365, $days));
        $since = "(CURDATE() - INTERVAL $days DAY)";

        return [
            'has_data' => (int)self::one($db, "SELECT COUNT(*) FROM analytics_visits") > 0,
            'views'    => (int)self::one($db, "SELECT COUNT(*) FROM analytics_visits WHERE day >= $since"),
            'visitors' => (int)self::one($db, "SELECT COUNT(DISTINCT visitor_hash) FROM analytics_visits WHERE day >= $since"),
            'today_views'    => (int)self::one($db, "SELECT COUNT(*) FROM analytics_visits WHERE day = CURDATE()"),
            'today_visitors' => (int)self::one($db, "SELECT COUNT(DISTINCT visitor_hash) FROM analytics_visits WHERE day = CURDATE()"),
            'daily' => self::rows($db,
                "SELECT day, COUNT(*) views, COUNT(DISTINCT visitor_hash) visitors
                   FROM analytics_visits WHERE day >= $since GROUP BY day ORDER BY day"),
            'pages' => self::rows($db,
                "SELECT path, COUNT(*) views, COUNT(DISTINCT visitor_hash) visitors
                   FROM analytics_visits WHERE day >= $since
                  GROUP BY path ORDER BY views DESC LIMIT 15"),
            'referrers' => self::rows($db,
                "SELECT ref_host, COUNT(DISTINCT visitor_hash) visitors
                   FROM analytics_visits WHERE day >= $since AND ref_host IS NOT NULL
                  GROUP BY ref_host ORDER BY visitors DESC LIMIT 15"),
            'campaigns' => self::rows($db,
                "SELECT COALESCE(utm_source,'—') src, COALESCE(utm_campaign,'—') camp,
                        COUNT(DISTINCT visitor_hash) visitors
                   FROM analytics_visits
                  WHERE day >= $since AND (utm_source IS NOT NULL OR utm_campaign IS NOT NULL)
                  GROUP BY src, camp ORDER BY visitors DESC LIMIT 15"),
            'devices' => self::rows($db,
                "SELECT device, COUNT(DISTINCT visitor_hash) visitors
                   FROM analytics_visits WHERE day >= $since GROUP BY device ORDER BY visitors DESC"),
        ];
    }

    /* ───────────────────────── Reading: product ───────────────────────── */

    /**
     * The funnel that actually matters: arrived → signed up → confirmed →
     * made something → published a trip. Derived from live tables, so it
     * covers all history rather than starting the day analytics was added.
     */
    public static function product(PDO $db, int $days = 30): array
    {
        $days  = max(1, min(365, $days));
        $since = "(CURDATE() - INTERVAL $days DAY)";

        $users     = (int)self::one($db, "SELECT COUNT(*) FROM users");
        $verified  = (int)self::one($db, "SELECT COUNT(*) FROM users WHERE email_verified_at IS NOT NULL");
        $activated = (int)self::one($db,
            "SELECT COUNT(DISTINCT u.id) FROM users u
              WHERE EXISTS (SELECT 1 FROM dream_boards d WHERE d.user_id=u.id AND d.deleted_at IS NULL)
                 OR EXISTS (SELECT 1 FROM visions v      WHERE v.user_id=u.id AND v.deleted_at IS NULL)
                 OR EXISTS (SELECT 1 FROM mood_boards m  WHERE m.user_id=u.id AND m.deleted_at IS NULL)");
        $published = (int)self::one($db,
            "SELECT COUNT(DISTINCT user_id) FROM visions
              WHERE trip_enabled = 1 AND deleted_at IS NULL");

        // "Feature used by N accounts" — breadth of adoption, not raw volume.
        $featureUsers = [
            'Dreams caught'      => "SELECT COUNT(DISTINCT user_id) FROM dream_boards WHERE deleted_at IS NULL",
            'Visions created'    => "SELECT COUNT(DISTINCT user_id) FROM visions WHERE deleted_at IS NULL",
            'Mood boards'        => "SELECT COUNT(DISTINCT user_id) FROM mood_boards WHERE deleted_at IS NULL",
            'Shot lists'         => "SELECT COUNT(DISTINCT v.user_id) FROM vision_shots s JOIN visions v ON v.id=s.vision_id",
            'Itineraries'        => "SELECT COUNT(DISTINCT v.user_id) FROM vision_itinerary i JOIN visions v ON v.id=i.vision_id",
            'Budgets'            => "SELECT COUNT(DISTINCT v.user_id) FROM vision_budget b JOIN visions v ON v.id=b.vision_id",
            'Contacts'           => "SELECT COUNT(DISTINCT v.user_id) FROM vision_contacts c JOIN visions v ON v.id=c.vision_id",
            'Documents'          => "SELECT COUNT(DISTINCT v.user_id) FROM vision_documents d JOIN visions v ON v.id=d.vision_id",
            'Published a Trip'   => "SELECT COUNT(DISTINCT user_id) FROM visions WHERE trip_enabled=1 AND deleted_at IS NULL",
            'Shared with others' => "SELECT COUNT(DISTINCT v.user_id) FROM vision_roles r JOIN visions v ON v.id=r.vision_id",
            'Built a team'       => "SELECT COUNT(DISTINCT owner_user_id) FROM teams",
        ];
        $features = [];
        foreach ($featureUsers as $label => $sql) {
            $features[$label] = (int)self::one($db, $sql);
        }

        // The payoff moment: shots actually ticked off, i.e. used in the field.
        $shotsPlanned  = (int)self::one($db, "SELECT COUNT(*) FROM vision_shots WHERE status <> 'dropped'");
        $shotsCaptured = (int)self::one($db, "SELECT COUNT(*) FROM vision_shots WHERE status = 'captured'");

        return [
            'users'     => $users,
            'verified'  => $verified,
            'activated' => $activated,
            'published' => $published,
            'active_30' => (int)self::one($db,
                "SELECT COUNT(*) FROM users WHERE last_login_at >= $since"),
            'new_users' => (int)self::one($db,
                "SELECT COUNT(*) FROM users WHERE created_at >= $since"),
            'features'      => $features,
            'shots_planned' => $shotsPlanned,
            'shots_captured'=> $shotsCaptured,
            'daily' => [
                'signups' => self::rows($db,
                    "SELECT DATE(created_at) d, COUNT(*) n FROM users
                      WHERE created_at >= $since GROUP BY d ORDER BY d"),
                'dreams' => self::rows($db,
                    "SELECT DATE(created_at) d, COUNT(*) n FROM dream_boards
                      WHERE created_at >= $since AND deleted_at IS NULL GROUP BY d ORDER BY d"),
                'visions' => self::rows($db,
                    "SELECT DATE(created_at) d, COUNT(*) n FROM visions
                      WHERE created_at >= $since AND deleted_at IS NULL GROUP BY d ORDER BY d"),
            ],
        ];
    }
}
