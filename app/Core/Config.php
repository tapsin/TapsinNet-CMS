<?php
declare(strict_types=1);

namespace Core;

/**
 * Config deposu — config/*.php dosyalarını yükler, nokta notasyonuyla erişir.
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $items = [];
    private static string $path = '';

    public static function loadFrom(string $dir): void
    {
        self::$path = rtrim($dir, '/');
        foreach (glob(self::$path . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            /** @psalm-suppress UnresolvableInclude */
            $data = require $file;
            if (is_array($data)) {
                self::$items[$key] = $data;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $cursor   = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return $default;
            }
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments    = explode('.', $key);
        $cursor      = &self::$items;
        $last        = array_pop($segments);

        foreach ($segments as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }
        $cursor[$last] = $value;
    }

    public static function all(): array
    {
        return self::$items;
    }
}
