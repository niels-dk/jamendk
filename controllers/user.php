<?php
require_once __DIR__ . '/../models/User.php';

class user_controller
{
    /** GET /login  (form)  |  POST /login  (authenticate) */
    public static function login(): void
    {
        $error = null;
        // Remember where the user was trying to go (set as ?next=)
        $next = self::safeNext($_REQUEST['next'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check($_POST['csrf_token'] ?? null)) {
                $error = 'Your session expired. Please try again.';
            } else {
                $email = trim($_POST['email'] ?? '');
                $pass  = $_POST['password'] ?? '';
                if (!$email || !$pass) {
                    $error = 'Please enter both email and password.';
                } else if ($user = User::authenticate($email, $pass)) {
                    self::loginUser($user);
                    redirect($next ?: 'dashboard');
                } else {
                    $error = 'Invalid email or password.';
                }
            }
        }
        include __DIR__ . '/../views/login.php';
    }

    /** GET /register  (form)  |  POST /register  (create + auto-login) */
    public static function register(): void
    {
        $error = null;
        $name  = '';
        $email = '';
        $next  = self::safeNext($_REQUEST['next'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check($_POST['csrf_token'] ?? null)) {
                $error = 'Your session expired. Please try again.';
            } else {
                $name  = trim($_POST['name']  ?? '');
                $email = trim($_POST['email'] ?? '');
                $pass  = $_POST['password'] ?? '';
                if (!$name) {
                    $error = 'Please tell us your name.';
                } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'That email address doesn\'t look right.';
                } else if (strlen($pass) < 6) {
                    $error = 'Password must be at least 6 characters.';
                } else if (User::emailExists($email)) {
                    $error = 'An account with that email already exists.';
                } else {
                    $newId = User::create($name, $email, $pass);
                    if (!$newId) {
                        $error = 'Could not create the account. Please try again.';
                    } else {
                        $user = User::find($newId);
                        if ($user) {
                            self::loginUser($user);
                            redirect($next ?: 'dashboard');
                        }
                        $error = 'Account created — please sign in.';
                        redirect('login' . ($next ? '?next=' . urlencode($next) : ''));
                    }
                }
            }
        }
        include __DIR__ . '/../views/register.php';
    }

    public static function logout(): void
    {
        // Clear PHP session entirely so the auth.php fallback kicks in again on next request.
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']);
        }
        session_destroy();
        redirect('');
    }

    /**
     * Validate a ?next= redirect target. Accepts only relative URLs starting
     * with "/" (no protocol, no //host) so attackers can't bounce sign-ins
     * off our site to phishing pages.
     */
    private static function safeNext(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '/') return '';
        if ($raw[0] !== '/' || (isset($raw[1]) && $raw[1] === '/')) return '';
        return $raw;
    }

    /** Internal: write a successful sign-in into the session. */
    private static function loginUser(array $user): void
    {
        session_regenerate_id(true); // prevent session fixation
        $_SESSION['user_id']       = (int)$user['id'];
        $_SESSION['authenticated'] = true;
        $_SESSION['user']          = [
            'id'    => (int)$user['id'],
            'name'  => $user['name']  ?? '',
            'email' => $user['email'] ?? '',
            'role'  => $user['role']  ?? 'user',
        ];
    }
}
