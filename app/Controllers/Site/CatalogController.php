<?php
declare(strict_types=1);

namespace Controllers\Site;

use Controllers\Controller;
use Core\Config;
use Core\Database;
use Core\HttpException;
use Core\ModuleRegistry;
use Core\Request;
use Core\Response;
use Core\Str;
use Core\Translator;
use Models\{Service, Project, Certificate, GalleryItem, Video, News, Profile, Testimonial, FaqItem, SocialPost, Page, Comment, Settings};

/**
 * JENERİK KATALOG — tüm public listeleme + detay sayfaları.
 *
 * Route, config/modules.php'den üretildiği için modül adı
 * {slug} parametresinden okunur; yeni bir modül eklemek burada
 * tek satır eklemek demektir.
 */
final class CatalogController extends Controller
{
    protected string $viewPrefix = 'site';

    /** Bu controller modül kapısı uygulamaz — slug rota üzerinden gelir. */
    public static function moduleSlug(): string
    {
        return '';
    }

    // =========================================================================
    //  LİSTELEME
    // =========================================================================

    public function index(array $params): Response
    {
        $slug = $this->moduleFromRoute($params);
        $def  = $this->moduleDef($slug);

        // Modül kapısı: bu controller jenerik olduğu için kapı burada uygulanır.
        // Pasif bir modülün public route'u 404 döner.
        ModuleRegistry::requireActive($slug);

        $page = $this->page();
        $term = $this->term();
        $lang = Translator::lang();

        [$model, $where, $search, $order, $query] = $this->listQuery($slug, $def, $term);

        $paginator = $model::paginate($where, $search, $order, $page, (int) Config::get('app.per_page', 9), $query);

        return $this->view('catalog.index', [
            'module'     => $slug,
            'moduleName' => ModuleRegistry::name($slug, $lang),
            'moduleDesc' => ModuleRegistry::desc($slug, $lang),
            'glyph'      => ModuleRegistry::glyph($slug),
            'paginator'  => $paginator,
            'rows'       => $paginator->items,
            'term'       => $term,
            'filters'    => $this->filterOptions($slug, $def),
            'activeFilter' => $this->activeFilterLabel($slug, $def),
            'pageTitle'  => $paginator->currentPage > 1
                ? ModuleRegistry::name($slug, $lang) . ' — ' . $paginator->currentPage
                : ModuleRegistry::name($slug, $lang),
            'metaTitle'  => $this->metaTitle($slug, $def, $paginator->currentPage),
            'metaDesc'   => $this->metaDesc($slug, $def),
        ], 'layouts.site');
    }

    // =========================================================================
    //  DETAY
    // =========================================================================

    public function show(array $params): Response
    {
        $slugParam = (string) ($params['slug'] ?? '');
        $slug      = $this->moduleFromRoute($params);
        $def       = $this->moduleDef($slug);
        $model     = $this->modelFor($slug);

        ModuleRegistry::requireActive($slug);

        $row = $model::activeBySlug($slugParam);
        if ($row === null) {
            $this->notFound();
        }

        // Görüntülenme sayacı
        $this->countView($slug, (int) $row['id']);

        // Yorumlar
        // Comment::TYPES anahtarlari TEKILdir (project/news/service) ve
        // degerinin icindeki 'table' COĞUL modul slug'idir. Once esleşen
        // taniminin tablosunu bul, sonra $slug'ı ona göre kontrol et —
        // yoksa hiçbir detay sayfasında yorum bölümü açılmaz.
        $comments = [];
        $commentCount = 0;
        $commentType = $this->commentTypeFor($slug);
        $canComment  = \Core\ModuleRegistry::isActive('comments') && $commentType !== null;

        if ($canComment) {
            $comments     = Comment::approvedFor($commentType, (int) $row['id']);
            $commentCount = Comment::countFor($commentType, (int) $row['id']);
        }

        // İlgili içerik
        $related = $this->related($slug, $row, (int) $row['id']);

        return $this->view('catalog.show', [
            'module'     => $slug,
            'moduleName' => ModuleRegistry::name($slug),
            'row'        => $row,
            'related'    => $related,
            'comments'   => $comments,
            'commentCount' => $commentCount,
            'canComment' => $canComment,
            'commentType' => $commentType,
            'prev'       => $this->sibling($slug, (int) $row['id'], '<'),
            'next'       => $this->sibling($slug, (int) $row['id'], '>'),
            'metaTitle'  => $this->rowMetaTitle($slug, $row),
            'metaDesc'   => $this->rowMetaDesc($slug, $row),
        ], 'layouts.site');
    }

    // =========================================================================
    //  YARDIMCILAR
    // =========================================================================

    /**
     * Modül slug'ına karşılık gelen yorum tipini bulur.
     * Comment::TYPES: ['project' => ['table' => 'projects', ...], ...]
     * Yani 'projects' -> 'project'. Eşleşme yoksa null döner.
     */
    private function commentTypeFor(string $slug): ?string
    {
        foreach (Comment::TYPES as $type => $def) {
            if (($def['table'] ?? null) === $slug) {
                return $type;
            }
        }
        return null;
    }

    /** Route adından modül slug'ını çıkar. */
    private function moduleFromRoute(array $params): string
    {
        $name = $this->router->name();   // örn. "services" veya "projects.show"
        $slug = explode('.', $name)[0];
        if (ModuleRegistry::exists($slug)) {
            return $slug;
        }
        // Geri düşüş: eşleşen tanımı bul
        foreach (ModuleRegistry::definitions() as $s => $d) {
            if (($d['route'] ?? null) !== null && $name === $s) {
                return $s;
            }
        }
        $this->notFound();
    }

    private function moduleDef(string $slug): array
    {
        $def = ModuleRegistry::definitions()[$slug] ?? null;
        if ($def === null) {
            $this->notFound();
        }
        return $def;
    }

    private function modelFor(string $slug): string
    {
        return match ($slug) {
            'services'     => Service::class,
            'projects'     => Project::class,
            'certificates' => Certificate::class,
            'gallery'      => GalleryItem::class,
            'videos'       => Video::class,
            'news'         => News::class,
            'profiles'     => Profile::class,
            'testimonials' => Testimonial::class,
            'faq'          => FaqItem::class,
            'social'       => SocialPost::class,
            'pages'        => Page::class,
            default        => $this->notFound(),
        };
    }

    /** Sorgu parametrelerini hazırlar. */
    private function listQuery(string $slug, array $def, string $term): array
    {
        $model  = $this->modelFor($slug);
        $where  = [];
        $search = [];
        $query  = $this->currentQuery();
        unset($query['q']);
        if ($term !== '') {
            $query['q'] = $term;
        }

        // Modüle özel filtreler
        match ($slug) {
            'projects' => (static function () use (&$where, &$query): void {
                $cat = (string) Request::current()->query('kategori', '');
                if ($cat !== '') {
                    $where['category'] = $cat;
                }
                $year = (string) Request::current()->query('yil', '');
                if (preg_match('/^\d{4}$/', $year)) {
                    $where['@COALESCE(completed_at, created_at) LIKE'] = $year . '%';
                }
            })(),
            'gallery' => (static function () use (&$where, &$query): void {
                $album = (string) Request::current()->query('album', '');
                if ($album !== '') {
                    $where['album'] = $album;
                }
            })(),
            'videos' => (static function () use (&$where): void {
                $album = (string) Request::current()->query('album', '');
                if ($album !== '') {
                    $where['album'] = $album;
                }
            })(),
            'news' => (static function () use (&$where): void {
                $cat = (string) Request::current()->query('kategori', '');
                if ($cat !== '') {
                    $where['category'] = $cat;
                }
                $year = (string) Request::current()->query('yil', '');
                if (preg_match('/^\d{4}$/', $year)) {
                    $where['@COALESCE(published_at, created_at) LIKE'] = $year . '%';
                }
            })(),
            'profiles' => (static function () use (&$where): void {
                $type = (string) Request::current()->query('type', '');
                if (in_array($type, Profile::TYPES, true)) {
                    $where['profile_type'] = $type;
                }
            })(),
            'faq' => (static function () use (&$where): void {
                $cat = (string) Request::current()->query('kategori', '');
                if ($cat !== '') {
                    $where['category'] = $cat;
                }
            })(),
            'social' => (static function () use (&$where): void {
                $p = (string) Request::current()->query('provider', '');
                if (in_array($p, SocialPost::PROVIDERS, true)) {
                    $where['provider'] = $p;
                }
            })(),
            default => null,
        };

        if ($term !== '') {
            $search = $model::searchFor($term);
        }

        $order = $this->sortFromRequest(
            $this->sortableColumns($slug),
            $this->defaultSort($slug),
            $this->defaultDir($slug)
        );

        return [$model, $where, $search, $order, $query];
    }

    private function sortableColumns(string $slug): array
    {
        return match ($slug) {
            'projects'     => ['created_at', 'title_tr', 'views', 'sort_order'],
            'news'         => ['published_at', 'created_at', 'title_tr', 'views'],
            'services'     => ['sort_order', 'title_tr', 'created_at'],
            'certificates' => ['sort_order', 'issue_date', 'title_tr'],
            'gallery'      => ['sort_order', 'created_at', 'title_tr'],
            'videos'       => ['sort_order', 'created_at', 'views'],
            'profiles'     => ['sort_order', 'name', 'created_at'],
            'testimonials' => ['sort_order', 'created_at', 'rating'],
            'faq'          => ['sort_order', 'created_at'],
            'social'       => ['sort_order', 'created_at'],
            default        => ['created_at'],
        };
    }

    private function defaultSort(string $slug): string
    {
        return in_array($slug, ['news'], true) ? 'published_at' : ($this->sortableColumns($slug)[0]);
    }

    private function defaultDir(string $slug): string
    {
        return in_array($slug, ['services', 'certificates', 'gallery', 'profiles', 'faq', 'social'], true) ? 'ASC' : 'DESC';
    }

    /** Filtre seçenekleri. */
    private function filterOptions(string $slug, array $def): array
    {
        return match ($slug) {
            'projects' => [
                'kategori' => ['label' => t('filters.category'), 'options' => Project::categories()],
                'yil'      => ['label' => t('filters.year'),    'options' => $this->yearOptions('projects')],
            ],
            'gallery' => [
                'album' => ['label' => t('filters.album'), 'options' => GalleryItem::albums()],
            ],
            'videos' => [
                'album' => ['label' => t('filters.album'), 'options' => Video::albums()],
            ],
            'news' => [
                'kategori' => ['label' => t('filters.category'), 'options' => News::categories()],
                'yil'      => ['label' => t('filters.year'),    'options' => $this->yearOptions('news')],
            ],
            'profiles' => (static function (): array {
                $out = [];
                foreach (Profile::TYPES as $type) {
                    $out[] = ['key' => $type, 'label' => Profile::typeLabel($type), 'count' => 0];
                }
                return ['type' => ['label' => t('filters.type'), 'options' => $out]];
            })(),
            'faq' => [
                'kategori' => ['label' => t('filters.category'), 'options' => FaqItem::categories()],
            ],
            'social' => [
                'provider' => ['label' => t('filters.platform'), 'options' => array_map(
                    static fn (string $p): array => ['key' => $p, 'label' => ucfirst($p), 'count' => 0],
                    SocialPost::PROVIDERS
                )],
            ],
            default => [],
        };
    }

    private function yearOptions(string $table): array
    {
        $column = $table === 'news' ? 'COALESCE(published_at, created_at)' : 'COALESCE(completed_at, created_at)';
        try {
            $rows = Database::select(
                "SELECT strftime('%Y', {$column}) AS y, COUNT(*) AS n
                 FROM {$table} WHERE is_active = 1 AND deleted_at IS NULL
                 GROUP BY y ORDER BY y DESC LIMIT 15"
            );
        } catch (\Throwable) {
            return [];
        }

        return array_map(static fn (array $r): array => [
            'key'   => (string) $r['y'],
            'label' => (string) $r['y'],
            'count' => (int) $r['n'],
        ], $rows);
    }

    private function activeFilterLabel(string $slug, array $def): ?string
    {
        foreach (['kategori', 'album', 'type', 'provider', 'yil'] as $key) {
            $v = (string) $this->request->query($key, '');
            if ($v !== '') {
                return $key === 'yil' ? (string) $v : ucfirst(str_replace('_', ' ', $v));
            }
        }
        return null;
    }

    /** Önceki/sonraki kayıt. */
    private function sibling(string $slug, int $id, string $direction): ?array
    {
        $model = $this->modelFor($slug);
        $cmp   = $direction === '<' ? '<' : '>';
        $ord   = $direction === '<' ? 'DESC' : 'ASC';
        $col   = 'id';

        try {
            return Database::first(
                "SELECT * FROM {$model::table()}
                 WHERE id {$cmp} :id AND is_active = 1 AND deleted_at IS NULL
                 ORDER BY id {$ord} LIMIT 1",
                ['id' => $id]
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * İlgili içerikler.
     *
     * Kurallar:
     *  · Mevcut kayıt ASLA kendi kendini önerilmez (id != $id).
     *  · Kategori boşsa filtre uygulanmaz; tüm modül listelenir.
     *  · 3 göstermek için 4 çekilir: kendisi elendikten sonra 3 kalmasın diye.
     */
    private function related(string $slug, array $row, int $id): array
    {
        $self = ['id !=' => $id];
        $cat  = trim((string) ($row['category'] ?? ''));

        $pick = static function (string $model) use ($self, $cat, $id): array {
            $where = $self;
            if ($cat !== '') {
                $where['category'] = $cat;
            }
            $order = $model === 'News' ? ['published_at' => 'DESC'] : ['created_at' => 'DESC'];
            $out = [];
            foreach ($model::list($where, [], $order, 4) as $r) {
                if ((int) $r['id'] === $id) {
                    continue;
                }
                $out[] = $r;
                if (count($out) === 3) {
                    break;
                }
            }
            return $out;
        };

        return match ($slug) {
            'projects' => $pick(Project::class),
            'news'     => $pick(News::class),
            'services' => $pick(Service::class),
            default    => [],
        };
    }

    private function countView(string $slug, int $id): void
    {
        try {
            match ($slug) {
                'projects', 'videos' => method_exists($this->modelFor($slug), 'countViews')
                    ? $this->modelFor($slug)::countViews($id) : null,
                'news' => News::countViews($id),
                default => null,
            };
        } catch (\Throwable) {
            // Sayaç hatası sayfayı bozmamalı
        }
    }

    private function metaTitle(string $slug, array $def, int $page): string
    {
        $base = Settings::get('meta_title', '') ?: ModuleRegistry::name($slug);
        return $page > 1 ? $base . ' — ' . $page : $base;
    }

    private function metaDesc(string $slug, array $def): string
    {
        $d = Settings::get('meta_description', '');
        if ($d !== '') {
            return Str::limit($d, 160);
        }
        $desc = ModuleRegistry::desc($slug);
        return $desc !== '' ? Str::limit($desc, 160) : Str::limit(ModuleRegistry::name($slug), 160);
    }

    private function rowMetaTitle(string $slug, array $row): string
    {
        return Str::limit((string) (loc($row, 'meta_title') ?: loc($row, 'title', null, $row['name'] ?? '')), 70);
    }

    private function rowMetaDesc(string $slug, array $row): string
    {
        $fallback = loc($row, 'summary') ?: loc($row, 'excerpt') ?: loc($row, 'description') ?: loc($row, 'quote') ?: '';
        return Str::limit((string) (loc($row, 'meta_desc') ?: $fallback), 160);
    }
}
