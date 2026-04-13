<?php
// views/admin/news/index.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Contenido</p>
            <h1 class="headline-lg text-on-surface">Noticias</h1>
            <p class="body-md mt-1">Gestiona publicaciones con edición en ventana modal y actualización inmediata.</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/news/create" class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient inline-flex items-center gap-2">
            <i class="bi bi-plus-lg text-sm"></i> Nueva noticia
        </a>
    </div>

    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <div class="grid gap-3 md:grid-cols-5">
            <div class="md:col-span-2">
                <label for="news-search" class="label-sm">Buscar</label>
                <input id="news-search" type="text" placeholder="Título, slug o autor"
                    class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
            </div>
            <div>
                <label for="news-status" class="label-sm">Estado</label>
                <select id="news-status" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todos</option>
                    <option value="published">Publicada</option>
                    <option value="scheduled">Programada</option>
                    <option value="draft">Borrador</option>
                </select>
            </div>
            <div>
                <label for="news-page-size" class="label-sm">Filas</label>
                <select id="news-page-size" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </select>
            </div>
            <div class="hidden md:block"></div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table id="news-table" class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="title" class="news-sort inline-flex items-center gap-1 hover:text-primary">
                                Título <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="author" class="news-sort inline-flex items-center gap-1 hover:text-primary">
                                Autor <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="status_rank" class="news-sort inline-flex items-center gap-1 hover:text-primary">
                                Estado <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="published_at_sort" class="news-sort inline-flex items-center gap-1 hover:text-primary">
                                Publicación <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="news-table-body" class="divide-y divide-outline-variant/50 text-sm">
                    <?php foreach ($news as $item): ?>
                        <?php
                        $isPublished = (int) ($item['is_published'] ?? 0) === 1;
                        $isScheduled = $isPublished && !empty($item['published_at']) && strtotime((string) $item['published_at']) > time();
                        $status = $isScheduled ? 'scheduled' : ($isPublished ? 'published' : 'draft');
                        $statusLabel = $isScheduled ? 'Programada' : ($isPublished ? 'Publicada' : 'Borrador');
                        $statusClass = $isScheduled ? 'bg-violet-100 text-violet-700' : ($isPublished ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700');
                        $publishedAtLabel = !empty($item['published_at']) ? (new DateTime((string) $item['published_at']))->format('d/m/Y H:i') : '-';
                        $publishedAtSort = !empty($item['published_at']) ? strtotime((string) $item['published_at']) : 0;
                        $statusRank = $status === 'published' ? 1 : ($status === 'scheduled' ? 2 : 3);
                        ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors"
                            data-news-id="<?= (int) $item['id'] ?>"
                            data-title="<?= $e(mb_strtolower((string) $item['title'])) ?>"
                            data-slug="<?= $e(mb_strtolower((string) $item['slug'])) ?>"
                            data-author="<?= $e(mb_strtolower((string) ($item['author_name'] ?? 'sin autor'))) ?>"
                            data-status="<?= $e($status) ?>"
                            data-status_rank="<?= (int) $statusRank ?>"
                            data-published_at_sort="<?= (int) $publishedAtSort ?>">
                            <td class="px-4 py-3.5">
                                <p class="font-semibold text-on-surface" data-cell="title"><?= $e($item['title']) ?></p>
                                <p class="text-xs text-on-surface-subtle" data-cell="slug"><?= $e($item['slug']) ?></p>
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-muted" data-cell="author"><?= $e($item['author_name'] ?? 'Sin autor') ?></td>
                            <td class="px-4 py-3.5">
                                <span data-cell="status" class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass ?>">
                                    <?= $e($statusLabel) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-muted" data-cell="published_at"><?= $e($publishedAtLabel) ?></td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="<?= BASE_URL ?>/news/<?= $e($item['slug']) ?>" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">Ver</a>
                                <button type="button"
                                        data-edit-url="<?= BASE_URL ?>/admin/news/<?= (int) $item['id'] ?>/edit"
                                        data-edit-title="<?= $e($item['title']) ?>"
                                        class="ml-1 rounded-lg border border-primary/30 bg-primary/5 px-2.5 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors js-open-news-edit-modal inline-flex items-center gap-1">
                                    <i class="bi bi-pencil-square text-[12px]"></i> Editar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-outline-variant/60 px-4 py-3 text-sm text-on-surface-muted sm:flex-row sm:items-center sm:justify-between">
            <p id="news-table-info">Mostrando 0-0 de 0 noticias</p>
            <div class="flex items-center gap-1">
                <button id="news-prev" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    <i class="bi bi-arrow-left text-[12px]"></i> Anterior
                </button>
                <span id="news-page-indicator" class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white">1</span>
                <button id="news-next" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    Siguiente <i class="bi bi-arrow-right text-[12px]"></i>
                </button>
            </div>
        </div>
    </div>

    <div id="news-edit-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-primary/35 backdrop-blur-[1px]" data-close-news-edit-modal></div>
        <div class="absolute inset-0 p-4 sm:p-6 flex items-center justify-center">
            <div class="relative flex h-[88vh] w-[95vw] max-w-[1300px] flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">
                <div class="flex h-12 items-center justify-between border-b border-outline-variant/60 bg-gradient-to-b from-slate-100 to-slate-200 pl-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-primary/15 text-primary">
                            <i class="bi bi-newspaper text-[12px]"></i>
                        </span>
                        <p id="news-edit-modal-title" class="truncate text-sm font-semibold text-slate-700">Editor de noticia</p>
                    </div>
                    <button type="button" class="inline-flex h-12 w-12 items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors" data-close-news-edit-modal aria-label="Cerrar ventana">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1">
                    <iframe id="news-edit-frame" title="Editar noticia" class="h-full w-full bg-white" src="about:blank"></iframe>
                </div>
                <div class="flex min-h-14 items-center justify-between border-t border-outline-variant/70 bg-slate-100/95 px-4 py-2.5">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1">
                            <i class="bi bi-check2 text-[12px]"></i> Guardado editorial inmediato
                        </span>
                    </div>
                    <button type="button" id="news-edit-modal-save" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                        <i class="bi bi-floppy text-sm"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(() => {
    const tableBody = document.getElementById('news-table-body');
    if (!tableBody) return;

    const rows = Array.from(tableBody.querySelectorAll('tr'));
    const searchInput = document.getElementById('news-search');
    const statusSelect = document.getElementById('news-status');
    const pageSizeSelect = document.getElementById('news-page-size');
    const prevBtn = document.getElementById('news-prev');
    const nextBtn = document.getElementById('news-next');
    const pageIndicator = document.getElementById('news-page-indicator');
    const info = document.getElementById('news-table-info');
    const sortButtons = Array.from(document.querySelectorAll('.news-sort'));

    const editModal = document.getElementById('news-edit-modal');
    const editFrame = document.getElementById('news-edit-frame');
    const editModalTitle = document.getElementById('news-edit-modal-title');
    const editSaveButton = document.getElementById('news-edit-modal-save');
    const openEditButtons = Array.from(document.querySelectorAll('.js-open-news-edit-modal'));
    const closeEditButtons = Array.from(document.querySelectorAll('[data-close-news-edit-modal]'));

    const state = {
        search: '',
        status: 'all',
        page: 1,
        pageSize: Number(pageSizeSelect?.value || 5),
        sortBy: 'title',
        sortDir: 'asc',
    };

    const statusClasses = {
        published: 'bg-emerald-100 text-emerald-700',
        scheduled: 'bg-violet-100 text-violet-700',
        draft: 'bg-slate-100 text-slate-700',
    };
    const statusLabels = {
        published: 'Publicada',
        scheduled: 'Programada',
        draft: 'Borrador',
    };

    const valueForSort = (row, sortBy) => {
        if (sortBy === 'status_rank' || sortBy === 'published_at_sort') {
            return Number(row.dataset[sortBy] || 0);
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
            const text = [row.dataset.title, row.dataset.slug, row.dataset.author].join(' ');
            const matchesSearch = !search || text.includes(search);
            const matchesStatus = state.status === 'all' || row.dataset.status === state.status;
            return matchesSearch && matchesStatus;
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

        rows.forEach((row) => { row.style.display = 'none'; });
        filtered.slice(start, end).forEach((row) => {
            row.style.display = '';
            tableBody.appendChild(row);
        });

        const showingFrom = total === 0 ? 0 : start + 1;
        const showingTo = total === 0 ? 0 : end;
        info.textContent = `Mostrando ${showingFrom}-${showingTo} de ${total} noticias`;

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
        const row = tableBody.querySelector(`tr[data-news-id="${payload.id}"]`);
        if (!row) return;

        row.dataset.title = String(payload.title || '').toLowerCase();
        row.dataset.slug = String(payload.slug || '').toLowerCase();
        row.dataset.author = String(payload.author_name || '').toLowerCase();
        row.dataset.status = String(payload.status || 'draft');
        row.dataset.status_rank = String(payload.status === 'published' ? 1 : (payload.status === 'scheduled' ? 2 : 3));
        row.dataset.published_at_sort = String(Number(payload.published_at_sort || 0));

        const titleCell = row.querySelector('[data-cell="title"]');
        const slugCell = row.querySelector('[data-cell="slug"]');
        const authorCell = row.querySelector('[data-cell="author"]');
        const statusCell = row.querySelector('[data-cell="status"]');
        const publishedCell = row.querySelector('[data-cell="published_at"]');

        if (titleCell) titleCell.textContent = payload.title || '';
        if (slugCell) slugCell.textContent = payload.slug || '';
        if (authorCell) authorCell.textContent = payload.author_name || 'Sin autor';
        if (publishedCell) publishedCell.textContent = payload.published_at_label || '-';

        const editButton = row.querySelector('.js-open-news-edit-modal');
        if (editButton) {
            editButton.dataset.editTitle = payload.title || '';
        }
        if (statusCell) {
            const status = payload.status || 'draft';
            statusCell.className = `inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${statusClasses[status] || statusClasses.draft}`;
            statusCell.textContent = statusLabels[status] || 'Borrador';
        }

        render();
    };

    const openEditModal = (url, title = '') => {
        if (!editModal || !editFrame || !url) return;
        const modalUrl = url.includes('?') ? `${url}&modal=1` : `${url}?modal=1`;
        editFrame.src = modalUrl;
        if (editModalTitle) {
            editModalTitle.textContent = title ? `Editor de noticia · ${title}` : 'Editor de noticia';
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

    searchInput?.addEventListener('input', (event) => {
        state.search = event.target.value || '';
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

    openEditButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            openEditModal(btn.dataset.editUrl || '', btn.dataset.editTitle || '');
        });
    });

    closeEditButtons.forEach((btn) => {
        btn.addEventListener('click', closeEditModal);
    });

    editSaveButton?.addEventListener('click', () => {
        if (!editFrame || !editFrame.contentWindow) return;
        editFrame.contentWindow.postMessage({ type: 'submit-news-edit-form' }, '*');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && editModal && !editModal.classList.contains('hidden')) {
            closeEditModal();
        }
    });

    window.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'close-news-edit-modal') {
            closeEditModal();
        }
        if (event.data && event.data.type === 'news-edit-saved') {
            closeEditModal();
            if (event.data.payload) {
                updateRow(event.data.payload);
            }
            if (typeof window.showLibraryToast === 'function') {
                window.showLibraryToast('success', (event.data.payload && event.data.payload.message) || 'Noticia actualizada correctamente.');
            }
        }
    });

    render();
})();
</script>
