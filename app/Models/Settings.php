<?php
declare(strict_types=1);

namespace Models;

use Core\Database;

/**
 * Site ayarları — tek sorguda yüklenir, istek boyunca bellekte.
 * Admin ayarları sayfasından düzenlenir.
 */
final class Settings
{
    /** @var array<string,array>|null */
    private static ?array $cache = null;

    public static function flush(): void
    {
        self::$cache = null;
    }

    /** @return array<string,array> */
    public static function all(?string $group = null): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (Database::select('SELECT * FROM settings ORDER BY grp ASC, key ASC') as $row) {
                    self::$cache[(string) $row['key']] = $row;
                }
            } catch (\Throwable) {
                self::$cache = [];
            }
        }

        if ($group === null) {
            return self::$cache;
        }

        return array_filter(self::$cache, static fn (array $r): bool => (string) $r['grp'] === $group);
    }

    public static function get(string $key, string $default = '', ?string $lang = null): string
    {
        $row = self::all()[$key] ?? null;
        if ($row === null) {
            return $default;
        }

        $lang     = $lang ?? \Core\Translator::lang();
        $fallback = \Core\Translator::fallbackLang();

        $primary   = (string) ($row['value_' . $lang] ?? '');
        $secondary = (string) ($row['value_' . $fallback] ?? '');

        $value = trim($primary) !== '' ? $primary : $secondary;
        if (trim($value) === '' && !empty($row['value_tr'])) {
            $value = (string) $row['value_tr'];
        }

        return trim($value) !== '' ? $value : $default;
    }

    public static function set(string $key, ?string $valueTr, ?string $valueEn, ?string $type = null, ?string $group = null): void
    {
        $existing = self::all()[$key] ?? null;

        if ($existing === null) {
            Database::execute(
                'INSERT INTO settings (key, value_tr, value_en, type, grp, updated_at)
                 VALUES (:k, :tr, :en, :t, :g, :u)',
                [
                    'k'  => $key,
                    'tr' => $valueTr,
                    'en' => $valueEn,
                    't'  => $type ?? 'text',
                    'g'  => $group ?? 'general',
                    'u'  => now(),
                ]
            );
        } else {
            $sets = ['value_tr = :tr', 'value_en = :en', 'updated_at = :u'];
            $binds = ['tr' => $valueTr, 'en' => $valueEn, 'u' => now()];
            if ($type !== null) { $sets[] = 'type = :t'; $binds['t'] = $type; }
            if ($group !== null) { $sets[] = 'grp = :g'; $binds['g'] = $group; }
            $binds['k'] = $key;

            Database::execute('UPDATE settings SET ' . implode(', ', $sets) . ' WHERE key = :k', $binds);
        }

        self::flush();
    }

    public static function delete(string $key): void
    {
        Database::execute('DELETE FROM settings WHERE key = :k', ['k' => $key]);
        self::flush();
    }

    /** Boolean tipindeki ayarlar. */
    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key, $default ? '1' : '0');
        return in_array(strtolower($v), ['1', 'true', 'on', 'yes'], true);
    }
}
