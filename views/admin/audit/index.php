<?php
// views/admin/audit/index.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$entityLabel = static function (?string $entity): string {
    $entity = (string) $entity;
    return match ($entity) {
        'books' => 'Recursos',
        'users' => 'Usuarios',
        'loans' => 'Prestamos',
        'reservations' => 'Reservaciones',
        'fines' => 'Multas',
        'news' => 'Noticias',
        default => $entity !== '' ? ucfirst($entity) : 'Sistema',
    };
};

$actionClass = static function (string $action): string {
    $action = mb_strtolower($action);
    if (str_contains($action, 'create') || str_contains($action, 'crear')) {
        return 'bg-emerald-100 text-emerald-700';
    }
    if (str_contains($action, 'update') || str_contains($action, 'editar') || str_contains($action, 'modificar')) {
        return 'bg-blue-100 text-blue-700';
    }
    if (str_contains($action, 'delete') || str_contains($action, 'eliminar')) {
        return 'bg-red-100 text-red-700';
    }
    return 'bg-slate-100 text-slate-700';
};

$mailActionClass = static function (string $action): string {
    $action = mb_strtolower($action);
    if (str_contains($action, 'success')) {
        return 'bg-emerald-100 text-emerald-700';
    }
    if (str_contains($action, 'failed')) {
        return 'bg-red-100 text-red-700';
    }
    return 'bg-slate-100 text-slate-700';
};

$mailSourceLabel = static function (?string $source): string {
    $source = mb_strtolower(trim((string) $source));
    return match ($source) {
        'queue' => 'Cola',
        'smtp_test' => 'Prueba SMTP',
        'direct' => 'Directo',
        default => $source !== '' ? ucfirst($source) : 'N/A',
    };
};

$activeTab = (string) ($active_tab ?? 'general');
$tabs = is_array($audit_tabs ?? null) ? $audit_tabs : [
    'general' => 'General',
    'correos' => 'Correos',
    'seguridad' => 'Seguridad',
    'sistema' => 'Sistema',
];
$activeLabel = (string) ($tabs[$activeTab] ?? 'General');
?>

<section class="p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Sistema</p>
            <h1 class="headline-lg text-on-surface">Auditoria</h1>
            <p class="body-md mt-1">Trazabilidad de acciones administrativas recientes.</p>
        </div>
    </div>

    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-outline-variant/60 bg-white px-3 py-1.5 shadow-ambient text-xs font-semibold text-on-surface-muted">
        <span class="h-2 w-2 rounded-full bg-primary inline-block"></span>
        <span>Submenu activo:</span>
        <span class="text-primary"><?= $e($activeLabel) ?></span>
    </div>

    <?php
    if ($activeTab === 'correos') {
        require __DIR__ . '/tabs/correos.php';
    } elseif ($activeTab === 'seguridad') {
        require __DIR__ . '/tabs/seguridad.php';
    } elseif ($activeTab === 'sistema') {
        require __DIR__ . '/tabs/sistema.php';
    } else {
        require __DIR__ . '/tabs/general.php';
    }
    ?>
</section>
