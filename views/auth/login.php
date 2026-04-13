<?php
// views/auth/login.php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<div>
    <h1 class="headline-md text-on-surface mb-1">Iniciar sesión</h1>
    <p class="text-sm text-on-surface-subtle mb-8">Accede a tu cuenta de socio</p>

    <form method="POST" action="<?= BASE_URL ?>/login" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
        <?php if ($redirect !== ''): ?>
            <input type="hidden" name="redirect" value="<?= $e($redirect) ?>">
        <?php endif; ?>

        <!-- Email -->
        <div class="mb-5">
            <label for="email" class="block text-sm font-medium text-on-surface mb-1.5">
                Correo electrónico
            </label>
            <input type="email"
                   id="email"
                   name="email"
                   autocomplete="email"
                   required
                   class="w-full px-3.5 py-2.5 text-sm text-on-surface bg-surface-container-lowest border border-outline-variant rounded-[0.625rem] transition-colors duration-200 placeholder-on-surface-subtle"
                   placeholder="tu@correo.com">
        </div>

        <!-- Password -->
        <div class="mb-5">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-medium text-on-surface">
                    Contraseña
                </label>
                <a href="<?= BASE_URL ?>/forgot-password"
                   class="text-xs text-primary hover:text-primary-muted font-medium transition-colors">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>
            <div class="relative">
                <input type="password"
                       id="password"
                       name="password"
                       autocomplete="current-password"
                       required
                       class="w-full px-3.5 py-2.5 text-sm text-on-surface bg-surface-container-lowest border border-outline-variant rounded-[0.625rem] transition-colors duration-200 pr-11"
                       placeholder="••••••••">
                <button type="button"
                        onclick="togglePassword('password', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-subtle hover:text-on-surface transition-colors"
                        aria-label="Mostrar contraseña">
                    <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Remember me -->
        <div class="flex items-center gap-2.5 mb-6">
            <input type="checkbox"
                   id="remember"
                   name="remember"
                   value="1"
                   class="w-4 h-4 rounded border-outline-variant text-primary accent-primary cursor-pointer">
            <label for="remember" class="text-sm text-on-surface-muted cursor-pointer select-none">
                Recuérdame por 30 días
            </label>
        </div>

        <!-- Submit -->
        <button type="submit"
                class="w-full py-2.5 px-4 bg-primary hover:bg-primary-muted text-on-primary font-semibold text-sm rounded-[0.625rem] transition-colors duration-200 shadow-ambient inline-flex items-center justify-center gap-2">
            <?= Icons::arrowRight('w-4 h-4') ?> Iniciar sesión
        </button>
    </form>

    <!-- Divider -->
    <div class="flex items-center gap-3 my-6">
        <div class="flex-1 h-px bg-outline-variant"></div>
        <span class="text-xs text-on-surface-subtle">¿No tienes cuenta?</span>
        <div class="flex-1 h-px bg-outline-variant"></div>
    </div>

    <a href="<?= BASE_URL ?>/register"
       class="flex items-center justify-center w-full py-2.5 px-4 border border-outline-variant hover:border-primary/40 hover:bg-surface-container-low text-sm font-medium text-on-surface rounded-[0.625rem] transition-colors duration-200">
        Crear nueva cuenta
    </a>

    <p class="text-center mt-6">
        <a href="<?= BASE_URL ?>/" class="text-xs text-on-surface-subtle hover:text-primary transition-colors">
            ← Volver al sitio
        </a>
    </p>
</div>

<script>
function togglePassword(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const isText = field.type === 'text';
    field.type = isText ? 'password' : 'text';
    btn.setAttribute('aria-label', isText ? 'Mostrar contraseña' : 'Ocultar contraseña');
}
</script>
