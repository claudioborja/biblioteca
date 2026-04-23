<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$file = __DIR__ . '/../LIBROS FISICOS TOTALES ISTEL actualizado 11-03-2025DR.xlsx';
$ss = PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$ws = $ss->getActiveSheet();

for ($r = 1; $r <= 15; $r++) {
    $vals = [];
    foreach (range('A', 'L') as $c) {
        $vals[$c] = trim((string) $ws->getCell($c . $r)->getFormattedValue());
    }
    echo 'R' . $r . ' ' . json_encode($vals, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}
