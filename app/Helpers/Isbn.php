<?php
// app/Helpers/Isbn.php — Validación y normalización de ISBN
declare(strict_types=1);

namespace Helpers;

final class Isbn
{
    public static function normalize(string $input): ?string
    {
        $clean = preg_replace('/[^0-9X]/i', '', strtoupper($input));

        if (strlen($clean) === 10) {
            if (!self::validIsbn10($clean)) return null;
            return self::isbn10to13($clean);
        }

        if (strlen($clean) === 13) {
            if (!self::validIsbn13($clean)) return null;
            return $clean;
        }

        return null;
    }

    public static function validIsbn10(string $isbn): bool
    {
        if (!preg_match('/^\d{9}[\dX]$/', $isbn)) return false;

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $isbn[$i] * (10 - $i);
        }
        $last = strtoupper($isbn[9]);
        $sum += $last === 'X' ? 10 : (int) $last;

        return $sum % 11 === 0;
    }

    public static function validIsbn13(string $isbn): bool
    {
        if (!preg_match('/^\d{13}$/', $isbn)) return false;

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $check = (10 - ($sum % 10)) % 10;

        return $check === (int) $isbn[12];
    }

    public static function isbn10to13(string $isbn10): string
    {
        $base = '978' . substr($isbn10, 0, 9);
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $base[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $check = (10 - ($sum % 10)) % 10;
        return $base . $check;
    }

    public static function format(string $isbn13): string
    {
        if (strlen($isbn13) !== 13) return $isbn13;
        return substr($isbn13, 0, 3) . '-' . substr($isbn13, 3, 2) . '-'
            . substr($isbn13, 5, 3) . '-' . substr($isbn13, 8, 4) . '-' . substr($isbn13, 12, 1);
    }
}
