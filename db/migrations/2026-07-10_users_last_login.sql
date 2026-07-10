-- Track last sign-in per user (shown on /admin/users).
ALTER TABLE users
  ADD COLUMN last_login_at DATETIME NULL;
