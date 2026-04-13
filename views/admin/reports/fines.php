<?php
// views/admin/reports/fines.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$currency = '$';
?>

<section class="p-6 lg:p-8">
    <?php $currentReport = 'fines'; require __DIR__ . '/_subnav.php'; ?>

    <div class="mb-6">
        <p class="label-sm">Reportes</p>
        <h1 class="headline-lg text-on-surface">Multas</h1>
        <p class="body-md mt-1">Exporta el registro de multas con montos, pagos y estado de cobranza.</p>
    </div>

    <!-- Stats -->
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Total multas</p>
            <p class="mt-2 text-3xl font-bold font-display text-on-surface"><?= (int) ($kpis['total_count'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Monto total</p>
            <p class="mt-2 text-2xl font-bold font-display text-violet-700"><?= $e($currency . number_format((float)($kpis['total_amount'] ?? 0), 2, '.', ',')) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Pendiente por cobrar</p>
            <p class="mt-2 text-2xl font-bold font-display text-red-700"><?= $e($currency . number_format((float)($kpis['pending_amount'] ?? 0), 2, '.', ',')) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Total cobrado</p>
            <p class="mt-2 text-2xl font-bold font-display text-emerald-700"><?= $e($currency . number_format((float)($kpis['paid_amount'] ?? 0), 2, '.', ',')) ?></p>
        </article>
    </div>

    <!-- Export -->
    <div class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient">
        <h2 class="headline-md text-on-surface mb-1">Exportar registros</h2>
        <p class="text-sm text-on-surface-muted mb-6">Se exportan todas las multas con datos del usuario, recurso, montos y estado de pago.</p>

        <div class="flex flex-wrap gap-3">
            <a href="<?= BASE_URL ?>/admin/reports/fines/export/csv"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-ambient transition-colors hover:bg-emerald-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Descargar CSV
            </a>
            <a href="<?= BASE_URL ?>/admin/reports/fines/export/pdf"
               class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-ambient transition-colors hover:bg-red-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                Descargar PDF
            </a>
        </div>

        <p class="mt-4 text-xs text-on-surface-subtle">
            Columnas: Usuario · N° Usuario · Recurso · Monto · Pagado · Pendiente · Estado · Fecha
        </p>
    </div>
</section>
