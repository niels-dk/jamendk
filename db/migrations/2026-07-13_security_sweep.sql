-- Security sweep: track the uploader of each media item so orphan media
-- (uploaded but not yet attached to a board) stays private to them.
ALTER TABLE vision_media
  ADD COLUMN user_id INT NULL;
