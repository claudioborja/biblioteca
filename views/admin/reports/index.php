<?php
// views/admin/reports/index.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$currency = '$';

$reportCards = [
    [
        'title' => 'Préstamos',
        'desc' => 'Actividad de préstamos, vencimientos y renovaciones.',
        'href' => BASE_URL . '/admin/reports/loans',
        'icon' => 'bi-journal-check',
    ],
    [
        'title' => 'Inventario',
        'desc' => 'Disponibilidad, existencias y estado del catálogo.',
        'href' => BASE_URL . '/admin/reports/inventory',
        'icon' => 'bi-box-seam',
    ],
    [
        'title' => 'Usuarios',
        'desc' => 'Altas, estado y evolución de número de usuarios.',
        'href' => BASE_URL . '/admin/reports/users',
        'icon' => 'bi-people',
    ],
    [
        'title' => 'Multas',
        'desc' => 'Cobranzas, pendientes y condonaciones.',
        'href' => BASE_URL . '/admin/reports/fines',
        'icon' => 'bi-cash-stack',
    ],
    [
        'title' => 'Visitas',
        'desc' => 'Tráfico y comportamiento de acceso al sistema.',
        'href' => BASE_URL . '/admin/reports/visits',
        'icon' => 'bi-graph-up-arrow',
    ],
];

$kpis = [
    ['label' => 'Préstamos totales', 'value' => (string) ((int) ($summary['total_loans'] ?? 0)), 'tone' => 'text-on-surface'],
    ['label' => 'Préstamos activos', 'value' => (string) ((int) ($summary['active_loans'] ?? 0)), 'tone' => 'text-blue-700'],
    ['label' => 'Préstamos vencidos', 'value' => (string) ((int) ($summary['overdue_loans'] ?? 0)), 'tone' => 'text-amber-700'],
    ['label' => 'Multas acumuladas', 'value' => $currency . number_format((float) ($summary['total_fines'] ?? 0), 2, '.', ','), 'tone' => 'text-violet-700'],
    ['label' => 'Multas pendientes', 'value' => $currency . number_format((float) ($summary['pending_fines'] ?? 0), 2, '.', ','), 'tone' => 'text-red-700'],
    ['label' => 'Usuarios', 'value' => (string) ((int) ($summary['users'] ?? 0)), 'tone' => 'text-emerald-700'],
];
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="p-6 lg:p-8">
    <?php $currentReport = 'index'; require __DIR__ . '/_subnav.php'; ?>

    <div class="mb-7 overflow-hidden rounded-3xl border border-outline-variant/60 bg-gradient-to-br from-primary/95 to-primary-container p-6 text-white shadow-ambient-lg">
        <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr] lg:items-end">
            <div>
                <p class="label-sm text-white/75">Analítica</p>
                <h1 class="headline-lg text-white">Centro de Reportes</h1>
                <p class="mt-2 text-sm text-white/85">Accede a reportes operativos y métricas clave en un solo panel.</p>
            </div>
            <div class="grid gap-2 sm:grid-cols-2">
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-xs uppercase tracking-wide text-white/75">Préstamos activos</p>
                    <p class="mt-1 text-2xl font-display font-bold text-white"><?= (int) ($summary['active_loans'] ?? 0) ?></p>
                </div>
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-xs uppercase tracking-wide text-white/75">Multa pendiente</p>
                    <p class="mt-1 text-2xl font-display font-bold text-white"><?= $e($currency . number_format((float) ($summary['pending_fines'] ?? 0), 2, '.', ',')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-7 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($reportCards as $card): ?>
            <article class="group rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient transition-all hover:-translate-y-0.5 hover:shadow-ambient-lg">
                <div class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary/8 text-primary">
                    <i class="bi <?= $e($card['icon']) ?> text-base"></i>
                </div>
                <h2 class="text-lg font-semibold text-on-surface"><?= $e($card['title']) ?></h2>
                <p class="mt-1 text-sm text-on-surface-muted"><?= $e($card['desc']) ?></p>
                <a href="<?= $e($card['href']) ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm font-semibold text-on-surface transition-colors group-hover:border-primary/40 group-hover:text-primary hover:bg-surface-container-low">
                    Ver reporte <i class="bi bi-arrow-right text-[12px]"></i>
                </a>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($kpis as $kpi): ?>
            <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
                <p class="label-md"><?= $e($kpi['label']) ?></p>
                <p class="mt-2 text-2xl font-bold font-display <?= $e($kpi['tone']) ?>"><?= $e($kpi['value']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <h2 class="headline-md text-on-surface mb-4">Top recursos prestados</h2>
            <?php if (empty($top_books)): ?>
                <p class="text-sm text-on-surface-subtle">Sin datos disponibles.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($top_books as $idx => $book): ?>
                        <div class="flex items-center justify-between rounded-xl border border-outline-variant/50 px-3.5 py-2.5">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-on-surface truncate">#<?= (int) $idx + 1 ?> · <?= $e($book['title']) ?></p>
                            </div>
                            <span class="ml-3 text-xs font-semibold text-primary bg-primary/8 rounded-full px-2 py-1"><?= (int) $book['loans_count'] ?> préstamos</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <h2 class="headline-md text-on-surface mb-4">Tendencia mensual de préstamos</h2>
            <?php if (empty($monthly_loans)): ?>
                <p class="text-sm text-on-surface-subtle">Sin datos disponibles.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($monthly_loans as $row): ?>
                        <?php
                            $count = (int) ($row['loans_count'] ?? 0);
                            $width = min(100, max(4, $count * 4));
                        ?>
                        <div>
                            <div class="flex items-center justify-between text-xs text-on-surface-subtle mb-1">
                                <span><?= $e($row['month_key']) ?></span>
                                <span><?= $count ?> préstamos</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-surface-container-low overflow-hidden">
                                <div class="h-full gradient-scholar" style="width: <?= $width ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
