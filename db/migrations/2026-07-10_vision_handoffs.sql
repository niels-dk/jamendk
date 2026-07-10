-- "Work handed back" notes: a collaborator sends a vision back to its
-- owner with an optional note. Each row stays in the owner's login
-- notice until they explicitly acknowledge (check) it.
CREATE TABLE vision_handoffs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vision_id BIGINT UNSIGNED NOT NULL,
  from_user_id INT NOT NULL,
  to_user_id INT NOT NULL,
  note TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  acknowledged_at DATETIME NULL,
  KEY idx_to (to_user_id, acknowledged_at),
  KEY idx_vision (vision_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
