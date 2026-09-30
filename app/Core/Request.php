<?php
declare(strict_types=1);

namespace Core;

/**
 * Gelen HTTP isteğinin normalize edilmiş temsili.
 * Global süperküresellerin controller'lara sızmasını engeller.
 */
final class Request
{
    private array $query;
    private array $body;
    private array $files;
    private array $server;
    private array $cookies;

    private static ?self $instance = null;

    public function __construct()
    {
        $this->query   = $_GET;
        $this->body    = $_POST;
        $this->files   = $_FILES;
        $this->server  = $_SERVER;
        $this->cookies = $_COOKIE;

        if (self::$instance === null) {
            self::$instance = $this;
        }
    }

    /** İstek nesnesi — uygulama boyunca tek örnek. */
    public static function current(): self
    {
        return self::$instance ??= new self();
    }

    /** Test/CLI için istek değiştirir. */
    public static function swap(?self $request): void
    {
        self::$instance = $request;
    }

    public function method(): string
    {
        $m = strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
        // HTML formları _method alanı ile PUT/PATCH/DELETE taklit edebilir
        if ($m === 'POST' && isset($this->body['_method'])) {
            $spoof = strtoupper((string) $this->body['_method']);
            if (in_array($spoof, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $spoof;
            }
        }
        return $m;
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function path(): string
    {
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        // Alt dizin kurulumu: /site/public/index.php isteklerinde taban yolu soyulur
        $script = (string) ($this->server['SCRIPT_NAME'] ?? '');
        $base   = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if ($base !== '' && $base !== '/' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        if ($uri === '' || $uri === false) {
            return '/';
        }

        return '/' . trim($uri, '/');
    }

    public function url(): string
    {
        return (string) ($this->server['REQUEST_URI'] ?? '/');
    }

    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host   = (string) ($this->server['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host . $this->url();
    }

    public function isSecure(): bool
    {
        if (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') {
            return true;
        }
        if ((string) ($this->server['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        if (Config::get('security.trust_proxy')) {
            $proto = $this->header('X-Forwarded-Proto');
            if ($proto === 'https') {
                return true;
            }
        }
        return false;
    }

    public function ip(): string
    {
        if (Config::get('security.trust_proxy')) {
            $fwd = $this->header('X-Forwarded-For');
            if ($fwd) {
                $first = trim(explode(',', $fwd)[0]);
                if (filter_var($first, FILTER_VALIDATE_IP)) {
                    return $first;
                }
            }
        }
        $ip = (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($this->server[$key]) ? (string) $this->server[$key] : $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        $v = $this->query[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    public function queryInt(string $key, int $default = 0): int
    {
        $v = $this->query[$key] ?? null;
        return is_numeric($v) ? (int) $v : $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function str(string $key, string $default = ''): string
    {
        $v = $this->input($key, $default);
        return is_scalar($v) ? trim((string) $v) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->input($key, $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    public function bool(string $key): bool
    {
        $v = $this->input($key);
        return in_array(is_string($v) ? strtolower($v) : $v, ['1', 'true', 'on', 'yes'], true);
    }

    public function arr(string $key): array
    {
        $v = $this->input($key, []);
        return is_array($v) ? $v : [];
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function file(string $key): ?array
    {
        $f = $this->files[$key] ?? null;
        if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $f;
    }

    public function cookie(string $key, ?string $default = null): ?string
    {
        $v = $this->cookies[$key] ?? $default;
        return is_string($v) ? $v : $default;
    }

    public function ajax(): bool
    {
        return strtolower((string) $this->header('X-Requested-With')) === 'xmlhttprequest'
            || str_contains((string) $this->header('Accept'), 'application/json');
    }

    public function expectsJson(): bool
    {
        return $this->ajax()
            || str_contains((string) $this->header('Accept'), 'application/json')
            || str_starts_with((string) $this->header('Content-Type'), 'application/json');
    }

    /** JSON gövde gövdesi (API uçları için). */
    public function json(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public function referer(): ?string
    {
        $r = $this->header('Referer');
        return is_string($r) && $r !== '' ? $r : null;
    }

    /**
     * Geri dönülecek YOL (path + query), doğrulanmış.
     *
     * DÖNÜŞÜM BİLEREKÇE: Bu metot eskiden Referer'ı olduğu gibi (mutlak URL)
     * döndürüyordu. Tarayıcılar Referer'ı daima mutlak gönderir ve
     * Security::safeRedirect() mutlak URL'i reddeder — yani geri dönüş her
     * zaman fallback'e düşüyordu. Yorum gönderimi gibi #comments yemine
     * düşen durumlarda tarayıcı çapayı mevcut adrese göre çözüp
     * POST adresine (404) gidiyordu.
     *
     * Aynı-origin doğrulaması korunur; yalnızca dönüş değeri yola indirgenir.
     */
    public function wantsBack(string $fallback = '/'): string
    {
        $ref = $this->referer();
        if ($ref === null) {
            return $fallback;
        }

        // HTTP response splitting: başlık değerine kontrol karakteri sızmaz
        if (preg_match('/[\x00-\x1F\x7F]/', $ref)) {
            return $fallback;
        }

        // Açık yönlendirme (open redirect) koruması: yalnızca aynı origin
        $host = (string) ($this->server['HTTP_HOST'] ?? '');
        $refHost = parse_url($ref, PHP_URL_HOST);
        if ($refHost !== null) {
            $refPort  = parse_url($ref, PHP_URL_PORT);
            $expected = $refHost . ($refPort ? ':' . $refPort : '');
            if ($expected !== $host) {
                return $fallback;
            }
        } elseif (!str_starts_with($ref, '/')) {
            return $fallback;   // göreli de değil, şema da yok → güvenlik yok
        }

        // Yalnızca yol + sorgu. Çapa (fragment) korunmaz: istenirse
        // back() yardımcısı fallback'ten ekler.
        $path  = parse_url($ref, PHP_URL_PATH);
        $query = parse_url($ref, PHP_URL_QUERY);
        $path  = (is_string($path) && $path !== '') ? $path : '/';

        return (is_string($query) && $query !== '') ? $path . '?' . $query : $path;
    }
}
