-- 025_rename_books_to_resources.sql
-- Renombra tabla books → resources y columnas relacionadas
-- book_type → support_type  (tipo de soporte físico/digital)
-- book_id   → resource_id   (FK en tablas dependientes)
-- book_suggestions → resource_suggestions

-- ─── 1. Renombrar tabla principal ────────────────────────────────────────────
RENAME TABLE books TO resources;

-- ─── 2. Renombrar columna book_type → support_type ───────────────────────────
ALTER TABLE resources
    CHANGE COLUMN book_type support_type
        ENUM('physical','digital','audiovisual','journal','thesis','map','score','kit','game','other')
        NOT NULL DEFAULT 'physical';

-- Actualizar índice que usaba book_type
ALTER TABLE resources
    DROP INDEX idx_books_type_active,
    ADD INDEX idx_resources_support_active (support_type, is_active);

-- Renombrar índices y constraints que mencionan "books"
ALTER TABLE resources
    DROP INDEX uq_books_isbn,
    ADD UNIQUE INDEX uq_resources_isbn (isbn_13);

ALTER TABLE resources
    DROP INDEX ft_books,
    ADD FULLTEXT INDEX ft_resources (title, publisher, description);

-- Renombrar índice de branch
ALTER TABLE resources
    DROP INDEX idx_books_branch,
    ADD INDEX idx_resources_branch (branch_id, is_active);

ALTER TABLE resources
    DROP INDEX idx_books_new_acquisition,
    ADD INDEX idx_resources_new_acquisition (is_new_acquisition, acquired_at);

ALTER TABLE resources
    DROP INDEX idx_books_category,
    ADD INDEX idx_resources_category (category_id);

-- Renombrar FK constraints internos de resources
ALTER TABLE resources
    DROP FOREIGN KEY fk_books_category,
    ADD CONSTRAINT fk_resources_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE resources
    DROP FOREIGN KEY fk_books_branch,
    ADD CONSTRAINT fk_resources_branch
        FOREIGN KEY (branch_id) REFERENCES library_branches (id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE resources
    DROP FOREIGN KEY fk_books_deactivated_by,
    ADD CONSTRAINT fk_resources_deactivated_by
        FOREIGN KEY (deactivated_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL;

-- ─── 3. Tabla loans: book_id → resource_id ───────────────────────────────────
ALTER TABLE loans
    DROP FOREIGN KEY fk_loans_book,
    DROP INDEX idx_loans_book_status;

ALTER TABLE loans
    CHANGE COLUMN book_id resource_id INT UNSIGNED NOT NULL;

ALTER TABLE loans
    ADD CONSTRAINT fk_loans_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD INDEX idx_loans_resource_status (resource_id, status);

-- ─── 4. Tabla reservations: book_id → resource_id ────────────────────────────
ALTER TABLE reservations
    DROP FOREIGN KEY fk_reservations_book,
    DROP INDEX idx_reservations_book_queue;

ALTER TABLE reservations
    CHANGE COLUMN book_id resource_id INT UNSIGNED NOT NULL;

ALTER TABLE reservations
    ADD CONSTRAINT fk_reservations_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD INDEX idx_reservations_resource_queue (resource_id, queue_position, status);

-- ─── 5. Tabla digital_access_log: book_id → resource_id ──────────────────────
ALTER TABLE digital_access_log
    DROP FOREIGN KEY fk_digital_access_book,
    DROP INDEX idx_digital_access_book;

ALTER TABLE digital_access_log
    CHANGE COLUMN book_id resource_id INT UNSIGNED NOT NULL;

ALTER TABLE digital_access_log
    ADD CONSTRAINT fk_digital_access_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    ADD INDEX idx_digital_access_resource (resource_id);

-- ─── 6. Tabla reading_assignments: book_id → resource_id ─────────────────────
ALTER TABLE reading_assignments
    DROP FOREIGN KEY fk_reading_assignments_book,
    DROP INDEX idx_reading_assignments_book;

ALTER TABLE reading_assignments
    CHANGE COLUMN book_id resource_id INT UNSIGNED NOT NULL;

ALTER TABLE reading_assignments
    ADD CONSTRAINT fk_reading_assignments_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD INDEX idx_reading_assignments_resource (resource_id);

-- ─── 7. Renombrar tabla book_suggestions → resource_suggestions ───────────────
RENAME TABLE book_suggestions TO resource_suggestions;

ALTER TABLE resource_suggestions
    DROP INDEX idx_book_suggestions_user,
    DROP INDEX idx_book_suggestions_status,
    DROP INDEX idx_book_suggestions_reviewer,
    DROP FOREIGN KEY fk_book_suggestions_user,
    DROP FOREIGN KEY fk_book_suggestions_reviewer;

ALTER TABLE resource_suggestions
    ADD INDEX idx_resource_suggestions_user (user_id),
    ADD INDEX idx_resource_suggestions_status (status),
    ADD INDEX idx_resource_suggestions_reviewer (reviewed_by),
    ADD CONSTRAINT fk_resource_suggestions_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE,
    ADD CONSTRAINT fk_resource_suggestions_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users (id)
        ON DELETE SET NULL;
