<?php
// views/account/suggestions.php
declare(strict_types=1);

$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$csrf = \Core\Session::get('_csrf_token', '');
$flashSuccess = \Core\Session::getFlash('success');
$flashError   = \Core\Session::getFlash('error');
$old          = \Core\Session::getFlash('old') ?? [];

$statusLabel = static fn(string $s): string => match ($s) {
    'pending'  => 'Pendiente',
    'approved' => 'Aprobada',
    'rejected' => 'Rechazada',
    'acquired' => 'Adquirida',
    default    => ucfirst($s),
};
$statusClass = static fn(string $s): string => match ($s) {
    'pending'  => 'bg-amber-100 text-amber-700',
    'approved' => 'bg-blue-100 text-blue-700',
    'rejected' => 'bg-red-100 text-red-700',
    'acquired' => 'bg-emerald-100 text-emerald-700',
    default    => 'bg-slate-100 text-slate-700',
};

$inputClass = 'mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none';
?>

<section class="p-6 lg:p-8 max-w-4xl mx-auto">

    <!-- Header -->
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="label-sm text-on-surface-subtle">Mi zona</p>
            <h1 class="headline-lg text-on-surface">Sugerencias de recursos</h1>
            <p class="body-md mt-1 text-on-surface-muted">Propón recursos que te gustaría ver en el catálogo de la biblioteca.</p>
        </div>
        <button type="button" id="js-open-suggest-form"
                class="shrink-0 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors shadow-ambient">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nueva sugerencia
        </button>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= $e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $e($flashError) ?></div>
    <?php endif; ?>

    <!-- Suggestion list -->
    <?php if (empty($suggestions)): ?>
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-12 text-center shadow-ambient">
            <svg class="mx-auto mb-3 h-10 w-10 text-on-surface-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/></svg>
            <p class="body-md text-on-surface-subtle mb-3">Aún no has enviado ninguna sugerencia.</p>
            <button type="button" id="js-open-suggest-form-empty"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                Enviar mi primera sugerencia
            </button>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($suggestions as $s): ?>
                <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h2 class="font-semibold text-on-surface"><?= $e($s['title']) ?></h2>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass((string) ($s['status'] ?? '')) ?>">
                                    <?= $statusLabel((string) ($s['status'] ?? '')) ?>
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-x-4 gap-y-0.5 text-sm text-on-surface-muted">
                                <?php if (!empty($s['author'])): ?><span><?= $e($s['author']) ?></span><?php endif; ?>
                                <?php if (!empty($s['publisher'])): ?><span><?= $e($s['publisher']) ?></span><?php endif; ?>
                                <?php if (!empty($s['isbn'])): ?><span class="font-mono text-xs">ISBN <?= $e($s['isbn']) ?></span><?php endif; ?>
                            </div>
                            <?php if (!empty($s['reason'])): ?>
                                <p class="mt-1.5 text-sm text-on-surface-subtle italic">"<?= $e($s['reason']) ?>"</p>
                            <?php endif; ?>
                            <?php if (!empty($s['admin_notes']) && $s['status'] !== 'pending'): ?>
                                <div class="mt-2 rounded-lg border <?= $s['status'] === 'rejected' ? 'border-red-200 bg-red-50' : ($s['status'] === 'acquired' ? 'border-emerald-200 bg-emerald-50' : 'border-blue-200 bg-blue-50') ?> px-3 py-2 text-sm <?= $s['status'] === 'rejected' ? 'text-red-700' : ($s['status'] === 'acquired' ? 'text-emerald-700' : 'text-blue-700') ?>">
                                    <span class="font-semibold">Nota del bibliotecario:</span> <?= $e($s['admin_notes']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <p class="shrink-0 text-xs text-on-surface-subtle">
                            <?= $e((new DateTime((string) $s['created_at']))->format('d/m/Y')) ?>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>

<!-- New suggestion modal -->
<div id="suggest-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4" aria-modal="true">
    <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl flex flex-col max-h-[92vh]">
        <div class="flex items-center justify-between border-b border-outline-variant/60 bg-gradient-to-b from-slate-100 to-slate-200 px-5 py-3 rounded-t-2xl">
            <h2 class="font-semibold text-slate-700">Nueva sugerencia de recurso</h2>
            <button type="button" id="js-close-suggest-modal" class="rounded-lg p-1.5 text-slate-500 hover:bg-red-600 hover:text-white transition-colors" aria-label="Cerrar">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 px-6 py-5">
            <p class="body-sm text-on-surface-muted mb-5">Completa el formulario con la información del recurso que deseas sugerir. Solo el título es obligatorio.</p>
            <form method="POST" action="<?= BASE_URL ?>/account/suggestions" id="suggest-form" class="space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                <div>
                    <label class="label-sm" for="sug-title">Título <span class="text-red-500">*</span></label>
                    <input id="sug-title" name="title" type="text" required
                           value="<?= $e($old['title'] ?? '') ?>"
                           placeholder="Título del libro, revista u otro recurso"
                           class="<?= $inputClass ?>">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label-sm" for="sug-author">Autor / Autora</label>
                        <input id="sug-author" name="author" type="text"
                               value="<?= $e($old['author'] ?? '') ?>"
                               placeholder="Nombre del autor"
                               class="<?= $inputClass ?>">
                    </div>
                    <div>
                        <label class="label-sm" for="sug-isbn">ISBN</label>
                        <input id="sug-isbn" name="isbn" type="text"
                               value="<?= $e($old['isbn'] ?? '') ?>"
                               placeholder="978-X-XXXX-XXXX-X"
                               class="<?= $inputClass ?>">
                    </div>
                </div>
                <div>
                    <label class="label-sm" for="sug-publisher">Editorial / Publicación</label>
                    <input id="sug-publisher" name="publisher" type="text"
                           value="<?= $e($old['publisher'] ?? '') ?>"
                           placeholder="Editorial o fuente"
                           class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="label-sm" for="sug-reason">¿Por qué lo sugieres?</label>
                    <textarea id="sug-reason" name="reason" rows="3"
                              placeholder="Describe brevemente por qué este recurso sería útil para la comunidad..."
                              class="<?= $inputClass ?> resize-none"><?= $e($old['reason'] ?? '') ?></textarea>
                </div>
            </form>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-outline-variant/60 bg-slate-50 px-6 py-4 rounded-b-2xl">
            <button type="button" id="js-close-suggest-modal-2"
                    class="rounded-xl border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                Cancelar
            </button>
            <button type="submit" form="suggest-form"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                Enviar sugerencia
            </button>
        </div>
    </div>
</div>

<script>
(() => {
    const modal   = document.getElementById('suggest-modal');
    const openBtns = [
        document.getElementById('js-open-suggest-form'),
        document.getElementById('js-open-suggest-form-empty'),
    ];
    const closeBtns = [
        document.getElementById('js-close-suggest-modal'),
        document.getElementById('js-close-suggest-modal-2'),
    ];

    const open  = () => { if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); } };
    const close = () => { if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); } };

    openBtns.forEach(btn => btn?.addEventListener('click', open));
    closeBtns.forEach(btn => btn?.addEventListener('click', close));

    modal?.addEventListener('click', e => { if (e.target === modal) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

    <?php if (!empty($old)): ?>
    // Re-open if there were validation errors
    open();
    <?php endif; ?>
})();
</script>
