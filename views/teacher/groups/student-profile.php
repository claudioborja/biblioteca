<?php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$loanStatus = [
    'active'   => ['Activo',   'bg-green-50 text-green-700'],
    'returned' => ['Devuelto', 'bg-blue-50 text-blue-700'],
    'overdue'  => ['Vencido',  'bg-red-50 text-red-700'],
    'lost'     => ['Perdido',  'bg-gray-100 text-gray-600'],
    'renewed'  => ['Renovado', 'bg-yellow-50 text-yellow-700'],
];
$progressStatus = [
    'pending'     => ['Pendiente',   'bg-gray-100 text-gray-600'],
    'in_progress' => ['En progreso', 'bg-blue-50 text-blue-700'],
    'completed'   => ['Completado',  'bg-green-50 text-green-700'],
];
?>

<section class="p-6 lg:p-8">
    <div class="mb-7">
        <a href="<?= BASE_URL ?>/teacher/groups/<?= (int)$group['id'] ?>" class="label-sm hover:text-primary transition-colors">← <?= $e($group['name']) ?></a>
        <h1 class="headline-lg mt-1 text-on-surface"><?= $e($student['name']) ?></h1>
        <p class="body-md mt-1">
            <?= $e($student['email']) ?>
            <?php if ($student['user_number']): ?>
                · N.° <?= $e($student['user_number']) ?>
            <?php endif; ?>
        </p>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <!-- Loans -->
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <h2 class="headline-md mb-4 text-on-surface">Préstamos</h2>

            <?php if (empty($loans)): ?>
                <p class="body-md">El estudiante no tiene préstamos registrados.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($loans as $loan): ?>
                        <?php
                        $s  = $loan['status'] ?? 'active';
                        [$label, $badgeClass] = $loanStatus[$s] ?? [$s, 'bg-gray-100 text-gray-600'];
                        ?>
                        <div class="flex items-start justify-between rounded-xl border border-outline-variant/40 p-3 gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-on-surface"><?= $e($loan['book_title']) ?></p>
                                <p class="mt-0.5 text-xs text-on-surface-muted">
                                    <?= $loan['loan_at'] ? (new DateTime($loan['loan_at']))->format('d/m/Y') : '—' ?>
                                    → <?= $loan['due_at'] ? (new DateTime($loan['due_at']))->format('d/m/Y') : '—' ?>
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $badgeClass ?>">
                                <?= $e($label) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Assignments -->
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <h2 class="headline-md mb-4 text-on-surface">Asignaciones de este grupo</h2>

            <?php if (empty($assignments)): ?>
                <p class="body-md">El estudiante no tiene asignaciones en este grupo.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($assignments as $a): ?>
                        <?php
                        $ps = $a['progress_status'] ?? 'pending';
                        [$plabel, $pbadge] = $progressStatus[$ps] ?? [$ps, 'bg-gray-100 text-gray-600'];
                        ?>
                        <div class="flex items-start justify-between rounded-xl border border-outline-variant/40 p-3 gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-on-surface"><?= $e($a['title']) ?></p>
                                <p class="mt-0.5 text-xs text-on-surface-muted"><?= $e($a['book_title']) ?> · Entrega <?= $a['due_date'] ? (new DateTime($a['due_date']))->format('d/m/Y') : '—' ?></p>
                                <?php if ($a['completed_at']): ?>
                                    <p class="mt-0.5 text-xs text-green-600">Completado el <?= (new DateTime($a['completed_at']))->format('d/m/Y') ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $pbadge ?>">
                                <?= $e($plabel) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
