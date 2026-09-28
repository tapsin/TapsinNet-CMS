<?php
declare(strict_types=1);

namespace Models;

use Core\Uploader;

/**
 * Sosyal medya akışı.
 * Ham embed HTML saklanmaz; görünüm embed URL'sini sağlayıcıya göre kurar.
 */
final class SocialPost extends ContentModel
{
    protected string $table = 'social_posts';
    protected string $moduleSlug = 'social';
    protected string $slugSource = 'caption_tr';
    protected array $searchColumns = ['caption_tr', 'caption_en', 'permalink'];
    protected string $homeOrder = 'sort_order ASC, created_at DESC';

    protected array $fillable = [
        'slug', 'provider', 'permalink', 'embed_id', 'caption_tr', 'caption_en',
        'media_path', 'external_thumb',
        'is_featured', 'is_active', 'sort_order',
    ];

    public const PROVIDERS = ['instagram', 'youtube', 'linkedin', 'x'];

    /** Instagram shortcode / gönderi kimliğini çıkarır. */
    public static function instagramId(string $url): ?string
    {
        if (preg_match('#instagram\.com/(?:p|reel|tv)/([A-Za-z0-9_-]+)#', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    public static function embedUrl(array $row): ?string
    {
        $provider = (string) ($row['provider'] ?? 'instagram');
        $id       = (string) ($row['embed_id'] ?? '');

        return match ($provider) {
            'instagram' => $id !== ''
                ? 'https://www.instagram.com/p/' . rawurlencode($id) . '/embed'
                : null,
            'youtube'   => $id !== ''
                ? 'https://www.youtube-nocookie.com/embed/' . rawurlencode($id)
                : null,
            'linkedin'  => null,
            'x'         => null,
            default     => null,
        };
    }

    public static function deleteWithFiles(int $id): int
    {
        $row = static::findAny($id);
        if ($row !== null) {
            Uploader::delete($row['media_path'] ?? null);
        }
        return static::delete($id);
    }
}
