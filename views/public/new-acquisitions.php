<?php
/**
 * Vista: Novedades / Nuevas Adquisiciones
 * Variables: $books (array), $settings (array), $title (string), $month_options (array), $selected_month (string)
 */
use Core\View;

$total    = count($books);
$physical = array_filter($books, fn($b) => ($b['support_type'] ?? '') !== 'digital');
$digital  = array_filter($books, fn($b) => ($b['support_type'] ?? '') === 'digital');
$monthOptions = is_array($month_options ?? null) ? $month_options : [];
$selectedMonth = (string) ($selected_month ?? '');

// Group books by month of acquisition
$byMonth = [];
foreach ($books as $book) {
    $ts    = strtotime($book['acquired_at'] ?? 'now');
    $key   = date('Y-m', $ts);
    $months = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $label = $months[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
    if (!isset($byMonth[$key])) {
        $byMonth[$key] = ['label' => $label, 'books' => []];
    }
    $byMonth[$key]['books'][] = $book;
}
krsort($byMonth); // most recent first
?>

<!-- ── Hero ─────────────────────────────────────────────────────────── -->
<div class="relative overflow-hidden gradient-scholar">
    <div class="absolute inset-0 opacity-10"
         style="background-image:radial-gradient(circle at 20% 50%,white 1px,transparent 1px),radial-gradient(circle at 80% 20%,white 1px,transparent 1px);background-size:40px 40px;"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 bg-white/15 text-white text-xs font-semibold uppercase tracking-widest px-3 py-1.5 rounded-full mb-4">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    Recién llegados
                </div>
                <h1 class="text-3xl sm:text-4xl font-display font-bold text-white leading-tight">
                    Novedades
                </h1>
                <p class="mt-2 text-white/75 text-sm sm:text-base max-w-lg">
                    Los últimos títulos incorporados a nuestra colección. ¡Sé el primero en leerlos!
                </p>
            </div>

            <!-- Stats pills -->
            <div class="flex flex-wrap gap-3 shrink-0">
                <div class="bg-white/15 backdrop-blur-sm rounded-xl px-4 py-3 text-center min-w-[80px]">
                    <p class="text-2xl font-display font-bold text-white"><?= $total ?></p>
                    <p class="text-white/70 text-[11px] font-medium mt-0.5">Total</p>
                </div>
                <div class="bg-white/15 backdrop-blur-sm rounded-xl px-4 py-3 text-center min-w-[80px]">
                    <p class="text-2xl font-display font-bold text-white"><?= count($physical) ?></p>
                    <p class="text-white/70 text-[11px] font-medium mt-0.5">Físicos</p>
                </div>
                <div class="bg-white/15 backdrop-blur-sm rounded-xl px-4 py-3 text-center min-w-[80px]">
                    <p class="text-2xl font-display font-bold text-white"><?= count($digital) ?></p>
                    <p class="text-white/70 text-[11px] font-medium mt-0.5">Digitales</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Breadcrumb ─────────────────────────────────────────────────────── -->
<div class="border-b border-surface-container bg-surface-container-lowest">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-1.5 text-xs text-on-surface-subtle py-3" aria-label="Breadcrumb">
            <a href="<?= BASE_URL ?>/" class="hover:text-primary transition-colors">Inicio</a>
            <svg class="w-3 h-3 shrink-0 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <span class="text-on-surface font-medium">Novedades</span>
        </nav>
    </div>
</div>

<!-- ── Content ───────────────────────────────────────────────────────── -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php if ($monthOptions !== []): ?>
        <form method="get" action="<?= BASE_URL ?>/new-arrivals" class="mb-6 flex items-center justify-end gap-2">
            <label for="month" class="text-xs font-semibold uppercase tracking-wider text-on-surface-subtle">Mes</label>
            <select id="month" name="month" class="rounded-lg border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none" onchange="this.form.submit()">
                <?php foreach ($monthOptions as $month): ?>
                    <option value="<?= View::e((string) ($month['key'] ?? '')) ?>" <?= ((string) ($month['key'] ?? '') === $selectedMonth) ? 'selected' : '' ?>>
                        <?= View::e((string) ($month['label'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>

    <?php if (empty($books)): ?>
        <!-- Empty state -->
        <div class="flex flex-col items-center justify-center py-24 text-center gap-5">
            <div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center">
                <svg class="w-8 h-8 text-on-surface-subtle/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div>
                <p class="text-on-surface font-semibold">Sin novedades por ahora</p>
                <p class="text-on-surface-subtle text-sm mt-1">Vuelve pronto para ver los nuevos títulos.</p>
            </div>
            <a href="<?= BASE_URL ?>/catalog"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-surface-container hover:bg-surface-container-high text-on-surface font-medium text-sm rounded-[0.5rem] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                Ver catálogo completo
            </a>
        </div>

    <?php else: ?>

        <?php foreach ($byMonth as $monthKey => $group): ?>
        <!-- Month group -->
        <section class="mb-12 last:mb-0">
            <!-- Month header -->
            <div class="flex items-center gap-3 mb-6">
                <div class="gradient-scholar rounded-lg p-2 shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-display font-bold text-on-surface capitalize"><?= View::e($group['label']) ?></h2>
                    <p class="text-xs text-on-surface-subtle"><?= count($group['books']) ?> <?= count($group['books']) === 1 ? 'título' : 'títulos' ?> nuevos</p>
                </div>
                <div class="flex-1 h-px bg-surface-container ml-2"></div>
            </div>

            <!-- Books grid -->
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 xl:grid-cols-7 gap-3 sm:gap-4">
                <?php foreach ($group['books'] as $book):
                    $authors = $book['authors'] ?? '';
                    if (is_string($authors) && str_starts_with(trim($authors), '[')) {
                        $dec     = json_decode($authors, true);
                        $authors = is_array($dec) ? implode(', ', $dec) : $authors;
                    }
                    $available = (int)($book['available_copies'] ?? 0);
                    $isDigital = ($book['support_type'] ?? '') === 'digital';
                    $acquiredTs = strtotime($book['acquired_at'] ?? 'now');
                    $isThisWeek = (time() - $acquiredTs) < (7 * 86400);
                ?>
                <a href="<?= BASE_URL ?>/catalog/<?= (int)$book['id'] ?>"
                   class="group block bg-surface rounded-[0.75rem] overflow-hidden shadow-ambient hover:shadow-ambient-lg transition-all duration-300 hover:-translate-y-0.5">

                    <!-- Cover -->
                    <div class="relative aspect-[2/3] overflow-hidden">
                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="<?= View::e($book['cover_image']) ?>"
                                 alt="<?= View::e($book['title']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-[1.05] transition-transform duration-500"
                                 loading="lazy" decoding="async">
                        <?php else: ?>
                            <div class="w-full h-full bg-surface-container flex flex-col items-center justify-center gap-2 p-3">
                                <svg class="w-6 h-6 text-on-surface-subtle/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <p class="text-[9px] text-on-surface-subtle text-center leading-snug line-clamp-3"><?= View::e($book['title']) ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Hover overlay -->
                        <div class="absolute inset-0 bg-primary/0 group-hover:bg-primary/50 transition-all duration-300 flex items-center justify-center">
                            <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-white text-primary text-[10px] font-display font-bold uppercase tracking-wide px-2.5 py-1 rounded-full shadow-md">Ver recurso</span>
                        </div>

                        <!-- Badges top-left -->
                        <div class="absolute top-1.5 left-1.5 flex flex-col gap-1">
                            <?php if ($isThisWeek): ?>
                                <span class="bg-error text-white text-[8px] font-display font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-[3px] leading-none w-fit">Esta semana</span>
                            <?php else: ?>
                                <span class="bg-tertiary text-white text-[8px] font-display font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-[3px] leading-none w-fit">Nuevo</span>
                            <?php endif; ?>
                            <?php if ($isDigital): ?>
                                <span class="bg-primary text-white text-[8px] font-display font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded-[3px] leading-none w-fit">Digital</span>
                            <?php endif; ?>
                        </div>

                        <!-- Unavailable strip -->
                        <?php if (!$isDigital && $available === 0): ?>
                            <div class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[9px] text-center py-0.5 font-medium">No disponible</div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
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
        </section>
        <?php endforeach; ?>

        <!-- Bottom link to full catalog -->
        <div class="mt-10 pt-8 border-t border-surface-container text-center">
            <p class="text-on-surface-subtle text-sm mb-4">¿Buscas algo más? Explora toda la colección.</p>
            <a href="<?= BASE_URL ?>/catalog"
               class="inline-flex items-center gap-2 px-6 py-2.5 bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-sm rounded-[0.5rem] transition-colors duration-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                Catálogo completo
            </a>
        </div>

    <?php endif; ?>
</div>

<?php include BASE_PATH . '/views/partials/cta-register.php'; ?>
