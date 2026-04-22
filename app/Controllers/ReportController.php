<?php
// app/Controllers/ReportController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;
use Services\PdfService;

final class ReportController
{
    private \PDO $db;
    private View $view;
    private PdfService $pdf;

    public function __construct()
    {
        $this->db  = Database::connect();
        $this->view = new View(BASE_PATH . '/views');
        $this->pdf  = new PdfService();
    }

    // ── Overview ──────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $authUser = $this->auth();
        if ($authUser === null) return $this->redirectLogin();
        $settings = $this->settings();

        $summary = [
            'total_loans'   => (int)   $this->db->query('SELECT COUNT(*) FROM loans')->fetchColumn(),
            'active_loans'  => (int)   $this->db->query("SELECT COUNT(*) FROM loans WHERE status = 'active'")->fetchColumn(),
            'overdue_loans' => (int)   $this->db->query("SELECT COUNT(*) FROM loans WHERE status IN ('active','overdue') AND due_at < NOW()")->fetchColumn(),
            'total_fines'   => (float) $this->db->query('SELECT COALESCE(SUM(amount), 0) FROM fines')->fetchColumn(),
            'pending_fines' => (float) $this->db->query("SELECT COALESCE(SUM(amount - amount_paid), 0) FROM fines WHERE status IN ('pending','partially_paid')")->fetchColumn(),
            'users'         => (int)   $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
        ];

        $topBooks = $this->db->query(
            "SELECT b.title, COUNT(l.id) AS loans_count
             FROM loans l JOIN resources b ON b.id = l.resource_id
             GROUP BY b.id, b.title ORDER BY loans_count DESC LIMIT 6"
        )->fetchAll();

        $monthly = $this->db->query(
            "SELECT DATE_FORMAT(loan_at, '%Y-%m') AS month_key, COUNT(*) AS loans_count
             FROM loans WHERE loan_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY month_key ORDER BY month_key ASC"
        )->fetchAll();

        return Response::html($this->view->render('admin/reports/index', [
            'title'         => 'Reportes - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'      => $settings,
            'auth_user'     => $authUser,
            'summary'       => $summary,
            'top_books'     => $topBooks,
            'monthly_loans' => $monthly,
        ], 'layouts/panel'));
    }

    // ── Report pages ──────────────────────────────────────────────────────────

    public function loans(Request $request): Response
    {
        $authUser = $this->auth(); if ($authUser === null) return $this->redirectLogin();
        return Response::html($this->view->render('admin/reports/loans', [
            'title'    => 'Préstamos · Reportes',
            'settings' => $this->settings(),
            'auth_user'=> $authUser,
            'kpis'     => [
                'total'    => (int) $this->db->query('SELECT COUNT(*) FROM loans')->fetchColumn(),
                'active'   => (int) $this->db->query("SELECT COUNT(*) FROM loans WHERE status = 'active'")->fetchColumn(),
                'overdue'  => (int) $this->db->query("SELECT COUNT(*) FROM loans WHERE status IN ('active','overdue') AND due_at < NOW()")->fetchColumn(),
                'returned' => (int) $this->db->query("SELECT COUNT(*) FROM loans WHERE status = 'returned'")->fetchColumn(),
            ],
        ], 'layouts/panel'));
    }

    public function inventory(Request $request): Response
    {
        $authUser = $this->auth(); if ($authUser === null) return $this->redirectLogin();
        return Response::html($this->view->render('admin/reports/inventory', [
            'title'    => 'Inventario · Reportes',
            'settings' => $this->settings(),
            'auth_user'=> $authUser,
            'kpis'     => [
                'total'     => (int) $this->db->query("SELECT COUNT(*) FROM resources WHERE is_active = 1")->fetchColumn(),
                'available' => (int) $this->db->query("SELECT COALESCE(SUM(available_copies),0) FROM resources WHERE is_active = 1")->fetchColumn(),
                'on_loan'   => (int) $this->db->query("SELECT COALESCE(SUM(total_copies - available_copies),0) FROM resources WHERE is_active = 1")->fetchColumn(),
                'inactive'  => (int) $this->db->query("SELECT COUNT(*) FROM resources WHERE is_active = 0")->fetchColumn(),
            ],
        ], 'layouts/panel'));
    }

    public function users(Request $request): Response
    {
        $authUser = $this->auth(); if ($authUser === null) return $this->redirectLogin();
        return Response::html($this->view->render('admin/reports/users', [
            'title'    => 'Usuarios · Reportes',
            'settings' => $this->settings(),
            'auth_user'=> $authUser,
            'kpis'     => [
                'total'             => (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
                'active'            => (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role='user' AND status='active'")->fetchColumn(),
                'with_active_loan'  => (int) $this->db->query("SELECT COUNT(DISTINCT user_id) FROM loans WHERE status='active'")->fetchColumn(),
                'with_pending_fine' => (int) $this->db->query("SELECT COUNT(DISTINCT user_id) FROM fines WHERE status IN ('pending','partially_paid')")->fetchColumn(),
            ],
        ], 'layouts/panel'));
    }

    public function fines(Request $request): Response
    {
        $authUser = $this->auth(); if ($authUser === null) return $this->redirectLogin();
        return Response::html($this->view->render('admin/reports/fines', [
            'title'    => 'Multas · Reportes',
            'settings' => $this->settings(),
            'auth_user'=> $authUser,
            'kpis'     => [
                'total_count'    => (int)   $this->db->query('SELECT COUNT(*) FROM fines')->fetchColumn(),
                'total_amount'   => (float) $this->db->query('SELECT COALESCE(SUM(amount),0) FROM fines')->fetchColumn(),
                'pending_amount' => (float) $this->db->query("SELECT COALESCE(SUM(amount-amount_paid),0) FROM fines WHERE status IN ('pending','partially_paid')")->fetchColumn(),
                'paid_amount'    => (float) $this->db->query("SELECT COALESCE(SUM(amount_paid),0) FROM fines WHERE status IN ('paid','partially_paid')")->fetchColumn(),
            ],
        ], 'layouts/panel'));
    }

    public function visits(Request $request): Response
    {
        $authUser = $this->auth(); if ($authUser === null) return $this->redirectLogin();
        $kpis   = $this->visitsKpis();
        $recent = $this->db->query(
            "SELECT
                COALESCE(u.name, 'Anónimo')  AS usuario,
                COALESCE(u.email, '—')        AS correo,
                COALESCE(u.role, 'guest')     AS rol,
                v.page                        AS pagina,
                v.ip_address                  AS ip,
                v.referer                     AS referencia,
                v.created_at
             FROM visits_log v
             LEFT JOIN users u ON u.id = v.user_id
             ORDER BY v.created_at DESC
             LIMIT 100"
        )->fetchAll();
        return Response::html($this->view->render('admin/reports/visits', [
            'title'    => 'Visitas · Reportes',
            'settings' => $this->settings(),
            'auth_user'=> $authUser,
            'kpis'     => $kpis,
            'recent'   => $recent,
        ], 'layouts/panel'));
    }

    // ── CSV exports ───────────────────────────────────────────────────────────

    public function exportLoansCsv(Request $request): Response
    {
        if ($this->auth() === null) return $this->redirectLogin();

        $rows = $this->db->query(
            "SELECT u.name AS usuario, u.user_number AS numero_usuario,
                    r.title AS recurso, COALESCE(r.isbn_13, '') AS codigo,
                    l.loan_at, l.due_at, l.returned_at, l.status
             FROM loans l
             JOIN users u ON u.id = l.user_id
             JOIN resources r ON r.id = l.resource_id
             ORDER BY l.loan_at DESC"
        )->fetchAll();

        $handle = fopen('php://temp', 'r+');
        fputs($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Usuario', 'N° Usuario', 'Recurso', 'ISBN/Código', 'Fecha préstamo', 'Fecha vencimiento', 'Fecha devolución', 'Estado']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['usuario'], $row['numero_usuario'], $row['recurso'], $row['codigo'],
                $row['loan_at'] ?? '', $row['due_at'] ?? '', $row['returned_at'] ?? '',
                $this->loanStatusLabel($row['status']),
            ]);
        }
        return $this->csvResponse($handle, 'prestamos');
    }

    public function exportInventoryCsv(Request $request): Response
    {
        if ($this->auth() === null) return $this->redirectLogin();

        $rows = $this->db->query(
            "SELECT COALESCE(r.isbn_13, '') AS codigo, r.title, r.authors, COALESCE(c.name,'Sin categoría') AS categoria,
                    COALESCE(NULLIF(r.resource_type, ''), r.support_type, 'other') AS tipo,
                    r.total_copies, r.available_copies,
                    GREATEST(0, r.total_copies - r.available_copies) AS en_prestamo,
                    COUNT(l.id) AS prestamos_totales, r.is_active
             FROM resources r
             LEFT JOIN categories c ON c.id = r.category_id
             LEFT JOIN loans l ON l.resource_id = r.id
             GROUP BY r.id, r.isbn_13, r.title, r.authors, c.name, r.resource_type, r.support_type, r.total_copies, r.available_copies, r.is_active
             ORDER BY r.title ASC"
        )->fetchAll();

        $handle = fopen('php://temp', 'r+');
        fputs($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Código', 'Título', 'Autor', 'Categoría', 'Tipo', 'Copias', 'Disponibles', 'En préstamo', 'Préstamos totales', 'Estado']);
        foreach ($rows as $r) {
            fputcsv($handle, [
                $r['codigo'], $r['title'], $this->formatAuthors($r['authors'] ?? ''), $r['categoria'],
                $r['tipo'], $r['total_copies'], $r['available_copies'],
                $r['en_prestamo'], $r['prestamos_totales'],
                $this->resourceStatusLabel($r['is_active'] ?? 0),
            ]);
        }
        return $this->csvResponse($handle, 'inventario');
    }

    public function exportUsersCsv(Request $request): Response
    {
        if ($this->auth() === null) return $this->redirectLogin();

        $rows = $this->db->query(
            "SELECT u.user_number, u.name, u.email, u.role, u.status, u.created_at,
                    COUNT(DISTINCT l.id) AS prestamos,
                    COUNT(DISTINCT f.id) AS multas_pendientes
             FROM users u
             LEFT JOIN loans l ON l.user_id = u.id
             LEFT JOIN fines f ON f.loan_id = l.id AND f.status IN ('pending','partially_paid')
             GROUP BY u.id, u.user_number, u.name, u.email, u.role, u.status, u.created_at
             ORDER BY u.name ASC"
        )->fetchAll();

        $handle = fopen('php://temp', 'r+');
        fputs($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['N° Usuario', 'Nombre', 'Correo', 'Rol', 'Estado', 'Préstamos', 'Multas pendientes', 'Fecha registro']);
        foreach ($rows as $r) {
            fputcsv($handle, [
                $r['user_number'], $r['name'], $r['email'],
                $this->roleLabel($r['role']),
                $r['status'] === 'active' ? 'Activo' : ucfirst($r['status']),
                $r['prestamos'], $r['multas_pendientes'],
                substr($r['created_at'] ?? '', 0, 10),
            ]);
        }
        return $this->csvResponse($handle, 'usuarios');
    }

    public function exportFinesCsv(Request $request): Response
    {
        if ($this->auth() === null) return $this->redirectLogin();

        $rows = $this->db->query(
            "SELECT u.name AS usuario, u.user_number,
                    r.title AS recurso,
                    f.amount, f.amount_paid,
                    GREATEST(0, f.amount - f.amount_paid) AS pendiente,
                    f.status, f.created_at
             FROM fines f
             JOIN loans l ON l.id = f.loan_id
             JOIN users u ON u.id = l.user_id
             JOIN resources r ON r.id = l.resource_id
             ORDER BY f.created_at DESC"
        )->fetchAll();

        $handle = fopen('php://temp', 'r+');
        fputs($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Usuario', 'N° Usuario', 'Recurso', 'Monto', 'Pagado', 'Pendiente', 'Estado', 'Fecha']);
        foreach ($rows as $r) {
            fputcsv($handle, [
                $r['usuario'], $r['user_number'], $r['recurso'],
                number_format((float)$r['amount'], 2, '.', ''),
                number_format((float)$r['amount_paid'], 2, '.', ''),
                number_format((float)$r['pendiente'], 2, '.', ''),
                $this->fineStatusLabel($r['status']),
                substr($r['created_at'] ?? '', 0, 10),
            ]);
        }
        return $this->csvResponse($handle, 'multas');
    }

    public function exportVisitsCsv(Request $request): Response
    {
        if ($this->auth() === null) return $this->redirectLogin();

        $rows        = $this->visitsRows();
        $libraryName = $this->libraryName();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Visitas');

        $headerFill = ['fillType'    => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor'  => ['argb' => 'FF1E3A5F']];
        $headerFont = ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10];
        $borderThin = ['style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                       'color' => ['argb' => 'FFD0D8E4']];
        $allBorders = ['allBorders' => $borderThin];

        // Fila 1 — título
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', $libraryName . ' · Reporte de visitas · ' . date('d/m/Y H:i'));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Fila 2 — cabeceras
        $headers = ['A' => 'Usuario', 'B' => 'Correo', 'C' => 'Rol',
                    'D' => 'Página',  'E' => 'IP',      'F' => 'Referencia', 'G' => 'Fecha y hora'];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . '2', $label);
        }
        $sheet->getStyle('A2:G2')->applyFromArray([
            'fill'      => $headerFill,
            'font'      => $headerFont,
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders'   => $allBorders,
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Filas de datos
        $rolLabels = ['admin' => 'Admin', 'librarian' => 'Bibliotecario',
                      'teacher' => 'Docente', 'user' => 'Socio', 'guest' => 'Anónimo'];
        $dataRow = 3;
        foreach ($rows as $r) {
            $sheet->setCellValue('A' . $dataRow, (string) ($r['usuario']    ?? ''));
            $sheet->setCellValue('B' . $dataRow, (string) ($r['correo']     ?? ''));
            $sheet->setCellValue('C' . $dataRow, $rolLabels[$r['rol'] ?? ''] ?? (string) ($r['rol'] ?? ''));
            $sheet->setCellValue('D' . $dataRow, (string) ($r['pagina']     ?? ''));
            $sheet->setCellValue('E' . $dataRow, (string) ($r['ip']         ?? ''));
            $sheet->setCellValue('F' . $dataRow, (string) ($r['referencia'] ?? ''));
            $sheet->setCellValue('G' . $dataRow, (string) ($r['created_at'] ?? ''));

            if ($dataRow % 2 === 0) {
                $sheet->getStyle('A' . $dataRow . ':G' . $dataRow)->applyFromArray([
                    'fill' => ['fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                               'startColor' => ['argb' => 'FFF5F7FA']],
                ]);
            }
            $sheet->getStyle('A' . $dataRow . ':G' . $dataRow)->applyFromArray(['borders' => $allBorders]);
            $dataRow++;
        }

        // Anchos
        foreach (['A' => 28, 'B' => 32, 'C' => 16, 'D' => 20, 'E' => 18, 'F' => 40, 'G' => 20] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        $sheet->freezePane('A3');
        $lastRow = max($dataRow - 1, 2);
        $sheet->setAutoFilter('A2:G' . $lastRow);

        $spreadsheet->getProperties()
            ->setCreator($libraryName)
            ->setTitle('Reporte de visitas')
            ->setDescription('Generado el ' . date('d/m/Y H:i'));

        $filename = 'visitas_' . date('Ymd_His') . '.xlsx';
        ob_start();
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        $xlsx = ob_get_clean();

        return new Response((string) $xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    // ── PDF exports ───────────────────────────────────────────────────────────

    public function exportLoansPdf(Request $request): Response
    {
        if ($this->auth() === null) return $this->redirectLogin();

        $rows = $this->db->query(
            "SELECT u.name, u.user_number, r.title,
                    l.loan_at, l.due_at, l.status
             FROM loans l
             JOIN users u ON u.id = l.user_id
             JOIN resources r ON r.id = l.resource_id
             ORDER BY l.loan_at DESC"
        )->fetchAll();

        $kpis = [
            'total'    => (int) $this->db->query('SELECT COUNT(*) FROM loans')->fetchColumn(),
            'active'   => (int) $this->db->query("SELECT COUNT(*) FROM loans WHERE status = 'active'")->fetchColumn(),
            'overdue'  => (int) $this->db->query("SELECT COUNT(*) FROM loans WHERE status IN ('active','overdue') AND due_at < NOW()")->fetchColumn(),
            'returned' => (int) $this->db->query("SELECT COUNT(*) FROM loans WHERE status = 'returned'")->fetchColumn(),
        ];

        // Serie de los últimos 14 días para incluir gráfico en el PDF
        $seriesStmt = $this->db->query(
            "SELECT DATE(loan_at) AS d, COUNT(*) AS n
             FROM loans
             WHERE loan_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
             GROUP BY DATE(loan_at)
             ORDER BY d ASC"
        );
        $seriesRaw = $seriesStmt->fetchAll();
        $seriesMap = [];
        foreach ($seriesRaw as $row) {
            $seriesMap[(string) ($row['d'] ?? '')] = (int) ($row['n'] ?? 0);
        }

        $chartLabels = [];
        $chartValues = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $chartLabels[] = date('d/m', strtotime($date));
            $chartValues[] = (int) ($seriesMap[$date] ?? 0);
        }

        $tcpdfPath = '/usr/share/php/tcpdf/tcpdf.php';
        if (!class_exists('TCPDF')) {
            if (!file_exists($tcpdfPath)) {
                throw new \RuntimeException('TCPDF no está disponible en el servidor.');
            }
            require_once $tcpdfPath;
        }

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator($this->libraryName());
        $pdf->SetAuthor($this->libraryName());
        $pdf->SetTitle('Informe de Préstamos');
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();

        // Header del reporte
        $pdf->SetFillColor(30, 58, 95);
        $pdf->Rect(0, 0, 297, 24, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('dejavusans', 'B', 13);
        $pdf->SetXY(12, 7);
        $pdf->Cell(190, 6, $this->libraryName() . ' · Informe de Préstamos', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(80, 6, 'Generado: ' . date('d/m/Y H:i'), 0, 0, 'R');

        // KPIs
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetY(29);
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->Cell(70, 7, 'Total acumulado: ' . number_format((int) ($kpis['total'] ?? 0)), 0, 0, 'L');
        $pdf->Cell(70, 7, 'Activos: ' . number_format((int) ($kpis['active'] ?? 0)), 0, 0, 'L');
        $pdf->Cell(90, 7, 'Vencidos: ' . number_format((int) ($kpis['overdue'] ?? 0)), 0, 0, 'L');
        $pdf->Cell(55, 7, 'Devueltos: ' . number_format((int) ($kpis['returned'] ?? 0)), 0, 1, 'R');

        // Gráfico de línea con área (14 días), igual al estilo del dashboard
        $chartX = 12.0;
        $chartY = 40.0;
        $chartW = 273.0;
        $chartH = 56.0;
        $innerPad = 5.0;
        $plotX = $chartX + $innerPad;
        $plotY = $chartY + 8;
        $plotW = $chartW - ($innerPad * 2);
        $plotH = $chartH - 16;

        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->RoundedRect($chartX, $chartY, $chartW, $chartH, 2.5, '1111', 'DF');
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->SetXY($chartX + 3, $chartY + 2);
        $pdf->Cell(120, 5, 'Tendencia de préstamos (últimos 14 días)', 0, 0, 'L');

        $maxValue = max(1, ...$chartValues);
        $countPoints = max(1, count($chartValues));
        $stepX = $countPoints > 1 ? ($plotW / ($countPoints - 1)) : 0;

        // Línea base
        $pdf->SetDrawColor(180, 190, 205);
        $pdf->Line($plotX, $plotY + $plotH, $plotX + $plotW, $plotY + $plotH);

        $points = [];
        foreach ($chartValues as $i => $value) {
            $x = $plotX + ($stepX * $i);
            $lineHeight = $maxValue > 0 ? ($value / $maxValue) * ($plotH - 6) : 0;
            $y = $plotY + $plotH - $lineHeight;
            $points[] = ['x' => $x, 'y' => $y];

            // Relleno vertical suave bajo la curva (simula área de la línea)
            $pdf->SetDrawColor(191, 219, 254);
            $pdf->Line($x, $plotY + $plotH, $x, $y);

            // Etiquetas de eje X (cada 2 días)
            if ($i % 2 === 0) {
                $pdf->SetFont('dejavusans', '', 6.5);
                $pdf->SetTextColor(100, 116, 139);
                $pdf->SetXY($x - 4, $plotY + $plotH + 1.2);
                $pdf->Cell(8, 4, $chartLabels[$i], 0, 0, 'C');
            }
        }

        // Línea principal
        $pdf->SetDrawColor(59, 130, 246);
        $pdf->SetLineWidth(1.2);
        for ($i = 1; $i < count($points); $i++) {
            $pdf->Line($points[$i - 1]['x'], $points[$i - 1]['y'], $points[$i]['x'], $points[$i]['y']);
        }

        // Puntos
        $pdf->SetFillColor(59, 130, 246);
        foreach ($points as $p) {
            $pdf->Circle($p['x'], $p['y'], 0.9, 0, 360, 'F');
        }
        $pdf->SetLineWidth(0.2);

        // Tabla de registros
        $pdf->SetY($chartY + $chartH + 5);
        $pdf->SetFont('dejavusans', 'B', 8);
        $pdf->SetFillColor(30, 58, 95);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(148, 163, 184);

        $headers = ['Usuario', 'N° Usuario', 'Recurso', 'Préstamo', 'Vencimiento', 'Estado'];
        $widths  = [52, 28, 96, 30, 30, 25];

        foreach ($headers as $i => $h) {
            $pdf->Cell($widths[$i], 8, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('dejavusans', '', 7.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetDrawColor(226, 232, 240);

        foreach ($rows as $ri => $r) {
            if ($pdf->GetY() > 190) {
                $pdf->AddPage();
                $pdf->SetFont('dejavusans', 'B', 8);
                $pdf->SetFillColor(30, 58, 95);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetDrawColor(148, 163, 184);
                foreach ($headers as $i => $h) {
                    $pdf->Cell($widths[$i], 8, $h, 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetFont('dejavusans', '', 7.5);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetDrawColor(226, 232, 240);
            }

            $pdf->SetFillColor($ri % 2 === 0 ? 255 : 246, $ri % 2 === 0 ? 255 : 248, $ri % 2 === 0 ? 255 : 252);
            $cells = [
                (string) ($r['name'] ?? ''),
                (string) ($r['user_number'] ?? ''),
                (string) ($r['title'] ?? ''),
                substr((string) ($r['loan_at'] ?? ''), 0, 10),
                substr((string) ($r['due_at'] ?? ''), 0, 10),
                $this->loanStatusLabel((string) ($r['status'] ?? '')),
            ];
            foreach ($cells as $i => $cell) {
                $maxLen = $i === 2 ? 70 : 45;
                $pdf->Cell($widths[$i], 6, mb_strimwidth($cell, 0, $maxLen, '…', 'UTF-8'), 1, 0, 'L', true);
            }
            $pdf->Ln();
        }

        $content = $pdf->Output('', 'S');
        return $this->pdfResponse($content, 'prestamos');
    }

    public function exportInventoryPdf(Request $request): Response
    {
        if ($this->auth() === null) return $this->redirectLogin();

        $rows = $this->db->query(
            "SELECT COALESCE(r.isbn_13, '') AS codigo, r.title, r.authors, COALESCE(c.name,'Sin categoría') AS categoria,
                    r.total_copies, r.available_copies,
                    GREATEST(0, r.total_copies - r.available_copies) AS en_prestamo, r.is_active
             FROM resources r
             LEFT JOIN categories c ON c.id = r.category_id
             ORDER BY r.title ASC"
        )->fetchAll();

        $data = array_map(fn($r) => [
            $r['codigo'], $r['title'], $this->formatAuthors($r['authors'] ?? ''), $r['categoria'],
            $r['total_copies'], $r['available_copies'], $r['en_prestamo'],
            $this->resourceStatusLabel($r['is_active'] ?? 0),
        ], $rows);

        $content = $this->pdf->renderSimpleTableReport([
            'library'      => $this->libraryName(),
            'title'        => 'Informe de Inventario',
            'subtitle'     => 'Catálogo general de recursos · Total: ' . count($rows),
            'headers'      => ['Código', 'Título', 'Autor', 'Categoría', 'Copias', 'Disp.', 'Prest.', 'Estado'],
            'rows'         => $data,
            'col_widths'   => [22, 60, 40, 32, 16, 16, 16, 18],
            'orientation'  => 'L',
            'generated_at' => date('d/m/Y H:i'),
        ]);
        return $this->pdfResponse($content, 'inventario');
    }

    public function exportUsersPdf(Request $request): Response
    {
        if ($this->auth() === null) return $this->redirectLogin();

        $rows = $this->db->query(
            "SELECT u.user_number, u.name, u.email, u.role, u.status, u.created_at,
                    COUNT(DISTINCT l.id) AS prestamos
             FROM users u
             LEFT JOIN loans l ON l.user_id = u.id
             GROUP BY u.id, u.user_number, u.name, u.email, u.role, u.status, u.created_at
             ORDER BY u.name ASC"
        )->fetchAll();

        $data = array_map(fn($r) => [
            $r['user_number'], $r['name'], $r['email'],
            $this->roleLabel($r['role']),
            $r['status'] === 'active' ? 'Activo' : ucfirst($r['status']),
            $r['prestamos'],
            substr($r['created_at'] ?? '', 0, 10),
        ], $rows);

        $content = $this->pdf->renderSimpleTableReport([
            'library'      => $this->libraryName(),
            'title'        => 'Informe de Usuarios',
            'subtitle'     => 'Padrón general de usuarios · Total: ' . count($rows),
            'headers'      => ['N° Usuario', 'Nombre', 'Correo', 'Rol', 'Estado', 'Préstamos', 'Registro'],
            'rows'         => $data,
            'col_widths'   => [24, 45, 55, 24, 18, 20, 24],
            'orientation'  => 'L',
            'generated_at' => date('d/m/Y H:i'),
        ]);
        return $this->pdfResponse($content, 'usuarios');
    }

    public function exportFinesPdf(Request $request): Response
    {
        if ($this->auth() === null) return $this->redirectLogin();

        $rows = $this->db->query(
            "SELECT u.name, u.user_number, r.title,
                    f.amount, f.amount_paid,
                    GREATEST(0, f.amount - f.amount_paid) AS pendiente,
                    f.status, f.created_at
             FROM fines f
             JOIN loans l ON l.id = f.loan_id
             JOIN users u ON u.id = l.user_id
             JOIN resources r ON r.id = l.resource_id
             ORDER BY f.created_at DESC"
        )->fetchAll();

        $data = array_map(fn($r) => [
            $r['name'], $r['user_number'], $r['title'],
            '$' . number_format((float)$r['amount'], 2),
            '$' . number_format((float)$r['amount_paid'], 2),
            '$' . number_format((float)$r['pendiente'], 2),
            $this->fineStatusLabel($r['status']),
            substr($r['created_at'] ?? '', 0, 10),
        ], $rows);

        $content = $this->pdf->renderSimpleTableReport([
            'library'      => $this->libraryName(),
            'title'        => 'Informe de Multas',
            'subtitle'     => 'Registro de multas y cobranzas · Total: ' . count($rows),
            'headers'      => ['Usuario', 'N° Usuario', 'Recurso', 'Monto', 'Pagado', 'Pendiente', 'Estado', 'Fecha'],
            'rows'         => $data,
            'col_widths'   => [38, 22, 55, 20, 20, 22, 22, 22],
            'orientation'  => 'L',
            'generated_at' => date('d/m/Y H:i'),
        ]);
        return $this->pdfResponse($content, 'multas');
    }

    public function exportVisitsPdf(Request $request): Response
    {
        if ($this->auth() === null) return $this->redirectLogin();

        $rows = $this->visitsRows();
        $kpis = $this->visitsKpis();

        // Serie de los últimos 14 días para incluir gráfico en el PDF
        $seriesStmt = $this->db->query(
            "SELECT DATE(created_at) AS d, COUNT(*) AS n
             FROM visits_log
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
             GROUP BY DATE(created_at)
             ORDER BY d ASC"
        );
        $seriesRaw = $seriesStmt->fetchAll();
        $seriesMap = [];
        foreach ($seriesRaw as $row) {
            $seriesMap[(string) ($row['d'] ?? '')] = (int) ($row['n'] ?? 0);
        }
        $chartLabels = [];
        $chartValues = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $chartLabels[] = date('d/m', strtotime($date));
            $chartValues[] = (int) ($seriesMap[$date] ?? 0);
        }

        $tcpdfPath = '/usr/share/php/tcpdf/tcpdf.php';
        if (!class_exists('TCPDF')) {
            if (!file_exists($tcpdfPath)) {
                throw new \RuntimeException('TCPDF no está disponible en el servidor.');
            }
            require_once $tcpdfPath;
        }

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator($this->libraryName());
        $pdf->SetAuthor($this->libraryName());
        $pdf->SetTitle('Informe de Visitas');
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();

        // Header del reporte
        $pdf->SetFillColor(30, 58, 95);
        $pdf->Rect(0, 0, 297, 24, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('dejavusans', 'B', 13);
        $pdf->SetXY(12, 7);
        $pdf->Cell(190, 6, $this->libraryName() . ' · Informe de Visitas', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(80, 6, 'Generado: ' . date('d/m/Y H:i'), 0, 0, 'R');

        // KPIs
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetY(29);
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->Cell(70, 7, 'Total acumulado: ' . number_format((int) ($kpis['total'] ?? 0)), 0, 0, 'L');
        $pdf->Cell(70, 7, 'Ultimos 30 días: ' . number_format((int) ($kpis['visits_30d'] ?? 0)), 0, 0, 'L');
        $pdf->Cell(90, 7, 'Visitantes únicos (30 d): ' . number_format((int) ($kpis['unique_users_30d'] ?? 0)), 0, 0, 'L');
        $pdf->Cell(55, 7, 'Hoy: ' . number_format((int) ($kpis['visits_today'] ?? 0)), 0, 1, 'R');

        // Gráfico de barras (14 días)
        $chartX = 12.0;
        $chartY = 40.0;
        $chartW = 273.0;
        $chartH = 56.0;
        $innerPad = 5.0;
        $plotX = $chartX + $innerPad;
        $plotY = $chartY + 8;
        $plotW = $chartW - ($innerPad * 2);
        $plotH = $chartH - 16;

        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->RoundedRect($chartX, $chartY, $chartW, $chartH, 2.5, '1111', 'DF');
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->SetXY($chartX + 3, $chartY + 2);
        $pdf->Cell(120, 5, 'Tendencia de visitas (últimos 14 días)', 0, 0, 'L');

        $maxValue = max(1, ...$chartValues);
        $countBars = max(1, count($chartValues));
        $slotW = $plotW / $countBars;
        $barW = max(2.5, $slotW * 0.6);

        // Línea base
        $pdf->SetDrawColor(180, 190, 205);
        $pdf->Line($plotX, $plotY + $plotH, $plotX + $plotW, $plotY + $plotH);

        foreach ($chartValues as $i => $value) {
            $x = $plotX + ($slotW * $i) + (($slotW - $barW) / 2);
            $barHeight = $maxValue > 0 ? ($value / $maxValue) * ($plotH - 6) : 0;
            $y = $plotY + $plotH - $barHeight;

            $pdf->SetFillColor(245, 158, 11);
            $pdf->Rect($x, $y, $barW, $barHeight, 'F');

            if ($i % 2 === 0) {
                $pdf->SetFont('dejavusans', '', 6.5);
                $pdf->SetTextColor(100, 116, 139);
                $pdf->SetXY($x - 2, $plotY + $plotH + 1.2);
                $pdf->Cell($barW + 4, 4, $chartLabels[$i], 0, 0, 'C');
            }
        }

        // Tabla de registros
        $pdf->SetY($chartY + $chartH + 5);
        $pdf->SetFont('dejavusans', 'B', 8);
        $pdf->SetFillColor(30, 58, 95);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(148, 163, 184);

        $headers = ['Usuario', 'Correo', 'Rol', 'Página', 'IP', 'Fecha y hora'];
        $widths  = [52, 60, 22, 58, 30, 40];

        foreach ($headers as $i => $h) {
            $pdf->Cell($widths[$i], 8, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('dejavusans', '', 7.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetDrawColor(226, 232, 240);

        foreach ($rows as $ri => $r) {
            if ($pdf->GetY() > 190) {
                $pdf->AddPage();
                $pdf->SetFont('dejavusans', 'B', 8);
                $pdf->SetFillColor(30, 58, 95);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetDrawColor(148, 163, 184);
                foreach ($headers as $i => $h) {
                    $pdf->Cell($widths[$i], 8, $h, 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetFont('dejavusans', '', 7.5);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetDrawColor(226, 232, 240);
            }

            $pdf->SetFillColor($ri % 2 === 0 ? 255 : 246, $ri % 2 === 0 ? 255 : 248, $ri % 2 === 0 ? 255 : 252);
            $cells = [
                (string) ($r['usuario'] ?? ''),
                (string) ($r['correo'] ?? ''),
                $this->roleLabel((string) ($r['rol'] ?? '')),
                (string) ($r['pagina'] ?? ''),
                (string) ($r['ip'] ?? ''),
                substr((string) ($r['created_at'] ?? ''), 0, 16),
            ];
            foreach ($cells as $i => $cell) {
                $pdf->Cell($widths[$i], 6, mb_strimwidth($cell, 0, 55, '…', 'UTF-8'), 1, 0, 'L', true);
            }
            $pdf->Ln();
        }

        $content = $pdf->Output('', 'S');
        return $this->pdfResponse($content, 'visitas');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function csvResponse(mixed $handle, string $name): Response
    {
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return new Response((string) $csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $name . '_' . date('Ymd_His') . '.csv"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function pdfResponse(string $content, string $name): Response
    {
        return new Response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $name . '_' . date('Ymd_His') . '.pdf"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function purgeVisits(Request $request): Response
    {
        $authUser = $this->auth();
        if ($authUser === null) return $this->redirectLogin();
        if (($authUser['role'] ?? '') !== 'admin') {
            Session::flash('error', 'No tienes permiso para realizar esta acción.');
            return Response::redirect(BASE_URL . '/admin/reports/visits');
        }

        $mode     = $request->post('mode', '');
        $days     = (int) $request->post('days', 0);
        $dateFrom = (string) $request->post('date_from', '');
        $dateTo   = (string) $request->post('date_to', '');

        try {
            $deleted = 0;
            if ($mode === 'all') {
                $stmt = $this->db->query("DELETE FROM visits_log");
                $deleted = $stmt->rowCount();

            } elseif ($mode === 'older' && $days > 0) {
                $stmt = $this->db->prepare(
                    "DELETE FROM visits_log WHERE created_at < DATE_SUB(NOW(), INTERVAL :d DAY)"
                );
                $stmt->execute([':d' => $days]);
                $deleted = $stmt->rowCount();

            } elseif ($mode === 'range' && $dateFrom !== '' && $dateTo !== '') {
                $stmt = $this->db->prepare(
                    "DELETE FROM visits_log WHERE DATE(created_at) BETWEEN :f AND :t"
                );
                $stmt->execute([':f' => $dateFrom, ':t' => $dateTo]);
                $deleted = $stmt->rowCount();

            } else {
                Session::flash('error', 'Opción de limpieza no válida.');
                return Response::redirect(BASE_URL . '/admin/reports/visits');
            }

            Session::flash('success', number_format($deleted) . ' registro(s) de visitas eliminado(s).');
        } catch (\Throwable $e) {
            Session::flash('error', 'Error al eliminar registros: ' . $e->getMessage());
        }

        return Response::redirect(BASE_URL . '/admin/reports/visits');
    }

    private function visitsKpis(): array
    {
        return [
            'total'            => (int) $this->db->query("SELECT COUNT(*) FROM visits_log")->fetchColumn(),
            'visits_30d'       => (int) $this->db->query("SELECT COUNT(*) FROM visits_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
            'unique_users_30d' => (int) $this->db->query("SELECT COUNT(DISTINCT COALESCE(CAST(user_id AS CHAR), ip_address)) FROM visits_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
            'visits_today'     => (int) $this->db->query("SELECT COUNT(*) FROM visits_log WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
            'last_visit'       => (string) ($this->db->query("SELECT created_at FROM visits_log ORDER BY created_at DESC LIMIT 1")->fetchColumn() ?: '—'),
        ];
    }

    private function visitsRows(): array
    {
        return $this->db->query(
            "SELECT
                COALESCE(u.name, 'Anónimo')      AS usuario,
                COALESCE(u.email, '—')            AS correo,
                COALESCE(u.role, 'guest')         AS rol,
                v.page                            AS pagina,
                v.ip_address                      AS ip,
                v.referer                         AS referencia,
                v.created_at
             FROM visits_log v
             LEFT JOIN users u ON u.id = v.user_id
             ORDER BY v.created_at DESC
             LIMIT 10000"
        )->fetchAll();
    }

    private function hasTable(string $table): bool
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = '" . addslashes($table) . "'"
        )->fetchColumn() > 0;
    }

    private function libraryName(): string
    {
        return (string) ($this->settings()['library_name'] ?? 'Biblioteca');
    }

    private function formatAuthors(mixed $authors): string
    {
        if (is_array($authors)) {
            return implode(', ', array_map(static fn(mixed $v): string => trim((string) $v), $authors));
        }

        $text = trim((string) $authors);
        if ($text === '') {
            return '';
        }

        if (str_starts_with($text, '[')) {
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                return implode(', ', array_map(static fn(mixed $v): string => trim((string) $v), $decoded));
            }
        }

        return $text;
    }

    private function resourceStatusLabel(mixed $isActive): string
    {
        return (int) $isActive === 1 ? 'Activo' : 'Inactivo';
    }

    private function loanStatusLabel(string $s): string
    {
        return match($s) { 'active' => 'Activo', 'overdue' => 'Vencido', 'returned' => 'Devuelto', 'lost' => 'Perdido', default => ucfirst($s) };
    }

    private function fineStatusLabel(string $s): string
    {
        return match($s) { 'paid' => 'Pagada', 'pending' => 'Pendiente', 'partially_paid' => 'Parcial', 'waived' => 'Condonada', default => ucfirst($s) };
    }

    private function roleLabel(string $r): string
    {
        return match($r) { 'admin' => 'Administrador', 'librarian' => 'Bibliotecario', 'teacher' => 'Docente', 'user' => 'Socio', default => ucfirst($r) };
    }

    private function settings(): array
    {
        return $this->db->query(
            "SELECT `key`, value FROM system_settings WHERE `key` IN ('library_name','library_logo','library_favicon')"
        )->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    private function auth(): ?array
    {
        $userId = (int) Session::get('auth.user_id');
        if ($userId <= 0) return null;
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) return null;
        return [
            'id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'],
            'role' => $user['role'], 'user_number' => $user['user_number'],
            'last_login_at' => $user['last_login_at'], 'created_at' => $user['created_at'],
            'status' => $user['status'],
        ];
    }

    private function redirectLogin(): Response
    {
        Session::destroy();
        return Response::redirect(BASE_URL . '/login');
    }
}
