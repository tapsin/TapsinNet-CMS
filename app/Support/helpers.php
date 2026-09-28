<?php
declare(strict_types=1);

use Core\Application;
use Core\Auth;
use Core\Config;
use Core\Csrf;
use Core\Database;
use Core\ModuleRegistry;
use Core\Paginator;
use Core\Request;
use Core\Response;
use Core\Sanitizer;
use Core\Security;
use Core\Session;
use Core\Str;
use Core\Translator;
use Core\View;

if (!function_exists('e')) {
    /**
     * HTML kaçışı — şablonlarda varsayılan çıktı fonksiyonu.
     * Ham HTML basmak için: {!! ... !!} yerine view()->raw() kullanılır.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return \Core\Env::get($key, $default);
    }
}

if (!function_exists('env_bool')) {
    function env_bool(string $key, bool $default = false): bool
    {
        $v = env($key);
        if ($v === null || $v === '') {
            return $default;
        }
        return in_array(strtolower((string) $v), ['1', 'true', 'on', 'yes'], true);
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('app')) {
    function app(?string $suffix = null): mixed
    {
        $app = Application::instance();
        return $suffix === null ? $app : $app->{$suffix}();
    }
}

if (!function_exists('config_path')) {
    function config_path(string $append = ''): string
    {
        return Application::basePath('config' . ($append ? '/' . $append : ''));
    }
}

if (!function_exists('base_path')) {
    function base_path(string $append = ''): string
    {
        return Application::basePath($append);
    }
}

if (!function_exists('public_path')) {
    function public_path(string $append = ''): string
    {
        return Application::publicPath($append);
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $append = ''): string
    {
        return Application::storagePath($append);
    }
}

if (!function_exists('db')) {
    function db(): Database
    {
        return Database::connection();
    }
}

if (!function_exists('t')) {
    /**
     * Çeviri. Kısa kullanım: t('nav.home') · t('items.count', ['n' => 5])
     */
    function t(string $key, array $replace = [], ?string $lang = null): string
    {
        return Translator::t($key, $replace, $lang);
    }
}

if (!function_exists('__')) {
    /** Çeviri kısayolu. */
    function __(string $key, array $replace = []): string
    {
        return Translator::t($key, $replace);
    }
}

if (!function_exists('locale')) {
    function locale(): string
    {
        return Translator::lang();
    }
}

if (!function_exists('url')) {
    /**
     * Adlandırılmış route'tan veya doğrudan yoldan URL üretir.
     * url('services.index')  ·  url('/hizmetler', ['page' => 2])
     */
    function url(string $path = '/', array $query = []): string
    {
        if (str_starts_with($path, 'name:')) {
            $path = (string) Router::current()->resolvePath(substr($path, 5));
        }
        $base = rtrim((string) Config::get('app.url', ''), '/');
        $qs   = $query === [] ? '' : '?' . http_build_query($query);
        return $base . '/' . ltrim($path, '/') . $qs;
    }
}

if (!function_exists('route')) {
    /** Aktif route adını döner. */
    function route(?string $default = ''): string
    {
        $name = Router::current()->name();
        return $name === '' ? $default : $name;
    }
}

if (!function_exists('asset')) {
    /** Statik varlık URL'i — sürüm damgası ile önbellek kırılır. */
    function asset(string $path): string
    {
        $rel  = ltrim($path, '/');
        $full = Application::publicPath($rel);
        $ver  = is_file($full) ? substr((string) filemtime($full), -6) : Config::get('app.env', 'production');
        $base = rtrim((string) Config::get('app.url', ''), '/');
        return $base . '/' . $rel . '?v=' . $ver;
    }
}

if (!function_exists('upload_url')) {
    /** Yüklenen dosya URL'i. */
    function upload_url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        $base = rtrim((string) Config::get('app.url', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('module')) {
    /** Modül durumu / adı. */
    function module(string $slug, string $what = 'active'): mixed
    {
        return match ($what) {
            'active'  => ModuleRegistry::isActive($slug),
            'name'    => ModuleRegistry::name($slug),
            'route'   => ModuleRegistry::route($slug),
            'glyph'   => ModuleRegistry::glyph($slug),
            'exists'  => ModuleRegistry::exists($slug),
            default   => null,
        };
    }
}

if (!function_exists('module_url')) {
    /** Modülün public listeleme URL'i. */
    function module_url(string $slug, array $query = []): ?string
    {
        $route = ModuleRegistry::route($slug);
        if ($route === null) {
            return null;
        }
        $lang = Translator::lang();
        $prefix = $lang === (string) Config::get('i18n.default') ? '' : $lang . '/';
        return rtrim((string) Config::get('app.url', ''), '/') . '/' . $prefix . $route . ($query ? '?' . http_build_query($query) : '');
    }

    /**
     * Bir kaydın detay URL'i — kart şablonlarının tek kaynağı.
     *
     * Neden var: kart şablonları yolu elle yazıyordu ve rota adlarıyla
     * uyuşmuyordu ('/profil/x' yerine rotada '/profiller' vardı). Sonuç:
     * her profil ve sertifika kartı 404'e düşüyordu. Rota config'den
     * okunur, yol elle uydurulmaz.
     *
     * @return string|null slug yoksa veya modülün public rotası yoksa null
     */
    function item_url(string $module, array $row): ?string
    {
        $slug = trim((string) ($row['slug'] ?? ''));
        $route = ModuleRegistry::route($module);
        if ($slug === '' || $route === null) {
            return null;
        }
        $lang = Translator::lang();
        $prefix = $lang === (string) Config::get('i18n.default') ? '' : $lang . '/';
        return rtrim((string) Config::get('app.url', ''), '/') . '/' . $prefix . $route . '/' . $slug;
    }

    /**
     * Kartlarda detay bağlantısı basarken kullanılır.
     *
     * Detay sayfası olmayan kayıt (slug yok, ya da modülün public rotası
     * tanımlı değil) için `href="#"` üretmek yanlıştı: boş ya da ölü
     * bağlantı veriyordu. Bu durumda metin bağlantıya dönüşür — kart yine
     * bilgi taşır, ama tıklanabilir olmayan bir şey gösterilmez.
     *
     * @return array{href: string|null, tag: string} hangi etiket basılacak
     */
    function card_link(string $module, array $row, string $class = ''): array
    {
        $href = item_url($module, $row);
        return $href === null
            ? ['href' => null, 'tag' => 'span']
            : ['href' => $href, 'tag' => 'a'];
    }
}

if (!function_exists('current_query')) {
    /**
     * Görünümlerde kullanılan, beyaz listeli sorgu parametreleri.
     * Filtre bağlantılarında mevcut parametreleri korumak için gerekir.
     *
     * @return array<string,string>
     */
    function current_query(array $override = []): array
    {
        static $keys = ['q', 'kategori', 'category', 'album', 'type', 'filter',
                        'sirala', 'yon', 'provider', 'yil', 'page'];

        $out = [];
        foreach ($keys as $key) {
            $value = $_GET[$key] ?? null;
            if (is_string($value) && $value !== '') {
                $out[$key] = mb_substr($value, 0, 100);
            }
        }
        unset($out['page']);   // sayfa her yeniden hesaplanır

        foreach ($override as $key => $value) {
            if ($value === '' || $value === null) {
                unset($out[$key]);
            } else {
                $out[$key] = (string) $value;
            }
        }

        return $out;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('old')) {
    /** Form hatası sonrası eski değer. */
    function old(string $key, mixed $default = ''): mixed
    {
        $v = Session::get('_old.' . $key, $default);
        return $v === null ? $default : $v;
    }
}

if (!function_exists('old_input')) {
    function old_input(): array
    {
        $v = Session::get('_old', []);
        unset($v['_token']);
        return is_array($v) ? $v : [];
    }
}

if (!function_exists('error_for')) {
    function error_for(string $field): ?string
    {
        $errors = Session::get('_errors', []);
        return is_array($errors) ? ($errors[$field] ?? null) : null;
    }
}

if (!function_exists('all_errors')) {
    function all_errors(): array
    {
        $e = Session::get('_errors', []);
        return is_array($e) ? $e : [];
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }
}

if (!function_exists('flashes')) {
    function flashes(): array
    {
        return Session::flashes();
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return Auth::check();
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect(Security::safeRedirect($url), $status);
    }
}

if (!function_exists('back')) {
    function back(string $fallback = '/'): Response
    {
        $target = Security::safeRedirect(Request::current()->wantsBack($fallback), $fallback);

        // Geri dönüş yolunda çapa yoksa fallback'in çapası eklenir.
        // Yorum gönderimi bu çapaya dayanır: kullanıcı sayfanın başına
        // değil, yorum bölümüne dönmelidir. Fallback yalnızca çapa da olabilir
        // ('#comments'), yol + çapa da olabilir ('/isler/x#comments').
        if (!str_contains($target, '#') && str_contains($fallback, '#')) {
            $pos = strpos($fallback, '#');
            $target .= substr($fallback, (int) $pos);
        }

        return Response::redirect($target);
    }
}

if (!function_exists('abort')) {
    function abort(int $status = 404, ?string $message = null): never
    {
        Application::abort($status, $message);
    }
}

if (!function_exists('abort_if')) {
    function abort_if(bool $condition, int $status = 404, ?string $message = null): void
    {
        Application::abortIf($condition, $status, $message);
    }
}

if (!function_exists('abort_unless')) {
    function abort_unless(bool $condition, int $status = 404, ?string $message = null): void
    {
        Application::abortIf(!$condition, $status, $message);
    }
}

if (!function_exists('view')) {
    /**
     * Görünüm render eder.
     * view('site.home', $data, 'layouts.site')
     */
    function view(string $template, array $data = [], ?string $layout = null): Response
    {
        return View::make($template, $data, $layout);
    }
}

if (!function_exists('partial')) {
    function partial(string $template, array $data = []): string
    {
        return View::partial($template, $data);
    }
}

if (!function_exists('card_for')) {
    /**
     * Modül slug'ından kart partial'ı çözer.
     * Ana sayfa rayları ve liste sayfaları aynı kartları kullanır.
     */
    function card_for(string $module, array $row, array $extra = []): string
    {
        $map = [
            'services'     => 'site.partials.card.service',
            'projects'     => 'site.partials.card.project',
            'certificates' => 'site.partials.card.certificate',
            'gallery'      => 'site.partials.card.gallery',
            'videos'       => 'site.partials.card.video',
            'news'         => 'site.partials.card.news',
            'profiles'     => 'site.partials.card.profile',
            'testimonials' => 'site.partials.card.testimonial',
            'faq'          => 'site.partials.card.faq',
            'social'       => 'site.partials.card.social',
        ];

        $template = $map[$module] ?? null;
        if ($template === null) {
            return '';
        }
        return View::partial($template, $row + $extra + ['row' => $row]);
    }
}

if (!function_exists('share')) {
    function share(string $key, mixed $value): void
    {
        View::share($key, $value);
    }
}

if (!function_exists('content')) {
    function content(): string
    {
        return View::yieldContent();
    }
}

if (!function_exists('section')) {
    /** Layout'ta isimli bölüm basar. */
    function section(string $name, ?string $value = null): void
    {
        if ($value === null) {
            ob_start();
            return;
        }
        View::append($name, $value);
    }
}

if (!function_exists('section_end')) {
    function section_end(string $name): void
    {
        View::append($name, (string) ob_get_clean());
    }
}

if (!function_exists('section_get')) {
    function section_get(string $name, string $default = ''): string
    {
        return View::section($name, $default);
    }
}

if (!function_exists('csrf_meta')) {
    function csrf_meta(): string
    {
        return '<meta name="csrf-token" content="' . e(Csrf::token()) . '">';
    }
}

if (!function_exists('loc')) {
    /**
     * İki dilli içerik alanını çözer.
     * $row['title_tr'] / $row['title_en'] → aktif dile göre değer
     */
    function loc(array $row, string $field, ?string $lang = null, string $default = ''): string
    {
        $lang ??= Translator::lang();
        $primary   = $row[$field . '_' . $lang] ?? null;
        $fallbackL = Translator::fallbackLang();
        $secondary = $row[$field . '_' . $fallbackL] ?? null;

        $value = is_string($primary) && trim($primary) !== '' ? $primary : $secondary;
        return is_string($value) ? $value : $default;
    }
}

if (!function_exists('loc_raw')) {
    /** loc() ama HTML kaçışı yapılmadan (zaten sanitize edilmiş alanlar için). */
    function loc_raw(array $row, string $field, ?string $lang = null, string $default = ''): string
    {
        return loc($row, $field, $lang, $default);
    }
}

if (!function_exists('safe_html')) {
    /**
     * Beyaz listeye göre temizlenmiş HTML. Yalnızca Sanitizer'dan
     * geçmiş içeriklerde kullanılmalıdır.
     */
    function safe_html(?string $dirty, bool $allowImages = true): string
    {
        return Sanitizer::html($dirty, $allowImages);
    }
}

if (!function_exists('str_limit')) {
    function str_limit(?string $value, int $limit = 120, string $end = '…'): string
    {
        return Str::limit((string) $value, $limit, $end);
    }
}

if (!function_exists('str_slug')) {
    function str_slug(string $value): string
    {
        return Str::slug($value);
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, ?string $format = null): string
    {
        return Str::date($date, $format);
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $date): string
    {
        return Str::ago($date);
    }
}

if (!function_exists('excerpt')) {
    function excerpt(?string $html, int $limit = 160): string
    {
        return Str::excerpt($html, $limit);
    }
}

if (!function_exists('paginator_url')) {
    function paginator_url(Paginator $p, int $page): string
    {
        return $p->url($page);
    }
}

if (!function_exists('youtube_id')) {
    /**
     * YouTube video kimliğini çıkarır.
     * youtube.com/watch?v=ID · youtu.be/ID · /embed/ID · /shorts/ID
     */
    function youtube_id(string $urlOrId): ?string
    {
        $v = trim($urlOrId);
        if ($v === '') {
            return null;
        }
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $v)) {
            return $v;
        }
        $patterns = [
            '#youtube\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})#',
            '#youtu\.be/([A-Za-z0-9_-]{11})#',
            '#youtube\.com/embed/([A-Za-z0-9_-]{11})#',
            '#youtube\.com/shorts/([A-Za-z0-9_-]{11})#',
            '#youtube\.com/v/([A-Za-z0-9_-]{11})#',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $v, $m)) {
                return $m[1];
            }
        }
        return null;
    }
}

if (!function_exists('vimeo_id')) {
    function vimeo_id(string $urlOrId): ?string
    {
        $v = trim($urlOrId);
        if ($v === '') {
            return null;
        }
        if (preg_match('/^\d{6,12}$/', $v)) {
            return $v;
        }
        if (preg_match('#vimeo\.com/(?:video/)?(\d{6,12})#', $v, $m)) {
            return $m[1];
        }
        return null;
    }
}

if (!function_exists('video_embed_url')) {
    /** Gizlilik dostu (nocookie) embed URL'si. */
    function video_embed_url(array $row): ?string
    {
        $provider = (string) ($row['provider'] ?? 'youtube');
        $id       = (string) ($row['video_id'] ?? '');
        if ($id === '') {
            return null;
        }
        return $provider === 'vimeo'
            ? 'https://player.vimeo.com/video/' . rawurlencode($id)
            : 'https://www.youtube-nocookie.com/embed/' . rawurlencode($id);
    }
}

if (!function_exists('video_watch_url')) {
    function video_watch_url(array $row): ?string
    {
        $provider = (string) ($row['provider'] ?? 'youtube');
        $id       = (string) ($row['video_id'] ?? '');
        if ($id === '') {
            return null;
        }
        return $provider === 'vimeo'
            ? 'https://vimeo.com/' . rawurlencode($id)
            : 'https://www.youtube.com/watch?v=' . rawurlencode($id);
    }
}

if (!function_exists('setting')) {
    /**
     * Site ayarı. Ayar yoksa $default döner.
     * Ayar değerleri tek sorguda önbelleğe alınır.
     */
    function setting(string $key, string $default = '', ?string $lang = null): string
    {
        return \Models\Settings::get($key, $default, $lang);
    }
}
