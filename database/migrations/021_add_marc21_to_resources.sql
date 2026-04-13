-- 021_add_marc21_to_resources.sql
ALTER TABLE resources
    ADD COLUMN marc_leader CHAR(24) NULL AFTER isbn_13,
    ADD COLUMN marc_control_number VARCHAR(64) NULL AFTER marc_leader,
    ADD COLUMN marc_record JSON NULL AFTER authors,
    ADD UNIQUE INDEX uq_books_marc_control_number (marc_control_number);
