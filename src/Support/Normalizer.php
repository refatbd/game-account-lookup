<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Support;

final class Normalizer
{
    public static function gameCode(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', trim($value)));
    }

    public static function identifier(string|int $value): string
    {
        return trim((string) $value);
    }

    public static function nickname(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $nickname = trim(urldecode((string) $value));

        return $nickname === '' ? null : $nickname;
    }

    private function __construct()
    {
    }
}
