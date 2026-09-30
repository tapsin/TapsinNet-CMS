<?php
declare(strict_types=1);

/**
 * Uygulama önyükleyici.
 *
 *  · PSR-4 benzeri basit sınıf yükleyici (Composer gerekmez)
 *  · .env yükleme
 *  · config/*.php yükleme
 *  · hata işleyicileri
 *  · global yardımcı fonksiyonlar
 */

define('DSH_TAPSINNET_START', microtime(true));

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('TapsinNet PHP 8.1 veya üzeri gerektirir. Mevcut sürüm: ' . PHP_VERSION);
}

$basePath = dirname(__DIR__);

// --- PSR-4 benzeri otomatik yükleyici ---------------------------------------
spl_autoload_register(static function (string $class) use ($basePath): void {
    static $prefixes = [
        'Core\\'       => '/app/Core/',
        'Models\\'     => '/app/Models/',
        'Controllers\\'=> '/app/Controllers/',
    ];

    foreach ($prefixes as $prefix => $dir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $relative = substr($class, strlen($prefix));
        $file = $basePath . $dir . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

// --- Dahil dosyalar --------------------------------------------------------
require $basePath . '/app/Support/helpers.php';

// --- Uygulamayı başlat ------------------------------------------------------
\Core\Application::boot($basePath, PHP_SAPI === 'cli');

if (PHP_SAPI !== 'cli') {
    \Core\Application::setExceptionHandler();
}
