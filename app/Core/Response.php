<?php
declare(strict_types=1);

namespace Core;

/**
 * HTTP yanıtı.
 * Başlıklar burada merkezî olarak uygulanır (güvenlik başlıkları dahil).
 */
final class Response
{
    private string $content = '';
    private int $status = 200;
    /** @var array<string,string> */
    private array $headers = [];
    /** @var array<int,array{0:string,1:string}> */
    private array $cookies = [];

    public function __construct(string $content = '', int $status = 200)
    {
        $this->content = $content;
        $this->status  = $status;
    }

    public static function html(string $html, int $status = 200): self
    {
        return (new self($html, $status))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public static function json(array $data, int $status = 200): self
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return (new self($body === false ? '{}' : $body, $status))
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    public static function text(string $text, int $status = 200): self
    {
        return (new self($text, $status))->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public static function xml(string $xml, int $status = 200): self
    {
        return (new self($xml, $status))->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return (new self('', $status))->header('Location', $url);
    }

    public static function download(string $path, string $name): self
    {
        $mime = 'application/octet-stream';
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($path);
            if (is_string($detected) && $detected !== '') {
                $mime = $detected;
            }
        }
        return (new self('', 200))
            ->header('Content-Type', $mime)
            ->header('Content-Length', (string) filesize($path))
            ->header('Content-Disposition', 'attachment; filename="' . rawurlencode($name) . '"')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function cookie(string $key, string $value, array $options = []): self
    {
        $this->cookies[] = [$key, $value, $options];
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->content;
    }

    public function withHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function send(bool $withSecurityHeaders = true): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $key => $value) {
                header("$key: $value");
            }

            if ($withSecurityHeaders) {
                Security::applyHeaders();
            }

            foreach ($this->cookies as [$key, $value, $options]) {
                setcookie($key, $value, $options + [
                    'expires'  => time() + (int) ($options['expires'] ?? 0),
                    'path'     => $options['path'] ?? '/',
                    'secure'   => (bool) ($options['secure'] ?? false),
                    'httponly' => (bool) ($options['httponly'] ?? true),
                    'samesite' => $options['samesite'] ?? 'Lax',
                ]);
            }
        }

        echo $this->content;
    }
}
