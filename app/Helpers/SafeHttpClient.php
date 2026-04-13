<?php
// app/Helpers/SafeHttpClient.php — Cliente HTTP seguro (SSRF prevention)
declare(strict_types=1);

namespace Helpers;

final class SafeHttpClient
{
    private const ALLOWED_HOSTS = [
        'openlibrary.org',
    ];

    private const TIMEOUT = 5;

    public static function get(string $url): ?string
    {
        $parsed = parse_url($url);

        if (!isset($parsed['host']) || !in_array($parsed['host'], self::ALLOWED_HOSTS, true)) {
            return null;
        }

        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'], true)) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => self::TIMEOUT,
                'header'  => "User-Agent: Biblioteca/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);
        return $result !== false ? $result : null;
    }
}
