<?php
// views/teacher/assignments/create.php
use Helpers\Icons;

$e   = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$old = $old ?? [];
$v   = fn(string $key, string $default = '') => $e($old[$key] ?? $default);
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Docente · Asignaciones</p>
            <h1 class="headline-lg text-on-surface">Nueva asignación</h1>
            <p class="body-md mt-1">Asigna una lectura a un grupo y notifica automáticamente a los estudiantes.</p>
        </div>
        <a href="<?= BASE_URL ?>/teacher/assignments"
           class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
            <?= Icons::arrowLeft('w-4 h-4') ?> Volver a asignaciones
        </a>
    </div>

    <?php if (!empty($errors ?? [])): ?>
        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4">
            <ul class="list-disc list-inside space-y-1 text-sm text-red-700">
                <?php foreach ($errors as $err): ?>
                    <li><?= $e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
        <div class="rounded-2xl border border-dashed border-outline-variant/70 bg-surface-container-low p-8 text-center">
            <p class="headline-md text-on-surface">Sin grupos activos</p>
            <p class="body-md mt-2">Necesitas al menos un grupo activo para crear una asignación.</p>
            <a href="<?= BASE_URL ?>/teacher/groups/create"
               class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                <?= Icons::plus('w-4 h-4') ?> Crear grupo
            </a>
        </div>
    <?php else: ?>

    <form method="POST" action="<?= BASE_URL ?>/teacher/assignments" class="max-w-3xl space-y-5">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf ?? '') ?>">

        <div class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient space-y-6">

            <!-- Datos básicos -->
            <div>
                <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-on-surface-subtle">Datos de la asignación</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="title" class="label-sm">Título <span class="text-error">*</span></label>
                        <input id="title" name="title" type="text" required
                               value="<?= $v('title') ?>"
                               placeholder="Ej. Lectura obligatoria — Cap. 1 al 5"
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="group_id" class="label-sm">Grupo <span class="text-error">*</span></label>
                        <select id="group_id" name="group_id" required
                                class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                            <option value="">— Seleccionar grupo —</option>
                            <?php foreach ($groups as $g): ?>
                                <option value="<?= (int) $g['id'] ?>"
                                    <?= ((string) ($old['group_id'] ?? '') === (string) $g['id']) ? 'selected' : '' ?>>
                                    <?= $e($g['name']) ?> (<?= $e($g['school_year']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="due_date" class="label-sm">Fecha límite <span class="text-error">*</span></label>
                        <input id="due_date" name="due_date" type="date" required
                               value="<?= $v('due_date') ?>"
                               min="<?= date('Y-m-d') ?>"
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label for="description" class="label-sm">Instrucciones (opcional)</label>
                        <textarea id="description" name="description" rows="3"
                                  maxlength="1000"
                                  placeholder="Indicaciones adicionales para los estudiantes…"
                                  class="mt-1 w-full resize-none rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none"><?= $v('description') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Recurso -->
            <div>
                <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-on-surface-subtle">Recurso a leer</p>
                <div>
                    <label for="resource-search" class="label-sm">Buscar recurso <span class="text-error">*</span></label>
                    <input id="resource-search" type="text" placeholder="Título, ISBN o autor…"
                           autocomplete="off"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none"
                           oninput="filterResources(this.value)">
                    <input type="hidden" id="resource_id" name="resource_id" value="<?= $v('resource_id') ?>">

                    <div id="resource-list"
                         class="mt-1 max-h-56 overflow-y-auto rounded-xl border border-outline-variant bg-white shadow-ambient hidden">
                        <?php foreach ($resources as $r): ?>
                            <button type="button"
                                    class="resource-option w-full px-4 py-2.5 text-left text-sm hover:bg-surface-container-low border-b border-outline-variant/40 last:border-0 transition-colors"
                                    data-id="<?= (int) $r['id'] ?>"
                                    data-label="<?= $e($r['title']) ?>"
                                    data-search="<?= $e(strtolower($r['title'] . ' ' . ($r['authors'] ?? '') . ' ' . ($r['isbn'] ?? ''))) ?>">
                                <span class="font-medium text-on-surface"><?= $e($r['title']) ?></span>
                                <?php if (!empty($r['authors'])): ?>
                                    <span class="ml-2 text-xs text-on-surface-muted"><?= $e($r['authors']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($r['isbn'])): ?>
                                    <span class="ml-1 text-xs text-on-surface-subtle"><?= $e($r['isbn']) ?></span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <p id="resource-selected" class="mt-1 hidden text-xs text-emerald-700 font-semibold"></p>
                    <p class="mt-1 text-[11px] text-on-surface-subtle">Se notificará a los estudiantes del grupo seleccionado al crear la asignación.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="<?= BASE_URL ?>/teacher/assignments"
               class="rounded-xl border border-outline-variant bg-white px-5 py-2.5 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                <?= Icons::plus('w-4 h-4') ?> Crear asignación
            </button>
        </div>
    </form>

    <?php endif; ?>
</section>

<script>
(() => {
    const list    = document.getElementById('resource-list');
    const input   = document.getElementById('resource-search');
    const hidden  = document.getElementById('resource_id');
    const sel     = document.getElementById('resource-selected');
    const options = [...list.querySelectorAll('.resource-option')];

    window.filterResources = function (q) {
        const term = q.toLowerCase().trim();
        let visible = 0;
        options.forEach(opt => {
            const match = term === '' || opt.dataset.search.includes(term);
            opt.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        list.classList.toggle('hidden', visible === 0 && term !== '');
        if (visible > 0 || term === '') list.classList.remove('hidden');
        if (term === '') { hidden.value = ''; sel.classList.add('hidden'); }
    };

    options.forEach(opt => {
        opt.addEventListener('click', () => {
            hidden.value   = opt.dataset.id;
            input.value    = opt.dataset.label;
            sel.textContent = '✓ Seleccionado: ' + opt.dataset.label;
            sel.classList.remove('hidden');
            list.classList.add('hidden');
            options.forEach(o => o.style.display = '');
        });
    });

    input.addEventListener('focus', () => list.classList.remove('hidden'));
    document.addEventListener('click', e => {
        if (!list.contains(e.target) && e.target !== input) list.classList.add('hidden');
    });
})();
</script>
