<?php
// app/Controllers/SuggestionController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;
use Services\MailQueueService;

final class SuggestionController
{
    private \PDO $db;
    private View $view;

    public function __construct()
    {
        $this->db   = Database::connect();
        $this->view = new View(BASE_PATH . '/views');
    }

    // -------------------------------------------------------------------------
    // Teacher: list own suggestions
    // -------------------------------------------------------------------------
    public function index(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();

        $stmt = $this->db->prepare(
            "SELECT s.*, u.name AS reviewer_name
             FROM resource_suggestions s
             LEFT JOIN users u ON u.id = s.reviewed_by
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC"
        );
        $stmt->execute([(int) $authUser['id']]);
        $suggestions = $stmt->fetchAll();

        return Response::html($this->view->render('teacher/suggestions/index', [
            'title'       => 'Mis sugerencias – ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'    => $settings,
            'auth_user'   => $authUser,
            'suggestions' => $suggestions,
        ], 'layouts/panel'));
    }

    // -------------------------------------------------------------------------
    // Teacher: show create form
    // -------------------------------------------------------------------------
    public function create(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();

        return Response::html($this->view->render('teacher/suggestions/create', [
            'title'     => 'Nueva sugerencia – ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'  => $settings,
            'auth_user' => $authUser,
        ], 'layouts/panel'));
    }

    // -------------------------------------------------------------------------
    // Teacher: store new suggestion
    // -------------------------------------------------------------------------
    public function store(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        // CSRF
        if (!hash_equals(Session::get('_csrf_token', ''), $request->post('_csrf_token', ''))) {
            Session::flash('error', 'Token de seguridad inválido. Intenta de nuevo.');
            return Response::redirect(BASE_URL . '/teacher/suggestions/create');
        }

        $title     = trim($request->post('title', ''));
        $author    = trim($request->post('author', ''));
        $isbn      = trim($request->post('isbn', ''));
        $publisher = trim($request->post('publisher', ''));
        $reason    = trim($request->post('reason', ''));

        if ($title === '') {
            Session::flash('error', 'El título del recurso es obligatorio.');
            return Response::redirect(BASE_URL . '/teacher/suggestions/create');
        }

        // Normalise ISBN (remove dashes/spaces)
        $isbn = preg_replace('/[\s\-]/', '', $isbn) ?? '';

        $stmt = $this->db->prepare(
            "INSERT INTO resource_suggestions (user_id, title, author, isbn, publisher, reason, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())"
        );
        $stmt->execute([
            (int) $authUser['id'],
            $title,
            $author !== '' ? $author : null,
            $isbn !== '' ? $isbn : null,
            $publisher !== '' ? $publisher : null,
            $reason !== '' ? $reason : null,
        ]);

        $suggestionId = (int) $this->db->lastInsertId();

        // Notify admins/librarians
        try {
            $adminStmt = $this->db->query(
                "SELECT email, name FROM users WHERE role IN ('admin','librarian') AND status = 'active'"
            );
            $admins = $adminStmt->fetchAll();

            $mail = new MailQueueService($this->db);
            $adminUrl = BASE_URL . '/admin/suggestions';

            foreach ($admins as $admin) {
                $bodyHtml = "<p>El docente <strong>" . htmlspecialchars($authUser['name'], ENT_QUOTES, 'UTF-8') . "</strong> "
                    . "ha enviado una nueva sugerencia de recurso:</p>"
                    . "<ul>"
                    . "<li><strong>Título:</strong> " . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</li>"
                    . ($author !== '' ? "<li><strong>Autor:</strong> " . htmlspecialchars($author, ENT_QUOTES, 'UTF-8') . "</li>" : '')
                    . ($isbn !== '' ? "<li><strong>ISBN:</strong> " . htmlspecialchars($isbn, ENT_QUOTES, 'UTF-8') . "</li>" : '')
                    . "</ul>"
                    . "<p><a href=\"{$adminUrl}\">Revisar sugerencias pendientes</a></p>";

                $mail->enqueue(
                    $admin['email'],
                    $admin['name'],
                    'Nueva sugerencia de recurso: ' . $title,
                    $bodyHtml
                );
            }
        } catch (\Throwable $e) {
            // Mail failure is non-blocking
        }

        Session::flash('success', 'Tu sugerencia ha sido enviada. Te notificaremos cuando sea revisada.');
        return Response::redirect(BASE_URL . '/teacher/suggestions');
    }

    // -------------------------------------------------------------------------
    // Admin: list all suggestions with optional status filter
    // -------------------------------------------------------------------------
    public function adminIndex(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();

        $status  = $request->get('status', '');
        $allowed = ['pending', 'approved', 'rejected', 'acquired', ''];
        if (!in_array($status, $allowed, true)) {
            $status = '';
        }

        $sql    = "SELECT s.*, u.name AS teacher_name, u.email AS teacher_email,
                          r.name AS reviewer_name
                   FROM resource_suggestions s
                   JOIN users u ON u.id = s.user_id
                   LEFT JOIN users r ON r.id = s.reviewed_by";
        $params = [];

        if ($status !== '') {
            $sql .= " WHERE s.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY FIELD(s.status,'pending','approved','acquired','rejected'), s.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $suggestions = $stmt->fetchAll();

        $counts = $this->db
            ->query("SELECT status, COUNT(*) AS n FROM resource_suggestions GROUP BY status")
            ->fetchAll(\PDO::FETCH_KEY_PAIR);

        return Response::html($this->view->render('admin/suggestions/index', [
            'title'       => 'Sugerencias de recursos – ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'    => $settings,
            'auth_user'   => $authUser,
            'suggestions' => $suggestions,
            'filter'      => $status,
            'counts'      => $counts,
        ], 'layouts/panel'));
    }

    // -------------------------------------------------------------------------
    // Admin: approve suggestion
    // -------------------------------------------------------------------------
    public function approve(Request $request, int $id): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        if (!hash_equals(Session::get('_csrf_token', ''), $request->post('_csrf_token', ''))) {
            Session::flash('error', 'Token de seguridad inválido.');
            return Response::redirect(BASE_URL . '/admin/suggestions');
        }

        $adminNotes = trim($request->post('admin_notes', ''));

        $stmt = $this->db->prepare(
            "SELECT s.*, u.name AS teacher_name, u.email AS teacher_email
             FROM resource_suggestions s
             JOIN users u ON u.id = s.user_id
             WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        $suggestion = $stmt->fetch();

        if (!$suggestion) {
            Session::flash('error', 'Sugerencia no encontrada.');
            return Response::redirect(BASE_URL . '/admin/suggestions');
        }

        if ($suggestion['status'] !== 'pending') {
            Session::flash('error', 'Esta sugerencia ya fue procesada.');
            return Response::redirect(BASE_URL . '/admin/suggestions');
        }

        $upd = $this->db->prepare(
            "UPDATE resource_suggestions
             SET status = 'approved', admin_notes = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
             WHERE id = ?"
        );
        $upd->execute([
            $adminNotes !== '' ? $adminNotes : null,
            (int) $authUser['id'],
            $id,
        ]);

        // Notify teacher
        try {
            $mail     = new MailQueueService($this->db);
            $bodyHtml = "<p>Tu sugerencia <strong>"
                . htmlspecialchars((string) $suggestion['title'], ENT_QUOTES, 'UTF-8')
                . "</strong> ha sido <strong>aprobada</strong>.</p>"
                . ($adminNotes !== '' ? "<p><em>Nota del bibliotecario:</em> " . htmlspecialchars($adminNotes, ENT_QUOTES, 'UTF-8') . "</p>" : '')
                . "<p>El recurso será gestionado para su adquisición.</p>";

            $mail->enqueue(
                (string) $suggestion['teacher_email'],
                (string) $suggestion['teacher_name'],
                'Tu sugerencia ha sido aprobada: ' . $suggestion['title'],
                $bodyHtml
            );
        } catch (\Throwable $e) {
            // Mail failure is non-blocking
        }

        Session::flash('success', 'Sugerencia aprobada correctamente.');
        return Response::redirect(BASE_URL . '/admin/suggestions');
    }

    // -------------------------------------------------------------------------
    // Admin: mark suggestion as acquired
    // -------------------------------------------------------------------------
    public function markAcquired(Request $request, int $id): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        if (!hash_equals(Session::get('_csrf_token', ''), $request->post('_csrf_token', ''))) {
            Session::flash('error', 'Token de seguridad inválido.');
            return Response::redirect(BASE_URL . '/admin/suggestions');
        }

        $adminNotes = trim($request->post('admin_notes', ''));

        $stmt = $this->db->prepare(
            "SELECT s.*, u.name AS teacher_name, u.email AS teacher_email
             FROM resource_suggestions s
             JOIN users u ON u.id = s.user_id
             WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        $suggestion = $stmt->fetch();

        if (!$suggestion) {
            Session::flash('error', 'Sugerencia no encontrada.');
            return Response::redirect(BASE_URL . '/admin/suggestions');
        }

        if ($suggestion['status'] !== 'approved') {
            Session::flash('error', 'Solo se pueden marcar como adquiridas las sugerencias aprobadas.');
            return Response::redirect(BASE_URL . '/admin/suggestions');
        }

        $upd = $this->db->prepare(
            "UPDATE resource_suggestions
             SET status = 'acquired', admin_notes = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
             WHERE id = ?"
        );
        $upd->execute([
            $adminNotes !== '' ? $adminNotes : null,
            (int) $authUser['id'],
            $id,
        ]);

        // Notify the user who made the suggestion
        try {
            $mail     = new MailQueueService($this->db);
            $bodyHtml = "<p>¡Buenas noticias! Tu sugerencia <strong>"
                . htmlspecialchars((string) $suggestion['title'], ENT_QUOTES, 'UTF-8')
                . "</strong> ya ha sido <strong>adquirida</strong> y está disponible en el catálogo de la biblioteca.</p>"
                . ($adminNotes !== '' ? "<p><em>Nota:</em> " . htmlspecialchars($adminNotes, ENT_QUOTES, 'UTF-8') . "</p>" : '')
                . "<p>¡Gracias por tu sugerencia!</p>";

            $mail->enqueue(
                (string) $suggestion['teacher_email'],
                (string) $suggestion['teacher_name'],
                'Tu sugerencia ya está en el catálogo: ' . $suggestion['title'],
                $bodyHtml
            );
        } catch (\Throwable $e) {
            // Mail failure is non-blocking
        }

        Session::flash('success', 'Sugerencia marcada como adquirida. El solicitante ha sido notificado.');
        return Response::redirect(BASE_URL . '/admin/suggestions');
    }

    // -------------------------------------------------------------------------
    // User (account): list own suggestions
    // -------------------------------------------------------------------------
    public function userIndex(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();

        $stmt = $this->db->prepare(
            "SELECT s.*, u.name AS reviewer_name
             FROM resource_suggestions s
             LEFT JOIN users u ON u.id = s.reviewed_by
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC"
        );
        $stmt->execute([(int) $authUser['id']]);
        $suggestions = $stmt->fetchAll();

        return Response::html($this->view->render('account/suggestions', [
            'title'       => 'Mis sugerencias – ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'    => $settings,
            'auth_user'   => $authUser,
            'suggestions' => $suggestions,
        ], 'layouts/panel'));
    }

    // -------------------------------------------------------------------------
    // User (account): store new suggestion
    // -------------------------------------------------------------------------
    public function userStore(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        if (!hash_equals(Session::get('_csrf_token', ''), $request->post('_csrf_token', ''))) {
            Session::flash('error', 'Token de seguridad inválido. Intenta de nuevo.');
            return Response::redirect(BASE_URL . '/account/suggestions');
        }

        $title     = trim($request->post('title', ''));
        $author    = trim($request->post('author', ''));
        $isbn      = trim($request->post('isbn', ''));
        $publisher = trim($request->post('publisher', ''));
        $reason    = trim($request->post('reason', ''));

        if ($title === '') {
            Session::flash('error', 'El título del recurso es obligatorio.');
            Session::flash('old', compact('title', 'author', 'isbn', 'publisher', 'reason'));
            return Response::redirect(BASE_URL . '/account/suggestions');
        }

        $isbn = preg_replace('/[\s\-]/', '', $isbn) ?? '';

        $stmt = $this->db->prepare(
            "INSERT INTO resource_suggestions (user_id, title, author, isbn, publisher, reason, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())"
        );
        $stmt->execute([
            (int) $authUser['id'],
            $title,
            $author !== '' ? $author : null,
            $isbn !== '' ? $isbn : null,
            $publisher !== '' ? $publisher : null,
            $reason !== '' ? $reason : null,
        ]);

        // Notify admins/librarians
        try {
            $adminStmt = $this->db->query(
                "SELECT email, name FROM users WHERE role IN ('admin','librarian') AND status = 'active'"
            );
            $admins  = $adminStmt->fetchAll();
            $mail    = new MailQueueService($this->db);
            $adminUrl = BASE_URL . '/admin/suggestions';

            foreach ($admins as $admin) {
                $bodyHtml = "<p>El socio <strong>" . htmlspecialchars($authUser['name'], ENT_QUOTES, 'UTF-8') . "</strong> "
                    . "ha enviado una nueva sugerencia de recurso:</p>"
                    . "<ul>"
                    . "<li><strong>Título:</strong> " . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</li>"
                    . ($author !== '' ? "<li><strong>Autor:</strong> " . htmlspecialchars($author, ENT_QUOTES, 'UTF-8') . "</li>" : '')
                    . ($isbn !== '' ? "<li><strong>ISBN:</strong> " . htmlspecialchars($isbn, ENT_QUOTES, 'UTF-8') . "</li>" : '')
                    . "</ul>"
                    . "<p><a href=\"{$adminUrl}\">Revisar sugerencias pendientes</a></p>";

                $mail->enqueue(
                    $admin['email'],
                    $admin['name'],
                    'Nueva sugerencia de recurso: ' . $title,
                    $bodyHtml
                );
            }
        } catch (\Throwable $e) {
            // Mail failure is non-blocking
        }

        Session::flash('success', 'Tu sugerencia ha sido enviada. Te notificaremos cuando sea revisada.');
        return Response::redirect(BASE_URL . '/account/suggestions');
    }

    // -------------------------------------------------------------------------
    // Admin: reject suggestion
    // -------------------------------------------------------------------------
    public function reject(Request $request, int $id): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        if (!hash_equals(Session::get('_csrf_token', ''), $request->post('_csrf_token', ''))) {
            Session::flash('error', 'Token de seguridad inválido.');
            return Response::redirect(BASE_URL . '/admin/suggestions');
        }

        $adminNotes = trim($request->post('admin_notes', ''));

        if ($adminNotes === '') {
            Session::flash('error', 'Debes indicar el motivo del rechazo.');
            return Response::redirect(BASE_URL . '/admin/suggestions');
        }

        $stmt = $this->db->prepare(
            "SELECT s.*, u.name AS teacher_name, u.email AS teacher_email
             FROM resource_suggestions s
             JOIN users u ON u.id = s.user_id
             WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        $suggestion = $stmt->fetch();

        if (!$suggestion) {
            Session::flash('error', 'Sugerencia no encontrada.');
            return Response::redirect(BASE_URL . '/admin/suggestions');
        }

        if ($suggestion['status'] !== 'pending') {
            Session::flash('error', 'Esta sugerencia ya fue procesada.');
            return Response::redirect(BASE_URL . '/admin/suggestions');
        }

        $upd = $this->db->prepare(
            "UPDATE resource_suggestions
             SET status = 'rejected', admin_notes = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
             WHERE id = ?"
        );
        $upd->execute([
            $adminNotes,
            (int) $authUser['id'],
            $id,
        ]);

        // Notify teacher
        try {
            $mail     = new MailQueueService($this->db);
            $bodyHtml = "<p>Tu sugerencia <strong>"
                . htmlspecialchars((string) $suggestion['title'], ENT_QUOTES, 'UTF-8')
                . "</strong> no ha podido ser aprobada en este momento.</p>"
                . "<p><em>Motivo:</em> " . htmlspecialchars($adminNotes, ENT_QUOTES, 'UTF-8') . "</p>"
                . "<p>Si tienes dudas, puedes contactar con tu bibliotecario.</p>";

            $mail->enqueue(
                (string) $suggestion['teacher_email'],
                (string) $suggestion['teacher_name'],
                'Actualización sobre tu sugerencia: ' . $suggestion['title'],
                $bodyHtml
            );
        } catch (\Throwable $e) {
            // Mail failure is non-blocking
        }

        Session::flash('success', 'Sugerencia rechazada.');
        return Response::redirect(BASE_URL . '/admin/suggestions');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------
    private function scalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function panelSettings(): array
    {
        return $this->db
            ->query("SELECT `key`, value FROM system_settings WHERE `key` IN ('library_name','library_logo','library_favicon')")
            ->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    private function resolveAuthUser(): ?array
    {
        $userId = (int) Session::get('auth.user_id');
        if ($userId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        return [
            'id'          => $user['id'],
            'name'        => $user['name'],
            'email'       => $user['email'],
            'role'        => $user['role'],
            'user_number' => $user['user_number'],
            'status'      => $user['status'],
        ];
    }
}
