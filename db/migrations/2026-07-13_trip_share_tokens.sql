-- Public trip sharing: unguessable token URL (/t/{token}) + optional expiry,
-- so publishing a trip no longer exposes the board's real slug.
ALTER TABLE visions
  ADD COLUMN trip_token VARCHAR(64) NULL,
  ADD COLUMN trip_token_expires_at DATETIME NULL,
  ADD UNIQUE KEY uniq_trip_token (trip_token);
