<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Models\News;
use Core\Uploader;

final class NewsController extends ResourceController
{
    protected string $model = News::class;
    protected string $moduleSlug = 'news';
    protected string $defaultSort = 'published_at';
    protected string $defaultDir = 'DESC';
    protected string $singular = 'article';
    protected string $plural = 'news';

    protected array $columns = [
        'cover_image'  => ['type' => 'image', 'label' => 'admin.col.image', 'width' => 56],
        'title_tr'     => ['type' => 'title', 'label' => 'admin.col.title'],
        'category'     => ['type' => 'text',  'label' => 'admin.col.category'],
        'published_at' => ['type' => 'date',  'label' => 'admin.col.published'],
        'views'        => ['type' => 'number', 'label' => 'admin.col.views'],
        'is_active'    => ['type' => 'toggle', 'label' => 'admin.col.status'],
    ];

    protected function sortableColumns(): array
    {
        return ['title_tr', 'category', 'published_at', 'views', 'created_at'];
    }

    protected function filters(): array
    {
        return ['kategori' => ['category', '='], 'yil' => ['published_at', 'LIKE']];
    }

    protected function filterOptions(): array
    {
        return ['kategori' => News::categories()];
    }

    protected array $form = [
        'title_tr'   => ['type' => 'text', 'label' => 'Başlık (TR)', 'rules' => 'required|string|max:220', 'col' => 12],
        'title_en'   => ['type' => 'text', 'label' => 'Title (EN)', 'rules' => 'nullable|string|max:220', 'col' => 12],
        'slug'       => ['type' => 'slug', 'label' => 'URL slug', 'hint' => 'admin.hint.slug', 'col' => 6],
        'category'   => ['type' => 'text', 'label' => 'Kategori', 'col' => 6],
        'summary_tr' => ['type' => 'textarea', 'label' => 'Özet (TR)', 'rules' => 'nullable|string|max:400', 'col' => 6],
        'summary_en' => ['type' => 'textarea', 'label' => 'Summary (EN)', 'rules' => 'nullable|string|max:400', 'col' => 6],
        'cover_image'=> ['type' => 'image', 'label' => 'Kapak görseli', 'col' => 6],
        'author'     => ['type' => 'text', 'label' => 'Yazar', 'col' => 6],
        'body_tr'    => ['type' => 'richtext', 'label' => 'İçerik (TR)', 'col' => 12],
        'body_en'    => ['type' => 'richtext', 'label' => 'Content (EN)', 'col' => 12],
        'tags'       => ['type' => 'tags', 'label' => 'Etiketler', 'hint' => 'admin.hint.tags', 'col' => 6],
        'published_at' => ['type' => 'date', 'label' => 'Yayın tarihi', 'col' => 6],
        'meta_title_tr' => ['type' => 'text', 'label' => 'SEO başlık (TR)', 'col' => 6],
        'meta_title_en' => ['type' => 'text', 'label' => 'SEO title (EN)', 'col' => 6],
        'meta_desc_tr'  => ['type' => 'textarea', 'label' => 'SEO açıklama (TR)', 'col' => 6],
        'meta_desc_en'  => ['type' => 'textarea', 'label' => 'Meta description (EN)', 'col' => 6],
        'is_featured'   => ['type' => 'checkbox', 'label' => 'Öne çıkan haber', 'col' => 4],
        'is_active'     => ['type' => 'checkbox', 'label' => 'Yayında', 'col' => 4, 'default' => 1],
        'sort_order'    => ['type' => 'number', 'label' => 'Sıra numarası', 'col' => 4],
    ];

    protected function transform(array $data, ?array $existing): array
    {
        if (empty($data['published_at'])) {
            $data['published_at'] = $existing['published_at'] ?? $data['published_at'] ?? now();
        }
        if (empty($data['read_minutes'])) {
            $data['read_minutes'] = News::estimateReadTime($data['body_tr'] ?? '');
        }
        return $data;
    }

    protected function beforeDelete(array $row): void
    {
        Uploader::delete($row['cover_image'] ?? null);
    }
}
