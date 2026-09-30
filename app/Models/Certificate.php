<?php
declare(strict_types=1);

namespace Models;

use Core\Str;

/**
 * Sertifikalar ve belgeler.
 */
final class Certificate extends ContentModel
{
    protected string $table = 'certificates';
    protected string $moduleSlug = 'certificates';
    protected string $slugSource = 'title_tr';
    protected array $searchColumns = ['title_tr', 'title_en', 'issuer', 'issuer_tr', 'issuer_en', 'credential_id'];
    protected string $homeOrder = 'sort_order ASC, issue_date DESC, created_at DESC';

    protected array $fillable = [
        'slug', 'title_tr', 'title_en', 'issuer', 'issuer_tr', 'issuer_en', 'credential_id',
        'issue_date', 'expiry_date', 'score', 'url', 'file_path', 'cover_image',
        'description_tr', 'description_en',
        'is_featured', 'is_active', 'sort_order',
    ];

    /** Süresi dolmuş / dolmak üzere olan belgeler. */
    public static function expiringSoon(int $days = 60): array
    {
        $limit = date('Y-m-d', strtotime("+{$days} days"));
        return static::list(['expiry_date <' => $limit], [], ['expiry_date' => 'ASC'], 20);
    }
}
