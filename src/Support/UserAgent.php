<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Support;

final class UserAgent
{
    public static function resolve(
        string $environmentVariable,
        string $fallback,
        mixed $configured = null,
    ): string {
        $environmentValue = getenv($environmentVariable);
        $candidate = is_string($environmentValue) && trim($environmentValue) !== ''
            ? $environmentValue
            : (is_string($configured) ? $configured : '');

        $sanitized = trim(str_replace(["\r", "\n"], '', $candidate));

        return $sanitized !== '' ? $sanitized : $fallback;
    }
}
