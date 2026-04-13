<?php
/** @var array<int, array<string, mixed>> $logs */
/** @var array<string, int> $summary */

$entityFilter = (string) ($general_entity_filter ?? '');
$actionFilter = (string) ($general_action_filter ?? '');
?>

<div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
    <form method="get" action="<?= BASE_URL ?>/admin/audit" class="grid gap-3 md:grid-cols-4">
        <input type="hidden" name="tab" value="general">
        <div>
            <label for="audit-entity-filter" class="label-sm">Entidad</label>
            <input id="audit-entity-filter" type="text" name="entity" value="<?= $e($entityFilter) ?>" placeholder="Ej: users"
                class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
        </div>
        <div>
            <label for="audit-action-filter" class="label-sm">Accion</label>
            <input id="audit-action-filter" type="text" name="action" value="<?= $e($actionFilter) ?>" placeholder="Ej: create_user"
                class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
        </div>
        <div class="md:col-span-2 flex items-end gap-2">
            <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">Filtrar</button>
            <a href="<?= BASE_URL ?>/admin/audit?tab=general" class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">Limpiar</a>
        </div>
    </form>
</div>

<div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Registros</p>
        <p class="mt-2 text-2xl font-bold text-on-surface font-display"><?= (int) ($summary['total'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Creaciones</p>
        <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= (int) ($summary['create'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Actualizaciones</p>
        <p class="mt-2 text-2xl font-bold text-blue-700 font-display"><?= (int) ($summary['update'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Eliminaciones</p>
        <p class="mt-2 text-2xl font-bold text-red-700 font-display"><?= (int) ($summary['delete'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Otros eventos</p>
        <p class="mt-2 text-2xl font-bold text-slate-700 font-display"><?= (int) ($summary['other'] ?? 0) ?></p>
    </article>
</div>

<div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left">
            <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                <tr>
                    <th class="px-4 py-3 font-semibold">Fecha</th>
                    <th class="px-4 py-3 font-semibold">Usuario</th>
                    <th class="px-4 py-3 font-semibold">Accion</th>
                    <th class="px-4 py-3 font-semibold">Entidad</th>
                    <th class="px-4 py-3 font-semibold">ID</th>
                    <th class="px-4 py-3 font-semibold">IP</th>
                    <th class="px-4 py-3 font-semibold text-right">Opciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/50 text-sm">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-on-surface-subtle">No hay eventos de auditoria registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors">
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((new DateTime($log['created_at']))->format('d/m/Y H:i:s')) ?></td>
                            <td class="px-4 py-3.5">
                                <p class="font-semibold text-on-surface"><?= $e($log['user_name'] ?? 'Sistema') ?></p>
                                <p class="text-xs text-on-surface-subtle"><?= $e($log['user_email'] ?? '-') ?></p>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $actionClass((string) $log['action']) ?>">
                                    <?= $e($log['action']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($entityLabel($log['entity_type'] ?? null)) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((string) ($log['entity_id'] ?? '-')) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($log['ip_address'] ?? '-') ?></td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a href="<?= BASE_URL ?>/admin/audit?tab=general&entity=<?= rawurlencode((string) ($log['entity_type'] ?? '')) ?>"
                                       class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                                        Entidad
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/audit?tab=general&action=<?= rawurlencode((string) ($log['action'] ?? '')) ?>"
                                       class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                                        Accion
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
