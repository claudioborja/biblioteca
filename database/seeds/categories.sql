-- categories.sql — Categorías iniciales del sistema de biblioteca
-- Ejecutar después de 001_create_categories.sql

INSERT INTO categories (name, slug, description) VALUES
    ('Literatura',              'literatura',               'Novelas, cuentos, poesía, teatro y obras literarias en general'),
    ('Ciencias Naturales',      'ciencias-naturales',       'Biología, química, física, astronomía y ciencias de la tierra'),
    ('Ciencias Sociales',       'ciencias-sociales',        'Sociología, antropología, ciencia política y economía'),
    ('Historia',                'historia',                 'Historia universal, nacional y regional'),
    ('Matemáticas',             'matematicas',              'Álgebra, geometría, cálculo, estadística y matemáticas aplicadas'),
    ('Tecnología',              'tecnologia',               'Informática, programación, ingeniería y tecnologías emergentes'),
    ('Arte y Cultura',          'arte-y-cultura',           'Pintura, escultura, música, cine, fotografía y artes escénicas'),
    ('Filosofía',               'filosofia',                'Filosofía clásica, moderna, contemporánea y ética'),
    ('Psicología',              'psicologia',               'Psicología clínica, social, educativa y del desarrollo'),
    ('Educación',               'educacion',                'Pedagogía, didáctica, formación docente y sistemas educativos'),
    ('Idiomas',                 'idiomas',                  'Gramática, diccionarios, aprendizaje de lenguas extranjeras'),
    ('Derecho',                 'derecho',                  'Legislación, derecho civil, penal, constitucional y laboral'),
    ('Economía y Finanzas',     'economia-y-finanzas',      'Microeconomía, macroeconomía, contabilidad y finanzas personales'),
    ('Salud y Medicina',        'salud-y-medicina',         'Medicina general, enfermería, nutrición y salud pública'),
    ('Deportes',                'deportes',                 'Educación física, deportes olímpicos y recreación'),
    ('Geografía',               'geografia',                'Geografía física, humana, atlas y cartografía'),
    ('Enciclopedias',           'enciclopedias',            'Obras de referencia general, enciclopedias y almanques'),
    ('Infantil y Juvenil',      'infantil-y-juvenil',       'Libros para niños y jóvenes, cuentos ilustrados y fábulas'),
    ('Biografías',              'biografias',               'Autobiografías, memorias y biografías de personajes notables'),
    ('Religión y Espiritualidad','religion-y-espiritualidad','Teología, estudios religiosos comparados y espiritualidad');
