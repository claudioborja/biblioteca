<?php
// app/Controllers/FineController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;
use Middleware\CsrfMiddleware;

final class FineController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function myFines(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $stmt = $this->db->prepare(
            "SELECT
                f.id,
                f.amount,
                f.amount_paid,
                f.reason,
                f.status,
                f.hours_overdue,
                f.created_at,
                l.id AS loan_ref,
                l.due_at,
                b.title AS book_title
             FROM fines f
             LEFT JOIN loans l ON l.id = f.loan_id
             LEFT JOIN resources b ON b.id = l.resource_id
             WHERE f.user_id = ?
             ORDER BY f.created_at DESC"
        );
        $stmt->execute([(int) $authUser['id']]);
        $fines = $stmt->fetchAll();

        $summary = [
            'pending' => 0.0,
            'paid' => 0.0,
            'total' => 0.0,
            'open_count' => 0,
        ];

        foreach ($fines as $fine) {
            $amount = (float) $fine['amount'];
            $paid = (float) $fine['amount_paid'];
            $summary['total'] += $amount;

            if (($fine['status'] ?? '') === 'paid') {
                $summary['paid'] += $amount;
            } else {
                $summary['pending'] += max(0.0, $amount - $paid);
                $summary['open_count']++;
            }
        }

        $settings = $this->panelSettings();

        return Response::html($this->view->render('account/fines', [
            'title' => 'Mis multas - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'fines' => $fines,
            'summary' => $summary,
            'currency' => '$',
        ], 'layouts/panel'));
    }

    public function index(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $search = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $reason = trim((string) $request->get('reason', ''));

        $sql = "SELECT
                    f.id,
                    f.amount,
                    f.amount_paid,
                    f.reason,
                    f.status,
                    f.hours_overdue,
                    f.waiver_reason,
                    f.created_at,
                    u.name AS user_name,
                    u.user_number,
                    l.id AS loan_ref,
                    b.title AS book_title
                FROM fines f
                JOIN users u ON u.id = f.user_id
                LEFT JOIN loans l ON l.id = f.loan_id
                LEFT JOIN resources b ON b.id = l.resource_id
                WHERE 1=1";

        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                        u.name LIKE :search
                        OR u.user_number LIKE :search
                        OR b.title LIKE :search
                        OR CAST(f.id AS CHAR) LIKE :search
                        OR CAST(COALESCE(l.id, 0) AS CHAR) LIKE :search
                    )";
            $params[':search'] = '%' . $search . '%';
        }

        if (in_array($status, ['pending', 'partially_paid', 'paid', 'waived'], true)) {
            $sql .= ' AND f.status = :status';
            $params[':status'] = $status;
        }

        if (in_array($reason, ['overdue', 'damage', 'loss'], true)) {
            $sql .= ' AND f.reason = :reason';
            $params[':reason'] = $reason;
        }

        $sql .= ' ORDER BY f.created_at DESC LIMIT 250';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $fines = $stmt->fetchAll();

        $stats = [
            'pending_count' => 0,
            'open_balance' => 0.0,
            'collected' => 0.0,
            'waived' => 0.0,
        ];

        foreach ($fines as $fine) {
            $amount = (float) $fine['amount'];
            $paid = (float) $fine['amount_paid'];
            $status = (string) ($fine['status'] ?? '');

            $stats['collected'] += $paid;

            if ($status === 'waived') {
                $stats['waived'] += max(0.0, $amount - $paid);
                continue;
            }

            if ($status !== 'paid') {
                $stats['pending_count']++;
                $stats['open_balance'] += max(0.0, $amount - $paid);
            }
        }

        $settings = $this->panelSettings();

        return Response::html($this->view->render('admin/fines/index', [
            'title' => 'Multas - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'fines' => $fines,
            'stats' => $stats,
            'currency' => '$',
            'csrf' => CsrfMiddleware::token(),
            'filters' => [
                'q' => $search,
                'status' => $status,
                'reason' => $reason,
            ],
        ], 'layouts/panel'));
    }

    public function recordPayment(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $fineId = (int) $id;
        if ($fineId <= 0) {
            Session::flash('error', 'Multa invalida.');
            return Response::redirect(BASE_URL . '/admin/fines');
        }

        $stmt = $this->db->prepare('SELECT id, amount, amount_paid, status FROM fines WHERE id = ? LIMIT 1');
        $stmt->execute([$fineId]);
        $fine = $stmt->fetch();

        if (!$fine) {
            Session::flash('error', 'La multa no existe.');
            return Response::redirect(BASE_URL . '/admin/fines');
        }

        if (($fine['status'] ?? '') === 'paid') {
            Session::flash('info', 'La multa ya esta pagada.');
            return Response::redirect(BASE_URL . '/admin/fines');
        }

        if (($fine['status'] ?? '') === 'waived') {
            Session::flash('info', 'La multa fue condonada y no admite pagos.');
            return Response::redirect(BASE_URL . '/admin/fines');
        }

        $rawAmount = trim((string) $request->post('amount', '0'));
        $payment = (float) $rawAmount;
        if ($payment <= 0) {
            Session::flash('error', 'El monto de pago debe ser mayor a cero.');
            return Response::redirect(BASE_URL . '/admin/fines');
        }

        $amount = (float) $fine['amount'];
        $amountPaid = (float) $fine['amount_paid'];
        $remaining = max(0.0, $amount - $amountPaid);
        $appliedPayment = min($remaining, $payment);
        $newPaid = $amountPaid + $appliedPayment;

        $newStatus = 'partially_paid';
        if ($newPaid <= 0.00001) {
            $newStatus = 'pending';
        }
        if ($newPaid >= ($amount - 0.00001)) {
            $newStatus = 'paid';
            $newPaid = $amount;
        }

        $update = $this->db->prepare('UPDATE fines SET amount_paid = ?, status = ?, updated_at = NOW() WHERE id = ?');
        $update->execute([$newPaid, $newStatus, $fineId]);

        Session::flash('success', 'Pago registrado correctamente. Monto aplicado: $' . number_format($appliedPayment, 2, '.', ','));
        return Response::redirect(BASE_URL . '/admin/fines');
    }

    public function waive(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $fineId = (int) $id;
        if ($fineId <= 0) {
            Session::flash('error', 'Multa invalida.');
            return Response::redirect(BASE_URL . '/admin/fines');
        }

        $stmt = $this->db->prepare('SELECT id, status FROM fines WHERE id = ? LIMIT 1');
        $stmt->execute([$fineId]);
        $fine = $stmt->fetch();

        if (!$fine) {
            Session::flash('error', 'La multa no existe.');
            return Response::redirect(BASE_URL . '/admin/fines');
        }

        if (($fine['status'] ?? '') === 'paid') {
            Session::flash('info', 'No se puede condonar una multa ya pagada.');
            return Response::redirect(BASE_URL . '/admin/fines');
        }

        if (($fine['status'] ?? '') === 'waived') {
            Session::flash('info', 'La multa ya fue condonada.');
            return Response::redirect(BASE_URL . '/admin/fines');
        }

        $reason = trim((string) $request->post('waiver_reason', 'Condonacion administrativa.'));
        if ($reason === '') {
            $reason = 'Condonacion administrativa.';
        }

        $update = $this->db->prepare('UPDATE fines SET status = ?, waiver_reason = ?, waived_by = ?, updated_at = NOW() WHERE id = ?');
        $update->execute(['waived', $reason, (int) $authUser['id'], $fineId]);

        Session::flash('success', 'Multa condonada correctamente.');
        return Response::redirect(BASE_URL . '/admin/fines');
    }
}
