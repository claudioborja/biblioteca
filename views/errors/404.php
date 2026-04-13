<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Página No Encontrada</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style type="text/tailwindcss">
        @theme {
            --font-display: 'Manrope', ui-sans-serif, system-ui, sans-serif;
            --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --color-primary: #0a2540;
            --color-primary-muted: #1a3a5c;
            --color-on-primary: #ffffff;
            --color-surface: #f8f9fc;
            --color-on-surface: #191c1d;
            --color-on-surface-muted: #565d6b;
            --color-on-surface-subtle: #8590a2;
        }
    </style>
</head>
<body class="min-h-screen bg-surface flex items-center justify-center font-sans antialiased">
    <div class="text-center px-6">
        <p class="text-[10rem] font-extrabold font-display text-primary/[0.07] leading-none select-none">404</p>
        <h1 class="mt-[-2rem] text-2xl font-bold font-display text-on-surface">Página no encontrada</h1>
        <p class="mt-3 text-on-surface-muted max-w-sm mx-auto leading-relaxed">Lo sentimos, la página que buscas no existe o ha sido movida.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/"
               class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-on-primary rounded-[0.375rem] hover:bg-primary-muted text-sm font-semibold transition-colors duration-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                Volver al inicio
            </a>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/search"
               class="inline-flex items-center gap-2 px-6 py-2.5 bg-transparent text-on-surface-muted rounded-[0.375rem] hover:bg-primary/5 text-sm font-semibold transition-colors duration-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                Buscar en el catálogo
            </a>
        </div>
    </div>
</body>
</html>
