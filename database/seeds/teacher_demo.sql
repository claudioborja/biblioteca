-- teacher_demo.sql — Datos de demostración para módulo docente
-- Ejecutar después de todas las migraciones y seeds anteriores
-- Requiere: admin_user.sql (usuario id=1), categories.sql, al menos un libro

-- 1. Crear usuario docente de demostración
INSERT INTO users (
    name, email, document_number, password_hash, role, status,
    force_password_change, created_at
) VALUES (
    'María García López', 'docente@biblioteca.local', 'DOC-001',
    '$argon2id$v=19$m=65536,t=4,p=1$eDNsb05xcU5sSEE0RVlydg$EcbMwUq1cE4nTeYl725jRsd/hFKVULhoTdek+9wFWXM',
    'teacher', 'active', 1, NOW()
);

SET @teacher_id = LAST_INSERT_ID();

-- 2. Crear usuarios estudiante de demostración
INSERT INTO users (name, email, document_number, password_hash, role, status, force_password_change, created_at) VALUES
    ('Carlos Hernández Ruiz',   'estudiante1@biblioteca.local', 'EST-001', '$argon2id$v=19$m=65536,t=4,p=1$eDNsb05xcU5sSEE0RVlydg$EcbMwUq1cE4nTeYl725jRsd/hFKVULhoTdek+9wFWXM', 'user', 'active', 1, NOW()),
    ('Ana Martínez Soto',       'estudiante2@biblioteca.local', 'EST-002', '$argon2id$v=19$m=65536,t=4,p=1$eDNsb05xcU5sSEE0RVlydg$EcbMwUq1cE4nTeYl725jRsd/hFKVULhoTdek+9wFWXM', 'user', 'active', 1, NOW()),
    ('Luis Pérez Vega',         'estudiante3@biblioteca.local', 'EST-003', '$argon2id$v=19$m=65536,t=4,p=1$eDNsb05xcU5sSEE0RVlydg$EcbMwUq1cE4nTeYl725jRsd/hFKVULhoTdek+9wFWXM', 'user', 'active', 1, NOW());

SET @student1_id = @teacher_id + 1;
SET @student2_id = @teacher_id + 2;
SET @student3_id = @teacher_id + 3;

-- 3. Crear un libro de demostración (si no existe)
INSERT INTO books (
    isbn_13, title, authors, publisher, publication_year, pages,
    category_id, book_type, language, description,
    replacement_cost, total_copies, available_copies, created_at
) VALUES (
    '9780307474728',
    'Cien años de soledad',
    '["Gabriel García Márquez"]',
    'Editorial Sudamericana',
    1967,
    471,
    (SELECT id FROM categories WHERE slug = 'literatura' LIMIT 1),
    'physical', 'es',
    'Novela del escritor colombiano Gabriel García Márquez, ganador del Premio Nobel de Literatura.',
    25.00, 5, 5, NOW()
);

SET @book_id = LAST_INSERT_ID();

-- 4. Crear grupo docente
INSERT INTO teacher_groups (teacher_id, name, description, school_year, is_active, created_at) VALUES
    (@teacher_id, 'Literatura 3°A', 'Grupo de lectura de tercer año sección A', '2024-2025', 1, NOW());

SET @group_id = LAST_INSERT_ID();

-- 5. Asignar estudiantes al grupo
INSERT INTO teacher_group_students (group_id, student_id) VALUES
    (@group_id, @student1_id),
    (@group_id, @student2_id),
    (@group_id, @student3_id);

-- 6. Crear asignación de lectura
INSERT INTO reading_assignments (group_id, book_id, title, description, due_date, is_active, created_at) VALUES
    (@group_id, @book_id, 'Lectura: Cien años de soledad',
     'Leer los primeros 10 capítulos y preparar un resumen de los personajes principales.',
     DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, NOW());

SET @assignment_id = LAST_INSERT_ID();

-- 7. Registrar progreso de estudiantes en la asignación
INSERT INTO reading_assignment_students (assignment_id, student_id, status) VALUES
    (@assignment_id, @student1_id, 'in_progress'),
    (@assignment_id, @student2_id, 'pending'),
    (@assignment_id, @student3_id, 'pending');
