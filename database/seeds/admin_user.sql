-- admin_user.sql — Usuario administrador inicial
-- Ejecutar después de 003_create_users.sql
-- Contraseña por defecto: Admin2024 (debe cambiarse en el primer acceso)

INSERT INTO users (
    name,
    email,
    document_number,
    password_hash,
    role,
    status,
    force_password_change,
    created_at
) VALUES (
    'Administrador del Sistema',
    'admin@biblioteca.local',
    'ADMIN-001',
    '$argon2id$v=19$m=65536,t=4,p=1$eDNsb05xcU5sSEE0RVlydg$EcbMwUq1cE4nTeYl725jRsd/hFKVULhoTdek+9wFWXM',
    'admin',
    'active',
    1,
    NOW()
);
