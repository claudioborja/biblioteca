<?php
// views/admin/dashboard.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

/** @var int $total_resources */
/** @var int $total_types */
/** @var list<array<string,mixed>> $resources_by_type */
/** @var array<string,mixed> $user_stats */
$totalResources = (int) ($total_resources ?? 0);
$totalTypes = (int) ($total_types ?? 0);
$types = is_array($resources_by_type ?? null) ? $resources_by_type : [];
$userStats = is_array($user_stats ?? null) ? $user_stats : [];

$typeLabels = [
    'physical' => 'Físico',
    'digital' => 'Digital',
    'audiovisual' => 'Audiovisual',
    'journal' => 'Seriado',
    'thesis' => 'Tesis',
    'map' => 'Cartográfico',
    'score' => 'Partitura',
    'kit' => 'Kit',
    'game' => 'Juego',
    'other' => 'Otro',
];
$typeCrudSlugMap = [
    'physical' => 'libros',
    'digital' => 'digitales',
    'journal' => 'revistas',
    'thesis' => 'tesis',
    'map' => 'mapas',
    'score' => 'partituras',
    'audiovisual' => 'audiovisuales',
    'game' => 'juegos',
    'kit' => 'kits',
    'other' => 'otros',
];
$typeIcons = [
    'physical' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
    'digital' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9m-9 6h9m-9 6h9M4.5 7.5h.008v.008H4.5V7.5zm0 6h.008v.008H4.5V13.5zm0 6h.008v.008H4.5V19.5z"/>',
    'audiovisual' => '<path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-2.36A.75.75 0 0121.75 8.81v6.38a.75.75 0 01-1.28.67l-4.72-2.36v2.25A2.25 2.25 0 0113.5 18h-8.25A2.25 2.25 0 013 15.75v-7.5A2.25 2.25 0 015.25 6h8.25a2.25 2.25 0 012.25 2.25v2.25z"/>',
    'journal' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.5v11.25A2.25 2.25 0 0117.25 21H6.75A2.25 2.25 0 014.5 18.75V5.25A2.25 2.25 0 016.75 3h7.5L19.5 7.5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3v4.5h4.5M8.25 12h7.5M8.25 15h7.5"/>',
    'thesis' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 14.25 3.75 9.75 12 5.25l8.25 4.5L12 14.25z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12v3.75c0 1.243 2.35 2.25 5.25 2.25s5.25-1.007 5.25-2.25V12"/>',
    'map' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75v10.5m6-9v10.5m-10.5-12 4.5-1.5 6 1.5 4.5-1.5v12l-4.5 1.5-6-1.5-4.5 1.5v-12z"/>',
    'score' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 18V5l10-2.25v12M9 9l10-2.25M6.75 18a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5zm10 0a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z"/>',
    'kit' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511V15.75a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V8.511M20.25 8.511 14.25 3.75h-4.5L3.75 8.511m16.5 0L12 13.5 3.75 8.511"/>',
    'game' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15h.008v.008H8.25V15zm7.5-3h.008v.008H15.75V12zm0 3h.008v.008H15.75V15z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 13.5h3m-1.5-1.5v3m2.25 4.5h7.5a3.75 3.75 0 003.75-3.75v-3a6.75 6.75 0 00-6.75-6.75h-1.5A6.75 6.75 0 004.5 12.75v3a3.75 3.75 0 003.75 3.75z"/>',
    'other' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>',
];

$topTypes = $types;
$hiddenTypes = 0;
?>

<section class="p-4 lg:p-5">
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Panel administrativo</p>
            <h1 class="headline-lg text-on-surface">Dashboard</h1>
            <p class="body-md mt-1">Vista mosaico de recursos por tipo y usuarios.</p>
        </div>
    </div>

    <div class="grid gap-2.5 md:grid-cols-2 xl:grid-cols-12">
        <article class="xl:col-span-4 rounded-2xl border border-primary/20 bg-gradient-to-br from-primary via-primary-container to-primary-light p-3.5 text-white shadow-ambient">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="label-sm text-white/70">Total recursos</p>
                    <p class="mt-1 font-display text-3xl font-extrabold leading-none"><?= number_format($totalResources) ?></p>
                    <p class="mt-1.5 text-[11px] text-white/80">Inventario global</p>
                </div>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </span>
            </div>
        </article>

        <article class="xl:col-span-2 rounded-2xl border border-outline-variant/60 bg-white p-3.5 shadow-ambient">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="label-sm">Tipos de recurso</p>
                    <p class="mt-1 font-display text-2xl font-bold text-on-surface"><?= number_format($totalTypes) ?></p>
                    <p class="mt-1 text-[11px] text-on-surface-muted">Tipos con recursos</p>
                </div>
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-surface-container-low text-on-surface-muted">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </span>
            </div>
        </article>

        <article class="xl:col-span-2 rounded-2xl border border-outline-variant/60 bg-white p-3.5 shadow-ambient">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="label-sm">Usuarios</p>
                    <p class="mt-1 font-display text-2xl font-bold text-on-surface"><?= (int) ($userStats['total_users'] ?? 0) ?></p>
                    <p class="mt-1 text-[11px] text-on-surface-muted">Total sistema</p>
                </div>
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-surface-container-low text-on-surface-muted">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z"/></svg>
                </span>
            </div>
        </article>

        <article class="xl:col-span-2 rounded-2xl border border-emerald-200 bg-emerald-50 p-3.5 shadow-ambient">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="label-sm text-emerald-700">Usuarios activos</p>
                    <p class="mt-1 font-display text-2xl font-bold text-emerald-700"><?= (int) ($userStats['active_users'] ?? 0) ?></p>
                    <p class="mt-1 text-[11px] text-emerald-700/80">Operativos</p>
                </div>
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                </span>
            </div>
        </article>

        <article class="xl:col-span-2 rounded-2xl border border-amber-200 bg-amber-50 p-3.5 shadow-ambient">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="label-sm text-amber-700">Usuarios restringidos</p>
                    <p class="mt-1 font-display text-2xl font-bold text-amber-700"><?= (int) ($userStats['restricted_users'] ?? 0) ?></p>
                    <p class="mt-1 text-[11px] text-amber-700/80">Bloqueados / suspendidos</p>
                </div>
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 0h10.5A2.25 2.25 0 0119.5 12.75v6A2.25 2.25 0 0117.25 21h-10.5A2.25 2.25 0 014.5 18.75v-6A2.25 2.25 0 016.75 10.5z"/></svg>
                </span>
            </div>
        </article>
    </div>

    <article class="mt-2.5 rounded-2xl border border-outline-variant/60 bg-white p-3.5 shadow-ambient">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-on-surface">Recursos por tipo (top)</h2>
            <?php if ($hiddenTypes > 0): ?>
                <span class="rounded-full bg-surface-container-low px-2 py-0.5 text-[11px] font-semibold text-on-surface-muted">+<?= $hiddenTypes ?> más</span>
            <?php endif; ?>
        </div>

        <?php if ($topTypes === []): ?>
            <p class="text-sm text-on-surface-subtle">No hay recursos registrados.</p>
        <?php else: ?>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <?php $maxCount = max(1, (int) ($topTypes[0]['resources_count'] ?? 1)); ?>
                <?php foreach ($topTypes as $type): ?>
                    <?php
                    $count = (int) ($type['resources_count'] ?? 0);
                    $ratio = min(100, (int) round(($count / $maxCount) * 100));
                    $slug = strtolower(trim((string) ($type['resource_type'] ?? 'other')));
                    $label = $typeLabels[$slug] ?? ucfirst($slug);
                    $iconPath = $typeIcons[$slug] ?? $typeIcons['other'];
                    $crudSlug = $typeCrudSlugMap[$slug] ?? null;
                    $crudHref = $crudSlug !== null
                        ? (BASE_URL . '/admin/resources/type/' . rawurlencode($crudSlug))
                        : (BASE_URL . '/admin/resources');
                    ?>
                    <a href="<?= $e($crudHref) ?>"
                       class="group block rounded-xl border border-outline-variant/50 bg-surface-container-lowest p-2 transition-all duration-150 hover:border-primary/40 hover:bg-primary/5 hover:shadow-ambient focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                       aria-label="Gestionar <?= $e($label) ?>">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-[10px] font-semibold uppercase tracking-wide text-on-surface-subtle"><?= $e($label) ?></p>
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-primary/10 text-primary transition-colors duration-150 group-hover:bg-primary group-hover:text-white">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><?= $iconPath ?></svg>
                            </span>
                        </div>
                        <p class="mt-1 font-display text-lg font-bold text-on-surface"><?= $count ?></p>
                        <div class="mt-1 h-1.5 w-full rounded-full bg-surface-container-low">
                            <div class="h-full rounded-full bg-primary" style="width: <?= $ratio ?>%"></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>
