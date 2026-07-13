-- Comment thread per goal (assigner ⇄ assignee discussion).
CREATE TABLE goal_comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  goal_id INT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_goal (goal_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
