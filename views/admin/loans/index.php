<?php
// views/admin/loans/index.php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$statusClass = static function (string $status): string {
    return match ($status) {
        'active' => 'bg-blue-100 text-blue-700',
        'overdue' => 'bg-amber-100 text-amber-700',
        'returned' => 'bg-emerald-100 text-emerald-700',
        'lost' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-700',
    };
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'active' => 'Activo',
        'overdue' => 'Vencido',
        'returned' => 'Devuelto',
        'lost' => 'Perdido',
        default => ucfirst($status),
    };
};

$totalLoans = count($loans);
$visibleLoans = $loans;
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Administracion</p>
            <h1 class="headline-lg text-on-surface">Prestamos</h1>
            <p class="body-md mt-1">Vista de interfaz para gestion de prestamos.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted opacity-70 cursor-not-allowed">
                Exportar
            </button>
            <a href="<?= BASE_URL ?>/admin/loans/create"
               class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity inline-flex items-center gap-2">
                <?= Icons::plus('w-4 h-4') ?> Nuevo préstamo
            </a>
        </div>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Activos</p>
            <p class="mt-2 text-2xl font-bold text-blue-700 font-display"><?= (int) $stats['active'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Vencidos</p>
            <p class="mt-2 text-2xl font-bold text-amber-700 font-display"><?= (int) $stats['overdue'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Devueltos</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 font-display"><?= (int) $stats['returned'] ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Perdidos</p>
            <p class="mt-2 text-2xl font-bold text-red-700 font-display"><?= (int) $stats['lost'] ?></p>
        </article>
    </div>

    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <div class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label for="loan-search" class="label-sm">Buscar</label>
                <input id="loan-search" type="text" placeholder="ISBN, título o código"
                    class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
            </div>
            <div>
                <label for="loan-status" class="label-sm">Estado</label>
                <select id="loan-status" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option>Todos</option>
                    <option>Activo</option>
                    <option>Vencido</option>
                    <option>Devuelto</option>
                    <option>Perdido</option>
                </select>
            </div>
            <div>
                <label for="loan-date" class="label-sm">Periodo</label>
                <select id="loan-date" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option>Ultimos 30 dias</option>
                    <option>Hoy</option>
                    <option>Esta semana</option>
                    <option>Este mes</option>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Codigo</th>
                        <th class="px-4 py-3 font-semibold">Usuario</th>
                        <th class="px-4 py-3 font-semibold">Recurso</th>
                        <th class="px-4 py-3 font-semibold">Prestamo</th>
                        <th class="px-4 py-3 font-semibold">Vence</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50 text-sm">
                    <?php if (empty($visibleLoans)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-on-surface-subtle">No hay prestamos registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($visibleLoans as $loan): ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors">
                                <td class="px-4 py-3.5 font-semibold text-on-surface"><?= $e($loan['id']) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($loan['user']) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e($loan['book']) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((new DateTime($loan['loan_at']))->format('d/m/Y H:i')) ?></td>
                                <td class="px-4 py-3.5 text-on-surface-muted"><?= $e((new DateTime($loan['due_at']))->format('d/m/Y H:i')) ?></td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass($loan['status']) ?>">
                                        <?= $e($statusLabel($loan['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <?php if (in_array((string) ($loan['status'] ?? ''), ['active', 'overdue'], true)): ?>
                                        <button type="button"
                                                class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low inline-flex items-center gap-1 transition-colors js-open-renew-modal"
                                                data-loan-id="<?= (int) ($loan['id'] ?? 0) ?>"
                                                data-loan-book="<?= $e((string) ($loan['book'] ?? 'Recurso')) ?>">
                                            <?= Icons::refresh('w-3.5 h-3.5') ?> Renovar
                                        </button>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/loans/<?= (int) ($loan['id'] ?? 0) ?>/return" class="inline">
                                            <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                            <button type="submit" class="ml-1 rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-on-surface-muted hover:bg-surface-container-low inline-flex items-center gap-1 transition-colors"><?= Icons::returnIcon('w-3.5 h-3.5') ?> Devolver</button>
                                        </form>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/loans/<?= (int) ($loan['id'] ?? 0) ?>/lost" class="inline">
                                            <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">
                                            <button type="submit" class="ml-1 rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 inline-flex items-center gap-1 transition-colors"><?= Icons::alert('w-3.5 h-3.5') ?> Perdido</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs text-on-surface-subtle">Sin acciones</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-outline-variant/60 px-4 py-3 text-sm text-on-surface-muted sm:flex-row sm:items-center sm:justify-between">
            <p>Mostrando <?= $totalLoans > 0 ? '1-' . $totalLoans : '0-0' ?> de <?= (int) $totalLoans ?> prestamos</p>
            <div class="flex items-center gap-1">
                <button type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold opacity-60 cursor-not-allowed inline-flex items-center gap-1"><?= Icons::arrowLeft('w-3.5 h-3.5') ?> Anterior</button>
                <button type="button" class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white">1</button>
                <button type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold opacity-60 cursor-not-allowed inline-flex items-center gap-1">Siguiente <?= Icons::arrowRight('w-3.5 h-3.5') ?></button>
            </div>
        </div>
    </div>

    <div id="renew-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-ambient-lg">
            <h2 class="headline-md text-on-surface">Renovar préstamo</h2>
            <p id="renew-modal-book" class="mt-1 text-sm text-on-surface-muted">Selecciona el tiempo de renovación.</p>

            <form id="renew-modal-form" method="POST" action="<?= BASE_URL ?>/admin/loans/0/renew" class="mt-4 space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= $e((string) \Core\Session::get('_csrf_token', '')) ?>">

                <label class="block">
                    <span class="label-sm">Tiempo de renovación</span>
                    <select name="renewal_hours" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <option value="24">24 horas (1 día)</option>
                        <option value="48">48 horas (2 días)</option>
                        <option value="72" selected>72 horas (3 días)</option>
                        <option value="96">96 horas (4 días)</option>
                        <option value="168">168 horas (7 días)</option>
                    </select>
                </label>

                <div class="flex items-center justify-end gap-2">
                    <button type="button" id="renew-modal-cancel" class="rounded-xl border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low">Cancelar</button>
                    <button type="submit" class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient">Confirmar renovación</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
(() => {
    const modal = document.getElementById('renew-modal');
    const form = document.getElementById('renew-modal-form');
    const title = document.getElementById('renew-modal-book');
    const cancel = document.getElementById('renew-modal-cancel');
    const buttons = document.querySelectorAll('.js-open-renew-modal');

    if (!modal || !form || !title || !cancel || buttons.length === 0) {
        return;
    }

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const loanId = button.getAttribute('data-loan-id') || '0';
            const book = button.getAttribute('data-loan-book') || 'Recurso';
            form.action = '<?= BASE_URL ?>/admin/loans/' + loanId + '/renew';
            title.textContent = 'Recurso: ' + book;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    cancel.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
})();
</script>
