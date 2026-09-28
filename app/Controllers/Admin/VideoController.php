<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Models\Video;
use Core\Uploader;

final class VideoController extends ResourceController
{
    protected string $model = Video::class;
    protected string $moduleSlug = 'videos';
    protected string $defaultSort = 'sort_order';
    protected string $defaultDir = 'ASC';
    protected string $singular = 'video';
    protected string $plural = 'videos';

    protected array $columns = [
        'cover_image' => ['type' => 'image', 'label' => 'admin.col.image', 'width' => 56],
        'title_tr'    => ['type' => 'title', 'label' => 'admin.col.title'],
        'provider'    => ['type' => 'text',  'label' => 'admin.col.provider'],
        'album'       => ['type' => 'text',  'label' => 'admin.col.album'],
        'views'       => ['type' => 'number', 'label' => 'admin.col.views'],
        'is_active'   => ['type' => 'toggle', 'label' => 'admin.col.status'],
    ];

    protected function sortableColumns(): array
    {
        return ['title_tr', 'provider', 'views', 'sort_order', 'created_at'];
    }

    protected function filters(): array
    {
        return ['album' => ['album', '='], 'provider' => ['provider', '=']];
    }

    protected function filterOptions(): array
    {
        return [
            'album'    => Video::albums(),
            'provider' => [
                ['key' => 'youtube', 'label' => 'YouTube', 'count' => 0],
                ['key' => 'vimeo',   'label' => 'Vimeo',   'count' => 0],
            ],
        ];
    }

    protected array $form = [
        'title_tr'       => ['type' => 'text', 'label' => 'Video başlığı (TR)', 'rules' => 'required|string|max:200', 'col' => 6],
        'title_en'       => ['type' => 'text', 'label' => 'Video title (EN)', 'rules' => 'nullable|string|max:200', 'col' => 6],
        'slug'           => ['type' => 'slug', 'label' => 'URL slug', 'hint' => 'admin.hint.slug', 'col' => 4],
        'video_url'      => ['type' => 'text', 'label' => 'Video bağlantısı veya kimliği', 'hint' => 'admin.hint.video', 'rules' => 'required', 'col' => 8],
        'cover_image'    => ['type' => 'image', 'label' => 'Kapak görseli', 'hint' => 'admin.hint.cover', 'col' => 6],
        'duration'       => ['type' => 'text', 'label' => 'Süre (örn. 4:32)', 'col' => 3],
        'album'          => ['type' => 'text', 'label' => 'Kategori / albüm', 'col' => 3],
        'description_tr' => ['type' => 'textarea', 'label' => 'Açıklama (TR)', 'col' => 6],
        'description_en' => ['type' => 'textarea', 'label' => 'Description (EN)', 'col' => 6],
        'is_featured'    => ['type' => 'checkbox', 'label' => 'Ana sayfada göster', 'col' => 4],
        'is_active'      => ['type' => 'checkbox', 'label' => 'Yayında', 'col' => 4, 'default' => 1],
        'sort_order'     => ['type' => 'number', 'label' => 'Sıra numarası', 'col' => 4],
    ];

    protected function buildData(?array $existing = null): array
    {
        $data = parent::buildData($existing);

        // Formdaki ham bağlantıyı provider + id olarak ayır
        $input = trim((string) $this->request->input('video_url', ''));
        if ($input !== '') {
            $extracted = Video::extractProvider($input);
            $data['provider']  = $extracted['provider'];
            $data['video_id']  = $extracted['video_id'];
            $data['watch_url'] = $input;
        } elseif ($existing !== null) {
            $data['provider']  = $existing['provider'] ?? 'youtube';
            $data['video_id']  = $existing['video_id'] ?? '';
            $data['watch_url'] = $existing['watch_url'] ?? null;
        }

        return $data;
    }

    protected function validateData(array $data, ?array $existing = null): array
    {
        if (empty($data['video_id'])) {
            return [[], t('admin.err.invalid_video')];
        }
        return parent::validateData($data, $existing);
    }

    protected function beforeDelete(array $row): void
    {
        Uploader::delete($row['cover_image'] ?? null);
    }
}
