<?php
declare(strict_types=1);

namespace Models;

use Core\Paginator;
use Core\Str;

/**
 * Hizmetler.
 */
final class Service extends ContentModel
{
    protected string $table = 'services';
    protected string $moduleSlug = 'services';
    protected string $slugSource = 'title_tr';
    protected array $searchColumns = ['title_tr', 'title_en', 'excerpt_tr', 'excerpt_en', 'body_tr'];
    protected string $homeOrder = 'sort_order ASC, created_at DESC';

    protected array $fillable = [
        'slug', 'title_tr', 'title_en', 'excerpt_tr', 'excerpt_en', 'body_tr', 'body_en',
        'icon', 'price_from', 'duration', 'deliverables_tr', 'deliverables_en', 'link_url',
        'cover_image', 'meta_title_tr', 'meta_title_en', 'meta_desc_tr', 'meta_desc_en',
        'is_featured', 'is_active', 'sort_order',
    ];

    /** Teslim kalemleri listesi. */
    public static function deliverables(array $row, ?string $lang = null): array
    {
        $raw = (string) loc($row, 'deliverables', $lang, '');
        if (trim($raw) === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn (string $v): bool => $v !== ''));
    }

    public static function publicIndex(array $where, array $search, int $page, array $query = []): Paginator
    {
        return static::paginate($where, $search, ['sort_order' => 'ASC'], $page, null, $query);
    }
}
