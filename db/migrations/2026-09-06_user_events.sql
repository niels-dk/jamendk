-- Per-user history: an append-only support timeline.
--
-- Deactivating someone records THAT an account is off, but never why — and six
-- months later "why" is the only question anyone actually asks. Same for a
-- discount, or a support conversation that ended in an exception being made.
--
-- Every entry carries who wrote it and when, and nothing in the UI can edit or
-- delete a single one. That is what makes this history rather than a notes
-- field: an entry that can be quietly rewritten cannot answer the question it
-- exists for.
--
-- `type` is a free string rather than an ENUM on purpose. A new kind of entry
-- later — a discount and its reason, a support contact — is then a new row,
-- not a migration.
--
-- No string column here is ever compared against another table (author_id and
-- user_id are numeric), which sidesteps the collation mismatch that already
-- exists between users.email and mail_log.to_email.
--
-- PRIVACY: these entries describe a named person, so they are that person's
-- data. Deleting the account deletes them too (see Admin::deleteUser), and the
-- person may ask to see them. Write facts, not opinions.

CREATE TABLE IF NOT EXISTS user_events (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,   -- who the entry is about
  author_id  INT UNSIGNED NULL,       -- the admin who wrote it; NULL = the system
  type       VARCHAR(40) NOT NULL,    -- note | deactivated | reactivated | ...
  body       TEXT NULL,               -- may be empty on a bare event
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_time (user_id, created_at),
  KEY idx_type (type)
);
