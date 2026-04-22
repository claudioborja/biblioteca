<?php
// views/admin/loans/create.php
use Helpers\Icons;

$e   = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$old = $old ?? [];
$v   = fn(string $key, string $default = '') => $e($old[$key] ?? $default);
?>

<section class="p-6 lg:p-8">
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

    <?php if (!empty($errors ?? [])): ?>
        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4">
            <ul class="list-disc list-inside space-y-1 text-sm text-red-700">
                <?php foreach ($errors as $err): ?>
                    <li><?= $e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/admin/loans" class="max-w-3xl space-y-5">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf ?? '') ?>">

        <div class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient space-y-6">

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
                                    class="resource-option w-full px-4 py-2.5 text-left text-sm hover:bg-surface-container-low border-b border-outline-variant/40 last:border-0 transition-colors"
                                    data-id="<?= (int) $r['id'] ?>"
                                    data-label="<?= $e($r['title']) ?>"
                                    data-copies="<?= (int) $r['available_copies'] ?>"
                                    data-search="<?= $e(strtolower($r['title'] . ' ' . ($r['authors'] ?? '') . ' ' . ($r['isbn'] ?? ''))) ?>">
                                <span class="font-medium text-on-surface"><?= $e($r['title']) ?></span>
                                <?php if (!empty($r['isbn'])): ?>
                                    <span class="ml-2 text-xs text-on-surface-muted"><?= $e($r['isbn']) ?></span>
                                <?php endif; ?>
                                <span class="ml-1 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700"><?= (int) $r['available_copies'] ?> disponible<?= (int) $r['available_copies'] !== 1 ? 's' : '' ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <p id="resource-selected" class="mt-1 hidden text-xs text-emerald-700 font-semibold"></p>
                    <p class="mt-1 text-[11px] text-on-surface-subtle">Solo recursos físicos activos con copias disponibles.</p>
                </div>
            </div>

            <!-- Plazo y notas -->
            <div>
                <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-on-surface-subtle">Condiciones</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="label-sm">Duración del préstamo</label>
                        <p class="mt-1 rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface-muted">
                            <?= (int) $loan_hours ?> horas (configurado en el sistema)
                        </p>
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
    </form>
</section>

<script>
(() => {
    const loanHours = <?= (int) $loan_hours ?>;

    // ── Due-date preview ──────────────────────────────────────────────
    const preview = document.getElementById('due-preview');
    if (preview) {
        const due = new Date(Date.now() + loanHours * 3600 * 1000);
        preview.textContent = due.toLocaleString('es-EC', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    }

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
            opt.addEventListener('click', () => {
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

    makeFilter({
        inputId: 'resource-search', listId: 'resource-list', optionClass: 'resource-option',
        hiddenId: 'resource_id', selectedId: 'resource-selected',
        searchKey: 'filterResources',
    });

    // Show lists on first focus when empty
    ['user-search', 'resource-search'].forEach(id => {
        const el = document.getElementById(id);
        el.addEventListener('focus', () => {
            const listId = id === 'user-search' ? 'user-list' : 'resource-list';
            document.getElementById(listId).classList.remove('hidden');
        });
    });
})();
</script>
