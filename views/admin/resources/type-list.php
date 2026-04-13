<?php
// views/admin/resources/type-list.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Collect unique categories from results for filter
$filterCategories = [];
foreach ($resources as $r) {
    $cat = (string) ($r['category'] ?? '');
    if ($cat !== '') $filterCategories[] = $cat;
}
$filterCategories = array_values(array_unique($filterCategories));
sort($filterCategories);

// All type links for the top subnav
$allTypes = [
    'libros'        => 'Libros',
    'digitales'     => 'Digitales',
    'revistas'      => 'Revistas',
    'articulos'     => 'Artículos',
    'tesis'         => 'Tesis',
    'mapas'         => 'Mapas',
    'partituras'    => 'Partituras',
    'audiovisuales' => 'Audiovisuales',
    'juegos'        => 'Juegos',
    'kits'          => 'Kits',
    'otros'         => 'Otros',
];

// Wizard steps
$wizardSteps = [
    ['id' => 'step-datos',        'label' => 'Identificación'],
    ['id' => 'step-descripcion',  'label' => 'Descripción'],
    ['id' => 'step-clasificacion','label' => 'Clasificación e Inventario'],
];

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

<section class="p-6 lg:p-8">

    <!-- Type pill subnav -->
    <div class="mb-6 flex items-center gap-2 overflow-x-auto pb-1">
        <?php foreach ($allTypes as $tSlug => $tLabel): ?>
        <a href="<?= BASE_URL ?>/admin/resources/type/<?= $e($tSlug) ?>"
           class="inline-flex shrink-0 items-center rounded-full px-3 py-1.5 text-xs font-semibold transition-colors <?= $tSlug === $slug ? 'bg-primary text-white shadow-ambient' : 'bg-white border border-outline-variant/60 text-on-surface-muted hover:border-primary/30 hover:text-primary' ?>">
            <?= $e($tLabel) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Header -->
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Administración</p>
            <h1 class="headline-lg text-on-surface"><?= $e($cfg['label_plural']) ?></h1>
            <p class="body-md mt-1">Gestión del catálogo de <?= $e(mb_strtolower($cfg['label_plural'])) ?>.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="js-open-type-wizard"
                    class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity inline-flex items-center gap-2">
                <i class="bi bi-plus-lg text-sm"></i> Nuevo <?= $e($cfg['label']) ?>
            </button>
        </div>
    </div>

    <!-- Filter bar -->
    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <div class="grid gap-3 md:grid-cols-<?= $cfg['show_inventory'] ? '5' : '4' ?>">
            <div class="md:col-span-2">
                <label for="type-search" class="label-sm">Buscar</label>
                <input id="type-search" type="text" placeholder="Título, autor, ISBN..."
                    class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
            </div>
            <div>
                <label for="type-category" class="label-sm">Categoría</label>
                <select id="type-category" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    <option value="all">Todas</option>
                    <?php foreach ($filterCategories as $cat): ?>
                    <option value="<?= $e(mb_strtolower($cat)) ?>"><?= $e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($cfg['show_inventory']): ?>
            <div>
                <label for="type-status" class="label-sm">Estado</label>
                <select id="type-status" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    <option value="all">Todos</option>
                    <option value="active">Disponible</option>
                    <option value="low">Pocas copias</option>
                    <option value="out">Sin stock</option>
                    <option value="inactive">Inactivo</option>
                </select>
            </div>
            <?php endif; ?>
            <div>
                <label for="type-page-size" class="label-sm">Filas</label>
                <select id="type-page-size" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table id="type-resources-table" class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="title" class="type-sort inline-flex items-center gap-1 hover:text-primary">
                                Título <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <?php if ($cfg['show_authors']): ?>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="author" class="type-sort inline-flex items-center gap-1 hover:text-primary">
                                <?= $e($cfg['authors_label']) ?> <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <?php endif; ?>
                        <?php if ($cfg['show_publisher']): ?>
                        <th class="px-4 py-3 font-semibold hidden md:table-cell">
                            <button type="button" data-sort="publisher" class="type-sort inline-flex items-center gap-1 hover:text-primary">
                                <?= $e($cfg['publisher_label']) ?> <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <?php endif; ?>
                        <th class="px-4 py-3 font-semibold hidden sm:table-cell">
                            <button type="button" data-sort="category" class="type-sort inline-flex items-center gap-1 hover:text-primary">
                                Categoría <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <?php if ($cfg['show_inventory']): ?>
                        <th class="px-4 py-3 font-semibold text-center">
                            <button type="button" data-sort="available" class="type-sort inline-flex items-center gap-1 hover:text-primary">
                                Copias <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="status" class="type-sort inline-flex items-center gap-1 hover:text-primary">
                                Estado <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <?php endif; ?>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="type-table-body" class="divide-y divide-outline-variant/50 text-sm">
                    <?php foreach ($resources as $r):
                        $avail  = (int) ($r['available_copies'] ?? 0);
                        $total  = (int) ($r['total_copies'] ?? 0);
                        $active = (int) ($r['is_active'] ?? 1);
                        if (!$active) {
                            $rowStatus = 'inactive';
                        } elseif ($avail <= 0) {
                            $rowStatus = 'out';
                        } elseif ($avail <= 2) {
                            $rowStatus = 'low';
                        } else {
                            $rowStatus = 'active';
                        }
                        $authorDisplay = '';
                        if (!empty($r['authors'])) {
                            $decoded = json_decode($r['authors'], true);
                            $authorDisplay = is_array($decoded) ? implode(', ', $decoded) : $r['authors'];
                        }
                    ?>
                    <tr class="hover:bg-surface-container-low/60 transition-colors"
                        data-row-id="<?= (int) $r['id'] ?>"
                        data-title="<?= $e(mb_strtolower($r['title'] ?? '')) ?>"
                        data-author="<?= $e(mb_strtolower($authorDisplay)) ?>"
                        data-isbn="<?= $e(mb_strtolower($r['isbn_13'] ?? '')) ?>"
                        data-publisher="<?= $e(mb_strtolower($r['publisher'] ?? '')) ?>"
                        data-category="<?= $e(mb_strtolower($r['category'] ?? '')) ?>"
                        data-status="<?= $e($rowStatus) ?>"
                        data-available="<?= $avail ?>"
                        data-copies="<?= $total ?>">
                        <td class="px-4 py-3.5">
                            <p class="font-semibold text-on-surface" data-cell="title"><?= $e($r['title'] ?? '') ?></p>
                            <?php if ($r['isbn_13'] ?? ''): ?>
                            <p class="text-xs text-on-surface-subtle mt-0.5">ISBN: <?= $e($r['isbn_13']) ?></p>
                            <?php endif; ?>
                            <?php if ($cfg['show_digital_url'] && ($r['digital_url'] ?? '')): ?>
                            <a href="<?= $e($r['digital_url']) ?>" target="_blank" rel="noopener"
                               class="text-xs text-primary hover:underline mt-0.5 inline-flex items-center gap-0.5">
                                Acceso digital <i class="bi bi-box-arrow-up-right text-[10px]"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                        <?php if ($cfg['show_authors']): ?>
                        <td class="px-4 py-3.5 text-on-surface-muted max-w-[160px] truncate" data-cell="author">
                            <?= $e($authorDisplay ?: '—') ?>
                        </td>
                        <?php endif; ?>
                        <?php if ($cfg['show_publisher']): ?>
                        <td class="px-4 py-3.5 text-on-surface-muted hidden md:table-cell" data-cell="publisher">
                            <?= $e($r['publisher'] ?? '—') ?>
                        </td>
                        <?php endif; ?>
                        <td class="px-4 py-3.5 text-on-surface-muted hidden sm:table-cell" data-cell="category">
                            <?= $e($r['category'] ?? '—') ?>
                        </td>
                        <?php if ($cfg['show_inventory']): ?>
                        <td class="px-4 py-3.5 text-center tabular-nums" data-cell="inventory">
                            <span class="font-semibold text-on-surface"><?= $avail ?></span>
                            <span class="text-on-surface-subtle">/ <?= $total ?></span>
                        </td>
                        <td class="px-4 py-3.5">
                            <span data-cell="status" class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= match($rowStatus) {
                                'active'   => 'bg-emerald-100 text-emerald-700',
                                'low'      => 'bg-amber-100 text-amber-700',
                                'out'      => 'bg-red-100 text-red-700',
                                'inactive' => 'bg-slate-100 text-slate-600',
                                default    => 'bg-slate-100 text-slate-700',
                            } ?>">
                                <?= match($rowStatus) {
                                    'active'   => 'Disponible',
                                    'low'      => 'Pocas copias',
                                    'out'      => 'Sin stock',
                                    'inactive' => 'Inactivo',
                                    default    => 'Estado',
                                } ?>
                            </span>
                        </td>
                        <?php endif; ?>
                        <td class="px-4 py-3.5 text-right">
                            <button type="button"
                                    data-edit-url="<?= BASE_URL ?>/admin/resources/type/<?= $e($slug) ?>/<?= (int) $r['id'] ?>/edit"
                                    data-edit-title="<?= $e($r['title'] ?? '') ?>"
                                    class="ml-1 rounded-lg border border-primary/30 bg-primary/5 px-2.5 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors js-open-type-edit-modal inline-flex items-center gap-1">
                                <i class="bi bi-pencil-square text-[12px]"></i> Editar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-outline-variant/60 px-4 py-3 text-sm text-on-surface-muted sm:flex-row sm:items-center sm:justify-between">
            <p id="type-table-info">Mostrando 0-0 de 0 registros</p>
            <div class="flex items-center gap-1">
                <button id="type-prev" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    <i class="bi bi-arrow-left text-[12px]"></i> Anterior
                </button>
                <span id="type-page-indicator" class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white">1</span>
                <button id="type-next" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    Siguiente <i class="bi bi-arrow-right text-[12px]"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Edit modal (iframe) ──────────────────────────────────── -->
    <div id="type-edit-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-primary/35 backdrop-blur-[1px]" data-close-type-edit-modal></div>
        <div class="absolute inset-0 p-4 sm:p-6 flex items-center justify-center">
            <div class="relative flex h-[92vh] w-[96vw] max-w-[1100px] flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">
                <div class="flex h-12 items-center justify-between border-b border-outline-variant/60 bg-gradient-to-b from-slate-100 to-slate-200 pl-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-primary/15 text-primary">
                            <i class="bi bi-pencil-square text-[12px]"></i>
                        </span>
                        <p id="type-edit-modal-title" class="truncate text-sm font-semibold text-slate-700">Editar recurso</p>
                    </div>
                    <button type="button" class="inline-flex h-12 w-12 items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors" data-close-type-edit-modal aria-label="Cerrar">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1">
                    <iframe id="type-edit-frame" title="Editar recurso" class="h-full w-full bg-white" src="about:blank"></iframe>
                </div>
                <div class="flex min-h-14 items-center justify-end border-t border-outline-variant/70 bg-slate-100/95 px-4 py-2.5">
                    <button type="button" id="type-edit-modal-save"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                        <i class="bi bi-floppy text-sm"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Create wizard modal ──────────────────────────────────── -->
    <div id="type-wizard-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-primary/35 backdrop-blur-[1px]" id="type-wizard-backdrop"></div>
        <div class="absolute inset-0 p-4 sm:p-6 flex items-center justify-center">
            <div class="relative flex max-h-[92vh] w-[96vw] max-w-[780px] flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">

                <!-- Wizard header -->
                <div class="flex h-12 items-center justify-between border-b border-outline-variant/60 bg-gradient-to-b from-slate-100 to-slate-200 pl-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-primary/15 text-primary">
                            <i class="bi bi-plus-lg text-[12px]"></i>
                        </span>
                        <p class="truncate text-sm font-semibold text-slate-700">Nuevo <?= $e($cfg['label']) ?></p>
                    </div>
                    <button type="button" id="type-wizard-close" class="inline-flex h-12 w-12 items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors" aria-label="Cerrar">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>

                <!-- Step indicator -->
                <div class="border-b border-outline-variant/50 bg-surface-container-lowest px-4 py-3">
                    <div class="flex items-center gap-2">
                        <?php foreach ($wizardSteps as $i => $step): ?>
                        <div class="flex items-center gap-2 <?= $i > 0 ? 'flex-1' : '' ?>">
                            <?php if ($i > 0): ?><div class="h-px flex-1 bg-outline-variant/50 wizard-connector" data-connector="<?= $i ?>"></div><?php endif; ?>
                            <div class="wizard-step-indicator flex items-center gap-1.5" data-step-num="<?= $i ?>">
                                <span class="wizard-step-num inline-flex h-6 w-6 items-center justify-center rounded-full border-2 border-outline-variant text-xs font-bold text-on-surface-muted transition-colors" data-step-num="<?= $i ?>">
                                    <?= $i + 1 ?>
                                </span>
                                <span class="hidden sm:inline text-xs font-semibold text-on-surface-muted wizard-step-label" data-step-num="<?= $i ?>">
                                    <?= $e($step['label']) ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Wizard form -->
                <form method="POST" action="<?= BASE_URL ?>/admin/resources/type/<?= $e($slug) ?>" id="type-wizard-form" class="flex flex-1 flex-col overflow-hidden">
                    <input type="hidden" name="_csrf_token" value="<?= $e(\Core\Session::get('_csrf_token', '')) ?>">

                    <div class="flex-1 overflow-y-auto px-5 py-5">

                        <!-- Step 0: Identificación -->
                        <div id="wizard-step-0" data-wizard-panel="0" class="space-y-4">
                            <div>
                                <h3 class="title-md text-on-surface">Identificación</h3>
                                <p class="body-sm mt-1">Datos principales del recurso.</p>
                            </div>

                            <div>
                                <label class="label-sm">Título <span class="text-red-500">*</span></label>
                                <input type="text" name="title" class="<?= $inputClass ?>" placeholder="Título completo" required>
                            </div>

                            <?php if ($cfg['show_isbn']): ?>
                            <div>
                                <label class="label-sm">ISBN</label>
                                <input type="text" name="isbn" class="<?= $inputClass ?>" placeholder="978-X-XXXX-XXXX-X">
                            </div>
                            <?php endif; ?>

                            <?php if ($cfg['show_authors']): ?>
                            <div>
                                <label class="label-sm">
                                    <?= $e($cfg['authors_label']) ?>
                                    <?= $cfg['authors_required'] ? '<span class="text-red-500">*</span>' : '' ?>
                                </label>
                                <textarea name="authors" rows="2" class="<?= $inputClass ?>"
                                          placeholder="Separar con comas o punto y coma"
                                          <?= $cfg['authors_required'] ? 'required' : '' ?>></textarea>
                            </div>
                            <?php endif; ?>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="label-sm">Año de publicación</label>
                                    <input type="number" name="publication_year" class="<?= $inputClass ?>"
                                           placeholder="<?= date('Y') ?>" min="1000" max="<?= date('Y') + 1 ?>">
                                </div>
                                <div>
                                    <label class="label-sm">Idioma</label>
                                    <select name="language" class="<?= $inputClass ?>">
                                        <?php foreach ($languages as $code => $label): ?>
                                        <option value="<?= $e($code) ?>" <?= $code === 'es' ? 'selected' : '' ?>>
                                            <?= $e($label) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <?php if ($cfg['show_publisher']): ?>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="label-sm"><?= $e($cfg['publisher_label']) ?></label>
                                    <input type="text" name="publisher" class="<?= $inputClass ?>"
                                           placeholder="<?= $e($cfg['publisher_label']) ?>">
                                </div>
                                <?php if ($cfg['show_edition']): ?>
                                <div>
                                    <label class="label-sm">Mención de edición</label>
                                    <input type="text" name="edition_statement" class="<?= $inputClass ?>"
                                           placeholder="2a ed., Rev. y aum., etc.">
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <?php if ($cfg['show_digital_url']): ?>
                            <div>
                                <label class="label-sm">
                                    URL de acceso
                                    <?= $cfg['digital_url_required'] ? '<span class="text-red-500">*</span>' : '' ?>
                                </label>
                                <input type="url" name="digital_url" class="<?= $inputClass ?>"
                                       placeholder="https://..."
                                       <?= $cfg['digital_url_required'] ? 'required' : '' ?>>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Step 1: Descripción -->
                        <div id="wizard-step-1" data-wizard-panel="1" class="hidden space-y-4">
                            <div>
                                <h3 class="title-md text-on-surface">Descripción</h3>
                                <p class="body-sm mt-1">Resumen y portada del recurso.</p>
                            </div>
                            <div>
                                <label class="label-sm">Resumen / descripción</label>
                                <textarea name="description" rows="6" class="<?= $inputClass ?>"
                                          placeholder="Breve descripción o resumen del contenido..."></textarea>
                            </div>
                            <?php if ($cfg['show_cover']): ?>
                            <div>
                                <label class="label-sm">URL de portada / imagen</label>
                                <input type="url" name="cover_image" class="<?= $inputClass ?>" placeholder="https://...">
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Step 2: Clasificación + Inventario -->
                        <div id="wizard-step-2" data-wizard-panel="2" class="hidden space-y-4">
                            <div>
                                <h3 class="title-md text-on-surface">Clasificación e Inventario</h3>
                                <p class="body-sm mt-1">Categoría, sede y existencias.</p>
                            </div>

                            <div>
                                <label class="label-sm">Categoría <span class="text-red-500">*</span></label>
                                <select name="category_id" class="<?= $inputClass ?>" required>
                                    <option value="">— Seleccionar —</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int) $cat['id'] ?>"><?= $e($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if ($cfg['show_branch'] && !empty($branches)): ?>
                            <div>
                                <label class="label-sm">Sede / Sucursal</label>
                                <select name="branch_id" class="<?= $inputClass ?>">
                                    <option value="">— Sin asignar —</option>
                                    <?php foreach ($branches as $br): ?>
                                    <option value="<?= (int) $br['id'] ?>"><?= $e($br['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <?php if ($cfg['show_inventory']): ?>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <?php if ($cfg['show_location']): ?>
                                <div class="sm:col-span-2">
                                    <label class="label-sm">Ubicación física</label>
                                    <input type="text" name="location" class="<?= $inputClass ?>"
                                           placeholder="Ej. Estante A-3, Sala 2...">
                                </div>
                                <?php endif; ?>
                                <div>
                                    <label class="label-sm">Copias totales <?= $cfg['copies_required'] ? '<span class="text-red-500">*</span>' : '' ?></label>
                                    <input type="number" name="total_copies" class="<?= $inputClass ?>"
                                           min="0" value="1"
                                           <?= $cfg['copies_required'] ? 'required' : '' ?>>
                                </div>
                                <?php if ($cfg['show_replacement_cost']): ?>
                                <div>
                                    <label class="label-sm">Costo reposición <?= $cfg['replacement_cost_required'] ? '<span class="text-red-500">*</span>' : '' ?></label>
                                    <input type="number" step="0.01" min="0" name="replacement_cost"
                                           class="<?= $inputClass ?>" placeholder="0.00" value="0.00"
                                           <?= $cfg['replacement_cost_required'] ? 'required' : '' ?>>
                                </div>
                                <div>
                                    <label class="label-sm">Precio adquisición</label>
                                    <input type="number" step="0.01" min="0" name="acquisition_price"
                                           class="<?= $inputClass ?>" placeholder="0.00" value="0.00">
                                </div>
                                <?php endif; ?>
                                <div>
                                    <label class="label-sm">Fecha de adquisición</label>
                                    <input type="date" name="acquisition_date" class="<?= $inputClass ?>">
                                </div>
                            </div>
                            <div>
                                <label class="label-sm">¿Novedad?</label>
                                <select name="is_new_acquisition" class="<?= $inputClass ?>">
                                    <option value="1" selected>Sí, es nueva adquisición</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>

                    </div><!-- /overflow panel -->

                    <!-- Wizard footer -->
                    <div class="flex items-center justify-between border-t border-outline-variant/60 bg-slate-50 px-5 py-3.5">
                        <button type="button" id="wizard-prev-btn"
                                class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors invisible">
                            <i class="bi bi-arrow-left text-sm"></i> Anterior
                        </button>
                        <div class="flex items-center gap-2">
                            <button type="button" id="wizard-next-btn"
                                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                                Siguiente <i class="bi bi-arrow-right text-sm"></i>
                            </button>
                            <button type="submit" id="wizard-submit-btn"
                                    class="hidden inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                                <i class="bi bi-floppy text-sm"></i> Registrar
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>

</section>

<script>
(() => {
    // ── Table state & rendering ──────────────────────────────────
    const tableBody   = document.getElementById('type-table-body');
    if (!tableBody) return;

    const rows        = Array.from(tableBody.querySelectorAll('tr'));
    const searchInput = document.getElementById('type-search');
    const catSelect   = document.getElementById('type-category');
    const statusSelect= document.getElementById('type-status');
    const pageSizeSel = document.getElementById('type-page-size');
    const prevBtn     = document.getElementById('type-prev');
    const nextBtn     = document.getElementById('type-next');
    const pageIndicator = document.getElementById('type-page-indicator');
    const info        = document.getElementById('type-table-info');
    const sortButtons = Array.from(document.querySelectorAll('.type-sort'));

    const state = {
        search: '', category: 'all', status: 'all',
        page: 1, pageSize: 5,
        sortBy: 'title', sortDir: 'asc',
    };

    const statusRank = { active: 1, low: 2, out: 3, inactive: 4 };
    const statusClasses = {
        active:   'bg-emerald-100 text-emerald-700',
        low:      'bg-amber-100 text-amber-700',
        out:      'bg-red-100 text-red-700',
        inactive: 'bg-slate-100 text-slate-600',
    };
    const statusLabels = {
        active: 'Disponible', low: 'Pocas copias', out: 'Sin stock', inactive: 'Inactivo',
    };

    const valForSort = (row, key) => {
        if (key === 'available') return Number(row.dataset.available || 0);
        if (key === 'status') return statusRank[row.dataset.status || ''] || 99;
        return (row.dataset[key] || '').toString();
    };

    const getFiltered = () => {
        const q = state.search.trim().toLowerCase();
        return rows.filter(row => {
            const text = [row.dataset.title, row.dataset.author, row.dataset.isbn, row.dataset.publisher, row.dataset.category].join(' ');
            const mSearch   = !q || text.includes(q);
            const mCategory = state.category === 'all' || row.dataset.category === state.category;
            const mStatus   = state.status === 'all' || row.dataset.status === state.status;
            return mSearch && mCategory && mStatus;
        });
    };

    const render = () => {
        const filtered = getFiltered().sort((a, b) => {
            const va = valForSort(a, state.sortBy), vb = valForSort(b, state.sortBy);
            if (typeof va === 'number') return state.sortDir === 'asc' ? va - vb : vb - va;
            return state.sortDir === 'asc'
                ? String(va).localeCompare(String(vb), 'es')
                : String(vb).localeCompare(String(va), 'es');
        });

        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / state.pageSize));
        state.page = Math.min(state.page, totalPages);
        const start = (state.page - 1) * state.pageSize;
        const end   = Math.min(start + state.pageSize, total);

        rows.forEach(r => r.style.display = 'none');
        filtered.slice(start, end).forEach(r => { r.style.display = ''; tableBody.appendChild(r); });

        const from = total === 0 ? 0 : start + 1;
        info.textContent = `Mostrando ${from}-${total === 0 ? 0 : end} de ${total} registros`;

        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= totalPages;
        prevBtn.classList.toggle('opacity-60', prevBtn.disabled);
        nextBtn.classList.toggle('opacity-60', nextBtn.disabled);
        pageIndicator.textContent = `${state.page}/${totalPages}`;

        sortButtons.forEach(btn => {
            const icon = btn.querySelector('span');
            if (!icon) return;
            if (btn.dataset.sort !== state.sortBy) { icon.textContent = '⇅'; return; }
            icon.textContent = state.sortDir === 'asc' ? '↑' : '↓';
        });
    };

    searchInput?.addEventListener('input',  e => { state.search   = e.target.value; state.page = 1; render(); });
    catSelect?.addEventListener('change',   e => { state.category = e.target.value; state.page = 1; render(); });
    statusSelect?.addEventListener('change',e => { state.status   = e.target.value; state.page = 1; render(); });
    pageSizeSel?.addEventListener('change', e => { state.pageSize = Number(e.target.value); state.page = 1; render(); });
    prevBtn?.addEventListener('click', () => { if (state.page > 1) { state.page--; render(); } });
    nextBtn?.addEventListener('click', () => { state.page++; render(); });
    sortButtons.forEach(btn => btn.addEventListener('click', () => {
        const key = btn.dataset.sort;
        if (state.sortBy === key) state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
        else { state.sortBy = key; state.sortDir = 'asc'; }
        state.page = 1; render();
    }));

    render();

    // ── Update row after edit ────────────────────────────────────
    const updateRow = (payload) => {
        if (!payload?.id) return;
        const row = tableBody.querySelector(`tr[data-row-id="${payload.id}"]`);
        if (!row) return;

        row.dataset.title     = String(payload.title || '').toLowerCase();
        row.dataset.author    = String(payload.author || '').toLowerCase();
        row.dataset.category  = String(payload.category || '').toLowerCase();
        row.dataset.status    = String(payload.status || 'active');
        row.dataset.available = Number(payload.available || 0);
        row.dataset.copies    = Number(payload.copies || 0);

        const titleCell    = row.querySelector('[data-cell="title"]');
        const authorCell   = row.querySelector('[data-cell="author"]');
        const categoryCell = row.querySelector('[data-cell="category"]');
        const inventoryCell= row.querySelector('[data-cell="inventory"]');
        const statusCell   = row.querySelector('[data-cell="status"]');

        if (titleCell)    titleCell.textContent    = payload.title || '';
        if (authorCell)   authorCell.textContent   = payload.author || '';
        if (categoryCell) categoryCell.textContent = payload.category || '';
        if (inventoryCell) inventoryCell.innerHTML =
            `<span class="font-semibold text-on-surface">${Number(payload.available || 0)}</span><span class="text-on-surface-subtle">/ ${Number(payload.copies || 0)}</span>`;
        if (statusCell) {
            const s = String(payload.status || 'active');
            statusCell.className = `inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${statusClasses[s] || 'bg-slate-100 text-slate-700'}`;
            statusCell.textContent = statusLabels[s] || 'Estado';
        }
        render();
    };

    // ── Edit modal ───────────────────────────────────────────────
    const editModal  = document.getElementById('type-edit-modal');
    const editFrame  = document.getElementById('type-edit-frame');
    const editTitle  = document.getElementById('type-edit-modal-title');
    const editSave   = document.getElementById('type-edit-modal-save');

    const openEditModal = (url, title) => {
        if (!editModal || !editFrame) return;
        editFrame.src = url.includes('?') ? `${url}&modal=1` : `${url}?modal=1`;
        if (editTitle) editTitle.textContent = title ? `Editar · ${title}` : 'Editar recurso';
        editModal.classList.remove('hidden');
        editModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };
    const closeEditModal = () => {
        if (!editModal || !editFrame) return;
        editModal.classList.add('hidden');
        editModal.setAttribute('aria-hidden', 'true');
        editFrame.src = 'about:blank';
        document.body.classList.remove('overflow-hidden');
    };

    document.querySelectorAll('.js-open-type-edit-modal').forEach(btn => {
        btn.addEventListener('click', () => openEditModal(btn.dataset.editUrl || '', btn.dataset.editTitle || ''));
    });
    document.querySelectorAll('[data-close-type-edit-modal]').forEach(btn => btn.addEventListener('click', closeEditModal));
    editSave?.addEventListener('click', () => editFrame?.contentWindow?.postMessage({ type: 'submit-resource-edit-form' }, '*'));
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && editModal && !editModal.classList.contains('hidden')) closeEditModal();
    });

    window.addEventListener('message', e => {
        if (e.data?.type === 'resource-type-saved') {
            closeEditModal();
            if (e.data.payload) updateRow(e.data.payload);
            if (typeof window.showLibraryToast === 'function') {
                window.showLibraryToast('success', e.data.payload?.message || 'Recurso actualizado.');
            }
        }
    });

    // ── Create wizard ────────────────────────────────────────────
    const wizardModal    = document.getElementById('type-wizard-modal');
    const wizardBackdrop = document.getElementById('type-wizard-backdrop');
    const wizardClose    = document.getElementById('type-wizard-close');
    const wizardForm     = document.getElementById('type-wizard-form');
    const openWizardBtn  = document.getElementById('js-open-type-wizard');
    const prevWizBtn     = document.getElementById('wizard-prev-btn');
    const nextWizBtn     = document.getElementById('wizard-next-btn');
    const submitWizBtn   = document.getElementById('wizard-submit-btn');
    const panels         = Array.from(document.querySelectorAll('[data-wizard-panel]'));
    const stepNums       = Array.from(document.querySelectorAll('[data-step-num]'));
    const TOTAL_STEPS    = panels.length;
    let wizardStep       = 0;

    const updateWizardUI = () => {
        panels.forEach((p, i) => p.classList.toggle('hidden', i !== wizardStep));

        // Step indicators
        document.querySelectorAll('.wizard-step-num[data-step-num]').forEach(el => {
            const n = Number(el.dataset.stepNum);
            el.classList.toggle('border-primary', n <= wizardStep);
            el.classList.toggle('bg-primary', n < wizardStep);
            el.classList.toggle('text-white', n < wizardStep);
            el.classList.toggle('text-primary', n === wizardStep);
            el.classList.toggle('border-outline-variant', n > wizardStep);
            el.classList.toggle('text-on-surface-muted', n > wizardStep);
        });

        // Hide Anterior on first step, show on all others
        prevWizBtn.classList.toggle('invisible', wizardStep === 0);
        prevWizBtn.classList.toggle('pointer-events-none', wizardStep === 0);

        const isLast = wizardStep === TOTAL_STEPS - 1;
        nextWizBtn.classList.toggle('hidden', isLast);
        submitWizBtn.classList.toggle('hidden', !isLast);
    };

    const openWizard = () => {
        if (!wizardModal) return;
        wizardStep = 0;
        wizardForm?.reset();
        updateWizardUI();
        wizardModal.classList.remove('hidden');
        wizardModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };
    const closeWizard = () => {
        if (!wizardModal) return;
        wizardModal.classList.add('hidden');
        wizardModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    openWizardBtn?.addEventListener('click', openWizard);
    wizardClose?.addEventListener('click', closeWizard);
    wizardBackdrop?.addEventListener('click', closeWizard);

    nextWizBtn?.addEventListener('click', () => {
        // Basic required-field check for current step
        const panel = panels[wizardStep];
        const required = panel ? Array.from(panel.querySelectorAll('[required]')) : [];
        const invalid = required.find(f => !f.value.trim());
        if (invalid) { invalid.focus(); invalid.reportValidity(); return; }
        if (wizardStep < TOTAL_STEPS - 1) { wizardStep++; updateWizardUI(); }
    });
    prevWizBtn?.addEventListener('click', () => {
        if (wizardStep > 0) { wizardStep--; updateWizardUI(); }
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && wizardModal && !wizardModal.classList.contains('hidden')) closeWizard();
    });
})();
</script>
