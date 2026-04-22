<?php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$old = $old ?? ['name' => $group['name'] ?? '', 'description' => $group['description'] ?? '', 'school_year' => $group['school_year'] ?? ''];
?>

<section class="p-6 lg:p-8">
    <div class="mb-7">
        <a href="<?= BASE_URL ?>/teacher/groups/<?= (int)$group['id'] ?>" class="label-sm hover:text-primary transition-colors">← Volver al grupo</a>
        <h1 class="headline-lg mt-1 text-on-surface">Editar grupo</h1>
        <p class="body-md mt-1">Modifica los datos del grupo.</p>
    </div>

    <?php $flashErr = \Core\Session::getFlash('error'); if ($flashErr): ?>
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $e($flashErr) ?></div>
    <?php endif; ?>

    <div class="mx-auto max-w-xl">
        <form method="POST" action="<?= BASE_URL ?>/teacher/groups/<?= (int)$group['id'] ?>"
              class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient space-y-5">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">

            <div>
                <label for="name" class="label-sm block mb-1.5">Nombre del grupo <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" required maxlength="150"
                       value="<?= $e($old['name']) ?>"
                       class="w-full rounded-xl border border-outline-variant bg-white px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>

            <div>
                <label for="school_year" class="label-sm block mb-1.5">Año / periodo escolar <span class="text-red-500">*</span></label>
                <input type="text" id="school_year" name="school_year" required maxlength="20"
                       value="<?= $e($old['school_year']) ?>"
                       class="w-full rounded-xl border border-outline-variant bg-white px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>

            <div>
                <label for="description" class="label-sm block mb-1.5">Descripción (opcional)</label>
                <textarea id="description" name="description" rows="3" maxlength="1000"
                          class="w-full rounded-xl border border-outline-variant bg-white px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 resize-none"><?= $e($old['description']) ?></textarea>
            </div>

            <div>
                <label class="label-sm block mb-1.5">Estado</label>
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="checkbox" name="is_active" value="1"
                           <?= (int)($group['is_active'] ?? 1) ? 'checked' : '' ?>
                           class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary/20">
                    <span class="text-sm text-on-surface">Grupo activo</span>
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <a href="<?= BASE_URL ?>/teacher/groups/<?= (int)$group['id'] ?>"
                   class="flex-1 rounded-xl border border-outline-variant py-2.5 text-center text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="flex-1 rounded-xl gradient-scholar py-2.5 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</section>
