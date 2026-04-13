---
name: skill-barcode-isbn
description: "**WORKFLOW SKILL** — ISBN validation, barcode and QR code generation in pure PHP for library systems. USE FOR: ISBN-10 and ISBN-13 checksum validation and conversion; EAN-13 barcode generation with GD (no external libraries); Code-128 barcode generation with GD; QR code generation for member cards and book labels (phpqrcode single-file library); ISBN formatting and normalization; book metadata lookup by ISBN via Open Library API; bulk ISBN import validation; printing book spine labels; generating member card QR codes; scanning ISBN input from barcode readers (USB HID input handling); ISBN-13 prefix meaning (publisher, language group); check digit calculation. DO NOT USE FOR: PDF417 or DataMatrix barcodes; NFC tags; RFID systems."
---

# Barcode & ISBN — Pure PHP for Library Systems

## Core Philosophy

- **GD for barcodes**: PHP's GD extension generates EAN-13 and Code-128 images without any external library.
- **phpqrcode for QR**: A single PHP file — no Composer, works on any shared hosting.
- **Validate before storing**: An invalid ISBN in the catalog corrupts searches and imports.
- **ISBN-13 is the standard**: Convert ISBN-10 to ISBN-13 on input; store only ISBN-13.
- **Serve as image or embed**: Barcodes can be streamed as PNG or embedded as base64 in HTML/PDF.

---

## ISBN Validator

```php
<?php
// src/Helpers/Isbn.php
declare(strict_types=1);

namespace Helpers;

final class Isbn
{
    /**
     * Validate and normalize any ISBN (10 or 13) to ISBN-13.
     * Returns null if invalid.
     */
    public static function normalize(string $input): ?string
    {
        $clean = preg_replace('/[^0-9X]/i', '', strtoupper($input));

        if (strlen($clean) === 10) {
            if (!self::validIsbn10($clean)) return null;
            return self::isbn10to13($clean);
        }

        if (strlen($clean) === 13) {
            if (!self::validIsbn13($clean)) return null;
            return $clean;
        }

        return null;
    }

    public static function validIsbn10(string $isbn): bool
    {
        if (!preg_match('/^\d{9}[\dX]$/', $isbn)) return false;

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $isbn[$i] * (10 - $i);
        }
        $last  = strtoupper($isbn[9]);
        $sum  += $last === 'X' ? 10 : (int) $last;

        return $sum % 11 === 0;
    }

    public static function validIsbn13(string $isbn): bool
    {
        if (!preg_match('/^\d{13}$/', $isbn)) return false;

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $check = (10 - ($sum % 10)) % 10;

        return $check === (int) $isbn[12];
    }

    public static function isbn10to13(string $isbn10): string
    {
        $base = '978' . substr($isbn10, 0, 9);
        $sum  = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $base[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $check = (10 - ($sum % 10)) % 10;
        return $base . $check;
    }

    public static function isbn13to10(string $isbn13): ?string
    {
        if (!str_starts_with($isbn13, '978')) return null;

        $base = substr($isbn13, 3, 9);
        $sum  = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $base[$i] * (10 - $i);
        }
        $check = (11 - ($sum % 11)) % 11;
        return $base . ($check === 10 ? 'X' : (string) $check);
    }

    /** Format for display: 978-84-376-0494-7 */
    public static function format(string $isbn13): string
    {
        if (strlen($isbn13) !== 13) return $isbn13;

        // Simplified grouping (prefix-group-publisher-title-check)
        return substr($isbn13, 0, 3) . '-'
             . substr($isbn13, 3, 2) . '-'
             . substr($isbn13, 5, 3) . '-'
             . substr($isbn13, 8, 4) . '-'
             . substr($isbn13, 12, 1);
    }

    /** Detect input type */
    public static function type(string $input): string
    {
        $clean = preg_replace('/[^0-9X]/i', '', strtoupper($input));
        return match (strlen($clean)) {
            10 => 'ISBN-10',
            13 => 'ISBN-13',
            default => 'unknown',
        };
    }

    /**
     * EAN-13 prefix meaning (first 3 digits of ISBN-13)
     */
    public static function prefixLabel(string $isbn13): string
    {
        return match (substr($isbn13, 0, 3)) {
            '978' => 'Bookland (original)',
            '979' => 'Bookland (extended)',
            default => 'Unknown',
        };
    }
}
```

---

## EAN-13 Barcode Generator (GD — no dependencies)

```php
<?php
// src/Services/BarcodeService.php
declare(strict_types=1);

namespace Services;

final class BarcodeService
{
    // EAN-13 encoding table (L, G, R codes)
    private const L_CODE = ['0001101','0011001','0010011','0111101','0100011',
                             '0110001','0101111','0111011','0110111','0001011'];
    private const G_CODE = ['0100111','0110011','0011011','0100001','0011101',
                             '0111001','0000101','0010001','0001001','0010111'];
    private const R_CODE = ['1110010','1100110','1101100','1000010','1011100',
                             '1001110','1010000','1000100','1001000','1110100'];

    // First digit parity pattern for EAN-13
    private const FIRST_DIGIT_PARITY = [
        '0' => 'LLLLLL', '1' => 'LLGLGG', '2' => 'LLGGLG', '3' => 'LLGGGL',
        '4' => 'LGLLGG', '5' => 'LGGLLG', '6' => 'LGGGLL', '7' => 'LGLGLG',
        '8' => 'LGLGGL', '9' => 'LGGLGL',
    ];

    /**
     * Generate EAN-13 barcode PNG
     *
     * @param string $isbn13  13-digit ISBN
     * @param int    $width   Total image width in pixels
     * @param int    $height  Total image height in pixels
     * @return string         PNG binary string
     */
    public function ean13(string $isbn13, int $width = 300, int $height = 120): string
    {
        if (strlen($isbn13) !== 13 || !ctype_digit($isbn13)) {
            throw new \InvalidArgumentException("EAN-13 requires exactly 13 digits.");
        }

        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0,   0,   0);
        $gray  = imagecolorallocate($img, 100, 100, 100);

        imagefill($img, 0, 0, $white);

        // Build bit string
        $bits = $this->encode($isbn13);

        // Draw bars
        $barWidth    = (int) floor($width / (strlen($bits) + 20));
        $barWidth    = max(1, $barWidth);
        $totalBars   = strlen($bits) * $barWidth;
        $offsetX     = (int) floor(($width - $totalBars) / 2);
        $barHeight   = $height - 20;

        for ($i = 0, $len = strlen($bits); $i < $len; $i++) {
            if ($bits[$i] === '1') {
                imagefilledrectangle(
                    $img,
                    $offsetX + $i * $barWidth,
                    5,
                    $offsetX + $i * $barWidth + $barWidth - 1,
                    $barHeight,
                    $black,
                );
            }
        }

        // Draw ISBN number below bars
        $fontSize = 2;
        $textY    = $height - 14;
        $textX    = (int) floor(($width - imagefontwidth($fontSize) * 13) / 2);
        imagestring($img, $fontSize, $textX, $textY, $isbn13, $gray);

        // Capture PNG output
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return $png;
    }

    /**
     * Generate Code-128 barcode (for member cards, general use)
     * Simplified implementation for alphanumeric data
     */
    public function code128(string $data, int $width = 300, int $height = 80): string
    {
        // Code-128B encoding map (ASCII 32–127)
        $patterns = $this->code128Patterns();

        $img   = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        // Build bars: start code B + data + checksum + stop
        $bits = $this->encodeCode128($data, $patterns);

        $barWidth  = max(1, (int) floor($width / strlen($bits)));
        $barHeight = $height - 15;
        $offsetX   = (int) floor(($width - strlen($bits) * $barWidth) / 2);

        for ($i = 0, $len = strlen($bits); $i < $len; $i++) {
            if ($bits[$i] === '1') {
                imagefilledrectangle(
                    $img,
                    $offsetX + $i * $barWidth, 2,
                    $offsetX + $i * $barWidth + $barWidth - 1, $barHeight,
                    $black,
                );
            }
        }

        $textX = (int) floor(($width - strlen($data) * imagefontwidth(2)) / 2);
        imagestring($img, 2, $textX, $height - 13, $data, $black);

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return $png;
    }

    /**
     * Save barcode to file and return relative storage path
     */
    public function saveBarcode(string $png, string $name): string
    {
        $dir  = BASE_PATH . '/storage/generated/barcodes';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $file = $dir . '/' . $name . '.png';
        file_put_contents($file, $png);
        return 'generated/barcodes/' . $name . '.png';
    }

    /**
     * Base64 data URI for embedding in HTML or PDF
     */
    public function toDataUri(string $png): string
    {
        return 'data:image/png;base64,' . base64_encode($png);
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function encode(string $isbn13): string
    {
        $firstDigit = $isbn13[0];
        $parity     = self::FIRST_DIGIT_PARITY[$firstDigit];

        $bits = '101'; // Start guard

        for ($i = 1; $i <= 6; $i++) {
            $digit  = (int) $isbn13[$i];
            $bits  .= $parity[$i - 1] === 'L'
                ? self::L_CODE[$digit]
                : self::G_CODE[$digit];
        }

        $bits .= '01010'; // Center guard

        for ($i = 7; $i <= 12; $i++) {
            $bits .= self::R_CODE[(int) $isbn13[$i]];
        }

        $bits .= '101'; // End guard

        return $bits;
    }

    private function encodeCode128(string $data, array $patterns): string
    {
        $startB    = 104;
        $checksum  = $startB;
        $bits      = $patterns[$startB];

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $code      = ord($data[$i]) - 32;
            $checksum += $code * ($i + 1);
            $bits     .= $patterns[$code];
        }

        $bits .= $patterns[$checksum % 103]; // Checksum
        $bits .= '1100011101011';             // Stop pattern

        return $bits;
    }

    private function code128Patterns(): array
    {
        // Partial Code-128B pattern table (first 64 values — ASCII 32–95)
        return [
            '11011001100','11001101100','11001100110','10010011000','10010001100',
            '10001001100','10011001000','10011000100','10001100100','11001001000',
            '11001000100','11000100100','10110011100','10011011100','10011001110',
            '10111001100','10011101100','10011100110','11001110010','11001011100',
            '11001001110','11011100100','11001110100','11101101110','11101001100',
            '11100101100','11100100110','11101100100','11100110100','11100110010',
            '11011011000','11011000110','11000110110','10100011000','10001011000',
            '10001000110','10110001000','10001101000','10001100010','11010001000',
            '11000101000','11000100010','10110111000','10110001110','10001101110',
            '10111011000','10111000110','10001110110','11101110110','11010001110',
            '11000101110','11011101000','11011100010','11011101110','11101011000',
            '11101000110','11100010110','11101101000','11101100010','11100011010',
            '11101111010','11001000010','11110001010','10100110000','10100001100',
            '10010110000','10010000110','10000101100','10000100110','10110010000',
            '10110000100','10011010000','10011000010','10000110100','10000110010',
            '11000010010','11001010000','11110111010','11000010100','10001111010',
            '10100111100','10010111100','10010011110','10111100100','10011110100',
            '10011110010','11110100100','11110010100','11110010010','11011011110',
            '11011110110','11110110110','10101111000','10100011110','10001011110',
            '10111101000','10111100010','11110101000','11110100010','10111011110',
            '10111101110','11101011110','11110101110','11010000100','11010010000',
            '11010011100','1100011101011',
        ];
    }
}
```

---

## QR Code Generation

```php
<?php
// src/Services/QrService.php
declare(strict_types=1);

namespace Services;

/**
 * QR Code generation using phpqrcode (single-file library).
 * Download: https://sourceforge.net/projects/phpqrcode/
 * Place at: src/Vendor/phpqrcode/qrlib.php
 */
final class QrService
{
    public function __construct()
    {
        require_once BASE_PATH . '/src/Vendor/phpqrcode/qrlib.php';
    }

    /**
     * Generate QR PNG and return binary string
     *
     * @param string $data     Content to encode
     * @param int    $level    Error correction: QR_ECLEVEL_L|M|Q|H
     * @param int    $size     Pixel size per module (1–10)
     * @param int    $margin   Quiet zone modules
     */
    public function generate(
        string $data,
        int    $level  = QR_ECLEVEL_M,
        int    $size   = 6,
        int    $margin = 2,
    ): string {
        $tmpFile = tempnam(sys_get_temp_dir(), 'qr_') . '.png';

        \QRcode::png($data, $tmpFile, $level, $size, $margin);

        $png = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $png;
    }

    /**
     * Member card QR — encodes member URL or member number
     */
    public function memberCard(int $memberId, string $memberNumber): string
    {
        $url = ($_ENV['APP_URL'] ?? '') . '/members/' . $memberId;
        return $this->generate($url, QR_ECLEVEL_H, 5, 2);
    }

    /**
     * Book label QR — encodes book URL or ISBN
     */
    public function bookLabel(int $bookId, string $isbn): string
    {
        $url = ($_ENV['APP_URL'] ?? '') . '/books/' . $bookId;
        return $this->generate($url, QR_ECLEVEL_M, 4, 1);
    }

    /**
     * Save to file
     */
    public function save(string $png, string $filename): string
    {
        $dir = BASE_PATH . '/storage/generated/qr';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        file_put_contents($dir . '/' . $filename . '.png', $png);
        return 'generated/qr/' . $filename . '.png';
    }

    /**
     * Base64 data URI for embedding in HTML/PDF
     */
    public function toDataUri(string $png): string
    {
        return 'data:image/png;base64,' . base64_encode($png);
    }
}
```

---

## Book Label Generator (Barcode + QR + Spine)

```php
<?php
// src/Services/LabelService.php
declare(strict_types=1);

namespace Services;

final class LabelService
{
    public function __construct(
        private readonly BarcodeService $barcode,
        private readonly QrService      $qr,
        private readonly PdfService     $pdf,
    ) {}

    /**
     * Generate a printable book label sheet (PDF with barcodes)
     * Standard label: 50mm x 30mm
     */
    public function generateBookLabels(array $books): string
    {
        require_once BASE_PATH . '/src/Vendor/tcpdf/tcpdf.php';

        $pdf = new \TCPDF('L', 'mm', [210, 148], true, 'UTF-8');
        $pdf->SetCreator('Biblioteca');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(5, 5, 5);
        $pdf->AddPage();

        $col = 0;
        $row = 0;
        $labelW = 65;
        $labelH = 35;
        $cols   = 3;

        foreach ($books as $book) {
            $x = 5 + ($col * $labelW);
            $y = 5 + ($row * $labelH);

            // EAN-13 barcode
            if (isset($book['isbn']) && strlen($book['isbn']) === 13) {
                $barcodePng = $this->barcode->ean13($book['isbn'], 180, 60);
                $b64        = base64_encode($barcodePng);
                $pdf->Image(
                    '@' . base64_decode($b64),
                    $x + 2, $y + 2, 40, 14,
                    'PNG', '', '', false,
                );
            }

            // Book title (truncated)
            $pdf->SetFont('helvetica', '', 6);
            $pdf->SetXY($x + 2, $y + 17);
            $pdf->Cell($labelW - 4, 4, mb_substr($book['title'], 0, 40), 0, 1, 'L');

            // Author
            $pdf->SetXY($x + 2, $y + 21);
            $pdf->Cell($labelW - 4, 4, mb_substr($book['author'], 0, 35), 0, 1, 'L');

            // QR code
            $qrPng = $this->qr->bookLabel($book['id'], $book['isbn'] ?? '');
            $pdf->Image('@' . $qrPng, $x + 48, $y + 2, 12, 12, 'PNG');

            // Label border
            $pdf->Rect($x, $y, $labelW - 2, $labelH - 2);

            $col++;
            if ($col >= $cols) {
                $col = 0;
                $row++;
                if ($row * $labelH > 140) {
                    $pdf->AddPage();
                    $row = 0;
                }
            }
        }

        $path = BASE_PATH . '/storage/generated/labels_' . date('Ymd_His') . '.pdf';
        $pdf->Output($path, 'F');
        return str_replace(BASE_PATH . '/storage/', '', $path);
    }

    /**
     * Generate member card PDF with QR
     */
    public function generateMemberCard(array $user): string
    {
        $qrPng = $this->qr->memberCard($user['id'], $user['member_number']);

        require_once BASE_PATH . '/src/Vendor/tcpdf/tcpdf.php';
        $pdf = new \TCPDF('L', 'mm', [85.6, 54], true, 'UTF-8');
        $pdf->SetCreator('Biblioteca');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(3, 3, 3);
        $pdf->AddPage();

        // Background
        $pdf->SetFillColor(30, 58, 95);
        $pdf->Rect(0, 0, 85.6, 54, 'F');

        // QR code (top-right)
        $pdf->Image('@' . $qrPng, 62, 3, 20, 20, 'PNG');

        // Text
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY(3, 5);
        $pdf->Cell(58, 6, 'BIBLIOTECA MUNICIPAL', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(3, 11);
        $pdf->Cell(58, 4, 'CARNET DE SOCIO', 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(3, 25);
        $pdf->Cell(80, 7, strtoupper($user['name']), 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(3, 33);
        $pdf->Cell(80, 5, 'Nº ' . $user['member_number'], 0, 1, 'L');

        // Barcode member number (Code-128)
        $barPng = $this->barcode->code128($user['member_number'], 180, 40);
        $pdf->Image('@' . $barPng, 3, 39, 50, 12, 'PNG');

        $path = BASE_PATH . '/storage/generated/cards/member_' . $user['id'] . '.pdf';
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        $pdf->Output($path, 'F');
        return str_replace(BASE_PATH . '/storage/', '', $path);
    }
}
```

---

## ISBN Lookup via Open Library API

```php
<?php
// src/Services/IsbnLookupService.php
declare(strict_types=1);

namespace Services;

use Helpers\Isbn;

final class IsbnLookupService
{
    private const API_URL = 'https://openlibrary.org/api/books?bibkeys=ISBN:%s&format=json&jscmd=data';
    private const TIMEOUT = 5;

    /**
     * Fetch book metadata by ISBN from Open Library.
     * Returns null on failure — always handle gracefully.
     */
    public function lookup(string $isbn): ?array
    {
        $isbn13 = Isbn::normalize($isbn);
        if ($isbn13 === null) return null;

        $url = sprintf(self::API_URL, $isbn13);

        $ctx = stream_context_create(['http' => [
            'timeout'        => self::TIMEOUT,
            'ignore_errors'  => true,
            'header'         => "User-Agent: BibliotecaApp/1.0\r\n",
        ]]);

        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) return null;

        $data = json_decode($response, true);
        $key  = "ISBN:{$isbn13}";
        if (empty($data[$key])) return null;

        $book = $data[$key];

        return [
            'isbn'        => $isbn13,
            'title'       => $book['title']                                      ?? null,
            'author'      => $book['authors'][0]['name']                         ?? null,
            'publisher'   => $book['publishers'][0]['name']                      ?? null,
            'year'        => (int) ($book['publish_date'] ?? 0) ?: null,
            'pages'       => $book['number_of_pages']                            ?? null,
            'language'    => $book['languages'][0]['key']                        ?? null,
            'description' => $book['description']['value'] ?? $book['description'] ?? null,
            'cover_url'   => $book['cover']['large'] ?? $book['cover']['medium'] ?? null,
            'subjects'    => array_slice(
                array_map(fn($s) => $s['name'], $book['subjects'] ?? []), 0, 5
            ),
        ];
    }

    /**
     * Lookup with file cache (avoid repeated API calls)
     */
    public function lookupCached(string $isbn, int $cacheDays = 30): ?array
    {
        $isbn13 = Isbn::normalize($isbn);
        if ($isbn13 === null) return null;

        $cacheFile = BASE_PATH . '/storage/cache/isbn_' . $isbn13 . '.json';

        if (file_exists($cacheFile) && filemtime($cacheFile) > time() - ($cacheDays * 86400)) {
            return json_decode(file_get_contents($cacheFile), true);
        }

        $data = $this->lookup($isbn13);

        if ($data !== null) {
            file_put_contents($cacheFile, json_encode($data), LOCK_EX);
        }

        return $data;
    }
}
```

---

## Barcode Controller

```php
<?php
// src/Controllers/BarcodeController.php
declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Auth\AuthService;
use Auth\Permission;
use Services\BarcodeService;
use Services\QrService;
use Helpers\Isbn;

final class BarcodeController
{
    public function __construct(
        private readonly AuthService    $auth,
        private readonly BarcodeService $barcode,
        private readonly QrService      $qr,
    ) {}

    /** GET /barcode/isbn/{isbn} — EAN-13 barcode image */
    public function isbn(string $isbn): void
    {
        $isbn13 = Isbn::normalize($isbn);
        if ($isbn13 === null) {
            http_response_code(400);
            exit('Invalid ISBN');
        }

        $png = $this->barcode->ean13($isbn13, 300, 100);

        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=31536000');
        header('Content-Length: ' . strlen($png));
        echo $png;
        exit;
    }

    /** GET /barcode/member/{id} — QR code for member card */
    public function member(int $memberId): void
    {
        $this->auth->requirePermission(Permission::UsersView);

        $png = $this->qr->memberCard($memberId, "M-{$memberId}");

        header('Content-Type: image/png');
        header('Cache-Control: private, max-age=3600');
        echo $png;
        exit;
    }

    /** GET /barcode/book/{id} — QR code for book */
    public function book(int $bookId): void
    {
        $png = $this->qr->bookLabel($bookId, (string) $bookId);

        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        echo $png;
        exit;
    }
}
```

---

## Usage in Views

```php
<!-- Book detail page — show barcode inline -->
<?php if ($book['isbn']): ?>
<figure class="book-barcode">
    <img src="/barcode/isbn/<?= urlencode($book['isbn']) ?>"
         alt="Código de barras ISBN <?= \Core\View::e($book['isbn']) ?>"
         width="200" height="70">
    <figcaption><?= \Core\View::e(\Helpers\Isbn::format($book['isbn'])) ?></figcaption>
</figure>
<?php endif; ?>

<!-- Member card — QR -->
<figure class="member-qr">
    <img src="/barcode/member/<?= \Core\View::e($member['id']) ?>"
         alt="QR carnet de socio"
         width="100" height="100">
</figure>
```

---

## ISBN Input Handling (Barcode Scanner Support)

```php
<?php
// Barcode USB scanners send keystrokes ending in Enter.
// The browser captures them as fast keyboard input in a text field.
// No special handling needed — treat as normal text input.

// In the book form:
?>
<input type="text"
       name="isbn"
       id="isbn-input"
       placeholder="Escanee o escriba el ISBN"
       maxlength="17"
       inputmode="numeric"
       autocomplete="off">

<script>
// Auto-submit or auto-lookup when 13 digits are scanned
document.getElementById('isbn-input').addEventListener('input', function () {
    const clean = this.value.replace(/[^0-9X]/gi, '');
    if (clean.length === 13 || clean.length === 10) {
        // Auto-lookup metadata
        fetch('/api/isbn/' + clean)
            .then(r => r.json())
            .then(data => {
                if (data.title) document.getElementById('title').value = data.title;
                if (data.author) document.getElementById('author').value = data.author;
            });
    }
});
</script>
```

---

## Routes

```php
$router->get('/barcode/isbn/{isbn}',      [BarcodeController::class, 'isbn']);
$router->get('/barcode/member/{id}',      [BarcodeController::class, 'member']);
$router->get('/barcode/book/{id}',        [BarcodeController::class, 'book']);
$router->get('/api/isbn/{isbn}',          [BookController::class, 'isbnLookup']);
```

---

## Workflow

1. **Normalizar ISBN a 13 dígitos siempre** — `Isbn::normalize()` en cada entrada; rechazar si devuelve `null`.
2. **Barcode por URL, no pre-generado** — `<img src="/barcode/isbn/978...">` genera on-demand y cachea en browser.
3. **QR en PDFs con data URI** — `$qr->toDataUri($png)` permite incrustar en TCPDF sin archivos temporales.
4. **ISBN lookup con caché de archivo** — La API de Open Library es gratuita pero lenta; cachear 30 días.
5. **Escáner USB funciona sin código especial** — Es solo un teclado muy rápido; el campo `<input>` lo captura naturalmente.
6. **Code-128 para carnets** — Más compacto que EAN-13 para datos alfanuméricos (número de socio).
7. **Labels en lote** — Generar el PDF de etiquetas al agregar libros en lote, no una por una.
8. **Validar antes de guardar** — Un ISBN inválido en la BD rompe el catálogo; validar en controller Y en BD (UNIQUE constraint).
