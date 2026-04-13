<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($meta_description ?? 'Editor modal — Biblioteca', ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($title ?? 'Editor — Biblioteca', ENT_QUOTES, 'UTF-8') ?></title>
    <?php if (!empty($settings['library_favicon'])): ?>
        <link rel="icon" href="<?= htmlspecialchars($settings['library_favicon'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
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
        .gradient-scholar  { background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-container) 100%); }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
    <?php if (!empty($extra_css)): ?>
        <?= $extra_css ?>
    <?php endif; ?>
</head>
<body class="min-h-screen bg-surface text-on-surface font-sans antialiased">
    <?php include __DIR__ . '/../partials/flash.php'; ?>
    <?= $content ?>

    <?php include __DIR__ . '/../partials/tooltip-assets.php'; ?>

    <?php if (!empty($extra_js)): ?>
        <?= $extra_js ?>
    <?php endif; ?>
</body>
</html>
