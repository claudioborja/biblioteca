<?php
// views/admin/dashboard.php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$totalResources = (int) ($total_resources ?? 0);
$types          = is_array($resources_by_type ?? null) ? $resources_by_type : [];
$userStats      = is_array($user_stats  ?? null) ? $user_stats  : [];
$loanStats      = is_array($loan_stats  ?? null) ? $loan_stats  : [];
$fineStats      = is_array($fine_stats  ?? null) ? $fine_stats  : [];
$visitStats     = is_array($visit_stats ?? null) ? $visit_stats : [];
$recentLoans    = is_array($recent_loans ?? null) ? $recent_loans : [];

// Chart data from PHP → JS
$visitsLabels = array_map(fn($d) => date('d/m', strtotime($d)), array_keys($visits_chart ?? []));
$visitsData   = array_values($visits_chart ?? []);
$loansLabels  = array_map(fn($d) => date('d/m', strtotime($d)), array_keys($loans_chart ?? []));
$loansData    = array_values($loans_chart ?? []);

$typeLabels = [
    'physical' => 'Libros físicos',
    'digital'  => 'Libros digitales',
    'journal'  => 'Revistas / Artículos',
    'thesis'   => 'Tesis',
    'other'    => 'Otros',
];
$typeCrudSlugMap = [
    'physical' => 'libros',
    'digital'  => 'digitales',
    'journal'  => 'revistas',
    'thesis'   => 'tesis',
    'other'    => 'otros',
];
$typeColors = [
    'physical' => ['bg' => '#3b82f6', 'light' => '#eff6ff'],
    'digital'  => ['bg' => '#8b5cf6', 'light' => '#f5f3ff'],
    'journal'  => ['bg' => '#10b981', 'light' => '#ecfdf5'],
    'thesis'   => ['bg' => '#f59e0b', 'light' => '#fffbeb'],
    'other'    => ['bg' => '#6b7280', 'light' => '#f9fafb'],
];
$typeIcons = [
    'physical' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
    'digital'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9m-9 6h9m-9 6h9M4.5 7.5h.008v.008H4.5V7.5zm0 6h.008v.008H4.5V13.5zm0 6h.008v.008H4.5V19.5z"/>',
    'journal'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.5v11.25A2.25 2.25 0 0117.25 21H6.75A2.25 2.25 0 014.5 18.75V5.25A2.25 2.25 0 016.75 3h7.5L19.5 7.5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3v4.5h4.5M8.25 12h7.5M8.25 15h7.5"/>',
    'thesis'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 14.25 3.75 9.75 12 5.25l8.25 4.5L12 14.25z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12v3.75c0 1.243 2.35 2.25 5.25 2.25s5.25-1.007 5.25-2.25V12"/>',
    'other'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>',
];

$loanStatusColors = [
    'active'   => 'bg-blue-100 text-blue-700',
    'overdue'  => 'bg-red-100 text-red-700',
    'returned' => 'bg-emerald-100 text-emerald-700',
    'lost'     => 'bg-slate-100 text-slate-600',
];
$loanStatusLabels = [
    'active'   => 'Activo',
    'overdue'  => 'Vencido',
    'returned' => 'Devuelto',
    'lost'     => 'Perdido',
];

$weekdayMap = [
    'Monday' => 'lunes',
    'Tuesday' => 'martes',
    'Wednesday' => 'miercoles',
    'Thursday' => 'jueves',
    'Friday' => 'viernes',
    'Saturday' => 'sabado',
    'Sunday' => 'domingo',
];
$monthMap = [
    'January' => 'enero',
    'February' => 'febrero',
    'March' => 'marzo',
    'April' => 'abril',
    'May' => 'mayo',
    'June' => 'junio',
    'July' => 'julio',
    'August' => 'agosto',
    'September' => 'septiembre',
    'October' => 'octubre',
    'November' => 'noviembre',
    'December' => 'diciembre',
];
$weekdayEn = date('l');
$monthEn = date('F');
$currentDateLabel = sprintf(
    '%s, %s de %s de %s',
    $weekdayMap[$weekdayEn] ?? strtolower($weekdayEn),
    date('d'),
    $monthMap[$monthEn] ?? strtolower($monthEn),
    date('Y')
);
$currentTimeLabel = date('H:i:s');
$csrfToken = (string) \Core\Session::get('_csrf_token', '');
?>

<section class="p-4 lg:p-6 space-y-5">
    <style>
        .dash-fade-up { animation: dashFadeUp .45s ease-out both; }
        .dash-delay-1 { animation-delay: .04s; }
        .dash-delay-2 { animation-delay: .08s; }
        .dash-delay-3 { animation-delay: .12s; }
        .dash-delay-4 { animation-delay: .16s; }
        .dash-delay-5 { animation-delay: .2s; }

        @keyframes dashFadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .dash-fade-up,
            .dash-delay-1,
            .dash-delay-2,
            .dash-delay-3,
            .dash-delay-4,
            .dash-delay-5 {
                animation: none !important;
            }
        }
    </style>

    <div class="relative overflow-hidden rounded-2xl gradient-scholar px-5 py-6 text-white shadow-ambient-lg dash-fade-up">
        <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10"></div>
        <div class="pointer-events-none absolute -bottom-12 left-10 h-40 w-40 rounded-full bg-white/5"></div>
        <div class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/70">Panel administrativo</p>
                <h1 class="mt-1 text-3xl font-display font-extrabold tracking-tight">Dashboard bibliotecario</h1>
            </div>
            <div class="lg:min-w-[420px]">
                <p class="text-[10px] uppercase tracking-[0.14em] text-white/70 lg:text-right">Resumen en tiempo real</p>
                <div class="mt-1 flex flex-wrap items-end gap-x-5 gap-y-2 text-white/90 lg:justify-end">
                    <p class="text-sm">
                        <span class="text-white/65">Recursos</span>
                        <span class="ml-1 font-display text-xl font-extrabold text-white"><?= number_format($totalResources) ?></span>
                    </p>
                    <p class="text-sm">
                        <span class="text-white/65">Préstamos</span>
                        <span class="ml-1 font-display text-xl font-extrabold text-white"><?= number_format((int)($loanStats['active_loans'] ?? 0)) ?></span>
                    </p>
                    <p class="text-sm">
                        <span class="text-white/65">Usuarios</span>
                        <span class="ml-1 font-display text-xl font-extrabold text-white"><?= number_format((int)($userStats['active_users'] ?? 0)) ?></span>
                    </p>
                    <p class="text-sm">
                        <span class="text-white/65">Visitas hoy</span>
                        <span class="ml-1 font-display text-xl font-extrabold text-white"><?= number_format((int)($visitStats['visits_today'] ?? 0)) ?></span>
                    </p>
                </div>
            </div>
        </div>
        <p class="relative mt-4 flex flex-wrap items-center gap-x-3 text-xs text-white/65">
            <span><?= $e($currentDateLabel) ?></span>
            <span class="inline-flex items-center gap-1 rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-[11px] font-semibold text-white">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                <span id="dashboard-live-clock"><?= $e($currentTimeLabel) ?></span>
            </span>
        </p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient dash-fade-up dash-delay-2">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-on-surface">Visitas — últimos 14 días</h2>
                    <p class="text-xs text-on-surface-subtle">Total acumulado: <?= number_format((int)($visitStats['total_visits'] ?? 0)) ?></p>
                </div>
                <div class="relative" id="visits-menu-wrap">
                    <button type="button"
                            id="visits-menu-btn"
                            class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100"
                            aria-expanded="false"
                            aria-controls="visits-menu-list">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Hoy: <?= (int)($visitStats['visits_today'] ?? 0) ?>
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.162l3.71-3.93a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div id="visits-menu-list" class="absolute right-0 z-20 mt-2 hidden w-80 overflow-hidden rounded-xl border border-outline-variant/60 bg-white shadow-ambient">
                        <a href="<?= BASE_URL ?>/admin/reports/visits"
                           class="flex items-center gap-2 px-3 py-2 text-xs text-on-surface hover:bg-surface-container-low">
                            <?= Icons::list('h-4 w-4 text-primary') ?>
                            Ver todas las visitas
                        </a>
                        <a href="<?= BASE_URL ?>/admin/reports/visits/export/pdf"
                           class="flex items-center gap-2 px-3 py-2 text-xs text-on-surface hover:bg-surface-container-low">
                            <?= Icons::download('h-4 w-4 text-red-600') ?>
                            Descargar reporte
                        </a>
                        <button type="button"
                                id="dashboard-open-purge-visits"
                                class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-red-700 hover:bg-red-50">
                            <?= Icons::delete('h-4 w-4') ?>
                            Borrar visitas anteriores
                        </button>
                    </div>
                </div>
            </div>
            <div class="h-52"><canvas id="chart-visits"></canvas></div>
        </div>

        <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient dash-fade-up dash-delay-3">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-on-surface">Préstamos — últimos 14 días</h2>
                    <p class="text-xs text-on-surface-subtle">Total histórico: <?= number_format((int)($loanStats['total_loans'] ?? 0)) ?></p>
                </div>
                <div class="relative" id="loans-menu-wrap">
                    <button type="button"
                            id="loans-menu-btn"
                            class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                            aria-expanded="false"
                            aria-controls="loans-menu-list">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                        Activos: <?= (int)($loanStats['active_loans'] ?? 0) ?>
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.162l3.71-3.93a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div id="loans-menu-list" class="absolute right-0 z-20 mt-2 hidden w-72 overflow-hidden rounded-xl border border-outline-variant/60 bg-white shadow-ambient">
                        <a href="<?= BASE_URL ?>/admin/loans"
                           class="flex items-center gap-2 px-3 py-2 text-xs text-on-surface hover:bg-surface-container-low">
                            <?= Icons::list('h-4 w-4 text-primary') ?>
                            Ver todos los préstamos
                        </a>
                        <a href="<?= BASE_URL ?>/admin/reports/loans/export/pdf"
                           class="flex items-center gap-2 px-3 py-2 text-xs text-on-surface hover:bg-surface-container-low">
                            <?= Icons::download('h-4 w-4 text-red-600') ?>
                            Descargar reporte
                        </a>
                    </div>
                </div>
            </div>
            <div class="h-52"><canvas id="chart-loans"></canvas></div>
        </div>
    </div>

    <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient dash-fade-up dash-delay-3">
        <h2 class="mb-4 text-sm font-semibold text-on-surface">Colección por tipo</h2>
        <?php if ($types !== []): ?>
            <div class="mb-4 h-auto min-h-64 w-full">
                <canvas id="chart-types"></canvas>
            </div>
        <?php else: ?>
            <p class="text-sm text-on-surface-subtle">Sin recursos registrados.</p>
        <?php endif; ?>
    </div>

    <?php if ($types !== []): ?>
    <div class="grid gap-2 sm:grid-cols-3 lg:grid-cols-5 dash-fade-up dash-delay-4">
        <?php foreach ($types as $type):
            $count    = (int)($type['resources_count'] ?? 0);
            $slug     = strtolower(trim((string)($type['resource_type'] ?? 'other')));
            $label    = $typeLabels[$slug] ?? ucfirst($slug);
            $iconPath = $typeIcons[$slug] ?? $typeIcons['other'];
            $color    = $typeColors[$slug] ?? $typeColors['other'];
            $crudSlug = $typeCrudSlugMap[$slug] ?? null;
            $href     = $crudSlug ? BASE_URL . '/admin/resources/type/' . rawurlencode($crudSlug) : BASE_URL . '/admin/resources';
        ?>
        <a href="<?= $e($href) ?>"
           class="group flex items-center gap-3 rounded-xl border border-outline-variant/60 bg-white p-3.5 shadow-ambient transition-all duration-150 hover:-translate-y-0.5 hover:shadow-ambient-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg transition-colors duration-150"
                  style="background:<?= $e($color['light']) ?>; color:<?= $e($color['bg']) ?>">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><?= $iconPath ?></svg>
            </span>
            <div class="min-w-0">
                <p class="truncate text-[10px] font-semibold uppercase tracking-wide text-on-surface-subtle"><?= $e($label) ?></p>
                <p class="font-display text-xl font-extrabold leading-none text-on-surface"><?= number_format($count) ?></p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 dash-fade-up dash-delay-5">
        <div class="rounded-xl border border-outline-variant/60 bg-white px-4 py-3 shadow-ambient flex items-center gap-3">
            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </span>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-subtle">Vencidos</p>
                <p class="text-xl font-extrabold font-display text-red-600"><?= number_format((int)($loanStats['overdue_loans'] ?? 0)) ?></p>
            </div>
        </div>
        <div class="rounded-xl border border-outline-variant/60 bg-white px-4 py-3 shadow-ambient flex items-center gap-3">
            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/></svg>
            </span>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-subtle">Multas pendientes</p>
                <p class="text-xl font-extrabold font-display text-orange-600">$<?= number_format((float)($fineStats['pending_amount'] ?? 0), 2) ?></p>
            </div>
        </div>
        <div class="rounded-xl border border-outline-variant/60 bg-white px-4 py-3 shadow-ambient flex items-center gap-3">
            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            </span>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-subtle">Socios</p>
                <p class="text-xl font-extrabold font-display text-on-surface"><?= number_format((int)($userStats['member_users'] ?? 0)) ?></p>
            </div>
        </div>
        <div class="rounded-xl border border-outline-variant/60 bg-white px-4 py-3 shadow-ambient flex items-center gap-3">
            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
            </span>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-on-surface-subtle">Docentes</p>
                <p class="text-xl font-extrabold font-display text-indigo-600"><?= number_format((int)($userStats['teacher_users'] ?? 0)) ?></p>
            </div>
        </div>
    </div>
</section>

<?php if (($auth_user['role'] ?? '') === 'admin'): ?>
<div id="modal-purge" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="modal-purge-backdrop"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-outline-variant/60 px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9 14.394 18m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                </span>
                <h2 class="text-base font-semibold text-on-surface">Limpiar registro de visitas</h2>
            </div>
            <button type="button" id="modal-purge-close" class="rounded-lg p-1.5 text-on-surface-subtle hover:bg-surface-container-low">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/admin/reports/visits/purge" id="form-purge">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
            <div class="px-6 py-5 space-y-4">
                <p class="text-sm text-on-surface-muted">Selecciona qué registros deseas eliminar de forma permanente:</p>

                <div class="space-y-3">
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-outline-variant p-3 has-[:checked]:border-red-400 has-[:checked]:bg-red-50 transition-colors">
                        <input type="radio" name="mode" value="all" class="mt-0.5 accent-red-600" id="purge-all">
                        <div>
                            <p class="text-sm font-semibold text-on-surface">Todos los registros</p>
                            <p class="text-xs text-on-surface-subtle">Vacía completamente la tabla de visitas.</p>
                        </div>
                    </label>

                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-outline-variant p-3 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50 transition-colors">
                        <input type="radio" name="mode" value="older" class="mt-0.5 accent-amber-600" id="purge-older">
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-on-surface">Anteriores a N días</p>
                            <p class="text-xs text-on-surface-subtle mb-2">Elimina visitas más antiguas que el número de días indicado.</p>
                            <div class="flex items-center gap-2">
                                <input type="number" name="days" id="purge-days" min="1" max="3650" value="30"
                                       class="w-24 rounded-lg border border-outline-variant px-2.5 py-1.5 text-sm focus:border-primary focus:outline-none">
                                <span class="text-xs text-on-surface-subtle">días atrás</span>
                            </div>
                        </div>
                    </label>

                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-outline-variant p-3 has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50 transition-colors">
                        <input type="radio" name="mode" value="range" class="mt-0.5 accent-blue-600" id="purge-range">
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-on-surface">Rango de fechas</p>
                            <p class="text-xs text-on-surface-subtle mb-2">Elimina visitas entre las fechas seleccionadas (inclusive).</p>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-on-surface-subtle">Desde</label>
                                    <input type="date" name="date_from" id="purge-from"
                                           class="mt-1 w-full rounded-lg border border-outline-variant px-2.5 py-1.5 text-sm focus:border-primary focus:outline-none">
                                </div>
                                <div>
                                    <label class="text-xs text-on-surface-subtle">Hasta</label>
                                    <input type="date" name="date_to" id="purge-to"
                                           class="mt-1 w-full rounded-lg border border-outline-variant px-2.5 py-1.5 text-sm focus:border-primary focus:outline-none">
                                </div>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="flex items-start gap-2 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
                    <svg class="h-4 w-4 text-red-500 mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2.004 4.043-2.004 5.198 0l6.857 11.897c1.154 2.003-.289 4.505-2.6 4.505H5.144c-2.311 0-3.754-2.502-2.6-4.505L9.401 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a1.125 1.125 0 1 0 0-2.25 1.125 1.125 0 0 0 0 2.25Z" clip-rule="evenodd"/></svg>
                    <p class="text-xs text-red-700">Esta acción es <strong>irreversible</strong>. Los registros eliminados no se pueden recuperar.</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-outline-variant/60 px-6 py-4">
                <button type="button" id="modal-purge-cancel"
                        class="rounded-xl border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                    Cancelar
                </button>
                <button type="submit" id="btn-purge-confirm"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Eliminar registros
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
(() => {
    // Registrar plugin datalabels
    Chart.register(ChartDataLabels);
    
    const liveClockEl = document.getElementById('dashboard-live-clock');
    if (liveClockEl) {
        const updateLiveClock = () => {
            const now = new Date();
            liveClockEl.textContent = now.toLocaleTimeString('es-EC', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });
        };
        updateLiveClock();
        window.setInterval(updateLiveClock, 1000);
    }

    const visitsMenuWrap = document.getElementById('visits-menu-wrap');
    const visitsMenuBtn = document.getElementById('visits-menu-btn');
    const visitsMenuList = document.getElementById('visits-menu-list');
    const dashboardOpenPurgeBtn = document.getElementById('dashboard-open-purge-visits');
    if (visitsMenuWrap && visitsMenuBtn && visitsMenuList) {
        const closeMenu = () => {
            visitsMenuList.classList.add('hidden');
            visitsMenuBtn.setAttribute('aria-expanded', 'false');
        };
        const openMenu = () => {
            visitsMenuList.classList.remove('hidden');
            visitsMenuBtn.setAttribute('aria-expanded', 'true');
        };
        visitsMenuBtn.addEventListener('click', () => {
            if (visitsMenuList.classList.contains('hidden')) {
                openMenu();
            } else {
                closeMenu();
            }
        });
        document.addEventListener('click', (event) => {
            if (!visitsMenuWrap.contains(event.target)) {
                closeMenu();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });
        dashboardOpenPurgeBtn?.addEventListener('click', () => {
            closeMenu();
            document.getElementById('modal-purge')?.classList.remove('hidden');
            document.getElementById('modal-purge')?.classList.add('flex');
        });
    }

    const loansMenuWrap = document.getElementById('loans-menu-wrap');
    const loansMenuBtn = document.getElementById('loans-menu-btn');
    const loansMenuList = document.getElementById('loans-menu-list');
    if (loansMenuWrap && loansMenuBtn && loansMenuList) {
        const closeLoansMenu = () => {
            loansMenuList.classList.add('hidden');
            loansMenuBtn.setAttribute('aria-expanded', 'false');
        };
        const openLoansMenu = () => {
            loansMenuList.classList.remove('hidden');
            loansMenuBtn.setAttribute('aria-expanded', 'true');
        };
        loansMenuBtn.addEventListener('click', () => {
            if (loansMenuList.classList.contains('hidden')) {
                openLoansMenu();
            } else {
                closeLoansMenu();
            }
        });
        document.addEventListener('click', (event) => {
            if (!loansMenuWrap.contains(event.target)) {
                closeLoansMenu();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeLoansMenu();
            }
        });
    }

    // ── Modal limpiar visitas (mismo comportamiento que vista de visitas) ───
    (() => {
        const modal    = document.getElementById('modal-purge');
        if (!modal) return;

        const closeBtn = document.getElementById('modal-purge-close');
        const cancelBtn= document.getElementById('modal-purge-cancel');
        const backdrop = document.getElementById('modal-purge-backdrop');
        const confirm  = document.getElementById('btn-purge-confirm');
        const radios   = modal.querySelectorAll('input[type=radio]');
        const form     = document.getElementById('form-purge');

        const open  = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); };
        const close = () => { modal.classList.add('hidden');    modal.classList.remove('flex'); };

        closeBtn?.addEventListener('click', close);
        cancelBtn?.addEventListener('click', close);
        backdrop?.addEventListener('click', close);

        radios.forEach(r => r.addEventListener('change', () => { confirm.disabled = false; }));

        form?.addEventListener('submit', (e) => {
            const mode = form.querySelector('input[name=mode]:checked')?.value;
            if (!mode) { e.preventDefault(); return; }

            const labels = {
                all: 'TODOS los registros de visitas',
                older: 'los registros anteriores al período indicado',
                range: 'los registros del rango de fechas seleccionado'
            };
            if (!window.confirm(`¿Seguro que deseas eliminar ${labels[mode]}? Esta acción no se puede deshacer.`)) {
                e.preventDefault();
            }
        });
    })();

    const font = { family: 'Inter, system-ui, sans-serif', size: 11 };
    Chart.defaults.font = font;
    Chart.defaults.color = '#6b7280';

    const gridColor  = 'rgba(0,0,0,0.05)';
    const tooltipBg  = '#1e293b';

    const sharedTooltip = {
        backgroundColor: tooltipBg,
        titleColor: '#f1f5f9',
        bodyColor: '#cbd5e1',
        cornerRadius: 8,
        padding: 10,
        displayColors: false,
    };

    // ── Gráfico visitas ───────────────────────────────────────────────────
    new Chart(document.getElementById('chart-visits'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_values($visitsLabels)) ?>,
            datasets: [{
                data: <?= json_encode($visitsData) ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245,158,11,0.12)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#f59e0b',
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { 
                legend: { display: false }, 
                tooltip: sharedTooltip,
                datalabels: { display: false }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font } },
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { font, precision: 0 } }
            }
        }
    });

    // ── Gráfico préstamos ─────────────────────────────────────────────────
    new Chart(document.getElementById('chart-loans'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_values($loansLabels)) ?>,
            datasets: [{
                data: <?= json_encode($loansData) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.12)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#3b82f6',
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { 
                legend: { display: false }, 
                tooltip: sharedTooltip,
                datalabels: { display: false }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font } },
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { font, precision: 0 } }
            }
        }
    });

    // ── Gráfico barras horizontales tipos ────────────────────────────────────────────────
    <?php if ($types !== []): ?>
    const typesData = <?= json_encode(array_map(fn($t) => (int)($t['resources_count'] ?? 0), $types)) ?>;
    const typesTotalResources = <?= (int)$totalResources ?>;
    const typesPercentages = typesData.map(v => v > 0 ? Math.round((v / typesTotalResources) * 100) : 0);
    const typesMaxValue = Math.max(...typesData);
    const typesThreshold = typesMaxValue * 0.15; // Si es <15% del máximo, mostrar en un lado
    
    new Chart(document.getElementById('chart-types'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(fn($t) => $typeLabels[strtolower(trim((string)($t['resource_type'] ?? 'other')))] ?? 'Otro', $types)) ?>,
            datasets: [{
                data: typesData,
                backgroundColor: <?= json_encode(array_map(fn($t) => $typeColors[strtolower(trim((string)($t['resource_type'] ?? 'other')))]['bg'] ?? '#6b7280', $types)) ?>,
                borderWidth: 0,
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: sharedTooltip,
                datalabels: {
                    display: true,
                    anchor: (context) => context.dataset.data[context.dataIndex] > typesThreshold ? 'center' : 'end',
                    align: (context) => context.dataset.data[context.dataIndex] > typesThreshold ? 'center' : 'right',
                    offset: (context) => context.dataset.data[context.dataIndex] > typesThreshold ? 0 : 8,
                    font: { size: 13, weight: 'bold' },
                    color: (context) => context.dataset.data[context.dataIndex] > typesThreshold ? '#ffffff' : '#6b7280',
                    formatter: (value, context) => value + ' (' + typesPercentages[context.dataIndex] + '%)',
                }
            },
            scales: {
                x: { beginAtZero: true, grid: { color: gridColor }, ticks: { font, precision: 0 } },
                y: { grid: { display: false }, ticks: { font } }
            }
        }
    });
    <?php endif; ?>
})();
</script>
