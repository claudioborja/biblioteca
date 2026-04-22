<?php
// app/Controllers/PublicController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;

final class PublicController
{
    private \PDO $db;
    private View $view;

    public function __construct()
    {
        $this->db   = Database::connect();
        $this->view = new View(BASE_PATH . '/views');
    }

    /**
     * RF-PUB-01 — Página de Inicio (Home)
     */
    public function home(Request $request): Response
    {
        // Cargar configuración de la biblioteca
        $settings = $this->loadSettings([
            'library_name', 'library_logo', 'library_slogan',
            'library_address', 'library_phone', 'library_email',
            'library_schedule', 'library_website', 'library_favicon',
            'new_acquisition_days', 'news_on_home', 'currency_symbol',
        ]);

        $newsLimit = (int) ($settings['news_on_home'] ?: 3);

        // Nuevas adquisiciones (portada)
        $newAcquisitions = $this->db->query("
            SELECT b.id, b.title, b.authors, b.cover_image, b.acquired_at,
                   b.support_type, c.name AS category_name
            FROM resources b
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE b.is_new_acquisition = 1
              AND b.is_active = 1
              AND DATE_FORMAT(COALESCE(b.acquired_at, CONCAT(b.acquisition_date, ' 00:00:00'), b.created_at), '%Y-%m') = (
                  SELECT DATE_FORMAT(MAX(COALESCE(r2.acquired_at, CONCAT(r2.acquisition_date, ' 00:00:00'), r2.created_at)), '%Y-%m')
                  FROM resources r2
                  WHERE r2.is_new_acquisition = 1
                    AND r2.is_active = 1
              )
            ORDER BY b.acquired_at DESC
            LIMIT 14
        ")->fetchAll();

        // Libros más prestados (último mes)
        $topBooks = $this->db->query("
            SELECT b.id, b.title, b.authors, b.cover_image, b.support_type,
                   COUNT(l.id) AS loan_count,
                   c.name AS category_name
            FROM loans l
            JOIN resources b ON b.id = l.resource_id
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              AND b.is_active = 1
            GROUP BY b.id, b.title, b.authors, b.cover_image, b.support_type, c.name
            ORDER BY loan_count DESC
            LIMIT 14
        ")->fetchAll();

        // Últimas noticias publicadas
        $stmt = $this->db->prepare("
            SELECT n.id, n.title, n.slug, n.excerpt, n.cover_image, n.published_at
            FROM news n
            WHERE n.is_published = 1
              AND n.published_at <= NOW()
            ORDER BY n.published_at DESC
            LIMIT ?
        ");
        $stmt->execute([$newsLimit]);
        $news = $stmt->fetchAll();

        // RF-PUB-06 — Estadísticas públicas (cacheables)
        $stats = $this->getPublicStats();

        // ── Registro de visita en BD (única por visitante/día) ──────────────────
        $this->recordVisit($request);
        $stats['total_visits'] = (int) $this->db
            ->query("SELECT COUNT(*) FROM visits_log")
            ->fetchColumn();

        // Sedes (si hay múltiples)
        $branches = $this->db->query("
            SELECT id, name, address, phone, email, schedule
            FROM library_branches
            WHERE status = 'active'
            ORDER BY name
        ")->fetchAll();

        $html = $this->view->render('public/home', [
            'title'            => ($settings['library_name'] ?: 'Biblioteca') . ' — Inicio',
            'meta_description' => $settings['library_slogan'] ?: 'Sistema de Gestión Bibliotecaria',
            'settings'         => $settings,
            'newAcquisitions'  => $newAcquisitions,
            'topBooks'         => $topBooks,
            'news'             => $news,
            'stats'            => $stats,
            'branches'         => $branches,
        ]);

        return Response::html($html);
    }

    /**
     * Catálogo — listado de libros
     */
    public function catalog(Request $request): Response
    {
        $settings = $this->loadSettings([
            'library_name', 'library_favicon', 'library_logo',
            'library_slogan', 'library_address', 'library_phone', 'library_email', 'library_schedule',
        ]);

        // Filters from query string
        $q            = trim((string) $request->get('q', ''));
        $categoryId   = (int) $request->get('category', 0);
        $resourceType = trim((string) $request->get('resource_type', ''));
        $supportType  = trim((string) $request->get('support_type', ''));
        $legacyType   = trim((string) $request->get('type', ''));
        $language     = strtolower(trim((string) $request->get('language', '')));
        $avail        = (bool) $request->get('available', false);

        if ($supportType === '' && in_array($legacyType, ['physical', 'digital'], true)) {
            $supportType = $legacyType;
        }

        $resourceTypeExpr = "CASE
            WHEN b.resource_type IS NULL OR b.resource_type = '' THEN
                CASE b.support_type
                    WHEN 'digital' THEN 'ebook'
                    WHEN 'journal' THEN 'journal'
                    WHEN 'thesis' THEN 'thesis'
                    WHEN 'map' THEN 'map'
                    WHEN 'score' THEN 'score'
                    WHEN 'audiovisual' THEN 'audiovisual'
                    WHEN 'game' THEN 'game'
                    WHEN 'kit' THEN 'kit'
                    ELSE 'book'
                END
            ELSE b.resource_type
        END";

        $buildFilters = static function (bool $includeResourceType = true, bool $includeSupportType = true) use (
            $q,
            $categoryId,
            $resourceType,
            $supportType,
            $language,
            $avail,
            $resourceTypeExpr
        ): array {
            $where = ['b.is_active = 1'];
            $params = [];

            if ($q !== '') {
                $where[]  = '(b.title LIKE ? OR b.authors LIKE ? OR b.isbn_13 LIKE ? OR b.publisher LIKE ? OR b.marc_control_number LIKE ?)';
                $like     = '%' . $q . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
            if ($categoryId > 0) {
                $where[]  = 'b.category_id = ?';
                $params[] = $categoryId;
            }
            if ($includeResourceType && $resourceType !== '') {
                $where[]  = $resourceTypeExpr . ' = ?';
                $params[] = $resourceType;
            }
            if ($includeSupportType && $supportType !== '') {
                $where[]  = 'b.support_type = ?';
                $params[] = $supportType;
            }
            if ($language !== '') {
                $where[]  = 'b.language = ?';
                $params[] = $language;
            }
            if ($avail) {
                $where[] = '(b.available_copies > 0 OR b.support_type = \'digital\')';
            }

            return [$where, $params];
        };

        [$where, $params] = $buildFilters(true, true);

        $countSql = "
            SELECT COUNT(*)
            FROM resources b
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE " . implode(' AND ', $where) . "
        ";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalResults = (int) $countStmt->fetchColumn();

        $perPage = 12; // 2 filas (6 columnas en desktop)
        $totalPages = max(1, (int) ceil($totalResults / $perPage));
        $page = max(1, (int) $request->get('page', 1));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT b.id, b.title, b.authors, b.cover_image, b.support_type, b.resource_type,
                   b.available_copies, b.total_copies, b.is_new_acquisition,
                   b.publication_year, b.language, b.publisher, b.edition_statement,
                   b.content_type, b.media_type, b.carrier_type, b.location, b.digital_url,
                   c.name AS category_name
            FROM resources b
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY b.title ASC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($params, [$perPage, $offset]));
        $books = $stmt->fetchAll();

        // Categories for sidebar filter
        $categories = $this->db->query("
            SELECT c.id, c.name, COUNT(b.id) AS book_count
            FROM categories c
            LEFT JOIN resources b ON b.category_id = c.id AND b.is_active = 1
            GROUP BY c.id, c.name
            HAVING book_count > 0
            ORDER BY c.name ASC
        ")->fetchAll();

        [$resourceWhere, $resourceParams] = $buildFilters(false, true);
        $resourceStmt = $this->db->prepare("
            SELECT " . $resourceTypeExpr . " AS value, COUNT(*) AS total
            FROM resources b
            WHERE " . implode(' AND ', $resourceWhere) . "
            GROUP BY value
            ORDER BY total DESC, value ASC
        ");
        $resourceStmt->execute($resourceParams);
        $resourceTypes = $resourceStmt->fetchAll();

        $supportStmt = $this->db->prepare("
            SELECT COALESCE(NULLIF(b.support_type, ''), 'other') AS value, COUNT(*) AS total
            FROM resources b
            WHERE b.is_active = 1
            GROUP BY value
            ORDER BY total DESC, value ASC
        ");
        $supportStmt->execute();
        $supportTypes = $supportStmt->fetchAll();

        $languages = $this->db->query("
            SELECT b.language AS value, COUNT(*) AS total
            FROM resources b
            WHERE b.is_active = 1 AND b.language IS NOT NULL AND b.language <> ''
            GROUP BY b.language
            ORDER BY total DESC, value ASC
        ")->fetchAll();

        $pairStmt = $this->db->query("
            SELECT " . $resourceTypeExpr . " AS resource_type,
                   COALESCE(NULLIF(b.support_type, ''), 'other') AS support_type
            FROM resources b
            WHERE b.is_active = 1
            GROUP BY resource_type, support_type
        ");
        $resourceSupportMap = [];
        foreach ($pairStmt->fetchAll() as $row) {
            $rt = (string) ($row['resource_type'] ?? 'other');
            $st = (string) ($row['support_type'] ?? 'other');
            if (!isset($resourceSupportMap[$rt])) {
                $resourceSupportMap[$rt] = [];
            }
            if (!in_array($st, $resourceSupportMap[$rt], true)) {
                $resourceSupportMap[$rt][] = $st;
            }

            // "Libro" en catálogo puede abarcar físico + digital (book + ebook).
            if ($rt === 'ebook') {
                if (!isset($resourceSupportMap['book'])) {
                    $resourceSupportMap['book'] = [];
                }
                if (!in_array($st, $resourceSupportMap['book'], true)) {
                    $resourceSupportMap['book'][] = $st;
                }
            }
        }

        $html = $this->view->render('public/catalog', [
            'title'      => 'Catálogo',
            'settings'   => $settings,
            'books'      => $books,
            'categories' => $categories,
            'resource_types' => $resourceTypes,
            'support_types'  => $supportTypes,
            'languages'      => $languages,
            'resource_support_map' => $resourceSupportMap,
            'extra_css' => '
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.catalog-filters .select2-container { width: 100% !important; }
.catalog-filters .select2-container--default .select2-selection--single {
    height: 40px;
    border: 1px solid transparent;
    border-radius: .5rem;
    background: var(--color-surface-container);
}
.catalog-filters .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px;
    padding-left: .65rem;
    padding-right: 2rem;
    color: var(--color-on-surface);
    font-size: .875rem;
}
.catalog-filters .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 38px;
    right: .35rem;
}
.catalog-filters .select2-container--default.select2-container--focus .select2-selection--single,
.catalog-filters .select2-container--default.select2-container--open .select2-selection--single {
    border-color: var(--color-primary);
}
.select2-dropdown {
    border: 1px solid var(--color-outline-variant);
    border-radius: .6rem;
    overflow: hidden;
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid var(--color-outline-variant);
    border-radius: .45rem;
    padding: .35rem .5rem;
}
.select2-results__option { font-size: .875rem; }
</style>',
            'extra_js' => '
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    if (typeof window.__initCatalogFilterSelects === "function") {
        window.__initCatalogFilterSelects();
    }
});
</script>',
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalResults,
                'total_pages' => $totalPages,
                'from' => $totalResults > 0 ? ($offset + 1) : 0,
                'to' => min($offset + $perPage, $totalResults),
            ],
            'filters'    => compact('q', 'categoryId', 'resourceType', 'supportType', 'language', 'avail'),
        ]);

        return Response::html($html);
    }

    /**
     * Detalle de libro
     */
    public function resourceDetail(Request $request, string $id = '0'): Response
    {
        $id = (int) $id;
        $authUser = null;
        $authUserId = (int) Session::get('auth.user_id', 0);
        if ($authUserId > 0) {
            $authStmt = $this->db->prepare('SELECT id, name, email, role, status FROM users WHERE id = ? LIMIT 1');
            $authStmt->execute([$authUserId]);
            $authUser = $authStmt->fetch() ?: null;
        }

        $stmt = $this->db->prepare("
            SELECT b.*, c.name AS category_name, c.slug AS category_slug,
                   COALESCE(br.name, 'No asignada') AS branch_name
            FROM resources b
            LEFT JOIN categories c ON c.id = b.category_id
            LEFT JOIN library_branches br ON br.id = b.branch_id
            WHERE b.id = ? AND b.is_active = 1
        ");
        $stmt->execute([$id]);
        $book = $stmt->fetch();

        if (!$book) {
            return Response::html('<h1>Libro no encontrado</h1>', 404);
        }

        $canReadDigital = false;
        if (($book['support_type'] ?? '') === 'digital' && $authUser !== null) {
            $reservationCheck = $this->db->prepare(
                "SELECT id
                 FROM reservations
                 WHERE resource_id = ?
                   AND user_id = ?
                   AND status IN ('waiting', 'notified', 'fulfilled')
                 LIMIT 1"
            );
            $reservationCheck->execute([(int) $book['id'], (int) $authUser['id']]);
                        $hasReservation = (bool) $reservationCheck->fetch();

                        $loanCheck = $this->db->prepare(
                                "SELECT id
                                 FROM loans
                                 WHERE resource_id = ?
                                     AND user_id = ?
                                     AND status IN ('active','overdue')
                                 LIMIT 1"
                        );
                        $loanCheck->execute([(int) $book['id'], (int) $authUser['id']]);
                        $hasActiveLoan = (bool) $loanCheck->fetch();

                        $canReadDigital = $hasReservation || $hasActiveLoan;
        }

        // Loan count (all time)
        $stmtLoans = $this->db->prepare("SELECT COUNT(*) FROM loans WHERE resource_id = ?");
        $stmtLoans->execute([$id]);
        $loanCount = (int) $stmtLoans->fetchColumn();

        // Related books (same category, excluding current)
        $related = $this->db->prepare("
            SELECT b.id, b.title, b.authors, b.cover_image, b.support_type, b.available_copies
            FROM resources b
            WHERE b.category_id = ? AND b.id != ? AND b.is_active = 1
            ORDER BY b.acquired_at DESC
            LIMIT 6
        ");
        $related->execute([$book['category_id'], $id]);
        $relatedBooks = $related->fetchAll();

        // Queue size for reservations
        $stmtQueue = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE resource_id = ? AND status IN ('waiting','notified')");
        $stmtQueue->execute([$id]);
        $queueSize = (int) $stmtQueue->fetchColumn();

        $settings = $this->loadSettings([
            'library_name', 'library_favicon', 'library_logo', 'currency_symbol',
            'library_slogan', 'library_address', 'library_phone', 'library_email', 'library_schedule',
        ]);

        $html = $this->view->render('public/resource-detail', [
            'title'        => $book['title'],
            'settings'     => $settings,
            'auth_user'    => $authUser,
            'book'         => $book,
            'loanCount'    => $loanCount,
            'relatedBooks' => $relatedBooks,
            'queueSize'    => $queueSize,
            'canReadDigital' => $canReadDigital,
        ]);

        return Response::html($html);
    }

    /**
     * Nuevas Adquisiciones — página completa
     */
    public function newAcquisitions(Request $request): Response
    {
        $settings = $this->loadSettings([
            'library_name', 'library_favicon', 'library_logo',
            'library_slogan', 'library_address', 'library_phone', 'library_email', 'library_schedule',
        ]);

        $monthsRows = $this->db->query("
            SELECT DATE_FORMAT(COALESCE(b.acquired_at, CONCAT(b.acquisition_date, ' 00:00:00'), b.created_at), '%Y-%m') AS month_key
            FROM resources b
            WHERE b.is_new_acquisition = 1
              AND b.is_active = 1
            GROUP BY month_key
            ORDER BY month_key DESC
            LIMIT 12
        ")->fetchAll();

        $monthOptions = [];
        $monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        foreach ($monthsRows as $row) {
            $key = (string) ($row['month_key'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}$/', $key)) {
                continue;
            }
            [$year, $month] = explode('-', $key);
            $monthIndex = (int) $month;
            $label = ($monthNames[$monthIndex - 1] ?? $key) . ' ' . $year;
            $monthOptions[] = ['key' => $key, 'label' => $label];
        }

        $selectedMonth = trim((string) $request->get('month', ''));
        $validMonthKeys = array_map(static fn(array $m): string => (string) $m['key'], $monthOptions);
        if ($selectedMonth === '' || !in_array($selectedMonth, $validMonthKeys, true)) {
            $selectedMonth = $validMonthKeys[0] ?? '';
        }

        $books = [];
        if ($selectedMonth !== '') {
            $stmt = $this->db->prepare("
            SELECT b.id, b.title, b.authors, b.cover_image, b.support_type,
                   b.available_copies, b.total_copies, b.acquired_at,
                   c.name AS category_name
            FROM resources b
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE b.is_new_acquisition = 1
              AND b.is_active = 1
              AND DATE_FORMAT(COALESCE(b.acquired_at, CONCAT(b.acquisition_date, ' 00:00:00'), b.created_at), '%Y-%m') = ?
            ORDER BY b.acquired_at DESC
        ");
            $stmt->execute([$selectedMonth]);
            $books = $stmt->fetchAll();
        }

        $html = $this->view->render('public/new-acquisitions', [
            'title'    => 'Novedades',
            'settings' => $settings,
            'books'    => $books,
            'month_options' => $monthOptions,
            'selected_month' => $selectedMonth,
        ]);

        return Response::html($html);
    }

    public function newAcquisitionsPrivate(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $monthsRows = $this->db->query("
            SELECT DATE_FORMAT(COALESCE(b.acquired_at, CONCAT(b.acquisition_date, ' 00:00:00'), b.created_at), '%Y-%m') AS month_key
            FROM resources b
            WHERE b.is_new_acquisition = 1
              AND b.is_active = 1
            GROUP BY month_key
            ORDER BY month_key DESC
            LIMIT 12
        ")->fetchAll();

        $monthOptions = [];
        $monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                       'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        foreach ($monthsRows as $row) {
            $key = (string) ($row['month_key'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}$/', $key)) {
                continue;
            }
            [$year, $month] = explode('-', $key);
            $monthIndex = (int) $month;
            $label = ($monthNames[$monthIndex - 1] ?? $key) . ' ' . $year;
            $monthOptions[] = ['key' => $key, 'label' => $label];
        }

        $selectedMonth = trim((string) $request->get('month', ''));
        $validMonthKeys = array_map(static fn(array $m): string => (string) $m['key'], $monthOptions);
        if ($selectedMonth === '' || !in_array($selectedMonth, $validMonthKeys, true)) {
            $selectedMonth = $validMonthKeys[0] ?? '';
        }

        $books = [];
        if ($selectedMonth !== '') {
            $stmt = $this->db->prepare("
                SELECT b.id, b.title, b.authors, b.cover_image, b.support_type,
                       b.available_copies, b.total_copies, b.acquired_at,
                       c.name AS category_name
                FROM resources b
                LEFT JOIN categories c ON c.id = b.category_id
                WHERE b.is_new_acquisition = 1
                  AND b.is_active = 1
                  AND DATE_FORMAT(COALESCE(b.acquired_at, CONCAT(b.acquisition_date, ' 00:00:00'), b.created_at), '%Y-%m') = ?
                ORDER BY b.acquired_at DESC
            ");
            $stmt->execute([$selectedMonth]);
            $books = $stmt->fetchAll();
        }

        $settings = $this->panelSettings();

        return Response::html($this->view->render('public/new-acquisitions', [
            'title'          => 'Novedades — ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'       => $settings,
            'auth_user'      => $authUser,
            'books'          => $books,
            'month_options'  => $monthOptions,
            'selected_month' => $selectedMonth,
        ], 'layouts/panel'));
    }

    /**
     * Página Nosotros
     */
    public function about(Request $request): Response
    {
        $settings = $this->loadSettings([
            'library_name', 'library_slogan', 'library_address',
            'library_phone', 'library_email', 'library_schedule',
            'library_logo', 'library_favicon',
            'about_hero_badge', 'about_hero_title', 'about_hero_subtitle',
            'about_mission_title', 'about_mission_text',
            'about_vision_title', 'about_vision_text',
            'about_values_title', 'about_values_items',
            'about_history_badge', 'about_history_title', 'about_history_text',
            'about_history_p1', 'about_history_p2', 'about_history_p3',
            'about_timeline_items', 'about_contact_badge', 'about_contact_title',
        ]);

        $values = array_values(array_filter(array_map(
            static fn(string $line): string => trim($line),
            preg_split('/\r\n|\r|\n/', (string) ($settings['about_values_items'] ?? '')) ?: []
        ), static fn(string $line): bool => $line !== ''));

        if ($values === []) {
            $values = [
                'Acceso libre e igualitario',
                'Respeto e inclusión',
                'Compromiso con la educación',
                'Innovación y mejora continua',
                'Transparencia y servicio',
            ];
        }

        $timeline = [];
        $timelineLines = preg_split('/\r\n|\r|\n/', (string) ($settings['about_timeline_items'] ?? '')) ?: [];
        foreach ($timelineLines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = explode('|', $line, 2);
            $year = trim((string) ($parts[0] ?? ''));
            $text = trim((string) ($parts[1] ?? ''));

            if ($year === '' || $text === '') {
                continue;
            }

            $timeline[] = ['year' => $year, 'text' => $text];
        }

        if ($timeline === []) {
            $timeline = [
                ['year' => '2010', 'text' => 'Apertura de la biblioteca con una colección inicial de 2 000 volúmenes.'],
                ['year' => '2014', 'text' => 'Inauguración de la sala infantil y programa de animación lectora.'],
                ['year' => '2017', 'text' => 'Lanzamiento del catálogo en línea y las primeras suscripciones digitales.'],
                ['year' => '2020', 'text' => 'Adaptación a servicios remotos y expansión del fondo digital durante la pandemia.'],
                ['year' => '2023', 'text' => 'Renovación de instalaciones y apertura de sala de co-trabajo.'],
                ['year' => '2025', 'text' => 'Más de 10 000 socios activos y 50 000 préstamos anuales.'],
            ];
        }

        $aboutHistoryText = trim((string) ($settings['about_history_text'] ?? ''));
        if ($aboutHistoryText === '') {
            $legacyHistoryParts = array_values(array_filter([
                trim((string) ($settings['about_history_p1'] ?? '')),
                trim((string) ($settings['about_history_p2'] ?? '')),
                trim((string) ($settings['about_history_p3'] ?? '')),
            ], static fn(string $part): bool => $part !== ''));

            if ($legacyHistoryParts !== []) {
                $aboutHistoryText = implode("\n\n", $legacyHistoryParts);
            } else {
                $aboutHistoryText = "Fundada con el propósito de democratizar el acceso al conocimiento, nuestra biblioteca ha sido desde sus inicios un punto de encuentro para estudiantes, investigadores, familias y amantes de la lectura.\n\nA lo largo de los años hemos ampliado nuestra colección, modernizado nuestros espacios y adaptado nuestros servicios a las nuevas necesidades digitales, sin perder jamás la calidez del trato humano que nos caracteriza.\n\nHoy contamos con un amplio catálogo físico y digital, préstamos a domicilio, salas de estudio y un equipo de bibliotecarios comprometidos con guiar a cada visitante en su búsqueda del saber.";
            }
        }

        $html = $this->view->render('public/about', [
            'settings' => $settings,
            'about_values' => $values,
            'about_timeline' => $timeline,
            'about_history_text' => $aboutHistoryText,
            'page_title' => 'Nosotros',
        ]);

        return Response::html($html);
    }

    /**
     * Cargar settings de la tabla system_settings.
     */
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

    /**
     * RF-PUB-06 — Estadísticas públicas con cache de 1 hora.
     */
    private function getPublicStats(): array
    {
        $cacheKey = 'public_stats';
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $success);
            if ($success) {
                return $cached;
            }
        }

        $stats = [];

        $stats['total_books'] = (int) $this->db->query(
            "SELECT COUNT(*) FROM resources WHERE is_active = 1"
        )->fetchColumn();

        $stats['total_users'] = (int) $this->db->query(
            "SELECT COUNT(*) FROM users WHERE status = 'active' AND role != 'admin'"
        )->fetchColumn();

        $stats['total_loans'] = (int) $this->db->query(
            "SELECT COUNT(*) FROM loans"
        )->fetchColumn();

        if (function_exists('apcu_store')) {
            apcu_store($cacheKey, $stats, 3600);
        }

        return $stats;
    }

    /**
     * Registra una visita a la página principal.
     * Regla: un mismo visitante solo cuenta una vez por día natural.
     * Identificador: user_id para autenticados, IP para anónimos.
     */
    private function recordVisit(Request $request): void
    {
        try {
            $userId = null;
            $sessionUserId = (int) Session::get('auth.user_id', 0);
            if ($sessionUserId > 0) {
                $userId = $sessionUserId;
            }

            $ip        = $this->resolveIp($request);
            $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
            $referer   = substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 500);
            $page      = '/';
            $today     = date('Y-m-d');

            // Verificar si ya existe una visita hoy para este visitante
            if ($userId !== null) {
                $check = $this->db->prepare(
                    "SELECT COUNT(*) FROM visits_log
                     WHERE user_id = ? AND DATE(created_at) = ?"
                );
                $check->execute([$userId, $today]);
            } else {
                $check = $this->db->prepare(
                    "SELECT COUNT(*) FROM visits_log
                     WHERE user_id IS NULL AND ip_address = ? AND DATE(created_at) = ?"
                );
                $check->execute([$ip, $today]);
            }

            if ((int) $check->fetchColumn() > 0) {
                return; // Ya contó hoy
            }

            $this->db->prepare(
                "INSERT INTO visits_log (user_id, page, ip_address, user_agent, referer, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            )->execute([$userId, $page, $ip, $userAgent ?: null, $referer ?: null]);
        } catch (\Throwable) {
            // No interrumpir la carga de la página si falla el registro
        }
    }

    private function resolveIp(Request $request): ?string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
            $val = $_SERVER[$key] ?? '';
            if ($val !== '') {
                // X-Forwarded-For puede ser una lista; tomar la primera
                $ip = trim(explode(',', $val)[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return substr($ip, 0, 45);
                }
            }
        }
        return null;
    }
}
