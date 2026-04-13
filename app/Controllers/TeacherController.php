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
