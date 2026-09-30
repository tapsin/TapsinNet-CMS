<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Models\Service;

final class ServiceController extends ResourceController
{
    protected string $model = Service::class;
    protected string $moduleSlug = 'services';
    protected string $defaultSort = 'sort_order';
    protected string $defaultDir = 'ASC';
    protected string $singular = 'service';
    protected string $plural = 'services';

    protected array $columns = [
        'cover_image' => ['type' => 'image', 'label' => 'admin.col.image', 'width' => 56],
        'title_tr'    => ['type' => 'title', 'label' => 'admin.col.title'],
        'price_from'  => ['type' => 'text',  'label' => 'admin.col.price'],
        'icon'        => ['type' => 'text',  'label' => 'admin.col.icon'],
        'sort_order'  => ['type' => 'order', 'label' => 'admin.col.order'],
        'is_active'   => ['type' => 'toggle', 'label' => 'admin.col.status'],
    ];

    protected array $form = [
        'title_tr'  => ['type' => 'text', 'label' => 'Hizmet adı (TR)', 'rules' => 'required|string|max:180', 'col' => 6],
        'title_en'  => ['type' => 'text', 'label' => 'Service title (EN)', 'rules' => 'nullable|string|max:180', 'col' => 6],
        'slug'      => ['type' => 'slug', 'label' => 'URL slug', 'hint' => 'admin.hint.slug', 'col' => 4],
        'icon'      => ['type' => 'text', 'label' => 'İkon', 'hint' => 'admin.hint.icon', 'col' => 4],
        'price_from'=> ['type' => 'text', 'label' => 'Başlangıç fiyatı', 'col' => 4],
        'excerpt_tr'=> ['type' => 'textarea', 'label' => 'Kısa açıklama (TR)', 'rules' => 'nullable|string|max:400', 'col' => 6],
        'excerpt_en'=> ['type' => 'textarea', 'label' => 'Short description (EN)', 'rules' => 'nullable|string|max:400', 'col' => 6],
        'cover_image' => ['type' => 'image', 'label' => 'Kapak görseli', 'col' => 6],
        'body_tr'   => ['type' => 'richtext', 'label' => 'Detaylı açıklama (TR)', 'col' => 6],
        'body_en'   => ['type' => 'richtext', 'label' => 'Full description (EN)', 'col' => 6],
        'duration'  => ['type' => 'text', 'label' => 'Süre (örn. 2–3 hafta)', 'col' => 4],
        'link_url'  => ['type' => 'url',  'label' => 'Dış bağlantı', 'rules' => 'nullable|url|max:300', 'col' => 4],
        'deliverables_tr' => ['type' => 'tags', 'label' => 'Teslim kalemleri (TR)', 'hint' => 'admin.hint.tags', 'col' => 6],
        'deliverables_en' => ['type' => 'tags', 'label' => 'Deliverables (EN)', 'hint' => 'admin.hint.tags', 'col' => 6],
        'meta_title_tr' => ['type' => 'text', 'label' => 'SEO başlık (TR)', 'col' => 6],
        'meta_title_en' => ['type' => 'text', 'label' => 'SEO title (EN)', 'col' => 6],
        'meta_desc_tr'  => ['type' => 'textarea', 'label' => 'SEO açıklama (TR)', 'col' => 6],
        'meta_desc_en'  => ['type' => 'textarea', 'label' => 'Meta description (EN)', 'col' => 6],
        'is_featured'   => ['type' => 'checkbox', 'label' => 'Ana sayfada öne çıkar', 'col' => 4],
        'is_active'     => ['type' => 'checkbox', 'label' => 'Yayında', 'col' => 4, 'default' => 1],
        'sort_order'    => ['type' => 'number', 'label' => 'Sıra numarası', 'col' => 4],
    ];
}
