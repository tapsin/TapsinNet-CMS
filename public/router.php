<?php
/**
 * PHP yerleşik sunucusu için yönlendirici (GELİŞTİRME ARAÇI).
 *
 * Kullanım:
 *   php -S 127.0.0.1:8088 -t public public/router.php
 *
 * `public/index.php` üretimde Apache/nginx tarafından çalıştırılır ve
 * statik dosya mantığı .htaccess'te yaşar. Yerleşik sunucuda .htaccess
 * çalışmadığı için burada dosyalar doğrudan sunulur.
 *
 * Bu dosya yalnızca geliştirme içindir; web köküne yüklemeye gerek yoktur.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$file = __DIR__ . urldecode($path);

// --- Gizli ve hassas dosyalar hiçbir koşulda sunulmaz -----------------------
// Apache bu dosyaları zaten engeller (FilesMatch); yerleşik sunucuda
// .htaccess'i kendimiz reddetmeliyiz.
if (preg_match('#(^|/)\.#', $path) || preg_match('#(^|/)(composer\.(json|lock)|package(-lock)?\.json)$#', $path)) {
    http_response_code(404);
    exit;
}

if (preg_match('#\.(sqlite|sqlite-wal|sqlite-shm|db|log|sql|ini|lock|bak|orig|dist|sh|inc|yml|yaml|md)$#i', $path)) {
    http_response_code(404);
    exit;
}

// uploads/ altında PHP çalıştırmayı reddet
if (str_starts_with($path, '/uploads/') && preg_match('/\.(php|phtml|phar|cgi|pl|py|sh)$/i', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

// Var olan gerçek dosya → sunucuya bırak (return false = statik olarak sun)
if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
