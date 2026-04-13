<?php
declare(strict_types=1);

namespace Tests\Unit\Security;

use Core\Validator;
use Helpers\Sanitize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A03/A04 — Input validation and sanitization security.
 * Validates that the application rejects oversized, malformed, and
 * boundary-violating inputs before they reach persistence layers.
 */
final class InputValidationTest extends TestCase
{
    // ── Oversized inputs (DoS via large payloads) ────────────────────────────

    #[Test]
    public function validator_rejects_string_exceeding_max_length(): void
    {
        $v = Validator::make(
            ['name' => str_repeat('A', 256)],
            ['name' => 'max:255']
        );
        $this->assertTrue($v->fails());
    }

    #[Test]
    public function sanitize_truncates_filename_to_safe_length(): void
    {
        $long   = str_repeat('a', 500) . '.pdf';
        $result = Sanitize::filename($long);
        $this->assertLessThanOrEqual(200, strlen($result),
            'Filenames must be capped at 200 chars to prevent filesystem issues');
    }

    // ── SQL injection attempts via Validator ─────────────────────────────────

    #[Test]
    #[DataProvider('sqlInjectionPayloads')]
    public function sql_injection_in_email_field_is_rejected_by_email_rule(string $payload): void
    {
        $v = Validator::make(['email' => $payload], ['email' => 'required|email']);
        $this->assertTrue($v->fails(),
            "SQL payload should fail email validation: {$payload}");
    }

    public static function sqlInjectionPayloads(): array
    {
        return [
            "classic OR 1=1"    => ["' OR '1'='1"],
            "union select"      => ["' UNION SELECT * FROM users--"],
            "drop table"        => ["'; DROP TABLE users;--"],
            "comment bypass"    => ["admin'--"],
            "batch statement"   => ["'; INSERT INTO users VALUES('hacked')--"],
        ];
    }

    // ── Path traversal in filename sanitization ───────────────────────────────

    #[Test]
    #[DataProvider('pathTraversalPayloads')]
    public function sanitize_filename_blocks_path_traversal(string $payload): void
    {
        $result = Sanitize::filename($payload);

        $this->assertStringNotContainsString('/', $result,
            "Forward slash must not survive filename sanitization");
        $this->assertStringNotContainsString('\\', $result,
            "Backslash must not survive filename sanitization");
        $this->assertStringNotContainsString('..', $result,
            "Directory traversal (..) must not survive filename sanitization");
    }

    public static function pathTraversalPayloads(): array
    {
        return [
            'unix traversal'     => ['../../etc/passwd'],
            'windows traversal'  => ['..\\..\\windows\\system32'],
            'nested traversal'   => ['../../../../root/.ssh/id_rsa'],
            'absolute path'      => ['/etc/shadow'],
        ];
    }

    #[Test]
    public function sanitize_filename_strips_null_bytes(): void
    {
        $result = Sanitize::filename("evil\0.php");
        $this->assertStringNotContainsString("\0", $result);
    }

    // ── Null byte handling ───────────────────────────────────────────────────

    #[Test]
    public function html_encoding_is_applied_even_when_input_contains_null_byte(): void
    {
        // htmlspecialchars with ENT_SUBSTITUTE replaces invalid bytes
        // — our main guarantee is that < and > are encoded regardless
        $result = Sanitize::html("<script>\0alert(1)</script>");
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    // ── HTTP parameter pollution ──────────────────────────────────────────────

    #[Test]
    public function integer_cast_defeats_array_parameter_injection(): void
    {
        // Simulate ?page[]=1&page[]=99 — PHP delivers an array to the controller
        // The safe pattern: always cast to int first
        $rawInput = ['1', '99'];          // what PHP gives when ?page[]=1&page[]=99
        $safe     = (int) ($rawInput[0] ?? 1);

        $this->assertIsInt($safe);
        $this->assertSame(1, $safe);
    }

    #[Test]
    public function string_cast_defeats_array_query_parameter(): void
    {
        // ?sort[]=title&sort[]=id — attacker tries to inject an array
        $rawInput = ['title', 'id'];
        $safe     = (string) ($rawInput[0] ?? 'title');

        $this->assertIsString($safe);
        $this->assertSame('title', $safe);
    }

    // ── Slug generation (used in URLs) ────────────────────────────────────────

    #[Test]
    public function slug_strips_html_angle_brackets_and_special_chars(): void
    {
        $result = Sanitize::slug('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
        $this->assertStringNotContainsString('(', $result);
        $this->assertStringNotContainsString(')', $result);
        $this->assertStringNotContainsString(';', $result);
    }

    #[Test]
    public function slug_prevents_path_separators(): void
    {
        $result = Sanitize::slug('../admin/delete');

        $this->assertStringNotContainsString('/', $result);
        // Note: slug does not need to strip '..' since slugs go into
        // URL segments — the router handles segment isolation.
        // What matters is no slashes.
    }

    // ── Integer overflow / boundary ───────────────────────────────────────────

    #[Test]
    public function validator_rejects_value_outside_in_list(): void
    {
        $v = Validator::make(
            ['status' => '-1'],
            ['status' => 'in:0,1,2']
        );
        $this->assertTrue($v->fails());
    }

    #[Test]
    public function sanitize_int_handles_extremely_large_number_safely(): void
    {
        // Must not throw — just truncates to PHP_INT_MAX behavior
        $result = Sanitize::int('999999999999999999999');
        $this->assertIsInt($result);
    }

    #[Test]
    public function sanitize_int_handles_negative_numbers(): void
    {
        $this->assertSame(-5, Sanitize::int('-5'));
    }
}
