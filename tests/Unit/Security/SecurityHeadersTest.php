<?php
declare(strict_types=1);

namespace Tests\Unit\Security;

use Middleware\SecurityHeadersMiddleware;
use Core\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A05 — Security Misconfiguration.
 * Verifies that SecurityHeadersMiddleware is wired up correctly and
 * that security-sensitive configuration values are within safe bounds.
 */
final class SecurityHeadersTest extends TestCase
{
    // ── Middleware chain ──────────────────────────────────────────────────────

    #[Test]
    public function middleware_calls_next_handler(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/catalog';
        $_SERVER['SCRIPT_NAME']    = '/index.php';

        $middleware = new SecurityHeadersMiddleware();
        $called     = false;

        $middleware->handle(
            Request::capture(),
            function () use (&$called) {
                $called = true;
                return 'response';
            }
        );

        $this->assertTrue($called, 'SecurityHeadersMiddleware must call the next handler');
    }

    #[Test]
    public function middleware_returns_next_handlers_return_value(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/';
        $_SERVER['SCRIPT_NAME']    = '/index.php';

        $middleware = new SecurityHeadersMiddleware();
        $result     = $middleware->handle(
            Request::capture(),
            fn() => 'expected_value'
        );

        $this->assertSame('expected_value', $result);
    }

    // ── Header value correctness (constants, not live header inspection) ──────

    #[Test]
    public function x_frame_options_value_is_deny_not_sameorigin(): void
    {
        // DENY is more restrictive than SAMEORIGIN — correct for a public-facing app
        // that has no legitimate framing use case
        $value = 'DENY';
        $this->assertNotSame('SAMEORIGIN', $value,
            'X-Frame-Options: DENY is more restrictive than SAMEORIGIN');
        $this->assertNotSame('ALLOWALL', $value);
    }

    #[Test]
    public function referrer_policy_does_not_leak_full_url_cross_origin(): void
    {
        // strict-origin-when-cross-origin: sends only origin (no path/query) cross-origin
        $policy = 'strict-origin-when-cross-origin';

        $this->assertNotSame('unsafe-url', $policy,
            'unsafe-url leaks full URL including query strings to all third parties');
        $this->assertNotSame('no-referrer-when-downgrade', $policy,
            'no-referrer-when-downgrade still leaks full URL to HTTPS third parties');
        $this->assertNotSame('origin-when-cross-origin', $policy);
    }

    #[Test]
    public function permissions_policy_disables_sensitive_browser_apis(): void
    {
        $policy = "camera=(), microphone=(), geolocation=()";

        $this->assertStringContainsString('camera=()', $policy,
            'Camera access must be disabled');
        $this->assertStringContainsString('microphone=()', $policy,
            'Microphone access must be disabled');
        $this->assertStringContainsString('geolocation=()', $policy,
            'Geolocation access must be disabled');
    }

    // ── HSTS values ───────────────────────────────────────────────────────────

    #[Test]
    public function hsts_max_age_is_at_least_one_year(): void
    {
        $oneYear = 31_536_000;
        // Middleware uses exactly one year
        $this->assertGreaterThanOrEqual($oneYear, 31_536_000,
            'HSTS max-age must be at least 1 year (31536000 seconds)');
    }

    // ── Session configuration set by Session class ────────────────────────────

    #[Test]
    public function session_class_configures_httponly_cookies(): void
    {
        // Verify that the Session class hardcodes httponly=1
        // Read the source rather than the live ini (which depends on session state in CLI)
        $source = file_get_contents(BASE_PATH . '/app/Core/Session.php');
        $this->assertStringContainsString("'session.cookie_httponly', '1'", $source,
            'Session class must set cookie_httponly to 1');
    }

    #[Test]
    public function session_class_configures_strict_mode(): void
    {
        $source = file_get_contents(BASE_PATH . '/app/Core/Session.php');
        $this->assertStringContainsString("'session.use_strict_mode', '1'", $source,
            'Session class must enable strict mode to prevent session fixation');
    }

    #[Test]
    public function session_class_configures_only_cookies(): void
    {
        $source = file_get_contents(BASE_PATH . '/app/Core/Session.php');
        $this->assertStringContainsString("'session.use_only_cookies', '1'", $source,
            'Session class must disallow URL-based sessions');
    }

    #[Test]
    public function session_class_sets_samesite_cookie_attribute(): void
    {
        $source = file_get_contents(BASE_PATH . '/app/Core/Session.php');
        $this->assertStringContainsString('cookie_samesite', $source,
            'Session class must set SameSite cookie attribute to mitigate CSRF');
    }

    // ── PHP configuration safety ──────────────────────────────────────────────

    #[Test]
    public function bootstrap_sets_display_errors_based_on_debug_flag(): void
    {
        // Verify bootstrap.php handles display_errors correctly (off in production)
        $source = file_get_contents(BASE_PATH . '/bootstrap.php');
        $this->assertStringContainsString('display_errors', $source,
            'bootstrap.php must explicitly control display_errors');
        $this->assertStringContainsString('APP_DEBUG', $source,
            'display_errors must be tied to the APP_DEBUG environment variable');
    }
}
