<?php
// app/Controllers/ReservationController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;
use Helpers\SafeRedirect;

final class ReservationController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $stmt = $this->db->query(
            "SELECT
                r.id,
                r.status,
                r.queue_position,
                r.created_at,
                r.notified_at,
                r.expires_at,
                u.name AS user_name,
                b.title AS resource_title
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN resources b ON b.id = r.resource_id
             ORDER BY r.created_at DESC"
        );
        $reservations = $stmt->fetchAll();

        $settings = $this->panelSettings();

        return Response::html($this->view->render('admin/reservations/index', [
            'title' => 'Reservaciones - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'reservations' => $reservations,
            'stats' => [
                'pending' => count(array_filter($reservations, fn(array $r): bool => $r['status'] === 'waiting')),
                'ready' => count(array_filter($reservations, fn(array $r): bool => $r['status'] === 'notified')),
                'expired' => count(array_filter($reservations, fn(array $r): bool => $r['status'] === 'expired')),
                'cancelled' => count(array_filter($reservations, fn(array $r): bool => $r['status'] === 'cancelled')),
            ],
        ], 'layouts/panel'));
    }

    public function myReservations(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $stmt = $this->db->prepare(
            "SELECT
                r.id,
                r.resource_id,
                r.status,
                r.queue_position,
                r.notified_at,
                r.expires_at,
                r.created_at,
                b.title AS book_title,
                b.authors AS book_authors,
                b.cover_image
             FROM reservations r
             JOIN resources b ON b.id = r.resource_id
             WHERE r.user_id = ?
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([(int) $authUser['id']]);
        $reservations = $stmt->fetchAll();

        $summary = [
            'waiting' => 0,
            'notified' => 0,
            'fulfilled' => 0,
            'expired' => 0,
            'cancelled' => 0,
        ];

        foreach ($reservations as $reservation) {
            $status = (string) ($reservation['status'] ?? '');
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        $resourceSearch = trim((string) $request->get('resource_q', ''));
        $resourceCandidates = [];
        if ($resourceSearch !== '') {
            $candidateStmt = $this->db->prepare(
                "SELECT
                    id,
                    title,
                    authors,
                    isbn_13,
                    publisher,
                    available_copies,
                    support_type
                 FROM resources
                 WHERE is_active = 1
                   AND support_type <> 'digital'
                   AND (
                        title LIKE :like
                        OR isbn_13 LIKE :like
                        OR publisher LIKE :like
                        OR description LIKE :like
                        OR authors LIKE :like
                   )
                 ORDER BY available_copies DESC, title ASC
                 LIMIT 12"
            );
            $candidateStmt->execute([':like' => '%' . $resourceSearch . '%']);
            $resourceCandidates = $candidateStmt->fetchAll();
        }

        $settings = $this->panelSettings();

        return Response::html($this->view->render('account/reservations', [
            'title' => 'Mis reservaciones - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'reservations' => $reservations,
            'summary' => $summary,
            'resource_search' => $resourceSearch,
            'resource_candidates' => $resourceCandidates,
        ], 'layouts/panel'));
    }

    public function store(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $resourceId = (int) $request->post('resource_id', 0);
        $resourceQuery = trim((string) $request->post('resource_query', ''));
        $redirect = $this->resolveRedirectTarget((string) $request->post('redirect', '/account/reservations'));

        $redirectWithQuery = BASE_URL . '/account/reservations';
        if ($resourceQuery !== '') {
            $redirectWithQuery .= '?resource_q=' . rawurlencode($resourceQuery);
        }

        if ($resourceId <= 0 && $resourceQuery !== '') {
            $matchStmt = $this->db->prepare(
                "SELECT id
                 FROM resources
                 WHERE is_active = 1
                   AND support_type <> 'digital'
                   AND (
                        title LIKE :like
                        OR isbn_13 LIKE :like
                        OR publisher LIKE :like
                        OR description LIKE :like
                        OR authors LIKE :like
                   )
                 ORDER BY available_copies DESC, title ASC
                 LIMIT 2"
            );
            $matchStmt->execute([':like' => '%' . $resourceQuery . '%']);
            $matches = $matchStmt->fetchAll();

            if (count($matches) === 0) {
                Session::flash('error', 'No encontramos recursos con ese termino. Prueba con otro nombre, ISBN o palabra clave.');
                return Response::redirect($redirectWithQuery);
            }

            if (count($matches) > 1) {
                Session::flash('info', 'Se encontraron varios recursos. Selecciona uno de la lista para reservar.');
                return Response::redirect($redirectWithQuery);
            }

            $resourceId = (int) ($matches[0]['id'] ?? 0);
        }

        if ($resourceId <= 0) {
            Session::flash('error', 'Debes buscar y seleccionar un recurso valido para reservar.');
            return Response::redirect($resourceQuery !== '' ? $redirectWithQuery : (BASE_URL . $redirect));
        }

        $resourceStmt = $this->db->prepare('SELECT id, title, support_type, is_active FROM resources WHERE id = ? LIMIT 1');
        $resourceStmt->execute([$resourceId]);
        $resource = $resourceStmt->fetch();

        if (!$resource || (int) ($resource['is_active'] ?? 0) !== 1) {
            Session::flash('error', 'El recurso no esta disponible para reservacion.');
            return Response::redirect(BASE_URL . $redirect);
        }

        if ((string) ($resource['support_type'] ?? '') === 'digital') {
            Session::flash('error', 'Los recursos digitales no requieren reservacion.');
            return Response::redirect(BASE_URL . $redirect);
        }

        $activeLoanStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM loans
             WHERE user_id = ? AND resource_id = ? AND status IN ('active','overdue')"
        );
        $activeLoanStmt->execute([(int) $authUser['id'], $resourceId]);
        if ((int) $activeLoanStmt->fetchColumn() > 0) {
            Session::flash('error', 'Ya tienes un prestamo activo para este recurso.');
            return Response::redirect(BASE_URL . $redirect);
        }

        $activeReservationStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM reservations
             WHERE user_id = ? AND resource_id = ? AND status IN ('waiting','notified')"
        );
        $activeReservationStmt->execute([(int) $authUser['id'], $resourceId]);
        if ((int) $activeReservationStmt->fetchColumn() > 0) {
            Session::flash('info', 'Ya tienes una reservacion activa para este recurso.');
            return Response::redirect(BASE_URL . '/account/reservations');
        }

        $this->db->beginTransaction();
        try {
            $queueStmt = $this->db->prepare(
                "SELECT COALESCE(MAX(queue_position), 0)
                 FROM reservations
                 WHERE resource_id = ? AND status IN ('waiting','notified')
                 FOR UPDATE"
            );
            $queueStmt->execute([$resourceId]);
            $queuePosition = ((int) $queueStmt->fetchColumn()) + 1;

            $insertStmt = $this->db->prepare(
                "INSERT INTO reservations (resource_id, user_id, queue_position, status, created_at)
                 VALUES (?, ?, ?, 'waiting', NOW())"
            );
            $insertStmt->execute([$resourceId, (int) $authUser['id'], $queuePosition]);

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        Session::flash('success', 'Reservacion creada correctamente. Tu posicion en fila es #' . $queuePosition . '.');
        return Response::redirect(BASE_URL . '/account/reservations');
    }

    public function cancel(Request $request, string $id = '0'): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $reservationId = (int) $id;
        if ($reservationId <= 0) {
            Session::flash('error', 'Reservacion invalida.');
            return Response::redirect(BASE_URL . '/account/reservations');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT id, resource_id, queue_position, status
                 FROM reservations
                 WHERE id = ? AND user_id = ?
                 FOR UPDATE"
            );
            $stmt->execute([$reservationId, (int) $authUser['id']]);
            $reservation = $stmt->fetch();

            if (!$reservation) {
                $this->db->rollBack();
                Session::flash('error', 'No se encontro la reservacion solicitada.');
                return Response::redirect(BASE_URL . '/account/reservations');
            }

            if (!in_array((string) $reservation['status'], ['waiting', 'notified'], true)) {
                $this->db->rollBack();
                Session::flash('info', 'Solo se pueden cancelar reservaciones activas.');
                return Response::redirect(BASE_URL . '/account/reservations');
            }

            $cancelStmt = $this->db->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
            $cancelStmt->execute([$reservationId]);

            $reorderStmt = $this->db->prepare(
                "UPDATE reservations
                 SET queue_position = queue_position - 1
                 WHERE resource_id = ?
                   AND status IN ('waiting','notified')
                   AND queue_position > ?"
            );
            $reorderStmt->execute([(int) $reservation['resource_id'], (int) $reservation['queue_position']]);

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        Session::flash('success', 'Reservacion cancelada correctamente.');
        return Response::redirect(BASE_URL . '/account/reservations');
    }

    public function convertToLoan(Request $request, string $id = '0'): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $reservationId = (int) $id;
        if ($reservationId <= 0) {
            Session::flash('error', 'Reservacion invalida.');
            return Response::redirect(BASE_URL . '/admin/reservations');
        }

        $this->db->beginTransaction();
        try {
            $reservationStmt = $this->db->prepare(
                "SELECT id, user_id, resource_id, queue_position, status
                 FROM reservations
                 WHERE id = ?
                 FOR UPDATE"
            );
            $reservationStmt->execute([$reservationId]);
            $reservation = $reservationStmt->fetch();

            if (!$reservation) {
                $this->db->rollBack();
                Session::flash('error', 'No se encontro la reservacion.');
                return Response::redirect(BASE_URL . '/admin/reservations');
            }

            if (!in_array((string) $reservation['status'], ['waiting', 'notified'], true)) {
                $this->db->rollBack();
                Session::flash('info', 'La reservacion ya no esta activa.');
                return Response::redirect(BASE_URL . '/admin/reservations');
            }

            $resourceStmt = $this->db->prepare('SELECT id, available_copies, branch_id FROM resources WHERE id = ? FOR UPDATE');
            $resourceStmt->execute([(int) $reservation['resource_id']]);
            $resource = $resourceStmt->fetch();

            if (!$resource || (int) ($resource['available_copies'] ?? 0) <= 0) {
                $this->db->rollBack();
                Session::flash('error', 'No hay ejemplares disponibles para convertir esta reservacion en prestamo.');
                return Response::redirect(BASE_URL . '/admin/reservations');
            }

            $loanHours = 72;
            $insertLoanStmt = $this->db->prepare(
                "INSERT INTO loans
                 (resource_id, user_id, librarian_id, branch_id, loan_at, due_at, loan_hours_applied, renewals_count, status, created_at)
                 VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR), ?, 0, 'active', NOW())"
            );
            $insertLoanStmt->execute([
                (int) $reservation['resource_id'],
                (int) $reservation['user_id'],
                (int) $authUser['id'],
                (int) ($resource['branch_id'] ?? 0) ?: null,
                $loanHours,
                $loanHours,
            ]);

            $resourceUpdateStmt = $this->db->prepare(
                'UPDATE resources SET available_copies = available_copies - 1 WHERE id = ? AND available_copies > 0'
            );
            $resourceUpdateStmt->execute([(int) $reservation['resource_id']]);

            $reservationUpdateStmt = $this->db->prepare(
                "UPDATE reservations
                 SET status = 'fulfilled', notified_at = COALESCE(notified_at, NOW()), expires_at = NULL
                 WHERE id = ?"
            );
            $reservationUpdateStmt->execute([$reservationId]);

            $reorderStmt = $this->db->prepare(
                "UPDATE reservations
                 SET queue_position = queue_position - 1
                 WHERE resource_id = ?
                   AND status IN ('waiting','notified')
                   AND queue_position > ?"
            );
            $reorderStmt->execute([(int) $reservation['resource_id'], (int) $reservation['queue_position']]);

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        Session::flash('success', 'Reservacion convertida en prestamo correctamente.');
        return Response::redirect(BASE_URL . '/admin/reservations');
    }

    public function adminCancel(Request $request, string $id = '0'): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $reservationId = (int) $id;
        if ($reservationId <= 0) {
            Session::flash('error', 'Reservacion invalida.');
            return Response::redirect(BASE_URL . '/admin/reservations');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT id, resource_id, queue_position, status
                 FROM reservations
                 WHERE id = ?
                 FOR UPDATE"
            );
            $stmt->execute([$reservationId]);
            $reservation = $stmt->fetch();

            if (!$reservation) {
                $this->db->rollBack();
                Session::flash('error', 'No se encontro la reservacion.');
                return Response::redirect(BASE_URL . '/admin/reservations');
            }

            if (!in_array((string) $reservation['status'], ['waiting', 'notified'], true)) {
                $this->db->rollBack();
                Session::flash('info', 'Solo se pueden cancelar reservaciones activas.');
                return Response::redirect(BASE_URL . '/admin/reservations');
            }

            $cancelStmt = $this->db->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
            $cancelStmt->execute([$reservationId]);

            $reorderStmt = $this->db->prepare(
                "UPDATE reservations
                 SET queue_position = queue_position - 1
                 WHERE resource_id = ?
                   AND status IN ('waiting','notified')
                   AND queue_position > ?"
            );
            $reorderStmt->execute([(int) $reservation['resource_id'], (int) $reservation['queue_position']]);

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        Session::flash('success', 'Reservacion cancelada correctamente.');
        return Response::redirect(BASE_URL . '/admin/reservations');
    }

    private function resolveRedirectTarget(string $raw): string
    {
        $fallback = '/account/reservations';
        $candidate = trim($raw);
        if ($candidate === '') {
            return $fallback;
        }

        return SafeRedirect::to($candidate, $fallback);
    }
}
