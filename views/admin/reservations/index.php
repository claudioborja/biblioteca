<?php
// views/admin/reservations/index.php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$statusClass = static function (string $status): string {
    return match ($status) {
        'waiting' => 'bg-blue-100 text-blue-700',
        'notified' => 'bg-emerald-100 text-emerald-700',
        'expired' => 'bg-amber-100 text-amber-700',
        'cancelled' => 'bg-red-100 text-red-700',
        'fulfilled' => 'bg-violet-100 text-violet-700',
        default => 'bg-slate-100 text-slate-700',
    };
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'waiting' => 'Pendiente',
        'notified' => 'Lista',
        'expired' => 'Expirada',
        'cancelled' => 'Cancelada',
        'fulfilled' => 'Completada',
        default => ucfirst($status),
    };
};

$totalReservations = count($reservations);
$visibleReservations = array_slice($reservations, 0, 5);
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Administracion</p>
            <h1 class="headline-lg text-on-surface">Reservaciones</h1>
            <p class="body-md mt-1">Vista de interfaz para gestion de reservaciones.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted opacity-70 cursor-not-allowed inline-flex items-center gap-2">
                <?= Icons::download('w-4 h-4') ?>
                Exportar
            </button>
            <a href="<?= BASE_URL ?>/catalog"
               class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient inline-flex items-center gap-2 hover:opacity-90 transition-opacity">
                <?= Icons::plus('w-4 h-4') ?>
                Nueva reservacion
            </a>
        </div>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Pendientes</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 font-display"><?= (int) $stats['pending'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Listas para prestar</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= (int) $stats['ready'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Expiradas</p>
            <p class="mt-2 text-2xl font-bold text-amber-700 font-display"><?= (int) $stats['expired'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Canceladas</p>
            <p class="mt-2 text-2xl font-bold text-red-700 font-display"><?= (int) $stats['cancelled'] ?></p>
        </article>
    </div>

    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <div class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label for="reservation-search" class="label-sm">Buscar</label>
                <input id="reservation-search" type="text" placeholder="Código, usuario o recurso"
                    class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
            </div>
            <div>
                <label for="reservation-status" class="label-sm">Estado</label>
                <select id="reservation-status" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option>Todos</option>
                    <option>Pendiente</option>
                    <option>Lista</option>
                    <option>Expirada</option>
                    <option>Cancelada</option>
                </select>
            </div>
            <div>
                <label for="reservation-priority" class="label-sm">Prioridad</label>
                <select id="reservation-priority" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option>Todas</option>
                    <option>Fila 1</option>
                    <option>Fila 2</option>
                    <option>Fila 3+</option>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">ID</th>
                        <th class="px-4 py-3 font-semibold">Usuario</th>
                        <th class="px-4 py-3 font-semibold">Recurso</th>
                        <th class="px-4 py-3 font-semibold">Solicitud</th>
                        <th class="px-4 py-3 font-semibold">Fila</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50 text-sm">
                    <?php foreach ($visibleReservations as $reservation): ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors">
                            <td class="px-4 py-3.5 font-semibold text-on-surface">#<?= (int) ($reservation['id'] ?? 0) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($reservation['user_name'] ?? 'Usuario') ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($reservation['resource_title'] ?? 'Recurso') ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((new DateTime((string) ($reservation['created_at'] ?? 'now')))->format('d/m/Y H:i')) ?></td>
                            <td class="px-4 py-3.5 text-on-surface-muted">#<?= (int) ($reservation['queue_position'] ?? 0) ?></td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass((string) ($reservation['status'] ?? '')) ?>">
                                    <?= $e($statusLabel((string) ($reservation['status'] ?? ''))) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <?php if (in_array((string) ($reservation['status'] ?? ''), ['waiting', 'notified'], true)): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/admin/reservations/<?= (int) ($reservation['id'] ?? 0) ?>/convert" class="inline">
                                        <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                        <button type="submit" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low inline-flex items-center gap-1 transition-colors"><?= Icons::arrowRight('w-3.5 h-3.5') ?> Prestar</button>
                                    </form>
                                    <form method="POST" action="<?= BASE_URL ?>/admin/reservations/<?= (int) ($reservation['id'] ?? 0) ?>/cancel" class="inline">
                                        <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                        <button type="submit" class="ml-1 rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low inline-flex items-center gap-1 transition-colors"><?= Icons::x('w-3.5 h-3.5') ?> Cancelar</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-on-surface-subtle">Sin acciones</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-outline-variant/60 px-4 py-3 text-sm text-on-surface-muted sm:flex-row sm:items-center sm:justify-between">
            <p>Mostrando <?= $totalReservations > 0 ? '1-' . min(5, $totalReservations) : '0-0' ?> de <?= (int) $totalReservations ?> reservaciones</p>
            <div class="flex items-center gap-1">
                <button type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold opacity-60 cursor-not-allowed inline-flex items-center gap-1"><?= Icons::arrowLeft('w-3.5 h-3.5') ?> Anterior</button>
                <button type="button" class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white">1</button>
                <button type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold opacity-60 cursor-not-allowed inline-flex items-center gap-1">Siguiente <?= Icons::arrowRight('w-3.5 h-3.5') ?></button>
            </div>
        </div>
    </div>
</section>
