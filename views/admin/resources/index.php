<?php
// views/admin/resources/index.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$statusClass = static function (string $status): string {
    return match ($status) {
        'active' => 'bg-emerald-100 text-emerald-700',
        'low' => 'bg-amber-100 text-amber-700',
        'out' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-700',
    };
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'active' => 'Disponible',
        'low' => 'Pocas copias',
        'out' => 'Sin stock',
        default => 'Estado',
    };
};

$categories = [];
foreach ($books as $book) {
    $categories[] = (string) $book['category'];
}
$categories = array_values(array_unique($categories));
sort($categories);

$typeLabels = [
    'book'        => 'Libro',
    'ebook'       => 'Digital',
    'journal'     => 'Revista',
    'thesis'      => 'Tesis',
    'map'         => 'Otro',
    'score'       => 'Otro',
    'audiovisual' => 'Otro',
    'game'        => 'Otro',
    'kit'         => 'Otro',
    'other'       => 'Otro',
];
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Administracion</p>
            <h1 class="headline-lg text-on-surface">Recursos</h1>
            <p class="body-md mt-1">Gestión del catálogo de recursos físicos, digitales y otros materiales.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= BASE_URL ?>/admin/resources/report/pdf"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                <iconify-icon icon="mdi:file-pdf-box"></iconify-icon> PDF
            </a>
            <a href="<?= BASE_URL ?>/admin/resources/export"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                <iconify-icon icon="mdi:file-excel-box"></iconify-icon> Excel
            </a>
            <a href="<?= BASE_URL ?>/admin/resources/create" class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity inline-flex items-center gap-2">
                <i class="bi bi-plus-lg text-sm"></i> Nuevo recurso
            </a>
        </div>
    </div>

    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <div class="grid gap-3 md:grid-cols-5">
            <div class="md:col-span-2">
                <label for="resource-search" class="label-sm">Buscar</label>
                <input id="resource-search" type="text" placeholder="Titulo, responsable, ISBN o codigo"
                    class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
            </div>
            <div>
                <label for="resource-category" class="label-sm">Categoria</label>
                <select id="resource-category" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todas</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $e(mb_strtolower($category)) ?>"><?= $e($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="resource-status" class="label-sm">Estado</label>
                <select id="resource-status" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todos</option>
                    <option value="active">Disponible</option>
                    <option value="low">Pocas copias</option>
                    <option value="out">Sin stock</option>
                </select>
            </div>
            <div>
                <label for="resource-page-size" class="label-sm">Filas</label>
                <select id="resource-page-size" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table id="resources-table" class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="title" class="resource-sort inline-flex items-center gap-1 hover:text-primary">
                                Recurso <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="category" class="resource-sort inline-flex items-center gap-1 hover:text-primary">
                                Categoria <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold hidden md:table-cell">
                            <button type="button" data-sort="type" class="resource-sort inline-flex items-center gap-1 hover:text-primary">
                                Tipo <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="available" class="resource-sort inline-flex items-center gap-1 hover:text-primary">
                                Inventario <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="status" class="resource-sort inline-flex items-center gap-1 hover:text-primary">
                                Estado <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="resources-table-body" class="divide-y divide-outline-variant/50 text-sm">
                    <?php foreach ($books as $book): ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors"
                            data-resource-id="<?= (int) $book['id'] ?>"
                            data-title="<?= $e(mb_strtolower($book['title'])) ?>"
                            data-author="<?= $e(mb_strtolower($book['author'])) ?>"
                            data-code="<?= $e(mb_strtolower($book['code'])) ?>"
                            data-category="<?= $e(mb_strtolower($book['category'])) ?>"
                            data-type="<?= $e(mb_strtolower($book['resource_type'] ?? 'book')) ?>"
                            data-status="<?= $e($book['status']) ?>"
                            data-available="<?= (int) $book['available'] ?>"
                            data-copies="<?= (int) $book['copies'] ?>">
                            <td class="px-4 py-3.5">
                                <p class="font-semibold text-on-surface" data-cell="title"><?= $e($book['title']) ?></p>
                                <p class="text-xs text-on-surface-subtle"><span data-cell="author"><?= $e($book['author']) ?></span> · <span data-cell="code"><?= $e($book['code']) ?></span></p>
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-muted" data-cell="category"><?= $e($book['category']) ?></td>
                            <td class="px-4 py-3.5 hidden md:table-cell">
                                <span class="inline-flex rounded-full bg-surface-container-low px-2.5 py-1 text-xs font-semibold text-on-surface-muted" data-cell="type">
                                    <?= $e($typeLabels[$book['resource_type'] ?? 'book'] ?? 'Otro') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-muted" data-cell="inventory">
                                <?= (int) $book['available'] ?> / <?= (int) $book['copies'] ?> disponibles
                            </td>
                            <td class="px-4 py-3.5">
                                <span data-cell="status" class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass($book['status']) ?>">
                                    <?= $e($statusLabel($book['status'])) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="<?= BASE_URL ?>/catalog/<?= (int) $book['id'] ?>" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors inline-flex items-center gap-1">
                                    <i class="bi bi-eye text-[12px]"></i> Ver
                                </a>
                                <button type="button"
                                        data-edit-url="<?= BASE_URL ?>/admin/resources/<?= (int) $book['id'] ?>/edit"
                                        data-edit-title="<?= $e($book['title']) ?>"
                                        class="ml-1 rounded-lg border border-primary/30 bg-primary/5 px-2.5 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors js-open-resource-edit-modal inline-flex items-center gap-1">
                                    <i class="bi bi-pencil-square text-[12px]"></i> Editar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-outline-variant/60 px-4 py-3 text-sm text-on-surface-muted sm:flex-row sm:items-center sm:justify-between">
            <p id="resources-table-info">Mostrando 0-0 de 0 recursos</p>
            <div class="flex items-center gap-1">
                <button id="resources-prev" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    <i class="bi bi-arrow-left text-[12px]"></i> Anterior
                </button>
                <span id="resources-page-indicator" class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white">1</span>
                <button id="resources-next" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    Siguiente <i class="bi bi-arrow-right text-[12px]"></i>
                </button>
            </div>
        </div>
    </div>

    <div id="resource-edit-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-primary/35 backdrop-blur-[1px]" data-close-resource-edit-modal></div>
        <div class="absolute inset-0 p-4 sm:p-6 flex items-center justify-center">
            <div class="relative flex h-[92vh] w-[96vw] max-w-[1500px] flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">
                <div class="flex h-12 items-center justify-between border-b border-outline-variant/60 bg-gradient-to-b from-slate-100 to-slate-200 pl-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-primary/15 text-primary">
                            <i class="bi bi-book text-[12px]"></i>
                        </span>
                        <p id="resource-edit-modal-title" class="truncate text-sm font-semibold text-slate-700">Editor de recurso</p>
                    </div>
                    <button type="button" class="inline-flex h-12 w-12 items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors" data-close-resource-edit-modal aria-label="Cerrar ventana">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1">
                    <iframe id="resource-edit-frame" title="Editar recurso" class="h-full w-full bg-white" src="about:blank"></iframe>
                </div>
                <div class="flex min-h-14 items-center justify-between border-t border-outline-variant/70 bg-slate-100/95 px-4 py-2.5">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1">
                            <i class="bi bi-check2 text-[12px]"></i> Guardado global
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1">
                            <i class="bi bi-arrow-repeat text-[12px]"></i> RDA + MARC21
                        </span>
                    </div>
                    <button type="button" id="resource-edit-modal-save" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                        <i class="bi bi-floppy text-sm"></i> Guardar todo
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(() => {
    const tableBody = document.getElementById('resources-table-body');
    if (!tableBody) return;

    const rows = Array.from(tableBody.querySelectorAll('tr'));
    const searchInput = document.getElementById('resource-search');
    const categorySelect = document.getElementById('resource-category');
    const statusSelect = document.getElementById('resource-status');
    const pageSizeSelect = document.getElementById('resource-page-size');
    const prevBtn = document.getElementById('resources-prev');
    const nextBtn = document.getElementById('resources-next');
    const pageIndicator = document.getElementById('resources-page-indicator');
    const info = document.getElementById('resources-table-info');
    const sortButtons = Array.from(document.querySelectorAll('.resource-sort'));
    const editModal = document.getElementById('resource-edit-modal');
    const editFrame = document.getElementById('resource-edit-frame');
    const editModalTitle = document.getElementById('resource-edit-modal-title');
    const editModalSaveButton = document.getElementById('resource-edit-modal-save');
    const openEditButtons = Array.from(document.querySelectorAll('.js-open-resource-edit-modal'));
    const closeEditButtons = Array.from(document.querySelectorAll('[data-close-resource-edit-modal]'));

    const state = {
        search: '',
        category: 'all',
        status: 'all',
        page: 1,
        pageSize: Number(pageSizeSelect?.value || 5),
        sortBy: 'title',
        sortDir: 'asc',
    };

    const statusRank = { active: 1, low: 2, out: 3 };
    const statusClasses = {
        active: 'bg-emerald-100 text-emerald-700',
        low: 'bg-amber-100 text-amber-700',
        out: 'bg-red-100 text-red-700',
    };
    const statusLabels = {
        active: 'Disponible',
        low: 'Pocas copias',
        out: 'Sin stock',
    };

    const valueForSort = (row, sortBy) => {
        if (sortBy === 'available') {
            return Number(row.dataset.available || 0);
        }
        if (sortBy === 'status') {
            return statusRank[row.dataset.status || ''] || 99;
        }
        return (row.dataset[sortBy] || '').toString();
    };

    const updateSortLabels = () => {
        sortButtons.forEach((btn) => {
            const icon = btn.querySelector('span');
            if (!icon) return;
            if (btn.dataset.sort !== state.sortBy) {
                icon.textContent = '⇅';
                return;
            }
            icon.textContent = state.sortDir === 'asc' ? '↑' : '↓';
        });
    };

    const getFilteredRows = () => {
        const search = state.search.trim().toLowerCase();

        return rows.filter((row) => {
            const text = [
                row.dataset.title,
                row.dataset.author,
                row.dataset.code,
                row.dataset.category,
                row.dataset.type,
            ].join(' ');

            const matchesSearch = !search || text.includes(search);
            const matchesCategory = state.category === 'all' || row.dataset.category === state.category;
            const matchesStatus = state.status === 'all' || row.dataset.status === state.status;

            return matchesSearch && matchesCategory && matchesStatus;
        });
    };

    const render = () => {
        const filtered = getFilteredRows().sort((a, b) => {
            const va = valueForSort(a, state.sortBy);
            const vb = valueForSort(b, state.sortBy);

            if (typeof va === 'number' && typeof vb === 'number') {
                return state.sortDir === 'asc' ? va - vb : vb - va;
            }

            return state.sortDir === 'asc'
                ? String(va).localeCompare(String(vb), 'es')
                : String(vb).localeCompare(String(va), 'es');
        });

        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / state.pageSize));
        state.page = Math.min(state.page, totalPages);

        const start = (state.page - 1) * state.pageSize;
        const end = Math.min(start + state.pageSize, total);

        rows.forEach((row) => {
            row.style.display = 'none';
        });

        filtered.slice(start, end).forEach((row) => {
            row.style.display = '';
            tableBody.appendChild(row);
        });

        const showingFrom = total === 0 ? 0 : start + 1;
        const showingTo = total === 0 ? 0 : end;
        info.textContent = `Mostrando ${showingFrom}-${showingTo} de ${total} recursos`;

        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= totalPages;
        prevBtn.classList.toggle('opacity-60', prevBtn.disabled);
        prevBtn.classList.toggle('cursor-not-allowed', prevBtn.disabled);
        nextBtn.classList.toggle('opacity-60', nextBtn.disabled);
        nextBtn.classList.toggle('cursor-not-allowed', nextBtn.disabled);
        pageIndicator.textContent = `${state.page}/${totalPages}`;

        updateSortLabels();
    };

    const updateRow = (payload) => {
        if (!payload || !payload.id) return;
        const row = tableBody.querySelector(`tr[data-resource-id="${payload.id}"]`);
        if (!row) return;

        row.dataset.title = String(payload.title || '').toLowerCase();
        row.dataset.author = String(payload.author || '').toLowerCase();
        row.dataset.code = String(payload.code || '').toLowerCase();
        row.dataset.category = String(payload.category || '').toLowerCase();
        row.dataset.status = String(payload.status || 'active');
        row.dataset.available = Number(payload.available || 0);
        row.dataset.copies = Number(payload.copies || 0);

        const titleCell = row.querySelector('[data-cell="title"]');
        const authorCell = row.querySelector('[data-cell="author"]');
        const codeCell = row.querySelector('[data-cell="code"]');
        const categoryCell = row.querySelector('[data-cell="category"]');
        const inventoryCell = row.querySelector('[data-cell="inventory"]');
        const statusCell = row.querySelector('[data-cell="status"]');

        if (titleCell) titleCell.textContent = payload.title || '';
        if (authorCell) authorCell.textContent = payload.author || '';
        if (codeCell) codeCell.textContent = payload.code || '';
        if (categoryCell) categoryCell.textContent = payload.category || '';
        if (inventoryCell) inventoryCell.textContent = `${Number(payload.available || 0)} / ${Number(payload.copies || 0)} disponibles`;
        if (statusCell) {
            const status = String(payload.status || 'active');
            statusCell.className = `inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${statusClasses[status] || 'bg-slate-100 text-slate-700'}`;
            statusCell.textContent = statusLabels[status] || 'Estado';
        }

        render();
    };

    searchInput?.addEventListener('input', (event) => {
        state.search = event.target.value || '';
        state.page = 1;
        render();
    });

    categorySelect?.addEventListener('change', (event) => {
        state.category = event.target.value || 'all';
        state.page = 1;
        render();
    });

    statusSelect?.addEventListener('change', (event) => {
        state.status = event.target.value || 'all';
        state.page = 1;
        render();
    });

    pageSizeSelect?.addEventListener('change', (event) => {
        state.pageSize = Number(event.target.value || 5);
        state.page = 1;
        render();
    });

    prevBtn?.addEventListener('click', () => {
        if (state.page <= 1) return;
        state.page -= 1;
        render();
    });

    nextBtn?.addEventListener('click', () => {
        state.page += 1;
        render();
    });

    sortButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetSort = btn.dataset.sort || 'title';
            if (state.sortBy === targetSort) {
                state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                state.sortBy = targetSort;
                state.sortDir = 'asc';
            }
            state.page = 1;
            render();
        });
    });

    const openEditModal = (url, title = '') => {
        if (!editModal || !editFrame || !url) return;
        const modalUrl = url.includes('?') ? `${url}&modal=1` : `${url}?modal=1`;
        editFrame.src = modalUrl;
        if (editModalTitle) {
            editModalTitle.textContent = title ? `Editor de recurso · ${title}` : 'Editor de recurso';
        }
        editModal.classList.remove('hidden');
        editModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    const closeEditModal = () => {
        if (!editModal || !editFrame) return;
        editModal.classList.add('hidden');
        editModal.setAttribute('aria-hidden', 'true');
        editFrame.src = 'about:blank';
        document.body.classList.remove('overflow-hidden');
    };

    openEditButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            openEditModal(btn.dataset.editUrl || '', btn.dataset.editTitle || '');
        });
    });

    closeEditButtons.forEach((btn) => {
        btn.addEventListener('click', closeEditModal);
    });
    editModalSaveButton?.addEventListener('click', () => {
        if (!editFrame || !editFrame.contentWindow) return;
        editFrame.contentWindow.postMessage({ type: 'submit-resource-edit-form' }, '*');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && editModal && !editModal.classList.contains('hidden')) {
            closeEditModal();
        }
    });

    window.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'close-resource-edit-modal') {
            closeEditModal();
        }
        if (event.data && event.data.type === 'book-edit-saved') {
            closeEditModal();
            if (event.data.payload) {
                updateRow(event.data.payload);
            }
            if (typeof window.showLibraryToast === 'function') {
                window.showLibraryToast('success', (event.data.payload && event.data.payload.message) || 'Recurso actualizado correctamente.');
            }
        }
    });

    render();
})();
</script>
