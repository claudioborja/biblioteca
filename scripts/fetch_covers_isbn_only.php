#!/usr/bin/env php
<?php
declare(strict_types=1);

use Core\Database;

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

$opts = getopt('', ['limit:', 'dry-run']);
$limit = isset($opts['limit']) ? (int) $opts['limit'] : 1000;
$dryRun = isset($opts['dry-run']);

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$coverDir = BASE_PATH . '/public/uploads/resources';
if (!is_dir($coverDir)) {
    mkdir($coverDir, 0775, true);
}

$stmt = $pdo->prepare(
    'SELECT id, title, isbn_13
     FROM resources
     WHERE is_active = 1
       AND isbn_13 IS NOT NULL
       AND isbn_13 <> ""
       AND (cover_image IS NULL OR cover_image = "")
     ORDER BY id ASC
     LIMIT ?'
);
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo 'Procesando ' . count($books) . " libros con ISBN sin portada...\n\n";

$found = 0;
$notFound = 0;
$invalid = 0;

$httpCtx = stream_context_create([
    'http' => [
        'timeout' => 2,
        'user_agent' => 'BibliotecaIST/1.0',
        'ignore_errors' => true,
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
]);

foreach ($books as $book) {
    $id = (int) $book['id'];
    $title = (string) $book['title'];
    $isbn = trim((string) $book['isbn_13']);

    echo "[$id] $title";

    $candidates = [
        ['url' => "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg?default=false", 'source' => 'ol_isbn'],
    ];

    $apiUrl = "https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}&maxResults=1";
    $json = @file_get_contents($apiUrl, false, $httpCtx);
    if (is_string($json) && $json !== '') {
        $data = json_decode($json, true);
        $thumb = $data['items'][0]['volumeInfo']['imageLinks']['thumbnail'] ?? null;
        if (is_string($thumb) && $thumb !== '') {
            $thumb = str_replace('http://', 'https://', $thumb);
            $thumb = preg_replace('/&zoom=\d+/', '&zoom=3', $thumb) ?: $thumb;
            $candidates[] = ['url' => $thumb, 'source' => 'google_isbn'];
        }
    }

    $saved = false;
    foreach ($candidates as $candidate) {
        $imgData = @file_get_contents($candidate['url'], false, $httpCtx);
        if (!is_string($imgData) || strlen($imgData) < 2000) {
            continue;
        }

        $filename = sprintf('resource_auto_%d_%s_%s.jpg', $id, $candidate['source'], date('YmdHis'));
        $destPath = $coverDir . '/' . $filename;
        $dbPath = '/uploads/resources/' . $filename;

        if ($dryRun) {
            echo " -> [DRY-RUN] {$candidate['source']}\n";
            $saved = true;
            $found++;
            break;
        }

        file_put_contents($destPath, $imgData);
        $upd = $pdo->prepare('UPDATE resources SET cover_image = ? WHERE id = ?');
        $upd->execute([$dbPath, $id]);
        echo ' -> OK (' . $candidate['source'] . ', ' . round(strlen($imgData) / 1024) . "KB)\n";
        $saved = true;
        $found++;
        break;
    }

    if (!$saved) {
        $notFound++;
        echo " -> no encontrada\n";
    }
}

echo "\n--------------------------------\n";
echo "Encontradas : {$found}\n";
echo "No halladas : {$notFound}\n";
echo "Inválidas   : {$invalid}\n";
echo "--------------------------------\n";
