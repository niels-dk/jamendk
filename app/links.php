<?php
/**
 * LinkTokens — tracked marketing links at /l/{token}.
 *
 * One short string in the URL; every dimension (from / campaign / product /
 * feature / source / partner) lives in the database. That means a link already
 * printed on a flyer or pasted into a bio can be re-pointed or re-labelled
 * later, and new dimensions can be added without reissuing anything.
 *
 * Attribution runs in three stages:
 *   1. click     — /l/{token} records the hit and remembers the link for the visit
 *   2. journey   — every later page view in that visit carries link_token_id
 *   3. signup    — if they create an account, the link is stamped on the user
 * Stage 3 is the one that turns "clicks" into "did this effort actually work".
 */
class LinkTokens
{
    /** Session key holding the link this visit is attributed to. */
    public const SESSION_KEY = 'attr_link_token_id';

    /** The dimensions, in display order: column => [label, placeholder]. */
    public const DIMENSIONS = [
        'from_place'  => ['From',       'instagram-bio, flyer-cphdox, newsletter-footer'],
        'campaign_id' => ['Campaign',   'launch-2026, hilux-series'],
        'product'     => ['Product',    'merely-a-dream'],
        'feature'     => ['Feature',    'shot-list, trip-page, offline'],
        'source'      => ['Source',     'social, email, web, app, print, qr, person'],
        'partner_id'  => ['Partner',    'who sent them'],
    ];

    /** Turn a label into a clean, readable token: "Insta bio July" → insta-bio-july */
    public static function slugify(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('~[^a-z0-9]+~', '-', $s);
        $s = trim((string)$s, '-');
        return $s !== '' ? substr($s, 0, 48) : bin2hex(random_bytes(3));
    }

    /** Look up a usable link. Returns null when unknown, inactive or expired. */
    public static function resolve(PDO $db, string $token): ?array
    {
        try {
            $st = $db->prepare("SELECT * FROM link_tokens
                                 WHERE token = ? AND active = 1
                                   AND (expires_at IS NULL OR expires_at > NOW())
                                 LIMIT 1");
            $st->execute([$token]);
            return $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) { return null; }
    }

    /** The link this visit is attributed to, if any. */
    public static function currentId(): ?int
    {
        return !empty($_SESSION[self::SESSION_KEY]) ? (int)$_SESSION[self::SESSION_KEY] : null;
    }

    /** Remember the link for the rest of this visit. */
    public static function attribute(int $linkId): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        $_SESSION[self::SESSION_KEY] = $linkId;
    }

    /** Only ever redirect somewhere on this site — never an open redirect. */
    public static function safeTarget(?string $target): string
    {
        $t = trim((string)$target);
        if ($t === '' || $t[0] !== '/') return '/';
        if (isset($t[1]) && $t[1] === '/') return '/';   // protocol-relative
        return $t;
    }

    /* ─────────────────────────── Admin CRUD ─────────────────────────── */

    public static function all(PDO $db): array
    {
        try {
            return $db->query("SELECT * FROM link_tokens ORDER BY active DESC, id DESC")
                      ->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) { return []; }
    }

    /** Create a link. Returns [ok, message]. Token collisions get a suffix. */
    public static function create(PDO $db, array $in): array
    {
        $label = trim((string)($in['label'] ?? ''));
        if ($label === '') return [false, 'Give the link a name so you recognise it later.'];

        $token = self::slugify((string)($in['token'] ?? '') ?: $label);
        // Reserved: don't let a link shadow the admin area or the manager itself.
        if (in_array($token, ['admin', 'new', 'edit', 'delete'], true)) $token .= '-link';

        try {
            for ($i = 0; $i < 5; $i++) {
                $try = $i === 0 ? $token : $token . '-' . bin2hex(random_bytes(2));
                $ex  = $db->prepare("SELECT 1 FROM link_tokens WHERE token = ? LIMIT 1");
                $ex->execute([$try]);
                if (!$ex->fetchColumn()) { $token = $try; break; }
                if ($i === 4) return [false, 'Could not find a free token — try another name.'];
            }

            $cols = ['token', 'label', 'target', 'notes'];
            $vals = [$token, mb_substr($label, 0, 120),
                     self::safeTarget($in['target'] ?? '/'),
                     mb_substr(trim((string)($in['notes'] ?? '')), 0, 255) ?: null];
            foreach (array_keys(self::DIMENSIONS) as $d) {
                $cols[] = $d;
                $v = trim((string)($in[$d] ?? ''));
                $vals[] = $v === '' ? null : mb_substr($v, 0, 80);
            }
            $ph = implode(',', array_fill(0, count($cols), '?'));
            $db->prepare("INSERT INTO link_tokens (" . implode(',', $cols) . ") VALUES ($ph)")
               ->execute($vals);
            return [true, $token];
        } catch (\Throwable $e) {
            return [false, 'Run the link-tokens migration first.'];
        }
    }

    public static function setActive(PDO $db, int $id, bool $on): void
    {
        try {
            $db->prepare("UPDATE link_tokens SET active = ? WHERE id = ?")
               ->execute([$on ? 1 : 0, $id]);
        } catch (\Throwable $e) { /* ignore */ }
    }

    public static function delete(PDO $db, int $id): void
    {
        try {
            // Keep the history: null out references rather than orphaning rows.
            $db->prepare("UPDATE analytics_visits SET link_token_id = NULL WHERE link_token_id = ?")->execute([$id]);
            $db->prepare("UPDATE users SET signup_link_token_id = NULL WHERE signup_link_token_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM link_tokens WHERE id = ?")->execute([$id]);
        } catch (\Throwable $e) { /* ignore */ }
    }

    /* ─────────────────────────── Reporting ─────────────────────────── */

    /** Per-link performance: clicks, people, pages seen, and signups produced. */
    public static function stats(PDO $db, int $days = 30): array
    {
        $days = max(1, min(3650, $days));
        try {
            $sql = "
              SELECT lt.*,
                     COALESCE(v.views, 0)     AS views,
                     COALESCE(v.visitors, 0)  AS visitors,
                     COALESCE(s.signups, 0)   AS signups
                FROM link_tokens lt
                LEFT JOIN (
                     SELECT link_token_id, COUNT(*) views,
                            COUNT(DISTINCT visitor_hash) visitors
                       FROM analytics_visits
                      WHERE link_token_id IS NOT NULL
                        AND day >= (CURDATE() - INTERVAL $days DAY)
                      GROUP BY link_token_id
                ) v ON v.link_token_id = lt.id
                LEFT JOIN (
                     SELECT signup_link_token_id, COUNT(*) signups
                       FROM users
                      WHERE signup_link_token_id IS NOT NULL
                        AND created_at >= (CURDATE() - INTERVAL $days DAY)
                      GROUP BY signup_link_token_id
                ) s ON s.signup_link_token_id = lt.id
               ORDER BY visitors DESC, lt.id DESC";
            return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) { return []; }
    }

    /** Roll-up by any single dimension — "which sources work", "which partners". */
    public static function byDimension(PDO $db, string $dim, int $days = 30): array
    {
        if (!array_key_exists($dim, self::DIMENSIONS)) return [];
        $days = max(1, min(3650, $days));
        try {
            $sql = "
              SELECT lt.`$dim` AS k,
                     COUNT(DISTINCT av.visitor_hash) visitors,
                     COUNT(av.id) views,
                     (SELECT COUNT(*) FROM users u
                        JOIN link_tokens lt2 ON lt2.id = u.signup_link_token_id
                       WHERE lt2.`$dim` = lt.`$dim`
                         AND u.created_at >= (CURDATE() - INTERVAL $days DAY)) signups
                FROM link_tokens lt
                LEFT JOIN analytics_visits av
                       ON av.link_token_id = lt.id
                      AND av.day >= (CURDATE() - INTERVAL $days DAY)
               WHERE lt.`$dim` IS NOT NULL AND lt.`$dim` <> ''
               GROUP BY lt.`$dim`
               ORDER BY visitors DESC
               LIMIT 20";
            return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) { return []; }
    }
}
