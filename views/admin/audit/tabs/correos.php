<?php
/** @var array<int, array<string, mixed>> $email_logs */
/** @var array<string, int> $mail_summary */

$sourceFilter = (string) ($email_source_filter ?? '');
$statusFilter = (string) ($email_status_filter ?? '');
?>

<div class="mb-5">
    <h2 class="headline-md text-on-surface">Auditoria de Correos</h2>
    <p class="body-sm mt-1 text-on-surface-subtle">Eventos de envio de correos (exitos y fallos) registrados por el sistema.</p>
</div>

<div class="mb-6 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
    <form method="get" action="<?= BASE_URL ?>/admin/audit" class="grid gap-3 md:grid-cols-5">
        <input type="hidden" name="tab" value="correos">
        <div class="md:col-span-2">
            <label for="audit-mail-source" class="label-sm">Origen</label>
            <select id="audit-mail-source" name="source" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                <option value="" <?= $sourceFilter === '' ? 'selected' : '' ?>>Todos los origenes</option>
                <option value="queue" <?= $sourceFilter === 'queue' ? 'selected' : '' ?>>Cola</option>
                <option value="smtp_test" <?= $sourceFilter === 'smtp_test' ? 'selected' : '' ?>>Prueba SMTP</option>
                <option value="direct" <?= $sourceFilter === 'direct' ? 'selected' : '' ?>>Directo</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label for="audit-mail-status" class="label-sm">Estado</label>
            <select id="audit-mail-status" name="status" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>Todos los estados</option>
                <option value="success" <?= $statusFilter === 'success' ? 'selected' : '' ?>>Enviados</option>
                <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Fallidos</option>
            </select>
        </div>
        <div class="md:col-span-1 flex items-end gap-2">
            <button type="submit" class="w-full rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">Aplicar</button>
        </div>
        <div class="md:col-span-5 flex flex-wrap items-center gap-2 pt-1">
            <span class="text-xs font-semibold text-on-surface-subtle">Filtros activos:</span>
            <span class="inline-flex items-center rounded-full border border-outline-variant bg-surface-container-low px-2.5 py-1 text-xs text-on-surface-muted">
                Origen: <?= $e($sourceFilter !== '' ? $mailSourceLabel($sourceFilter) : 'Todos') ?>
            </span>
            <span class="inline-flex items-center rounded-full border border-outline-variant bg-surface-container-low px-2.5 py-1 text-xs text-on-surface-muted">
                Estado: <?= $e($statusFilter !== '' ? ($statusFilter === 'failed' ? 'Fallidos' : 'Enviados') : 'Todos') ?>
            </span>
            <a href="<?= BASE_URL ?>/admin/audit?tab=correos" class="ml-auto rounded-lg border border-outline-variant px-2.5 py-1 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">Limpiar filtros</a>
        </div>
    </form>
</div>

<div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Eventos correo</p>
        <p class="mt-2 text-2xl font-bold text-on-surface font-display"><?= (int) ($mail_summary['total'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Enviados</p>
        <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= (int) ($mail_summary['success'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Fallidos</p>
        <p class="mt-2 text-2xl font-bold text-red-700 font-display"><?= (int) ($mail_summary['failed'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Desde cola</p>
        <p class="mt-2 text-2xl font-bold text-indigo-700 font-display"><?= (int) ($mail_summary['queue'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Prueba SMTP</p>
        <p class="mt-2 text-2xl font-bold text-sky-700 font-display"><?= (int) ($mail_summary['smtp_test'] ?? 0) ?></p>
    </article>
</div>

<div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left">
            <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                <tr>
                    <th class="px-4 py-3 font-semibold">Fecha</th>
                    <th class="px-4 py-3 font-semibold">Estado</th>
                    <th class="px-4 py-3 font-semibold">Origen</th>
                    <th class="px-4 py-3 font-semibold">Destinatario</th>
                    <th class="px-4 py-3 font-semibold">Asunto</th>
                    <th class="px-4 py-3 font-semibold">Error</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/50 text-sm">
                <?php if (empty($email_logs)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-on-surface-subtle">No hay eventos de correo registrados para los filtros seleccionados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($email_logs as $mail): ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors">
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((new DateTime($mail['created_at']))->format('d/m/Y H:i:s')) ?></td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $mailActionClass((string) ($mail['action'] ?? '')) ?>">
                                    <?= $e((string) ($mail['action'] ?? 'mail_event')) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($mailSourceLabel($mail['source'] ?? null)) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted font-mono text-[12px]\"><?= $e((string) ($mail['to_email'] ?? '-')) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted max-w-[320px] truncate" title="<?= $e((string) ($mail['subject'] ?? '')) ?>"><?= $e((string) ($mail['subject'] ?? '-')) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted max-w-[340px] truncate" title="<?= $e((string) ($mail['error_message'] ?? '')) ?>"><?= $e((string) ($mail['error_message'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
