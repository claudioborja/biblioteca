<?php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="<?= BASE_URL ?>/teacher/groups/<?= (int)$group['id'] ?>" class="label-sm hover:text-primary transition-colors">← <?= $e($group['name']) ?></a>
            <h1 class="headline-lg mt-1 text-on-surface">Reporte del grupo</h1>
            <p class="body-md mt-1">Periodo: <?= $e($group['school_year']) ?> · Generado el <?= date('d/m/Y H:i') ?></p>
        </div>
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-sm font-semibold text-on-surface shadow-ambient hover:bg-surface-container-low transition-colors print:hidden">
            Imprimir reporte
        </button>
    </div>

    <!-- Summary cards -->
    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Estudiantes</p>
            <p class="mt-2 text-2xl font-bold text-on-surface font-display"><?= (int)$group['students_count'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Asignaciones activas</p>
            <p class="mt-2 text-2xl font-bold text-violet-700 font-display"><?= (int)$group['assignments_count'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Con préstamos activos</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 font-display">
                <?= count(array_filter($students, fn($s) => (int)$s['active_loans'] > 0)) ?>
            </p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Con préstamos vencidos</p>
            <p class="mt-2 text-2xl font-bold text-red-700 font-display">
                <?= count(array_filter($students, fn($s) => (int)$s['overdue_loans'] > 0)) ?>
            </p>
        </article>
    </div>

    <?php if ($group['description']): ?>
        <div class="mb-6 rounded-xl border border-outline-variant/40 bg-surface-container-low p-4 text-sm text-on-surface-muted">
            <?= $e($group['description']) ?>
        </div>
    <?php endif; ?>

    <!-- Students table -->
    <div class="rounded-2xl border border-outline-variant/60 bg-white shadow-ambient overflow-hidden">
        <div class="border-b border-outline-variant/40 px-5 py-4">
            <h2 class="headline-md text-on-surface">Detalle por estudiante</h2>
        </div>

        <?php if (empty($students)): ?>
            <div class="p-8 text-center">
                <p class="body-md">No hay estudiantes registrados en este grupo.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-outline-variant/40 bg-surface-container-low">
                        <tr>
                            <th class="px-4 py-3 text-left label-sm font-semibold text-on-surface-muted">Estudiante</th>
                            <th class="px-4 py-3 text-center label-sm font-semibold text-on-surface-muted">N.° usuario</th>
                            <th class="px-4 py-3 text-center label-sm font-semibold text-on-surface-muted">Préstamos activos</th>
                            <th class="px-4 py-3 text-center label-sm font-semibold text-on-surface-muted">Vencidos</th>
                            <th class="px-4 py-3 text-center label-sm font-semibold text-on-surface-muted">Asignaciones completadas</th>
                            <th class="px-4 py-3 text-left label-sm font-semibold text-on-surface-muted print:hidden">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        <?php foreach ($students as $student): ?>
                            <tr class="hover:bg-surface-container-low/40 transition-colors">
                                <td class="px-4 py-3 font-medium text-on-surface"><?= $e($student['name']) ?></td>
                                <td class="px-4 py-3 text-center text-on-surface-muted"><?= $e($student['user_number'] ?? '—') ?></td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ((int)$student['active_loans'] > 0): ?>
                                        <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700"><?= (int)$student['active_loans'] ?></span>
                                    <?php else: ?>
                                        <span class="text-on-surface-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ((int)$student['overdue_loans'] > 0): ?>
                                        <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700"><?= (int)$student['overdue_loans'] ?></span>
                                    <?php else: ?>
                                        <span class="text-on-surface-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                    $total = (int)$student['total_assignments'];
                                    $done  = (int)$student['completed_assignments'];
                                    ?>
                                    <span class="text-on-surface"><?= $done ?>/<?= $total ?></span>
                                    <?php if ($total > 0): ?>
                                        <span class="ml-1 text-xs text-on-surface-muted">(<?= $total > 0 ? round(($done/$total)*100) : 0 ?>%)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 print:hidden">
                                    <a href="<?= BASE_URL ?>/teacher/groups/<?= (int)$group['id'] ?>/student/<?= (int)$student['id'] ?>"
                                       class="text-xs font-semibold text-primary hover:underline">Ver perfil</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
@media print {
    aside, nav, header, .print\\:hidden { display: none !important; }
    section { padding: 0 !important; }
}
</style>
