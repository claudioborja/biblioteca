<?php
/**
 * Vista: Detalle de recurso
 * Variables: $book, $loanCount, $relatedBooks, $queueSize, $settings
 */
use Core\View;

// Normalizar authors
$authors = $book['authors'] ?? '';
if (is_string($authors) && str_starts_with(trim($authors), '[')) {
    $dec = json_decode($authors, true);
    $authors = is_array($dec) ? implode(', ', $dec) : $authors;
}

$available  = (int) ($book['available_copies'] ?? 0);
$total      = (int) ($book['total_copies'] ?? 0);
$isDigital  = ($book['support_type'] ?? '') === 'digital';
$currency   = $settings['currency_symbol'] ?? '$';
$resourceTypeLabels = [
    'book' => 'Libro',
    'ebook' => 'Libro digital',
    'journal'     => 'Revista / Artículo',
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
    'journal'  => 'Revista / Artículo',
    'thesis'   => 'Tesis',
    'other'    => 'Otro',
];
$resourceType = strtolower(trim((string) ($book['resource_type'] ?? '')));
$supportType = strtolower(trim((string) ($book['support_type'] ?? '')));
$resourceTypeLabel = $resourceTypeLabels[$resourceType] ?? ($resourceType !== '' ? ucfirst($resourceType) : 'Recurso');
$supportTypeLabel = $supportTypeLabels[$supportType] ?? ($supportType !== '' ? ucfirst($supportType) : 'No definido');
$fmtDate = static fn(?string $date): string => ($date !== null && $date !== '' && strtotime($date) !== false) ? date('d/m/Y', strtotime($date)) : '-';
$fmtDateTime = static fn(?string $date): string => ($date !== null && $date !== '' && strtotime($date) !== false) ? date('d/m/Y H:i', strtotime($date)) : '-';
?>

<!-- Breadcrumb -->
<div class="bg-surface-container-lowest border-b border-surface-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-sm text-on-surface-subtle" aria-label="Breadcrumb">
            <a href="<?= BASE_URL ?>/" class="hover:text-primary transition-colors">Inicio</a>
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <a href="<?= BASE_URL ?>/catalog" class="hover:text-primary transition-colors">Catálogo</a>
            <?php if (!empty($book['category_name'])): ?>
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <?php if (!empty($book['category_id'])): ?>
                <a href="<?= BASE_URL ?>/catalog?category=<?= (int) $book['category_id'] ?>" class="hover:text-primary transition-colors">
                    <?= View::e($book['category_name']) ?>
                </a>
            <?php else: ?>
                <span><?= View::e($book['category_name']) ?></span>
            <?php endif; ?>
            <?php endif; ?>
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <span class="text-on-surface font-medium truncate max-w-[180px]"><?= View::e($book['title']) ?></span>
        </nav>
    </div>
</div>

<!-- Main detail -->
<section class="py-12 bg-surface">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-10 lg:gap-16">

            <!-- ── Cover column ─────────────────────────────── -->
            <div class="flex flex-col items-center lg:items-start gap-5">

                <!-- Cover -->
                <div class="relative w-[200px] lg:w-full max-w-[260px]">
                    <div class="aspect-[2/3] rounded-[1rem] overflow-hidden shadow-[0_8px_32px_rgba(0,0,0,0.18)]">
                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="<?= View::e($book['cover_image']) ?>"
                                 alt="Portada de <?= View::e($book['title']) ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full bg-surface-container flex flex-col items-center justify-center gap-3 p-6">
                                <svg class="w-12 h-12 text-on-surface-subtle/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                <p class="text-sm text-on-surface-subtle text-center"><?= View::e($book['title']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Badges -->
                    <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                        <?php if ($book['is_new_acquisition']): ?>
                            <span class="bg-tertiary text-white text-[9px] font-display font-bold uppercase tracking-wider px-2 py-1 rounded-[4px] leading-none shadow-sm">Nuevo</span>
                        <?php endif; ?>
                        <?php if ($isDigital): ?>
                            <span class="bg-primary text-white text-[9px] font-display font-semibold uppercase tracking-wider px-2 py-1 rounded-[4px] leading-none shadow-sm">Digital</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Availability badge -->
                <?php if ($isDigital): ?>
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-primary/8 text-primary rounded-[0.5rem] text-sm font-medium w-full justify-center">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Acceso digital disponible
                    </div>
                <?php elseif ($available > 0): ?>
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-[#14a085]/10 text-[#14a085] rounded-[0.5rem] text-sm font-medium w-full justify-center">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?= $available ?> ejemplar<?= $available !== 1 ? 'es' : '' ?> disponible<?= $available !== 1 ? 's' : '' ?>
                    </div>
                <?php else: ?>
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-error/8 text-error rounded-[0.5rem] text-sm font-medium w-full justify-center">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        No disponible
                        <?php if ($queueSize > 0): ?>
                        <span class="ml-1 text-on-surface-subtle">(<?= $queueSize ?> en espera)</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- CTA Buttons -->
                <div class="flex flex-col gap-2.5 w-full">
                    <?php if ($isDigital && !empty($book['digital_url'])): ?>
                        <a href="<?= View::e($book['digital_url']) ?>" target="_blank" rel="noopener"
                           class="flex items-center justify-center gap-2 w-full px-5 py-3 gradient-scholar text-on-primary font-semibold rounded-[0.5rem] hover:opacity-90 transition-opacity text-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                            Leer en línea
                        </a>
                    <?php elseif ($available > 0): ?>
                        <?php if (!empty($auth_user)): ?>
                            <form method="POST" action="<?= BASE_URL ?>/account/reservations" class="w-full">
                                <input type="hidden" name="_csrf_token" value="<?= View::e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                <input type="hidden" name="resource_id" value="<?= (int) ($book['id'] ?? 0) ?>">
                                <input type="hidden" name="redirect" value="<?= View::e('/catalog/' . (int) ($book['id'] ?? 0)) ?>">
                                <button type="submit"
                                        class="flex items-center justify-center gap-2 w-full px-5 py-3 gradient-scholar text-on-primary font-semibold rounded-[0.5rem] hover:opacity-90 transition-opacity text-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    Reservar
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/login"
                               class="flex items-center justify-center gap-2 w-full px-5 py-3 gradient-scholar text-on-primary font-semibold rounded-[0.5rem] hover:opacity-90 transition-opacity text-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                Reservar
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (!empty($auth_user)): ?>
                            <form method="POST" action="<?= BASE_URL ?>/account/reservations" class="w-full">
                                <input type="hidden" name="_csrf_token" value="<?= View::e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                <input type="hidden" name="resource_id" value="<?= (int) ($book['id'] ?? 0) ?>">
                                <input type="hidden" name="redirect" value="<?= View::e('/catalog/' . (int) ($book['id'] ?? 0)) ?>">
                                <button type="submit"
                                        class="flex items-center justify-center gap-2 w-full px-5 py-3 bg-surface-container text-on-surface font-semibold rounded-[0.5rem] hover:bg-surface-container-highest transition-colors text-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Añadirse a lista de espera
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/login"
                               class="flex items-center justify-center gap-2 w-full px-5 py-3 bg-surface-container text-on-surface font-semibold rounded-[0.5rem] hover:bg-surface-container-highest transition-colors text-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Añadirse a lista de espera
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/catalog"
                       class="flex items-center justify-center gap-2 w-full px-5 py-2.5 border border-surface-container text-on-surface-subtle hover:text-primary hover:border-primary rounded-[0.5rem] transition-colors text-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        Volver al catálogo
                    </a>
                </div>

                <!-- Quick stats -->
                <div class="w-full bg-surface-container-lowest rounded-[0.75rem] p-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-on-surface-subtle">Préstamos totales</span>
                        <span class="font-semibold text-on-surface"><?= $loanCount ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-on-surface-subtle">Ejemplares totales</span>
                        <span class="font-semibold text-on-surface"><?= $total ?></span>
                    </div>
                    <?php if (!empty($book['location'])): ?>
                    <div class="flex items-center justify-between">
                        <span class="text-on-surface-subtle">Ubicación</span>
                        <span class="font-semibold text-on-surface"><?= View::e($book['location']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- ── Info column ─────────────────────────────── -->
            <div>

                <!-- Category + type pill -->
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <?php if (!empty($book['category_name'])): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-primary/8 text-primary text-xs font-semibold font-display uppercase tracking-wide">
                            <?= View::e($book['category_name']) ?>
                        </span>
                    <?php endif; ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-surface-container text-on-surface-subtle text-xs font-display uppercase tracking-wide">
                        <?= $isDigital ? 'Digital' : 'Físico' ?>
                    </span>
                    <?php if (!empty($book['language'])): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-surface-container text-on-surface-subtle text-xs font-display uppercase tracking-wide">
                            <?= strtoupper(View::e($book['language'])) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Title -->
                <h1 class="headline-xl text-on-surface leading-tight mb-2"><?= View::e($book['title']) ?></h1>

                <!-- Authors -->
                <?php if (!empty($authors)): ?>
                    <p class="text-lg text-on-surface-subtle mb-6"><?= View::e($authors) ?></p>
                <?php endif; ?>

                <!-- Description -->
                <?php if (!empty($book['description'])): ?>
                    <div class="mb-8">
                        <h2 class="title-sm font-display font-semibold text-on-surface mb-3">Descripción</h2>
                        <p class="text-on-surface-subtle leading-relaxed"><?= nl2br(View::e($book['description'])) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Metadata grid -->
                <div class="bg-surface-container-lowest rounded-[1rem] p-6">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="title-sm font-display font-semibold text-on-surface">Ficha del recurso</h2>
                        <button type="button"
                                id="btn-toggle-resource-details"
                                class="inline-flex items-center gap-1 rounded-md border border-outline-variant px-2.5 py-1 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors"
                                aria-expanded="false"
                                aria-controls="resource-details-extra">
                            <span id="btn-toggle-resource-details-label">Ver ficha completa</span>
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25 12 15.75 4.5 8.25" />
                            </svg>
                        </button>
                    </div>

                    <h3 class="label-sm mb-2">Datos principales</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                        <div>
                            <dt class="text-on-surface-subtle mb-0.5">Tipo de recurso</dt>
                            <dd class="font-medium text-on-surface"><?= View::e($resourceTypeLabel) ?></dd>
                        </div>
                        <div>
                            <dt class="text-on-surface-subtle mb-0.5">Tipo de soporte</dt>
                            <dd class="font-medium text-on-surface"><?= View::e($supportTypeLabel) ?></dd>
                        </div>
                        <?php if (!empty($book['content_type'])): ?>
                        <div>
                            <dt class="text-on-surface-subtle mb-0.5">RDA contenido</dt>
                            <dd class="font-medium text-on-surface"><?= View::e($book['content_type']) ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($book['media_type'])): ?>
                        <div>
                            <dt class="text-on-surface-subtle mb-0.5">RDA medio</dt>
                            <dd class="font-medium text-on-surface"><?= View::e($book['media_type']) ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($book['carrier_type'])): ?>
                        <div>
                            <dt class="text-on-surface-subtle mb-0.5">RDA soporte</dt>
                            <dd class="font-medium text-on-surface"><?= View::e($book['carrier_type']) ?></dd>
                        </div>
                        <?php endif; ?>
                        <div>
                            <dt class="text-on-surface-subtle mb-0.5">Sede</dt>
                            <dd class="font-medium text-on-surface"><?= View::e((string) ($book['branch_name'] ?? 'No asignada')) ?></dd>
                        </div>
                        <?php if (!empty($book['isbn_13'])): ?>
                        <div>
                            <dt class="text-on-surface-subtle mb-0.5">ISBN-13</dt>
                            <dd class="font-medium text-on-surface font-mono"><?= View::e($book['isbn_13']) ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($book['publisher'])): ?>
                        <div>
                            <dt class="text-on-surface-subtle mb-0.5">Editorial</dt>
                            <dd class="font-medium text-on-surface"><?= View::e($book['publisher']) ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($book['publication_year'])): ?>
                        <div>
                            <dt class="text-on-surface-subtle mb-0.5">Año de publicación</dt>
                            <dd class="font-medium text-on-surface"><?= (int) $book['publication_year'] ?></dd>
                        </div>
                        <?php endif; ?>
                        <div>
                            <dt class="text-on-surface-subtle mb-0.5">Disponibilidad</dt>
                            <dd class="font-medium text-on-surface">
                                <?= $isDigital ? 'Acceso digital' : ($available . ' de ' . $total . ' disponibles') ?>
                            </dd>
                        </div>
                    </dl>

                    <div id="resource-details-extra" class="mt-6 hidden">
                        <h3 class="label-sm mb-2">Bibliográfico ampliado</h3>
                        <dl class="mb-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                            <?php if (!empty($book['isbn_13'])): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">ISBN-13</dt>
                                <dd class="font-medium text-on-surface font-mono"><?= View::e($book['isbn_13']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($book['publisher'])): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Editorial</dt>
                                <dd class="font-medium text-on-surface"><?= View::e($book['publisher']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($book['publication_year'])): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Año de publicación</dt>
                                <dd class="font-medium text-on-surface"><?= (int) $book['publication_year'] ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($book['edition_statement'])): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Edición</dt>
                                <dd class="font-medium text-on-surface"><?= View::e($book['edition_statement']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($book['pages'])): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Páginas</dt>
                                <dd class="font-medium text-on-surface"><?= (int) $book['pages'] ?></dd>
                            </div>
                            <?php endif; ?>
                        </dl>

                        <h3 class="label-sm mb-2">Inventario y adquisición</h3>
                        <dl class="mb-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Ejemplares totales</dt>
                                <dd class="font-medium text-on-surface"><?= (int) ($book['total_copies'] ?? 0) ?></dd>
                            </div>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Ejemplares disponibles</dt>
                                <dd class="font-medium text-on-surface"><?= (int) ($book['available_copies'] ?? 0) ?></dd>
                            </div>
                            <?php if (!empty($book['location'])): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Ubicación</dt>
                                <dd class="font-medium text-on-surface"><?= View::e($book['location']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($book['acquisition_date'])): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Fecha de adquisición</dt>
                                <dd class="font-medium text-on-surface"><?= $fmtDate((string) $book['acquisition_date']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($book['acquired_at'])): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Registrado en</dt>
                                <dd class="font-medium text-on-surface"><?= $fmtDateTime((string) $book['acquired_at']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($book['acquisition_price']) && (float) $book['acquisition_price'] > 0): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Precio de adquisición</dt>
                                <dd class="font-medium text-on-surface"><?= $currency . ' ' . number_format((float)$book['acquisition_price'], 2) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($book['replacement_cost']) && (float)$book['replacement_cost'] > 0): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Valor de reposición</dt>
                                <dd class="font-medium text-on-surface"><?= $currency . ' ' . number_format((float)$book['replacement_cost'], 2) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($book['digital_access_count']) && (int) $book['digital_access_count'] > 0): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Accesos digitales</dt>
                                <dd class="font-medium text-on-surface"><?= (int) $book['digital_access_count'] ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($book['digital_url'])): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">Enlace digital</dt>
                                <dd class="font-medium text-on-surface">
                                    <a href="<?= View::e($book['digital_url']) ?>" target="_blank" rel="noopener" class="text-primary hover:underline break-all"><?= View::e($book['digital_url']) ?></a>
                                </dd>
                            </div>
                            <?php endif; ?>
                        </dl>

                        <h3 class="label-sm mb-2">Control bibliográfico</h3>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                            <?php if (!empty($book['marc_control_number'])): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">MARC 001</dt>
                                <dd class="font-medium text-on-surface font-mono"><?= View::e($book['marc_control_number']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($book['marc_leader'])): ?>
                            <div>
                                <dt class="text-on-surface-subtle mb-0.5">MARC Leader</dt>
                                <dd class="font-medium text-on-surface font-mono"><?= View::e($book['marc_leader']) ?></dd>
                            </div>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<script>
(() => {
    const btn = document.getElementById('btn-toggle-resource-details');
    const label = document.getElementById('btn-toggle-resource-details-label');
    const panel = document.getElementById('resource-details-extra');
    if (!btn || !panel || !label) return;

    btn.addEventListener('click', () => {
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        panel.classList.toggle('hidden', expanded);
        label.textContent = expanded ? 'Ver ficha completa' : 'Ocultar ficha completa';
    });
})();
</script>

<!-- Related books -->
<?php if (!empty($relatedBooks)): ?>
<section class="py-14 bg-surface-container-lowest">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="headline-md text-on-surface mb-8">Recursos relacionados</h2>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-4">
            <?php foreach ($relatedBooks as $rel):
                $relAuth = $rel['authors'] ?? '';
                if (is_string($relAuth) && str_starts_with(trim($relAuth), '[')) {
                    $dec = json_decode($relAuth, true);
                    $relAuth = is_array($dec) ? implode(', ', $dec) : $relAuth;
                }
            ?>
            <a href="<?= BASE_URL ?>/catalog/<?= (int) $rel['id'] ?>"
               class="group block bg-surface rounded-[0.75rem] overflow-hidden shadow-ambient hover:shadow-ambient-lg transition-all duration-300 hover:-translate-y-0.5">
                <div class="relative aspect-[2/3] overflow-hidden">
                    <?php if (!empty($rel['cover_image'])): ?>
                        <img src="<?= View::e($rel['cover_image']) ?>"
                             alt="<?= View::e($rel['title']) ?>"
                             class="w-full h-full object-cover group-hover:scale-[1.05] transition-transform duration-500"
                             loading="lazy">
                    <?php else: ?>
                        <div class="w-full h-full bg-surface-container flex items-center justify-center p-3">
                            <svg class="w-6 h-6 text-on-surface-subtle/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                    <?php endif; ?>
                    <?php if ((int)($rel['available_copies'] ?? 0) === 0 && ($rel['support_type'] ?? '') !== 'digital'): ?>
                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                            <span class="bg-black/60 text-white text-[9px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded">No disponible</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-2">
                    <h3 class="text-[11px] font-semibold text-on-surface group-hover:text-primary line-clamp-2 leading-snug transition-colors"><?= View::e($rel['title']) ?></h3>
                    <?php if (!empty($relAuth)): ?>
                        <p class="text-[10px] text-on-surface-subtle mt-0.5 line-clamp-1"><?= View::e($relAuth) ?></p>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
