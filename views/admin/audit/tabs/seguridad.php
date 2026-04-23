<?php
/** @var array<string, int|string> $security_summary */
/** @var array<int, array<string, string>> $security_controls */
/** @var array<int, array<string, string>> $security_events */
/** @var array<int, array<string, int|string>> $security_throttle_entries */

$severityClass = static function (string $severity): string {
    return match ($severity) {
        'high' => 'bg-red-100 text-red-700',
        'medium' => 'bg-amber-100 text-amber-700',
        default => 'bg-emerald-100 text-emerald-700',
    };
};

$controlStatusClass = static function (string $status): string {
    $normalized = mb_strtolower(trim($status));

    if (in_array($normalized, ['activo', 'aplicado', 'si', 'sí', 'argon2id', 'bcrypt'], true)) {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (in_array($normalized, ['no', 'no aplicado', 'no disponible', 'desconocido'], true)) {
        return 'bg-red-100 text-red-700';
    }

    return 'bg-slate-100 text-slate-700';
};

$throttleStatusClass = static function (string $status): string {
    return match ($status) {
        'Bloqueado' => 'bg-red-100 text-red-700',
        default => 'bg-amber-100 text-amber-700',
    };
};
?>

<div class="mb-5">
    <h2 class="headline-md text-on-surface">Auditoría de Seguridad</h2>
    <p class="body-sm mt-1 text-on-surface-subtle">Monitoreo real de eventos de autenticación, bloqueos activos, controles de protección y trazabilidad sensible.</p>
</div>

<div class="mb-6 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-on-surface">Estado operativo de seguridad</p>
            <p class="mt-1 text-xs text-on-surface-subtle">Generado automáticamente: <?= $e((string) ($security_summary['generated_at'] ?? 'N/A')) ?></p>
        </div>
        <div class="text-xs text-on-surface-subtle">Fuente: storage/logs/auth.log, storage/throttle y audit_logs</div>
    </div>
</div>

<div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Eventos 24h</p>
        <p class="mt-2 text-2xl font-bold text-on-surface font-display"><?= (int) ($security_summary['events_24h'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Login fallidos 24h</p>
        <p class="mt-2 text-2xl font-bold text-red-700 font-display"><?= (int) ($security_summary['failed_logins_24h'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Bloqueos activos</p>
        <p class="mt-2 text-2xl font-bold text-amber-700 font-display"><?= (int) ($security_summary['active_locks'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Cuentas restringidas</p>
        <p class="mt-2 text-2xl font-bold text-sky-700 font-display"><?= (int) ($security_summary['restricted_accounts'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">IPs únicas 24h</p>
        <p class="mt-2 text-2xl font-bold text-indigo-700 font-display"><?= (int) ($security_summary['unique_ips_24h'] ?? 0) ?></p>
    </article>
</div>

<div class="mb-7 grid gap-6 xl:grid-cols-[1.05fr_1fr]">
    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="border-b border-outline-variant/60 px-5 py-4">
            <h3 class="title-sm text-on-surface">Controles de seguridad</h3>
            <p class="mt-1 text-sm text-on-surface-subtle">Estado actual detectado en runtime y configuración del sistema.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Control</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50 text-sm">
                    <?php if (empty($security_controls)): ?>
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-on-surface-subtle">No se pudieron determinar controles de seguridad.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($security_controls as $control): ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors">
                                <td class="px-4 py-3.5 font-semibold text-on-surface"><?= $e((string) ($control['control'] ?? 'Control')) ?></td>
                                <td class="px-4 py-3.5">
                                    <?php $status = (string) ($control['status'] ?? '-'); ?>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $controlStatusClass($status) ?>">
                                        <?= $e($status) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((string) ($control['detail'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="border-b border-outline-variant/60 px-5 py-4">
            <h3 class="title-sm text-on-surface">Throttling de autenticación</h3>
            <p class="mt-1 text-sm text-on-surface-subtle">Registros activos de intentos recientes y ventanas de bloqueo.</p>
        </div>
        <div class="max-h-[520px] overflow-auto px-5 py-4 space-y-3">
            <?php if (empty($security_throttle_entries)): ?>
                <div class="rounded-xl border border-dashed border-outline-variant p-4 text-sm text-on-surface-subtle">
                    No hay registros de throttle activos en este momento.
                </div>
            <?php else: ?>
                <?php foreach ($security_throttle_entries as $entry): ?>
                    <article class="rounded-xl border border-outline-variant/60 bg-surface-container-lowest p-3">
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="font-mono text-on-surface-muted"><?= $e((string) ($entry['key'] ?? 'record.json')) ?></span>
                            <span class="inline-flex rounded-full px-2.5 py-1 font-semibold <?= $throttleStatusClass((string) ($entry['status'] ?? '')) ?>">
                                <?= $e((string) ($entry['status'] ?? 'Estado')) ?>
                            </span>
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-on-surface-subtle">
                            <p>Intentos: <strong class="text-on-surface"><?= (int) ($entry['attempts'] ?? 0) ?></strong></p>
                            <p>Vence en: <strong class="text-on-surface"><?= (int) ($entry['remaining_seconds'] ?? 0) ?>s</strong></p>
                            <p class="col-span-2">Último intento: <strong class="text-on-surface"><?= $e((string) ($entry['last_attempt'] ?? 'N/A')) ?></strong></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
    <div class="border-b border-outline-variant/60 px-5 py-4">
        <h3 class="title-sm text-on-surface">Eventos recientes de seguridad</h3>
        <p class="mt-1 text-sm text-on-surface-subtle">Consolidado de autenticación y eventos sensibles de auditoría.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left">
            <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                <tr>
                    <th class="px-4 py-3 font-semibold">Fecha</th>
                    <th class="px-4 py-3 font-semibold">Severidad</th>
                    <th class="px-4 py-3 font-semibold">Evento</th>
                    <th class="px-4 py-3 font-semibold">Actor</th>
                    <th class="px-4 py-3 font-semibold">IP</th>
                    <th class="px-4 py-3 font-semibold">Fuente</th>
                    <th class="px-4 py-3 font-semibold">Detalle</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/50 text-sm">
                <?php if (empty($security_events)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-on-surface-subtle">No hay eventos de seguridad registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($security_events as $event): ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors">
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((string) ($event['occurred_at'] ?? '')) ?></td>
                            <td class="px-4 py-3.5">
                                <?php $severity = (string) ($event['severity'] ?? 'low'); ?>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $severityClass($severity) ?>">
                                    <?= $e(mb_strtoupper($severity)) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-on-surface"><?= $e((string) ($event['action'] ?? 'security_event')) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((string) ($event['actor'] ?? 'Sistema')) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((string) ($event['ip'] ?? '-')) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted font-mono text-xs"><?= $e((string) ($event['source'] ?? '-')) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted max-w-[380px] truncate" title="<?= $e((string) ($event['details'] ?? '')) ?>"><?= $e((string) ($event['details'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
