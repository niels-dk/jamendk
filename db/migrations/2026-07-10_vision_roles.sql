-- Board-level roles: share a Vision with other users.
-- Owner is implicit (visions.user_id); everyone else gets a row here.
-- Mood boards inherit the parent Vision's roles (per scope docs).
-- Run once in phpMyAdmin (jamen_dk database).

CREATE TABLE vision_roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vision_id BIGINT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  role ENUM('co_owner','editor','viewer','delegate') NOT NULL DEFAULT 'viewer',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_vision_user (vision_id, user_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
