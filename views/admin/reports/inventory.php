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
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Descargar CSV
            </a>
            <a href="<?= BASE_URL ?>/admin/reports/inventory/export/pdf"
               class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-ambient transition-colors hover:bg-red-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                Descargar PDF
            </a>
        </div>

        <p class="mt-4 text-xs text-on-surface-subtle">
            Columnas: Código · Título · Autor · Categoría · Tipo · Copias · Disponibles · En préstamo · Préstamos totales · Estado
        </p>
    </div>
</section>
