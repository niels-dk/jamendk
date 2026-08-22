<?php
/**
 * i18n — interface translation.
 *
 * Deliberately tiny: no library, no compilation step, no dependency. Language
 * files are plain PHP arrays in /lang, so adding a language is adding a file.
 *
 * Resolution order for the current request:
 *   1. $_SESSION['lang']   — the picker sets this, so switching is instant
 *   2. users.lang          — persists across devices and drives outbound email
 *   3. DEFAULT_LANG ('en')
 *
 * Anonymous visitors are NOT translated yet — the marketing pages stay English
 * until the translations are checked by a native speaker. Flip ALLOW_ANON to
 * true when that's done.
 *
 * Missing strings fall back to English, and then to the key itself, so a gap
 * shows up as visible text rather than a blank space.
 */
class I18n
{
    public const DEFAULT_LANG = 'en';

    /** Set true to translate for logged-out visitors as well. */
    public const ALLOW_ANON = false;

    /**
     * Languages offered in the picker. Order is the order shown.
     * 'cc' is the flag drawn by flag() — decorative only; a language isn't a
     * country, but a flag is what people scan for.
     */
    public const LANGUAGES = [
        'en' => ['label' => 'English', 'native' => 'English', 'cc' => 'gb'],
        'da' => ['label' => 'Danish',  'native' => 'Dansk',   'cc' => 'dk'],
        // Ready to switch on once translated and reviewed:
        // 'pt-BR' => ['label' => 'Portuguese', 'native' => 'Português (Brasil)', 'cc' => 'br'],
        // 'es'    => ['label' => 'Spanish',    'native' => 'Español',            'cc' => 'es'],
    ];

    /**
     * Inline SVG flag — NOT emoji.
     *
     * Windows ships no glyphs for regional-indicator flag emoji, so 🇩🇰
     * renders as the bare letters "DK" in every browser on Windows. Since
     * that's a large share of any audience, the flags are drawn as SVG:
     * identical everywhere, no font dependency, no external request.
     */
    public static function flag(string $cc, int $size = 20): string
    {
        $id = 'fc' . $cc;   // clip id, unique per country in a page
        $clip = '<defs><clipPath id="' . $id . '"><circle cx="12" cy="12" r="12"/></clipPath></defs>';
        $g = '<g clip-path="url(#' . $id . ')">';

        switch ($cc) {
            case 'dk': // Dannebrog: white cross, offset to the hoist
                $body = '<rect width="24" height="24" fill="#C8102E"/>'
                      . '<rect y="10" width="24" height="4" fill="#fff"/>'
                      . '<rect x="7" width="4" height="24" fill="#fff"/>';
                break;
            case 'gb': // Union Jack, simplified
                $body = '<rect width="24" height="24" fill="#012169"/>'
                      . '<path d="M0 0 24 24M24 0 0 24" stroke="#fff" stroke-width="5"/>'
                      . '<path d="M0 0 24 24M24 0 0 24" stroke="#C8102E" stroke-width="2"/>'
                      . '<path d="M12 0v24M0 12h24" stroke="#fff" stroke-width="8"/>'
                      . '<path d="M12 0v24M0 12h24" stroke="#C8102E" stroke-width="4"/>';
                break;
            case 'br': // Brazil: green field, yellow lozenge, blue globe
                $body = '<rect width="24" height="24" fill="#009C3B"/>'
                      . '<path d="M12 3 22 12 12 21 2 12Z" fill="#FEDF00"/>'
                      . '<circle cx="12" cy="12" r="4.2" fill="#002776"/>'
                      . '<path d="M8 11.2q4 -1.4 8 .6" stroke="#fff" stroke-width="1.1" fill="none"/>';
                break;
            case 'es': // Spain: red-yellow-red bands
                $body = '<rect width="24" height="24" fill="#AA151B"/>'
                      . '<rect y="6" width="24" height="12" fill="#F1BF00"/>';
                break;
            default:
                $body = '<rect width="24" height="24" fill="#2b3346"/>';
        }

        return '<svg class="lang-flag" width="' . $size . '" height="' . $size . '"'
             . ' viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
             . $clip . $g . $body . '</g>'
             . '<circle cx="12" cy="12" r="11.5" fill="none" stroke="rgba(255,255,255,.25)"/>'
             . '</svg>';
    }

    private static ?string $lang = null;
    private static array $strings = [];
    private static array $fallback = [];

    /** Is this a language we actually ship? */
    public static function isSupported(?string $code): bool
    {
        return $code !== null && array_key_exists($code, self::LANGUAGES);
    }

    /** The language for THIS request. */
    public static function lang(): string
    {
        if (self::$lang !== null) return self::$lang;

        $chosen = self::DEFAULT_LANG;

        if (self::ALLOW_ANON || (function_exists('is_logged_in') && is_logged_in())) {
            // Session first — the picker writes here so the change is immediate.
            if (!empty($_SESSION['lang']) && self::isSupported($_SESSION['lang'])) {
                $chosen = $_SESSION['lang'];
            } else {
                global $currentUser;
                $fromUser = $currentUser['lang'] ?? null;
                if (self::isSupported($fromUser)) $chosen = $fromUser;
            }
        }

        return self::$lang = $chosen;
    }

    /** Load a language file (returns [] when the file doesn't exist). */
    private static function load(string $code): array
    {
        $file = __DIR__ . '/../lang/' . preg_replace('~[^a-zA-Z-]~', '', $code) . '.php';
        if (!is_file($file)) return [];
        $data = include $file;
        return is_array($data) ? $data : [];
    }

    /** Force a language for the rest of this request (used when sending mail). */
    public static function use(string $code): void
    {
        if (!self::isSupported($code)) $code = self::DEFAULT_LANG;
        self::$lang = $code;
        self::$strings = self::load($code);
    }

    /**
     * Translate. Placeholders are :name style:
     *     t('greeting', ['name' => 'Niels'])   // "Hej Niels"
     */
    public static function t(string $key, array $vars = []): string
    {
        if (!self::$strings) self::$strings = self::load(self::lang());
        if (!self::$fallback && self::lang() !== self::DEFAULT_LANG) {
            self::$fallback = self::load(self::DEFAULT_LANG);
        }

        $s = self::$strings[$key]
             ?? self::$fallback[$key]
             ?? $key;   // visible gap beats a blank space

        foreach ($vars as $k => $v) {
            $s = str_replace(':' . $k, (string)$v, $s);
        }
        return $s;
    }

    /** Persist a choice for the signed-in user (and this session). */
    public static function setForCurrentUser(string $code): bool
    {
        if (!self::isSupported($code)) return false;
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        $_SESSION['lang'] = $code;
        self::$lang = null;          // recompute on next use
        self::$strings = [];

        global $db, $currentUserId, $currentUser;
        if (!empty($currentUserId)) {
            try {
                $db->prepare('UPDATE users SET lang = ? WHERE id = ?')
                   ->execute([$code, (int)$currentUserId]);
                if (is_array($currentUser)) {
                    $currentUser['lang'] = $code;
                    $_SESSION['user']['lang'] = $code;
                }
            } catch (\Throwable $e) { /* column not migrated — session still works */ }
        }
        return true;
    }

    /** A recipient's language, for outbound email. Falls back to the default. */
    public static function forUser(PDO $db, int $userId): string
    {
        try {
            $st = $db->prepare('SELECT lang FROM users WHERE id = ? LIMIT 1');
            $st->execute([$userId]);
            $l = $st->fetchColumn();
            return self::isSupported($l ?: null) ? $l : self::DEFAULT_LANG;
        } catch (\Throwable $e) {
            return self::DEFAULT_LANG;
        }
    }

    /** Same, by email address — signup/reset only know the address. */
    public static function forEmail(PDO $db, string $email): string
    {
        try {
            $st = $db->prepare('SELECT lang FROM users WHERE email = ? LIMIT 1');
            $st->execute([$email]);
            $l = $st->fetchColumn();
            return self::isSupported($l ?: null) ? $l : self::DEFAULT_LANG;
        } catch (\Throwable $e) {
            return self::DEFAULT_LANG;
        }
    }
}

/**
 * Shorthand used throughout the views.
 *
 * NOTE: t() and te() are now RESERVED global names. Views that need a local
 * HTML-escape helper must use the *_e convention the rest of the codebase
 * follows (au_e, p_e, dash_e, …) — dashboard_overview.php previously declared
 * its own t() escaper and that collision took the dashboard down with a
 * "Cannot redeclare t()" fatal.
 */
function t(string $key, array $vars = []): string
{
    return I18n::t($key, $vars);
}

/**
 * Localised medium date, e.g. "May 28, 2026" / "28. maj 2026".
 *
 * date() only ever speaks English, so the month name comes from the language
 * file and the field order from 'date.medium'. Deliberately not IntlDateFormatter:
 * the intl extension is not guaranteed on shared hosting, and a missing
 * extension would be a fatal on every page that shows a date.
 *
 * @param int|string|null $when  timestamp, or anything strtotime() accepts
 */
function fmt_date($when): string
{
    if ($when === null || $when === '') return '';
    $ts = is_int($when) ? $when : strtotime((string)$when);
    if (!$ts) return '';
    return I18n::t('date.medium', [
        'd' => (int)date('j', $ts),
        'm' => I18n::t('month.' . strtolower(date('M', $ts))),
        'y' => date('Y', $ts),
    ]);
}

/** Escaped shorthand — the common case in HTML. */
function te(string $key, array $vars = []): string
{
    return htmlspecialchars(I18n::t($key, $vars), ENT_QUOTES, 'UTF-8');
}
