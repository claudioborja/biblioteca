<?php
$_uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$_basePath = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/');
$_uriPath  = '/' . ltrim(substr($_uri, strlen($_basePath)), '/');

$sessionAuthUser = null;
if (empty($auth_user) && (int) \Core\Session::get('auth.user_id', 0) > 0) {
    $sessionAuthUser = [
        'id' => (int) \Core\Session::get('auth.user_id', 0),
        'name' => (string) \Core\Session::get('auth.name', ''),
        'email' => (string) \Core\Session::get('auth.email', ''),
        'role' => (string) \Core\Session::get('auth.role', ''),
    ];
}

$headerAuthUser = !empty($auth_user) ? $auth_user : $sessionAuthUser;

$_navActive = static function(string $segment) use ($_uriPath): string {
    $active = $segment === '/'
        ? $_uriPath === '/'
        : str_starts_with($_uriPath, $segment);
    return $active
        ? 'text-primary font-semibold after:absolute after:bottom-0 after:left-3 after:right-3 after:h-[3px] after:rounded-full after:bg-tertiary'
        : 'text-on-surface-muted hover:text-primary';
};
?>
<header class="glass-header sticky top-0 z-40" role="banner">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-6 h-16 lg:h-[4.25rem]">

            <!-- Logo / Brand -->
            <a href="<?= BASE_URL ?>/" class="flex items-center gap-2.5 shrink-0 group" aria-label="Ir al inicio">
                <?php if (!empty($settings['library_logo'])): ?>
                    <img src="<?= htmlspecialchars($settings['library_logo'], ENT_QUOTES, 'UTF-8') ?>"
                         alt="" class="h-8 w-auto" aria-hidden="true">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-[0.375rem] gradient-scholar flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-on-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                <?php endif; ?>
                <span class="font-display text-[0.9375rem] font-bold text-primary group-hover:text-primary-muted transition-colors duration-200 hidden sm:block leading-none">
                    <?= htmlspecialchars($settings['library_name'] ?? 'Biblioteca', ENT_QUOTES, 'UTF-8') ?>
                </span>
            </a>

            <!-- Nav principal (desktop) -->
            <nav class="hidden md:flex items-stretch h-full gap-0.5" aria-label="Navegación principal">
                <a href="<?= BASE_URL ?>/"
                   class="relative flex items-center gap-1.5 px-4 text-sm transition-colors duration-200 <?= $_navActive('/') ?>">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    Inicio
                </a>
                <a href="<?= BASE_URL ?>/catalog"
                   class="relative flex items-center gap-1.5 px-4 text-sm transition-colors duration-200 <?= $_navActive('/catalog') ?>">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                    Catálogo
                </a>
                <a href="<?= BASE_URL ?>/new-arrivals"
                   class="relative flex items-center gap-1.5 px-4 text-sm transition-colors duration-200 <?= $_navActive('/new-arrivals') ?>">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                    Novedades
                </a>
                <a href="<?= BASE_URL ?>/news"
                   class="relative flex items-center gap-1.5 px-4 text-sm transition-colors duration-200 <?= $_navActive('/news') ?>">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5"/></svg>
                    Noticias
                </a>
                <a href="<?= BASE_URL ?>/about"
                   class="relative flex items-center gap-1.5 px-4 text-sm transition-colors duration-200 <?= $_navActive('/about') ?>">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>
                    Nosotros
                </a>
            </nav>

            <!-- Right: search + auth + mobile toggle -->
            <div class="flex items-center gap-2 ml-auto">
                <!-- Inline search (desktop) -->
                <form action="<?= BASE_URL ?>/search" method="GET"
                      class="hidden md:flex items-center bg-surface-container rounded-[0.375rem] px-3 gap-2 h-8 w-44 lg:w-56"
                      role="search">
                    <svg class="w-3.5 h-3.5 text-on-surface-subtle shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input type="text" name="q"
                           placeholder="Buscar…"
                           class="flex-1 bg-transparent text-sm text-on-surface placeholder-on-surface-subtle focus:outline-none min-w-0"
                           autocomplete="off"
                           aria-label="Buscar en el catálogo">
                </form>
                <!-- Search icon — solo móvil -->
                <a href="<?= BASE_URL ?>/search"
                   class="md:hidden p-2 text-on-surface-subtle hover:text-primary rounded-[0.375rem] transition-colors duration-200"
                   aria-label="Buscar">
                    <svg class="w-[1.1rem] h-[1.1rem]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                </a>

                <?php if (!empty($headerAuthUser)): ?>
                    <div class="hidden sm:flex items-center gap-1.5 ml-1">
                        <a href="<?= BASE_URL ?>/account"
                           class="flex items-center gap-2 px-2.5 py-1.5 text-sm text-on-surface-muted hover:text-primary rounded-[0.375rem] transition-colors duration-200">
                            <div class="w-7 h-7 rounded-full gradient-scholar text-on-primary flex items-center justify-center text-xs font-bold font-display">
                                <?= htmlspecialchars(mb_strtoupper(mb_substr($headerAuthUser['name'] ?? '?', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <span class="font-medium text-sm hidden xl:block"><?= htmlspecialchars($headerAuthUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                        <?php if (in_array($headerAuthUser['role'] ?? '', ['admin', 'librarian'], true)): ?>
                            <a href="<?= BASE_URL ?>/admin"
                               class="px-3 py-1.5 text-xs font-semibold text-primary bg-primary/8 hover:bg-primary/14 rounded-[0.375rem] transition-colors duration-200">
                                Panel
                            </a>
                        <?php elseif (($headerAuthUser['role'] ?? '') === 'teacher'): ?>
                            <a href="<?= BASE_URL ?>/teacher"
                               class="px-3 py-1.5 text-xs font-semibold text-primary bg-primary/8 hover:bg-primary/14 rounded-[0.375rem] transition-colors duration-200">
                                Docente
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login"
                       class="hidden sm:inline-flex items-center gap-1.5 ml-1 px-4 py-2 text-sm font-semibold text-on-primary gradient-scholar rounded-[0.375rem] transition-opacity duration-200 hover:opacity-90">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
                        </svg>
                        Ingresar
                    </a>
                <?php endif; ?>

                <!-- Mobile menu toggle -->
                <button type="button"
                        id="mobile-menu-btn"
                        class="md:hidden p-2 ml-1 text-on-surface-subtle hover:text-primary rounded-[0.375rem] transition-colors duration-200"
                        aria-expanded="false"
                        aria-controls="mobile-menu"
                        aria-label="Abrir menú">
                    <svg id="icon-menu" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                    <svg id="icon-close" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <nav id="mobile-menu" class="hidden md:hidden py-3" aria-label="Navegación móvil">
            <!-- Inline search mobile -->
            <form action="<?= BASE_URL ?>/search" method="GET"
                  class="flex items-center bg-surface-container rounded-[0.375rem] px-3 gap-2 h-9 mb-3"
                  role="search">
                <svg class="w-3.5 h-3.5 text-on-surface-subtle shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input type="text" name="q" placeholder="Buscar…"
                       class="flex-1 bg-transparent text-sm text-on-surface placeholder-on-surface-subtle focus:outline-none"
                       autocomplete="off" aria-label="Buscar">
            </form>
            <div class="space-y-0.5">
                <a href="<?= BASE_URL ?>/"          class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-on-surface-muted hover:text-primary hover:bg-surface-container rounded-[0.375rem] transition-colors duration-200">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    Inicio
                </a>
                <a href="<?= BASE_URL ?>/catalog"     class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-on-surface-muted hover:text-primary hover:bg-surface-container rounded-[0.375rem] transition-colors duration-200">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                    Catálogo
                </a>
                <a href="<?= BASE_URL ?>/new-arrivals" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-on-surface-muted hover:text-primary hover:bg-surface-container rounded-[0.375rem] transition-colors duration-200">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                    Novedades
                </a>
                <a href="<?= BASE_URL ?>/news"     class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-on-surface-muted hover:text-primary hover:bg-surface-container rounded-[0.375rem] transition-colors duration-200">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5"/></svg>
                    Noticias
                </a>
                <a href="<?= BASE_URL ?>/about"     class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-on-surface-muted hover:text-primary hover:bg-surface-container rounded-[0.375rem] transition-colors duration-200">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>
                    Nosotros
                </a>
            </div>
            <?php if (empty($headerAuthUser)): ?>
                <div class="mt-3 pt-3">
                    <a href="<?= BASE_URL ?>/login"
                       class="flex items-center justify-center gap-2 w-full px-4 py-2.5 text-sm font-semibold text-on-primary gradient-scholar rounded-[0.375rem] transition-opacity duration-200 hover:opacity-90">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/></svg>
                        Iniciar sesión
                    </a>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>
<script>
(function(){
    var btn = document.getElementById('mobile-menu-btn');
    var menu = document.getElementById('mobile-menu');
    var iconMenu = document.getElementById('icon-menu');
    var iconClose = document.getElementById('icon-close');
    if (!btn) return;
    btn.addEventListener('click', function(){
        var open = menu.classList.toggle('hidden');
        btn.setAttribute('aria-expanded', String(!open));
        iconMenu.classList.toggle('hidden', !open);
        iconClose.classList.toggle('hidden', open);
    });
})();
</script>
