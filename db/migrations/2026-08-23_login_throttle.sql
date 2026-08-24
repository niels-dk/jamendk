-- Login throttling.
--
-- Until now password attempts were unlimited. Combined with a seeded
-- admin/admin account and steady scanner traffic on /login, that was the one
-- path in this stack a credential-stuffing bot could actually walk through.
--
-- Only FAILURES are recorded, and a successful sign-in clears that identifier's
-- history, so nothing accumulates about people who simply log in.
--
-- Two independent counters, because either one alone has a hole:
--   * per identifier — stops someone hammering one account
--   * per IP         — stops someone spraying one password across many accounts
-- Both are sliding windows, so a lockout heals by itself. That matters: a
-- per-identifier lock with no expiry lets an attacker lock a real user out of
-- their own account just by failing on purpose.
--
-- ip is stored in the clear, as mail_log already does for the same
-- abuse-prevention reason, and the privacy page says so. Rows are pruned after
-- 24 hours; nothing here is useful beyond that.

CREATE TABLE IF NOT EXISTS login_attempts (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(190) NOT NULL,   -- the email/username that was TRIED
  ip         VARCHAR(45)  NULL,       -- 45 = max INET6_ADDRSTRLEN
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ident_time (identifier, created_at),
  KEY idx_ip_time (ip, created_at),
  KEY idx_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
