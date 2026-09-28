<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Models\GalleryItem;

final class GalleryController extends ResourceController
{
    protected string $model = GalleryItem::class;
    protected string $moduleSlug = 'gallery';
    protected string $defaultSort = 'sort_order';
    protected string $defaultDir = 'ASC';
    protected string $singular = 'photo';
    protected string $plural = 'gallery';

    protected array $columns = [
        'image_path' => ['type' => 'image', 'label' => 'admin.col.image', 'width' => 56],
        'title_tr'   => ['type' => 'title', 'label' => 'admin.col.title'],
        'album'      => ['type' => 'text',  'label' => 'admin.col.album'],
        'is_active'  => ['type' => 'toggle', 'label' => 'admin.col.status'],
    ];

    protected function sortableColumns(): array
    {
        return ['title_tr', 'album', 'sort_order', 'created_at'];
    }

    protected function filters(): array
    {
        return ['album' => ['album', '=']];
    }

    protected function filterOptions(): array
    {
        return ['album' => GalleryItem::albums()];
    }

    protected array $form = [
        'title_tr'   => ['type' => 'text', 'label' => 'Başlık (TR)', 'rules' => 'nullable|string|max:180', 'col' => 6],
        'title_en'   => ['type' => 'text', 'label' => 'Title (EN)', 'rules' => 'nullable|string|max:180', 'col' => 6],
        'slug'       => ['type' => 'slug', 'label' => 'URL slug', 'hint' => 'admin.hint.slug', 'col' => 6],
        'album'      => ['type' => 'text', 'label' => 'Albüm / etiket', 'col' => 6],
        'image_path' => ['type' => 'image', 'label' => 'Görsel', 'rules' => 'required', 'col' => 6],
        'alt_text'   => ['type' => 'text', 'label' => 'Alt metin (erişilebilirlik)', 'hint' => 'admin.hint.alt', 'col' => 6],
        'caption_tr' => ['type' => 'textarea', 'label' => 'Açıklama (TR)', 'col' => 6],
        'caption_en' => ['type' => 'textarea', 'label' => 'Caption (EN)', 'col' => 6],
        'is_featured'=> ['type' => 'checkbox', 'label' => 'Ana sayfada göster', 'col' => 4],
        'is_active'  => ['type' => 'checkbox', 'label' => 'Yayında', 'col' => 4, 'default' => 1],
        'sort_order' => ['type' => 'number', 'label' => 'Sıra numarası', 'col' => 4],
    ];

    protected function transform(array $data, ?array $existing): array
    {
        // Görsel zorunlu: eski dosya silinmişse hata
        if (empty($data['image_path']) && ($existing === null || empty($existing['image_path']))) {
            \Core\Session::flash('error', t('upload.required', ['field' => t('admin.col.image')]));
        }
        return $data;
    }

    protected function beforeDelete(array $row): void
    {
        \Core\Uploader::delete($row['image_path'] ?? null);
        \Core\Uploader::delete($row['thumb_path'] ?? null);
    }
}
