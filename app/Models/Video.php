<?php
declare(strict_types=1);

namespace Models;

use Core\Database;

/**
 * Video galeri — YouTube / Vimeo embed.
 * Ham HTML saklanmaz; sadece sağlayıcı + video kimliği tutulur.
 */
final class Video extends ContentModel
{
    protected string $table = 'videos';
    protected string $moduleSlug = 'videos';
    protected string $slugSource = 'title_tr';
    protected array $searchColumns = ['title_tr', 'title_en', 'description_tr', 'description_en', 'album'];
    protected string $homeOrder = 'sort_order ASC, created_at DESC';

    protected array $fillable = [
        'slug', 'title_tr', 'title_en', 'description_tr', 'description_en',
        'provider', 'video_id', 'watch_url', 'cover_image', 'duration', 'album',
        'is_featured', 'is_active', 'sort_order',
    ];

    public static function albums(): array
    {
        $rows = Database::select(
            "SELECT album, COUNT(*) AS n FROM videos
             WHERE album IS NOT NULL AND album != '' AND is_active = 1 AND deleted_at IS NULL
             GROUP BY album ORDER BY album ASC"
        );
        return array_map(static fn (array $r): array => [
            'key'   => (string) $r['album'],
            'label' => (string) $r['album'],
            'count' => (int) $r['n'],
        ], $rows);
    }

    /** Gönderimde sağlayıcıyı ve kimliği URL'den çıkarır. */
    public static function extractProvider(string $input): array
    {
        $input = trim($input);

        if (($id = vimeo_id($input)) !== null) {
            return ['provider' => 'vimeo', 'video_id' => $id];
        }
        if (($id = youtube_id($input)) !== null) {
            return ['provider' => 'youtube', 'video_id' => $id];
        }
        return ['provider' => 'youtube', 'video_id' => ''];
    }

    public static function countViews(int $id): void
    {
        Database::execute('UPDATE videos SET views = views + 1 WHERE id = :id', ['id' => $id]);
    }

    public static function totalViews(): int
    {
        return (int) Database::value('SELECT COALESCE(SUM(views), 0) FROM videos', [], 0);
    }
}
