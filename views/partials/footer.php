<footer class="bg-primary text-white/70 mt-auto" role="contentinfo">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
            <!-- Brand -->
            <div class="lg:col-span-1">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-[0.375rem] bg-white/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <span class="text-white font-bold text-lg font-display"><?= htmlspecialchars($settings['library_name'] ?? 'Biblioteca', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php if (!empty($settings['library_slogan'])): ?>
                    <p class="text-white/50 text-sm leading-relaxed"><?= htmlspecialchars($settings['library_slogan'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-tertiary-light font-semibold text-sm uppercase tracking-wider mb-4 font-display">Navegación</h4>
                <ul class="space-y-3">
                    <li><a href="<?= BASE_URL ?>/" class="text-white/50 hover:text-white text-sm transition-colors duration-200">Inicio</a></li>
                    <li><a href="<?= BASE_URL ?>/catalog" class="text-white/50 hover:text-white text-sm transition-colors duration-200">Catálogo</a></li>
                    <li><a href="<?= BASE_URL ?>/new-arrivals" class="text-white/50 hover:text-white text-sm transition-colors duration-200">Nuevas Adquisiciones</a></li>
                    <li><a href="<?= BASE_URL ?>/news" class="text-white/50 hover:text-white text-sm transition-colors duration-200">Noticias</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h4 class="text-tertiary-light font-semibold text-sm uppercase tracking-wider mb-4 font-display">Contacto</h4>
                <ul class="space-y-3 text-sm">
                    <?php if (!empty($settings['library_address'])): ?>
                        <li class="flex items-start gap-3 text-white/50">
                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                            </svg>
                            <span><?= htmlspecialchars($settings['library_address'], ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($settings['library_phone'])): ?>
                        <li class="flex items-center gap-3 text-white/50">
                            <svg class="w-4 h-4 shrink-0 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                            </svg>
                            <span><?= htmlspecialchars($settings['library_phone'], ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($settings['library_email'])): ?>
                        <li class="flex items-center gap-3 text-white/50">
                            <svg class="w-4 h-4 shrink-0 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                            </svg>
                            <a href="mailto:<?= htmlspecialchars($settings['library_email'], ENT_QUOTES, 'UTF-8') ?>" class="hover:text-white transition-colors duration-200"><?= htmlspecialchars($settings['library_email'], ENT_QUOTES, 'UTF-8') ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Schedule -->
            <div>
                <h4 class="text-tertiary-light font-semibold text-sm uppercase tracking-wider mb-4 font-display">Horario</h4>
                <?php if (!empty($settings['library_schedule'])): ?>
                    <div class="text-sm text-white/50 space-y-1">
                        <?php foreach (explode('|', $settings['library_schedule']) as $line): ?>
                            <p><?= htmlspecialchars(trim($line), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bottom bar — tonal shift, no border -->
    <div class="bg-black/15">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-white/35">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($settings['library_name'] ?? 'Biblioteca', ENT_QUOTES, 'UTF-8') ?>. Todos los derechos reservados.</p>
                <p class="inline-flex items-center justify-center sm:justify-start gap-1.5 text-center sm:text-left">
                    <span>Sistema de Gestión Bibliotecaria desarrollado con</span>
                    <svg class="h-3.5 w-3.5 text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10 17.583l-1.087-.992C4.39 12.465 1.5 9.819 1.5 6.583 1.5 3.937 3.573 2 6.125 2c1.443 0 2.827.69 3.875 1.779C11.048 2.69 12.432 2 13.875 2 16.427 2 18.5 3.937 18.5 6.583c0 3.236-2.89 5.882-7.413 10.008L10 17.583z"/>
                    </svg>
                    <span>por</span>
                    <a href="https://softecsa.com" target="_blank" rel="noopener noreferrer" class="text-current hover:underline transition-colors duration-200">
                        SOFTECAPPS S.A.S.
                    </a>
                </p>
            </div>
        </div>
    </div>
</footer>
