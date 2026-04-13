<?php
// app/Helpers/Sanitize.php — Sanitización y escape de datos
declare(strict_types=1);

namespace Helpers;

final class Sanitize
{
    public static function string(mixed $value): string
    {
        return trim((string) $value);
    }

    public static function email(string $value): string
    {
        return strtolower(trim($value));
    }

    public static function html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function int(mixed $value): int
    {
        return (int) $value;
    }

    public static function slug(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[áàäâ]/u', 'a', $text);
        $text = preg_replace('/[éèëê]/u', 'e', $text);
        $text = preg_replace('/[íìïî]/u', 'i', $text);
        $text = preg_replace('/[óòöô]/u', 'o', $text);
        $text = preg_replace('/[úùüû]/u', 'u', $text);
        $text = preg_replace('/ñ/u', 'n', $text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }

    public static function filename(string $name): string
    {
        // Strip null bytes (prevent truncation attacks on some filesystems)
        $name = str_replace("\0", '', $name);
        // Replace all characters not in safe set (a-z A-Z 0-9 . _ -)
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
        // Collapse consecutive dots to prevent path traversal sequences (..)
        $name = preg_replace('/\.{2,}/', '.', $name);
        return substr($name, 0, 200);
    }
}
