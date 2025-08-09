<?php
class User
{
    public static function create(string $name, string $email, string $pass): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');
        return $stmt->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
    }

    public static function authenticate(string $email, string $pass): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password_hash'])) {
            return $user;
        }
        return null;
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
?>