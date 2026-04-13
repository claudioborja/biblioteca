<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($meta_description ?? 'Panel de usuario — Biblioteca', ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($title ?? 'Panel — Biblioteca', ENT_QUOTES, 'UTF-8') ?></title>
    <?php if (!empty($settings['library_favicon'])): ?>
        <link rel="icon" href="<?= htmlspecialchars($settings['library_favicon'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style type="text/tailwindcss">
        @theme {
            --font-display: 'Manrope', ui-sans-serif, system-ui, sans-serif;
            --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --color-primary: #0a2540;
            --color-primary-light: #1a3a5c;
            --color-primary-container: #163560;
            --color-primary-muted: #2a4a7f;
            --color-on-primary: #f0f4fa;
            --color-tertiary: #b8860b;
            --color-tertiary-light: #d4a843;
            --color-surface: #f8f9fc;
            --color-surface-container-low: #f1f3f8;
            --color-surface-container: #e9ecf3;
            --color-surface-container-high: #dfe3ec;
            --color-surface-container-highest: #d5d9e3;
            --color-surface-container-lowest: #ffffff;
            --color-on-surface: #191c1d;
            --color-on-surface-muted: #565d6b;
            --color-on-surface-subtle: #8590a2;
            --color-outline-variant: #c4c9d4;
            --color-success: #2d7a4f;
            --color-success-container: #e8f5ee;
            --color-error: #b91c1c;
            --color-error-container: #fef2f2;
        }

        .display-md  { font-family: var(--font-display); font-size: clamp(2rem, 4vw, 2.75rem); font-weight: 800; line-height: 1.12; letter-spacing: -0.02em; }
        .headline-lg { font-family: var(--font-display); font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 700; line-height: 1.2; letter-spacing: -0.015em; }
        .headline-md { font-family: var(--font-display); font-size: clamp(1.25rem, 2.5vw, 1.5rem); font-weight: 700; line-height: 1.25; }
        .title-sm    { font-family: var(--font-display); font-size: 0.9375rem; font-weight: 600; line-height: 1.35; }
        .label-md    { font-family: var(--font-sans); font-size: 0.8125rem; font-weight: 500; line-height: 1.4; color: var(--color-on-surface-muted); }
        .label-sm    { font-family: var(--font-sans); font-size: 0.6875rem; font-weight: 600; line-height: 1.3; letter-spacing: 0.04em; text-transform: uppercase; color: var(--color-on-surface-subtle); }
        .body-md     { font-family: var(--font-sans); font-size: 0.9375rem; line-height: 1.65; color: var(--color-on-surface-muted); }

        .shadow-ambient    { box-shadow: 0 12px 40px rgba(10, 37, 64, 0.06); }
        .shadow-ambient-lg { box-shadow: 0 20px 60px rgba(10, 37, 64, 0.08); }
        .ghost-border      { box-shadow: inset 0 0 0 1px rgba(196, 201, 212, 0.15); }
        .gradient-scholar  { background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-container) 100%); }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
    <?php if (!empty($extra_css)): ?>
        <?= $extra_css ?>
    <?php endif; ?>
</head>
<body class="min-h-screen bg-surface text-on-surface font-sans antialiased flex flex-col">

    <!-- Skip to content -->
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-white focus:px-4 focus:py-2 focus:rounded focus:shadow-ambient focus:text-primary focus:font-medium">
        Saltar al contenido principal
    </a>

    <?php include __DIR__ . '/../partials/panel-header.php'; ?>

    <div class="flex flex-1 min-h-[calc(100vh-3.75rem)]">
        <?php include __DIR__ . '/../partials/panel-sidebar.php'; ?>

        <div class="flex-1 min-w-0 flex flex-col">
            <main id="main-content" class="flex-1">
                <?php include __DIR__ . '/../partials/flash.php'; ?>
                <?= $content ?>
            </main>

            <footer class="shrink-0 border-t border-outline-variant/40 bg-white" role="contentinfo">
                <div class="min-h-[74px] flex items-center py-2">
                    <div class="px-4 sm:px-6 lg:px-8 w-full flex flex-col sm:flex-row items-center justify-center sm:justify-between gap-1 sm:gap-2 text-xs text-on-surface-subtle text-center sm:text-left min-w-0">
                        <p class="w-full sm:w-auto break-words min-w-0">&copy; <?= date('Y') ?> <?= htmlspecialchars($settings['library_name'] ?? 'Biblioteca', ENT_QUOTES, 'UTF-8') ?>. Todos los derechos reservados.</p>
                        <p class="w-full sm:w-auto break-words min-w-0 inline-flex items-center justify-center sm:justify-start gap-1.5">
                            <span>Sistema de Gestión Bibliotecaria desarrollado con</span>
                            <svg class="h-3.5 w-3.5 text-red-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10 17.583l-1.087-.992C4.39 12.465 1.5 9.819 1.5 6.583 1.5 3.937 3.573 2 6.125 2c1.443 0 2.827.69 3.875 1.779C11.048 2.69 12.432 2 13.875 2 16.427 2 18.5 3.937 18.5 6.583c0 3.236-2.89 5.882-7.413 10.008L10 17.583z"/>
                            </svg>
                            <span>por</span>
                            <a href="https://softecsa.com" target="_blank" rel="noopener noreferrer" class="text-current hover:underline">
                                SOFTECAPPS S.A.S.
                            </a>
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/tooltip-assets.php'; ?>

    <?php if (!empty($extra_js)): ?>
        <?= $extra_js ?>
    <?php endif; ?>
</body>
</html>
