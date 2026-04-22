<?php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$groups = [
    'library' => ['title' => 'Biblioteca', 'keys' => ['library_name', 'library_slogan', 'library_address', 'library_phone', 'library_email', 'library_website', 'library_schedule', 'library_logo', 'library_favicon']],
    'loans' => ['title' => 'Prestamos', 'keys' => ['loan_hours', 'loan_hours_extended', 'renewal_grace_hours', 'max_loans_per_user', 'max_renewals', 'reservation_hold_hours', 'new_acquisition_days']],
    'fines' => ['title' => 'Multas', 'keys' => ['fine_per_hour', 'max_fine_multiplier', 'block_loans_with_fines']],
    'notifications' => ['title' => 'Comunicacion', 'keys' => ['reminder_hours_before', 'news_on_home']],
    'smtp' => ['title' => 'Correo SMTP', 'keys' => ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_address', 'smtp_from_name', 'smtp_timeout']],
    'about' => ['title' => 'Pagina About', 'keys' => ['about_hero_badge', 'about_hero_title', 'about_hero_subtitle', 'about_mission_title', 'about_mission_text', 'about_vision_title', 'about_vision_text', 'about_values_title', 'about_values_items', 'about_history_badge', 'about_history_title', 'about_history_text', 'about_timeline_items', 'about_contact_badge', 'about_contact_title']],
    'system' => ['title' => 'Sistema', 'keys' => ['timezone', 'locale', 'date_format', 'currency_symbol', 'carnet_prefix', 'app_url']],
];

$labels = [
    'library_name' => 'Nombre de la biblioteca', 'library_address' => 'Direccion', 'library_logo' => 'Logo', 'library_phone' => 'Telefono',
    'library_email' => 'Correo', 'library_website' => 'Sitio web', 'library_schedule' => 'Horario', 'library_slogan' => 'Eslogan', 'library_favicon' => 'Favicon',
    'loan_hours' => 'Horas de prestamo', 'loan_hours_extended' => 'Horas de prestamo extendido', 'renewal_grace_hours' => 'Gracia para renovacion',
    'max_loans_per_user' => 'Maximo de prestamos por socio', 'max_renewals' => 'Maximo de renovaciones', 'reservation_hold_hours' => 'Horas de reserva apartada',
    'new_acquisition_days' => 'Dias de nuevas adquisiciones', 'fine_per_hour' => 'Multa por hora', 'max_fine_multiplier' => 'Multiplicador maximo de multa',
    'block_loans_with_fines' => 'Bloquear prestamos con multas', 'reminder_hours_before' => 'Horas antes del recordatorio', 'news_on_home' => 'Noticias en portada',
    'smtp_host' => 'Servidor SMTP', 'smtp_port' => 'Puerto SMTP', 'smtp_username' => 'Usuario SMTP', 'smtp_password' => 'Contrasena SMTP',
    'smtp_encryption' => 'Cifrado SMTP', 'smtp_from_address' => 'Correo remitente', 'smtp_from_name' => 'Nombre remitente', 'smtp_timeout' => 'Timeout SMTP (segundos)',
    'timezone' => 'Zona horaria', 'locale' => 'Localizacion', 'date_format' => 'Formato de fecha', 'currency_symbol' => 'Simbolo de moneda', 'carnet_prefix' => 'Prefijo de codigo de usuario', 'app_url' => 'URL publica de la aplicacion',
    'about_hero_badge' => 'About: etiqueta hero', 'about_hero_title' => 'About: titulo hero', 'about_hero_subtitle' => 'About: subtitulo hero',
    'about_mission_title' => 'About: titulo mision', 'about_mission_text' => 'About: texto mision', 'about_vision_title' => 'About: titulo vision', 'about_vision_text' => 'About: texto vision',
    'about_values_title' => 'About: titulo valores', 'about_values_items' => 'About: lista de valores', 'about_history_badge' => 'About: etiqueta historia',
    'about_history_title' => 'About: titulo historia', 'about_history_text' => 'About: historia', 'about_timeline_items' => 'About: linea de tiempo',
    'about_contact_badge' => 'About: etiqueta contacto', 'about_contact_title' => 'About: titulo contacto',
];

$textareaKeys = ['library_schedule', 'about_hero_subtitle', 'about_mission_text', 'about_vision_text', 'about_values_items', 'about_history_text', 'about_timeline_items'];

$settingsMap = [];
foreach (($all_settings ?? []) as $setting) {
    $settingsMap[(string) $setting['key']] = $setting;
}
unset($settingsMap['smtp_enabled']);

$fieldLabel = static fn(string $key): string => $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
