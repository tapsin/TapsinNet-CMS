<?php
declare(strict_types=1);

namespace Core;

/**
 * Sabit pencere hız sınırlandırıcı.
 *
 * Form spam'i ve kaba kuvvet parola denemelerini engeller.
 * Sayaçlar dosya tabanlı tutulur; çok sunuculu kurulumda
 * config/database.php içindeki 'redis'/'database' sürücüsüne
 * taşınabilir. Tek sunucu için dosya yeterlidir.
 */
final class RateLimiter
{
    private static string $dir = '';

    public static function setDir(string $dir): void
    {
        self::$dir = rtrim($dir, '/');
    }

    /** Kalan deneme hakkı. */
    public static function remaining(string $key, int $max, int $decay): int
    {
        $now = time();
        $hits = self::hits($key, $decay);
        $left = $max - count($hits);
        return $left < 0 ? 0 : $left;
    }

    /** Sınır aşıldı mı? Başarılı ise false döner ve sayacı artırmaz. */
    public static function tooManyAttempts(string $key, int $max, int $decay): bool
    {
        return count(self::hits($key, $decay)) >= $max;
    }

    /** Deneme kaydet — sınıra ulaşıldıysa false döner. */
    public static function hit(string $key, int $max, int $decay): bool
    {
        $file = self::file($key);
        $now  = time();
        $hits = self::hits($key, $decay);

        if (count($hits) >= $max) {
            self::write($file, $hits);
            return false;
        }

        $hits[] = $now;
        self::write($file, $hits);
        return true;
    }

    /** Sınırı aşmadan kaydet; aşıldıysa bekleme süresi saniyesini döner. */
    public static function attempt(string $key, int $max, int $decay): int
    {
        if (self::tooManyAttempts($key, $max, $decay)) {
            $hits = self::hits($key, $decay);
            $oldest = $hits === [] ? time() : $hits[0];
            return max(1, $decay - (time() - $oldest));
        }
        self::hit($key, $max, $decay);
        return 0;
    }

    public static function clear(string $key): void
    {
        $file = self::file($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /** @return array<int,int> */
    private static function hits(string $key, int $decay): array
    {
        $file = self::file($key);
        if (!is_file($file)) {
            return [];
        }
        $raw  = @file_get_contents($file);
        if ($raw === false || $raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        $cutoff = time() - $decay;
        $data  = array_values(array_filter(
            array_map('intval', $data),
            static fn (int $t): bool => $t > $cutoff
        ));
        return $data;
    }

    private static function write(string $file, array $hits): void
    {
        if (!is_dir(self::$dir)) {
            @mkdir(self::$dir, 0775, true);
        }
        @file_put_contents($file, json_encode(array_values($hits)), LOCK_EX);
    }

    private static function file(string $key): string
    {
        return self::$dir . '/' . hash('sha256', $key) . '.json';
    }

    /** E-posta gönderimi gibi gerçek limitler için. */
    public static function throttle(string $key, string $bucket): array
    {
        $cfg = Config::get('security.throttle.' . $bucket, ['max' => 5, 'decay' => 600]);
        $wait = self::attempt($key, (int) $cfg['max'], (int) $cfg['decay']);
        return [
            'allowed'   => $wait === 0,
            'wait'      => $wait,
            'max'       => (int) $cfg['max'],
            'remaining' => self::remaining($key, (int) $cfg['max'], (int) $cfg['decay']),
        ];
    }
}
