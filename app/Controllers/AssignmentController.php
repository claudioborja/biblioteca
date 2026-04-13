<?php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;

final class AssignmentController
{
    private \PDO $db;
    private View $view;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->view = new View(BASE_PATH . '/views');
    }

    public function index(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $teacherId = (int) $authUser['id'];
        $settings = $this->panelSettings();

        $summary = [
            'active_assignments' => (int) $this->scalar(
                'SELECT COUNT(*)
                 FROM reading_assignments ra
                 JOIN teacher_groups tg ON tg.id = ra.group_id
                 WHERE tg.teacher_id = ? AND ra.is_active = 1',
                [$teacherId]
            ),
            'groups' => (int) $this->scalar(
                'SELECT COUNT(*) FROM teacher_groups WHERE teacher_id = ? AND is_active = 1',
                [$teacherId]
            ),
            'assigned_students' => (int) $this->scalar(
                'SELECT COUNT(DISTINCT ras.student_id)
                 FROM reading_assignment_students ras
                 JOIN reading_assignments ra ON ra.id = ras.assignment_id
                 JOIN teacher_groups tg ON tg.id = ra.group_id
                 WHERE tg.teacher_id = ? AND ra.is_active = 1',
                [$teacherId]
            ),
            'completed_submissions' => (int) $this->scalar(
                "SELECT COUNT(*)
                 FROM reading_assignment_students ras
                 JOIN reading_assignments ra ON ra.id = ras.assignment_id
                 JOIN teacher_groups tg ON tg.id = ra.group_id
                 WHERE tg.teacher_id = ? AND ras.status = 'completed' AND ra.is_active = 1",
                [$teacherId]
            ),
        ];

        $stmt = $this->db->prepare(
            "SELECT
                ra.id,
                ra.title,
                ra.description,
                ra.due_date,
                ra.is_active,
                tg.id AS group_id,
                tg.name AS group_name,
                tg.school_year,
                b.id AS resource_id,
                b.title AS book_title,
                COUNT(ras.student_id) AS assigned_students,
                SUM(CASE WHEN ras.status = 'pending' THEN 1 ELSE 0 END) AS pending_students,
                SUM(CASE WHEN ras.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_students,
                SUM(CASE WHEN ras.status = 'completed' THEN 1 ELSE 0 END) AS completed_students
             FROM reading_assignments ra
             JOIN teacher_groups tg ON tg.id = ra.group_id
             JOIN resources b ON b.id = ra.resource_id
             LEFT JOIN reading_assignment_students ras ON ras.assignment_id = ra.id
             WHERE tg.teacher_id = ? AND ra.is_active = 1
             GROUP BY
                ra.id, ra.title, ra.description, ra.due_date, ra.is_active,
                tg.id, tg.name, tg.school_year,
                b.id, b.title
             ORDER BY ra.due_date ASC, ra.created_at DESC"
        );
        $stmt->execute([$teacherId]);
        $assignments = $stmt->fetchAll();

        return Response::html($this->view->render('teacher/assignments/index', [
            'title' => 'Asignaciones - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'summary' => $summary,
            'assignments' => $assignments,
        ], 'layouts/panel'));
    }

    public function myAssignments(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $userId = (int) $authUser['id'];

        $stmt = $this->db->prepare(
            "SELECT
                ra.id,
                ra.title,
                ra.description,
                ra.due_date,
                ra.is_active,
                ras.status,
                ras.completed_at,
                ras.updated_at,
                tg.name AS group_name,
                b.title AS resource_title,
                b.authors AS resource_authors
             FROM reading_assignment_students ras
             JOIN reading_assignments ra ON ra.id = ras.assignment_id
             JOIN teacher_groups tg ON tg.id = ra.group_id
             JOIN resources b ON b.id = ra.resource_id
             WHERE ras.student_id = ?
             ORDER BY
                CASE ras.status
                    WHEN 'pending' THEN 1
                    WHEN 'in_progress' THEN 2
                    WHEN 'completed' THEN 3
                    ELSE 4
                END,
                ra.due_date ASC,
                ra.created_at DESC"
        );
        $stmt->execute([$userId]);
        $assignments = $stmt->fetchAll();

        $summary = [
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'total' => count($assignments),
        ];

        foreach ($assignments as $assignment) {
            $status = (string) ($assignment['status'] ?? '');
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        $settings = $this->panelSettings();

        return Response::html($this->view->render('account/assignments', [
            'title' => 'Mis asignaciones - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'assignments' => $assignments,
            'summary' => $summary,
        ], 'layouts/panel'));
    }

    public function create(Request $request): Response
    {
        Session::flash('info', 'La creación de asignaciones estará disponible próximamente.');
        return Response::redirect(BASE_URL . '/teacher/assignments');
    }

    public function store(Request $request): Response
    {
        Session::flash('info', 'La creación de asignaciones estará disponible próximamente.');
        return Response::redirect(BASE_URL . '/teacher/assignments');
    }

    public function show(Request $request, string $id = ''): Response
    {
        Session::flash('info', 'El detalle de asignación estará disponible próximamente.');
        return Response::redirect(BASE_URL . '/teacher/assignments');
    }

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
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'user_number' => $user['user_number'],
            'last_login_at' => $user['last_login_at'],
            'created_at' => $user['created_at'],
            'status' => $user['status'],
        ];
    }
}
