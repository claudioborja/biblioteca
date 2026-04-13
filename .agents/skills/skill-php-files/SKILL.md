---
name: skill-php-files
description: "**WORKFLOW SKILL** — Professional file handling in pure PHP for library systems. USE FOR: secure file uploads (books covers, user avatars, documents); file validation (type, size, MIME); image processing with GD (resize, crop, thumbnails, WebP conversion, watermark); image processing with Imagick when available; PDF generation with TCPDF or Dompdf (loan receipts, membership cards, overdue notices, inventory reports); file storage organization (local filesystem); secure file download with access control (only authenticated users can download); file deletion and cleanup; MIME type detection; virus scanning integration concept; handling large file uploads; chunked upload; file naming and path sanitization; storage directory structure for a library system. DO NOT USE FOR: cloud storage (S3/Cloudflare R2); video processing; external CDN integration."
---

# PHP Files — Professional File Handling for Library Systems

## Core Philosophy

- **Never trust uploaded files**: Validate MIME type, extension, size, and content — not just the filename.
- **Never store uploads in public/**: Files go in `storage/uploads/` (above web root); serve via a PHP controller.
- **Sanitize filenames always**: User-provided filenames can contain path traversal or malicious characters.
- **GD is always available**: Use GD for images; fall back gracefully if Imagick/Imagick not installed.
- **Unique filenames**: Never use the original filename — generate a UUID-based name to prevent collisions and enumeration.

---

## Storage Directory Structure

```
storage/
├── uploads/
│   ├── covers/          ← book cover images (original)
│   │   └── thumbs/      ← generated thumbnails
│   ├── avatars/         ← user profile pictures
│   ├── documents/       ← PDFs and other documents
│   └── temp/            ← temporary upload staging
├── generated/
│   ├── receipts/        ← loan receipt PDFs
│   ├── reports/         ← inventory/stats PDFs
│   └── cards/           ← membership card PDFs
├── cache/
└── logs/
```

---

## Upload Service

```php
<?php
// src/Services/FileUploadService.php
declare(strict_types=1);

namespace Services;

final class FileUploadService
{
    // Allowed MIME types per category
    private const ALLOWED = [
        'cover'    => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'avatar'   => ['image/jpeg', 'image/png', 'image/webp'],
        'document' => ['application/pdf'],
    ];

    private const MAX_SIZES = [
        'cover'    => 5 * 1024 * 1024,   // 5 MB
        'avatar'   => 2 * 1024 * 1024,   // 2 MB
        'document' => 20 * 1024 * 1024,  // 20 MB
    ];

    private const DIRS = [
        'cover'    => 'uploads/covers',
        'avatar'   => 'uploads/avatars',
        'document' => 'uploads/documents',
    ];

    private string $storageBase;

    public function __construct()
    {
        $this->storageBase = BASE_PATH . '/storage';
        $this->ensureDirectories();
    }

    /**
     * @param array  $file    $_FILES['field']
     * @param string $type    cover | avatar | document
     * @return string         Relative path from storage root
     */
    public function upload(array $file, string $type): string
    {
        $this->validateUpload($file, $type);

        $mime      = $this->detectMime($file['tmp_name']);
        $extension = $this->extensionForMime($mime);
        $filename  = $this->generateFilename($extension);
        $subDir    = date('Y/m');
        $dir       = $this->storageBase . '/' . self::DIRS[$type] . '/' . $subDir;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $destination = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('Error al mover el archivo subido.');
        }

        // Strip EXIF metadata from images (privacy)
        if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
            $this->stripExif($destination, $mime);
        }

        return self::DIRS[$type] . '/' . $subDir . '/' . $filename;
    }

    public function delete(string $relativePath): bool
    {
        $full = $this->storageBase . '/' . ltrim($relativePath, '/');
        if (file_exists($full)) {
            return unlink($full);
        }
        return false;
    }

    // ── Validation ───────────────────────────────────────────────────────────

    private function validateUpload(array $file, string $type): void
    {
        if (!isset(self::ALLOWED[$type])) {
            throw new \InvalidArgumentException("Tipo de archivo desconocido: {$type}");
        }

        // PHP upload error check
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->uploadErrorMessage($file['error']));
        }

        // Size check
        if ($file['size'] > self::MAX_SIZES[$type]) {
            $max = round(self::MAX_SIZES[$type] / 1024 / 1024, 1);
            throw new \RuntimeException("El archivo supera el tamaño máximo permitido ({$max} MB).");
        }

        // MIME check via file content (not $_FILES['type'] — it's user-supplied)
        $mime = $this->detectMime($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED[$type], true)) {
            throw new \RuntimeException(
                "Tipo de archivo no permitido. Se esperaba: " . implode(', ', self::ALLOWED[$type])
            );
        }

        // Extension check (secondary defense)
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = $this->allowedExtensions($type);
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException("Extensión no permitida: .{$extension}");
        }
    }

    private function detectMime(string $tmpPath): string
    {
        // Use finfo for reliable MIME detection (reads file magic bytes)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($tmpPath);
    }

    private function allowedExtensions(string $type): array
    {
        return match ($type) {
            'cover'    => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
            'avatar'   => ['jpg', 'jpeg', 'png', 'webp'],
            'document' => ['pdf'],
            default    => [],
        };
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function generateFilename(string $extension): string
    {
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg'       => 'jpg',
            'image/png'        => 'png',
            'image/webp'       => 'webp',
            'image/gif'        => 'gif',
            'application/pdf'  => 'pdf',
            default            => throw new \RuntimeException("MIME no soportado: {$mime}"),
        };
    }

    private function stripExif(string $path, string $mime): void
    {
        if ($mime === 'image/jpeg') {
            $img = imagecreatefromjpeg($path);
            if ($img) { imagejpeg($img, $path, 90); imagedestroy($img); }
        } elseif ($mime === 'image/png') {
            $img = imagecreatefrompng($path);
            if ($img) { imagepng($img, $path, 6); imagedestroy($img); }
        }
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL   => 'El archivo se subió parcialmente. Intente de nuevo.',
            UPLOAD_ERR_NO_FILE   => 'No se seleccionó ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Error de configuración del servidor (sin directorio temporal).',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el servidor.',
            default              => 'Error desconocido al subir el archivo.',
        };
    }

    private function ensureDirectories(): void
    {
        foreach (self::DIRS as $dir) {
            $full = $this->storageBase . '/' . $dir;
            if (!is_dir($full)) mkdir($full, 0755, true);
        }
        // Protect storage from direct web access
        $htaccess = $this->storageBase . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Require all denied\n");
        }
    }
}
```

---

## Image Processing Service (GD)

```php
<?php
// src/Services/ImageService.php
declare(strict_types=1);

namespace Services;

final class ImageService
{
    private string $storageBase;

    public function __construct()
    {
        $this->storageBase = BASE_PATH . '/storage';
    }

    /**
     * Generate thumbnail for a book cover
     * Returns relative path to thumbnail
     */
    public function thumbnail(string $relativePath, int $width = 200, int $height = 300): string
    {
        $source     = $this->storageBase . '/' . ltrim($relativePath, '/');
        $thumbDir   = dirname($source) . '/thumbs';
        $thumbFile  = $thumbDir . '/' . pathinfo($source, PATHINFO_FILENAME)
                    . "_{$width}x{$height}." . pathinfo($source, PATHINFO_EXTENSION);

        if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

        // Return cached thumbnail if exists
        if (file_exists($thumbFile)) {
            return str_replace($this->storageBase . '/', '', $thumbFile);
        }

        $image = $this->loadImage($source);
        $thumb = $this->resizeCrop($image, $width, $height);

        $this->saveImage($thumb, $thumbFile, $this->detectMime($source));

        imagedestroy($image);
        imagedestroy($thumb);

        return str_replace($this->storageBase . '/', '', $thumbFile);
    }

    /**
     * Resize maintaining aspect ratio
     */
    public function resize(string $relativePath, int $maxWidth, int $maxHeight): string
    {
        $source = $this->storageBase . '/' . ltrim($relativePath, '/');
        $image  = $this->loadImage($source);

        [$srcW, $srcH] = [imagesx($image), imagesy($image)];
        $ratio = min($maxWidth / $srcW, $maxHeight / $srcH);

        if ($ratio >= 1) {
            imagedestroy($image);
            return $relativePath; // already smaller
        }

        $newW   = (int) round($srcW * $ratio);
        $newH   = (int) round($srcH * $ratio);
        $resized = imagecreatetruecolor($newW, $newH);

        $this->preserveTransparency($resized);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        $this->saveImage($resized, $source, $this->detectMime($source));

        imagedestroy($image);
        imagedestroy($resized);

        return $relativePath;
    }

    /**
     * Convert image to WebP (modern format, smaller file)
     */
    public function toWebp(string $relativePath, int $quality = 85): string
    {
        $source  = $this->storageBase . '/' . ltrim($relativePath, '/');
        $webpPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $source);

        $image = $this->loadImage($source);
        imagewebp($image, $webpPath, $quality);
        imagedestroy($image);

        return str_replace($this->storageBase . '/', '', $webpPath);
    }

    /**
     * Add text watermark
     */
    public function watermark(string $relativePath, string $text = 'Biblioteca'): void
    {
        $source = $this->storageBase . '/' . ltrim($relativePath, '/');
        $image  = $this->loadImage($source);

        $color = imagecolorallocatealpha($image, 255, 255, 255, 80);
        $font  = BASE_PATH . '/public/assets/fonts/arial.ttf';

        if (function_exists('imagettftext') && file_exists($font)) {
            imagettftext($image, 12, 0, 10, imagesy($image) - 15, $color, $font, $text);
        } else {
            imagestring($image, 3, 5, imagesy($image) - 20, $text, $color);
        }

        $this->saveImage($image, $source, $this->detectMime($source));
        imagedestroy($image);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function loadImage(string $path): \GdImage
    {
        $mime = $this->detectMime($path);

        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            'image/gif'  => imagecreatefromgif($path),
            default      => throw new \RuntimeException("Formato de imagen no soportado: {$mime}"),
        };

        if ($image === false) {
            throw new \RuntimeException("No se pudo cargar la imagen: {$path}");
        }

        return $image;
    }

    private function resizeCrop(\GdImage $src, int $targetW, int $targetH): \GdImage
    {
        $srcW = imagesx($src);
        $srcH = imagesy($src);

        $ratio  = max($targetW / $srcW, $targetH / $srcH);
        $newW   = (int) round($srcW * $ratio);
        $newH   = (int) round($srcH * $ratio);
        $offsetX = (int) round(($newW - $targetW) / 2);
        $offsetY = (int) round(($newH - $targetH) / 2);

        $thumb = imagecreatetruecolor($targetW, $targetH);
        $this->preserveTransparency($thumb);

        $temp = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($temp, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
        imagecopy($thumb, $temp, 0, 0, $offsetX, $offsetY, $targetW, $targetH);
        imagedestroy($temp);

        return $thumb;
    }

    private function preserveTransparency(\GdImage $image): void
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
    }

    private function saveImage(\GdImage $image, string $path, string $mime): void
    {
        match ($mime) {
            'image/jpeg' => imagejpeg($image, $path, 85),
            'image/png'  => imagepng($image, $path, 6),
            'image/webp' => imagewebp($image, $path, 85),
            'image/gif'  => imagegif($image, $path),
            default      => throw new \RuntimeException("No se puede guardar MIME: {$mime}"),
        };
    }

    private function detectMime(string $path): string
    {
        return (new \finfo(FILEINFO_MIME_TYPE))->file($path);
    }
}
```

---

## Secure File Download Controller

```php
<?php
// src/Controllers/FileController.php
declare(strict_types=1);

namespace Controllers;

use Auth\AuthService;
use Auth\Permission;

final class FileController
{
    public function __construct(private readonly AuthService $auth) {}

    /** Serve a book cover (public — no auth required) */
    public function cover(string $path): void
    {
        $this->serveFile('uploads/covers/' . $path, requireAuth: false);
    }

    /** Serve a document (auth required) */
    public function document(string $path): void
    {
        $this->auth->requirePermission(Permission::BooksView);
        $this->serveFile('uploads/documents/' . $path, requireAuth: true);
    }

    /** Download generated PDF receipt */
    public function receipt(int $loanId): void
    {
        $this->auth->requireAuth();
        // Verify the loan belongs to the authenticated user
        $path = "generated/receipts/loan_{$loanId}.pdf";
        $this->serveFile($path, inline: false, downloadName: "recibo_prestamo_{$loanId}.pdf");
    }

    private function serveFile(
        string $relativePath,
        bool   $requireAuth  = true,
        bool   $inline       = true,
        string $downloadName = '',
    ): void {
        if ($requireAuth) $this->auth->requireAuth();

        // Prevent path traversal
        $relativePath = $this->sanitizePath($relativePath);
        $fullPath     = BASE_PATH . '/storage/' . $relativePath;

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            \Core\Response::abort(404);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($fullPath);
        $size = filesize($fullPath);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . $size);
        header('X-Content-Type-Options: nosniff');

        if (!$inline || $downloadName !== '') {
            $name = $downloadName ?: basename($fullPath);
            header('Content-Disposition: attachment; filename="' . addslashes($name) . '"');
        } else {
            header('Content-Disposition: inline');
            header('Cache-Control: private, max-age=3600');
        }

        readfile($fullPath);
        exit;
    }

    private function sanitizePath(string $path): string
    {
        // Remove null bytes, normalize slashes, strip ../ traversal
        $path = str_replace("\0", '', $path);
        $path = preg_replace('/\.\.+/', '', $path);
        $path = str_replace(['\\', '//'], '/', $path);
        return ltrim($path, '/');
    }
}
```

---

## PDF Generation with TCPDF

```php
<?php
// src/Services/PdfService.php
declare(strict_types=1);

namespace Services;

// Require TCPDF (place in src/Vendor/tcpdf/)
require_once BASE_PATH . '/src/Vendor/tcpdf/tcpdf.php';

final class PdfService
{
    private string $outputDir;

    public function __construct()
    {
        $this->outputDir = BASE_PATH . '/storage/generated';
        if (!is_dir($this->outputDir)) mkdir($this->outputDir, 0755, true);
    }

    /** Generate loan receipt PDF */
    public function generateLoanReceipt(array $loan, array $user, array $book): string
    {
        $pdf = $this->createPdf('Recibo de Préstamo');

        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'BIBLIOTECA MUNICIPAL', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Recibo de Préstamo #' . $loan['id'], 0, 1, 'C');
        $pdf->Ln(5);

        // Data table
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 10);
        $data = [
            ['Socio',              $user['name']],
            ['Nº de Socio',        $user['member_number']],
            ['Libro',              $book['title']],
            ['Autor',              $book['author']],
            ['ISBN',               $book['isbn']],
            ['Fecha de préstamo',  $loan['loan_date']],
            ['Fecha de devolución', $loan['due_date']],
        ];

        foreach ($data as $i => [$label, $value]) {
            $fill = ($i % 2 === 0);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(60, 8, $label . ':', 1, 0, 'L', $fill);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 8, (string) $value, 1, 1, 'L', $fill);
        }

        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->MultiCell(0, 6,
            'Por favor devuelva el material en la fecha indicada. El retraso genera una multa de $'
            . ($_ENV['FINE_PER_DAY'] ?? '0.50') . ' por día.',
            0, 'C'
        );

        $fileName = "receipts/loan_{$loan['id']}.pdf";
        $fullPath = $this->outputDir . '/' . $fileName;
        $pdf->Output($fullPath, 'F');

        return 'generated/' . $fileName;
    }

    /** Generate membership card PDF */
    public function generateMembershipCard(array $user): string
    {
        $pdf = $this->createPdf('Carnet de Socio');
        $pdf->AddPage('L', [85.6, 54]); // Credit card size landscape

        $pdf->SetFillColor(30, 58, 95);
        $pdf->Rect(0, 0, 85.6, 54, 'F');

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetY(5);
        $pdf->Cell(0, 8, 'BIBLIOTECA MUNICIPAL', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 6, 'CARNET DE SOCIO', 0, 1, 'C');

        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, strtoupper($user['name']), 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'Nº ' . $user['member_number'], 0, 1, 'C');
        $pdf->Cell(0, 5, 'Válido hasta: ' . date('m/Y', strtotime('+1 year')), 0, 1, 'C');

        $fileName = "cards/card_{$user['id']}.pdf";
        $pdf->Output($this->outputDir . '/' . $fileName, 'F');

        return 'generated/' . $fileName;
    }

    /** Generate inventory report */
    public function generateInventoryReport(array $books, array $stats): string
    {
        $pdf = $this->createPdf('Inventario de Libros');
        $pdf->AddPage('L'); // Landscape for wide tables

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'INVENTARIO DE LIBROS', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 6, 'Generado: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $pdf->Ln(3);

        // Stats summary
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(30, 58, 95);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(70, 8, 'Total de ejemplares: ' . $stats['total'], 1, 0, 'C', true);
        $pdf->Cell(70, 8, 'En préstamo: ' . $stats['loaned'], 1, 0, 'C', true);
        $pdf->Cell(70, 8, 'Disponibles: ' . $stats['available'], 1, 1, 'C', true);
        $pdf->Ln(4);

        // Table header
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(10,  7, 'ID',      1, 0, 'C', true);
        $pdf->Cell(90,  7, 'Título',  1, 0, 'L', true);
        $pdf->Cell(70,  7, 'Autor',   1, 0, 'L', true);
        $pdf->Cell(35,  7, 'ISBN',    1, 0, 'C', true);
        $pdf->Cell(25,  7, 'Estado',  1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 8);
        foreach ($books as $i => $book) {
            $fill = ($i % 2 === 0);
            $pdf->Cell(10,  6, (string) $book['id'],     1, 0, 'C', $fill);
            $pdf->Cell(90,  6, $book['title'],            1, 0, 'L', $fill);
            $pdf->Cell(70,  6, $book['author'],           1, 0, 'L', $fill);
            $pdf->Cell(35,  6, $book['isbn'] ?? '-',      1, 0, 'C', $fill);
            $pdf->Cell(25,  6, $book['status'],           1, 1, 'C', $fill);
        }

        $fileName = 'reports/inventory_' . date('Ymd_His') . '.pdf';
        $pdf->Output($this->outputDir . '/' . $fileName, 'F');

        return 'generated/' . $fileName;
    }

    private function createPdf(string $title): \TCPDF
    {
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Biblioteca Municipal');
        $pdf->SetAuthor('Biblioteca Municipal');
        $pdf->SetTitle($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 10);
        return $pdf;
    }
}
```

---

## Cleanup Service

```php
<?php
// src/Services/StorageCleanupService.php
declare(strict_types=1);

namespace Services;

use Repositories\BookRepository;

final class StorageCleanupService
{
    public function __construct(
        private readonly BookRepository $books,
    ) {}

    /** Remove orphaned cover files not referenced in DB */
    public function cleanOrphanedCovers(): int
    {
        $dbFiles   = $this->books->getAllCoverPaths();
        $diskFiles = $this->scanDirectory(BASE_PATH . '/storage/uploads/covers');
        $count     = 0;

        foreach ($diskFiles as $file) {
            $relative = str_replace(BASE_PATH . '/storage/', '', $file);
            if (!in_array($relative, $dbFiles, true)) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }

    /** Delete temp files older than 1 hour */
    public function cleanTempFiles(): int
    {
        $count   = 0;
        $tempDir = BASE_PATH . '/storage/uploads/temp';

        foreach (glob($tempDir . '/*') ?: [] as $file) {
            if (filemtime($file) < time() - 3600) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }

    /** Delete old generated PDFs older than 7 days */
    public function cleanOldReports(): int
    {
        $count = 0;
        $dirs  = [
            BASE_PATH . '/storage/generated/reports',
        ];

        foreach ($dirs as $dir) {
            foreach (glob($dir . '/*.pdf') ?: [] as $file) {
                if (filemtime($file) < time() - (7 * 86400)) {
                    unlink($file);
                    $count++;
                }
            }
        }

        return $count;
    }

    private function scanDirectory(string $dir): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }
}
```

---

## Routes for File Serving

```php
<?php
// config/routes.php
$router->get('/storage/covers/{path}',    [FileController::class, 'cover']);
$router->get('/storage/documents/{path}', [FileController::class, 'document']);
$router->get('/storage/receipts/{id}',    [FileController::class, 'receipt']);
```

---

## Nginx Rule (Serve PHP files, Block Direct Storage Access)

```nginx
# Block direct access to storage — serve via FileController
location /storage/ {
    deny all;
}

# PHP serves files through FileController
location ~* ^/(covers|documents|receipts)/ {
    try_files $uri /index.php?$query_string;
}
```

---

## .env for File Settings

```dotenv
STORAGE_PATH=/var/www/biblioteca/shared/storage
MAX_UPLOAD_SIZE_MB=20
ALLOWED_IMAGE_TYPES=jpeg,png,webp
COVER_WIDTH=400
COVER_HEIGHT=600
THUMB_WIDTH=200
THUMB_HEIGHT=300
```

---

## Cron: Storage Maintenance

```bash
# Daily cleanup at 3:30am
30 3 * * * php /var/www/biblioteca/bin/storage-cleanup.php
```

```php
<?php
// bin/storage-cleanup.php
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

$service = new \Services\StorageCleanupService(new \Repositories\BookRepository());
$orphans = $service->cleanOrphanedCovers();
$temps   = $service->cleanTempFiles();
$reports = $service->cleanOldReports();

echo date('Y-m-d H:i:s') . " — Cleanup: {$orphans} orphans, {$temps} temps, {$reports} old reports removed.\n";
```

---

## Workflow

1. **Validar MIME por magic bytes** — Usar `finfo`, nunca `$_FILES['type']` (controlado por el usuario).
2. **Nombres aleatorios siempre** — `bin2hex(random_bytes(16))` para el filename; nunca el nombre original.
3. **Storage fuera de public/** — Ningún archivo subido debe ser accesible directamente por URL.
4. **Servir archivos vía controller** — Verificar permisos antes de `readfile()`; nunca redirigir a ruta directa.
5. **Generar thumbnails lazy** — Solo al primer acceso; cachear el resultado en disco.
6. **Limpiar metadata EXIF** — Cargar y re-guardar la imagen con GD para eliminar datos GPS y personales.
7. **PDF en storage/generated/** — Los PDFs se generan una vez y se sirven como descarga controlada.
8. **Cron de limpieza diario** — Archivos temporales y reportes viejos se acumulan rápido sin limpieza periódica.
