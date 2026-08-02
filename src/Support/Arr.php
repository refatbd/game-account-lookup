<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Support;

final class Arr
{
    public static function get(array $data, string $path, mixed $default = null): mixed
    {
        if ($path === '') {
            return $data;
        }

        foreach (explode('.', $path) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }

            $data = $data[$segment];
        }

        return $data;
    }

    /**
     * @param list<string> $paths
     */
    public static function firstFilled(array $data, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = self::get($data, $path);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function __construct()
    {
    }
}
