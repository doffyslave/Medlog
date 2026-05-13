-- MedLog appointments module — optional one-time upgrades
-- Run in phpMyAdmin or mysql CLI if automatic ALTER from PHP is not permitted.

-- Widen status for new lifecycle values (safe if already VARCHAR)
ALTER TABLE appointments
    MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'Pending';

-- Optional note when an appointment is rescheduled
ALTER TABLE appointments
    ADD COLUMN reschedule_note TEXT NULL;
