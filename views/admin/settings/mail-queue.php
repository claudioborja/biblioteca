<?php
// views/admin/settings/mail-queue.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

/** @var array<string,int> $queue_stats */
/** @var list<array<string,mixed>> $queue_items */
/** @var bool $cron_configured */
/** @var string $cron_output */
/** @var bool $exec_available */
/** @var string $csrf_token */

$stats        = is_array($queue_stats ?? null) ? $queue_stats : [];
$items        = is_array($queue_items ?? null) ? $queue_items : [];
$cronOk       = (bool) ($cron_configured ?? false);
$cronRaw      = trim((string) ($cron_output ?? ''));
$execOk       = (bool) ($exec_available ?? false);
$csrfToken    = (string) ($csrf_token ?? '');
$workerCmd    = 'php ' . BASE_PATH . '/bin/mail_worker.php';
$cronEntry    = '*/5 * * * * ' . PHP_BINARY . ' ' . BASE_PATH . '/bin/mail_worker.php >> ' . BASE_PATH . '/storage/logs/mail_worker_cron.log 2>&1';

$statusBadge = static function (string $status): string {
    return match ($status) {
        'sent'    => 'bg-emerald-100 text-emerald-700',
        'failed'  => 'bg-red-100 text-red-700',
        'pending' => 'bg-amber-100 text-amber-700',
        default   => 'bg-slate-100 text-slate-600',
    };
};
$statusLabel = static function (string $status): string {
    return match ($status) {
        'sent'    => 'Enviado',
        'failed'  => 'Fallido',
        'pending' => 'Pendiente',
        default   => ucfirst($status),
    };
};
$attemptTone = static function (int $attempts, string $status): string {
    if ($status === 'failed') {
        return $attempts >= 3
            ? 'bg-red-100 text-red-700 border border-red-200'
            : 'bg-rose-100 text-rose-700 border border-rose-200';
    }

    if ($status === 'pending') {
        return $attempts > 0
            ? 'bg-amber-100 text-amber-700 border border-amber-200'
            : 'bg-slate-100 text-slate-700 border border-slate-200';
    }

    return 'bg-emerald-100 text-emerald-700 border border-emerald-200';
};
?>

<section class="p-4 lg:p-6">

    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-3 shadow-ambient lg:p-4">
        <div class="mb-3 border-b border-outline-variant/50 pb-2">
            <h1 class="inline-flex items-center gap-2 text-sm font-semibold text-on-surface">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <?= \Helpers\Icons::settings('h-3.5 w-3.5') ?>
                </span>
                <span class="text-on-surface-subtle">Infraestructura de correo</span>
                <span class="text-on-surface-subtle">·</span>
                <span>Cola de correo</span>
            </h1>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-on-surface-subtle">Estado de envíos, reintentos y configuración del worker.</p>
            <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>/admin/settings"
                   class="flex items-center gap-1.5 rounded-xl border border-outline-variant px-3 py-1.5 text-sm font-semibold text-on-surface hover:bg-surface-container transition-colors">
                    <?= \Helpers\Icons::settings('h-4 w-4') ?>
                    Configuración
                </a>
                <?php if ($execOk): ?>
                <button id="btn-run-worker"
                        class="flex items-center gap-1.5 rounded-xl gradient-scholar px-3 py-1.5 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity">
                    <?= \Helpers\Icons::arrowRight('h-4 w-4') ?>
                    Ejecutar ahora
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cron status banner -->
    <?php if (!$cronOk): ?>
    <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-ambient">
        <div class="flex items-start gap-3">
            <i data-lucide="triangle-alert" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600"></i>
            <div class="flex-1">
                <p class="text-sm font-semibold text-amber-800">El worker de correo no está configurado como tarea programada</p>
                <p class="mt-1 text-xs text-amber-700">
                    Los correos en cola no se enviarán automáticamente. Debes configurar un cron o ejecutar el worker manualmente.
                </p>
                <details class="mt-3 rounded-xl border border-amber-200 bg-white">
                    <summary class="flex cursor-pointer items-center gap-2 px-3 py-2 text-xs font-semibold text-amber-800">
                        <i data-lucide="info" class="h-4 w-4"></i>
                        Ver guía de configuración
                    </summary>
                    <div class="space-y-2 border-t border-amber-100 px-3 py-3">
                        <p class="text-xs text-amber-700">1. Abre la terminal del servidor y ejecuta <code class="rounded bg-amber-100 px-1 font-mono">crontab -e</code></p>
                        <p class="text-xs text-amber-700">2. Agrega esta línea al final:</p>
                        <div class="mt-1 flex items-center gap-2 rounded-xl border border-amber-200 bg-white px-3 py-2">
                            <code id="cron-entry-text" class="flex-1 font-mono text-xs text-slate-700 break-all"><?= $e($cronEntry) ?></code>
                            <button type="button" id="btn-copy-cron"
                                    class="shrink-0 rounded-lg border border-outline-variant p-1.5 text-on-surface-muted hover:bg-surface-container transition-colors"
                                    title="Copiar">
                                <i data-lucide="copy" class="h-3.5 w-3.5"></i>
                            </button>
                        </div>
                        <p class="text-xs text-amber-700">3. Guarda y sal del editor. El worker se ejecutará cada 5 minutos.</p>
                        <p class="text-xs text-amber-700">
                            También puedes ejecutar el worker una vez manualmente:
                            <code class="rounded bg-amber-100 px-1 font-mono"><?= $e($workerCmd) ?></code>
                        </p>
                    </div>
                </details>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-3 shadow-ambient">
        <div class="flex items-center gap-2.5">
            <i data-lucide="circle-check" class="h-4 w-4 shrink-0 text-emerald-600"></i>
            <p class="text-sm font-semibold text-emerald-800">Worker configurado — los correos se envían automáticamente cada 5 minutos.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats cards -->
    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <div class="flex items-center justify-between">
                <p class="label-md text-on-surface-subtle">Total en cola</p>
                <i data-lucide="inbox" class="h-4 w-4 text-on-surface-subtle"></i>
            </div>
            <p class="mt-2 font-display text-2xl font-bold text-on-surface"><?= (int) ($stats['total'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-ambient">
            <div class="flex items-center justify-between">
                <p class="label-md text-amber-700">Pendientes</p>
                <i data-lucide="clock" class="h-4 w-4 text-amber-500"></i>
            </div>
            <p class="mt-2 font-display text-2xl font-bold text-amber-700"><?= (int) ($stats['pending'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-ambient">
            <div class="flex items-center justify-between">
                <p class="label-md text-emerald-700">Enviados</p>
                <i data-lucide="circle-check" class="h-4 w-4 text-emerald-500"></i>
            </div>
            <p class="mt-2 font-display text-2xl font-bold text-emerald-700"><?= (int) ($stats['sent'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border <?= (int)($stats['failed'] ?? 0) > 0 ? 'border-red-200 bg-red-50' : 'border-outline-variant/60 bg-white' ?> p-5 shadow-ambient">
            <div class="flex items-center justify-between">
                <p class="label-md <?= (int)($stats['failed'] ?? 0) > 0 ? 'text-red-700' : 'text-on-surface-subtle' ?>">Fallidos</p>
                <i data-lucide="circle-x" class="h-4 w-4 <?= (int)($stats['failed'] ?? 0) > 0 ? 'text-red-500' : 'text-on-surface-subtle' ?>"></i>
            </div>
            <p class="mt-2 font-display text-2xl font-bold <?= (int)($stats['failed'] ?? 0) > 0 ? 'text-red-700' : 'text-on-surface' ?>"><?= (int) ($stats['failed'] ?? 0) ?></p>
            <?php if ((int)($stats['failed'] ?? 0) > 0): ?>
            <button id="btn-retry-all"
                    class="mt-2 flex items-center gap-1 rounded-lg border border-red-300 bg-white px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-50 transition-colors">
                <i data-lucide="refresh-cw" class="h-3 w-3"></i>
                Reintentar todos
            </button>
            <?php endif; ?>
        </article>
    </div>

    <!-- Worker result toast -->
    <div id="worker-toast"
         class="mb-4 hidden rounded-xl border px-4 py-3 text-sm font-semibold"
         role="alert" aria-live="polite"></div>

    <!-- Queue table -->
    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="border-b border-outline-variant/50 px-4 py-3.5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-on-surface">
                        <i data-lucide="list" class="h-4 w-4 text-primary"></i>
                        Últimos 60 correos
                    </h2>
                    <p class="mt-1 text-xs text-on-surface-muted">
                        Filtra por estado, busca por asunto/correo y prioriza errores para depuración.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button"
                            class="status-chip rounded-full border border-outline-variant px-3 py-1 text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors"
                            data-chip-status="all"
                            aria-pressed="true">
                        Todos
                    </button>
                    <button type="button"
                            class="status-chip rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition-colors"
                            data-chip-status="pending"
                            aria-pressed="false">
                        Pendientes
                    </button>
                    <button type="button"
                            class="status-chip rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition-colors"
                            data-chip-status="sent"
                            aria-pressed="false">
                        Enviados
                    </button>
                    <button type="button"
                            class="status-chip rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 hover:bg-red-100 transition-colors"
                            data-chip-status="failed"
                            aria-pressed="false">
                        Fallidos
                    </button>
                </div>
            </div>

            <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <label class="relative block">
                    <span class="sr-only">Buscar en la cola</span>
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-on-surface-subtle"></i>
                    <input type="search"
                           id="queue-search"
                           placeholder="Buscar por correo, nombre o asunto…"
                           class="w-full rounded-xl border border-outline-variant bg-white py-2 pl-9 pr-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" />
                </label>

                <label class="relative block">
                    <span class="sr-only">Filtrar por estado</span>
                    <select id="queue-status-filter"
                            class="w-full appearance-none rounded-xl border border-outline-variant bg-white px-3 py-2 pr-8 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <option value="all">Todos los estados</option>
                        <option value="pending">Pendiente</option>
                        <option value="failed">Fallido</option>
                        <option value="sent">Enviado</option>
                    </select>
                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-on-surface-subtle"></i>
                </label>

                <label class="flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface">
                    <input id="queue-errors-only" type="checkbox" class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary/30" />
                    Solo con error
                </label>

                <div class="flex items-center justify-between rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2 text-xs">
                    <span id="queue-filter-summary" class="font-semibold text-on-surface">Mostrando 0 de 0</span>
                    <button type="button"
                            id="queue-reset-filters"
                            class="rounded-lg border border-outline-variant bg-white px-2 py-1 font-semibold text-on-surface-muted hover:bg-surface-container transition-colors">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <div class="mt-2 flex items-center justify-between gap-2">
                <p class="text-[11px] text-on-surface-subtle">
                    Actualizado: <span id="queue-updated-at">justo ahora</span>
                </p>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-clear-processed"
                            class="flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-100 transition-colors">
                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                        Limpiar cola
                    </button>
                    <button type="button" onclick="window.location.reload()"
                            class="flex items-center gap-1.5 rounded-lg border border-outline-variant px-2.5 py-1 text-xs font-semibold text-on-surface-muted hover:bg-surface-container transition-colors">
                        <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                        Actualizar
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($items)): ?>
        <div class="flex flex-col items-center justify-center gap-2 py-16 text-center">
            <i data-lucide="mail-check" class="h-10 w-10 text-on-surface-subtle opacity-40"></i>
            <p class="text-sm font-semibold text-on-surface-subtle">No hay correos en la cola</p>
            <p class="text-xs text-on-surface-muted">Los correos aparecerán aquí cuando el sistema los genere.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">#</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Destinatario</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Asunto</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Intentos</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Programado</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Restante</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Enviado</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Error</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40" id="queue-tbody">
                    <?php foreach ($items as $item):
                        $status  = (string) ($item['status'] ?? '');
                        $itemId  = (int) ($item['id'] ?? 0);
                        $toName  = (string) ($item['to_name'] ?? '');
                        $toEmail = (string) ($item['to_email'] ?? '');
                        $subject = (string) ($item['subject'] ?? '');
                        $errMsg  = trim((string) ($item['error_message'] ?? ''));
                        $attempts = (int) ($item['attempts'] ?? 0);
                        $scheduledTs = strtotime((string) ($item['scheduled_at'] ?? ''));
                        $searchText = mb_strtolower(trim($toName . ' ' . $toEmail . ' ' . $subject . ' ' . $errMsg));
                    ?>
                    <tr class="hover:bg-surface-container/40 transition-colors"
                        id="row-<?= $itemId ?>"
                        data-row-status="<?= $e($status) ?>"
                        data-row-has-error="<?= $errMsg !== '' ? '1' : '0' ?>"
                        data-row-search="<?= $e($searchText) ?>">
                        <td class="px-4 py-3 font-mono text-xs text-on-surface-muted"><?= $itemId ?></td>
                        <td class="px-4 py-3">
                            <p class="text-xs font-semibold text-on-surface"><?= $e($toName) ?></p>
                            <p class="text-[11px] text-on-surface-muted"><?= $e($toEmail) ?></p>
                        </td>
                        <td class="max-w-[200px] px-4 py-3 text-xs text-on-surface truncate" title="<?= $e($subject) ?>">
                            <?= $e(mb_strimwidth($subject, 0, 55, '…')) ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold <?= $statusBadge($status) ?>">
                                <?= $statusLabel($status) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex min-w-9 items-center justify-center rounded-full px-2 py-0.5 text-xs font-semibold <?= $attemptTone($attempts, $status) ?>">
                                <?= $attempts ?>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-[11px] text-on-surface-muted">
                            <?= $e($item['scheduled_at'] ?? '—') ?>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-[11px]">
                            <span class="js-time-remaining <?= $status === 'pending' ? 'text-amber-700 font-semibold' : 'text-on-surface-subtle' ?>"
                                  data-status="<?= $e($status) ?>"
                                  data-scheduled-ts="<?= $scheduledTs !== false ? (int) $scheduledTs : 0 ?>">
                                <?= $status === 'pending' ? 'Calculando…' : '—' ?>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-[11px] text-on-surface-muted">
                            <?= $item['sent_at'] ? $e($item['sent_at']) : '<span class="text-on-surface-subtle">—</span>' ?>
                        </td>
                        <td class="max-w-[180px] px-4 py-3">
                            <?php if ($errMsg !== ''): ?>
                            <details class="group max-w-[200px]">
                                <summary class="cursor-pointer list-none text-[11px] font-semibold text-red-600 hover:text-red-700">
                                    <?= $e(mb_strimwidth($errMsg, 0, 60, '…')) ?>
                                </summary>
                                <p class="mt-1 rounded-lg border border-red-200 bg-red-50 p-2 text-[11px] text-red-700 break-words">
                                    <?= $e($errMsg) ?>
                                </p>
                            </details>
                            <?php else: ?>
                            <span class="text-[11px] text-on-surface-subtle">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col items-start gap-1.5">
                                <button type="button"
                                        class="btn-preview-one flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-700 hover:bg-blue-100 transition-colors"
                                        data-id="<?= $itemId ?>">
                                    <i data-lucide="eye" class="h-3 w-3"></i>
                                    Ver correo
                                </button>
                                <?php if (in_array($status, ['failed', 'pending'], true)): ?>
                                <button type="button"
                                        class="btn-retry-one flex items-center gap-1 rounded-lg border border-outline-variant px-2 py-1 text-[11px] font-semibold text-on-surface hover:bg-surface-container transition-colors"
                                        data-id="<?= $itemId ?>">
                                    <i data-lucide="refresh-cw" class="h-3 w-3"></i>
                                    Reintentar
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Cron setup guide card -->
    <div class="mt-5 rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-on-surface">
            <i data-lucide="terminal" class="h-4 w-4 text-primary"></i>
            Referencia de configuración del worker
        </h3>
        <div class="space-y-3 text-xs text-on-surface-subtle">
            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-outline-variant/50 bg-surface-container-low p-3">
                    <p class="mb-1.5 font-semibold text-on-surface">Cron (recomendado)</p>
                    <p class="mb-1 text-on-surface-muted">Ejecuta el worker automáticamente cada 5 minutos:</p>
                    <div class="flex items-center gap-2 rounded-lg border border-outline-variant bg-white px-2.5 py-1.5">
                        <code class="flex-1 font-mono text-[11px] text-slate-700 break-all"><?= $e($cronEntry) ?></code>
                    </div>
                    <p class="mt-1.5 text-on-surface-muted">Edita el crontab con: <code class="rounded bg-surface-container px-1 font-mono">crontab -e</code></p>
                </div>
                <div class="rounded-xl border border-outline-variant/50 bg-surface-container-low p-3">
                    <p class="mb-1.5 font-semibold text-on-surface">Ejecución manual</p>
                    <p class="mb-1 text-on-surface-muted">Para enviar los correos pendientes de inmediato:</p>
                    <div class="rounded-lg border border-outline-variant bg-white px-2.5 py-1.5">
                        <code class="font-mono text-[11px] text-slate-700"><?= $e($workerCmd) ?></code>
                    </div>
                    <p class="mt-1.5 text-on-surface-muted">Ejecuta esto desde la raíz del proyecto en el servidor.</p>
                </div>
            </div>
            <div class="rounded-xl border border-outline-variant/50 bg-surface-container-low p-3">
                <p class="mb-1.5 font-semibold text-on-surface">Log del worker</p>
                <p class="text-on-surface-muted">
                    El cron redirige la salida a
                    <code class="rounded bg-surface-container px-1 font-mono"><?= $e(BASE_PATH . '/storage/logs/mail_worker_cron.log') ?></code>.
                    Revisa ese archivo si los correos no llegan — encontrarás errores SMTP detallados.
                </p>
                <p class="mt-1.5 text-on-surface-muted">
                    Para ver en tiempo real:
                    <code class="rounded bg-surface-container px-1 font-mono">tail -f <?= $e(BASE_PATH . '/storage/logs/mail_worker_cron.log') ?></code>
                </p>
            </div>
        </div>
    </div>
    <?php if ($cronRaw !== ''): ?>
    <div class="mt-5 rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
        <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-on-surface">
            <i data-lucide="clipboard-list" class="h-4 w-4 text-primary"></i>
            Crontab detectado para este usuario
        </h3>
        <p class="mb-2 text-xs text-on-surface-subtle">
            Salida parcial de <code class="rounded bg-surface-container px-1 font-mono">crontab -l</code> para verificar que exista la tarea del worker.
        </p>
        <pre class="max-h-48 overflow-auto rounded-xl border border-outline-variant bg-surface-container-low p-3 text-[11px] text-on-surface-muted"><?= $e($cronRaw) ?></pre>
    </div>
    <?php endif; ?>

</section>

<!-- Email preview modal -->
<div id="mail-preview-modal" class="fixed inset-0 z-[60] hidden">
    <div id="mail-preview-backdrop" class="absolute inset-0 bg-slate-900/55"></div>
    <div class="relative z-[61] mx-auto mt-6 w-[96%] max-w-5xl rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="flex items-center justify-between border-b border-outline-variant/60 px-4 py-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-on-surface-subtle">Vista previa del correo</p>
                <h3 id="mail-preview-subject" class="text-sm font-semibold text-on-surface">Asunto</h3>
                <p id="mail-preview-meta" class="text-[11px] text-on-surface-muted"></p>
            </div>
            <button type="button" id="btn-close-mail-preview"
                    class="rounded-lg border border-outline-variant px-2 py-1 text-xs font-semibold text-on-surface-muted hover:bg-surface-container transition-colors">
                Cerrar
            </button>
        </div>
        <div class="max-h-[78vh] overflow-auto p-4">
            <iframe id="mail-preview-frame" class="hidden h-[68vh] w-full rounded-xl border border-outline-variant bg-white" sandbox=""></iframe>
            <pre id="mail-preview-text" class="hidden whitespace-pre-wrap rounded-xl border border-outline-variant bg-surface-container-low p-3 text-xs text-on-surface"></pre>
        </div>
    </div>
</div>

<script>
(function () {
    const CSRF    = <?= json_encode($csrfToken) ?>;
    const actionPath = `${window.location.pathname.replace(/\/+$/, '')}/action`;
    const ACTION  = `${window.location.origin}${actionPath}`;

    const toast   = document.getElementById('worker-toast');
    const tbody   = document.getElementById('queue-tbody');
    const rows    = tbody ? Array.from(tbody.querySelectorAll('tr[id^="row-"]')) : [];
    const statusFilter = document.getElementById('queue-status-filter');
    const searchInput = document.getElementById('queue-search');
    const errorsOnly = document.getElementById('queue-errors-only');
    const summary = document.getElementById('queue-filter-summary');
    const resetFilters = document.getElementById('queue-reset-filters');
    const updatedAt = document.getElementById('queue-updated-at');
    const statusChips = Array.from(document.querySelectorAll('.status-chip'));
    const previewModal = document.getElementById('mail-preview-modal');
    const previewBackdrop = document.getElementById('mail-preview-backdrop');
    const previewCloseBtn = document.getElementById('btn-close-mail-preview');
    const previewSubject = document.getElementById('mail-preview-subject');
    const previewMeta = document.getElementById('mail-preview-meta');
    const previewFrame = document.getElementById('mail-preview-frame');
    const previewText = document.getElementById('mail-preview-text');

    function showToast(msg, ok) {
        if (!toast) return;
        toast.textContent = msg;
        toast.className = 'mb-4 rounded-xl border px-4 py-3 text-sm font-semibold '
            + (ok ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                   : 'border-red-200 bg-red-50 text-red-800');
        toast.classList.remove('hidden');
        clearTimeout(toast._hide);
        toast._hide = setTimeout(() => toast.classList.add('hidden'), 6000);
    }

    async function doAction(payload) {
        try {
            const fd = new FormData();
            fd.append('_csrf_token', CSRF);
            Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
            const res  = await fetch(ACTION, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            showToast(data.message ?? (data.ok ? 'Listo.' : 'Error.'), data.ok === true);
            if (data.ok) setTimeout(() => window.location.reload(), 1800);
        } catch (err) {
            showToast('Error de red: ' + err.message, false);
        }
    }

    const closePreview = () => {
        if (!previewModal) return;
        previewModal.classList.add('hidden');
        if (previewFrame) {
            previewFrame.srcdoc = '';
            previewFrame.classList.add('hidden');
        }
        if (previewText) {
            previewText.textContent = '';
            previewText.classList.add('hidden');
        }
    };

    const openPreview = async (id) => {
        try {
            const fd = new FormData();
            fd.append('_csrf_token', CSRF);
            fd.append('action', 'preview_one');
            fd.append('id', String(id));

            const res = await fetch(ACTION, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (!data.ok || !data.item) {
                showToast(data.message ?? 'No se pudo cargar el correo.', false);
                return;
            }

            const item = data.item;
            const subject = (item.subject || '').toString();
            const toName = (item.to_name || '').toString();
            const toEmail = (item.to_email || '').toString();
            const status = (item.status || '').toString();
            const sentAt = (item.sent_at || '').toString();
            const createdAt = (item.created_at || '').toString();
            const bodyHtml = (item.body_html || '').toString().trim();
            const bodyText = (item.body_text || '').toString().trim();

            if (previewSubject) {
                previewSubject.textContent = subject !== '' ? subject : '(Sin asunto)';
            }
            if (previewMeta) {
                const sentLabel = sentAt !== '' ? `Enviado: ${sentAt}` : `Creado: ${createdAt}`;
                previewMeta.textContent = `Para: ${toName} <${toEmail}> · Estado: ${status} · ${sentLabel}`;
            }

            const hasHtmlTag = /<html[\s>]/i.test(bodyHtml);
            const htmlDoc = hasHtmlTag
                ? bodyHtml
                : `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:16px;background:#fff;color:#111;font-family:Arial,sans-serif;">${bodyHtml}</body></html>`;

            if (bodyHtml !== '') {
                if (previewFrame) {
                    previewFrame.srcdoc = htmlDoc;
                    previewFrame.classList.remove('hidden');
                }
                if (previewText) {
                    previewText.classList.add('hidden');
                    previewText.textContent = '';
                }
            } else {
                if (previewFrame) {
                    previewFrame.classList.add('hidden');
                    previewFrame.srcdoc = '';
                }
                if (previewText) {
                    previewText.textContent = bodyText !== '' ? bodyText : '(Sin contenido para mostrar)';
                    previewText.classList.remove('hidden');
                }
            }

            if (previewModal) {
                previewModal.classList.remove('hidden');
            }
        } catch (err) {
            showToast('Error al cargar la vista previa: ' + err.message, false);
        }
    };

    function formatUpdatedAt() {
        if (!updatedAt) return;
        updatedAt.textContent = new Date().toLocaleString('es-EC', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    }

    function setActiveChip(value) {
        statusChips.forEach((chip) => {
            const active = chip.dataset.chipStatus === value;
            chip.setAttribute('aria-pressed', active ? 'true' : 'false');
            chip.classList.toggle('ring-2', active);
            chip.classList.toggle('ring-primary/30', active);
        });
    }

    function normalizeText(value) {
        return (value || '').toString().toLowerCase().trim();
    }

    function applyFilters() {
        if (!rows.length) return;

        const q = normalizeText(searchInput?.value || '');
        const status = normalizeText(statusFilter?.value || 'all');
        const onlyErrors = Boolean(errorsOnly?.checked);

        let visible = 0;
        rows.forEach((row) => {
            const rowStatus = normalizeText(row.dataset.rowStatus || '');
            const rowSearch = normalizeText(row.dataset.rowSearch || '');
            const rowHasError = row.dataset.rowHasError === '1';

            const okStatus = status === 'all' || rowStatus === status;
            const okError = !onlyErrors || rowHasError;
            const okSearch = q === '' || rowSearch.includes(q);
            const show = okStatus && okError && okSearch;

            row.classList.toggle('hidden', !show);
            if (show) visible++;
        });

        if (summary) {
            summary.textContent = `Mostrando ${visible} de ${rows.length}`;
        }
    }

    // Run worker
    const btnRun = document.getElementById('btn-run-worker');
    if (btnRun) {
        btnRun.addEventListener('click', () => {
                btnRun.disabled = true;
                btnRun.innerHTML = '<i data-lucide="loader-circle" class="h-4 w-4 animate-spin"></i> Ejecutando…';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                doAction({ action: 'run_worker' }).finally(() => {
                    btnRun.disabled = false;
                    btnRun.innerHTML = '<i data-lucide="play" class="h-4 w-4"></i> Ejecutar ahora';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
        }

    // Retry all failed
    const btnRetryAll = document.getElementById('btn-retry-all');
    if (btnRetryAll) {
        btnRetryAll.addEventListener('click', () => doAction({ action: 'retry_all_failed' }));
    }

    // Clear processed queue entries
    const btnClearProcessed = document.getElementById('btn-clear-processed');
    if (btnClearProcessed) {
        btnClearProcessed.addEventListener('click', () => {
            const ok = window.confirm('¿Limpiar correos enviados y fallidos de la cola? Esta acción no se puede deshacer.');
            if (!ok) {
                return;
            }
            doAction({ action: 'clear_processed' });
        });
    }

    // Copy cron entry
    const btnCopy = document.getElementById('btn-copy-cron');
    if (btnCopy) {
        btnCopy.addEventListener('click', () => {
            const text = document.getElementById('cron-entry-text')?.textContent ?? '';
            navigator.clipboard.writeText(text).then(() => {
                btnCopy.title = '¡Copiado!';
                setTimeout(() => { btnCopy.title = 'Copiar'; }, 2000);
            });
        });
    }

    // Retry single
    document.querySelectorAll('.btn-retry-one').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            doAction({ action: 'retry_one', id });
        });
    });

    // Preview email
    document.querySelectorAll('.btn-preview-one').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = Number(btn.dataset.id || 0);
            if (id > 0) {
                openPreview(id);
            }
        });
    });
    if (previewCloseBtn) previewCloseBtn.addEventListener('click', closePreview);
    if (previewBackdrop) previewBackdrop.addEventListener('click', closePreview);
    document.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape') closePreview();
    });

    // Live countdown: time left until the worker cycle that can process each pending email
    const remainingEls = Array.from(document.querySelectorAll('.js-time-remaining'));
    const WORKER_INTERVAL_SECONDS = 5 * 60;

    const formatClock = (totalSeconds) => {
        const sec = Math.max(0, Math.floor(totalSeconds));
        const days = Math.floor(sec / 86400);
        const hours = Math.floor((sec % 86400) / 3600);
        const mins = Math.floor((sec % 3600) / 60);
        const secs = sec % 60;
        const base = `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        return days > 0 ? `${String(days).padStart(2, '0')}:${base}` : base;
    };

    const nextWorkerRunTs = (fromTs, strict = false) => {
        const k = Math.floor(fromTs / WORKER_INTERVAL_SECONDS);
        const exact = k * WORKER_INTERVAL_SECONDS === fromTs;
        if (!strict && exact) return fromTs;
        return (k + 1) * WORKER_INTERVAL_SECONDS;
    };

    const refreshRemaining = () => {
        const nowTs = Math.floor(Date.now() / 1000);
        remainingEls.forEach((el) => {
            const status = (el.dataset.status || '').toLowerCase();
            const scheduledTs = Number(el.dataset.scheduledTs || 0);

            if (status !== 'pending') {
                el.textContent = '—';
                return;
            }
            if (!Number.isFinite(scheduledTs) || scheduledTs <= 0) {
                el.textContent = '—';
                return;
            }

            // If scheduled time is in the future, show time to the first worker tick
            // at/after scheduled_at. If already due, show time to next worker tick.
            const targetRunTs = scheduledTs > nowTs
                ? nextWorkerRunTs(scheduledTs, false)
                : nextWorkerRunTs(nowTs, true);
            el.textContent = formatClock(targetRunTs - nowTs);
        });
    };

    refreshRemaining();
    if (remainingEls.length > 0) {
        setInterval(refreshRemaining, 1000);
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            setActiveChip(statusFilter.value || 'all');
            applyFilters();
        });
    }
    if (errorsOnly) errorsOnly.addEventListener('change', applyFilters);
    if (resetFilters) {
        resetFilters.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (statusFilter) statusFilter.value = 'all';
            if (errorsOnly) errorsOnly.checked = false;
            setActiveChip('all');
            applyFilters();
        });
    }

    statusChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            const value = chip.dataset.chipStatus || 'all';
            if (statusFilter) statusFilter.value = value;
            setActiveChip(value);
            applyFilters();
        });
    });

    formatUpdatedAt();
    setActiveChip('all');
    applyFilters();
})();
</script>
