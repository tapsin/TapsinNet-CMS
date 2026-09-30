<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Models\Testimonial;
use Core\Uploader;

final class TestimonialController extends ResourceController
{
    protected string $model = Testimonial::class;
    protected string $moduleSlug = 'testimonials';
    protected string $defaultSort = 'sort_order';
    protected string $defaultDir = 'ASC';
    protected string $singular = 'testimonial';
    protected string $plural = 'testimonials';

    protected array $columns = [
        'author_avatar' => ['type' => 'avatar', 'label' => 'admin.col.avatar'],
        'author_name'   => ['type' => 'title',  'label' => 'admin.col.name'],
        'author_company'=> ['type' => 'text',   'label' => 'admin.col.company'],
        'rating'        => ['type' => 'rating', 'label' => 'admin.col.rating'],
        'is_active'     => ['type' => 'toggle', 'label' => 'admin.col.status'],
    ];

    protected function sortableColumns(): array
    {
        return ['author_name', 'author_company', 'rating', 'sort_order', 'created_at'];
    }

    protected function stats(): array
    {
        $s = parent::stats();
        $s['avg'] = Testimonial::averageRating();
        return $s;
    }

    protected array $form = [
        'author_name'    => ['type' => 'text', 'label' => 'Ad Soyad', 'rules' => 'required|string|max:160', 'col' => 6],
        'author_title'   => ['type' => 'text', 'label' => 'Ünvan', 'col' => 6],
        'author_company' => ['type' => 'text', 'label' => 'Şirket', 'col' => 6],
        'company_url'    => ['type' => 'url',  'label' => 'Şirket bağlantısı', 'rules' => 'nullable|url|max:300', 'col' => 6],
        'author_avatar'  => ['type' => 'image', 'label' => 'Fotoğraf', 'col' => 6],
        'rating'         => ['type' => 'select', 'label' => 'Puan', 'rules' => 'required', 'col' => 6,
                             'options' => ['5' => '★★★★★ 5', '4' => '★★★★ 4', '3' => '★★★ 3', '2' => '★★ 2', '1' => '★ 1']],
        'quote_tr'       => ['type' => 'textarea', 'label' => 'Yorum metni (TR)', 'rules' => 'required|string|max:900', 'col' => 12],
        'quote_en'       => ['type' => 'textarea', 'label' => 'Quote (EN)', 'rules' => 'nullable|string|max:900', 'col' => 12],
        'project_slug'   => ['type' => 'text', 'label' => 'İlgili iş (slug)', 'hint' => 'admin.hint.project_slug', 'col' => 6],
        'is_featured'    => ['type' => 'checkbox', 'label' => 'Ana sayfada göster', 'col' => 4],
        'is_active'      => ['type' => 'checkbox', 'label' => 'Yayında', 'col' => 4, 'default' => 1],
        'sort_order'     => ['type' => 'number', 'label' => 'Sıra numarası', 'col' => 4],
    ];

    protected function beforeDelete(array $row): void
    {
        Uploader::delete($row['author_avatar'] ?? null);
    }
}
