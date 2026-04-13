<?php
// views/account/loans.php — rebuilt
declare(strict_types=1);

$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$statusBadge = static function (string $status): array {
    return match ($status) {
        'active'   => ['Activo',   'bg-blue-100 text-blue-700'],
        'overdue'  => ['Vencido',  'bg-amber-100 text-amber-700'],
        'returned' => ['Devuelto', 'bg-emerald-100 text-emerald-700'],
        'lost'     => ['Perdido',  'bg-red-100 text-red-700'],
        default    => [ucfirst($status), 'bg-slate-100 text-slate-700'],
    };
};

$userName  = $e($auth_user['name'] ?? 'Usuario');
$userId    = (int) ($auth_user['id'] ?? 0);
$userEmail = $e($auth_user['email'] ?? '');
$total     = count($loans ?? []);
$maxRenewals = (int) ($settings['max_renewals'] ?? 3);
$flashSuccess = \Core\Session::getFlash('success');
$flashError   = \Core\Session::getFlash('error');
$flashInfo    = \Core\Session::getFlash('info');
?>

<section class="p-6 lg:p-8 max-w-6xl mx-auto">

    <!-- Header -->
    <div class="mb-7">

        <?php if ($flashSuccess): ?>
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= $e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $e($flashError) ?></div>
        <?php endif; ?>
        <?php if ($flashInfo): ?>
            <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800"><?= $e($flashInfo) ?></div>
        <?php endif; ?>

        <p class="label-sm text-on-surface-subtle">Mi zona</p>
        <h1 class="headline-lg text-on-surface">Mis préstamos</h1>
        <p class="body-md mt-1 text-on-surface-muted">Consulta tus préstamos activos, vencidos y el historial de devoluciones.</p>
    </div>

    <!-- Summary cards -->
    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php
        $cards = [
            ['label' => 'Activos',   'key' => 'active',   'color' => 'text-blue-700'],
            ['label' => 'Vencidos',  'key' => 'overdue',  'color' => 'text-amber-700'],
            ['label' => 'Devueltos', 'key' => 'returned', 'color' => 'text-emerald-700'],
            ['label' => 'Perdidos',  'key' => 'lost',     'color' => 'text-red-700'],
        ];
        foreach ($cards as $card): ?>
            <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
                <p class="label-md text-on-surface-subtle"><?= $card['label'] ?></p>
                <p class="mt-2 text-2xl font-bold font-display <?= $card['color'] ?>"><?= (int) ($summary[$card['key']] ?? 0) ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- Loans table -->
    <?php if ($total === 0): ?>
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-10 text-center shadow-ambient">
            <svg class="mx-auto mb-3 h-12 w-12 text-on-surface-subtle/40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
            </svg>
            <p class="text-lg font-semibold text-on-surface">No tienes préstamos registrados</p>
            <p class="mt-1 text-sm text-on-surface-muted">Cuando solicites un préstamo aparecerá aquí.</p>
        </div>
    <?php else: ?>
        <!-- Mobile cards (visible < lg) -->
        <div class="space-y-4 lg:hidden">
            <?php foreach ($loans as $loan):
                [$badgeLabel, $badgeClass] = $statusBadge((string) ($loan['status'] ?? ''));
                $loanDate = (new DateTime($loan['loan_at']))->format('d/m/Y');
                $dueDate  = (new DateTime($loan['due_at']))->format('d/m/Y');
            ?>
                <article class="rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-on-surface truncate"><?= $e($loan['book_title'] ?? 'Sin título') ?></p>
                            <p class="text-xs text-on-surface-subtle"><?= $e($loan['book_authors'] ?? '') ?></p>
                        </div>
                        <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold <?= $badgeClass ?>">
                            <?= $e($badgeLabel) ?>
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-on-surface-muted">
                        <div><span class="font-medium text-on-surface-subtle">Prestado:</span> <?= $e($loanDate) ?></div>
                        <div><span class="font-medium text-on-surface-subtle">Vence:</span> <?= $e($dueDate) ?></div>
                        <div><span class="font-medium text-on-surface-subtle">Renovaciones:</span> <?= (int) ($loan['renewals_count'] ?? 0) ?></div>
                        <?php if (!empty($loan['returned_at'])): ?>
                            <div><span class="font-medium text-on-surface-subtle">Devuelto:</span> <?= $e((new DateTime($loan['returned_at']))->format('d/m/Y')) ?></div>
                        <?php endif; ?>
                    </div>
                        <?php if (in_array((string) ($loan['status'] ?? ''), ['active', 'overdue'], true) && (int) ($loan['renewals_count'] ?? 0) < $maxRenewals): ?>
                            <form method="POST" action="<?= BASE_URL ?>/account/loans/<?= (int) $loan['id'] ?>/renew" class="mt-3">
                                <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                <button type="submit" class="w-full rounded-lg border border-primary/40 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors">
                                    Renovar préstamo
                                </button>
                            </form>
                        <?php elseif (in_array((string) ($loan['status'] ?? ''), ['active', 'overdue'], true)): ?>
                            <p class="mt-3 text-center text-xs text-on-surface-muted italic">Renovaciones agotadas (<?= $maxRenewals ?>)</p>
                        <?php endif; ?>
                    <?php if (!empty($loan['notes'])): ?>
                        <p class="mt-2 text-xs text-on-surface-muted italic"><?= $e($loan['notes']) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Desktop table (visible >= lg) -->
        <div class="hidden lg:block overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Recurso</th>
                            <th class="px-4 py-3 font-semibold">Prestado</th>
                            <th class="px-4 py-3 font-semibold">Vence</th>
                            <th class="px-4 py-3 font-semibold">Devuelto</th>
                            <th class="px-4 py-3 font-semibold">Renovaciones</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                            <th class="px-4 py-3 font-semibold">Notas</th>
                                <th class="px-4 py-3 font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/50 text-sm">
                        <?php foreach ($loans as $loan):
                            [$badgeLabel, $badgeClass] = $statusBadge((string) ($loan['status'] ?? ''));
                        ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors">
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-on-surface"><?= $e($loan['book_title'] ?? 'Sin título') ?></p>
                                    <p class="text-xs text-on-surface-subtle"><?= $e($loan['book_authors'] ?? '') ?></p>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-on-surface-muted"><?= $e((new DateTime($loan['loan_at']))->format('d/m/Y H:i')) ?></td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-on-surface-muted"><?= $e((new DateTime($loan['due_at']))->format('d/m/Y H:i')) ?></td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-on-surface-muted">
                                    <?= !empty($loan['returned_at']) ? $e((new DateTime($loan['returned_at']))->format('d/m/Y H:i')) : '-' ?>
                                </td>
                                <td class="px-4 py-3.5 text-center text-on-surface-muted"><?= (int) ($loan['renewals_count'] ?? 0) ?></td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $badgeClass ?>">
                                        <?= $e($badgeLabel) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted max-w-[200px] truncate"><?= $e($loan['notes'] ?? '-') ?></td>
                                    <td class="px-4 py-3.5">
                                        <?php if (in_array((string) ($loan['status'] ?? ''), ['active', 'overdue'], true) && (int) ($loan['renewals_count'] ?? 0) < $maxRenewals): ?>
                                            <form method="POST" action="<?= BASE_URL ?>/account/loans/<?= (int) $loan['id'] ?>/renew">
                                                <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                                <button type="submit" class="rounded-lg border border-primary/40 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors whitespace-nowrap">
                                                    Renovar
                                                </button>
                                            </form>
                                        <?php elseif (in_array((string) ($loan['status'] ?? ''), ['active', 'overdue'], true)): ?>
                                            <span class="text-xs text-on-surface-muted italic">Máx. alcanzado</span>
                                        <?php else: ?>
                                            <span class="text-xs text-on-surface-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</section>
