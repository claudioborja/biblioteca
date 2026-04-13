<?php
// app/Controllers/LoanController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;

final class LoanController extends BaseController
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
                l.id,
                l.loan_at,
                l.due_at,
                l.returned_at,
                l.status,
                u.name AS user,
                b.title AS book
             FROM loans l
             JOIN users u ON u.id = l.user_id
             JOIN resources b ON b.id = l.resource_id
             ORDER BY l.loan_at DESC"
        );
        $loans = $stmt->fetchAll();

        $stats = [
            'active' => 0,
            'overdue' => 0,
            'returned' => 0,
            'lost' => 0,
        ];

        foreach ($loans as $loan) {
            $status = (string) ($loan['status'] ?? '');
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        $settings = $this->panelSettings();

        return Response::html($this->view->render('admin/loans/index', [
            'title' => 'Prestamos - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'loans' => $loans,
            'stats' => $stats,
        ], 'layouts/panel'));
    }

    public function myLoans(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $currentUserId = (int) Session::get('auth.user_id', 0);
        if ($currentUserId <= 0) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $stmt = $this->db->prepare(
            "SELECT
                l.id,
                l.user_id,
                l.loan_at,
                l.due_at,
                l.returned_at,
                l.renewals_count,
                l.status,
                l.notes,
                b.title AS book_title,
                b.authors AS book_authors,
                b.cover_image
             FROM loans l
             JOIN resources b ON b.id = l.resource_id
               WHERE l.user_id = :user_id
             ORDER BY l.loan_at DESC"
        );
           $stmt->bindValue(':user_id', $currentUserId, \PDO::PARAM_INT);
           $stmt->execute();
        $loans = array_values(array_filter(
            $stmt->fetchAll(),
            static fn(array $loan): bool => (int) ($loan['user_id'] ?? 0) === $currentUserId
        ));

        $summary = [
            'active' => 0,
            'overdue' => 0,
            'returned' => 0,
            'lost' => 0,
        ];

        foreach ($loans as $loan) {
            $status = (string) ($loan['status'] ?? '');
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        $settings = $this->panelSettings();

        return Response::html($this->view->render('account/loans', [
            'title' => 'Mis prestamos - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'loans' => $loans,
            'summary' => $summary,
        ], 'layouts/panel'));
    }

    public function renew(Request $request, string $id = '0'): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $loanId = (int) $id;
        if ($loanId <= 0) {
            Session::flash('error', 'Préstamo inválido.');
            return Response::redirect(BASE_URL . '/account/loans');
        }

        $currentUserId = (int) Session::get('auth.user_id', 0);

        $loanStmt = $this->db->prepare(
            'SELECT id, user_id, status, renewals_count, due_at
             FROM loans WHERE id = ? LIMIT 1'
        );
        $loanStmt->execute([$loanId]);
        $loan = $loanStmt->fetch();

        if (!$loan || (int) $loan['user_id'] !== $currentUserId) {
            Session::flash('error', 'No se encontró el préstamo o no te pertenece.');
            return Response::redirect(BASE_URL . '/account/loans');
        }

        if (!in_array((string) ($loan['status'] ?? ''), ['active', 'overdue'], true)) {
            Session::flash('info', 'Solo puedes renovar préstamos activos o vencidos.');
            return Response::redirect(BASE_URL . '/account/loans');
        }

        $settings  = $this->panelSettings();
        $maxRenewals = (int) ($settings['max_renewals'] ?? 3);
        $renewalHours = (int) ($settings['loan_hours'] ?? 72);
        if ($renewalHours <= 0) {
            $renewalHours = 72;
        }

        if ((int) $loan['renewals_count'] >= $maxRenewals) {
            Session::flash('error', 'Este préstamo ya alcanzó el máximo de ' . $maxRenewals . ' renovaciones permitidas.');
            return Response::redirect(BASE_URL . '/account/loans');
        }

        $renewStmt = $this->db->prepare(
            "UPDATE loans
             SET due_at = DATE_ADD(due_at, INTERVAL :hours HOUR),
                 renewals_count = renewals_count + 1,
                 status = 'active',
                 updated_at = NOW()
             WHERE id = :id"
        );
        $renewStmt->execute([':hours' => $renewalHours, ':id' => $loanId]);

        Session::flash('success', 'Préstamo renovado por ' . $renewalHours . ' horas adicionales.');
        return Response::redirect(BASE_URL . '/account/loans');
    }

    public function create(Request $request): Response
    {
        Session::flash('info', 'La creación manual de préstamos estará disponible próximamente.');
        return Response::redirect(BASE_URL . '/admin/loans');
    }

    public function store(Request $request): Response
    {
        Session::flash('info', 'La creación manual de préstamos estará disponible próximamente.');
        return Response::redirect(BASE_URL . '/admin/loans');
    }

    public function returnBook(Request $request, string $id = '0'): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $loanId = (int) $id;
        if ($loanId <= 0) {
            Session::flash('error', 'Préstamo inválido.');
            return Response::redirect(BASE_URL . '/admin/loans');
        }

        $fineCreated = false;

        $this->db->beginTransaction();
        try {
            $loanStmt = $this->db->prepare(
                "SELECT id, resource_id, status
                 FROM loans
                 WHERE id = ?
                 FOR UPDATE"
            );
            $loanStmt->execute([$loanId]);
            $loan = $loanStmt->fetch();

            if (!$loan) {
                $this->db->rollBack();
                Session::flash('error', 'No se encontró el préstamo.');
                return Response::redirect(BASE_URL . '/admin/loans');
            }

            if (!in_array((string) ($loan['status'] ?? ''), ['active', 'overdue'], true)) {
                $this->db->rollBack();
                Session::flash('info', 'Solo puedes devolver préstamos activos o vencidos.');
                return Response::redirect(BASE_URL . '/admin/loans');
            }

            $updateLoanStmt = $this->db->prepare(
                "UPDATE loans
                  SET status = 'returned', returned_at = NOW()
                 WHERE id = ?"
            );
            $updateLoanStmt->execute([$loanId]);

            $updateResourceStmt = $this->db->prepare(
                'UPDATE resources SET available_copies = available_copies + 1 WHERE id = ?'
            );
            $updateResourceStmt->execute([(int) ($loan['resource_id'] ?? 0)]);

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        Session::flash('success', 'Préstamo devuelto correctamente.');
        return Response::redirect(BASE_URL . '/admin/loans');
    }

    public function adminRenew(Request $request, string $id = '0'): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $loanId = (int) $id;
        if ($loanId <= 0) {
            Session::flash('error', 'Préstamo inválido.');
            return Response::redirect(BASE_URL . '/admin/loans');
        }

        $loanStmt = $this->db->prepare(
            "SELECT id, status, renewals_count
             FROM loans
             WHERE id = ?
             LIMIT 1"
        );
        $loanStmt->execute([$loanId]);
        $loan = $loanStmt->fetch();

        if (!$loan) {
            Session::flash('error', 'No se encontró el préstamo.');
            return Response::redirect(BASE_URL . '/admin/loans');
        }

        if (!in_array((string) ($loan['status'] ?? ''), ['active', 'overdue'], true)) {
            Session::flash('info', 'Solo puedes renovar préstamos activos o vencidos.');
            return Response::redirect(BASE_URL . '/admin/loans');
        }

        $renewals = (int) ($loan['renewals_count'] ?? 0);
        if ($renewals >= 3) {
            Session::flash('error', 'Este préstamo alcanzó el máximo de 3 renovaciones.');
            return Response::redirect(BASE_URL . '/admin/loans');
        }

        $requestedHours = (int) $request->post('renewal_hours', 72);
        $allowedHours = [24, 48, 72, 96, 168];
        $renewalHours = in_array($requestedHours, $allowedHours, true) ? $requestedHours : 72;

        $renewStmt = $this->db->prepare(
            "UPDATE loans
             SET due_at = DATE_ADD(due_at, INTERVAL {$renewalHours} HOUR),
                 renewals_count = renewals_count + 1,
                 status = 'active'
             WHERE id = ?"
        );
        $renewStmt->execute([$loanId]);

        Session::flash('success', 'Préstamo renovado por ' . $renewalHours . ' horas.');
        return Response::redirect(BASE_URL . '/admin/loans');
    }

    public function markLost(Request $request, string $id = '0'): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $loanId = (int) $id;
        if ($loanId <= 0) {
            Session::flash('error', 'Préstamo inválido.');
            return Response::redirect(BASE_URL . '/admin/loans');
        }

        $fineCreated = false;

        $this->db->beginTransaction();
        try {
            $loanStmt = $this->db->prepare(
                "SELECT l.id, l.status, l.user_id, l.resource_id
                 FROM loans l
                 WHERE l.id = ?
                 FOR UPDATE"
            );
            $loanStmt->execute([$loanId]);
            $loan = $loanStmt->fetch();

            if (!$loan) {
                $this->db->rollBack();
                Session::flash('error', 'No se encontró el préstamo.');
                return Response::redirect(BASE_URL . '/admin/loans');
            }

            if (!in_array((string) ($loan['status'] ?? ''), ['active', 'overdue', 'lost'], true)) {
                $this->db->rollBack();
                Session::flash('info', 'Solo puedes gestionar préstamos activos, vencidos o ya perdidos.');
                return Response::redirect(BASE_URL . '/admin/loans');
            }

            if ((string) ($loan['status'] ?? '') !== 'lost') {
                $lostStmt = $this->db->prepare(
                    "UPDATE loans
                     SET status = 'lost'
                     WHERE id = ?"
                );
                $lostStmt->execute([$loanId]);
            }

            $resourceStmt = $this->db->prepare(
                'SELECT replacement_cost, acquisition_price FROM resources WHERE id = ? LIMIT 1'
            );
            $resourceStmt->execute([(int) ($loan['resource_id'] ?? 0)]);
            $resource = $resourceStmt->fetch();
            $replacementCost = (float) ($resource['replacement_cost'] ?? 0);
            if ($replacementCost <= 0) {
                $replacementCost = (float) ($resource['acquisition_price'] ?? 0);
            }

            $existingFineStmt = $this->db->prepare(
                "SELECT id
                 FROM fines
                 WHERE loan_id = ? AND reason = 'loss'
                 LIMIT 1"
            );
            $existingFineStmt->execute([$loanId]);
            $existingFine = $existingFineStmt->fetch();

            if (!$existingFine && $replacementCost > 0) {
                $fineStmt = $this->db->prepare(
                    "INSERT INTO fines
                     (loan_id, user_id, amount, hours_overdue, replacement_cost_at_fine, reason, status, amount_paid, created_at, updated_at)
                     VALUES (?, ?, ?, 0, ?, 'loss', 'pending', 0, NOW(), NOW())"
                );
                $fineStmt->execute([
                    $loanId,
                    (int) ($loan['user_id'] ?? 0),
                    $replacementCost,
                    $replacementCost,
                ]);
                $fineCreated = true;
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        if ($fineCreated) {
            Session::flash('success', 'Préstamo marcado como perdido y multa generada por reposición.');
        } else {
            Session::flash('info', 'Préstamo perdido procesado. No se creó multa nueva (ya existía o no hay valor de reposición).');
        }
        return Response::redirect(BASE_URL . '/admin/loans');
    }
}
