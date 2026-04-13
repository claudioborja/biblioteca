<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Impresión — Biblioteca', ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <style>
        @media print {
            body { margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 12pt; }
            .no-print { display: none !important; }
            @page { margin: 1.5cm; }
        }
        @media screen {
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
            .print-btn { position: fixed; top: 1rem; right: 1rem; padding: 0.5rem 1.5rem; background: #2563eb; color: #fff; border: none; border-radius: 0.25rem; cursor: pointer; font-size: 0.875rem; }
            .print-btn:hover { background: #1d4ed8; }
        }
    </style>
</head>
<body>

    <button class="print-btn no-print inline-flex items-center gap-2" onclick="window.print()"><?= \Helpers\Icons::print('w-4 h-4') ?> Imprimir</button>

    <?= $content ?>

</body>
</html>
