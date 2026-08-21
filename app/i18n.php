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
     * 'flag' is decorative — a language isn't a country, but a flag is what
     * people scan for.
     */
    public const LANGUAGES = [
        'en' => ['label' => 'English',   'native' => 'English',    'flag' => '🇬🇧'],
        'da' => ['label' => 'Danish',    'native' => 'Dansk',      'flag' => '🇩🇰'],
        // Ready to switch on once translated and reviewed:
        // 'pt-BR' => ['label' => 'Portuguese', 'native' => 'Português (Brasil)', 'flag' => '🇧🇷'],
        // 'es'    => ['label' => 'Spanish',    'native' => 'Español',            'flag' => '🇪🇸'],
    ];

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

/** Escaped shorthand — the common case in HTML. */
function te(string $key, array $vars = []): string
{
    return htmlspecialchars(I18n::t($key, $vars), ENT_QUOTES, 'UTF-8');
}
