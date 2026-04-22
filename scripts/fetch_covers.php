<?php
/**
 * fetch_covers.php
 * Busca portadas en Open Library y Google Books para todos los recursos sin portada.
 * Uso: php scripts/fetch_covers.php [--limit=100] [--dry-run]
 */

$opts    = getopt('', ['limit:', 'dry-run', 'id:']);
$limit   = isset($opts['limit'])  ? (int)$opts['limit']  : 50;
$dryRun  = isset($opts['dry-run']);
$onlyId  = isset($opts['id'])     ? (int)$opts['id']     : 0;

$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=biblioteca;charset=utf8mb4',
    'biblioteca_user',
    'Biblioteca2026!',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$coverDir = __DIR__ . '/../public/uploads/resources/';
if (!is_dir($coverDir)) {
    mkdir($coverDir, 0775, true);
}

// ──────────────────────────────────────────────
// Obtener libros sin portada
// ──────────────────────────────────────────────
if ($onlyId > 0) {
    $sql = 'SELECT id, title, authors, isbn_13 FROM resources WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$onlyId]);
} else {
    $sql = 'SELECT id, title, authors, isbn_13
            FROM resources
            WHERE (cover_image IS NULL OR cover_image = "")
              AND is_active = 1
            ORDER BY id ASC
            LIMIT ' . $limit;
    $stmt = $pdo->query($sql);
}
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Procesando " . count($books) . " recursos sin portada...\n\n";

$found = 0; $notFound = 0;

foreach ($books as $book) {
    $id     = $book['id'];
    $title  = $book['title'];
    $isbn   = trim($book['isbn_13'] ?? '');
    $author = trim($book['authors'] ?? '');

    echo "[$id] $title";

    $imageUrl = null;
    $source   = '';

    // ── 1. Open Library por ISBN ───────────────
    if ($isbn !== '') {
        $url = "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg?default=false";
        $head = @get_headers($url, 1);
        if ($head && strpos($head[0], '200') !== false) {
            $imageUrl = $url;
            $source   = 'ol_isbn';
        }
    }

    // ── 2. Open Library por título (search API) ─
    if (!$imageUrl) {
        $query = urlencode($title . ($author ? ' ' . $author : ''));
        $apiUrl = "https://openlibrary.org/search.json?q={$query}&limit=1&fields=cover_i";
        $json = @file_get_contents($apiUrl, false, stream_context_create([
            'http' => ['timeout' => 8, 'user_agent' => 'BibliotecaIST/1.0']
        ]));
        if ($json) {
            $data = json_decode($json, true);
            $coverId = $data['docs'][0]['cover_i'] ?? null;
            if ($coverId) {
                $imageUrl = "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg";
                $source   = 'ol_search';
            }
        }
    }

    // ── 3. Google Books por ISBN ────────────────
    if (!$imageUrl && $isbn !== '') {
        $apiUrl = "https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}&maxResults=1";
        $json = @file_get_contents($apiUrl, false, stream_context_create([
            'http' => ['timeout' => 8, 'user_agent' => 'BibliotecaIST/1.0']
        ]));
        if ($json) {
            $data = json_decode($json, true);
            $thumb = $data['items'][0]['volumeInfo']['imageLinks']['thumbnail'] ?? null;
            if ($thumb) {
                // Pedir imagen más grande
                $thumb = str_replace('zoom=1', 'zoom=3', $thumb);
                $thumb = str_replace('http://', 'https://', $thumb);
                $imageUrl = $thumb;
                $source   = 'google_isbn';
            }
        }
    }

    // ── 4. Google Books por título ──────────────
    if (!$imageUrl) {
        $query  = urlencode($title . ($author ? '+inauthor:' . $author : ''));
        $apiUrl = "https://www.googleapis.com/books/v1/volumes?q={$query}&maxResults=1&langRestrict=es";
        $json = @file_get_contents($apiUrl, false, stream_context_create([
            'http' => ['timeout' => 8, 'user_agent' => 'BibliotecaIST/1.0']
        ]));
        if ($json) {
            $data = json_decode($json, true);
            $thumb = $data['items'][0]['volumeInfo']['imageLinks']['thumbnail'] ?? null;
            if ($thumb) {
                $thumb = str_replace('zoom=1', 'zoom=3', $thumb);
                $thumb = str_replace('http://', 'https://', $thumb);
                $imageUrl = $thumb;
                $source   = 'google_search';
            }
        }
    }

    if (!$imageUrl) {
        echo " → no encontrada\n";
        $notFound++;
        usleep(300000); // 0.3s
        continue;
    }

    // ── Descargar imagen ────────────────────────
    $ext      = 'jpg';
    $ts       = date('YmdHis');
    $filename = "resource_auto_{$id}_{$source}_{$ts}.{$ext}";
    $destPath = $coverDir . $filename;
    $dbPath   = '/uploads/resources/' . $filename;

    if ($dryRun) {
        echo " → [DRY-RUN] {$source}: {$imageUrl}\n";
        $found++;
        usleep(200000);
        continue;
    }

    $imgData = @file_get_contents($imageUrl, false, stream_context_create([
        'http' => ['timeout' => 10, 'user_agent' => 'BibliotecaIST/1.0']
    ]));

    if (!$imgData || strlen($imgData) < 2000) {
        echo " → imagen inválida ($source)\n";
        $notFound++;
        usleep(300000);
        continue;
    }

    file_put_contents($destPath, $imgData);

    $upd = $pdo->prepare('UPDATE resources SET cover_image = ? WHERE id = ?');
    $upd->execute([$dbPath, $id]);

    echo " → OK ({$source}, " . round(strlen($imgData)/1024) . "KB)\n";
    $found++;
    usleep(400000); // 0.4s pausa para no sobrecargar APIs
}

echo "\n────────────────────────────────\n";
echo "Encontradas : $found\n";
echo "No halladas : $notFound\n";
echo "────────────────────────────────\n";
