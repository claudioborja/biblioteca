<?php
// views/admin/loans/create.php
use Helpers\Icons;

$e   = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$old = $old ?? [];
$v   = fn(string $key, string $default = '') => $e($old[$key] ?? $default);
$isModal = isset($_GET['modal']) && $_GET['modal'] === '1';
?>

<section class="<?= $isModal ? 'p-0' : 'p-6 lg:p-8' ?>">
    <?php if (!$isModal): ?>
        <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="label-sm">Administración · Préstamos</p>
                <h1 class="headline-lg text-on-surface">Nuevo préstamo</h1>
                <p class="body-md mt-1">Registra un préstamo manual para un usuario y un recurso físico disponible.</p>
            </div>
            <a href="<?= BASE_URL ?>/admin/loans"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                <?= Icons::arrowLeft('w-4 h-4') ?> Volver a préstamos
            </a>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors ?? [])): ?>
        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4">
            <ul class="list-disc list-inside space-y-1 text-sm text-red-700">
                <?php foreach ($errors as $err): ?>
                    <li><?= $e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/admin/loans" id="loan-create-form" class="<?= $isModal ? 'space-y-3' : 'max-w-3xl space-y-5' ?>">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf ?? '') ?>">
        <?php if ($isModal): ?>
            <input type="hidden" name="modal" value="1">
        <?php endif; ?>

        <div class="<?= $isModal ? 'rounded-none border-0 bg-white p-4 shadow-none' : 'rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient' ?> space-y-6">

            <!-- Usuario -->
            <div>
                <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-on-surface-subtle">Usuario</p>
                <div>
                    <label for="user-search" class="label-sm">Buscar usuario <span class="text-error">*</span></label>
                    <input id="user-search" type="text" placeholder="Nombre, carnet o correo…"
                           autocomplete="off"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none"
                           oninput="filterUsers(this.value)">
                    <input type="hidden" id="user_id" name="user_id" value="<?= $v('user_id') ?>">

                    <div id="user-list"
                         class="mt-1 max-h-56 overflow-y-auto rounded-xl border border-outline-variant bg-white shadow-ambient hidden">
                        <?php foreach ($users as $u): ?>
                            <button type="button"
                                    class="user-option w-full px-4 py-2.5 text-left text-sm hover:bg-surface-container-low border-b border-outline-variant/40 last:border-0 transition-colors"
                                    data-id="<?= (int) $u['id'] ?>"
                                    data-label="<?= $e($u['name'] . ' — ' . ($u['user_number'] ?? $u['email'])) ?>">
                                <span class="font-medium text-on-surface"><?= $e($u['name']) ?></span>
                                <span class="ml-2 text-xs text-on-surface-muted"><?= $e($u['user_number'] ?? '') ?></span>
                                <span class="ml-1 text-xs text-on-surface-subtle"><?= $e($u['email']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <p id="user-selected" class="mt-1 hidden text-xs text-emerald-700 font-semibold"></p>
                    <p class="mt-1 text-[11px] text-on-surface-subtle">Solo usuarios con estado activo.</p>
                </div>
            </div>

            <!-- Recurso -->
            <div>
                <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-on-surface-subtle">Recurso</p>
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
                                    class="resource-option w-full px-4 py-2.5 text-left text-sm border-b border-outline-variant/40 last:border-0 transition-colors <?= (int) $r['available_copies'] > 0 ? 'hover:bg-surface-container-low cursor-pointer' : 'opacity-50 cursor-not-allowed bg-surface-dim' ?>"
                                    data-id="<?= (int) $r['id'] ?>"
                                    data-label="<?= $e($r['title']) ?>"
                                    data-copies="<?= (int) $r['available_copies'] ?>"
                                    data-search="<?= $e(strtolower($r['title'] . ' ' . ($r['authors'] ?? '') . ' ' . ($r['isbn'] ?? ''))) ?>"
                                    <?= (int) $r['available_copies'] <= 0 ? 'disabled' : '' ?>>
                                <span class="font-medium <?= (int) $r['available_copies'] > 0 ? 'text-on-surface' : 'text-on-surface-muted' ?>"><?= $e($r['title']) ?></span>
                                <?php if (!empty($r['isbn'])): ?>
                                    <span class="ml-2 text-xs text-on-surface-muted"><?= $e($r['isbn']) ?></span>
                                <?php endif; ?>
                                <span class="ml-1 rounded-full <?= (int) $r['available_copies'] > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' ?> px-1.5 py-0.5 text-[10px] font-semibold"><?= (int) $r['available_copies'] ?> disponible<?= (int) $r['available_copies'] !== 1 ? 's' : '' ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <p id="resource-selected" class="mt-1 hidden text-xs text-emerald-700 font-semibold"></p>
                    <p class="mt-1 text-[11px] text-on-surface-subtle">Se muestran todos los recursos. Los deshabilitados no tienen copias disponibles.</p>
                </div>
            </div>

            <!-- Plazo y notas -->
            <div>
                <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-on-surface-subtle">Condiciones</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="duration-option" class="label-sm">Duración del préstamo</label>
                        <select id="duration-option" name="duration_option"
                                class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                            <option value="system" selected><?= (int) $loan_hours ?> horas (configurado en sistema)</option>
                            <option value="24">24 horas (1 día)</option>
                            <option value="48">48 horas (2 días)</option>
                            <option value="72">72 horas (3 días)</option>
                            <option value="96">96 horas (4 días)</option>
                            <option value="168">168 horas (7 días)</option>
                            <option value="manual">Fecha manual</option>
                        </select>
                        <div id="manual-due-wrapper" class="mt-2 hidden">
                            <label for="due-at-manual" class="label-sm">Fecha manual de devolución</label>
                            <input id="due-at-manual"
                                   name="due_at_manual"
                                   type="datetime-local"
                                   class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                            <p class="mt-1 text-[11px] text-on-surface-subtle">Debe ser una fecha futura.</p>
                        </div>
                        <p class="mt-1 text-[11px] text-on-surface-subtle">
                            Vence el <strong id="due-preview">—</strong>
                        </p>
                    </div>
                    <div>
                        <label for="notes" class="label-sm">Notas internas (opcional)</label>
                        <textarea id="notes" name="notes" rows="2"
                                  maxlength="500"
                                  placeholder="Observaciones para el bibliotecario…"
                                  class="mt-1 w-full resize-none rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none"><?= $v('notes') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$isModal): ?>
            <div class="flex items-center justify-end gap-3">
                <a href="<?= BASE_URL ?>/admin/loans"
                   class="rounded-xl border border-outline-variant bg-white px-5 py-2.5 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl gradient-scholar px-5 py-2.5 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity">
                    <?= Icons::plus('w-4 h-4') ?> Registrar préstamo
                </button>
            </div>
        <?php endif; ?>
    </form>
</section>

<script>
(() => {
    const isModal = <?= $isModal ? 'true' : 'false' ?>;
    if (isModal) {
        document.body.classList.remove('min-h-screen');
        document.body.style.minHeight = 'auto';
        document.documentElement.style.minHeight = 'auto';
    }

    const loanHours = <?= (int) $loan_hours ?>;
    const durationSelect = document.getElementById('duration-option');
    const manualDueWrapper = document.getElementById('manual-due-wrapper');
    const manualDueInput = document.getElementById('due-at-manual');

    // ── Due-date preview ──────────────────────────────────────────────
    const preview = document.getElementById('due-preview');
    const updateDuePreview = () => {
        if (!preview) return;
        let dueDate = new Date(Date.now() + loanHours * 3600 * 1000);
        const option = durationSelect?.value ?? 'system';

        if (option === 'manual') {
            if (manualDueInput?.value) {
                const parsed = new Date(manualDueInput.value);
                if (!Number.isNaN(parsed.getTime())) {
                    dueDate = parsed;
                }
            }
        } else if (option !== 'system') {
            const optionHours = Number(option);
            if (!Number.isNaN(optionHours) && optionHours > 0) {
                dueDate = new Date(Date.now() + optionHours * 3600 * 1000);
            }
        }

        preview.textContent = dueDate.toLocaleString('es-EC', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    };

    const syncDurationUI = () => {
        const isManual = (durationSelect?.value ?? '') === 'manual';
        if (manualDueWrapper) {
            manualDueWrapper.classList.toggle('hidden', !isManual);
        }
        if (manualDueInput) {
            manualDueInput.required = isManual;
            if (isManual) {
                const minDate = new Date(Date.now() + 60 * 1000);
                const yyyy = minDate.getFullYear();
                const mm = String(minDate.getMonth() + 1).padStart(2, '0');
                const dd = String(minDate.getDate()).padStart(2, '0');
                const hh = String(minDate.getHours()).padStart(2, '0');
                const ii = String(minDate.getMinutes()).padStart(2, '0');
                manualDueInput.min = `${yyyy}-${mm}-${dd}T${hh}:${ii}`;
            } else {
                manualDueInput.value = '';
                manualDueInput.removeAttribute('min');
            }
        }
        updateDuePreview();
    };

    durationSelect?.addEventListener('change', syncDurationUI);
    manualDueInput?.addEventListener('input', updateDuePreview);
    syncDurationUI();

    // ── Generic live-filter helper ────────────────────────────────────
    function makeFilter({ inputId, listId, optionClass, hiddenId, selectedId, searchKey }) {
        const input     = document.getElementById(inputId);
        const list      = document.getElementById(listId);
        const hidden    = document.getElementById(hiddenId);
        const selected  = document.getElementById(selectedId);
        const options   = [...list.querySelectorAll('.' + optionClass)];

        input.addEventListener('focus', () => {
            if (input.value.trim() !== '') list.classList.remove('hidden');
        });

        window[searchKey] = function (q) {
            const term = q.toLowerCase().trim();
            let visible = 0;
            options.forEach(opt => {
                const haystack = (opt.dataset.search ?? opt.dataset.label ?? opt.textContent).toLowerCase();
                const show = term === '' || haystack.includes(term);
                opt.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            list.classList.toggle('hidden', visible === 0 && term !== '');
            if (visible > 0 || term === '') list.classList.remove('hidden');
            if (term === '') {
                hidden.value = '';
                selected.classList.add('hidden');
                selected.textContent = '';
            }
        };

        options.forEach(opt => {
            opt.addEventListener('click', (e) => {
                // Prevent selection of disabled resources
                if (opt.hasAttribute('disabled')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                hidden.value = opt.dataset.id;
                input.value  = opt.dataset.label;
                selected.textContent = '✓ Seleccionado: ' + opt.dataset.label;
                selected.classList.remove('hidden');
                list.classList.add('hidden');
                options.forEach(o => o.style.display = '');
            });
        });

        document.addEventListener('click', e => {
            if (!list.contains(e.target) && e.target !== input) {
                list.classList.add('hidden');
            }
        });
    }

    makeFilter({
        inputId: 'user-search', listId: 'user-list', optionClass: 'user-option',
        hiddenId: 'user_id', selectedId: 'user-selected',
        searchKey: 'filterUsers',
    });

    const resourceFilterConfig = {
        inputId: 'resource-search', listId: 'resource-list', optionClass: 'resource-option',
        hiddenId: 'resource_id', selectedId: 'resource-selected',
        searchKey: 'filterResources',
    };
    makeFilter(resourceFilterConfig);

    // Show lists on first focus when empty
    ['user-search', 'resource-search'].forEach(id => {
        const el = document.getElementById(id);
        el.addEventListener('focus', () => {
            const listId = id === 'user-search' ? 'user-list' : 'resource-list';
            document.getElementById(listId).classList.remove('hidden');
        });
    });

    const form = document.getElementById('loan-create-form');
    if (form) {
        window.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'submit-loan-create-form') {
                form.requestSubmit();
            }
        });
    }

})();
</script>
