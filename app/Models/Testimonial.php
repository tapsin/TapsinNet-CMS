<?php
declare(strict_types=1);

namespace Models;

/**
 * Müşteri referansları.
 */
final class Testimonial extends ContentModel
{
    protected string $table = 'testimonials';
    protected string $moduleSlug = 'testimonials';
    protected string $slugSource = 'author_name';
    protected array $searchColumns = ['author_name', 'author_title', 'author_company', 'quote_tr', 'quote_en'];
    protected string $homeOrder = 'sort_order ASC, created_at DESC';

    protected array $fillable = [
        'slug', 'author_name', 'author_title', 'author_company', 'company_url', 'author_avatar',
        'quote_tr', 'quote_en', 'rating', 'project_slug',
        'is_featured', 'is_active', 'sort_order',
    ];

    public static function averageRating(): float
    {
        $avg = \Core\Database::value(
            'SELECT AVG(rating) FROM testimonials WHERE is_active = 1 AND deleted_at IS NULL', [], 0
        );
        return round((float) $avg, 1);
    }
}
