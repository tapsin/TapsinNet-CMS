<?php
declare(strict_types=1);

namespace Core;

/**
 * Ortam değişkeni okuyucu.
 *
 * .env dosyası basit KEY=VALUE formatındadır. Değerler $_ENV ve
 * $_SERVER içine yazılır; gerçek ortam değişkenleri .env'i ezebilir.
 */
final class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded || !is_file($path) || !is_readable($path)) {
            self::$loaded = true;
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = substr($line, 7);
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));

            // Tırnak içindeyse tırnakları soy, kaçışları çöz
            if (strlen($val) >= 2) {
                $first = $val[0];
                if (($first === '"' || $first === "'") && str_ends_with($val, $first)) {
                    $val = substr($val, 1, -1);
                    if ($first === '"') {
                        $val = stripcslashes($val);
                    }
                }
            }

            $val = self::expand($val);

            // Gerçek ortam değişkeni .env'i ezebilir (container/CI uyumu)
            if (getenv($key) === false) {
                putenv("$key=$val");
            }
            $_ENV[$key]    = $val;
            $_SERVER[$key] = $val;
        }

        self::$loaded = true;
    }

    /** ${VAR} referanslarını çözer. */
    private static function expand(string $value): string
    {
        return (string) preg_replace_callback(
            '/\$\{([A-Z0-9_]+)\}/',
            static fn (array $m): string => (string) (getenv($m[1]) ?: $_SERVER[$m[1]] ?? ''),
            $value
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $v = getenv($key);
        if ($v === false) {
            $v = $_SERVER[$key] ?? $_ENV[$key] ?? null;
        }
        return $v === null || $v === '' ? $default : $v;
    }
}

/** Shorthand: env('KEY') */
function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

/** Shorthand: env_bool('FLAG', false) */
function env_bool(string $key, bool $default = false): bool
{
    $v = Env::get($key);
    if ($v === null) {
        return $default;
    }
    return in_array(strtolower((string) $v), ['1', 'true', 'on', 'yes'], true);
}
