<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use Core\Paginator;
use Core\Str;

/**
 * İşler / portföy projeleri.
 */
final class Project extends ContentModel
{
    protected string $table = 'projects';
    protected string $moduleSlug = 'projects';
    protected string $slugSource = 'title_tr';
    protected array $searchColumns = ['title_tr', 'title_en', 'summary_tr', 'summary_en', 'client', 'tags', 'category', 'tech_stack'];
    protected string $homeOrder = 'created_at DESC, id DESC';

    protected array $fillable = [
        'slug', 'title_tr', 'title_en', 'summary_tr', 'summary_en', 'body_tr', 'body_en',
        'client', 'client_url', 'category', 'tags', 'location', 'project_url', 'project_date',
        'completed_at', 'cover_image', 'gallery', 'tech_stack', 'duration',
        'meta_title_tr', 'meta_title_en', 'meta_desc_tr', 'meta_desc_en',
        'is_featured', 'is_active', 'sort_order',
    ];

    /** Kategori listesi — kayıtlardan türetilir. */
    public static function categories(): array
    {
        $rows = Database::select(
            "SELECT category, COUNT(*) AS n FROM projects
             WHERE category IS NOT NULL AND category != '' AND is_active = 1 AND deleted_at IS NULL
             GROUP BY category ORDER BY n DESC, category ASC"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['key' => (string) $r['category'], 'label' => \Core\Str::slug((string) $r['category'], ' '), 'count' => (int) $r['n']];
        }
        return $out;
    }

    public static function categoriesWithLabels(): array
    {
        return self::categories();
    }

    /** Etiket listesi. */
    public static function tags(): array
    {
        $rows = Database::select(
            "SELECT tags FROM projects WHERE tags IS NOT NULL AND tags != '' AND is_active = 1 AND deleted_at IS NULL"
        );
        $counts = [];
        foreach ($rows as $r) {
            foreach (static::tagList((string) $r['tags']) as $t) {
                $counts[$t] = ($counts[$t] ?? 0) + 1;
            }
        }
        arsort($counts);
        return array_map(static fn (string $k, int $n): array => ['key' => $k, 'count' => $n], array_keys($counts), array_values($counts));
    }

    /** Proje galeri görselleri. */
    public static function gallery(array $row): array
    {
        $raw = (string) ($row['gallery'] ?? '');
        if (trim($raw) === '') {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $item) {
            if (is_string($item) && $item !== '') {
                $out[] = $item;
            }
        }
        return $out;
    }

    public static function saveGallery(int $id, array $paths): void
    {
        Database::execute(
            'UPDATE projects SET gallery = :g, updated_at = :u WHERE id = :id',
            ['g' => json_encode(array_values($paths), JSON_UNESCAPED_SLASHES), 'u' => now(), 'id' => $id]
        );
    }

    public static function countViews(int $id): void
    {
        Database::execute('UPDATE projects SET views = views + 1 WHERE id = :id', ['id' => $id]);
    }

    public static function related(int $id, string $category, int $limit = 3): array
    {
        return static::list(
            ['category' => $category],
            [],
            ['created_at' => 'DESC'],
            $limit
        );
    }

    public static function homeRail(int $limit): array
    {
        if (!\Core\ModuleRegistry::isActive('projects')) {
            return [];
        }
        return static::list([], [], [], $limit);
    }
}
