-- Tracked marketing links: merelyadream.com/l/{token}
--
-- The token is the only thing in the URL; every dimension lives here, so a
-- link already printed on a flyer or sitting in an Instagram bio can be
-- re-labelled, re-pointed or given new dimensions later without reissuing it.

CREATE TABLE link_tokens (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token       VARCHAR(48) NOT NULL,          -- the /l/{token} string
  label       VARCHAR(120) NOT NULL,         -- human name in admin
  target      VARCHAR(255) NOT NULL DEFAULT '/',  -- internal path to land on

  -- Dimensions
  from_place  VARCHAR(80) NULL,   -- where it physically lives: instagram-bio, flyer-cphdox
  campaign_id VARCHAR(80) NULL,   -- the campaign this belongs to
  product     VARCHAR(80) NULL,   -- which product is promoted
  feature     VARCHAR(80) NULL,   -- which feature is promoted
  source      VARCHAR(40) NULL,   -- email / web / app / social / print / qr / person
  partner_id  VARCHAR(80) NULL,   -- who sent them (partner, affiliate, festival…)

  notes       VARCHAR(255) NULL,
  active      TINYINT(1) NOT NULL DEFAULT 1,
  expires_at  DATETIME NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_token (token),
  KEY idx_campaign (campaign_id),
  KEY idx_partner (partner_id),
  KEY idx_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Which link (if any) a page view is attributed to. Set on the click and
-- carried for the rest of that visit, so you see the whole journey, not just
-- the landing hit.
ALTER TABLE analytics_visits
  ADD COLUMN link_token_id INT UNSIGNED NULL,
  ADD KEY idx_link_token (link_token_id, day);

-- The payoff: which link produced an actual account. Without this you only
-- ever learn about clicks, never conversions.
ALTER TABLE users
  ADD COLUMN signup_link_token_id INT UNSIGNED NULL,
  ADD KEY idx_signup_link (signup_link_token_id);
