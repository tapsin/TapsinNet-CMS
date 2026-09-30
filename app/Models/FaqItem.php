<?php
declare(strict_types=1);

namespace Models;

use Core\Database;

/**
 * Sıkça sorulan sorular.
 */
final class FaqItem extends ContentModel
{
    protected string $table = 'faq_items';
    protected string $moduleSlug = 'faq';
    protected string $slugSource = 'question_tr';
    protected array $searchColumns = ['question_tr', 'question_en', 'answer_tr', 'answer_en', 'category'];
    protected string $homeOrder = 'sort_order ASC, created_at ASC';

    protected array $fillable = [
        'slug', 'question_tr', 'question_en', 'answer_tr', 'answer_en', 'category',
        'is_featured', 'is_active', 'sort_order',
    ];

    public static function categories(): array
    {
        $rows = Database::select(
            "SELECT category, COUNT(*) AS n FROM faq_items
             WHERE category IS NOT NULL AND category != '' AND is_active = 1 AND deleted_at IS NULL
             GROUP BY category ORDER BY category ASC"
        );
        return array_map(static fn (array $r): array => [
            'key'   => (string) $r['category'],
            'label' => (string) $r['category'],
            'count' => (int) $r['n'],
        ], $rows);
    }
}
