---
name: skill-php-reports
description: "**WORKFLOW SKILL** — Professional reports, dashboards and data exports in pure PHP for library systems. USE FOR: admin dashboard with KPIs and statistics (active loans, overdue books, fines collected, catalog size, top books); data aggregation queries with MariaDB (GROUP BY, window functions, date ranges); SVG charts without JavaScript libraries (bar charts, line charts, pie/donut charts rendered server-side); CSV export with fputcsv (native PHP); Excel-compatible export via HTML table with correct MIME type; printable reports with CSS @media print; date range filtering for reports; top borrowed books, most active members, category statistics; overdue and fine reports; inventory reports; monthly/yearly trends; report caching to avoid heavy queries on every load. DO NOT USE FOR: real-time dashboards with WebSockets; Chart.js or D3.js (requires JS skill); PDF reports (covered in skill-php-files)."
---

# PHP Reports — Dashboard, Statistics & Data Exports

## Core Philosophy

- **SQL does the aggregation**: Never pull thousands of rows to PHP and aggregate in memory — use GROUP BY, SUM, COUNT at the DB layer.
- **Cache heavy reports**: Dashboard stats change slowly — cache for 5–15 minutes; don't re-run complex queries on every page load.
- **SVG charts server-side**: Pure SVG from PHP requires zero JS libraries and works in PDF, email, and print.
- **CSV is universal**: Every report must be exportable as CSV — it opens in Excel, LibreOffice, and every data tool.
- **Print-ready by default**: `@media print` CSS ensures any report page prints cleanly.

---

## Report Queries (MariaDB)

```php
<?php
// src/Repositories/ReportRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class ReportRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ── Dashboard KPIs ───────────────────────────────────────────────────────

    public function dashboardKpis(): array
    {
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM books      WHERE deleted_at IS NULL)            AS total_books,
                (SELECT SUM(copies) FROM books   WHERE deleted_at IS NULL)            AS total_copies,
                (SELECT SUM(available) FROM books WHERE deleted_at IS NULL)           AS available_copies,
                (SELECT COUNT(*) FROM loans      WHERE status = 'active')             AS active_loans,
                (SELECT COUNT(*) FROM loans
                    WHERE status = 'active' AND due_date < CURDATE())                 AS overdue_loans,
                (SELECT COUNT(*) FROM loans      WHERE DATE(loan_date) = CURDATE())   AS loans_today,
                (SELECT COUNT(*) FROM loans      WHERE DATE(return_date) = CURDATE()) AS returns_today,
                (SELECT COUNT(*) FROM users      WHERE deleted_at IS NULL
                    AND role = 'member')                                              AS total_members,
                (SELECT COUNT(*) FROM users      WHERE deleted_at IS NULL
                    AND role = 'member' AND is_active = 1)                            AS active_members,
                (SELECT COALESCE(SUM(fine_amount), 0) FROM loans
                    WHERE fine_paid = 0 AND fine_amount > 0)                         AS pending_fines,
                (SELECT COALESCE(SUM(fine_amount), 0) FROM loans
                    WHERE fine_paid = 1
                    AND MONTH(updated_at) = MONTH(NOW())
                    AND YEAR(updated_at) = YEAR(NOW()))                              AS fines_collected_month,
                (SELECT COUNT(*) FROM reservations WHERE status = 'pending')          AS pending_reservations
        ";

        return $this->db->query($sql)->fetch() ?: [];
    }

    // ── Loan trends ──────────────────────────────────────────────────────────

    public function loansByDay(int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT
                DATE(loan_date)   AS day,
                COUNT(*)          AS loans,
                SUM(status = 'returned') AS returns
            FROM loans
            WHERE loan_date >= CURDATE() - INTERVAL ? DAY
            GROUP BY DATE(loan_date)
            ORDER BY day ASC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function loansByMonth(int $months = 12): array
    {
        $stmt = $this->db->prepare("
            SELECT
                DATE_FORMAT(loan_date, '%Y-%m') AS month,
                COUNT(*)                        AS loans,
                SUM(status = 'returned')        AS returns,
                COALESCE(SUM(fine_amount), 0)   AS fines
            FROM loans
            WHERE loan_date >= CURDATE() - INTERVAL ? MONTH
            GROUP BY DATE_FORMAT(loan_date, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute([$months]);
        return $stmt->fetchAll();
    }

    // ── Top books ────────────────────────────────────────────────────────────

    public function topBorrowedBooks(int $limit = 10, string $since = '-30 days'): array
    {
        $stmt = $this->db->prepare("
            SELECT
                b.id,
                b.title,
                b.author,
                c.name      AS category,
                COUNT(l.id) AS loan_count
            FROM loans l
            JOIN books b      ON b.id = l.book_id
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE l.loan_date >= ?
            GROUP BY b.id, b.title, b.author, c.name
            ORDER BY loan_count DESC
            LIMIT ?
        ");
        $stmt->execute([date('Y-m-d', strtotime($since)), $limit]);
        return $stmt->fetchAll();
    }

    public function neverBorrowedBooks(): array
    {
        return $this->db->query("
            SELECT b.id, b.title, b.author, b.isbn, b.year
            FROM books b
            LEFT JOIN loans l ON l.book_id = b.id
            WHERE b.deleted_at IS NULL AND l.id IS NULL
            ORDER BY b.title
        ")->fetchAll();
    }

    // ── Member statistics ────────────────────────────────────────────────────

    public function topActiveMembers(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.name,
                u.email,
                u.member_number,
                COUNT(l.id)                    AS total_loans,
                SUM(l.status = 'active')       AS active_loans,
                COALESCE(SUM(l.fine_amount), 0) AS total_fines
            FROM users u
            JOIN loans l ON l.user_id = u.id
            WHERE u.role = 'member'
            GROUP BY u.id, u.name, u.email, u.member_number
            ORDER BY total_loans DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function membersWithOverdueLoans(): array
    {
        return $this->db->query("
            SELECT
                u.id,
                u.name,
                u.email,
                u.member_number,
                COUNT(l.id)                      AS overdue_count,
                COALESCE(SUM(l.fine_amount), 0)  AS total_fine,
                MIN(l.due_date)                  AS oldest_due_date,
                MAX(DATEDIFF(CURDATE(), l.due_date)) AS max_days_overdue
            FROM loans l
            JOIN users u ON u.id = l.user_id
            WHERE l.status = 'active'
              AND l.due_date < CURDATE()
            GROUP BY u.id, u.name, u.email, u.member_number
            ORDER BY total_fine DESC, overdue_count DESC
        ")->fetchAll();
    }

    // ── Category statistics ──────────────────────────────────────────────────

    public function booksByCategory(): array
    {
        return $this->db->query("
            SELECT
                c.id,
                c.name,
                COUNT(b.id)        AS book_count,
                SUM(b.copies)      AS total_copies,
                SUM(b.available)   AS available_copies,
                COUNT(l.id)        AS total_loans
            FROM categories c
            LEFT JOIN books b  ON b.category_id = c.id AND b.deleted_at IS NULL
            LEFT JOIN loans l  ON l.book_id = b.id
            GROUP BY c.id, c.name
            ORDER BY total_loans DESC
        ")->fetchAll();
    }

    // ── Fine reports ─────────────────────────────────────────────────────────

    public function fineReport(string $from, string $to): array
    {
        $stmt = $this->db->prepare("
            SELECT
                l.id                AS loan_id,
                u.name              AS member_name,
                u.member_number,
                b.title             AS book_title,
                l.due_date,
                l.return_date,
                DATEDIFF(COALESCE(l.return_date, CURDATE()), l.due_date) AS days_overdue,
                l.fine_amount,
                l.fine_paid,
                l.updated_at        AS last_updated
            FROM loans l
            JOIN users u ON u.id = l.user_id
            JOIN books b ON b.id = l.book_id
            WHERE l.fine_amount > 0
              AND l.loan_date BETWEEN ? AND ?
            ORDER BY l.fine_amount DESC
        ");
        $stmt->execute([$from, $to]);
        return $stmt->fetchAll();
    }

    // ── Inventory ────────────────────────────────────────────────────────────

    public function inventorySummary(): array
    {
        return $this->db->query("
            SELECT
                b.id,
                b.isbn,
                b.title,
                b.author,
                c.name          AS category,
                b.year,
                b.copies,
                b.available,
                (b.copies - b.available) AS on_loan,
                COUNT(l.id)              AS total_loans_ever
            FROM books b
            LEFT JOIN categories c ON c.id = b.category_id
            LEFT JOIN loans l      ON l.book_id = b.id
            WHERE b.deleted_at IS NULL
            GROUP BY b.id, b.isbn, b.title, b.author, c.name, b.year, b.copies, b.available
            ORDER BY b.title
        ")->fetchAll();
    }
}
```

---

## SVG Chart Generator (Zero JS)

```php
<?php
// src/Services/ChartService.php
declare(strict_types=1);

namespace Services;

final class ChartService
{
    /**
     * Horizontal bar chart in SVG
     * @param array $data  [['label' => '...', 'value' => N], ...]
     */
    public function barChart(
        array  $data,
        int    $width   = 600,
        string $color   = '#1e3a5f',
        string $title   = '',
    ): string {
        if (empty($data)) return '';

        $max       = max(array_column($data, 'value')) ?: 1;
        $barHeight = 28;
        $labelW    = 160;
        $barAreaW  = $width - $labelW - 60;
        $height    = count($data) * ($barHeight + 6) + 40 + ($title ? 30 : 0);
        $offsetY   = $title ? 35 : 10;

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" role="img" aria-label="%s">',
            $width, $height, htmlspecialchars($title),
        );

        if ($title) {
            $svg .= sprintf(
                '<text x="%d" y="20" font-family="sans-serif" font-size="14" font-weight="bold" fill="#333">%s</text>',
                $width / 2, htmlspecialchars($title),
            );
        }

        foreach ($data as $i => $item) {
            $y      = $offsetY + $i * ($barHeight + 6);
            $barW   = (int) round($item['value'] / $max * $barAreaW);
            $label  = mb_substr($item['label'], 0, 28);
            $value  = number_format($item['value']);

            // Label
            $svg .= sprintf(
                '<text x="%d" y="%d" text-anchor="end" font-family="sans-serif" font-size="12" fill="#555">%s</text>',
                $labelW - 6, $y + 19, htmlspecialchars($label),
            );

            // Bar background
            $svg .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" rx="3" fill="#e8ecf0"/>',
                $labelW, $y, $barAreaW, $barHeight,
            );

            // Bar fill
            if ($barW > 0) {
                $svg .= sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d" rx="3" fill="%s"/>',
                    $labelW, $y, $barW, $barHeight, $color,
                );
            }

            // Value label
            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="sans-serif" font-size="11" fill="#fff" font-weight="bold">%s</text>',
                $labelW + max($barW - 4, 4), $y + 18, $barW > 30 ? $value : '',
            );

            if ($barW <= 30) {
                $svg .= sprintf(
                    '<text x="%d" y="%d" font-family="sans-serif" font-size="11" fill="#333">%s</text>',
                    $labelW + $barW + 4, $y + 18, $value,
                );
            }
        }

        $svg .= '</svg>';
        return $svg;
    }

    /**
     * Donut / pie chart in SVG
     * @param array $data [['label' => '...', 'value' => N, 'color' => '#hex'], ...]
     */
    public function donutChart(array $data, int $size = 200, string $title = ''): string
    {
        if (empty($data)) return '';

        $total     = array_sum(array_column($data, 'value')) ?: 1;
        $cx        = $size / 2;
        $cy        = $size / 2;
        $r         = $size * 0.38;
        $innerR    = $size * 0.22;
        $colors    = ['#1e3a5f','#2d6a9f','#4a9eda','#7bc4e8','#b8ddf4','#d4a017','#8b4513','#2e8b57'];

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" role="img">',
            $size + 180, $size + ($title ? 30 : 0),
        );

        $startAngle = -90;
        $legendY    = $title ? 45 : 15;

        if ($title) {
            $svg .= sprintf(
                '<text x="%d" y="20" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#333">%s</text>',
                $cx, htmlspecialchars($title),
            );
        }

        $offsetY = $title ? 30 : 0;

        foreach ($data as $i => $item) {
            $pct   = $item['value'] / $total;
            $angle = $pct * 360;
            $color = $item['color'] ?? $colors[$i % count($colors)];

            $endAngle   = $startAngle + $angle;
            $x1 = $cx + $r * cos(deg2rad($startAngle));
            $y1 = $cy + $r * sin(deg2rad($startAngle)) + $offsetY;
            $x2 = $cx + $r * cos(deg2rad($endAngle));
            $y2 = $cy + $r * sin(deg2rad($endAngle)) + $offsetY;
            $ix1 = $cx + $innerR * cos(deg2rad($startAngle));
            $iy1 = $cy + $innerR * sin(deg2rad($startAngle)) + $offsetY;
            $ix2 = $cx + $innerR * cos(deg2rad($endAngle));
            $iy2 = $cy + $innerR * sin(deg2rad($endAngle)) + $offsetY;

            $large = $angle > 180 ? 1 : 0;

            $svg .= sprintf(
                '<path d="M %.2f %.2f A %.2f %.2f 0 %d 1 %.2f %.2f L %.2f %.2f A %.2f %.2f 0 %d 0 %.2f %.2f Z" fill="%s" opacity="0.9"/>',
                $x1, $y1, $r, $r, $large, $x2, $y2,
                $ix2, $iy2, $innerR, $innerR, $large, $ix1, $iy1,
                $color,
            );

            // Legend
            $svg .= sprintf(
                '<rect x="%d" y="%d" width="12" height="12" rx="2" fill="%s"/>',
                $size + 10, $legendY + $i * 20 + $offsetY, $color,
            );
            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="sans-serif" font-size="11" fill="#444">%s (%.1f%%)</text>',
                $size + 26, $legendY + $i * 20 + 10 + $offsetY,
                htmlspecialchars(mb_substr($item['label'], 0, 20)),
                $pct * 100,
            );

            $startAngle = $endAngle;
        }

        // Center label
        $svg .= sprintf(
            '<text x="%d" y="%d" text-anchor="middle" font-family="sans-serif" font-size="11" fill="#666">Total</text>',
            $cx, $cy + $offsetY - 5,
        );
        $svg .= sprintf(
            '<text x="%d" y="%d" text-anchor="middle" font-family="sans-serif" font-size="16" font-weight="bold" fill="#333">%s</text>',
            $cx, $cy + $offsetY + 14, number_format($total),
        );

        $svg .= '</svg>';
        return $svg;
    }

    /**
     * Line chart for trends (loans over time)
     * @param array $data [['label' => 'Jan', 'value' => 45], ...]
     */
    public function lineChart(array $data, int $width = 600, int $height = 200, string $title = ''): string
    {
        if (count($data) < 2) return '';

        $max     = max(array_column($data, 'value')) ?: 1;
        $padL    = 45;
        $padR    = 15;
        $padT    = $title ? 35 : 15;
        $padB    = 30;
        $plotW   = $width  - $padL - $padR;
        $plotH   = $height - $padT - $padB;
        $n       = count($data);
        $stepX   = $plotW / ($n - 1);
        $color   = '#1e3a5f';
        $fill    = '#b8ddf4';

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" role="img">',
            $width, $height,
        );

        if ($title) {
            $svg .= sprintf(
                '<text x="%d" y="20" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#333">%s</text>',
                $width / 2, htmlspecialchars($title),
            );
        }

        // Grid lines
        for ($g = 0; $g <= 4; $g++) {
            $gy = $padT + $plotH - ($g / 4 * $plotH);
            $gv = (int) round($max * $g / 4);
            $svg .= sprintf('<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" stroke="#e0e0e0" stroke-width="1"/>', $padL, $gy, $padL + $plotW, $gy);
            $svg .= sprintf('<text x="%d" y="%.1f" text-anchor="end" font-family="sans-serif" font-size="10" fill="#999">%s</text>', $padL - 4, $gy + 4, $gv);
        }

        // Build points
        $points = [];
        foreach ($data as $i => $item) {
            $x = $padL + $i * $stepX;
            $y = $padT + $plotH - ($item['value'] / $max * $plotH);
            $points[] = [$x, $y, $item['label'], $item['value']];
        }

        // Fill area
        $fillPath = "M {$points[0][0]} " . ($padT + $plotH);
        foreach ($points as [$x, $y]) {
            $fillPath .= " L {$x} {$y}";
        }
        $fillPath .= ' L ' . end($points)[0] . ' ' . ($padT + $plotH) . ' Z';
        $svg .= sprintf('<path d="%s" fill="%s" opacity="0.3"/>', $fillPath, $fill);

        // Line
        $linePath = "M {$points[0][0]} {$points[0][1]}";
        foreach (array_slice($points, 1) as [$x, $y]) {
            $linePath .= " L {$x} {$y}";
        }
        $svg .= sprintf('<path d="%s" fill="none" stroke="%s" stroke-width="2.5"/>', $linePath, $color);

        // Dots and labels
        foreach ($points as [$x, $y, $label, $value]) {
            $svg .= sprintf('<circle cx="%.1f" cy="%.1f" r="4" fill="%s"/>', $x, $y, $color);
            $svg .= sprintf(
                '<text x="%.1f" y="%d" text-anchor="middle" font-family="sans-serif" font-size="10" fill="#777">%s</text>',
                $x, $padT + $plotH + 18, htmlspecialchars($label),
            );
        }

        $svg .= '</svg>';
        return $svg;
    }
}
```

---

## CSV Export

```php
<?php
// src/Services/ExportService.php
declare(strict_types=1);

namespace Services;

final class ExportService
{
    /**
     * Stream CSV directly to browser (no temp file needed)
     *
     * @param string   $filename  Without extension
     * @param array    $headers   Column headers
     * @param iterable $rows      Data rows (arrays)
     */
    public static function csv(string $filename, array $headers, iterable $rows): never
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');

        // UTF-8 BOM for Excel compatibility
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, $headers, ',', '"', '\\');

        foreach ($rows as $row) {
            fputcsv($out, $row, ',', '"', '\\');
        }

        fclose($out);
        exit;
    }

    /**
     * Excel-compatible export (HTML table with Excel MIME)
     * Opens in Excel without any library
     */
    public static function excel(string $filename, array $headers, array $rows, string $sheetName = 'Reporte'): never
    {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Cache-Control: no-store');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
               xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        echo '<Worksheet ss:Name="' . htmlspecialchars($sheetName) . '"><Table>';

        // Header row
        echo '<Row>';
        foreach ($headers as $h) {
            echo '<Cell ss:StyleID="header"><Data ss:Type="String">'
                . htmlspecialchars((string) $h) . '</Data></Cell>';
        }
        echo '</Row>';

        // Data rows
        foreach ($rows as $row) {
            echo '<Row>';
            foreach ($row as $cell) {
                $type = is_numeric($cell) ? 'Number' : 'String';
                echo '<Cell><Data ss:Type="' . $type . '">'
                    . htmlspecialchars((string) $cell) . '</Data></Cell>';
            }
            echo '</Row>';
        }

        echo '</Table></Worksheet></Workbook>';
        exit;
    }
}
```

---

## Report Service (Orchestration + Cache)

```php
<?php
// src/Services/ReportService.php
declare(strict_types=1);

namespace Services;

use Cache\CacheInterface;
use Repositories\ReportRepository;

final class ReportService
{
    public function __construct(
        private readonly ReportRepository $repo,
        private readonly CacheInterface   $cache,
        private readonly ChartService     $charts,
    ) {}

    public function dashboard(): array
    {
        return $this->cache->remember('reports:dashboard:kpis', 600, function () {
            $kpis          = $this->repo->dashboardKpis();
            $loansByDay    = $this->repo->loansByDay(30);
            $topBooks      = $this->repo->topBorrowedBooks(8);
            $byCategory    = $this->repo->booksByCategory();

            // Pre-render charts as SVG strings
            $charts = [
                'loans_trend' => $this->charts->lineChart(
                    array_map(fn($r) => [
                        'label' => date('d/m', strtotime($r['day'])),
                        'value' => (int) $r['loans'],
                    ], $loansByDay),
                    title: 'Préstamos últimos 30 días',
                ),
                'top_books' => $this->charts->barChart(
                    array_map(fn($b) => [
                        'label' => mb_substr($b['title'], 0, 30),
                        'value' => (int) $b['loan_count'],
                    ], $topBooks),
                    title: 'Libros más prestados',
                ),
                'by_category' => $this->charts->donutChart(
                    array_map(fn($c) => [
                        'label' => $c['name'],
                        'value' => (int) $c['total_loans'],
                    ], array_filter($byCategory, fn($c) => $c['total_loans'] > 0)),
                    title: 'Préstamos por categoría',
                ),
            ];

            return compact('kpis', 'loansByDay', 'topBooks', 'byCategory', 'charts');
        });
    }

    public function overdueReport(): array
    {
        return $this->cache->remember('reports:overdue', 300, function () {
            return $this->repo->membersWithOverdueLoans();
        });
    }

    public function fineReport(string $from, string $to): array
    {
        return $this->repo->fineReport($from, $to);
    }

    public function inventoryReport(): array
    {
        return $this->cache->remember('reports:inventory', 900, function () {
            return $this->repo->inventorySummary();
        });
    }

    public function monthlyTrend(): array
    {
        return $this->cache->remember('reports:monthly:12', 3600, function () {
            $data = $this->repo->loansByMonth(12);
            return [
                'data'  => $data,
                'chart' => $this->charts->lineChart(
                    array_map(fn($r) => [
                        'label' => date('M', strtotime($r['month'] . '-01')),
                        'value' => (int) $r['loans'],
                    ], $data),
                    title: 'Tendencia anual de préstamos',
                ),
            ];
        });
    }
}
```

---

## Report Controller

```php
<?php
// src/Controllers/ReportController.php
declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;
use Core\View;
use Auth\AuthService;
use Auth\Permission;
use Services\ReportService;
use Services\ExportService;

final class ReportController
{
    public function __construct(
        private readonly AuthService   $auth,
        private readonly ReportService $reports,
        private readonly View          $view,
    ) {}

    /** GET /admin/dashboard */
    public function dashboard(): void
    {
        $this->auth->requirePermission(Permission::ReportsView);
        $data = $this->reports->dashboard();
        echo $this->view->render('admin/dashboard', $data);
    }

    /** GET /admin/reports/overdue */
    public function overdue(Request $request): void
    {
        $this->auth->requirePermission(Permission::ReportsView);
        $rows = $this->reports->overdueReport();

        if ($request->string('export') === 'csv') {
            ExportService::csv('prestamos_vencidos_' . date('Ymd'), [
                'Socio', 'Nº Socio', 'Email', 'Vencidos', 'Multa total', 'Días máx.',
            ], array_map(fn($r) => [
                $r['name'], $r['member_number'], $r['email'],
                $r['overdue_count'], $r['total_fine'], $r['max_days_overdue'],
            ], $rows));
        }

        echo $this->view->render('admin/reports/overdue', compact('rows'));
    }

    /** GET /admin/reports/fines */
    public function fines(Request $request): void
    {
        $this->auth->requirePermission(Permission::ReportsView);

        $from = $request->string('from') ?: date('Y-m-01');
        $to   = $request->string('to')   ?: date('Y-m-d');
        $rows = $this->reports->fineReport($from, $to);

        if ($request->string('export') === 'csv') {
            ExportService::csv('multas_' . $from . '_' . $to, [
                'ID Préstamo', 'Socio', 'Nº Socio', 'Libro',
                'Vencimiento', 'Devolución', 'Días retraso', 'Multa', 'Pagada',
            ], array_map(fn($r) => [
                $r['loan_id'], $r['member_name'], $r['member_number'], $r['book_title'],
                $r['due_date'], $r['return_date'] ?? '-', $r['days_overdue'],
                $r['fine_amount'], $r['fine_paid'] ? 'Sí' : 'No',
            ], $rows));
        }

        echo $this->view->render('admin/reports/fines', compact('from', 'to', 'rows'));
    }

    /** GET /admin/reports/inventory */
    public function inventory(Request $request): void
    {
        $this->auth->requirePermission(Permission::ReportsExport);
        $rows = $this->reports->inventoryReport();

        if ($request->string('export') === 'csv') {
            ExportService::csv('inventario_' . date('Ymd'), [
                'ID', 'ISBN', 'Título', 'Autor', 'Categoría',
                'Año', 'Ejemplares', 'Disponibles', 'En préstamo', 'Total préstamos',
            ], array_map(fn($r) => [
                $r['id'], $r['isbn'], $r['title'], $r['author'], $r['category'],
                $r['year'], $r['copies'], $r['available'], $r['on_loan'], $r['total_loans_ever'],
            ], $rows));
        }

        if ($request->string('export') === 'excel') {
            ExportService::excel('inventario_' . date('Ymd'), [
                'ID', 'ISBN', 'Título', 'Autor', 'Categoría',
                'Año', 'Ejemplares', 'Disponibles', 'En préstamo', 'Total préstamos',
            ], array_map(fn($r) => [
                $r['id'], $r['isbn'], $r['title'], $r['author'], $r['category'],
                $r['year'], $r['copies'], $r['available'], $r['on_loan'], $r['total_loans_ever'],
            ], $rows), 'Inventario');
        }

        echo $this->view->render('admin/reports/inventory', compact('rows'));
    }
}
```

---

## Dashboard View

```php
<!-- views/admin/dashboard.php -->
<div class="dashboard">

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-value"><?= \Core\View::e(number_format($kpis['total_books'])) ?></span>
            <span class="kpi-label">Libros en catálogo</span>
        </div>
        <div class="kpi-card kpi-warning">
            <span class="kpi-value"><?= \Core\View::e($kpis['active_loans']) ?></span>
            <span class="kpi-label">Préstamos activos</span>
        </div>
        <div class="kpi-card kpi-danger">
            <span class="kpi-value"><?= \Core\View::e($kpis['overdue_loans']) ?></span>
            <span class="kpi-label">Préstamos vencidos</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-value"><?= \Core\View::e($kpis['total_members']) ?></span>
            <span class="kpi-label">Socios registrados</span>
        </div>
        <div class="kpi-card kpi-success">
            <span class="kpi-value"><?= \Core\View::e($kpis['loans_today']) ?></span>
            <span class="kpi-label">Préstamos hoy</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-value">$<?= \Core\View::e(number_format($kpis['pending_fines'], 2)) ?></span>
            <span class="kpi-label">Multas pendientes</span>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <div class="chart-card">
            <?= $charts['loans_trend'] ?>
        </div>
        <div class="chart-card">
            <?= $charts['by_category'] ?>
        </div>
    </div>

    <div class="chart-card chart-full">
        <?= $charts['top_books'] ?>
    </div>

</div>
```

---

## Print CSS

```css
/* public/assets/css/print.css */
@media print {
    /* Hide UI chrome */
    nav, .sidebar, .btn, .search-form,
    .export-actions, footer { display: none !important; }

    /* Reset layout */
    body    { font-size: 11pt; color: #000; background: #fff; }
    .dashboard, .report-page { width: 100%; margin: 0; padding: 0; }

    /* KPI grid: 3 per row in print */
    .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8pt; }
    .kpi-card { border: 1pt solid #ccc; padding: 6pt; }
    .kpi-value { font-size: 20pt; font-weight: bold; }

    /* Tables */
    table { width: 100%; border-collapse: collapse; font-size: 9pt; }
    th, td { border: 0.5pt solid #ccc; padding: 4pt; }
    th { background: #eee !important; -webkit-print-color-adjust: exact; }

    /* Page breaks */
    .chart-card  { page-break-inside: avoid; }
    tr           { page-break-inside: avoid; }
    h2, h3       { page-break-after: avoid; }

    /* Report header */
    .report-header::before {
        content: "Biblioteca Municipal — " attr(data-report) " — " attr(data-date);
        display: block;
        font-size: 10pt;
        color: #666;
        margin-bottom: 8pt;
    }
}
```

---

## Routes

```php
$router->get('/admin/dashboard',            [ReportController::class, 'dashboard']);
$router->get('/admin/reports/overdue',      [ReportController::class, 'overdue']);
$router->get('/admin/reports/fines',        [ReportController::class, 'fines']);
$router->get('/admin/reports/inventory',    [ReportController::class, 'inventory']);
$router->get('/admin/reports/monthly',      [ReportController::class, 'monthly']);
```

---

## Workflow

1. **SQL agrega, PHP presenta** — Nunca traer miles de filas a PHP para sumar; usar `SUM()`, `COUNT()`, `GROUP BY` en la query.
2. **Cachear todos los dashboards** — Los KPIs se cachean 5–10 min; los reportes de inventario 15 min. Invalidar al crear/modificar datos.
3. **SVG charts sin JS** — Los SVGs generados en PHP funcionan en PDF, email y print sin ninguna dependencia.
4. **CSV siempre disponible** — Cada reporte tiene su botón `?export=csv`. `fputcsv` + BOM UTF-8 garantiza compatibilidad con Excel.
5. **Excel sin PHPSpreadsheet** — El export HTML con MIME `vnd.ms-excel` abre en Excel sin librerías pesadas.
6. **`@media print` desde el inicio** — Diseñar tablas con bordes `0.5pt solid` desde el día 1; no parchear al final.
7. **Filtros de fecha en todos los reportes** — `?from=2024-01-01&to=2024-12-31` — siempre rango configurable.
8. **Permisos en cada endpoint** — `ReportsView` para ver; `ReportsExport` para exportar — granularidad importante.
