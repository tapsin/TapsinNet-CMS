<?php declare(strict_types=1);

namespace Controllers\Site;

use Controllers\Controller;
use Core\Config;
use Core\Database;
use Core\ModuleRegistry;
use Core\Response;
use Models\{Service, Project, News, Testimonial, FaqItem, Page, Settings};

/**
 * ANA SAYFA — odaklı portfolyo.
 *
 * ÖNCEKİ YAPI: 10 eşit ağırlıklı bölüm, 7'si birbirinin aynısı olan "ray".
 * Sayfa uzun ama düz bir bant gibi okunduğu için göz nereye gideceğini
 * bilemiyordu.
 *
 * YENİ YAPI — 5 bölüm, net hiyerarşi:
 *   1. Vitrin         → 1 büyük iş + 2 yanında (asimetrik)
 *   2. Hizmetler      → tek satır, ince ayraçlı liste
 *   3. Referanslar    → yalnızca tipografi
 *   4. Haber + SSS    → iki kolon, derinliksiz
 * Sertifikalar, profiller, galeri, video ve sosyal medya menüden açılır;
 * ana sayfaya yüklenmezler. Aynı ağırlıkta 10 bölüm hiyerarşiyi yok eder.
 */
final class HomeController extends Controller
{
    protected string $viewPrefix = 'site';

    public function index(): Response
    {
        $on = static fn (string $m): bool => ModuleRegistry::isActive($m);

        return $this->view('home', [
            // 1 · Vitrin
            'feature'      => $on('projects') ? $this->feature() : [null, [], []],
            'recentWorks'  => $on('projects') ? Project::list([], [], [], 4) : [],
            'workCount'    => $on('projects') ? $this->count('projects') : 0,

            // 2 · Hizmetler
            'services'     => $on('services') ? Service::list([], [], [], 6) : [],

            // 3 · Referanslar
            'testimonials' => $on('testimonials') ? Testimonial::list([], [], [], 5) : [],

            // 4 · Haber + SSS
            'news'         => $on('news') ? News::list([], [], [], 3) : [],
            'faq'          => $on('faq') ? FaqItem::list([], [], [], 4) : [],

            'siteName'     => Settings::get('site_name', (string) Config::get('app.name')),
            'hero'         => [
                'eyebrow' => Settings::get('home_hero_eyebrow', t('home.eyebrow')),
                'title'   => Settings::get('home_hero_title', t('home.default_title')),
                'text'    => Settings::get('home_hero_text', t('home.default_text')),
            ],
            'stats'        => $this->stats(),
            'about'        => [
                'title' => Settings::get('home_about_title', t('home.about_title')),
                'text'  => Settings::get('home_about_text', ''),
                'cv'    => Settings::get('home_cv_link', ''),
            ],
            'footerPages'  => Page::footerPages(),
        ], 'layouts.site');
    }

    /**
     * Vitrin: öne çıkan ilk iş büyük, sonraki iki yanında.
     * @return array{0:?array,1:array,2:array}
     */
    private function feature(): array
    {
        $rows = Project::featured(3);
        if ($rows === []) {
            $rows = Project::list([], [], [], 3);
        }
        if ($rows === []) {
            return [null, [], []];
        }
        return [$rows[0], $rows[1] ?? [], $rows[2] ?? []];
    }

    /** Gerçek sayım — uydurma metrik yok. */
    private function count(string $module): int
    {
        $table = ModuleRegistry::table($module);
        if ($table === null) {
            return 0;
        }
        try {
            return (int) Database::value(
                "SELECT COUNT(*) FROM {$table} WHERE is_active = 1 AND deleted_at IS NULL", [], 0
            );
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @return array<int,array{value:string,label:string}> */
    private function stats(): array
    {
        $projects     = $this->count('projects');
        $certificates = $this->count('certificates');

        $years = 0;
        try {
            $min = Database::value(
                'SELECT MIN(COALESCE(completed_at, created_at)) FROM projects
                 WHERE is_active = 1 AND deleted_at IS NULL', [], null
            );
            if (is_string($min) && $min !== '') {
                $years = max(0, (int) date('Y') - (int) date('Y', strtotime($min)));
            }
        } catch (\Throwable) {
            $years = 0;
        }

        return [
            ['value' => (string) $projects,     'label' => t('stats.projects')],
            ['value' => (string) $certificates, 'label' => t('stats.certificates')],
            ['value' => (string) $years,        'label' => t('stats.years')],
        ];
    }
}
