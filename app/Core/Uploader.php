<?php
declare(strict_types=1);

namespace Core;

/**
 * Güvenli dosya yükleme.
 *
 * SAVUNMA KATMANLARI (her biri tek başına yetersiz, birlikte kullanılır):
 *  1. $_FILES hata kodu kontrolü
 *  2. Boyut sınırı (sunucu + uygulama katmanı)
 *  3. UPLOAD_ERR_OK
 *  4. Gerçek MIME tespiti (finfo / getimagesize) — istemci başlığına GÜVENİLMEZ
 *  5. Uzantı beyaz listesi (MIME ile çapraz doğrulanır)
 *  6. Görsellerde yeniden kodlama (polyglot dosya / gömülü PHP temizliği)
 *  7. Rastgele dosya adı — kullanıcı adı ASLA dosya sistemine yansımaz
 *  8. Uploads dizinine .htaccess ile PHP yürütmesi kapatılır
 *  9. Yükleme dizini web kökünün DIŞINDA tutulabilir (yapılandırma)
 * 10. Görsel ise getimagesize() boyut doğrulaması
 */
final class Uploader
{
    private array $errors = [];

    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @param string $destination uploads/services gibi göreli klasör
     * @return string|null göreli yol (örn. "uploads/services/abc.webp") veya null
     */
    public function image(string $field, string $destination, ?Request $request = null): ?string
    {
        return $this->store($field, $destination, 'image', $request);
    }

    public function document(string $field, string $destination, ?Request $request = null): ?string
    {
        return $this->store($field, $destination, 'file', $request);
    }

    private function store(string $field, string $destination, string $kind, ?Request $request): ?string
    {
        $this->errors = [];
        $request ??= Request::current();
        $file = $request->file($field);

        if ($file === null) {
            return null;
        }

        $cfg      = Config::get('security.upload');
        $maxSize  = $kind === 'image' ? (int) $cfg['max_image_size'] : (int) $cfg['max_file_size'];
        $mimes    = $kind === 'image' ? (array) $cfg['image_mimes'] : (array) $cfg['file_mimes'];
        $exts     = $kind === 'image' ? (array) $cfg['image_ext']  : (array) $cfg['file_ext'];
        $tmpPath  = (string) ($file['tmp_name'] ?? '');

        // 1) Yükleme hatası
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            $this->errors[$field] = $this->errorMessage($err, $maxSize);
            return null;
        }

        // 2) Gerçekten yüklenmiş mi? (is_uploaded_file — path traversal'a karşı)
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            $this->errors[$field] = Translator::t('upload.invalid_source');
            Logger::security('Geçersiz yükleme kaynağı', ['field' => $field, 'ip' => $request->ip()]);
            return null;
        }

        // 3) Boyut — hem sunucu hem uygulama tarafı
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            $this->errors[$field] = Translator::t('upload.empty');
            return null;
        }
        if ($size > $maxSize) {
            $this->errors[$field] = Translator::t('upload.too_large', ['max' => Str::humanBytes($maxSize)]);
            return null;
        }
        $iniMax = (int) ini_get('upload_max_filesize');
        if ($iniMax > 0 && $iniMax < $maxSize) {
            $maxSize = $iniMax;
        }

        // 4) Gerçek MIME — istemcinin gönderdiği Content-Type'a ASLA güvenilmez
        $mime = $this->detectMime($tmpPath, $kind);
        if ($mime === null || !in_array($mime, $mimes, true)) {
            $this->errors[$field] = Translator::t('upload.bad_mime');
            Logger::security('Reddedilen MIME', ['field' => $field, 'mime' => $mime, 'ip' => $request->ip()]);
            return null;
        }

        // 5) Görsellerde ek doğrulama + yeniden kodlama
        $ext = $this->extensionFor($mime, $exts);
        if ($ext === null) {
            $this->errors[$field] = Translator::t('upload.bad_mime');
            return null;
        }

        // Veritabanında dönen yol ile fiziksel konum aynı olmalı:
        // public/uploads/<modül>/...
        $storagePath = 'uploads/' . ltrim($destination, '/');
        $dir = $this->ensureDir($storagePath);
        if ($dir === null) {
            $this->errors[$field] = Translator::t('upload.dir_failed');
            Logger::error('Yükleme dizini oluşturulamadı: ' . $destination);
            return null;
        }

        // 6) Rastgele ad — kullanıcı dosya adı hiçbir koşulda kullanılmaz
        $name = bin2hex(random_bytes(16)) . ($kind === 'image' ? '.' . $ext : '.' . $ext);
        $target = $dir . '/' . $name;

        if ($kind === 'image') {
            $ok = $this->recodeImage($tmpPath, $target, $mime, (bool) $cfg['strip_metadata']);
            if (!$ok) {
                $this->errors[$field] = Translator::t('upload.not_an_image');
                Logger::security('Görsel doğrulaması başarısız', ['field' => $field, 'ip' => $request->ip()]);
                return null;
            }
        } else {
            if (!@move_uploaded_file($tmpPath, $target)) {
                $this->errors[$field] = Translator::t('upload.move_failed');
                return null;
            }
            @chmod($target, 0644);
        }

        $this->hardenDirectory(dirname($target));

        return $storagePath . '/' . $name;
    }

    /** Gerçek MIME tespiti. */
    private function detectMime(string $path, string $kind): ?string
    {
        if ($kind === 'image') {
            $info = @getimagesize($path);
            if ($info === false || empty($info['mime'])) {
                return null;
            }
            $mime = (string) $info['mime'];
            // getimagesize imagerotate vb. bazı geçerli görselleri kaçırabilir
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif'], true)) {
                $finfoMime = $this->finfoMime($path);
                return $finfoMime;
            }
            return $mime;
        }
        return $this->finfoMime($path);
    }

    private function finfoMime(string $path): ?string
    {
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = @finfo_file($finfo, $path);
                @finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }
        return null;
    }

    private function extensionFor(string $mime, array $allowed): ?string
    {
        $map = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'image/avif'      => 'avif',
            'image/gif'       => 'gif',
            'application/pdf' => 'pdf',
        ];
        $ext = $map[$mime] ?? null;
        return ($ext !== null && in_array($ext, $allowed, true)) ? $ext : null;
    }

    /**
     * Görseli yeniden kodlar — dosyaya gömülü PHP/shell kodunu
     * (polyglot) ve EXIF verisini ortadan kaldırır.
     *
     * İKİ YOL:
     *  A) GD mevcutsa tam yeniden kodlama — piksel verisi baştan yazılır,
     *     gömülü her şey yok olur. En güçlü yol.
     *  B) GD yoksa YAPISAL KIRPMA — dosya formatının gerçek bitiş
     *     işaretine (JPEG: FFD9 · PNG: IEND · WEBP: RIFF boyut ·
     *     GIF: blok sonu · AVIF: üst düzey kutu sonu) kadar kesilir.
     *     "Görsel + PHP" birleşik dosyaların tamamı bu şekilde etkisiz
     *     hâle gelir. Kalan risk, geçerli bir görselin İÇİNDEki veridir;
     *     o katman CSP `img-src 'self'`, `nosniff` ve uploads/.htaccess
     *     ile kapatılır (bkz. secureDirectory()).
     */
    private function recodeImage(string $source, string $target, string $mime, bool $stripMeta): bool
    {
        $gd = function_exists('imagecreatefromstring') && function_exists('imagejpeg');

        if ($gd) {
            $ok = $this->recodeWithGd($source, $target, $mime);
            if ($ok) {
                return true;
            }
        }

        // GD yoksa veya biçim desteklenmiyorsa yapısal kırpma
        return $this->truncateToRealEnd($source, $target, $mime);
    }

    /** GD ile tam yeniden kodlama. */
    private function recodeWithGd(string $source, string $target, string $mime): bool
    {
        $data = @file_get_contents($source);
        if ($data === false || $data === '' || strlen($data) > 30 * 1024 * 1024) {
            return false;
        }
        $editor = @imagecreatefromstring($data);
        unset($data);

        if ($editor === false) {
            return false;
        }

        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($editor, $target, 84),
            'image/png'  => imagepng($editor, $target, 6),
            'image/webp' => function_exists('imagewebp') ? imagewebp($editor, $target, 82) : false,
            'image/gif'  => imagegif($editor, $target),
            default      => false,
        };

        imagedestroy($editor);

        if (!$ok) {
            return false;
        }
        @chmod($target, 0644);
        return true;
    }

    /**
     * Dosyayı gerçek görsel verisinin bittiği yere kadar keser.
     * Bölge tabanlı (region-based) kırpma: her formatın sonlandırıcı
     * yapısı vardır; o yapıdan sonrası yüklenen veridir.
     */
    private function truncateToRealEnd(string $source, string $target, string $mime): bool
    {
        $data = @file_get_contents($source);
        if ($data === false || $data === '' || strlen($data) > 30 * 1024 * 1024) {
            return false;
        }
        $len = strlen($data);

        $end = match ($mime) {
            'image/png'  => $this->pngEnd($data),
            'image/jpeg' => $this->jpegEnd($data),
            'image/webp' => $this->riffEnd($data),
            'image/gif'  => $this->gifEnd($data),
            'image/avif' => $this->isobmffEnd($data),
            default      => null,
        };

        if ($end === null || $end < 16 || $end > $len) {
            return false;
        }

        if ($end === $len) {
            // Ek yük yok; yine de başlık doğrulaması yap
            if (!$this->magicOk($data, $mime)) {
                return false;
            }
            $ok = @copy($source, $target);
        } else {
            $ok = @file_put_contents($target, substr($data, 0, $end), LOCK_EX);
            unset($data);
        }

        if (!$ok) {
            return false;
        }
        @chmod($target, 0644);
        return true;
    }

    private function magicOk(string $data, string $mime): bool
    {
        return match ($mime) {
            'image/png'  => str_starts_with($data, "\x89PNG\r\n\x1a\n"),
            'image/jpeg' => str_starts_with($data, "\xFF\xD8\xFF"),
            'image/gif'  => str_starts_with($data, 'GIF87a') || str_starts_with($data, 'GIF89a'),
            'image/webp' => str_starts_with($data, 'RIFF') && substr($data, 8, 4) === 'WEBP',
            default      => true,
        };
    }

    /** PNG: IEND parçasının sonu (CRC dahil). */
    private function pngEnd(string $data): ?int
    {
        if (!str_starts_with($data, "\x89PNG\r\n\x1a\n")) {
            return null;
        }
        $pos = strrpos($data, 'IEND');
        if ($pos === false) {
            return null;
        }
        return $pos + 8;   // 'IEND' + 4 bayt CRC
    }

    /** JPEG: son EOI işareti (FF D9). */
    private function jpegEnd(string $data): ?int
    {
        if (!str_starts_with($data, "\xFF\xD8")) {
            return null;
        }
        $pos = strrpos($data, "\xFF\xD9");
        return $pos === false ? null : $pos + 2;
    }

    /** WEBP (RIFF): başlıktaki boyut alanı dosya boyutunu verir. */
    private function riffEnd(string $data): ?int
    {
        if (strlen($data) < 12 || !str_starts_with($data, 'RIFF') || substr($data, 8, 4) !== 'WEBP') {
            return null;
        }
        $size = unpack('V', substr($data, 4, 4));
        if (!is_array($size)) {
            return null;
        }
        $declared = 8 + (int) $size[1];
        return ($declared > 0 && $declared <= strlen($data)) ? $declared : null;
    }

    /** GIF: blok zinciri gezilerek gerçek trailer (0x3B) bulunur. */
    private function gifEnd(string $data): ?int
    {
        $len = strlen($data);
        if ($len < 13 || (!str_starts_with($data, 'GIF87a') && !str_starts_with($data, 'GIF89a'))) {
            return null;
        }

        $p = 6;
        if ($len < $p + 7) {
            return null;
        }
        $packed = ord($data[$p + 4]);
        $p += 7;                                   // LSD

        // Global renk tablosu
        if (($packed & 0x80) !== 0) {
            $p += 3 * (2 << (($packed & 0x07)));
        }

        while ($p < $len) {
            $b = ord($data[$p]);

            if ($b === 0x3B) {                      // trailer
                return $p + 1;
            }
            if ($b === 0x21) {                      // extension
                $p += 2;
                while ($p < $len) {
                    $size = ord($data[$p]);
                    $p  += 1 + $size;
                    if ($size === 0) {
                        break;
                    }
                }
                continue;
            }
            if ($b === 0x2C) {                      // image descriptor
                $p += 10;
                if ($p > $len) {
                    return null;
                }
                $lpacked = ord($data[$p - 1]);
                if (($lpacked & 0x80) !== 0) {
                    $p += 3 * (2 << ($lpacked & 0x07));
                }
                // LZW minimum kod boyutu baytı — değeri 2..8 arasında olabilir,
                // 0x3C DEĞİL. Her zaman tüketilir.
                $p++;
                // LZW veri alt blokları: [boyut][veri] … [0]
                while ($p < $len) {
                    $size = ord($data[$p]);
                    $p  += 1 + $size;
                    if ($size === 0) {
                        break;
                    }
                }
                continue;
            }
            return null;                            // tanınmayan yapı
        }
        return null;
    }

    /** AVIF/HEIF (ISO-BMFF): üst düzey kutuların sonu. */
    private function isobmffEnd(string $data): ?int
    {
        $len = strlen($data);
        $p   = 0;
        $end = 0;

        while ($p + 8 <= $len) {
            $size = unpack('N', substr($data, $p, 4));
            if (!is_array($size)) {
                return null;
            }
            $boxSize = (int) $size[1];
            $type    = substr($data, $p + 4, 4);

            if ($boxSize === 1) {                   // 64-bit boyut
                if ($p + 16 > $len) {
                    return null;
                }
                $big = unpack('J', substr($data, $p + 8, 8));
                if (!is_array($big)) {
                    return null;
                }
                $boxSize = (int) $big[1];
                $header  = 16;
            } elseif ($boxSize === 0) {
                return $len;                        // "kutu sona kadar"
            } else {
                $header = 8;
            }

            if ($boxSize < $header || $p + $boxSize > $len) {
                return null;
            }
            $p += $boxSize;
            $end = $p;

            if (in_array($type, ['mdat'], true)) {
                break;                              // medya verisi bitti
            }
        }

        return $end > 0 ? $end : null;
    }

    /** Web kökü altında yükleme klasörü oluşturur. */
    private function ensureDir(string $relative): ?string
    {
        $base = Application::publicPath();
        $rel  = trim(str_replace('..', '', $relative), '/');
        $dir  = $base . '/' . $rel;

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }
        return $dir;
    }

    /**
     * Yükleme dizinini sertleştirir: PHP yürütmesini kapatır, dizin
     * listelemeyi engeller. Daha önce kurulmuşsa tekrar yazılmaz.
     */
    private function hardenDirectory(string $dir): void
    {
        $htaccess = $dir . '/.htaccess';
        if (is_file($htaccess)) {
            return;
        }
        $rules = "# Yükleme dizini — görseller erişilebilir, çalıştırılabilir dosyalar engelli\n"
            . "<IfModule mod_authz_core.c>\n    Require all granted\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Allow from all\n</IfModule>\n"
            . "<IfModule mod_php.c>\n    php_flag engine off\n</IfModule>\n"
            . "<IfModule mod_php7.c>\n    php_flag engine off\n</IfModule>\n"
            . "<IfModule mod_php8.c>\n    php_flag engine off\n</IfModule>\n"
            . "Options -ExecCGI -Indexes\n"
            . "AddType text/plain .php .php3 .php4 .php5 .php7 .php8 .phtml .pht .phar .pl .py .cgi .sh .htm .html .shtml\n"
            . "<FilesMatch \"\\.(?i:php|phtml|phar|php[0-9]|cgi|pl|py|sh)$\">\n"
            . "    <IfModule mod_authz_core.c>\n        Require all denied\n    </IfModule>\n"
            . "    <IfModule !mod_authz_core.c>\n        Order allow,deny\n        Deny from all\n    </IfModule>\n"
            . "</FilesMatch>\n";

        @file_put_contents($htaccess, $rules);
    }

    /** Kaydı güvenle siler. */
    public static function delete(?string $relativePath): bool
    {
        if ($relativePath === null || $relativePath === '') {
            return false;
        }
        // yalnızca uploads/ altındaki yollar kabul edilir
        if (!preg_match('#^uploads/[A-Za-z0-9_\-/]+\.[A-Za-z0-9]+$#', $relativePath)) {
            Logger::security('Şüpheli silme yolu engellendi', ['path' => $relativePath]);
            return false;
        }
        $full = Application::publicPath() . '/' . $relativePath;
        $real = realpath($full);
        $root = realpath(Application::publicPath() . '/uploads');
        if ($real === false || $root === false || !str_starts_with($real, $root)) {
            return false;
        }
        return @unlink($real);
    }

    /** Yükleme alanı adı — formda tekrar kullanmak için. */
    public function old(string $field): ?string
    {
        $v = Session::get('_old.' . $field);
        return is_string($v) ? $v : null;
    }

    private function errorMessage(int $code, int $maxSize): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                Translator::t('upload.too_large', ['max' => Str::humanBytes($maxSize)]),
            UPLOAD_ERR_PARTIAL   => Translator::t('upload.partial'),
            UPLOAD_ERR_NO_FILE   => Translator::t('upload.empty'),
            UPLOAD_ERR_NO_TMPDIR => Translator::t('upload.no_tmp'),
            UPLOAD_ERR_CANT_WRITE => Translator::t('upload.write_failed'),
            default              => Translator::t('upload.move_failed'),
        };
    }
}
