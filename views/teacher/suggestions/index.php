<?php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

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
?>

<section class="p-6 lg:p-8">

    <!-- Header -->
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="label-sm">Docente · Sugerencias</p>
            <h1 class="headline-lg text-on-surface">Mis sugerencias</h1>
        </div>
        <a href="<?= BASE_URL ?>/teacher/suggestions/create"
           class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90 transition-opacity">
            <?= Icons::plus('w-4 h-4') ?> Nueva sugerencia
        </a>
    </div>

    <?php if ($flash = \Core\Session::get('flash.success')): ?>
        <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            <?= $e($flash) ?>
        </div>
    <?php elseif ($flash = \Core\Session::get('flash.error')): ?>
        <div class="mb-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <?= $e($flash) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($suggestions)): ?>
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-12 text-center shadow-ambient">
            <p class="body-md text-on-surface-subtle mb-4">Todavía no has enviado ninguna sugerencia.</p>
            <a href="<?= BASE_URL ?>/teacher/suggestions/create"
               class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 transition-opacity">
                <?= Icons::plus('w-4 h-4') ?> Sugerir un recurso
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Título</th>
                            <th class="px-5 py-3 font-semibold">Autor</th>
                            <th class="px-5 py-3 font-semibold">ISBN</th>
                            <th class="px-5 py-3 font-semibold">Estado</th>
                            <th class="px-5 py-3 font-semibold">Nota del bibliotecario</th>
                            <th class="px-5 py-3 font-semibold">Enviada el</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/50">
                        <?php foreach ($suggestions as $s): ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors">
                                <td class="px-5 py-3.5 font-medium text-on-surface">
                                    <?= $e($s['title']) ?>
                                </td>
                                <td class="px-5 py-3.5 text-on-surface-muted">
                                    <?= $e($s['author'] ?? '—') ?>
                                </td>
                                <td class="px-5 py-3.5 text-on-surface-muted font-mono text-xs">
                                    <?= $e($s['isbn'] ?? '—') ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass((string) ($s['status'] ?? '')) ?>">
                                        <?= $statusLabel((string) ($s['status'] ?? '')) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-on-surface-muted max-w-xs">
                                    <?= !empty($s['admin_notes']) ? $e($s['admin_notes']) : '<span class="text-on-surface-subtle">—</span>' ?>
                                </td>
                                <td class="px-5 py-3.5 text-on-surface-muted text-xs">
                                    <?= $e((new DateTime((string) $s['created_at']))->format('d/m/Y')) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</section>
