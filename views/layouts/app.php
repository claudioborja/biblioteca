<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($meta_description ?? 'Sistema de Gestión Bibliotecaria', ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($title ?? 'Biblioteca', ENT_QUOTES, 'UTF-8') ?></title>
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
            /* Dual-font strategy: Manrope (display) + Inter (body) */
            --font-display: 'Manrope', ui-sans-serif, system-ui, sans-serif;
            --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;

            /* Primary: Deep intellectual blues */
            --color-primary: #0a2540;
            --color-primary-light: #1a3a5c;
            --color-primary-container: #163560;
            --color-primary-muted: #2a4a7f;
            --color-on-primary: #f0f4fa;

            /* Tertiary: Gold hallmark accent */
            --color-tertiary: #b8860b;
            --color-tertiary-light: #d4a843;
            --color-tertiary-fixed: #f0d68a;

            /* Surfaces: Tonal layering system */
            --color-surface: #f8f9fc;
            --color-surface-container-low: #f1f3f8;
            --color-surface-container: #e9ecf3;
            --color-surface-container-high: #dfe3ec;
            --color-surface-container-highest: #d5d9e3;
            --color-surface-container-lowest: #ffffff;

            /* Text: Never 100% black */
            --color-on-surface: #191c1d;
            --color-on-surface-muted: #565d6b;
            --color-on-surface-subtle: #8590a2;
            --color-outline-variant: #c4c9d4;

            /* Semantic accents */
            --color-success: #2d7a4f;
            --color-success-container: #e8f5ee;
        }

        /* Editorial typography scale */
        .display-lg { font-family: var(--font-display); font-size: clamp(2.5rem, 5vw, 3.75rem); font-weight: 800; line-height: 1.08; letter-spacing: -0.025em; color: var(--color-on-surface); }
        .display-md { font-family: var(--font-display); font-size: clamp(2rem, 4vw, 2.75rem); font-weight: 800; line-height: 1.12; letter-spacing: -0.02em; }
        .headline-lg { font-family: var(--font-display); font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 700; line-height: 1.2; letter-spacing: -0.015em; }
        .headline-md { font-family: var(--font-display); font-size: clamp(1.25rem, 2.5vw, 1.5rem); font-weight: 700; line-height: 1.25; }
        .title-sm { font-family: var(--font-display); font-size: 0.9375rem; font-weight: 600; line-height: 1.35; }
        .label-md { font-family: var(--font-sans); font-size: 0.8125rem; font-weight: 500; line-height: 1.4; color: var(--color-on-surface-muted); }
        .label-sm { font-family: var(--font-sans); font-size: 0.6875rem; font-weight: 600; line-height: 1.3; letter-spacing: 0.04em; text-transform: uppercase; color: var(--color-on-surface-subtle); }
        .body-md { font-family: var(--font-sans); font-size: 0.9375rem; line-height: 1.65; color: var(--color-on-surface-muted); }

        /* Ambient shadow — tinted with on-primary-fixed */
        .shadow-ambient { box-shadow: 0 12px 40px rgba(10, 37, 64, 0.06); }
        .shadow-ambient-lg { box-shadow: 0 20px 60px rgba(10, 37, 64, 0.08); }

        /* Ghost border — outline-variant at 15% opacity */
        .ghost-border { box-shadow: inset 0 0 0 1px rgba(196, 201, 212, 0.15); }

        /* Signature gradient */
        .gradient-scholar { background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-container) 100%); }

        /* Glass header */
        .glass-header { background: rgba(248, 249, 252, 0.80); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <?php include __DIR__ . '/../partials/flash-assets.php'; ?>
    <?php if (!empty($extra_css)): ?>
    <?= $extra_css ?>
    <?php endif; ?>
</head>
<body class="min-h-screen bg-surface text-on-surface flex flex-col font-sans antialiased">
    <!-- Skip to content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-surface-container-lowest focus:px-4 focus:py-2 focus:rounded-[0.375rem] focus:shadow-ambient focus:text-primary focus:font-medium">
        Saltar al contenido principal
    </a>

    <?php include __DIR__ . '/../partials/header.php'; ?>

    <div class="flex flex-1">
        <?php if (!empty($show_sidebar)): ?>
            <?php include __DIR__ . '/../partials/sidebar.php'; ?>
        <?php endif; ?>

        <main id="main-content" class="flex-1">
            <?php include __DIR__ . '/../partials/flash.php'; ?>
            <?= $content ?>
        </main>
    </div>

    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <?php include __DIR__ . '/../partials/tooltip-assets.php'; ?>

    <?php if (!empty($extra_js)): ?>
        <?= $extra_js ?>
    <?php endif; ?>
</body>
</html>
