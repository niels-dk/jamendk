-- Account handover + login deactivation.
--
-- Handover moves everything a creator OWNS (dreams, visions, moods, teams) to
-- another creator's account. Accounts are single-user, so we never share a
-- login — we reassign ownership. Boards merely shared WITH the departing
-- person stay with their real owner.
--
-- Creator-initiated transfers are a two-party agreement: a pending request the
-- recipient must accept. Admins can also transfer directly (for someone who's
-- already gone) and, optionally, block the old login.

CREATE TABLE account_transfers (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  from_user_id INT NOT NULL,
  to_user_id   INT NOT NULL,
  -- pending: awaiting the recipient. accepted/declined/cancelled are terminal.
  status       ENUM('pending','accepted','declined','cancelled') NOT NULL DEFAULT 'pending',
  -- who set it running: the owner (self-serve) or an admin (assisted).
  initiated_by ENUM('owner','admin') NOT NULL DEFAULT 'owner',
  note         VARCHAR(500) NULL,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  resolved_at  DATETIME NULL,
  KEY idx_to_pending (to_user_id, status),
  KEY idx_from (from_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Block a login without deleting the person (keeps their history intact).
-- Set when someone leaves; auth refuses a sign-in while it's non-null.
ALTER TABLE users
  ADD COLUMN deactivated_at DATETIME NULL;
