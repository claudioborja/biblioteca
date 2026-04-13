<?php
// views/admin/users/create.php
$e      = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$old    = $old ?? [];
$v      = fn(string $key, string $default = '') => $e($old[$key] ?? $default);
$isModal = isset($_GET['modal']) && $_GET['modal'] === '1';
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="<?= $isModal ? 'p-4' : 'p-6 lg:p-8' ?>">
    <?php if (!$isModal): ?>
        <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="label-sm">Administración · Usuarios</p>
                <h1 class="headline-lg text-on-surface">Nuevo usuario</h1>
                <p class="body-md mt-1">Crea una cuenta sin necesidad de registro público.</p>
            </div>
            <a href="<?= BASE_URL ?>/admin/users"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                <i class="bi bi-arrow-left text-sm"></i> Volver a usuarios
            </a>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/admin/users" id="user-create-form" class="space-y-5 <?= $isModal ? '' : 'max-w-5xl' ?>">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf ?? '') ?>">
        <?php if ($isModal): ?>
            <input type="hidden" name="modal" value="1">
        <?php endif; ?>

        <div class="<?= $isModal ? 'rounded-2xl border border-outline-variant/60 bg-white p-4' : 'rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient' ?> space-y-6">

            <!-- Datos personales -->
            <div>
                <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-on-surface-subtle">Datos personales</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="name" class="label-sm">Nombre completo <span class="text-error">*</span></label>
                        <input id="name" name="name" type="text" required minlength="3"
                               value="<?= $v('name') ?>"
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="email" class="label-sm">Correo electrónico <span class="text-error">*</span></label>
                        <input id="email" name="email" type="email" required
                               value="<?= $v('email') ?>"
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    </div>
                    <div>
                        <label for="document_number" class="label-sm">Cédula</label>
                        <input id="document_number" name="document_number" type="text"
                               value="<?= $v('document_number') ?>"
                               maxlength="10"
                               pattern="[0-9]{10}"
                               inputmode="numeric"
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                        <p class="mt-1 text-[11px] text-on-surface-subtle">Cédula ecuatoriana (10 dígitos).</p>
                    </div>
                    <div>
                        <label for="phone" class="label-sm">Teléfono</label>
                        <input id="phone" name="phone" type="text"
                               value="<?= $v('phone') ?>"
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label for="address" class="label-sm">Dirección</label>
                        <input id="address" name="address" type="text"
                               value="<?= $v('address') ?>"
                               class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Contraseña -->
            <div>
                <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-on-surface-subtle">Acceso</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="password" class="label-sm">Contraseña <span class="text-error">*</span></label>
                        <div class="relative mt-1">
                            <input id="password" name="password" type="password" required minlength="8"
                                   autocomplete="new-password"
                                   class="w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 pr-10 text-sm text-on-surface focus:border-primary focus:outline-none">
                            <button type="button" id="toggle-password"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-subtle hover:text-on-surface"
                                    aria-label="Mostrar/ocultar contraseña">
                                <i class="bi bi-eye text-sm"></i>
                            </button>
                        </div>
                        <p class="mt-1 text-[11px] text-on-surface-subtle">Mínimo 8 caracteres.</p>
                    </div>
                </div>
            </div>

            <!-- Clasificación y estado -->
            <div>
                <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-on-surface-subtle">Clasificación y acceso</p>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label for="user_type" class="label-sm">Tipo de usuario <span class="text-error">*</span></label>
                        <select id="user_type" name="user_type"
                                class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                            <?php $selectedType = (string) ($old['user_type'] ?? 'user'); ?>
                            <option value="user"      <?= $selectedType === 'user'      ? 'selected' : '' ?>>Usuario</option>
                            <option value="teacher"   <?= $selectedType === 'teacher'   ? 'selected' : '' ?>>Docente</option>
                            <option value="librarian" <?= $selectedType === 'librarian' ? 'selected' : '' ?>>Bibliotecario</option>
                        </select>
                        <p class="mt-1 text-[11px] text-on-surface-subtle">Docente activa panel de docentes · Bibliotecario activa panel de administración.</p>
                    </div>
                    <div>
                        <label for="status" class="label-sm">Estado inicial <span class="text-error">*</span></label>
                        <select id="status" name="status"
                                class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                            <?php $selectedStatus = (string) ($old['status'] ?? 'active'); ?>
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
                            <?php $selectedEmailVerified = (string) ($old['email_verified'] ?? '0'); ?>
                            <option value="0" <?= $selectedEmailVerified === '0' ? 'selected' : '' ?>>No verificado</option>
                            <option value="1" <?= $selectedEmailVerified === '1' ? 'selected' : '' ?>>Verificado</option>
                        </select>
                        <p class="mt-1 text-[11px] text-on-surface-subtle">Si eliges verificado, se registrará fecha y hora actual.</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$isModal): ?>
            <div class="rounded-2xl border border-outline-variant/60 bg-white px-4 py-3 shadow-ambient">
                <div class="flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-surface-container-low px-3 py-1.5 text-sm text-on-surface-muted">
                        <i class="bi bi-person-plus text-sm"></i> Número de usuario generado automáticamente
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                            <i class="bi bi-floppy text-sm"></i> Crear usuario
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
    // Toggle contraseña
    const toggleBtn = document.getElementById('toggle-password');
    const passInput = document.getElementById('password');
    if (toggleBtn && passInput) {
        toggleBtn.addEventListener('click', () => {
            const show = passInput.type === 'password';
            passInput.type = show ? 'text' : 'password';
            toggleBtn.querySelector('i').className = show ? 'bi bi-eye-slash text-sm' : 'bi bi-eye text-sm';
        });
    }

    // Escucha mensaje del padre para enviar el formulario (modal)
    const form = document.getElementById('user-create-form');
    if (form) {
        window.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'submit-user-create-form') {
                form.requestSubmit();
            }
        });
    }
})();
</script>
