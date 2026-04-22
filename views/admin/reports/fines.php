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
                <iconify-icon icon="mdi:file-excel-box"></iconify-icon>
                Descargar CSV
            </a>
            <a href="<?= BASE_URL ?>/admin/reports/fines/export/pdf"
               class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-ambient transition-colors hover:bg-red-700">
                <iconify-icon icon="mdi:file-pdf-box"></iconify-icon>
                Descargar PDF
            </a>
        </div>

        <p class="mt-4 text-xs text-on-surface-subtle">
            Columnas: Usuario · N° Usuario · Recurso · Monto · Pagado · Pendiente · Estado · Fecha
        </p>
    </div>
</section>
