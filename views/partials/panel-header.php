<?php
// views/partials/panel-header.php
$_e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$homeHref = match($auth_user['role'] ?? '') {
    'admin', 'librarian' => BASE_URL . '/admin',
    'teacher' => BASE_URL . '/teacher',
    default => BASE_URL . '/account',
};

$panelLabel = match($auth_user['role'] ?? '') {
    'admin' => 'Panel de administración',
    'librarian' => 'Panel de bibliotecario',
    'teacher' => 'Panel docente',
    'user' => 'Panel de socio',
    default => 'Panel de usuario',
};
?>
<header class="h-[3.75rem] bg-primary sticky top-0 z-40 flex items-center shadow-sm" role="banner">
    <div class="flex items-center w-full px-4 sm:px-6 gap-3">

        <!-- Logo / brand -->
        <a href="<?= $_e($homeHref) ?>"
           class="flex items-center gap-2 shrink-0 group"
           aria-label="Ir al panel">
            <?php if (!empty($settings['library_logo'])): ?>
                <img src="<?= $_e($settings['library_logo']) ?>" alt="" class="h-7 w-auto brightness-200" aria-hidden="true">
            <?php else: ?>
                <div class="w-7 h-7 rounded-[0.375rem] bg-white/10 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
            <?php endif; ?>
            <span class="hidden sm:block font-display text-[0.875rem] font-bold text-white/90 group-hover:text-white transition-colors leading-none">
                <?= $_e($settings['library_name'] ?? 'Biblioteca') ?>
            </span>
        </a>

        <!-- Divider -->
        <div class="hidden sm:block h-5 w-px bg-white/20"></div>

        <!-- Section label -->
        <span class="hidden sm:block text-xs font-semibold text-white/40 uppercase tracking-wider"><?= $_e($panelLabel) ?></span>

        <!-- Spacer -->
        <div class="flex-1"></div>

        <!-- Link to public site -->
        <a href="<?= BASE_URL ?>/"
           class="hidden md:inline-flex items-center gap-1.5 text-xs font-medium text-white/60 hover:text-white transition-colors duration-200 mr-1"
           title="Ver sitio público">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
            </svg>
            Sitio público
        </a>

    </div>
</header>
