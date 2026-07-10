-- Site-level user roles + seeded admin account.
-- Run once in phpMyAdmin (jamen_dk database).

-- 1) Role column: 'admin' controls everything, 'user' is a normal creator.
ALTER TABLE users
  ADD COLUMN role ENUM('admin','user') NOT NULL DEFAULT 'user';

-- 2) Emails must be unique (login identity).
ALTER TABLE users
  ADD UNIQUE KEY uniq_email (email);

-- 3) Seed the admin account (username: admin / password: admin).
--    CHANGE THIS PASSWORD after first login on a public server!
INSERT INTO users (email, password_hash, name, role) VALUES
('admin', '$2y$10$.NA9uQJjDzEJn.6CUiaqpez48leKuhoyIsRqEyrbyKEyyiwEJVm6i', 'Admin', 'admin');

-- 4) Optional: also make Niels a site admin (uncomment to apply).
-- UPDATE users SET role='admin' WHERE id=1;
