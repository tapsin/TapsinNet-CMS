<?php
declare(strict_types=1);

namespace Controllers\Site;

use Controllers\Controller;
use Core\Config;
use Core\Database;
use Core\HttpException;
use Core\ModuleRegistry;
use Core\Response;
use Core\Str;
use Core\Translator;
use Models\{Service, Project, News, Profile, FaqItem, GalleryItem, Video, Certificate, Page, Testimonial, SocialPost};

/**
 * Site geneli arama.
 *
 * Yalnızca AKTİF modüller taranır. Her tabloda en fazla 6 sonuç alınır;
 * kullanıcı sonuç sayfasına tıklayınca ilgili modülün kendi aramasına
 * (kalıcı, indeksli) yönlendirilir.
 */
final class SearchController extends Controller
{
    protected string $viewPrefix = 'site';

    /** modül => [model sınıfı, aranacak sütunlar] */
    private const SOURCES = [
        'services'     => [Service::class,      ['title_tr', 'title_en', 'excerpt_tr', 'excerpt_en']],
        'projects'     => [Project::class,      ['title_tr', 'title_en', 'summary_tr', 'summary_en', 'tags', 'tech_stack']],
        'news'         => [News::class,          ['title_tr', 'title_en', 'summary_tr', 'summary_en', 'tags', 'category']],
        'certificates' => [Certificate::class,  ['title_tr', 'title_en', 'issuer']],
        'gallery'      => [GalleryItem::class,  ['title_tr', 'title_en', 'caption_tr', 'caption_en', 'album']],
        'videos'       => [Video::class,        ['title_tr', 'title_en', 'description_tr', 'description_en', 'album']],
        'profiles'     => [Profile::class,      ['name', 'title_tr', 'title_en', 'company', 'bio_tr', 'location']],
        'testimonials' => [Testimonial::class,  ['author_name', 'author_company', 'quote_tr']],
        'faq'          => [FaqItem::class,      ['question_tr', 'question_en', 'answer_tr']],
        'social'       => [SocialPost::class,   ['caption_tr', 'caption_en']],
        'pages'        => [Page::class,         ['title_tr', 'title_en', 'body_tr']],
    ];

    private const MAX_PER_MODULE = 6;

    public function index(): Response
    {
        $term = $this->term();
        $lang = Translator::lang();

        $groups = [];

        if (mb_strlen($term) >= 2) {
            foreach (self::SOURCES as $slug => [$model, $columns]) {
                if (!ModuleRegistry::isActive($slug)) {
                    continue;
                }
                $route = ModuleRegistry::route($slug);
                if ($route === null) {
                    continue;
                }

                $items = $model::list([], $model::searchFor($term), ['created_at' => 'DESC'], self::MAX_PER_MODULE);
                if ($items === []) {
                    continue;
                }

                $groups[] = [
                    'module' => $slug,
                    'name'   => ModuleRegistry::name($slug, $lang),
                    'url'    => module_url($slug, ['q' => $term]),
                    'items'  => array_map(fn (array $r): array => [
                        'title'   => $this->rowTitle($r),
                        'url'     => $this->rowUrl($slug, $r),
                        'excerpt' => $this->rowExcerpt($r, $term),
                        'image'   => $this->rowImage($r),
                    ], $items),
                ];
            }
        }

        $total = array_sum(array_map(static fn (array $g): int => count($g['items']), $groups));

        return $this->view('search', [
            'term'      => $term,
            'groups'    => $groups,
            'total'     => $total,
            'metaTitle' => $term !== '' ? t('search.results_for', ['term' => $term]) : t('search.title'),
            'metaDesc'  => '',
        ], 'layouts.site');
    }

    private function rowTitle(array $r): string
    {
        return (string) (loc($r, 'title') ?: ($r['name'] ?? $r['author_name'] ?? $r['caption_tr'] ?? $r['question_tr'] ?? ''));
    }

    private function rowUrl(string $slug, array $r): string
    {
        $route = ModuleRegistry::route($slug);
        $base  = rtrim((string) Config::get('app.url', ''), '/');
        $lang  = Translator::lang();
        $prefix = $lang === (string) Config::get('i18n.default') ? '' : $lang . '/';

        if ($slug === 'pages') {
            return $base . '/' . $prefix . 'sayfa/' . rawurlencode((string) $r['slug']);
        }
        if ($route === null) {
            return $base . '/';
        }
        return $base . '/' . $prefix . $route . '/' . rawurlencode((string) $r['slug']);
    }

    private function rowExcerpt(array $r, string $term): string
    {
        $text = (string) (loc($r, 'summary') ?: loc($r, 'excerpt') ?: loc($r, 'description')
            ?: loc($r, 'quote') ?: loc($r, 'body') ?: $r['bio_tr'] ?? '');
        return Str::highlight(Str::limit(strip_tags($text), 150), $term);
    }

    private function rowImage(array $r): ?string
    {
        foreach (['cover_image', 'image_path', 'avatar', 'author_avatar', 'media_path'] as $field) {
            $v = (string) ($r[$field] ?? '');
            if ($v !== '') {
                return upload_url($v);
            }
        }
        return null;
    }
}
