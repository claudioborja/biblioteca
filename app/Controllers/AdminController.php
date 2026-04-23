<?php
// app/Controllers/AdminController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;

final class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function dashboard(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();

        // ── Recursos ──────────────────────────────────────────────────────────
        $totalResources = (int) $this->db->query('SELECT COUNT(*) FROM resources')->fetchColumn();
        $totalTypes     = (int) $this->db->query("SELECT COUNT(DISTINCT COALESCE(NULLIF(support_type, ''), 'other')) FROM resources")->fetchColumn();
        $resourcesByType = $this->db->query(
            "SELECT COALESCE(NULLIF(r.support_type, ''), 'other') AS resource_type, COUNT(*) AS resources_count
             FROM resources r
             GROUP BY COALESCE(NULLIF(r.support_type, ''), 'other')
             ORDER BY resources_count DESC, resource_type ASC"
        )->fetchAll();

        // ── Usuarios ──────────────────────────────────────────────────────────
        $userStats = $this->db->query(
            "SELECT
                COUNT(*) AS total_users,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_users,
                SUM(CASE WHEN status IN ('suspended','blocked') THEN 1 ELSE 0 END) AS restricted_users,
                SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) AS member_users,
                SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) AS teacher_users
             FROM users
             WHERE role <> 'admin'"
        )->fetch() ?: [];

        // ── Préstamos ─────────────────────────────────────────────────────────
        $loanStats = $this->db->query(
            "SELECT
                COUNT(*) AS total_loans,
                SUM(CASE WHEN status = 'active'  THEN 1 ELSE 0 END) AS active_loans,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) AS overdue_loans,
                SUM(CASE WHEN status = 'returned' AND DATE(returned_at) = CURDATE() THEN 1 ELSE 0 END) AS returned_today
             FROM loans"
        )->fetch() ?: [];

        // ── Multas ────────────────────────────────────────────────────────────
        $fineStats = $this->db->query(
            "SELECT
                SUM(CASE WHEN status IN ('pending','partially_paid') THEN amount - amount_paid ELSE 0 END) AS pending_amount,
                COUNT(CASE WHEN status IN ('pending','partially_paid') THEN 1 END) AS pending_count
             FROM fines"
        )->fetch() ?: [];

        // ── Visitas ───────────────────────────────────────────────────────────
        $visitStats = $this->db->query(
            "SELECT
                COUNT(*) AS total_visits,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS visits_30d,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS visits_today,
                COUNT(DISTINCT COALESCE(CAST(user_id AS CHAR), ip_address)) AS unique_visitors
             FROM visits_log"
        )->fetch() ?: [];

        // ── Gráfico visitas: últimos 14 días ──────────────────────────────────
        $visitsChart = $this->db->query(
            "SELECT DATE(created_at) AS day, COUNT(*) AS n
             FROM visits_log
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC"
        )->fetchAll();

        // Rellenar días sin visitas
        $visitsChartFull = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $visitsChartFull[$day] = 0;
        }
        foreach ($visitsChart as $row) {
            $visitsChartFull[(string)$row['day']] = (int)$row['n'];
        }

        // ── Gráfico préstamos: últimos 14 días ────────────────────────────────
        $loansChart = $this->db->query(
            "SELECT DATE(loan_at) AS day, COUNT(*) AS n
             FROM loans
             WHERE loan_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
             GROUP BY DATE(loan_at)
             ORDER BY day ASC"
        )->fetchAll();
        $loansChartFull = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $loansChartFull[$day] = 0;
        }
        foreach ($loansChart as $row) {
            $loansChartFull[(string)$row['day']] = (int)$row['n'];
        }

        // ── Préstamos recientes (actividad) ───────────────────────────────────
        $recentLoans = $this->db->query(
            "SELECT u.name AS user_name, r.title AS resource_title, l.loan_at, l.due_at, l.status
             FROM loans l
             JOIN users u     ON u.id = l.user_id
             JOIN resources r ON r.id = l.resource_id
             ORDER BY l.loan_at DESC
             LIMIT 6"
        )->fetchAll();

        return Response::html($this->view->render('admin/dashboard', [
            'title'           => 'Dashboard - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'        => $settings,
            'auth_user'       => $authUser,
            'total_resources' => $totalResources,
            'total_types'     => $totalTypes,
            'resources_by_type' => $resourcesByType,
            'user_stats'      => $userStats,
            'loan_stats'      => $loanStats,
            'fine_stats'      => $fineStats,
            'visit_stats'     => $visitStats,
            'visits_chart'    => $visitsChartFull,
            'loans_chart'     => $loansChartFull,
            'recent_loans'    => $recentLoans,
        ], 'layouts/panel'));
    }

    public function auditLogs(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();

        $activeTab = trim((string) $request->get('tab', 'general'));
        $allowedTabs = ['general', 'correos', 'seguridad', 'sistema'];
        if (!in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'general';
        }

        $generalEntityFilter = trim((string) $request->get('entity', ''));
        $generalActionFilter = trim((string) $request->get('action', ''));
        $emailSourceFilter = trim((string) $request->get('source', ''));
        $emailStatusFilter = trim((string) $request->get('status', ''));

        $generalSql = "SELECT
                a.id,
                a.action,
                a.entity_type,
                a.entity_id,
                a.ip_address,
                a.created_at,
                u.name AS user_name,
                u.email AS user_email
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE NOT (a.entity_type = 'emails' OR a.action LIKE 'mail_%')";
        $generalParams = [];

        if ($generalEntityFilter !== '') {
            $generalSql .= ' AND a.entity_type = ?';
            $generalParams[] = $generalEntityFilter;
        }

        if ($generalActionFilter !== '') {
            $generalSql .= ' AND a.action = ?';
            $generalParams[] = $generalActionFilter;
        }

        $generalSql .= ' ORDER BY a.created_at DESC LIMIT 80';
        $generalStmt = $this->db->prepare($generalSql);
        $generalStmt->execute($generalParams);
        $logs = $generalStmt->fetchAll();

        $summary = [
            'total' => count($logs),
            'create' => 0,
            'update' => 0,
            'delete' => 0,
            'other' => 0,
        ];

        foreach ($logs as $log) {
            $action = mb_strtolower((string) ($log['action'] ?? ''));
            if (str_contains($action, 'create') || str_contains($action, 'crear')) {
                $summary['create']++;
            } elseif (str_contains($action, 'update') || str_contains($action, 'editar') || str_contains($action, 'modificar')) {
                $summary['update']++;
            } elseif (str_contains($action, 'delete') || str_contains($action, 'eliminar')) {
                $summary['delete']++;
            } else {
                $summary['other']++;
            }
        }

        $emailSql = "SELECT
                a.id,
                a.action,
                a.entity_id,
                a.ip_address,
                a.created_at,
                u.name AS user_name,
                u.email AS user_email,
                JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.to_email')) AS to_email,
                JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.subject')) AS subject,
                JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.source')) AS source,
                JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.error')) AS error_message
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.entity_type = 'emails' OR a.action LIKE 'mail_%'";
        $emailParams = [];

        if ($emailSourceFilter !== '') {
            $emailSql .= " AND JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.source')) = ?";
            $emailParams[] = $emailSourceFilter;
        }

        if ($emailStatusFilter === 'success') {
            $emailSql .= " AND a.action LIKE 'mail_send_success%'";
        } elseif ($emailStatusFilter === 'failed') {
            $emailSql .= " AND a.action LIKE 'mail_send_failed%'";
        }

        $emailSql .= ' ORDER BY a.created_at DESC LIMIT 80';
        $emailStmt = $this->db->prepare($emailSql);
        $emailStmt->execute($emailParams);
        $emailLogs = $emailStmt->fetchAll();

        $mailSummary = [
            'total' => count($emailLogs),
            'success' => 0,
            'failed' => 0,
            'queue' => 0,
            'smtp_test' => 0,
        ];

        foreach ($emailLogs as $mailLog) {
            $action = mb_strtolower((string) ($mailLog['action'] ?? ''));
            $source = mb_strtolower((string) ($mailLog['source'] ?? ''));

            if (str_contains($action, 'success')) {
                $mailSummary['success']++;
            } elseif (str_contains($action, 'failed')) {
                $mailSummary['failed']++;
            }

            if ($source === 'queue') {
                $mailSummary['queue']++;
            } elseif ($source === 'smtp_test') {
                $mailSummary['smtp_test']++;
            }
        }

        $systemAudit = $this->buildSystemAuditData();
        $securityAudit = $this->buildSecurityAuditData();

        return Response::html($this->view->render('admin/audit/index', [
            'title' => 'Auditoria - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'active_tab' => $activeTab,
            'audit_tabs' => [
                'general' => 'General',
                'correos' => 'Correos',
                'seguridad' => 'Seguridad',
                'sistema' => 'Sistema',
            ],
            'logs' => $logs,
            'summary' => $summary,
            'general_entity_filter' => $generalEntityFilter,
            'general_action_filter' => $generalActionFilter,
            'email_logs' => $emailLogs,
            'mail_summary' => $mailSummary,
            'email_source_filter' => $emailSourceFilter,
            'email_status_filter' => $emailStatusFilter,
            'security_summary' => $securityAudit['summary'],
            'security_controls' => $securityAudit['controls'],
            'security_events' => $securityAudit['events'],
            'security_throttle_entries' => $securityAudit['throttle_entries'],
            'system_summary' => $systemAudit['summary'],
            'system_log_files' => $systemAudit['log_files'],
            'system_jobs' => $systemAudit['jobs'],
            'system_recent_logs' => $systemAudit['recent_logs'],
        ], 'layouts/panel'));
    }

    /**
     * Build security-audit metrics from auth log, throttle files and audit table.
     *
     * @return array{summary: array<string, int|string>, controls: array<int, array<string, string>>, events: array<int, array<string, string>>, throttle_entries: array<int, array<string, int|string>>}
     */
    private function buildSecurityAuditData(): array
    {
        $now = time();
        $window24h = $now - 86400;
        $lockoutSeconds = 900;
        $maxAttempts = 5;

        $authEvents = [];
        $authLogPath = BASE_PATH . '/storage/logs/auth.log';
        foreach ($this->tailLogFile($authLogPath, 600) as $line) {
            $parsed = $this->parseAuthLogLine($line);
            if ($parsed !== null) {
                $authEvents[] = $parsed;
            }
        }

        $dbSecurityStmt = $this->db->prepare(
            "SELECT a.action, a.ip_address, a.created_at, a.new_values, u.name AS user_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
               AND (
                   a.action LIKE 'login_%'
                   OR a.action LIKE '%password%'
                   OR a.action LIKE '%role%'
                   OR a.action LIKE '%verify%'
                   OR a.action LIKE '%reset%'
               )
             ORDER BY a.created_at DESC
             LIMIT 160"
        );
        $dbSecurityStmt->execute();
        $dbSecurityRows = $dbSecurityStmt->fetchAll();

        $events = [];

        foreach ($authEvents as $event) {
            $events[] = [
                'occurred_at' => (string) ($event['occurred_at'] ?? ''),
                'action' => (string) ($event['action'] ?? ''),
                'severity' => (string) ($event['severity'] ?? 'info'),
                'source' => 'auth.log',
                'ip' => (string) ($event['ip'] ?? '-'),
                'actor' => (string) ($event['actor'] ?? 'Sistema'),
                'details' => (string) ($event['details'] ?? ''),
            ];
        }

        foreach ($dbSecurityRows as $row) {
            $action = (string) ($row['action'] ?? 'security_event');
            $details = '';
            $rawPayload = (string) ($row['new_values'] ?? '');
            if ($rawPayload !== '') {
                $decoded = json_decode($rawPayload, true);
                if (is_array($decoded)) {
                    if (isset($decoded['target_email'])) {
                        $details = 'Objetivo: ' . (string) $decoded['target_email'];
                    } elseif (isset($decoded['to_email'])) {
                        $details = 'Correo: ' . (string) $decoded['to_email'];
                    }
                }
            }

            $events[] = [
                'occurred_at' => (string) ($row['created_at'] ?? ''),
                'action' => $action,
                'severity' => $this->classifySecuritySeverity($action),
                'source' => 'audit_logs',
                'ip' => (string) ($row['ip_address'] ?? '-'),
                'actor' => (string) (($row['user_name'] ?? '') !== '' ? $row['user_name'] : 'Sistema'),
                'details' => $details,
            ];
        }

        usort($events, static function (array $a, array $b): int {
            return strcmp((string) ($b['occurred_at'] ?? ''), (string) ($a['occurred_at'] ?? ''));
        });
        $events = array_slice($events, 0, 120);

        $failed24h = 0;
        $success24h = 0;
        $passwordReset24h = 0;
        $events24h = 0;
        $ips24h = [];

        foreach ($events as $event) {
            $action = mb_strtolower((string) ($event['action'] ?? ''));
            $ts = strtotime((string) ($event['occurred_at'] ?? '')) ?: 0;
            if ($ts >= $window24h) {
                $events24h++;
                $ip = trim((string) ($event['ip'] ?? ''));
                if ($ip !== '' && $ip !== '-' && $ip !== 'cli') {
                    $ips24h[$ip] = true;
                }
                if ($action === 'login_failed') {
                    $failed24h++;
                } elseif ($action === 'login_success') {
                    $success24h++;
                } elseif ($action === 'password_reset_requested') {
                    $passwordReset24h++;
                }
            }
        }

        $throttleDir = BASE_PATH . '/storage/throttle';
        $throttleEntries = [];
        $activeLocks = 0;
        $throttleFiles = glob($throttleDir . '/*.json') ?: [];

        foreach ($throttleFiles as $path) {
            if (!is_file($path)) {
                continue;
            }

            $raw = @file_get_contents($path);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($decoded)) {
                continue;
            }

            $attempts = (int) ($decoded['attempts'] ?? 0);
            $lastAttempt = (int) ($decoded['last_attempt'] ?? 0);
            if ($lastAttempt <= 0) {
                $mtime = (int) @filemtime($path);
                $lastAttempt = $mtime > 0 ? $mtime : 0;
            }

            $expiresAt = $lastAttempt + $lockoutSeconds;
            $remaining = $expiresAt - $now;

            if ($remaining <= 0) {
                continue;
            }

            $isLocked = $attempts >= $maxAttempts;
            if ($isLocked) {
                $activeLocks++;
            }

            $throttleEntries[] = [
                'key' => basename($path),
                'attempts' => $attempts,
                'status' => $isLocked ? 'Bloqueado' : 'En observación',
                'last_attempt' => date('Y-m-d H:i:s', $lastAttempt),
                'remaining_seconds' => max(0, $remaining),
            ];
        }

        usort($throttleEntries, static function (array $a, array $b): int {
            return ((int) ($b['remaining_seconds'] ?? 0)) <=> ((int) ($a['remaining_seconds'] ?? 0));
        });

        $restrictedUsers = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE status IN ('suspended','blocked')")->fetchColumn();
        $roleChanges30d = (int) $this->db->query(
            "SELECT COUNT(*)
             FROM audit_logs
             WHERE action = 'user_role_changed'
               AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )->fetchColumn();

        $sampleHash = (string) ($this->db->query("SELECT password_hash FROM users WHERE password_hash IS NOT NULL AND password_hash <> '' ORDER BY id DESC LIMIT 1")->fetchColumn() ?: '');
        $passwordAlgo = str_starts_with($sampleHash, '$argon2id$')
            ? 'Argon2id'
            : (str_starts_with($sampleHash, '$2y$') ? 'Bcrypt' : 'Desconocido');

        $controls = [
            [
                'control' => 'CSRF Middleware',
                'status' => class_exists(\Middleware\CsrfMiddleware::class) ? 'Activo' : 'No disponible',
                'detail' => 'Protección de formularios por token de sesión.',
            ],
            [
                'control' => 'Rate limiting de login',
                'status' => is_dir($throttleDir) ? 'Activo' : 'No disponible',
                'detail' => 'Ventana de bloqueo: 15 minutos, máximo 5 intentos.',
            ],
            [
                'control' => 'Security headers middleware',
                'status' => $this->isSecurityHeadersMiddlewareActive() ? 'Aplicado' : 'No aplicado',
                'detail' => 'X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy.',
            ],
            [
                'control' => 'Canal HTTPS detectado',
                'status' => $this->isHttpsEnabled() ? 'Sí' : 'No',
                'detail' => 'Detección por variables del servidor para la solicitud actual.',
            ],
            [
                'control' => 'Algoritmo de hash de contraseña',
                'status' => $passwordAlgo,
                'detail' => 'Muestra tomada del último hash registrado en usuarios.',
            ],
            [
                'control' => 'Directorio de logs escribible',
                'status' => is_writable(BASE_PATH . '/storage/logs') ? 'Sí' : 'No',
                'detail' => 'Necesario para auditoría de auth y eventos críticos.',
            ],
        ];

        return [
            'summary' => [
                'events_24h' => $events24h,
                'failed_logins_24h' => $failed24h,
                'successful_logins_24h' => $success24h,
                'password_resets_24h' => $passwordReset24h,
                'unique_ips_24h' => count($ips24h),
                'active_locks' => $activeLocks,
                'restricted_accounts' => $restrictedUsers,
                'role_changes_30d' => $roleChanges30d,
                'throttle_records' => count($throttleEntries),
                'generated_at' => date('Y-m-d H:i:s'),
            ],
            'controls' => $controls,
            'events' => array_slice($events, 0, 80),
            'throttle_entries' => array_slice($throttleEntries, 0, 40),
        ];
    }

    /**
     * Parse a line from storage/logs/auth.log.
     *
     * @return array<string, string>|null
     */
    private function parseAuthLogLine(string $line): ?array
    {
        $line = trim($line);
        if ($line === '') {
            return null;
        }

        $pattern = '/^\[(?<ts>[^\]]+)\]\s+action=(?<action>\S+)\s+user_id=(?<user>\S+)\s+ip=(?<ip>\S*)\s*(?<ctx>.*)$/';
        if (!preg_match($pattern, $line, $match)) {
            return null;
        }

        $action = (string) ($match['action'] ?? 'event');
        $contextRaw = trim((string) ($match['ctx'] ?? ''));
        $details = '';

        if ($contextRaw !== '') {
            $decoded = json_decode($contextRaw, true);
            if (is_array($decoded)) {
                foreach (['reset_url', 'token', 'password', 'password_hash'] as $sensitiveKey) {
                    if (array_key_exists($sensitiveKey, $decoded)) {
                        $decoded[$sensitiveKey] = '[redacted]';
                    }
                }
                $details = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
            } else {
                $details = $contextRaw;
            }
        }

        return [
            'occurred_at' => (string) ($match['ts'] ?? ''),
            'action' => $action,
            'severity' => $this->classifySecuritySeverity($action),
            'ip' => (string) ($match['ip'] ?? '-'),
            'actor' => (string) (($match['user'] ?? 'null') === 'null' ? 'Anónimo' : ('Usuario #' . (string) $match['user'])),
            'details' => $details,
        ];
    }

    private function classifySecuritySeverity(string $action): string
    {
        $action = mb_strtolower(trim($action));

        if ($action === 'login_failed' || str_contains($action, 'failed') || str_contains($action, 'blocked')) {
            return 'high';
        }

        if (str_contains($action, 'password') || str_contains($action, 'role') || str_contains($action, 'reset')) {
            return 'medium';
        }

        return 'low';
    }

    private function isSecurityHeadersMiddlewareActive(): bool
    {
        $pipelinePath = BASE_PATH . '/app/Core/MiddlewarePipeline.php';
        if (!is_file($pipelinePath)) {
            return false;
        }

        $content = (string) @file_get_contents($pipelinePath);
        if ($content === '') {
            return false;
        }

        return str_contains($content, 'SecurityHeadersMiddleware::class')
            || str_contains($content, "'security'");
    }

    private function isHttpsEnabled(): bool
    {
        $https = (string) ($_SERVER['HTTPS'] ?? '');
        $forwardedProto = (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
        $forwardedSsl = (string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '');

        return ($https !== '' && $https !== 'off')
            || mb_strtolower($forwardedProto) === 'https'
            || mb_strtolower($forwardedSsl) === 'on';
    }

    /**
     * Build system-audit data from local log files and monitored cron/task logs.
     * This is intentionally file-based so it works in limited hosting environments.
     *
     * @return array{summary: array<string, int|string>, log_files: array<int, array<string, mixed>>, jobs: array<int, array<string, mixed>>, recent_logs: array<int, array<string, mixed>>}
     */
    private function buildSystemAuditData(): array
    {
        $logsDir = BASE_PATH . '/storage/logs';
        $logFiles = [];
        $recentLogs = [];
        $totalLogSize = 0;
        $updatedLast24h = 0;
        $errorLikeFiles = 0;

        $paths = glob($logsDir . '/*.log') ?: [];
        rsort($paths);

        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }

            $basename = basename($path);
            $size = (int) @filesize($path);
            $mtime = (int) @filemtime($path);
            $modifiedAt = $mtime > 0 ? date('Y-m-d H:i:s', $mtime) : null;
            $category = str_contains($basename, 'cron-')
                ? 'Tarea programada'
                : (str_contains($basename, 'php') ? 'PHP' : 'Aplicación');

            $logFiles[] = [
                'name' => $basename,
                'path' => $path,
                'size_bytes' => $size,
                'size_human' => $this->humanFileSize($size),
                'modified_at' => $modifiedAt,
                'category' => $category,
            ];

            $totalLogSize += $size;

            if ($mtime >= time() - 86400) {
                $updatedLast24h++;
            }

            if (str_contains($basename, 'php') || str_contains($basename, 'error') || str_contains($basename, 'critical')) {
                $errorLikeFiles++;
            }
        }

        usort($logFiles, static fn(array $a, array $b): int => strcmp((string) ($b['modified_at'] ?? ''), (string) ($a['modified_at'] ?? '')));

        $recentLogCandidates = array_slice($logFiles, 0, 4);
        foreach ($recentLogCandidates as $logFile) {
            $recentLines = $this->tailLogFile((string) ($logFile['path'] ?? ''), 6);
            foreach ($recentLines as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $recentLogs[] = [
                    'file' => (string) ($logFile['name'] ?? ''),
                    'modified_at' => (string) ($logFile['modified_at'] ?? ''),
                    'line' => trim($line),
                ];
            }
        }
        $recentLogs = array_slice($recentLogs, 0, 12);

        $jobs = [];
        $jobsRecent = 0;
        $jobsStale = 0;
        $cronLogsByName = [];

        foreach ($logFiles as $logFile) {
            $logName = (string) ($logFile['name'] ?? '');
            if (str_starts_with($logName, 'cron-') && str_ends_with($logName, '.log')) {
                $cronLogsByName[$logName] = $logFile;
            }
        }

        $scriptPaths = glob(BASE_PATH . '/bin/*.php') ?: [];
        sort($scriptPaths);

        foreach ($scriptPaths as $scriptPath) {
            $script = basename($scriptPath);
            $scriptStem = pathinfo($script, PATHINFO_FILENAME);
            $normalizedStem = str_replace('_', '-', $scriptStem);

            $candidateLogs = [
                'cron-' . $normalizedStem . '.log',
            ];

            if (str_ends_with($scriptStem, '_check')) {
                $withoutSuffix = substr($scriptStem, 0, -6);
                if ($withoutSuffix !== '') {
                    $candidateLogs[] = 'cron-' . str_replace('_', '-', $withoutSuffix) . '.log';
                }
            }

            $matchedLogName = null;
            foreach ($candidateLogs as $candidateLog) {
                if (isset($cronLogsByName[$candidateLog])) {
                    $matchedLogName = $candidateLog;
                    break;
                }
            }

            if ($matchedLogName === null) {
                $stemTokens = array_values(array_filter(explode('-', $normalizedStem), static fn(string $token): bool => $token !== ''));
                foreach (array_keys($cronLogsByName) as $cronLogName) {
                    foreach ($stemTokens as $token) {
                        if (strlen($token) >= 4 && str_contains($cronLogName, $token)) {
                            $matchedLogName = $cronLogName;
                            break 2;
                        }
                    }
                }
            }

            $matchedLog = $matchedLogName !== null ? ($cronLogsByName[$matchedLogName] ?? null) : null;
            $logMtime = 0;
            $logSize = 0;

            if (is_array($matchedLog)) {
                $logMtime = strtotime((string) ($matchedLog['modified_at'] ?? '')) ?: 0;
                $logSize = (int) ($matchedLog['size_bytes'] ?? 0);
            }

            $secondsSinceUpdate = $logMtime > 0 ? time() - $logMtime : null;

            $status = 'Sin registro';
            if ($secondsSinceUpdate !== null) {
                if ($secondsSinceUpdate <= 86400) {
                    $status = 'Reciente';
                    $jobsRecent++;
                } elseif ($secondsSinceUpdate <= 259200) {
                    $status = 'Desactualizado';
                    $jobsStale++;
                } else {
                    $status = 'Sin actividad reciente';
                    $jobsStale++;
                }
            }

            $jobs[] = [
                'label' => ucwords(str_replace(['_', '-'], ' ', $scriptStem)),
                'script' => $script,
                'script_exists' => true,
                'log_name' => $matchedLogName,
                'log_exists' => $matchedLogName !== null,
                'status' => $status,
                'modified_at' => $logMtime > 0 ? date('Y-m-d H:i:s', $logMtime) : null,
                'size_human' => $this->humanFileSize($logSize),
            ];
        }

        usort($jobs, static function (array $a, array $b): int {
            $aTime = strtotime((string) ($a['modified_at'] ?? '')) ?: 0;
            $bTime = strtotime((string) ($b['modified_at'] ?? '')) ?: 0;
            return $bTime <=> $aTime;
        });

        return [
            'summary' => [
                'total_log_files' => count($logFiles),
                'total_log_size' => $this->humanFileSize($totalLogSize),
                'updated_last_24h' => $updatedLast24h,
                'error_like_files' => $errorLikeFiles,
                'monitored_jobs' => count($jobs),
                'jobs_recent' => $jobsRecent,
                'jobs_stale' => $jobsStale,
                'generated_at' => date('Y-m-d H:i:s'),
            ],
            'log_files' => array_slice($logFiles, 0, 12),
            'jobs' => $jobs,
            'recent_logs' => $recentLogs,
        ];
    }

    private function tailLogFile(string $path, int $lineCount = 5): array
    {
        if ($path === '' || !is_file($path) || $lineCount <= 0) {
            return [];
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines) || $lines === []) {
            return [];
        }

        return array_slice($lines, -$lineCount);
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $size = $bytes / 1024;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, $size >= 10 ? 0 : 1, '.', '') . ' ' . $units[$unitIndex];
    }

    public function settings(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }
        return Response::redirect(BASE_URL . '/admin/settings/library');
    }

    public function settingsLibrary(Request $request): Response
    {
        return $this->renderSettingsSection('library');
    }

    public function settingsLoans(Request $request): Response
    {
        return $this->renderSettingsSection('loans');
    }

    public function settingsFines(Request $request): Response
    {
        return $this->renderSettingsSection('fines');
    }

    public function settingsNotifications(Request $request): Response
    {
        return $this->renderSettingsSection('notifications');
    }

    public function settingsSmtp(Request $request): Response
    {
        return $this->renderSettingsSection('smtp');
    }

    public function settingsAbout(Request $request): Response
    {
        return $this->renderSettingsSection('about');
    }

    public function settingsSystem(Request $request): Response
    {
        return $this->renderSettingsSection('system');
    }

    private function renderSettingsSection(string $section): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $this->ensureCoreSettings();
        $this->ensureSmtpSettings();
        $this->ensureAboutSettings();

        $settings = $this->panelSettings();
        $allSettings = $this->db
            ->query('SELECT `key`, value, type FROM system_settings ORDER BY `key`')
            ->fetchAll();

        $activeSection = $this->normalizeSettingsSection($section);

        return Response::html($this->view->render('admin/settings/' . $activeSection, [
            'title' => 'Configuracion - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'all_settings' => $allSettings,
            'csrf' => \Middleware\CsrfMiddleware::token(),
            'active_section' => $activeSection,
        ], 'layouts/panel'));
    }

    public function updateSettings(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $this->ensureCoreSettings();
        $this->ensureSmtpSettings();
        $this->ensureAboutSettings();

        // Handle logo / favicon file uploads before the generic key processing
        $uploadKeys = ['library_logo', 'library_favicon'];
        foreach ($uploadKeys as $uploadKey) {
            $file = $request->file($uploadKey);
            if ($file !== null && (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $path = $this->storeSettingImage($file, $uploadKey);
                if ($path !== null) {
                    $stmt = $this->db->prepare('UPDATE system_settings SET value = ? WHERE `key` = ?');
                    $stmt->execute([$path, $uploadKey]);
                }
            }
        }

        $requestedTab = $this->normalizeSettingsSection((string) $request->post('active_tab', ''));
        $sectionPath = fn(string $tab): string => BASE_URL . '/admin/settings/' . $this->normalizeSettingsSection($tab);

        $sectionKey = trim((string) $request->post('section_key', ''));
        if ($sectionKey !== '') {
            $rawSectionKeys = $request->post('section_keys', []);
            $sectionKeys = [];
            if (is_array($rawSectionKeys)) {
                $csv = (string) ($rawSectionKeys[$sectionKey] ?? '');
                foreach (explode(',', $csv) as $candidate) {
                    $candidate = trim($candidate);
                    if ($candidate !== '') {
                        $sectionKeys[] = $candidate;
                    }
                }
            }

            if ($sectionKeys === []) {
                Session::flash('error', 'No se encontraron configuraciones para la seccion seleccionada.');
                return Response::redirect($sectionPath($sectionKey));
            }

            $placeholders = implode(',', array_fill(0, count($sectionKeys), '?'));
            $rowStmt = $this->db->prepare("SELECT `key`, type FROM system_settings WHERE `key` IN ({$placeholders})");
            $rowStmt->execute($sectionKeys);
            $rows = $rowStmt->fetchAll();

            $stmt = $this->db->prepare('UPDATE system_settings SET value = ? WHERE `key` = ?');

            foreach ($rows as $row) {
                $key = (string) $row['key'];
                $type = (string) $row['type'];
                $raw = $request->post('settings', []);
                $input = is_array($raw) ? ($raw[$key] ?? null) : null;

                if ($input === null) {
                    if ($type === 'boolean') {
                        $value = 'false';
                    } else {
                        continue;
                    }
                } else {
                    $value = $this->normalizeSettingValue($input, $type);
                }

                $stmt->execute([$value, $key]);
            }

            Session::flash('success', 'Seccion actualizada correctamente.');
            return Response::redirect($sectionPath($sectionKey));
        }

        $singleKey = trim((string) $request->post('single_key', ''));

        if ($singleKey !== '') {
            $rowStmt = $this->db->prepare('SELECT `key`, type FROM system_settings WHERE `key` = ? LIMIT 1');
            $rowStmt->execute([$singleKey]);
            $row = $rowStmt->fetch();

            if ($row !== false) {
                $key = (string) $row['key'];
                $type = (string) $row['type'];
                $raw = $request->post('settings', []);
                $input = is_array($raw) ? ($raw[$key] ?? null) : null;

                if ($input === null) {
                    $value = $type === 'boolean' ? 'false' : '';
                } else {
                    $value = $this->normalizeSettingValue($input, $type);
                }

                $stmt = $this->db->prepare('UPDATE system_settings SET value = ? WHERE `key` = ?');
                $stmt->execute([$value, $key]);

                Session::flash('success', 'Configuracion actualizada correctamente.');
                return Response::redirect($sectionPath($requestedTab));
            }

            Session::flash('error', 'No se encontro la configuracion solicitada.');
            return Response::redirect($sectionPath($requestedTab));
        }

        $rows = $this->db
            ->query('SELECT `key`, type FROM system_settings ORDER BY `key`')
            ->fetchAll();

        $stmt = $this->db->prepare('UPDATE system_settings SET value = ? WHERE `key` = ?');

        foreach ($rows as $row) {
            $key = (string) $row['key'];
            $type = (string) $row['type'];
            $raw = $request->post('settings', []);
            $input = is_array($raw) ? ($raw[$key] ?? null) : null;

            if ($input === null) {
                if ($type === 'boolean') {
                    $value = 'false';
                } else {
                    continue;
                }
            } else {
                $value = $this->normalizeSettingValue($input, $type);
            }

            $stmt->execute([$value, $key]);
        }

        Session::flash('success', 'Configuracion actualizada correctamente.');
        return Response::redirect($sectionPath($requestedTab));
    }

    private function normalizeSettingsSection(string $section): string
    {
        $allowed = ['library', 'loans', 'fines', 'notifications', 'smtp', 'about', 'system'];
        $section = trim($section);
        return in_array($section, $allowed, true) ? $section : 'library';
    }

    public function categories(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();
        $categories = $this->db->query(
            "SELECT c.id, c.name, c.slug, c.description, c.created_at,
                    COUNT(b.id) AS resources_count
             FROM categories c
             LEFT JOIN resources b ON b.category_id = c.id
             GROUP BY c.id, c.name, c.slug, c.description, c.created_at
             ORDER BY c.name ASC"
        )->fetchAll();

        return Response::html($this->view->render('admin/categories/index', [
            'title' => 'Categorias - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'categories' => $categories,
            'csrf' => \Middleware\CsrfMiddleware::token(),
        ], 'layouts/panel'));
    }

    private function normalizeCategoryName(string $name): string
    {
        // Convert to Title Case preserving UTF-8 (Spanish accents, ñ, etc.)
        $words = explode(' ', mb_strtolower($name, 'UTF-8'));
        $small = ['de', 'del', 'la', 'las', 'los', 'el', 'y', 'e', 'o', 'u', 'a', 'en', 'por', 'con'];
        foreach ($words as $i => &$word) {
            if ($i === 0 || !in_array($word, $small, true)) {
                $word = mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8')
                      . mb_substr($word, 1, null, 'UTF-8');
            }
        }
        return implode(' ', $words);
    }

    public function storeCategory(Request $request): Response
    {
        $name = $this->normalizeCategoryName(trim((string) $request->post('name', '')));
        $description = trim((string) $request->post('description', ''));
        if ($name === '') {
            Session::flash('error', 'El nombre de la categoria es obligatorio.');
            return Response::redirect(BASE_URL . '/admin/categories');
        }

        $slug = $this->uniqueCategorySlug($this->slugify($name));

        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $name,
            $slug,
            $description !== '' ? $description : null,
        ]);

        Session::flash('success', 'Categoria creada correctamente.');
        return Response::redirect(BASE_URL . '/admin/categories');
    }

    public function updateCategory(Request $request, string $id = ''): Response
    {
        $categoryId = (int) $id;
        if ($categoryId <= 0) {
            Session::flash('error', 'Categoria no valida.');
            return Response::redirect(BASE_URL . '/admin/categories');
        }

        $name = $this->normalizeCategoryName(trim((string) $request->post('name', '')));
        $description = trim((string) $request->post('description', ''));
        if ($name === '') {
            Session::flash('error', 'El nombre de la categoria es obligatorio.');
            return Response::redirect(BASE_URL . '/admin/categories');
        }

        $slug = $this->uniqueCategorySlug($this->slugify($name), $categoryId);

        $stmt = $this->db->prepare(
            'UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?'
        );
        $stmt->execute([
            $name,
            $slug,
            $description !== '' ? $description : null,
            $categoryId,
        ]);

        Session::flash('success', 'Categoria actualizada correctamente.');
        return Response::redirect(BASE_URL . '/admin/categories');
    }

    public function deleteCategory(Request $request, string $id = ''): Response
    {
        $categoryId = (int) $id;
        if ($categoryId <= 0) {
            Session::flash('error', 'Categoria no valida.');
            return Response::redirect(BASE_URL . '/admin/categories');
        }

        $booksCountStmt = $this->db->prepare('SELECT COUNT(*) FROM resources WHERE category_id = ?');
        $booksCountStmt->execute([$categoryId]);
        $booksCount = (int) $booksCountStmt->fetchColumn();

        if ($booksCount > 0) {
            Session::flash('error', 'No puedes eliminar una categoría que tiene recursos asociados.');
            return Response::redirect(BASE_URL . '/admin/categories');
        }

        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$categoryId]);

        Session::flash('success', 'Categoria eliminada correctamente.');
        return Response::redirect(BASE_URL . '/admin/categories');
    }

    public function testSmtp(Request $request): Response
    {
        header('Content-Type: application/json; charset=UTF-8');

        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            return new Response(json_encode(['ok' => false, 'message' => 'No autenticado.']), 401);
        }

        // CSRF check
        $token = trim((string) $request->post('_csrf_token', ''));
        if (!hash_equals(\Middleware\CsrfMiddleware::token(), $token)) {
            return new Response(json_encode(['ok' => false, 'message' => 'Token CSRF inválido.']), 403);
        }

        $to = trim((string) $request->post('to', ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return new Response(json_encode(['ok' => false, 'message' => 'Dirección de destino inválida.']), 422);
        }

        try {
            $template = new \Services\EmailTemplateService();
            $testTime = date('Y-m-d H:i:s');
            $bodyHtml = $template->renderSystem(
                preheader: 'Prueba del canal de correo',
                title: 'Prueba SMTP - Biblioteca',
                intro: 'Este mensaje confirma que el sistema puede enviar notificaciones correctamente.',
                contentHtml: '<p>La conexion SMTP, autenticacion y entrega del mensaje se completaron.</p>'
                    . '<p><strong>Fecha de prueba:</strong> ' . htmlspecialchars($testTime, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
                    . '<p><strong>Destinatario:</strong> ' . htmlspecialchars($to, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>',
                footerNote: 'Correo de prueba generado desde Configuracion > Correo SMTP.'
            );
            $bodyText = $template->renderSystemText(
                title: 'Prueba SMTP - Biblioteca',
                intro: 'Este mensaje confirma que el sistema puede enviar notificaciones correctamente.',
                contentText: "La conexion SMTP, autenticacion y entrega del mensaje se completaron.\n"
                    . "Fecha de prueba: {$testTime}\n"
                    . "Destinatario: {$to}",
                footerNote: 'Correo de prueba generado desde Configuracion > Correo SMTP.'
            );

            $queue   = new \Services\MailQueueService();
            $queueId = $queue->enqueue(
                toEmail:  $to,
                toName:   'Prueba SMTP',
                subject:  'Prueba SMTP — Biblioteca',
                bodyHtml: $bodyHtml,
                bodyText: $bodyText,
            );

            $mailer = new \Services\MailService();
            $result = $mailer->sendWithLog(
                toEmail:  $to,
                toName:   'Prueba SMTP',
                subject:  'Prueba SMTP — Biblioteca',
                bodyHtml: $bodyHtml,
                bodyText: $bodyText,
                context: [
                    'source' => 'smtp_test',
                    'queue_id' => $queueId,
                ],
            );

            array_unshift($result['steps'], ['type' => 'info', 'text' => "Encolado en email_queue id #{$queueId}, procesando…"]);

            if ($result['ok']) {
                $queue->markSent($queueId);
                $result['steps'][] = ['type' => 'ok', 'text' => "Cola #{$queueId} marcada como enviada."];
            } else {
                $queue->markFailed($queueId, 1, $result['message']);
                $result['steps'][] = ['type' => 'error', 'text' => "Cola #{$queueId} marcada como fallida."];
            }

            return new Response(json_encode($result), 200);
        } catch (\Throwable $e) {
            return new Response(json_encode([
                'ok'      => false,
                'message' => $e->getMessage(),
                'steps'   => [['type' => 'error', 'text' => $e->getMessage()]],
            ]), 200);
        }
    }

    public function mailQueue(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();

        // Stats by status
        $statsStmt = $this->db->query(
            "SELECT status, COUNT(*) AS cnt FROM email_queue GROUP BY status"
        );
        $rawStats = $statsStmt->fetchAll();
        $queueStats = ['pending' => 0, 'sent' => 0, 'failed' => 0, 'total' => 0];
        foreach ($rawStats as $row) {
            $key = (string) ($row['status'] ?? '');
            if (array_key_exists($key, $queueStats)) {
                $queueStats[$key] = (int) $row['cnt'];
            }
            $queueStats['total'] += (int) $row['cnt'];
        }

        // Recent 60 items, newest first
        $itemsStmt = $this->db->query(
            "SELECT id, to_email, to_name, subject, status, attempts, error_message,
                    scheduled_at, sent_at, created_at
             FROM email_queue
             ORDER BY id DESC
             LIMIT 60"
        );
        $queueItems = $itemsStmt->fetchAll();

        // Detect whether mail_worker cron is configured for the current OS user
        $cronOutput   = '';
        $cronConfigured = false;
        if (function_exists('shell_exec')) {
            $raw = @shell_exec('crontab -l 2>/dev/null');
            $cronOutput   = is_string($raw) ? $raw : '';
            $cronConfigured = str_contains($cronOutput, 'mail_worker');
        }

        // Check if exec is available for "run worker now" action
        $execAvailable = function_exists('exec') && !in_array(
            'exec',
            array_map('trim', explode(',', (string) ini_get('disable_functions'))),
            true
        );

        return Response::html($this->view->render('admin/settings/mail-queue', [
            'title'           => 'Cola de correo — ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'        => $settings,
            'auth_user'       => $authUser,
            'queue_stats'     => $queueStats,
            'queue_items'     => $queueItems,
            'cron_configured' => $cronConfigured,
            'cron_output'     => $cronOutput,
            'exec_available'  => $execAvailable,
            'csrf_token'      => \Middleware\CsrfMiddleware::token(),
        ], 'layouts/panel'));
    }

    public function mailQueueAction(Request $request): Response
    {
        header('Content-Type: application/json; charset=UTF-8');

        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            return new Response(json_encode(['ok' => false, 'message' => 'No autenticado.']), 401);
        }

        $token = trim((string) $request->post('_csrf_token', ''));
        if (!hash_equals(\Middleware\CsrfMiddleware::token(), $token)) {
            return new Response(json_encode(['ok' => false, 'message' => 'Token CSRF inválido.']), 403);
        }

        $action = trim((string) $request->post('action', ''));

        if ($action === 'preview_one') {
            $id = (int) $request->post('id', 0);
            if ($id <= 0) {
                return new Response(json_encode(['ok' => false, 'message' => 'ID inválido.']), 422);
            }

            $stmt = $this->db->prepare(
                "SELECT id, to_email, to_name, subject, body_html, body_text, status,
                        scheduled_at, sent_at, created_at
                 FROM email_queue
                 WHERE id = ?
                 LIMIT 1"
            );
            $stmt->execute([$id]);
            $item = $stmt->fetch();

            if (!$item) {
                return new Response(json_encode(['ok' => false, 'message' => 'Correo no encontrado.']), 404);
            }

            return new Response(json_encode([
                'ok' => true,
                'item' => $item,
            ]), 200);
        }

        if ($action === 'retry_all_failed') {
            $stmt = $this->db->prepare(
                "UPDATE email_queue
                 SET status = 'pending', attempts = 0, error_message = NULL, scheduled_at = NOW()
                 WHERE status = 'failed'"
            );
            $stmt->execute();
            $affected = $stmt->rowCount();
            return new Response(json_encode([
                'ok'      => true,
                'message' => "Se pusieron en cola {$affected} correo(s) fallido(s) para reenvío.",
                'count'   => $affected,
            ]), 200);
        }

        if ($action === 'retry_one') {
            $id = (int) $request->post('id', 0);
            if ($id <= 0) {
                return new Response(json_encode(['ok' => false, 'message' => 'ID inválido.']), 422);
            }
            $stmt = $this->db->prepare(
                "UPDATE email_queue
                 SET status = 'pending', attempts = 0, error_message = NULL, scheduled_at = NOW()
                 WHERE id = ? AND status IN ('failed', 'pending')"
            );
            $stmt->execute([$id]);
            $found = $stmt->rowCount() > 0;
            return new Response(json_encode([
                'ok'      => $found,
                'message' => $found
                    ? "Correo #{$id} reprogramado para reenvío."
                    : "No se encontró el correo #{$id} o ya fue enviado.",
            ]), 200);
        }

        if ($action === 'run_worker') {
            if (!function_exists('exec')) {
                return new Response(json_encode([
                    'ok'      => false,
                    'message' => 'exec() no está disponible en este servidor. Ejecuta el worker manualmente desde la consola.',
                ]), 200);
            }
            $workerPath = BASE_PATH . '/bin/mail_worker.php';
            $phpBin     = PHP_BINARY;
            $cmd        = escapeshellcmd($phpBin) . ' ' . escapeshellarg($workerPath) . ' 2>&1';
            $output     = [];
            $exitCode   = 0;
            exec($cmd, $output, $exitCode);
            $summary = implode("\n", array_map('htmlspecialchars', $output));
            return new Response(json_encode([
                'ok'      => $exitCode === 0,
                'message' => $summary !== '' ? strip_tags($summary) : 'Worker ejecutado sin salida.',
                'output'  => $output,
            ]), 200);
        }

        if ($action === 'clear_processed') {
            $stmt = $this->db->prepare("DELETE FROM email_queue WHERE status IN ('sent', 'failed')");
            $stmt->execute();
            $affected = $stmt->rowCount();
            return new Response(json_encode([
                'ok'      => true,
                'message' => "Se limpiaron {$affected} correo(s) enviados/fallidos de la cola.",
                'count'   => $affected,
            ]), 200);
        }

        return new Response(json_encode(['ok' => false, 'message' => 'Acción no reconocida.']), 422);
    }

    private function normalizeSettingValue(mixed $input, string $type): string
    {
        return match ($type) {
            'boolean' => filter_var($input, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
            'integer' => (string) (int) $input,
            'decimal' => number_format((float) $input, 2, '.', ''),
            'json' => $this->normalizeJsonSetting($input),
            default => trim((string) $input),
        };
    }

    private function normalizeJsonSetting(mixed $input): string
    {
        if (is_array($input)) {
            return (string) json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $json = trim((string) $input);
        if ($json === '') {
            return '[]';
        }

        json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return '[]';
    }

    private function ensureSmtpSettings(): void
    {
        $defaults = [
            'smtp_enabled' => ['true', 'boolean'],
            'smtp_host' => ['', 'string'],
            'smtp_port' => ['587', 'integer'],
            'smtp_username' => ['', 'string'],
            'smtp_password' => ['', 'string'],
            'smtp_encryption' => ['tls', 'string'],
            'smtp_from_address' => ['no-reply@biblioteca.com', 'string'],
            'smtp_from_name' => ['Biblioteca', 'string'],
            'smtp_timeout' => ['30', 'integer'],
            'app_url' => ['', 'string'],
        ];

        $stmt = $this->db->prepare(
            'INSERT INTO system_settings (`key`, `value`, `type`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `type` = VALUES(`type`)' 
        );

        foreach ($defaults as $key => [$value, $type]) {
            $stmt->execute([$key, $value, $type]);
        }
    }

    private function ensureCoreSettings(): void
    {
        $defaults = [
            // Library
            'library_name' => ['', 'string'],
            'library_slogan' => ['', 'string'],
            'library_address' => ['', 'string'],
            'library_phone' => ['', 'string'],
            'library_email' => ['', 'string'],
            'library_website' => ['', 'string'],
            'library_schedule' => ['', 'string'],
            'library_logo' => ['', 'string'],
            'library_favicon' => ['', 'string'],

            // Loans
            'loan_hours' => ['72', 'integer'],
            'loan_hours_extended' => ['120', 'integer'],
            'renewal_grace_hours' => ['2', 'integer'],
            'max_loans_per_user' => ['3', 'integer'],
            'max_renewals' => ['2', 'integer'],
            'reservation_hold_hours' => ['48', 'integer'],
            'new_acquisition_days' => ['30', 'integer'],

            // Fines
            'fine_per_hour' => ['0.05', 'decimal'],
            'max_fine_multiplier' => ['2.00', 'decimal'],
            'block_loans_with_fines' => ['true', 'boolean'],

            // Notifications
            'reminder_hours_before' => ['24', 'integer'],
            'news_on_home' => ['3', 'integer'],

            // System
            'timezone' => ['America/Guayaquil', 'string'],
            'locale' => ['es_EC', 'string'],
            'date_format' => ['d/m/Y H:i', 'string'],
            'currency_symbol' => ['$', 'string'],
            'carnet_prefix' => ['BIB', 'string'],
            'app_url' => ['', 'string'],
        ];

        $stmt = $this->db->prepare(
            'INSERT INTO system_settings (`key`, `value`, `type`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `type` = VALUES(`type`)'
        );

        foreach ($defaults as $key => [$value, $type]) {
            $stmt->execute([$key, $value, $type]);
        }
    }

    private function ensureAboutSettings(): void
    {
        $defaults = [
            'about_hero_badge' => ['Quiénes somos', 'string'],
            'about_hero_title' => ['', 'string'],
            'about_hero_subtitle' => ['', 'string'],
            'about_mission_title' => ['Misión', 'string'],
            'about_mission_text' => ['Promover el acceso libre al conocimiento y fomentar el hábito lector en nuestra comunidad, ofreciendo un espacio acogedor, inclusivo y actualizado para todas las edades.', 'string'],
            'about_vision_title' => ['Visión', 'string'],
            'about_vision_text' => ['Ser el centro cultural de referencia de la región, reconocida por la excelencia de sus servicios, la riqueza de su colección y su compromiso con la educación permanente.', 'string'],
            'about_values_title' => ['Valores', 'string'],
            'about_values_items' => ["Acceso libre e igualitario\nRespeto e inclusión\nCompromiso con la educación\nInnovación y mejora continua\nTransparencia y servicio", 'string'],
            'about_history_badge' => ['Nuestra historia', 'string'],
            'about_history_title' => ['Más de una década al servicio de la comunidad', 'string'],
            'about_history_text' => ["Fundada con el propósito de democratizar el acceso al conocimiento, nuestra biblioteca ha sido desde sus inicios un punto de encuentro para estudiantes, investigadores, familias y amantes de la lectura.\n\nA lo largo de los años hemos ampliado nuestra colección, modernizado nuestros espacios y adaptado nuestros servicios a las nuevas necesidades digitales, sin perder jamás la calidez del trato humano que nos caracteriza.\n\nHoy contamos con un amplio catálogo físico y digital, préstamos a domicilio, salas de estudio y un equipo de bibliotecarios comprometidos con guiar a cada visitante en su búsqueda del saber.", 'string'],
            'about_history_p1' => ['Fundada con el propósito de democratizar el acceso al conocimiento, nuestra biblioteca ha sido desde sus inicios un punto de encuentro para estudiantes, investigadores, familias y amantes de la lectura.', 'string'],
            'about_history_p2' => ['A lo largo de los años hemos ampliado nuestra colección, modernizado nuestros espacios y adaptado nuestros servicios a las nuevas necesidades digitales, sin perder jamás la calidez del trato humano que nos caracteriza.', 'string'],
            'about_history_p3' => ['Hoy contamos con un amplio catálogo físico y digital, préstamos a domicilio, salas de estudio y un equipo de bibliotecarios comprometidos con guiar a cada visitante en su búsqueda del saber.', 'string'],
            'about_timeline_items' => ["2010|Apertura de la biblioteca con una colección inicial de 2 000 volúmenes.\n2014|Inauguración de la sala infantil y programa de animación lectora.\n2017|Lanzamiento del catálogo en línea y las primeras suscripciones digitales.\n2020|Adaptación a servicios remotos y expansión del fondo digital durante la pandemia.\n2023|Renovación de instalaciones y apertura de sala de co-trabajo.\n2025|Más de 10 000 socios activos y 50 000 préstamos anuales.", 'string'],
            'about_contact_badge' => ['Encuéntranos', 'string'],
            'about_contact_title' => ['Información de contacto', 'string'],
        ];

        $stmt = $this->db->prepare(
            'INSERT INTO system_settings (`key`, `value`, `type`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `type` = VALUES(`type`)'
        );

        foreach ($defaults as $key => [$value, $type]) {
            $stmt->execute([$key, $value, $type]);
        }
    }

    private function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'categoria';
    }

    private function uniqueCategorySlug(string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = $baseSlug;
        $suffix = 2;

        do {
            if ($ignoreId !== null) {
                $stmt = $this->db->prepare('SELECT COUNT(*) FROM categories WHERE slug = ? AND id != ?');
                $stmt->execute([$slug, $ignoreId]);
            } else {
                $stmt = $this->db->prepare('SELECT COUNT(*) FROM categories WHERE slug = ?');
                $stmt->execute([$slug]);
            }

            $exists = (int) $stmt->fetchColumn() > 0;
            if ($exists) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }
        } while ($exists);

        return $slug;
    }

    private function storeSettingImage(array $file, string $key): ?string
    {
        $tmpName = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmpName)) {
            return null;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 2 * 1024 * 1024) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($tmpName);
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon'];
        if (!in_array($mime, $allowed, true)) {
            return null;
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            'image/x-icon', 'image/vnd.microsoft.icon' => 'ico',
            default => null,
        };
        if ($ext === null) {
            return null;
        }

        $directory = BASE_PATH . '/public/uploads/settings';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return null;
        }

        try {
            $entropy = bin2hex(random_bytes(4));
        } catch (\Throwable $e) {
            $entropy = (string) mt_rand(100000, 999999);
        }

        $filename    = $key . '_' . date('Ymd_His') . '_' . $entropy . '.' . $ext;
        $destination = $directory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            return null;
        }

        return '/uploads/settings/' . $filename;
    }
}
