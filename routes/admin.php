<?php
declare(strict_types=1);

/**
 * ADMIN ROTALARI
 *
 * Tümü `auth` ara katmanıyla korunur; yazma işlemleri `csrf` doğrulamasına tabidir.
 * İçerik CRUD rotaları modül kataloğundan dinamik üretilir; böylece yeni bir
 * modül eklemek için route dosyasına dokunmak gerekmez.
 */

use Core\Router;
use Controllers\Admin\{AuthController, DashboardController, MessageController, CommentController,
                       ModuleController, SettingController, UserController, ContentController};

/** @var Router $router */

// --- Oturum (auth grubunun DIŞINDA — giriş sayfası korumasız olmalı) -------
$router->get('/admin/giris',  [AuthController::class, 'showLogin'], 'admin.login');
$router->post('/admin/giris', [AuthController::class, 'login'],     'admin.login.post');
$router->post('/admin/cikis', [AuthController::class, 'logout'],    'admin.logout');
$router->get('/admin/cikis',  [AuthController::class, 'logout'],    'admin.logout.get');

$router->group(['prefix' => 'admin', 'middleware' => ['auth']], function (Router $router): void {

    // --- Panel --------------------------------------------------------------
    $router->get('/', [DashboardController::class, 'index'], 'admin.dashboard');

    // --- Gelen kutusu -------------------------------------------------------
    $router->get('/mesajlar',                 [MessageController::class, 'index'],   'admin.messages.index');
    $router->get('/mesajlar/{id}',            [MessageController::class, 'show'],    'admin.messages.show');
    $router->post('/mesajlar/islem/{id}',     [MessageController::class, 'action'],  'admin.messages.action');
    $router->post('/mesajlar/tumunu-okundu',  [MessageController::class, 'readAll'], 'admin.messages.readAll');

    // --- Yorum onay kuyruğu -------------------------------------------------
    $router->get('/yorumlar',              [CommentController::class, 'index'],  'admin.comments.index');
    $router->post('/yorumlar/islem/{id}',  [CommentController::class, 'action'], 'admin.comments.action');

    // --- Modül yönetimi -----------------------------------------------------
    $router->get('/moduller',             [ModuleController::class, 'index'],  'admin.modules.index');
    $router->post('/moduller/islem',      [ModuleController::class, 'action'], 'admin.modules.action');

    // --- Ayarlar ------------------------------------------------------------
    $router->get('/ayarlar',          [SettingController::class, 'index'],  'admin.settings.index');
    $router->post('/ayarlar',         [SettingController::class, 'save'],   'admin.settings.save');
    $router->post('/ayarlar/sil',     [SettingController::class, 'delete'], 'admin.settings.delete');

    // --- Hesap --------------------------------------------------------------
    $router->get('/hesap',            [UserController::class, 'edit'],   'admin.user.edit');
    $router->post('/hesap',           [UserController::class, 'update'], 'admin.user.update');
    $router->post('/hesap/parola',    [UserController::class, 'password'],'admin.user.password');

    // --- Hızlı arama --------------------------------------------------------
    $router->get('/ara', [DashboardController::class, 'search'], 'admin.search');

    // -----------------------------------------------------------------------------
    //  DİNAMİK İÇERİK CRUD
    // -----------------------------------------------------------------------------
    foreach (\Core\Config::get('modules', []) as $slug => $def) {
        // Yönetimi özel controller'da olan modüller (mesajlar, yorumlar, arama)
        if (in_array($slug, ['messages', 'comments', 'search'], true)) {
            continue;
        }

        $router->get('/' . $slug,                        [ContentController::class, 'index'],  "admin.{$slug}.index");
        $router->get('/' . $slug . '/ekle',              [ContentController::class, 'create'], "admin.{$slug}.create");
        $router->post('/' . $slug . '/ekle',             [ContentController::class, 'store'],  "admin.{$slug}.store");
        $router->get('/' . $slug . '/duzenle/{id}',      [ContentController::class, 'edit'],   "admin.{$slug}.edit");
        $router->post('/' . $slug . '/duzenle/{id}',     [ContentController::class, 'update'], "admin.{$slug}.update");
        $router->post('/' . $slug . '/islem/{id}',       [ContentController::class, 'action'], "admin.{$slug}.action");
    }

});
