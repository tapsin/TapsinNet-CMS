<?php
declare(strict_types=1);

namespace Controllers;

use Core\Application;
use Core\Csrf;
use Core\Database;
use Core\HttpException;
use Core\Model;
use Core\Paginator;
use Core\Request;
use Core\Response;
use Core\Router;
use Core\Session;
use Core\Translator;
use Core\Uploader;
use Core\Validator;
use Core\ModuleRegistry;

/**
 * Tüm controller'ların ortak tabanı.
 */
abstract class Controller
{
    protected Request $request;
    protected Router  $router;

    /** Bu controller'ın bağlı olduğu modül slug'ı (boş = tümü erişilebilir). */
    protected string $moduleSlug = '';

    /** Görünüm öneki — 'site.home' gibi. */
    protected string $viewPrefix = '';

    /** Düzenleme sonrası dönecek liste rotası. */
    protected string $indexRoute = '/admin';

    public function setRouter(Router $router): void
    {
        $this->router = $router;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /** Modül kapısı — public/index.php tarafından okunur. */
    public static function moduleSlug(): string
    {
        return (new static())->moduleSlug;
    }

    // ------------------------------------------------------------ yanıtlar

    protected function view(string $template, array $data = [], ?string $layout = 'layouts.site'): Response
    {
        return Response::html(\Core\View::render($this->prefix($template), $data, $layout));
    }

    protected function viewNoLayout(string $template, array $data = []): Response
    {
        return Response::html(\Core\View::render($this->prefix($template), $data));
    }

    protected function prefix(string $template): string
    {
        return $this->viewPrefix === '' ? $template : $this->viewPrefix . '.' . $template;
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect(\Core\Security::safeRedirect($url), $status);
    }

    /**
     * Geri dön. Çapa (fragment) koruma kuralı global back() yardımcısında
     * yaşıyor; burada tekrar yazmıyoruz, iki uygulama birbirinden ayrışmasın.
     */
    protected function back(string $fallback = '/'): Response
    {
        return \back($fallback);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function notFound(string $message = ''): never
    {
        throw new HttpException(404, $message);
    }

    // ------------------------------------------------------------ form durumu

    /**
     * Doğrulama hatasında: eski girdileri ve hataları oturuma yazar,
     * geldiği sayfaya döner.
     */
    protected function withErrors(array $input, array $errors, ?string $redirectTo = null): Response
    {
        unset($input['_token'], $input['_method'], $input['password'], $input['password_confirmation']);

        Session::put('_old', $input);
        Session::put('_errors', $errors);

        return $this->back($redirectTo ?? $this->indexRoute);
    }

    protected function ok(string $message, ?string $redirectTo = null): Response
    {
        Session::flash('success', $message);
        return $this->redirect($redirectTo ?? $this->indexRoute);
    }

    protected function fail(string $message, ?string $redirectTo = null): Response
    {
        Session::flash('error', $message);
        return $this->redirect($redirectTo ?? $this->indexRoute);
    }

    // ------------------------------------------------------------ doğrulama

    /**
     * @return array{0:array,1:?string} doğrulanmış veri, hata mesajı
     */
    protected function validate(array $data, array $rules, array $labels = []): array
    {
        $v = Validator::make($data, $rules, $labels);
        if ($v->fails()) {
            return [[], $v->firstError()];
        }
        return [$v->validated(), null];
    }

    // ------------------------------------------------------------ sorgular

    protected function page(): int
    {
        return max(1, $this->request->queryInt('page', 1));
    }

    protected function term(): string
    {
        return mb_substr(trim((string) $this->request->query('q', '')), 0, 100);
    }

    protected function currentQuery(): array
    {
        $q = [];
        foreach (['q', 'kategori', 'category', 'album', 'type', 'filter', 'sirala', 'sort', 'yil', 'year'] as $key) {
            $v = $this->request->query($key);
            if ($v !== null && $v !== '' && is_string($v)) {
                $q[$key] = $v;
            }
        }
        return $q;
    }

    /** Beyaz listeli sıralama. */
    protected function sortFromRequest(array $allowed, string $defaultColumn, string $defaultDir = 'DESC'): array
    {
        $col = (string) $this->request->query('sirala', $defaultColumn);
        $dir = (string) $this->request->query('yon', $defaultDir);
        $col = \Core\Security::safeSort($col, $allowed, $defaultColumn);
        $dir = \Core\Security::safeDirection($dir);
        return [$col => $dir];
    }

    // ------------------------------------------------------------ modül

    protected function requireModule(string $slug): void
    {
        ModuleRegistry::requireActive($slug);
    }
}
