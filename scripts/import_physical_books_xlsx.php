#!/usr/bin/env php
<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';
require BASE_PATH . '/vendor/autoload.php';

use Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

$xlsx = BASE_PATH . '/LIBROS FISICOS TOTALES ISTEL actualizado 11-03-2025DR.xlsx';
$limit = PHP_INT_MAX;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int) substr($arg, 8));
    }
}

if (!is_file($xlsx)) {
    fwrite(STDERR, "No existe el archivo XLSX: {$xlsx}" . PHP_EOL);
    exit(1);
}

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sheet = IOFactory::load($xlsx)->getActiveSheet();
$maxRow = $sheet->getHighestDataRow();

$headerRow = 3;
$headers = [];
foreach (range('A', 'L') as $col) {
    $headers[$col] = trim((string) $sheet->getCell($col . $headerRow)->getFormattedValue());
}

$get = static function (int $row, string $col) use ($sheet): string {
    return trim((string) $sheet->getCell($col . $row)->getFormattedValue());
};

$slugify = static function (string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $text);
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'general';
};

$normalizeIsbn13 = static function (string $raw): ?string {
    $digits = preg_replace('/\D+/', '', $raw) ?? '';
    if (strlen($digits) === 13) {
        return $digits;
    }
    return null;
};

$parseAuthors = static function (string $raw): string {
    $parts = preg_split('/[,;\/]+/', $raw) ?: [];
    $clean = [];
    foreach ($parts as $part) {
        $v = trim($part);
        if ($v !== '') {
            $clean[] = $v;
        }
    }
    if ($clean === []) {
        $clean = ['Autor no especificado'];
    }
    return json_encode(array_values(array_unique($clean)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '["Autor no especificado"]';
};

$toYear = static function (string $raw): ?int {
    if ($raw === '') {
        return null;
    }
    if (preg_match('/(19|20)\d{2}/', $raw, $m)) {
        $year = (int) $m[0];
        return ($year >= 1900 && $year <= 2100) ? $year : null;
    }
    return null;
};

$toCopies = static function (string $raw): int {
    $n = (int) preg_replace('/\D+/', '', $raw);
    return $n > 0 ? $n : 1;
};

$rows = [];
$categories = [];

for ($r = 4; $r <= $maxRow; $r++) {
    $tipo = mb_strtoupper($get($r, 'I'), 'UTF-8');
    $titulo = $get($r, 'D');
    if ($titulo === '') {
        continue;
    }
    if ($tipo !== '' && !str_contains($tipo, 'IMPRESO')) {
        continue;
    }

    $categoria = $get($r, 'J');
    if ($categoria === '') {
        $categoria = 'LIBRO';
    }
    $categories[$categoria] = true;

    $rows[] = [
        'codigo' => $get($r, 'B'),
        'isbn' => $get($r, 'C'),
        'title' => $titulo,
        'authors' => $get($r, 'E'),
        'publisher' => $get($r, 'F'),
        'edition' => $get($r, 'G'),
        'year' => $get($r, 'H'),
        'category' => $categoria,
        'copies' => $get($r, 'K'),
        'volume' => $get($r, 'L'),
    ];
}

if ($rows === []) {
    fwrite(STDERR, "No se encontraron filas importables." . PHP_EOL);
    exit(1);
}

$pdo->beginTransaction();
try {
    $insertCategory = $pdo->prepare(
        'INSERT INTO categories (name, slug, description, created_at) VALUES (?, ?, ?, NOW())'
    );

    foreach (array_keys($categories) as $name) {
        $slug = $slugify($name);
        $exists = $pdo->prepare('SELECT id FROM categories WHERE slug = ? OR name = ? LIMIT 1');
        $exists->execute([$slug, $name]);
        if (!$exists->fetch()) {
            $insertCategory->execute([$name, $slug, 'Categoría importada desde Excel de libros físicos']);
        }
    }

    $catRows = $pdo->query('SELECT id, name, slug FROM categories')->fetchAll(PDO::FETCH_ASSOC);
    $catMap = [];
    foreach ($catRows as $cat) {
        $catMap[mb_strtoupper((string) $cat['name'], 'UTF-8')] = (int) $cat['id'];
        $catMap[mb_strtoupper((string) $cat['slug'], 'UTF-8')] = (int) $cat['id'];
    }

    $existingIsbn = $pdo->query('SELECT isbn_13 FROM resources WHERE isbn_13 IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    $usedIsbn = array_fill_keys(array_map('strval', $existingIsbn), true);

    $insertResource = $pdo->prepare(
        'INSERT INTO resources (
            isbn_13, title, authors, publisher, edition_statement, publication_year,
            category_id, support_type, resource_type, description, language,
            replacement_cost, acquisition_date, is_new_acquisition,
            total_copies, available_copies, is_active, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, CURDATE(), 1,
            ?, ?, 1, NOW(), NOW()
        )'
    );

    $imported = 0;
    $skippedNoIsbn = 0;
    $skippedDuplicateIsbn = 0;
    foreach ($rows as $row) {
        if ($imported >= $limit) {
            break;
        }

        $catKey = mb_strtoupper($row['category'], 'UTF-8');
        $catId = $catMap[$catKey] ?? null;
        if ($catId === null) {
            $catId = $catMap['LIBRO'] ?? null;
        }
        if ($catId === null) {
            continue;
        }

        $isbn = $normalizeIsbn13($row['isbn']);
        if ($isbn === null) {
            $skippedNoIsbn++;
            continue;
        }
        if (isset($usedIsbn[$isbn])) {
            $skippedDuplicateIsbn++;
            continue;
        }
        $usedIsbn[$isbn] = true;

        $title = $row['title'];
        $authors = $parseAuthors($row['authors']);
        $publisher = $row['publisher'] !== '' ? $row['publisher'] : null;
        $edition = $row['edition'] !== '' ? $row['edition'] : null;
        $year = $toYear($row['year']);
        $copies = $toCopies($row['copies']);

        $descParts = ['Importado desde LIBROS FISICOS TOTALES ISTEL'];
        if ($row['codigo'] !== '') {
            $descParts[] = 'Código: ' . $row['codigo'];
        }
        if ($row['volume'] !== '') {
            $descParts[] = 'Volumen: ' . $row['volume'];
        }
        $description = implode(' | ', $descParts);

        $insertResource->execute([
            $isbn,
            $title,
            $authors,
            $publisher,
            $edition,
            $year,
            $catId,
            'physical',
            'book',
            $description,
            'es',
            20.00,
            $copies,
            $copies,
        ]);

        $imported++;
    }

    $pdo->commit();

    echo "Categorías detectadas: " . count($categories) . PHP_EOL;
    echo "Libros físicos importados: {$imported}" . PHP_EOL;
    echo "Omitidos sin ISBN válido: {$skippedNoIsbn}" . PHP_EOL;
    echo "Omitidos por ISBN duplicado: {$skippedDuplicateIsbn}" . PHP_EOL;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Error de importación: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
