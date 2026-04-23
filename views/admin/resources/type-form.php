<?php
// views/admin/resources/type-form.php
$e   = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$val = fn(string $k) => $e($old[$k] ?? '');
$isModal = $is_modal ?? false;

$formAction = $is_edit
    ? BASE_URL . '/admin/resources/type/' . $slug . '/' . $resource_id
    : BASE_URL . '/admin/resources/type/' . $slug;

$languages = [
    'es' => 'Español', 'en' => 'Inglés', 'fr' => 'Francés',
    'de' => 'Alemán',  'pt' => 'Portugués', 'it' => 'Italiano',
    'la' => 'Latín',   'other' => 'Otro',
];

$inputClass = 'mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none';
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="<?= $isModal ? 'p-4' : 'p-6 lg:p-8' ?>">

    <?php if (!$isModal): ?>
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Recursos</p>
            <h1 class="headline-lg text-on-surface">
                <?= $is_edit ? 'Editar' : 'Nuevo' ?> <?= $e($cfg['label']) ?>
            </h1>
        </div>
        <a href="<?= BASE_URL ?>/admin/resources/type/<?= $e($slug) ?>"
           class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors inline-flex items-center gap-2">
            <i class="bi bi-arrow-left text-sm"></i> Volver
        </a>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= $e($formAction) ?>" id="type-resource-form" enctype="multipart/form-data" class="space-y-6" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= $e(\Core\Session::get('_csrf_token', '')) ?>">
        <?php if ($isModal): ?>
        <input type="hidden" name="modal" value="1">
        <?php endif; ?>

        <!-- Tab bar -->
        <nav class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white <?= $isModal ? '' : 'shadow-ambient' ?>">
            <div class="border-b border-outline-variant/50 bg-surface-container-lowest px-3 py-2.5">
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" data-tab-target="datos"
                            class="type-tab inline-flex items-center gap-2 rounded-lg border border-primary/40 bg-white px-3 py-2 text-sm font-semibold text-primary shadow-sm transition-colors">
                        <i class="bi bi-file-text text-sm"></i> Datos
                    </button>
                    <button type="button" data-tab-target="descripcion"
                            class="type-tab inline-flex items-center gap-2 rounded-lg border border-transparent bg-transparent px-3 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low hover:text-on-surface transition-colors">
                        <i class="bi bi-card-text text-sm"></i> Descripción
                    </button>
                    <button type="button" data-tab-target="clasificacion"
                            class="type-tab inline-flex items-center gap-2 rounded-lg border border-transparent bg-transparent px-3 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low hover:text-on-surface transition-colors">
                        <i class="bi bi-funnel text-sm"></i> Clasificación
                    </button>
                    <?php if ($cfg['show_inventory']): ?>
                    <button type="button" data-tab-target="inventario"
                            class="type-tab inline-flex items-center gap-2 rounded-lg border border-transparent bg-transparent px-3 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low hover:text-on-surface transition-colors">
                        <i class="bi bi-stack text-sm"></i> Inventario
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Tab panels -->
        <div class="<?= $isModal ? 'rounded-2xl border border-outline-variant/60 bg-white p-4' : 'rounded-3xl border border-outline-variant/60 bg-white p-5 shadow-ambient-lg lg:p-6' ?>">

            <!-- ── Datos ─────────────────────────────────────────── -->
            <section id="section-datos" data-tab-panel="datos" class="space-y-5">
                <div>
                    <h2 class="headline-md text-on-surface">Identificación</h2>
                    <p class="body-md mt-1">Información principal del recurso.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">

                    <div class="xl:col-span-<?= ($cfg['show_authors'] ? '2' : '3') ?>">
                        <label class="label-sm"><?= $e($cfg['title_label'] ?? 'Titulo') ?> <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="<?= $val('title') ?>"
                               class="<?= $inputClass ?>" placeholder="<?= $e($cfg['title_placeholder'] ?? 'Titulo completo') ?>" required>
                    </div>

                    <?php if ($cfg['show_isbn']): ?>
                    <div>
                        <label class="label-sm"><?= $e($cfg['identifier_label'] ?? 'ISBN') ?></label>
                        <input type="text" name="isbn" value="<?= $val('isbn') ?>"
                               class="<?= $inputClass ?>" placeholder="<?= $e($cfg['identifier_placeholder'] ?? '978-X-XXXX-XXXX-X') ?>">
                    </div>
                    <?php endif; ?>

                    <?php if ($cfg['show_authors']): ?>
                    <div class="xl:col-span-2">
                        <label class="label-sm">
                            <?= $e($cfg['authors_label']) ?>
                            <?= $cfg['authors_required'] ? '<span class="text-red-500">*</span>' : '' ?>
                        </label>
                        <textarea name="authors" rows="2" class="<?= $inputClass ?>"
                                  placeholder="Separar con comas o punto y coma"
                                  <?= $cfg['authors_required'] ? 'required' : '' ?>><?= $val('authors') ?></textarea>
                    </div>
                    <?php endif; ?>

                    <div>
                        <label class="label-sm">Año de publicación</label>
                        <input type="number" name="publication_year" value="<?= $val('publication_year') ?>"
                               class="<?= $inputClass ?>" placeholder="<?= date('Y') ?>"
                               min="1000" max="<?= date('Y') + 1 ?>">
                    </div>

                    <div>
                        <label class="label-sm">Idioma</label>
                        <select name="language" class="<?= $inputClass ?>">
                            <?php foreach ($languages as $code => $label): ?>
                            <option value="<?= $e($code) ?>" <?= ($old['language'] ?? 'es') === $code ? 'selected' : '' ?>>
                                <?= $e($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($cfg['show_publisher']): ?>
                    <div>
                        <label class="label-sm"><?= $e($cfg['publisher_label']) ?></label>
                        <input type="text" name="publisher" value="<?= $val('publisher') ?>"
                               class="<?= $inputClass ?>" placeholder="<?= $e($cfg['publisher_label']) ?>">
                    </div>
                    <?php if ($cfg['show_edition']): ?>
                    <div>
                        <label class="label-sm">Mención de edición</label>
                        <input type="text" name="edition_statement" value="<?= $val('edition_statement') ?>"
                               class="<?= $inputClass ?>" placeholder="2a ed., Rev. y aum., etc.">
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($cfg['show_digital_url']): ?>
                    <div class="xl:col-span-2">
                        <label class="label-sm">
                            <?= $e($cfg['digital_url_label'] ?? 'URL de acceso') ?>
                            <?= $cfg['digital_url_required'] ? '<span class="text-red-500">*</span>' : '' ?>
                        </label>
                        <input type="url" name="digital_url" value="<?= $val('digital_url') ?>"
                               class="<?= $inputClass ?>" placeholder="<?= $e($cfg['digital_url_placeholder'] ?? 'https://...') ?>"
                               <?= $cfg['digital_url_required'] ? 'required' : '' ?>>
                    </div>
                    <?php endif; ?>

                </div>
            </section>

            <!-- ── Descripción ───────────────────────────────────── -->
            <section id="section-descripcion" data-tab-panel="descripcion" class="hidden space-y-5">
                <div>
                    <h2 class="headline-md text-on-surface">Descripción</h2>
                    <p class="body-md mt-1">Resumen, portada y notas adicionales.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div class="xl:col-span-3">
                        <label class="label-sm">Resumen / descripción</label>
                        <textarea name="description" rows="5" class="<?= $inputClass ?>"
                                  placeholder="Breve descripción o resumen del contenido..."><?= $val('description') ?></textarea>
                    </div>
                    <?php if ($cfg['show_cover']): ?>
                    <div class="xl:col-span-2">
                        <label class="label-sm">Imagen de portada</label>
                        <?php if ($is_edit): ?>
                        <input type="hidden" name="existing_cover_image" value="<?= $val('cover_image') ?>">
                        <?php endif; ?>
                        <?php if ($val('cover_image') !== ''): ?>
                        <div class="mb-2 flex items-center gap-2 rounded-xl border border-outline-variant bg-surface-container-lowest p-2">
                            <img src="<?= $e(BASE_URL . ($old['cover_image'] ?? '')) ?>" alt="Portada actual" class="h-14 w-10 rounded object-cover">
                            <span class="text-xs text-on-surface-muted truncate"><?= $val('cover_image') ?></span>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif"
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/20 focus:border-primary focus:outline-none">
                        <p class="mt-1 text-xs text-on-surface-muted"><?= $is_edit ? 'Dejar vacío para conservar. ' : '' ?>Máx. 5 MB · JPG, PNG, WEBP, GIF</p>
                        <p id="cover-error-type" class="mt-1 hidden text-xs font-semibold text-red-600"></p>
                        <div id="cover-preview-type" class="mt-2 hidden">
                            <img id="cover-preview-img-type" src="" alt="Vista previa" class="h-28 w-20 rounded-xl border border-outline-variant object-cover">
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($type === 'ebook'): ?>
                    <div class="xl:col-span-3">
                        <label class="label-sm">Archivo PDF del libro digital <span class="text-red-500">*</span></label>
                        <?php if ($is_edit && $val('digital_url') !== ''): ?>
                        <input type="hidden" name="existing_digital_url" value="<?= $val('digital_url') ?>">
                        <div class="mb-2 flex items-center gap-2 rounded-xl border border-outline-variant bg-surface-container-lowest p-2">
                            <iconify-icon icon="mdi:file-pdf-box" class="text-red-600"></iconify-icon>
                            <span class="text-xs text-on-surface-muted truncate"><?= $val('digital_url') ?></span>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="digital_pdf" accept="application/pdf,.pdf" <?= $is_edit ? '' : 'required' ?>
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/20 focus:border-primary focus:outline-none">
                        <p class="mt-1 text-xs text-on-surface-muted"><?= $is_edit ? 'Si no cargas un nuevo PDF, se conserva el archivo actual. ' : '' ?>Formato PDF, máximo 50 MB.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ── Clasificación ─────────────────────────────────── -->
            <section id="section-clasificacion" data-tab-panel="clasificacion" class="hidden space-y-5">
                <div>
                    <h2 class="headline-md text-on-surface">Clasificación</h2>
                    <p class="body-md mt-1">Categoría, sede y estado del recurso.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label class="label-sm">Categoría <span class="text-red-500">*</span></label>
                        <select name="category_id" class="<?= $inputClass ?>" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>"
                                <?= (string)(int)($old['category_id'] ?? '') === (string)(int)$cat['id'] ? 'selected' : '' ?>>
                                <?= $e($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($cfg['show_branch'] && !empty($branches)): ?>
                    <div>
                        <label class="label-sm">Sede / Sucursal</label>
                        <select name="branch_id" class="<?= $inputClass ?>">
                            <option value="">— Sin asignar —</option>
                            <?php foreach ($branches as $br): ?>
                            <option value="<?= (int) $br['id'] ?>"
                                <?= (string)(int)($old['branch_id'] ?? '') === (string)(int)$br['id'] ? 'selected' : '' ?>>
                                <?= $e($br['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($is_edit && !$isModal): ?>
                    <div>
                        <label class="label-sm">Estado del recurso</label>
                        <select name="is_active" class="<?= $inputClass ?>">
                            <option value="1" <?= ($old['is_active'] ?? '1') === '1' ? 'selected' : '' ?>>Activo</option>
                            <option value="0" <?= ($old['is_active'] ?? '1') === '0' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ── Inventario ────────────────────────────────────── -->
            <?php if ($cfg['show_inventory']): ?>
            <section id="section-inventario" data-tab-panel="inventario" class="hidden space-y-5">
                <div>
                    <h2 class="headline-md text-on-surface">Inventario</h2>
                    <p class="body-md mt-1">Copias, costos y adquisición.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <?php if ($cfg['show_location']): ?>
                    <div>
                        <label class="label-sm">Ubicación física</label>
                        <input type="text" name="location" value="<?= $val('location') ?>"
                               class="<?= $inputClass ?>" placeholder="Ej. Estante A-3, Sala 2...">
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="label-sm">Copias totales <?= $cfg['copies_required'] ? '<span class="text-red-500">*</span>' : '' ?></label>
                        <input type="number" name="total_copies" value="<?= $val('total_copies') ?>"
                               class="<?= $inputClass ?>" min="0"
                               <?= $cfg['copies_required'] ? 'required' : '' ?>>
                    </div>
                    <?php if ($is_edit && !$isModal): ?>
                    <div>
                        <label class="label-sm">Disponibles</label>
                        <input type="number" name="available_copies" value="<?= $val('available_copies') ?>"
                               class="<?= $inputClass ?>" min="0">
                    </div>
                    <?php endif; ?>
                    <?php if ($cfg['show_replacement_cost']): ?>
                    <div>
                        <label class="label-sm">Costo reposición <?= $cfg['replacement_cost_required'] ? '<span class="text-red-500">*</span>' : '' ?></label>
                        <input type="number" step="0.01" min="0" name="replacement_cost"
                               value="<?= $val('replacement_cost') ?>"
                               class="<?= $inputClass ?>" placeholder="0.00"
                               <?= $cfg['replacement_cost_required'] ? 'required' : '' ?>>
                    </div>
                    <div>
                        <label class="label-sm">Precio adquisición</label>
                        <input type="number" step="0.01" min="0" name="acquisition_price"
                               value="<?= $val('acquisition_price') ?>"
                               class="<?= $inputClass ?>" placeholder="0.00">
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="label-sm">Fecha de adquisición</label>
                        <input type="date" name="acquisition_date" value="<?= $val('acquisition_date') ?>"
                               class="<?= $inputClass ?>">
                    </div>
                    <div>
                        <label class="label-sm">¿Novedad?</label>
                        <select name="is_new_acquisition" class="<?= $inputClass ?>">
                            <option value="1" <?= ($old['is_new_acquisition'] ?? '1') === '1' ? 'selected' : '' ?>>Sí, es nueva adquisición</option>
                            <option value="0" <?= ($old['is_new_acquisition'] ?? '1') === '0' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>
            </section>
            <?php endif; ?>

        </div>

        <?php if (!$isModal): ?>
        <div class="rounded-2xl border border-outline-variant/60 bg-white px-4 py-3 shadow-ambient">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="<?= BASE_URL ?>/admin/resources/type/<?= $e($slug) ?>"
                   class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                    <i class="bi bi-x-lg text-sm"></i> Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                    <i class="bi bi-floppy text-sm"></i>
                    <?= $is_edit ? 'Guardar cambios' : 'Registrar ' . $e($cfg['label']) ?>
                </button>
            </div>
        </div>
        <?php endif; ?>
    </form>
</section>

<script>
(() => {
    window.initCoverImageInput?.({
        inputEl:     document.querySelector('input[name="cover_image"][type="file"]'),
        previewWrap: document.getElementById('cover-preview-type'),
        previewImg:  document.getElementById('cover-preview-img-type'),
        errorEl:     document.getElementById('cover-error-type'),
        maxMB: 5,
    });
})();

(() => {
    const form = document.getElementById('type-resource-form');
    if (!form) return;

    const tabButtons = Array.from(form.querySelectorAll('[data-tab-target]'));
    const tabPanels  = Array.from(document.querySelectorAll('[data-tab-panel]'));

    const activateTab = (target) => {
        tabButtons.forEach((btn) => {
            const active = btn.dataset.tabTarget === target;
            btn.classList.toggle('border-primary/40', active);
            btn.classList.toggle('bg-white', active);
            btn.classList.toggle('text-primary', active);
            btn.classList.toggle('shadow-sm', active);
            btn.classList.toggle('border-transparent', !active);
            btn.classList.toggle('bg-transparent', !active);
            btn.classList.toggle('text-on-surface-muted', !active);
        });
        tabPanels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.tabPanel !== target);
        });
    };

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => activateTab(btn.dataset.tabTarget || 'datos'));
    });

    // Listen for save trigger from parent (modal mode)
    window.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'submit-resource-edit-form') {
            form.requestSubmit();
        }
    });

    activateTab('datos');
})();
</script>
