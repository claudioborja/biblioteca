<?php

namespace Helpers;

/**
 * Icons Helper - Renders icons through external Iconify library.
 */
class Icons
{
    /**
     * Map internal icon keys to Iconify IDs.
     * Uses Heroicons set via Iconify CDN.
     */
    private const ICON_MAP = [
        'print' => 'heroicons:printer',
        'pencil-square' => 'heroicons:pencil-square',
        'trash' => 'heroicons:trash',
        'check' => 'heroicons:check',
        'x-mark' => 'heroicons:x-mark',
        'plus' => 'heroicons:plus',
        'arrow-right' => 'heroicons:arrow-right',
        'arrow-left' => 'heroicons:arrow-left',
        'arrow-path' => 'heroicons:arrow-path',
        'arrow-up-left' => 'heroicons:arrow-uturn-left',
        'document-duplicate' => 'heroicons:document-duplicate',
        'eye' => 'heroicons:eye',
        'cog-6-tooth' => 'heroicons:cog-6-tooth',
        'arrow-down-tray' => 'heroicons:arrow-down-tray',
        'arrow-up-tray' => 'heroicons:arrow-up-tray',
        'user' => 'heroicons:user',
        'magnifying-glass' => 'heroicons:magnifying-glass',
        'lock-closed' => 'heroicons:lock-closed',
        'lock-open' => 'heroicons:lock-open',
        'calendar-days' => 'heroicons:calendar-days',
        'clock' => 'heroicons:clock',
        'funnel' => 'heroicons:funnel',
        'bars-3' => 'heroicons:bars-3',
        'chevron-down' => 'heroicons:chevron-down',
        'chevron-up' => 'heroicons:chevron-up',
        'book-open' => 'heroicons:book-open',
        'list-bullet' => 'heroicons:list-bullet',
        'exclamation-triangle' => 'heroicons:exclamation-triangle',
    ];

    /**
     * Render an icon through Iconify web component.
     */
    public static function load(string $name, string $class = ''): string
    {
        $safeClass = htmlspecialchars(trim($class), ENT_QUOTES);
        $safeName = htmlspecialchars($name, ENT_QUOTES);
        $iconId = self::ICON_MAP[$name] ?? null;

        if ($iconId === null) {
            return '<span class="' . $safeClass . '" aria-label="' . $safeName . '">?</span>';
        }

        return '<iconify-icon icon="' . htmlspecialchars($iconId, ENT_QUOTES) . '" class="' . $safeClass . '" aria-hidden="true"></iconify-icon>';
    }

    // Convenience methods for commonly used icons
    public static function print(string $class = 'w-4 h-4'): string { return self::load('print', $class); }
    public static function edit(string $class = 'w-4 h-4'): string { return self::load('pencil-square', $class); }
    public static function delete(string $class = 'w-4 h-4'): string { return self::load('trash', $class); }
    public static function save(string $class = 'w-4 h-4'): string { return self::load('check', $class); }
    public static function check(string $class = 'w-4 h-4'): string { return self::load('check', $class); }
    public static function close(string $class = 'w-4 h-4'): string { return self::load('x-mark', $class); }
    public static function x(string $class = 'w-4 h-4'): string { return self::load('x-mark', $class); }
    public static function plus(string $class = 'w-4 h-4'): string { return self::load('plus', $class); }
    public static function arrowRight(string $class = 'w-4 h-4'): string { return self::load('arrow-right', $class); }
    public static function arrowLeft(string $class = 'w-4 h-4'): string { return self::load('arrow-left', $class); }
    public static function refresh(string $class = 'w-4 h-4'): string { return self::load('arrow-path', $class); }
    public static function returnIcon(string $class = 'w-4 h-4'): string { return self::load('arrow-up-left', $class); }
    public static function copy(string $class = 'w-4 h-4'): string { return self::load('document-duplicate', $class); }
    public static function eye(string $class = 'w-4 h-4'): string { return self::load('eye', $class); }
    public static function settings(string $class = 'w-4 h-4'): string { return self::load('cog-6-tooth', $class); }
    public static function download(string $class = 'w-4 h-4'): string { return self::load('arrow-down-tray', $class); }
    public static function upload(string $class = 'w-4 h-4'): string { return self::load('arrow-up-tray', $class); }
    public static function user(string $class = 'w-4 h-4'): string { return self::load('user', $class); }
    public static function search(string $class = 'w-4 h-4'): string { return self::load('magnifying-glass', $class); }
    public static function lock(string $class = 'w-4 h-4'): string { return self::load('lock-closed', $class); }
    public static function unlock(string $class = 'w-4 h-4'): string { return self::load('lock-open', $class); }
    public static function calendar(string $class = 'w-4 h-4'): string { return self::load('calendar-days', $class); }
    public static function clock(string $class = 'w-4 h-4'): string { return self::load('clock', $class); }
    public static function filter(string $class = 'w-4 h-4'): string { return self::load('funnel', $class); }
    public static function sort(string $class = 'w-4 h-4'): string { return self::load('bars-3', $class); }
    public static function chevronDown(string $class = 'w-4 h-4'): string { return self::load('chevron-down', $class); }
    public static function chevronUp(string $class = 'w-4 h-4'): string { return self::load('chevron-up', $class); }
    public static function book(string $class = 'w-4 h-4'): string { return self::load('book-open', $class); }
    public static function list(string $class = 'w-4 h-4'): string { return self::load('list-bullet', $class); }
    public static function alert(string $class = 'w-4 h-4'): string { return self::load('exclamation-triangle', $class); }
    public static function tick(string $class = 'w-4 h-4'): string { return self::load('check', $class); }
    public static function library(string $class = 'w-4 h-4'): string { return self::load('book-open', $class); }
}
