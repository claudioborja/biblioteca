<?php
// views/account/reservations.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$statusClass = static function (string $status): string {
    return match ($status) {
        'waiting' => 'bg-blue-100 text-blue-700',
        'notified' => 'bg-emerald-100 text-emerald-700',
        'fulfilled' => 'bg-violet-100 text-violet-700',
        'expired' => 'bg-amber-100 text-amber-700',
        'cancelled' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-700',
    };
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'waiting' => 'En espera',
        'notified' => 'Notificada',
        'fulfilled' => 'Completada',
        'expired' => 'Expirada',
        'cancelled' => 'Cancelada',
        default => ucfirst($status),
    };
};

$flashSuccess = \Core\Session::getFlash('success');
$flashError   = \Core\Session::getFlash('error');
$flashInfo    = \Core\Session::getFlash('info');
$csrfToken = (string) \Core\Session::get('_csrf_token', '');
$resourceSearch = trim((string) ($resource_search ?? ''));
?>

<section class="p-6 lg:p-8">
    <div class="mb-7">
        <p class="label-sm">Mi zona</p>
        <h1 class="headline-lg text-on-surface">Mis reservaciones</h1>
        <p class="body-md mt-1">Revisa el estado y posicion de tus reservaciones activas.</p>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= $e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $e($flashError) ?></div>
    <?php endif; ?>
    <?php if ($flashInfo): ?>
        <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800"><?= $e($flashInfo) ?></div>
    <?php endif; ?>

    <div class="mb-7 rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <h2 class="title-sm text-on-surface">Crear reservacion desde panel</h2>
        <p class="body-md mt-1">Usa el buscador inteligente como en la página principal: escribe y reserva desde las sugerencias.</p>

        <form method="POST" action="<?= BASE_URL ?>/account/reservations" class="mt-4 max-w-3xl" id="account-reservation-form">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
            <input type="hidden" name="redirect" value="/account/reservations">
            <input type="hidden" name="resource_id" id="reservation-resource-id" value="">
            <input type="hidden" name="resource_query" id="reservation-resource-query" value="">

            <label for="account-resource-search" class="sr-only">Buscar recurso para reservar</label>
            <div class="relative">
                <div class="flex items-center bg-surface-container-lowest rounded-[0.75rem] shadow-ambient relative z-10 border border-outline-variant/60">
                    <svg class="w-4 h-4 ml-4 text-on-surface-subtle shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input type="text"
                           id="account-resource-search"
                              value="<?= $e($resourceSearch) ?>"
                           placeholder="Buscar por titulo, autor, ISBN, editorial..."
                           class="flex-1 px-3 py-3.5 text-on-surface text-sm bg-transparent focus:outline-none placeholder-on-surface-subtle"
                           autocomplete="off"
                           aria-autocomplete="list"
                           aria-controls="account-search-dropdown"
                           aria-expanded="false">
                    <svg id="account-search-spinner" class="hidden w-4 h-4 mr-3 text-on-surface-subtle animate-spin shrink-0" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                    <button type="submit"
                            class="mr-1.5 px-5 py-2 bg-primary hover:bg-primary-muted text-on-primary font-semibold rounded-[0.375rem] text-sm transition-colors duration-200 shrink-0 inline-flex items-center gap-2">
                        Reservar
                    </button>
                </div>

                <div id="account-search-dropdown"
                     role="listbox"
                     aria-label="Sugerencias para reservacion"
                     class="hidden absolute left-0 right-0 top-full mt-1.5 bg-surface-container-lowest rounded-[0.875rem] shadow-[0_8px_32px_rgba(0,0,0,0.18)] overflow-hidden z-50 border border-surface-container text-left">
                    <div id="account-search-header" class="px-4 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-widest text-on-surface-subtle/60"></div>
                    <ul id="account-search-results" class="py-1 max-h-[380px] overflow-y-auto divide-y divide-surface-container/50"></ul>
                </div>
            </div>
        </form>

        <p class="mt-3 text-xs text-on-surface-muted">Tip: selecciona una sugerencia para autocompletar y reservar, o presiona Enter para buscar coincidencia unica.</p>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">En espera</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 font-display"><?= (int) ($summary['waiting'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Notificadas</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= (int) ($summary['notified'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Completadas</p>
            <p class="mt-2 text-2xl font-bold text-violet-700 font-display"><?= (int) ($summary['fulfilled'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Expiradas</p>
            <p class="mt-2 text-2xl font-bold text-amber-700 font-display"><?= (int) ($summary['expired'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Canceladas</p>
            <p class="mt-2 text-2xl font-bold text-red-700 font-display"><?= (int) ($summary['cancelled'] ?? 0) ?></p>
        </article>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Recurso</th>
                        <th class="px-4 py-3 font-semibold">Solicitud</th>
                        <th class="px-4 py-3 font-semibold">Posicion</th>
                        <th class="px-4 py-3 font-semibold">Notificada</th>
                        <th class="px-4 py-3 font-semibold">Expira</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50 text-sm">
                    <?php if (empty($reservations)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-on-surface-subtle">No tienes reservaciones registradas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors">
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-on-surface"><?= $e($reservation['book_title'] ?? 'Libro') ?></p>
                                    <p class="text-xs text-on-surface-subtle"><?= $e($reservation['book_authors'] ?? '') ?></p>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((new DateTime($reservation['created_at']))->format('d/m/Y H:i')) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted">#<?= (int) ($reservation['queue_position'] ?? 0) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted">
                                    <?= !empty($reservation['notified_at']) ? $e((new DateTime($reservation['notified_at']))->format('d/m/Y H:i')) : '-' ?>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted">
                                    <?= !empty($reservation['expires_at']) ? $e((new DateTime($reservation['expires_at']))->format('d/m/Y H:i')) : '-' ?>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass((string) $reservation['status']) ?>">
                                        <?= $e($statusLabel((string) $reservation['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <?php if (in_array((string) ($reservation['status'] ?? ''), ['waiting', 'notified'], true)): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/account/reservations/<?= (int) ($reservation['id'] ?? 0) ?>/cancel" class="inline">
                                            <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                                                Cancelar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs text-on-surface-subtle">Sin acciones</span>
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

<script>
(function () {
    const BASE = '<?= BASE_URL ?>';
    const form = document.getElementById('account-reservation-form');
    const input = document.getElementById('account-resource-search');
    const hiddenId = document.getElementById('reservation-resource-id');
    const hiddenQuery = document.getElementById('reservation-resource-query');
    const drop = document.getElementById('account-search-dropdown');
    const list = document.getElementById('account-search-results');
    const header = document.getElementById('account-search-header');
    const spinner = document.getElementById('account-search-spinner');

    if (!form || !input || !hiddenId || !hiddenQuery || !drop || !list || !header || !spinner) {
        return;
    }

    let controller = null;
    let debounceTimer = null;
    let activeIndex = -1;
    let currentItems = [];

    function openDrop() {
        drop.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
    }

    function closeDrop() {
        drop.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
    }

    function escHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;');
    }

    function highlight(newIdx) {
        const items = list.querySelectorAll('[role=option]');
        items.forEach((el, i) => {
            el.classList.toggle('bg-surface-container', i === newIdx);
            el.setAttribute('aria-selected', i === newIdx ? 'true' : 'false');
        });
        activeIndex = newIdx;
    }

    function chooseItem(book) {
        hiddenId.value = String(book.id || '');
        hiddenQuery.value = input.value.trim();
        input.value = book.title || input.value;
        closeDrop();
    }

    function buildItem(book) {
        const li = document.createElement('li');
        li.setAttribute('role', 'option');
        li.setAttribute('aria-selected', 'false');
        li.className = 'flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-surface-container transition-colors duration-100 group';

        const isDigital = String(book.type || '') === 'digital';
        const isAvailable = Number(book.available || 0) > 0;
        const badgeColor = isDigital
            ? 'bg-sky-100 text-sky-700'
            : (isAvailable ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700');
        const badgeText = isDigital ? 'Digital' : (isAvailable ? 'Disponible' : 'Sin ejemplares');

        const coverHtml = book.cover
            ? `<img src="${escHtml(book.cover)}" alt="" class="w-10 h-14 object-cover rounded-[0.375rem] shrink-0 shadow-sm" loading="lazy">`
            : `<div class="w-10 h-14 rounded-[0.375rem] bg-surface-container-high shrink-0 flex items-center justify-center">
                 <svg class="w-4 h-4 text-on-surface-subtle/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                 </svg>
               </div>`;

        li.innerHTML = `
            ${coverHtml}
            <div class="flex-1 min-w-0">
                <p class="text-[13px] font-semibold text-on-surface group-hover:text-primary transition-colors line-clamp-1 leading-snug">${escHtml(book.title || '')}</p>
                ${book.authors ? `<p class="text-[11px] text-on-surface-subtle mt-0.5 line-clamp-1">${escHtml(book.authors)}</p>` : ''}
                <p class="text-[10px] text-on-surface-subtle mt-1">ISBN: ${escHtml(book.isbn || 'N/A')}</p>
            </div>
            <span class="inline-block text-[9px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded-[3px] ${badgeColor}">${badgeText}</span>
            <svg class="w-3.5 h-3.5 text-on-surface-subtle/40 shrink-0 group-hover:text-primary transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        `;

        li.addEventListener('click', () => chooseItem(book));
        li.addEventListener('mouseenter', () => {
            const idx = currentItems.findIndex((b) => Number(b.id) === Number(book.id));
            highlight(idx);
        });

        return li;
    }

    function renderResults(books) {
        list.innerHTML = '';
        currentItems = books;
        activeIndex = -1;

        if (books.length === 0) {
            header.textContent = 'Sin resultados';
            list.innerHTML = '<li class="px-4 py-4 text-center text-sm text-on-surface-subtle">No se encontraron recursos para reservar.</li>';
            openDrop();
            return;
        }

        header.textContent = `${books.length} sugerencia${books.length !== 1 ? 's' : ''}`;
        books.forEach((book) => list.appendChild(buildItem(book)));
        openDrop();
    }

    function fetchSuggestions(q) {
        if (controller) {
            controller.abort();
        }
        controller = new AbortController();
        spinner.classList.remove('hidden');

        fetch(`${BASE}/api/autocomplete?q=${encodeURIComponent(q)}`, { signal: controller.signal })
            .then((r) => r.json())
            .then((data) => {
                spinner.classList.add('hidden');
                const books = Array.isArray(data) ? data : [];
                renderResults(books);
            })
            .catch((err) => {
                if (err && err.name !== 'AbortError') {
                    spinner.classList.add('hidden');
                    closeDrop();
                }
            });
    }

    input.addEventListener('focus', () => {
        const q = input.value.trim();
        if (q.length >= 1) {
            fetchSuggestions(q);
        }
    });

    input.addEventListener('input', () => {
        hiddenId.value = '';
        hiddenQuery.value = input.value.trim();

        clearTimeout(debounceTimer);
        const q = input.value.trim();
        if (q.length < 2) {
            closeDrop();
            return;
        }
        debounceTimer = setTimeout(() => fetchSuggestions(q), 250);
    });

    input.addEventListener('keydown', (e) => {
        if (drop.classList.contains('hidden')) {
            return;
        }

        const items = list.querySelectorAll('[role=option]');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlight(Math.min(activeIndex + 1, items.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlight(Math.max(activeIndex - 1, -1));
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            if (currentItems[activeIndex]) {
                chooseItem(currentItems[activeIndex]);
            }
        } else if (e.key === 'Escape') {
            closeDrop();
        }
    });

    form.addEventListener('submit', () => {
        hiddenQuery.value = input.value.trim();
    });

    document.addEventListener('click', (e) => {
        if (!form.contains(e.target)) {
            closeDrop();
        }
    });

    if (input.value.trim().length >= 2) {
        fetchSuggestions(input.value.trim());
    }
})();
</script>
