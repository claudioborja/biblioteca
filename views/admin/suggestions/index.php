<?php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$csrf = \Core\Session::get('_csrf_token', '');

$flashSuccess = \Core\Session::getFlash('success');
$flashError   = \Core\Session::getFlash('error');

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

$filterOptions = [
    ''         => 'Todas',
    'pending'  => 'Pendientes',
    'approved' => 'Aprobadas',
    'rejected' => 'Rechazadas',
    'acquired' => 'Adquiridas',
];
?>

<section class="p-6 lg:p-8">

    <!-- Header -->
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="label-sm">Biblioteca</p>
            <h1 class="headline-lg text-on-surface">Sugerencias de recursos</h1>
            <p class="body-md mt-1 text-on-surface-muted">Revisa, aprueba o rechaza las sugerencias enviadas por docentes y socios.</p>
        </div>
        <span class="text-sm text-on-surface-muted">
            <?= (int) ($counts['pending'] ?? 0) ?> pendiente<?= ((int) ($counts['pending'] ?? 0)) !== 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800"><?= $e($flashSuccess) ?></div>
    <?php elseif ($flashError): ?>
        <div class="mb-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800"><?= $e($flashError) ?></div>
    <?php endif; ?>

    <!-- Filter tabs -->
    <div class="mb-5 flex flex-wrap gap-2">
        <?php foreach ($filterOptions as $val => $label): ?>
            <?php
            $active = ($filter === $val);
            $count  = '';
            if ($val !== '') {
                $n = (int) ($counts[$val] ?? 0);
                $count = " ({$n})";
            }
            ?>
            <a href="<?= BASE_URL ?>/admin/suggestions<?= $val !== '' ? '?status=' . $val : '' ?>"
               class="rounded-xl px-3.5 py-1.5 text-sm font-semibold transition-colors
                      <?= $active
                          ? 'bg-primary text-white shadow-sm'
                          : 'border border-outline-variant bg-white text-on-surface-muted hover:bg-surface-container-low' ?>">
                <?= $e($label . $count) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($suggestions)): ?>
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-12 text-center shadow-ambient">
            <p class="body-md text-on-surface-subtle">No hay sugerencias para mostrar.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($suggestions as $s): ?>
                <div class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h2 class="headline-md text-on-surface"><?= $e($s['title']) ?></h2>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass((string) ($s['status'] ?? '')) ?>">
                                    <?= $statusLabel((string) ($s['status'] ?? '')) ?>
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-on-surface-muted">
                                <?php if (!empty($s['author'])): ?>
                                    <span><?= $e($s['author']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($s['publisher'])): ?>
                                    <span><?= $e($s['publisher']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($s['isbn'])): ?>
                                    <span class="font-mono text-xs">ISBN <?= $e($s['isbn']) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="mt-1 text-xs text-on-surface-subtle">
                                Por <strong><?= $e($s['teacher_name']) ?></strong>
                                (<?= $e($s['teacher_email']) ?>)
                                · <?= $e((new DateTime((string) $s['created_at']))->format('d/m/Y H:i')) ?>
                            </p>
                            <?php if (!empty($s['reason'])): ?>
                                <p class="mt-2 text-sm text-on-surface-muted italic">
                                    "<?= $e($s['reason']) ?>"
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($s['admin_notes']) && $s['status'] !== 'pending'): ?>
                                <p class="mt-2 text-sm font-medium <?= $s['status'] === 'rejected' ? 'text-red-700' : ($s['status'] === 'acquired' ? 'text-emerald-700' : 'text-blue-700') ?>">
                                    Nota: <?= $e($s['admin_notes']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($s['reviewer_name']) && $s['status'] !== 'pending'): ?>
                                <p class="mt-1 text-xs text-on-surface-subtle">
                                    Revisado por <strong><?= $e($s['reviewer_name']) ?></strong>
                                    <?php if (!empty($s['reviewed_at'])): ?>
                                        · <?= $e((new DateTime((string) $s['reviewed_at']))->format('d/m/Y')) ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2">
                            <?php if ($s['status'] === 'pending'): ?>
                                <!-- Approve -->
                                <button type="button"
                                        onclick="openModal('approve-<?= (int) $s['id'] ?>')"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                                    <?= Icons::check('w-4 h-4') ?> Aprobar
                                </button>
                                <!-- Reject -->
                                <button type="button"
                                        onclick="openModal('reject-<?= (int) $s['id'] ?>')"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors">
                                    <?= Icons::close('w-4 h-4') ?> Rechazar
                                </button>
                            <?php elseif ($s['status'] === 'approved'): ?>
                                <!-- Mark as acquired -->
                                <button type="button"
                                        onclick="openModal('acquire-<?= (int) $s['id'] ?>')"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-3.5 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                                    <?= Icons::check('w-4 h-4') ?> Marcar adquirida
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Approve modal -->
                <?php if ($s['status'] === 'pending'): ?>
                <div id="approve-<?= (int) $s['id'] ?>" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
                    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <h3 class="headline-md text-on-surface mb-4">Aprobar sugerencia</h3>
                        <p class="body-md text-on-surface-muted mb-4">
                            ¿Confirmas la aprobación de <strong><?= $e($s['title']) ?></strong>?
                        </p>
                        <form method="POST" action="<?= BASE_URL ?>/admin/suggestions/<?= (int) $s['id'] ?>/approve">
                            <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                            <div class="mb-4">
                                <label class="label-sm block mb-1" for="approve-notes-<?= (int) $s['id'] ?>">
                                    Nota para el solicitante (opcional)
                                </label>
                                <textarea id="approve-notes-<?= (int) $s['id'] ?>" name="admin_notes" rows="3"
                                          class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary resize-none"
                                          placeholder="Ej. Procederemos a adquirirlo en breve..."></textarea>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" onclick="closeModal('approve-<?= (int) $s['id'] ?>')"
                                        class="rounded-xl border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                                    Confirmar aprobación
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reject modal -->
                <div id="reject-<?= (int) $s['id'] ?>" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
                    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <h3 class="headline-md text-on-surface mb-4">Rechazar sugerencia</h3>
                        <p class="body-md text-on-surface-muted mb-4">
                            Indica el motivo del rechazo para <strong><?= $e($s['title']) ?></strong>.
                        </p>
                        <form method="POST" action="<?= BASE_URL ?>/admin/suggestions/<?= (int) $s['id'] ?>/reject">
                            <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                            <div class="mb-4">
                                <label class="label-sm block mb-1" for="reject-notes-<?= (int) $s['id'] ?>">
                                    Motivo <span class="text-red-500">*</span>
                                </label>
                                <textarea id="reject-notes-<?= (int) $s['id'] ?>" name="admin_notes" rows="3" required
                                          class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary resize-none"
                                          placeholder="Ej. Ya disponemos de un recurso similar en catálogo..."></textarea>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" onclick="closeModal('reject-<?= (int) $s['id'] ?>')"
                                        class="rounded-xl border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors">
                                    Confirmar rechazo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Acquire modal -->
                <?php if ($s['status'] === 'approved'): ?>
                <div id="acquire-<?= (int) $s['id'] ?>" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
                    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <h3 class="headline-md text-on-surface mb-4">Marcar como adquirida</h3>
                        <p class="body-md text-on-surface-muted mb-4">
                            Confirma que el recurso <strong><?= $e($s['title']) ?></strong> ya fue adquirido y agregado al catálogo.
                        </p>
                        <form method="POST" action="<?= BASE_URL ?>/admin/suggestions/<?= (int) $s['id'] ?>/acquire">
                            <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                            <div class="mb-4">
                                <label class="label-sm block mb-1" for="acquire-notes-<?= (int) $s['id'] ?>">
                                    Nota para el solicitante (opcional)
                                </label>
                                <textarea id="acquire-notes-<?= (int) $s['id'] ?>" name="admin_notes" rows="3"
                                          class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary resize-none"
                                          placeholder="Ej. Ya puedes encontrarlo en catálogo con ISBN 978-..."></textarea>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" onclick="closeModal('acquire-<?= (int) $s['id'] ?>')"
                                        class="rounded-xl border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                                    Confirmar adquisición
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>

<script>
function openModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('hidden'); el.classList.add('flex'); }
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('hidden'); el.classList.remove('flex'); }
}
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('fixed') && e.target.classList.contains('flex')) {
        e.target.classList.add('hidden');
        e.target.classList.remove('flex');
    }
});
</script>
