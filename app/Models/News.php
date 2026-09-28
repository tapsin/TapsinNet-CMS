<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use Core\Str;

/**
 * Haberler / blog yazıları.
 */
final class News extends ContentModel
{
    protected string $table = 'news';
    protected string $moduleSlug = 'news';
    protected string $slugSource = 'title_tr';
    protected array $searchColumns = ['title_tr', 'title_en', 'summary_tr', 'summary_en', 'body_tr', 'body_en', 'tags', 'category', 'author'];
    protected string $homeOrder = 'published_at DESC, created_at DESC';

    protected array $fillable = [
        'slug', 'title_tr', 'title_en', 'summary_tr', 'summary_en', 'body_tr', 'body_en',
        'author', 'category', 'tags', 'cover_image', 'published_at', 'read_minutes',
        'meta_title_tr', 'meta_title_en', 'meta_desc_tr', 'meta_desc_en',
        'is_featured', 'is_active', 'sort_order',
    ];

    /** Yayın tarihi boşsa oluşturma tarihini kullanır. */
    protected function applyTimestamps(array $data, bool $isCreate): array
    {
        $data = parent::applyTimestamps($data, $isCreate);
        if ($isCreate && empty($data['published_at'])) {
            $data['published_at'] = $data[$this->createdAtField];
        }
        return $data;
    }

    public static function categories(): array
    {
        $rows = Database::select(
            "SELECT category, COUNT(*) AS n FROM news
             WHERE category IS NOT NULL AND category != '' AND is_active = 1 AND deleted_at IS NULL
             GROUP BY category ORDER BY n DESC, category ASC"
        );
        return array_map(static fn (array $r): array => [
            'key'   => (string) $r['category'],
            'label' => (string) $r['category'],
            'count' => (int) $r['n'],
        ], $rows);
    }

    /** Okuma süresi tahmini (dakika). */
    public static function estimateReadTime(?string $html): int
    {
        $words = str_word_count(strip_tags((string) $html), 0, 'UTF-8');
        return max(1, (int) ceil($words / 180));
    }

    public static function countViews(int $id): void
    {
        Database::execute('UPDATE news SET views = views + 1 WHERE id = :id', ['id' => $id]);
    }

    public static function archives(): array
    {
        $rows = Database::select(
            "SELECT strftime('%Y-%m', COALESCE(published_at, created_at)) AS ym, COUNT(*) AS n
             FROM news WHERE is_active = 1 AND deleted_at IS NULL
             GROUP BY ym ORDER BY ym DESC LIMIT 24"
        );
        return array_map(static fn (array $r): array => [
            'key'   => (string) $r['ym'],
            'count' => (int) $r['n'],
        ], $rows);
    }
}
