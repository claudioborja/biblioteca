<?php
// views/admin/reports/inventory.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<section class="p-6 lg:p-8">
    <?php $currentReport = 'inventory'; require __DIR__ . '/_subnav.php'; ?>

    <div class="mb-6">
        <p class="label-sm">Reportes</p>
        <h1 class="headline-lg text-on-surface">Inventario</h1>
        <p class="body-md mt-1">Exporta el catálogo de recursos con disponibilidad y estadísticas de préstamo.</p>
    </div>

    <!-- Stats -->
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Total recursos</p>
            <p class="mt-2 text-3xl font-bold font-display text-on-surface"><?= (int) ($kpis['total'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Copias disponibles</p>
            <p class="mt-2 text-3xl font-bold font-display text-emerald-700"><?= (int) ($kpis['available'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">En préstamo</p>
            <p class="mt-2 text-3xl font-bold font-display text-blue-700"><?= (int) ($kpis['on_loan'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Inactivos</p>
            <p class="mt-2 text-3xl font-bold font-display text-slate-500"><?= (int) ($kpis['inactive'] ?? 0) ?></p>
        </article>
    </div>

    <!-- Export -->
    <div class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient">
        <h2 class="headline-md text-on-surface mb-1">Exportar registros</h2>
        <p class="text-sm text-on-surface-muted mb-6">Se exportan todos los recursos del catálogo con sus datos de disponibilidad y actividad.</p>

        <div class="flex flex-wrap gap-3">
            <a href="<?= BASE_URL ?>/admin/reports/inventory/export/csv"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-ambient transition-colors hover:bg-emerald-700">
                <iconify-icon icon="mdi:file-excel-box"></iconify-icon>
                Descargar CSV
            </a>
            <a href="<?= BASE_URL ?>/admin/reports/inventory/export/pdf"
               class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-ambient transition-colors hover:bg-red-700">
                <iconify-icon icon="mdi:file-pdf-box"></iconify-icon>
                Descargar PDF
            </a>
        </div>

        <p class="mt-4 text-xs text-on-surface-subtle">
            Columnas: Código · Título · Autor · Categoría · Tipo · Copias · Disponibles · En préstamo · Préstamos totales · Estado
        </p>
    </div>
</section>
