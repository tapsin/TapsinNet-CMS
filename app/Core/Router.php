<?php
declare(strict_types=1);

namespace Core;

/**
 * Router — Laravel benzeri, controller + action tabanlı.
 *
 *  · Route grupları: ->name(), ->middleware(), ->prefix()
 *  · Parametreler: {id} (sayısal), {slug} (herhangi), {path?} (opsiyonel)
 *  · Eşleşen route saklanır; controller bu bilgiye View ve URL üretiminde
 *    erişebilir (ör. aktif menü öğesi).
 */
final class Router
{
    private static ?self $instance = null;

    public static function current(): self
    {
        return self::$instance ??= new self();
    }

    public static function swap(?self $router): void
    {
        self::$instance = $router;
    }

    /** @var array<string,array<int,array>> */
    private array $routes = ['GET' => [], 'POST' => [], 'PUT' => [], 'PATCH' => [], 'DELETE' => []];
    /** @var array<int,array{pattern:string,params:array,group:?string,raw:string,methods:array}> */
    private array $compiled = [];
    /** @var array<int,array{name:string,handler:mixed,options:array}> */
    private array $groupStack = [];
    private string $groupPrefix = '';
    private ?string $groupName  = null;
    /** @var array<int,string> */
    private array $groupMiddleware = [];

    public string $matchedName = '';
    public array  $matchedParams = [];

    // ---------------------------------------------------------------- kayıt

    public function get(string $uri, mixed $handler, ?string $name = null): self
    {
        return $this->add('GET', $uri, $handler, $name);
    }

    public function post(string $uri, mixed $handler, ?string $name = null): self
    {
        return $this->add('POST', $uri, $handler, $name);
    }

    public function put(string $uri, mixed $handler, ?string $name = null): self
    {
        return $this->add('PUT', $uri, $handler, $name);
    }

    public function patch(string $uri, mixed $handler, ?string $name = null): self
    {
        return $this->add('PATCH', $uri, $handler, $name);
    }

    public function delete(string $uri, mixed $handler, ?string $name = null): self
    {
        return $this->add('DELETE', $uri, $handler, $name);
    }

    /** Kayıtlı route listesine gizli yardımcı (özellikle /admin için). */
    public function match(array $methods, string $uri, mixed $handler, ?string $name = null): self
    {
        $ok = true;
        foreach ($methods as $m) {
            $this->add(strtoupper($m), $uri, $handler, $name);
            $ok = false;
        }
        unset($ok);
        return $this;
    }

    public function group(array $options, callable $callback): void
    {
        $prevPrefix     = $this->groupPrefix;
        $prevName       = $this->groupName;
        $prevMiddleware = $this->groupMiddleware;

        $this->groupPrefix     = $prevPrefix . (string) ($options['prefix'] ?? '');
        $this->groupName       = isset($options['name']) ? trim((string) $prevName . '.' . $options['name'], '.') : $prevName;
        $this->groupMiddleware = array_merge($prevMiddleware, (array) ($options['middleware'] ?? []));

        $callback($this);

        $this->groupPrefix     = $prevPrefix;
        $this->groupName       = $prevName;
        $this->groupMiddleware = $prevMiddleware;
    }

    private function add(string $method, string $uri, mixed $handler, ?string $name): self
    {
        $uri = '/' . trim($this->groupPrefix . '/' . ltrim($uri, '/'), '/');
        $uri = $uri === '//' ? '/' : rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        $fullName = $name !== null
            ? ($this->groupName !== null ? $this->groupName . '.' . $name : $name)
            : null;

        $this->routes[$method][] = [
            'uri'        => $uri,
            'handler'    => $handler,
            'name'       => $fullName,
            'middleware' => $this->groupMiddleware,
        ];

        return $this;
    }

    // ------------------------------------------------------------ eşleştirme

    /** @return array{0:?callable,1:array,2:array} handler, params, middleware */
    public function resolve(string $method, string $path): array
    {
        $method = strtoupper($method);
        // HTML formları _method ile PUT/DELETE taklit edebilir
        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoof = strtoupper((string) $_POST['_method']);
            if (in_array($spoof, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $spoof;
            }
        }

        // HEAD -> GET
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $candidates = $this->routes[$method] ?? [];

        // 1) Tam eşleşme
        foreach ($candidates as $route) {
            if ($route['uri'] === $path) {
                $this->matchedName = (string) $route['name'];
                $this->matchedParams = [];
                return [$route['handler'], [], $route['middleware']];
            }
        }

        // 2) Parametreli eşleşme
        foreach ($candidates as $route) {
            $params = self::matchPattern($route['uri'], $path);
            if ($params !== null) {
                $this->matchedName   = (string) $route['name'];
                $this->matchedParams = $params;
                return [$route['handler'], $params, $route['middleware']];
            }
        }

        return [null, [], []];
    }

    /**
     * /isler/{slug} ile /isler/web-tasarimi karşılaştırır.
     * {path?} segmenti sıfır veya daha fazla segment kabul eder.
     */
    private static function matchPattern(string $pattern, string $path): ?array
    {
        $pSegs = $path === '/' ? [] : explode('/', trim($pattern, '/'));
        $aSegs = $path === '/' ? [] : explode('/', trim($path, '/'));

        // {path?} sonda ise esnek
        if (str_ends_with($pattern, '{path?}')) {
            $fixed = substr($pattern, 0, -strlen('{path?}'));
            $fixed = rtrim($fixed, '/');
            $fixedPrefix = $fixed === '' ? '' : $fixed;
            if ($fixedPrefix !== '' && !str_starts_with($path, $fixedPrefix . '/') && $path !== $fixedPrefix) {
                return null;
            }
            $rest = $fixedPrefix === '' ? $path : substr($path, strlen($fixedPrefix));
            return ['path' => trim($rest, '/')];
        }

        if (count($pSegs) !== count($aSegs)) {
            return null;
        }

        $params = [];
        foreach ($pSegs as $i => $seg) {
            if (str_starts_with($seg, '{') && str_ends_with($seg, '}')) {
                $name = substr($seg, 1, -1);
                $optional = str_ends_with($name, '?');
                $name = rtrim($name, '?');
                $value = $aSegs[$i];
                if ($value === '') {
                    if ($optional) {
                        $params[$name] = null;
                        continue;
                    }
                    return null;
                }
                $params[$name] = $value;
                continue;
            }
            if ($seg !== $aSegs[$i]) {
                return null;
            }
        }

        return $params;
    }

    public function name(): string
    {
        return $this->matchedName;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->matchedParams[$key] ?? $default;
    }

    public function params(): array
    {
        return $this->matchedParams;
    }

    /** @return array<int,array> */
    public function all(): array
    {
        return array_merge(...array_values($this->routes));
    }
}
