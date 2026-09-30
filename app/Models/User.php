<?php
declare(strict_types=1);

namespace Models;

use Core\Auth;
use Core\Database;
use Core\Model;

/**
 * Yönetici kullanıcıları.
 */
final class User extends Model
{
    protected string $table = 'users';
    protected bool $scopeActive = false;
    protected bool $softDeletes = true;
    protected string $homeOrder = 'created_at ASC';

    protected array $fillable = ['name', 'username', 'email', 'password_hash', 'role', 'avatar', 'is_active'];

    public static function findByEmail(string $email): ?array
    {
        return Database::first(
            'SELECT * FROM users WHERE email = :e AND deleted_at IS NULL LIMIT 1',
            ['e' => mb_strtolower(trim($email))]
        );
    }

    /** Giriş ekranı için: kullanıcı adı ya da e-posta. */
    public static function findByLogin(string $login): ?array
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }
        return Database::first(
            'SELECT * FROM users
              WHERE deleted_at IS NULL
                AND (lower(username) = lower(:l) OR lower(email) = lower(:l))
              LIMIT 1',
            ['l' => $login]
        );
    }

    public static function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM users WHERE email = :e AND deleted_at IS NULL';
        $binds  = ['e' => mb_strtolower(trim($email))];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $binds['id'] = $ignoreId;
        }
        return (int) Database::value($sql, $binds, 0) > 0;
    }

    /** Kullanıcı adı benzersiz mi? Yalnızca e-posta ile giriş yapılabilir. */
    public static function usernameExists(string $username, ?int $ignoreId = null): bool
    {
        $username = trim($username);
        if ($username === '') {
            return false;
        }
        $sql    = 'SELECT COUNT(*) FROM users WHERE username = :u AND deleted_at IS NULL';
        $binds  = ['u' => $username];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $binds['id'] = $ignoreId;
        }
        return (int) Database::value($sql, $binds, 0) > 0;
    }

    public static function countAdmins(): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL', [], 0);
    }

    public static function updatePassword(int $id, string $plain): void
    {
        Database::execute(
            'UPDATE users SET password_hash = :h, updated_at = :u WHERE id = :id',
            ['h' => Auth::hash($plain), 'u' => now(), 'id' => $id]
        );
    }

    public static function updateProfile(int $id, array $data): void
    {
        $fields = [];
        $binds  = ['u' => now(), 'id' => $id];
        foreach (['name', 'username', 'email', 'avatar'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "{$f} = :{$f}";
                $binds[$f] = $data[$f];
            }
        }
        if ($fields === []) {
            return;
        }
        Database::execute('UPDATE users SET ' . implode(', ', $fields) . ', updated_at = :u WHERE id = :id', $binds);
    }

    /** Son admin silinemez / pasifleştirilemez. */
    public static function delete(int $id): int
    {
        if (self::countAdmins() <= 1) {
            return 0;
        }
        return parent::delete($id);
    }
}
