<?php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Panel docente</p>
            <h1 class="headline-lg text-on-surface">Mis grupos</h1>
            <p class="body-md mt-1">Gestiona los grupos de estudiantes a tu cargo.</p>
        </div>
        <a href="<?= BASE_URL ?>/teacher/groups/create"
           class="inline-flex items-center gap-2 rounded-xl gradient-scholar px-5 py-2.5 text-sm font-semibold text-white shadow-ambient transition-opacity hover:opacity-90">
            Nuevo grupo
        </a>
    </div>

    <?php $flash = \Core\Session::getFlash('success'); if ($flash): ?>
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= $e($flash) ?></div>
    <?php endif; ?>
    <?php $flashErr = \Core\Session::getFlash('error'); if ($flashErr): ?>
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $e($flashErr) ?></div>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-10 text-center shadow-ambient">
            <p class="headline-md text-on-surface">Todavía no tienes grupos</p>
            <p class="body-md mt-2">Crea tu primer grupo para empezar a gestionar estudiantes y asignaciones.</p>
            <a href="<?= BASE_URL ?>/teacher/groups/create"
               class="mt-5 inline-flex items-center gap-2 rounded-xl gradient-scholar px-5 py-2.5 text-sm font-semibold text-white">
                Crear primer grupo
            </a>
        </div>
    <?php else: ?>
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($groups as $group): ?>
                <article class="flex flex-col rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-ambient transition-shadow hover:shadow-md">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <h2 class="headline-sm truncate text-on-surface"><?= $e($group['name']) ?></h2>
                            <span class="mt-1 inline-block rounded-lg bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                <?= $e($group['school_year']) ?>
                            </span>
                        </div>
                        <?php if (!(int)$group['is_active']): ?>
                            <span class="shrink-0 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-600">Inactivo</span>
                        <?php endif; ?>
                    </div>

                    <p class="body-md mt-3 line-clamp-2 grow">
                        <?= $e($group['description'] ?: 'Sin descripción.') ?>
                    </p>

                    <div class="mt-4 flex items-center justify-between border-t border-outline-variant/30 pt-3 text-sm text-on-surface-muted">
                        <span><?= (int)$group['students_count'] ?> estudiantes</span>
                        <span><?= (int)$group['assignments_count'] ?> asignaciones</span>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <a href="<?= BASE_URL ?>/teacher/groups/<?= (int)$group['id'] ?>"
                           class="flex-1 rounded-xl border border-outline-variant bg-white py-2 text-center text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                            Ver grupo
                        </a>
                        <a href="<?= BASE_URL ?>/teacher/groups/<?= (int)$group['id'] ?>/edit"
                           class="flex-1 rounded-xl border border-outline-variant bg-white py-2 text-center text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                            Editar
                        </a>
                        <a href="<?= BASE_URL ?>/teacher/groups/<?= (int)$group['id'] ?>/report"
                           class="flex-1 rounded-xl gradient-scholar py-2 text-center text-sm font-semibold text-white hover:opacity-90 transition-opacity">
                            Reporte
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
