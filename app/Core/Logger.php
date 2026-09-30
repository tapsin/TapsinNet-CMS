<?php
declare(strict_types=1);

namespace Core;

/**
 * Basit dosya tabanlı logger — hataları storage/logs/ altına yazar.
 * Production'da dahili detaylar asla kullanıcıya gösterilmez; sadece
 * özet mesaj + referans kodu döner.
 */
final class Logger
{
    private static string $dir = '';

    public static function setDir(string $dir): void
    {
        self::$dir = rtrim($dir, '/');
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function security(string $message, array $context = []): void
    {
        self::write('security', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        if (self::$dir === '') {
            self::$dir = dirname(__DIR__, 2) . '/storage/logs';
        }
        if (!is_dir(self::$dir)) {
            @mkdir(self::$dir, 0775, true);
        }

        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('c'),
            strtoupper($level),
            $message,
            $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
        );

        @file_put_contents(
            self::$dir . '/app-' . date('Y-m-d') . '.log',
            $line,
            FILE_APPEND | LOCK_EX
        );
    }
}
