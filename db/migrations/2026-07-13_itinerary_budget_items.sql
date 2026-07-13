-- Trip layer upgrade: day-by-day itinerary + budget line items.

CREATE TABLE vision_itinerary (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vision_id BIGINT UNSIGNED NOT NULL,
  day_date DATE NOT NULL,
  start_time TIME NULL,
  title VARCHAR(255) NOT NULL,
  location VARCHAR(255) NULL,
  notes TEXT NULL,
  show_on_trip TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_vision_day (vision_id, day_date, start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Section toggle for the Basics "Show on Trip layer" list
ALTER TABLE vision_presentation
  ADD COLUMN itinerary TINYINT(1) NOT NULL DEFAULT 1;

-- Budget breakdown (travel / gear / talent / …); the single
-- vision_budget total becomes the sum of items when items exist.
CREATE TABLE vision_budget_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vision_id BIGINT UNSIGNED NOT NULL,
  label VARCHAR(150) NOT NULL,
  amount_cents BIGINT NOT NULL DEFAULT 0,
  paid TINYINT(1) NOT NULL DEFAULT 0,
  show_on_trip TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  KEY idx_vision (vision_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
