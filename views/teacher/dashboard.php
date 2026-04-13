<?php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$statusClass = static function (string $status): string {
    return match ($status) {
        'overdue' => 'bg-red-100 text-red-700',
        'returned' => 'bg-emerald-100 text-emerald-700',
        'active' => 'bg-blue-100 text-blue-700',
        'lost' => 'bg-amber-100 text-amber-700',
        default => 'bg-slate-100 text-slate-700',
    };
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'overdue' => 'Vencido',
        'returned' => 'Devuelto',
        'active' => 'Activo',
        'lost' => 'Perdido',
        default => ucfirst($status),
    };
};
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Panel docente</p>
            <h1 class="headline-lg text-on-surface">Seguimiento de lectura</h1>
            <p class="body-md mt-1">Resumen de grupos, asignaciones y actividad reciente de tus estudiantes.</p>
        </div>
        <div class="rounded-xl bg-blue-50 px-3.5 py-2 text-sm font-semibold text-blue-700">
            Vista docente
        </div>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Grupos activos</p>
            <p class="mt-2 text-2xl font-bold text-on-surface font-display"><?= (int) $stats['groups'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Estudiantes</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 font-display"><?= (int) $stats['students'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Asignaciones</p>
            <p class="mt-2 text-2xl font-bold text-violet-700 font-display"><?= (int) $stats['assignments'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Seguimientos pendientes</p>
            <p class="mt-2 text-2xl font-bold text-amber-700 font-display"><?= (int) $stats['pending_reviews'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Sugerencias</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= (int) $stats['suggestions'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Estudiantes con mora</p>
            <p class="mt-2 text-2xl font-bold text-red-700 font-display"><?= (int) $stats['overdue_students'] ?></p>
        </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient xl:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="headline-md text-on-surface">Tus grupos</h2>
                <a href="<?= BASE_URL ?>/teacher/groups" class="text-sm font-semibold text-primary hover:opacity-80">Ver todos</a>
            </div>

            <?php if (empty($groups)): ?>
                <p class="body-md">Todavía no tienes grupos activos registrados.</p>
            <?php else: ?>
                <div class="grid gap-4 md:grid-cols-2">
                    <?php foreach ($groups as $group): ?>
                        <article class="rounded-2xl border border-outline-variant/50 bg-surface-container-lowest p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="title-sm text-on-surface"><?= $e($group['name']) ?></h3>
                                    <p class="label-md mt-1">Periodo: <?= $e($group['school_year']) ?></p>
                                </div>
                                <span class="rounded-full bg-primary/8 px-2.5 py-1 text-xs font-semibold text-primary">
                                    <?= (int) $group['students_count'] ?> estudiantes
                                </span>
                            </div>
                            <p class="body-md mt-3"><?= $e($group['description'] ?: 'Sin descripción registrada.') ?></p>
                            <div class="mt-4 flex items-center justify-between text-xs text-on-surface-subtle">
                                <span><?= (int) $group['assignments_count'] ?> asignaciones</span>
                                <a href="<?= BASE_URL ?>/teacher/groups/<?= (int) $group['id'] ?>" class="font-semibold text-primary hover:opacity-80">Abrir grupo</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <h2 class="headline-md text-on-surface mb-4">Acceso rápido</h2>
            <div class="space-y-2.5">
                <a href="<?= BASE_URL ?>/teacher/groups/create" class="flex items-center justify-between rounded-xl border border-outline-variant/60 px-3.5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                    <span class="inline-flex items-center gap-2"><?= Icons::plus('w-4 h-4') ?> Crear grupo</span>
                    <span class="text-on-surface-subtle">></span>
                </a>
                <a href="<?= BASE_URL ?>/teacher/assignments/create" class="flex items-center justify-between rounded-xl border border-outline-variant/60 px-3.5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                    <span class="inline-flex items-center gap-2"><?= Icons::plus('w-4 h-4') ?> Nueva asignación</span>
                    <span class="text-on-surface-subtle">></span>
                </a>
                <a href="<?= BASE_URL ?>/teacher/suggestions/create" class="flex items-center justify-between rounded-xl border border-outline-variant/60 px-3.5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                    <span class="inline-flex items-center gap-2"><?= Icons::plus('w-4 h-4') ?> Sugerir recurso</span>
                    <span class="text-on-surface-subtle">></span>
                </a>
                <a href="<?= BASE_URL ?>/account/assignments" class="flex items-center justify-between rounded-xl border border-outline-variant/60 px-3.5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                    <span class="inline-flex items-center gap-2"><?= Icons::eye('w-4 h-4') ?> Ver mi zona</span>
                    <span class="text-on-surface-subtle">></span>
                </a>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="headline-md text-on-surface">Próximas asignaciones</h2>
                <a href="<?= BASE_URL ?>/teacher/assignments" class="text-sm font-semibold text-primary hover:opacity-80">Ver todas</a>
            </div>

            <?php if (empty($assignments)): ?>
                <p class="body-md">No hay asignaciones activas por el momento.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($assignments as $assignment): ?>
                        <?php
                        $assigned = (int) ($assignment['assigned_students'] ?? 0);
                        $completed = (int) ($assignment['completed_students'] ?? 0);
                        $progress = $assigned > 0 ? (int) round(($completed / $assigned) * 100) : 0;
                        ?>
                        <article class="rounded-xl border border-outline-variant/50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="title-sm text-on-surface"><?= $e($assignment['title']) ?></h3>
                                    <p class="label-md mt-1"><?= $e($assignment['group_name']) ?> · <?= $e($assignment['book_title']) ?></p>
                                </div>
                                <span class="rounded-full bg-surface-container px-2.5 py-1 text-xs font-semibold text-on-surface-muted">
                                    <?= $e((new DateTime($assignment['due_date']))->format('d/m/Y')) ?>
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-xs text-on-surface-subtle">
                                <span><?= $completed ?>/<?= $assigned ?> completadas</span>
                                <span><?= $progress ?>%</span>
                            </div>
                            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-surface-container-low">
                                <div class="h-full gradient-scholar" style="width: <?= $progress ?>%"></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="headline-md text-on-surface">Actividad reciente</h2>
                <span class="text-xs font-semibold text-on-surface-subtle">Préstamos de tus grupos</span>
            </div>

            <?php if (empty($recent_activity)): ?>
                <p class="body-md">Aún no hay actividad registrada para tus grupos.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recent_activity as $item): ?>
                        <article class="flex items-start justify-between gap-3 rounded-xl border border-outline-variant/50 p-4">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-on-surface"><?= $e($item['student_name']) ?></p>
                                <p class="text-sm text-on-surface-muted truncate"><?= $e($item['book_title']) ?></p>
                                <p class="mt-1 text-xs text-on-surface-subtle">
                                    Prestado: <?= $e((new DateTime($item['loan_at']))->format('d/m/Y H:i')) ?>
                                    · Vence: <?= $e((new DateTime($item['due_at']))->format('d/m/Y H:i')) ?>
                                </p>
                            </div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass((string) $item['status']) ?>">
                                <?= $e($statusLabel((string) $item['status'])) ?>
                            </span>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
