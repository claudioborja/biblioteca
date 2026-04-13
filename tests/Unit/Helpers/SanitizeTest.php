<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use Helpers\Sanitize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SanitizeTest extends TestCase
{
    // ── string ───────────────────────────────────────────────────────────────

    #[Test]
    public function it_trims_whitespace(): void
    {
        $this->assertSame('hello', Sanitize::string('  hello  '));
    }

    #[Test]
    public function it_casts_non_string_to_string(): void
    {
        $this->assertSame('42', Sanitize::string(42));
        $this->assertSame('', Sanitize::string(null));
    }

    // ── email ────────────────────────────────────────────────────────────────

    #[Test]
    public function it_lowercases_and_trims_email(): void
    {
        $this->assertSame('user@example.com', Sanitize::email('  USER@EXAMPLE.COM  '));
    }

    // ── html ─────────────────────────────────────────────────────────────────

    #[Test]
    public function it_escapes_html_special_chars(): void
    {
        $input  = '<script>alert("xss")</script>';
        $result = Sanitize::html($input);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    #[Test]
    public function it_escapes_double_and_single_quotes(): void
    {
        $result = Sanitize::html("He said \"hello\" and it's fine");
        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringContainsString('&#039;', $result);
    }

    // ── int ──────────────────────────────────────────────────────────────────

    #[Test]
    public function it_casts_string_to_int(): void
    {
        $this->assertSame(42, Sanitize::int('42'));
        $this->assertSame(0, Sanitize::int(''));
        $this->assertSame(0, Sanitize::int(null));
    }

    #[Test]
    public function it_truncates_float_to_int(): void
    {
        $this->assertSame(3, Sanitize::int('3.9'));
    }

    // ── slug ─────────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('slugCases')]
    public function it_generates_valid_slugs(string $input, string $expected): void
    {
        $this->assertSame($expected, Sanitize::slug($input));
    }

    public static function slugCases(): array
    {
        return [
            'basic text'          => ['Hello World',          'hello-world'],
            'spanish accents'     => ['Año Nuevo',            'ano-nuevo'],
            'ñ character'         => ['España',               'espana'],
            'multiple spaces'     => ['one  two   three',     'one-two-three'],
            'leading/trailing -'  => [' Test ',               'test'],
            'special chars'       => ['Hello! @World#',       'hello-world'],
            'already slug'        => ['cien-anos-soledad',    'cien-anos-soledad'],
            'mixed accents'       => ['Éclair Ñoño Über',     'eclair-nono-uber'],
        ];
    }

    // ── filename ─────────────────────────────────────────────────────────────

    #[Test]
    public function it_replaces_unsafe_chars_in_filename(): void
    {
        $result = Sanitize::filename('my file (1).pdf');
        $this->assertStringNotContainsString(' ', $result);
        $this->assertStringNotContainsString('(', $result);
        $this->assertStringNotContainsString(')', $result);
    }

    #[Test]
    public function it_truncates_filename_to_200_chars(): void
    {
        $long = str_repeat('a', 300) . '.pdf';
        $this->assertSame(200, strlen(Sanitize::filename($long)));
    }

    #[Test]
    public function it_preserves_safe_filename_chars(): void
    {
        $this->assertSame('cover_image-01.jpg', Sanitize::filename('cover_image-01.jpg'));
    }
}
