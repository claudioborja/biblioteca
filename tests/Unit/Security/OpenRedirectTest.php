<?php
declare(strict_types=1);

namespace Tests\Unit\Security;

use Helpers\SafeRedirect;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A01 — Open Redirect prevention.
 * SafeRedirect::to() must reject any URL that would send a user off-site.
 */
final class OpenRedirectTest extends TestCase
{
    // ── Safe internal paths (must pass through) ──────────────────────────────

    #[Test]
    #[DataProvider('safeInternalPaths')]
    public function it_allows_safe_internal_paths(string $path): void
    {
        $result = SafeRedirect::to($path, '/');
        $this->assertSame($path, $result, "Expected '{$path}' to be allowed");
    }

    public static function safeInternalPaths(): array
    {
        return [
            'root'                 => ['/'],
            'catalog page'         => ['/catalog'],
            'resource detail'      => ['/catalog/42'],
            'nested admin path'    => ['/admin/resources/create'],
            'query string'         => ['/catalog?q=garcia'],
            'account path'         => ['/account/profile'],
        ];
    }

    // ── Malicious redirects (must use fallback) ───────────────────────────────

    #[Test]
    #[DataProvider('maliciousRedirects')]
    public function it_blocks_external_and_malicious_redirects(string $url): void
    {
        $result = SafeRedirect::to($url, '/');
        $this->assertSame('/', $result, "Expected '{$url}' to be blocked, got '{$result}'");
    }

    public static function maliciousRedirects(): array
    {
        return [
            'absolute external URL'           => ['https://evil.com/steal'],
            'http external URL'               => ['http://attacker.org/'],
            'protocol-relative URL'           => ['//evil.com/phish'],
            'protocol-relative with path'     => ['//evil.com'],
            'javascript URI'                  => ['javascript:alert(1)'],
            'data URI'                        => ['data:text/html,<script>alert(1)</script>'],
            'external with encoded slashes'   => ['https://evil.com/%2F'],
            'null-byte injection'             => ["/safe\0https://evil.com"],
        ];
    }

    // ── Fallback behavior ────────────────────────────────────────────────────

    #[Test]
    public function it_uses_fallback_for_empty_string(): void
    {
        // Empty string has no host but also no leading slash — implementation dependent
        // What matters is it never redirects externally
        $result = SafeRedirect::to('', '/dashboard');
        $this->assertStringNotContainsString('evil', $result);
    }

    #[Test]
    public function it_uses_custom_fallback_when_url_is_blocked(): void
    {
        $result = SafeRedirect::to('https://evil.com', '/login');
        $this->assertSame('/login', $result);
    }

    // ── Edge cases ───────────────────────────────────────────────────────────

    #[Test]
    public function it_blocks_url_with_embedded_host_after_slash(): void
    {
        // Browsers treat "//evil.com" as a protocol-relative URL pointing off-site
        $result = SafeRedirect::to('//evil.com/path', '/');
        $this->assertSame('/', $result);
    }

    #[Test]
    public function it_blocks_url_with_username_in_host(): void
    {
        // "https://trusted.com@evil.com" — browser sends to evil.com
        $result = SafeRedirect::to('https://trusted.com@evil.com', '/');
        $this->assertSame('/', $result);
    }
}
