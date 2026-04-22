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
        ], 'layouts/panel'));
    }

    public function settings(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $this->ensureSmtpSettings();
        $this->ensureAboutSettings();

        $settings = $this->panelSettings();
        $allSettings = $this->db
            ->query('SELECT `key`, value, type FROM system_settings ORDER BY `key`')
            ->fetchAll();

        $activeTab = trim((string) $request->get('tab', ''));

        return Response::html($this->view->render('admin/settings/index', [
            'title' => 'Configuracion - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'all_settings' => $allSettings,
            'csrf' => \Middleware\CsrfMiddleware::token(),
            'active_tab' => $activeTab,
        ], 'layouts/panel'));
    }

    public function updateSettings(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

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

        $requestedTab = trim((string) $request->post('active_tab', ''));
        $tabQuery = static fn(string $tab): string => $tab !== '' ? ('?tab=' . rawurlencode($tab)) : '';

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
                return Response::redirect(BASE_URL . '/admin/settings' . $tabQuery($sectionKey));
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
            return Response::redirect(BASE_URL . '/admin/settings' . $tabQuery($sectionKey));
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
                return Response::redirect(BASE_URL . '/admin/settings' . $tabQuery($requestedTab));
            }

            Session::flash('error', 'No se encontro la configuracion solicitada.');
            return Response::redirect(BASE_URL . '/admin/settings' . $tabQuery($requestedTab));
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
        return Response::redirect(BASE_URL . '/admin/settings' . $tabQuery($requestedTab));
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
