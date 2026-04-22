<?php
// views/partials/panel-sidebar.php
$_e      = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$role    = $auth_user['role'] ?? '';
$_uri    = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$_query  = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '';
$_base   = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/');
$_path   = '/' . ltrim(substr($_uri, strlen($_base)), '/');
$queryParams = [];
if ($_query !== '') {
    parse_str($_query, $queryParams);
}
$auditTab = (string) ($queryParams['tab'] ?? 'general');
$_reportSub = basename(rtrim($_path, '/'));
$_reportMap = ['reports' => 'Resumen', 'loans' => 'Préstamos', 'inventory' => 'Inventario', 'users' => 'Usuarios', 'fines' => 'Multas', 'visits' => 'Visitas'];
$active  = fn(string $p) => str_starts_with($_path, $p)
    ? 'bg-primary/8 text-primary font-semibold'
    : 'text-on-surface-muted hover:bg-surface-container hover:text-on-surface';
$activeExact = fn(string $p) => $_path === $p
    ? 'bg-primary/8 text-primary font-semibold'
    : 'text-on-surface-muted hover:bg-surface-container hover:text-on-surface';
$roleBadge = match($role) {
    'admin'     => ['Administrador del sistema', 'bg-red-100 text-red-700 border-red-200'],
    'librarian' => ['Bibliotecario', 'bg-violet-100 text-violet-700 border-violet-200'],
    'teacher'   => ['Docente', 'bg-blue-100 text-blue-700 border-blue-200'],
    'user'    => ['Socio', 'bg-emerald-100 text-emerald-700 border-emerald-200'],
    default     => ['Usuario', 'bg-slate-100 text-slate-700 border-slate-200'],
};
$initials = mb_strtoupper(mb_substr($auth_user['name'] ?? '?', 0, 2));
$isAccountSectionActive = str_starts_with($_path, '/account');
?>
<aside class="hidden lg:flex flex-col w-56 bg-white border-r border-outline-variant/50 shrink-0" aria-label="Navegación del panel">
    <nav class="flex-1 overflow-y-auto p-3 space-y-0.5 text-[0.8125rem]">

        <?php if (in_array($role, ['user', 'teacher'], true)): ?>
            <p class="px-2 pt-3 pb-1 label-sm">Mi Cuenta</p>
            <a href="<?= BASE_URL ?>/account"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $activeExact('/account') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Resumen
            </a>
            <a href="<?= BASE_URL ?>/account/loans"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/account/loans') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Préstamos
            </a>
            <a href="<?= BASE_URL ?>/account/reservations"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/account/reservations') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Reservaciones
            </a>
            <a href="<?= BASE_URL ?>/account/fines"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/account/fines') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Multas
            </a>
            <?php if ($role === 'user'): ?>
            <a href="<?= BASE_URL ?>/account/assignments"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/account/assignments') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                Asignaciones
            </a>
            <a href="<?= BASE_URL ?>/account/suggestions"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/account/suggestions') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                Sugerencias
            </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'librarian'])): ?>
            <p class="px-2 pt-3 pb-1 label-sm">Administración</p>
            <a href="<?= BASE_URL ?>/admin"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $activeExact('/admin') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                Dashboard
            </a>
            <details class="group rounded-[0.5rem]" <?= str_starts_with($_path, '/admin/resources') ? 'open' : '' ?>>
                <summary class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] cursor-pointer list-none transition-colors duration-150 select-none <?= str_starts_with($_path, '/admin/resources') ? 'bg-primary/8 text-primary font-semibold' : 'text-on-surface-muted hover:bg-surface-container hover:text-on-surface' ?>">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span class="flex-1">Recursos</span>
                    <svg class="w-3 h-3 shrink-0 transition-transform duration-200 group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="ml-6 mb-2 mt-0.5 space-y-0.5 border-l border-outline-variant/60 pl-2 text-[12px]">
                    <?php
                    $_resLinks = [
                        ['Todos',                '/admin/resources',                    false],
                        ['Libros físicos',       '/admin/resources/type/libros',        true],
                        ['Libros digitales',     '/admin/resources/type/digitales',     true],
                        ['Revistas / Artículos', '/admin/resources/type/revistas',      true],
                        ['Tesis',                '/admin/resources/type/tesis',         true],
                        ['Otros',                '/admin/resources/type/otros',         true],
                    ];
                    foreach ($_resLinks as [$_rLabel, $_rPath, $_isType]):
                        $_rActive = $_isType
                            ? str_starts_with($_path, $_rPath)
                            : ($_path === '/admin/resources');
                    ?>
                    <a href="<?= BASE_URL . $_e($_rPath) ?>"
                       class="block rounded-md px-2 py-1.5 transition-colors duration-150 <?= $_rActive ? 'bg-white text-primary font-semibold' : 'text-on-surface-muted hover:bg-white hover:text-on-surface' ?>">
                        <?= $_e($_rLabel) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </details>
            <a href="<?= BASE_URL ?>/admin/loans"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/admin/loans') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Préstamos
            </a>
            <a href="<?= BASE_URL ?>/admin/reservations"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/admin/reservations') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Reservaciones
            </a>
            <a href="<?= BASE_URL ?>/admin/fines"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/admin/fines') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Multas
            </a>
            <a href="<?= BASE_URL ?>/admin/users"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/admin/users') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                Usuarios
            </a>
            <a href="<?= BASE_URL ?>/admin/categories"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/admin/categories') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                Categorías
            </a>
            <a href="<?= BASE_URL ?>/admin/branches"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/admin/branches') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>
                Sedes
            </a>
            <a href="<?= BASE_URL ?>/admin/news"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/admin/news') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5"/></svg>
                Noticias
            </a>
            <a href="<?= BASE_URL ?>/admin/suggestions"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/admin/suggestions') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/></svg>
                Sugerencias
            </a>
            <a href="<?= BASE_URL ?>/admin/labels"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/admin/labels') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                Etiquetas
            </a>
            <details class="group rounded-[0.5rem]" <?= str_starts_with($_path, '/admin/reports') ? 'open' : '' ?>>
                <summary class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] cursor-pointer list-none transition-colors duration-150 select-none <?= str_starts_with($_path, '/admin/reports') ? 'bg-primary/8 text-primary font-semibold' : 'text-on-surface-muted hover:bg-surface-container hover:text-on-surface' ?>">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                    <span class="flex-1">Reportes</span>
                    <svg class="w-3 h-3 shrink-0 transition-transform duration-200 group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="ml-6 mb-2 mt-0.5 space-y-0.5 border-l border-outline-variant/60 pl-2 text-[12px]">
                    <?php
                    $_rLinks = [
                        'reports'   => ['Resumen',    '/admin/reports'],
                        'loans'     => ['Préstamos',  '/admin/reports/loans'],
                        'inventory' => ['Inventario', '/admin/reports/inventory'],
                        'users'     => ['Usuarios',   '/admin/reports/users'],
                        'fines'     => ['Multas',     '/admin/reports/fines'],
                        'visits'    => ['Visitas',    '/admin/reports/visits'],
                    ];
                    foreach ($_rLinks as $rKey => [$rLabel, $rPath]):
                        $_rActive = ($rKey === 'reports') ? ($_path === '/admin/reports') : ($_path === $rPath);
                    ?>
                    <a href="<?= BASE_URL . $_e($rPath) ?>"
                       class="block rounded-md px-2 py-1.5 transition-colors duration-150 <?= $_rActive ? 'bg-white text-primary font-semibold' : 'text-on-surface-muted hover:bg-white hover:text-on-surface' ?>">
                        <?= $_e($rLabel) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <p class="px-2 pt-4 pb-1 label-sm">Sistema</p>
            <a href="<?= BASE_URL ?>/admin/settings"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/admin/settings') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0H12m-8.457 3.077l1.41-.513m14.095-5.13l1.41-.513M5.106 17.785l1.15-.964m11.49-9.642l1.149-.964M7.501 19.795l.75-1.3m7.5-12.99l.75-1.3m-6.063 16.658l.26-1.477m2.605-14.772l.26-1.477m0 17.726l-.26-1.477M10.698 4.614l-.26-1.477M16.5 19.794l-.75-1.299M7.5 4.205L12 12m6.894 5.785l-1.149-.964M6.256 7.178l-1.15-.964m15.352 8.864l-1.41-.513M4.954 9.435l-1.41-.514M12.002 12l-3.75 6.495"/></svg>
                Configuración
            </a>
            <a href="<?= BASE_URL ?>/admin/settings/mail-queue"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/admin/settings/mail-queue') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                Cola de correo
            </a>

            <details class="group rounded-[0.5rem]" <?= str_starts_with($_path, '/admin/audit') ? 'open' : '' ?>>
                <summary class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] cursor-pointer list-none transition-colors duration-150 select-none <?= str_starts_with($_path, '/admin/audit') ? 'bg-primary/8 text-primary font-semibold' : 'text-on-surface-muted hover:bg-surface-container hover:text-on-surface' ?>">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    <span class="flex-1">Auditoría</span>
                    <svg class="w-3 h-3 shrink-0 transition-transform duration-200 group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="ml-6 mb-2 mt-0.5 space-y-0.5 border-l border-outline-variant/60 pl-2 text-[12px]">
                    <a href="<?= BASE_URL ?>/admin/audit?tab=general"
                       class="block rounded-md px-2 py-1.5 transition-colors duration-150 <?= str_starts_with($_path, '/admin/audit') && $auditTab === 'general' ? 'bg-white text-primary font-semibold' : 'text-on-surface-muted hover:bg-white hover:text-on-surface' ?>">
                        General
                    </a>
                    <a href="<?= BASE_URL ?>/admin/audit?tab=correos"
                       class="block rounded-md px-2 py-1.5 transition-colors duration-150 <?= str_starts_with($_path, '/admin/audit') && $auditTab === 'correos' ? 'bg-white text-primary font-semibold' : 'text-on-surface-muted hover:bg-white hover:text-on-surface' ?>">
                        Correos
                    </a>
                    <a href="<?= BASE_URL ?>/admin/audit?tab=seguridad"
                       class="block rounded-md px-2 py-1.5 transition-colors duration-150 <?= str_starts_with($_path, '/admin/audit') && $auditTab === 'seguridad' ? 'bg-white text-primary font-semibold' : 'text-on-surface-muted hover:bg-white hover:text-on-surface' ?>">
                        Seguridad
                    </a>
                    <a href="<?= BASE_URL ?>/admin/audit?tab=sistema"
                       class="block rounded-md px-2 py-1.5 transition-colors duration-150 <?= str_starts_with($_path, '/admin/audit') && $auditTab === 'sistema' ? 'bg-white text-primary font-semibold' : 'text-on-surface-muted hover:bg-white hover:text-on-surface' ?>">
                        Sistema
                    </a>
                </div>
            </details>
        <?php endif; ?>

        <?php if ($role === 'teacher'): ?>
            <p class="px-2 pt-3 pb-1 label-sm">Docente</p>
            <a href="<?= BASE_URL ?>/teacher"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $activeExact('/teacher') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                Inicio
            </a>
            <a href="<?= BASE_URL ?>/teacher/groups"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/teacher/groups') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
                Mis grupos
            </a>
            <a href="<?= BASE_URL ?>/teacher/assignments"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/teacher/assignments') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                Asignaciones
            </a>
            <a href="<?= BASE_URL ?>/teacher/suggestions"
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-[0.375rem] transition-colors duration-150 <?= $active('/teacher/suggestions') ?>">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/></svg>
                Sugerencias
            </a>
        <?php endif; ?>

    </nav>

    <div class="min-h-[74px] border-t border-outline-variant/40 bg-white px-4 py-2 flex items-center">
        <details class="group relative w-full" onmouseenter="if (this._closeTimer) { clearTimeout(this._closeTimer); this._closeTimer = null; }" onmouseleave="this._closeTimer = setTimeout(() => this.removeAttribute('open'), 320)">
            <summary class="list-none flex items-center gap-3 rounded-lg bg-surface-container-low px-2 py-2 cursor-pointer transition-colors duration-150 hover:bg-white text-on-surface">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-bold text-primary">
                    <?= $_e($initials) ?>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold"><?= $_e($auth_user['name'] ?? '') ?></p>
                    <p class="truncate text-[11px] text-on-surface-subtle"><?= $_e($roleBadge[0]) ?></p>
                </div>
                <svg class="w-4 h-4 text-on-surface-subtle transition-transform duration-200 group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>

            <div class="absolute left-0 right-0 bottom-full mb-2 z-50 rounded-lg border border-outline-variant/50 bg-white p-2 shadow-ambient-lg space-y-0.5">
                <?php if ($role === 'admin'): ?>
                    <a href="<?= BASE_URL ?>/account"
                       class="flex w-full items-center gap-2 px-2 py-1.5 rounded-md text-xs text-on-surface-muted hover:bg-white hover:text-on-surface transition-colors duration-150">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Mi cuenta</span>
                    </a>
                    <a href="<?= BASE_URL ?>/account/loans"
                       class="flex w-full items-center gap-2 px-2 py-1.5 rounded-md text-xs text-on-surface-muted hover:bg-white hover:text-on-surface transition-colors duration-150">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span>Mis préstamos</span>
                    </a>
                    <a href="<?= BASE_URL ?>/account/reservations"
                       class="flex w-full items-center gap-2 px-2 py-1.5 rounded-md text-xs text-on-surface-muted hover:bg-white hover:text-on-surface transition-colors duration-150">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Mis reservaciones</span>
                    </a>
                    <a href="<?= BASE_URL ?>/account/fines"
                       class="flex w-full items-center gap-2 px-2 py-1.5 rounded-md text-xs text-on-surface-muted hover:bg-white hover:text-on-surface transition-colors duration-150">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Mis multas</span>
                    </a>
                    <a href="<?= BASE_URL ?>/account/profile"
                       class="flex w-full items-center gap-2 px-2 py-1.5 rounded-md text-xs text-on-surface-muted hover:bg-white hover:text-on-surface transition-colors duration-150">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        <span>Mi perfil</span>
                    </a>
                    <div class="my-1 h-px bg-outline-variant/40"></div>
                <?php endif; ?>
                <form method="POST" action="<?= BASE_URL ?>/logout">
                    <input type="hidden" name="_csrf_token" value="<?= $_e(\Core\Session::get('_csrf_token', '')) ?>">
                    <button type="submit"
                            class="flex w-full items-center gap-2 px-2 py-1.5 rounded-md text-xs text-on-surface-muted hover:bg-white hover:text-on-surface transition-colors duration-150">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </details>
    </div>
</aside>
<script>
(() => {
    const sidebarNav = document.querySelector('aside[aria-label="Navegación del panel"] nav');
    if (!sidebarNav) return;

    const groups = Array.from(sidebarNav.querySelectorAll('details'));
    if (groups.length < 2) return;

    groups.forEach((group) => {
        group.addEventListener('toggle', () => {
            if (!group.open) return;
            groups.forEach((other) => {
                if (other !== group) other.open = false;
            });
        });
    });
})();
</script>
