-- First-party analytics. No cookies, no third parties, no personal data.
--
-- Design notes (these are the promises the privacy page now makes — don't
-- break them without changing that page too):
--   * IP addresses are NEVER stored. They're mixed into a one-way hash with a
--     secret salt AND the date, so the same person on two different days
--     produces two unrelated hashes. Nothing can be reversed to an individual
--     or followed across days.
--   * No cookie is set. Uniqueness is inferred per-day from that hash only.
--   * Nothing is sent anywhere — the data never leaves this database.

CREATE TABLE analytics_visits (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  day          DATE NOT NULL,
  path         VARCHAR(190) NOT NULL,
  ref_host     VARCHAR(190) NULL,          -- referring domain only, never full URL
  utm_source   VARCHAR(80) NULL,
  utm_medium   VARCHAR(80) NULL,
  utm_campaign VARCHAR(80) NULL,
  visitor_hash CHAR(64) NOT NULL,          -- salted daily hash; not reversible
  is_auth      TINYINT(1) NOT NULL DEFAULT 0,
  device       VARCHAR(10) NULL,           -- phone / tablet / desktop
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_day (day),
  KEY idx_day_path (day, path),
  KEY idx_day_ref (day, ref_host),
  KEY idx_day_visitor (day, visitor_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Holds the hashing salt. Generated once, random, server-side only. If this
-- row is deleted a new salt is generated and old hashes become permanently
-- unlinkable — which is a feature, not a problem.
CREATE TABLE analytics_meta (
  k VARCHAR(40) NOT NULL PRIMARY KEY,
  v VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO analytics_meta (k, v)
VALUES ('salt', SHA2(CONCAT(RAND(), UUID(), NOW()), 256));
