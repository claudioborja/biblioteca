-- visits_demo.sql — Datos históricos de visitas para dashboard y reportes
-- Inserta visitas distribuidas en distintas fechas sin duplicar el mismo registro exacto.

INSERT INTO visits_log (
    user_id,
    branch_id,
    page,
    ip_address,
    user_agent,
    referer,
    created_at
)
SELECT
    seed.user_id,
    seed.branch_id,
    seed.page,
    seed.ip_address,
    seed.user_agent,
    seed.referer,
    seed.created_at
FROM (
    SELECT
        (SELECT id FROM users WHERE email = 'admin@biblioteca.local' LIMIT 1) AS user_id,
        (SELECT id FROM library_branches ORDER BY id ASC LIMIT 1) AS branch_id,
        '/' AS page,
        '10.10.0.10' AS ip_address,
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/135.0 Safari/537.36' AS user_agent,
        'https://www.google.com/' AS referer,
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 35 DAY), '08:15:00') AS created_at

    UNION ALL SELECT
        NULL,
        NULL,
        '/catalogo',
        '190.15.22.101',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/134.0 Safari/537.36',
        'https://facebook.com/',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 32 DAY), '11:40:00')

    UNION ALL SELECT
        (SELECT id FROM users WHERE email = 'usuario@biblioteca.local' LIMIT 1),
        (SELECT id FROM library_branches ORDER BY id ASC LIMIT 1),
        '/recursos/15',
        '10.10.0.20',
        'Mozilla/5.0 (Android 14; Mobile) AppleWebKit/537.36 Chrome/133.0 Mobile Safari/537.36',
        '/catalogo',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 28 DAY), '14:05:00')

    UNION ALL SELECT
        NULL,
        NULL,
        '/novedades',
        '181.198.10.55',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 18_3 like Mac OS X) AppleWebKit/605.1.15 Version/18.3 Mobile/15E148 Safari/604.1',
        'https://m.facebook.com/',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 25 DAY), '09:25:00')

    UNION ALL SELECT
        (SELECT id FROM users WHERE email = 'docente@biblioteca.local' LIMIT 1),
        (SELECT id FROM library_branches ORDER BY id ASC LIMIT 1),
        '/docente/asignaciones',
        '10.10.0.30',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/132.0 Safari/537.36',
        '/login',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 21 DAY), '07:50:00')

    UNION ALL SELECT
        NULL,
        NULL,
        '/buscar?q=historia',
        '186.47.91.77',
        'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/131.0 Mobile Safari/537.36',
        '/',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 18 DAY), '16:10:00')

    UNION ALL SELECT
        (SELECT id FROM users WHERE email = 'bibliotecario@biblioteca.local' LIMIT 1),
        (SELECT id FROM library_branches ORDER BY id ASC LIMIT 1),
        '/admin',
        '10.10.0.40',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/135.0 Safari/537.36',
        '/login',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 14 DAY), '08:30:00')

    UNION ALL SELECT
        (SELECT id FROM users WHERE email = 'usuario@biblioteca.local' LIMIT 1),
        (SELECT id FROM library_branches ORDER BY id ASC LIMIT 1),
        '/mi-cuenta',
        '10.10.0.20',
        'Mozilla/5.0 (Android 14; Mobile) AppleWebKit/537.36 Chrome/133.0 Mobile Safari/537.36',
        '/login',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 12 DAY), '19:20:00')

    UNION ALL SELECT
        NULL,
        NULL,
        '/recursos/8',
        '190.152.44.90',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:136.0) Gecko/20100101 Firefox/136.0',
        '/buscar?q=novela',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 10 DAY), '10:05:00')

    UNION ALL SELECT
        (SELECT id FROM users WHERE email = 'admin@biblioteca.local' LIMIT 1),
        (SELECT id FROM library_branches ORDER BY id ASC LIMIT 1),
        '/admin/reports/visits',
        '10.10.0.10',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/135.0 Safari/537.36',
        '/admin',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 7 DAY), '17:45:00')

    UNION ALL SELECT
        (SELECT id FROM users WHERE email = 'docente@biblioteca.local' LIMIT 1),
        (SELECT id FROM library_branches ORDER BY id ASC LIMIT 1),
        '/docente/grupos',
        '10.10.0.30',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/132.0 Safari/537.36',
        '/docente',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 5 DAY), '12:35:00')

    UNION ALL SELECT
        NULL,
        NULL,
        '/',
        '200.63.11.25',
        'Mozilla/5.0 (iPad; CPU OS 18_3 like Mac OS X) AppleWebKit/605.1.15 Version/18.3 Mobile/15E148 Safari/604.1',
        'https://bing.com/',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 4 DAY), '15:30:00')

    UNION ALL SELECT
        (SELECT id FROM users WHERE email = 'usuario@biblioteca.local' LIMIT 1),
        (SELECT id FROM library_branches ORDER BY id ASC LIMIT 1),
        '/reservas',
        '10.10.0.20',
        'Mozilla/5.0 (Android 14; Mobile) AppleWebKit/537.36 Chrome/133.0 Mobile Safari/537.36',
        '/mi-cuenta',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '09:10:00')

    UNION ALL SELECT
        (SELECT id FROM users WHERE email = 'bibliotecario@biblioteca.local' LIMIT 1),
        (SELECT id FROM library_branches ORDER BY id ASC LIMIT 1),
        '/admin/loans',
        '10.10.0.40',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/135.0 Safari/537.36',
        '/admin',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '13:55:00')

    UNION ALL SELECT
        NULL,
        NULL,
        '/catalogo?tipo=digital',
        '181.113.70.201',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/134.0 Safari/537.36',
        '/',
        TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '18:05:00')
) AS seed
WHERE NOT EXISTS (
    SELECT 1
    FROM visits_log existing
    WHERE existing.created_at = seed.created_at
      AND existing.page = seed.page
      AND COALESCE(existing.user_id, 0) = COALESCE(seed.user_id, 0)
      AND COALESCE(existing.branch_id, 0) = COALESCE(seed.branch_id, 0)
      AND COALESCE(existing.ip_address, '') = COALESCE(seed.ip_address, '')
);
