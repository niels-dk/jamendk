-- Goal/milestone assignment + generic notifications.

ALTER TABLE vision_goals
  ADD COLUMN assigned_user_id INT NULL,
  ADD COLUMN assigned_by_user_id INT NULL,
  ADD COLUMN assignment_status ENUM('open','resolved','returned') NOT NULL DEFAULT 'open';

ALTER TABLE vision_goal_milestones
  ADD COLUMN assigned_user_id INT NULL,
  ADD COLUMN due_date DATE NULL;

-- Generic check-to-dismiss notices (assignments, resolutions, returns, …).
-- Each row stays in the recipient's dashboard popup until acknowledged.
CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(40) NOT NULL,
  vision_id BIGINT UNSIGNED NULL,
  goal_id INT UNSIGNED NULL,
  from_user_id INT NULL,
  note TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  acknowledged_at DATETIME NULL,
  KEY idx_user (user_id, acknowledged_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
