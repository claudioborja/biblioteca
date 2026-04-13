<?php
// views/admin/fines/index.php
use Helpers\Icons;

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

$filters = $filters ?? ['q' => '', 'status' => '', 'reason' => ''];
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Administracion</p>
            <h1 class="headline-lg text-on-surface">Multas</h1>
            <p class="body-md mt-1">Consulta y gestiona pagos o condonaciones de multas.</p>
        </div>
    </div>

    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <form method="GET" action="<?= BASE_URL ?>/admin/fines" class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label for="fines-q" class="label-sm">Buscar</label>
                <input id="fines-q" type="text" name="q" value="<?= $e((string) ($filters['q'] ?? '')) ?>" placeholder="Usuario, socio, recurso, #multa o #prestamo"
                    class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none">
            </div>
            <div>
                <label for="fines-status" class="label-sm">Estado</label>
                <select id="fines-status" name="status" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="">Todos</option>
                    <option value="pending" <?= (($filters['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pendiente</option>
                    <option value="partially_paid" <?= (($filters['status'] ?? '') === 'partially_paid') ? 'selected' : '' ?>>Pago parcial</option>
                    <option value="paid" <?= (($filters['status'] ?? '') === 'paid') ? 'selected' : '' ?>>Pagada</option>
                    <option value="waived" <?= (($filters['status'] ?? '') === 'waived') ? 'selected' : '' ?>>Condonada</option>
                </select>
            </div>
            <div>
                <label for="fines-reason" class="label-sm">Motivo</label>
                <select id="fines-reason" name="reason" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="">Todos</option>
                    <option value="overdue" <?= (($filters['reason'] ?? '') === 'overdue') ? 'selected' : '' ?>>Retraso</option>
                    <option value="damage" <?= (($filters['reason'] ?? '') === 'damage') ? 'selected' : '' ?>>Daño</option>
                    <option value="loss" <?= (($filters['reason'] ?? '') === 'loss') ? 'selected' : '' ?>>Pérdida</option>
                </select>
            </div>
            <div class="md:col-span-4 flex items-center justify-end gap-2">
                <a href="<?= BASE_URL ?>/admin/fines" class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low">Limpiar</a>
                <button type="submit" class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Multas abiertas</p>
            <p class="mt-2 text-2xl font-bold text-amber-700 font-display"><?= (int) $stats['pending_count'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Saldo pendiente</p>
            <p class="mt-2 text-2xl font-bold text-on-surface font-display"><?= $e($fmtMoney((float) $stats['open_balance'], (string) $currency)) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Cobrado</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= $e($fmtMoney((float) $stats['collected'], (string) $currency)) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Condonado</p>
            <p class="mt-2 text-2xl font-bold text-violet-700 font-display"><?= $e($fmtMoney((float) $stats['waived'], (string) $currency)) ?></p>
        </article>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Fecha</th>
                        <th class="px-4 py-3 font-semibold">Usuario</th>
                        <th class="px-4 py-3 font-semibold">Recurso</th>
                        <th class="px-4 py-3 font-semibold">Detalle</th>
                        <th class="px-4 py-3 font-semibold">Monto</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50 text-sm">
                    <?php if (empty($fines)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-on-surface-subtle">No hay multas registradas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fines as $fine): ?>
                            <?php
                            $amount = (float) $fine['amount'];
                            $paid = (float) $fine['amount_paid'];
                            $pending = max(0.0, $amount - $paid);
                            $status = (string) $fine['status'];
                            ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors align-top">
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((new DateTime((string) $fine['created_at']))->format('d/m/Y H:i')) ?></td>
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-on-surface"><?= $e($fine['user_name']) ?></p>
                                    <p class="text-xs text-on-surface-subtle">Socio #<?= $e($fine['user_number'] ?? '-') ?></p>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted">
                                    <p class="font-medium text-on-surface"><?= $e($fine['book_title'] ?? 'Libro') ?></p>
                                    <p class="text-xs text-on-surface-subtle">Prestamo #<?= (int) ($fine['loan_ref'] ?? 0) ?></p>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted">
                                    <p><?= $e($reasonLabel((string) ($fine['reason'] ?? ''))) ?></p>
                                    <?php if ((int) ($fine['hours_overdue'] ?? 0) > 0): ?>
                                        <p class="text-xs text-on-surface-subtle"><?= (int) $fine['hours_overdue'] ?> h de retraso</p>
                                    <?php endif; ?>
                                    <?php if (!empty($fine['waiver_reason'])): ?>
                                        <p class="mt-1 text-xs text-violet-700">Motivo condonacion: <?= $e($fine['waiver_reason']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted">
                                    <p class="font-semibold text-on-surface"><?= $e($fmtMoney($amount, (string) $currency)) ?></p>
                                    <p class="text-xs">Pagado: <?= $e($fmtMoney($paid, (string) $currency)) ?></p>
                                    <p class="text-xs">Pendiente: <?= $e($fmtMoney($pending, (string) $currency)) ?></p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass($status) ?>">
                                        <?= $e($statusLabel($status)) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <?php if ($status !== 'paid' && $status !== 'waived'): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/fines/<?= (int) $fine['id'] ?>/payment" class="mb-2 flex items-center justify-end gap-1.5">
                                            <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                                            <input type="number" name="amount" min="0.01" step="0.01" value="<?= $e(number_format($pending > 0 ? $pending : 0.01, 2, '.', '')) ?>"
                                                   class="w-24 rounded-lg border border-outline-variant px-2 py-1.5 text-xs text-on-surface focus:border-primary focus:outline-none">
                                            <button type="submit" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 inline-flex items-center gap-1">
                                                <?= Icons::check('w-3.5 h-3.5') ?> Cobrar
                                            </button>
                                        </form>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/fines/<?= (int) $fine['id'] ?>/waive" class="flex items-center justify-end gap-1.5">
                                            <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                                            <input type="text" name="waiver_reason" value="Condonacion administrativa"
                                                   class="w-32 rounded-lg border border-outline-variant px-2 py-1.5 text-xs text-on-surface focus:border-primary focus:outline-none"
                                                   placeholder="Motivo">
                                            <button type="submit" class="rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100 inline-flex items-center gap-1">
                                                <?= Icons::x('w-3.5 h-3.5') ?> Condonar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted">
                                            Sin acciones
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
