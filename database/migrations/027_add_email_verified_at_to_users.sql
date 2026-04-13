-- 027_add_email_verified_at_to_users.sql
-- Backfill migration for instances created before email verification tracking existed.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verified_at DATETIME NULL AFTER remember_token;
