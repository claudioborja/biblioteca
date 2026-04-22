<?php
// views/admin/settings/index.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$typeBadge = static function (string $type): string {
    return match ($type) {
        'boolean' => 'bg-blue-100 text-blue-700',
        'integer' => 'bg-emerald-100 text-emerald-700',
        'decimal' => 'bg-violet-100 text-violet-700',
        'json' => 'bg-amber-100 text-amber-700',
        default => 'bg-slate-100 text-slate-700',
    };
};

$labelForKey = static function (string $key): string {
    return match ($key) {
        'library_name' => 'Nombre de la biblioteca',
        'library_address' => 'Dirección',
        'library_logo' => 'Logo',
        'library_phone' => 'Teléfono',
        'library_email' => 'Correo',
        'library_website' => 'Sitio web',
        'library_schedule' => 'Horario',
        'library_slogan' => 'Eslogan',
        'library_favicon' => 'Favicon',
        'loan_hours' => 'Horas de préstamo',
        'loan_hours_extended' => 'Horas de préstamo extendido',
        'renewal_grace_hours' => 'Gracia para renovación',
        'max_loans_per_user' => 'Máximo de préstamos por socio',
        'max_renewals' => 'Máximo de renovaciones',
        'reservation_hold_hours' => 'Horas de reserva apartada',
        'fine_per_hour' => 'Multa por hora',
        'block_loans_with_fines' => 'Bloquear préstamos con multas',
        'reminder_hours_before' => 'Horas antes del recordatorio',
        'new_acquisition_days' => 'Días de nuevas adquisiciones',
        'news_on_home' => 'Noticias en portada',
        'max_fine_multiplier' => 'Multiplicador máximo de multa',
        'timezone' => 'Zona horaria',
        'locale' => 'Localización',
        'date_format' => 'Formato de fecha',
        'carnet_prefix' => 'Prefijo de codigo de usuario',
        'currency_symbol' => 'Símbolo de moneda',
        'smtp_host' => 'Servidor SMTP',
        'smtp_port' => 'Puerto SMTP',
        'smtp_username' => 'Usuario SMTP',
        'smtp_password' => 'Contraseña SMTP',
        'smtp_encryption' => 'Cifrado SMTP',
        'smtp_from_address' => 'Correo remitente',
        'smtp_from_name' => 'Nombre remitente',
        'smtp_timeout' => 'Timeout SMTP (segundos)',
        'app_url' => 'URL pública de la aplicación',
        'about_hero_badge' => 'About: etiqueta hero',
        'about_hero_title' => 'About: título hero',
        'about_hero_subtitle' => 'About: subtítulo hero',
        'about_mission_title' => 'About: título misión',
        'about_mission_text' => 'About: texto misión',
        'about_vision_title' => 'About: título visión',
        'about_vision_text' => 'About: texto visión',
        'about_values_title' => 'About: título valores',
        'about_values_items' => 'About: lista de valores',
        'about_history_badge' => 'About: etiqueta historia',
        'about_history_title' => 'About: título historia',
        'about_history_text' => 'About: historia',
        'about_timeline_items' => 'About: línea de tiempo',
        'about_contact_badge' => 'About: etiqueta contacto',
        'about_contact_title' => 'About: título contacto',
        default => ucwords(str_replace('_', ' ', $key)),
    };
};

$helpForKey = static function (string $key): string {
    return match ($key) {
        'loan_hours' => 'Tiempo estándar que dura un préstamo activo.',
        'loan_hours_extended' => 'Tiempo adicional para préstamos especiales.',
        'renewal_grace_hours' => 'Margen previo al vencimiento para renovar.',
        'max_loans_per_user' => 'Cantidad máxima de préstamos simultáneos por socio.',
        'max_renewals' => 'Número máximo de renovaciones permitidas.',
        'reservation_hold_hours' => 'Tiempo reservado antes de liberar el recurso al siguiente socio.',
        'fine_per_hour' => 'Valor monetario aplicado por cada hora de retraso.',
        'block_loans_with_fines' => 'Impide nuevos préstamos si hay multas pendientes.',
        'reminder_hours_before' => 'Cuándo enviar alertas antes del vencimiento.',
        'new_acquisition_days' => 'Cuántos días un recurso se muestra como novedad.',
        'news_on_home' => 'Cantidad de noticias visibles en la portada pública.',
        'max_fine_multiplier' => 'Tope relativo para evitar multas excesivas.',
        'library_logo' => 'Ruta o URL del logo institucional.',
        'library_favicon' => 'Ruta o URL del icono del navegador.',
        'date_format' => 'Formato usado al mostrar fechas en el sistema.',
        'carnet_prefix' => 'Prefijo para la numeracion interna del codigo de usuario.',
        'smtp_host' => 'Servidor SMTP obligatorio para el envio de correos (dominio o IP saliente).',
        'smtp_port' => 'Puerto del servidor SMTP (común: 587 TLS o 465 SSL).',
        'smtp_username' => 'Usuario o correo autenticado en el servidor SMTP.',
        'smtp_password' => 'Clave de aplicación o contraseña SMTP.',
        'smtp_encryption' => 'Tipo de cifrado para la conexión SMTP.',
        'smtp_from_address' => 'Correo que aparecerá como remitente.',
        'smtp_from_name' => 'Nombre visible del remitente.',
        'smtp_timeout' => 'Tiempo máximo de espera para conexión SMTP.',
        'app_url' => 'Dominio público para enlaces en correos (ej. https://midominio.com/biblioteca). Déjalo vacío para detección automática.',
        'about_hero_badge' => 'Texto pequeño superior del encabezado de la página About.',
        'about_hero_title' => 'Título principal del encabezado. Déjalo vacío para usar el nombre de biblioteca.',
        'about_hero_subtitle' => 'Subtítulo del encabezado. Déjalo vacío para usar el eslogan.',
        'about_mission_text' => 'Descripción de la misión institucional.',
        'about_vision_text' => 'Descripción de la visión institucional.',
        'about_values_items' => 'Escribe un valor por línea.',
        'about_history_badge' => 'Etiqueta sobre la sección de historia.',
        'about_history_title' => 'Título de la sección de historia.',
        'about_history_text' => 'Texto completo de historia en un solo campo.',
        'about_timeline_items' => 'Una entrada por línea con formato: año|texto.',
        'about_contact_badge' => 'Etiqueta de la sección de contacto en About.',
        'about_contact_title' => 'Título de la sección de contacto en About.',
        default => '',
    };
};

$groups = [
    'library' => [
        'title' => 'Biblioteca',
        'description' => 'Identidad institucional y datos públicos.',
        'keys' => [
            'library_name',
            'library_slogan',
            'library_address',
            'library_phone',
            'library_email',
            'library_website',
            'library_schedule',
            'library_logo',
            'library_favicon',
        ],
    ],
    'loans' => [
        'title' => 'Préstamos',
        'description' => 'Reglas operativas para circulación y reservas.',
        'keys' => [
            'loan_hours',
            'loan_hours_extended',
            'renewal_grace_hours',
            'max_loans_per_user',
            'max_renewals',
            'reservation_hold_hours',
            'new_acquisition_days',
        ],
    ],
    'fines' => [
        'title' => 'Multas',
        'description' => 'Políticas de mora, bloqueo y topes.',
        'keys' => [
            'fine_per_hour',
            'max_fine_multiplier',
            'block_loans_with_fines',
        ],
    ],
    'notifications' => [
        'title' => 'Comunicación',
        'description' => 'Ajustes de recordatorios y visibilidad pública.',
        'keys' => [
            'reminder_hours_before',
            'news_on_home',
        ],
    ],
    'smtp' => [
        'title' => 'Correo SMTP',
        'description' => 'Credenciales y remitente para salida de correos.',
        'keys' => [
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_password',
            'smtp_encryption',
            'smtp_from_address',
            'smtp_from_name',
            'smtp_timeout',
        ],
    ],
    'about' => [
        'title' => 'Página About',
        'description' => 'Contenidos públicos de la página Nosotros.',
        'keys' => [
            'about_hero_badge',
            'about_hero_title',
            'about_hero_subtitle',
            'about_mission_title',
            'about_mission_text',
            'about_vision_title',
            'about_vision_text',
            'about_values_title',
            'about_values_items',
            'about_history_badge',
            'about_history_title',
            'about_history_text',
            'about_timeline_items',
            'about_contact_badge',
            'about_contact_title',
        ],
    ],
    'system' => [
        'title' => 'Sistema',
        'description' => 'Preferencias generales de formato y regionalización.',
        'keys' => [
            'timezone',
            'locale',
            'date_format',
            'currency_symbol',
            'carnet_prefix',
            'app_url',
        ],
    ],
];

$groupIcons = [
    'library' => 'library-big',
    'loans' => 'book-open-check',
    'fines' => 'badge-alert',
    'notifications' => 'bell-ring',
    'smtp' => 'mail',
    'about' => 'scroll-text',
    'system' => 'sliders-horizontal',
    'other' => 'settings-2',
];

$fieldIcons = [
    'library_name' => 'building-2',
    'library_slogan' => 'quote',
    'library_address' => 'map-pin',
    'library_phone' => 'phone',
    'library_email' => 'mail',
    'library_website' => 'globe',
    'library_schedule' => 'clock-3',
    'library_logo' => 'image',
    'library_favicon' => 'badge-check',
    'loan_hours' => 'hourglass',
    'loan_hours_extended' => 'clock-4',
    'renewal_grace_hours' => 'refresh-cw',
    'max_loans_per_user' => 'book-user',
    'max_renewals' => 'repeat',
    'reservation_hold_hours' => 'calendar-clock',
    'new_acquisition_days' => 'sparkles',
    'fine_per_hour' => 'banknote',
    'max_fine_multiplier' => 'scale',
    'block_loans_with_fines' => 'shield-alert',
    'reminder_hours_before' => 'bell-ring',
    'news_on_home' => 'newspaper',
    'smtp_host' => 'server',
    'smtp_port' => 'plug',
    'smtp_username' => 'user',
    'smtp_password' => 'key-round',
    'smtp_encryption' => 'lock',
    'smtp_from_address' => 'at-sign',
    'smtp_from_name' => 'user-round',
    'smtp_timeout' => 'timer',
    'timezone' => 'map',
    'locale' => 'languages',
    'date_format' => 'calendar-days',
    'currency_symbol' => 'circle-dollar-sign',
    'carnet_prefix' => 'id-card',
    'app_url' => 'link',
    'about_hero_badge' => 'badge-info',
    'about_hero_title' => 'heading-1',
    'about_hero_subtitle' => 'text-cursor-input',
    'about_mission_title' => 'target',
    'about_mission_text' => 'text',
    'about_vision_title' => 'eye',
    'about_vision_text' => 'scan-text',
    'about_values_title' => 'heart',
    'about_values_items' => 'list-checks',
    'about_history_badge' => 'history',
    'about_history_title' => 'book-open-text',
    'about_history_text' => 'paragraph',
    'about_timeline_items' => 'calendar-range',
    'about_contact_badge' => 'map-pin',
    'about_contact_title' => 'contact',
];

$textareaKeys = [
    'library_schedule',
    'about_hero_subtitle',
    'about_mission_text',
    'about_vision_text',
    'about_values_items',
    'about_history_text',
    'about_timeline_items',
];

$aboutSubgroups = [
    'hero' => [
        'title' => 'Hero',
        'keys' => [
            'about_hero_badge',
            'about_hero_title',
            'about_hero_subtitle',
        ],
    ],
    'mission' => [
        'title' => 'Misión',
        'keys' => [
            'about_mission_title',
            'about_mission_text',
        ],
    ],
    'vision' => [
        'title' => 'Visión',
        'keys' => [
            'about_vision_title',
            'about_vision_text',
        ],
    ],
    'values' => [
        'title' => 'Valores',
        'keys' => [
            'about_values_title',
            'about_values_items',
        ],
    ],
    'history' => [
        'title' => 'Historia',
        'keys' => [
            'about_history_badge',
            'about_history_title',
            'about_history_text',
        ],
    ],
    'timeline-contact' => [
        'title' => 'Timeline · Contacto',
        'keys' => [
            'about_timeline_items',
            'about_contact_badge',
            'about_contact_title',
        ],
    ],
];

$settingsMap = [];
foreach ($all_settings as $setting) {
    $settingsMap[(string) $setting['key']] = $setting;
}

// smtp_enabled is handled internally and should not be editable in UI.
unset($settingsMap['smtp_enabled']);

$groupOrder = array_keys($groups);
$requestedTab = isset($active_tab) ? trim((string) $active_tab) : '';
$defaultTab = in_array($requestedTab, $groupOrder, true)
    ? $requestedTab
    : ($groupOrder[0] ?? 'library');

$countConfiguredInGroup = static function (array $groupKeys, array $settingsMap): int {
    $configured = 0;

    foreach ($groupKeys as $key) {
        if (!isset($settingsMap[$key])) {
            continue;
        }

        $setting = $settingsMap[$key];
        $type = (string) ($setting['type'] ?? 'string');
        $value = (string) ($setting['value'] ?? '');

        if ($type === 'boolean') {
            if ($value === 'true') {
                $configured++;
            }
            continue;
        }

        if (trim($value) !== '') {
            $configured++;
        }
    }

    return $configured;
};

?>

<section class="p-4 lg:p-5">
    <div class="mb-4 rounded-2xl border border-outline-variant/60 bg-white px-4 py-3 shadow-ambient">
        <h1 class="headline-lg text-on-surface">Configuración</h1>
        <p class="mt-1 text-sm text-on-surface-muted">Ajusta los módulos principales de la biblioteca.</p>
    </div>

    <form id="settings-form" method="POST" action="<?= BASE_URL ?>/admin/settings" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
        <input type="hidden" name="active_tab" id="active-tab-input" value="<?= $e($defaultTab) ?>">

        <div class="grid gap-4 xl:grid-cols-[260px_minmax(0,1fr)]">
            <aside class="h-fit rounded-2xl border border-outline-variant/60 bg-white p-2 shadow-ambient">
                <div class="space-y-2" role="tablist" aria-label="Grupos de configuración">
                    <?php foreach ($groups as $groupKey => $group): ?>
                        <?php
                            $groupConfigured = $countConfiguredInGroup($group['keys'], $settingsMap);
                            $groupTotal = max(1, count($group['keys']));
                            $groupPercent = (int) floor(($groupConfigured / $groupTotal) * 100);
                        ?>
                        <button
                            type="button"
                            class="settings-tab block w-full rounded-xl border border-transparent px-2.5 py-2 text-left transition-colors duration-150"
                            data-tab-target="<?= $e($groupKey) ?>"
                            role="tab"
                            aria-selected="<?= $groupKey === $defaultTab ? 'true' : 'false' ?>">
                            <span class="flex items-start justify-between gap-3">
                                <span class="flex items-center gap-2.5">
                                    <i data-lucide="<?= $e($groupIcons[$groupKey] ?? 'circle') ?>" class="h-4 w-4 text-on-surface-subtle"></i>
                                    <span class="block text-sm font-semibold text-on-surface"><?= $e($group['title']) ?></span>
                                </span>
                                <span class="rounded-full bg-surface-container px-2 py-0.5 text-[11px] font-semibold text-on-surface-muted">
                                    <?= $groupConfigured ?>/<?= count($group['keys']) ?>
                                </span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </aside>

            <div class="space-y-4">
                <?php foreach ($groups as $groupKey => $group): ?>
                    <?php
                        $groupConfigured = $countConfiguredInGroup($group['keys'], $settingsMap);
                        $groupTotal = max(1, count($group['keys']));
                        $groupPercent = (int) floor(($groupConfigured / $groupTotal) * 100);
                    ?>
                    <section
                        class="settings-panel rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient <?= $groupKey === $defaultTab ? '' : 'hidden' ?>"
                        data-tab-panel="<?= $e($groupKey) ?>"
                        role="tabpanel">
                        <div class="mb-4 flex items-center justify-between border-b border-outline-variant/50 pb-3">
                            <h2 class="headline-md text-on-surface flex items-center gap-2">
                                <i data-lucide="<?= $e($groupIcons[$groupKey] ?? 'circle') ?>" class="h-5 w-5 text-primary"></i>
                                <span><?= $e($group['title']) ?></span>
                            </h2>
                            <p class="text-xs font-semibold text-on-surface-subtle"><?= $groupConfigured ?>/<?= count($group['keys']) ?> configurados</p>
                        </div>

                        <?php if ($groupKey === 'about'): ?>
                            <div class="space-y-3" data-about-tabs>
                                <div class="flex flex-wrap gap-2 border-b border-outline-variant/50 pb-3" role="tablist" aria-label="Subsecciones About">
                                    <?php $aboutDefault = array_key_first($aboutSubgroups); ?>
                                    <?php foreach ($aboutSubgroups as $subgroupKey => $subgroup): ?>
                                        <button
                                            type="button"
                                            class="about-subtab rounded-lg border border-outline-variant px-3 py-1.5 text-sm font-semibold text-on-surface-subtle transition-colors"
                                            data-about-target="<?= $e($subgroupKey) ?>"
                                            aria-selected="<?= $subgroupKey === $aboutDefault ? 'true' : 'false' ?>">
                                            <?= $e($subgroup['title']) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>

                                <?php foreach ($aboutSubgroups as $subgroupKey => $subgroup): ?>
                                    <div class="about-subpanel <?= $subgroupKey === $aboutDefault ? '' : 'hidden' ?>" data-about-panel="<?= $e($subgroupKey) ?>">
                                        <div class="space-y-2.5">
                                            <?php foreach ($subgroup['keys'] as $key):
                                                if (!isset($settingsMap[$key])) {
                                                    continue;
                                                }
                                                $setting = $settingsMap[$key];
                                                $value = (string) $setting['value'];
                                                $type = (string) $setting['type'];
                                                $help = $helpForKey($key);
                                            ?>
                                                <div>
                                                    <div class="space-y-1.5">
                                                        <div>
                                                            <h3 class="flex items-center gap-2 text-sm font-semibold text-on-surface">
                                                                <i data-lucide="<?= $e($fieldIcons[$key] ?? 'circle') ?>" class="h-4 w-4 text-on-surface-subtle"></i>
                                                                <span><?= $e($labelForKey($key)) ?></span>
                                                            </h3>
                                                            <?php if ($help !== ''): ?>
                                                                <p class="mt-1 text-[11px] text-on-surface-muted"><?= $e($help) ?></p>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div>
                                                            <?php if ($type === 'boolean'): ?>
                                                                <label class="inline-flex items-center gap-2 text-sm text-on-surface">
                                                                    <input type="checkbox"
                                                                           name="settings[<?= $e($key) ?>]"
                                                                           value="true"
                                                                           class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary"
                                                                           <?= $value === 'true' ? 'checked' : '' ?>>
                                                                    <span><?= $value === 'true' ? 'Habilitado' : 'Deshabilitado' ?></span>
                                                                </label>
                                                            <?php elseif ($type === 'integer'): ?>
                                                                <input type="number"
                                                                       step="1"
                                                                       name="settings[<?= $e($key) ?>]"
                                                                       value="<?= $e($value) ?>"
                                                                       class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                                                            <?php elseif ($type === 'decimal'): ?>
                                                                <input type="number"
                                                                       step="0.01"
                                                                       name="settings[<?= $e($key) ?>]"
                                                                       value="<?= $e($value) ?>"
                                                                       class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                                                            <?php elseif ($key === 'smtp_encryption'): ?>
                                                                <select
                                                                    name="settings[<?= $e($key) ?>]"
                                                                    class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                                                                    <?php $encryption = mb_strtolower($value, 'UTF-8'); ?>
                                                                    <option value="none" <?= $encryption === 'none' ? 'selected' : '' ?>>Ninguno</option>
                                                                    <option value="tls" <?= $encryption === 'tls' ? 'selected' : '' ?>>TLS</option>
                                                                    <option value="ssl" <?= $encryption === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                                                </select>
                                                            <?php elseif ($key === 'smtp_password'): ?>
                                                                <input type="password"
                                                                       autocomplete="new-password"
                                                                       name="settings[<?= $e($key) ?>]"
                                                                       value="<?= $e($value) ?>"
                                                                       class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                                                            <?php elseif (in_array($key, $textareaKeys, true)): ?>
                                                                <textarea
                                                                    name="settings[<?= $e($key) ?>]"
                                                                    rows="4"
                                                                    class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none"><?= $e($value) ?></textarea>
                                                            <?php else: ?>
                                                                <input type="text"
                                                                       name="settings[<?= $e($key) ?>]"
                                                                       value="<?= $e($value) ?>"
                                                                       class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="<?= in_array($groupKey, ['library', 'smtp'], true) ? 'grid gap-2.5 md:grid-cols-2' : 'space-y-2.5' ?>">
                                <?php foreach ($group['keys'] as $key):
                                    if (!isset($settingsMap[$key])) {
                                        continue;
                                    }
                                    $setting = $settingsMap[$key];
                                    $value = (string) $setting['value'];
                                    $type = (string) $setting['type'];
                                    $help = $helpForKey($key);
                                ?>
                                    <div>
                                        <div class="space-y-1.5">
                                            <div>
                                                <h3 class="flex items-center gap-2 text-sm font-semibold text-on-surface">
                                                    <i data-lucide="<?= $e($fieldIcons[$key] ?? 'circle') ?>" class="h-4 w-4 text-on-surface-subtle"></i>
                                                    <span><?= $e($labelForKey($key)) ?></span>
                                                </h3>
                                                <?php if ($help !== ''): ?>
                                                    <p class="mt-1 text-[11px] text-on-surface-muted"><?= $e($help) ?></p>
                                                <?php endif; ?>
                                            </div>

                                            <div>
                                                <?php if ($type === 'boolean'): ?>
                                                    <label class="inline-flex items-center gap-2 text-sm text-on-surface">
                                                        <input type="checkbox"
                                                               name="settings[<?= $e($key) ?>]"
                                                               value="true"
                                                               class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary"
                                                               <?= $value === 'true' ? 'checked' : '' ?>>
                                                        <span><?= $value === 'true' ? 'Habilitado' : 'Deshabilitado' ?></span>
                                                    </label>
                                                <?php elseif ($type === 'integer'): ?>
                                                    <input type="number"
                                                           step="1"
                                                           name="settings[<?= $e($key) ?>]"
                                                           value="<?= $e($value) ?>"
                                                           class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                                                <?php elseif ($type === 'decimal'): ?>
                                                    <input type="number"
                                                           step="0.01"
                                                           name="settings[<?= $e($key) ?>]"
                                                           value="<?= $e($value) ?>"
                                                           class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                                                <?php elseif ($key === 'smtp_encryption'): ?>
                                                    <select
                                                        name="settings[<?= $e($key) ?>]"
                                                        class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                                                        <?php $encryption = mb_strtolower($value, 'UTF-8'); ?>
                                                        <option value="none" <?= $encryption === 'none' ? 'selected' : '' ?>>Ninguno</option>
                                                        <option value="tls" <?= $encryption === 'tls' ? 'selected' : '' ?>>TLS</option>
                                                        <option value="ssl" <?= $encryption === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                                    </select>
                                                <?php elseif ($key === 'smtp_password'): ?>
                                                    <input type="password"
                                                           autocomplete="new-password"
                                                           name="settings[<?= $e($key) ?>]"
                                                           value="<?= $e($value) ?>"
                                                           class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                                                <?php elseif ($key === 'library_logo' || $key === 'library_favicon'): ?>
                                                    <?php $previewId = 'settings-preview-' . $e($key); ?>
                                                    <?php if ($value !== ''): ?>
                                                        <div id="<?= $previewId ?>-current" class="mb-2 flex items-center gap-3">
                                                            <img src="<?= $e(BASE_URL . $value) ?>"
                                                                 alt="Imagen actual"
                                                                 class="h-10 max-w-[120px] rounded border border-outline-variant object-contain bg-surface-container-low p-1">
                                                            <span class="text-xs text-on-surface-muted truncate"><?= $e($value) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <input type="file"
                                                           id="<?= $previewId ?>-input"
                                                           name="<?= $e($key) ?>"
                                                           accept="image/jpeg,image/png,image/webp,image/gif,image/x-icon"
                                                           class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/20 focus:border-primary focus:outline-none">
                                                    <p class="mt-1 text-xs text-on-surface-muted">Máx. 2 MB · JPG, PNG, WEBP, GIF<?= $key === 'library_favicon' ? ', ICO' : '' ?></p>
                                                    <p id="<?= $previewId ?>-error" class="mt-1 hidden text-xs font-semibold text-red-600"></p>
                                                    <div id="<?= $previewId ?>-new" class="mt-2 hidden">
                                                        <img id="<?= $previewId ?>-img" src="" alt="Vista previa"
                                                             class="h-14 max-w-[140px] rounded-xl border border-outline-variant object-contain bg-surface-container-low p-1">
                                                    </div>
                                                <?php elseif (in_array($key, $textareaKeys, true)): ?>
                                                    <textarea
                                                        name="settings[<?= $e($key) ?>]"
                                                        rows="4"
                                                        class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none"><?= $e($value) ?></textarea>
                                                <?php else: ?>
                                                    <input type="text"
                                                           name="settings[<?= $e($key) ?>]"
                                                           value="<?= $e($value) ?>"
                                                           class="w-full rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <input type="hidden"
                               name="section_keys[<?= $e($groupKey) ?>]"
                               value="<?= $e(implode(',', $group['keys'])) ?>">

                        <div class="mt-4 flex items-center justify-between border-t border-outline-variant/50 pt-3">
                            <?php if ($groupKey === 'smtp'): ?>
                                <button type="button"
                                        id="btn-smtp-test"
                                        class="flex items-center gap-1.5 rounded-xl border border-outline-variant px-4 py-1.5 text-sm font-semibold text-on-surface hover:bg-surface-container transition-colors">
                                    <i data-lucide="send" class="h-4 w-4"></i>
                                    Probar envío
                                </button>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <button type="submit"
                                    name="section_key"
                                    value="<?= $e($groupKey) ?>"
                                    class="rounded-xl gradient-scholar px-4 py-1.5 text-sm font-semibold text-white shadow-ambient">
                                Guardar
                            </button>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    </form>
</section>

<!-- ── SMTP Test Modal ───────────────────────────────────────────────────── -->
<div id="smtp-test-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4"
     role="dialog" aria-modal="true" aria-labelledby="smtp-modal-title">
    <div class="w-full max-w-xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-outline-variant/50 px-5 py-4">
            <h2 id="smtp-modal-title" class="flex items-center gap-2 text-base font-semibold text-on-surface">
                <i data-lucide="terminal" class="h-5 w-5 text-primary"></i>
                Probar envío SMTP
            </h2>
            <button type="button" id="smtp-modal-close" class="rounded-lg p-1 hover:bg-surface-container" aria-label="Cerrar">
                <i data-lucide="x" class="h-4 w-4 text-on-surface-muted"></i>
            </button>
        </div>

        <div class="px-5 py-4 space-y-3">
            <div class="flex gap-2">
                <input type="email"
                       id="smtp-test-to"
                       placeholder="destinatario@ejemplo.com"
                       class="flex-1 rounded-xl border border-outline-variant bg-white px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                <button type="button" id="smtp-modal-send"
                        class="flex items-center gap-1.5 rounded-xl gradient-scholar px-4 py-2 text-sm font-semibold text-white shadow-ambient disabled:opacity-50">
                    <i data-lucide="loader-circle" id="smtp-modal-spinner" class="h-4 w-4 animate-spin hidden"></i>
                    <i data-lucide="send" id="smtp-modal-send-icon" class="h-4 w-4"></i>
                    <span id="smtp-modal-send-label">Enviar</span>
                </button>
            </div>

            <!-- Terminal output panel -->
            <div id="smtp-terminal-wrap" class="hidden">
                <div class="rounded-xl bg-gray-950 border border-gray-700 overflow-hidden">
                    <div class="flex items-center gap-1.5 border-b border-gray-700 bg-gray-900 px-3 py-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-yellow-500"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
                        <span class="ml-2 text-[10px] font-mono text-gray-500">SMTP — biblioteca.local</span>
                    </div>
                    <div id="smtp-terminal"
                         class="font-mono text-[11px] leading-relaxed h-52 overflow-y-auto p-3 space-y-px"></div>
                </div>
            </div>
        </div>

        <div class="flex justify-end border-t border-outline-variant/50 px-5 py-3">
            <button type="button" id="smtp-modal-cancel"
                    class="rounded-xl border border-outline-variant px-4 py-1.5 text-sm font-semibold text-on-surface hover:bg-surface-container transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.min.js"></script>
<script>
(() => {
    const tabs = Array.from(document.querySelectorAll('.settings-tab'));
    const panels = Array.from(document.querySelectorAll('.settings-panel'));
    const activeTabInput = document.getElementById('active-tab-input');
    if (!tabs.length || !panels.length) return;

    const activate = (target) => {
        if (activeTabInput) {
            activeTabInput.value = target;
        }

        tabs.forEach((tab) => {
            const active = tab.dataset.tabTarget === target;
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.classList.toggle('bg-primary/8', active);
            tab.classList.toggle('text-primary', active);
        });

        panels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.tabPanel !== target);
        });
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => activate(tab.dataset.tabTarget));
    });

    activate('<?= $e($defaultTab) ?>');

    const aboutGroups = Array.from(document.querySelectorAll('[data-about-tabs]'));
    aboutGroups.forEach((group) => {
        const subtabs = Array.from(group.querySelectorAll('.about-subtab'));
        const subpanels = Array.from(group.querySelectorAll('.about-subpanel'));
        if (!subtabs.length || !subpanels.length) {
            return;
        }

        const activateAbout = (target) => {
            subtabs.forEach((tab) => {
                const active = tab.dataset.aboutTarget === target;
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
                tab.classList.toggle('bg-primary/8', active);
                tab.classList.toggle('border-primary/40', active);
                tab.classList.toggle('text-primary', active);
            });

            subpanels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.aboutPanel !== target);
            });
        };

        subtabs.forEach((tab) => {
            tab.addEventListener('click', () => activateAbout(tab.dataset.aboutTarget));
        });

        activateAbout(subtabs[0].dataset.aboutTarget);
    });

    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }

    // ── SMTP Test Modal ────────────────────────────────────────────────────
    const smtpModal      = document.getElementById('smtp-test-modal');
    const smtpBtnOpen    = document.getElementById('btn-smtp-test');
    const smtpClose      = document.getElementById('smtp-modal-close');
    const smtpCancel     = document.getElementById('smtp-modal-cancel');
    const smtpSend       = document.getElementById('smtp-modal-send');
    const smtpInput      = document.getElementById('smtp-test-to');
    const smtpTermWrap   = document.getElementById('smtp-terminal-wrap');
    const smtpTerm       = document.getElementById('smtp-terminal');
    const smtpSpinner    = document.getElementById('smtp-modal-spinner');
    const smtpSendIcon   = document.getElementById('smtp-modal-send-icon');
    const smtpLabel      = document.getElementById('smtp-modal-send-label');
    const csrfToken      = document.querySelector('input[name="_csrf_token"]')?.value ?? '';

    const typeColors = {
        info:  'text-gray-400',
        send:  'text-cyan-300',
        recv:  'text-green-400',
        ok:    'text-emerald-400',
        error: 'text-red-400',
    };
    const typePrefixes = {
        info:  '  ',
        send:  '→ ',
        recv:  '← ',
        ok:    '✓ ',
        error: '✗ ',
    };

    const appendTermLine = (type, text) => {
        const line = document.createElement('div');
        const color = typeColors[type] ?? 'text-gray-300';
        const bold  = (type === 'ok' || type === 'error') ? ' font-bold' : '';
        line.className = `${color}${bold} whitespace-pre-wrap`;
        line.textContent = (typePrefixes[type] ?? '  ') + text;
        smtpTerm.appendChild(line);
        smtpTerm.scrollTop = smtpTerm.scrollHeight;
    };

    const animateSteps = async (steps) => {
        for (const step of steps) {
            appendTermLine(step.type, step.text);
            await new Promise(r => setTimeout(r, 45));
        }
    };

    const openModal = () => {
        smtpModal.classList.remove('hidden');
        smtpModal.classList.add('flex');
        smtpTermWrap.classList.add('hidden');
        smtpTerm.innerHTML = '';
        smtpInput.value = '';
        setTimeout(() => smtpInput.focus(), 50);
    };
    const closeModal = () => {
        smtpModal.classList.add('hidden');
        smtpModal.classList.remove('flex');
    };

    if (smtpBtnOpen) smtpBtnOpen.addEventListener('click', openModal);
    if (smtpClose)   smtpClose.addEventListener('click', closeModal);
    if (smtpCancel)  smtpCancel.addEventListener('click', closeModal);
    smtpModal?.addEventListener('click', (e) => { if (e.target === smtpModal) closeModal(); });

    if (smtpSend) {
        smtpSend.addEventListener('click', async () => {
            const to = smtpInput?.value.trim() ?? '';
            if (!to) { smtpInput?.focus(); return; }

            smtpSend.disabled = true;
            smtpSpinner.classList.remove('hidden');
            smtpSendIcon.classList.add('hidden');
            smtpLabel.textContent = 'Enviando…';

            // Show terminal and first line immediately
            smtpTerm.innerHTML = '';
            smtpTermWrap.classList.remove('hidden');
            appendTermLine('info', `Iniciando prueba → ${to}`);

            try {
                const fd = new FormData();
                fd.append('_csrf_token', csrfToken);
                fd.append('to', to);

                const res  = await fetch('<?= BASE_URL ?>/admin/settings/smtp-test', { method: 'POST', body: fd });
                const data = await res.json();

                // Animate received steps (skip the first 'info' we already showed)
                if (Array.isArray(data.steps) && data.steps.length > 0) {
                    await animateSteps(data.steps);
                } else {
                    appendTermLine(data.ok ? 'ok' : 'error', data.message ?? 'Sin respuesta');
                }
            } catch (err) {
                appendTermLine('error', 'Error de red: ' + err.message);
            } finally {
                smtpSend.disabled = false;
                smtpSpinner.classList.add('hidden');
                smtpSendIcon.classList.remove('hidden');
                smtpLabel.textContent = 'Enviar';
            }
        });

        // Allow Enter key in the email input
        smtpInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); smtpSend.click(); }
        });
    }
})();

// Image upload preview for logo and favicon
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
</script>
