<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Acceso — Biblioteca', ENT_QUOTES, 'UTF-8') ?></title>
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
        .gradient-scholar { background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-container) 100%); }
        .shadow-ambient { box-shadow: 0 12px 40px rgba(10, 37, 64, 0.06); }
        .shadow-ambient-lg { box-shadow: 0 20px 60px rgba(10, 37, 64, 0.08); }
        .headline-md { font-family: var(--font-display); font-size: clamp(1.25rem, 2.5vw, 1.5rem); font-weight: 700; line-height: 1.25; }
        .label-sm { font-family: var(--font-sans); font-size: 0.6875rem; font-weight: 600; line-height: 1.3; letter-spacing: 0.04em; text-transform: uppercase; }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(10, 37, 64, 0.1);
        }
    </style>
    <?php include __DIR__ . '/../partials/flash-assets.php'; ?>
</head>
<body class="min-h-screen bg-surface text-on-surface font-sans antialiased">

    <!-- Split layout: left brand panel + right form -->
    <div class="min-h-screen flex">

        <!-- Left: brand panel (hidden on mobile) -->
        <div class="hidden lg:flex lg:w-[42%] gradient-scholar flex-col justify-between p-12 relative overflow-hidden">
            <!-- Decorative circles -->
            <div class="absolute inset-0 opacity-[0.06] pointer-events-none">
                <svg class="absolute top-0 right-0 w-[420px] h-[420px] -mr-24 -mt-24 text-white" fill="none" viewBox="0 0 200 200" aria-hidden="true">
                    <circle cx="100" cy="100" r="80" stroke="currentColor" stroke-width="0.5"/>
                    <circle cx="100" cy="100" r="55" stroke="currentColor" stroke-width="0.5"/>
                    <circle cx="100" cy="100" r="30" stroke="currentColor" stroke-width="0.5"/>
                </svg>
                <svg class="absolute bottom-0 left-0 w-72 h-72 -ml-16 -mb-16 text-white" fill="none" viewBox="0 0 200 200" aria-hidden="true">
                    <path d="M20 140 Q80 60 140 120 Q180 160 190 80" stroke="currentColor" stroke-width="0.7" fill="none"/>
                    <path d="M30 100 Q60 20 100 80 Q140 140 170 60" stroke="currentColor" stroke-width="0.7" fill="none"/>
                </svg>
            </div>

            <!-- Brand -->
            <div class="relative">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-[0.625rem] bg-white/15 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                        </svg>
                    </div>
                    <span class="text-white font-display font-700 text-lg tracking-tight">Biblioteca</span>
                </div>

                <h2 class="text-white font-display font-extrabold text-3xl leading-tight mb-4">
                    El conocimiento<br>al alcance de todos.
                </h2>
                <p class="text-white/60 text-sm leading-relaxed max-w-xs">
                    Accede a miles de títulos, gestiona tus préstamos y descubre nuevas lecturas desde cualquier dispositivo.
                </p>
            </div>

            <!-- Stats strip -->
            <div class="relative grid grid-cols-3 gap-4">
                <div class="text-center">
                    <p class="text-white font-display font-bold text-xl">10.000+</p>
                    <p class="text-white/50 text-xs mt-0.5">Títulos</p>
                </div>
                <div class="text-center border-x border-white/10">
                    <p class="text-white font-display font-bold text-xl">5.000+</p>
                    <p class="text-white/50 text-xs mt-0.5">Socios</p>
                </div>
                <div class="text-center">
                    <p class="text-white font-display font-bold text-xl">98%</p>
                    <p class="text-white/50 text-xs mt-0.5">Satisfacción</p>
                </div>
            </div>
        </div>

        <!-- Right: form panel -->
        <div class="flex-1 flex flex-col justify-center items-center px-6 py-12 lg:px-16">

            <!-- Mobile logo -->
            <div class="lg:hidden mb-8 text-center">
                <div class="inline-flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-[0.5rem] gradient-scholar flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                        </svg>
                    </div>
                    <span class="font-display font-bold text-primary text-lg">Biblioteca</span>
                </div>
            </div>

            <div class="w-full max-w-sm">
                <?php include __DIR__ . '/../partials/flash.php'; ?>
                <?= $content ?>
            </div>
        </div>

    </div>

    <?php include __DIR__ . '/../partials/tooltip-assets.php'; ?>

</body>
</html>
