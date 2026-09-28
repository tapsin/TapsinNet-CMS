<?php
declare(strict_types=1);

/**
 * Ön denetleyici — tüm istekler buraya gelir.
 *
 *  · .env yüklenir, config okunur
 *  · yönlendirme tablosu kurulur
 *  · modül kapıları uygulanır
 *  · admin rotaları oturum + CSRF ile korunur
 */

require dirname(__DIR__) . '/bootstrap/app.php';

use Core\Application;
use Core\Auth;
use Core\Csrf;
use Core\HttpException;
use Core\ModuleRegistry;
use Core\Request;
use Core\Response;
use Core\Router;
use Core\Session;
use Core\Translator;

$router = Router::current();
$request = Request::current();

// Oturum kimliğini periyodik olarak yenile
Session::maybeRegenerate();

// =============================================================================
//  YÖNLENDİRME TABLOLARI
// =============================================================================

require Application::basePath('routes/web.php');
require Application::basePath('routes/admin.php');

// =============================================================================
//  DİL SEGMENTİ
// =============================================================================
// /en/hizmetler  →  rota /hizmetler'e indirgenir, dil oturuma yazılır.
// Böylece rotalar dilden bağımsız kalır; her rotaya iki kez yazm gerekmez.
$cfg        = \Core\Config::get('i18n');
$segmentKey = (string) $cfg['segment'];
$path       = $request->path();
$segments   = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));

$routerPath = $path;

if (($segments[0] ?? null) === $segmentKey && in_array($segments[1] ?? '', Translator::available(), true)) {
    $lang = $segments[1];
    Translator::setLang($lang);
    Session::put((string) $cfg['session_key'], $lang);
    $routerPath = '/' . implode('/', array_slice($segments, 2));
    $routerPath = $routerPath === '/' ? '/' : rtrim($routerPath, '/');
} elseif (in_array($segments[0] ?? '', Translator::available(), true)) {
    $lang = $segments[0];
    Translator::setLang($lang);
    Session::put((string) $cfg['session_key'], $lang);
    $routerPath = '/' . implode('/', array_slice($segments, 1));
    $routerPath = $routerPath === '/' ? '/' : rtrim($routerPath, '/');
} else {
    // Açılışta varsayılan dile dön ve URL'i normalize et
    $routerPath = $path === '' ? '/' : rtrim($path, '/');
    if ($routerPath === '') {
        $routerPath = '/';
    }
}

// =============================================================================
//  EŞLEŞTİRME
// =============================================================================

[$handler, $params, $middleware] = $router->resolve($request->method(), $routerPath);

if ($handler === null) {
    throw new HttpException(404);
}

// =============================================================================
//  ARA KATMANLAR (middleware)
// =============================================================================

foreach ($middleware as $layer) {
    match ($layer) {
        'auth' => (static function (): void {
            if (!Auth::check()) {
                if (Request::current()->expectsJson()) {
                    throw new HttpException(401, 'Yetkilendirme gerekli.');
                }
                Session::put('intended', Request::current()->wantsBack('/admin'));
                Session::flash('warning', t('auth.login_required'));
                Response::redirect(url('/admin/giris'))->send();
                exit;
            }
        })(),

        'guest' => (static function (): void {
            if (Auth::check()) {
                Response::redirect(url('/admin'))->send();
                exit;
            }
        })(),

        'csrf' => Csrf::verifyOrFail($request),

        // Admin panelinde her istekte modül durumunu yeniden okuma
        default  => null,
    };
}

// =============================================================================
//  ÇAĞRI
// =============================================================================

// Controller çözümlemesi: "Site\HomeController@index" veya [Class::class, 'method']
$controller = null;
$action     = 'index';

if (is_array($handler)) {
    $controller = $handler[0];
    $action     = (string) $handler[1];
} elseif (is_string($handler) && str_contains($handler, '@')) {
    [$class, $action] = explode('@', $handler, 2);
    $controller = 'Controllers\\' . $class;
} elseif (is_string($handler)) {
    $controller = 'Controllers\\' . $handler;
    $action     = 'index';
} elseif (is_callable($handler)) {
    // Ham closure
    $result = $handler($params, $request);
    ($result instanceof Response ? $result : Response::html(''))->send();
    exit;
}

if ($controller === null) {
    throw new HttpException(404);
}

if (!class_exists($controller)) {
    \Core\Logger::error('Controller bulunamadı: ' . $controller);
    throw new HttpException(500, 'Controller bulunamadı: ' . $controller);
}

if (!method_exists($controller, $action)) {
    \Core\Logger::error('Eylem bulunamadı: ' . $controller . '@' . $action);
    throw new HttpException(500, 'Eylem bulunamadı: ' . $action);
}

// Modül kapısı: rota modülüyle ilişkiliyse ve modül pasifse 404
$requiredModule = $controller::moduleSlug();
if ($requiredModule !== '') {
    ModuleRegistry::requireActive($requiredModule);
}

// ============================================================================
//  VIEW PAYLAŞIMLARI
// ============================================================================
\Core\View::share('currentRoute', $router->name());
\Core\View::share('currentParams', $params);
\Core\View::share('currentPath', $routerPath);
\Core\View::share('locale', Translator::lang());
\Core\View::share('siteName', \Models\Settings::get('site_name', (string) \Core\Config::get('app.name')));
\Core\View::share('authUser', Auth::user());
\Core\View::share('isAdmin', Auth::check());
\Core\View::share('activeModules', ModuleRegistry::states());
\Core\View::share('navModules', ModuleRegistry::navigable());
\Core\View::share('homeModules', ModuleRegistry::homeModules());

$instance = new $controller();
$instance->setRouter($router);
$instance->setRequest($request);

$result = $instance->{$action}($params);

if ($result instanceof Response) {
    $result->send();
} elseif (is_string($result)) {
    Response::html($result)->send();
} else {
    Response::html('')->send();
}
