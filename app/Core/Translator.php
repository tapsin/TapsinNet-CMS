<?php
declare(strict_types=1);

namespace Core;

/**
 * Çeviri motoru — düz anahtar/değer dosyaları (lang/tr.php, lang/en.php).
 * İçerik tablolarındaki alanlar ayrıca Model katmanında
 * `locales()` üzerinden çözülür.
 */
final class Translator
{
    private static string $lang = 'tr';
    private static string $fallback = 'tr';
    /** @var array<string,array<string,string>> */
    private static array $lines = [];
    private static string $path = '';

    public static function boot(string $path, string $default, string $fallback): void
    {
        self::$path     = rtrim($path, '/');
        self::$lang     = $default;
        self::$fallback = $fallback;
        self::$lines    = [];
    }

    public static function setLang(string $lang): void
    {
        if (in_array($lang, (array) Config::get('i18n.available', ['tr', 'en']), true)) {
            self::$lang = $lang;
        }
    }

    public static function lang(): string
    {
        return self::$lang;
    }

    public static function fallbackLang(): string
    {
        return self::$fallback;
    }

    public static function available(): array
    {
        return (array) Config::get('i18n.available', ['tr', 'en']);
    }

    public static function isRTL(string $lang): bool
    {
        return in_array(substr($lang, 0, 2), ['ar', 'he', 'fa', 'ur'], true);
    }

    public static function t(string $key, array $replace = [], ?string $lang = null): string
    {
        $lang ??= self::$lang;
        $lines = self::lines($lang);

        $value = $lines[$key] ?? null;
        if ($value === null && $lang !== self::$fallback) {
            $value = self::lines(self::$fallback)[$key] ?? null;
        }
        if ($value === null) {
            return $key;   // çeviri yoksa anahtar döner — sessizce boş dönme
        }

        foreach ($replace as $from => $to) {
            $value = str_replace(':' . $from, (string) $to, $value);
        }
        return $value;
    }

    /** Yönü çevir (TR <-> EN). */
    public static function otherLang(): string
    {
        $avail = self::available();
        $idx   = array_search(self::$lang, $avail, true);
        return $avail[($idx === false ? 0 : $idx + 1) % max(1, count($avail))];
    }

    public static function name(string $lang): string
    {
        return (string) (Config::get('i18n.names.' . $lang, [] )[$lang] ?? strtoupper($lang));
    }

    /** @return array<string,string> */
    private static function lines(string $lang): array
    {
        if (isset(self::$lines[$lang])) {
            return self::$lines[$lang];
        }
        $file = self::$path . '/' . $lang . '.php';
        /** @psalm-suppress UnresolvableInclude */
        $data = is_file($file) ? require $file : [];

        // Lang dosyaları okunabilirlik için iç içe dizi kullanır; Translator
        // düz nokta anahtarı bekler. Bir kez düzleştirip önbelleğe alırız.
        return self::$lines[$lang] = is_array($data) ? self::flatten($data) : [];
    }

    /**
     * ['nav' => ['home' => 'Ana sayfa']] → ['nav.home' => 'Ana sayfa']
     *
     * @param array<mixed,mixed> $data
     * @return array<string,string>
     */
    private static function flatten(array $data, string $prefix = ''): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $out += self::flatten($value, $path);
            } elseif (is_scalar($value)) {
                $out[$path] = (string) $value;
            }
        }
        return $out;
    }
}
