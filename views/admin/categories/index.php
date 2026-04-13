<?php
// views/admin/categories/index.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="p-6 lg:p-8">

    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Administración</p>
            <h1 class="headline-lg text-on-surface">Categorías</h1>
            <p class="body-md mt-1">Gestión de categorías para clasificar y organizar recursos.</p>
        </div>
        <button type="button" id="btn-nueva-categoria"
                class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity inline-flex items-center gap-2">
            <i class="bi bi-plus-lg text-sm"></i> Nueva categoría
        </button>
    </div>

    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-2">
                <label for="cat-search" class="label-sm">Buscar</label>
                <input id="cat-search" type="text" placeholder="Nombre o descripcion"
                    class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
            </div>
            <div>
                <label for="cat-page-size" class="label-sm">Filas</label>
                <select id="cat-page-size" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table id="cat-table" class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="name" class="cat-sort inline-flex items-center gap-1 hover:text-primary">
                                Categoría <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="resources" class="cat-sort inline-flex items-center gap-1 hover:text-primary">
                                Recursos <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="cat-table-body" class="divide-y divide-outline-variant/50 text-sm">
                    <?php foreach ($categories as $category): ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors"
                            data-cat-id="<?= (int) $category['id'] ?>"
                            data-name="<?= $e(mb_strtolower($category['name'])) ?>"
                            data-desc="<?= $e(mb_strtolower($category['description'] ?? '')) ?>"
                            data-resources="<?= (int) $category['resources_count'] ?>">
                            <td class="px-4 py-3.5">
                                <p class="font-semibold text-on-surface" data-cell="name"><?= $e($category['name']) ?></p>
                                <?php if (!empty($category['description'])): ?>
                                    <p class="mt-1 text-xs text-on-surface-muted" data-cell="desc"><?= $e($category['description']) ?></p>
                                <?php else: ?>
                                    <p class="mt-1 text-xs text-on-surface-muted hidden" data-cell="desc"></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-muted font-medium" data-cell="resources"><?= (int) $category['resources_count'] ?></td>
                            <td class="px-4 py-3.5 text-right">
                                <button type="button"
                                        class="js-edit-category rounded-lg border border-primary/30 bg-primary/5 px-2.5 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors inline-flex items-center gap-1"
                                        data-id="<?= (int) $category['id'] ?>"
                                        data-name="<?= $e($category['name']) ?>"
                                        data-description="<?= $e($category['description'] ?? '') ?>">
                                    <i class="bi bi-pencil-square text-[12px]"></i> Editar
                                </button>
                                <?php if ((int) $category['resources_count'] > 0): ?>
                                    <button type="button"
                                            class="ml-1 rounded-lg border border-slate-200 bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-500 inline-flex items-center gap-1 cursor-not-allowed"
                                            title="No se puede eliminar una categoría con recursos asociados"
                                            disabled>
                                        <i class="bi bi-lock text-[12px]"></i> En uso
                                    </button>
                                <?php else: ?>
                                <form id="delete-category-form-<?= (int) $category['id'] ?>" method="POST" action="<?= BASE_URL ?>/admin/categories/<?= (int) $category['id'] ?>/delete" class="inline-block ml-1">
                                    <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                                    <button type="button"
                                            class="js-delete-category rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 inline-flex items-center gap-1"
                                            data-id="<?= (int) $category['id'] ?>"
                                            data-name="<?= $e($category['name']) ?>">
                                        <i class="bi bi-trash text-[12px]"></i> Eliminar
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-outline-variant/60 px-4 py-3 text-sm text-on-surface-muted sm:flex-row sm:items-center sm:justify-between">
            <p id="cat-table-info">Mostrando 0-0 de 0 categorías</p>
            <div class="flex items-center gap-1">
                <button id="cat-prev" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    <i class="bi bi-arrow-left text-[12px]"></i> Anterior
                </button>
                <span id="cat-page-indicator" class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white">1</span>
                <button id="cat-next" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    Siguiente <i class="bi bi-arrow-right text-[12px]"></i>
                </button>
            </div>
        </div>
    </div>

    <div id="modal-nueva-categoria" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-primary/35 backdrop-blur-[1px]" data-close-cat-modal></div>
        <div class="absolute inset-0 p-4 sm:p-6 flex items-center justify-center">
            <div class="relative w-full max-w-lg flex flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">
                <div class="flex h-12 items-center justify-between border-b border-outline-variant/60 bg-gradient-to-b from-slate-100 to-slate-200 pl-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-primary/15 text-primary">
                            <i class="bi bi-tag text-[12px]"></i>
                        </span>
                        <p class="truncate text-sm font-semibold text-slate-700">Nueva categoría</p>
                    </div>
                    <button type="button" class="inline-flex h-12 w-12 items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors" data-close-cat-modal aria-label="Cerrar">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/admin/categories" class="p-5 space-y-4">
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                    <div>
                        <label class="label-sm" for="new-name">Nombre <span class="text-red-500">*</span></label>
                        <input id="new-name" name="name" type="text" required
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none"
                               placeholder="Ej. Literatura">
                    </div>
                    <div>
                        <label class="label-sm" for="new-description">Descripcion</label>
                        <input id="new-description" name="description" type="text"
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none"
                               placeholder="Descripcion opcional">
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" data-close-cat-modal
                                class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors inline-flex items-center gap-2">
                            <i class="bi bi-x-lg text-sm"></i> Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity inline-flex items-center gap-2">
                            <i class="bi bi-plus-lg text-sm"></i> Crear categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-editar-categoria" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-primary/35 backdrop-blur-[1px]" data-close-edit-modal></div>
        <div class="absolute inset-0 p-4 sm:p-6 flex items-center justify-center">
            <div class="relative w-full max-w-lg flex flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">
                <div class="flex h-12 items-center justify-between border-b border-outline-variant/60 bg-gradient-to-b from-slate-100 to-slate-200 pl-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-primary/15 text-primary">
                            <i class="bi bi-tag text-[12px]"></i>
                        </span>
                        <p id="edit-modal-title" class="truncate text-sm font-semibold text-slate-700">Editar categoría</p>
                    </div>
                    <button type="button" class="inline-flex h-12 w-12 items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors" data-close-edit-modal aria-label="Cerrar">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <form id="edit-cat-form" method="POST" action="" class="p-5 space-y-4">
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                    <div>
                        <label class="label-sm" for="edit-name">Nombre <span class="text-red-500">*</span></label>
                        <input id="edit-name" name="name" type="text" required
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="label-sm" for="edit-description">Descripcion</label>
                        <input id="edit-description" name="description" type="text"
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none"
                               placeholder="Descripcion opcional">
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" data-close-edit-modal
                                class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors inline-flex items-center gap-2">
                            <i class="bi bi-x-lg text-sm"></i> Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors inline-flex items-center gap-2">
                            <i class="bi bi-floppy text-sm"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-eliminar-categoria" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-primary/35 backdrop-blur-[1px]" data-close-delete-modal></div>
        <div class="absolute inset-0 p-4 sm:p-6 flex items-center justify-center">
            <div class="relative w-full max-w-md flex flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">
                <div class="flex h-12 items-center justify-between border-b border-outline-variant/60 bg-gradient-to-b from-red-50 to-red-100 pl-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-red-100 text-red-700">
                            <i class="bi bi-exclamation-triangle text-[12px]"></i>
                        </span>
                        <p class="truncate text-sm font-semibold text-red-800">Confirmar eliminación</p>
                    </div>
                    <button type="button" class="inline-flex h-12 w-12 items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors" data-close-delete-modal aria-label="Cerrar">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <div class="p-5 space-y-3">
                    <p class="text-sm text-on-surface">
                        ¿Eliminar la categoría <span id="delete-category-name" class="font-semibold text-red-700">—</span>?
                    </p>
                    <p class="text-xs text-on-surface-muted">
                        Esta acción no se puede deshacer.
                    </p>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" data-close-delete-modal
                                class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors inline-flex items-center gap-2">
                            <i class="bi bi-x-lg text-sm"></i> Cancelar
                        </button>
                        <button id="btn-confirm-delete-category" type="button"
                                class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors inline-flex items-center gap-2">
                            <i class="bi bi-trash text-sm"></i> Sí, eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

<script>
(() => {
    // Tabla con filtro, ordenamiento y paginación
    const tableBody  = document.getElementById('cat-table-body');
    if (!tableBody) return;

    const rows        = Array.from(tableBody.querySelectorAll('tr'));
    const searchInput = document.getElementById('cat-search');
    const pageSizeEl  = document.getElementById('cat-page-size');
    const prevBtn     = document.getElementById('cat-prev');
    const nextBtn     = document.getElementById('cat-next');
    const pageInd     = document.getElementById('cat-page-indicator');
    const infoEl      = document.getElementById('cat-table-info');
    const sortBtns    = Array.from(document.querySelectorAll('.cat-sort'));

    const state = { search: '', page: 1, pageSize: 5, sortBy: 'name', sortDir: 'asc' };

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
            if (!q) return true;
            return [row.dataset.name, row.dataset.desc].join(' ').includes(q);
        });
    };

    const render = () => {
        const filtered = getFiltered().sort((a, b) => {
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

        infoEl.textContent = `Mostrando ${total === 0 ? 0 : start + 1}-${total === 0 ? 0 : end} de ${total} categorías`;
        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= totalPages;
        prevBtn.classList.toggle('opacity-60', prevBtn.disabled);
        prevBtn.classList.toggle('cursor-not-allowed', prevBtn.disabled);
        nextBtn.classList.toggle('opacity-60', nextBtn.disabled);
        nextBtn.classList.toggle('cursor-not-allowed', nextBtn.disabled);
        pageInd.textContent = `${state.page}/${totalPages}`;
        updateSortLabels();
    };

    searchInput?.addEventListener('input', (e) => { state.search = e.target.value; state.page = 1; render(); });
    pageSizeEl?.addEventListener('change', (e) => { state.pageSize = Number(e.target.value); state.page = 1; render(); });
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

    // Modales
    const openModal = (modal) => {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };
    const closeModal = (modal) => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    // Modal nueva categoría
    const modalNueva = document.getElementById('modal-nueva-categoria');
    document.getElementById('btn-nueva-categoria')?.addEventListener('click', () => openModal(modalNueva));
    modalNueva?.querySelectorAll('[data-close-cat-modal]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modalNueva));
    });

    // Modal editar categoría
    const modalEditar   = document.getElementById('modal-editar-categoria');
    const editForm      = document.getElementById('edit-cat-form');
    const editTitle     = document.getElementById('edit-modal-title');
    const editNameEl    = document.getElementById('edit-name');
    const editDescEl    = document.getElementById('edit-description');
    const modalDelete   = document.getElementById('modal-eliminar-categoria');
    const deleteNameEl  = document.getElementById('delete-category-name');
    const btnConfirmDelete = document.getElementById('btn-confirm-delete-category');
    const BASE          = '<?= BASE_URL ?>';
    let pendingDeleteForm = null;

    document.querySelectorAll('.js-edit-category').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id   = btn.dataset.id;
            const name = btn.dataset.name;
            if (editTitle)   editTitle.textContent = `Editar · ${name}`;
            if (editForm)    editForm.action = `${BASE}/admin/categories/${id}`;
            if (editNameEl)  editNameEl.value = name;
            if (editDescEl)  editDescEl.value = btn.dataset.description || '';
            openModal(modalEditar);
        });
    });

    modalEditar?.querySelectorAll('[data-close-edit-modal]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modalEditar));
    });

    // Modal eliminar categoría
    document.querySelectorAll('.js-delete-category').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name || '';
            pendingDeleteForm = document.getElementById(`delete-category-form-${id}`);
            if (deleteNameEl) deleteNameEl.textContent = `«${name}»`;
            openModal(modalDelete);
        });
    });

    modalDelete?.querySelectorAll('[data-close-delete-modal]').forEach((el) => {
        el.addEventListener('click', () => {
            pendingDeleteForm = null;
            closeModal(modalDelete);
        });
    });

    btnConfirmDelete?.addEventListener('click', () => {
        if (pendingDeleteForm) pendingDeleteForm.submit();
    });

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (modalNueva && !modalNueva.classList.contains('hidden')) closeModal(modalNueva);
        if (modalEditar && !modalEditar.classList.contains('hidden')) closeModal(modalEditar);
        if (modalDelete && !modalDelete.classList.contains('hidden')) {
            pendingDeleteForm = null;
            closeModal(modalDelete);
        }
    });
})();
</script>
