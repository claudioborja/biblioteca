<?php
/** @var array<string, int|string> $system_summary */
/** @var array<int, array<string, mixed>> $system_log_files */
/** @var array<int, array<string, mixed>> $system_jobs */
/** @var array<int, array<string, mixed>> $system_recent_logs */

$jobStatusClass = static function (string $status): string {
    return match ($status) {
        'Reciente' => 'bg-emerald-100 text-emerald-700',
        'Desactualizado' => 'bg-amber-100 text-amber-700',
        'Sin actividad reciente' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-700',
    };
};
?>

<div class="mb-5">
    <h2 class="headline-md text-on-surface">Auditoría de Sistema</h2>
    <p class="body-sm mt-1 text-on-surface-subtle">Vista operativa construida desde archivos reales de logs y scripts detectados en el servidor.</p>
</div>

<div class="mb-6 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-semibold text-on-surface">Estado actual del sistema</p>
            <p class="mt-1 text-xs text-on-surface-subtle">Última actualización de auditoría: <?= $e((string) ($system_summary['generated_at'] ?? 'N/A')) ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-700">Reciente</span>
            <span class="inline-flex rounded-full border border-amber-200 bg-amber-100 px-2.5 py-1 font-semibold text-amber-700">Desactualizado</span>
            <span class="inline-flex rounded-full border border-red-200 bg-red-100 px-2.5 py-1 font-semibold text-red-700">Sin actividad reciente</span>
            <span class="inline-flex rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">Sin registro</span>
        </div>
    </div>
</div>

<div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Archivos log</p>
        <p class="mt-2 text-2xl font-bold text-on-surface font-display"><?= (int) ($system_summary['total_log_files'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Peso total</p>
        <p class="mt-2 text-2xl font-bold text-on-surface font-display"><?= $e((string) ($system_summary['total_log_size'] ?? '0 B')) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Actualizados 24h</p>
        <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= (int) ($system_summary['updated_last_24h'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Logs críticos/PHP</p>
        <p class="mt-2 text-2xl font-bold text-amber-700 font-display"><?= (int) ($system_summary['error_like_files'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Tareas monitoreadas</p>
        <p class="mt-2 text-2xl font-bold text-sky-700 font-display"><?= (int) ($system_summary['monitored_jobs'] ?? 0) ?></p>
    </article>
    <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <p class="label-md">Jobs recientes</p>
        <p class="mt-2 text-2xl font-bold text-emerald-700 font-display\"><?= (int) ($system_summary['jobs_recent'] ?? 0) ?></p>
    </article>
</div>

<div class="mb-7 grid gap-6 xl:grid-cols-[1.2fr_1fr]">
    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="border-b border-outline-variant/60 px-5 py-4">
            <h3 class="title-sm text-on-surface">Tareas programadas</h3>
            <p class="mt-1 text-sm text-on-surface-subtle">Scripts detectados automáticamente en bin y el log más cercano encontrado.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Proceso</th>
                        <th class="px-4 py-3 font-semibold">Script</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold">Última actividad</th>
                        <th class="px-4 py-3 font-semibold">Log</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50 text-sm">
                    <?php if (empty($system_jobs)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-on-surface-subtle">No se detectaron scripts en bin para monitorear.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($system_jobs as $job): ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors">
                                <td class="px-4 py-3.5 font-semibold text-on-surface\"><?= $e((string) ($job['label'] ?? 'Proceso')) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted font-mono text-xs\"><?= $e((string) ($job['script'] ?? '-')) ?></td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $jobStatusClass((string) ($job['status'] ?? '')) ?>">
                                        <?= $e((string) ($job['status'] ?? 'Sin estado')) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-muted\"><?= $e((string) ($job['modified_at'] ?? 'Sin registro')) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted">
                                    <p><?= $e((string) ($job['log_name'] ?? 'No encontrado')) ?></p>
                                    <p class="text-xs text-on-surface-subtle\"><?= $e((string) ($job['size_human'] ?? '0 B')) ?></p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="border-b border-outline-variant/60 px-5 py-4">
            <h3 class="title-sm text-on-surface">Actividad reciente</h3>
            <p class="mt-1 text-sm text-on-surface-subtle">Últimas líneas detectadas en los logs más recientes.</p>
        </div>
        <div class="max-h-[520px] overflow-auto px-5 py-4 space-y-3">
            <?php if (empty($system_recent_logs)): ?>
                <div class="rounded-xl border border-dashed border-outline-variant p-4 text-sm text-on-surface-subtle">
                    No se encontraron líneas recientes en los logs monitoreados.
                </div>
            <?php else: ?>
                <?php foreach ($system_recent_logs as $entry): ?>
                    <article class="rounded-xl border border-outline-variant/60 bg-surface-container-lowest p-3">
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="font-semibold text-primary"><?= $e((string) ($entry['file'] ?? 'log')) ?></span>
                            <span class="text-on-surface-subtle"><?= $e((string) ($entry['modified_at'] ?? '')) ?></span>
                        </div>
                        <p class="mt-2 break-words font-mono text-[12px] text-on-surface-muted"><?= $e((string) ($entry['line'] ?? '')) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
    <div class="border-b border-outline-variant/60 px-5 py-4">
        <h3 class="title-sm text-on-surface">Archivos de log detectados</h3>
        <p class="mt-1 text-sm text-on-surface-subtle">Inventario detectado automáticamente en storage/logs.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left">
            <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                <tr>
                    <th class="px-4 py-3 font-semibold">Archivo</th>
                    <th class="px-4 py-3 font-semibold">Categoría</th>
                    <th class="px-4 py-3 font-semibold">Tamaño</th>
                    <th class="px-4 py-3 font-semibold">Última modificación</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/50 text-sm">
                <?php if (empty($system_log_files)): ?>
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-on-surface-subtle">No se encontraron archivos log.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($system_log_files as $logFile): ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors">
                            <td class="px-4 py-3.5 font-mono text-xs text-on-surface"><?= $e((string) ($logFile['name'] ?? '-')) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((string) ($logFile['category'] ?? '-')) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((string) ($logFile['size_human'] ?? '0 B')) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((string) ($logFile['modified_at'] ?? 'Sin fecha')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
