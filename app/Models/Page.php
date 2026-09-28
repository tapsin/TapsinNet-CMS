<?php
declare(strict_types=1);

namespace Models;

/**
 * Kurumsal / yasal sayfalar.
 */
final class Page extends ContentModel
{
    protected string $table = 'pages';
    protected string $moduleSlug = 'pages';
    protected string $slugSource = 'title_tr';
    protected array $searchColumns = ['title_tr', 'title_en', 'body_tr', 'body_en'];
    protected string $homeOrder = 'sort_order ASC, created_at ASC';

    protected array $fillable = [
        'slug', 'title_tr', 'title_en', 'body_tr', 'body_en', 'excerpt_tr', 'excerpt_en',
        'cover_image', 'is_system', 'show_in_footer', 'show_in_menu',
        'meta_title_tr', 'meta_title_en', 'meta_desc_tr', 'meta_desc_en',
        'is_active', 'sort_order',
    ];

    /** Alt menüde gösterilecek aktif sayfalar. */
    public static function menuPages(): array
    {
        return static::list(['show_in_menu' => 1], [], ['sort_order' => 'ASC']);
    }

    /** Footer'da gösterilecek aktif sayfalar (KVKK, gizlilik, çerez…). */
    public static function footerPages(): array
    {
        return static::list(['show_in_footer' => 1], [], ['sort_order' => 'ASC']);
    }

    /** Sistem sayfası (KVKK/gizlilik/çerez) silinemez. */
    public static function delete(int $id): int
    {
        $row = static::findAny($id);
        if ($row !== null && (int) ($row['is_system'] ?? 0) === 1) {
            return 0;
        }
        return parent::delete($id);
    }
}
