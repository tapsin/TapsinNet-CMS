<?php
declare(strict_types=1);

namespace Core;

/**
 * Şablon motoru — saf PHP, derleme (compile) adımı yok.
 *
 *  · Görünümler storage/framework/views altında önbelleğe alınmaz
 *    (PHP dosyası zaten opcode cache'inin konusudur; ek bir katman
 *    gereksiz disk I/O'su yaratırdı).
 *  · $this->render() ile parça dahil etme (partial) — layout içinde kullanılır.
 *  · Layout, içerik tamponundan beslenir.
 */
final class View
{
    private static string $viewPath = '';
    private static string $basePath = '';
    private static array  $shared  = [];
    private static array  $sections = [];
    private static ?string $yielding = null;

    public function __construct(array $data = [])
    {
        self::$shared = $data;
    }

    public static function setPaths(string $views, string $base): void
    {
        self::$viewPath = rtrim($views, '/');
        self::$basePath = rtrim($base, '/');
    }

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function shared(string $key, mixed $default = null): mixed
    {
        return self::$shared[$key] ?? $default;
    }

    /** Bir görünümü render edip string döner. */
    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $file = self::resolve($template);
        if ($file === null) {
            throw new \RuntimeException("Görünüm bulunamadı: {$template}");
        }

        $content = self::capture($file, $data);

        if ($layout !== null) {
            $layoutFile = self::resolve($layout);
            if ($layoutFile === null) {
                throw new \RuntimeException("Layout bulunamadı: {$layout}");
            }
            self::$yielding = $content;
            $out           = self::capture($layoutFile, $data);
            self::$yielding = null;
            return $out;
        }

        return $content;
    }

    /** Yanıt olarak gönder. */
    public static function make(string $template, array $data = [], ?string $layout = null): Response
    {
        return Response::html(self::render($template, $data, $layout));
    }

    public static function exists(string $template): bool
    {
        return self::resolve($template) !== null;
    }

    /** Parça dahil etme. */
    public static function partial(string $template, array $data = []): string
    {
        $file = self::resolve($template);
        if ($file === null) {
            return '';
        }
        return self::capture($file, $data);
    }

    private static function resolve(string $template): ?string
    {
        // Nokta gösterimi → dizin: 'site.partials.card' → 'site/partials/card'
        $template = str_replace('\\', '/', $template);
        $template = str_replace('.', '/', $template);
        $template = str_replace(['..', "\0"], '', $template);
        $template = trim($template, '/');

        if ($template === '') {
            return null;
        }

        $file = self::$viewPath . '/' . $template . '.php';
        $real = realpath($file);
        $root = realpath(self::$viewPath);

        // Path traversal koruması: çözümlenen yol kökün altında olmalı
        if ($real === false || $root === false || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $real;
    }

    private static function capture(string $file, array $data): string
    {
        $level = ob_get_level();
        ob_start();
        try {
            (function (string $__file, array $__data): void {
                extract($__data, EXTR_SKIP);
                require $__file;
            })($file, $data + self::$shared);
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
        return (string) ob_get_clean();
    }

    // -------------------------------------------------------------- bölümler

    public static function start(string $name): void
    {
        self::$sections[$name] = '';
    }

    public static function stop(string $name): void
    {
        // no-op: content() doğrudan yazar
    }

    public static function append(string $name, string $value): void
    {
        self::$sections[$name] = (self::$sections[$name] ?? '') . $value;
    }

    public static function section(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    public static function hasSection(string $name): bool
    {
        return isset(self::$sections[$name]) && trim(self::$sections[$name]) !== '';
    }

    public static function yieldContent(): string
    {
        return self::$yielding ?? '';
    }

    public static function reset(): void
    {
        self::$sections = [];
        self::$yielding = null;
    }
}
