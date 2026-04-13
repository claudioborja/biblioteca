-- 026_rename_member_to_user_fields.sql
-- Rename member-specific columns and update role/type enum values

-- Rename membership_number → user_number
ALTER TABLE users
    CHANGE membership_number user_number VARCHAR(20) NULL;

-- Update unique index name
ALTER TABLE users
    DROP INDEX uq_users_membership,
    ADD UNIQUE INDEX uq_users_user_number (user_number);

-- Rename member_type → user_type
ALTER TABLE users
    CHANGE member_type user_type ENUM('student','teacher','external','staff') NOT NULL DEFAULT 'student';

-- Update role enum: add 'user', migrate data, remove 'member'
ALTER TABLE users
    MODIFY role ENUM('admin','librarian','teacher','member','user','guest') NOT NULL DEFAULT 'user';

UPDATE users SET role = 'user' WHERE role = 'member';

ALTER TABLE users
    MODIFY role ENUM('admin','librarian','teacher','user','guest') NOT NULL DEFAULT 'user';

-- Update system_settings default_member_type references if applicable
UPDATE system_settings SET value = 'user' WHERE `key` = 'default_role' AND value = 'member';
