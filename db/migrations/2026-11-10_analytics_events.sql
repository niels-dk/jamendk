-- Before-sign-up funnel: a few counted moments on the public pages.
--
-- Same promises as analytics_visits (see 2026-07-18_analytics.sql) — do not
-- break them without changing the privacy page too:
--   * no cookie, no IP stored, nothing sent anywhere
--   * visitor_hash is the same salted one-day fingerprint, so a moment can be
--     tied to a visit on the SAME day and to nothing else
--   * `name` is one of a fixed list (Analytics::EVENTS); `detail` is a short
--     code such as "hero" or "short_pass" — never anything a person typed
--
-- What it is for: analytics_visits can say that 870 visitors saw the front
-- page and 52 saw the sign-up form, but most of those are scanners, and it
-- cannot say what happened in between. A row here is written only when a real
-- browser ran the page's script (or, for the signup_* rows, when the server
-- handled a sign-up attempt).

CREATE TABLE analytics_events (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  day          DATE NOT NULL,
  visitor_hash CHAR(64) NOT NULL,          -- salted daily hash; not reversible
  name         VARCHAR(24) NOT NULL,       -- human / scroll / cta / form_start / …
  detail       VARCHAR(40) NULL,           -- hero, half, short_pass, …
  path         VARCHAR(190) NOT NULL,      -- page it happened on, normalised
  device       VARCHAR(10) NULL,           -- phone / tablet / desktop
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_day_name (day, name),
  KEY idx_day_visitor (day, visitor_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
