<?php
// views/admin/reports/loans.php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<section class="p-6 lg:p-8">
    <?php $currentReport = 'loans'; require __DIR__ . '/_subnav.php'; ?>

    <div class="mb-6">
        <p class="label-sm">Reportes</p>
        <h1 class="headline-lg text-on-surface">Préstamos</h1>
        <p class="body-md mt-1">Exporta el historial de préstamos con filtros de estado y fechas.</p>
    </div>

    <!-- Stats -->
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Total</p>
            <p class="mt-2 text-3xl font-bold font-display text-on-surface"><?= (int) ($kpis['total'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Activos</p>
            <p class="mt-2 text-3xl font-bold font-display text-blue-700"><?= (int) ($kpis['active'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Vencidos</p>
            <p class="mt-2 text-3xl font-bold font-display text-amber-700"><?= (int) ($kpis['overdue'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Devueltos</p>
            <p class="mt-2 text-3xl font-bold font-display text-emerald-700"><?= (int) ($kpis['returned'] ?? 0) ?></p>
        </article>
    </div>

    <!-- Export -->
    <div class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient">
        <h2 class="headline-md text-on-surface mb-1">Exportar registros</h2>
        <p class="text-sm text-on-surface-muted mb-6">Se exportan todos los préstamos con datos de usuario, recurso, fechas y estado.</p>

        <div class="flex flex-wrap gap-3">
            <a href="<?= BASE_URL ?>/admin/reports/loans/export/excel"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-ambient transition-colors hover:bg-emerald-700">
                <iconify-icon icon="mdi:file-excel-box"></iconify-icon>
                Descargar Excel
            </a>
            <a href="<?= BASE_URL ?>/admin/reports/loans/export/pdf"
               class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-ambient transition-colors hover:bg-red-700">
                <iconify-icon icon="mdi:file-pdf-box"></iconify-icon>
                Descargar PDF
            </a>
        </div>

        <p class="mt-4 text-xs text-on-surface-subtle">
            Columnas: Usuario · N° Usuario · Recurso · ISBN/Código · Fecha préstamo · Fecha vencimiento · Estado
        </p>
    </div>
</section>
