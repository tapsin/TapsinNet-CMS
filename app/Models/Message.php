<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use Core\Model;
use Core\Paginator;
use Core\Str;

/**
 * İletişim formu mesajları.
 */
final class Message extends Model
{
    protected string $table = 'messages';
    protected bool $scopeActive = false;
    protected bool $softDeletes = false;
    protected string $homeOrder = 'created_at DESC';

    protected array $fillable = [
        'name', 'email', 'phone', 'subject', 'message', 'source_page',
        'module', 'locale', 'ip', 'user_agent',
    ];

    public static function unreadCount(): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM messages WHERE is_read = 0 AND is_archived = 0', [], 0
        );
    }

    public static function pendingCommentsCount(): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM comments WHERE is_approved = 0 AND is_spam = 0 AND deleted_at IS NULL', [], 0
        );
    }

    /** Gelen kutusu — arama + durum filtresi ile sayfalanır. */
    public static function inbox(string $filter = '', string $term = '', int $page = 1, ?int $perPage = null): Paginator
    {
        $where  = [];
        $search = [];

        if ($filter === 'unread')    { $where['is_read'] = 0; $where['is_archived'] = 0; }
        if ($filter === 'read')      { $where['is_read'] = 1; $where['is_archived'] = 0; }
        if ($filter === 'starred')   { $where['is_starred'] = 1; }
        if ($filter === 'archived')  { $where['is_archived'] = 1; }

        if ($term !== '') {
            foreach (['name', 'email', 'subject', 'message'] as $col) {
                $search[$col] = ['op' => 'like', 'value' => $term];
            }
        }

        $query = array_filter([
            'filter' => $filter !== '' ? $filter : null,
            'q'      => $term !== '' ? $term : null,
        ]);

        return static::paginate($where, $search, ['created_at' => 'DESC'], $page, $perPage ?? (int) \Core\Config::get('app.admin_per_page', 20), $query);
    }

    public static function markRead(int $id, bool $read = true): void
    {
        Database::execute('UPDATE messages SET is_read = :r, updated_at = :u WHERE id = :id',
            ['r' => $read ? 1 : 0, 'u' => now(), 'id' => $id]);
    }

    public static function markAllRead(): int
    {
        return Database::execute(
            'UPDATE messages SET is_read = 1, updated_at = :u WHERE is_read = 0',
            ['u' => now()]
        );
    }

    public static function setArchived(int $id, bool $archived): void
    {
        Database::execute('UPDATE messages SET is_archived = :a, updated_at = :u WHERE id = :id',
            ['a' => $archived ? 1 : 0, 'u' => now(), 'id' => $id]);
    }

    public static function setStarred(int $id, bool $starred): void
    {
        Database::execute('UPDATE messages SET is_starred = :s, updated_at = :u WHERE id = :id',
            ['s' => $starred ? 1 : 0, 'u' => now(), 'id' => $id]);
    }

    public static function saveNote(int $id, string $note): void
    {
        Database::execute('UPDATE messages SET admin_note = :n, updated_at = :u WHERE id = :id',
            ['n' => Str::textarea($note), 'u' => now(), 'id' => $id]);
    }

    /** Yeni mesaj geldi bildirimi (üst bar rozeti). */
    public static function badge(): array
    {
        return [
            'messages' => self::unreadCount(),
            'comments' => self::pendingCommentsCount(),
        ];
    }
}
