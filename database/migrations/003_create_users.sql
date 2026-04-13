-- 003_create_users.sql
CREATE TABLE IF NOT EXISTS users (
    id                    INT UNSIGNED         NOT NULL AUTO_INCREMENT,
    user_number     VARCHAR(20)          NULL,
    name                  VARCHAR(150)         NOT NULL,
    email                 VARCHAR(150)         NOT NULL,
    document_number       VARCHAR(30)          NOT NULL,
    phone                 VARCHAR(30)          NULL,
    address               TEXT                 NULL,
    birthdate             DATE                 NULL,
    photo                 VARCHAR(255)         NULL,
    role                  ENUM('admin','librarian','teacher','user','guest') NOT NULL DEFAULT 'user',
    user_type           ENUM('student','teacher','external','staff') NOT NULL DEFAULT 'student',
    status                ENUM('active','suspended','blocked','inactive') NOT NULL DEFAULT 'active',
    password_hash         VARCHAR(255)         NOT NULL,
    remember_token        VARCHAR(64)          NULL,
    email_verified_at     DATETIME             NULL,
    force_password_change TINYINT(1)           NOT NULL DEFAULT 0,
    last_login_at         DATETIME             NULL,
    last_login_ip         VARCHAR(45)          NULL,
    created_at            DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE INDEX uq_users_email (email),
    UNIQUE INDEX uq_users_document (document_number),
    UNIQUE INDEX uq_users_user_number (user_number),
    INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add FK for library_branches.manager_id after users table exists
ALTER TABLE library_branches
    ADD CONSTRAINT fk_branches_manager
    FOREIGN KEY (manager_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL;
