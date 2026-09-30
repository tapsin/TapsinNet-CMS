<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Models\SocialPost;
use Core\Uploader;

final class SocialController extends ResourceController
{
    protected string $model = SocialPost::class;
    protected string $moduleSlug = 'social';
    protected string $defaultSort = 'sort_order';
    protected string $defaultDir = 'ASC';
    protected string $singular = 'post';
    protected string $plural = 'social';

    protected array $columns = [
        'media_path'  => ['type' => 'image', 'label' => 'admin.col.image', 'width' => 56],
        'caption_tr'  => ['type' => 'title', 'label' => 'admin.col.caption'],
        'provider'    => ['type' => 'text',  'label' => 'admin.col.provider'],
        'sort_order'  => ['type' => 'order', 'label' => 'admin.col.order'],
        'is_active'   => ['type' => 'toggle', 'label' => 'admin.col.status'],
    ];

    protected function sortableColumns(): array
    {
        return ['caption_tr', 'provider', 'sort_order', 'created_at'];
    }

    protected function filters(): array
    {
        return ['provider' => ['provider', '=']];
    }

    protected function filterOptions(): array
    {
        $out = [];
        foreach (SocialPost::PROVIDERS as $p) {
            $out[] = ['key' => $p, 'label' => ucfirst($p), 'count' => 0];
        }
        return ['provider' => $out];
    }

    protected array $form = [
        'caption_tr' => ['type' => 'textarea', 'label' => 'Açıklama (TR)', 'rules' => 'required|string|max:500', 'col' => 12],
        'caption_en' => ['type' => 'textarea', 'label' => 'Caption (EN)', 'rules' => 'nullable|string|max:500', 'col' => 12],
        'provider'   => ['type' => 'select', 'label' => 'Platform', 'rules' => 'required', 'col' => 4,
                         'options' => ['instagram' => 'Instagram', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'x' => 'X']],
        'permalink'  => ['type' => 'url', 'label' => 'Gönderi bağlantısı', 'rules' => 'required|url|max:400', 'col' => 8],
        'media_path' => ['type' => 'image', 'label' => 'Kapak görseli', 'hint' => 'admin.hint.social_media', 'col' => 6],
        'external_thumb' => ['type' => 'text', 'label' => 'Dış kapak görseli (https://…)', 'col' => 6],
        'is_featured'=> ['type' => 'checkbox', 'label' => 'Ana sayfada göster', 'col' => 4],
        'is_active'  => ['type' => 'checkbox', 'label' => 'Yayında', 'col' => 4, 'default' => 1],
        'sort_order' => ['type' => 'number', 'label' => 'Sıra numarası', 'col' => 4],
    ];

    /** Ham embed HTML saklanmaz — embed_id bağlantıdan türetilir. */
    protected function transform(array $data, ?array $existing): array
    {
        $permalink = (string) ($data['permalink'] ?? '');
        $provider  = (string) ($data['provider'] ?? 'instagram');

        $data['embed_id'] = $provider === 'instagram'
            ? (SocialPost::instagramId($permalink) ?? '')
            : (($data['external_thumb'] ?? '') === '' ? youtube_id($permalink) : '');

        // Dış görsel yalnızca https ve izinli host olmalı
        $thumb = (string) ($data['external_thumb'] ?? '');
        if ($thumb !== '' && !preg_match('#^https://[a-z0-9.\-]+/.+#i', $thumb)) {
            $data['external_thumb'] = null;
        } else {
            $data['external_thumb'] = $thumb !== '' ? $thumb : null;
        }

        return $data;
    }

    protected function beforeDelete(array $row): void
    {
        Uploader::delete($row['media_path'] ?? null);
    }
}
