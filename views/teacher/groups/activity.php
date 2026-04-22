<?php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$statusLabel = [
    'active'   => ['Activo',    'bg-green-50 text-green-700'],
    'returned' => ['Devuelto',  'bg-blue-50 text-blue-700'],
    'overdue'  => ['Vencido',   'bg-red-50 text-red-700'],
    'lost'     => ['Perdido',   'bg-gray-100 text-gray-600'],
    'renewed'  => ['Renovado',  'bg-yellow-50 text-yellow-700'],
];
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="<?= BASE_URL ?>/teacher/groups/<?= (int)$group['id'] ?>" class="label-sm hover:text-primary transition-colors">← <?= $e($group['name']) ?></a>
            <h1 class="headline-lg mt-1 text-on-surface">Actividad del grupo</h1>
            <p class="body-md mt-1">Historial de préstamos de todos los estudiantes del grupo.</p>
        </div>
    </div>

    <div class="rounded-2xl border border-outline-variant/60 bg-white shadow-ambient overflow-hidden">
        <?php if (empty($activity)): ?>
            <div class="p-10 text-center">
                <p class="headline-md text-on-surface">Sin actividad registrada</p>
                <p class="body-md mt-2">Ningún estudiante del grupo tiene préstamos todavía.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-outline-variant/40 bg-surface-container-low">
                        <tr>
                            <th class="px-4 py-3 text-left label-sm font-semibold text-on-surface-muted">Estudiante</th>
                            <th class="px-4 py-3 text-left label-sm font-semibold text-on-surface-muted">Recurso</th>
                            <th class="px-4 py-3 text-left label-sm font-semibold text-on-surface-muted">Estado</th>
                            <th class="px-4 py-3 text-left label-sm font-semibold text-on-surface-muted">Prestado</th>
                            <th class="px-4 py-3 text-left label-sm font-semibold text-on-surface-muted">Vence</th>
                            <th class="px-4 py-3 text-left label-sm font-semibold text-on-surface-muted">Devuelto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        <?php foreach ($activity as $row): ?>
                            <?php
                            $s  = $row['status'] ?? 'active';
                            [$label, $badgeClass] = $statusLabel[$s] ?? [$s, 'bg-gray-100 text-gray-600'];
                            ?>
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="px-4 py-3">
                                    <a href="<?= BASE_URL ?>/teacher/groups/<?= (int)$group['id'] ?>/student/<?= (int)$row['student_id'] ?>"
                                       class="font-medium text-primary hover:underline">
                                        <?= $e($row['student_name']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-on-surface"><?= $e($row['book_title']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $badgeClass ?>">
                                        <?= $e($label) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-on-surface-muted"><?= $row['loan_at'] ? (new DateTime($row['loan_at']))->format('d/m/Y') : '—' ?></td>
                                <td class="px-4 py-3 text-on-surface-muted"><?= $row['due_at'] ? (new DateTime($row['due_at']))->format('d/m/Y') : '—' ?></td>
                                <td class="px-4 py-3 text-on-surface-muted"><?= $row['returned_at'] ? (new DateTime($row['returned_at']))->format('d/m/Y') : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
