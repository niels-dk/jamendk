-- Shot list: the "what to capture" layer — a filming idea tied to a place
-- and a moment. Shots attach to a specific day (or float as "Anytime"),
-- can pin mood-board images as visual references, and get checked off in
-- the field from the trip page.

CREATE TABLE vision_shots (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vision_id     BIGINT UNSIGNED NOT NULL,
  itinerary_id  INT UNSIGNED NULL,                    -- reserved: link to a specific itinerary entry
  day_date      DATE NULL,                            -- NULL = "Anytime" bucket
  title         VARCHAR(255) NOT NULL,
  shot_type     VARCHAR(30) NULL,                     -- drone / broll / interview / timelapse / photo / pov / other
  how_notes     TEXT NULL,                            -- angle, movement, lens, what to say to camera
  light         VARCHAR(30) NULL,                     -- sunrise / golden / midday / blue / night
  location      VARCHAR(255) NULL,
  priority      TINYINT(1) NOT NULL DEFAULT 0,        -- 1 = must-have
  status        ENUM('planned','captured','dropped') NOT NULL DEFAULT 'planned',
  captured_at   DATETIME NULL,
  show_on_trip  TINYINT(1) NOT NULL DEFAULT 1,
  sort_order    INT NOT NULL DEFAULT 0,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_vision_day (vision_id, day_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mood-board images pinned to a shot as visual references
CREATE TABLE vision_shot_refs (
  shot_id  INT UNSIGNED NOT NULL,
  media_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (shot_id, media_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Section toggles for the Basics "Show on Trip layer" list
ALTER TABLE vision_presentation
  ADD COLUMN shots   TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN anchors TINYINT(1) NOT NULL DEFAULT 1;
