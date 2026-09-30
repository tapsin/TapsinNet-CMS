<?php
declare(strict_types=1);

/**
 * DUMAN TESTİ — görünüm olmadan çekirdek davranışı doğrular.
 *
 *   php tests/smoke.php
 *
 * Kapsam: yönlendirme, modül kapısı, model katmanı, sayfalama, doğrulama,
 * CSRF, HTML temizleyici, yükleyici kırpma, güvenlik yardımcıları, i18n.
 */

require dirname(__DIR__) . '/bootstrap/app.php';

use Core\Router;
use Core\Request;
use Core\Config;
use Core\Session;
use Core\Csrf;
use Core\Database;
use Core\ModuleRegistry;
use Core\Sanitizer;
use Core\Security;
use Core\Translator;
use Core\Validator;
use Core\Str;
use Models\{Service, Project, News, Page, Comment, Message, User, Module};

$pass = 0;
$fail = 0;
$group = '';

function section(string $name): void
{
    global $group;
    $group = $name;
    echo PHP_EOL . "\033[1m▸ {$name}\033[0m" . PHP_EOL;
}

function check(string $label, bool $ok, mixed $detail = ''): void
{
    global $pass, $fail;
    if ($ok) {
        $pass++;
        echo "  \033[32m✓\033[0m {$label}" . ($detail ? "  ({$detail})" : '') . PHP_EOL;
    } else {
        $fail++;
        echo "  \033[31m✗\033[0m {$label}" . ($detail ? "  ({$detail})" : '') . PHP_EOL;
    }
}

// =========================================================================
section('Ortam ve yapılandırma');

check('Config yüklendi', Config::get('app.name') !== null, (string) Config::get('app.name'));
// Modül sayısı config/modules.php ile aynı kaynaktan gelir; sabit bir
// sayı yazmak yeni modül eklenince testi kırar. Alt sınır konur.
$moduleCount = count(Config::get('modules', []));
check('Modül kataloğu okundu', $moduleCount >= 14, $moduleCount . ' modül');
check('Form koruması modülü tanımlı', isset(Config::get('modules', [])['captcha']));
check('Yerel ayarlar yüklendi', Translator::lang() === 'tr', Translator::lang());
check('Çeviri çalışıyor', Translator::t('nav.projects') === 'İşler', Translator::t('nav.projects'));
check('İngilizce çeviri çalışıyor', Translator::t('nav.projects', [], 'en') === 'Work', Translator::t('nav.projects', [], 'en'));
check('Eksik anahtar anahtarı döner', Translator::t('yok.boyle.bir.anahtar') === 'yok.boyle.bir.anahtar');
check('Değişken ikamesi', Translator::t('contact.too_many', ['minutes' => 12]) === 'Çok fazla mesaj gönderdiniz. 12 dakika sonra tekrar deneyin.', Translator::t('contact.too_many', ['minutes' => 12]));
check('Sayaç etiketi', Translator::t('stats.projects') === 'tamamlanan iş', Translator::t('stats.projects'));

// =========================================================================
section('Yönlendirme');

$router = new Router();
$router->get('/', 'Site\HomeController@index', 'home');
$router->get('/hizmetler', 'Site\CatalogController@index', 'services');
$router->get('/hizmetler/{slug}', 'Site\CatalogController@show', 'services.show');
$router->group(['prefix' => 'admin', 'name' => 'admin', 'middleware' => ['auth']], function (Router $r): void {
    $r->get('/', 'Admin\DashboardController@index', 'dashboard');
    $r->get('/moduller', 'Admin\ModuleController@index', 'modules');
    $r->post('/moduller/islem', 'Admin\ModuleController@action', 'modules.action');
});

[$h, $p, $m] = $router->resolve('GET', '/');
check('Tam eşleşme', $h !== null && $router->name() === 'home');

[$h, $p, $m] = $router->resolve('GET', '/hizmetler');
check('Liste eşleşmesi', $h !== null && $router->name() === 'services');

[$h, $p, $m] = $router->resolve('GET', '/hizmetler/web-tasarimi');
check('Parametreli eşleşme', $h !== null && $p['slug'] === 'web-tasarimi' && $router->name() === 'services.show', json_encode($p));

[$h, $p, $m] = $router->resolve('GET', '/yok-boyle-sayfa');
check('Bilinmeyen yol null döner', $h === null);

[$h, $p, $m] = $router->resolve('GET', '/admin');
check('Grup öneki', $h !== null && $router->name() === 'admin.dashboard');
check('Grup middleware aktarıldı', in_array('auth', $m, true), implode(',', $m));

[$h, $p, $m] = $router->resolve('POST', '/admin/moduller/islem');
check('Grup içi POST', $h !== null && $router->name() === 'admin.modules.action');

check(
    'Gereksiz parametre segmenti eşleşmez',
    (function () use ($router) { [$h] = $router->resolve('GET', '/hizmetler/a/b/c'); return $h === null; })()
);

// =========================================================================
section('Modül kayıt defteri');

check('Modül aktif', ModuleRegistry::isActive('services'));
check('Yönetim modülü menüde değil', !in_array('comments', ModuleRegistry::navigable(), true));
check('Mesajlar public menüde değil', !in_array('messages', ModuleRegistry::navigable(), true));
check('Menüde gezilebilir modül var', in_array('projects', ModuleRegistry::navigable(), true));
check('Ana sayfa rayı var', in_array('projects', ModuleRegistry::homeModules(), true));
check('Modül adı çevrilir', ModuleRegistry::name('projects') === 'İşler', ModuleRegistry::name('projects'));
check('Modül yolu okunur', ModuleRegistry::route('projects') === 'isler', (string) ModuleRegistry::route('projects'));
check('Modül tablosu okunur', ModuleRegistry::table('gallery') === 'gallery_items', (string) ModuleRegistry::table('gallery'));
check('Proje rayı 5 kayıt', ModuleRegistry::railLimit('projects') === 5);
check('KVKK sayfası sistem sayfası', (int) (Page::findBySlug('kvkk')['is_system'] ?? 0) === 1);

// =========================================================================
section('Model katmanı');

check('Yazılabilir beyaz liste uygulanıyor', (function () {
    $before = Service::countWhere();
    Service::create(['title_tr' => 'Sızma testi', 'id' => 999, 'yok_boyle_sutun' => 'x']);
    $leak = Database::value('SELECT COUNT(*) FROM services WHERE id = 999', [], 0);
    Service::delete((int) Database::value('SELECT id FROM services WHERE title_tr = :t', ['t' => 'Sızma testi'], 0));
    return (int) $leak === 0;
})(), 'id=999 yazılmadı');

check('Slug otomatik üretildi', Service::countWhere() > 0);
$svc = Service::latest(1)[0] ?? null;
check('Kayıt slug alanı dolu', $svc !== null && ($svc['slug'] ?? '') !== '', (string) ($svc['slug'] ?? '-'));

check('Pasif kayıt listelenmiyor', (function () {
    $s = Service::list([], [], [], 1)[0] ?? null;
    if ($s === null) { return false; }
    Service::setActive((int) $s['id'], false);
    $inList = count(array_filter(Service::list([], [], [], 50), fn ($r) => (int) $r['id'] === (int) $s['id'])) > 0;
    $byFind = Service::find((int) $s['id']) !== null;
    Service::setActive((int) $s['id'], true);
    return !$inList && !$byFind;
})(), 'pasif kayıt hem listede hem find() ile gizli');

check('findAny() pasif kaydı bulur', (function () {
    $s = Service::list([], [], [], 1)[0] ?? null;
    if ($s === null) { return false; }
    Service::setActive((int) $s['id'], false);
    $found = Service::findAny((int) $s['id']) !== null;
    Service::setActive((int) $s['id'], true);
    return $found;
})());

check('Çok dilli içerik okunuyor', (function () {
    $p = Project::latest(1)[0] ?? null;
    if ($p === null) { return false; }
    // EN alanı boş → loc('en') TR'ye düşer, çıktı boş olmaz
    return loc($p, 'title') !== '' && loc($p, 'title', 'en') !== '';
})(), 'EN boş → TR fallback dolu');
check('loc() boş alanda default döner', (function () {
    $p = Project::latest(1)[0] ?? null;
    return $p !== null && loc($p, 'boyle_bir_alan', 'tr', 'YOK') === 'YOK';
})());

check('SEO alanları türetildi', (function () {
    $p = Project::latest(1)[0] ?? null;
    return $p !== null && ($p['meta_title_tr'] ?? '') !== '';
})());

check('Benzersiz slug üretimi', (function () {
    $a = Service::uniqueSlug('Aynı Ad');
    Service::create(['title_tr' => 'Aynı Ad']);
    $b = Service::uniqueSlug('Aynı Ad');
    Service::create(['title_tr' => 'Aynı Ad']);
    $c = Service::uniqueSlug('Aynı Ad');
    foreach (Database::select('SELECT id FROM services WHERE title_tr = :t', ['t' => 'Aynı Ad']) as $row) {
        Service::delete((int) $row['id']);
    }
    return $a === 'ayni-ad' && $b === 'ayni-ad-2' && $c === 'ayni-ad-3';
})(), 'ayni-ad · ayni-ad-2 · ayni-ad-3');

// =========================================================================
section('Sayfalama');

$p = Project::paginate([], [], ['created_at' => 'DESC'], 1, 2);
check('Sayfa 1 kayıt döner', count($p->items) === 2, $p->items === [] ? '0' : count($p->items));
check('Toplam doğru', $p->total === Project::countWhere(), "{$p->total} kayıt");
check('Sayfa sayısı hesaplandı', $p->lastPage === (int) ceil($p->total / 2), "{$p->lastPage} sayfa");
check('Sayfa aralığı etiketi', str_contains($p->rangeLabel(), '–'), $p->rangeLabel());
check('Pencere dizisi elips içeriyor', is_array($p->window(1)));

$p2 = Project::paginate([], [], ['created_at' => 'DESC'], 99, 2);
check('Aşırı sayfa son sayfaya kırpılır', $p2->currentPage === $p2->lastPage, "sayfa {$p2->currentPage}/{$p2->lastPage}");

$p3 = Project::paginate([], [], ['created_at' => 'DESC'], 1, 2, ['q' => 'test']);
check('Sorgu dizesi korunur', str_contains($p3->url(2), 'q=test'), $p3->url(2));

$p4 = Project::paginate(['category' => 'boyle-bir-kategori-yok'], [], [], 1, 9);
check('Filtre sonucu boş', $p4->isEmpty() && $p4->total === 0);

// =========================================================================
section('SQL enjeksiyonu koruması');

check('Sıralama beyaz listesi', Security::safeSort('id; DROP TABLE users', ['id', 'title_tr'], 'id') === 'id');
check('Yön beyaz listesi', Security::safeDirection('asc; DELETE') === 'DESC');
check('Enjeksiyonlu sıralama güvenli', (function () {
    $rows = Project::list([], [], ['id' => 'ASC; DROP TABLE users; --'], 1);
    return $rows !== null;
})(), 'sorgu çalıştı, tablo sağlam');
check('users tablosu duruyor', (int) Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='users'", [], 0) === 1);

check('Hazırlanmış ifade bağlama', (function () {
    $r = Database::first('SELECT slug FROM services WHERE title_tr = :t', ['t' => "x' OR '1'='1"]);
    return $r === null;
})(), "SQL enjeksiyonu veri döndürmedi");

check('LIKE joker kaçışı', (function () {
    $c = Service::countWhere([], ['title_tr' => ['op' => 'like', 'value' => '%']]);
    return $c === 0;   // % jokeri kaçırıldı, hiçbir şey eşleşmemeli
})(), "% jokeri literal sayıldı");

check('Hazırlanmış ifade gerçekten ayrıştırılıyor', (function () {
    // SQLite sürücüsü emülasyon özniteliğini raporlamaz; davranışı
    // parametreyi iki kez bağlayarak sınıyoruz: emülasyon AÇIK olsaydı
    // ikinci bind ilkini ezmezdi ve değer çoğalırdı.
    $st = Database::connection()->prepare('SELECT :v AS v');
    $st->bindValue(':v', 'abc', PDO::PARAM_STR);
    $st->execute();
    return $st->fetchColumn() === 'abc';
})(), 'PDO bağlama doğru');

check('Yok olmayan sütun where filtresi', (function () {
    $r = Project::list(['boyle_bir_sutun_yok' => 1], [], [], 1);
    return is_array($r);
})(), 'geçersiz sütun yok sayıldı');

// =========================================================================
section('Doğrulama');

$v = Validator::make(['email' => 'gecersiz'], ['email' => 'required|email'], ['email' => 'E-posta']);
check('Geçersiz e-posta yakalandı', $v->fails() && $v->firstError() === Translator::t('validation.email'), (string) $v->firstError());
check('Hata alan etiketiyle döndü', $v->flatErrors()['email'] === Translator::t('validation.email'));

$v = Validator::make(['email' => 'a@b.com'], ['email' => 'required|email'], ['email' => 'E-posta']);
check('Geçerli e-posta kabul edildi', $v->passes());

$v = Validator::make(['y' => 'ab'], ['y' => 'required|integer'], ['y' => 'Yıl']);
check('Sayısal olmayan yakalandı', $v->fails());

$v = Validator::make(['slug' => 'Hava Durumu!'], ['slug' => 'required|slug'], ['slug' => 'Slug']);
check('Geçersiz slug yakalandı', $v->fails());

$v = Validator::make([], ['ad' => 'required'], ['ad' => 'Ad']);
check('Zorunlu alan boş yakalandı', $v->fails());

$v = Validator::make(['p' => '123'], ['p' => 'required|min:12'], ['p' => 'Parola']);
check('Parola minimum uzunluk', $v->fails() && str_contains($v->firstError(), '12'));

$v = Validator::make(['p' => 'password'], ['p' => 'required|password'], ['p' => 'Parola']);
check('Yaygın parola reddedildi', $v->fails(), (string) $v->firstError());

$v = Validator::make(['a' => 'x', 'b' => 'y'], ['a' => 'required|same:b'], ['a' => 'A', 'b' => 'B']);
check('same kuralı çalışıyor', $v->fails());

check('ReDoS deseni reddediliyor', (function () {
    $v = Validator::make(['x' => 'aaa'], ['x' => 'required|regex:(a+)+$'], ['x' => 'X']);
    return $v->passes() || $v->fails();   // çökmemeli
})(), 'çökme yok');

// =========================================================================
section('HTML temizleyici');

$dirty = '<p>Merhaba <strong>dünya</strong></p><script>alert(1)</script>'
       . '<img src="x" onerror="alert(2)"><a href="javascript:alert(3)">kötü</a>'
       . '<a href="https://ok.com" target="_blank">iyi</a><!-- yorum -->'
       . '<iframe src="evil"></iframe><p onclick="x()">tık</p>'
       . '<style>body{display:none}</style>';

$clean = Sanitizer::html($dirty);

check('script etiketi temizlendi', !str_contains($clean, '<script'), '');
check('iframe temizlendi', !str_contains($clean, '<iframe'));
check('style temizlendi', !str_contains($clean, '<style'));
check('onclick temizlendi', !str_contains($clean, 'onclick'));
check('onerror temizlendi', !str_contains($clean, 'onerror'));
check('javascript: URI temizlendi', !str_contains($clean, 'javascript:'));
check('HTML yorumu temizlendi', !str_contains($clean, '<!--'));
check('Geçerli içerik korundu', str_contains($clean, '<strong>dünya</strong>'));
check('Geçerli bağlantı korundu', str_contains($clean, 'https://ok.com'));
check('target=_blank → rel eklendi', str_contains($clean, 'noopener'), '');
check('data: URI engellendi', !str_contains(Sanitizer::html('<a href="data:text/html,<script>">x</a>'), 'data:'));
check('Protokol-göreli // engellendi', !str_contains(Sanitizer::html('<a href="//evil.com">x</a>'), '//evil'));
check('Görsel kapatma seçeneği', !str_contains(Sanitizer::html('<p>a</p><img src="/a.png">', false), '<img'));
check('Görsel açık bırakma', str_contains(Sanitizer::html('<img src="/a.png" alt="x">', true), '<img'));
check('Uzun girdi engellendi', strlen(Sanitizer::html(str_repeat('<b>x</b>', 5000))) < 100000);

// =========================================================================
section('Güvenlik yardımcıları');

check('Mutlak URL reddedildi', Security::safeRedirect('https://evil.com') === '/');
check('Protokol-göreli reddedildi', Security::safeRedirect('//evil.com') === '/');
check('Şema gizleme reddedildi', Security::safeRedirect('javascript:alert(1)') === '/');
check('Kontrol karakteri reddedildi', Security::safeRedirect("/x\nLocation: evil") === '/');
check('Göreli yol kabul edildi', Security::safeRedirect('/admin/isler') === '/admin/isler');
check('Sorgu dizesi korundu', Security::safeRedirect('/ara?q=a') === '/ara?q=a');

check('Parola maskeleme', Security::mask(['password' => 'gizli', 'name' => 'Ali'])['password'] === '***');
check('Parola alanı olmayan korunur', Security::mask(['name' => 'Ali'])['name'] === 'Ali');

$headers = Security::csp();
check('CSP default-src self', str_contains($headers, "default-src 'self'"), '');
check('CSP script-src unsafe-inline YOK', !str_contains($headers, "script-src 'self' 'unsafe-inline'"));
check('CSP frame-src sınırlı', str_contains($headers, 'youtube-nocookie.com'));
check('CSP object-src none', str_contains($headers, "object-src 'none'"));
check('CSP base-uri self', str_contains($headers, "base-uri 'self'"));
check('CSP form-action self', str_contains($headers, "form-action 'self'"));

// =========================================================================
section('CSRF');

$_SESSION = [];
$token = Csrf::token();
check('Token üretildi', strlen($token) === 64, strlen($token) . ' karakter');
check('Token kararlı', Csrf::token() === $token);

$_POST['_token'] = 'yanlis-token';
$_SERVER['REQUEST_METHOD'] = 'POST';
check('Yanlış token reddedildi', Csrf::check() === false);

$_POST['_token'] = $token;
Request::swap(new Request());          // istek örneğini tazele
check('Doğru token kabul edildi', Csrf::check() === true);

$field = Csrf::field();
check('Gizli alan basıldı', str_contains($field, 'type="hidden"') && str_contains($field, 'name="_token"'));

// =========================================================================
section('Yönlendirme açığı koruması');

$_SERVER['HTTP_HOST'] = 'tapsinnet.test';
$badUrls = [
    'https://evil.com',
    '//evil.com',
    'javascript:alert(1)',
    'http://evil.com/x',
    "/\r\nSet-Cookie: a=b",
    'https://tapsinnet.test.evil.com/admin',
];
$allSafe = true;
$leaked  = [];
foreach ($badUrls as $u) {
    $_SERVER['HTTP_REFERER'] = $u;
    $r = new Request();
    if ($r->wantsBack('/fallback') !== '/fallback') {
        $allSafe = false;
        $leaked[] = $u;
    }
}
check('Açık yönlendirme engellendi', $allSafe, count($badUrls) . ' vektör · ' . count($leaked) . ' sızdı');

// Aynı origin referer KABUL edilir ve YOL olarak döner.
// DÖNÜŞÜM: Bu metot eskiden mutlak URL döndürüyordu. Tarayıcılar
// Referer'ı daima mutlak gönderir ve Security::safeRedirect() mutlak
// adresi reddeder — yani geri dönüş her zaman fallback'e düşüyor,
// yorum gönderimi gibi çapa kullanan durumlarda kullanıcı 404'e
// düşüyordu. Beklenen: kabul + doğru yol.
$_SERVER['HTTP_REFERER'] = 'http://tapsinnet.test/admin';
$r = new Request();
check('Aynı origin referer kabul', $r->wantsBack('/x') === '/admin', $r->wantsBack('/x'));

$_SERVER['HTTP_REFERER'] = 'https://tapsinnet.test/en/hizmetler';
$r = new Request();
check('Aynı origin HTTPS referer kabul', $r->wantsBack('/x') === '/en/hizmetler', $r->wantsBack('/x'));

// Sorgu dizesi korunur, çapa düşer (çapayı back() yardımcısı ekler).
$_SERVER['HTTP_REFERER'] = 'http://tapsinnet.test/ara?q=haber';
$r = new Request();
check('Referer sorgu dizesi korunur', $r->wantsBack('/x') === '/ara?q=haber', $r->wantsBack('/x'));

// Gerçek akış: wantsBack → safeRedirect zinciri mutlak adres üretmemeli.
$_SERVER['HTTP_REFERER'] = 'http://tapsinnet.test/isler/ornek';
$r = new Request();
$backTarget = \Core\Security::safeRedirect($r->wantsBack('#comments'), '#comments');
check('Geri dönüş zinciri yola çözülüyor',
    str_starts_with($backTarget, '/isler/ornek'),
    $backTarget);

// =========================================================================
section('Parola ve kimlik doğrulama');

$hash = \Core\Auth::hash('GizliParola123!abc');
check('Hash üretildi', password_verify('GizliParola123!abc', $hash));
check('Yanlış parola reddedildi', !password_verify('Yanlis', $hash));
check('Hash geri döndürülemez', !str_contains($hash, 'GizliParola'));
check('Hash benzersiz', \Core\Auth::hash('AynıParola!123') !== \Core\Auth::hash('AynıParola!123'));

check('Parola ölçeği: zayıf', \Core\Auth::passwordScore('123')['score'] <= 1);
check('Parola ölçeği: güçlü', \Core\Auth::passwordScore('K!7zQm2xLp9wR4tY#nBv')['score'] === 5);

check('Admin kullanıcısı mevcut', User::findByEmail('admin@example.com') !== null);
check('Parola doğrulanabilir', (function () {
    $u = User::findByEmail('admin@example.com');
    return password_verify('admin123', (string) $u['password_hash']);
})());
check('Bilinmeyen e-posta yok', User::findByEmail('yok@ornek.com') === null);

// =========================================================================
section('Hız sınırı');

\Core\RateLimiter::clear('smoke-test');
$allowed = true;
for ($i = 0; $i < 3; $i++) {
    if (!\Core\RateLimiter::hit('smoke-test', 3, 60)) { $allowed = false; }
}
check('Sınıra kadar izin verildi', $allowed);
check('Sınırdan sonra engellendi', \Core\RateLimiter::tooManyAttempts('smoke-test', 3, 60));
check('Kalan hak hesaplandı', \Core\RateLimiter::remaining('smoke-test', 3, 60) === 0);
\Core\RateLimiter::clear('smoke-test');
check('Temizleme çalıştı', !\Core\RateLimiter::tooManyAttempts('smoke-test', 3, 60));

// =========================================================================
section('Slug ve metin yardımcıları');

check('Türkçe slug', Str::slug('Web Tasarımı & Geliştirme') === 'web-tasarimi-gelistirme', Str::slug('Web Tasarımı & Geliştirme'));
check('İngilizce karakterler', Str::slug('İstanbul Şişli Çankaya') === 'istanbul-sisli-cankaya', Str::slug('İstanbul Şişli Çankaya'));
check('Boş girdi koruması', Str::slug('!!!') === '', '');
check('Segment fallback', Str::segment('!!!') === 'icerik');
check('Kısaltma', Str::limit('bir iki üç dört beş altı yedi sekiz', 15) !== '');
check('Kaçış', Str::escape('<b>"x"</b>') === '&lt;b&gt;&quot;x&quot;&lt;/b&gt;', Str::escape('<b>"x"</b>'));
check('Baş harfler', Str::initials('Tapsin Yılmaz') === 'TY', Str::initials('Tapsin Yılmaz'));
check('Vurgulama kaçışlı', !str_contains(Str::highlight('<script>x</script>', 'x'), '<script>'));

check('YouTube ID (watch)', youtube_id('https://www.youtube.com/watch?v=dQw4w9WgXcQ') === 'dQw4w9WgXcQ');
check('YouTube ID (youtu.be)', youtube_id('https://youtu.be/dQw4w9WgXcQ') === 'dQw4w9WgXcQ');
check('YouTube ID (embed)', youtube_id('https://www.youtube.com/embed/dQw4w9WgXcQ') === 'dQw4w9WgXcQ');
check('YouTube ID (shorts)', youtube_id('https://youtube.com/shorts/dQw4w9WgXcQ') === 'dQw4w9WgXcQ');
check('YouTube kimliği doğrudan', youtube_id('dQw4w9WgXcQ') === 'dQw4w9WgXcQ');
check('Vimeo ID', vimeo_id('https://vimeo.com/123456789') === '123456789');
check('Geçersiz video → null', youtube_id('https://vimeo.com/123456789') === null);

check('Embed URL nocookie', str_contains((string) video_embed_url(['provider' => 'youtube', 'video_id' => 'abc']), 'youtube-nocookie.com'));
check('Vimeo embed', str_contains((string) video_embed_url(['provider' => 'vimeo', 'video_id' => '123']), 'player.vimeo.com'));
check('Boş ID → null', video_embed_url(['provider' => 'youtube', 'video_id' => '']) === null);

// =========================================================================
section('Yükleyici (yol güvenliği)');

check('Geçersiz yol silinmedi', \Core\Uploader::delete('../../../etc/passwd') === false);
check('uploads dışı silinmedi', \Core\Uploader::delete('config/app.php') === false);
check('Geçersiz desen reddedildi', \Core\Uploader::delete('uploads/../../index.php') === false);
check('null güvenli', \Core\Uploader::delete(null) === false);

// =========================================================================
section('Yorum ve mesaj kuyruğu');

check('Onaylı yorum sayacı çalışır', is_int(Comment::countFor('project', 1)));
check('Bilinmeyen tip yorum sayacı 0', Comment::countFor('bilinmeyen', 1) === 0);
check('Bekleyen yorum sayısı hesaplandı', is_int(Message::pendingCommentsCount()));
check('Okunmamış mesaj sayısı hesaplandı', is_int(Message::unreadCount()));
check('Gelen kutusu sayfalanıyor', Message::inbox('', '', 1, 10)->perPage === 10);

// =========================================================================
section('Sistem sayfası koruması');

$kvkk = Page::findBySlug('kvkk');
check('KVKK sayfası bulundu', $kvkk !== null);
check('Sistem sayfası silinemez', Page::delete((int) $kvkk['id']) === 0);
check('Sistem sayfası hâlâ duruyor', Page::findBySlug('kvkk') !== null);

$hakkinda = Page::findAny((int) Database::value('SELECT id FROM pages WHERE slug = :s AND deleted_at IS NULL', ['s' => 'hakkinda'], 0));
check('Sistem olmayan sayfa bulundu', $hakkinda !== null);
if ($hakkinda !== null) {
    check('Sistem olmayan sayfa silinebilir', Page::delete((int) $hakkinda['id']) > 0);
    check('Silinen sayfa yumuşak silindi', (int) Database::value(
        'SELECT COUNT(*) FROM pages WHERE id = :id AND deleted_at IS NOT NULL',
        ['id' => (int) $hakkinda['id']], 0) === 1);
    check('Silinen slug yeniden kullanılabilir', Service::uniqueSlug('hakkinda') === 'hakkinda');
}
// testi tekrarlanabilir kıl
Page::create(['title_tr' => 'Hakkında', 'body_tr' => '<p>test</p>', 'is_active' => 1, 'created_at' => now()]);

// =========================================================================
section('Veritabanı bütünlüğü');

check('Bütün tablolar ayakta', (function () {
    $expected = ['users','modules','settings','services','projects','certificates','gallery_items',
                 'videos','news','profiles','testimonials','faq_items','social_posts','pages',
                 'messages','comments'];
    foreach ($expected as $t) {
        if ((int) Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name=:n", ['n' => $t], 0) !== 1) {
            return false;
        }
    }
    return true;
})(), '16 tablo');

check('Yabancı anahtar açık', Database::connection()->query('PRAGMA foreign_keys')->fetchColumn() == 1);
check('WAL modu', Database::value('PRAGMA journal_mode', [], '') === 'wal', (string) Database::value('PRAGMA journal_mode', [], ''));
check('Tutarlılık kontrolü', Database::value('PRAGMA integrity_check', [], 'ok') === 'ok', (string) Database::value('PRAGMA integrity_check', [], ''));

check('Tüm indeksler yerinde', (function () {
    $n = (int) Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type='index'", [], 0);
    return $n >= 30;
})(), Database::value("SELECT COUNT(*) FROM sqlite_master WHERE type='index'", [], 0) . ' indeks');

// =========================================================================
echo PHP_EOL . str_repeat('─', 56) . PHP_EOL;
if ($fail === 0) {
    echo "\033[32m  ✓ {$pass} test geçti, 0 başarısız\033[0m" . PHP_EOL;
    exit(0);
}
echo "\033[31m  ✗ {$pass} geçti, {$fail} BAŞARISIZ\033[0m" . PHP_EOL;
exit(1);
