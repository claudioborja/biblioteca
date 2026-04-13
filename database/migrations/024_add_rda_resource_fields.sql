-- 024_add_rda_resource_fields.sql
ALTER TABLE resources
    MODIFY COLUMN support_type ENUM('physical','digital','audiovisual','journal','thesis','map','score','kit','game','other')
        NOT NULL DEFAULT 'physical',
    ADD COLUMN resource_type VARCHAR(60) NULL AFTER book_type,
    ADD COLUMN content_type VARCHAR(80) NULL AFTER resource_type,
    ADD COLUMN media_type VARCHAR(80) NULL AFTER content_type,
    ADD COLUMN carrier_type VARCHAR(80) NULL AFTER media_type,
    ADD COLUMN edition_statement VARCHAR(200) NULL AFTER publisher;
