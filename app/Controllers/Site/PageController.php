<?php
declare(strict_types=1);

namespace Controllers\Site;

use Controllers\Controller;
use Core\Response;
use Core\Str;
use Models\Page;
use Models\Settings;

/**
 * Statik sayfalar (KVKK, gizlilik, çerez, hakkında…).
 */
final class PageController extends Controller
{
    protected string $viewPrefix = 'site';

    public function show(array $params): Response
    {
        $row = Page::activeBySlug((string) $params['slug']);
        if ($row === null) {
            $this->notFound();
        }

        return $this->view('page', [
            'row'   => $row,
            'metaTitle' => Str::limit((string) (loc($row, 'meta_title') ?: loc($row, 'title')), 70),
            'metaDesc'  => Str::limit((string) (loc($row, 'meta_desc') ?: loc($row, 'excerpt')), 160),
        ], 'layouts.site');
    }
}
