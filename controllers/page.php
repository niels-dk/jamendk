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

    /** GET /help */
    public static function help(): void
    {
        self::render('page_help', 'Help',
            'How DreamBoard works: catch a Dream, grow it into a Vision, publish '
          . 'a Trip page you can use offline in the field.');
    }

    /** GET /contact */
    public static function contact(): void
    {
        self::render('page_contact', 'Contact',
            'Get in touch with DreamBoard — email or Instagram.');
    }

    /** GET /terms */
    public static function terms(): void
    {
        self::render('page_terms', 'Terms of use',
            'The terms you agree to when using DreamBoard.');
    }

    /** GET /privacy */
    public static function privacy(): void
    {
        self::render('page_privacy', 'Privacy policy',
            'What data DreamBoard stores, why, and how to get it deleted.');
    }
}
