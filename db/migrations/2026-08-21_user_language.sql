-- Per-user interface language.
--
-- Stored on the user (not a cookie) for two reasons: it follows them across
-- devices, and outbound email can look up the RECIPIENT's language. A Danish
-- account inviting a Brazilian collaborator must not send that person a
-- Danish verification mail.
--
-- NULL = follow the site default (English). Only logged-in users are
-- translated for now; anonymous visitors always get English until the
-- translations are proven.

ALTER TABLE users
  ADD COLUMN lang VARCHAR(5) NULL AFTER email;
