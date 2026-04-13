<?php
// app/Helpers/SafeRedirect.php — Prevención de open redirect
declare(strict_types=1);

namespace Helpers;

final class SafeRedirect
{
    public static function to(string $url, string $fallback = '/'): string
    {
        // Null bytes have no valid use in URLs — presence indicates injection attempt
        if (str_contains($url, "\0")) {
            return $fallback;
        }

        // Must start with a single slash (relative internal path)
        // Reject: empty, external URLs, protocol-relative (//), scheme-based (javascript:, data:)
        if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return $fallback;
        }

        $parsed = parse_url($url);

        // Reject if a host or scheme was parsed (shouldn't happen for /path, but be safe)
        if (isset($parsed['host']) || isset($parsed['scheme'])) {
            return $fallback;
        }

        return $url;
    }
}
