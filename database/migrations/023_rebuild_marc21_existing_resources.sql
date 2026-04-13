-- 023_rebuild_marc21_existing_resources.sql
-- Rebuild MARC21 payload for all existing resources from current relational fields.

UPDATE resources b
LEFT JOIN categories c ON c.id = b.category_id
SET
    b.marc_leader = CASE
        WHEN b.marc_leader IS NULL OR TRIM(b.marc_leader) = '' THEN '00000nam a2200000 i 4500'
        ELSE LEFT(TRIM(b.marc_leader), 24)
    END,
    b.marc_control_number = CASE
        WHEN b.marc_control_number IS NULL OR TRIM(b.marc_control_number) = '' THEN CONCAT('BIB-', LPAD(b.id, 8, '0'))
        ELSE TRIM(b.marc_control_number)
    END,
    b.marc_record = JSON_OBJECT(
        'leader', CASE
            WHEN b.marc_leader IS NULL OR TRIM(b.marc_leader) = '' THEN '00000nam a2200000 i 4500'
            ELSE LEFT(TRIM(b.marc_leader), 24)
        END,
        'controlfields', JSON_OBJECT(
            '001', CASE
                WHEN b.marc_control_number IS NULL OR TRIM(b.marc_control_number) = '' THEN CONCAT('BIB-', LPAD(b.id, 8, '0'))
                ELSE TRIM(b.marc_control_number)
            END,
            '020', COALESCE(b.isbn_13, ''),
            '041', COALESCE(b.language, '')
        ),
        'datafields', JSON_OBJECT(
            '100', JSON_OBJECT('a', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(b.authors, '$[0]')), '')),
            '245', JSON_OBJECT('a', COALESCE(b.title, ''), 'b', ''),
            '250', JSON_OBJECT('a', ''),
            '260', JSON_OBJECT('b', COALESCE(b.publisher, ''), 'c', COALESCE(CAST(b.publication_year AS CHAR), '')),
            '300', JSON_OBJECT('a', CASE
                WHEN b.pages IS NULL OR b.pages = 0 THEN ''
                ELSE CONCAT(CAST(b.pages AS CHAR), ' p.')
            END),
            '520', JSON_OBJECT('a', COALESCE(b.description, '')),
            '650', JSON_OBJECT('a', CASE
                WHEN c.name IS NULL OR TRIM(c.name) = '' THEN JSON_ARRAY()
                ELSE JSON_ARRAY(c.name)
            END),
            '700', JSON_OBJECT('a', CASE
                WHEN JSON_VALID(b.authors) THEN COALESCE(b.authors, JSON_ARRAY())
                ELSE JSON_ARRAY()
            END),
            '856', JSON_OBJECT('u', COALESCE(b.digital_url, ''))
        )
    );
