<?php
/**
 * Link controller — the public side of tracked links: GET /l/{token}
 *
 * Records the click, remembers the link for the rest of the visit, then
 * redirects to wherever the link points. Deliberately forgiving: an unknown,
 * expired or switched-off token still lands the visitor on the site rather
 * than showing them a 404 — a marketing link that's been retired should never
 * turn a real person away.
 */
require_once __DIR__ . '/../app/links.php';

class link_controller
{
    /** GET /l/{token} */
    public static function go(string $token): void
    {
        global $db;

        $link = LinkTokens::resolve($db, $token);
        if (!$link) {
            // Unknown/retired link → home, no error page.
            redirect('/');
        }

        // Attribute this visit (and the rest of the session) to the link.
        LinkTokens::attribute((int)$link['id']);

        // Analytics::record() skips this request (it's a redirect, not a page
        // view), so log the click here — the landing page it forwards to will
        // be recorded normally and will carry the same attribution.
        self::logClick($db, (int)$link['id']);

        redirect(LinkTokens::safeTarget($link['target'] ?? '/'));
    }

    /** One row in analytics_visits marking the click itself. */
    private static function logClick(PDO $db, int $linkId): void
    {
        try {
            $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
            if ($ua === '' || preg_match('~bot|crawl|spider|preview|curl|wget|headless~i', $ua)) return;
            if (function_exists('is_admin') && is_admin()) return;

            $st = $db->prepare("SELECT v FROM analytics_meta WHERE k='salt' LIMIT 1");
            $st->execute();
            $salt = (string)$st->fetchColumn();
            if ($salt === '') return;

            $day  = date('Y-m-d');
            $hash = hash('sha256', $salt . '|' . $day . '|'
                                 . ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . $ua);

            $refHost = null;
            $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
            if ($ref !== '') {
                $h = parse_url($ref, PHP_URL_HOST);
                if ($h && stripos($h, (string)($_SERVER['HTTP_HOST'] ?? '')) === false) {
                    $refHost = mb_substr(preg_replace('~^www\.~i', '', $h), 0, 190);
                }
            }

            $device = preg_match('~iPad|Tablet~i', $ua) ? 'tablet'
                    : (preg_match('~Mobi|Android|iPhone|iPod~i', $ua) ? 'phone' : 'desktop');

            global $currentUserId;
            $db->prepare("INSERT INTO analytics_visits
                          (day, path, ref_host, visitor_hash, is_auth, device, link_token_id)
                          VALUES (?,?,?,?,?,?,?)")
               ->execute([$day, '/l/*', $refHost, $hash,
                          !empty($currentUserId) ? 1 : 0, $device, $linkId]);
        } catch (\Throwable $e) {
            // Never let click logging break the redirect.
        }
    }
}
