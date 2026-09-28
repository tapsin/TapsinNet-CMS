<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use Core\Model;
use Core\Paginator;
use Core\Sanitizer;

/**
 * Ziyaretçi yorumları — admin onayıyla yayınlanır.
 */
final class Comment extends Model
{
    protected string $table = 'comments';
    protected bool $scopeActive = false;
    protected bool $softDeletes = true;
    protected string $homeOrder = 'created_at DESC';

    protected array $fillable = [
        'content_type', 'entity_id', 'parent_id', 'author_name', 'author_email',
        'body', 'locale', 'is_approved', 'is_spam', 'ip', 'user_agent',
    ];

    /** İçerik türleri — hangi tablolara yorum yapılabilir. */
    public const TYPES = [
        'project' => ['table' => 'projects', 'label' => 'İşler'],
        'news'    => ['table' => 'news',    'label' => 'Haberler'],
        'service' => ['table' => 'services', 'label' => 'Hizmetler'],
    ];

    /** Onaylı yorumlar. */
    public static function approvedFor(string $type, int $entityId, ?string $lang = null): array
    {
        $lang ??= \Core\Translator::lang();
        return Database::select(
            'SELECT * FROM comments
             WHERE content_type = :t AND entity_id = :e AND is_approved = 1
               AND is_spam = 0 AND deleted_at IS NULL AND locale = :l
             ORDER BY created_at ASC',
            ['t' => $type, 'e' => $entityId, 'l' => $lang]
        );
    }

    public static function countFor(string $type, int $entityId, ?string $lang = null): int
    {
        $lang ??= \Core\Translator::lang();
        return (int) Database::value(
            'SELECT COUNT(*) FROM comments
             WHERE content_type = :t AND entity_id = :e AND is_approved = 1
               AND is_spam = 0 AND deleted_at IS NULL AND locale = :l',
            ['t' => $type, 'e' => $entityId, 'l' => $lang],
            0
        );
    }

    public static function queue(string $filter = '', int $page = 1, ?int $perPage = null): Paginator
    {
        $where = [];
        if ($filter === 'pending') { $where['is_approved'] = 0; $where['is_spam'] = 0; }
        if ($filter === 'approved') { $where['is_approved'] = 1; }
        if ($filter === 'spam')  { $where['is_spam'] = 1; }

        $query = $filter !== '' ? ['filter' => $filter] : [];
        return static::paginate($where, [], ['created_at' => 'DESC'], $page, $perPage, $query);
    }

    public static function setApproved(int $id, bool $approved): void
    {
        Database::execute('UPDATE comments SET is_approved = :a, updated_at = :u WHERE id = :id',
            ['a' => $approved ? 1 : 0, 'u' => now(), 'id' => $id]);
    }

    public static function setSpam(int $id, bool $spam): void
    {
        Database::execute('UPDATE comments SET is_spam = :s, updated_at = :u WHERE id = :id',
            ['s' => $spam ? 1 : 0, 'u' => now(), 'id' => $id]);
    }

    /**
     * Yönetici notu.
     *
     * NOT: Str::textarea() diye bir metot YOK; doğrusu Sanitizer::textarea.
     * Yanlış çağrı yönetici notu kaydederken 500 veriyordu.
     */
    public static function saveNote(int $id, string $note): void
    {
        Database::execute('UPDATE comments SET admin_note = :n, updated_at = :u WHERE id = :id',
            ['n' => Sanitizer::textarea($note), 'u' => now(), 'id' => $id]);
    }

    /** Yorumun ait olduğu içeriğin başlığını çözer. */
    public static function entityLabel(array $comment): string
    {
        $type = (string) $comment['content_type'];
        $def  = self::TYPES[$type] ?? null;
        if ($def === null) {
            return (string) $comment['entity_id'];
        }
        $row = Database::first(
            "SELECT title_tr FROM {$def['table']} WHERE id = :id LIMIT 1",
            ['id' => (int) $comment['entity_id']]
        );
        return $row === null ? ('#' . $comment['entity_id']) : (string) $row['title_tr'];
    }

    public static function entityUrl(array $comment): ?string
    {
        $type = (string) $comment['content_type'];
        $def  = self::TYPES[$type] ?? null;
        if ($def === null) {
            return null;
        }
        $row = Database::first(
            "SELECT slug FROM {$def['table']} WHERE id = :id LIMIT 1",
            ['id' => (int) $comment['entity_id']]
        );
        if ($row === null) {
            return null;
        }
        $route = ['project' => 'isler', 'news' => 'haberler', 'service' => 'hizmetler'][$type] ?? '';
        return $route === '' ? null : module_url('projects' === $type ? 'projects' : ($type === 'news' ? 'news' : 'services'), ['id' => $row['slug']]);
    }
}
