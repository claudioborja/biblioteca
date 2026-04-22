<?php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<section class="p-6 lg:p-8">

    <!-- Header -->
    <div class="mb-7 flex items-center gap-3">
        <a href="<?= BASE_URL ?>/teacher/suggestions"
           class="inline-flex items-center gap-1.5 rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface-muted hover:bg-surface-container-low transition-colors">
            <?= Icons::arrowLeft('w-4 h-4') ?>
        </a>
        <div>
            <p class="label-sm">Docente · Sugerencias</p>
            <h1 class="headline-lg text-on-surface">Nueva sugerencia</h1>
        </div>
    </div>

    <?php if ($flash = \Core\Session::get('flash.error')): ?>
        <div class="mb-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <?= $e($flash) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/teacher/suggestions" class="max-w-2xl space-y-6">
        <input type="hidden" name="_csrf_token" value="<?= $e(\Core\Session::get('_csrf_token', '')) ?>">

        <div class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient space-y-5">

            <!-- Título -->
            <div>
                <label for="title" class="label-sm block mb-1">
                    Título del recurso <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" required maxlength="255"
                       class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                       placeholder="Ej. Introducción a la Programación">
            </div>

            <!-- Autor -->
            <div>
                <label for="author" class="label-sm block mb-1">Autor / Editor</label>
                <input type="text" id="author" name="author" maxlength="255"
                       class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                       placeholder="Ej. Donald Knuth">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <!-- ISBN -->
                <div>
                    <label for="isbn" class="label-sm block mb-1">ISBN (opcional)</label>
                    <input type="text" id="isbn" name="isbn" maxlength="17"
                           class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-2.5 text-sm text-on-surface font-mono placeholder-on-surface-subtle focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                           placeholder="978-0-00-000000-0">
                </div>
                <!-- Editorial -->
                <div>
                    <label for="publisher" class="label-sm block mb-1">Editorial (opcional)</label>
                    <input type="text" id="publisher" name="publisher" maxlength="200"
                           class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                           placeholder="Ej. Pearson">
                </div>
            </div>

            <!-- Justificación -->
            <div>
                <label for="reason" class="label-sm block mb-1">¿Por qué lo recomiendas?</label>
                <textarea id="reason" name="reason" rows="4" maxlength="2000"
                          class="w-full rounded-xl border border-outline-variant bg-surface-container-low px-4 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary resize-none"
                          placeholder="Explica brevemente por qué este recurso sería útil para tus estudiantes..."></textarea>
                <p class="mt-1 text-xs text-on-surface-subtle">Máximo 2 000 caracteres.</p>
            </div>

        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="<?= BASE_URL ?>/teacher/suggestions"
               class="rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-sm font-semibold text-on-surface-muted hover:bg-surface-container-low transition-colors">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90 transition-opacity">
                <?= Icons::save('w-4 h-4') ?> Enviar sugerencia
            </button>
        </div>
    </form>

</section>
