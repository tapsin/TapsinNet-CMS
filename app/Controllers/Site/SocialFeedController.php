<?php
declare(strict_types=1);

namespace Controllers\Site;

use Controllers\Controller;
use Core\Config;
use Core\ModuleRegistry;
use Core\Response;
use Core\Str;
use Models\{SocialPost, Settings};

/**
 * Sosyal medya akışı sayfası (modül aktifse).
 */
final class SocialFeedController extends Controller
{
    protected string $viewPrefix = 'site';
    protected string $moduleSlug = 'social';

    public function index(): Response
    {
        $paginator = SocialPost::paginate(
            [],
            [],
            ['sort_order' => 'ASC', 'created_at' => 'DESC'],
            $this->page(),
            (int) Config::get('app.per_page', 12),
            $this->currentQuery()
        );

        return $this->view('social', [
            'paginator' => $paginator,
            'rows'      => $paginator->items,
            'metaTitle' => t('social.title'),
            'metaDesc'  => Str::limit(Settings::get('site_description', t('social.intro')), 160),
        ], 'layouts.site');
    }
}
