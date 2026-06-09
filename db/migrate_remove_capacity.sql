-- Migration: elimina capacity din shelters, adauga edit_count in events
-- Ruleaza in phpMyAdmin daca ai deja baza de date creata

ALTER TABLE shelters DROP COLUMN IF EXISTS capacity_total;
ALTER TABLE shelters DROP COLUMN IF EXISTS capacity_used;
ALTER TABLE events ADD COLUMN IF NOT EXISTS edit_count INT NOT NULL DEFAULT 0 AFTER status;
