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
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $teacherId = (int) $authUser['id'];
        $settings  = $this->panelSettings();

        // Active groups belonging to this teacher
        $groupsStmt = $this->db->prepare(
            "SELECT id, name, school_year FROM teacher_groups
             WHERE teacher_id = ? AND is_active = 1
             ORDER BY name ASC"
        );
        $groupsStmt->execute([$teacherId]);
        $groups = $groupsStmt->fetchAll();

        // Physical and digital active resources
        $resources = $this->db->query(
            "SELECT id, title, authors, isbn
             FROM resources
             WHERE is_active = 1
             ORDER BY title ASC"
        )->fetchAll();

        return Response::html($this->view->render('teacher/assignments/create', [
            'title'     => 'Nueva asignación - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'  => $settings,
            'auth_user' => $authUser,
            'groups'    => $groups,
            'resources' => $resources,
            'csrf'      => (string) Session::get('_csrf_token', ''),
            'old'       => [],
            'errors'    => [],
        ], 'layouts/panel'));
    }

    public function store(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $teacherId = (int) $authUser['id'];

        // CSRF
        $csrf = (string) $request->post('_csrf_token', '');
        if (!hash_equals((string) Session::get('_csrf_token', ''), $csrf)) {
            Session::flash('error', 'Token de seguridad inválido. Intenta de nuevo.');
            return Response::redirect(BASE_URL . '/teacher/assignments/create');
        }

        $title      = trim((string) $request->post('title', ''));
        $groupId    = (int) $request->post('group_id', 0);
        $resourceId = (int) $request->post('resource_id', 0);
        $dueDate    = trim((string) $request->post('due_date', ''));
        $description = trim((string) $request->post('description', ''));

        $errors = [];
        if ($title === '')    { $errors[] = 'El título es obligatorio.'; }
        if ($groupId <= 0)    { $errors[] = 'Debes seleccionar un grupo.'; }
        if ($resourceId <= 0) { $errors[] = 'Debes seleccionar un recurso.'; }
        if ($dueDate === '')  { $errors[] = 'La fecha límite es obligatoria.'; }
        if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $errors[] = 'Formato de fecha inválido.';
        }

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            return Response::redirect(BASE_URL . '/teacher/assignments/create');
        }

        // Verify the group belongs to this teacher
        $groupStmt = $this->db->prepare(
            "SELECT id, name FROM teacher_groups
             WHERE id = ? AND teacher_id = ? AND is_active = 1 LIMIT 1"
        );
        $groupStmt->execute([$groupId, $teacherId]);
        $group = $groupStmt->fetch();

        if (!$group) {
            Session::flash('error', 'El grupo seleccionado no existe o no te pertenece.');
            return Response::redirect(BASE_URL . '/teacher/assignments/create');
        }

        // Verify the resource exists
        $resStmt = $this->db->prepare(
            "SELECT id, title FROM resources WHERE id = ? AND is_active = 1 LIMIT 1"
        );
        $resStmt->execute([$resourceId]);
        $resource = $resStmt->fetch();

        if (!$resource) {
            Session::flash('error', 'El recurso seleccionado no existe o no está activo.');
            return Response::redirect(BASE_URL . '/teacher/assignments/create');
        }

        // Load students of the group
        $studentsStmt = $this->db->prepare(
            "SELECT u.id, u.name, u.email
             FROM teacher_group_students tgs
             JOIN users u ON u.id = tgs.student_id
             WHERE tgs.group_id = ? AND u.status = 'active'"
        );
        $studentsStmt->execute([$groupId]);
        $students = $studentsStmt->fetchAll();

        $this->db->beginTransaction();
        try {
            $insertAssignment = $this->db->prepare(
                "INSERT INTO reading_assignments
                 (group_id, resource_id, title, description, due_date, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())"
            );
            $insertAssignment->execute([
                $groupId,
                $resourceId,
                $title,
                $description !== '' ? $description : null,
                $dueDate,
            ]);
            $assignmentId = (int) $this->db->lastInsertId();

            if (!empty($students)) {
                $insertStudent = $this->db->prepare(
                    "INSERT INTO reading_assignment_students
                     (assignment_id, student_id, status, updated_at)
                     VALUES (?, ?, 'pending', NOW())"
                );
                foreach ($students as $student) {
                    $insertStudent->execute([$assignmentId, (int) $student['id']]);
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        // Enqueue notification emails to students
        if (!empty($students)) {
            try {
                $mailQueue = new \Services\MailQueueService();
                $assignmentData = [
                    'id'         => $assignmentId,
                    'title'      => $title,
                    'book_title' => (string) $resource['title'],
                    'due_date'   => $dueDate,
                ];
                foreach ($students as $student) {
                    if (trim((string) ($student['email'] ?? '')) !== '') {
                        $mailQueue->enqueueAssignmentReminder($student, $assignmentData);
                    }
                }
            } catch (\Throwable) {
                // Mail failure is non-blocking
            }
        }

        Session::flash('success', 'Asignación creada y notificaciones enviadas a ' . count($students) . ' estudiante(s).');
        return Response::redirect(BASE_URL . '/teacher/assignments/' . $assignmentId);
    }

    public function show(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $assignmentId = (int) $id;
        $teacherId    = (int) $authUser['id'];

        if ($assignmentId <= 0) {
            Session::flash('error', 'Asignación no válida.');
            return Response::redirect(BASE_URL . '/teacher/assignments');
        }

        // Load assignment — must belong to one of this teacher's groups
        $assignStmt = $this->db->prepare(
            "SELECT
                ra.id,
                ra.title,
                ra.description,
                ra.due_date,
                ra.is_active,
                ra.created_at,
                tg.id   AS group_id,
                tg.name AS group_name,
                tg.school_year,
                b.id    AS resource_id,
                b.title AS resource_title,
                b.authors AS resource_authors,
                b.isbn  AS resource_isbn,
                COUNT(ras.student_id) AS total_students,
                SUM(CASE WHEN ras.status = 'pending'     THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN ras.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count,
                SUM(CASE WHEN ras.status = 'completed'   THEN 1 ELSE 0 END) AS completed_count
             FROM reading_assignments ra
             JOIN teacher_groups tg ON tg.id = ra.group_id
             JOIN resources b ON b.id = ra.resource_id
             LEFT JOIN reading_assignment_students ras ON ras.assignment_id = ra.id
             WHERE ra.id = ? AND tg.teacher_id = ?
             GROUP BY
                ra.id, ra.title, ra.description, ra.due_date, ra.is_active, ra.created_at,
                tg.id, tg.name, tg.school_year,
                b.id, b.title, b.authors, b.isbn"
        );
        $assignStmt->execute([$assignmentId, $teacherId]);
        $assignment = $assignStmt->fetch();

        if (!$assignment) {
            Session::flash('error', 'Asignación no encontrada o no tienes acceso.');
            return Response::redirect(BASE_URL . '/teacher/assignments');
        }

        // Load students with their progress
        $studentsStmt = $this->db->prepare(
            "SELECT
                u.id,
                u.name,
                u.email,
                u.user_number,
                ras.status,
                ras.completed_at,
                ras.updated_at,
                ras.notes,
                (SELECT COUNT(*) FROM loans l
                 WHERE l.user_id = u.id
                   AND l.resource_id = :resource_id
                   AND l.status IN ('active','returned','overdue')) AS has_loan
             FROM reading_assignment_students ras
             JOIN users u ON u.id = ras.student_id
             WHERE ras.assignment_id = :assignment_id
             ORDER BY
                CASE ras.status
                    WHEN 'pending'     THEN 1
                    WHEN 'in_progress' THEN 2
                    WHEN 'completed'   THEN 3
                    ELSE 4
                END,
                u.name ASC"
        );
        $studentsStmt->execute([
            ':resource_id'   => (int) $assignment['resource_id'],
            ':assignment_id' => $assignmentId,
        ]);
        $students = $studentsStmt->fetchAll();

        $settings = $this->panelSettings();

        return Response::html($this->view->render('teacher/assignments/show', [
            'title'      => $assignment['title'] . ' - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'   => $settings,
            'auth_user'  => $authUser,
            'assignment' => $assignment,
            'students'   => $students,
        ], 'layouts/panel'));
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
