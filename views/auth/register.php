<?php
// views/auth/register.php
use Helpers\Icons;

$e              = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$oldName        = \Core\Session::getFlash('old_name', '');
$oldEmail       = \Core\Session::getFlash('old_email', '');
$oldDocumentNum = \Core\Session::getFlash('old_document_number', '');
?>

<div>
    <h1 class="headline-md text-on-surface mb-1">Crear cuenta</h1>
    <p class="text-sm text-on-surface-subtle mb-8">Regístrate para acceder al catálogo y gestionar tus préstamos</p>

    <form method="POST" action="<?= BASE_URL ?>/register" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">

        <!-- Name -->
        <div class="mb-5">
            <label for="name" class="block text-sm font-medium text-on-surface mb-1.5">
                Nombre completo
            </label>
            <input type="text"
                   id="name"
                   name="name"
                   value="<?= $e($oldName) ?>"
                   autocomplete="name"
                   required
                   class="w-full px-3.5 py-2.5 text-sm text-on-surface bg-surface-container-lowest border border-outline-variant rounded-[0.625rem] transition-colors duration-200 placeholder-on-surface-subtle"
                   placeholder="Ej. María García López">
        </div>

        <!-- Cedula -->
        <div class="mb-5">
            <label for="document_number" class="block text-sm font-medium text-on-surface mb-1.5">
                Cédula
                <span class="text-on-surface-subtle font-normal">(Ecuador, 10 dígitos)</span>
            </label>
            <input type="text"
                   id="document_number"
                   name="document_number"
                   value="<?= $e($oldDocumentNum) ?>"
                   autocomplete="off"
                   required
                   maxlength="10"
                   pattern="[0-9]{10}"
                   inputmode="numeric"
                   class="w-full px-3.5 py-2.5 text-sm text-on-surface bg-surface-container-lowest border border-outline-variant rounded-[0.625rem] transition-colors duration-200 placeholder-on-surface-subtle"
                   placeholder="Ej. 1712345678">
        </div>

        <!-- Email -->
        <div class="mb-5">
            <label for="email" class="block text-sm font-medium text-on-surface mb-1.5">
                Correo electrónico
            </label>
            <input type="email"
                   id="email"
                   name="email"
                   value="<?= $e($oldEmail) ?>"
                   autocomplete="email"
                   required
                   class="w-full px-3.5 py-2.5 text-sm text-on-surface bg-surface-container-lowest border border-outline-variant rounded-[0.625rem] transition-colors duration-200 placeholder-on-surface-subtle"
                   placeholder="tu@correo.com">
        </div>

        <!-- Password -->
        <div class="mb-5">
            <label for="password" class="block text-sm font-medium text-on-surface mb-1.5">
                Contraseña
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
            <!-- Password strength bar -->
            <div class="mt-2 h-1 rounded-full bg-surface-container-high overflow-hidden">
                <div id="strength-bar" class="h-full rounded-full transition-all duration-300 w-0 bg-on-surface-subtle"></div>
            </div>
        </div>

        <!-- Confirm password -->
        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-medium text-on-surface mb-1.5">
                Confirmar contraseña
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

        <!-- Submit -->
        <button type="submit"
                class="w-full py-2.5 px-4 bg-primary hover:bg-primary-muted text-on-primary font-semibold text-sm rounded-[0.625rem] transition-colors duration-200 shadow-ambient inline-flex items-center justify-center gap-2">
            <?= Icons::plus('w-4 h-4') ?> Crear mi cuenta
        </button>
    </form>

    <!-- Divider -->
    <div class="flex items-center gap-3 my-6">
        <div class="flex-1 h-px bg-outline-variant"></div>
        <span class="text-xs text-on-surface-subtle">¿Ya tienes cuenta?</span>
        <div class="flex-1 h-px bg-outline-variant"></div>
    </div>

    <a href="<?= BASE_URL ?>/login"
       class="flex items-center justify-center w-full py-2.5 px-4 border border-outline-variant hover:border-primary/40 hover:bg-surface-container-low text-sm font-medium text-on-surface rounded-[0.625rem] transition-colors duration-200">
        Iniciar sesión
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

// Password strength bar
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

        const pct = (score / 4) * 100;
        bar.style.width = pct + '%';
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
        if (pw.value !== conf.value) {
            matchMsg.classList.remove('hidden');
        } else {
            matchMsg.classList.add('hidden');
        }
    }
})();
</script>
