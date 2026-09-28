<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use Core\Str;
use Core\Uploader;

/**
 * Görsel galeri.
 */
final class GalleryItem extends ContentModel
{
    protected string $table = 'gallery_items';
    protected string $moduleSlug = 'gallery';
    protected string $slugSource = 'title_tr';
    protected array $searchColumns = ['title_tr', 'title_en', 'caption_tr', 'caption_en', 'album', 'alt_text'];
    protected string $homeOrder = 'sort_order ASC, created_at DESC';

    protected array $fillable = [
        'slug', 'title_tr', 'title_en', 'caption_tr', 'caption_en', 'alt_text',
        'image_path', 'thumb_path', 'album', 'width', 'height',
        'is_featured', 'is_active', 'sort_order',
    ];

    /** Albüm grupları. */
    public static function albums(): array
    {
        $rows = Database::select(
            "SELECT album, COUNT(*) AS n FROM gallery_items
             WHERE album IS NOT NULL AND album != '' AND is_active = 1 AND deleted_at IS NULL
             GROUP BY album ORDER BY album ASC"
        );
        return array_map(static fn (array $r): array => [
            'key'   => (string) $r['album'],
            'label' => (string) $r['album'],
            'count' => (int) $r['n'],
        ], $rows);
    }

    public static function image(array $row): string
    {
        return (string) ($row['image_path'] ?? '');
    }

    public static function thumb(array $row): string
    {
        $t = (string) ($row['thumb_path'] ?? '');
        return $t !== '' ? $t : (string) ($row['image_path'] ?? '');
    }

    /** Yumuşak silmede dosyaları da temizler. */
    public static function deleteWithFiles(int $id): int
    {
        $row = static::findAny($id);
        if ($row !== null) {
            Uploader::delete($row['image_path'] ?? null);
            Uploader::delete($row['thumb_path'] ?? null);
        }
        return static::delete($id);
    }
}
