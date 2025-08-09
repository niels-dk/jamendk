<?php
require_once __DIR__.'/../models/User.php';

class user_controller
{
    public static function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $pass  = $_POST['password'] ?? '';
            if ($user = User::authenticate($email, $pass)) {
                $_SESSION['uid'] = $user['id'];
                redirect('dashboard');
            }
            $error = 'Invalid credentials';
        }
        include __DIR__.'/../views/login.php';
    }

    public static function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name  = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $pass  = $_POST['password'] ?? '';
            if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $pass) {
                User::create($name, $email, $pass);
                redirect('login');
            }
            $error = 'Invalid form input';
        }
        include __DIR__.'/../views/register.php';
    }

    public static function logout(): void
    {
        session_destroy();
        redirect('');
    }
}
?>