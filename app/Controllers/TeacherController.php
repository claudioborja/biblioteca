<?php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;

final class TeacherController
{
    private \PDO $db;
    private View $view;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->view = new View(BASE_PATH . '/views');
    }

    public function dashboard(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();
        $teacherId = (int) $authUser['id'];

        $stats = [
            'groups' => (int) $this->scalar(
                'SELECT COUNT(*) FROM teacher_groups WHERE teacher_id = ? AND is_active = 1',
                [$teacherId]
            ),
            'students' => (int) $this->scalar(
                'SELECT COUNT(DISTINCT tgs.student_id)
                 FROM teacher_group_students tgs
                 JOIN teacher_groups tg ON tg.id = tgs.group_id
                 WHERE tg.teacher_id = ? AND tg.is_active = 1',
                [$teacherId]
            ),
            'assignments' => (int) $this->scalar(
                'SELECT COUNT(*)
                 FROM reading_assignments ra
                 JOIN teacher_groups tg ON tg.id = ra.group_id
                 WHERE tg.teacher_id = ? AND ra.is_active = 1',
                [$teacherId]
            ),
            'pending_reviews' => (int) $this->scalar(
                "SELECT COUNT(*)
                 FROM reading_assignment_students ras
                 JOIN reading_assignments ra ON ra.id = ras.assignment_id
                 JOIN teacher_groups tg ON tg.id = ra.group_id
                 WHERE tg.teacher_id = ? AND ras.status IN ('pending', 'in_progress') AND ra.is_active = 1",
                [$teacherId]
            ),
            'suggestions' => (int) $this->scalar(
                'SELECT COUNT(*) FROM resource_suggestions WHERE user_id = ?',
                [$teacherId]
            ),
            'overdue_students' => (int) $this->scalar(
                "SELECT COUNT(DISTINCT l.user_id)
                 FROM loans l
                 JOIN teacher_group_students tgs ON tgs.student_id = l.user_id
                 JOIN teacher_groups tg ON tg.id = tgs.group_id
                 WHERE tg.teacher_id = ? AND l.status = 'overdue' AND tg.is_active = 1",
                [$teacherId]
            ),
        ];

        $groupsStmt = $this->db->prepare(
            'SELECT
                tg.id,
                tg.name,
                tg.school_year,
                tg.description,
                COUNT(DISTINCT tgs.student_id) AS students_count,
                COUNT(DISTINCT ra.id) AS assignments_count
             FROM teacher_groups tg
             LEFT JOIN teacher_group_students tgs ON tgs.group_id = tg.id
             LEFT JOIN reading_assignments ra ON ra.group_id = tg.id AND ra.is_active = 1
             WHERE tg.teacher_id = ? AND tg.is_active = 1
             GROUP BY tg.id, tg.name, tg.school_year, tg.description
             ORDER BY tg.created_at DESC
             LIMIT 6'
        );
        $groupsStmt->execute([$teacherId]);
        $groups = $groupsStmt->fetchAll();

        $assignmentsStmt = $this->db->prepare(
            "SELECT
                ra.id,
                ra.title,
                ra.due_date,
                tg.name AS group_name,
                b.title AS book_title,
                COUNT(ras.student_id) AS assigned_students,
                SUM(CASE WHEN ras.status = 'completed' THEN 1 ELSE 0 END) AS completed_students
             FROM reading_assignments ra
             JOIN teacher_groups tg ON tg.id = ra.group_id
             JOIN resources b ON b.id = ra.resource_id
             LEFT JOIN reading_assignment_students ras ON ras.assignment_id = ra.id
             WHERE tg.teacher_id = ? AND ra.is_active = 1
             GROUP BY ra.id, ra.title, ra.due_date, tg.name, b.title
             ORDER BY ra.due_date ASC, ra.created_at DESC
             LIMIT 6"
        );
        $assignmentsStmt->execute([$teacherId]);
        $assignments = $assignmentsStmt->fetchAll();

        $activityStmt = $this->db->prepare(
            "SELECT
                u.name AS student_name,
                b.title AS book_title,
                l.status,
                l.loan_at,
                l.due_at
             FROM loans l
             JOIN teacher_group_students tgs ON tgs.student_id = l.user_id
             JOIN teacher_groups tg ON tg.id = tgs.group_id
             JOIN users u ON u.id = l.user_id
             JOIN resources b ON b.id = l.resource_id
             WHERE tg.teacher_id = ?
             ORDER BY l.loan_at DESC
             LIMIT 8"
        );
        $activityStmt->execute([$teacherId]);
        $recentActivity = $activityStmt->fetchAll();

        return Response::html($this->view->render('teacher/dashboard', [
            'title' => 'Panel docente - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'stats' => $stats,
            'groups' => $groups,
            'assignments' => $assignments,
            'recent_activity' => $recentActivity,
        ], 'layouts/panel'));
    }

    public function groups(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $teacherId = (int) $authUser['id'];
        $settings  = $this->panelSettings();

        $stmt = $this->db->prepare(
            'SELECT
                tg.id,
                tg.name,
                tg.description,
                tg.school_year,
                tg.is_active,
                tg.created_at,
                COUNT(DISTINCT tgs.student_id) AS students_count,
                COUNT(DISTINCT ra.id) AS assignments_count
             FROM teacher_groups tg
             LEFT JOIN teacher_group_students tgs ON tgs.group_id = tg.id
             LEFT JOIN reading_assignments ra ON ra.group_id = tg.id AND ra.is_active = 1
             WHERE tg.teacher_id = ?
             GROUP BY tg.id, tg.name, tg.description, tg.school_year, tg.is_active, tg.created_at
             ORDER BY tg.is_active DESC, tg.created_at DESC'
        );
        $stmt->execute([$teacherId]);
        $groups = $stmt->fetchAll();

        return Response::html($this->view->render('teacher/groups/index', [
            'title'     => 'Mis grupos - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'  => $settings,
            'auth_user' => $authUser,
            'groups'    => $groups,
        ], 'layouts/panel'));
    }

    public function createGroup(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();
        $old = Session::getFlash('group_form_old', [
            'name' => '', 'description' => '', 'school_year' => '',
        ]);

        return Response::html($this->view->render('teacher/groups/create', [
            'title'     => 'Nuevo grupo - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'  => $settings,
            'auth_user' => $authUser,
            'old'       => $old,
            'csrf'      => Session::get('_csrf_token', ''),
        ], 'layouts/panel'));
    }

    public function storeGroup(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        if (!hash_equals(Session::get('_csrf_token', ''), $request->post('_csrf_token', ''))) {
            Session::flash('error', 'Token de seguridad inválido.');
            return Response::redirect(BASE_URL . '/teacher/groups/create');
        }

        $teacherId   = (int) $authUser['id'];
        $name        = trim((string) $request->post('name', ''));
        $description = trim((string) $request->post('description', ''));
        $schoolYear  = trim((string) $request->post('school_year', ''));

        $errors = [];
        if (mb_strlen($name) < 3) {
            $errors[] = 'El nombre del grupo debe tener al menos 3 caracteres.';
        }
        if ($schoolYear === '') {
            $errors[] = 'El año escolar es obligatorio.';
        }

        if ($errors !== []) {
            Session::flash('error', implode(' ', $errors));
            Session::flash('group_form_old', compact('name', 'description', 'schoolYear'));
            return Response::redirect(BASE_URL . '/teacher/groups/create');
        }

        $this->db->prepare(
            'INSERT INTO teacher_groups (teacher_id, name, description, school_year, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, 1, NOW(), NOW())'
        )->execute([$teacherId, $name, $description !== '' ? $description : null, $schoolYear]);

        $groupId = (int) $this->db->lastInsertId();

        Session::flash('success', 'Grupo creado correctamente.');
        return Response::redirect(BASE_URL . '/teacher/groups/' . $groupId);
    }

    public function editGroup(Request $request, string $id): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $teacherId = (int) $authUser['id'];
        $groupId   = (int) $id;
        $settings  = $this->panelSettings();

        $stmt = $this->db->prepare('SELECT * FROM teacher_groups WHERE id = ? AND teacher_id = ? LIMIT 1');
        $stmt->execute([$groupId, $teacherId]);
        $group = $stmt->fetch();

        if (!$group) {
            Session::flash('error', 'Grupo no encontrado.');
            return Response::redirect(BASE_URL . '/teacher/groups');
        }

        $old = Session::getFlash('group_form_old', [
            'name'        => $group['name'],
            'description' => $group['description'] ?? '',
            'school_year' => $group['school_year'],
        ]);

        return Response::html($this->view->render('teacher/groups/edit', [
            'title'     => 'Editar grupo - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'  => $settings,
            'auth_user' => $authUser,
            'group'     => $group,
            'old'       => $old,
            'csrf'      => Session::get('_csrf_token', ''),
        ], 'layouts/panel'));
    }

    public function updateGroup(Request $request, string $id): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        if (!hash_equals(Session::get('_csrf_token', ''), $request->post('_csrf_token', ''))) {
            Session::flash('error', 'Token de seguridad inválido.');
            return Response::redirect(BASE_URL . '/teacher/groups');
        }

        $teacherId   = (int) $authUser['id'];
        $groupId     = (int) $id;
        $name        = trim((string) $request->post('name', ''));
        $description = trim((string) $request->post('description', ''));
        $schoolYear  = trim((string) $request->post('school_year', ''));
        $isActive    = $request->post('is_active', '1') === '1' ? 1 : 0;

        $stmt = $this->db->prepare('SELECT id FROM teacher_groups WHERE id = ? AND teacher_id = ? LIMIT 1');
        $stmt->execute([$groupId, $teacherId]);
        if (!$stmt->fetch()) {
            Session::flash('error', 'Grupo no encontrado.');
            return Response::redirect(BASE_URL . '/teacher/groups');
        }

        $errors = [];
        if (mb_strlen($name) < 3) {
            $errors[] = 'El nombre del grupo debe tener al menos 3 caracteres.';
        }
        if ($schoolYear === '') {
            $errors[] = 'El año escolar es obligatorio.';
        }

        if ($errors !== []) {
            Session::flash('error', implode(' ', $errors));
            Session::flash('group_form_old', compact('name', 'description', 'schoolYear'));
            return Response::redirect(BASE_URL . '/teacher/groups/' . $groupId . '/edit');
        }

        $this->db->prepare(
            'UPDATE teacher_groups SET name = ?, description = ?, school_year = ?, is_active = ?, updated_at = NOW()
             WHERE id = ? AND teacher_id = ?'
        )->execute([$name, $description !== '' ? $description : null, $schoolYear, $isActive, $groupId, $teacherId]);

        Session::flash('success', 'Grupo actualizado correctamente.');
        return Response::redirect(BASE_URL . '/teacher/groups/' . $groupId);
    }

    public function groupActivity(Request $request, string $id): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $teacherId = (int) $authUser['id'];
        $groupId   = (int) $id;
        $settings  = $this->panelSettings();

        $stmt = $this->db->prepare(
            'SELECT id, name, school_year FROM teacher_groups WHERE id = ? AND teacher_id = ? LIMIT 1'
        );
        $stmt->execute([$groupId, $teacherId]);
        $group = $stmt->fetch();

        if (!$group) {
            Session::flash('error', 'Grupo no encontrado.');
            return Response::redirect(BASE_URL . '/teacher/groups');
        }

        $activityStmt = $this->db->prepare(
            "SELECT
                u.id AS student_id,
                u.name AS student_name,
                r.title AS book_title,
                l.status,
                l.loan_at,
                l.due_at,
                l.returned_at
             FROM loans l
             JOIN teacher_group_students tgs ON tgs.student_id = l.user_id AND tgs.group_id = ?
             JOIN users u ON u.id = l.user_id
             JOIN resources r ON r.id = l.resource_id
             ORDER BY l.loan_at DESC
             LIMIT 100"
        );
        $activityStmt->execute([$groupId]);
        $activity = $activityStmt->fetchAll();

        return Response::html($this->view->render('teacher/groups/activity', [
            'title'     => 'Actividad del grupo - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'  => $settings,
            'auth_user' => $authUser,
            'group'     => $group,
            'activity'  => $activity,
        ], 'layouts/panel'));
    }

    public function studentProfile(Request $request, string $id, string $studentId = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $teacherId = (int) $authUser['id'];
        $groupId   = (int) $id;
        $stuId     = (int) $studentId;
        $settings  = $this->panelSettings();

        $groupStmt = $this->db->prepare(
            'SELECT id, name FROM teacher_groups WHERE id = ? AND teacher_id = ? LIMIT 1'
        );
        $groupStmt->execute([$groupId, $teacherId]);
        $group = $groupStmt->fetch();

        if (!$group) {
            Session::flash('error', 'Grupo no encontrado.');
            return Response::redirect(BASE_URL . '/teacher/groups');
        }

        // Verify student belongs to this group
        $memberStmt = $this->db->prepare(
            'SELECT 1 FROM teacher_group_students WHERE group_id = ? AND student_id = ? LIMIT 1'
        );
        $memberStmt->execute([$groupId, $stuId]);
        if (!$memberStmt->fetch()) {
            Session::flash('error', 'El alumno no pertenece a este grupo.');
            return Response::redirect(BASE_URL . '/teacher/groups/' . $groupId);
        }

        $userStmt = $this->db->prepare('SELECT id, name, email, user_number, status FROM users WHERE id = ? LIMIT 1');
        $userStmt->execute([$stuId]);
        $student = $userStmt->fetch();

        if (!$student) {
            Response::abort(404);
        }

        $loansStmt = $this->db->prepare(
            "SELECT l.id, r.title AS book_title, l.status, l.loan_at, l.due_at, l.returned_at
             FROM loans l
             JOIN resources r ON r.id = l.resource_id
             WHERE l.user_id = ?
             ORDER BY l.loan_at DESC
             LIMIT 20"
        );
        $loansStmt->execute([$stuId]);
        $loans = $loansStmt->fetchAll();

        $assignmentsStmt = $this->db->prepare(
            "SELECT ra.title, ra.due_date, r.title AS book_title, ras.status AS progress_status, ras.completed_at
             FROM reading_assignment_students ras
             JOIN reading_assignments ra ON ra.id = ras.assignment_id
             JOIN resources r ON r.id = ra.resource_id
             WHERE ras.student_id = ? AND ra.group_id = ? AND ra.is_active = 1
             ORDER BY ra.due_date ASC"
        );
        $assignmentsStmt->execute([$stuId, $groupId]);
        $assignments = $assignmentsStmt->fetchAll();

        return Response::html($this->view->render('teacher/groups/student-profile', [
            'title'       => $student['name'] . ' - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'    => $settings,
            'auth_user'   => $authUser,
            'group'       => $group,
            'student'     => $student,
            'loans'       => $loans,
            'assignments' => $assignments,
        ], 'layouts/panel'));
    }

    public function groupReport(Request $request, string $id): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $teacherId = (int) $authUser['id'];
        $groupId   = (int) $id;
        $settings  = $this->panelSettings();

        $stmt = $this->db->prepare(
            'SELECT
                tg.id,
                tg.name,
                tg.description,
                tg.school_year,
                tg.created_at,
                COUNT(DISTINCT tgs.student_id) AS students_count,
                COUNT(DISTINCT ra.id) AS assignments_count
             FROM teacher_groups tg
             LEFT JOIN teacher_group_students tgs ON tgs.group_id = tg.id
             LEFT JOIN reading_assignments ra ON ra.group_id = tg.id AND ra.is_active = 1
             WHERE tg.id = ? AND tg.teacher_id = ?
             GROUP BY tg.id, tg.name, tg.description, tg.school_year, tg.created_at
             LIMIT 1'
        );
        $stmt->execute([$groupId, $teacherId]);
        $group = $stmt->fetch();

        if (!$group) {
            Session::flash('error', 'Grupo no encontrado.');
            return Response::redirect(BASE_URL . '/teacher/groups');
        }

        $studentsStmt = $this->db->prepare(
            "SELECT
                u.id,
                u.name,
                u.user_number,
                COUNT(DISTINCT CASE WHEN l.status = 'active' THEN l.id END) AS active_loans,
                COUNT(DISTINCT CASE WHEN l.status = 'overdue' THEN l.id END) AS overdue_loans,
                COUNT(DISTINCT CASE WHEN ras.status = 'completed' THEN ras.assignment_id END) AS completed_assignments,
                COUNT(DISTINCT ras.assignment_id) AS total_assignments
             FROM teacher_group_students tgs
             JOIN users u ON u.id = tgs.student_id
             LEFT JOIN loans l ON l.user_id = u.id
             LEFT JOIN reading_assignment_students ras ON ras.student_id = u.id
             WHERE tgs.group_id = ?
             GROUP BY u.id, u.name, u.user_number
             ORDER BY u.name ASC"
        );
        $studentsStmt->execute([$groupId]);
        $students = $studentsStmt->fetchAll();

        return Response::html($this->view->render('teacher/groups/report', [
            'title'     => 'Reporte: ' . $group['name'] . ' - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'  => $settings,
            'auth_user' => $authUser,
            'group'     => $group,
            'students'  => $students,
        ], 'layouts/panel'));
    }

    public function addGroupStudent(Request $request, string $id): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        if (!hash_equals((string) Session::get('_csrf_token', ''), (string) $request->post('_csrf_token', ''))) {
            Session::flash('error', 'Token de seguridad inválido.');
            return Response::redirect(BASE_URL . '/teacher/groups/' . (int) $id);
        }

        $teacherId = (int) $authUser['id'];
        $groupId = (int) $id;
        $studentId = (int) $request->post('student_id', 0);

        if ($groupId <= 0 || $studentId <= 0) {
            Session::flash('error', 'Debes seleccionar un estudiante válido.');
            return Response::redirect(BASE_URL . '/teacher/groups/' . $groupId);
        }

        $groupStmt = $this->db->prepare('SELECT id, is_active FROM teacher_groups WHERE id = ? AND teacher_id = ? LIMIT 1');
        $groupStmt->execute([$groupId, $teacherId]);
        $group = $groupStmt->fetch();

        if (!$group) {
            Session::flash('error', 'Grupo no encontrado.');
            return Response::redirect(BASE_URL . '/teacher/groups');
        }

        if ((int) ($group['is_active'] ?? 0) !== 1) {
            Session::flash('error', 'No puedes agregar estudiantes a un grupo inactivo.');
            return Response::redirect(BASE_URL . '/teacher/groups/' . $groupId);
        }

        $studentStmt = $this->db->prepare(
            "SELECT id
             FROM users
             WHERE id = ?
               AND status = 'active'
               AND role = 'user'
             LIMIT 1"
        );
        $studentStmt->execute([$studentId]);
        if (!$studentStmt->fetch()) {
            Session::flash('error', 'El estudiante seleccionado no existe o no está activo.');
            return Response::redirect(BASE_URL . '/teacher/groups/' . $groupId);
        }

        $existsStmt = $this->db->prepare('SELECT 1 FROM teacher_group_students WHERE group_id = ? AND student_id = ? LIMIT 1');
        $existsStmt->execute([$groupId, $studentId]);
        if ($existsStmt->fetch()) {
            Session::flash('info', 'El estudiante ya pertenece a este grupo.');
            return Response::redirect(BASE_URL . '/teacher/groups/' . $groupId);
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare('INSERT INTO teacher_group_students (group_id, student_id, added_at) VALUES (?, ?, NOW())')
                ->execute([$groupId, $studentId]);

            $this->db->prepare(
                "INSERT INTO reading_assignment_students (assignment_id, student_id, status, updated_at)
                 SELECT ra.id, ?, 'pending', NOW()
                 FROM reading_assignments ra
                 WHERE ra.group_id = ?
                   AND ra.is_active = 1
                   AND NOT EXISTS (
                       SELECT 1
                       FROM reading_assignment_students ras
                       WHERE ras.assignment_id = ra.id
                         AND ras.student_id = ?
                   )"
            )->execute([$studentId, $groupId, $studentId]);

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        Session::flash('success', 'Estudiante agregado al grupo correctamente.');
        return Response::redirect(BASE_URL . '/teacher/groups/' . $groupId);
    }

    public function removeGroupStudent(Request $request, string $id, string $studentId = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        if (!hash_equals((string) Session::get('_csrf_token', ''), (string) $request->post('_csrf_token', ''))) {
            Session::flash('error', 'Token de seguridad inválido.');
            return Response::redirect(BASE_URL . '/teacher/groups/' . (int) $id);
        }

        $teacherId = (int) $authUser['id'];
        $groupId = (int) $id;
        $stuId = (int) $studentId;

        if ($groupId <= 0 || $stuId <= 0) {
            Session::flash('error', 'Parámetros inválidos para quitar estudiante.');
            return Response::redirect(BASE_URL . '/teacher/groups/' . $groupId);
        }

        $groupStmt = $this->db->prepare('SELECT id FROM teacher_groups WHERE id = ? AND teacher_id = ? LIMIT 1');
        $groupStmt->execute([$groupId, $teacherId]);
        if (!$groupStmt->fetch()) {
            Session::flash('error', 'Grupo no encontrado.');
            return Response::redirect(BASE_URL . '/teacher/groups');
        }

        $this->db->beginTransaction();
        try {
            $deleteStmt = $this->db->prepare('DELETE FROM teacher_group_students WHERE group_id = ? AND student_id = ?');
            $deleteStmt->execute([$groupId, $stuId]);

            if ($deleteStmt->rowCount() > 0) {
                $this->db->prepare(
                    "DELETE ras
                     FROM reading_assignment_students ras
                     JOIN reading_assignments ra ON ra.id = ras.assignment_id
                     WHERE ra.group_id = ?
                       AND ras.student_id = ?"
                )->execute([$groupId, $stuId]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        if ($deleteStmt->rowCount() > 0) {
            Session::flash('success', 'Estudiante removido del grupo.');
        } else {
            Session::flash('info', 'El estudiante ya no estaba en este grupo.');
        }

        return Response::redirect(BASE_URL . '/teacher/groups/' . $groupId);
    }

    public function showGroup(Request $request, string $id): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $groupId = (int) $id;
        $teacherId = (int) $authUser['id'];
        $settings = $this->panelSettings();

        $groupStmt = $this->db->prepare(
            'SELECT
                tg.id,
                tg.name,
                tg.description,
                tg.school_year,
                tg.is_active,
                tg.created_at,
                COUNT(DISTINCT tgs.student_id) AS students_count,
                COUNT(DISTINCT ra.id) AS assignments_count
             FROM teacher_groups tg
             LEFT JOIN teacher_group_students tgs ON tgs.group_id = tg.id
             LEFT JOIN reading_assignments ra ON ra.group_id = tg.id AND ra.is_active = 1
             WHERE tg.id = ? AND tg.teacher_id = ?
             GROUP BY tg.id, tg.name, tg.description, tg.school_year, tg.is_active, tg.created_at
             LIMIT 1'
        );
        $groupStmt->execute([$groupId, $teacherId]);
        $group = $groupStmt->fetch();

        if (!$group) {
            Response::abort(404);
        }

        $studentsStmt = $this->db->prepare(
            "SELECT
                u.id,
                u.name,
                u.email,
                u.user_number,
                u.status,
                COUNT(DISTINCT CASE WHEN l.status = 'active' THEN l.id END) AS active_loans,
                COUNT(DISTINCT CASE WHEN l.status = 'overdue' THEN l.id END) AS overdue_loans,
                COALESCE(SUM(CASE WHEN f.status IN ('pending', 'partially_paid') THEN f.amount - f.amount_paid ELSE 0 END), 0) AS pending_fines
             FROM teacher_group_students tgs
             JOIN users u ON u.id = tgs.student_id
             LEFT JOIN loans l ON l.user_id = u.id
             LEFT JOIN fines f ON f.user_id = u.id
             WHERE tgs.group_id = ?
             GROUP BY u.id, u.name, u.email, u.user_number, u.status
             ORDER BY u.name ASC"
        );
        $studentsStmt->execute([$groupId]);
        $students = $studentsStmt->fetchAll();

                $availableStudentsStmt = $this->db->prepare(
                        "SELECT u.id, u.name, u.email, u.user_number
                         FROM users u
                         WHERE u.status = 'active'
                             AND u.role = 'user'
                             AND NOT EXISTS (
                                     SELECT 1
                                     FROM teacher_group_students tgs
                                     WHERE tgs.group_id = :group_id
                                         AND tgs.student_id = u.id
                             )
                         ORDER BY u.name ASC"
                );
                $availableStudentsStmt->execute([':group_id' => $groupId]);
                $availableStudents = $availableStudentsStmt->fetchAll();

        $assignmentsStmt = $this->db->prepare(
            "SELECT
                ra.id,
                ra.title,
                ra.description,
                ra.due_date,
                b.title AS book_title,
                COUNT(ras.student_id) AS assigned_students,
                SUM(CASE WHEN ras.status = 'pending' THEN 1 ELSE 0 END) AS pending_students,
                SUM(CASE WHEN ras.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_students,
                SUM(CASE WHEN ras.status = 'completed' THEN 1 ELSE 0 END) AS completed_students
             FROM reading_assignments ra
             JOIN resources b ON b.id = ra.resource_id
             LEFT JOIN reading_assignment_students ras ON ras.assignment_id = ra.id
             WHERE ra.group_id = ? AND ra.is_active = 1
             GROUP BY ra.id, ra.title, ra.description, ra.due_date, b.title
             ORDER BY ra.due_date ASC, ra.created_at DESC"
        );
        $assignmentsStmt->execute([$groupId]);
        $assignments = $assignmentsStmt->fetchAll();

        $summary = [
            'students' => (int) $group['students_count'],
            'assignments' => (int) $group['assignments_count'],
            'active_loans' => (int) $this->scalar(
                "SELECT COUNT(DISTINCT l.id)
                 FROM loans l
                 JOIN teacher_group_students tgs ON tgs.student_id = l.user_id
                 WHERE tgs.group_id = ? AND l.status = 'active'",
                [$groupId]
            ),
            'overdue_loans' => (int) $this->scalar(
                "SELECT COUNT(DISTINCT l.id)
                 FROM loans l
                 JOIN teacher_group_students tgs ON tgs.student_id = l.user_id
                 WHERE tgs.group_id = ? AND l.status = 'overdue'",
                [$groupId]
            ),
        ];

        return Response::html($this->view->render('teacher/groups/show', [
            'title' => $group['name'] . ' - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'group' => $group,
            'summary' => $summary,
            'students' => $students,
            'assignments' => $assignments,
            'available_students' => $availableStudents,
            'csrf' => Session::get('_csrf_token', ''),
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
