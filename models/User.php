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
            // Anyone signing up during the free era is a Founding Creator —
            // free forever at their team size. Separate, non-fatal statement so
            // it degrades gracefully if the column isn't migrated yet.
            if ($id) {
                try {
                    $db->prepare('UPDATE users SET founding_creator_at = NOW()
                                   WHERE id = ? AND founding_creator_at IS NULL')
                       ->execute([$id]);
                } catch (\Throwable $e) { /* column not migrated — fine */ }

                // If they arrived through a tracked link, stamp it. This is what
                // turns "the campaign got clicks" into "the campaign got users".
                try {
                    require_once __DIR__ . '/../app/links.php';
                    $linkId = LinkTokens::currentId();
                    if ($linkId) {
                        $db->prepare('UPDATE users SET signup_link_token_id = ? WHERE id = ?')
                           ->execute([$linkId, $id]);
                    }
                } catch (\Throwable $e) { /* column not migrated — fine */ }
            }
            return $id ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /* ───────────────── Login throttling ─────────────────
     *
     * Two sliding windows, checked independently:
     *   identifier — 5 failures in 15 min stops someone hammering one account
     *   ip         — 25 failures in 15 min stops one host spraying a password
     *                across many accounts, which the per-identifier limit
     *                would never see
     *
     * Sliding, not a hard lock: an attacker who fails on purpose against
     * someone else's address can otherwise lock that person out of their own
     * account indefinitely. Fifteen quiet minutes clears it.
     *
     * Every method degrades to "not limited" if the table is missing, so a
     * pending migration cannot lock anybody out of the site.
     */
    private const THROTTLE_WINDOW_MIN  = 15;
    private const THROTTLE_MAX_IDENT   = 5;
    private const THROTTLE_MAX_IP      = 25;

    /** True when this identifier or this IP has failed too often lately. */
    public static function loginThrottled(string $identifier, string $ip): bool
    {
        global $db;
        try {
            $win = (int)self::THROTTLE_WINDOW_MIN;   // literal after INTERVAL

            $st = $db->prepare("SELECT COUNT(*) FROM login_attempts
                                 WHERE identifier = ?
                                   AND created_at > (NOW() - INTERVAL $win MINUTE)");
            $st->execute([$identifier]);
            if ((int)$st->fetchColumn() >= self::THROTTLE_MAX_IDENT) return true;

            if ($ip !== '') {
                $st = $db->prepare("SELECT COUNT(*) FROM login_attempts
                                     WHERE ip = ?
                                       AND created_at > (NOW() - INTERVAL $win MINUTE)");
                $st->execute([$ip]);
                if ((int)$st->fetchColumn() >= self::THROTTLE_MAX_IP) return true;
            }
            return false;
        } catch (\Throwable $e) {
            // Table not migrated yet — never lock people out over that.
            return false;
        }
    }

    /** Record one failed attempt, and opportunistically prune old rows. */
    public static function recordFailedLogin(string $identifier, string $ip): void
    {
        global $db;
        try {
            $db->prepare('INSERT INTO login_attempts (identifier, ip) VALUES (?, ?)')
               ->execute([mb_substr($identifier, 0, 190), $ip !== '' ? $ip : null]);

            // Housekeeping without a cron: roughly one request in twenty clears
            // anything older than a day. Nothing here is useful after that.
            if (random_int(1, 20) === 1) {
                $db->exec('DELETE FROM login_attempts
                            WHERE created_at < (NOW() - INTERVAL 24 HOUR)');
            }
        } catch (\Throwable $e) { /* never block a login on bookkeeping */ }
    }

    /** A correct password clears that identifier's history. */
    public static function clearLoginAttempts(string $identifier): void
    {
        global $db;
        try {
            $db->prepare('DELETE FROM login_attempts WHERE identifier = ?')
               ->execute([$identifier]);
        } catch (\Throwable $e) { /* ignore */ }
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
