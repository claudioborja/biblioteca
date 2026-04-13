-- 002_create_library_branches.sql
CREATE TABLE IF NOT EXISTS library_branches (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    code        VARCHAR(10)     NOT NULL,
    name        VARCHAR(150)    NOT NULL,
    address     TEXT            NOT NULL,
    phone       VARCHAR(30)     NULL,
    email       VARCHAR(150)    NULL,
    schedule    TEXT            NULL,
    manager_id  INT UNSIGNED    NULL,
    is_main     TINYINT(1)      NOT NULL DEFAULT 0,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    sort_order  TINYINT         NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE INDEX uq_branches_code (code),
    UNIQUE INDEX uq_branches_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
