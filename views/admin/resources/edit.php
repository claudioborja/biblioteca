<?php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$isModal = isset($_GET['modal']) && $_GET['modal'] === '1';
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="<?= $isModal ? 'p-4' : 'p-6 lg:p-8' ?>">
    <?php if (!$isModal): ?>
        <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="label-sm">Administración</p>
                <h1 class="headline-lg text-on-surface">Editar recurso</h1>
                <p class="body-md mt-1">Actualiza la ficha del recurso con captura principal RDA y MARC21 solo para interoperabilidad.</p>
            </div>
            <a href="<?= BASE_URL ?>/admin/resources" class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors inline-flex items-center gap-2">
                <i class="bi bi-arrow-left text-sm"></i> Volver al catálogo
            </a>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/admin/resources/<?= (int) ($book_id ?? 0) ?>" id="resource-edit-form" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="_csrf_token" value="<?= $e(\Core\Session::get('_csrf_token', '')) ?>">
        <?php if ($isModal): ?>
            <input type="hidden" name="modal" value="1">
        <?php endif; ?>

        <nav class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white <?= $isModal ? 'shadow-none' : 'shadow-ambient' ?>" aria-label="Secciones de edición">
            <?php if (!$isModal): ?>
                <div class="border-b border-outline-variant/50 bg-surface-container-low px-4 py-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="min-w-0">
                            <p class="label-sm">Editor de Recursos</p>
                            <h2 class="title-sm text-on-surface">Barra de edición catalográfica</h2>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="border-b border-outline-variant/50 bg-surface-container-lowest px-3 py-2.5">
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" data-tab-target="rda" class="resource-tab inline-flex items-center gap-2 rounded-lg border border-primary/40 bg-white px-3 py-2 text-sm font-semibold text-primary shadow-sm transition-colors">
                        <i class="bi bi-book text-sm"></i> RDA
                    </button>
                    <button type="button" data-tab-target="marc" class="resource-tab inline-flex items-center gap-2 rounded-lg border border-transparent bg-transparent px-3 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low hover:text-on-surface transition-colors">
                        <i class="bi bi-list-ul text-sm"></i> MARC21
                    </button>
                    <button type="button" data-tab-target="clasificacion" class="resource-tab inline-flex items-center gap-2 rounded-lg border border-transparent bg-transparent px-3 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low hover:text-on-surface transition-colors">
                        <i class="bi bi-funnel text-sm"></i> Clasificación
                    </button>
                    <button type="button" data-tab-target="inventario" class="resource-tab inline-flex items-center gap-2 rounded-lg border border-transparent bg-transparent px-3 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low hover:text-on-surface transition-colors">
                        <i class="bi bi-stack text-sm"></i> Inventario
                    </button>
                </div>
            </div>
        </nav>

        <div class="<?= $isModal ? 'rounded-2xl border border-outline-variant/60 bg-white p-4' : 'rounded-3xl border border-outline-variant/60 bg-white p-5 shadow-ambient-lg lg:p-6' ?>">
            <section id="section-rda" data-tab-panel="rda" class="space-y-5">
                <div>
                    <h2 class="headline-md text-on-surface">Datos RDA</h2>
                    <p class="body-md mt-1">Revisa y corrige los datos descriptivos principales del recurso.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label for="isbn" class="label-sm">ISBN</label>
                        <input id="isbn" name="isbn" type="text" value="<?= $e($old['isbn'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="publication_year" class="label-sm">Año de publicación</label>
                        <input id="publication_year" name="publication_year" type="number" min="1000" max="<?= date('Y') + 1 ?>" value="<?= $e($old['publication_year'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="language" class="label-sm">Idioma</label>
                        <input id="language" name="language" type="text" maxlength="2" value="<?= $e($old['language'] ?? 'es') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm uppercase focus:border-primary focus:outline-none">
                    </div>
                    <div class="xl:col-span-2">
                        <label for="title" class="label-sm">Título</label>
                        <input id="title" name="title" type="text" value="<?= $e($old['title'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div class="xl:col-span-2">
                        <label for="authors" class="label-sm">Autoría / responsabilidad principal</label>
                        <textarea id="authors" name="authors" rows="2" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"><?= $e($old['authors'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label for="publisher" class="label-sm">Entidad publicadora</label>
                        <input id="publisher" name="publisher" type="text" value="<?= $e($old['publisher'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="edition_statement" class="label-sm">Mención de edición</label>
                        <input id="edition_statement" name="edition_statement" type="text" value="<?= $e($old['edition_statement'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div class="xl:col-span-3">
                        <label for="description" class="label-sm">Descripción</label>
                        <textarea id="description" name="description" rows="3" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"><?= $e($old['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </section>

            <section id="section-marc" data-tab-panel="marc" class="hidden space-y-5">
                <div>
                    <h2 class="headline-md text-on-surface">MARC21 e interoperabilidad</h2>
                    <p class="body-md mt-1">Usa este bloque para importación, exportación y compatibilidad con otros sistemas.</p>
                </div>

                <div class="rounded-2xl border border-outline-variant/50 bg-surface-container-low p-4">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <label for="marc_leader" class="label-sm">LDR (Leader)</label>
                                <input id="marc_leader" name="marc_leader" type="text" maxlength="24" value="<?= $e($old['marc_leader'] ?? '00000nam a2200000 i 4500') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm font-mono focus:border-primary focus:outline-none">
                            </div>
                            <div>
                                <label for="marc_001" class="label-sm">001 (Número de control)</label>
                                <input id="marc_001" name="marc_001" type="text" value="<?= $e($old['marc_001'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            </div>
                            <div>
                                <label for="marc_020" class="label-sm">020$a (ISBN)</label>
                                <input id="marc_020" name="marc_020" type="text" value="<?= $e($old['marc_020'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            </div>
                            <div>
                                <label for="marc_041" class="label-sm">041$a (Código idioma)</label>
                                <input id="marc_041" name="marc_041" type="text" maxlength="3" value="<?= $e($old['marc_041'] ?? 'spa') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm uppercase focus:border-primary focus:outline-none">
                            </div>
                            <div class="xl:col-span-2">
                                <label for="marc_100" class="label-sm">100$a (Autor principal)</label>
                                <input id="marc_100" name="marc_100" type="text" value="<?= $e($old['marc_100'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            </div>
                            <div>
                                <label for="marc_245a" class="label-sm">245$a (Título)</label>
                                <input id="marc_245a" name="marc_245a" type="text" value="<?= $e($old['marc_245a'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            </div>
                            <div>
                                <label for="marc_245b" class="label-sm">245$b (Subtítulo)</label>
                                <input id="marc_245b" name="marc_245b" type="text" value="<?= $e($old['marc_245b'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            </div>
                            <div>
                                <label for="marc_250a" class="label-sm">250$a (Edición)</label>
                                <input id="marc_250a" name="marc_250a" type="text" value="<?= $e($old['marc_250a'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            </div>
                            <div>
                                <label for="marc_260b" class="label-sm">260$b (Editorial)</label>
                                <input id="marc_260b" name="marc_260b" type="text" value="<?= $e($old['marc_260b'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            </div>
                            <div>
                                <label for="marc_260c" class="label-sm">260$c (Fecha publicación)</label>
                                <input id="marc_260c" name="marc_260c" type="text" value="<?= $e($old['marc_260c'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            </div>
                            <div>
                                <label for="marc_300a" class="label-sm">300$a (Descripción física)</label>
                                <input id="marc_300a" name="marc_300a" type="text" value="<?= $e($old['marc_300a'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            </div>
                            <div class="xl:col-span-3">
                                <label for="marc_520a" class="label-sm">520$a (Resumen)</label>
                                <textarea id="marc_520a" name="marc_520a" rows="2" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"><?= $e($old['marc_520a'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label for="marc_650a" class="label-sm">650$a (Temas)</label>
                                <input id="marc_650a" name="marc_650a" type="text" value="<?= $e($old['marc_650a'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            </div>
                            <div>
                                <label for="marc_700a" class="label-sm">700$a (Autores secundarios)</label>
                                <input id="marc_700a" name="marc_700a" type="text" value="<?= $e($old['marc_700a'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            </div>
                            <div class="xl:col-span-2">
                                <label for="marc_856u" class="label-sm">856$u (URL recurso)</label>
                                <input id="marc_856u" name="marc_856u" type="url" value="<?= $e($old['marc_856u'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            </div>
                    </div>
                </div>
            </section>

            <section id="section-clasificacion" data-tab-panel="clasificacion" class="hidden space-y-5">
                <div>
                    <h2 class="headline-md text-on-surface">Clasificación y acceso</h2>
                    <p class="body-md mt-1">Define la naturaleza del recurso y su modo de acceso.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label for="category_id" class="label-sm">Categoría</label>
                        <select id="category_id" name="category_id" required class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= (string) ($old['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>>
                                    <?= $e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="branch_id" class="label-sm">Sede</label>
                        <select id="branch_id" name="branch_id" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            <option value="">Sin sede específica</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= (int) $branch['id'] ?>" <?= (string) ($old['branch_id'] ?? '') === (string) $branch['id'] ? 'selected' : '' ?>>
                                    <?= $e($branch['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="resource_type" class="label-sm">Tipo de recurso</label>
                        <select id="resource_type" name="resource_type" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            <option value="book" <?= ($old['resource_type'] ?? 'book') === 'book' ? 'selected' : '' ?>>Libro</option>
                            <option value="ebook" <?= ($old['resource_type'] ?? '') === 'ebook' ? 'selected' : '' ?>>Libro electrónico</option>
                            <option value="journal" <?= ($old['resource_type'] ?? '') === 'journal' ? 'selected' : '' ?>>Revista / publicación seriada</option>
                            <option value="article" <?= ($old['resource_type'] ?? '') === 'article' ? 'selected' : '' ?>>Artículo</option>
                            <option value="thesis" <?= ($old['resource_type'] ?? '') === 'thesis' ? 'selected' : '' ?>>Tesis</option>
                            <option value="map" <?= ($old['resource_type'] ?? '') === 'map' ? 'selected' : '' ?>>Mapa</option>
                            <option value="score" <?= ($old['resource_type'] ?? '') === 'score' ? 'selected' : '' ?>>Partitura</option>
                            <option value="audiovisual" <?= ($old['resource_type'] ?? '') === 'audiovisual' ? 'selected' : '' ?>>Audiovisual</option>
                            <option value="game" <?= ($old['resource_type'] ?? '') === 'game' ? 'selected' : '' ?>>Juego</option>
                            <option value="kit" <?= ($old['resource_type'] ?? '') === 'kit' ? 'selected' : '' ?>>Kit</option>
                            <option value="other" <?= ($old['resource_type'] ?? '') === 'other' ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                    <div>
                        <label for="book_type" class="label-sm">Tipo de soporte</label>
                        <select id="support_type" name="support_type" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            <option value="physical" <?= ($old['support_type'] ?? 'physical') === 'physical' ? 'selected' : '' ?>>Físico</option>
                            <option value="digital" <?= ($old['support_type'] ?? '') === 'digital' ? 'selected' : '' ?>>Digital</option>
                            <option value="audiovisual" <?= ($old['support_type'] ?? '') === 'audiovisual' ? 'selected' : '' ?>>Audiovisual</option>
                            <option value="journal" <?= ($old['support_type'] ?? '') === 'journal' ? 'selected' : '' ?>>Seriado</option>
                            <option value="thesis" <?= ($old['support_type'] ?? '') === 'thesis' ? 'selected' : '' ?>>Tesis</option>
                            <option value="map" <?= ($old['support_type'] ?? '') === 'map' ? 'selected' : '' ?>>Cartográfico</option>
                            <option value="score" <?= ($old['support_type'] ?? '') === 'score' ? 'selected' : '' ?>>Partitura</option>
                            <option value="kit" <?= ($old['support_type'] ?? '') === 'kit' ? 'selected' : '' ?>>Kit</option>
                            <option value="game" <?= ($old['support_type'] ?? '') === 'game' ? 'selected' : '' ?>>Juego</option>
                            <option value="other" <?= ($old['support_type'] ?? '') === 'other' ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                    <div>
                        <label for="content_type" class="label-sm">RDA tipo de contenido</label>
                        <input id="content_type" name="content_type" type="text" value="<?= $e($old['content_type'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="media_type" class="label-sm">RDA tipo de medio</label>
                        <input id="media_type" name="media_type" type="text" value="<?= $e($old['media_type'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div class="xl:col-span-3">
                        <label for="carrier_type" class="label-sm">RDA tipo de soporte</label>
                        <input id="carrier_type" name="carrier_type" type="text" value="<?= $e($old['carrier_type'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="digital_url" class="label-sm">URL digital</label>
                        <input id="digital_url" name="digital_url" type="url" value="<?= $e($old['digital_url'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="is_new_acquisition" class="label-sm">Novedad</label>
                        <select id="is_new_acquisition" name="is_new_acquisition" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            <option value="1" <?= ($old['is_new_acquisition'] ?? '0') === '1' ? 'selected' : '' ?>>Sí</option>
                            <option value="0" <?= ($old['is_new_acquisition'] ?? '0') === '0' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>
            </section>

            <section id="section-inventario" data-tab-panel="inventario" class="hidden space-y-5">
                <div>
                    <h2 class="headline-md text-on-surface">Inventario y estado</h2>
                    <p class="body-md mt-1">Actualiza copias, costos, ubicación y visibilidad del recurso.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label for="total_copies" class="label-sm">Copias totales</label>
                        <input id="total_copies" name="total_copies" type="number" min="1" value="<?= $e($old['total_copies'] ?? '1') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="available_copies" class="label-sm">Copias disponibles</label>
                        <input id="available_copies" name="available_copies" type="number" min="0" value="<?= $e($old['available_copies'] ?? '0') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="replacement_cost" class="label-sm">Costo de reposición</label>
                        <input id="replacement_cost" name="replacement_cost" type="number" min="0" step="0.01" value="<?= $e($old['replacement_cost'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="acquisition_price" class="label-sm">Costo de adquisición</label>
                        <input id="acquisition_price" name="acquisition_price" type="number" min="0" step="0.01" value="<?= $e($old['acquisition_price'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="acquisition_date" class="label-sm">Fecha de adquisición</label>
                        <input id="acquisition_date" name="acquisition_date" type="date" value="<?= $e($old['acquisition_date'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="cover_image" class="label-sm">Portada</label>
                        <input type="hidden" name="existing_cover_image" value="<?= $e($old['cover_image'] ?? '') ?>">
                        <?php if (!empty($old['cover_image'])): ?>
                        <div class="mb-2 flex items-center gap-2 rounded-xl border border-outline-variant bg-surface-container-lowest p-2">
                            <img src="<?= $e(BASE_URL . $old['cover_image']) ?>" alt="Portada actual" class="h-14 w-10 rounded object-cover">
                            <span class="text-xs text-on-surface-muted truncate"><?= $e($old['cover_image']) ?></span>
                        </div>
                        <?php endif; ?>
                        <input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif"
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/20 focus:border-primary focus:outline-none">
                        <p class="mt-1 text-xs text-on-surface-muted">Dejar vacío para conservar la imagen actual. Máx. 5 MB · JPG, PNG, WEBP, GIF</p>
                        <div id="cover-preview-edit" class="mt-2 hidden">
                            <img id="cover-preview-img-edit" src="" alt="Vista previa" class="h-28 w-20 rounded-xl border border-outline-variant object-cover">
                        </div>
                    </div>
                    <div>
                        <label for="location" class="label-sm">Ubicación</label>
                        <input id="location" name="location" type="text" value="<?= $e($old['location'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="is_active" class="label-sm">Estado</label>
                        <select id="is_active" name="is_active" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                            <option value="1" <?= ($old['is_active'] ?? '1') === '1' ? 'selected' : '' ?>>Activo</option>
                            <option value="0" <?= ($old['is_active'] ?? '1') === '0' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
            </section>

        </div>

        <?php if (!$isModal): ?>
            <div class="rounded-2xl border border-outline-variant/60 bg-white px-4 py-3 shadow-ambient">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-3 text-sm text-on-surface-muted">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/85 px-3 py-1.5">
                            <i class="bi bi-check2 text-sm"></i> Un solo guardado para todas las pestañas
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/85 px-3 py-1.5">
                            <i class="bi bi-arrow-repeat text-sm"></i> Cambios RDA y MARC se envían juntos
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" id="edit-submit" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                            <i class="bi bi-floppy text-sm"></i> Guardar todo
                        </button>
                        <a href="<?= BASE_URL ?>/admin/resources" class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                            <i class="bi bi-x-lg text-sm"></i> Cancelar
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </form>
</section>

<script>
(() => {
    // Cover image preview
    const coverInput = document.getElementById('cover_image');
    const previewWrap = document.getElementById('cover-preview-edit');
    const previewImg  = document.getElementById('cover-preview-img-edit');
    if (coverInput && previewWrap && previewImg) {
        coverInput.addEventListener('change', () => {
            const file = coverInput.files?.[0];
            if (file) {
                previewImg.src = URL.createObjectURL(file);
                previewWrap.classList.remove('hidden');
            } else {
                previewWrap.classList.add('hidden');
            }
        });
    }
})();

(() => {
    const form = document.getElementById('resource-edit-form');
    if (!form) return;
    const categorySelect = document.getElementById('category_id');
    const branchSelect = document.getElementById('branch_id');
    const bookTypeSelect = document.getElementById('book_type');
    const tabButtons = Array.from(form.querySelectorAll('[data-tab-target]'));
    const tabPanels = Array.from(form.querySelectorAll('[data-tab-panel]'));

    const getFieldValue = (name) => {
        const field = form.elements.namedItem(name);
        if (!field) return '';
        return field.value?.trim?.() ?? field.value ?? '';
    };

    const labelForSelect = (select) => {
        if (!select) return '—';
        const option = select.options[select.selectedIndex];
        return option ? option.textContent.trim() : '—';
    };

    const updateReview = () => {
        const marcIsbn = getFieldValue('marc_020');
        const marcTitle = getFieldValue('marc_245a');
        const marcSubtitle = getFieldValue('marc_245b');
        const marcMainAuthor = getFieldValue('marc_100');
        const marcAddedAuthors = getFieldValue('marc_700a');
        const computedTitle = marcTitle ? `${marcTitle}${marcSubtitle ? ' : ' + marcSubtitle : ''}` : '';
        const computedAuthors = [marcMainAuthor, marcAddedAuthors].filter(Boolean).join(', ');

        const reviewMap = {
            isbn: marcIsbn || getFieldValue('isbn') || '—',
            title: computedTitle || getFieldValue('title') || '—',
            authors: computedAuthors || getFieldValue('authors') || '—',
            publisher: getFieldValue('marc_260b') || getFieldValue('publisher') || '—',
            edition_statement: getFieldValue('marc_250a') || getFieldValue('edition_statement') || '—',
            category_id: labelForSelect(categorySelect),
            branch_id: labelForSelect(branchSelect),
            resource_type: getFieldValue('resource_type') || '—',
            book_type: getFieldValue('book_type') || '—',
            content_type: getFieldValue('content_type') || '—',
            media_type: getFieldValue('media_type') || '—',
            carrier_type: getFieldValue('carrier_type') || '—',
            inventory: `${getFieldValue('available_copies') || 0}/${getFieldValue('total_copies') || 0} disponibles`,
        };

        Object.entries(reviewMap).forEach(([key, value]) => {
            const target = form.querySelector(`[data-review="${key}"]`);
            if (target) target.textContent = value;
        });
    };

    const syncUi = () => {
        const isDigital = bookTypeSelect?.value === 'digital';
        const digitalUrl = document.getElementById('digital_url');
        const location = document.getElementById('location');
        if (digitalUrl) digitalUrl.required = isDigital;
        if (location) location.disabled = isDigital;
        updateReview();
    };

    const activateTab = (target) => {
        tabButtons.forEach((button) => {
            const active = button.dataset.tabTarget === target;
            button.classList.toggle('border-primary/40', active);
            button.classList.toggle('bg-white', active);
            button.classList.toggle('text-primary', active);
            button.classList.toggle('shadow-sm', active);
            button.classList.toggle('border-transparent', !active);
            button.classList.toggle('bg-transparent', !active);
            button.classList.toggle('text-on-surface-muted', !active);
            button.classList.toggle('text-on-surface', false);
        });

        tabPanels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.tabPanel !== target);
        });
    };

    bookTypeSelect?.addEventListener('change', syncUi);
    form.addEventListener('input', updateReview);
    form.addEventListener('change', updateReview);
    tabButtons.forEach((button) => {
        button.addEventListener('click', () => activateTab(button.dataset.tabTarget || 'rda'));
    });
    window.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'submit-resource-edit-form') {
            form.requestSubmit();
        }
    });
    activateTab('rda');
    syncUi();
})();
</script>
