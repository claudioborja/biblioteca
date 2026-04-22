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
use Services\PdfService;

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

        $successMessage = 'Reservacion creada correctamente. Tu posicion en fila es #' . $queuePosition . '.';
        if ((string) ($resource['support_type'] ?? '') === 'digital') {
            $successMessage .= ' Ya puedes leer el PDF desde el detalle del recurso.';
        }

        Session::flash('success', $successMessage);
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

    public function exportExcel(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $rows = $this->db->query(
            "SELECT
                r.id,
                r.status,
                r.queue_position,
                r.created_at,
                r.notified_at,
                r.expires_at,
                u.name AS user_name,
                COALESCE(u.user_number, '') AS user_number,
                b.title AS resource_title,
                COALESCE(b.isbn_13, '') AS resource_code
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN resources b ON b.id = r.resource_id
             ORDER BY r.created_at DESC"
        )->fetchAll();

        $libraryName = (string) ($this->panelSettings()['library_name'] ?? 'Biblioteca');
        $ecuadorNow = $this->ecuadorNow();
        $generatedAt = $ecuadorNow->format('d/m/Y H:i');
        $fileSuffix = $ecuadorNow->format('Ymd_His');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reservas');

        $headerFill = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']];
        $headerFont = ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10];
        $borderThin = ['style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D8E4']];
        $allBorders = ['allBorders' => $borderThin];

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', $libraryName . ' · Reporte de reservas · ' . $generatedAt);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF1E3A5F']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $headers = [
            'A' => 'Reserva',
            'B' => 'Usuario',
            'C' => 'N° Usuario',
            'D' => 'Recurso',
            'E' => 'ISBN/Código',
            'F' => 'Fila',
            'G' => 'Estado',
            'H' => 'Solicitada',
            'I' => 'Notificada/Expira',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . '2', $label);
        }
        $sheet->getStyle('A2:I2')->applyFromArray([
            'fill' => $headerFill,
            'font' => $headerFont,
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => $allBorders,
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        $dataRow = 3;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $dataRow, '#' . (int) ($row['id'] ?? 0));
            $sheet->setCellValue('B' . $dataRow, (string) ($row['user_name'] ?? ''));
            $sheet->setCellValue('C' . $dataRow, (string) ($row['user_number'] ?? ''));
            $sheet->setCellValue('D' . $dataRow, (string) ($row['resource_title'] ?? ''));
            $sheet->setCellValue('E' . $dataRow, (string) ($row['resource_code'] ?? ''));
            $sheet->setCellValue('F' . $dataRow, '#' . (int) ($row['queue_position'] ?? 0));
            $sheet->setCellValue('G' . $dataRow, $this->reservationStatusLabel((string) ($row['status'] ?? '')));
            $sheet->setCellValue('H' . $dataRow, (string) ($row['created_at'] ?? ''));
            $sheet->setCellValue('I' . $dataRow, $this->reservationTimelineValue($row));

            if ($dataRow % 2 === 0) {
                $sheet->getStyle('A' . $dataRow . ':I' . $dataRow)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF5F7FA'],
                    ],
                ]);
            }
            $sheet->getStyle('A' . $dataRow . ':I' . $dataRow)->applyFromArray(['borders' => $allBorders]);
            $dataRow++;
        }

        foreach ([
            'A' => 14,
            'B' => 28,
            'C' => 16,
            'D' => 42,
            'E' => 18,
            'F' => 10,
            'G' => 16,
            'H' => 20,
            'I' => 24,
        ] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $sheet->freezePane('A3');
        $lastRow = max($dataRow - 1, 2);
        $sheet->setAutoFilter('A2:I' . $lastRow);

        $spreadsheet->getProperties()
            ->setCreator($libraryName)
            ->setTitle('Reporte de reservas')
            ->setDescription('Generado el ' . $generatedAt);

        $filename = 'reservas_' . $fileSuffix . '.xlsx';
        ob_start();
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        $xlsx = ob_get_clean();

        return new Response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $rows = $this->db->query(
            "SELECT
                r.id,
                r.status,
                r.queue_position,
                r.created_at,
                r.notified_at,
                r.expires_at,
                u.name AS user_name,
                COALESCE(u.user_number, '') AS user_number,
                b.title AS resource_title
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN resources b ON b.id = r.resource_id
             ORDER BY r.created_at DESC"
        )->fetchAll();

        $libraryName = (string) ($this->panelSettings()['library_name'] ?? 'Biblioteca');
        $ecuadorNow = $this->ecuadorNow();
        $generatedAt = $ecuadorNow->format('d/m/Y H:i');
        $fileSuffix = $ecuadorNow->format('Ymd_His');
        $data = array_map(function (array $row): array {
            return [
                '#' . (int) ($row['id'] ?? 0),
                (string) ($row['user_name'] ?? ''),
                (string) ($row['user_number'] ?? ''),
                (string) ($row['resource_title'] ?? ''),
                '#' . (int) ($row['queue_position'] ?? 0),
                $this->reservationStatusLabel((string) ($row['status'] ?? '')),
                substr((string) ($row['created_at'] ?? ''), 0, 16),
                $this->reservationTimelineValue($row),
            ];
        }, $rows);

        $pdfService = new PdfService();
        $content = $pdfService->renderSimpleTableReport([
            'library' => $libraryName,
            'title' => 'Informe de Reservas',
            'subtitle' => 'Cola de reservas del sistema · Total: ' . count($rows),
            'headers' => ['Reserva', 'Usuario', 'N° Usuario', 'Recurso', 'Fila', 'Estado', 'Solicitada', 'Notificada/Expira'],
            'rows' => $data,
            'col_widths' => [18, 40, 24, 78, 14, 25, 28, 44],
            'orientation' => 'L',
            'generated_at' => $generatedAt,
            'generated_by' => (string) ($authUser['name'] ?? ''),
        ]);

        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reservas_' . $fileSuffix . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
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

    private function reservationStatusLabel(string $status): string
    {
        return match ($status) {
            'waiting' => 'Pendiente',
            'notified' => 'Notificada',
            'expired' => 'Expirada',
            'cancelled' => 'Cancelada',
            'fulfilled' => 'Completada',
            default => ucfirst($status),
        };
    }

    private function reservationTimelineValue(array $row): string
    {
        $notifiedAt = trim((string) ($row['notified_at'] ?? ''));
        $expiresAt = trim((string) ($row['expires_at'] ?? ''));

        if ($notifiedAt !== '' && $expiresAt !== '') {
            return $notifiedAt . ' / ' . $expiresAt;
        }
        if ($notifiedAt !== '') {
            return $notifiedAt;
        }
        if ($expiresAt !== '') {
            return $expiresAt;
        }
        return '-';
    }

    private function ecuadorNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('America/Guayaquil'));
    }
}
