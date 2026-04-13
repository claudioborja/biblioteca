<?php
use Helpers\Icons;
/**
 * Vista: Detalle de noticia
 * Variables: $article (array), $related (array), $settings (array)
 */
use Core\View;

$months = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$formatDate = static function(string $date) use ($months): string {
    $ts = strtotime($date);
    return (int)date('d', $ts) . ' de ' . $months[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts);
};

$publishedTs  = strtotime($article['published_at'] ?? 'now');
$readingWords = str_word_count(strip_tags($article['content'] ?? ''));
$readingMins  = max(1, (int)ceil($readingWords / 200));
?>

<!-- ── Breadcrumb ── -->
<div class="border-b border-surface-container bg-surface-container-lowest">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-1.5 text-xs text-on-surface-subtle py-3 flex-wrap" aria-label="Breadcrumb">
            <a href="<?= BASE_URL ?>/" class="hover:text-primary transition-colors">Inicio</a>
            <svg class="w-3 h-3 shrink-0 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <a href="<?= BASE_URL ?>/news" class="hover:text-primary transition-colors">Noticias</a>
            <svg class="w-3 h-3 shrink-0 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <span class="text-on-surface font-medium truncate max-w-[200px]"><?= View::e($article['title']) ?></span>
        </nav>
    </div>
</div>

<!-- ── Article ── -->
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="lg:flex lg:gap-12">

        <!-- Main column -->
        <article class="flex-1 min-w-0">

            <!-- Meta -->
            <div class="flex flex-wrap items-center gap-3 mb-5 text-xs text-on-surface-subtle">
                <span class="inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    <?= $formatDate($article['published_at']) ?>
                </span>
                <?php if (!empty($article['author_name'])): ?>
                <span class="opacity-30">·</span>
                <span class="inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    <?= View::e($article['author_name']) ?>
                </span>
                <?php endif; ?>
                <span class="opacity-30">·</span>
                <span class="inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?= $readingMins ?> min de lectura
                </span>
            </div>

            <!-- Title -->
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-display font-bold text-on-surface leading-tight mb-5">
                <?= View::e($article['title']) ?>
            </h1>

            <!-- Excerpt (lead) -->
            <?php if (!empty($article['excerpt'])): ?>
            <p class="text-base sm:text-lg text-on-surface-subtle leading-relaxed border-l-4 border-primary pl-4 mb-6 italic">
                <?= View::e($article['excerpt']) ?>
            </p>
            <?php endif; ?>

            <!-- Cover image -->
            <?php if (!empty($article['cover_image'])): ?>
            <div class="rounded-[0.875rem] overflow-hidden mb-8 aspect-video">
                <img src="<?= View::e($article['cover_image']) ?>"
                     alt="<?= View::e($article['title']) ?>"
                     class="w-full h-full object-cover">
            </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="prose-news">
                <?= $article['content'] /* Content from DB — admin-controlled, trusted HTML */ ?>
            </div>

            <!-- Share strip -->
            <div class="mt-10 pt-6 border-t border-surface-container">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <p class="text-sm text-on-surface-subtle">¿Te resultó interesante? Compártelo.</p>
                    <div class="flex items-center gap-2">
                        <a href="https://twitter.com/intent/tweet?text=<?= urlencode($article['title']) ?>&url=<?= urlencode(BASE_URL . '/news/' . $article['slug']) ?>"
                           target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-[#1DA1F2] text-white rounded-[0.375rem] hover:opacity-90 transition-opacity">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            Twitter / X
                        </a>
                        <button onclick="navigator.clipboard.writeText(window.location.href).then(()=>{this.textContent='¡Copiado!';setTimeout(()=>this.textContent='Copiar enlace',2000)})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-surface-container hover:bg-surface-container-high text-on-surface rounded-[0.375rem] transition-colors">
                            <?= Icons::copy('w-3.5 h-3.5') ?> Copiar enlace
                        </button>
                    </div>
                </div>
            </div>

            <!-- Back link -->
            <a href="<?= BASE_URL ?>/news"
               class="mt-6 inline-flex items-center gap-2 text-sm text-on-surface-subtle hover:text-primary transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Volver a noticias
            </a>
        </article>

        <!-- Sidebar: related -->
        <?php if (!empty($related)): ?>
        <aside class="lg:w-72 shrink-0 mt-12 lg:mt-0">
            <div class="sticky top-24">
                <h2 class="text-sm font-display font-bold text-on-surface uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="w-3 h-[3px] rounded-full bg-tertiary inline-block"></span>
                    Más noticias
                </h2>
                <div class="flex flex-col gap-4">
                    <?php foreach ($related as $rel): ?>
                    <a href="<?= BASE_URL ?>/news/<?= View::e($rel['slug']) ?>"
                       class="group flex gap-3 bg-surface rounded-[0.75rem] p-3 hover:shadow-ambient-lg transition-all duration-200 hover:-translate-y-0.5 shadow-ambient">

                        <?php if (!empty($rel['cover_image'])): ?>
                        <div class="w-16 h-16 rounded-[0.5rem] overflow-hidden shrink-0">
                            <img src="<?= View::e($rel['cover_image']) ?>"
                                 alt="<?= View::e($rel['title']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-[1.05] transition-transform duration-300"
                                 loading="lazy" decoding="async">
                        </div>
                        <?php else: ?>
                        <div class="w-16 h-16 rounded-[0.5rem] gradient-scholar flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5"/>
                            </svg>
                        </div>
                        <?php endif; ?>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-[12px] font-semibold text-on-surface group-hover:text-primary transition-colors line-clamp-2 leading-snug">
                                <?= View::e($rel['title']) ?>
                            </h3>
                            <p class="mt-1 text-[10px] text-on-surface-subtle">
                                <?= $formatDate($rel['published_at']) ?>
                            </p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
        <?php endif; ?>

    </div>
</div>

<!-- ── Prose styles for article content ── -->
<style>
.prose-news { color: var(--color-on-surface, #1a1a1a); line-height: 1.8; font-size: 1rem; }
.prose-news p { margin-bottom: 1.25rem; }
.prose-news h2 { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; margin: 2rem 0 0.75rem; color: var(--color-on-surface); }
.prose-news h3 { font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; margin: 1.5rem 0 0.5rem; color: var(--color-on-surface); }
.prose-news strong { font-weight: 700; color: var(--color-on-surface); }
.prose-news em { font-style: italic; }
.prose-news ul, .prose-news ol { margin: 0 0 1.25rem 1.5rem; }
.prose-news ul { list-style-type: disc; }
.prose-news ol { list-style-type: decimal; }
.prose-news li { margin-bottom: 0.35rem; }
.prose-news a { color: var(--color-primary); text-decoration: underline; }
.prose-news a:hover { opacity: 0.8; }
.prose-news blockquote { border-left: 4px solid var(--color-primary); padding-left: 1rem; margin: 1.5rem 0; font-style: italic; color: var(--color-on-surface-muted, #555); }
</style>

<?php include BASE_PATH . '/views/partials/cta-register.php'; ?>
