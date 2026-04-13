<?php
// views/account/assignments.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$statusClass = static function (string $status): string {
    return match ($status) {
        'pending' => 'bg-amber-100 text-amber-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'completed' => 'bg-emerald-100 text-emerald-700',
        default => 'bg-slate-100 text-slate-700',
    };
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'pending' => 'Pendiente',
        'in_progress' => 'En progreso',
        'completed' => 'Completada',
        default => ucfirst($status),
    };
};
?>

<section class="p-6 lg:p-8">
    <div class="mb-7">
        <p class="label-sm">Mi zona</p>
        <h1 class="headline-lg text-on-surface">Mis asignaciones</h1>
        <p class="body-md mt-1">Revisa tus lecturas asignadas y su estado de avance.</p>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Pendientes</p>
            <p class="mt-2 text-2xl font-bold text-amber-700 font-display"><?= (int) ($summary['pending'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">En progreso</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 font-display"><?= (int) ($summary['in_progress'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Completadas</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= (int) ($summary['completed'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Total</p>
            <p class="mt-2 text-2xl font-bold text-primary font-display"><?= (int) ($summary['total'] ?? 0) ?></p>
        </article>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Asignacion</th>
                        <th class="px-4 py-3 font-semibold">Recurso</th>
                        <th class="px-4 py-3 font-semibold">Grupo</th>
                        <th class="px-4 py-3 font-semibold">Entrega</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold">Actualizado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50 text-sm">
                    <?php if (empty($assignments)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-on-surface-subtle">No tienes asignaciones registradas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors">
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-on-surface"><?= $e($assignment['title'] ?? 'Asignacion') ?></p>
                                    <p class="text-xs text-on-surface-subtle truncate"><?= $e($assignment['description'] ?? '') ?></p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="text-on-surface"><?= $e($assignment['resource_title'] ?? 'Recurso') ?></p>
                                    <p class="text-xs text-on-surface-subtle truncate"><?= $e($assignment['resource_authors'] ?? '') ?></p>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($assignment['group_name'] ?? '-') ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted">
                                    <?= !empty($assignment['due_date']) ? $e((new DateTime((string) $assignment['due_date']))->format('d/m/Y')) : '-' ?>
                                </td>
                                <td class="px-4 py-3.5">
                                    <?php $status = (string) ($assignment['status'] ?? 'pending'); ?>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass($status) ?>">
                                        <?= $e($statusLabel($status)) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted">
                                    <?= !empty($assignment['updated_at']) ? $e((new DateTime((string) $assignment['updated_at']))->format('d/m/Y H:i')) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
