<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Models\Page;
use Core\Uploader;

final class PageController extends ResourceController
{
    protected string $model = Page::class;
    protected string $moduleSlug = 'pages';
    protected string $defaultSort = 'sort_order';
    protected string $defaultDir = 'ASC';
    protected string $singular = 'page';
    protected string $plural = 'pages';

    protected array $columns = [
        'title_tr'     => ['type' => 'title', 'label' => 'admin.col.title'],
        'slug'         => ['type' => 'text',  'label' => 'admin.col.slug'],
        'is_system'    => ['type' => 'badge',  'label' => 'admin.col.kind'],
        'show_in_footer' => ['type' => 'check', 'label' => 'admin.col.footer'],
        'is_active'    => ['type' => 'toggle', 'label' => 'admin.col.status'],
    ];

    protected function sortableColumns(): array
    {
        return ['title_tr', 'sort_order', 'created_at'];
    }

    protected array $form = [
        'title_tr'  => ['type' => 'text', 'label' => 'Sayfa başlığı (TR)', 'rules' => 'required|string|max:200', 'col' => 6],
        'title_en'  => ['type' => 'text', 'label' => 'Page title (EN)', 'rules' => 'nullable|string|max:200', 'col' => 6],
        'slug'      => ['type' => 'slug', 'label' => 'URL slug', 'hint' => 'admin.hint.slug', 'col' => 6],
        'cover_image' => ['type' => 'image', 'label' => 'Kapak görseli', 'col' => 6],
        'excerpt_tr'=> ['type' => 'textarea', 'label' => 'Özet (TR)', 'col' => 12],
        'excerpt_en'=> ['type' => 'textarea', 'label' => 'Excerpt (EN)', 'col' => 12],
        'body_tr'   => ['type' => 'richtext', 'label' => 'İçerik (TR)', 'col' => 12],
        'body_en'   => ['type' => 'richtext', 'label' => 'Content (EN)', 'col' => 12],
        'meta_title_tr' => ['type' => 'text', 'label' => 'SEO başlık (TR)', 'col' => 6],
        'meta_title_en' => ['type' => 'text', 'label' => 'SEO title (EN)', 'col' => 6],
        'meta_desc_tr'  => ['type' => 'textarea', 'label' => 'SEO açıklama (TR)', 'col' => 6],
        'meta_desc_en'  => ['type' => 'textarea', 'label' => 'Meta description (EN)', 'col' => 6],
        'show_in_footer' => ['type' => 'checkbox', 'label' => 'Footer içinde göster', 'col' => 4],
        'show_in_menu'   => ['type' => 'checkbox', 'label' => 'Menüde göster', 'col' => 4],
        'is_active'      => ['type' => 'checkbox', 'label' => 'Yayında', 'col' => 4, 'default' => 1],
        'sort_order'     => ['type' => 'number', 'label' => 'Sıra numarası', 'col' => 4],
    ];
}
