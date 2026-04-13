<?php
// views/account/profile.php
declare(strict_types=1);

$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$roleLabel = match ((string) ($user['role'] ?? '')) {
    'admin'     => 'Administrador',
    'librarian' => 'Bibliotecario',
    'teacher'   => 'Docente',
    default     => 'Socio',
};

$typeLabel = match ((string) ($user['user_type'] ?? '')) {
    'staff'    => 'Personal',
    'teacher'  => 'Docente',
    'external' => 'Externo',
    default    => 'Estudiante',
};

$statusClass = match ((string) ($user['status'] ?? '')) {
    'active'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'suspended' => 'bg-amber-50 text-amber-700 border-amber-200',
    'blocked'   => 'bg-red-50 text-red-700 border-red-200',
    default     => 'bg-slate-50 text-slate-600 border-slate-200',
};

$statusLabel = match ((string) ($user['status'] ?? '')) {
    'active'    => 'Activa',
    'suspended' => 'Suspendida',
    'blocked'   => 'Bloqueada',
    default     => 'Inactiva',
};

$joinedAt = !empty($user['created_at'])
    ? (new DateTime((string) $user['created_at']))->format('d/m/Y')
    : '—';

$lastLogin = !empty($user['last_login_at'])
    ? (new DateTime((string) $user['last_login_at']))->format('d/m/Y H:i')
    : 'Sin accesos previos';

$birthdateVal = !empty($user['birthdate'])
    ? (new DateTime((string) $user['birthdate']))->format('Y-m-d')
    : '';

$initials = mb_strtoupper(mb_substr((string) ($user['name'] ?? '?'), 0, 2));

$error   = \Core\Session::getFlash('error', '');
$success = \Core\Session::getFlash('success', '');
?>

<section class="p-6 lg:p-8 max-w-5xl mx-auto space-y-6">

    <!-- Header -->
    <header class="rounded-3xl border border-outline-variant/60 bg-white overflow-hidden shadow-ambient">
        <div class="bg-[radial-gradient(circle_at_top_right,_rgba(10,37,64,0.16),_transparent_55%),linear-gradient(120deg,_#0f2f52_0%,_#0a2540_55%,_#163560_100%)] p-6 sm:p-7 text-on-primary">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-6">
                <div class="h-16 w-16 shrink-0 rounded-2xl bg-white/20 flex items-center justify-center text-xl font-bold text-white">
                    <?= $e($initials) ?>
                </div>
                <div class="min-w-0">
                    <p class="text-xs uppercase tracking-[0.12em] text-on-primary/70">Mi perfil</p>
                    <h1 class="headline-lg text-white mt-0.5"><?= $e($user['name'] ?? '') ?></h1>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-sm text-on-primary/80">
                        <span><?= $e($user['email'] ?? '') ?></span>
                        <span class="text-on-primary/40">·</span>
                        <span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-semibold <?= $e($statusClass) ?>"><?= $e($statusLabel) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid divide-x divide-outline-variant/50 sm:grid-cols-4">
            <div class="px-4 py-3">
                <p class="label-sm text-on-surface-subtle">Número</p>
                <p class="mt-0.5 font-semibold text-on-surface text-sm"><?= $e($user['user_number'] ?? '—') ?></p>
            </div>
            <div class="px-4 py-3">
                <p class="label-sm text-on-surface-subtle">Rol</p>
                <p class="mt-0.5 font-semibold text-on-surface text-sm"><?= $e($roleLabel) ?> / <?= $e($typeLabel) ?></p>
            </div>
            <div class="px-4 py-3">
                <p class="label-sm text-on-surface-subtle">Miembro desde</p>
                <p class="mt-0.5 font-semibold text-on-surface text-sm"><?= $e($joinedAt) ?></p>
            </div>
            <div class="px-4 py-3">
                <p class="label-sm text-on-surface-subtle">Último acceso</p>
                <p class="mt-0.5 font-semibold text-on-surface text-sm"><?= $e($lastLogin) ?></p>
            </div>
        </div>
    </header>

    <!-- Flash messages -->
    <?php if ($error !== ''): ?>
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <?= $e($error) ?>
        </div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <?= $e($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/account/profile" class="space-y-6">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">

        <!-- Personal data -->
        <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
            <div class="border-b border-outline-variant/50 bg-surface-container-lowest px-5 py-3.5">
                <h2 class="title-sm text-on-surface">Datos personales</h2>
                <p class="body-sm text-on-surface-muted">Actualiza tu nombre, teléfono y dirección.</p>
            </div>
            <div class="p-5 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="label-sm">Nombre completo <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" required
                           value="<?= $e($user['name'] ?? '') ?>"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="label-sm">Correo electrónico</label>
                    <input type="email" value="<?= $e($user['email'] ?? '') ?>" disabled
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2.5 text-sm text-on-surface-muted cursor-not-allowed">
                    <p class="mt-1 text-xs text-on-surface-subtle">El correo no puede modificarse desde aquí.</p>
                </div>
                <div>
                    <label class="label-sm">Cédula</label>
                    <input type="text" value="<?= $e($user['document_number'] ?? '') ?>" disabled
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 py-2.5 text-sm text-on-surface-muted cursor-not-allowed">
                    <p class="mt-1 text-xs text-on-surface-subtle">La cédula es gestionada por administración.</p>
                </div>
                <div>
                    <label for="phone" class="label-sm">Teléfono</label>
                    <input id="phone" name="phone" type="tel"
                           value="<?= $e($user['phone'] ?? '') ?>"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none"
                           placeholder="+593 99 000 0000">
                </div>
                <div>
                    <label for="birthdate" class="label-sm">Fecha de nacimiento</label>
                    <input id="birthdate" name="birthdate" type="date"
                           value="<?= $e($birthdateVal) ?>"
                           max="<?= date('Y-m-d') ?>"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label for="address" class="label-sm">Dirección</label>
                    <textarea id="address" name="address" rows="2"
                              class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none"
                              placeholder="Calle, barrio, ciudad..."><?= $e($user['address'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Password change -->
        <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
            <div class="border-b border-outline-variant/50 bg-surface-container-lowest px-5 py-3.5">
                <h2 class="title-sm text-on-surface">Cambiar contraseña</h2>
                <p class="body-sm text-on-surface-muted">Opcional. Deja en blanco para mantener la contraseña actual.</p>
            </div>
            <div class="p-5 grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="current_password" class="label-sm">Contraseña actual</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label for="new_password" class="label-sm">Nueva contraseña</label>
                    <input id="new_password" name="new_password" type="password" autocomplete="new-password"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <p class="mt-1 text-xs text-on-surface-subtle">Mínimo 8 caracteres.</p>
                </div>
                <div>
                    <label for="confirm_password" class="label-sm">Confirmar nueva contraseña</label>
                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Save -->
        <div class="flex items-center justify-end gap-3">
            <a href="<?= BASE_URL ?>/account"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl gradient-scholar px-5 py-2 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V7l-4-4z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 3v4H7V3M12 12v5m-2-2h4"/>
                </svg>
                Guardar cambios
            </button>
        </div>
    </form>

</section>
