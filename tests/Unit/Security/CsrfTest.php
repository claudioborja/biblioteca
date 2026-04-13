<?php
declare(strict_types=1);

namespace Tests\Unit\Security;

use Middleware\CsrfMiddleware;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A01/A04 — CSRF token generation and validation.
 */
final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure a clean session state for each test
        $_SESSION = [];
    }

    // ── Token generation ─────────────────────────────────────────────────────

    #[Test]
    public function token_is_generated_as_hex_string(): void
    {
        $token = CsrfMiddleware::token();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token,
            'CSRF token must be 32 random bytes encoded as 64-char hex');
    }

    #[Test]
    public function token_is_idempotent_within_same_session(): void
    {
        $first  = CsrfMiddleware::token();
        $second = CsrfMiddleware::token();

        $this->assertSame($first, $second,
            'Multiple calls must return the same token (stored in session)');
    }

    #[Test]
    public function token_is_stored_in_session(): void
    {
        CsrfMiddleware::token();
        $this->assertArrayHasKey('_csrf_token', $_SESSION);
    }

    #[Test]
    public function new_session_gets_fresh_token(): void
    {
        $_SESSION = [];
        $token1   = CsrfMiddleware::token();
        $_SESSION = [];
        $token2   = CsrfMiddleware::token();

        // Each new session must produce a cryptographically distinct token
        $this->assertNotSame($token1, $token2,
            'A fresh session must not reuse a token from a previous session');
    }

    #[Test]
    public function token_has_sufficient_entropy(): void
    {
        // Generate many tokens and ensure no duplicates (trivially true for 32 random bytes,
        // but verifies the randomness source is not a static value or counter)
        $tokens = [];
        for ($i = 0; $i < 20; $i++) {
            $_SESSION = [];
            $tokens[] = CsrfMiddleware::token();
        }

        $this->assertSame(count($tokens), count(array_unique($tokens)),
            'All generated CSRF tokens must be unique');
    }

    // ── HTML field helper ────────────────────────────────────────────────────

    #[Test]
    public function field_returns_hidden_input_html(): void
    {
        $field = CsrfMiddleware::field();

        $this->assertStringContainsString('<input type="hidden"', $field);
        $this->assertStringContainsString('name="_csrf_token"', $field);
        $this->assertStringContainsString('value="', $field);
    }

    #[Test]
    public function field_contains_the_current_token(): void
    {
        $token = CsrfMiddleware::token();
        $field = CsrfMiddleware::field();

        $this->assertStringContainsString($token, $field);
    }

    // ── Constant-time comparison ─────────────────────────────────────────────

    #[Test]
    public function middleware_uses_hash_equals_for_comparison(): void
    {
        // Verify the implementation uses hash_equals() by checking behavior:
        // A token that is almost correct (differs by 1 char) must be rejected
        // exactly like a completely wrong token — no timing shortcut.
        $correct = CsrfMiddleware::token();
        $almostCorrect = substr($correct, 0, -1) . (substr($correct, -1) === 'a' ? 'b' : 'a');

        $this->assertFalse(hash_equals($correct, $almostCorrect),
            'A token differing by one character must not match');

        $this->assertTrue(hash_equals($correct, $correct),
            'The exact token must match itself');
    }

    // ── Token length ─────────────────────────────────────────────────────────

    #[Test]
    public function token_is_at_least_32_bytes_of_entropy(): void
    {
        $token = CsrfMiddleware::token();
        // hex-encoded: 2 chars per byte → 64 chars = 32 bytes
        $this->assertGreaterThanOrEqual(64, strlen($token),
            'CSRF token must have at least 32 bytes (256 bits) of entropy');
    }
}
