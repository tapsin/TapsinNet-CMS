<?php
declare(strict_types=1);

namespace Core;

/**
 * Güvenlik başlıkları, CSP üretimi ve genel güvenlik yardımcıları.
 */
final class Security
{
    /** Tüm yanıtlara güvenlik başlıklarını ekler. */
    public static function applyHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        foreach ((array) Config::get('security.headers', []) as $key => $value) {
            if (is_string($value) && $value !== '') {
                header("$key: $value");
            }
        }

        if (Config::get('security.csp.enabled', true)) {
            header('Content-Security-Policy: ' . self::csp(), !Config::get('security.csp.report_only', false));
        }
    }

    public static function csp(): string
    {
        $parts = [];
        foreach ((array) Config::get('security.csp.directives', []) as $directive => $values) {
            $values = array_values(array_filter((array) $values, static fn ($v): bool => $v !== '' && $v !== null));
            if ($values === []) {
                continue;
            }
            $parts[] = $directive . ' ' . implode(' ', $values);
        }
        return implode('; ', $parts);
    }

    /**
     * Hassas veri günlüğe yazılmadan önce maskeler.
     * @param array<string,mixed> $data
     */
    public static function mask(array $data): array
    {
        $sensitive = ['password', 'password_confirmation', 'current_password', 'new_password', 'token', '_token', 'secret', 'api_key'];
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $data[$key] = '***';
            }
        }
        return $data;
    }

    /**
     * Güvenli yönlendirme — açık yönlendirme (open redirect) koruması.
     * Yalnızca site içi, göreli yollar kabul edilir.
     */
    public static function safeRedirect(string $target, string $fallback = '/'): string
    {
        // Mutlak URL, şema-relative URL ve kontrol karakteri reddi
        if ($target === '' || !str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return $fallback;
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $target)) {
            return $fallback;
        }
        // http:// gibi şema gizleme denemeleri
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $target)) {
            return $fallback;
        }
        return $target;
    }

    /** Yalnızca izinli sıralama sütunlarını kabul eder. */
    public static function safeSort(string $column, array $allowed, string $default): string
    {
        return in_array($column, $allowed, true) ? $column : $default;
    }

    public static function safeDirection(string $dir): string
    {
        return strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
    }

    /** Sabit zamanlı karşılaştırma. */
    public static function secureEquals(string $known, string $given): bool
    {
        return hash_equals($known, $given);
    }
}
