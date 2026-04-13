<?php
// views/admin/users/edit.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$isModal = isset($_GET['modal']) && $_GET['modal'] === '1';

$typeLabel = static function (string $type): string {
    return match ($type) {
        'student'  => 'Estudiante',
        'teacher'  => 'Docente',
        'external' => 'Externo',
        'staff'    => 'Personal',
        default    => 'General',
    };
};
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="<?= $isModal ? 'p-4' : 'p-6 lg:p-8' ?>">
    <?php if (!$isModal): ?>
        <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="label-sm">Administración</p>
                <h1 class="headline-lg text-on-surface">Editar usuario</h1>
                <p class="body-md mt-1">Actualiza los datos, tipo y estado de acceso del usuario.</p>
            </div>
            <a href="<?= BASE_URL ?>/admin/users"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                <i class="bi bi-arrow-left text-sm"></i> Volver a usuarios
            </a>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/admin/users/<?= (int) ($user['id'] ?? 0) ?>" id="user-edit-form" class="space-y-5">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf ?? '') ?>">
        <?php if ($isModal): ?>
            <input type="hidden" name="modal" value="1">
        <?php endif; ?>

        <div class="<?= $isModal ? 'rounded-2xl border border-outline-variant/60 bg-white p-4' : 'max-w-5xl rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient' ?>">

            <?php /* Datos de solo lectura */ ?>
            <div class="mb-6 grid grid-cols-3 gap-4 rounded-xl bg-surface-container-low/60 px-4 py-3 text-sm">
                <div>
                    <p class="label-sm mb-0.5">ID</p>
                    <p class="font-semibold text-on-surface">#<?= (int) ($user['id'] ?? 0) ?></p>
                </div>
                <div>
                    <p class="label-sm mb-0.5">Número de usuario</p>
                    <p class="font-semibold text-on-surface"><?= $e($user['user_number'] ?? '—') ?></p>
                </div>
                <div>
                    <p class="label-sm mb-0.5">Registrado</p>
                    <p class="font-semibold text-on-surface">
                        <?= !empty($user['created_at'])
                            ? $e((new DateTime((string) $user['created_at']))->format('d/m/Y H:i'))
                            : '—' ?>
                    </p>
                </div>
                <div>
                    <p class="label-sm mb-0.5">Correo verificado</p>
                    <p class="font-semibold text-on-surface">
                        <?= !empty($user['email_verified_at'])
                            ? $e((new DateTime((string) $user['email_verified_at']))->format('d/m/Y H:i'))
                            : 'No verificado' ?>
                    </p>
                </div>
            </div>

            <?php /* Datos personales */ ?>
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-on-surface-subtle">Datos personales</p>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="name" class="label-sm">Nombre completo <span class="text-error">*</span></label>
                    <input id="name" name="name" type="text" required minlength="3"
                           value="<?= $e($user['name'] ?? '') ?>"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label for="email" class="label-sm">Correo electrónico <span class="text-error">*</span></label>
                    <input id="email" name="email" type="email" required
                           value="<?= $e($user['email'] ?? '') ?>"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label for="document_number" class="label-sm">Cédula</label>
                    <input id="document_number" name="document_number" type="text"
                           value="<?= $e($user['document_number'] ?? '') ?>"
                           maxlength="10"
                           pattern="[0-9]{10}"
                           inputmode="numeric"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <p class="mt-1 text-[11px] text-on-surface-subtle">Cédula ecuatoriana (10 dígitos).</p>
                </div>
                <div>
                    <label for="phone" class="label-sm">Teléfono</label>
                    <input id="phone" name="phone" type="text"
                           value="<?= $e($user['phone'] ?? '') ?>"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                </div>
                <div class="md:col-span-2">
                    <label for="address" class="label-sm">Dirección</label>
                    <input id="address" name="address" type="text"
                           value="<?= $e($user['address'] ?? '') ?>"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                </div>
            </div>

            <?php /* Clasificación y estado */ ?>
            <p class="mb-3 mt-6 text-xs font-semibold uppercase tracking-wide text-on-surface-subtle">Clasificación y acceso</p>
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="user_type" class="label-sm">Tipo de usuario <span class="text-error">*</span></label>
                    <select id="user_type" name="user_type"
                            class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <?php
                        $selectedType = match ($user['role'] ?? 'user') {
                            'librarian' => 'librarian',
                            'teacher'   => 'teacher',
                            default     => 'user',
                        };
                        ?>
                        <option value="user"      <?= $selectedType === 'user'      ? 'selected' : '' ?>>Usuario</option>
                        <option value="teacher"   <?= $selectedType === 'teacher'   ? 'selected' : '' ?>>Docente</option>
                        <option value="librarian" <?= $selectedType === 'librarian' ? 'selected' : '' ?>>Bibliotecario</option>
                    </select>
                    <p class="mt-1 text-[11px] text-on-surface-subtle">Docente activa panel de docentes · Bibliotecario activa panel de administración.</p>
                </div>
                <div>
                    <label for="status" class="label-sm">Estado <span class="text-error">*</span></label>
                    <select id="status" name="status"
                            class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <?php $selectedStatus = (string) ($user['status'] ?? 'active'); ?>
                        <option value="active"    <?= $selectedStatus === 'active'    ? 'selected' : '' ?>>Activo</option>
                        <option value="inactive"  <?= $selectedStatus === 'inactive'  ? 'selected' : '' ?>>Inactivo</option>
                        <option value="suspended" <?= $selectedStatus === 'suspended' ? 'selected' : '' ?>>Suspendido</option>
                        <option value="blocked"   <?= $selectedStatus === 'blocked'   ? 'selected' : '' ?>>Bloqueado</option>
                    </select>
                </div>
                <div>
                    <label for="email_verified" class="label-sm">Correo verificado</label>
                    <select id="email_verified" name="email_verified"
                            class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <?php $selectedEmailVerified = !empty($user['email_verified_at']) ? '1' : '0'; ?>
                        <option value="0" <?= $selectedEmailVerified === '0' ? 'selected' : '' ?>>No verificado</option>
                        <option value="1" <?= $selectedEmailVerified === '1' ? 'selected' : '' ?>>Verificado</option>
                    </select>
                    <p class="mt-1 text-[11px] text-on-surface-subtle">Al marcar verificado se conservará fecha previa o se registrará la actual.</p>
                </div>
            </div>
        </div>

        <?php if (!$isModal): ?>
            <div class="max-w-5xl rounded-2xl border border-outline-variant/60 bg-white px-4 py-3 shadow-ambient">
                <div class="flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-surface-container-low px-3 py-1.5 text-sm text-on-surface-muted">
                        <i class="bi bi-check2 text-sm"></i> Los cambios se aplican de inmediato
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                            <i class="bi bi-floppy text-sm"></i> Guardar cambios
                        </button>
                        <a href="<?= BASE_URL ?>/admin/users"
                           class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </form>
</section>

<script>
(() => {
    const form = document.getElementById('user-edit-form');
    if (!form) return;

    window.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'submit-user-edit-form') {
            form.requestSubmit();
        }
    });
})();
</script>
