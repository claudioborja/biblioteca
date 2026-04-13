<?php
// views/admin/branches/create.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="p-6 lg:p-8">

    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Administración</p>
            <h1 class="headline-lg text-on-surface">Nueva sede</h1>
            <p class="body-md mt-1">Registra una nueva sede o sucursal de la biblioteca.</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/branches"
           class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors inline-flex items-center gap-2">
            <i class="bi bi-arrow-left text-sm"></i> Volver a sedes
        </a>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/admin/branches" class="space-y-6">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">

        <div class="rounded-3xl border border-outline-variant/60 bg-white p-5 shadow-ambient-lg lg:p-6 space-y-5">

            <div>
                <h2 class="headline-md text-on-surface">Información general</h2>
                <p class="body-md mt-1">Datos identificativos y de contacto de la nueva sede.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label for="code" class="label-sm">Código <span class="text-red-500">*</span></label>
                    <input id="code" name="code" type="text" required maxlength="10"
                           value="<?= $e($old['code'] ?? '') ?>"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm uppercase focus:border-primary focus:outline-none"
                           placeholder="Ej. CENT">
                    <p class="mt-1 text-xs text-on-surface-subtle">Máximo 10 caracteres, se guardará en mayúsculas.</p>
                </div>
                <div class="xl:col-span-2">
                    <label for="name" class="label-sm">Nombre <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" required maxlength="150"
                           value="<?= $e($old['name'] ?? '') ?>"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"
                           placeholder="Ej. Sede Central">
                </div>
                <div class="xl:col-span-3">
                    <label for="address" class="label-sm">Dirección <span class="text-red-500">*</span></label>
                    <textarea id="address" name="address" rows="2" required
                              class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"
                              placeholder="Calle, número, colonia, ciudad"><?= $e($old['address'] ?? '') ?></textarea>
                </div>
                <div>
                    <label for="phone" class="label-sm">Teléfono</label>
                    <input id="phone" name="phone" type="tel" maxlength="30"
                           value="<?= $e($old['phone'] ?? '') ?>"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"
                           placeholder="Ej. 55 1234 5678">
                </div>
                <div>
                    <label for="email" class="label-sm">Correo electrónico</label>
                    <input id="email" name="email" type="email" maxlength="150"
                           value="<?= $e($old['email'] ?? '') ?>"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"
                           placeholder="sede@biblioteca.mx">
                </div>
                <div>
                    <label for="manager_id" class="label-sm">Responsable</label>
                    <select id="manager_id" name="manager_id"
                            class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                        <option value="">Sin responsable asignado</option>
                        <?php foreach ($managers as $manager): ?>
                            <option value="<?= (int) $manager['id'] ?>"
                                <?= (string) ($old['manager_id'] ?? '') === (string) $manager['id'] ? 'selected' : '' ?>>
                                <?= $e($manager['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="xl:col-span-3">
                    <label for="schedule" class="label-sm">Horario de atención</label>
                    <textarea id="schedule" name="schedule" rows="2"
                              class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none"
                              placeholder="Ej. Lunes a viernes 8:00–20:00, Sábados 9:00–14:00"><?= $e($old['schedule'] ?? '') ?></textarea>
                </div>
            </div>

            <hr class="border-outline-variant/50">

            <div>
                <h2 class="headline-md text-on-surface">Configuración</h2>
                <p class="body-md mt-1">Visibilidad y orden de aparición de la sede.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="status" class="label-sm">Estado</label>
                    <select id="status" name="status"
                            class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                        <option value="active" <?= ($old['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Activa</option>
                        <option value="inactive" <?= ($old['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactiva</option>
                    </select>
                </div>
                <div>
                    <label for="sort_order" class="label-sm">Orden de aparición</label>
                    <input id="sort_order" name="sort_order" type="number" min="0" max="127"
                           value="<?= $e($old['sort_order'] ?? '0') ?>"
                           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    <p class="mt-1 text-xs text-on-surface-subtle">Número menor aparece primero.</p>
                </div>
                <div>
                    <label for="is_main" class="label-sm">Sede principal</label>
                    <select id="is_main" name="is_main"
                            class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                        <option value="0" <?= ($old['is_main'] ?? '0') === '0' ? 'selected' : '' ?>>No</option>
                        <option value="1" <?= ($old['is_main'] ?? '0') === '1' ? 'selected' : '' ?>>Sí</option>
                    </select>
                </div>
            </div>

        </div>

        <div class="rounded-2xl border border-outline-variant/60 bg-white px-4 py-3 shadow-ambient">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity">
                        <i class="bi bi-plus-lg text-sm"></i> Crear sede
                    </button>
                    <a href="<?= BASE_URL ?>/admin/branches"
                       class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                        <i class="bi bi-x-lg text-sm"></i> Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>

</section>
