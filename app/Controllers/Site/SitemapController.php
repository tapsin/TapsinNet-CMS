<?php
declare(strict_types=1);

namespace Controllers\Site;

use Controllers\Controller;
use Core\Config;
use Core\Database;
use Core\Response;
use Core\Translator;
use Core\ModuleRegistry;
use Models\Settings;

/**
 * sitemap.xml ve robots.txt — dinamik, modül durumuna göre.
 */
final class SitemapController extends Controller
{
    protected string $viewPrefix = 'site';

    public function sitemap(): Response
    {
        $base = rtrim((string) Config::get('app.url', ''), '/');
        $lang = Translator::lang();
        $langs = Translator::available();
        $prefix = $lang === (string) Config::get('i18n.default') ? '' : $lang . '/';

        $urls = [[
            'loc'        => $base . '/',
            'lastmod'    => date('Y-m-d'),
            'changefreq' => 'weekly',
            'priority'   => '1.0',
        ]];

        foreach ([
            'projects' => ['daily', '0.9'],
            'services' => ['monthly', '0.8'],
            'news'     => ['daily', '0.7'],
            'gallery'  => ['weekly', '0.6'],
            'videos'   => ['weekly', '0.6'],
            'certificates' => ['monthly', '0.5'],
            'profiles' => ['monthly', '0.5'],
            'testimonials' => ['monthly', '0.5'],
            'faq'      => ['monthly', '0.5'],
        ] as $slug => [$freq, $prio]) {
            if (!ModuleRegistry::isActive($slug)) {
                continue;
            }
            $route = ModuleRegistry::route($slug);
            $table = ModuleRegistry::table($slug);
            if ($route === null || $table === null) {
                continue;
            }

            $urls[] = [
                'loc'        => $base . '/' . $prefix . $route,
                'lastmod'    => date('Y-m-d'),
                'changefreq' => $freq,
                'priority'   => $prio,
            ];

            try {
                $rows = Database::select(
                    "SELECT slug, updated_at, created_at FROM {$table}
                     WHERE is_active = 1 AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 2000"
                );
            } catch (\Throwable) {
                $rows = [];
            }

            foreach ($rows as $r) {
                $urls[] = [
                    'loc'        => $base . '/' . $prefix . $route . '/' . rawurlencode((string) $r['slug']),
                    'lastmod'    => substr((string) ($r['updated_at'] ?? $r['created_at'] ?? date('Y-m-d')), 0, 10),
                    'changefreq' => $freq,
                    'priority'   => '0.6',
                ];
            }
        }

        if (ModuleRegistry::isActive('pages')) {
            try {
                $pages = Database::select(
                    "SELECT slug, updated_at FROM pages WHERE is_active = 1 AND deleted_at IS NULL"
                );
            } catch (\Throwable) {
                $pages = [];
            }
            foreach ($pages as $p) {
                $urls[] = [
                    'loc'        => $base . '/' . $prefix . 'sayfa/' . rawurlencode((string) $p['slug']),
                    'lastmod'    => substr((string) ($p['updated_at'] ?? date('Y-m-d')), 0, 10),
                    'changefreq' => 'yearly',
                    'priority'   => '0.3',
                ];
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n"
                  . '    <loc>' . e($u['loc']) . "</loc>\n"
                  . '    <lastmod>' . e($u['lastmod']) . "</lastmod>\n"
                  . '    <changefreq>' . e($u['changefreq']) . "</changefreq>\n"
                  . '    <priority>' . e($u['priority']) . "</priority>\n"
                  . "  </url>\n";
        }
        $xml .= '</urlset>';

        return Response::xml($xml)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function robots(): Response
    {
        $base = rtrim((string) Config::get('app.url', ''), '/');
        $index = Settings::get('show_index', '1');
        $allow = $index === '0' ? "Disallow: /\n" : "Allow: /\n";

        $body = "User-agent: *\n{$allow}"
              . "Disallow: /admin\n"
              . "Disallow: /lang/\n"
              . "Sitemap: {$base}/sitemap.xml\n";

        return Response::text($body)
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
