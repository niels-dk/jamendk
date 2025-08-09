<?php
/**
 * Tiny router that maps path‐regex => [controller, method]
 * ─────────────────────────────────────────────────────────
 *  • Add new routes by appending regex patterns to $routes.
 *  • Regex delimiters are added automatically by preg_match in the loop.
 */

function route(string $uri): void
{
    $routes = [
    ''                => ['home',  'index'],
    '/'               => ['home',  'index'],
    '/index\.php'     => ['home',  'index'],

    '/login'          => ['user',  'login'],
    '/register'       => ['user',  'register'],
    '/logout'         => ['user',  'logout'],
    '/dashboard'      => ['home',  'dashboard'],

    '/dreams/new'     => ['dream', 'create'],
    '/dreams/store'   => ['dream', 'store'],
    '/dreams/([A-Za-z0-9]{6,10})' => ['dream', 'show'],
    '/dreams/([A-Za-z0-9]{6,16})/edit' => ['dream', 'edit'],
    '/dreams/update'                   => ['dream', 'update'],   // legacy non-AJAX fallback
	'/dashboard/archived'   => ['home','archived'],
    '/dashboard/trash'      => ['home','trash'],

    '/dreams/new'           => ['dream','create'],
    '/dreams/store'         => ['dream','store'],
    '/dreams/([A-Za-z0-9]{6,16})'             => ['dream','show'],
    '/dreams/([A-Za-z0-9]{6,16})/edit'        => ['dream','edit'],
    '/dreams/([A-Za-z0-9]{6,16})/archive'     => ['dream','archive'],
    '/dreams/([A-Za-z0-9]{6,16})/unarchive'   => ['dream','unarchive'],
    '/dreams/([A-Za-z0-9]{6,16})/delete'      => ['dream','destroy'],
    '/dreams/([A-Za-z0-9]{6,16})/restore'     => ['dream','restore'],

];


    foreach ($routes as $pattern => $target) {
        if (preg_match('@^' . $pattern . '$@', $uri, $matches)) {

            // Locate controller file
            $ctrlFile = __DIR__ . '/../controllers/' . $target[0] . '.php';
            if (!file_exists($ctrlFile)) {
                break; // fall through to 404
            }

            require_once $ctrlFile;
            $class = $target[0] . '_controller';

            // Call the mapped method with any captured params
            if (is_callable([$class, $target[1]])) {
                call_user_func_array(
                    [$class, $target[1]],
                    array_slice($matches, 1) // pass regex captures
                );
                return;
            }
        }
    }

    // No match → 404
    http_response_code(404);
    echo '404 - Not Found';
}
?>
