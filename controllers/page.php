<?php
/**
 * Static information pages — help, contact, terms, privacy.
 * Public: a stranger must be able to read the terms before signing up.
 */
class page_controller
{
    private static function render(string $view, string $title, ?string $desc = null): void
    {
        // head.php reads $title for the tab and the OG tags; $desc feeds the
        // meta description so each page previews as itself when shared.
        $pageTitle       = $title;
        $metaDescription = $desc;
        $noSidebar = true;
        ob_start();
        include __DIR__ . '/../views/' . $view . '.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * POST /api/a — one counted moment from public/js/track.js.
     * Always answers 204 with no body, whatever happened: the page that sent
     * it is not waiting, and a caller learns nothing by probing it.
     */
    public static function track(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            && !(function_exists('is_logged_in') && is_logged_in())) {
            // Only the moments a browser can report. signup_ok / signup_fail
            // are written by the sign-up handler and cannot be sent from here.
            // The detail is one of a fixed set per moment; anything else is
            // dropped rather than stored, so the table only ever holds codes
            // this site defined.
            $allowed = [
                'human'       => [''],
                'scroll'      => ['fold', 'half', 'end'],
                'cta'         => ['header', 'hero', 'bottom', 'footer', 'other'],
                'demo_trip'   => [''],
                'form_start'  => [''],
                'form_submit' => [''],
            ];
            $name   = (string)($_POST['n'] ?? '');
            $detail = (string)($_POST['d'] ?? '');
            if (isset($allowed[$name])) {
                if (!in_array($detail, $allowed[$name], true)) {
                    $detail = $name === 'cta' ? 'other' : null;
                }
                if ($detail !== null) {
                    Analytics::event($name, $detail, (string)($_POST['p'] ?? '/'));
                }
            }
        }
        http_response_code(204);
    }

    /** GET /pricing — public. Shows real tiers, all free during beta. */
    public static function pricing(): void
    {
        require_once __DIR__ . '/../app/pricing.php';
        $tiers = Pricing::TIERS;
        self::render('page_pricing', t('nav.pricing'),
            'Merely a Dream is free while we\'re just getting started. One creator, '
          . 'free forever. You only pay when a team works with you — never for features.');
    }

    /** GET /help */
    public static function help(): void
    {
        self::render('page_help', t('footer.help'),
            'How Merely a Dream works: catch a Dream, grow it into a Vision, publish '
          . 'a Trip page you can use offline in the field.');
    }

    /** GET /contact */
    public static function contact(): void
    {
        self::render('page_contact', t('footer.contact'),
            'Get in touch with Merely a Dream — email or Instagram.');
    }

    /** GET /terms */
    public static function terms(): void
    {
        self::render('page_terms', t('footer.terms'),
            'The terms you agree to when using Merely a Dream.');
    }

    /** GET /privacy */
    public static function privacy(): void
    {
        self::render('page_privacy', t('footer.privacy'),
            'What data Merely a Dream stores, why, and how to get it deleted.');
    }
}
