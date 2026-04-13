<?php
use Helpers\Icons;
/**
 * Vista: Página de Inicio (Home)
 * RF-PUB-01 — Página pública sin requerir sesión
 *
 * Variables disponibles:
 *   $settings         — Configuración de la biblioteca (array)
 *   $newAcquisitions  — Últimas incorporaciones al catálogo (array)
 *   $topBooks         — Recursos más prestados del mes (array)
 *   $news             — Últimas noticias publicadas (array)
 *   $stats            — Estadísticas públicas: total_books, total_users, total_loans (array)
 *   $branches         — Sedes activas (array)
 */

use Core\View;
?>

<!-- ===== Hero / Banner — Scholarly Editorial ===== -->
<section class="relative gradient-scholar text-on-primary">
    <!-- Decorative background pattern (overflow contained here, not on section) -->
    <div class="absolute inset-0 opacity-[0.07] overflow-hidden pointer-events-none">
        <svg class="absolute top-0 right-0 w-[500px] h-[500px] -mr-32 -mt-32 text-white" fill="currentColor" viewBox="0 0 200 200" aria-hidden="true">
            <circle cx="100" cy="100" r="80" fill="none" stroke="currentColor" stroke-width="0.4"/>
            <circle cx="100" cy="100" r="60" fill="none" stroke="currentColor" stroke-width="0.4"/>
            <circle cx="100" cy="100" r="40" fill="none" stroke="currentColor" stroke-width="0.4"/>
            <circle cx="100" cy="100" r="20" fill="none" stroke="currentColor" stroke-width="0.4"/>
        </svg>
        <svg class="absolute bottom-0 left-0 w-80 h-80 -ml-20 -mb-20 text-white" fill="currentColor" viewBox="0 0 200 200" aria-hidden="true">
            <path d="M30 100 Q60 20 100 80 Q140 140 170 60" fill="none" stroke="currentColor" stroke-width="0.6"/>
            <path d="M20 140 Q80 60 140 120 Q180 160 190 80" fill="none" stroke="currentColor" stroke-width="0.6"/>
        </svg>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-24 md:pt-18 md:pb-28">
        <div class="max-w-2xl mx-auto text-center">
            <h1 class="display-md mb-4">Catálogo en línea</h1>
            <p class="text-base text-white/60 mb-8 leading-relaxed font-light max-w-sm mx-auto sm:max-w-lg lg:max-w-none lg:whitespace-nowrap">
                Miles de títulos disponibles. Busca, reserva y lleva contigo el conocimiento.
            </p>

            <!-- Search bar — Knowledge Gateway -->
            <form action="<?= BASE_URL ?>/search" method="GET" class="max-w-xl mx-auto" role="search" id="hero-search-form">
                <label for="hero-search" class="sr-only">Buscar en el catálogo</label>
                <div class="relative">
                    <div class="flex items-center bg-surface-container-lowest rounded-[0.75rem] shadow-ambient-lg relative z-10">
                        <svg class="w-4 h-4 ml-4 text-on-surface-subtle shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                        <input type="text"
                               id="hero-search"
                               name="q"
                               placeholder="Buscar por título, autor, ISBN..."
                               class="flex-1 px-3 py-3.5 text-on-surface text-sm bg-transparent focus:outline-none placeholder-on-surface-subtle"
                               autocomplete="off"
                               aria-autocomplete="list"
                               aria-controls="search-dropdown"
                               aria-expanded="false">
                        <!-- Spinner -->
                        <svg id="search-spinner" class="hidden w-4 h-4 mr-3 text-on-surface-subtle animate-spin shrink-0" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                        <button type="submit"
                                class="mr-1.5 px-5 py-2 bg-primary hover:bg-primary-muted text-on-primary font-semibold rounded-[0.375rem] text-sm transition-colors duration-200 shrink-0 inline-flex items-center gap-2">
                            <?= Icons::search('w-4 h-4') ?> Buscar
                        </button>
                    </div>

                    <!-- Dropdown -->
                    <div id="search-dropdown"
                         role="listbox"
                         aria-label="Sugerencias de búsqueda"
                         class="hidden absolute left-0 right-0 top-full mt-1.5 bg-surface-container-lowest rounded-[0.875rem] shadow-[0_8px_32px_rgba(0,0,0,0.18)] overflow-hidden z-50 border border-surface-container text-left">

                        <!-- Header label (injected by JS) -->
                        <div id="search-dropdown-header" class="px-4 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-widest text-on-surface-subtle/60"></div>

                        <!-- Results list (5 items × ~76 px = 380 px) -->
                        <ul id="search-results-list" class="py-1 max-h-[380px] overflow-y-auto divide-y divide-surface-container/50"></ul>

                        <!-- Footer — always visible -->
                        <div id="search-dropdown-footer" class="border-t border-surface-container px-4 py-2.5">
                            <a id="search-footer-link"
                               href="<?= BASE_URL ?>/catalog"
                               class="w-full text-center text-xs font-semibold text-primary hover:text-primary-muted transition-colors flex items-center justify-center gap-1.5 py-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                                <span id="search-footer-label">Explorar catálogo completo</span>
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <script>
            (function () {
                const BASE   = '<?= BASE_URL ?>';
                const input  = document.getElementById('hero-search');
                const form   = document.getElementById('hero-search-form');
                const drop   = document.getElementById('search-dropdown');
                const list   = document.getElementById('search-results-list');
                const header = document.getElementById('search-dropdown-header');
                const footer      = document.getElementById('search-dropdown-footer');
                const footerLink  = document.getElementById('search-footer-link');
                const footerLabel = document.getElementById('search-footer-label');
                const spinner= document.getElementById('search-spinner');

                let controller = null;   // AbortController for in-flight request
                let debounceTimer = null;
                let activeIndex = -1;
                let currentItems = [];

                // ── Helpers ─────────────────────────────────────────────────

                function openDrop()  { drop.classList.remove('hidden'); input.setAttribute('aria-expanded', 'true'); }
                function closeDrop() {
                    drop.classList.add('hidden');
                    input.setAttribute('aria-expanded', 'false');
                    activeIndex = -1;
                }

                function highlight(newIdx) {
                    const items = list.querySelectorAll('[role=option]');
                    items.forEach((el, i) => {
                        el.classList.toggle('bg-surface-container', i === newIdx);
                        el.setAttribute('aria-selected', i === newIdx ? 'true' : 'false');
                    });
                    activeIndex = newIdx;
                }

                function buildItem(book, query) {
                    const li = document.createElement('li');
                    li.setAttribute('role', 'option');
                    li.setAttribute('aria-selected', 'false');
                    li.className = 'flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-surface-container transition-colors duration-100 group';

                    const isDigital  = book.type === 'digital';
                    const isAvailable= isDigital || book.available > 0;
                    const badgeColor = isAvailable ? 'bg-green-100 text-green-700' : 'bg-red-50 text-red-500';
                    const badgeText  = isDigital ? 'Digital' : (isAvailable ? 'Disponible' : 'No disponible');

                    const titleHtml = escHtml(book.title);

                    const coverHtml = book.cover
                        ? `<img src="${escHtml(book.cover)}" alt="" class="w-10 h-14 object-cover rounded-[0.375rem] shrink-0 shadow-sm" loading="lazy">`
                        : `<div class="w-10 h-14 rounded-[0.375rem] bg-surface-container-high shrink-0 flex items-center justify-center">
                             <svg class="w-4 h-4 text-on-surface-subtle/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                           </div>`;

                    li.innerHTML = `
                        ${coverHtml}
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold text-on-surface group-hover:text-primary transition-colors line-clamp-1 leading-snug">${titleHtml}</p>
                            ${book.authors ? `<p class="text-[11px] text-on-surface-subtle mt-0.5 line-clamp-1">${escHtml(book.authors)}</p>` : ''}
                            <span class="inline-block mt-1 text-[9px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded-[3px] ${badgeColor}">${badgeText}</span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-on-surface-subtle/40 shrink-0 group-hover:text-primary transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    `;

                    li.addEventListener('click', () => { window.location.href = book.url; });
                    li.addEventListener('mouseenter', () => {
                        const idx = currentItems.indexOf(book);
                        highlight(idx);
                    });
                    return li;
                }

                function renderResults(books, query, isDefault) {
                    list.innerHTML = '';
                    currentItems = books;
                    activeIndex = -1;

                    if (books.length === 0) {
                        header.textContent = 'Sin resultados';
                        list.innerHTML = `<li class="px-4 py-5 text-center text-sm text-on-surface-subtle">No se encontraron recursos para "${escHtml(query)}"</li>`;
                    } else {
                        header.textContent = isDefault ? 'Novedades recientes' : `${books.length} resultado${books.length !== 1 ? 's' : ''}`;
                        books.forEach(b => list.appendChild(buildItem(b, isDefault ? '' : query)));
                    }

                    // Footer: always shown, update link and label based on query
                    if (!isDefault && query) {
                        footerLink.href = `${BASE}/catalog?q=${encodeURIComponent(query)}`;
                        footerLabel.textContent = `Ver todos los resultados para "${query}"`;
                    } else {
                        footerLink.href = `${BASE}/catalog`;
                        footerLabel.textContent = 'Explorar catálogo completo';
                    }

                    openDrop();
                }

                // ── Fetch ────────────────────────────────────────────────────

                function fetchSuggestions(q) {
                    if (controller) controller.abort();
                    controller = new AbortController();
                    spinner.classList.remove('hidden');

                    const url = `${BASE}/api/autocomplete?q=${encodeURIComponent(q)}`;

                    fetch(url, { signal: controller.signal })
                        .then(r => r.json())
                        .then(data => {
                            spinner.classList.add('hidden');
                            renderResults(data, q, q === '');
                        })
                        .catch(err => {
                            if (err.name !== 'AbortError') {
                                spinner.classList.add('hidden');
                                closeDrop();
                            }
                        });
                }

                // ── Events ───────────────────────────────────────────────────

                input.addEventListener('focus', () => {
                    // Show immediately with current value (or empty = featured)
                    fetchSuggestions(input.value.trim());
                });

                input.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    const q = input.value.trim();
                    debounceTimer = setTimeout(() => fetchSuggestions(q), 280);
                });

                input.addEventListener('keydown', (e) => {
                    if (!drop.classList.contains('hidden')) {
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
                                window.location.href = currentItems[activeIndex].url;
                            }
                        } else if (e.key === 'Escape') {
                            closeDrop();
                        }
                    }
                });

                document.addEventListener('click', (e) => {
                    if (!form.contains(e.target)) closeDrop();
                });

                // ── Escape helpers ───────────────────────────────────────────
                function escHtml(s) {
                    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                }
                function escRegex(s) {
                    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                }
            })();
            </script>
        </div>
    </div>

    <!-- Wave divider -->
    <div class="absolute bottom-0 left-0 right-0 leading-none" aria-hidden="true">
        <svg viewBox="0 0 1440 56" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto block" preserveAspectRatio="none">
            <path d="M0 56V32C180 8 360 0 540 16C720 32 900 48 1080 40C1260 32 1350 16 1440 8V56H0Z" fill="#f8f9fc"/>
        </svg>
    </div>
</section>

<!-- ===== RF-PUB-06 — Estadísticas Públicas ===== -->
<section class="bg-surface py-10" aria-label="Estadísticas de la biblioteca">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
            <!-- Recursos -->
            <div class="bg-surface-container-lowest rounded-[0.75rem] p-4 text-center shadow-ambient hover:shadow-ambient-lg transition-shadow duration-300 flex flex-col items-center justify-center gap-2">
                <div class="w-8 h-8 rounded-[0.375rem] bg-primary/8 flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <div>
                    <p class="headline-lg text-on-surface leading-none"><?= $stats['total_books'] ?? 0 ?></p>
                    <p class="label-sm text-on-surface-subtle mt-0.5">Recursos en catálogo</p>
                </div>
            </div>

            <!-- Usuarios -->
            <div class="bg-surface-container-lowest rounded-[0.75rem] p-4 text-center shadow-ambient hover:shadow-ambient-lg transition-shadow duration-300 flex flex-col items-center justify-center gap-2">
                <div class="w-8 h-8 rounded-[0.375rem] bg-primary/8 flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="headline-lg text-on-surface leading-none"><?= $stats['total_users'] ?? 0 ?></p>
                    <p class="label-sm text-on-surface-subtle mt-0.5">Usuarios activos</p>
                </div>
            </div>

            <!-- Préstamos -->
            <div class="bg-surface-container-lowest rounded-[0.75rem] p-4 text-center shadow-ambient hover:shadow-ambient-lg transition-shadow duration-300 flex flex-col items-center justify-center gap-2">
                <div class="w-8 h-8 rounded-[0.375rem] bg-tertiary/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                    </svg>
                </div>
                <div>
                    <p class="headline-lg text-on-surface leading-none"><?= $stats['total_loans'] ?? 0 ?></p>
                    <p class="label-sm text-on-surface-subtle mt-0.5">Préstamos realizados</p>
                </div>
            </div>

            <!-- Visitas — destacado con gradient-scholar -->
            <div class="gradient-scholar rounded-[0.75rem] p-4 text-center shadow-ambient-lg flex flex-col items-center justify-center gap-2 relative overflow-hidden">
                <div class="absolute inset-0 opacity-[0.06]">
                    <svg class="absolute -top-4 -right-4 w-24 h-24 text-white" fill="currentColor" viewBox="0 0 200 200" aria-hidden="true">
                        <circle cx="100" cy="100" r="80" fill="none" stroke="currentColor" stroke-width="2"/>
                        <circle cx="100" cy="100" r="50" fill="none" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div class="relative w-8 h-8 rounded-[0.375rem] bg-white/15 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="relative w-full overflow-hidden">
                    <p id="stat-visits-num" class="headline-lg text-white leading-none whitespace-nowrap"><?= $stats['total_visits'] ?? 0 ?></p>
                    <p class="label-sm text-white/70 mt-0.5">Visitas a la biblioteca</p>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
(function () {
    const el = document.getElementById('stat-visits-num');
    if (!el) return;
    const fit = function () {
        el.style.fontSize = '';
        var parent = el.parentElement;
        var fs = parseFloat(getComputedStyle(el).fontSize);
        while (el.scrollWidth > parent.clientWidth && fs > 8) {
            fs -= 1;
            el.style.fontSize = fs + 'px';
        }
    };
    fit();
    window.addEventListener('resize', fit);
})();
</script>

<!-- ===== Nuevas Adquisiciones ===== -->
<?php if (!empty($newAcquisitions)): ?>
<section class="py-16 bg-surface-container-lowest" aria-labelledby="new-acquisitions-title">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="label-sm text-tertiary font-display uppercase tracking-[0.12em] mb-2">Novedades</p>
                <h2 id="new-acquisitions-title" class="headline-lg text-on-surface">Nuevas Adquisiciones</h2>
            </div>
            <div class="flex items-center gap-2">
                <button class="swiper-btn-prev-new w-8 h-8 rounded-full bg-surface shadow-ambient hover:shadow-ambient-lg flex items-center justify-center text-on-surface-subtle hover:text-primary transition-all duration-200" aria-label="Anterior">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                </button>
                <button class="swiper-btn-next-new w-8 h-8 rounded-full bg-surface shadow-ambient hover:shadow-ambient-lg flex items-center justify-center text-on-surface-subtle hover:text-primary transition-all duration-200" aria-label="Siguiente">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </button>
                <a href="<?= BASE_URL ?>/catalog" class="hidden sm:inline-flex items-center gap-1 text-primary label-sm ml-2 hover:underline">
                    Ver todos
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
        </div>

        <!-- Swiper -->
        <div class="swiper swiper-new">
            <div class="swiper-wrapper">
                <?php foreach ($newAcquisitions as $book): ?>
                <?php
                    $authNew = $book['authors'] ?? '';
                    if (is_string($authNew) && str_starts_with($authNew, '[')) {
                        $dec = json_decode($authNew, true);
                        $authNew = is_array($dec) ? implode(', ', $dec) : $authNew;
                    }
                ?>
                <div class="swiper-slide" style="width:150px">
                    <a href="<?= BASE_URL ?>/catalog/<?= (int) $book['id'] ?>" class="group block bg-surface rounded-[0.75rem] overflow-hidden shadow-ambient hover:shadow-ambient-lg transition-all duration-300 hover:-translate-y-0.5 h-full">

                        <!-- Portada -->
                        <div class="relative aspect-[2/3] overflow-hidden">
                            <?php if (!empty($book['cover_image'])): ?>
                                <img src="<?= View::e($book['cover_image']) ?>"
                                     alt="Portada de <?= View::e($book['title']) ?>"
                                     class="w-full h-full object-cover group-hover:scale-[1.05] transition-transform duration-500"
                                     loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="w-full h-full flex flex-col items-center justify-center bg-surface-container gap-2 p-3">
                                    <svg class="w-7 h-7 text-on-surface-subtle/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    <p class="text-[10px] text-on-surface-subtle text-center leading-snug line-clamp-3"><?= View::e($book['title']) ?></p>
                                </div>
                            <?php endif; ?>
                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-primary/0 group-hover:bg-primary/50 transition-all duration-300 flex items-center justify-center">
                                <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-white text-primary text-[10px] font-display font-bold uppercase tracking-wide px-2.5 py-1 rounded-full shadow-md">Ver recurso</span>
                            </div>
                            <!-- Badges -->
                            <div class="absolute top-1.5 left-1.5 flex flex-col gap-1">
                                <span class="bg-tertiary text-white text-[8px] font-display font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-[3px] leading-none w-fit">Nuevo</span>
                                <?php if ($book['support_type'] === 'digital'): ?>
                                <span class="bg-primary text-white text-[8px] font-display font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded-[3px] leading-none w-fit">Digital</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="p-2 h-[3.75rem] flex flex-col">
                            <h3 class="text-[11px] font-semibold text-on-surface group-hover:text-primary line-clamp-2 leading-snug transition-colors duration-200"><?= View::e($book['title']) ?></h3>
                            <p class="text-[10px] text-on-surface-subtle mt-auto line-clamp-1"><?= View::e($authNew ?: '') ?></p>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>
<?php endif; ?>

<!-- ===== Recursos Más Prestados ===== -->
<?php if (!empty($topBooks)): ?>
<section class="py-16 bg-surface" aria-labelledby="top-books-title">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="label-sm text-tertiary font-display uppercase tracking-[0.12em] mb-2">Tendencias</p>
                <h2 id="top-books-title" class="headline-lg text-on-surface">Los Más Leídos</h2>
            </div>
            <div class="flex items-center gap-2">
                <button class="swiper-btn-prev-top w-8 h-8 rounded-full bg-surface-container-lowest shadow-ambient hover:shadow-ambient-lg flex items-center justify-center text-on-surface-subtle hover:text-primary transition-all duration-200" aria-label="Anterior">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                </button>
                <button class="swiper-btn-next-top w-8 h-8 rounded-full bg-surface-container-lowest shadow-ambient hover:shadow-ambient-lg flex items-center justify-center text-on-surface-subtle hover:text-primary transition-all duration-200" aria-label="Siguiente">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </button>
                <a href="<?= BASE_URL ?>/catalog" class="hidden sm:inline-flex items-center gap-1 text-primary label-sm ml-2 hover:underline">
                    Ver todos
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
        </div>

        <!-- Swiper -->
        <div class="swiper swiper-top">
            <div class="swiper-wrapper">
                <?php foreach ($topBooks as $book): ?>
                <?php
                    $authTop = $book['authors'] ?? '';
                    if (is_string($authTop) && str_starts_with($authTop, '[')) {
                        $dec = json_decode($authTop, true);
                        $authTop = is_array($dec) ? implode(', ', $dec) : $authTop;
                    }
                ?>
                <div class="swiper-slide" style="width:150px">
                    <a href="<?= BASE_URL ?>/catalog/<?= (int) $book['id'] ?>" class="group block bg-surface-container-lowest rounded-[0.75rem] overflow-hidden shadow-ambient hover:shadow-ambient-lg transition-all duration-300 hover:-translate-y-0.5 h-full">

                        <!-- Portada -->
                        <div class="relative aspect-[2/3] overflow-hidden">
                            <?php if (!empty($book['cover_image'])): ?>
                                <img src="<?= View::e($book['cover_image']) ?>"
                                     alt="Portada de <?= View::e($book['title']) ?>"
                                     class="w-full h-full object-cover group-hover:scale-[1.05] transition-transform duration-500"
                                     loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="w-full h-full flex flex-col items-center justify-center bg-surface-container gap-2 p-3">
                                    <svg class="w-7 h-7 text-on-surface-subtle/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    <p class="text-[10px] text-on-surface-subtle text-center leading-snug line-clamp-3"><?= View::e($book['title']) ?></p>
                                </div>
                            <?php endif; ?>
                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-primary/0 group-hover:bg-primary/50 transition-all duration-300 flex items-center justify-center">
                                <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-white text-primary text-[10px] font-display font-bold uppercase tracking-wide px-2.5 py-1 rounded-full shadow-md">Ver recurso</span>
                            </div>
                            <!-- Badge préstamos -->
                            <?php if (($book['loan_count'] ?? 0) > 0): ?>
                            <div class="absolute top-1.5 right-1.5 bg-tertiary/90 backdrop-blur-sm text-white text-[8px] font-display font-bold px-1.5 py-0.5 rounded-[3px] leading-none flex items-center gap-0.5">
                                <svg class="w-2 h-2" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <?= (int) $book['loan_count'] ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Info -->
                        <div class="p-2 h-[3.75rem] flex flex-col">
                            <h3 class="text-[11px] font-semibold text-on-surface group-hover:text-primary line-clamp-2 leading-snug transition-colors duration-200"><?= View::e($book['title']) ?></h3>
                            <p class="text-[10px] text-on-surface-subtle mt-auto line-clamp-1"><?= View::e($authTop ?: '') ?></p>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>
<?php endif; ?>

<!-- ===== Últimas Noticias ===== -->
<?php if (!empty($news)): ?>
<section class="py-20 bg-surface-container-lowest" aria-labelledby="news-title">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between mb-12">
            <div>
                <h2 id="news-title" class="headline-lg text-on-surface">Noticias</h2>
                <p class="mt-2 body-md text-on-surface-muted">Mantente al día con las novedades de la biblioteca</p>
            </div>
            <a href="<?= BASE_URL ?>/news" class="hidden sm:inline-flex items-center gap-1.5 text-primary hover:text-primary-muted label-md transition-colors duration-200">
                Ver todas
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($news as $item): ?>
                <article class="group bg-surface-container-lowest rounded-[0.75rem] overflow-hidden shadow-ambient hover:shadow-ambient-lg transition-all duration-300">
                    <div class="aspect-[16/9] overflow-hidden bg-surface-container">
                        <?php if (!empty($item['cover_image'])): ?>
                            <img src="<?= View::e($item['cover_image']) ?>"
                                 alt=""
                                 class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-500"
                                 loading="lazy"
                                 decoding="async">
                        <?php else: ?>
                            <div class="w-full h-full bg-surface-container-low flex items-center justify-center">
                                <svg class="w-12 h-12 text-on-surface-subtle/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-6">
                        <?php if (!empty($item['published_at'])): ?>
                            <time class="label-sm text-on-surface-subtle uppercase tracking-wider" datetime="<?= View::e($item['published_at']) ?>">
                                <?= date('d M Y', strtotime($item['published_at'])) ?>
                            </time>
                        <?php endif; ?>
                        <h3 class="mt-2 headline-md text-on-surface group-hover:text-primary transition-colors duration-200 line-clamp-2 leading-snug">
                            <a href="<?= BASE_URL ?>/news/<?= View::e($item['slug']) ?>" class="focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 rounded-[0.375rem]">
                                <?= View::e($item['title']) ?>
                            </a>
                        </h3>
                        <?php if (!empty($item['excerpt'])): ?>
                            <p class="mt-3 body-md text-on-surface-muted line-clamp-3 leading-relaxed">
                                <?= View::e($item['excerpt']) ?>
                            </p>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/news/<?= View::e($item['slug']) ?>"
                           class="inline-flex items-center gap-1 mt-4 label-md text-primary hover:text-primary-muted transition-colors duration-200"
                           aria-label="Leer más sobre <?= View::e($item['title']) ?>">
                            Leer más
                            <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== Sedes ===== -->
<?php if (count($branches) > 1): ?>
<section class="py-20 bg-surface" aria-labelledby="branches-title">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 id="branches-title" class="headline-lg text-on-surface">Nuestras Sedes</h2>
            <p class="mt-2 body-md text-on-surface-muted">Visítanos en cualquiera de nuestras ubicaciones</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($branches as $branch): ?>
                <div class="bg-surface-container-lowest rounded-[0.75rem] p-7 shadow-ambient hover:shadow-ambient-lg transition-all duration-300">
                    <h3 class="headline-md text-on-surface mb-4"><?= View::e($branch['name']) ?></h3>
                    <div class="space-y-3">
                        <?php if (!empty($branch['address'])): ?>
                            <div class="flex items-start gap-3 body-md text-on-surface-muted">
                                <svg class="w-4 h-4 mt-0.5 shrink-0 text-on-surface-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                </svg>
                                <span><?= View::e($branch['address']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($branch['phone'])): ?>
                            <div class="flex items-center gap-3 body-md text-on-surface-muted">
                                <svg class="w-4 h-4 shrink-0 text-on-surface-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                                </svg>
                                <span><?= View::e($branch['phone']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($branch['schedule'])): ?>
                            <div class="flex items-center gap-3 body-md text-on-surface-muted">
                                <svg class="w-4 h-4 shrink-0 text-on-surface-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span><?= View::e($branch['schedule']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var cfg = {
        slidesPerView: 'auto',
        spaceBetween: 12,
        grabCursor: true,
        loop: false,
        freeMode: true,
    };

    new Swiper('.swiper-new', Object.assign({}, cfg, {
        navigation: {
            prevEl: '.swiper-btn-prev-new',
            nextEl: '.swiper-btn-next-new',
        },
    }));

    new Swiper('.swiper-top', Object.assign({}, cfg, {
        navigation: {
            prevEl: '.swiper-btn-prev-top',
            nextEl: '.swiper-btn-next-top',
        },
    }));
});
</script>

<?php include BASE_PATH . '/views/partials/cta-register.php'; ?>
