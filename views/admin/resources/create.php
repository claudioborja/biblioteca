<?php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="p-6 lg:p-8">
    <form method="POST" action="<?= BASE_URL ?>/admin/resources" id="resource-wizard" enctype="multipart/form-data" class="mx-auto max-w-[1500px]">
        <input type="hidden" name="_csrf_token" value="<?= $e(\Core\Session::get('_csrf_token', '')) ?>">

        <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">
            <div class="flex h-12 items-center justify-between border-b border-outline-variant/60 bg-gradient-to-b from-slate-100 to-slate-200 pl-3">
                <div class="flex min-w-0 items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-primary/15 text-primary">
                        <i class="bi bi-journal-plus text-[12px]"></i>
                    </span>
                    <p class="truncate text-sm font-semibold text-slate-700">Asistente de nuevo recurso</p>
                </div>
                <a href="<?= BASE_URL ?>/admin/resources" class="inline-flex h-12 w-12 items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors" aria-label="Cerrar ventana">
                    <i class="bi bi-x-lg text-sm"></i>
                </a>
            </div>

            <div class="border-b border-outline-variant/50 bg-surface-container-lowest px-4 py-3">
                <div class="grid gap-3 lg:grid-cols-4">
                    <div class="wizard-step-indicator rounded-xl border border-outline-variant/60 bg-white p-3" data-step-indicator="1">
                        <p class="label-sm">Paso 1</p>
                        <p class="mt-1 font-semibold text-on-surface">Tipo de recurso</p>
                        <p class="mt-1 text-xs text-on-surface-subtle">Reglas y obligatorios</p>
                    </div>
                    <div class="wizard-step-indicator rounded-xl border border-outline-variant/60 bg-white p-3" data-step-indicator="2">
                        <p class="label-sm">Paso 2</p>
                        <p class="mt-1 font-semibold text-on-surface">Datos RDA</p>
                        <p class="mt-1 text-xs text-on-surface-subtle">Descripción principal</p>
                    </div>
                    <div class="wizard-step-indicator rounded-xl border border-outline-variant/60 bg-white p-3" data-step-indicator="3">
                        <p class="label-sm">Paso 3</p>
                        <p class="mt-1 font-semibold text-on-surface">Clasificación e inventario</p>
                        <p class="mt-1 text-xs text-on-surface-subtle">Acceso y costos</p>
                    </div>
                    <div class="wizard-step-indicator rounded-xl border border-outline-variant/60 bg-white p-3" data-step-indicator="4">
                        <p class="label-sm">Paso 4</p>
                        <p class="mt-1 font-semibold text-on-surface">MARC21 y revisión</p>
                        <p class="mt-1 text-xs text-on-surface-subtle">Interoperabilidad</p>
                    </div>
                </div>
            </div>

            <div class="p-5 lg:p-6">
                <div class="wizard-step space-y-5" data-step="1">
                    <div>
                        <h2 class="headline-md text-on-surface">Tipo de recurso y reglas</h2>
                        <p class="body-md mt-1">Selecciona el tipo en este primer paso. El asistente ajusta los campos obligatorios según el tipo.</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="resource_type" class="label-sm">Tipo de recurso</label>
                            <select id="resource_type" name="resource_type" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                                <option value="book" <?= ($old['resource_type'] ?? 'book') === 'book' ? 'selected' : '' ?>>Libro físico</option>
                                <option value="ebook" <?= ($old['resource_type'] ?? '') === 'ebook' ? 'selected' : '' ?>>Libro digital</option>
                                <option value="journal" <?= ($old['resource_type'] ?? '') === 'journal' ? 'selected' : '' ?>>Revista</option>
                                <option value="thesis" <?= ($old['resource_type'] ?? '') === 'thesis' ? 'selected' : '' ?>>Tesis</option>
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
                    </div>
                    <div class="rounded-2xl border border-primary/20 bg-primary/5 p-4">
                        <p class="label-sm text-primary">Campos obligatorios para el tipo seleccionado</p>
                        <p id="resource-required-hint" class="mt-2 text-sm text-on-surface"></p>
                    </div>
                </div>

                <div class="wizard-step hidden space-y-5" data-step="2">
                    <div>
                        <h2 class="headline-md text-on-surface">Datos bibliográficos RDA</h2>
                        <p class="body-md mt-1">Captura descriptiva principal del recurso.</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div>
                            <label for="isbn" class="label-sm">ISBN</label>
                            <input id="isbn" name="isbn" type="text" value="<?= $e($old['isbn'] ?? '') ?>" placeholder="9780306406157 o 0306406152" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
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
                </div>

                <div class="wizard-step hidden space-y-5" data-step="3">
                    <div>
                        <h2 class="headline-md text-on-surface">Clasificación e inventario</h2>
                        <p class="body-md mt-1">Define acceso, costos y existencias del recurso.</p>
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
                            <label for="digital_url" class="label-sm">URL digital</label>
                            <input id="digital_url" name="digital_url" type="url" value="<?= $e($old['digital_url'] ?? '') ?>" placeholder="https://..." class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                        </div>
                        <div>
                            <label for="content_type" class="label-sm">RDA tipo de contenido</label>
                            <input id="content_type" name="content_type" type="text" value="<?= $e($old['content_type'] ?? 'texto') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                        </div>
                        <div>
                            <label for="media_type" class="label-sm">RDA tipo de medio</label>
                            <input id="media_type" name="media_type" type="text" value="<?= $e($old['media_type'] ?? 'sin mediacion') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                        </div>
                        <div>
                            <label for="carrier_type" class="label-sm">RDA tipo de soporte</label>
                            <input id="carrier_type" name="carrier_type" type="text" value="<?= $e($old['carrier_type'] ?? 'volumen') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                        </div>
                        <div>
                            <label for="is_new_acquisition" class="label-sm">Marcar como novedad</label>
                            <select id="is_new_acquisition" name="is_new_acquisition" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                                <option value="1" <?= ($old['is_new_acquisition'] ?? '1') === '1' ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= ($old['is_new_acquisition'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div>
                            <label for="total_copies" class="label-sm">Copias totales</label>
                            <input id="total_copies" name="total_copies" type="number" min="1" value="<?= $e($old['total_copies'] ?? '1') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                        </div>
                        <div>
                            <label for="location" class="label-sm">Ubicación</label>
                            <input id="location" name="location" type="text" value="<?= $e($old['location'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
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
                            <input id="acquisition_date" name="acquisition_date" type="date" value="<?= $e($old['acquisition_date'] ?? date('Y-m-d')) ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                        </div>
                        <div>
                            <label for="cover_image" class="label-sm">Imagen de portada</label>
                            <?php if (!empty($old['cover_image'])): ?>
                            <div class="mb-2 flex items-center gap-2 rounded-xl border border-outline-variant bg-surface-container-lowest p-2">
                                <img src="<?= $e(BASE_URL . $old['cover_image']) ?>" alt="Portada actual" class="h-14 w-10 rounded object-cover">
                                <span class="text-xs text-on-surface-muted truncate"><?= $e($old['cover_image']) ?></span>
                            </div>
                            <?php endif; ?>
                            <input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif"
                                   class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/20 focus:border-primary focus:outline-none">
                            <p class="mt-1 text-xs text-on-surface-muted">Máx. 5 MB · JPG, PNG, WEBP, GIF</p>
                            <p id="cover-error-create" class="mt-1 hidden text-xs font-semibold text-red-600"></p>
                            <div id="cover-preview-create" class="mt-2 hidden">
                                <img id="cover-preview-img-create" src="" alt="Vista previa" class="h-28 w-20 rounded-xl border border-outline-variant object-cover">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wizard-step hidden space-y-5" data-step="4">
                    <div>
                        <h2 class="headline-md text-on-surface">MARC21 e interoperabilidad</h2>
                        <p class="body-md mt-1">Uso opcional para importación, exportación y compatibilidad entre sistemas.</p>
                    </div>
                    <div class="rounded-2xl border border-outline-variant/50 bg-surface-container-low p-4">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <div><label for="marc_leader" class="label-sm">LDR (Leader)</label><input id="marc_leader" name="marc_leader" type="text" maxlength="24" value="<?= $e($old['marc_leader'] ?? '00000nam a2200000 i 4500') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm font-mono focus:border-primary focus:outline-none"></div>
                            <div><label for="marc_001" class="label-sm">001 (Número de control)</label><input id="marc_001" name="marc_001" type="text" value="<?= $e($old['marc_001'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"></div>
                            <div><label for="marc_020" class="label-sm">020$a (ISBN)</label><input id="marc_020" name="marc_020" type="text" value="<?= $e($old['marc_020'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"></div>
                            <div><label for="marc_041" class="label-sm">041$a (Idioma)</label><input id="marc_041" name="marc_041" type="text" maxlength="3" value="<?= $e($old['marc_041'] ?? 'spa') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm uppercase focus:border-primary focus:outline-none"></div>
                            <div class="xl:col-span-2"><label for="marc_100" class="label-sm">100$a (Autor principal)</label><input id="marc_100" name="marc_100" type="text" value="<?= $e($old['marc_100'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"></div>
                            <div><label for="marc_245a" class="label-sm">245$a (Título)</label><input id="marc_245a" name="marc_245a" type="text" value="<?= $e($old['marc_245a'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"></div>
                            <div><label for="marc_245b" class="label-sm">245$b (Subtítulo)</label><input id="marc_245b" name="marc_245b" type="text" value="<?= $e($old['marc_245b'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"></div>
                            <div><label for="marc_250a" class="label-sm">250$a (Edición)</label><input id="marc_250a" name="marc_250a" type="text" value="<?= $e($old['marc_250a'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"></div>
                            <div><label for="marc_260b" class="label-sm">260$b (Editorial)</label><input id="marc_260b" name="marc_260b" type="text" value="<?= $e($old['marc_260b'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"></div>
                            <div><label for="marc_260c" class="label-sm">260$c (Fecha)</label><input id="marc_260c" name="marc_260c" type="text" value="<?= $e($old['marc_260c'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"></div>
                            <div><label for="marc_300a" class="label-sm">300$a (Desc. física)</label><input id="marc_300a" name="marc_300a" type="text" value="<?= $e($old['marc_300a'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"></div>
                            <div class="xl:col-span-3"><label for="marc_520a" class="label-sm">520$a (Resumen)</label><textarea id="marc_520a" name="marc_520a" rows="2" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"><?= $e($old['marc_520a'] ?? '') ?></textarea></div>
                            <div><label for="marc_650a" class="label-sm">650$a (Temas)</label><input id="marc_650a" name="marc_650a" type="text" value="<?= $e($old['marc_650a'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"></div>
                            <div><label for="marc_700a" class="label-sm">700$a (Autores secundarios)</label><input id="marc_700a" name="marc_700a" type="text" value="<?= $e($old['marc_700a'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"></div>
                            <div class="xl:col-span-2"><label for="marc_856u" class="label-sm">856$u (URL)</label><input id="marc_856u" name="marc_856u" type="url" value="<?= $e($old['marc_856u'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"></div>
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-outline-variant/50 bg-surface-container-low p-4">
                            <p class="label-sm">Ficha</p>
                            <dl class="mt-3 space-y-2 text-sm">
                                <div class="flex justify-between gap-3"><dt class="text-on-surface-subtle">ISBN</dt><dd data-review="isbn" class="font-semibold text-on-surface">—</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-on-surface-subtle">Título</dt><dd data-review="title" class="font-semibold text-on-surface text-right">—</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-on-surface-subtle">Autores</dt><dd data-review="authors" class="font-semibold text-on-surface text-right">—</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-on-surface-subtle">Editorial</dt><dd data-review="publisher" class="font-semibold text-on-surface text-right">—</dd></div>
                            </dl>
                        </div>
                        <div class="rounded-2xl border border-outline-variant/50 bg-surface-container-low p-4">
                            <p class="label-sm">Clasificación</p>
                            <dl class="mt-3 space-y-2 text-sm">
                                <div class="flex justify-between gap-3"><dt class="text-on-surface-subtle">Categoría</dt><dd data-review="category_id" class="font-semibold text-on-surface text-right">—</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-on-surface-subtle">Sede</dt><dd data-review="branch_id" class="font-semibold text-on-surface text-right">—</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-on-surface-subtle">Tipo de recurso</dt><dd data-review="resource_type" class="font-semibold text-on-surface text-right">—</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-on-surface-subtle">Tipo de soporte</dt><dd data-review="book_type" class="font-semibold text-on-surface text-right">—</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-on-surface-subtle">Copias</dt><dd data-review="total_copies" class="font-semibold text-on-surface text-right">—</dd></div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex min-h-14 items-center justify-between border-t border-outline-variant/70 bg-slate-100/95 px-4 py-2.5">
                <button type="button" id="wizard-prev" class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                    <i class="bi bi-arrow-left text-sm"></i> Anterior
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" id="wizard-next" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                        Siguiente <i class="bi bi-arrow-right text-sm"></i>
                    </button>
                    <button type="submit" id="wizard-submit" class="hidden inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                        <i class="bi bi-floppy text-sm"></i> Guardar recurso
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>

<script>
(() => {
    window.initCoverImageInput?.({
        inputEl:     document.getElementById('cover_image'),
        previewWrap: document.getElementById('cover-preview-create'),
        previewImg:  document.getElementById('cover-preview-img-create'),
        errorEl:     document.getElementById('cover-error-create'),
        maxMB: 5,
    });
})();

(() => {
    const form = document.getElementById('book-wizard');
    if (!form) return;

    const steps = Array.from(form.querySelectorAll('[data-step]'));
    const indicators = Array.from(form.querySelectorAll('[data-step-indicator]'));
    const prevBtn = document.getElementById('wizard-prev');
    const nextBtn = document.getElementById('wizard-next');
    const submitBtn = document.getElementById('wizard-submit');
    const categorySelect = document.getElementById('category_id');
    const branchSelect = document.getElementById('branch_id');
    const resourceTypeSelect = document.getElementById('resource_type');
    const bookTypeSelect = document.getElementById('book_type');
    const requiredHint = document.getElementById('resource-required-hint');
    let currentStep = 1;

    const requiredByType = {
        book: ['title', 'authors', 'category_id', 'replacement_cost'],
        ebook: ['title', 'authors', 'category_id', 'digital_url', 'replacement_cost'],
        journal: ['title', 'authors', 'category_id', 'publication_year', 'replacement_cost'],
        article: ['title', 'authors', 'category_id', 'replacement_cost'],
        thesis: ['title', 'authors', 'category_id', 'publication_year', 'replacement_cost'],
        map: ['title', 'authors', 'category_id', 'carrier_type', 'replacement_cost'],
        score: ['title', 'authors', 'category_id', 'carrier_type', 'replacement_cost'],
        audiovisual: ['title', 'authors', 'category_id', 'media_type', 'replacement_cost'],
        game: ['title', 'authors', 'category_id', 'replacement_cost'],
        kit: ['title', 'authors', 'category_id', 'replacement_cost'],
        other: ['title', 'authors', 'category_id', 'replacement_cost'],
    };

    const fieldLabels = {
        title: 'Título',
        authors: 'Autoría principal',
        category_id: 'Categoría',
        replacement_cost: 'Costo de reposición',
        digital_url: 'URL digital',
        publication_year: 'Año de publicación',
        media_type: 'RDA tipo de medio',
        carrier_type: 'RDA tipo de soporte',
    };

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

    const setFieldRequired = (id, required) => {
        const field = document.getElementById(id);
        if (!field) return;
        field.required = required;
    };

    const applyTypeRules = () => {
        const type = resourceTypeSelect?.value || 'book';
        const requiredFields = requiredByType[type] || requiredByType.book;
        const requiredSet = new Set(requiredFields);

        Object.keys(fieldLabels).forEach((id) => {
            setFieldRequired(id, requiredSet.has(id));
        });

        if (type === 'ebook' && bookTypeSelect) {
            bookTypeSelect.value = 'digital';
        }

        const digitalUrl = document.getElementById('digital_url');
        const location = document.getElementById('location');
        const isDigital = (bookTypeSelect?.value || '') === 'digital' || type === 'ebook';
        if (digitalUrl) digitalUrl.required = requiredSet.has('digital_url') || isDigital;
        if (location) location.disabled = isDigital;

        if (requiredHint) {
            const labels = requiredFields.map((key) => fieldLabels[key] || key);
            requiredHint.textContent = labels.join(' · ');
        }
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
            category_id: labelForSelect(categorySelect),
            branch_id: labelForSelect(branchSelect),
            resource_type: labelForSelect(resourceTypeSelect),
            book_type: labelForSelect(bookTypeSelect),
            total_copies: getFieldValue('total_copies') || '—',
        };

        Object.entries(reviewMap).forEach(([key, value]) => {
            const target = form.querySelector(`[data-review="${key}"]`);
            if (target) target.textContent = value;
        });
    };

    const showStepError = () => {
        const message = 'Completa los campos obligatorios de este paso antes de continuar.';
        if (typeof window.showLibraryToast === 'function') {
            window.showLibraryToast('error', message);
            return;
        }
        window.alert(message);
    };

    const validateStep = () => {
        const type = resourceTypeSelect?.value || 'book';
        const requiredFields = requiredByType[type] || requiredByType.book;

        if (currentStep === 1) {
            return getFieldValue('resource_type') !== '' && getFieldValue('book_type') !== '';
        }
        if (currentStep === 2) {
            const step2Fields = ['title', 'authors', 'publication_year'];
            return step2Fields.every((field) => {
                if (!requiredFields.includes(field)) return true;
                return getFieldValue(field) !== '';
            });
        }
        if (currentStep === 3) {
            if (requiredFields.includes('category_id') && getFieldValue('category_id') === '') return false;
            if (requiredFields.includes('replacement_cost') && getFieldValue('replacement_cost') === '') return false;
            if (requiredFields.includes('digital_url') && getFieldValue('digital_url') === '') return false;
            if ((bookTypeSelect?.value || '') !== 'digital' && Number(getFieldValue('total_copies') || 0) <= 0) return false;
        }
        return true;
    };

    const render = () => {
        steps.forEach((step) => {
            step.classList.toggle('hidden', Number(step.dataset.step) !== currentStep);
        });

        indicators.forEach((indicator) => {
            const active = Number(indicator.dataset.stepIndicator) === currentStep;
            indicator.classList.toggle('border-primary', active);
            indicator.classList.toggle('bg-primary/5', active);
        });

        prevBtn.disabled = currentStep === 1;
        prevBtn.classList.toggle('opacity-50', currentStep === 1);
        prevBtn.classList.toggle('cursor-not-allowed', currentStep === 1);
        nextBtn.classList.toggle('hidden', currentStep === steps.length);
        submitBtn.classList.toggle('hidden', currentStep !== steps.length);

        applyTypeRules();
        if (currentStep === steps.length) updateReview();
    };

    nextBtn?.addEventListener('click', () => {
        if (!validateStep()) {
            showStepError();
            return;
        }
        currentStep = Math.min(steps.length, currentStep + 1);
        render();
    });

    prevBtn?.addEventListener('click', () => {
        currentStep = Math.max(1, currentStep - 1);
        render();
    });

    resourceTypeSelect?.addEventListener('change', () => {
        applyTypeRules();
        updateReview();
    });
    bookTypeSelect?.addEventListener('change', applyTypeRules);
    form.addEventListener('input', updateReview);
    form.addEventListener('change', updateReview);

    render();
})();
</script>
