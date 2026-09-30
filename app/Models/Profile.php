<?php
declare(strict_types=1);

namespace Models;

use Core\Str;

/**
 * Profiller — ekip, iş ortakları, müşteriler.
 */
final class Profile extends ContentModel
{
    protected string $table = 'profiles';
    protected string $moduleSlug = 'profiles';
    protected string $slugSource = 'name';
    protected array $searchColumns = ['name', 'title_tr', 'title_en', 'company', 'bio_tr', 'bio_en', 'location'];
    protected string $homeOrder = 'sort_order ASC, created_at DESC';

    protected array $fillable = [
        'slug', 'name', 'title_tr', 'title_en', 'company', 'bio_tr', 'bio_en', 'avatar',
        'email', 'phone', 'website', 'location', 'profile_type',
        'linkedin', 'github', 'instagram', 'twitter',
        'is_featured', 'is_active', 'sort_order',
    ];

    public const TYPES = ['team', 'client', 'partner', 'other'];

    public static function typeCounts(): array
    {
        $rows = \Core\Database::select(
            'SELECT profile_type, COUNT(*) AS n FROM profiles
             WHERE is_active = 1 AND deleted_at IS NULL GROUP BY profile_type'
        );
        $out = array_fill_keys(self::TYPES, 0);
        foreach ($rows as $r) {
            $out[(string) $r['profile_type']] = (int) $r['n'];
        }
        return $out;
    }

    /** Kart üzerindeki sosyal bağlantılar. */
    public static function socials(array $row): array
    {
        $map = [
            'linkedin'  => 'https://www.linkedin.com/in/',
            'github'    => 'https://github.com/',
            'instagram' => 'https://instagram.com/',
            'twitter'   => 'https://x.com/',
        ];
        $out = [];
        foreach ($map as $field => $base) {
            $handle = trim((string) ($row[$field] ?? ''));
            if ($handle === '') {
                continue;
            }
            $handle = str_starts_with($handle, 'http') ? $handle : $base . ltrim($handle, '@/');
            $out[$field] = $handle;
        }
        if (!empty($row['website'])) {
            $out['website'] = (string) $row['website'];
        }
        return $out;
    }
}
