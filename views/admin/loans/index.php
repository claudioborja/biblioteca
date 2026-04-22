<?php
// views/admin/loans/index.php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$statusClass = static function (string $status): string {
    return match ($status) {
        'active' => 'bg-blue-100 text-blue-700',
        'overdue' => 'bg-amber-100 text-amber-700',
        'returned' => 'bg-emerald-100 text-emerald-700',
        'lost' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-700',
    };
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'active' => 'Activo',
        'overdue' => 'Vencido',
        'returned' => 'Devuelto',
        'lost' => 'Perdido',
        default => ucfirst($status),
    };
};

$totalLoans = count($loans);
$visibleLoans = $loans;
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Administración</p>
            <h1 class="headline-lg text-on-surface">Préstamos</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= BASE_URL ?>/admin/reports/loans/export/excel"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors"
               title="Exportar reporte de préstamos en Excel">
                <?= Icons::download('w-4 h-4') ?> Excel
            </a>
            <a href="<?= BASE_URL ?>/admin/reports/loans/export/pdf"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors"
               title="Exportar reporte de préstamos en PDF">
                <?= Icons::download('w-4 h-4') ?> PDF
            </a>
            <a href="<?= BASE_URL ?>/admin/loans/create"
               id="btn-open-loan-create-modal"
               class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity inline-flex items-center gap-2">
                <?= Icons::plus('w-4 h-4') ?> Nuevo préstamo
            </a>
        </div>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Activos</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 font-display"><?= (int) $stats['active'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Vencidos</p>
            <p class="mt-2 text-2xl font-bold text-amber-700 font-display"><?= (int) $stats['overdue'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Devueltos</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= (int) $stats['returned'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Perdidos</p>
            <p class="mt-2 text-2xl font-bold text-red-700 font-display"><?= (int) $stats['lost'] ?></p>
        </article>
    </div>

    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <div class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label for="loan-search" class="label-sm">Buscar</label>
                <input id="loan-search" type="text" placeholder="ISBN, título o código"
                    class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
            </div>
            <div>
                <label for="loan-status" class="label-sm">Estado</label>
                <select id="loan-status" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todos</option>
                    <option value="active">Activo</option>
                    <option value="overdue">Vencido</option>
                    <option value="returned">Devuelto</option>
                    <option value="lost">Perdido</option>
                </select>
            </div>
            <div>
                <label for="loan-date" class="label-sm">Periodo</label>
                <select id="loan-date" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todo el historial</option>
                    <option value="30d">Últimos 30 días</option>
                    <option value="today">Hoy</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mes</option>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Codigo</th>
                        <th class="px-4 py-3 font-semibold">Usuario</th>
                        <th class="px-4 py-3 font-semibold">Recurso</th>
                        <th class="px-4 py-3 font-semibold">Préstamo</th>
                        <th class="px-4 py-3 font-semibold">Vence</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="loan-table-body" class="divide-y divide-outline-variant/50 text-sm">
                    <?php if (empty($visibleLoans)): ?>
                        <tr id="loan-empty-server">
                            <td colspan="7" class="px-4 py-8 text-center text-on-surface-subtle">No hay préstamos registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($visibleLoans as $loan): ?>
                            <?php
                            $loanId = (int) ($loan['id'] ?? 0);
                            $loanUser = (string) ($loan['user'] ?? '');
                            $loanBook = (string) ($loan['book'] ?? '');
                            $loanStatus = strtolower(trim((string) ($loan['status'] ?? '')));
                            $loanAtRaw = (string) ($loan['loan_at'] ?? '');
                            $loanAtTs = strtotime($loanAtRaw) ?: 0;
                            $searchIndex = mb_strtolower(trim($loanId . ' ' . $loanUser . ' ' . $loanBook . ' ' . $loanAtRaw));
                            ?>
                            <tr data-loan-row="1"
                                data-loan-status="<?= $e($loanStatus) ?>"
                                data-loan-ts="<?= (int) $loanAtTs ?>"
                                data-loan-search="<?= $e($searchIndex) ?>"
                                class="hover:bg-surface-container-low/60 transition-colors">
                                <td class="px-4 py-3.5 font-semibold text-on-surface"><?= $e($loan['id']) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($loan['user']) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($loan['book']) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((new DateTime($loan['loan_at']))->format('d/m/Y H:i')) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((new DateTime($loan['due_at']))->format('d/m/Y H:i')) ?></td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass($loan['status']) ?>">
                                        <?= $e($statusLabel($loan['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <?php
                                    $statusRaw = (string) ($loan['status'] ?? '');
                                    $canManage = in_array($statusRaw, ['active', 'overdue'], true);
                                    $disabledReason = match ($statusRaw) {
                                        'returned' => 'No disponible: el préstamo ya fue devuelto.',
                                        'lost' => 'No disponible: el préstamo ya fue marcado como perdido.',
                                        default => 'No disponible para el estado actual del préstamo.',
                                    };
                                    ?>
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <span data-tooltip="<?= $e($canManage ? 'Renovar préstamo' : $disabledReason) ?>">
                                            <button type="button"
                                                    class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 transition-colors <?= $canManage ? 'text-on-surface-muted hover:bg-surface-container-low js-open-renew-modal' : 'text-on-surface-subtle opacity-60 cursor-not-allowed' ?>"
                                                    data-loan-id="<?= (int) ($loan['id'] ?? 0) ?>"
                                                    data-loan-book="<?= $e((string) ($loan['book'] ?? 'Recurso')) ?>"
                                                    <?= $canManage ? '' : 'disabled' ?>>
                                                <?= Icons::refresh('w-3.5 h-3.5') ?> Renovar
                                            </button>
                                        </span>

                                        <span data-tooltip="<?= $e($canManage ? 'Registrar devolución' : $disabledReason) ?>">
                                            <form method="POST" action="<?= BASE_URL ?>/admin/loans/<?= (int) ($loan['id'] ?? 0) ?>/return" class="inline">
                                                <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                                <button type="submit"
                                                        class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 transition-colors <?= $canManage ? 'text-on-surface-muted hover:bg-surface-container-low' : 'text-on-surface-subtle opacity-60 cursor-not-allowed' ?>"
                                                        <?= $canManage ? '' : 'disabled' ?>>
                                                    <?= Icons::returnIcon('w-3.5 h-3.5') ?> Devolver
                                                </button>
                                            </form>
                                        </span>

                                        <span data-tooltip="<?= $e($canManage ? 'Marcar como perdido' : $disabledReason) ?>">
                                            <form method="POST" action="<?= BASE_URL ?>/admin/loans/<?= (int) ($loan['id'] ?? 0) ?>/lost" class="inline">
                                                <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                                <button type="submit"
                                                        class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 transition-colors <?= $canManage ? 'text-red-700 hover:bg-red-50' : 'text-on-surface-subtle opacity-60 cursor-not-allowed' ?>"
                                                        <?= $canManage ? '' : 'disabled' ?>>
                                                    <?= Icons::alert('w-3.5 h-3.5') ?> Perdido
                                                </button>
                                            </form>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="loan-empty-filter" class="hidden">
                            <td colspan="7" class="px-4 py-8 text-center text-on-surface-subtle">No hay préstamos que coincidan con los filtros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-outline-variant/60 px-4 py-3 text-sm text-on-surface-muted sm:flex-row sm:items-center sm:justify-between">
            <p id="loan-results-count">Mostrando <?= $totalLoans > 0 ? '1-' . $totalLoans : '0-0' ?> de <?= (int) $totalLoans ?> préstamos</p>
            <div class="flex items-center gap-1">
                <button type="button" id="loan-page-prev" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 disabled:opacity-60 disabled:cursor-not-allowed"><?= Icons::arrowLeft('w-3.5 h-3.5') ?> Anterior</button>
                <span id="loan-page-indicator" class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white">1 / 1</span>
                <button type="button" id="loan-page-next" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 disabled:opacity-60 disabled:cursor-not-allowed">Siguiente <?= Icons::arrowRight('w-3.5 h-3.5') ?></button>
            </div>
        </div>
    </div>

    <div id="renew-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-ambient-lg">
            <h2 class="headline-md text-on-surface">Renovar préstamo</h2>
            <p id="renew-modal-book" class="mt-1 text-sm text-on-surface-muted">Selecciona el tiempo de renovación.</p>

            <form id="renew-modal-form" method="POST" action="<?= BASE_URL ?>/admin/loans/0/renew" class="mt-4 space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">

                <label class="block">
                    <span class="label-sm">Tiempo de renovación</span>
                    <select name="renewal_hours" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <option value="24">24 horas (1 día)</option>
                        <option value="48">48 horas (2 días)</option>
                        <option value="72" selected>72 horas (3 días)</option>
                        <option value="96">96 horas (4 días)</option>
                        <option value="168">168 horas (7 días)</option>
                    </select>
                </label>

                <div class="flex items-center justify-end gap-2">
                    <button type="button" id="renew-modal-cancel" class="rounded-xl border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low">Cancelar</button>
                    <button type="submit" class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient">Confirmar renovación</button>
                </div>
            </form>
        </div>
    </div>

    <div id="loan-create-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 p-3 lg:p-6"
         aria-hidden="true">
        <div class="absolute inset-0" data-close-loan-create-modal></div>
        <div class="relative z-10 flex h-[92vh] w-[min(1100px,96vw)] flex-col overflow-hidden rounded-2xl border border-outline-variant bg-white shadow-ambient-lg">
            <div class="flex min-h-14 items-center justify-between border-b border-outline-variant/70 bg-surface-container-low px-4 py-2.5">
                <div>
                    <h3 class="text-sm font-semibold text-on-surface">Nuevo préstamo</h3>
                </div>
                <button type="button"
                        data-close-loan-create-modal
                        class="inline-flex items-center gap-1 rounded-lg border border-outline-variant bg-white px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                    <?= Icons::x('w-3.5 h-3.5') ?> Cerrar
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-hidden">
                <iframe id="loan-create-frame" title="Nuevo préstamo" class="h-full w-full bg-white" src="about:blank"></iframe>
            </div>

            <div class="flex min-h-14 items-center justify-between border-t border-outline-variant/70 bg-slate-100/95 px-4 py-2.5">
                <p class="text-xs text-slate-600">Completa usuario, recurso y notas. Guarda desde aquí para mantener la lista visible.</p>
                <button type="button"
                        id="loan-create-modal-save"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                    <?= Icons::save('w-4 h-4') ?> Registrar préstamo
                </button>
            </div>
        </div>
    </div>
</section>

<script>
(() => {
    const searchInput = document.getElementById('loan-search');
    const statusSelect = document.getElementById('loan-status');
    const periodSelect = document.getElementById('loan-date');
    const resultsCount = document.getElementById('loan-results-count');
    const rows = Array.from(document.querySelectorAll('tr[data-loan-row="1"]'));
    const emptyFilterRow = document.getElementById('loan-empty-filter');
    const prevPageBtn = document.getElementById('loan-page-prev');
    const nextPageBtn = document.getElementById('loan-page-next');
    const pageIndicator = document.getElementById('loan-page-indicator');
    const openCreateBtn = document.getElementById('btn-open-loan-create-modal');
    const createModal = document.getElementById('loan-create-modal');
    const createFrame = document.getElementById('loan-create-frame');
    const createSaveBtn = document.getElementById('loan-create-modal-save');
    const closeCreateBtns = Array.from(document.querySelectorAll('[data-close-loan-create-modal]'));
    let loanCreateHandled = false;
    const perPage = 4;
    let currentPage = 1;

    const dayStart = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
    const periodThreshold = (period) => {
        const now = new Date();
        const today = dayStart(now);

        if (period === 'all') return 0;
        if (period === 'today') return today;
        if (period === 'week') {
            const day = now.getDay(); // 0 domingo, 1 lunes...
            const mondayOffset = day === 0 ? 6 : day - 1;
            return today - (mondayOffset * 86400000);
        }
        if (period === 'month') return new Date(now.getFullYear(), now.getMonth(), 1).getTime();
        return now.getTime() - (30 * 86400000);
    };

    const applyFilters = (resetPage = false) => {
        if (!searchInput || !statusSelect || !periodSelect || rows.length === 0) {
            return;
        }

        const search = searchInput.value.trim().toLowerCase();
        const status = statusSelect.value;
        const period = periodSelect.value;
        const threshold = periodThreshold(period);
        const filteredRows = [];

        rows.forEach((row) => {
            const rowStatus = (row.dataset.loanStatus || '').toLowerCase();
            const rowTs = Number(row.dataset.loanTs || '0') * 1000;
            const rowSearch = (row.dataset.loanSearch || '').toLowerCase();

            const matchesSearch = search === '' || rowSearch.includes(search);
            const matchesStatus = status === 'all' || rowStatus === status;
            const matchesPeriod = period === 'all' || rowTs >= threshold;
            const visible = matchesSearch && matchesStatus && matchesPeriod;

            if (visible) {
                filteredRows.push(row);
            }
            row.classList.add('hidden');
        });

        const totalFiltered = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalFiltered / perPage));
        if (resetPage) {
            currentPage = 1;
        } else {
            currentPage = Math.min(currentPage, totalPages);
        }

        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        const pageRows = filteredRows.slice(start, end);
        pageRows.forEach((row) => row.classList.remove('hidden'));

        if (emptyFilterRow) {
            emptyFilterRow.classList.toggle('hidden', totalFiltered > 0);
        }

        if (resultsCount) {
            if (totalFiltered > 0) {
                const rangeStart = start + 1;
                const rangeEnd = Math.min(end, totalFiltered);
                resultsCount.textContent = `Mostrando ${rangeStart}-${rangeEnd} de ${totalFiltered} préstamos`;
            } else {
                resultsCount.textContent = `Mostrando 0-0 de 0 préstamos`;
            }
        }

        if (pageIndicator) {
            pageIndicator.textContent = `${Math.min(currentPage, totalPages)} / ${totalPages}`;
        }

        if (prevPageBtn) {
            prevPageBtn.disabled = currentPage <= 1 || totalFiltered === 0;
        }
        if (nextPageBtn) {
            nextPageBtn.disabled = currentPage >= totalPages || totalFiltered === 0;
        }
    };

    if (searchInput && statusSelect && periodSelect && rows.length > 0) {
        searchInput.addEventListener('input', () => applyFilters(true));
        statusSelect.addEventListener('change', () => applyFilters(true));
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

    const openCreateModal = () => {
        if (!createModal || !createFrame) return;
        createModal.classList.add('hidden');
        createModal.classList.remove('flex');
        createModal.setAttribute('aria-hidden', 'true');

        const showModal = () => {
            createModal.classList.remove('hidden');
            createModal.classList.add('flex');
            createModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        };

        createFrame.onload = () => {
            showModal();
            createFrame.onload = null;
        };
        createFrame.src = '<?= BASE_URL ?>/admin/loans/create?modal=1';
    };

    const closeCreateModal = () => {
        if (!createModal || !createFrame) return;
        createModal.classList.add('hidden');
        createModal.classList.remove('flex');
        createModal.setAttribute('aria-hidden', 'true');
        createFrame.src = 'about:blank';
        document.body.classList.remove('overflow-hidden');
    };

    openCreateBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        openCreateModal();
    });
    closeCreateBtns.forEach((button) => button.addEventListener('click', closeCreateModal));
    createSaveBtn?.addEventListener('click', () => {
        createFrame?.contentWindow?.postMessage({ type: 'submit-loan-create-form' }, '*');
    });

    window.addEventListener('message', (event) => {
        if (event?.data?.type === 'loan-create-saved') {
            if (loanCreateHandled) return;
            loanCreateHandled = true;
            closeCreateModal();
            window.location.assign('<?= BASE_URL ?>/admin/loans');
        }
    });

    const renewModal = document.getElementById('renew-modal');
    const renewForm = document.getElementById('renew-modal-form');
    const renewTitle = document.getElementById('renew-modal-book');
    const renewCancel = document.getElementById('renew-modal-cancel');
    const buttons = document.querySelectorAll('.js-open-renew-modal');

    if (renewModal && renewForm && renewTitle && renewCancel && buttons.length > 0) {
        const closeRenewModal = () => {
            renewModal.classList.add('hidden');
            renewModal.classList.remove('flex');
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const loanId = button.getAttribute('data-loan-id') || '0';
                const book = button.getAttribute('data-loan-book') || 'Recurso';
                renewForm.action = '<?= BASE_URL ?>/admin/loans/' + loanId + '/renew';
                renewTitle.textContent = 'Recurso: ' + book;
                renewModal.classList.remove('hidden');
                renewModal.classList.add('flex');
            });
        });

        renewCancel.addEventListener('click', closeRenewModal);
        renewModal.addEventListener('click', (event) => {
            if (event.target === renewModal) {
                closeRenewModal();
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (createModal && !createModal.classList.contains('hidden')) {
            closeCreateModal();
        }
    });
})();
</script>
