-- Founding Creators.
--
-- Shadow pricing: the tier model is fully wired up but every charge is €0
-- while in beta. This one column records who joined during the free era, so
-- we can honour the promise — free forever at their team size — when payments
-- eventually switch on. Everything else (seat count, tier, "saved so far") is
-- computed live from teams/roles, so there's nothing else to store.

ALTER TABLE users
  ADD COLUMN founding_creator_at DATETIME NULL;

-- Grandfather everyone who's already here. They believed in it first; they
-- keep it free. Backdate to when they actually joined, so the "saved since"
-- number on their dashboard tells the truth.
UPDATE users
   SET founding_creator_at = COALESCE(created_at, NOW())
 WHERE founding_creator_at IS NULL;
