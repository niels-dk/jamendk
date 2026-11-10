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
 *   BEFORE SIGN-UP  (analytics_events) — "where do the ones who leave, leave?"
 *            A handful of counted moments on the public pages, sent by
 *            public/js/track.js or written by the sign-up handler. Same
 *            one-day fingerprint, same promises. It exists because most page
 *            views are scanners: a row here means a real browser ran the
 *            page's script.
 *
 * Never throws: analytics must not be able to take a page down.
 */
class Analytics
{
    /**
     * Paths that are never page views (assets, APIs, machine endpoints).
     * '/l/' is excluded because link_controller logs that click itself — it
     * has to, since attribution is only known after the token is resolved.
     * Without this the redirect would be counted twice.
     */
    private const SKIP_PREFIXES = ['/api/', '/public/', '/storage/', '/admin/', '/l/'];

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
    /**
     * Queue a page view, to be written only if the request turns out to have
     * resolved to a real page.
     *
     * record() used to run before routing, which meant a scanner walking
     * /wp-login.php, /.env and /config.yaml was counted exactly like a reader.
     * Deferring to shutdown lets us look at the status code that was actually
     * produced. That covers more than a route-table lookup would: a controller
     * answering 404 itself — an expired share token, a missing board — is not
     * a pageview either.
     *
     * Shutdown functions run after exit(), so redirect() is unaffected and 3xx
     * still counts.
     */
    public static function recordWhenResolved(string $path): void
    {
        register_shutdown_function(static function () use ($path) {
            $code = http_response_code();
            if ($code === false) $code = 200;          // CLI or never set
            if ($code >= 400) return;                  // 404, 403, 500 — not a page
            self::record($path);
        });
    }

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

            // Carry tracked-link attribution across the whole visit, so a
            // campaign gets credit for the journey, not just the landing hit.
            $linkId = null;
            if (class_exists('LinkTokens')) $linkId = LinkTokens::currentId();

            $st = $db->prepare(
                "INSERT INTO analytics_visits
                 (day, path, ref_host, utm_source, utm_medium, utm_campaign,
                  visitor_hash, is_auth, device, link_token_id)
                 VALUES (?,?,?,?,?,?,?,?,?,?)"
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
                $linkId,
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

    /* ───────────────────── Before sign-up: recording ───────────────────── */

    /** The only event names that are ever stored. */
    public const EVENTS = [
        'human',          // a real browser ran the page (first interaction or 2.5 s)
        'scroll',         // front page: detail fold | half | end
        'cta',            // a sign-up button: detail header | hero | bottom | footer | other
        'demo_trip',      // the example Trip link
        'form_start',     // first keystroke in the sign-up form
        'form_submit',    // the sign-up form was sent
        'signup_ok',      // server: account created
        'signup_fail',    // server: refused; detail is the reason code
    ];

    /** How many rows one visitor can write in a day. The funnel needs a dozen. */
    private const EVENTS_PER_VISITOR = 60;

    /**
     * Record one moment. Called by POST /api/a (from track.js) and by the
     * sign-up handler. Like record(): never throws, and does nothing if the
     * migration has not been run.
     */
    public static function event(string $name, ?string $detail = null, ?string $path = null): void
    {
        global $db;
        if (!isset($db) || !($db instanceof PDO)) return;

        try {
            if (!in_array($name, self::EVENTS, true)) return;

            $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
            if (self::isBot($ua)) return;
            if (function_exists('is_admin') && is_admin()) return;

            $salt = self::salt($db);
            if ($salt === '') return;

            // A short code, never free text: nothing a person typed can land here.
            $detail = $detail === null ? '' : substr(preg_replace('~[^a-z0-9_]~', '', strtolower($detail)), 0, 40);
            $detail = $detail === '' ? null : $detail;

            // The path only — no query string — and collapsed the same way
            // page views are, so tokens never reach this table either.
            $path = (string)(parse_url((string)($path ?? ($_SERVER['REQUEST_URI'] ?? '/')), PHP_URL_PATH) ?: '/');
            if ($path === '' || $path[0] !== '/') $path = '/';
            $path = mb_substr(self::normalisePath($path), 0, 190);

            $ip   = (string)($_SERVER['REMOTE_ADDR'] ?? '');
            $day  = date('Y-m-d');
            $hash = hash('sha256', $salt . '|' . $day . '|' . $ip . '|' . $ua);

            // Bound what one visitor (or one script pretending to be one) can write.
            $n = $db->prepare("SELECT COUNT(*) FROM analytics_events WHERE day = ? AND visitor_hash = ?");
            $n->execute([$day, $hash]);
            if ((int)$n->fetchColumn() >= self::EVENTS_PER_VISITOR) return;

            // The funnel counts visitors, not repetitions: one row per moment,
            // page and visitor per day. A refused sign-up is the exception —
            // every attempt is worth knowing about.
            if ($name !== 'signup_fail') {
                $dup = $db->prepare(
                    "SELECT 1 FROM analytics_events
                      WHERE day = ? AND visitor_hash = ? AND name = ? AND path = ?
                        AND COALESCE(detail, '') = ? LIMIT 1");
                $dup->execute([$day, $hash, $name, $path, (string)$detail]);
                if ($dup->fetchColumn()) return;
            }

            $db->prepare(
                "INSERT INTO analytics_events (day, visitor_hash, name, detail, path, device)
                 VALUES (?,?,?,?,?,?)"
            )->execute([$day, $hash, $name, $detail, $path, self::device($ua)]);

            if (random_int(1, 400) === 1) {
                $db->exec("DELETE FROM analytics_events
                            WHERE day < (CURDATE() - INTERVAL 400 DAY)");
            }
        } catch (\Throwable $e) {
            // Table not created yet, or anything else: counting never breaks a page.
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
                "SELECT ref_host, COUNT(DISTINCT visitor_hash) visitors, COUNT(*) views
                   FROM analytics_visits WHERE day >= $since AND ref_host IS NOT NULL
                  GROUP BY ref_host ORDER BY visitors DESC LIMIT 15"),
            // Which page each referrer's traffic actually landed on. The path
            // is already on the same row as the referrer, so this costs one
            // extra GROUP BY and no new collection — worth having because the
            // referrer alone cannot tell you what someone searched for
            // (Google strips the query) but CAN tell you what they were after.
            'ref_pages' => self::rows($db,
                "SELECT ref_host, path,
                        COUNT(DISTINCT visitor_hash) visitors, COUNT(*) views
                   FROM analytics_visits
                  WHERE day >= $since AND ref_host IS NOT NULL AND ref_host <> ''
                  GROUP BY ref_host, path
                  ORDER BY visitors DESC, views DESC LIMIT 120"),
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

    /* ───────────────────── Reading: before sign-up ────────────────────── */

    /**
     * Where the visitors who do not sign up drop off.
     *
     * Every number is "distinct one-day fingerprints": the hash already
     * contains the date, so a visitor is a person on a day. Someone who reads
     * the page today and signs up next week is two visitors — that is the
     * price of not being able to follow anyone, and it is stated on the page.
     */
    public static function acquisition(PDO $db, int $days = 30): array
    {
        $days  = max(1, min(365, $days));
        $since = "(CURDATE() - INTERVAL $days DAY)";

        // -1 when the table does not exist (one() returns the default on error).
        $total = (int)self::one($db, "SELECT COUNT(*) FROM analytics_events", [], -1);
        if ($total < 0) return ['has_table' => false];

        $who = fn(string $where, array $args = []) => (int)self::one($db,
            "SELECT COUNT(DISTINCT visitor_hash) FROM analytics_events WHERE day >= $since AND $where", $args);

        $steps = [
            'landed'   => $who("name = 'human' AND path = '/'"),
            'scrolled' => $who("name = 'scroll' AND detail = 'fold'"),
            'clicked'  => $who("name = 'cta'"),
            'form'     => $who("name = 'human' AND path = '/register'"),
            'typing'   => $who("name = 'form_start'"),
            'sent'     => $who("name = 'form_submit'"),
            'signed'   => $who("name = 'signup_ok'"),
        ];

        return [
            'has_table' => true,
            'has_data'  => $total > 0,
            'since'     => (string)self::one($db, "SELECT MIN(day) FROM analytics_events", [], ''),
            'steps'     => $steps,
            // Not tied to a fingerprint (it happens later, from an email), so a plain count.
            'confirmed' => (int)self::one($db,
                "SELECT COUNT(*) FROM users WHERE created_at >= $since AND email_verified_at IS NOT NULL"),
            'scroll' => [
                'fold' => $steps['scrolled'],
                'half' => $who("name = 'scroll' AND detail = 'half'"),
                'end'  => $who("name = 'scroll' AND detail = 'end'"),
            ],
            'cta' => self::rows($db,
                "SELECT COALESCE(detail, 'other') place, COUNT(DISTINCT visitor_hash) n
                   FROM analytics_events WHERE day >= $since AND name = 'cta'
                  GROUP BY place ORDER BY n DESC"),
            'demo_trip' => $who("name = 'demo_trip'"),
            'fails' => self::rows($db,
                "SELECT COALESCE(detail, 'error') reason, COUNT(*) n
                   FROM analytics_events WHERE day >= $since AND name = 'signup_fail'
                  GROUP BY reason ORDER BY n DESC"),
            // The last page seen by a real visitor who was never signed in and
            // did not sign up that day.
            'left' => self::rows($db,
                "SELECT v.path, COUNT(*) n
                   FROM analytics_visits v
                   JOIN (SELECT visitor_hash, MAX(id) mid
                           FROM analytics_visits
                          WHERE day >= $since
                          GROUP BY visitor_hash
                         HAVING MAX(is_auth) = 0) last ON last.mid = v.id
                  WHERE v.visitor_hash IN (SELECT visitor_hash FROM analytics_events
                                            WHERE day >= $since AND name = 'human')
                    AND v.visitor_hash NOT IN (SELECT visitor_hash FROM analytics_events
                                                WHERE day >= $since AND name = 'signup_ok')
                  GROUP BY v.path ORDER BY n DESC LIMIT 10"),
            // From page views alone, so it reaches back before the events
            // existed — and includes every scanner. Shown as "rough".
            'rough' => [
                'landing'  => (int)self::one($db,
                    "SELECT COUNT(DISTINCT visitor_hash) FROM analytics_visits WHERE day >= $since AND path = '/'"),
                'register' => (int)self::one($db,
                    "SELECT COUNT(DISTINCT visitor_hash) FROM analytics_visits WHERE day >= $since AND path = '/register'"),
                'signups'  => (int)self::one($db,
                    "SELECT COUNT(*) FROM users WHERE created_at >= $since"),
            ],
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
            t('adm.f_dreams')      => "SELECT COUNT(DISTINCT user_id) FROM dream_boards WHERE deleted_at IS NULL",
            t('adm.f_visions')     => "SELECT COUNT(DISTINCT user_id) FROM visions WHERE deleted_at IS NULL",
            t('adm.f_moods')       => "SELECT COUNT(DISTINCT user_id) FROM mood_boards WHERE deleted_at IS NULL",
            t('adm.f_shots')       => "SELECT COUNT(DISTINCT v.user_id) FROM vision_shots s JOIN visions v ON v.id=s.vision_id",
            t('adm.f_itineraries') => "SELECT COUNT(DISTINCT v.user_id) FROM vision_itinerary i JOIN visions v ON v.id=i.vision_id",
            t('adm.f_budgets')     => "SELECT COUNT(DISTINCT v.user_id) FROM vision_budget b JOIN visions v ON v.id=b.vision_id",
            t('adm.f_contacts')    => "SELECT COUNT(DISTINCT v.user_id) FROM vision_contacts c JOIN visions v ON v.id=c.vision_id",
            t('adm.f_documents')   => "SELECT COUNT(DISTINCT v.user_id) FROM vision_documents d JOIN visions v ON v.id=d.vision_id",
            t('adm.f_published')   => "SELECT COUNT(DISTINCT user_id) FROM visions WHERE trip_enabled=1 AND deleted_at IS NULL",
            t('adm.f_shared')      => "SELECT COUNT(DISTINCT v.user_id) FROM vision_roles r JOIN visions v ON v.id=r.vision_id",
            t('adm.f_team')        => "SELECT COUNT(DISTINCT owner_user_id) FROM teams",
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
