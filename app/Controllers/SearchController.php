<?php
// app/Controllers/SearchController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;

final class SearchController
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * GET /api/autocomplete?q=...
     * Returns JSON array of book suggestions (max 8).
     * Strategy:
     *   - Empty query   → 6 featured/new-acquisition books (cached)
     *   - ISBN-like     → prefix match on isbn_13 (BTREE index)
     *   - Text query    → FULLTEXT BOOLEAN on title+publisher  UNION  authors LIKE prefix
     * Always LIMIT 4, never a full-table scan on hot path.
     */
    public function autocomplete(Request $request): Response
    {
        $q = trim((string)$request->get('q', ''));

        // Security: max length to avoid abuse
        if (mb_strlen($q) > 100) {
            return $this->json([]);
        }

        // ── Empty query: return featured books (APCu-cached 5 min) ──────────
        if ($q === '') {
            return $this->json($this->featuredBooks());
        }

        // ── Single query strategy: LIKE on title + authors + isbn ────────────
        // FULLTEXT is unreliable for autocomplete: ignores short words, stopwords
        // ("el","la","de"), and can't index authors (longtext). LIKE with LIMIT 4
        // stops scanning after 4 matches — fast for all query lengths.
        $like = '%' . $q . '%';

        $stmt = $this->db->prepare("
            SELECT id, title, authors, cover_image, support_type, available_copies, isbn_13
            FROM resources
            WHERE is_active = 1
              AND (
                  title   LIKE ?
               OR CONVERT(authors, CHAR) LIKE ?
               OR isbn_13 LIKE ?
               OR publisher LIKE ?
              )
            ORDER BY
                CASE WHEN title LIKE ? THEN 0 ELSE 1 END,
                title ASC
            LIMIT 4
        ");
        $stmt->execute([$like, $like, $like, $like, $like]);

        return $this->json($this->formatBooks($stmt->fetchAll()));
    }

    /**
     * GET /search?q=... — Full search results page
     */
    public function search(Request $request): Response
    {
        $q = trim((string)$request->get('q', ''));

        if ($q !== '') {
            // Redirect to catalog with q filter
            return Response::redirect(BASE_URL . '/catalog?q=' . urlencode($q));
        }

        return Response::redirect(BASE_URL . '/catalog');
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * 6 recently acquired books for empty-query suggestions.
     * Cached in APCu for 5 minutes if available.
     */
    private function featuredBooks(): array
    {
        $cacheKey = 'autocomplete_featured';
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $ok);
            if ($ok) {
                return $cached;
            }
        }

        $stmt = $this->db->query("
            SELECT id, title, authors, cover_image, support_type, available_copies, isbn_13
            FROM resources
            WHERE is_new_acquisition = 1 AND is_active = 1
            ORDER BY acquired_at DESC
            LIMIT 4
        ");
        $result = $this->formatBooks($stmt->fetchAll());

        if (function_exists('apcu_store')) {
            apcu_store($cacheKey, $result, 300);
        }

        return $result;
    }

    /**
     * Normalize DB rows into the JSON shape expected by the frontend.
     */
    private function formatBooks(array $rows): array
    {
        return array_map(function (array $b): array {
            $authors = $b['authors'] ?? '';
            if (is_string($authors) && str_starts_with(trim($authors), '[')) {
                $dec     = json_decode($authors, true);
                $authors = is_array($dec) ? implode(', ', $dec) : $authors;
            }
            return [
                'id'        => (int)$b['id'],
                'title'     => $b['title'],
                'authors'   => $authors,
                'cover'     => $b['cover_image'] ?? null,
                'type'      => $b['support_type'] ?? 'physical',
                'available' => (int)($b['available_copies'] ?? 0),
                'isbn'      => $b['isbn_13'] ?? null,
                'url'       => BASE_URL . '/catalog/' . (int)$b['id'],
            ];
        }, $rows);
    }

    private function json(array $data): Response
    {
        return new Response(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            200,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }
}
