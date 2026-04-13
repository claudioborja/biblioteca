<?php
// views/account/dashboard.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$initials = mb_strtoupper(mb_substr((string) ($auth_user['name'] ?? '?'), 0, 2));
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Buenos dias' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');

$lastLogin = !empty($auth_user['last_login_at'])
    ? (new DateTime((string) $auth_user['last_login_at']))->format('d/m/Y H:i')
    : 'Primer acceso';

$joinedAt = !empty($auth_user['created_at'])
    ? (new DateTime((string) $auth_user['created_at']))->format('d/m/Y')
    : '—';

$statusLabel = (($auth_user['status'] ?? '') === 'active') ? 'Activa' : 'Inactiva';
$statusClass = (($auth_user['status'] ?? '') === 'active')
    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
    : 'bg-red-50 text-red-700 border-red-200';

$pendingFinesAmount = (float) ($stats['pending_fines_amount'] ?? 0.0);
$moneyLabel = '$' . number_format($pendingFinesAmount, 2, '.', ',');
?>

<section class="p-6 lg:p-8 space-y-6">
    <header class="rounded-3xl border border-outline-variant/60 bg-white shadow-ambient overflow-hidden">
        <div class="bg-[radial-gradient(circle_at_top_right,_rgba(10,37,64,0.16),_transparent_55%),linear-gradient(120deg,_#0f2f52_0%,_#0a2540_55%,_#163560_100%)] p-6 sm:p-7 lg:p-8 text-on-primary">
            <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0">
                    <p class="text-xs uppercase tracking-[0.12em] text-on-primary/70">Portal del socio</p>
                    <h1 class="headline-lg mt-1 text-white"><?= $e($greeting) ?>, <?= $e($auth_user['name'] ?? '') ?></h1>
                    <p class="mt-2 text-sm text-on-primary/85 max-w-2xl">
                        Este panel muestra solo lo esencial: tus prestamos, reservas y multas activas.
                    </p>
                </div>
                <div class="flex items-center gap-3 rounded-2xl border border-white/25 bg-white/10 px-4 py-3 backdrop-blur-sm">
                    <div class="h-11 w-11 shrink-0 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold tracking-wide">
                        <?= $e($initials) ?>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-white"><?= $e($auth_user['email'] ?? '') ?></p>
                        <p class="text-xs text-on-primary/75">Ultimo acceso: <?= $e($lastLogin) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Prestamos activos</p>
            <p class="mt-2 text-3xl font-display font-bold text-primary"><?= (int) ($stats['active_loans'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Vencidos</p>
            <p class="mt-2 text-3xl font-display font-bold text-red-700"><?= (int) ($stats['overdue_loans'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Reservas activas</p>
            <p class="mt-2 text-3xl font-display font-bold text-violet-700"><?= (int) ($stats['active_reservations'] ?? 0) ?></p>
        </article>
        <article class="rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient">
            <p class="label-md">Saldo pendiente</p>
            <p class="mt-2 text-3xl font-display font-bold text-amber-700"><?= $e($moneyLabel) ?></p>
            <p class="mt-1 text-xs text-on-surface-subtle"><?= (int) ($stats['open_fines'] ?? 0) ?> multa(s) abierta(s)</p>
        </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="xl:col-span-2 rounded-2xl border border-outline-variant/60 bg-white shadow-ambient p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h2 class="headline-md text-on-surface">Tus ultimos prestamos</h2>
                <a href="<?= BASE_URL ?>/account/loans" class="text-xs font-semibold text-primary hover:text-primary-light transition-colors">Ver detalle</a>
            </div>

            <?php if (empty($recentLoans)): ?>
                <div class="rounded-xl border border-dashed border-outline-variant/70 bg-surface-container-low px-4 py-10 text-center">
                    <p class="text-sm text-on-surface-subtle">Aun no registras prestamos en tu cuenta.</p>
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($recentLoans as $loan):
                        $loanStatus = (string) ($loan['status'] ?? '');
                        $badgeClass = match ($loanStatus) {
                            'active' => 'bg-blue-100 text-blue-700',
                            'overdue' => 'bg-red-100 text-red-700',
                            'returned' => 'bg-emerald-100 text-emerald-700',
                            default => 'bg-slate-100 text-slate-700',
                        };
                        $badgeLabel = match ($loanStatus) {
                            'active' => 'Activo',
                            'overdue' => 'Vencido',
                            'returned' => 'Devuelto',
                            default => ucfirst($loanStatus),
                        };

                        $requestedAt = !empty($loan['created_at'])
                            ? (new DateTime((string) $loan['created_at']))->format('d/m/Y')
                            : '—';
                        $dueAt = !empty($loan['due_at'])
                            ? (new DateTime((string) $loan['due_at']))->format('d/m/Y')
                            : '—';
                    ?>
                        <article class="rounded-xl border border-outline-variant/50 p-4 hover:bg-surface-container-low/70 transition-colors">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-on-surface"><?= $e($loan['title'] ?? 'Recurso') ?></p>
                                    <p class="truncate text-xs text-on-surface-subtle mt-0.5"><?= $e($loan['authors'] ?? '') ?></p>
                                </div>
                                <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold <?= $badgeClass ?>">
                                    <?= $e($badgeLabel) ?>
                                </span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-on-surface-subtle">
                                <span>Solicitado: <?= $e($requestedAt) ?></span>
                                <span>Vence: <?= $e($dueAt) ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-outline-variant/60 bg-white shadow-ambient p-5">
                <h2 class="title-sm text-on-surface">Datos de cuenta</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-on-surface-subtle">Numero de usuario</dt>
                        <dd class="font-semibold text-on-surface"><?= $e($auth_user['user_number'] ?? '—') ?></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-on-surface-subtle">Miembro desde</dt>
                        <dd class="font-semibold text-on-surface"><?= $e($joinedAt) ?></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-on-surface-subtle">Estado</dt>
                        <dd>
                            <span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-semibold <?= $statusClass ?>">
                                <?= $e($statusLabel) ?>
                            </span>
                        </dd>
                    </div>
                </dl>
            </section>
        </aside>
    </div>
</section>
