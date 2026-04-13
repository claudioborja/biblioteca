---
name: skill-php-search
description: "**WORKFLOW SKILL** — Professional search implementation in pure PHP with MariaDB for library catalog systems. USE FOR: full-text search with MariaDB FULLTEXT indexes (NATURAL LANGUAGE, BOOLEAN mode); search service layer in PHP; relevance ranking and scoring; multi-field search (title, author, ISBN, publisher, description); faceted filtering (category, year, language, availability); search results pagination with keyset; autocomplete and live suggestions via AJAX (vanilla JS + PHP JSON endpoint); search highlighting of matched terms; search history per user; popular searches tracking; advanced search (AND/OR/NOT operators); search with typo tolerance via SOUNDEX/LEVENSHTEIN; no-results handling with suggestions; search performance optimization; search across Spanish text with utf8mb4 and proper collation. DO NOT USE FOR: Elasticsearch/Meilisearch/Algolia; full-text search engines external to MariaDB."
---

# PHP Search — Professional Library Catalog Search

## Core Philosophy

- **MariaDB FULLTEXT first**: The database already has the data — use its native full-text engine before adding external services.
- **Search is a feature, not a query**: A search service encapsulates relevance, filtering, pagination, and suggestions.
- **Fast feedback**: Autocomplete must respond in < 200ms — use covering indexes and limit results aggressively.
- **Degrade gracefully**: If FULLTEXT returns nothing, fall back to LIKE; if that fails, suggest alternatives.
- **Track what users search**: Popular searches inform acquisitions; zero-result searches reveal catalog gaps.

---

## Database Setup

```sql
-- Ensure correct charset for Spanish full-text search
ALTER TABLE books CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Full-text index covering the main searchable fields
ALTER TABLE books ADD FULLTEXT INDEX ft_books_search (title, author, publisher, description);

-- Separate index for ISBN (exact match)
ALTER TABLE books ADD UNIQUE INDEX uq_books_isbn (isbn);

-- Covering index for autocomplete (title + author only, fast)
ALTER TABLE books ADD FULLTEXT INDEX ft_books_autocomplete (title, author);

-- Index for filtering
ALTER TABLE books ADD INDEX idx_books_category_available (category_id, available, deleted_at);
ALTER TABLE books ADD INDEX idx_books_year_language (year, language);

-- Search history table
CREATE TABLE search_log (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NULL,
    query      VARCHAR(255) NOT NULL,
    results    SMALLINT     NOT NULL DEFAULT 0,
    filters    JSON         NULL,
    ip         VARCHAR(45)  NOT NULL,
    searched_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_search_log_query (query),
    INDEX idx_search_log_user (user_id),
    INDEX idx_search_log_date (searched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Popular searches materialized view (refreshed by cron)
CREATE TABLE popular_searches (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    query      VARCHAR(255) NOT NULL,
    count      INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE INDEX uq_popular_searches_query (query),
    INDEX idx_popular_searches_count (count DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Search Service

```php
<?php
// src/Services/SearchService.php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

final class SearchService
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Main search — FULLTEXT with fallback to LIKE
     */
    public function search(string $query, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = $this->sanitizeQuery($query);

        if (strlen($query) < 2) {
            return $this->emptyResult($query, $page, $perPage);
        }

        // Try FULLTEXT NATURAL LANGUAGE first
        $result = $this->fulltextSearch($query, $filters, $page, $perPage);

        // Fall back to LIKE if FULLTEXT returns nothing
        if ($result['total'] === 0) {
            $result = $this->likeSearch($query, $filters, $page, $perPage);
            $result['used_fallback'] = true;
        }

        // Add search highlighting
        $result['data'] = array_map(
            fn($book) => $this->highlight($book, $query),
            $result['data'],
        );

        $this->logSearch($query, $filters, $result['total']);

        return $result;
    }

    private function fulltextSearch(string $query, array $filters, int $page, int $perPage): array
    {
        // Build BOOLEAN MODE query from user input
        $booleanQuery = $this->toBooleanMode($query);

        [$filterSql, $filterParams] = $this->buildFilters($filters);

        $baseSql = "
            SELECT
                b.*,
                MATCH(b.title, b.author, b.publisher, b.description)
                    AGAINST (? IN BOOLEAN MODE) AS relevance,
                c.name AS category_name
            FROM books b
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE b.deleted_at IS NULL
              AND MATCH(b.title, b.author, b.publisher, b.description)
                  AGAINST (? IN BOOLEAN MODE)
              {$filterSql}
            ORDER BY relevance DESC, b.title ASC
        ";

        $params     = [$booleanQuery, $booleanQuery, ...$filterParams];
        $countSql   = "SELECT COUNT(*) FROM books b WHERE b.deleted_at IS NULL
                       AND MATCH(b.title, b.author, b.publisher, b.description)
                       AGAINST (? IN BOOLEAN MODE) {$filterSql}";
        $countParams = [$booleanQuery, ...$filterParams];

        return $this->paginate($baseSql, $params, $countSql, $countParams, $page, $perPage);
    }

    private function likeSearch(string $query, array $filters, int $page, int $perPage): array
    {
        [$filterSql, $filterParams] = $this->buildFilters($filters);
        $like = '%' . $query . '%';

        $baseSql = "
            SELECT b.*, 0 AS relevance, c.name AS category_name
            FROM books b
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE b.deleted_at IS NULL
              AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.publisher LIKE ?)
              {$filterSql}
            ORDER BY b.title ASC
        ";

        $params    = [$like, $like, $like, $like, ...$filterParams];
        $countSql  = "SELECT COUNT(*) FROM books b WHERE b.deleted_at IS NULL
                      AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.publisher LIKE ?)
                      {$filterSql}";

        return $this->paginate($baseSql, $params, $countSql, $params, $page, $perPage);
    }

    /**
     * Autocomplete — fast, covers title and author only
     */
    public function autocomplete(string $term, int $limit = 8): array
    {
        $term = $this->sanitizeQuery($term);
        if (strlen($term) < 2) return [];

        // Prefix search with LIKE (faster for short terms than FULLTEXT)
        $stmt = $this->db->prepare("
            SELECT id, title, author, available
            FROM books
            WHERE deleted_at IS NULL
              AND (title LIKE ? OR author LIKE ?)
            ORDER BY available DESC, title ASC
            LIMIT ?
        ");
        $like = $term . '%';
        $stmt->execute([$like, $like, $limit]);
        $prefix = $stmt->fetchAll();

        // Fill remaining slots with FULLTEXT if needed
        if (count($prefix) < $limit) {
            $boolQuery = '+' . implode('* +', explode(' ', trim($term))) . '*';
            $stmt = $this->db->prepare("
                SELECT id, title, author, available
                FROM books
                WHERE deleted_at IS NULL
                  AND MATCH(title, author) AGAINST (? IN BOOLEAN MODE)
                  AND id NOT IN (" . implode(',', array_column($prefix, 'id') ?: [0]) . ")
                LIMIT ?
            ");
            $stmt->execute([$boolQuery, $limit - count($prefix)]);
            $prefix = array_merge($prefix, $stmt->fetchAll());
        }

        return array_map(fn($b) => [
            'id'        => $b['id'],
            'label'     => $b['title'],
            'sublabel'  => $b['author'],
            'available' => (bool) $b['available'],
        ], $prefix);
    }

    /**
     * Facets for sidebar filters
     */
    public function facets(string $query = '', array $activeFilters = []): array
    {
        $boolQuery = $query !== '' ? $this->toBooleanMode($query) : null;
        $matchSql  = $boolQuery
            ? "AND MATCH(b.title, b.author, b.publisher, b.description) AGAINST (? IN BOOLEAN MODE)"
            : '';

        // Categories
        $stmt = $this->db->prepare("
            SELECT c.id, c.name, COUNT(b.id) AS total
            FROM categories c
            LEFT JOIN books b ON b.category_id = c.id AND b.deleted_at IS NULL {$matchSql}
            GROUP BY c.id, c.name
            HAVING total > 0
            ORDER BY total DESC
        ");
        $stmt->execute($boolQuery ? [$boolQuery] : []);
        $categories = $stmt->fetchAll();

        // Publication years
        $stmt = $this->db->prepare("
            SELECT b.year, COUNT(*) AS total
            FROM books b
            WHERE b.deleted_at IS NULL AND b.year IS NOT NULL {$matchSql}
            GROUP BY b.year
            ORDER BY b.year DESC
            LIMIT 15
        ");
        $stmt->execute($boolQuery ? [$boolQuery] : []);
        $years = $stmt->fetchAll();

        // Languages
        $stmt = $this->db->prepare("
            SELECT b.language, COUNT(*) AS total
            FROM books b
            WHERE b.deleted_at IS NULL AND b.language IS NOT NULL {$matchSql}
            GROUP BY b.language
            ORDER BY total DESC
        ");
        $stmt->execute($boolQuery ? [$boolQuery] : []);
        $languages = $stmt->fetchAll();

        return [
            'categories' => $categories,
            'years'      => $years,
            'languages'  => $languages,
            'available'  => $this->countAvailable($query),
        ];
    }

    /**
     * Popular searches for homepage / suggestions
     */
    public function popularSearches(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT query, count FROM popular_searches
            ORDER BY count DESC LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Suggest alternative queries when no results found
     */
    public function suggest(string $query): array
    {
        $words = explode(' ', trim($query));
        $suggestions = [];

        foreach ($words as $word) {
            if (strlen($word) < 3) continue;

            // SOUNDEX for phonetic similarity
            $stmt = $this->db->prepare("
                SELECT DISTINCT author AS suggestion, 'author' AS type
                FROM books
                WHERE deleted_at IS NULL AND SOUNDEX(author) = SOUNDEX(?)
                UNION
                SELECT DISTINCT title AS suggestion, 'title' AS type
                FROM books
                WHERE deleted_at IS NULL
                  AND title SOUNDS LIKE ?
                LIMIT 5
            ");
            $stmt->execute([$word, $word]);
            $suggestions = array_merge($suggestions, $stmt->fetchAll());
        }

        return array_unique($suggestions, SORT_REGULAR);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function buildFilters(array $filters): array
    {
        $sql    = '';
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql    .= ' AND b.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['year'])) {
            $sql    .= ' AND b.year = ?';
            $params[] = (int) $filters['year'];
        }
        if (!empty($filters['language'])) {
            $sql    .= ' AND b.language = ?';
            $params[] = $filters['language'];
        }
        if (!empty($filters['available'])) {
            $sql    .= ' AND b.available > 0';
        }

        return [$sql, $params];
    }

    private function paginate(
        string $sql,
        array  $params,
        string $countSql,
        array  $countParams,
        int    $page,
        int    $perPage,
    ): array {
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($countParams);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt   = $this->db->prepare("{$sql} LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);

        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
            'used_fallback' => false,
        ];
    }

    private function highlight(array $book, string $query): array
    {
        $terms = array_filter(explode(' ', $query), fn($t) => strlen($t) > 1);

        foreach (['title', 'author', 'description'] as $field) {
            if (!isset($book[$field])) continue;
            $text = htmlspecialchars($book[$field], ENT_QUOTES, 'UTF-8');
            foreach ($terms as $term) {
                $text = preg_replace(
                    '/(' . preg_quote(htmlspecialchars($term, ENT_QUOTES), '/') . ')/iu',
                    '<mark>$1</mark>',
                    $text,
                );
            }
            $book[$field . '_highlighted'] = $text;
        }

        return $book;
    }

    private function toBooleanMode(string $query): string
    {
        // Handle quoted phrases: "exact phrase"
        $query = preg_replace_callback('/"([^"]+)"/', fn($m) => '"' . $m[1] . '"', $query);

        // Handle explicit operators
        if (preg_match('/[+\-*<>()]/', $query)) return $query;

        // Default: require all words (+word), with wildcard for last word
        $words = array_filter(explode(' ', trim($query)));
        $parts = [];
        foreach ($words as $i => $word) {
            $parts[] = ($i === count($words) - 1)
                ? '+' . $word . '*'   // last word gets wildcard (autocomplete effect)
                : '+' . $word;
        }
        return implode(' ', $parts);
    }

    private function sanitizeQuery(string $query): string
    {
        $query = trim($query);
        $query = preg_replace('/[^\p{L}\p{N}\s"\'*+\-()<>]/u', ' ', $query);
        $query = preg_replace('/\s+/', ' ', $query);
        return mb_substr($query, 0, 200);
    }

    private function logSearch(string $query, array $filters, int $results): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO search_log (user_id, query, results, filters, ip)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['auth.user_id'] ?? null,
                $query,
                $results,
                $filters !== [] ? json_encode($filters) : null,
                $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        } catch (\Throwable $e) {
            Logger::warning('Failed to log search', ['error' => $e->getMessage()]);
        }
    }

    private function countAvailable(string $query): int
    {
        if ($query === '') {
            $stmt = $this->db->query("SELECT COUNT(*) FROM books WHERE deleted_at IS NULL AND available > 0");
        } else {
            $boolQuery = $this->toBooleanMode($query);
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM books
                WHERE deleted_at IS NULL AND available > 0
                  AND MATCH(title, author, publisher, description) AGAINST (? IN BOOLEAN MODE)
            ");
            $stmt->execute([$boolQuery]);
        }
        return (int) $stmt->fetchColumn();
    }

    private function emptyResult(string $query, int $page, int $perPage): array
    {
        return [
            'data'          => [],
            'total'         => 0,
            'per_page'      => $perPage,
            'current_page'  => $page,
            'last_page'     => 1,
            'used_fallback' => false,
        ];
    }
}
```

---

## Search Controller

```php
<?php
// src/Controllers/SearchController.php
declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;
use Core\View;
use Services\SearchService;

final class SearchController
{
    public function __construct(
        private readonly SearchService $search,
        private readonly View          $view,
    ) {}

    /** GET /search */
    public function index(Request $request): void
    {
        $query   = $request->string('q');
        $page    = max(1, $request->int('page', 1));
        $filters = array_filter([
            'category_id' => $request->int('category'),
            'year'        => $request->int('year'),
            'language'    => $request->string('lang'),
            'available'   => $request->string('available') === '1',
        ]);

        if ($query === '') {
            echo $this->view->render('search/index', [
                'query'   => '',
                'results' => null,
                'popular' => $this->search->popularSearches(),
            ]);
            return;
        }

        $results = $this->search->search($query, $filters, $page);
        $facets  = $this->search->facets($query, $filters);

        if ($results['total'] === 0) {
            $suggestions = $this->search->suggest($query);
        }

        echo $this->view->render('search/results', [
            'query'       => $query,
            'results'     => $results,
            'facets'      => $facets,
            'filters'     => $filters,
            'suggestions' => $suggestions ?? [],
        ]);
    }

    /** GET /search/autocomplete?q=term — JSON endpoint */
    public function autocomplete(Request $request): void
    {
        $term = $request->string('q');
        Response::json($this->search->autocomplete($term, limit: 8));
    }

    /** GET /search/popular — JSON endpoint */
    public function popular(): void
    {
        Response::json($this->search->popularSearches(limit: 10));
    }
}
```

---

## Search Views

```php
<!-- views/search/results.php -->
<div class="search-page">

    <!-- Search bar -->
    <form action="/search" method="GET" class="search-form" role="search">
        <div class="search-input-wrapper">
            <input
                type="search"
                name="q"
                value="<?= \Core\View::e($query) ?>"
                placeholder="Buscar por título, autor, ISBN..."
                autocomplete="off"
                id="search-input"
                aria-label="Buscar libros"
            >
            <button type="submit" aria-label="Buscar">Buscar</button>
        </div>
    </form>

    <?php if ($results['used_fallback'] ?? false): ?>
    <div class="alert">
        No encontramos resultados exactos para «<?= \Core\View::e($query) ?>».
        Mostrando resultados similares.
    </div>
    <?php endif; ?>

    <div class="search-layout">

        <!-- Facets sidebar -->
        <aside class="facets" aria-label="Filtros de búsqueda">
            <h2>Filtrar por</h2>

            <!-- Availability -->
            <div class="facet-group">
                <label>
                    <input type="checkbox" form="search-filters"
                           name="available" value="1"
                           <?= !empty($filters['available']) ? 'checked' : '' ?>>
                    Solo disponibles (<?= \Core\View::e($facets['available']) ?>)
                </label>
            </div>

            <!-- Categories -->
            <div class="facet-group">
                <h3>Categoría</h3>
                <?php foreach ($facets['categories'] as $cat): ?>
                <label>
                    <input type="radio" form="search-filters"
                           name="category" value="<?= \Core\View::e($cat['id']) ?>"
                           <?= ($filters['category_id'] ?? 0) == $cat['id'] ? 'checked' : '' ?>>
                    <?= \Core\View::e($cat['name']) ?> (<?= \Core\View::e($cat['total']) ?>)
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Years -->
            <div class="facet-group">
                <h3>Año</h3>
                <select form="search-filters" name="year">
                    <option value="">Todos</option>
                    <?php foreach ($facets['years'] as $y): ?>
                    <option value="<?= \Core\View::e($y['year']) ?>"
                            <?= ($filters['year'] ?? '') == $y['year'] ? 'selected' : '' ?>>
                        <?= \Core\View::e($y['year']) ?> (<?= \Core\View::e($y['total']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <form id="search-filters" action="/search" method="GET">
                <input type="hidden" name="q" value="<?= \Core\View::e($query) ?>">
                <button type="submit" class="btn btn-sm">Aplicar filtros</button>
                <a href="/search?q=<?= urlencode($query) ?>">Limpiar</a>
            </form>
        </aside>

        <!-- Results -->
        <main class="results">
            <p class="results-count">
                <?= \Core\View::e($results['total']) ?> resultado<?= $results['total'] !== 1 ? 's' : '' ?>
                para «<?= \Core\View::e($query) ?>»
            </p>

            <?php if ($results['total'] === 0): ?>
                <div class="no-results">
                    <p>No se encontraron libros para esta búsqueda.</p>
                    <?php if (!empty($suggestions)): ?>
                    <p>¿Quiso decir?</p>
                    <ul>
                        <?php foreach ($suggestions as $s): ?>
                        <li>
                            <a href="/search?q=<?= urlencode($s['suggestion']) ?>">
                                <?= \Core\View::e($s['suggestion']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <ul class="book-list">
                <?php foreach ($results['data'] as $book): ?>
                    <li class="book-card">
                        <?php if ($book['cover_path']): ?>
                        <img src="/storage/covers/<?= urlencode(basename($book['cover_path'])) ?>"
                             alt="Portada de <?= \Core\View::e($book['title']) ?>"
                             loading="lazy" width="80" height="120">
                        <?php endif; ?>
                        <div class="book-info">
                            <h3>
                                <a href="/books/<?= \Core\View::e($book['id']) ?>">
                                    <?= $book['title_highlighted'] ?? \Core\View::e($book['title']) ?>
                                </a>
                            </h3>
                            <p class="author">
                                <?= $book['author_highlighted'] ?? \Core\View::e($book['author']) ?>
                            </p>
                            <p class="meta">
                                <?= \Core\View::e($book['category_name'] ?? '') ?>
                                <?php if ($book['year']): ?>— <?= \Core\View::e($book['year']) ?><?php endif; ?>
                            </p>
                            <span class="availability <?= $book['available'] > 0 ? 'available' : 'unavailable' ?>">
                                <?= $book['available'] > 0
                                    ? $book['available'] . ' disponible(s)'
                                    : 'No disponible' ?>
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>

                <!-- Pagination -->
                <?php if ($results['last_page'] > 1): ?>
                <nav class="pagination" aria-label="Páginas de resultados">
                    <?php for ($p = 1; $p <= $results['last_page']; $p++): ?>
                    <a href="/search?q=<?= urlencode($query) ?>&page=<?= $p ?>"
                       class="<?= $p === $results['current_page'] ? 'active' : '' ?>"
                       aria-current="<?= $p === $results['current_page'] ? 'page' : 'false' ?>">
                        <?= $p ?>
                    </a>
                    <?php endfor; ?>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>
```

---

## Autocomplete (Vanilla JS)

```javascript
// public/assets/js/search-autocomplete.js
(function () {
    'use strict';

    const input    = document.getElementById('search-input');
    const endpoint = '/search/autocomplete';

    if (!input) return;

    let dropdown = null;
    let timer    = null;

    input.setAttribute('autocomplete', 'off');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-haspopup', 'listbox');

    function createDropdown() {
        if (dropdown) return;
        dropdown = document.createElement('ul');
        dropdown.className = 'autocomplete-dropdown';
        dropdown.setAttribute('role', 'listbox');
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(dropdown);
    }

    function clearDropdown() {
        if (dropdown) { dropdown.innerHTML = ''; dropdown.hidden = true; }
    }

    function renderSuggestions(items) {
        if (!items.length) { clearDropdown(); return; }
        createDropdown();
        dropdown.innerHTML = items.map((item, i) => `
            <li role="option" id="suggestion-${i}"
                class="autocomplete-item ${item.available ? '' : 'unavailable'}"
                data-id="${item.id}">
                <span class="suggest-title">${escapeHtml(item.label)}</span>
                <span class="suggest-author">${escapeHtml(item.sublabel)}</span>
                ${item.available ? '' : '<span class="suggest-badge">No disponible</span>'}
            </li>
        `).join('');
        dropdown.hidden = false;

        dropdown.querySelectorAll('li').forEach(li => {
            li.addEventListener('mousedown', e => {
                e.preventDefault();
                window.location.href = `/books/${li.dataset.id}`;
            });
        });
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, c =>
            ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])
        );
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { clearDropdown(); return; }

        timer = setTimeout(async () => {
            try {
                const res  = await fetch(`${endpoint}?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                renderSuggestions(data);
            } catch (e) {
                clearDropdown();
            }
        }, 200);
    });

    document.addEventListener('click', e => {
        if (!input.contains(e.target)) clearDropdown();
    });

    input.addEventListener('keydown', e => {
        if (e.key === 'Escape') clearDropdown();
    });
}());
```

---

## Cron: Refresh Popular Searches

```php
<?php
// bin/refresh-popular-searches.php — run daily
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

$db = \Core\Database::connect();

// Aggregate last 30 days of searches
$db->exec("
    INSERT INTO popular_searches (query, count, updated_at)
    SELECT query, COUNT(*) AS count, NOW()
    FROM search_log
    WHERE searched_at >= NOW() - INTERVAL 30 DAY
      AND results > 0
      AND LENGTH(query) >= 3
    GROUP BY query
    ON DUPLICATE KEY UPDATE count = VALUES(count), updated_at = NOW()
");

// Clean rare searches
$db->exec("DELETE FROM popular_searches WHERE count < 3");

echo date('Y-m-d H:i:s') . " — Popular searches refreshed.\n";
```

---

## Routes

```php
<?php
// config/routes.php
$router->get('/search',               [SearchController::class, 'index']);
$router->get('/search/autocomplete',  [SearchController::class, 'autocomplete']);
$router->get('/search/popular',       [SearchController::class, 'popular']);
```

---

## Boolean Mode Reference

| User types | BOOLEAN query | Meaning |
|-----------|---------------|---------|
| `garcia marquez` | `+garcia* +marquez*` | Both words required |
| `"cien años"` | `"cien años"` | Exact phrase |
| `garcia -borges` | `+garcia* -borges` | Has garcia, not borges |
| `garcia OR borges` | `garcia borges` | Either word |
| `ISBN: 978...` | Exact LIKE query | Falls back to LIKE |

---

## Performance Tuning

```ini
# my.cnf — FULLTEXT tuning
ft_min_word_len         = 2    # allow 2-char words (important for Spanish: "el", "de")
innodb_ft_min_token_size = 2   # InnoDB equivalent
innodb_ft_cache_size    = 8M
innodb_ft_result_cache_limit = 2G
```

```sql
-- Rebuild FULLTEXT index after config change
REPAIR TABLE books QUICK;

-- Check FULLTEXT index status
SELECT * FROM INFORMATION_SCHEMA.INNODB_FT_CONFIG;

-- Test FULLTEXT stopwords (common words ignored by engine)
SELECT * FROM INFORMATION_SCHEMA.INNODB_FT_DEFAULT_STOPWORD;
```

---

## Workflow

1. **FULLTEXT index en el schema desde el inicio** — Es difícil agregar después con datos; diseñarlo desde el primer migration.
2. **`toBooleanMode()` transforma el input del usuario** — Nunca pasar texto crudo a `AGAINST()`.
3. **LIKE como fallback, no como primario** — FULLTEXT es órdenes de magnitud más rápido en tablas grandes.
4. **Loguear búsquedas siempre** — Las búsquedas sin resultados son el mejor indicador de qué libros faltan en el catálogo.
5. **Autocomplete con debounce 200ms** — Sin debounce, cada tecla dispara un request; el servidor no aguanta.
6. **Facets calculados con la misma query** — No hacer una query por cada faceta; agrupar en una sola pasada.
7. **`ft_min_word_len=2`** — Por defecto es 4; en español hay palabras cortas importantes ("el", "de", "la").
8. **`highlight()` solo en resultados visibles** — No hacer regex sobre campos que no se muestran al usuario.
