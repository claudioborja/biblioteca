<?php
declare(strict_types=1);

namespace Tests\Unit\Security;

use Helpers\Sanitize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A03 — Injection: XSS prevention.
 *
 * Sanitize::html() wraps htmlspecialchars(ENT_QUOTES|ENT_SUBSTITUTE).
 * It guarantees that HTML special characters are entity-encoded so that
 * user-supplied values CANNOT be interpreted as HTML markup.
 *
 * Note on scope:
 *   - Encoded output is safe in HTML text content and attribute values.
 *   - href/src attributes that accept URIs require ADDITIONAL URL validation
 *     (see OpenRedirectTest) — htmlspecialchars alone is not sufficient there.
 */
final class XssPreventionTest extends TestCase
{
    // ── Core invariant: < and > are always encoded ───────────────────────────

    #[Test]
    #[DataProvider('payloadsWithTags')]
    public function script_and_html_tags_are_encoded(string $payload): void
    {
        $output = Sanitize::html($payload);

        $this->assertStringNotContainsString('<script', $output,
            "Literal <script opening tag must not appear in encoded output");
        $this->assertStringNotContainsString('</script>', $output,
            "Literal </script> must not appear in encoded output");
        $this->assertStringContainsString('&lt;', $output,
            "< must be encoded as &lt;");
        $this->assertStringContainsString('&gt;', $output,
            "> must be encoded as &gt;");
    }

    public static function payloadsWithTags(): array
    {
        return [
            'classic script tag'    => ['<script>alert(1)</script>'],
            'img tag with onerror'  => ['<img src=x onerror=alert(1)>'],
            'svg onload'            => ['<svg onload=alert(1)>'],
            'style tag injection'   => ['<style>body{background:red}</style>'],
            'iframe injection'      => ['<iframe src="https://evil.com"></iframe>'],
        ];
    }

    // ── Double-encoding: already-escaped input is re-escaped, never passed raw ─

    #[Test]
    public function already_encoded_entities_are_double_encoded_not_bypassed(): void
    {
        // Input already contains HTML entities (e.g. from a previous encode pass)
        // htmlspecialchars must re-encode the & — output must never contain raw <>
        $input  = '&lt;script&gt;alert(1)&lt;/script&gt;';
        $output = Sanitize::html($input);

        $this->assertStringNotContainsString('<', $output,
            'Literal < must never appear in output, even from pre-encoded input');
        $this->assertStringNotContainsString('>', $output,
            'Literal > must never appear in output, even from pre-encoded input');
        // The & itself gets encoded — &lt; becomes &amp;lt;
        $this->assertStringContainsString('&amp;lt;', $output,
            'The & in &lt; must be encoded to &amp; (double-encoding, never a bypass)');
    }

    // ── Quote encoding prevents attribute breakout ───────────────────────────

    #[Test]
    public function double_quotes_are_encoded_to_prevent_attribute_breakout(): void
    {
        $input  = '"; onmouseover="alert(1)';
        $output = Sanitize::html($input);

        // The literal " must be encoded — the attribute cannot be broken out of
        $this->assertStringNotContainsString('"', $output);
        $this->assertStringContainsString('&quot;', $output);
    }

    #[Test]
    public function single_quotes_are_encoded_to_prevent_attribute_breakout(): void
    {
        $input  = "'; onmouseover='alert(1)";
        $output = Sanitize::html($input);

        $this->assertStringNotContainsString("'", $output);
        $this->assertStringContainsString('&#039;', $output);
    }

    #[Test]
    public function ampersand_is_encoded_to_prevent_entity_injection(): void
    {
        $output = Sanitize::html('A & B');
        $this->assertStringContainsString('&amp;', $output);
        // Must not be a bare &
        $this->assertStringNotContainsString('& B', $output);
    }

    // ── Event handler payloads are defused by tag encoding ───────────────────

    #[Test]
    public function onerror_inside_encoded_tag_cannot_execute(): void
    {
        $payload = '<img src=x onerror=alert(1)>';
        $output  = Sanitize::html($payload);

        // The tag is encoded — a browser renders this as literal text, not an element
        $this->assertStringContainsString('&lt;img', $output);
        $this->assertStringContainsString('onerror=alert(1)&gt;', $output);
        // "onerror" text is present but inside an encoded, non-executable string
        // — no browser will treat this as an event attribute
    }

    // ── Preserved safe content ───────────────────────────────────────────────

    #[Test]
    public function safe_text_is_preserved(): void
    {
        $this->assertSame('Cien años de soledad', Sanitize::html('Cien años de soledad'));
        $this->assertSame('García Márquez', Sanitize::html('García Márquez'));
        $this->assertSame('ISBN: 978-0-13-110362-7', Sanitize::html('ISBN: 978-0-13-110362-7'));
    }

    // ── JSON output encoding for JS data blocks ──────────────────────────────

    #[Test]
    public function json_hex_tag_encoding_prevents_script_injection_in_js_blocks(): void
    {
        // When JSON data is embedded directly in a <script> block,
        // JSON_HEX_TAG must be used to prevent </script> tag injection.
        $data = ['title' => '</script><script>alert(1)</script>'];
        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $this->assertStringNotContainsString('</script>', $json,
            'JSON_HEX_TAG must encode < and > to prevent script tag injection');
        $this->assertStringContainsString('\u003C', $json);
        $this->assertStringContainsString('\u003E', $json);
    }

    // ── URL header injection prevention ──────────────────────────────────────

    #[Test]
    public function url_encoding_strips_crlf_from_redirect_targets(): void
    {
        $malicious = "/catalog\r\nSet-Cookie: session=hacked";
        $encoded   = urlencode($malicious);

        $this->assertStringNotContainsString("\r", $encoded);
        $this->assertStringNotContainsString("\n", $encoded);
    }
}
