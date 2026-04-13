<?php
// app/Controllers/NewsController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;
use Middleware\CsrfMiddleware;

final class NewsController
{
    private \PDO $db;
    private View $view;

    public function __construct()
    {
        $this->db   = Database::connect();
        $this->view = new View(BASE_PATH . '/views');
    }

    /**
     * Listado público de noticias — GET /news
     */
    public function index(Request $request): Response
    {
        $settings = $this->loadSettings([
            'library_name', 'library_favicon', 'library_logo',
            'library_slogan', 'library_address', 'library_phone',
            'library_email', 'library_schedule',
        ]);

        $q = trim((string) $request->get('q', ''));
        $tablePage = max(1, (int) $request->get('table_page', 1));
        $tablePerPage = 5;

        $stmt = $this->db->prepare("
            SELECT n.id, n.title, n.slug, n.excerpt, n.cover_image, n.published_at,
                   u.name AS author_name
            FROM news n
            LEFT JOIN users u ON u.id = n.author_id
            WHERE n.is_published = 1 AND n.published_at <= NOW()
            ORDER BY n.published_at DESC
            LIMIT 4
        ");
        $stmt->execute();
        $latestNews = $stmt->fetchAll();

        $featured = $latestNews[0] ?? null;
        $secondary = array_slice($latestNews, 1, 3);

        $tableWhere = "n.is_published = 1 AND n.published_at <= NOW()";
        $tableParams = [];
        if ($q !== '') {
            $tableWhere .= " AND (n.title LIKE ? OR COALESCE(n.excerpt, '') LIKE ? OR COALESCE(u.name, '') LIKE ?)";
            $like = '%' . $q . '%';
            $tableParams[] = $like;
            $tableParams[] = $like;
            $tableParams[] = $like;
        }

        $tableCountStmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM news n
            LEFT JOIN users u ON u.id = n.author_id
            WHERE $tableWhere
        ");
        $tableCountStmt->execute($tableParams);
        $tableTotal = (int) $tableCountStmt->fetchColumn();
        $tableTotalPages = max(1, (int) ceil($tableTotal / $tablePerPage));
        if ($tablePage > $tableTotalPages) {
            $tablePage = $tableTotalPages;
        }
        $tableOffset = ($tablePage - 1) * $tablePerPage;

        $tableStmt = $this->db->prepare("
            SELECT n.id, n.title, n.slug, n.published_at, u.name AS author_name
            FROM news n
            LEFT JOIN users u ON u.id = n.author_id
            WHERE $tableWhere
            ORDER BY n.published_at DESC
            LIMIT ? OFFSET ?
        ");
        $tableStmt->execute(array_merge($tableParams, [$tablePerPage, $tableOffset]));
        $tableNews = $tableStmt->fetchAll();

        $total = (int) $this->db->query(
            "SELECT COUNT(*) FROM news WHERE is_published = 1 AND published_at <= NOW()"
        )->fetchColumn();

        $html = $this->view->render('public/news-index', [
            'title'      => 'Noticias',
            'settings'   => $settings,
            'featured'   => $featured,
            'secondary'  => $secondary,
            'table_news' => $tableNews,
            'table_page' => $tablePage,
            'table_per_page' => $tablePerPage,
            'table_total' => $tableTotal,
            'table_total_pages' => $tableTotalPages,
            'q' => $q,
            'total'      => $total,
        ]);

        return Response::html($html);
    }

    /**
     * Detalle de una noticia — GET /news/{slug}
     */
    public function show(Request $request, string $slug = ''): Response
    {
        $stmt = $this->db->prepare("
            SELECT n.*, u.name AS author_name
            FROM news n
            LEFT JOIN users u ON u.id = n.author_id
            WHERE n.slug = ? AND n.is_published = 1 AND n.published_at <= NOW()
        ");
        $stmt->execute([$slug]);
        $article = $stmt->fetch();

        if (!$article) {
            return Response::html('<h1>Noticia no encontrada</h1>', 404);
        }

        // Related news (excluding current, up to 3)
        $stmtRel = $this->db->prepare("
            SELECT id, title, slug, excerpt, cover_image, published_at
            FROM news
            WHERE is_published = 1 AND published_at <= NOW() AND id != ?
            ORDER BY published_at DESC
            LIMIT 3
        ");
        $stmtRel->execute([$article['id']]);
        $related = $stmtRel->fetchAll();

        $settings = $this->loadSettings([
            'library_name', 'library_favicon', 'library_logo',
            'library_slogan', 'library_address', 'library_phone',
            'library_email', 'library_schedule',
        ]);

        $html = $this->view->render('public/news-show', [
            'title'    => $article['title'],
            'settings' => $settings,
            'article'  => $article,
            'related'  => $related,
        ]);

        return Response::html($html);
    }

    public function adminIndex(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();

        $news = $this->db->query(
            "SELECT n.id, n.title, n.slug, n.excerpt, n.cover_image, n.is_published, n.published_at, n.created_at,
                    u.name AS author_name
             FROM news n
             LEFT JOIN users u ON u.id = n.author_id
             ORDER BY n.created_at DESC
             LIMIT 80"
        )->fetchAll();

        $stats = [
            'total' => count($news),
            'published' => count(array_filter($news, fn(array $n): bool => (int) $n['is_published'] === 1)),
            'drafts' => count(array_filter($news, fn(array $n): bool => (int) $n['is_published'] === 0)),
            'scheduled' => count(array_filter($news, fn(array $n): bool => !empty($n['published_at']) && strtotime((string) $n['published_at']) > time())),
        ];

        return Response::html($this->view->render('admin/news/index', [
            'title' => 'Noticias - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'news' => $news,
            'stats' => $stats,
        ], 'layouts/panel'));
    }

    public function create(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();
        $old = Session::getFlash('news_form_old', [
            'title' => '',
            'slug' => '',
            'excerpt' => '',
            'content' => '',
            'cover_image' => '',
            'is_published' => '0',
            'published_at' => '',
        ]);

        return Response::html($this->view->render('admin/news/edit', [
            'title' => 'Nueva noticia - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'news' => $old,
            'news_id' => 0,
            'is_create' => true,
            'csrf' => CsrfMiddleware::token(),
        ], 'layouts/panel'));
    }

    public function store(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $title = trim((string) $request->post('title', ''));
        $slugInput = trim((string) $request->post('slug', ''));
        $excerpt = trim((string) $request->post('excerpt', ''));
        $content = trim((string) $request->post('content', ''));
        $contentPlain = $this->plainTextFromHtml($content);
        $coverImage = '';
        $coverImageFile = $request->file('cover_image');
        $isPublished = $request->post('is_published', '0') === '1' ? 1 : 0;
        $publishedAtRaw = trim((string) $request->post('published_at', ''));

        $old = [
            'title' => $title,
            'slug' => $slugInput,
            'excerpt' => $excerpt,
            'content' => $content,
            'cover_image' => '',
            'is_published' => (string) $isPublished,
            'published_at' => $publishedAtRaw,
        ];

        $errors = [];
        if ($title === '') {
            $errors[] = 'El título es obligatorio.';
        }
        if ($contentPlain === '') {
            $errors[] = 'El contenido es obligatorio.';
        }
        if (mb_strlen($excerpt) > 500) {
            $errors[] = 'El extracto no puede superar 500 caracteres.';
        }

        if ($coverImageFile !== null && (int) ($coverImageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $coverValidationError = $this->validateCoverImageFile($coverImageFile);
            if ($coverValidationError !== null) {
                $errors[] = $coverValidationError;
            }
        }

        $publishedAt = null;
        if ($publishedAtRaw !== '') {
            $ts = strtotime($publishedAtRaw);
            if ($ts === false) {
                $errors[] = 'La fecha de publicación no es válida.';
            } else {
                $publishedAt = date('Y-m-d H:i:s', $ts);
            }
        } elseif ($isPublished === 1) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $baseSlug = $slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($title);
        $slug = $this->uniqueNewsSlug($baseSlug);

        if ($errors !== []) {
            Session::flash('news_form_old', $old);
            Session::flash('error', implode(' ', $errors));
            return Response::redirect(BASE_URL . '/admin/news/create');
        }

        if ($coverImageFile !== null && (int) ($coverImageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadedPath = $this->storeCoverImage($coverImageFile);
            if ($uploadedPath === null) {
                Session::flash('news_form_old', $old);
                Session::flash('error', 'No se pudo guardar la imagen de portada.');
                return Response::redirect(BASE_URL . '/admin/news/create');
            }
            $coverImage = $uploadedPath;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO news (title, slug, excerpt, content, cover_image, is_published, published_at, author_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $title,
            $slug,
            $excerpt !== '' ? $excerpt : null,
            $content,
            $coverImage !== '' ? $coverImage : null,
            $isPublished,
            $publishedAt,
            (int) ($authUser['id'] ?? 0),
        ]);

        Session::flash('success', 'Noticia creada correctamente.');
        return Response::redirect(BASE_URL . '/admin/news');
    }

    public function edit(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $newsId = (int) $id;
        if ($newsId <= 0) {
            Session::flash('error', 'Noticia inválida.');
            return Response::redirect(BASE_URL . '/admin/news');
        }

        if ($request->get('modal', '') === '1' && $request->get('saved', '') === '1') {
            $payload = Session::getFlash('news_edit_payload', null);
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return Response::html(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Guardado</title></head><body><script>window.parent.postMessage({type:"news-edit-saved", payload:' . ($jsonPayload ?: 'null') . '},"*");</script></body></html>'
            );
        }

        $news = $this->findNewsById($newsId);
        if ($news === null) {
            Session::flash('error', 'No se encontró la noticia.');
            return Response::redirect(BASE_URL . '/admin/news');
        }

        $old = Session::getFlash('news_form_old', []);
        if (is_array($old) && $old !== []) {
            $news = array_merge($news, $old);
        }

        $settings = $this->panelSettings();
        return Response::html($this->view->render('admin/news/edit', [
            'title' => 'Editar noticia - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'news' => $news,
            'news_id' => $newsId,
            'is_create' => false,
            'csrf' => CsrfMiddleware::token(),
        ], $request->get('modal', '') === '1' ? 'layouts/modal' : 'layouts/panel'));
    }

    public function update(Request $request, string $id = ''): Response
    {
        $isModalRequest = $request->post('modal', '') === '1' || $request->get('modal', '') === '1';
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $newsId = (int) $id;
        if ($newsId <= 0) {
            Session::flash('error', 'Noticia inválida.');
            return Response::redirect(BASE_URL . '/admin/news');
        }

        $current = $this->findNewsById($newsId);
        if ($current === null) {
            Session::flash('error', 'No se encontró la noticia.');
            return Response::redirect(BASE_URL . '/admin/news');
        }

        $title = trim((string) $request->post('title', ''));
        $slugInput = trim((string) $request->post('slug', ''));
        $excerpt = trim((string) $request->post('excerpt', ''));
        $content = trim((string) $request->post('content', ''));
        $contentPlain = $this->plainTextFromHtml($content);
        $existingCoverImage = trim((string) $request->post('existing_cover_image', ''));
        $coverImage = $existingCoverImage !== ''
            ? $existingCoverImage
            : (string) ($current['cover_image'] ?? '');
        $coverImageFile = $request->file('cover_image');
        $isPublished = $request->post('is_published', '0') === '1' ? 1 : 0;
        $publishedAtRaw = trim((string) $request->post('published_at', ''));

        $old = [
            'title' => $title,
            'slug' => $slugInput,
            'excerpt' => $excerpt,
            'content' => $content,
            'cover_image' => (string) ($current['cover_image'] ?? ''),
            'is_published' => (string) $isPublished,
            'published_at' => $publishedAtRaw,
        ];

        $errors = [];
        if ($title === '') {
            $errors[] = 'El título es obligatorio.';
        }
        if ($contentPlain === '') {
            $errors[] = 'El contenido es obligatorio.';
        }
        if (mb_strlen($excerpt) > 500) {
            $errors[] = 'El extracto no puede superar 500 caracteres.';
        }

        if ($coverImageFile !== null && (int) ($coverImageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $coverValidationError = $this->validateCoverImageFile($coverImageFile);
            if ($coverValidationError !== null) {
                $errors[] = $coverValidationError;
            }
        }

        $publishedAt = null;
        if ($publishedAtRaw !== '') {
            $ts = strtotime($publishedAtRaw);
            if ($ts === false) {
                $errors[] = 'La fecha de publicación no es válida.';
            } else {
                $publishedAt = date('Y-m-d H:i:s', $ts);
            }
        } elseif ($isPublished === 1) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $baseSlug = $slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($title);
        $slug = $this->uniqueNewsSlug($baseSlug, $newsId);

        if ($errors !== []) {
            Session::flash('news_form_old', $old);
            Session::flash('error', implode(' ', $errors));
            return Response::redirect(
                BASE_URL . '/admin/news/' . $newsId . '/edit' . ($isModalRequest ? '?modal=1' : '')
            );
        }

        if ($coverImageFile !== null && (int) ($coverImageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadedPath = $this->storeCoverImage($coverImageFile);
            if ($uploadedPath === null) {
                Session::flash('news_form_old', $old);
                Session::flash('error', 'No se pudo guardar la imagen de portada.');
                return Response::redirect(
                    BASE_URL . '/admin/news/' . $newsId . '/edit' . ($isModalRequest ? '?modal=1' : '')
                );
            }
            $coverImage = $uploadedPath;
        }

        $stmt = $this->db->prepare(
            'UPDATE news
             SET title = ?, slug = ?, excerpt = ?, content = ?, cover_image = ?, is_published = ?, published_at = ?, updated_at = NOW()
             WHERE id = ?
             LIMIT 1'
        );
        $stmt->execute([
            $title,
            $slug,
            $excerpt !== '' ? $excerpt : null,
            $content,
            $coverImage !== '' ? $coverImage : null,
            $isPublished,
            $publishedAt,
            $newsId,
        ]);

        if ($isModalRequest) {
            $status = 'draft';
            $statusLabel = 'Borrador';
            if ($isPublished === 1 && $publishedAt !== null && strtotime($publishedAt) > time()) {
                $status = 'scheduled';
                $statusLabel = 'Programada';
            } elseif ($isPublished === 1) {
                $status = 'published';
                $statusLabel = 'Publicada';
            }

            Session::flash('news_edit_payload', [
                'id' => $newsId,
                'title' => $title,
                'slug' => $slug,
                'author_name' => (string) ($current['author_name'] ?? ($authUser['name'] ?? 'Sin autor')),
                'is_published' => $isPublished,
                'published_at' => $publishedAt,
                'published_at_sort' => $publishedAt ? strtotime($publishedAt) : 0,
                'status' => $status,
                'status_label' => $statusLabel,
                'published_at_label' => $publishedAt ? date('d/m/Y H:i', strtotime($publishedAt)) : '-',
                'message' => 'Noticia actualizada correctamente.',
            ]);
            return Response::redirect(BASE_URL . '/admin/news/' . $newsId . '/edit?modal=1&saved=1');
        }

        Session::flash('success', 'Noticia actualizada correctamente.');
        return Response::redirect(BASE_URL . '/admin/news');
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function loadSettings(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $this->db->prepare(
            "SELECT `key`, `value` FROM system_settings WHERE `key` IN ({$placeholders})"
        );
        $stmt->execute($keys);
        $rows = $stmt->fetchAll();

        $settings = array_fill_keys($keys, '');
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }

    private function panelSettings(): array
    {
        return $this->db
            ->query("SELECT `key`, value FROM system_settings WHERE `key` IN ('library_name','library_logo','library_favicon')")
            ->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    private function resolveAuthUser(): ?array
    {
        $userId = (int) Session::get('auth.user_id');
        if ($userId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'user_number' => $user['user_number'],
            'last_login_at' => $user['last_login_at'],
            'created_at' => $user['created_at'],
            'status' => $user['status'],
        ];
    }

    private function findNewsById(int $newsId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT n.id, n.title, n.slug, n.excerpt, n.content, n.cover_image, n.is_published, n.published_at, n.author_id, n.created_at,
                    u.name AS author_name
             FROM news n
             LEFT JOIN users u ON u.id = n.author_id
             WHERE n.id = ?
             LIMIT 1"
        );
        $stmt->execute([$newsId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $row['is_published'] = (string) ((int) ($row['is_published'] ?? 0));
        $row['published_at'] = !empty($row['published_at'])
            ? date('Y-m-d\TH:i', strtotime((string) $row['published_at']))
            : '';

        return $row;
    }

    private function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        $value = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ä', 'ë', 'ï', 'ö', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u'],
            $value
        );
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
        $value = trim($value, '-');
        return $value !== '' ? $value : 'noticia';
    }

    private function uniqueNewsSlug(string $baseSlug, int $excludeId = 0): string
    {
        $slug = $baseSlug !== '' ? $baseSlug : 'noticia';
        $suffix = 2;

        while (true) {
            if ($excludeId > 0) {
                $stmt = $this->db->prepare('SELECT COUNT(*) FROM news WHERE slug = ? AND id != ?');
                $stmt->execute([$slug, $excludeId]);
            } else {
                $stmt = $this->db->prepare('SELECT COUNT(*) FROM news WHERE slug = ?');
                $stmt->execute([$slug]);
            }

            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }

            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }
    }

    private function plainTextFromHtml(string $html): string
    {
        $decoded = html_entity_decode($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $decoded = str_replace("\xC2\xA0", ' ', $decoded);
        $text = strip_tags($decoded);
        $text = preg_replace('/\s+/u', ' ', (string) $text) ?? '';
        return trim($text);
    }

    private function validateCoverImageFile(array $file): ?string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            return 'Error al cargar la imagen de portada.';
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            return 'La imagen de portada es inválida.';
        }
        if ($size > 5 * 1024 * 1024) {
            return 'La imagen de portada no puede superar 5MB.';
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $mime = '';
        if (is_file($tmpName)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($tmpName);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowed, true)) {
            return 'Formato de imagen no permitido. Usa JPG, PNG, WEBP o GIF.';
        }

        return null;
    }

    private function storeCoverImage(array $file): ?string
    {
        $tmpName = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmpName)) {
            return null;
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmpName);
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => null,
        };

        if ($ext === null) {
            return null;
        }

        $directory = BASE_PATH . '/public/uploads/news';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return null;
        }

        try {
            $entropy = bin2hex(random_bytes(4));
        } catch (\Throwable $e) {
            $entropy = (string) mt_rand(100000, 999999);
        }

        $filename = 'news_' . date('Ymd_His') . '_' . $entropy . '.' . $ext;
        $destination = $directory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            return null;
        }

        return '/uploads/news/' . $filename;
    }
}
