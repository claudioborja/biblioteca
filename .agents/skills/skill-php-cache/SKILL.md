---
name: skill-php-cache
description: "**WORKFLOW SKILL** — Lightweight caching in pure PHP without Redis or Memcached. USE FOR: APCu in-memory cache; file-based cache for shared hosting; OPcache configuration and management; query result caching; fragment/HTML caching; cache tags and invalidation; cache-aside pattern; TTL management; cache warming; MariaDB query cache strategy; HTTP cache headers (ETag, Last-Modified, Cache-Control); browser caching via .htaccess; cache for sessions and rate limiting; cache statistics and monitoring; cache that works on VPS and limited hosting panels with no extra services required. DO NOT USE FOR: Redis or Memcached (requires extra service); CDN configuration; Varnish."
---

# PHP Cache — Lightweight Caching Without External Services

## Core Philosophy

- **No external services required**: APCu (shared memory) + file cache covers all scenarios on any host.
- **Cache is a hint, not a source of truth**: Always able to rebuild from DB if cache is cold or corrupt.
- **TTL + explicit invalidation**: Don't rely solely on expiry — invalidate on write.
- **Cache at the right layer**: Query results → service layer; rendered fragments → view layer; HTTP responses → browser.
- **Measure hit ratio**: A cache with < 80% hit rate needs better key design, not more cache.

---

## Cache Driver Interface

```php
<?php
// src/Cache/CacheInterface.php
declare(strict_types=1);

namespace Cache;

interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): bool;
    public function has(string $key): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function remember(string $key, int $ttl, callable $callback): mixed;
    public function deletePattern(string $pattern): int;
}
```

---

## APCu Driver (VPS / PHP-FPM with APCu enabled)

```php
<?php
// src/Cache/ApcuCache.php
declare(strict_types=1);

namespace Cache;

final class ApcuCache implements CacheInterface
{
    private string $prefix;

    public function __construct(string $prefix = 'biblioteca:')
    {
        if (!extension_loaded('apcu') || !apcu_enabled()) {
            throw new \RuntimeException('APCu extension is not available or not enabled.');
        }
        $this->prefix = $prefix;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = apcu_fetch($this->prefix . $key, $success);
        return $success ? $value : $default;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        return apcu_store($this->prefix . $key, $value, $ttl);
    }

    public function has(string $key): bool
    {
        return apcu_exists($this->prefix . $key);
    }

    public function delete(string $key): bool
    {
        return apcu_delete($this->prefix . $key);
    }

    public function clear(): bool
    {
        return apcu_clear_cache();
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) return $cached;

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function deletePattern(string $pattern): int
    {
        $count    = 0;
        $info     = apcu_cache_info();
        $fullPat  = '#^' . preg_quote($this->prefix, '#') . $pattern . '#';

        foreach ($info['cache_list'] ?? [] as $entry) {
            if (preg_match($fullPat, $entry['info'])) {
                apcu_delete($entry['info']);
                $count++;
            }
        }

        return $count;
    }

    public function stats(): array
    {
        $info = apcu_cache_info(true);
        $sma  = apcu_sma_info();

        return [
            'hits'       => $info['num_hits']   ?? 0,
            'misses'     => $info['num_misses']  ?? 0,
            'hit_ratio'  => $info['num_hits'] > 0
                ? round($info['num_hits'] / ($info['num_hits'] + $info['num_misses']) * 100, 2)
                : 0,
            'entries'    => $info['num_entries'] ?? 0,
            'memory_mb'  => round(($sma['seg_size'] - $sma['avail_mem']) / 1024 / 1024, 2),
            'free_mb'    => round($sma['avail_mem'] / 1024 / 1024, 2),
        ];
    }
}
```

---

## File Cache Driver (Shared Hosting / No APCu)

```php
<?php
// src/Cache/FileCache.php
declare(strict_types=1);

namespace Cache;

final class FileCache implements CacheInterface
{
    private string $dir;
    private string $prefix;

    public function __construct(
        string $dir    = '',
        string $prefix = 'lib_',
    ) {
        $this->dir    = $dir ?: BASE_PATH . '/storage/cache';
        $this->prefix = $prefix;

        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->path($key);

        if (!file_exists($file)) return $default;

        $data = unserialize(file_get_contents($file));

        if ($data['expires'] !== 0 && $data['expires'] < time()) {
            unlink($file);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $data = serialize([
            'value'   => $value,
            'expires' => $ttl === 0 ? 0 : time() + $ttl,
        ]);

        return file_put_contents($this->path($key), $data, LOCK_EX) !== false;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        $file = $this->path($key);
        return file_exists($file) ? unlink($file) : true;
    }

    public function clear(): bool
    {
        foreach (glob($this->dir . '/' . $this->prefix . '*.cache') ?: [] as $file) {
            unlink($file);
        }
        return true;
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) return $cached;

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function deletePattern(string $pattern): int
    {
        $count   = 0;
        $fullPat = '#^' . preg_quote($this->prefix, '#') . $pattern . '#';

        foreach (glob($this->dir . '/' . $this->prefix . '*.cache') ?: [] as $file) {
            $name = basename($file, '.cache');
            if (preg_match($fullPat, $name)) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }

    /** Purge expired files — run via cron */
    public function purgeExpired(): int
    {
        $count = 0;
        foreach (glob($this->dir . '/' . $this->prefix . '*.cache') ?: [] as $file) {
            $data = unserialize(file_get_contents($file));
            if ($data['expires'] !== 0 && $data['expires'] < time()) {
                unlink($file);
                $count++;
            }
        }
        return $count;
    }

    private function path(string $key): string
    {
        return $this->dir . '/' . $this->prefix . hash('xxh3', $key) . '.cache';
    }
}
```

---

## Auto-Selecting Cache (APCu → File fallback)

```php
<?php
// src/Cache/CacheFactory.php
declare(strict_types=1);

namespace Cache;

final class CacheFactory
{
    public static function make(): CacheInterface
    {
        if (extension_loaded('apcu') && apcu_enabled()) {
            return new ApcuCache('biblioteca:');
        }

        return new FileCache(
            dir: BASE_PATH . '/storage/cache',
            prefix: 'lib_',
        );
    }
}
```

---

## Query Cache (Service Layer Pattern)

```php
<?php
// src/Services/BookService.php — cache-aside pattern
declare(strict_types=1);

namespace Services;

use Cache\CacheInterface;
use Repositories\BookRepository;

final class BookService
{
    public function __construct(
        private readonly BookRepository  $books,
        private readonly CacheInterface  $cache,
    ) {}

    public function getFeatured(): array
    {
        return $this->cache->remember('books:featured', 1800, function () {
            return $this->books->findFeatured(limit: 10);
        });
    }

    public function findById(int $id): ?array
    {
        return $this->cache->remember("books:id:{$id}", 3600, function () use ($id) {
            return $this->books->findById($id);
        });
    }

    public function getByCategory(int $categoryId, int $page = 1): array
    {
        $key = "books:category:{$categoryId}:page:{$page}";
        return $this->cache->remember($key, 900, function () use ($categoryId, $page) {
            return $this->books->findByCategory($categoryId, perPage: 20, page: $page);
        });
    }

    /** Call on any book write */
    public function invalidateBook(int $id): void
    {
        $this->cache->delete("books:id:{$id}");
        $this->cache->delete('books:featured');
        $this->cache->deletePattern('books:category:.*');
    }

    public function create(array $data): int
    {
        $id = $this->books->insert($data);
        $this->cache->delete('books:featured');
        $this->cache->deletePattern('books:category:.*');
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $result = $this->books->update($id, $data);
        $this->invalidateBook($id);
        return $result;
    }

    public function delete(int $id): bool
    {
        $result = $this->books->softDelete($id);
        $this->invalidateBook($id);
        return $result;
    }
}
```

---

## HTML Fragment Cache

```php
<?php
// src/Cache/FragmentCache.php
declare(strict_types=1);

namespace Cache;

final class FragmentCache
{
    private ?string $currentKey = null;

    public function __construct(private readonly CacheInterface $cache) {}

    public function start(string $key, int $ttl = 3600): bool
    {
        $cached = $this->cache->get($key);

        if ($cached !== null) {
            echo $cached;
            return false; // cached — skip the block
        }

        $this->currentKey = $key;
        $this->currentTtl = $ttl;
        ob_start();
        return true; // not cached — render the block
    }

    public function end(): void
    {
        $output = ob_get_clean();
        $this->cache->set($this->currentKey, $output, $this->currentTtl);
        echo $output;
        $this->currentKey = null;
    }

    private int $currentTtl = 3600;
}
```

```php
<!-- views/home.php — fragment cache usage -->
<?php if ($fragmentCache->start('home:stats:widget', 900)): ?>
    <div class="stats-widget">
        <span><?= $view->e($totalBooks) ?> libros</span>
        <span><?= $view->e($activeLoans) ?> préstamos activos</span>
    </div>
<?php $fragmentCache->end(); endif; ?>
```

---

## Rate Limiting with Cache

```php
<?php
// src/Cache/RateLimiter.php
declare(strict_types=1);

namespace Cache;

final class RateLimiter
{
    public function __construct(private readonly CacheInterface $cache) {}

    /**
     * @param string $key    Unique identifier (e.g. "login:127.0.0.1")
     * @param int    $max    Maximum attempts
     * @param int    $window Time window in seconds
     */
    public function attempt(string $key, int $max = 5, int $window = 60): bool
    {
        $cacheKey = 'rate:' . hash('xxh3', $key);
        $data     = $this->cache->get($cacheKey) ?? ['count' => 0, 'reset_at' => time() + $window];

        if (time() > $data['reset_at']) {
            $data = ['count' => 0, 'reset_at' => time() + $window];
        }

        if ($data['count'] >= $max) return false;

        $data['count']++;
        $this->cache->set($cacheKey, $data, $window);
        return true;
    }

    public function remaining(string $key, int $max = 5, int $window = 60): int
    {
        $data = $this->cache->get('rate:' . hash('xxh3', $key)) ?? ['count' => 0];
        return max(0, $max - $data['count']);
    }

    public function clear(string $key): void
    {
        $this->cache->delete('rate:' . hash('xxh3', $key));
    }
}
```

---

## HTTP Cache Headers

```php
<?php
// src/Cache/HttpCache.php
declare(strict_types=1);

namespace Cache;

final class HttpCache
{
    /** Static assets — cache 1 year (immutable, versioned URLs) */
    public static function immutable(): void
    {
        header('Cache-Control: public, max-age=31536000, immutable');
    }

    /** Public pages — cache with revalidation */
    public static function public(int $maxAge = 300, int $sMaxAge = 600): void
    {
        header("Cache-Control: public, max-age={$maxAge}, s-maxage={$sMaxAge}");
    }

    /** No cache — for authenticated pages, forms, dashboards */
    public static function noStore(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }

    /** ETag-based validation */
    public static function etag(mixed $data): bool
    {
        $etag = '"' . md5(serialize($data)) . '"';
        header("ETag: {$etag}");

        $clientEtag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($clientEtag === $etag) {
            http_response_code(304);
            return true; // already cached — stop rendering
        }

        return false;
    }

    /** Last-Modified validation */
    public static function lastModified(\DateTimeInterface $dt): bool
    {
        $formatted = $dt->format('D, d M Y H:i:s') . ' GMT';
        header("Last-Modified: {$formatted}");

        $clientDate = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
        if ($clientDate === $formatted) {
            http_response_code(304);
            return true;
        }

        return false;
    }
}
```

```php
// Controller usage
public function catalog(Request $request): void
{
    $books    = $this->bookService->getFeatured();
    $modified = new \DateTimeImmutable($books[0]['updated_at'] ?? 'now');

    if (HttpCache::lastModified($modified)) return; // 304 — no render needed

    HttpCache::public(maxAge: 300);
    echo $this->view->render('catalog/index', compact('books'));
}
```

---

## Browser Cache via .htaccess

```apache
# public/.htaccess — static asset caching
<IfModule mod_expires.c>
    ExpiresActive On

    # Images
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png  "access plus 1 year"
    ExpiresByType image/gif  "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"

    # Fonts
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType font/woff  "access plus 1 year"

    # CSS and JS (use versioned filenames: app.v2.css)
    ExpiresByType text/css               "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"

    # HTML — no cache
    ExpiresByType text/html "access plus 0 seconds"
</IfModule>
```

---

## Cache Key Conventions

```
books:id:{id}                   ← single book
books:featured                  ← featured books list
books:category:{id}:page:{n}    ← paginated category
books:search:{hash}             ← search results (hash of params)
users:id:{id}                   ← single user profile
loans:active:user:{id}          ← active loans by user
stats:dashboard                 ← dashboard counters
rate:{hash}                     ← rate limit counter
```

---

## OPcache Management

```php
<?php
// public/opcache-clear.php — protected endpoint (only accessible from localhost or with secret)
declare(strict_types=1);

$secret = $_GET['secret'] ?? '';
$valid  = hash_equals($_ENV['OPCACHE_SECRET'] ?? '', $secret);
$local  = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

if (!$valid && !$local) {
    http_response_code(403);
    exit('Forbidden');
}

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo json_encode(['status' => 'ok', 'message' => 'OPcache cleared']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'OPcache not available']);
}
```

```php
<?php
// OPcache status check
$status = opcache_get_status();
echo "Hit rate: " . round($status['opcache_statistics']['opcache_hit_rate'], 2) . "%\n";
echo "Cached files: " . $status['opcache_statistics']['num_cached_files'] . "\n";
echo "Memory used: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB\n";
```

---

## Cron: Purge File Cache

```bash
# crontab — purge expired file cache entries daily at 3am
0 3 * * * php /var/www/biblioteca/bin/cache-purge.php
```

```php
<?php
// bin/cache-purge.php
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

$cache = new \Cache\FileCache();
$purged = $cache->purgeExpired();
echo date('Y-m-d H:i:s') . " — Purged {$purged} expired cache entries.\n";
```

---

## Cache Configuration (.env)

```dotenv
CACHE_DRIVER=auto          # auto | apcu | file
CACHE_PREFIX=biblioteca:
CACHE_TTL_DEFAULT=3600
OPCACHE_SECRET=random_secret_here
```

---

## Workflow

1. **Driver auto-detecting** — `CacheFactory::make()` selecciona APCu si está disponible; cae a FileCache automáticamente.
2. **`remember()` en lugar de get/set manual** — Más limpio y atómico para el patrón cache-aside.
3. **Invalidar en escritura, no por TTL** — Al actualizar un libro, borrar su clave; no esperar que expire.
4. **Claves jerárquicas** — `entity:type:id` permite borrar por patrón: `books:category:*`.
5. **HTTP cache headers primero** — Una respuesta 304 es la más rápida posible; implementar antes que caché de servidor.
6. **OPcache siempre activo en producción** — `validate_timestamps=0` en producción; limpiar en cada deploy.
7. **FileCache + cron de purga** — Sin cron, el caché de archivos crece indefinidamente.
8. **No cachear datos sensibles** — Nunca cachear contraseñas, tokens, datos médicos/financieros en file cache.
