<aside class="hidden lg:block w-64 bg-white border-r min-h-screen">
    <nav class="p-4 space-y-1 text-sm">
        <?php
        $role = $auth_user['role'] ?? '';
        $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
        $isActive = fn(string $prefix) => str_starts_with($currentPath, $prefix) ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-50';
        ?>

        <?php if (in_array($role, ['admin', 'librarian'])): ?>
            <p class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Administración</p>
            <a href="<?= BASE_URL ?>/admin" class="block px-3 py-2 rounded <?= $isActive('/admin') ?>">Dashboard</a>
            <a href="<?= BASE_URL ?>/admin/resources" class="block px-3 py-2 rounded <?= $isActive("/admin/resources") ?>">Recursos</a>
            <a href="<?= BASE_URL ?>/admin/loans" class="block px-3 py-2 rounded <?= $isActive('/admin/loans') ?>">Préstamos</a>
            <a href="<?= BASE_URL ?>/admin/reservations" class="block px-3 py-2 rounded <?= $isActive('/admin/reservations') ?>">Reservaciones</a>
            <a href="<?= BASE_URL ?>/admin/fines" class="block px-3 py-2 rounded <?= $isActive('/admin/fines') ?>">Multas</a>
            <a href="<?= BASE_URL ?>/admin/users" class="block px-3 py-2 rounded <?= $isActive('/admin/users') ?>">Usuarios</a>
            <a href="<?= BASE_URL ?>/admin/categories" class="block px-3 py-2 rounded <?= $isActive('/admin/categories') ?>">Categorías</a>
            <a href="<?= BASE_URL ?>/admin/branches" class="block px-3 py-2 rounded <?= $isActive('/admin/branches') ?>">Sucursales</a>
            <a href="<?= BASE_URL ?>/admin/news" class="block px-3 py-2 rounded <?= $isActive('/admin/news') ?>">Noticias</a>
            <a href="<?= BASE_URL ?>/admin/suggestions" class="block px-3 py-2 rounded <?= $isActive('/admin/suggestions') ?>">Sugerencias</a>
            <a href="<?= BASE_URL ?>/admin/reports/loans" class="block px-3 py-2 rounded <?= $isActive('/admin/reports') ?>">Reportes</a>
            <a href="<?= BASE_URL ?>/admin/labels" class="block px-3 py-2 rounded <?= $isActive('/admin/labels') ?>">Códigos de barra</a>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <p class="px-3 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Sistema</p>
            <p class="px-3 py-1 text-[11px] font-semibold text-gray-400">Configuración</p>
            <a href="<?= BASE_URL ?>/admin/settings/library" class="block px-3 py-2 rounded <?= $isActive('/admin/settings/library') ?>">Biblioteca</a>
            <a href="<?= BASE_URL ?>/admin/settings/loans" class="block px-3 py-2 rounded <?= $isActive('/admin/settings/loans') ?>">Préstamos</a>
            <a href="<?= BASE_URL ?>/admin/settings/fines" class="block px-3 py-2 rounded <?= $isActive('/admin/settings/fines') ?>">Multas</a>
            <a href="<?= BASE_URL ?>/admin/settings/notifications" class="block px-3 py-2 rounded <?= $isActive('/admin/settings/notifications') ?>">Comunicación</a>
            <a href="<?= BASE_URL ?>/admin/settings/smtp" class="block px-3 py-2 rounded <?= $isActive('/admin/settings/smtp') ?>">Correo SMTP</a>
            <a href="<?= BASE_URL ?>/admin/settings/about" class="block px-3 py-2 rounded <?= $isActive('/admin/settings/about') ?>">Página About</a>
            <a href="<?= BASE_URL ?>/admin/settings/system" class="block px-3 py-2 rounded <?= $isActive('/admin/settings/system') ?>">Sistema</a>
            <a href="<?= BASE_URL ?>/admin/settings/mail-queue" class="block px-3 py-2 rounded <?= $isActive('/admin/settings/mail-queue') ?>">Cola de correo</a>
            <a href="<?= BASE_URL ?>/admin/audit" class="block px-3 py-2 rounded <?= $isActive('/admin/audit') ?>">Auditoría</a>
        <?php endif; ?>

        <?php if ($role === 'teacher'): ?>
            <p class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Docente</p>
            <a href="<?= BASE_URL ?>/teacher" class="block px-3 py-2 rounded <?= $isActive('/teacher') ?>">Mis Grupos</a>
            <a href="<?= BASE_URL ?>/teacher/assignments" class="block px-3 py-2 rounded <?= $isActive('/teacher/assignments') ?>">Asignaciones</a>
            <a href="<?= BASE_URL ?>/teacher/suggestions" class="block px-3 py-2 rounded <?= $isActive('/teacher/suggestions') ?>">Sugerencias</a>
        <?php endif; ?>

        <p class="px-3 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Mi Zona</p>
        <a href="<?= BASE_URL ?>/account" class="block px-3 py-2 rounded <?= $isActive('/account') ?>">Mi Perfil</a>
        <a href="<?= BASE_URL ?>/account/loans" class="block px-3 py-2 rounded <?= $isActive('/account/loans') ?>">Mis Préstamos</a>
        <a href="<?= BASE_URL ?>/account/reservations" class="block px-3 py-2 rounded <?= $isActive('/account/reservations') ?>">Mis Reservaciones</a>
        <a href="<?= BASE_URL ?>/account/fines" class="block px-3 py-2 rounded <?= $isActive('/account/fines') ?>">Mis Multas</a>
    </nav>
</aside>
