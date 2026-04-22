<?php
// app/Controllers/BarcodeController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;
use Helpers\Isbn;

final class BarcodeController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    // -------------------------------------------------------------------------
    // GET /admin/labels — HTML print page for book labels
    // -------------------------------------------------------------------------
    public function labels(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();

        if ($request->method() === 'POST') {
            if ($request->post('reset', '') === '1') {
                Session::remove('admin.labels.search');
                Session::remove('admin.labels.page');
            } else {
                Session::set('admin.labels.search', trim((string) $request->post('q', '')));
                Session::set('admin.labels.page', max(1, (int) $request->post('page', 1)));
            }

            return Response::redirect(BASE_URL . '/admin/labels');
        }

        $legacyQ = $request->get('q', null);
        $legacyPage = $request->get('page', null);
        if ($legacyQ !== null || $legacyPage !== null || $request->get('per_page', null) !== null) {
            Session::set('admin.labels.search', trim((string) ($legacyQ ?? '')));
            Session::set('admin.labels.page', max(1, (int) ($legacyPage ?? 1)));
            return Response::redirect(BASE_URL . '/admin/labels');
        }

        $search = trim((string) Session::get('admin.labels.search', ''));
        $page = max(1, (int) Session::get('admin.labels.page', 1));
        $perPage = 5;

        $where = " WHERE support_type = 'physical' AND is_active = 1";
        $params = [];
        if ($search !== '') {
            $where .= " AND (title LIKE :term_title OR isbn_13 LIKE :term_isbn OR location LIKE :term_location)";
            $like = '%' . $search . '%';
            $params[':term_title'] = $like;
            $params[':term_isbn'] = $like;
            $params[':term_location'] = $like;
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM resources" . $where);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value, \PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
            Session::set('admin.labels.page', $page);
        }
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT id, title, authors, COALESCE(isbn_13, '') AS isbn, COALESCE(location, '') AS classification_code
                FROM resources"
            . $where
            . " ORDER BY title ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $resources = $stmt->fetchAll();

        $from = $total === 0 ? 0 : ($offset + 1);
        $to = min($offset + $perPage, $total);

        return Response::html($this->view->render('admin/labels/index', [
            'title'     => 'Etiquetas – ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'  => $settings,
            'auth_user' => $authUser,
            'resources' => $resources,
            'search'    => $search,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => $lastPage,
            'total'     => $total,
            'from'      => $from,
            'to'        => $to,
        ], 'layouts/panel'));
    }

    // -------------------------------------------------------------------------
    // GET /admin/barcode/{isbn} — Stream EAN-13 barcode PNG
    // -------------------------------------------------------------------------
    public function barcode(Request $request, string $isbn): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            return Response::redirect(BASE_URL . '/login');
        }

        // Sanitise and normalise
        $clean = preg_replace('/[^0-9X]/i', '', strtoupper($isbn)) ?? '';

        // Accept 13-digit raw or normalised ISBN
        $isbn13 = Isbn::normalize($clean);

        // Fallback: if we have 13 raw digits, use them directly (covers EAN-13 that aren't ISBN)
        if ($isbn13 === null && strlen($clean) === 13 && ctype_digit($clean)) {
            $isbn13 = $clean;
        }

        if ($isbn13 === null) {
            return new Response('', 400, ['Content-Type' => 'text/plain']);
        }

        // Serve from cache if available
        $cacheDir  = BASE_PATH . '/storage/barcodes';
        $cachePath = $cacheDir . '/' . $isbn13 . '.png';

        if (is_file($cachePath)) {
            return new Response(
                (string) file_get_contents($cachePath),
                200,
                [
                    'Content-Type'  => 'image/png',
                    'Cache-Control' => 'public, max-age=31536000',
                ]
            );
        }

        $png = $this->generateEan13($isbn13);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents($cachePath, $png);

        return new Response($png, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /admin/qr/{type}/{id} — Stream Code-128 barcode PNG for an entity
    // -------------------------------------------------------------------------
    public function qr(Request $request, string $type, string $id): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            return Response::redirect(BASE_URL . '/login');
        }

        $allowedTypes = ['resource', 'user'];
        if (!in_array($type, $allowedTypes, true)) {
            return new Response('', 400, ['Content-Type' => 'text/plain']);
        }

        $id = preg_replace('/[^0-9]/', '', $id) ?? '';
        if ($id === '') {
            return new Response('', 400, ['Content-Type' => 'text/plain']);
        }

        // Encode as TYPE:ID — scannable by any Code-128 reader
        $data = strtoupper($type) . ':' . $id;

        $cacheDir  = BASE_PATH . '/storage/qrcodes';
        $cachePath = $cacheDir . '/' . $type . '_' . $id . '.png';

        if (is_file($cachePath)) {
            return new Response(
                (string) file_get_contents($cachePath),
                200,
                [
                    'Content-Type'  => 'image/png',
                    'Cache-Control' => 'public, max-age=86400',
                ]
            );
        }

        $png = $this->generateCode128($data, 280, 80);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents($cachePath, $png);

        return new Response($png, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /admin/user-card/{id} — PDF membership card
    // -------------------------------------------------------------------------
    public function userCard(Request $request, string $id): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $userId = (int) $id;
        if ($userId <= 0) {
            Session::flash('error', 'Usuario no válido.');
            return Response::redirect(BASE_URL . '/admin/users');
        }

        $stmt = $this->db->prepare(
            "SELECT id, name, email, user_number, role, status FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            Session::flash('error', 'Usuario no encontrado.');
            return Response::redirect(BASE_URL . '/admin/users');
        }

        $settings    = $this->panelSettings();
        $libraryName = $settings['library_name'] ?? 'Biblioteca';

        $tcpdfPath = '/usr/share/php/tcpdf/tcpdf.php';
        if (!class_exists('TCPDF')) {
            if (!file_exists($tcpdfPath)) {
                // Fallback: redirect to HTML print view
                Session::flash('error', 'TCPDF no está disponible. Instale la extensión php-tcpdf.');
                return Response::redirect(BASE_URL . '/admin/users');
            }
            require_once $tcpdfPath;
        }

        // Code-128 barcode for member number
        $memberNumber = $user['user_number'] !== '' && $user['user_number'] !== null
            ? (string) $user['user_number']
            : 'U' . str_pad((string) $user['id'], 6, '0', STR_PAD_LEFT);

        $barPng   = $this->generateCode128($memberNumber, 240, 50);
        $barData  = base64_encode($barPng);

        // Credit-card size: 85.6 x 54 mm, landscape
        $pdf = new \TCPDF('L', 'mm', [85.6, 54], true, 'UTF-8');
        $pdf->SetCreator($libraryName);
        $pdf->SetTitle('Carnet – ' . $user['name']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(3, 3, 3);
        $pdf->AddPage();

        // Dark blue background
        $pdf->SetFillColor(30, 58, 95);
        $pdf->Rect(0, 0, 85.6, 54, 'F');

        // Library name
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(3, 4);
        $pdf->Cell(65, 5, strtoupper($libraryName), 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetXY(3, 9);
        $pdf->Cell(65, 4, 'CARNET DE SOCIO', 0, 1, 'L');

        // Member name
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY(3, 22);
        $pdf->Cell(79, 7, mb_strtoupper((string) $user['name'], 'UTF-8'), 0, 1, 'L');

        // Member number
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(3, 29);
        $pdf->Cell(79, 5, 'Nº ' . $memberNumber, 0, 1, 'L');

        // Role
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetXY(3, 34);
        $pdf->Cell(79, 4, ucfirst((string) $user['role']), 0, 1, 'L');

        // Barcode
        $pdf->Image(
            '@' . base64_decode($barData),
            3, 39, 55, 12,
            'PNG', '', '', false, 150
        );

        $pdfContent = $pdf->Output('', 'S');

        $filename = 'carnet_' . preg_replace('/[^a-z0-9_]/i', '_', (string) $user['name']) . '.pdf';

        return new Response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    // =========================================================================
    // Private: EAN-13 barcode generator (GD, no dependencies)
    // =========================================================================

    private function generateEan13(string $isbn13, int $width = 300, int $height = 120): string
    {
        $lCode = ['0001101','0011001','0010011','0111101','0100011',
                  '0110001','0101111','0111011','0110111','0001011'];
        $gCode = ['0100111','0110011','0011011','0100001','0011101',
                  '0111001','0000101','0010001','0001001','0010111'];
        $rCode = ['1110010','1100110','1101100','1000010','1011100',
                  '1001110','1010000','1000100','1001000','1110100'];

        $parityMap = [
            '0' => 'LLLLLL', '1' => 'LLGLGG', '2' => 'LLGGLG', '3' => 'LLGGGL',
            '4' => 'LGLLGG', '5' => 'LGGLLG', '6' => 'LGGGLL', '7' => 'LGLGLG',
            '8' => 'LGLGGL', '9' => 'LGGLGL',
        ];

        // Build bit string
        $firstDigit = $isbn13[0];
        $parity     = $parityMap[$firstDigit] ?? 'LLLLLL';
        $bits       = '101'; // start guard

        for ($i = 1; $i <= 6; $i++) {
            $d     = (int) $isbn13[$i];
            $bits .= ($parity[$i - 1] === 'L') ? $lCode[$d] : $gCode[$d];
        }
        $bits .= '01010'; // centre guard

        for ($i = 7; $i <= 12; $i++) {
            $bits .= $rCode[(int) $isbn13[$i]];
        }
        $bits .= '101'; // end guard

        $img   = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        $gray  = imagecolorallocate($img, 80, 80, 80);
        imagefill($img, 0, 0, $white);

        $totalBits = strlen($bits);
        $barWidth  = max(1, (int) floor($width / ($totalBits + 16)));
        $totalW    = $totalBits * $barWidth;
        $offsetX   = (int) floor(($width - $totalW) / 2);
        $barHeight = $height - 18;

        for ($i = 0; $i < $totalBits; $i++) {
            if ($bits[$i] === '1') {
                imagefilledrectangle(
                    $img,
                    $offsetX + $i * $barWidth, 6,
                    $offsetX + $i * $barWidth + $barWidth - 1, $barHeight,
                    $black
                );
            }
        }

        // ISBN text below bars
        $fontSize = 2;
        $textW    = imagefontwidth($fontSize) * 13;
        $textX    = (int) floor(($width - $textW) / 2);
        imagestring($img, $fontSize, $textX, $height - 13, $isbn13, $gray);

        ob_start();
        imagepng($img);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        return $png;
    }

    // =========================================================================
    // Private: Code-128B barcode generator (GD, no dependencies)
    // =========================================================================

    private function generateCode128(string $data, int $width = 280, int $height = 80): string
    {
        // Code-128B patterns (ASCII 32–127, start/stop included at end)
        $patterns = [
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
        ];
        // Start Code B = index 104
        $startBPattern = '11010010000';
        // Stop pattern
        $stopPattern   = '1100011101011';

        $startB   = 104;
        $checksum = $startB;
        $bits     = $startBPattern;

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $code      = ord($data[$i]) - 32;
            if ($code < 0 || $code >= count($patterns)) {
                $code = 0; // fallback: space
            }
            $checksum += $code * ($i + 1);
            $bits     .= $patterns[$code];
        }

        $checksumIdx = $checksum % 103;
        if (isset($patterns[$checksumIdx])) {
            $bits .= $patterns[$checksumIdx];
        }
        $bits .= $stopPattern;

        $img   = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        $totalBits = strlen($bits);
        $barWidth  = max(1, (int) floor($width / ($totalBits + 10)));
        $totalW    = $totalBits * $barWidth;
        $offsetX   = (int) floor(($width - $totalW) / 2);
        $barHeight = $height - 16;

        for ($i = 0; $i < $totalBits; $i++) {
            if ($bits[$i] === '1') {
                imagefilledrectangle(
                    $img,
                    $offsetX + $i * $barWidth, 3,
                    $offsetX + $i * $barWidth + $barWidth - 1, $barHeight,
                    $black
                );
            }
        }

        // Text label below bars
        $fontSize  = 1;
        $maxChars  = min(strlen($data), 30);
        $display   = strlen($data) > 30 ? substr($data, 0, 27) . '...' : $data;
        $textW     = imagefontwidth($fontSize) * strlen($display);
        $textX     = max(0, (int) floor(($width - $textW) / 2));
        imagestring($img, $fontSize, $textX, $height - 13, $display, $black);

        ob_start();
        imagepng($img);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        return $png;
    }
}
