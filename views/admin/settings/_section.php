<?php
require __DIR__ . '/_config_shared.php';
$currentSection = trim((string) ($active_section ?? $current_section ?? 'library'));
if (!isset($groups[$currentSection])) {
    $currentSection = 'library';
}
$currentGroup = $groups[$currentSection];

$sectionMeta = [
    'library' => [
        'eyebrow' => 'Identidad institucional',
        'summary' => 'Datos públicos de la biblioteca, contacto y branding visual.',
    ],
    'loans' => [
        'eyebrow' => 'Reglas de circulación',
        'summary' => 'Parámetros de préstamo, renovaciones y reservas.',
    ],
    'fines' => [
        'eyebrow' => 'Política económica',
        'summary' => 'Cálculo de multas y restricciones por deuda pendiente.',
    ],
    'notifications' => [
        'eyebrow' => 'Comunicación',
        'summary' => 'Recordatorios y visibilidad de noticias en portada.',
    ],
    'smtp' => [
        'eyebrow' => 'Infraestructura de correo',
        'summary' => 'Configuración SMTP y prueba de envío en tiempo real.',
    ],
    'about' => [
        'eyebrow' => 'Contenido institucional',
        'summary' => 'Edición del contenido mostrado en la página About.',
    ],
    'system' => [
        'eyebrow' => 'Preferencias globales',
        'summary' => 'Zona horaria, formato y parámetros operativos de la app.',
    ],
];
$meta = $sectionMeta[$currentSection] ?? ['eyebrow' => 'Configuración', 'summary' => 'Administra los parámetros de esta sección.'];

$gridClass = 'grid gap-3 grid-cols-1 md:grid-cols-2';

$sectionIconMethod = [
    'library' => 'library',
    'loans' => 'list',
    'fines' => 'alert',
    'notifications' => 'clock',
    'smtp' => 'settings',
    'about' => 'book',
    'system' => 'settings',
];
$headerIcon = $sectionIconMethod[$currentSection] ?? 'settings';

$renderField = static function (string $key, string $wrapperClass = '') use (
    $settingsMap,
    $e,
    $fieldLabel,
    $textareaKeys
): string {
    if (!isset($settingsMap[$key])) {
        return '';
    }

    $setting = $settingsMap[$key];
    $value = (string) ($setting['value'] ?? '');
    $type = (string) ($setting['type'] ?? 'string');

    ob_start();
    ?>
    <div class="flex h-full flex-col space-y-1.5 rounded-xl border border-outline-variant/60 bg-surface-container-low/30 p-3 <?= $e($wrapperClass) ?>">
        <label class="text-sm font-semibold text-on-surface"><?= $e($fieldLabel($key)) ?></label>

        <?php if ($type === 'boolean'): ?>
            <label class="inline-flex items-center gap-2 text-sm text-on-surface">
                <input type="checkbox" name="settings[<?= $e($key) ?>]" value="true" class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary" <?= $value === 'true' ? 'checked' : '' ?>>
                <span><?= $value === 'true' ? 'Habilitado' : 'Deshabilitado' ?></span>
            </label>
        <?php elseif ($type === 'integer'): ?>
            <input type="number" step="1" name="settings[<?= $e($key) ?>]" value="<?= $e($value) ?>" class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
        <?php elseif ($type === 'decimal'): ?>
            <input type="number" step="0.01" name="settings[<?= $e($key) ?>]" value="<?= $e($value) ?>" class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
        <?php elseif ($key === 'smtp_encryption'): ?>
            <?php $encryption = mb_strtolower($value, 'UTF-8'); ?>
            <select name="settings[<?= $e($key) ?>]" class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                <option value="none" <?= $encryption === 'none' ? 'selected' : '' ?>>Ninguno</option>
                <option value="tls" <?= $encryption === 'tls' ? 'selected' : '' ?>>TLS</option>
                <option value="ssl" <?= $encryption === 'ssl' ? 'selected' : '' ?>>SSL</option>
            </select>
        <?php elseif ($key === 'smtp_password'): ?>
            <input type="password" autocomplete="new-password" name="settings[<?= $e($key) ?>]" value="<?= $e($value) ?>" class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
        <?php elseif ($key === 'library_logo' || $key === 'library_favicon'): ?>
            <?php $previewId = 'settings-preview-' . $e($key); ?>
            <?php if ($value !== ''): ?>
                <div id="<?= $previewId ?>-current" class="mb-2 flex items-center gap-3">
                    <img src="<?= $e(BASE_URL . $value) ?>" alt="Imagen actual" class="h-10 max-w-[120px] rounded border border-outline-variant object-contain bg-surface-container-low p-1">
                    <span class="text-xs text-on-surface-muted truncate"><?= $e($value) ?></span>
                </div>
            <?php endif; ?>
            <input type="file" id="<?= $previewId ?>-input" name="<?= $e($key) ?>" accept="image/jpeg,image/png,image/webp,image/gif,image/x-icon" class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/20 focus:border-primary focus:outline-none">
            <p class="mt-1 text-xs text-on-surface-muted">Max. 2 MB · JPG, PNG, WEBP, GIF<?= $key === 'library_favicon' ? ', ICO' : '' ?></p>
            <p id="<?= $previewId ?>-error" class="mt-1 hidden text-xs font-semibold text-red-600"></p>
            <div id="<?= $previewId ?>-new" class="mt-2 hidden">
                <img id="<?= $previewId ?>-img" src="" alt="Vista previa" class="h-14 max-w-[140px] rounded-xl border border-outline-variant object-contain bg-surface-container-low p-1">
            </div>
        <?php elseif (in_array($key, $textareaKeys, true)): ?>
            <?php $rows = in_array($key, ['about_history_text', 'about_timeline_items'], true) ? 5 : 3; ?>
            <textarea name="settings[<?= $e($key) ?>]" rows="<?= $rows ?>" class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none"><?= $e($value) ?></textarea>
        <?php else: ?>
            <input type="text" name="settings[<?= $e($key) ?>]" value="<?= $e($value) ?>" class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
        <?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
};
?>

<section class="p-4 lg:p-5">
    <form method="POST" action="<?= BASE_URL ?>/admin/settings" enctype="multipart/form-data" class="space-y-3">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
        <input type="hidden" name="active_tab" value="<?= $e($currentSection) ?>">

        <section class="rounded-2xl border border-outline-variant/60 bg-white p-3 shadow-ambient lg:p-4">
            <div class="mb-3 border-b border-outline-variant/50 pb-2">
                <div>
                    <h1 class="inline-flex items-center gap-2 text-sm font-semibold text-on-surface">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <?= \Helpers\Icons::load($headerIcon, 'h-3.5 w-3.5') ?>
                        </span>
                        <span class="text-on-surface-subtle"><?= $e($meta['eyebrow']) ?></span>
                        <span class="text-on-surface-subtle">·</span>
                        <span><?= $e($currentGroup['title']) ?></span>
                    </h1>
                </div>
            </div>

            <?php if ($currentSection === 'library'): ?>
                <div class="grid gap-4 xl:grid-cols-12">
                    <div class="space-y-4 xl:col-span-8">
                        <article class="rounded-xl border border-outline-variant/60 bg-white p-3">
                            <h2 class="mb-2 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-on-surface-subtle">
                                <?= \Helpers\Icons::book('h-3.5 w-3.5 text-primary') ?>
                                Identidad
                            </h2>
                            <div class="grid gap-3 md:grid-cols-2">
                                <?= $renderField('library_name') ?>
                                <?= $renderField('library_slogan') ?>
                            </div>
                        </article>

                        <article class="rounded-xl border border-outline-variant/60 bg-white p-3">
                            <h2 class="mb-2 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-on-surface-subtle">
                                <?= \Helpers\Icons::user('h-3.5 w-3.5 text-primary') ?>
                                Contacto
                            </h2>
                            <div class="grid gap-3 md:grid-cols-2">
                                <?= $renderField('library_address') ?>
                                <?= $renderField('library_phone') ?>
                                <?= $renderField('library_email') ?>
                                <?= $renderField('library_website') ?>
                                <div class="md:col-span-2">
                                    <?= $renderField('library_schedule') ?>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="space-y-4 xl:col-span-4">
                        <article class="rounded-xl border border-outline-variant/60 bg-white p-3">
                            <h2 class="mb-2 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-on-surface-subtle">
                                <?= \Helpers\Icons::settings('h-3.5 w-3.5 text-primary') ?>
                                Branding
                            </h2>
                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-1">
                                <?= $renderField('library_logo') ?>
                                <?= $renderField('library_favicon') ?>
                            </div>
                        </article>
                    </div>
                </div>
            <?php else: ?>
                <div class="<?= $e($gridClass) ?>">
                    <?php foreach ($currentGroup['keys'] as $key): ?>
                        <?= $renderField($key) ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <input type="hidden" name="section_keys[<?= $e($currentSection) ?>]" value="<?= $e(implode(',', $currentGroup['keys'])) ?>">

            <div class="sticky bottom-3 z-10 mt-5 flex items-center justify-between rounded-xl border border-outline-variant/70 bg-white/95 px-3 py-2 shadow-ambient backdrop-blur-sm">
                <?php if ($currentSection === 'smtp'): ?>
                    <button type="button" id="btn-smtp-test" class="flex items-center gap-1.5 rounded-xl border border-outline-variant px-4 py-1.5 text-sm font-semibold text-on-surface hover:bg-surface-container transition-colors">
                        Probar envio
                    </button>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
                <button type="submit" name="section_key" value="<?= $e($currentSection) ?>" class="rounded-xl gradient-scholar px-4 py-1.5 text-sm font-semibold text-white shadow-ambient">
                    Guardar
                </button>
            </div>
        </section>
    </form>
</section>

<?php if ($currentSection === 'smtp'): ?>
<div id="smtp-test-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4" role="dialog" aria-modal="true" aria-labelledby="smtp-modal-title">
    <div class="w-full max-w-xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-outline-variant/50 px-5 py-4">
            <h2 id="smtp-modal-title" class="text-base font-semibold text-on-surface">Probar envio SMTP</h2>
            <button type="button" id="smtp-modal-close" class="rounded-lg p-1 hover:bg-surface-container" aria-label="Cerrar">x</button>
        </div>
        <div class="px-5 py-4 space-y-3">
            <div class="flex gap-2">
                <input type="email" id="smtp-test-to" placeholder="destinatario@ejemplo.com" class="flex-1 rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                <button type="button" id="smtp-modal-send" class="rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient">Enviar</button>
            </div>
            <div id="smtp-terminal-wrap" class="hidden">
                <div class="rounded-xl bg-gray-950 border border-gray-700 overflow-hidden">
                    <div id="smtp-terminal" class="font-mono text-[11px] leading-relaxed h-52 overflow-y-auto p-3 space-y-px"></div>
                </div>
            </div>
        </div>
        <div class="flex justify-end border-t border-outline-variant/50 px-5 py-3">
            <button type="button" id="smtp-modal-cancel" class="rounded-xl border border-outline-variant px-4 py-1.5 text-sm font-semibold text-on-surface hover:bg-surface-container transition-colors">Cerrar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(() => {
    ['library_logo', 'library_favicon'].forEach(key => {
        window.initCoverImageInput?.({
            inputEl:      document.getElementById(`settings-preview-${key}-input`),
            previewWrap:  document.getElementById(`settings-preview-${key}-new`),
            previewImg:   document.getElementById(`settings-preview-${key}-img`),
            errorEl:      document.getElementById(`settings-preview-${key}-error`),
            maxMB:        2,
            allowedTypes: key === 'library_favicon'
                ? ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/x-icon']
                : ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        });
    });
})();

<?php if ($currentSection === 'smtp'): ?>
(() => {
    const smtpModal = document.getElementById('smtp-test-modal');
    const smtpBtnOpen = document.getElementById('btn-smtp-test');
    const smtpClose = document.getElementById('smtp-modal-close');
    const smtpCancel = document.getElementById('smtp-modal-cancel');
    const smtpSend = document.getElementById('smtp-modal-send');
    const smtpInput = document.getElementById('smtp-test-to');
    const smtpTermWrap = document.getElementById('smtp-terminal-wrap');
    const smtpTerm = document.getElementById('smtp-terminal');
    const csrfToken = document.querySelector('input[name="_csrf_token"]')?.value ?? '';

    const appendTermLine = (prefix, text) => {
        const line = document.createElement('div');
        line.className = 'text-gray-300 whitespace-pre-wrap';
        line.textContent = prefix + text;
        smtpTerm.appendChild(line);
        smtpTerm.scrollTop = smtpTerm.scrollHeight;
    };

    const openModal = () => {
        smtpModal.classList.remove('hidden');
        smtpModal.classList.add('flex');
        smtpTermWrap.classList.add('hidden');
        smtpTerm.innerHTML = '';
        smtpInput.value = '';
    };
    const closeModal = () => {
        smtpModal.classList.add('hidden');
        smtpModal.classList.remove('flex');
    };

    smtpBtnOpen?.addEventListener('click', openModal);
    smtpClose?.addEventListener('click', closeModal);
    smtpCancel?.addEventListener('click', closeModal);
    smtpModal?.addEventListener('click', (e) => { if (e.target === smtpModal) closeModal(); });

    smtpSend?.addEventListener('click', async () => {
        const to = smtpInput?.value.trim() ?? '';
        if (!to) { smtpInput?.focus(); return; }

        smtpTerm.innerHTML = '';
        smtpTermWrap.classList.remove('hidden');
        appendTermLine('> ', `Iniciando prueba -> ${to}`);

        try {
            const fd = new FormData();
            fd.append('_csrf_token', csrfToken);
            fd.append('to', to);

            const res = await fetch('<?= BASE_URL ?>/admin/settings/smtp-test', { method: 'POST', body: fd });
            const data = await res.json();
            if (Array.isArray(data.steps)) {
                data.steps.forEach((step) => appendTermLine('* ', String(step.text ?? '')));
            } else {
                appendTermLine('! ', String(data.message ?? 'Sin respuesta'));
            }
        } catch (err) {
            appendTermLine('x ', 'Error de red: ' + err.message);
        }
    });
})();
<?php endif; ?>
</script>
