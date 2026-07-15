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

    public static function findByEmail(string $email): ?array
    {
        global $db;
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* ─────────────────  Verification & reset tokens  ─────────────────
     * The raw token goes in the emailed link; only its SHA-256 is stored.
     * A leaked database therefore can't be used to take accounts over.
     */

    private static function hashToken(string $raw): string
    {
        return hash('sha256', $raw);
    }

    /** Issue a fresh verification token. Returns the RAW token for the link. */
    public static function issueVerifyToken(int $id, int $hours = 24): ?string
    {
        global $db;
        try {
            $raw = bin2hex(random_bytes(32));
            // $hours inlined (cast to int): MySQL wants a literal after INTERVAL.
            $hours = (int)$hours;
            $db->prepare("UPDATE users SET verify_token = ?,
                                 verify_expires_at = (NOW() + INTERVAL $hours HOUR)
                           WHERE id = ?")
               ->execute([self::hashToken($raw), $id]);
            return $raw;
        } catch (\Throwable $e) {
            return null; // columns not migrated yet
        }
    }

    /** Issue a fresh password-reset token. Returns the RAW token. */
    public static function issueResetToken(int $id, int $hours = 1): ?string
    {
        global $db;
        try {
            $raw = bin2hex(random_bytes(32));
            $hours = (int)$hours;
            $db->prepare("UPDATE users SET reset_token = ?,
                                 reset_expires_at = (NOW() + INTERVAL $hours HOUR)
                           WHERE id = ?")
               ->execute([self::hashToken($raw), $id]);
            return $raw;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Look up an unexpired verification token. */
    public static function findByVerifyToken(string $raw): ?array
    {
        global $db;
        try {
            $st = $db->prepare('SELECT * FROM users
                                 WHERE verify_token = ?
                                   AND verify_expires_at > NOW() LIMIT 1');
            $st->execute([self::hashToken($raw)]);
            return $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Look up an unexpired reset token. */
    public static function findByResetToken(string $raw): ?array
    {
        global $db;
        try {
            $st = $db->prepare('SELECT * FROM users
                                 WHERE reset_token = ?
                                   AND reset_expires_at > NOW() LIMIT 1');
            $st->execute([self::hashToken($raw)]);
            return $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Mark verified and burn the token so the link can't be replayed. */
    public static function markVerified(int $id): void
    {
        global $db;
        $db->prepare('UPDATE users SET email_verified_at = NOW(),
                             verify_token = NULL, verify_expires_at = NULL
                       WHERE id = ?')->execute([$id]);
    }

    /**
     * Set a new password and burn the reset token.
     * Completing a reset also proves control of the mailbox, so it verifies
     * the address too — otherwise a user who never clicked their original
     * verification link would still be locked out after resetting.
     */
    public static function resetPassword(int $id, string $newPass): void
    {
        global $db;
        $db->prepare('UPDATE users SET password_hash = ?,
                             reset_token = NULL, reset_expires_at = NULL,
                             email_verified_at = COALESCE(email_verified_at, NOW())
                       WHERE id = ?')
           ->execute([password_hash($newPass, PASSWORD_DEFAULT), $id]);
    }

    /** True when the account still needs to confirm its address. */
    public static function needsVerification(array $user): bool
    {
        // Column missing (pre-migration) → never block anyone.
        if (!array_key_exists('email_verified_at', $user)) return false;
        return empty($user['email_verified_at']);
    }
}
