<?php
declare(strict_types=1);

$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$statusBadge = static function (string $status): array {
    return match ($status) {
        'active'   => ['Activo', 'bg-blue-100 text-blue-700 border border-blue-200/70'],
        'overdue'  => ['Vencido', 'bg-amber-100 text-amber-700 border border-amber-200/70'],
        'returned' => ['Devuelto', 'bg-emerald-100 text-emerald-700 border border-emerald-200/70'],
        'lost'     => ['Perdido', 'bg-red-100 text-red-700 border border-red-200/70'],
        default    => [ucfirst($status), 'bg-slate-100 text-slate-700 border border-slate-200/70'],
    };
};

$statusLabel = static fn(string $status): string => match ($status) {
    'active' => 'Activos',
    'overdue' => 'Vencidos',
    'returned' => 'Devueltos',
    'lost' => 'Perdidos',
    default => 'Todos',
};

$pagination = is_array($pagination ?? null) ? $pagination : [];
$currentPage = max(1, (int) ($pagination['page'] ?? 1));
$totalPages = max(1, (int) ($pagination['total_pages'] ?? 1));
$total = (int) ($pagination['total'] ?? count($loans ?? []));
$from = (int) ($pagination['from'] ?? ($total > 0 ? 1 : 0));
$to = (int) ($pagination['to'] ?? count($loans ?? []));
$maxRenewals = (int) ($settings['max_renewals'] ?? 3);
$flashSuccess = \Core\Session::getFlash('success');
$flashError = \Core\Session::getFlash('error');
$flashInfo = \Core\Session::getFlash('info');
$memberName = trim((string) ($auth_user['name'] ?? 'Usuario'));

$cards = [
    ['key' => 'active', 'label' => 'Activos', 'tone' => 'text-blue-700 bg-blue-100/70', 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ['key' => 'overdue', 'label' => 'Vencidos', 'tone' => 'text-amber-700 bg-amber-100/70', 'icon' => 'M12 6v6l3 1.5m6-1.5a9 9 0 11-18 0 9 9 0 0118 0z'],
    ['key' => 'returned', 'label' => 'Devueltos', 'tone' => 'text-emerald-700 bg-emerald-100/70', 'icon' => 'M4.5 12.75l6 6 9-13.5'],
    ['key' => 'lost', 'label' => 'Perdidos', 'tone' => 'text-red-700 bg-red-100/70', 'icon' => 'M12 9v3.75m0 3.75h.007v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
];
?>

<section class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto" id="loans-page">
    <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 sm:p-6 shadow-ambient">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <p class="label-sm text-on-surface-subtle">Mi zona</p>
                <h1 class="headline-lg text-on-surface">Mis préstamos</h1>
                <p class="mt-1 text-sm text-on-surface-muted">Consulta tus préstamos y gestiona renovaciones.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center rounded-full border border-outline-variant px-3 py-1.5 text-on-surface-muted">
                    Socio: <strong class="ml-1 text-on-surface\"><?= $e($memberName) ?></strong>
                </span>
                <span class="inline-flex items-center rounded-full border border-outline-variant px-3 py-1.5 text-on-surface-muted">
                    Registros por página: <strong class="ml-1 text-on-surface">5</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="mt-5 space-y-3" aria-live="polite">
        <?php if ($flashSuccess): ?>
            <div class="rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><?= $e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="rounded-2xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900"><?= $e($flashError) ?></div>
        <?php endif; ?>
        <?php if ($flashInfo): ?>
            <div class="rounded-2xl border border-blue-300 bg-blue-50 px-4 py-3 text-sm text-blue-900"><?= $e($flashInfo) ?></div>
        <?php endif; ?>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($cards as $card): ?>
            <article class="rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-on-surface-muted"><?= $card['label'] ?></p>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full <?= $card['tone'] ?>">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $card['icon'] ?>"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-2 text-3xl font-display font-bold text-on-surface"><?= (int) ($summary[$card['key']] ?? 0) ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($total === 0): ?>
        <div class="mt-6 rounded-2xl border border-outline-variant/60 bg-white p-10 text-center shadow-ambient">
            <svg class="mx-auto mb-3 h-12 w-12 text-on-surface-subtle/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
            </svg>
            <p class="text-lg font-semibold text-on-surface">No tienes préstamos registrados</p>
            <p class="mt-1 text-sm text-on-surface-muted">Cuando solicites un préstamo aparecerá aquí.</p>
        </div>
    <?php else: ?>
        <div class="mt-6 rounded-2xl border border-outline-variant/60 bg-white px-4 py-3 text-sm text-on-surface-muted shadow-ambient">
            Mostrando <span class="font-semibold text-on-surface"><?= $from ?></span> a
            <span class="font-semibold text-on-surface"><?= $to ?></span> de
            <span class="font-semibold text-on-surface"><?= $total ?></span> préstamos.
        </div>

        <div class="mt-6 rounded-2xl border border-outline-variant/60 bg-white p-4 sm:p-5 shadow-ambient">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Filtro de estado de préstamos">
                    <button type="button" class="loan-filter-btn rounded-full bg-primary text-white px-3.5 py-1.5 text-xs font-semibold" data-filter="all" aria-pressed="true">Todos (<?= (int) $total ?>)</button>
                    <?php foreach ($cards as $card): ?>
                        <button type="button" class="loan-filter-btn rounded-full border border-outline-variant px-3.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:text-primary hover:border-primary transition-colors" data-filter="<?= $e($card['key']) ?>" aria-pressed="false">
                            <?= $e($statusLabel($card['key'])) ?> (<?= (int) ($summary[$card['key']] ?? 0) ?>)
                        </button>
                    <?php endforeach; ?>
                </div>
                <label class="relative block w-full lg:w-80">
                    <span class="sr-only">Buscar préstamo</span>
                    <input id="loan-search" type="search" placeholder="Buscar por título o autor"
                           class="h-10 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-10 text-sm text-on-surface placeholder:text-on-surface-subtle focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-on-surface-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                </label>
            </div>
        </div>

        <div id="loan-empty-filtered" class="mt-4 hidden rounded-2xl border border-dashed border-outline-variant bg-surface-container-lowest p-8 text-center">
            <p class="text-sm font-semibold text-on-surface">No hay resultados para ese filtro</p>
            <p class="mt-1 text-xs text-on-surface-subtle">Prueba con otro estado o cambia el texto de búsqueda.</p>
        </div>

        <div class="mt-4 space-y-4 lg:hidden" id="loan-mobile-list">
            <?php foreach ($loans as $loan):
                $status = (string) ($loan['status'] ?? '');
                [$badgeLabel, $badgeClass] = $statusBadge($status);
                $loanDate = (new DateTime((string) $loan['loan_at']))->format('d/m/Y');
                $dueDate = (new DateTime((string) $loan['due_at']))->format('d/m/Y');
                $title = (string) ($loan['book_title'] ?? 'Sin título');
                $authors = (string) ($loan['book_authors'] ?? '');
                $searchText = mb_strtolower(trim($title . ' ' . $authors));
            ?>
                <article class="loan-item rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient" data-status="<?= $e($status) ?>" data-search="<?= $e($searchText) ?>">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-on-surface truncate"><?= $e($title) ?></p>
                            <p class="text-xs text-on-surface-subtle"><?= $e($authors) ?></p>
                        </div>
                        <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold <?= $badgeClass ?>"><?= $e($badgeLabel) ?></span>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-on-surface-muted">
                        <div><span class="font-medium text-on-surface-subtle">Prestado:</span> <?= $e($loanDate) ?></div>
                        <div><span class="font-medium text-on-surface-subtle">Vence:</span> <?= $e($dueDate) ?></div>
                        <div><span class="font-medium text-on-surface-subtle">Renovaciones:</span> <?= (int) ($loan['renewals_count'] ?? 0) ?></div>
                        <?php if (!empty($loan['returned_at'])): ?>
                            <div><span class="font-medium text-on-surface-subtle">Devuelto:</span> <?= $e((new DateTime((string) $loan['returned_at']))->format('d/m/Y')) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php if ((string) ($loan['support_type'] ?? '') === 'digital' && !empty($loan['digital_url']) && in_array($status, ['active', 'overdue'], true)): ?>
                            <a href="<?= BASE_URL ?>/account/digital-resources/<?= (int) ($loan['resource_id'] ?? 0) ?>/read" target="_blank" rel="noopener"
                               class="inline-flex items-center rounded-lg border border-primary/40 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors">
                                Leer digital
                            </a>
                        <?php endif; ?>

                        <?php if (in_array($status, ['active', 'overdue'], true) && (int) ($loan['renewals_count'] ?? 0) < $maxRenewals): ?>
                            <form method="POST" action="<?= BASE_URL ?>/account/loans/<?= (int) $loan['id'] ?>/renew">
                                <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                <button type="submit" class="inline-flex items-center rounded-lg border border-primary/40 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors">
                                    Renovar
                                </button>
                            </form>
                        <?php elseif (in_array($status, ['active', 'overdue'], true)): ?>
                            <span class="inline-flex items-center rounded-lg border border-outline-variant px-3 py-1.5 text-xs text-on-surface-subtle">Renovaciones agotadas</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($loan['notes'])): ?>
                        <p class="mt-2 text-xs text-on-surface-muted italic"><?= $e($loan['notes']) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="mt-4 hidden lg:block overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient" id="loan-desktop-list">
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
                            $status = (string) ($loan['status'] ?? '');
                            [$badgeLabel, $badgeClass] = $statusBadge($status);
                            $title = (string) ($loan['book_title'] ?? 'Sin título');
                            $authors = (string) ($loan['book_authors'] ?? '');
                            $searchText = mb_strtolower(trim($title . ' ' . $authors));
                        ?>
                            <tr class="loan-item hover:bg-surface-container-low/70 transition-colors" data-status="<?= $e($status) ?>" data-search="<?= $e($searchText) ?>">
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-on-surface"><?= $e($title) ?></p>
                                    <p class="text-xs text-on-surface-subtle"><?= $e($authors) ?></p>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-on-surface-muted"><?= $e((new DateTime((string) $loan['loan_at']))->format('d/m/Y H:i')) ?></td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-on-surface-muted"><?= $e((new DateTime((string) $loan['due_at']))->format('d/m/Y H:i')) ?></td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-on-surface-muted"><?= !empty($loan['returned_at']) ? $e((new DateTime((string) $loan['returned_at']))->format('d/m/Y H:i')) : '-' ?></td>
                                <td class="px-4 py-3.5 text-center text-on-surface-muted"><?= (int) ($loan['renewals_count'] ?? 0) ?></td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $badgeClass ?>"><?= $e($badgeLabel) ?></span>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted max-w-[220px] truncate"><?= $e($loan['notes'] ?? '-') ?></td>
                                <td class="px-4 py-3.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <?php if ((string) ($loan['support_type'] ?? '') === 'digital' && !empty($loan['digital_url']) && in_array($status, ['active', 'overdue'], true)): ?>
                                            <a href="<?= BASE_URL ?>/account/digital-resources/<?= (int) ($loan['resource_id'] ?? 0) ?>/read" target="_blank" rel="noopener"
                                               class="inline-flex rounded-lg border border-primary/40 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors whitespace-nowrap">
                                                Leer digital
                                            </a>
                                        <?php endif; ?>

                                        <?php if (in_array($status, ['active', 'overdue'], true) && (int) ($loan['renewals_count'] ?? 0) < $maxRenewals): ?>
                                            <form method="POST" action="<?= BASE_URL ?>/account/loans/<?= (int) $loan['id'] ?>/renew">
                                                <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                                <button type="submit" class="rounded-lg border border-primary/40 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors whitespace-nowrap">
                                                    Renovar
                                                </button>
                                            </form>
                                        <?php elseif (in_array($status, ['active', 'overdue'], true)): ?>
                                            <span class="inline-flex rounded-lg border border-outline-variant px-3 py-1.5 text-xs text-on-surface-subtle whitespace-nowrap">Renovaciones agotadas</span>
                                        <?php else: ?>
                                            <span class="text-xs text-on-surface-subtle">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-5 flex flex-wrap items-center justify-center gap-2" aria-label="Paginación de préstamos">
                <?php if ($currentPage > 1): ?>
                    <a href="<?= BASE_URL ?>/account/loans?page=<?= $currentPage - 1 ?>"
                       class="rounded-lg border border-outline-variant px-3 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                        Anterior
                    </a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php if ($p === 1 || $p === $totalPages || abs($p - $currentPage) <= 1): ?>
                        <a href="<?= BASE_URL ?>/account/loans?page=<?= $p ?>"
                           class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors <?= $p === $currentPage ? 'bg-primary text-white' : 'border border-outline-variant text-on-surface-muted hover:bg-surface-container-low' ?>">
                            <?= $p ?>
                        </a>
                    <?php elseif ($p === 2 && $currentPage > 3): ?>
                        <span class="px-1 text-xs text-on-surface-subtle">...</span>
                    <?php elseif ($p === $totalPages - 1 && $currentPage < $totalPages - 2): ?>
                        <span class="px-1 text-xs text-on-surface-subtle">...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= BASE_URL ?>/account/loans?page=<?= $currentPage + 1 ?>"
                       class="rounded-lg border border-outline-variant px-3 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                        Siguiente
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php if ($total > 0): ?>
<script>
(() => {
    const filterButtons = Array.from(document.querySelectorAll('.loan-filter-btn'));
    const searchInput = document.getElementById('loan-search');
    const loanItems = Array.from(document.querySelectorAll('.loan-item'));
    const emptyState = document.getElementById('loan-empty-filtered');

    let activeFilter = 'all';

    const normalize = (value) => (value || '').toString().toLowerCase().trim();

    const applyFilters = () => {
        const query = normalize(searchInput ? searchInput.value : '');
        let visibleCount = 0;

        loanItems.forEach((item) => {
            const status = normalize(item.getAttribute('data-status'));
            const searchText = normalize(item.getAttribute('data-search'));
            const statusMatch = activeFilter === 'all' || status === activeFilter;
            const searchMatch = query === '' || searchText.includes(query);
            const visible = statusMatch && searchMatch;

            item.classList.toggle('hidden', !visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount > 0);
        }
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeFilter = normalize(button.getAttribute('data-filter')) || 'all';

            filterButtons.forEach((btn) => {
                const isActive = btn === button;
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                btn.classList.toggle('bg-primary', isActive);
                btn.classList.toggle('text-white', isActive);
                btn.classList.toggle('border', !isActive);
                btn.classList.toggle('border-outline-variant', !isActive);
                btn.classList.toggle('text-on-surface-muted', !isActive);
            });

            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    applyFilters();
})();
</script>
<?php endif; ?>
