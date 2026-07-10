-- Simple profile fields for the account page.
ALTER TABLE users
  ADD COLUMN company VARCHAR(150) NULL,
  ADD COLUMN organisation VARCHAR(150) NULL;
