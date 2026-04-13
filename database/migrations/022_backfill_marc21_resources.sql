-- 022_backfill_marc21_resources.sql
-- Backfill MARC 21 core fields from legacy book columns.

UPDATE resources
SET marc_leader = '00000nam a2200000 i 4500'
WHERE marc_leader IS NULL OR TRIM(marc_leader) = '';

UPDATE resources
SET marc_control_number = CONCAT('BIB-', LPAD(id, 8, '0'))
WHERE marc_control_number IS NULL OR TRIM(marc_control_number) = '';

UPDATE resources
SET marc_record = JSON_OBJECT(
    'leader', COALESCE(NULLIF(marc_leader, ''), '00000nam a2200000 i 4500'),
    'controlfields', JSON_OBJECT(
        '001', COALESCE(marc_control_number, ''),
        '020', COALESCE(isbn_13, ''),
        '041', COALESCE(language, '')
    ),
    'datafields', JSON_OBJECT(
        '100', JSON_OBJECT('a', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(authors, '$[0]')), '')),
        '245', JSON_OBJECT('a', COALESCE(title, ''), 'b', ''),
        '250', JSON_OBJECT('a', ''),
        '260', JSON_OBJECT('b', COALESCE(publisher, ''), 'c', COALESCE(CAST(publication_year AS CHAR), '')),
        '300', JSON_OBJECT('a', ''),
        '520', JSON_OBJECT('a', COALESCE(description, '')),
        '650', JSON_OBJECT('a', JSON_ARRAY()),
        '700', JSON_OBJECT('a', CASE WHEN JSON_VALID(authors) THEN COALESCE(authors, JSON_ARRAY()) ELSE JSON_ARRAY() END),
        '856', JSON_OBJECT('u', COALESCE(digital_url, ''))
    )
)
WHERE marc_record IS NULL;
