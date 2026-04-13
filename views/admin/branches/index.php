<?php
// views/admin/branches/index.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$totalBranches  = count($branches);
$activeBranches = count(array_filter($branches, fn(array $b): bool => $b['status'] === 'active'));
$totalResources     = array_sum(array_column($branches, 'resources_count'));
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="p-6 lg:p-8">

    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Administración</p>
            <h1 class="headline-lg text-on-surface">Sedes</h1>
            <p class="body-md mt-1">Gestión de sedes y sucursales de la biblioteca.</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/branches/create"
           class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity inline-flex items-center gap-2">
            <i class="bi bi-plus-lg text-sm"></i> Nueva sede
        </a>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-3">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
            <p class="label-md">Total sedes</p>
            <p class="mt-1 text-2xl font-display font-bold text-on-surface"><?= (int) $totalBranches ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
            <p class="label-md">Sedes activas</p>
            <p class="mt-1 text-2xl font-display font-bold text-primary"><?= (int) $activeBranches ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
            <p class="label-md">Recursos asignados</p>
            <p class="mt-1 text-2xl font-display font-bold text-emerald-700"><?= (int) $totalResources ?></p>
        </article>
    </div>

    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-2">
                <label for="branch-search" class="label-sm">Buscar</label>
                <input id="branch-search" type="text" placeholder="Nombre, código o dirección"
                    class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
            </div>
            <div>
                <label for="branch-status" class="label-sm">Estado</label>
                <select id="branch-status" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todos</option>
                    <option value="active">Activa</option>
                    <option value="inactive">Inactiva</option>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table id="branches-table" class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="name" class="branch-sort inline-flex items-center gap-1 hover:text-primary">
                                Sede <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">Contacto</th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="resources" class="branch-sort inline-flex items-center gap-1 hover:text-primary">
                                Recursos <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="branches-table-body" class="divide-y divide-outline-variant/50 text-sm">
                    <?php if (empty($branches)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-on-surface-subtle">
                                No hay sedes registradas.
                                <a href="<?= BASE_URL ?>/admin/branches/create" class="text-primary hover:underline">Crear la primera sede</a>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($branches as $branch): ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors"
                                data-branch-id="<?= (int) $branch['id'] ?>"
                                data-name="<?= $e(mb_strtolower($branch['name'])) ?>"
                                data-code="<?= $e(mb_strtolower($branch['code'])) ?>"
                                data-address="<?= $e(mb_strtolower($branch['address'])) ?>"
                                data-status="<?= $e($branch['status']) ?>"
                                data-resources="<?= (int) $branch['resources_count'] ?>">
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <?php if ($branch['is_main']): ?>
                                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-primary/15 text-primary">
                                                <i class="bi bi-star-fill text-[11px]"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-surface-container-low text-on-surface-subtle">
                                                <i class="bi bi-building text-[11px]"></i>
                                            </span>
                                        <?php endif; ?>
                                        <div>
                                            <p class="font-semibold text-on-surface">
                                                <?= $e($branch['name']) ?>
                                                <?php if ($branch['is_main']): ?>
                                                    <span class="ml-1 rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold text-primary">Principal</span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-xs text-on-surface-subtle"><?= $e($branch['code']) ?> · <?= $e($branch['address']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-on-surface-muted">
                                    <?php if (!empty($branch['phone'])): ?>
                                        <p><i class="bi bi-telephone text-[11px]"></i> <?= $e($branch['phone']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($branch['email'])): ?>
                                        <p><i class="bi bi-envelope text-[11px]"></i> <?= $e($branch['email']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($branch['manager_name'])): ?>
                                        <p><i class="bi bi-person text-[11px]"></i> <?= $e($branch['manager_name']) ?></p>
                                    <?php endif; ?>
                                    <?php if (empty($branch['phone']) && empty($branch['email']) && empty($branch['manager_name'])): ?>
                                        <span class="text-on-surface-subtle">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted font-medium"><?= (int) $branch['resources_count'] ?></td>
                                <td class="px-4 py-3.5">
                                    <?php if ($branch['status'] === 'active'): ?>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold bg-emerald-100 text-emerald-700">Activa</span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold bg-slate-100 text-slate-600">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <a href="<?= BASE_URL ?>/admin/branches/<?= (int) $branch['id'] ?>/edit"
                                       class="rounded-lg border border-primary/30 bg-primary/5 px-2.5 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors inline-flex items-center gap-1">
                                        <i class="bi bi-pencil-square text-[12px]"></i> Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-outline-variant/60 px-4 py-3 text-sm text-on-surface-muted sm:flex-row sm:items-center sm:justify-between">
            <p id="branches-table-info">Mostrando 0-0 de 0 sedes</p>
            <div class="flex items-center gap-1">
                <button id="branches-prev" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    <i class="bi bi-arrow-left text-[12px]"></i> Anterior
                </button>
                <span id="branches-page-indicator" class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white">1</span>
                <button id="branches-next" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    Siguiente <i class="bi bi-arrow-right text-[12px]"></i>
                </button>
            </div>
        </div>
    </div>

</section>

<script>
(() => {
    const tableBody   = document.getElementById('branches-table-body');
    if (!tableBody) return;

    const rows        = Array.from(tableBody.querySelectorAll('tr[data-branch-id]'));
    const searchInput = document.getElementById('branch-search');
    const statusSel   = document.getElementById('branch-status');
    const prevBtn     = document.getElementById('branches-prev');
    const nextBtn     = document.getElementById('branches-next');
    const pageInd     = document.getElementById('branches-page-indicator');
    const infoEl      = document.getElementById('branches-table-info');
    const sortBtns    = Array.from(document.querySelectorAll('.branch-sort'));

    const state = { search: '', status: 'all', page: 1, pageSize: 10, sortBy: 'name', sortDir: 'asc' };

    const valueForSort = (row, col) => {
        if (col === 'resources') return Number(row.dataset.resources || 0);
        return (row.dataset[col] || '').toString();
    };

    const updateSortLabels = () => {
        sortBtns.forEach((btn) => {
            const icon = btn.querySelector('span');
            if (!icon) return;
            icon.textContent = btn.dataset.sort !== state.sortBy ? '⇅'
                : state.sortDir === 'asc' ? '↑' : '↓';
        });
    };

    const getFiltered = () => {
        const q = state.search.trim().toLowerCase();
        return rows.filter((row) => {
            const matchSearch = !q || [row.dataset.name, row.dataset.code, row.dataset.address].join(' ').includes(q);
            const matchStatus = state.status === 'all' || row.dataset.status === state.status;
            return matchSearch && matchStatus;
        });
    };

    const render = () => {
        const filtered   = getFiltered().sort((a, b) => {
            const va = valueForSort(a, state.sortBy);
            const vb = valueForSort(b, state.sortBy);
            if (typeof va === 'number') return state.sortDir === 'asc' ? va - vb : vb - va;
            return state.sortDir === 'asc'
                ? String(va).localeCompare(String(vb), 'es')
                : String(vb).localeCompare(String(va), 'es');
        });

        const total      = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / state.pageSize));
        state.page       = Math.min(state.page, totalPages);
        const start      = (state.page - 1) * state.pageSize;
        const end        = Math.min(start + state.pageSize, total);

        rows.forEach((r) => { r.style.display = 'none'; });
        filtered.slice(start, end).forEach((r) => { r.style.display = ''; tableBody.appendChild(r); });

        infoEl.textContent = `Mostrando ${total === 0 ? 0 : start + 1}-${total === 0 ? 0 : end} de ${total} sedes`;
        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= totalPages;
        [prevBtn, nextBtn].forEach((btn) => {
            btn.classList.toggle('opacity-60', btn.disabled);
            btn.classList.toggle('cursor-not-allowed', btn.disabled);
        });
        pageInd.textContent = `${state.page}/${totalPages}`;
        updateSortLabels();
    };

    searchInput?.addEventListener('input', (e) => { state.search = e.target.value; state.page = 1; render(); });
    statusSel?.addEventListener('change', (e) => { state.status = e.target.value; state.page = 1; render(); });
    prevBtn?.addEventListener('click', () => { if (state.page > 1) { state.page--; render(); } });
    nextBtn?.addEventListener('click', () => { state.page++; render(); });
    sortBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const col = btn.dataset.sort;
            if (state.sortBy === col) { state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc'; }
            else { state.sortBy = col; state.sortDir = 'asc'; }
            state.page = 1;
            render();
        });
    });

    render();
})();
</script>
