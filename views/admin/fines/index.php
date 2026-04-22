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
        'damage' => 'Daño',
        'loss' => 'Pérdida',
        default => ucfirst($reason),
    };
};

$fmtMoney = static function (float $amount, string $currency): string {
    return $currency . number_format($amount, 2, '.', ',');
};

$filters = $filters ?? ['q' => '', 'status' => '', 'reason' => ''];
$totalFines = count($fines ?? []);
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Administración</p>
            <h1 class="headline-lg text-on-surface">Multas</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= BASE_URL ?>/admin/reports/fines/export/excel"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors"
               title="Exportar reporte de multas en Excel">
                <iconify-icon icon="mdi:file-excel-box"></iconify-icon> Excel
            </a>
            <a href="<?= BASE_URL ?>/admin/reports/fines/export/pdf"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors"
               title="Exportar reporte de multas en PDF">
                <iconify-icon icon="mdi:file-pdf-box"></iconify-icon> PDF
            </a>
        </div>
    </div>

    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <div class="grid gap-3 md:grid-cols-5">
            <div class="md:col-span-2">
                <label for="fine-search" class="label-sm">Buscar</label>
                <input id="fine-search" type="text" value="<?= $e((string) ($filters['q'] ?? '')) ?>" placeholder="Usuario, socio, recurso, #multa o #préstamo"
                    class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
            </div>
            <div>
                <label for="fine-status" class="label-sm">Estado</label>
                <select id="fine-status" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todos</option>
                    <option value="pending" <?= (($filters['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pendiente</option>
                    <option value="partially_paid" <?= (($filters['status'] ?? '') === 'partially_paid') ? 'selected' : '' ?>>Pago parcial</option>
                    <option value="paid" <?= (($filters['status'] ?? '') === 'paid') ? 'selected' : '' ?>>Pagada</option>
                    <option value="waived" <?= (($filters['status'] ?? '') === 'waived') ? 'selected' : '' ?>>Condonada</option>
                </select>
            </div>
            <div>
                <label for="fine-reason" class="label-sm">Motivo</label>
                <select id="fine-reason" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todos</option>
                    <option value="overdue" <?= (($filters['reason'] ?? '') === 'overdue') ? 'selected' : '' ?>>Retraso</option>
                    <option value="damage" <?= (($filters['reason'] ?? '') === 'damage') ? 'selected' : '' ?>>Daño</option>
                    <option value="loss" <?= (($filters['reason'] ?? '') === 'loss') ? 'selected' : '' ?>>Pérdida</option>
                </select>
            </div>
            <div>
                <label for="fine-date" class="label-sm">Periodo</label>
                <select id="fine-date" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todo el historial</option>
                    <option value="30d">Últimos 30 días</option>
                    <option value="today">Hoy</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mes</option>
                </select>
            </div>
        </div>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Multas abiertas</p>
            <p class="mt-2 text-2xl font-bold text-amber-700 font-display"><?= (int) ($stats['pending_count'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Saldo pendiente</p>
            <p class="mt-2 text-2xl font-bold text-on-surface font-display"><?= $e($fmtMoney((float) ($stats['open_balance'] ?? 0), (string) $currency)) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Cobrado</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= $e($fmtMoney((float) ($stats['collected'] ?? 0), (string) $currency)) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Condonado</p>
            <p class="mt-2 text-2xl font-bold text-violet-700 font-display"><?= $e($fmtMoney((float) ($stats['waived'] ?? 0), (string) $currency)) ?></p>
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
                <tbody id="fine-table-body" class="divide-y divide-outline-variant/50 text-sm">
                    <?php if (empty($fines)): ?>
                        <tr id="fine-empty-server">
                            <td colspan="7" class="px-4 py-8 text-center text-on-surface-subtle">No hay multas registradas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fines as $fine): ?>
                            <?php
                            $fineId = (int) ($fine['id'] ?? 0);
                            $amount = (float) ($fine['amount'] ?? 0);
                            $paid = (float) ($fine['amount_paid'] ?? 0);
                            $pending = max(0.0, $amount - $paid);
                            $status = strtolower(trim((string) ($fine['status'] ?? '')));
                            $reason = strtolower(trim((string) ($fine['reason'] ?? '')));
                            $createdRaw = (string) ($fine['created_at'] ?? '');
                            $createdTs = strtotime($createdRaw) ?: 0;
                            $searchIndex = mb_strtolower(trim(
                                '#' . $fineId . ' '
                                . (string) ($fine['user_name'] ?? '') . ' '
                                . (string) ($fine['user_number'] ?? '') . ' '
                                . (string) ($fine['book_title'] ?? '') . ' '
                                . (string) ($fine['loan_ref'] ?? '') . ' '
                                . $createdRaw
                            ));
                            $canManage = !in_array($status, ['paid', 'waived'], true);
                            $disabledReason = match ($status) {
                                'paid' => 'No disponible: la multa ya está pagada.',
                                'waived' => 'No disponible: la multa ya fue condonada.',
                                default => 'No disponible para el estado actual.',
                            };
                            ?>
                            <tr data-fine-row="1"
                                data-fine-status="<?= $e($status) ?>"
                                data-fine-reason="<?= $e($reason) ?>"
                                data-fine-ts="<?= (int) $createdTs ?>"
                                data-fine-search="<?= $e($searchIndex) ?>"
                                class="hover:bg-surface-container-low/60 transition-colors align-top">
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((new DateTime($createdRaw !== '' ? $createdRaw : 'now'))->format('d/m/Y H:i')) ?></td>
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-on-surface"><?= $e($fine['user_name'] ?? 'Usuario') ?></p>
                                    <p class="text-xs text-on-surface-subtle">Socio #<?= $e($fine['user_number'] ?? '-') ?></p>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted">
                                    <p class="font-medium text-on-surface"><?= $e($fine['book_title'] ?? 'Recurso') ?></p>
                                    <p class="text-xs text-on-surface-subtle">Préstamo #<?= (int) ($fine['loan_ref'] ?? 0) ?></p>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted">
                                    <p><?= $e($reasonLabel($reason)) ?></p>
                                    <?php if ((int) ($fine['hours_overdue'] ?? 0) > 0): ?>
                                        <p class="text-xs text-on-surface-subtle"><?= (int) ($fine['hours_overdue'] ?? 0) ?> h de retraso</p>
                                    <?php endif; ?>
                                    <?php if (!empty($fine['waiver_reason'])): ?>
                                        <p class="mt-1 text-xs text-violet-700">Motivo condonación: <?= $e($fine['waiver_reason']) ?></p>
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
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <span data-tooltip="<?= $e($canManage ? 'Registrar pago' : $disabledReason) ?>">
                                            <button type="button"
                                                    class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 transition-colors <?= $canManage ? 'text-emerald-700 hover:bg-emerald-50 js-open-fine-payment' : 'text-on-surface-subtle opacity-60 cursor-not-allowed' ?>"
                                                    data-fine-id="<?= $fineId ?>"
                                                    data-fine-user="<?= $e((string) ($fine['user_name'] ?? 'Usuario')) ?>"
                                                    data-fine-resource="<?= $e((string) ($fine['book_title'] ?? 'Recurso')) ?>"
                                                    data-fine-pending="<?= $e(number_format($pending > 0 ? $pending : 0.01, 2, '.', '')) ?>"
                                                    <?= $canManage ? '' : 'disabled' ?>>
                                                <?= Icons::check('w-3.5 h-3.5') ?> Cobrar
                                            </button>
                                        </span>

                                        <span data-tooltip="<?= $e($canManage ? 'Condonar multa' : $disabledReason) ?>">
                                            <button type="button"
                                                    class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 transition-colors <?= $canManage ? 'text-violet-700 hover:bg-violet-50 js-open-fine-waive' : 'text-on-surface-subtle opacity-60 cursor-not-allowed' ?>"
                                                    data-fine-id="<?= $fineId ?>"
                                                    data-fine-user="<?= $e((string) ($fine['user_name'] ?? 'Usuario')) ?>"
                                                    data-fine-resource="<?= $e((string) ($fine['book_title'] ?? 'Recurso')) ?>"
                                                    <?= $canManage ? '' : 'disabled' ?>>
                                                <?= Icons::x('w-3.5 h-3.5') ?> Condonar
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="fine-empty-filter" class="hidden">
                            <td colspan="7" class="px-4 py-8 text-center text-on-surface-subtle">No hay multas que coincidan con los filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-outline-variant/60 px-4 py-3 text-sm text-on-surface-muted sm:flex-row sm:items-center sm:justify-between">
            <p id="fine-results-count">Mostrando <?= $totalFines > 0 ? '1-' . min(4, $totalFines) : '0-0' ?> de <?= (int) $totalFines ?> multas</p>
            <div class="flex items-center gap-1">
                <button type="button" id="fine-page-prev" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 disabled:opacity-60 disabled:cursor-not-allowed"><?= Icons::arrowLeft('w-3.5 h-3.5') ?> Anterior</button>
                <span id="fine-page-indicator" class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white">1 / 1</span>
                <button type="button" id="fine-page-next" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 disabled:opacity-60 disabled:cursor-not-allowed">Siguiente <?= Icons::arrowRight('w-3.5 h-3.5') ?></button>
            </div>
        </div>
    </div>

    <div id="fine-payment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-ambient-lg">
            <h2 class="headline-md text-on-surface">Registrar pago</h2>
            <p id="fine-payment-modal-title" class="mt-1 text-sm text-on-surface-muted">Confirma el monto a cobrar.</p>

            <form id="fine-payment-form" method="POST" action="<?= BASE_URL ?>/admin/fines/0/payment" class="mt-4 space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= $e((string) $csrf) ?>">
                <label class="block">
                    <span class="label-sm">Monto</span>
                    <input id="fine-payment-amount" type="number" name="amount" min="0.01" step="0.01"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none" required>
                </label>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" id="fine-payment-cancel" class="rounded-xl border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low">Cancelar</button>
                    <button type="submit" class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient">Registrar pago</button>
                </div>
            </form>
        </div>
    </div>

    <div id="fine-waive-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-ambient-lg">
            <h2 class="headline-md text-on-surface">Condonar multa</h2>
            <p id="fine-waive-modal-title" class="mt-1 text-sm text-on-surface-muted">Indica el motivo de condonación.</p>

            <form id="fine-waive-form" method="POST" action="<?= BASE_URL ?>/admin/fines/0/waive" class="mt-4 space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= $e((string) $csrf) ?>">
                <label class="block">
                    <span class="label-sm">Motivo</span>
                    <input id="fine-waive-reason" type="text" name="waiver_reason"
                           value="Condonación administrativa"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none" required>
                </label>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" id="fine-waive-cancel" class="rounded-xl border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low">Cancelar</button>
                    <button type="submit" class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-2 text-sm font-semibold text-violet-700 hover:bg-violet-100">Confirmar condonación</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
(() => {
    const searchInput = document.getElementById('fine-search');
    const statusSelect = document.getElementById('fine-status');
    const reasonSelect = document.getElementById('fine-reason');
    const periodSelect = document.getElementById('fine-date');
    const resultsCount = document.getElementById('fine-results-count');
    const rows = Array.from(document.querySelectorAll('tr[data-fine-row="1"]'));
    const emptyFilterRow = document.getElementById('fine-empty-filter');
    const prevPageBtn = document.getElementById('fine-page-prev');
    const nextPageBtn = document.getElementById('fine-page-next');
    const pageIndicator = document.getElementById('fine-page-indicator');
    const perPage = 4;
    let currentPage = 1;

    const dayStart = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
    const periodThreshold = (period) => {
        const now = new Date();
        const today = dayStart(now);

        if (period === 'all') return 0;
        if (period === 'today') return today;
        if (period === 'week') {
            const day = now.getDay();
            const mondayOffset = day === 0 ? 6 : day - 1;
            return today - (mondayOffset * 86400000);
        }
        if (period === 'month') return new Date(now.getFullYear(), now.getMonth(), 1).getTime();
        return now.getTime() - (30 * 86400000);
    };

    const applyFilters = (resetPage = false) => {
        if (!searchInput || !statusSelect || !reasonSelect || !periodSelect || rows.length === 0) return;

        const search = searchInput.value.trim().toLowerCase();
        const status = statusSelect.value;
        const reason = reasonSelect.value;
        const period = periodSelect.value;
        const threshold = periodThreshold(period);
        const filteredRows = [];

        rows.forEach((row) => {
            const rowStatus = (row.dataset.fineStatus || '').toLowerCase();
            const rowReason = (row.dataset.fineReason || '').toLowerCase();
            const rowTs = Number(row.dataset.fineTs || '0') * 1000;
            const rowSearch = (row.dataset.fineSearch || '').toLowerCase();

            const okSearch = search === '' || rowSearch.includes(search);
            const okStatus = status === 'all' || rowStatus === status;
            const okReason = reason === 'all' || rowReason === reason;
            const okPeriod = period === 'all' || rowTs >= threshold;
            const visible = okSearch && okStatus && okReason && okPeriod;

            row.classList.add('hidden');
            if (visible) filteredRows.push(row);
        });

        const totalFiltered = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalFiltered / perPage));
        currentPage = resetPage ? 1 : Math.min(currentPage, totalPages);

        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        filteredRows.slice(start, end).forEach((row) => row.classList.remove('hidden'));

        if (emptyFilterRow) {
            emptyFilterRow.classList.toggle('hidden', totalFiltered > 0);
        }

        if (resultsCount) {
            if (totalFiltered > 0) {
                const rangeStart = start + 1;
                const rangeEnd = Math.min(end, totalFiltered);
                resultsCount.textContent = `Mostrando ${rangeStart}-${rangeEnd} de ${totalFiltered} multas`;
            } else {
                resultsCount.textContent = 'Mostrando 0-0 de 0 multas';
            }
        }

        if (pageIndicator) pageIndicator.textContent = `${Math.min(currentPage, totalPages)} / ${totalPages}`;
        if (prevPageBtn) prevPageBtn.disabled = currentPage <= 1 || totalFiltered === 0;
        if (nextPageBtn) nextPageBtn.disabled = currentPage >= totalPages || totalFiltered === 0;
    };

    if (searchInput && statusSelect && reasonSelect && periodSelect && rows.length > 0) {
        searchInput.addEventListener('input', () => applyFilters(true));
        statusSelect.addEventListener('change', () => applyFilters(true));
        reasonSelect.addEventListener('change', () => applyFilters(true));
        periodSelect.addEventListener('change', () => applyFilters(true));

        prevPageBtn?.addEventListener('click', () => {
            currentPage = Math.max(1, currentPage - 1);
            applyFilters(false);
        });
        nextPageBtn?.addEventListener('click', () => {
            currentPage += 1;
            applyFilters(false);
        });

        applyFilters(true);
    }

    const paymentModal = document.getElementById('fine-payment-modal');
    const paymentForm = document.getElementById('fine-payment-form');
    const paymentTitle = document.getElementById('fine-payment-modal-title');
    const paymentAmount = document.getElementById('fine-payment-amount');
    const paymentCancel = document.getElementById('fine-payment-cancel');
    const paymentButtons = Array.from(document.querySelectorAll('.js-open-fine-payment'));

    const waiveModal = document.getElementById('fine-waive-modal');
    const waiveForm = document.getElementById('fine-waive-form');
    const waiveTitle = document.getElementById('fine-waive-modal-title');
    const waiveReason = document.getElementById('fine-waive-reason');
    const waiveCancel = document.getElementById('fine-waive-cancel');
    const waiveButtons = Array.from(document.querySelectorAll('.js-open-fine-waive'));

    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    paymentButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!paymentForm || !paymentTitle || !paymentAmount) return;
            const fineId = button.getAttribute('data-fine-id') || '0';
            const user = button.getAttribute('data-fine-user') || 'Usuario';
            const resource = button.getAttribute('data-fine-resource') || 'Recurso';
            const pending = button.getAttribute('data-fine-pending') || '0.01';

            paymentForm.action = '<?= BASE_URL ?>/admin/fines/' + fineId + '/payment';
            paymentTitle.textContent = 'Usuario: ' + user + ' · Recurso: ' + resource;
            paymentAmount.value = pending;
            openModal(paymentModal);
        });
    });

    waiveButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!waiveForm || !waiveTitle || !waiveReason) return;
            const fineId = button.getAttribute('data-fine-id') || '0';
            const user = button.getAttribute('data-fine-user') || 'Usuario';
            const resource = button.getAttribute('data-fine-resource') || 'Recurso';

            waiveForm.action = '<?= BASE_URL ?>/admin/fines/' + fineId + '/waive';
            waiveTitle.textContent = 'Usuario: ' + user + ' · Recurso: ' + resource;
            waiveReason.value = 'Condonación administrativa';
            openModal(waiveModal);
        });
    });

    paymentCancel?.addEventListener('click', () => closeModal(paymentModal));
    waiveCancel?.addEventListener('click', () => closeModal(waiveModal));

    paymentModal?.addEventListener('click', (event) => {
        if (event.target === paymentModal) closeModal(paymentModal);
    });
    waiveModal?.addEventListener('click', (event) => {
        if (event.target === waiveModal) closeModal(waiveModal);
    });
})();
</script>
