<?php
declare(strict_types=1);

namespace Models;

use Core\Config;
use Core\Database;
use Core\Model;
use Core\ModuleRegistry;

/**
 * Modül durumları.
 */
final class Module extends Model
{
    protected string $table = 'modules';
    protected bool $scopeActive = false;
    protected bool $softDeletes = false;
    protected string $homeOrder = 'sort_order ASC';

    protected array $fillable = ['slug', 'is_active', 'sort_order'];

    /**
     * config/modules.php tanımlarıyla tablodaki durumları birleştirir.
     * @return array<int,array>
     */
    public static function catalog(): array
    {
        $rows = [];
        try {
            foreach (Database::select('SELECT * FROM modules') as $r) {
                $rows[(string) $r['slug']] = $r;
            }
        } catch (\Throwable) {
            $rows = [];
        }

        $out = [];
        foreach (Config::get('modules', []) as $slug => $def) {
            $row = $rows[$slug] ?? null;
            $out[] = [
                'slug'        => $slug,
                'name'        => $def['name'],
                'desc'        => $def['desc'] ?? ['tr' => '', 'en' => ''],
                'icon_glyph'  => $def['icon_glyph'] ?? '●',
                'group'       => $def['group'] ?? 'content',
                'route'       => $def['route'] ?? null,
                'admin'       => $def['admin'] ?? null,
                'show_home'   => (bool) ($def['show_home'] ?? false),
                'admin_only'  => (bool) ($def['admin_only'] ?? false),
                'is_active'   => $row !== null ? (int) $row['is_active'] === 1 : true,
                'sort_order'  => $row !== null ? (int) $row['sort_order'] : 0,
                'id'          => $row !== null ? (int) $row['id'] : null,
            ];
        }

        usort($out, static fn (array $a, array $b): int => $a['sort_order'] <=> $b['sort_order'] ?: strcmp($a['slug'], $b['slug']));
        return $out;
    }

    /** Bir modülün içerik sayısı. */
    public static function contentCount(string $slug): ?int
    {
        $table = ModuleRegistry::table($slug);
        if ($table === null) {
            return null;
        }
        try {
            return (int) Database::value(
                "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL", [], 0
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /** Modülü aktif/pasif yapar. (Core\Model::setActive ile imza çakışmaması için adlandırıldı.) */
    public static function setModuleActive(string $slug, bool $active): bool
    {
        return ModuleRegistry::toggle($slug, $active);
    }

    public static function setOrder(string $slug, int $order): void
    {
        Database::execute(
            'UPDATE modules SET sort_order = :s, updated_at = :u WHERE slug = :g',
            ['s' => $order, 'u' => now(), 'g' => $slug]
        );
        ModuleRegistry::flush();
    }
}
