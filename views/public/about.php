<?php
/**
 * Vista: Nosotros
 */
use Core\View;
?>

<!-- Hero -->
<section class="py-20 gradient-scholar text-on-primary">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="label-sm text-white/60 font-display uppercase tracking-[0.12em] mb-3\"><?= View::e($settings['about_hero_badge'] ?: 'Quiénes somos') ?></p>
        <h1 class="headline-xl text-white\"><?= View::e($settings['about_hero_title'] ?: ($settings['library_name'] ?: 'Nuestra Biblioteca')) ?></h1>
        <?php $aboutSubtitle = (string) ($settings['about_hero_subtitle'] ?: ($settings['library_slogan'] ?? '')); ?>
        <?php if ($aboutSubtitle !== ''): ?>
            <p class="mt-4 text-white/70 text-lg max-w-2xl mx-auto leading-relaxed\"><?= View::e($aboutSubtitle) ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- Misión / Visión / Valores -->
<section class="py-20 bg-surface-container-lowest">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

            <!-- Misión -->
            <div class="bg-surface rounded-[1rem] p-8 shadow-ambient flex flex-col gap-4">
                <div class="w-12 h-12 rounded-[0.75rem] bg-primary/10 flex items-center justify-center text-primary">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/>
                    </svg>
                </div>
                <h2 class="title-lg font-display text-on-surface\"><?= View::e($settings['about_mission_title'] ?: 'Misión') ?></h2>
                <p class="text-on-surface-subtle leading-relaxed\"><?= View::e($settings['about_mission_text'] ?: 'Promover el acceso libre al conocimiento y fomentar el hábito lector en nuestra comunidad, ofreciendo un espacio acogedor, inclusivo y actualizado para todas las edades.') ?></p>
            </div>

            <!-- Visión -->
            <div class="bg-surface rounded-[1rem] p-8 shadow-ambient flex flex-col gap-4">
                <div class="w-12 h-12 rounded-[0.75rem] bg-secondary/10 flex items-center justify-center text-secondary">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h2 class="title-lg font-display text-on-surface\"><?= View::e($settings['about_vision_title'] ?: 'Visión') ?></h2>
                <p class="text-on-surface-subtle leading-relaxed\"><?= View::e($settings['about_vision_text'] ?: 'Ser el centro cultural de referencia de la región, reconocida por la excelencia de sus servicios, la riqueza de su colección y su compromiso con la educación permanente.') ?></p>
            </div>

            <!-- Valores -->
            <div class="bg-surface rounded-[1rem] p-8 shadow-ambient flex flex-col gap-4">
                <div class="w-12 h-12 rounded-[0.75rem] bg-tertiary/10 flex items-center justify-center text-tertiary">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                    </svg>
                </div>
                <h2 class="title-lg font-display text-on-surface\"><?= View::e($settings['about_values_title'] ?: 'Valores') ?></h2>
                <ul class="text-on-surface-subtle space-y-1.5 leading-relaxed">
                    <?php foreach (($about_values ?? []) as $value): ?>
                        <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-tertiary shrink-0"></span><?= View::e($value) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </div>
    </div>
</section>

<!-- Historia -->
<section class="py-20 bg-surface">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

            <div>
                <p class="label-sm text-tertiary font-display uppercase tracking-[0.12em] mb-3\"><?= View::e($settings['about_history_badge'] ?: 'Nuestra historia') ?></p>
                <h2 class="headline-lg text-on-surface mb-6\"><?= View::e($settings['about_history_title'] ?: 'Más de una década al servicio de la comunidad') ?></h2>
                <div class="space-y-4 text-on-surface-subtle leading-relaxed">
                    <p><?= nl2br(View::e($about_history_text ?? '')) ?></p>
                </div>
            </div>

            <!-- Línea de tiempo -->
            <div class="relative pl-6 border-l-2 border-surface-container space-y-8">
                <?php foreach (($about_timeline ?? []) as $item): ?>
                <div class="relative">
                    <span class="absolute -left-[1.6rem] top-1.5 w-3.5 h-3.5 rounded-full bg-primary border-2 border-surface"></span>
                    <p class="label-sm text-primary font-display font-bold mb-1\"><?= View::e((string) ($item['year'] ?? '')) ?></p>
                    <p class="text-sm text-on-surface-subtle leading-relaxed\"><?= View::e((string) ($item['text'] ?? '')) ?></p>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</section>

<!-- Contacto / Datos -->
<section class="py-20 bg-surface-container-lowest">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <p class="label-sm text-tertiary font-display uppercase tracking-[0.12em] mb-3\"><?= View::e($settings['about_contact_badge'] ?: 'Encuéntranos') ?></p>
            <h2 class="headline-lg text-on-surface\"><?= View::e($settings['about_contact_title'] ?: 'Información de contacto') ?></h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-5xl mx-auto">

            <?php if (!empty($settings['library_address'])): ?>
            <div class="bg-surface rounded-[1rem] p-6 shadow-ambient text-center flex flex-col items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                </div>
                <p class="label-sm text-on-surface-subtle uppercase tracking-wider">Dirección</p>
                <p class="text-sm text-on-surface text-center leading-snug"><?= View::e($settings['library_address']) ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($settings['library_phone'])): ?>
            <div class="bg-surface rounded-[1rem] p-6 shadow-ambient text-center flex flex-col items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-secondary/10 flex items-center justify-center text-secondary">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                </div>
                <p class="label-sm text-on-surface-subtle uppercase tracking-wider">Teléfono</p>
                <a href="tel:<?= View::e($settings['library_phone']) ?>" class="text-sm text-on-surface hover:text-primary transition-colors"><?= View::e($settings['library_phone']) ?></a>
            </div>
            <?php endif; ?>

            <?php if (!empty($settings['library_email'])): ?>
            <div class="bg-surface rounded-[1rem] p-6 shadow-ambient text-center flex flex-col items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-tertiary/10 flex items-center justify-center text-tertiary">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                </div>
                <p class="label-sm text-on-surface-subtle uppercase tracking-wider">Email</p>
                <a href="mailto:<?= View::e($settings['library_email']) ?>" class="text-sm text-on-surface hover:text-primary transition-colors break-all"><?= View::e($settings['library_email']) ?></a>
            </div>
            <?php endif; ?>

            <?php if (!empty($settings['library_schedule'])): ?>
            <div class="bg-surface rounded-[1rem] p-6 shadow-ambient text-center flex flex-col items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="label-sm text-on-surface-subtle uppercase tracking-wider">Horario</p>
                <p class="text-sm text-on-surface text-center leading-snug"><?= nl2br(View::e($settings['library_schedule'])) ?></p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<?php include BASE_PATH . '/views/partials/cta-register.php'; ?>
