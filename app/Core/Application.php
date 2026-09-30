<?php
declare(strict_types=1);

namespace Core;

/**
 * Uygulama çekirdeği — bootstrap, yaşam döngüsü, hata yönetimi.
 */
final class Application
{
    private static string $basePath  = '';
    private static ?self $instance   = null;
    private static bool $booted      = false;
    private static bool $isConsole   = false;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public static function boot(string $basePath, bool $console = false): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted    = true;
        self::$isConsole = $console;
        self::$basePath  = rtrim($basePath, '/');

        Env::load(self::$basePath . '/.env');
        Config::loadFrom(self::$basePath . '/config');
        Logger::setDir(self::$basePath . '/storage/logs');
        RateLimiter::setDir(self::$basePath . '/storage/cache/ratelimit');
        View::setPaths(self::$basePath . '/app/Views', self::$basePath);

        date_default_timezone_set((string) Config::get('app.timezone', 'Europe/Istanbul'));
        Translator::boot(
            self::$basePath . '/lang',
            (string) Config::get('i18n.default', 'tr'),
            (string) Config::get('i18n.fallback', 'tr')
        );
        mb_internal_encoding('UTF-8');

        foreach ([self::$basePath . '/storage/cache/ratelimit', self::$basePath . '/storage/logs'] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }

        $debug = (bool) Config::get('app.debug', false);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);
        if ($debug) {
            ini_set('display_startup_errors', '1');
        }

        // Oturum
        if (!$console) {
            Session::start();
            self::bootLocale();
        }
    }

    /** Dil çözümlemesi: URL segmenti > oturum > çerez > varsayılan. */
    private static function bootLocale(): void
    {
        $cfg      = Config::get('i18n');
        $default  = (string) $cfg['default'];

        $request = Request::current();
        $lang    = $default;

        $segmentKey = (string) $cfg['segment'];
        $segments   = array_values(array_filter(explode('/', trim($request->path(), '/')), 'strlen'));
        if (($segments[0] ?? null) === $segmentKey) {
            $candidate = $segments[1] ?? '';
            if (in_array($candidate, Translator::available(), true)) {
                $lang = $candidate;
            }
        } elseif (in_array($segments[0] ?? '', Translator::available(), true)) {
            $lang = $segments[0];
        } else {
            $cookie = $request->cookie((string) $cfg['cookie']);
            if ($cookie !== null && in_array($cookie, Translator::available(), true)) {
                $lang = $cookie;
            } elseif ($saved = Session::get((string) $cfg['session_key'])) {
                if (in_array($saved, Translator::available(), true)) {
                    $lang = $saved;
                }
            }
        }

        Translator::setLang($lang);
        View::share('locale', Translator::lang());
        View::share('availableLocales', Translator::available());
    }

    public static function basePath(string $append = ''): string
    {
        return self::$basePath . ($append === '' ? '' : '/' . ltrim($append, '/'));
    }

    public static function publicPath(string $append = ''): string
    {
        return self::$basePath . '/public' . ($append === '' ? '' : '/' . ltrim($append, '/'));
    }

    public static function storagePath(string $append = ''): string
    {
        return self::$basePath . '/storage' . ($append === '' ? '' : '/' . ltrim($append, '/'));
    }

    public static function isConsole(): bool
    {
        return self::$isConsole;
    }

    public static function debug(): bool
    {
        return (bool) Config::get('app.debug', false);
    }

    // ------------------------------------------------------------- hata yönetimi

    public static function setExceptionHandler(): void
    {
        set_exception_handler(static function (\Throwable $e): void {
            $status = $e instanceof HttpException ? $e->getStatusCode() : 500;

            if ($status >= 500) {
                Logger::error(sprintf('[%s] %s: %s @ %s:%d', $status, get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
            }

            $ref = strtoupper(substr(Str::random(8), 0, 8));
            if ($status >= 500) {
                Logger::error('Hata referansı: ' . $ref);
            }

            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, sprintf("[%d] %s: %s\n  at %s:%d\n", $status, get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
                exit(1);
            }

            $request = Request::current();
            if ($request->expectsJson()) {
                Response::json([
                    'error'   => $status >= 500 ? 'Sunucu hatası. Referans: ' . $ref : $e->getMessage(),
                    'status'  => $status,
                ], $status)->send();
                exit;
            }

            // Hata görünümü
            $data = [
                'status'   => $status,
                'title'    => HttpException::defaultTitle($status),
                'message'  => $e->getMessage(),
                'ref'      => $ref,
                'debug'    => self::debug(),
                'trace'    => self::debug() ? $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() : null,
                'user'     => Auth::user(),
            ];

            try {
                $html = View::render('errors.error', $data, 'layouts.minimal');
            } catch (\Throwable) {
                $html = '<!doctype html><meta charset="utf-8"><title>' . $status . '</title>'
                      . '<body style="font:16px/1.6 system-ui;padding:3rem;max-width:40rem;margin:auto">'
                      . '<h1 style="font-size:3rem;margin:0">' . $status . '</h1>'
                      . '<p>' . Str::escape(HttpException::defaultTitle($status)) . '</p>'
                      . '<p><a href="/">Ana sayfaya dön</a></p></body>';
            }

            Response::html($html, $status)->send();
            exit;
        });

        set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
            if ((error_reporting() & $severity) === 0) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    // ------------------------------------------------------------ sonlandırma

    public static function abort(int $status, ?string $message = null): never
    {
        throw new HttpException($status, $message ?? HttpException::defaultTitle($status));
    }

    public static function abortIf(bool $condition, int $status = 404, ?string $message = null): void
    {
        if ($condition) {
            self::abort($status, $message);
        }
    }
}
