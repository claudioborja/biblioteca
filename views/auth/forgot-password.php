<?php
// views/auth/forgot-password.php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<div>
    <!-- Back link -->
    <a href="<?= BASE_URL ?>/login"
       class="inline-flex items-center gap-1.5 text-xs text-on-surface-subtle hover:text-primary transition-colors mb-6">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Volver al inicio de sesión
    </a>

    <div class="mb-8">
        <div class="w-11 h-11 rounded-[0.75rem] bg-surface-container-low border border-outline-variant flex items-center justify-center mb-5">
            <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
        </div>
        <h1 class="headline-md text-on-surface mb-1">Recuperar contraseña</h1>
        <p class="text-sm text-on-surface-subtle leading-relaxed">
            Ingresa tu correo electrónico y te enviaremos las instrucciones para restablecer tu contraseña.
        </p>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/forgot-password" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">

        <div class="mb-6">
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

        <button type="submit"
                class="w-full py-2.5 px-4 bg-primary hover:bg-primary-muted text-on-primary font-semibold text-sm rounded-[0.625rem] transition-colors duration-200 shadow-ambient inline-flex items-center justify-center gap-2">
            <?= Icons::arrowRight('w-4 h-4') ?> Enviar instrucciones
        </button>
    </form>

    <p class="text-center mt-8 text-xs text-on-surface-subtle leading-relaxed">
        Si el correo está registrado, recibirás un enlace válido por <strong>60 minutos</strong>.<br>
        Revisa también tu carpeta de spam.
    </p>
</div>
