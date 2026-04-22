<?php
// views/admin/reservations/index.php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$statusClass = static function (string $status): string {
    return match ($status) {
        'waiting' => 'bg-blue-100 text-blue-700',
        'notified' => 'bg-emerald-100 text-emerald-700',
        'expired' => 'bg-amber-100 text-amber-700',
        'cancelled' => 'bg-red-100 text-red-700',
        'fulfilled' => 'bg-violet-100 text-violet-700',
        default => 'bg-slate-100 text-slate-700',
    };
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'waiting' => 'Pendiente',
        'notified' => 'Lista',
        'expired' => 'Expirada',
        'cancelled' => 'Cancelada',
        'fulfilled' => 'Completada',
        default => ucfirst($status),
    };
};

$totalReservations = count($reservations);
$visibleReservations = $reservations;
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Administración</p>
            <h1 class="headline-lg text-on-surface">Reservaciones</h1>
            <p class="body-md mt-1">Gestiona cola de reservas y conviértelas en préstamos cuando haya disponibilidad.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= BASE_URL ?>/admin/reservations/export/excel"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors"
               title="Exportar reporte de reservas en Excel">
                <?= Icons::download('w-4 h-4') ?> Excel
            </a>
            <a href="<?= BASE_URL ?>/admin/reservations/export/pdf"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors"
               title="Exportar reporte de reservas en PDF">
                <?= Icons::download('w-4 h-4') ?> PDF
            </a>
            <a href="<?= BASE_URL ?>/catalog"
               class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient inline-flex items-center gap-2 hover:opacity-90 transition-opacity">
                <?= Icons::plus('w-4 h-4') ?>
                Nueva reservación
            </a>
        </div>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Pendientes</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 font-display"><?= (int) $stats['pending'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Listas para prestar</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= (int) $stats['ready'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Expiradas</p>
            <p class="mt-2 text-2xl font-bold text-amber-700 font-display"><?= (int) $stats['expired'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Canceladas</p>
            <p class="mt-2 text-2xl font-bold text-red-700 font-display"><?= (int) $stats['cancelled'] ?></p>
        </article>
    </div>

    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <div class="grid gap-3 md:grid-cols-5">
            <div class="md:col-span-2">
                <label for="reservation-search" class="label-sm">Buscar</label>
                <input id="reservation-search" type="text" placeholder="Código, usuario o recurso"
                    class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
            </div>
            <div>
                <label for="reservation-status" class="label-sm">Estado</label>
                <select id="reservation-status" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todos</option>
                    <option value="waiting">Pendiente</option>
                    <option value="notified">Lista</option>
                    <option value="expired">Expirada</option>
                    <option value="cancelled">Cancelada</option>
                    <option value="fulfilled">Completada</option>
                </select>
            </div>
            <div>
                <label for="reservation-priority" class="label-sm">Prioridad</label>
                <select id="reservation-priority" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todas</option>
                    <option value="p1">Fila 1</option>
                    <option value="p2">Fila 2</option>
                    <option value="p3plus">Fila 3+</option>
                </select>
            </div>
            <div>
                <label for="reservation-date" class="label-sm">Periodo</label>
                <select id="reservation-date" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
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
                        <th class="px-4 py-3 font-semibold">ID</th>
                        <th class="px-4 py-3 font-semibold">Usuario</th>
                        <th class="px-4 py-3 font-semibold">Recurso</th>
                        <th class="px-4 py-3 font-semibold">Solicitud</th>
                        <th class="px-4 py-3 font-semibold">Fila</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="reservation-table-body" class="divide-y divide-outline-variant/50 text-sm">
                    <?php if (empty($visibleReservations)): ?>
                        <tr id="reservation-empty-server">
                            <td colspan="7" class="px-4 py-8 text-center text-on-surface-subtle">No hay reservaciones registradas.</td>
                        </tr>
                    <?php else: ?>
                    <?php foreach ($visibleReservations as $reservation): ?>
                        <?php
                        $reservationId = (int) ($reservation['id'] ?? 0);
                        $reservationStatus = strtolower(trim((string) ($reservation['status'] ?? '')));
                        $reservationQueue = (int) ($reservation['queue_position'] ?? 0);
                        $reservationCreatedRaw = (string) ($reservation['created_at'] ?? '');
                        $reservationCreatedTs = strtotime($reservationCreatedRaw) ?: 0;
                        $reservationSearch = mb_strtolower(trim(
                            '#' . $reservationId . ' '
                            . (string) ($reservation['user_name'] ?? '') . ' '
                            . (string) ($reservation['resource_title'] ?? '') . ' '
                            . $reservationCreatedRaw
                        ));
                        $canManage = in_array($reservationStatus, ['waiting', 'notified'], true);
                        $disabledReason = match ($reservationStatus) {
                            'fulfilled' => 'No disponible: la reservación ya se convirtió en préstamo.',
                            'cancelled' => 'No disponible: la reservación ya fue cancelada.',
                            'expired' => 'No disponible: la reservación está expirada.',
                            default => 'No disponible para el estado actual.',
                        };
                        ?>
                            <tr data-reservation-row="1"
                                data-reservation-status="<?= $e($reservationStatus) ?>"
                                data-reservation-queue="<?= (int) $reservationQueue ?>"
                                data-reservation-ts="<?= (int) $reservationCreatedTs ?>"
                                data-reservation-search="<?= $e($reservationSearch) ?>"
                                class="hover:bg-surface-container-low/60 transition-colors">
                            <td class="px-4 py-3.5 font-semibold text-on-surface">#<?= (int) ($reservation['id'] ?? 0) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($reservation['user_name'] ?? 'Usuario') ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($reservation['resource_title'] ?? 'Recurso') ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((new DateTime((string) ($reservation['created_at'] ?? 'now')))->format('d/m/Y H:i')) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted">#<?= (int) ($reservation['queue_position'] ?? 0) ?></td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass((string) ($reservation['status'] ?? '')) ?>">
                                    <?= $e($statusLabel((string) ($reservation['status'] ?? ''))) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="inline-flex items-center justify-end gap-1">
                                    <span data-tooltip="<?= $e($canManage ? 'Convertir a préstamo' : $disabledReason) ?>">
                                        <form method="POST" action="<?= BASE_URL ?>/admin/reservations/<?= (int) ($reservation['id'] ?? 0) ?>/convert" class="inline">
                                            <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                            <button type="submit"
                                                    class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 transition-colors <?= $canManage ? 'text-on-surface-muted hover:bg-surface-container-low' : 'text-on-surface-subtle opacity-60 cursor-not-allowed' ?>"
                                                    <?= $canManage ? '' : 'disabled' ?>>
                                                <?= Icons::arrowRight('w-3.5 h-3.5') ?> Prestar
                                            </button>
                                        </form>
                                    </span>

                                    <span data-tooltip="<?= $e($canManage ? 'Cancelar reservación' : $disabledReason) ?>">
                                        <form method="POST" action="<?= BASE_URL ?>/admin/reservations/<?= (int) ($reservation['id'] ?? 0) ?>/cancel" class="inline">
                                            <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                            <button type="submit"
                                                    class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 transition-colors <?= $canManage ? 'text-on-surface-muted hover:bg-surface-container-low' : 'text-on-surface-subtle opacity-60 cursor-not-allowed' ?>"
                                                    <?= $canManage ? '' : 'disabled' ?>>
                                                <?= Icons::x('w-3.5 h-3.5') ?> Cancelar
                                            </button>
                                        </form>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="reservation-empty-filter" class="hidden">
                        <td colspan="7" class="px-4 py-8 text-center text-on-surface-subtle">No hay reservaciones que coincidan con los filtros.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-outline-variant/60 px-4 py-3 text-sm text-on-surface-muted sm:flex-row sm:items-center sm:justify-between">
            <p id="reservation-results-count">Mostrando <?= $totalReservations > 0 ? '1-' . min(4, $totalReservations) : '0-0' ?> de <?= (int) $totalReservations ?> reservaciones</p>
            <div class="flex items-center gap-1">
                <button type="button" id="reservation-page-prev" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 disabled:opacity-60 disabled:cursor-not-allowed"><?= Icons::arrowLeft('w-3.5 h-3.5') ?> Anterior</button>
                <span id="reservation-page-indicator" class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white">1 / 1</span>
                <button type="button" id="reservation-page-next" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1 disabled:opacity-60 disabled:cursor-not-allowed">Siguiente <?= Icons::arrowRight('w-3.5 h-3.5') ?></button>
            </div>
        </div>
    </div>
</section>

<script>
(() => {
    const searchInput = document.getElementById('reservation-search');
    const statusSelect = document.getElementById('reservation-status');
    const prioritySelect = document.getElementById('reservation-priority');
    const periodSelect = document.getElementById('reservation-date');
    const resultsCount = document.getElementById('reservation-results-count');
    const rows = Array.from(document.querySelectorAll('tr[data-reservation-row="1"]'));
    const emptyFilterRow = document.getElementById('reservation-empty-filter');
    const prevPageBtn = document.getElementById('reservation-page-prev');
    const nextPageBtn = document.getElementById('reservation-page-next');
    const pageIndicator = document.getElementById('reservation-page-indicator');
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

    const matchesPriority = (queue, filter) => {
        if (filter === 'all') return true;
        if (filter === 'p1') return queue === 1;
        if (filter === 'p2') return queue === 2;
        if (filter === 'p3plus') return queue >= 3;
        return true;
    };

    const applyFilters = (resetPage = false) => {
        if (!searchInput || !statusSelect || !prioritySelect || !periodSelect || rows.length === 0) return;

        const search = searchInput.value.trim().toLowerCase();
        const status = statusSelect.value;
        const priority = prioritySelect.value;
        const period = periodSelect.value;
        const threshold = periodThreshold(period);
        const filteredRows = [];

        rows.forEach((row) => {
            const rowStatus = (row.dataset.reservationStatus || '').toLowerCase();
            const rowQueue = Number(row.dataset.reservationQueue || '0');
            const rowTs = Number(row.dataset.reservationTs || '0') * 1000;
            const rowSearch = (row.dataset.reservationSearch || '').toLowerCase();

            const okSearch = search === '' || rowSearch.includes(search);
            const okStatus = status === 'all' || rowStatus === status;
            const okPriority = matchesPriority(rowQueue, priority);
            const okPeriod = period === 'all' || rowTs >= threshold;
            const visible = okSearch && okStatus && okPriority && okPeriod;

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
                resultsCount.textContent = `Mostrando ${rangeStart}-${rangeEnd} de ${totalFiltered} reservaciones`;
            } else {
                resultsCount.textContent = 'Mostrando 0-0 de 0 reservaciones';
            }
        }

        if (pageIndicator) pageIndicator.textContent = `${Math.min(currentPage, totalPages)} / ${totalPages}`;
        if (prevPageBtn) prevPageBtn.disabled = currentPage <= 1 || totalFiltered === 0;
        if (nextPageBtn) nextPageBtn.disabled = currentPage >= totalPages || totalFiltered === 0;
    };

    if (searchInput && statusSelect && prioritySelect && periodSelect && rows.length > 0) {
        searchInput.addEventListener('input', () => applyFilters(true));
        statusSelect.addEventListener('change', () => applyFilters(true));
        prioritySelect.addEventListener('change', () => applyFilters(true));
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
})();
</script>
