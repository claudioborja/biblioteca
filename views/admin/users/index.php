<?php
// views/admin/users/index.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$typeLabel = static function (string $type): string {
    return match ($type) {
        'teacher'   => 'Docente',
        'librarian' => 'Bibliotecario',
        default     => 'Usuario',
    };
};

$statusClass = static function (string $status): string {
    return match ($status) {
        'active' => 'bg-emerald-100 text-emerald-700',
        'suspended' => 'bg-amber-100 text-amber-700',
        'blocked' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-700',
    };
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'active' => 'Activo',
        'suspended' => 'Suspendido',
        'blocked' => 'Bloqueado',
        default => 'Inactivo',
    };
};

$emailVerifiedClass = static function (?string $verifiedAt): string {
    return !empty($verifiedAt)
        ? 'bg-emerald-100 text-emerald-700'
        : 'bg-slate-100 text-slate-700';
};

$emailVerifiedLabel = static function (?string $verifiedAt): string {
    return !empty($verifiedAt) ? 'Verificado' : 'No verificado';
};

$csrfToken = $csrf ?? '';
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Administración</p>
            <h1 class="headline-lg text-on-surface">Usuarios</h1>
            <p class="body-md mt-1">Gestión de usuarios con edición en ventana modal y actualización inmediata.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= BASE_URL ?>/admin/users/report/pdf"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                <i class="bi bi-file-earmark-pdf text-sm"></i> PDF
            </a>
            <a href="<?= BASE_URL ?>/admin/users/export"
               class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                <i class="bi bi-file-earmark-spreadsheet text-sm"></i> Excel
            </a>
            <button type="button" id="btn-open-create-modal"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                <i class="bi bi-person-plus text-sm"></i> Nuevo usuario
            </button>
        </div>
    </div>

    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <div class="grid gap-3 md:grid-cols-5">
            <div class="md:col-span-2">
                <label for="user-search" class="label-sm">Buscar</label>
                <input id="user-search" type="text" placeholder="Nombre, correo o número de usuario"
                    class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none" />
            </div>
            <div>
                <label for="user-type" class="label-sm">Tipo</label>
                <select id="user-type" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todos</option>
                    <option value="user">Usuario</option>
                    <option value="teacher">Docente</option>
                    <option value="librarian">Bibliotecario</option>
                </select>
            </div>
            <div>
                <label for="user-status" class="label-sm">Estado</label>
                <select id="user-status" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="all">Todos</option>
                    <option value="active">Activo</option>
                    <option value="suspended">Suspendido</option>
                    <option value="blocked">Bloqueado</option>
                    <option value="inactive">Inactivo</option>
                </select>
            </div>
            <div>
                <label for="user-page-size" class="label-sm">Filas</label>
                <select id="user-page-size" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </select>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table id="users-table" class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="name" class="user-sort inline-flex items-center gap-1 hover:text-primary">
                                Usuario <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="type" class="user-sort inline-flex items-center gap-1 hover:text-primary">
                                Tipo <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="status" class="user-sort inline-flex items-center gap-1 hover:text-primary">
                                Estado <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="verified" class="user-sort inline-flex items-center gap-1 hover:text-primary">
                                Correo <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="last_seen" class="user-sort inline-flex items-center gap-1 hover:text-primary">
                                Última actividad <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="users-table-body" class="divide-y divide-outline-variant/50 text-sm">
                    <?php foreach ($users as $user): ?>
                        <?php
                        $type = (string) ($user['type'] ?? 'student');
                        $status = (string) ($user['status'] ?? 'inactive');
                        ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors"
                            data-user-id="<?= (int) $user['id'] ?>"
                            data-name="<?= $e(mb_strtolower((string) $user['name'])) ?>"
                            data-email="<?= $e(mb_strtolower((string) $user['email'])) ?>"
                            data-usernumber="<?= $e(mb_strtolower((string) ($user['user_number'] ?? ''))) ?>"
                            data-type="<?= $e($type) ?>"
                            data-status="<?= $e($status) ?>"
                            data-verified="<?= !empty($user['email_verified_at']) ? '1' : '0' ?>"
                            data-last_seen="<?= $e(mb_strtolower((string) ($user['last_seen'] ?? 'sin accesos'))) ?>">
                            <td class="px-4 py-3.5">
                                <p class="font-semibold text-on-surface" data-cell="name"><?= $e($user['name']) ?></p>
                                <p class="text-xs text-on-surface-subtle">
                                    #<span data-cell="user_number"><?= $e($user['user_number'] ?? '—') ?></span>
                                    · <span data-cell="email"><?= $e($user['email']) ?></span>
                                </p>
                            </td>
                            <td class="px-4 py-3.5">
                                <button type="button"
                                        data-cell="type"
                                        data-user-id="<?= (int) $user['id'] ?>"
                                        data-user-name="<?= $e($user['name']) ?>"
                                        data-type="<?= $e($type) ?>"
                                        class="js-change-type inline-flex items-center justify-between gap-1 rounded-full border border-outline-variant/60 bg-surface-container-low px-2.5 py-1 text-xs font-semibold text-on-surface-muted hover:border-primary/40 hover:bg-primary/5 hover:text-primary transition-colors cursor-pointer min-w-[7.5rem]">
                                    <?= $e($typeLabel($type)) ?>
                                    <i class="bi bi-chevron-down text-[9px]"></i>
                                </button>
                            </td>
                            <td class="px-4 py-3.5">
                                <button type="button"
                                        data-cell="status"
                                        data-user-id="<?= (int) $user['id'] ?>"
                                        data-status="<?= $e($status) ?>"
                                        class="js-status-badge inline-flex items-center justify-between gap-1 rounded-full px-2.5 py-1 text-xs font-semibold cursor-pointer min-w-[7.5rem] <?= $statusClass($status) ?>">
                                    <?= $e($statusLabel($status)) ?>
                                    <i class="bi bi-chevron-down text-[9px]"></i>
                                </button>
                            </td>
                            <td class="px-4 py-3.5">
                                <span data-cell="email_verified"
                                      class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $emailVerifiedClass($user['email_verified_at'] ?? null) ?>">
                                    <?= $e($emailVerifiedLabel($user['email_verified_at'] ?? null)) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-muted" data-cell="last_seen"><?= $e($user['last_seen']) ?></td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button"
                                            data-edit-url="<?= BASE_URL ?>/admin/users/<?= (int) $user['id'] ?>/edit"
                                            data-edit-name="<?= $e($user['name']) ?>"
                                            class="rounded-lg border border-primary/30 bg-primary/5 px-2.5 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors js-open-user-edit-modal inline-flex items-center gap-1">
                                        <i class="bi bi-pencil-square text-[12px]"></i> Editar
                                    </button>
                                    <button type="button"
                                            data-user-id="<?= (int) $user['id'] ?>"
                                            data-user-name="<?= $e($user['name']) ?>"
                                            class="js-reset-password rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition-colors inline-flex items-center gap-1"
                                            title="Resetear contraseña">
                                        <i class="bi bi-key text-[12px]"></i>
                                    </button>
                                    <button type="button"
                                            data-user-id="<?= (int) $user['id'] ?>"
                                            data-user-name="<?= $e($user['name']) ?>"
                                            class="js-delete-user rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100 transition-colors inline-flex items-center gap-1"
                                            title="Eliminar usuario">
                                        <i class="bi bi-trash3 text-[12px]"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-outline-variant/60 px-4 py-3 text-sm text-on-surface-muted sm:flex-row sm:items-center sm:justify-between">
            <p id="users-table-info">Mostrando 0-0 de 0 usuarios</p>
            <div class="flex items-center gap-1">
                <button id="users-prev" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    <i class="bi bi-arrow-left text-[12px]"></i> Anterior
                </button>
                <span id="users-page-indicator" class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white">1</span>
                <button id="users-next" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    Siguiente <i class="bi bi-arrow-right text-[12px]"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Dropdown global: Cambiar estado -->
    <div id="status-dropdown" class="fixed z-[60] hidden min-w-[150px] rounded-xl border border-outline-variant/60 bg-white py-1 shadow-xl">
        <button type="button" data-val="active"    class="js-status-option w-full px-3 py-2 text-left text-xs hover:bg-surface-container-low flex items-center gap-2.5"><span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>Activo</button>
        <button type="button" data-val="inactive"  class="js-status-option w-full px-3 py-2 text-left text-xs hover:bg-surface-container-low flex items-center gap-2.5"><span class="inline-block h-2 w-2 rounded-full bg-slate-400"></span>Inactivo</button>
        <button type="button" data-val="suspended" class="js-status-option w-full px-3 py-2 text-left text-xs hover:bg-surface-container-low flex items-center gap-2.5"><span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>Suspendido</button>
        <button type="button" data-val="blocked"   class="js-status-option w-full px-3 py-2 text-left text-xs hover:bg-surface-container-low flex items-center gap-2.5"><span class="inline-block h-2 w-2 rounded-full bg-red-500"></span>Bloqueado</button>
    </div>

    <!-- Modal: Cambiar tipo de usuario -->
    <div id="user-type-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[1px]"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="relative w-full max-w-sm rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">
                <div class="flex items-center gap-3 border-b border-outline-variant/60 px-5 py-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <i class="bi bi-person-gear text-sm"></i>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-on-surface">Cambiar tipo de usuario</p>
                        <p id="user-type-modal-subtitle" class="text-xs text-on-surface-muted"></p>
                    </div>
                </div>
                <div class="px-5 py-4 space-y-2">
                    <p class="text-xs text-on-surface-muted mb-3">Selecciona el nuevo tipo. Esto modifica los permisos de acceso del usuario.</p>
                    <label class="flex items-center gap-3 rounded-xl border border-outline-variant/60 px-3 py-2.5 cursor-pointer hover:bg-surface-container-low has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                        <input type="radio" name="new_user_type" value="user" class="accent-primary">
                        <div>
                            <p class="text-sm font-semibold text-on-surface">Usuario</p>
                            <p class="text-xs text-on-surface-subtle">Acceso solo a área personal</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 rounded-xl border border-outline-variant/60 px-3 py-2.5 cursor-pointer hover:bg-surface-container-low has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                        <input type="radio" name="new_user_type" value="teacher" class="accent-primary">
                        <div>
                            <p class="text-sm font-semibold text-on-surface">Docente</p>
                            <p class="text-xs text-on-surface-subtle">Acceso a panel de docentes</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 rounded-xl border border-outline-variant/60 px-3 py-2.5 cursor-pointer hover:bg-surface-container-low has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                        <input type="radio" name="new_user_type" value="librarian" class="accent-primary">
                        <div>
                            <p class="text-sm font-semibold text-on-surface">Bibliotecario</p>
                            <p class="text-xs text-on-surface-subtle">Acceso a panel de administración</p>
                        </div>
                    </label>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-outline-variant/60 px-5 py-3">
                    <button type="button" id="user-type-modal-cancel"
                            class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                        Cancelar
                    </button>
                    <button type="button" id="user-type-modal-confirm"
                            class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors inline-flex items-center gap-2">
                        <i class="bi bi-check2 text-sm"></i> Confirmar cambio
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Eliminar usuario -->
    <div id="user-delete-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[1px]"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="relative w-full max-w-md rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">
                <div class="flex items-center gap-3 border-b border-outline-variant/60 px-5 py-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <i class="bi bi-trash3 text-sm"></i>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-on-surface">Eliminar usuario</p>
                        <p id="user-delete-modal-subtitle" class="text-xs text-on-surface-muted"></p>
                    </div>
                </div>
                <div class="px-5 py-4">
                    <p class="text-sm text-on-surface-muted">Esta acción es permanente. El usuario y su cuenta serán eliminados del sistema.</p>
                    <p id="user-delete-modal-error" class="mt-3 hidden rounded-xl bg-red-50 px-3 py-2.5 text-xs text-red-700"></p>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-outline-variant/60 px-5 py-3">
                    <button type="button" id="user-delete-modal-cancel"
                            class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                        Cancelar
                    </button>
                    <button type="button" id="user-delete-modal-confirm"
                            class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors inline-flex items-center gap-2">
                        <i class="bi bi-trash3 text-sm"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Resetear contraseña -->
    <div id="user-reset-pwd-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[1px]"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="relative w-full max-w-md rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">
                <div class="flex items-center gap-3 border-b border-outline-variant/60 px-5 py-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <i class="bi bi-key text-sm"></i>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-on-surface">Resetear contraseña</p>
                        <p id="user-reset-pwd-modal-subtitle" class="text-xs text-on-surface-muted"></p>
                    </div>
                </div>
                <div class="px-5 py-4">
                    <p class="text-sm text-on-surface-muted">Se generará una contraseña temporal. El usuario deberá cambiarla en su próximo inicio de sesión.</p>
                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="label-sm">Nueva contraseña temporal</label>
                            <div class="mt-1 flex items-center gap-2">
                                <input id="user-reset-pwd-input" type="text" readonly
                                       class="flex-1 rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm font-mono text-on-surface" />
                                <button type="button" id="user-reset-pwd-copy"
                                        class="rounded-lg border border-outline-variant bg-white px-2.5 py-2 text-xs text-on-surface-muted hover:bg-surface-container-low transition-colors"
                                        title="Copiar">
                                    <i class="bi bi-clipboard text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-outline-variant/60 px-5 py-3">
                    <button type="button" id="user-reset-pwd-modal-cancel"
                            class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                        Cancelar
                    </button>
                    <button type="button" id="user-reset-pwd-modal-confirm"
                            class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 transition-colors inline-flex items-center gap-2">
                        <i class="bi bi-key text-sm"></i> Aplicar reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Nuevo usuario -->
    <div id="user-create-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-primary/35 backdrop-blur-[1px]" data-close-user-create-modal></div>
        <div class="absolute inset-0 p-4 sm:p-6 flex items-center justify-center">
            <div class="relative flex h-[88vh] w-[95vw] max-w-[1200px] flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">
                <div class="flex h-12 items-center justify-between border-b border-outline-variant/60 bg-gradient-to-b from-slate-100 to-slate-200 pl-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-primary/15 text-primary">
                            <i class="bi bi-person-plus text-[12px]"></i>
                        </span>
                        <p class="truncate text-sm font-semibold text-slate-700">Nuevo usuario</p>
                    </div>
                    <button type="button" class="inline-flex h-12 w-12 items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors" data-close-user-create-modal aria-label="Cerrar">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1">
                    <iframe id="user-create-frame" title="Nuevo usuario" class="h-full w-full bg-white" src="about:blank"></iframe>
                </div>
                <div class="flex min-h-14 items-center justify-between border-t border-outline-variant/70 bg-slate-100/95 px-4 py-2.5">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1 text-xs text-slate-600">
                        <i class="bi bi-person-badge text-[12px]"></i> Número de usuario generado automáticamente
                    </span>
                    <button type="button" id="user-create-modal-save"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                        <i class="bi bi-floppy text-sm"></i> Crear usuario
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Editar usuario -->
    <div id="user-edit-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-primary/35 backdrop-blur-[1px]" data-close-user-edit-modal></div>
        <div class="absolute inset-0 p-4 sm:p-6 flex items-center justify-center">
            <div class="relative flex h-[88vh] w-[95vw] max-w-[1200px] flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient-lg">
                <div class="flex h-12 items-center justify-between border-b border-outline-variant/60 bg-gradient-to-b from-slate-100 to-slate-200 pl-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-primary/15 text-primary">
                            <i class="bi bi-person-badge text-[12px]"></i>
                        </span>
                        <p id="user-edit-modal-title" class="truncate text-sm font-semibold text-slate-700">Editor de usuario</p>
                    </div>
                    <button type="button" class="inline-flex h-12 w-12 items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors" data-close-user-edit-modal aria-label="Cerrar ventana">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1">
                    <iframe id="user-edit-frame" title="Editar usuario" class="h-full w-full bg-white" src="about:blank"></iframe>
                </div>
                <div class="flex min-h-14 items-center justify-between border-t border-outline-variant/70 bg-slate-100/95 px-4 py-2.5">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1">
                            <i class="bi bi-check2 text-[12px]"></i> Guardado inmediato
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1">
                            <i class="bi bi-arrow-repeat text-[12px]"></i> Actualiza la fila sin recargar
                        </span>
                    </div>
                    <button type="button" id="user-edit-modal-save" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-light transition-colors">
                        <i class="bi bi-floppy text-sm"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(() => {
    const tableBody = document.getElementById('users-table-body');
    if (!tableBody) return;

    const rows = Array.from(tableBody.querySelectorAll('tr'));
    const searchInput = document.getElementById('user-search');
    const typeSelect = document.getElementById('user-type');
    const statusSelect = document.getElementById('user-status');
    const pageSizeSelect = document.getElementById('user-page-size');
    const prevBtn = document.getElementById('users-prev');
    const nextBtn = document.getElementById('users-next');
    const pageIndicator = document.getElementById('users-page-indicator');
    const info = document.getElementById('users-table-info');
    const sortButtons = Array.from(document.querySelectorAll('.user-sort'));

    // ── Modal Crear ───────────────────────────────────────────────────────────
    const createModal      = document.getElementById('user-create-modal');
    const createFrame      = document.getElementById('user-create-frame');
    const createSaveButton = document.getElementById('user-create-modal-save');
    const openCreateButton = document.getElementById('btn-open-create-modal');
    const closeCreateBtns  = Array.from(document.querySelectorAll('[data-close-user-create-modal]'));

    const openCreateModal = () => {
        if (!createModal || !createFrame) return;
        createFrame.src = `<?= BASE_URL ?>/admin/users/create?modal=1`;
        createModal.classList.remove('hidden');
        createModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    const closeCreateModal = () => {
        if (!createModal || !createFrame) return;
        createModal.classList.add('hidden');
        createModal.setAttribute('aria-hidden', 'true');
        createFrame.src = 'about:blank';
        document.body.classList.remove('overflow-hidden');
    };

    openCreateButton?.addEventListener('click', openCreateModal);
    closeCreateBtns.forEach((btn) => btn.addEventListener('click', closeCreateModal));
    createSaveButton?.addEventListener('click', () => {
        createFrame?.contentWindow?.postMessage({ type: 'submit-user-create-form' }, '*');
    });

    // ── Modal Editar ──────────────────────────────────────────────────────────
    const editModal = document.getElementById('user-edit-modal');
    const editFrame = document.getElementById('user-edit-frame');
    const editModalTitle = document.getElementById('user-edit-modal-title');
    const editSaveButton = document.getElementById('user-edit-modal-save');
    const closeEditButtons = Array.from(document.querySelectorAll('[data-close-user-edit-modal]'));

    const state = {
        search: '',
        type: 'all',
        status: 'all',
        page: 1,
        pageSize: Number(pageSizeSelect?.value || 5),
        sortBy: 'name',
        sortDir: 'asc',
    };

    const typeLabels = {
        user:      'Usuario',
        teacher:   'Docente',
        librarian: 'Bibliotecario',
    };

    const statusRank = { active: 1, suspended: 2, blocked: 3, inactive: 4 };
    const statusClasses = {
        active: 'bg-emerald-100 text-emerald-700',
        suspended: 'bg-amber-100 text-amber-700',
        blocked: 'bg-red-100 text-red-700',
        inactive: 'bg-slate-100 text-slate-700',
    };
    const statusLabels = {
        active: 'Activo',
        suspended: 'Suspendido',
        blocked: 'Bloqueado',
        inactive: 'Inactivo',
    };

    const verifiedClasses = {
        '1': 'bg-emerald-100 text-emerald-700',
        '0': 'bg-slate-100 text-slate-700',
    };

    const verifiedLabels = {
        '1': 'Verificado',
        '0': 'No verificado',
    };

    const valueForSort = (row, sortBy) => {
        if (sortBy === 'status') return statusRank[row.dataset.status || ''] || 99;
        if (sortBy === 'verified') return Number(row.dataset.verified || 0);
        return (row.dataset[sortBy] || '').toString();
    };

    const updateSortLabels = () => {
        sortButtons.forEach((btn) => {
            const icon = btn.querySelector('span');
            if (!icon) return;
            if (btn.dataset.sort !== state.sortBy) {
                icon.textContent = '⇅';
                return;
            }
            icon.textContent = state.sortDir === 'asc' ? '↑' : '↓';
        });
    };

    const getFilteredRows = () => {
        const search = state.search.trim().toLowerCase();

        return rows.filter((row) => {
            const text = [row.dataset.name, row.dataset.email, row.dataset.usernumber].join(' ');
            const matchesSearch = !search || text.includes(search);
            const matchesType = state.type === 'all' || row.dataset.type === state.type;
            const matchesStatus = state.status === 'all' || row.dataset.status === state.status;
            return matchesSearch && matchesType && matchesStatus;
        });
    };

    const render = () => {
        const filtered = getFilteredRows().sort((a, b) => {
            const va = valueForSort(a, state.sortBy);
            const vb = valueForSort(b, state.sortBy);
            if (typeof va === 'number' && typeof vb === 'number') {
                return state.sortDir === 'asc' ? va - vb : vb - va;
            }
            return state.sortDir === 'asc'
                ? String(va).localeCompare(String(vb), 'es')
                : String(vb).localeCompare(String(va), 'es');
        });

        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / state.pageSize));
        state.page = Math.min(state.page, totalPages);
        const start = (state.page - 1) * state.pageSize;
        const end = Math.min(start + state.pageSize, total);

        rows.forEach((row) => {
            row.style.display = 'none';
        });
        filtered.slice(start, end).forEach((row) => {
            row.style.display = '';
            tableBody.appendChild(row);
        });

        const showingFrom = total === 0 ? 0 : start + 1;
        const showingTo = total === 0 ? 0 : end;
        info.textContent = `Mostrando ${showingFrom}-${showingTo} de ${total} usuarios`;

        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= totalPages;
        prevBtn.classList.toggle('opacity-60', prevBtn.disabled);
        prevBtn.classList.toggle('cursor-not-allowed', prevBtn.disabled);
        nextBtn.classList.toggle('opacity-60', nextBtn.disabled);
        nextBtn.classList.toggle('cursor-not-allowed', nextBtn.disabled);
        pageIndicator.textContent = `${state.page}/${totalPages}`;

        updateSortLabels();
    };

    const updateRow = (payload) => {
        if (!payload || !payload.id) return;
        const row = tableBody.querySelector(`tr[data-user-id="${payload.id}"]`);
        if (!row) return;

        row.dataset.name = String(payload.name || '').toLowerCase();
        row.dataset.email = String(payload.email || '').toLowerCase();
        row.dataset.usernumber = String(payload.user_number || '').toLowerCase();
        row.dataset.type = String(payload.user_type || 'student');
        row.dataset.status = String(payload.status || 'inactive');
        row.dataset.verified = String(payload.email_verified || '0');
        row.dataset.last_seen = String(payload.last_seen || '').toLowerCase();

        const nameCell = row.querySelector('[data-cell="name"]');
        const user_numberCell = row.querySelector('[data-cell="user_number"]');
        const emailCell = row.querySelector('[data-cell="email"]');
        const typeCell = row.querySelector('[data-cell="type"]');
        const statusCell = row.querySelector('[data-cell="status"]');
        const verifiedCell = row.querySelector('[data-cell="email_verified"]');
        const lastSeenCell = row.querySelector('[data-cell="last_seen"]');

        if (nameCell) nameCell.textContent = payload.name || '';
        if (user_numberCell) user_numberCell.textContent = payload.user_number || '';
        if (emailCell) emailCell.textContent = payload.email || '';
        if (typeCell) {
            typeCell.dataset.type = payload.user_type || 'user';
            typeCell.innerHTML = `${typeLabels[payload.user_type || ''] || 'Usuario'}<i class="bi bi-chevron-down text-[9px]"></i>`;
        }
        if (lastSeenCell) lastSeenCell.textContent = payload.last_seen || 'Sin accesos';
        if (statusCell) {
            const status = String(payload.status || 'inactive');
            statusCell.className = `js-status-badge inline-flex items-center justify-between gap-1 rounded-full px-2.5 py-1 text-xs font-semibold cursor-pointer min-w-[7.5rem] ${statusClasses[status] || statusClasses.inactive}`;
            statusCell.dataset.status = status;
            statusCell.innerHTML = `${statusLabels[status] || 'Inactivo'}<i class="bi bi-chevron-down text-[9px]"></i>`;
        }
        if (verifiedCell) {
            const verified = String(payload.email_verified || '0');
            verifiedCell.className = `inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${verifiedClasses[verified] || verifiedClasses['0']}`;
            verifiedCell.textContent = verifiedLabels[verified] || verifiedLabels['0'];
        }

        render();
    };

    const openEditModal = (url, name = '') => {
        if (!editModal || !editFrame || !url) return;
        const modalUrl = url.includes('?') ? `${url}&modal=1` : `${url}?modal=1`;
        editFrame.src = modalUrl;
        if (editModalTitle) {
            editModalTitle.textContent = name ? `Editor de usuario · ${name}` : 'Editor de usuario';
        }
        editModal.classList.remove('hidden');
        editModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    const closeEditModal = () => {
        if (!editModal || !editFrame) return;
        editModal.classList.add('hidden');
        editModal.setAttribute('aria-hidden', 'true');
        editFrame.src = 'about:blank';
        document.body.classList.remove('overflow-hidden');
    };

    searchInput?.addEventListener('input', (event) => {
        state.search = event.target.value || '';
        state.page = 1;
        render();
    });

    typeSelect?.addEventListener('change', (event) => {
        state.type = event.target.value || 'all';
        state.page = 1;
        render();
    });

    statusSelect?.addEventListener('change', (event) => {
        state.status = event.target.value || 'all';
        state.page = 1;
        render();
    });

    pageSizeSelect?.addEventListener('change', (event) => {
        state.pageSize = Number(event.target.value || 5);
        state.page = 1;
        render();
    });

    prevBtn?.addEventListener('click', () => {
        if (state.page <= 1) return;
        state.page -= 1;
        render();
    });

    nextBtn?.addEventListener('click', () => {
        state.page += 1;
        render();
    });

    sortButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetSort = btn.dataset.sort || 'name';
            if (state.sortBy === targetSort) {
                state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                state.sortBy = targetSort;
                state.sortDir = 'asc';
            }
            state.page = 1;
            render();
        });
    });

    closeEditButtons.forEach((btn) => {
        btn.addEventListener('click', closeEditModal);
    });

    editSaveButton?.addEventListener('click', () => {
        if (!editFrame || !editFrame.contentWindow) return;
        editFrame.contentWindow.postMessage({ type: 'submit-user-edit-form' }, '*');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (createModal && !createModal.classList.contains('hidden')) closeCreateModal();
        else if (editModal && !editModal.classList.contains('hidden')) closeEditModal();
        else if (typeModal && !typeModal.classList.contains('hidden')) closeTypeModal();
        else if (resetPwdModal && !resetPwdModal.classList.contains('hidden')) closeResetPwdModal();
        else if (deleteModal && !deleteModal.classList.contains('hidden')) closeDeleteModal();
    });

    const addRow = (payload) => {
        if (!payload || !payload.id) return;
        const editUrl = `<?= BASE_URL ?>/admin/users/${payload.id}/edit`;
        const status = payload.status || 'inactive';
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-surface-container-low/60 transition-colors';
        tr.dataset.userId    = payload.id;
        tr.dataset.name      = String(payload.name || '').toLowerCase();
        tr.dataset.email     = String(payload.email || '').toLowerCase();
        tr.dataset.usernumber = String(payload.user_number || '').toLowerCase();
        tr.dataset.type      = payload.user_type || 'user';
        tr.dataset.status    = status;
        tr.dataset.verified  = String(payload.email_verified || '0');
        tr.dataset.last_seen = 'sin accesos';
        tr.innerHTML = `
            <td class="px-4 py-3.5">
                <p class="font-semibold text-on-surface" data-cell="name">${escHtml(payload.name || '')}</p>
                <p class="text-xs text-on-surface-subtle">
                    #<span data-cell="user_number">${escHtml(payload.user_number || '')}</span>
                    · <span data-cell="email">${escHtml(payload.email || '')}</span>
                </p>
            </td>
            <td class="px-4 py-3.5">${buildTypeBadge(payload.id, payload.name || '', payload.user_type || 'user')}</td>
            <td class="px-4 py-3.5">${buildStatusBadge(payload.id, status)}</td>
            <td class="px-4 py-3.5">${buildVerifiedBadge(String(payload.email_verified || '0'))}</td>
            <td class="px-4 py-3.5 text-on-surface-muted" data-cell="last_seen">Sin accesos</td>
            <td class="px-4 py-3.5 text-right">${buildActionButtons(payload.id, payload.name || '', editUrl)}</td>`;
        tableBody.appendChild(tr);
        rows.push(tr);
        bindRowActions(tr);
        state.page = 1;
        render();
    };

    window.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'close-user-edit-modal') {
            closeEditModal();
        }
        if (event.data && event.data.type === 'user-edit-saved') {
            closeEditModal();
            if (event.data.payload) updateRow(event.data.payload);
            if (typeof window.showLibraryToast === 'function') {
                window.showLibraryToast('success', (event.data.payload?.message) || 'Usuario actualizado.');
            }
        }
        if (event.data && event.data.type === 'user-create-saved') {
            closeCreateModal();
            if (event.data.payload) addRow(event.data.payload);
            if (typeof window.showLibraryToast === 'function') {
                window.showLibraryToast('success', (event.data.payload?.message) || 'Usuario creado correctamente.');
            }
        }
    });

    // ── Helpers ───────────────────────────────────────────────────────────────
    const escHtml = (s) => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const csrfToken = <?= json_encode($csrfToken) ?>;

    const buildTypeBadge = (userId, userName, userType) => `
        <button type="button"
                data-cell="type"
                data-user-id="${userId}"
                data-user-name="${escHtml(userName)}"
                data-type="${userType}"
                class="js-change-type inline-flex items-center justify-between gap-1 rounded-full border border-outline-variant/60 bg-surface-container-low px-2.5 py-1 text-xs font-semibold text-on-surface-muted hover:border-primary/40 hover:bg-primary/5 hover:text-primary transition-colors cursor-pointer min-w-[7.5rem]">
            ${typeLabels[userType] || 'Usuario'}
            <i class="bi bi-chevron-down text-[9px]"></i>
        </button>`;

    const buildStatusBadge = (userId, status) => `
        <button type="button"
                data-cell="status"
                data-user-id="${userId}"
                data-status="${status}"
                class="js-status-badge inline-flex items-center justify-between gap-1 rounded-full px-2.5 py-1 text-xs font-semibold cursor-pointer min-w-[7.5rem] ${statusClasses[status] || statusClasses.inactive}">
            ${statusLabels[status] || 'Inactivo'}
            <i class="bi bi-chevron-down text-[9px]"></i>
        </button>`;

    const buildVerifiedBadge = (verified) => `
        <span data-cell="email_verified"
              class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${verifiedClasses[verified] || verifiedClasses['0']}">
            ${verifiedLabels[verified] || verifiedLabels['0']}
        </span>`;

    const buildActionButtons = (userId, userName, editUrl) => `
        <div class="inline-flex items-center gap-1">
            <button type="button"
                    data-edit-url="${editUrl}"
                    data-edit-name="${escHtml(userName)}"
                    class="rounded-lg border border-primary/30 bg-primary/5 px-2.5 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors js-open-user-edit-modal inline-flex items-center gap-1">
                <i class="bi bi-pencil-square text-[12px]"></i> Editar
            </button>
            <button type="button"
                    data-user-id="${userId}"
                    data-user-name="${escHtml(userName)}"
                    class="js-reset-password rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition-colors inline-flex items-center gap-1"
                    title="Resetear contraseña">
                <i class="bi bi-key text-[12px]"></i>
            </button>
            <button type="button"
                    data-user-id="${userId}"
                    data-user-name="${escHtml(userName)}"
                    class="js-delete-user rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100 transition-colors inline-flex items-center gap-1"
                    title="Eliminar usuario">
                <i class="bi bi-trash3 text-[12px]"></i>
            </button>
        </div>`;

    const bindRowActions = (tr) => {
        tr.querySelector('.js-open-user-edit-modal')?.addEventListener('click', (e) => {
            const btn = e.currentTarget;
            openEditModal(btn.dataset.editUrl || '', btn.dataset.editName || '');
        });
        tr.querySelector('.js-change-type')?.addEventListener('click', (e) => {
            const btn = e.currentTarget;
            openTypeModal(btn.dataset.userId, btn.dataset.userName || '', btn.dataset.type || 'user');
        });
        const badge = tr.querySelector('.js-status-badge');
        if (badge) {
            badge.addEventListener('click', (e) => { e.stopPropagation(); openStatusDropdown(badge); });
        }
        tr.querySelector('.js-reset-password')?.addEventListener('click', (e) => {
            const btn = e.currentTarget;
            openResetPwdModal(btn.dataset.userId, btn.dataset.userName || '');
        });
        tr.querySelector('.js-delete-user')?.addEventListener('click', (e) => {
            const btn = e.currentTarget;
            openDeleteModal(btn.dataset.userId, btn.dataset.userName || '');
        });
    };

    // ── Status dropdown (global, fixed-positioned) ────────────────────────────
    const statusDropdown = document.getElementById('status-dropdown');
    let activeBadge = null;

    const closeStatusDropdown = () => {
        statusDropdown?.classList.add('hidden');
        activeBadge = null;
    };

    const openStatusDropdown = (badge) => {
        if (!statusDropdown) return;
        if (activeBadge === badge) { closeStatusDropdown(); return; }
        activeBadge = badge;
        const rect = badge.getBoundingClientRect();
        statusDropdown.style.top  = `${rect.bottom + window.scrollY + 4}px`;
        statusDropdown.style.left = `${rect.left + window.scrollX}px`;
        statusDropdown.classList.remove('hidden');
    };

    document.addEventListener('click', (e) => {
        if (!statusDropdown?.contains(e.target) && !e.target.closest('.js-status-badge')) {
            closeStatusDropdown();
        }
    });

    statusDropdown?.querySelectorAll('.js-status-option').forEach((opt) => {
        opt.addEventListener('click', (e) => {
            e.stopPropagation();
            if (!activeBadge) return;
            const row = activeBadge.closest('tr');
            applyStatusChange(activeBadge, row, opt.dataset.val);
            closeStatusDropdown();
        });
    });

    const applyStatusChange = async (badge, row, newStatus) => {
        const userId = badge.dataset.userId;
        if (!userId || !newStatus) return;
        try {
            const res = await fetch(`<?= BASE_URL ?>/admin/users/${userId}/status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `status=${encodeURIComponent(newStatus)}&_csrf_token=${encodeURIComponent(csrfToken)}`,
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Error');
            const status = data.status;
            badge.dataset.status = status;
            badge.className = `js-status-badge inline-flex items-center justify-between gap-1 rounded-full px-2.5 py-1 text-xs font-semibold cursor-pointer min-w-[7.5rem] ${statusClasses[status] || statusClasses.inactive}`;
            badge.innerHTML = `${statusLabels[status] || status}<i class="bi bi-chevron-down text-[9px]"></i>`;
            if (row) row.dataset.status = status;
            if (typeof window.showLibraryToast === 'function') {
                window.showLibraryToast('success', `Estado cambiado a "${data.label}"`);
            }
        } catch (err) {
            if (typeof window.showLibraryToast === 'function') {
                window.showLibraryToast('error', err.message || 'No se pudo cambiar el estado.');
            }
        }
    };

    // ── Reset password modal ──────────────────────────────────────────────────
    const resetPwdModal    = document.getElementById('user-reset-pwd-modal');
    const resetPwdSubtitle = document.getElementById('user-reset-pwd-modal-subtitle');
    const resetPwdInput    = document.getElementById('user-reset-pwd-input');
    const resetPwdCopy     = document.getElementById('user-reset-pwd-copy');
    const resetPwdCancel   = document.getElementById('user-reset-pwd-modal-cancel');
    const resetPwdConfirm  = document.getElementById('user-reset-pwd-modal-confirm');
    let resetPwdTargetId   = null;

    const genTempPwd = () => {
        const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#';
        return Array.from(crypto.getRandomValues(new Uint8Array(12)), (b) => chars[b % chars.length]).join('');
    };

    const openResetPwdModal = (userId, userName) => {
        resetPwdTargetId = userId;
        if (resetPwdSubtitle) resetPwdSubtitle.textContent = userName;
        if (resetPwdInput) resetPwdInput.value = genTempPwd();
        resetPwdModal?.classList.remove('hidden');
        resetPwdModal?.setAttribute('aria-hidden', 'false');
    };

    const closeResetPwdModal = () => {
        resetPwdModal?.classList.add('hidden');
        resetPwdModal?.setAttribute('aria-hidden', 'true');
        resetPwdTargetId = null;
    };

    resetPwdCopy?.addEventListener('click', () => {
        navigator.clipboard?.writeText(resetPwdInput?.value || '').then(() => {
            resetPwdCopy.innerHTML = '<i class="bi bi-check2 text-sm"></i>';
            setTimeout(() => { resetPwdCopy.innerHTML = '<i class="bi bi-clipboard text-sm"></i>'; }, 1500);
        });
    });

    resetPwdCancel?.addEventListener('click', closeResetPwdModal);

    resetPwdConfirm?.addEventListener('click', async () => {
        if (!resetPwdTargetId) return;
        const tempPwd = resetPwdInput?.value || '';
        try {
            const res = await fetch(`<?= BASE_URL ?>/admin/users/${resetPwdTargetId}/reset-password`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `password=${encodeURIComponent(tempPwd)}&_csrf_token=${encodeURIComponent(csrfToken)}`,
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Error');
            closeResetPwdModal();
            if (typeof window.showLibraryToast === 'function') {
                window.showLibraryToast('success', 'Contraseña reseteada. El usuario deberá cambiarla al ingresar.');
            }
        } catch (err) {
            if (typeof window.showLibraryToast === 'function') {
                window.showLibraryToast('error', err.message || 'No se pudo resetear la contraseña.');
            }
        }
    });

    // ── Delete modal ──────────────────────────────────────────────────────────
    const deleteModal    = document.getElementById('user-delete-modal');
    const deleteSubtitle = document.getElementById('user-delete-modal-subtitle');
    const deleteError    = document.getElementById('user-delete-modal-error');
    const deleteCancel   = document.getElementById('user-delete-modal-cancel');
    const deleteConfirm  = document.getElementById('user-delete-modal-confirm');
    let deleteTargetId   = null;

    const openDeleteModal = (userId, userName) => {
        deleteTargetId = userId;
        if (deleteSubtitle) deleteSubtitle.textContent = userName;
        if (deleteError) { deleteError.textContent = ''; deleteError.classList.add('hidden'); }
        deleteModal?.classList.remove('hidden');
        deleteModal?.setAttribute('aria-hidden', 'false');
    };

    const closeDeleteModal = () => {
        deleteModal?.classList.add('hidden');
        deleteModal?.setAttribute('aria-hidden', 'true');
        deleteTargetId = null;
    };

    deleteCancel?.addEventListener('click', closeDeleteModal);

    deleteConfirm?.addEventListener('click', async () => {
        if (!deleteTargetId) return;
        try {
            deleteConfirm.disabled = true;
            const res = await fetch(`<?= BASE_URL ?>/admin/users/${deleteTargetId}/delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `_csrf_token=${encodeURIComponent(csrfToken)}`,
            });
            const data = await res.json();
            if (!data.ok) {
                if (deleteError) { deleteError.textContent = data.error || 'No se puede eliminar.'; deleteError.classList.remove('hidden'); }
                return;
            }
            // Remove row from DOM and rows array
            const row = tableBody.querySelector(`tr[data-user-id="${deleteTargetId}"]`);
            if (row) {
                const idx = rows.indexOf(row);
                if (idx !== -1) rows.splice(idx, 1);
                row.remove();
            }
            closeDeleteModal();
            render();
            if (typeof window.showLibraryToast === 'function') {
                window.showLibraryToast('success', 'Usuario eliminado correctamente.');
            }
        } catch (err) {
            if (deleteError) { deleteError.textContent = 'Error de conexión.'; deleteError.classList.remove('hidden'); }
        } finally {
            deleteConfirm.disabled = false;
        }
    });

    // ── Type change modal ─────────────────────────────────────────────────────
    const typeModal        = document.getElementById('user-type-modal');
    const typeModalSubtitle = document.getElementById('user-type-modal-subtitle');
    const typeModalCancel  = document.getElementById('user-type-modal-cancel');
    const typeModalConfirm = document.getElementById('user-type-modal-confirm');
    let typeTargetId       = null;
    let typeTargetRow      = null;

    const openTypeModal = (userId, userName, currentType) => {
        typeTargetId  = userId;
        typeTargetRow = tableBody.querySelector(`tr[data-user-id="${userId}"]`);
        if (typeModalSubtitle) typeModalSubtitle.textContent = userName;
        // Pre-select current type
        typeModal?.querySelectorAll('input[name="new_user_type"]').forEach((r) => {
            r.checked = r.value === currentType;
        });
        typeModal?.classList.remove('hidden');
        typeModal?.setAttribute('aria-hidden', 'false');
    };

    const closeTypeModal = () => {
        typeModal?.classList.add('hidden');
        typeModal?.setAttribute('aria-hidden', 'true');
        typeTargetId  = null;
        typeTargetRow = null;
    };

    typeModalCancel?.addEventListener('click', closeTypeModal);

    typeModalConfirm?.addEventListener('click', async () => {
        if (!typeTargetId) return;
        const selected = typeModal?.querySelector('input[name="new_user_type"]:checked');
        if (!selected) return;
        const newType = selected.value;
        try {
            typeModalConfirm.disabled = true;
            const res = await fetch(`<?= BASE_URL ?>/admin/users/${typeTargetId}/type`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_type=${encodeURIComponent(newType)}&_csrf_token=${encodeURIComponent(csrfToken)}`,
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Error');

            // Update the type button in the row
            if (typeTargetRow) {
                const typeBtn = typeTargetRow.querySelector('[data-cell="type"]');
                if (typeBtn) {
                    typeBtn.dataset.type = newType;
                    typeBtn.innerHTML = `${typeLabels[newType] || 'Usuario'}<i class="bi bi-chevron-down text-[9px]"></i>`;
                }
                typeTargetRow.dataset.type = newType;
            }
            closeTypeModal();
            if (typeof window.showLibraryToast === 'function') {
                window.showLibraryToast('success', `Tipo cambiado a "${data.label}"`);
            }
        } catch (err) {
            if (typeof window.showLibraryToast === 'function') {
                window.showLibraryToast('error', err.message || 'No se pudo cambiar el tipo.');
            }
        } finally {
            typeModalConfirm.disabled = false;
        }
    });

    // ── Bind existing rows ────────────────────────────────────────────────────
    rows.forEach((tr) => bindRowActions(tr));

    render();
})();
</script>
