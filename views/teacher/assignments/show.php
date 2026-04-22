<?php
// views/teacher/assignments/show.php
use Helpers\Icons;

$e      = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$today  = new DateTime('today');
$due    = new DateTime((string) $assignment['due_date']);

$total      = (int) ($assignment['total_students']    ?? 0);
$pending    = (int) ($assignment['pending_count']     ?? 0);
$inProgress = (int) ($assignment['in_progress_count'] ?? 0);
$completed  = (int) ($assignment['completed_count']   ?? 0);
$progress   = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
$isOverdue  = $due < $today && $completed < $total;

$statusLabel = static function (string $s): string {
    return match ($s) {
        'pending'     => 'Pendiente',
        'in_progress' => 'En progreso',
        'completed'   => 'Completada',
        default       => ucfirst($s),
    };
};
$statusClass = static function (string $s): string {
    return match ($s) {
        'pending'     => 'bg-amber-100 text-amber-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'completed'   => 'bg-emerald-100 text-emerald-700',
        default       => 'bg-slate-100 text-slate-700',
    };
};
?>

<section class="p-6 lg:p-8">

    <!-- Header -->
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p class="label-sm">Docente · Asignaciones</p>
            <h1 class="headline-lg text-on-surface"><?= $e($assignment['title']) ?></h1>
            <p class="body-md mt-1">
                <?= $e($assignment['group_name']) ?> · <?= $e($assignment['school_year']) ?>
                &nbsp;·&nbsp; Recurso: <strong><?= $e($assignment['resource_title']) ?></strong>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $isOverdue ? 'bg-red-100 text-red-700' : ($progress === 100 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700') ?>">
                <?= $isOverdue ? 'Vencida' : ($progress === 100 ? 'Completada' : 'En curso') ?>
            </span>
            <a href="<?= BASE_URL ?>/teacher/assignments"
               class="inline-flex items-center gap-1.5 rounded-xl border border-outline-variant bg-white px-3.5 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                <?= Icons::arrowLeft('w-4 h-4') ?> Volver
            </a>
        </div>
    </div>

    <!-- Meta + progreso -->
    <div class="mb-7 grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient space-y-4">
            <div class="grid gap-4 sm:grid-cols-4">
                <article class="rounded-xl bg-surface-container-low px-4 py-3 text-center">
                    <p class="label-sm">Total</p>
                    <p class="mt-1 text-2xl font-bold text-on-surface font-display"><?= $total ?></p>
                </article>
                <article class="rounded-xl bg-amber-50 px-4 py-3 text-center">
                    <p class="label-sm text-amber-700">Pendientes</p>
                    <p class="mt-1 text-2xl font-bold text-amber-700 font-display"><?= $pending ?></p>
                </article>
                <article class="rounded-xl bg-blue-50 px-4 py-3 text-center">
                    <p class="label-sm text-blue-700">En progreso</p>
                    <p class="mt-1 text-2xl font-bold text-blue-700 font-display"><?= $inProgress ?></p>
                </article>
                <article class="rounded-xl bg-emerald-50 px-4 py-3 text-center">
                    <p class="label-sm text-emerald-700">Completadas</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700 font-display"><?= $completed ?></p>
                </article>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between text-xs text-on-surface-subtle">
                    <span>Progreso general</span>
                    <span><?= $progress ?>%</span>
                </div>
                <div class="h-2.5 overflow-hidden rounded-full bg-surface-container-low">
                    <div class="h-full gradient-scholar transition-all" style="width: <?= $progress ?>%"></div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient space-y-3 text-sm">
            <div>
                <p class="label-sm">Fecha límite</p>
                <p class="mt-0.5 font-semibold text-on-surface <?= $isOverdue ? 'text-red-700' : '' ?>">
                    <?= $e($due->format('d/m/Y')) ?>
                    <?php if ($isOverdue): ?>
                        <span class="ml-1 text-xs">(vencida)</span>
                    <?php elseif ($due == $today): ?>
                        <span class="ml-1 text-xs text-amber-700">(hoy)</span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <p class="label-sm">Grupo</p>
                <a href="<?= BASE_URL ?>/teacher/groups/<?= (int) $assignment['group_id'] ?>"
                   class="mt-0.5 inline-block font-semibold text-primary hover:opacity-80">
                    <?= $e($assignment['group_name']) ?>
                </a>
            </div>
            <div>
                <p class="label-sm">Recurso</p>
                <a href="<?= BASE_URL ?>/catalog/<?= (int) $assignment['resource_id'] ?>"
                   target="_blank"
                   class="mt-0.5 inline-block font-semibold text-primary hover:opacity-80">
                    <?= $e($assignment['resource_title']) ?>
                </a>
                <?php if (!empty($assignment['resource_authors'])): ?>
                    <p class="text-xs text-on-surface-muted"><?= $e($assignment['resource_authors']) ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($assignment['description'])): ?>
                <div>
                    <p class="label-sm">Instrucciones</p>
                    <p class="mt-0.5 text-on-surface-muted"><?= $e($assignment['description']) ?></p>
                </div>
            <?php endif; ?>
            <div>
                <p class="label-sm">Creada el</p>
                <p class="mt-0.5 text-on-surface-muted">
                    <?= $e((new DateTime((string) $assignment['created_at']))->format('d/m/Y H:i')) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Tabla de estudiantes -->
    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="flex items-center justify-between border-b border-outline-variant/60 px-5 py-4">
            <h2 class="headline-md text-on-surface">Estudiantes</h2>
            <?php if ($total > 0): ?>
                <span class="text-sm text-on-surface-muted"><?= $total ?> estudiante<?= $total !== 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($students)): ?>
            <div class="p-8 text-center text-on-surface-subtle">
                <p class="body-md">Este grupo no tiene estudiantes registrados en la asignación.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Estudiante</th>
                            <th class="px-5 py-3 font-semibold">Carnet</th>
                            <th class="px-5 py-3 font-semibold">Estado</th>
                            <th class="px-5 py-3 font-semibold">Préstamo</th>
                            <th class="px-5 py-3 font-semibold">Completada</th>
                            <th class="px-5 py-3 font-semibold">Notas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/50">
                        <?php foreach ($students as $student): ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors">
                                <td class="px-5 py-3.5">
                                    <p class="font-medium text-on-surface"><?= $e($student['name']) ?></p>
                                    <p class="text-xs text-on-surface-muted"><?= $e($student['email']) ?></p>
                                </td>
                                <td class="px-5 py-3.5 text-on-surface-muted">
                                    <?= $e($student['user_number'] ?? '—') ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass((string) ($student['status'] ?? '')) ?>">
                                        <?= $e($statusLabel((string) ($student['status'] ?? ''))) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <?php if ((int) ($student['has_loan'] ?? 0) > 0): ?>
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                            <?= Icons::check('w-3.5 h-3.5') ?> Sí
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-on-surface-subtle">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 text-on-surface-muted text-xs">
                                    <?= !empty($student['completed_at'])
                                        ? $e((new DateTime((string) $student['completed_at']))->format('d/m/Y H:i'))
                                        : '—' ?>
                                </td>
                                <td class="px-5 py-3.5 text-on-surface-muted max-w-xs truncate">
                                    <?= $e($student['notes'] ?? '') ?: '<span class="text-on-surface-subtle">—</span>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</section>
