<?php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$old = $old ?? ['name' => '', 'description' => '', 'school_year' => ''];
?>

<section class="p-6 lg:p-8">
    <div class="mb-7">
        <a href="<?= BASE_URL ?>/teacher/groups" class="label-sm hover:text-primary transition-colors">← Mis grupos</a>
        <h1 class="headline-lg mt-1 text-on-surface">Nuevo grupo</h1>
        <p class="body-md mt-1">Crea un nuevo grupo para gestionar estudiantes y asignaciones de lectura.</p>
    </div>

    <?php $flashErr = \Core\Session::getFlash('error'); if ($flashErr): ?>
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $e($flashErr) ?></div>
    <?php endif; ?>

    <div class="mx-auto max-w-xl">
        <form method="POST" action="<?= BASE_URL ?>/teacher/groups" class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient space-y-5">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">

            <div>
                <label for="name" class="label-sm block mb-1.5">Nombre del grupo <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" required maxlength="150"
                       value="<?= $e($old['name']) ?>"
                       placeholder="Ej: 3ro Bachillerato A"
                       class="w-full rounded-xl border border-outline-variant bg-white px-3.5 py-2.5 text-sm text-on-surface placeholder:text-on-surface-subtle focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>

            <div>
                <label for="school_year" class="label-sm block mb-1.5">Año / periodo escolar <span class="text-red-500">*</span></label>
                <input type="text" id="school_year" name="school_year" required maxlength="20"
                       value="<?= $e($old['school_year']) ?>"
                       placeholder="Ej: 2024-2025"
                       class="w-full rounded-xl border border-outline-variant bg-white px-3.5 py-2.5 text-sm text-on-surface placeholder:text-on-surface-subtle focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>

            <div>
                <label for="description" class="label-sm block mb-1.5">Descripción (opcional)</label>
                <textarea id="description" name="description" rows="3" maxlength="1000"
                          placeholder="Descripción breve del grupo…"
                          class="w-full rounded-xl border border-outline-variant bg-white px-3.5 py-2.5 text-sm text-on-surface placeholder:text-on-surface-subtle focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 resize-none"><?= $e($old['description']) ?></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <a href="<?= BASE_URL ?>/teacher/groups"
                   class="flex-1 rounded-xl border border-outline-variant py-2.5 text-center text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="flex-1 rounded-xl gradient-scholar py-2.5 text-sm font-semibold text-white shadow-ambient hover:opacity-90 transition-opacity">
                    Crear grupo
                </button>
            </div>
        </form>
    </div>
</section>
