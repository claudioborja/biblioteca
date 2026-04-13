<?php
/**
 * Vista: Noticias públicas
 * Variables: $featured, $secondary, $table_news, $table_page, $table_total_pages, $table_total, $q, $total, $settings
 */
use Core\View;

$featured = is_array($featured ?? null) ? $featured : null;
$secondary = is_array($secondary ?? null) ? $secondary : [];
$tableNews = is_array($table_news ?? null) ? $table_news : [];
$tablePage = max(1, (int) ($table_page ?? 1));
$tableTotalPages = max(1, (int) ($table_total_pages ?? 1));
$tableTotal = (int) ($table_total ?? count($tableNews));
$q = (string) ($q ?? '');
$total = (int) ($total ?? 0);

$months = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$formatDate = static function(string $date) use ($months): string {
    $ts = strtotime($date);
    if ($ts === false) return '';
    return (int) date('d', $ts) . ' de ' . $months[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
};

$tableUrl = static function (int $page, string $search = ''): string {
    $params = ['table_page' => $page];
    if ($search !== '') {
        $params['q'] = $search;
    }
    return BASE_URL . '/news?' . http_build_query($params);
};
?>

<div class="relative overflow-hidden gradient-scholar">
    <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 20% 50%,white 1px,transparent 1px),radial-gradient(circle at 75% 25%,white 1px,transparent 1px);background-size:36px 36px;"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="inline-flex items-center gap-2 bg-white/15 text-white text-xs font-semibold uppercase tracking-widest px-3 py-1.5 rounded-full mb-4">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5"/></svg>
            Actualidad
        </div>
        <h1 class="text-3xl sm:text-4xl font-display font-bold text-white leading-tight">Noticias</h1>
        <p class="mt-2 text-white/75 text-sm sm:text-base max-w-xl">1 destacada, 3 recientes y archivo consultable.</p>
        <?php if ($total > 0): ?>
            <p class="mt-4 text-white/50 text-xs"><?= $total ?> <?= $total === 1 ? 'artículo publicado' : 'artículos publicados' ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="border-b border-surface-container bg-surface-container-lowest">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-1.5 text-xs text-on-surface-subtle py-3" aria-label="Breadcrumb">
            <a href="<?= BASE_URL ?>/" class="hover:text-primary transition-colors">Inicio</a>
            <svg class="w-3 h-3 shrink-0 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <span class="text-on-surface font-medium">Noticias</span>
        </nav>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php if ($featured === null): ?>
        <div class="flex flex-col items-center justify-center py-24 text-center gap-5">
            <div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center">
                <svg class="w-8 h-8 text-on-surface-subtle/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5"/></svg>
            </div>
            <div>
                <p class="text-on-surface font-semibold">Sin noticias publicadas</p>
                <p class="text-on-surface-subtle text-sm mt-1">Vuelve pronto para ver las últimas novedades.</p>
            </div>
        </div>
    <?php else: ?>
        <a href="<?= BASE_URL ?>/news/<?= View::e((string) $featured['slug']) ?>" class="group block mb-8 bg-surface rounded-[1rem] overflow-hidden shadow-ambient hover:shadow-ambient-lg transition-all duration-300 hover:-translate-y-0.5 sm:flex">
            <div class="sm:w-96 shrink-0 relative overflow-hidden aspect-video sm:aspect-auto">
                <?php if (!empty($featured['cover_image'])): ?>
                    <img src="<?= View::e((string) $featured['cover_image']) ?>" alt="<?= View::e((string) $featured['title']) ?>" class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-500">
                <?php else: ?>
                    <div class="w-full h-full min-h-[200px] gradient-scholar flex items-center justify-center">
                        <svg class="w-10 h-10 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5"/></svg>
                    </div>
                <?php endif; ?>
                <span class="absolute top-3 left-3 bg-tertiary text-white text-[10px] font-display font-bold uppercase tracking-wider px-2 py-1 rounded-[4px]">Destacada</span>
            </div>
            <div class="flex flex-col justify-between p-6 sm:p-8 flex-1">
                <div>
                    <p class="text-xs text-on-surface-subtle mb-2"><?= $formatDate((string) ($featured['published_at'] ?? '')) ?></p>
                    <h2 class="text-xl sm:text-2xl font-display font-bold text-on-surface group-hover:text-primary transition-colors leading-snug mb-3"><?= View::e((string) $featured['title']) ?></h2>
                    <?php if (!empty($featured['excerpt'])): ?><p class="text-on-surface-subtle text-sm leading-relaxed line-clamp-3"><?= View::e((string) $featured['excerpt']) ?></p><?php endif; ?>
                </div>
                <div class="mt-5 text-primary font-semibold text-sm">Leer más</div>
            </div>
        </a>

        <?php if ($secondary !== []): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <?php foreach ($secondary as $item): ?>
                    <a href="<?= BASE_URL ?>/news/<?= View::e((string) $item['slug']) ?>" class="group flex flex-col bg-surface rounded-[0.875rem] overflow-hidden shadow-ambient hover:shadow-ambient-lg transition-all duration-300 hover:-translate-y-0.5">
                        <div class="relative aspect-video overflow-hidden shrink-0">
                            <?php if (!empty($item['cover_image'])): ?>
                                <img src="<?= View::e((string) $item['cover_image']) ?>" alt="<?= View::e((string) $item['title']) ?>" class="w-full h-full object-cover group-hover:scale-[1.04] transition-transform duration-500" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="w-full h-full gradient-scholar"></div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <p class="text-[11px] text-on-surface-subtle mb-2"><?= $formatDate((string) ($item['published_at'] ?? '')) ?></p>
                            <h3 class="font-display font-bold text-on-surface group-hover:text-primary transition-colors text-[15px] leading-snug line-clamp-2"><?= View::e((string) $item['title']) ?></h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section id="news-archive-section" class="rounded-2xl border border-outline-variant/60 bg-white shadow-ambient overflow-hidden">
            <div class="border-b border-outline-variant/60 bg-surface-container-low px-4 py-3 sm:px-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="title-sm text-on-surface">Archivo de noticias</h2>
                        <p class="mt-0.5 text-xs text-on-surface-subtle">Historial completo con búsqueda y paginación</p>
                    </div>
                    <form method="get" action="<?= BASE_URL ?>/news" class="js-news-table-form flex w-full sm:w-auto gap-2">
                        <input type="hidden" name="table_page" value="1">
                        <input id="news-archive-search" name="q" value="<?= View::e($q) ?>" type="search" placeholder="Buscar por título, autor o fecha..." class="w-full sm:w-80 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2m0 0A7.5 7.5 0 1 0 5.2 5.2a7.5 7.5 0 0 0 10.6 10.6z"/></svg>
                            Buscar
                        </button>
                    </form>
                </div>
            </div>

            <div class="p-4 sm:p-5">
                <?php if ($tableNews === []): ?>
                    <p class="text-sm text-on-surface-subtle">No hay más noticias en el archivo.</p>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-surface-container-lowest">
                        <table class="w-full min-w-[620px] text-sm">
                            <thead class="bg-surface-container-low">
                                <tr class="text-left text-on-surface-subtle">
                                    <th class="py-2.5 px-3 font-semibold">Fecha</th>
                                    <th class="py-2.5 px-3 font-semibold">Título</th>
                                    <th class="py-2.5 px-3 font-semibold">Autor</th>
                                    <th class="py-2.5 px-3 font-semibold">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tableNews as $item): ?>
                                    <?php $title = (string) ($item['title'] ?? ''); $author = (string) ($item['author_name'] ?? ''); $dateLabel = $formatDate((string) ($item['published_at'] ?? '')); ?>
                                    <tr class="border-t border-outline-variant/40 odd:bg-white even:bg-surface-container-lowest hover:bg-primary/5 transition-colors">
                                        <td class="py-2.5 px-3"><span class="inline-flex rounded-full bg-surface-container-low px-2 py-0.5 text-xs font-medium text-on-surface-muted"><?= View::e($dateLabel) ?></span></td>
                                        <td class="py-2.5 px-3 text-on-surface font-semibold"><?= View::e($title) ?></td>
                                        <td class="py-2.5 px-3 text-on-surface-muted"><?= View::e($author !== '' ? $author : '-') ?></td>
                                        <td class="py-2.5 px-3">
                                            <a href="<?= BASE_URL ?>/news/<?= View::e((string) ($item['slug'] ?? '')) ?>"
                                               class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-primary/30 bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors"
                                               data-tooltip="Ver noticia"
                                               aria-label="Ver noticia">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75A3.75 3.75 0 1012 8.25a3.75 3.75 0 000 7.5z"/>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-on-surface-subtle"><?= $tableTotal ?> resultado<?= $tableTotal === 1 ? '' : 's' ?> en tabla</p>
                        <?php if ($tableTotalPages > 1): ?>
                            <nav class="flex items-center gap-1.5" aria-label="Paginación de tabla de noticias">
                                <?php if ($tablePage > 1): ?>
                                    <a href="<?= View::e($tableUrl($tablePage - 1, $q)) ?>" class="js-table-page rounded-md border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">Anterior</a>
                                <?php endif; ?>
                                <?php
                                $showPages = [1, $tableTotalPages, $tablePage - 1, $tablePage, $tablePage + 1];
                                $showPages = array_values(array_unique(array_filter($showPages, static fn(int $p): bool => $p >= 1 && $p <= $tableTotalPages)));
                                sort($showPages);
                                $prev = null;
                                foreach ($showPages as $p):
                                    if ($prev !== null && $p - $prev > 1):
                                ?>
                                    <span class="px-1 text-xs text-on-surface-subtle">...</span>
                                <?php
                                    endif;
                                ?>
                                    <a href="<?= View::e($tableUrl($p, $q)) ?>" class="js-table-page rounded-md px-2.5 py-1.5 text-xs font-semibold transition-colors <?= $p === $tablePage ? 'bg-primary text-white' : 'border border-outline-variant text-on-surface-muted hover:bg-surface-container-low' ?>"><?= $p ?></a>
                                <?php
                                    $prev = $p;
                                endforeach;
                                ?>
                                <?php if ($tablePage < $tableTotalPages): ?>
                                    <a href="<?= View::e($tableUrl($tablePage + 1, $q)) ?>" class="js-table-page rounded-md border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">Siguiente</a>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/views/partials/cta-register.php'; ?>

<script>
(() => {
    const sectionId = 'news-archive-section';

    const getSection = () => document.getElementById(sectionId);

    const loadTableSection = async (url) => {
        const currentSection = getSection();
        if (!currentSection) {
            window.location.href = url;
            return;
        }

        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
            if (!response.ok) {
                window.location.href = url;
                return;
            }

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextSection = doc.getElementById(sectionId);
            if (!nextSection) {
                window.location.href = url;
                return;
            }

            currentSection.replaceWith(nextSection);
            history.pushState({}, '', url);
        } catch (_err) {
            window.location.href = url;
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a.js-table-page');
        if (!link) return;
        const section = getSection();
        if (!section || !section.contains(link)) return;
        event.preventDefault();
        loadTableSection(link.href);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form.js-news-table-form');
        if (!form) return;
        const section = getSection();
        if (!section || !section.contains(form)) return;
        event.preventDefault();

        const params = new URLSearchParams(new FormData(form));
        params.set('table_page', '1');
        const url = form.action + '?' + params.toString();
        loadTableSection(url);
    });
})();
</script>
