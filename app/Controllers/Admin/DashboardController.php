<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Core\ModuleRegistry;
use Core\Response;
use Models\{Message, Module, News, Project, Service, GalleryItem, Video, Certificate, Profile, Testimonial};

/**
 * Panel özeti.
 */
final class DashboardController extends Controller
{
    protected string $viewPrefix = 'admin';
    protected string $indexRoute = '/admin';

    public function index(): Response
    {
        $counts = [];
        foreach (['services', 'projects', 'certificates', 'gallery', 'videos', 'news', 'profiles', 'testimonials', 'faq', 'social', 'pages'] as $slug) {
            $n = Module::contentCount($slug);
            if ($n !== null) {
                $counts[$slug] = $n;
            }
        }

        $recentNews = News::list([], [], ['created_at' => 'DESC'], 5, 0);
        $recentMsg  = Database::select('SELECT * FROM messages ORDER BY created_at DESC LIMIT 6');

        return $this->view('dashboard', [
            'badge'        => Message::badge(),
            'counts'       => $counts,
            'modules'      => Module::catalog(),
            'recentNews'   => $recentNews,
            'recentMsg'    => $recentMsg,
            'siteVersion'  => '1.0.0',
            'phpVersion'   => PHP_VERSION,
            'totalViews'   => (int) Database::value('SELECT COALESCE(SUM(views),0) FROM projects', [], 0)
                             + (int) Database::value('SELECT COALESCE(SUM(views),0) FROM news', [], 0)
                             + (int) Database::value('SELECT COALESCE(SUM(views),0) FROM videos', [], 0),
        ], 'layouts.admin');
    }

    /** Panel içi hızlı arama. */
    public function search(): Response
    {
        $term = $this->term();
        if (mb_strlen($term) < 2) {
            return $this->json(['results' => []]);
        }

        $results = [];

        foreach (['projects' => [Project::class, 'title_tr'], 'news' => [News::class, 'title_tr'], 'services' => [Service::class, 'title_tr']] as $slug => [$class, $col]) {
            if (!ModuleRegistry::isActive($slug)) {
                continue;
            }
            $rows = $class::list([], [$col => ['op' => 'like', 'value' => $term]], ['created_at' => 'DESC'], 5);
            foreach ($rows as $r) {
                $results[] = [
                    'type'  => ModuleRegistry::name($slug),
                    'title' => (string) $r['title_tr'],
                    'url'   => url('/admin/' . $slug . '/duzenle/' . (int) $r['id']),
                ];
            }
        }

        return $this->json(['results' => $results]);
    }
}
