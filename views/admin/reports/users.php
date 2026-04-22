<?php
// views/admin/reports/users.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<section class="p-6 lg:p-8">
    <?php $currentReport = 'users'; require __DIR__ . '/_subnav.php'; ?>

    <div class="mb-6">
        <p class="label-sm">Reportes</p>
        <h1 class="headline-lg text-on-surface">Usuarios</h1>
        <p class="body-md mt-1">Exporta el padrón de usuarios con su estado y actividad en el sistema.</p>
    </div>

    <!-- Stats -->
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Total socios</p>
            <p class="mt-2 text-3xl font-bold font-display text-on-surface"><?= (int) ($kpis['total'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Activos</p>
            <p class="mt-2 text-3xl font-bold font-display text-emerald-700"><?= (int) ($kpis['active'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Con préstamo activo</p>
            <p class="mt-2 text-3xl font-bold font-display text-blue-700"><?= (int) ($kpis['with_active_loan'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Con multa pendiente</p>
            <p class="mt-2 text-3xl font-bold font-display text-amber-700"><?= (int) ($kpis['with_pending_fine'] ?? 0) ?></p>
        </article>
    </div>

    <!-- Export -->
    <div class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient">
        <h2 class="headline-md text-on-surface mb-1">Exportar registros</h2>
        <p class="text-sm text-on-surface-muted mb-6">Se exportan todos los usuarios del sistema con su información de contacto y actividad.</p>

        <div class="flex flex-wrap gap-3">
            <a href="<?= BASE_URL ?>/admin/reports/users/export/csv"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-ambient transition-colors hover:bg-emerald-700">
                <iconify-icon icon="mdi:file-excel-box"></iconify-icon>
                Descargar CSV
            </a>
            <a href="<?= BASE_URL ?>/admin/reports/users/export/pdf"
               class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-ambient transition-colors hover:bg-red-700">
                <iconify-icon icon="mdi:file-pdf-box"></iconify-icon>
                Descargar PDF
            </a>
        </div>

        <p class="mt-4 text-xs text-on-surface-subtle">
            Columnas: N° Usuario · Nombre · Correo · Rol · Estado · Préstamos · Multas pendientes · Fecha registro
        </p>
    </div>
</section>
