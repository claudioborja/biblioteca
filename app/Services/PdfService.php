<?php
// app/Services/PdfService.php
declare(strict_types=1);

namespace Services;

final class PdfService
{
    public function renderSimpleTableReport(array $options): string
    {
        $tcpdfPath = '/usr/share/php/tcpdf/tcpdf.php';
        if (!class_exists('TCPDF')) {
            if (!file_exists($tcpdfPath)) {
                throw new \RuntimeException('TCPDF no esta disponible en el servidor.');
            }
            require_once $tcpdfPath;
        }

        $library     = (string) ($options['library']       ?? 'Biblioteca');
        $title       = (string) ($options['title']         ?? 'Informe');
        $subtitle    = (string) ($options['subtitle']      ?? '');
        $headers     = $options['headers']     ?? [];
        $rows        = $options['rows']        ?? [];
        $colWidths   = $options['col_widths']  ?? [];
        $colAligns   = $options['col_align']   ?? [];
        $orientation = (string) ($options['orientation']   ?? 'P');
        $generatedAt = (string) ($options['generated_at']  ?? date('d/m/Y H:i'));
        $generatedBy = (string) ($options['generated_by']  ?? '');

        // ── Calcular ancho de página útil ─────────────────────────────────────
        // A4 portrait: 210mm, landscape: 297mm, márgenes 12+12=24
        $pageWidth  = $orientation === 'L' ? 297 : 210;
        $usableWidth = $pageWidth - 24;

        // Si no se pasan anchos, repartir en partes iguales
        if (empty($colWidths) && count($headers) > 0) {
            $w = round($usableWidth / count($headers), 2);
            $colWidths = array_fill(0, count($headers), $w);
        }

        // Normalizar: escalar proporcionalmente para que sumen exactamente el ancho útil
        $sumWidths = array_sum($colWidths);
        if ($sumWidths > 0 && abs($sumWidths - $usableWidth) > 0.5) {
            $factor = $usableWidth / $sumWidths;
            $colWidths = array_map(fn($w) => round($w * $factor, 2), $colWidths);
        }

        // ── Clase PDF ──────────────────────────────────────────────────────────
        $pdf = new class($library, $title, $generatedAt, $generatedBy, $orientation) extends \TCPDF {
            private float $lineStart = 12;
            private float $lineEnd;
            private float $contentWidth;

            public function __construct(
                private string $libraryName,
                private string $reportTitle,
                private string $generatedAt,
                private string $generatedBy,
                private string $pageOrientation,
            ) {
                parent::__construct($pageOrientation, 'mm', 'A4', true, 'UTF-8', false);
                $this->lineEnd     = $pageOrientation === 'L' ? 285.0 : 198.0;
                $this->contentWidth = $this->lineEnd - $this->lineStart;
            }

            public function Header(): void
            {
                // Banda de color de fondo
                $this->SetFillColor(30, 58, 95);
                $this->Rect(0, 0, $this->getPageWidth(), 20, 'F');

                // Nombre de la biblioteca (blanco, grande)
                $this->SetFont('dejavusans', 'B', 12);
                $this->SetTextColor(255, 255, 255);
                $this->SetXY($this->lineStart, 4);
                $this->Cell($this->contentWidth * 0.6, 6, $this->libraryName, 0, 0, 'L');

                // Título del reporte (derecha, más pequeño)
                $this->SetFont('dejavusans', '', 9);
                $this->SetTextColor(186, 210, 240);
                $this->Cell($this->contentWidth * 0.4, 6, $this->reportTitle, 0, 0, 'R');

                // Línea separadora bajo la banda
                $this->SetDrawColor(71, 120, 180);
                $this->SetLineWidth(0.4);
                $this->Line($this->lineStart, 20, $this->lineEnd, 20);

                // Restaurar color de texto
                $this->SetTextColor(30, 41, 59);
            }

            public function Footer(): void
            {
                $this->SetY(-12);
                $this->SetDrawColor(203, 213, 225);
                $this->SetLineWidth(0.3);
                $this->Line($this->lineStart, $this->GetY(), $this->lineEnd, $this->GetY());
                $this->Ln(1);
                $this->SetFont('dejavusans', '', 7);
                $this->SetTextColor(100, 116, 139);
                $third = $this->contentWidth / 3;
                $byText = $this->generatedBy !== '' ? 'Generado por: ' . $this->generatedBy : '';
                $this->Cell($third, 5, $byText, 0, 0, 'L');
                $this->Cell($third, 5, $this->generatedAt, 0, 0, 'C');
                $this->Cell($third, 5, 'Página ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
            }
        };

        $pdf->SetCreator($library);
        $pdf->SetAuthor($library);
        $pdf->SetTitle($title);
        $pdf->SetSubject($title);
        $pdf->SetMargins(12, 26, 12);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->AddPage($orientation);

        // ── Subtítulo ──────────────────────────────────────────────────────────
        if ($subtitle !== '') {
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->MultiCell(0, 4.5, $subtitle, 0, 'L', false, 1, '', '', true);
            $pdf->Ln(2);
        }

        $rowH     = 6.5;   // alto fila datos
        $headH    = 8.0;   // alto fila cabecera (permite 2 líneas)
        $fontSize = 7.5;

        // ── Cabeceras ──────────────────────────────────────────────────────────
        $pdf->SetFont('dejavusans', 'B', $fontSize);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(30, 58, 95);       // azul oscuro
        $pdf->SetDrawColor(148, 163, 184);

        foreach ($headers as $i => $header) {
            $w     = (float) ($colWidths[$i] ?? 20);
            $align = strtoupper((string) ($colAligns[$i] ?? 'C'));
            $pdf->MultiCell(
                $w, $headH, (string) $header,
                1,        // borde
                $align,
                true,     // fill
                0,        // no salto de línea al final (siguiente celda a la derecha)
                '',       // x auto
                '',       // y auto
                true,     // reset height
                0,
                false,
                true,
                $headH,
                'M'       // alineación vertical: middle
            );
        }
        $pdf->Ln($headH);

        // ── Filas de datos ─────────────────────────────────────────────────────
        $pdf->SetFont('dejavusans', '', $fontSize);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetDrawColor(226, 232, 240);

        foreach ($rows as $ri => $row) {
            // Salto de página anticipado
            if ($pdf->GetY() + $rowH > $pdf->getPageHeight() - 16) {
                $pdf->AddPage($orientation);
                // Repetir cabecera
                $pdf->SetFont('dejavusans', 'B', $fontSize);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFillColor(30, 58, 95);
                $pdf->SetDrawColor(148, 163, 184);
                foreach ($headers as $i => $header) {
                    $w     = (float) ($colWidths[$i] ?? 20);
                    $align = strtoupper((string) ($colAligns[$i] ?? 'C'));
                    $pdf->MultiCell($w, $headH, (string) $header, 1, $align, true, 0, '', '', true, 0, false, true, $headH, 'M');
                }
                $pdf->Ln($headH);
                $pdf->SetFont('dejavusans', '', $fontSize);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetDrawColor(226, 232, 240);
            }

            // Color alterno
            if ($ri % 2 === 0) {
                $pdf->SetFillColor(255, 255, 255);
            } else {
                $pdf->SetFillColor(245, 247, 250);
            }

            foreach ($row as $ci => $value) {
                $w     = (float) ($colWidths[$ci] ?? 20);
                $align = strtoupper((string) ($colAligns[$ci] ?? 'L'));
                $pdf->MultiCell(
                    $w, $rowH, (string) $value,
                    1, $align, true, 0, '', '', true, 0, false, true, $rowH, 'M'
                );
            }
            $pdf->Ln($rowH);
        }

        if (empty($rows)) {
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Cell($usableWidth, $rowH, 'Sin registros.', 1, 1, 'L', true);
        }

        return $pdf->Output('', 'S');
    }
}
