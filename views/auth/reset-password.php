<?php
// views/auth/reset-password.php
use Helpers\Icons;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<div>
    <div class="mb-8">
        <div class="w-11 h-11 rounded-[0.75rem] bg-surface-container-low border border-outline-variant flex items-center justify-center mb-5">
            <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </svg>
        </div>
        <h1 class="headline-md text-on-surface mb-1">Nueva contraseña</h1>
        <p class="text-sm text-on-surface-subtle">Elige una contraseña segura para tu cuenta.</p>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/reset-password" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
        <input type="hidden" name="token" value="<?= $e($token) ?>">
        <input type="hidden" name="email" value="<?= $e($email) ?>">

        <!-- New password -->
        <div class="mb-5">
            <label for="password" class="block text-sm font-medium text-on-surface mb-1.5">
                Nueva contraseña
                <span class="text-on-surface-subtle font-normal">(mínimo 8 caracteres)</span>
            </label>
            <div class="relative">
                <input type="password"
                       id="password"
                       name="password"
                       autocomplete="new-password"
                       required
                       minlength="8"
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
            <!-- Strength bar -->
            <div class="mt-2 h-1 rounded-full bg-surface-container-high overflow-hidden">
                <div id="strength-bar" class="h-full rounded-full transition-all duration-300 w-0 bg-on-surface-subtle"></div>
            </div>
        </div>

        <!-- Confirm -->
        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-medium text-on-surface mb-1.5">
                Confirmar nueva contraseña
            </label>
            <div class="relative">
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       autocomplete="new-password"
                       required
                       class="w-full px-3.5 py-2.5 text-sm text-on-surface bg-surface-container-lowest border border-outline-variant rounded-[0.625rem] transition-colors duration-200 pr-11"
                       placeholder="••••••••">
                <button type="button"
                        onclick="togglePassword('password_confirmation', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-subtle hover:text-on-surface transition-colors"
                        aria-label="Mostrar contraseña">
                    <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>
            </div>
            <p id="match-msg" class="text-xs mt-1 hidden text-error font-medium">Las contraseñas no coinciden</p>
        </div>

        <button type="submit"
                class="w-full py-2.5 px-4 bg-primary hover:bg-primary-muted text-on-primary font-semibold text-sm rounded-[0.625rem] transition-colors duration-200 shadow-ambient inline-flex items-center justify-center gap-2">
            <?= Icons::save('w-4 h-4') ?> Guardar nueva contraseña
        </button>
    </form>

    <p class="text-center mt-6">
        <a href="<?= BASE_URL ?>/login" class="text-xs text-on-surface-subtle hover:text-primary transition-colors">
            ← Volver al inicio de sesión
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

(function () {
    const pw      = document.getElementById('password');
    const conf    = document.getElementById('password_confirmation');
    const bar     = document.getElementById('strength-bar');
    const matchMsg= document.getElementById('match-msg');

    pw.addEventListener('input', function () {
        const v = pw.value;
        let score = 0;
        if (v.length >= 8) score++;
        if (/[A-Z]/.test(v)) score++;
        if (/[0-9]/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;

        bar.style.width = (score / 4 * 100) + '%';
        bar.className = 'h-full rounded-full transition-all duration-300 ' + (
            score <= 1 ? 'bg-red-400' :
            score === 2 ? 'bg-yellow-400' :
            score === 3 ? 'bg-blue-400' : 'bg-green-500'
        );
        checkMatch();
    });

    conf.addEventListener('input', checkMatch);

    function checkMatch() {
        if (conf.value === '') { matchMsg.classList.add('hidden'); return; }
        matchMsg.classList.toggle('hidden', pw.value === conf.value);
    }
})();
</script>
