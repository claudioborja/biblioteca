<?php
declare(strict_types=1);

namespace Tests\Unit\Security;

use Helpers\SafeHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A10 — SSRF prevention.
 * SafeHttpClient::get() must only allow requests to the whitelisted host (openlibrary.org)
 * and must block all internal/private/forbidden destinations.
 */
final class SsrfPreventionTest extends TestCase
{
    // ── Blocked URLs (must return null) ──────────────────────────────────────

    #[Test]
    #[DataProvider('blockedUrls')]
    public function it_blocks_non_whitelisted_urls(string $url): void
    {
        $result = SafeHttpClient::get($url);
        $this->assertNull($result, "Expected null for blocked URL: {$url}");
    }

    public static function blockedUrls(): array
    {
        return [
            'localhost http'           => ['http://localhost/admin'],
            'localhost https'          => ['https://localhost/secret'],
            '127.0.0.1'               => ['http://127.0.0.1/'],
            '127.0.0.1 with port'     => ['http://127.0.0.1:8080/api'],
            '192.168.x.x (private)'   => ['http://192.168.1.1/router'],
            '10.x.x.x (private)'      => ['http://10.0.0.1/internal'],
            'evil.com'                 => ['https://evil.com/steal'],
            'metadata endpoint (AWS)' => ['http://169.254.169.254/latest/meta-data/'],
            'file:// URI'             => ['file:///etc/passwd'],
            'ftp:// URI'              => ['ftp://files.example.com/data'],
            'no scheme'               => ['openlibrary.org/api/books'],
            'empty string'            => [''],
        ];
    }

    // ── Scheme validation ────────────────────────────────────────────────────

    #[Test]
    public function it_blocks_non_http_schemes(): void
    {
        $this->assertNull(SafeHttpClient::get('file:///etc/passwd'));
        $this->assertNull(SafeHttpClient::get('ftp://openlibrary.org/file'));
        $this->assertNull(SafeHttpClient::get('gopher://openlibrary.org/'));
        $this->assertNull(SafeHttpClient::get('dict://openlibrary.org/'));
    }

    #[Test]
    public function it_blocks_url_with_different_host_even_if_path_contains_allowed_host(): void
    {
        // Trick: path contains the allowed host name but actual host is evil
        $this->assertNull(SafeHttpClient::get('https://evil.com/openlibrary.org/data'));
        $this->assertNull(SafeHttpClient::get('https://evil.com/?redirect=openlibrary.org'));
    }

    #[Test]
    public function it_blocks_url_with_credentials_targeting_allowed_host_via_bypass(): void
    {
        // "https://openlibrary.org@evil.com" resolves to evil.com
        $this->assertNull(SafeHttpClient::get('https://openlibrary.org@evil.com/path'));
    }

    // ── Whitelist ────────────────────────────────────────────────────────────

    #[Test]
    public function only_openlibrary_org_is_in_the_whitelist(): void
    {
        // Verify the whitelist is not overly permissive by testing a close-but-not-exact host
        $this->assertNull(SafeHttpClient::get('https://api.openlibrary.org/books'));
        $this->assertNull(SafeHttpClient::get('https://openlibrary.org.evil.com/books'));
        $this->assertNull(SafeHttpClient::get('https://myopenlibrary.org/books'));
    }
}
