<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use Helpers\Isbn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IsbnTest extends TestCase
{
    // ── ISBN-13 validation ───────────────────────────────────────────────────

    #[Test]
    #[DataProvider('validIsbn13s')]
    public function it_validates_correct_isbn13(string $isbn): void
    {
        $this->assertTrue(Isbn::validIsbn13($isbn), "Expected '{$isbn}' to be valid ISBN-13");
    }

    public static function validIsbn13s(): array
    {
        return [
            'K&R C Programming'  => ['9780131103627'],
            '1984 George Orwell' => ['9780451524935'],
        ];
    }

    #[Test]
    #[DataProvider('invalidIsbn13s')]
    public function it_rejects_invalid_isbn13(string $isbn): void
    {
        $this->assertFalse(Isbn::validIsbn13($isbn), "Expected '{$isbn}' to be invalid ISBN-13");
    }

    public static function invalidIsbn13s(): array
    {
        return [
            'wrong check digit' => ['9780060883288'],
            'too short'         => ['978006088328'],
            'non-numeric'       => ['978ABCDEFGHIJ'],
            'empty'             => [''],
        ];
    }

    // ── ISBN-10 validation ───────────────────────────────────────────────────

    #[Test]
    #[DataProvider('validIsbn10s')]
    public function it_validates_correct_isbn10(string $isbn): void
    {
        $this->assertTrue(Isbn::validIsbn10($isbn), "Expected '{$isbn}' to be valid ISBN-10");
    }

    public static function validIsbn10s(): array
    {
        return [
            // 0-451-52493-4 (George Orwell, 1984)
            'all numeric A'  => ['0451524934'],
            // 0-13-110362-8 (K&R The C Programming Language)
            'all numeric B'  => ['0131103628'],
        ];
    }

    #[Test]
    public function it_rejects_invalid_isbn10(): void
    {
        $this->assertFalse(Isbn::validIsbn10('0451524933')); // wrong check digit
        $this->assertFalse(Isbn::validIsbn10('123'));         // too short
    }

    // ── normalize ────────────────────────────────────────────────────────────

    #[Test]
    public function it_normalizes_isbn10_to_isbn13(): void
    {
        // 0131103628 → 9780131103627
        $result = Isbn::normalize('0131103628');
        $this->assertSame('9780131103627', $result);
    }

    #[Test]
    public function it_normalizes_isbn13_with_hyphens(): void
    {
        $result = Isbn::normalize('978-0-13-110362-7');
        $this->assertSame('9780131103627', $result);
    }

    #[Test]
    public function it_returns_null_for_garbage_input(): void
    {
        $this->assertNull(Isbn::normalize('notanisbn'));
        $this->assertNull(Isbn::normalize(''));
        $this->assertNull(Isbn::normalize('12345'));
    }

    #[Test]
    public function it_returns_null_for_isbn13_with_bad_check_digit(): void
    {
        $this->assertNull(Isbn::normalize('9780131103628')); // last digit off by 1
    }

    // ── format ───────────────────────────────────────────────────────────────

    #[Test]
    public function it_formats_isbn13_with_hyphens(): void
    {
        // Isbn::format does: [0-2]-[3-4]-[5-7]-[8-11]-[12]
        $formatted = Isbn::format('9780131103627');
        $this->assertSame('978-01-311-0362-7', $formatted);
    }

    #[Test]
    public function it_returns_raw_value_when_isbn13_is_wrong_length(): void
    {
        $this->assertSame('123', Isbn::format('123'));
    }

    // ── isbn10 to isbn13 conversion ──────────────────────────────────────────

    #[Test]
    public function it_converts_isbn10_to_isbn13(): void
    {
        $isbn13 = Isbn::isbn10to13('0131103628');
        $this->assertSame('9780131103627', $isbn13);
        $this->assertTrue(Isbn::validIsbn13($isbn13));
    }
}
