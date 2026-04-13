<?php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$today = new DateTime('today');
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Docente</p>
            <h1 class="headline-lg text-on-surface">Asignaciones</h1>
            <p class="body-md mt-1">Seguimiento de lecturas asignadas por grupo, progreso y fecha de entrega.</p>
        </div>
        <a href="<?= BASE_URL ?>/teacher/assignments/create" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors inline-flex items-center gap-2">
            <?= Icons::plus('w-4 h-4') ?> Nueva asignación
        </a>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Asignaciones activas</p>
            <p class="mt-2 text-2xl font-bold text-on-surface font-display"><?= (int) $summary['active_assignments'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Grupos vinculados</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 font-display"><?= (int) $summary['groups'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Estudiantes asignados</p>
            <p class="mt-2 text-2xl font-bold text-violet-700 font-display"><?= (int) $summary['assigned_students'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Entregas completadas</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= (int) $summary['completed_submissions'] ?></p>
        </article>
    </div>

    <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="headline-md text-on-surface">Listado de asignaciones</h2>
            <a href="<?= BASE_URL ?>/teacher" class="text-sm font-semibold text-primary hover:opacity-80">Volver al panel</a>
        </div>

        <?php if (empty($assignments)): ?>
            <div class="rounded-2xl border border-dashed border-outline-variant/70 bg-surface-container-low p-8 text-center">
                <p class="headline-md text-on-surface">Todavía no hay asignaciones activas</p>
                <p class="body-md mt-2">Crea tu primera lectura asignada para empezar a dar seguimiento a tus grupos.</p>
                <a href="<?= BASE_URL ?>/teacher/assignments/create" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                    <?= Icons::plus('w-4 h-4') ?> Crear asignación
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($assignments as $assignment): ?>
                    <?php
                    $dueDate = new DateTime((string) $assignment['due_date']);
                    $assigned = (int) ($assignment['assigned_students'] ?? 0);
                    $pending = (int) ($assignment['pending_students'] ?? 0);
                    $inProgress = (int) ($assignment['in_progress_students'] ?? 0);
                    $completed = (int) ($assignment['completed_students'] ?? 0);
                    $progress = $assigned > 0 ? (int) round(($completed / $assigned) * 100) : 0;
                    $isOverdue = $dueDate < $today && $completed < $assigned;
                    $badgeClass = $isOverdue
                        ? 'bg-red-100 text-red-700'
                        : ($progress === 100 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700');
                    $badgeLabel = $isOverdue
                        ? 'Vencida'
                        : ($progress === 100 ? 'Completada' : 'En curso');
                    ?>
                    <article class="rounded-2xl border border-outline-variant/50 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="headline-md text-on-surface"><?= $e($assignment['title']) ?></h3>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $badgeClass ?>">
                                        <?= $e($badgeLabel) ?>
                                    </span>
                                </div>
                                <p class="label-md mt-1"><?= $e($assignment['group_name']) ?> · <?= $e($assignment['school_year']) ?> · Recurso: <?= $e($assignment['book_title']) ?></p>
                                <p class="body-md mt-3"><?= $e($assignment['description'] ?: 'Sin descripción registrada.') ?></p>
                            </div>
                            <div class="rounded-xl bg-surface-container-low px-4 py-3 text-sm">
                                <p class="label-sm">Entrega</p>
                                <p class="mt-1 font-semibold text-on-surface"><?= $e($dueDate->format('d/m/Y')) ?></p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-4">
                            <div class="rounded-xl bg-surface-container-low px-3.5 py-3">
                                <p class="label-sm">Asignados</p>
                                <p class="mt-1 text-lg font-bold text-on-surface font-display"><?= $assigned ?></p>
                            </div>
                            <div class="rounded-xl bg-surface-container-low px-3.5 py-3">
                                <p class="label-sm">Pendientes</p>
                                <p class="mt-1 text-lg font-bold text-amber-700 font-display"><?= $pending ?></p>
                            </div>
                            <div class="rounded-xl bg-surface-container-low px-3.5 py-3">
                                <p class="label-sm">En progreso</p>
                                <p class="mt-1 text-lg font-bold text-blue-700 font-display"><?= $inProgress ?></p>
                            </div>
                            <div class="rounded-xl bg-surface-container-low px-3.5 py-3">
                                <p class="label-sm">Completadas</p>
                                <p class="mt-1 text-lg font-bold text-emerald-700 font-display"><?= $completed ?></p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="mb-1 flex items-center justify-between text-xs text-on-surface-subtle">
                                <span>Progreso general</span>
                                <span><?= $progress ?>%</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-surface-container-low">
                                <div class="h-full gradient-scholar" style="width: <?= $progress ?>%"></div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="<?= BASE_URL ?>/teacher/assignments/<?= (int) $assignment['id'] ?>" class="rounded-xl border border-outline-variant bg-white px-3.5 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                                Ver detalle
                            </a>
                            <a href="<?= BASE_URL ?>/teacher/groups/<?= (int) $assignment['group_id'] ?>" class="rounded-xl border border-outline-variant bg-white px-3.5 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                                Abrir grupo
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
