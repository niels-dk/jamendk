<?php
class User
{
    /** Returns the new user's id, or null on failure (e.g. duplicate email). */
    public static function create(string $name, string $email, string $pass): ?int
    {
        global $db;
        try {
            $stmt = $db->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            $id = (int)$db->lastInsertId();
            return $id ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function authenticate(string $email, string $pass): ?array
    {
        global $db;
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($pass, $user['password_hash'])) {
            return $user;
        }
        return null;
    }

    public static function find(int $id): ?array
    {
        global $db;
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        return $u ?: null;
    }

    public static function emailExists(string $email): bool
    {
        global $db;
        $stmt = $db->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return (bool)$stmt->fetchColumn();
    }
}
