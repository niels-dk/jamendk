-- One-time "new boards shared with you" notice:
-- shares with created_at newer than this stamp are announced once.
ALTER TABLE users
  ADD COLUMN shares_seen_at DATETIME NULL;
