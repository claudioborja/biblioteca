<?php
/**
 * Vista: Catalogo publico
 * Variables: $books, $categories, $resource_types, $support_types, $languages, $filters, $settings
 */
use Core\View;

$q            = (string) ($filters['q'] ?? '');
$categoryId   = (int) ($filters['categoryId'] ?? 0);
$resourceType = (string) ($filters['resourceType'] ?? '');
$supportType  = (string) ($filters['supportType'] ?? '');
$language     = (string) ($filters['language'] ?? '');
$avail        = !empty($filters['avail']);
$pagination   = is_array($pagination ?? null) ? $pagination : [];
$page         = max(1, (int) ($pagination['page'] ?? 1));
$totalPages   = max(1, (int) ($pagination['total_pages'] ?? 1));
$total        = (int) ($pagination['total'] ?? count($books ?? []));

$resourceTypes = is_array($resource_types ?? null) ? $resource_types : [];
$supportTypes  = is_array($support_types ?? null) ? $support_types : [];
$languages     = is_array($languages ?? null) ? $languages : [];
$categories    = is_array($categories ?? null) ? $categories : [];
$resourceSupportMap = is_array($resource_support_map ?? null) ? $resource_support_map : [];

$resourceTypeLabels = [
    'book' => 'Libro',
    'ebook' => 'Libro digital',
    'journal'     => 'Revista',
    'thesis'      => 'Tesis',
    'map'         => 'Otro',
    'score'       => 'Otro',
    'audiovisual' => 'Otro',
    'game'        => 'Otro',
    'kit'         => 'Otro',
    'other'       => 'Otro',
];

$supportTypeLabels = [
    'physical' => 'Físico',
    'digital'  => 'Digital',
    'journal'  => 'Revista',
    'thesis'   => 'Tesis',
    'other'    => 'Otro',
];

function catalogUrl(array $override = []): string {
    global $q, $categoryId, $resourceType, $supportType, $language, $avail;

    $params = array_filter([
        'q'             => $q,
        'category'      => $categoryId ?: '',
        'resource_type' => $resourceType,
        'support_type'  => $supportType,
        'language'      => $language,
        'available'     => $avail ? '1' : '',
    ], fn($v) => $v !== '');

    $params = array_merge($params, array_filter($override, fn($v) => $v !== ''));
    foreach ($override as $k => $v) {
        if ($v === '') {
            unset($params[$k]);
        }
    }

    return BASE_URL . '/catalog' . ($params ? '?' . http_build_query($params) : '');
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8">

        <aside class="lg:w-56 shrink-0">

            <form action="<?= BASE_URL ?>/catalog" method="GET" role="search" class="mb-6">
                <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
                <?php if ($resourceType): ?><input type="hidden" name="resource_type" value="<?= View::e($resourceType) ?>"><?php endif; ?>
                <?php if ($supportType): ?><input type="hidden" name="support_type" value="<?= View::e($supportType) ?>"><?php endif; ?>
                <?php if ($language): ?><input type="hidden" name="language" value="<?= View::e($language) ?>"><?php endif; ?>
                <?php if ($avail): ?><input type="hidden" name="available" value="1"><?php endif; ?>
                <div class="flex items-center bg-surface-container rounded-[0.5rem] px-3 gap-2 h-9">
                    <svg class="w-4 h-4 text-on-surface-subtle shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/></svg>
                    <input type="text" name="q" value="<?= View::e($q) ?>"
                           placeholder="Titulo, autor, ISBN..."
                           class="flex-1 bg-transparent text-sm text-on-surface placeholder-on-surface-subtle focus:outline-none"
                           autocomplete="off">
                </div>
            </form>

            <?php if ($q || $categoryId || $resourceType || $supportType || $language || $avail): ?>
            <div class="mb-5 flex flex-wrap gap-1.5">
                <?php if ($q): ?>
                    <a href="<?= catalogUrl(['q' => '']) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary text-xs font-medium rounded-full hover:bg-primary/20 transition-colors">
                        "<?= View::e(mb_strimwidth($q, 0, 18, '...')) ?>"
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>
                <?php if ($categoryId): ?>
                    <?php $catName = ''; foreach ($categories as $c) { if ((int)$c['id'] === $categoryId) { $catName = $c['name']; break; } } ?>
                    <a href="<?= catalogUrl(['category' => '']) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary text-xs font-medium rounded-full hover:bg-primary/20 transition-colors">
                        <?= View::e($catName) ?>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>
                <?php if ($resourceType): ?>
                    <a href="<?= catalogUrl(['resource_type' => '']) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary text-xs font-medium rounded-full hover:bg-primary/20 transition-colors">
                        <?= View::e($resourceTypeLabels[$resourceType] ?? ucfirst($resourceType)) ?>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>
                <?php if ($supportType): ?>
                    <a href="<?= catalogUrl(['support_type' => '']) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary text-xs font-medium rounded-full hover:bg-primary/20 transition-colors">
                        <?= View::e($supportTypeLabels[$supportType] ?? ucfirst($supportType)) ?>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>
                <?php if ($language): ?>
                    <a href="<?= catalogUrl(['language' => '']) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary text-xs font-medium rounded-full hover:bg-primary/20 transition-colors">
                        <?= strtoupper(View::e($language)) ?>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>
                <?php if ($avail): ?>
                    <a href="<?= catalogUrl(['available' => '']) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary text-xs font-medium rounded-full hover:bg-primary/20 transition-colors">
                        Disponibles
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form method="GET" action="<?= BASE_URL ?>/catalog" class="catalog-filters space-y-4">
                <input type="hidden" name="q" value="<?= View::e($q) ?>">

                <div>
                    <label class="mb-1 block label-sm text-on-surface-subtle uppercase tracking-wider">Tipo de recurso</label>
                    <select id="filter-resource-type" name="resource_type" class="js-catalog-select w-full rounded-[0.5rem] border border-transparent bg-surface-container px-2.5 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <option value="">Todos</option>
                        <?php foreach ($resourceTypes as $rt): $value = (string)($rt['value'] ?? 'other'); ?>
                            <option value="<?= View::e($value) ?>" <?= $resourceType === $value ? 'selected' : '' ?>>
                                <?= View::e(($resourceTypeLabels[$value] ?? ucfirst($value)) . ' (' . (int)($rt['total'] ?? 0) . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block label-sm text-on-surface-subtle uppercase tracking-wider">Soporte</label>
                    <select id="filter-support-type" name="support_type" class="js-catalog-select w-full rounded-[0.5rem] border border-transparent bg-surface-container px-2.5 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <option value="">Todos</option>
                        <?php foreach ($supportTypes as $st): $value = (string)($st['value'] ?? 'other'); ?>
                            <option value="<?= View::e($value) ?>" <?= $supportType === $value ? 'selected' : '' ?>>
                                <?= View::e(($supportTypeLabels[$value] ?? ucfirst($value)) . ' (' . (int)($st['total'] ?? 0) . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block label-sm text-on-surface-subtle uppercase tracking-wider">Idioma</label>
                    <select name="language" class="js-catalog-select w-full rounded-[0.5rem] border border-transparent bg-surface-container px-2.5 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <option value="">Todos</option>
                        <?php foreach ($languages as $lang): $value = strtolower((string)($lang['value'] ?? '')); if ($value === '') { continue; } ?>
                            <option value="<?= View::e($value) ?>" <?= strtolower($language) === $value ? 'selected' : '' ?>>
                                <?= strtoupper(View::e($value)) ?> (<?= (int)($lang['total'] ?? 0) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block label-sm text-on-surface-subtle uppercase tracking-wider">Categoria</label>
                    <select name="category" class="js-catalog-select w-full rounded-[0.5rem] border border-transparent bg-surface-container px-2.5 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <option value="">Todas</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= View::e($cat['name']) ?> (<?= (int)$cat['book_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block label-sm text-on-surface-subtle uppercase tracking-wider">Disponibilidad</label>
                    <select name="available" class="js-catalog-select w-full rounded-[0.5rem] border border-transparent bg-surface-container px-2.5 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <option value="" <?= !$avail ? 'selected' : '' ?>>Todas</option>
                        <option value="1" <?= $avail ? 'selected' : '' ?>>Solo disponibles</option>
                    </select>
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="submit" class="flex-1 rounded-[0.5rem] bg-primary/10 px-3 py-2 text-sm font-semibold text-primary hover:bg-primary/20 transition-colors">Aplicar</button>
                    <a href="<?= BASE_URL ?>/catalog<?= $q !== '' ? ('?q=' . rawurlencode($q)) : '' ?>" class="rounded-[0.5rem] px-3 py-2 text-sm font-semibold text-on-surface-subtle hover:bg-surface-container transition-colors">Limpiar</a>
                </div>
            </form>

        </aside>

        <div class="flex-1 min-w-0">

            <?php if (empty($books)): ?>
                <div class="flex flex-col items-center justify-center py-20 text-center gap-4">
                    <svg class="w-12 h-12 text-on-surface-subtle/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <p class="text-on-surface-subtle">No se encontraron recursos con estos filtros.</p>
                    <a href="<?= BASE_URL ?>/catalog" class="text-primary text-sm hover:underline">Limpiar filtros</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4">
                    <?php foreach ($books as $book):
                        $authors = $book['authors'] ?? '';
                        if (is_string($authors) && str_starts_with(trim($authors), '[')) {
                            $dec = json_decode($authors, true);
                            $authors = is_array($dec) ? implode(', ', $dec) : $authors;
                        }
                        $available = (int)($book['available_copies'] ?? 0);
                        $isDigital = ($book['support_type'] ?? '') === 'digital';
                    ?>
                    <a href="<?= BASE_URL ?>/catalog/<?= (int)$book['id'] ?>"
                       class="group block bg-surface rounded-[0.75rem] overflow-hidden shadow-ambient hover:shadow-ambient-lg transition-all duration-300 hover:-translate-y-0.5">

                        <div class="relative aspect-[2/3] overflow-hidden">
                            <?php if (!empty($book['cover_image'])): ?>
                                <img src="<?= View::e($book['cover_image']) ?>"
                                     alt="<?= View::e($book['title']) ?>"
                                     class="w-full h-full object-cover group-hover:scale-[1.05] transition-transform duration-500"
                                     loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="w-full h-full bg-surface-container flex flex-col items-center justify-center gap-2 p-3">
                                    <svg class="w-6 h-6 text-on-surface-subtle/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    <p class="text-[9px] text-on-surface-subtle text-center leading-snug line-clamp-3"><?= View::e($book['title']) ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="absolute inset-0 bg-primary/0 group-hover:bg-primary/50 transition-all duration-300 flex items-center justify-center">
                                <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-white text-primary text-[10px] font-display font-bold uppercase tracking-wide px-2.5 py-1 rounded-full shadow-md">Ver recurso</span>
                            </div>

                            <div class="absolute top-1.5 left-1.5 flex flex-col gap-1">
                                <?php if ($book['is_new_acquisition']): ?>
                                    <span class="bg-tertiary text-white text-[8px] font-display font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-[3px] leading-none">Nuevo</span>
                                <?php endif; ?>
                                <?php if ($isDigital): ?>
                                    <span class="bg-primary text-white text-[8px] font-display font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded-[3px] leading-none">Digital</span>
                                <?php endif; ?>
                            </div>

                            <?php if (!$isDigital && $available === 0): ?>
                                <div class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[9px] text-center py-0.5 font-medium">No disponible</div>
                            <?php endif; ?>
                        </div>

                        <div class="p-2">
                            <h3 class="text-[11px] font-semibold text-on-surface group-hover:text-primary line-clamp-2 leading-snug transition-colors"><?= View::e($book['title']) ?></h3>
                            <?php if (!empty($authors)): ?>
                                <p class="text-[10px] text-on-surface-subtle mt-0.5 line-clamp-1"><?= View::e($authors) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($book['category_name'])): ?>
                                <p class="text-[9px] text-tertiary mt-0.5 font-medium truncate"><?= View::e($book['category_name']) ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-6 flex items-center justify-center gap-2" aria-label="Paginacion de catalogo">
                        <?php if ($page > 1): ?>
                            <a href="<?= catalogUrl(['page' => (string) ($page - 1)]) ?>" class="rounded-md border border-outline-variant px-3 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">Anterior</a>
                        <?php endif; ?>

                        <?php
                        $pagesToShow = [1, $totalPages, $page - 1, $page, $page + 1];
                        $pagesToShow = array_values(array_unique(array_filter($pagesToShow, static fn(int $p): bool => $p >= 1 && $p <= $totalPages)));
                        sort($pagesToShow);
                        $prevPrinted = null;
                        foreach ($pagesToShow as $p):
                            if ($prevPrinted !== null && $p - $prevPrinted > 1):
                        ?>
                                <span class="px-1 text-xs font-semibold text-on-surface-subtle">...</span>
                        <?php
                            endif;
                        ?>
                            <a href="<?= catalogUrl(['page' => (string) $p]) ?>"
                               class="rounded-md px-3 py-1.5 text-xs font-semibold transition-colors <?= $p === $page ? 'bg-primary text-white' : 'border border-outline-variant text-on-surface-muted hover:bg-surface-container-low' ?>">
                                <?= $p ?>
                            </a>
                        <?php
                            $prevPrinted = $p;
                        endforeach;
                        ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?= catalogUrl(['page' => (string) ($page + 1)]) ?>" class="rounded-md border border-outline-variant px-3 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">Siguiente</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include BASE_PATH . '/views/partials/cta-register.php'; ?>

<script>
(() => {
    window.__initCatalogFilterSelects = function () {
        const resourceSelect = document.getElementById('filter-resource-type');
        const supportSelect = document.getElementById('filter-support-type');
        if (!resourceSelect || !supportSelect) return;

        const hasSelect2 = !!(window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function');
        const resourceSupportMap = <?= json_encode($resourceSupportMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const initialOptions = Array.from(supportSelect.options).map(opt => ({ value: opt.value, text: opt.text }));

        const initSelect2 = () => {
            if (!hasSelect2) return;
            window.jQuery('.js-catalog-select').select2({
                width: '100%',
                minimumResultsForSearch: 0
            });
        };

        const rebuildSupportOptions = () => {
            if (hasSelect2 && window.jQuery(supportSelect).data('select2')) {
                window.jQuery(supportSelect).select2('destroy');
            }

            const selectedResourceType = resourceSelect.value;
            const currentSupport = supportSelect.value;
            const allowed = selectedResourceType && Array.isArray(resourceSupportMap[selectedResourceType])
                ? new Set(resourceSupportMap[selectedResourceType])
                : null;

            supportSelect.innerHTML = '';
            initialOptions.forEach(opt => {
                if (opt.value === '' || !allowed || allowed.has(opt.value)) {
                    const option = document.createElement('option');
                    option.value = opt.value;
                    option.textContent = opt.text;
                    supportSelect.appendChild(option);
                }
            });

            if (currentSupport !== '' && Array.from(supportSelect.options).some(opt => opt.value === currentSupport)) {
                supportSelect.value = currentSupport;
            } else {
                supportSelect.value = '';
            }

            if (hasSelect2) {
                window.jQuery(supportSelect).select2({
                    width: '100%',
                    minimumResultsForSearch: 0
                });
            }
        };

        if (hasSelect2) {
            window.jQuery(resourceSelect).off('change.catalog select2:select.catalog');
            window.jQuery(resourceSelect).on('change.catalog select2:select.catalog', rebuildSupportOptions);
        } else {
            resourceSelect.removeEventListener('change', rebuildSupportOptions);
            resourceSelect.addEventListener('change', rebuildSupportOptions);
        }
        initSelect2();
        rebuildSupportOptions();
    };
})();
</script>
