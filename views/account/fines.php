<?php
// views/account/fines.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$statusClass = static function (string $status): string {
    return match ($status) {
        'pending' => 'bg-amber-100 text-amber-700',
        'partially_paid' => 'bg-blue-100 text-blue-700',
        'paid' => 'bg-emerald-100 text-emerald-700',
        'waived' => 'bg-violet-100 text-violet-700',
        default => 'bg-slate-100 text-slate-700',
    };
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'pending' => 'Pendiente',
        'partially_paid' => 'Pago parcial',
        'paid' => 'Pagada',
        'waived' => 'Condonada',
        default => ucfirst($status),
    };
};

$reasonLabel = static function (string $reason): string {
    return match ($reason) {
        'overdue' => 'Retraso',
        'damage' => 'Dano',
        'loss' => 'Perdida',
        default => ucfirst($reason),
    };
};

$fmtMoney = static function (float $amount, string $currency): string {
    return $currency . number_format($amount, 2, '.', ',');
};
?>

<section class="p-6 lg:p-8">
    <div class="mb-7">
        <p class="label-sm">Mi zona</p>
        <h1 class="headline-lg text-on-surface">Mis multas</h1>
        <p class="body-md mt-1">Consulta tus multas pendientes y el historial de pagos.</p>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Saldo pendiente</p>
            <p class="mt-2 text-2xl font-bold text-amber-700 font-display"><?= $e($fmtMoney((float) $summary['pending'], (string) $currency)) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Pagado</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= $e($fmtMoney((float) $summary['paid'], (string) $currency)) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Total historico</p>
            <p class="mt-2 text-2xl font-bold text-primary font-display"><?= $e($fmtMoney((float) $summary['total'], (string) $currency)) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Multas abiertas</p>
            <p class="mt-2 text-2xl font-bold text-on-surface font-display"><?= (int) $summary['open_count'] ?></p>
        </article>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Fecha</th>
                        <th class="px-4 py-3 font-semibold">Recurso</th>
                        <th class="px-4 py-3 font-semibold">Motivo</th>
                        <th class="px-4 py-3 font-semibold">Monto</th>
                        <th class="px-4 py-3 font-semibold">Pagado</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50 text-sm">
                    <?php if (empty($fines)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-on-surface-subtle">No tienes multas registradas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fines as $fine): ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors">
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((new DateTime($fine['created_at']))->format('d/m/Y H:i')) ?></td>
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-on-surface"><?= $e($fine['book_title'] ?? 'Libro') ?></p>
                                    <p class="text-xs text-on-surface-subtle">Prestamo #<?= (int) ($fine['loan_ref'] ?? 0) ?></p>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted">
                                    <?= $e($reasonLabel((string) ($fine['reason'] ?? ''))) ?>
                                    <?php if ((int) ($fine['hours_overdue'] ?? 0) > 0): ?>
                                        <span class="text-xs text-on-surface-subtle">(<?= (int) $fine['hours_overdue'] ?> h)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3.5 font-semibold text-on-surface"><?= $e($fmtMoney((float) $fine['amount'], (string) $currency)) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($fmtMoney((float) $fine['amount_paid'], (string) $currency)) ?></td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass((string) $fine['status']) ?>">
                                        <?= $e($statusLabel((string) $fine['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
