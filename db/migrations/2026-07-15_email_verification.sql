-- Email verification + password reset + a log of everything we send.
--
-- Tokens are stored as SHA-256 hashes, never in the clear: the raw token
-- only ever exists in the emailed link. A dump of this table therefore
-- can't be used to take over an account.

ALTER TABLE users
  ADD COLUMN email_verified_at DATETIME NULL,
  ADD COLUMN verify_token      CHAR(64) NULL,
  ADD COLUMN verify_expires_at DATETIME NULL,
  ADD COLUMN reset_token       CHAR(64) NULL,
  ADD COLUMN reset_expires_at  DATETIME NULL,
  ADD UNIQUE KEY uniq_verify_token (verify_token),
  ADD UNIQUE KEY uniq_reset_token  (reset_token);

-- Grandfather every existing account: nobody who can log in today gets
-- locked out tomorrow. Verification applies to new signups from here on.
UPDATE users SET email_verified_at = NOW() WHERE email_verified_at IS NULL;

-- Every send attempt, so the admin can see what went out and what failed.
-- Doubles as the rate limiter (see Mailer::rateLimited).
CREATE TABLE mail_log (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  to_email   VARCHAR(255) NOT NULL,
  subject    VARCHAR(255) NOT NULL,
  type       VARCHAR(40) NULL,          -- verify / reset / reset_notice / test
  status     ENUM('sent','failed') NOT NULL,
  error      TEXT NULL,
  ip         VARCHAR(45) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_to_created (to_email, created_at),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
