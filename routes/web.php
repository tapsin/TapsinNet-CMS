<?php
declare(strict_types=1);

/**
 * PUBLIC ROTALAR
 *
 * İçerik rotaları config/modules.php üzerinden DİNAMİK olarak üretilir:
 * bir modülün yolu tanımlandığında listeleme + detay rotaları otomatik gelir.
 * Modül pasifleştirildiğinde rota kayıtlı kalır ama public/index.php'teki
 * modül kapısı 404 döner.
 */

use Core\Router;
use Controllers\Site\{HomeController, CatalogController, PageController, ContactController,
                       SearchController, LocaleController, SitemapController, CommentController,
                       SocialFeedController};

/** @var Router $router */

// --- Ana sayfa --------------------------------------------------------------
$router->get('/',        [HomeController::class, 'index'], 'home');
$router->get('/anasayfa',[HomeController::class, 'index']);

// --- Dil değiştirme ---------------------------------------------------------
$router->get('/lang/{code}', [LocaleController::class, 'set'], 'locale.set');
$router->get('/dil/{code}', [LocaleController::class, 'set']);

// --- İletişim ---------------------------------------------------------------
$router->get('/iletisim',  [ContactController::class, 'show'],  'contact.show');
$router->post('/iletisim', [ContactController::class, 'submit'], 'contact.submit');

// --- Yorumlar ---------------------------------------------------------------
$router->post('/yorumlar', [CommentController::class, 'store'], 'comments.store');

// --- Arama ------------------------------------------------------------------
$router->get('/ara', [SearchController::class, 'index'], 'search');

// --- Sosyal medya akışı (modül aktifse) --------------------------------------
$router->get('/sosyal', [SocialFeedController::class, 'index'], 'social.index');

// --- Statik / yasal sayfalar ------------------------------------------------
$router->get('/sayfa/{slug}', [PageController::class, 'show'], 'pages.show');

// --- SEO --------------------------------------------------------------------
$router->get('/sitemap.xml', [SitemapController::class, 'sitemap'], 'sitemap');
$router->get('/robots.txt',  [SitemapController::class, 'robots'],  'robots');
$router->get('/favicon.ico', fn () => \Core\Response::redirect(asset('assets/img/favicon.svg'), 301));

// --- Eski /services adresi için kalıcı yönlendirme --------------------------
$router->get('/services', fn () => \Core\Response::redirect(url('/hizmetler'), 301), 'services.alias');

// -----------------------------------------------------------------------------
//  DİNAMİK İÇERİK ROTLARI
// -----------------------------------------------------------------------------
foreach (\Core\Config::get('modules', []) as $slug => $def) {
    $route = $def['route'] ?? null;
    if ($route === null || $route === '' || !empty($def['admin_only'])) {
        continue;
    }

    $router->get('/' . $route,            [CatalogController::class, 'index'], $slug);
    $router->get('/' . $route . '/{slug}', [CatalogController::class, 'show'],  $slug . '.show');
}
