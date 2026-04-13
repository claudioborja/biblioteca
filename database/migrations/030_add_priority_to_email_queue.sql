-- 030_add_priority_to_email_queue.sql
-- Adds a priority column to email_queue so critical emails (verification,
-- role changes, password reset) are processed before bulk reminders.
--
-- Priority scale: 1 = critical (immediate), 5 = normal (default), 9 = low
-- The dispatch index replaces a plain status index so the worker
-- fetches high-priority pending emails first within each cron cycle.

ALTER TABLE email_queue
    ADD COLUMN priority TINYINT UNSIGNED NOT NULL DEFAULT 5 AFTER subject;

-- Drop any existing plain status index and replace with a covering dispatch index
ALTER TABLE email_queue
    ADD INDEX idx_email_queue_dispatch (status, priority, scheduled_at, attempts);
