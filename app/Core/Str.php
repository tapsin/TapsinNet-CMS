<?php
declare(strict_types=1);

namespace Core;

/**
 * Yardımcı string fonksiyonları — URL üretimi, slug, kısaltma, güvenli metin.
 */
final class Str
{
    /** Türkçe karakterleri koruyan URL dostu slug. */
    public static function slug(string $value, string $separator = '-'): string
    {
        $map = [
            'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'I' => 'i', 'İ' => 'i',
            'i' => 'i', 'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u',
            'â' => 'a', 'Â' => 'a', 'î' => 'i', 'Î' => 'i', 'û' => 'u', 'Û' => 'u',
        ];
        $value = strtr($value, $map);
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^a-z0-9]+/u', $separator, $value) ?? '';
        $value = trim($value, $separator);
        return preg_replace('/' . preg_quote($separator, '/') . '{2,}/', $separator, $value) ?? $value;
    }

    public static function random(int $length = 32): string
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }

    /** URL/path segment'lerini güvenli hale getirir. */
    public static function segment(string $value): string
    {
        $value = self::slug($value);
        return $value === '' ? 'icerik' : $value;
    }

    public static function limit(string $value, int $limit = 120, string $end = '…'): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }
        $cut  = mb_substr($value, 0, $limit, 'UTF-8');
        $sp   = mb_strrpos($cut, ' ', 0, 'UTF-8');
        if ($sp !== false && $sp > (int) ($limit * 0.6)) {
            $cut = mb_substr($cut, 0, $sp, 'UTF-8');
        }
        return rtrim($cut, ' ,.;:') . $end;
    }

    /** Türkçe tarih biçimlendirme. */
    public static function date(?string $date, ?string $format = null): string
    {
        if ($date === null || $date === '' || str_starts_with($date, '0000')) {
            return '—';
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return '—';
        }
        return date($format ?? (string) Config::get('app.date_format', 'd F Y'), $ts);
    }

    /** Göreli zaman: "3 saat önce". */
    public static function ago(?string $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return '—';
        }
        $diff = time() - $ts;
        if ($diff < 60)      return 'az önce';
        if ($diff < 3600)    return intdiv($diff, 60) . ' dk önce';
        if ($diff < 86400)   return intdiv($diff, 3600) . ' saat önce';
        if ($diff < 604800)  return intdiv($diff, 86400) . ' gün önce';
        if ($diff < 2592000) return intdiv($diff, 604800) . ' hafta önce';
        return self::date($date);
    }

    /** HTML kaçışı — ENT_QUOTES varsayılan. */
    public static function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    public static function excerpt(?string $html, int $limit = 160): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html)) ?? '');
        return self::limit($text, $limit);
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $out = '';
        foreach ($parts as $p) {
            if ($p === '') {
                continue;
            }
            $out .= mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8');
            if (mb_strlen($out, 'UTF-8') >= 2) {
                break;
            }
        }
        return $out === '' ? '?' : $out;
    }

    /** Basit diff-benzeri metin vurgulama (arama sonuçları için). */
    public static function highlight(string $text, string $needle): string
    {
        $safe = self::escape($text);
        if ($needle === '' || mb_strlen($needle) < 2) {
            return $safe;
        }
        $pattern = '/(' . preg_quote(self::escape($needle), '/') . ')/iu';
        $marked = preg_replace($pattern, '<mark>$1</mark>', $safe);
        return $marked ?? $safe;
    }

    public static function humanBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $bytes = max($bytes, 0);
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
