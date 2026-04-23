<?php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<section class="p-6 lg:p-8">
    <?php $flashSuccess = \Core\Session::getFlash('success'); if ($flashSuccess): ?>
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= $e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php $flashError = \Core\Session::getFlash('error'); if ($flashError): ?>
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $e($flashError) ?></div>
    <?php endif; ?>
    <?php $flashInfo = \Core\Session::getFlash('info'); if ($flashInfo): ?>
        <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800"><?= $e($flashInfo) ?></div>
    <?php endif; ?>

    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Grupo docente</p>
            <h1 class="headline-lg text-on-surface"><?= $e($group['name']) ?></h1>
            <p class="body-md mt-1">
                <?= $e($group['description'] ?: 'Sin descripción registrada.') ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-xl bg-blue-50 px-3.5 py-2 text-sm font-semibold text-blue-700">
                Periodo: <?= $e($group['school_year']) ?>
            </span>
            <a href="<?= BASE_URL ?>/teacher/groups" class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                Volver
            </a>
        </div>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Estudiantes</p>
            <p class="mt-2 text-2xl font-bold text-on-surface font-display"><?= (int) $summary['students'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Asignaciones activas</p>
            <p class="mt-2 text-2xl font-bold text-violet-700 font-display"><?= (int) $summary['assignments'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Préstamos activos</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 font-display"><?= (int) $summary['active_loans'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Préstamos vencidos</p>
            <p class="mt-2 text-2xl font-bold text-red-700 font-display"><?= (int) $summary['overdue_loans'] ?></p>
        </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="headline-md text-on-surface">Asignaciones del grupo</h2>
                <a href="<?= BASE_URL ?>/teacher/assignments/create" class="text-sm font-semibold text-primary hover:opacity-80">Nueva asignación</a>
            </div>

            <?php if (empty($assignments)): ?>
                <p class="body-md">Este grupo todavía no tiene asignaciones activas.</p>
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
                                    <p class="label-md mt-1"><?= $e($assignment['book_title']) ?> · Entrega <?= $e((new DateTime($assignment['due_date']))->format('d/m/Y')) ?></p>
                                </div>
                                <span class="rounded-full bg-surface-container px-2.5 py-1 text-xs font-semibold text-on-surface-muted">
                                    <?= $progress ?>%
                                </span>
                            </div>
                            <p class="body-md mt-3"><?= $e($assignment['description'] ?: 'Sin descripción registrada.') ?></p>
                            <div class="mt-3 flex items-center justify-between text-xs text-on-surface-subtle">
                                <span><?= $completed ?>/<?= $assigned ?> completadas</span>
                                <span><?= (int) $assignment['pending_students'] ?> pendientes · <?= (int) $assignment['in_progress_students'] ?> en progreso</span>
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
                <h2 class="headline-md text-on-surface">Estudiantes del grupo</h2>
                <span class="text-xs font-semibold text-on-surface-subtle">Estado académico y bibliotecario</span>
            </div>

            <form method="POST" action="<?= BASE_URL ?>/teacher/groups/<?= (int) $group['id'] ?>/students" class="mb-4 rounded-xl border border-outline-variant/50 bg-surface-container-lowest p-3">
                <input type="hidden" name="_csrf_token" value="<?= $e((string) ($csrf ?? '')) ?>">
                <label for="student_id" class="label-sm block mb-1.5">Agregar estudiante al grupo</label>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <select id="student_id" name="student_id" class="flex-1 rounded-xl border border-outline-variant bg-white px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <option value="">Selecciona un estudiante...</option>
                        <?php foreach (($available_students ?? []) as $candidate): ?>
                            <option value="<?= (int) $candidate['id'] ?>">
                                <?= $e($candidate['name']) ?><?= !empty($candidate['user_number']) ? (' · ' . $e($candidate['user_number'])) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="rounded-xl gradient-scholar px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 transition-opacity">
                        Agregar
                    </button>
                </div>
                <?php if (empty($available_students ?? [])): ?>
                    <p class="mt-2 text-xs text-on-surface-subtle">No hay estudiantes disponibles para agregar.</p>
                <?php endif; ?>
            </form>

            <?php if (empty($students)): ?>
                <p class="body-md">No hay estudiantes registrados en este grupo.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($students as $student): ?>
                        <?php
                        $studentStatusClass = match ((string) $student['status']) {
                            'active' => 'bg-emerald-100 text-emerald-700',
                            'suspended', 'blocked', 'inactive' => 'bg-red-100 text-red-700',
                            default => 'bg-slate-100 text-slate-700',
                        };
                        ?>
                        <article class="rounded-xl border border-outline-variant/50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="title-sm text-on-surface"><?= $e($student['name']) ?></h3>
                                    <p class="label-md mt-1"><?= $e($student['email']) ?></p>
                                    <p class="label-md">Membresía: <?= $e($student['user_number'] ?: '—') ?></p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $studentStatusClass ?>">
                                    <?= $e(ucfirst((string) $student['status'])) ?>
                                </span>
                            </div>

                            <div class="mt-2 flex justify-end">
                                <form method="POST" action="<?= BASE_URL ?>/teacher/groups/<?= (int) $group['id'] ?>/students/<?= (int) $student['id'] ?>/remove" onsubmit="return confirm('¿Quitar este estudiante del grupo?');">
                                    <input type="hidden" name="_csrf_token" value="<?= $e((string) ($csrf ?? '')) ?>">
                                    <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition-colors">
                                        Quitar del grupo
                                    </button>
                                </form>
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                                <div class="rounded-xl bg-surface-container-low px-3 py-2">
                                    <p class="label-sm">Activos</p>
                                    <p class="mt-1 text-lg font-bold text-blue-700 font-display"><?= (int) $student['active_loans'] ?></p>
                                </div>
                                <div class="rounded-xl bg-surface-container-low px-3 py-2">
                                    <p class="label-sm">Vencidos</p>
                                    <p class="mt-1 text-lg font-bold text-red-700 font-display"><?= (int) $student['overdue_loans'] ?></p>
                                </div>
                                <div class="rounded-xl bg-surface-container-low px-3 py-2">
                                    <p class="label-sm">Multas</p>
                                    <p class="mt-1 text-lg font-bold text-amber-700 font-display">$<?= number_format((float) $student['pending_fines'], 2) ?></p>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
