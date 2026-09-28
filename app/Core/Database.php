<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Throwable;

/**
 * PDO / SQLite bağlantı sarmalayıcı.
 *
 * GÜVENLİK: PDO::ATTR_EMULATE_PREPARES=false — hazırlanmış ifadeler
 * gerçekten sunucu tarafında derlenir, böylece PDO'nun kendi kaçış
 * mantığına güvenmek zorunda kalmayız. Sorgularda kullanıcı girdisi
 * ASLA interpol edilmez; değerler daima :placeholder ile bağlanır.
 *
 * PERFORMANS: bağlantı başına tek PDO örneği, statement önbelleği ve
 * WAL modu.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static int $queryCount = 0;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = Config::get('database.connections.' . Config::get('database.default'));
        if (!is_array($cfg)) {
            throw new RuntimeException('Veritabanı bağlantısı tanımlı değil.');
        }

        $path = (string) $cfg['database'];
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!is_file($path)) {
            touch($path);
        }

        $dsn = 'sqlite:' . $path;
        try {
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            Logger::error('DB bağlantı hatası: ' . $e->getMessage());
            throw new RuntimeException('Veritabanına bağlanılamadı.', 0, $e);
        }

        // PRAGMA'lar bağlantı başına bir kez, statement cache ile çalışır.
        $busy   = (int) ($cfg['busy_timeout'] ?? 5000);
        $pdo->exec("PRAGMA busy_timeout = {$busy}");
        $pdo->exec('PRAGMA journal_mode = ' . (string) ($cfg['journal_mode'] ?? 'WAL'));
        $pdo->exec('PRAGMA synchronous = ' . (string) ($cfg['synchronous'] ?? 'NORMAL'));
        $pdo->exec('PRAGMA temp_store = MEMORY');
        $pdo->exec('PRAGMA foreign_keys = ' . (($cfg['foreign_key_constraints'] ?? true) ? 'ON' : 'OFF'));

        self::$pdo = $pdo;

        return $pdo;
    }

    /** Hazırlanmış ifade çalıştır, statement'i döndür. */
    public static function run(string $sql, array $bindings = []): PDOStatement
    {
        $tries = 0;
        beginning:
        try {
            $stmt = self::connection()->prepare($sql);
            self::bind($stmt, $bindings);
            $stmt->execute();
            self::$queryCount++;

            return $stmt;
        } catch (PDOException $e) {
            // SQLITE_BUSY: eşzamanlı yazma çakışması — kısa süreyle tek dene.
            if ($tries < 2 && self::isBusy($e)) {
                $tries++;
                usleep(120_000);
                goto beginning;
            }
            throw $e;
        }
    }

    private static function bind(PDOStatement $stmt, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            $param = is_int($key) ? $key + 1 : ':' . ltrim((string) $key, ':');
            $type  = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_INT,
                is_null($value) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue($param, is_bool($value) ? (int) $value : $value, $type);
        }
    }

    private static function isBusy(PDOException $e): bool
    {
        return str_contains($e->getMessage(), 'database is locked') || str_contains($e->getMessage(), 'busy');
    }

    /** Tüm satırları getir. */
    public static function select(string $sql, array $bindings = []): array
    {
        return self::run($sql, $bindings)->fetchAll();
    }

    /** Tek satır veya null. */
    public static function first(string $sql, array $bindings = []): ?array
    {
        $row = self::run($sql, $bindings)->fetch();
        return $row === false ? null : $row;
    }

    /** Tek bir skaler değer. */
    public static function value(string $sql, array $bindings = [], mixed $default = null): mixed
    {
        $val = self::run($sql, $bindings)->fetchColumn();
        return $val === false ? $default : $val;
    }

    /** INSERT — üretilen primary key döner. */
    public static function insert(string $sql, array $bindings = []): int
    {
        self::run($sql, $bindings);
        return (int) self::connection()->lastInsertId();
    }

    /** UPDATE / DELETE — etkilenen satır sayısı. */
    public static function execute(string $sql, array $bindings = []): int
    {
        return self::run($sql, $bindings)->rowCount();
    }

    /** UPDATE / DELETE — id dizisi üzerinden, IN() placeholder üretir. */
    public static function whereIn(string $table, array $ids, array $extra = [], string $op = '='): int
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return 0;
        }

        $cols  = array_keys($extra);
        $sets  = array_map(static fn (string $c): string => "{$c} = :set_{$c}", $cols);
        $binds = [];
        foreach ($extra as $col => $val) {
            $binds['set_' . $col] = $val;
        }

        $ph        = implode(',', array_fill(0, count($ids), '?'));
        $bindings  = array_merge($binds, $ids);
        $setClause = $sets ? 'SET ' . implode(', ', $sets) . ' ' : '';

        return self::execute(
            "UPDATE {$table} {$setClause}WHERE id IN ({$ph})",
            $bindings
        );
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        if ($pdo->inTransaction()) {
            return $callback($pdo);
        }

        $attempts = 0;
        while (true) {
            try {
                $pdo->beginTransaction();
                $result = $callback($pdo);
                $pdo->commit();
                return $result;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($attempts < 2 && self::isBusy($e instanceof PDOException ? $e : new PDOException($e->getMessage()))) {
                    $attempts++;
                    usleep(150_000);
                    continue;
                }
                throw $e;
            }
        }
    }

    public static function queryCount(): int
    {
        return self::$queryCount;
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }
}
