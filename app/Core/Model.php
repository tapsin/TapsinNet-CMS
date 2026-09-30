<?php
declare(strict_types=1);

namespace Core;

/**
 * Temel model — tüm modeller bunu genişletir.
 *
 *  · Tüm sorgular hazırlanmış ifade kullanır
 *  · Aktif/pasif kayıtlar `is_active` üzerinden filtrelenir
 *  · Sıralama sütunu beyaz listeye karşı doğrulanır
 *  · Sayfalama veritabanında (LIMIT/OFFSET) yapılır
 *  · İçerik alanları `_tr` / `_en` çiftleriyle tutulur
 */
abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';

    /** Yazılabilir beyaz liste — formdan gelen alanlar bununla filtrelenir. */
    protected array $fillable = [];

    /** Aktif kayıtları yalnızca göster (false ise tümü). */
    protected bool $scopeActive = true;

    /** Yumuşak silme kullanılsın mı. */
    protected bool $softDeletes = false;

    /** Ana sayfa rayında kullanılan sıralama. */
    protected string $homeOrder = 'created_at DESC';

    protected string $createdAtField = 'created_at';
    protected string $updatedAtField = 'updated_at';

    public static function table(): string
    {
        return (new static())->table;
    }

    public static function fillable(): array
    {
        return (new static())->fillable;
    }

    // ------------------------------------------------------------ okuma

    public static function find(int $id): ?array
    {
        $m = new static();
        $row = Database::first(
            "SELECT * FROM {$m->table} WHERE {$m->primaryKey} = :id LIMIT 1",
            ['id' => $id]
        );
        if ($row === null) {
            return null;
        }
        if ($m->softDeletes && !empty($row['deleted_at'])) {
            return null;
        }
        if ($m->scopeActive && isset($row['is_active']) && (int) $row['is_active'] !== 1) {
            return null;
        }
        return $row;
    }

    /** Aktif/pasif farkını görmeden bul (admin düzenleme ekranı). */
    public static function findAny(int $id): ?array
    {
        $m = new static();
        $row = Database::first(
            "SELECT * FROM {$m->table} WHERE {$m->primaryKey} = :id LIMIT 1",
            ['id' => $id]
        );
        if ($row === null) {
            return null;
        }
        if ($m->softDeletes && !empty($row['deleted_at'])) {
            return null;
        }
        return $row;
    }

    public static function findBySlug(string $slug, ?string $lang = null): ?array
    {
        $m = new static();
        $row = Database::first(
            "SELECT * FROM {$m->table} WHERE slug = :slug LIMIT 1",
            ['slug' => $slug]
        );
        if ($row === null) {
            return null;
        }
        if ($m->softDeletes && !empty($row['deleted_at'])) {
            return null;
        }
        if ($m->scopeActive && isset($row['is_active']) && (int) $row['is_active'] !== 1) {
            return null;
        }
        return $row;
    }

    /** Slug + aktif kayıt. */
    public static function activeBySlug(string $slug): ?array
    {
        $m = new static();
        $row = Database::first(
            "SELECT * FROM {$m->table} WHERE slug = :slug AND is_active = 1 LIMIT 1",
            ['slug' => $slug]
        );
        if ($row === null) {
            return null;
        }
        if ($m->softDeletes && !empty($row['deleted_at'])) {
            return null;
        }
        return $row;
    }

    /**
     * Koşullu liste.
     *
     * @param array $where  ['is_active' => 1, 'category_id' => 3]  veya
     *                      ['featured' => 1, 'year >' => 2024]
     * @param array $search ['title' => ['like' => 'x'], 'excerpt' => 'y']
     * @param array $order  ['sort_order' => 'ASC', 'created_at' => 'DESC']
     * @param int   $limit  0 = sınırsız
     */
    public static function list(
        array $where = [],
        array $search = [],
        array $order = [],
        int $limit = 0,
        int $offset = 0
    ): array {
        [$sql, $bindings] = static::buildQuery($where, $search, $order, $limit, $offset);
        return Database::select($sql, $bindings);
    }

    /** Ana sayfa rayı — aktif kayıtlar, en yeni/öne çıkan. */
    public static function latest(int $limit = 5, array $where = []): array
    {
        $m = new static();
        $w = $where;
        if ($m->scopeActive) {
            $w['is_active'] = 1;
        }
        return static::list($w, [], [], $limit);
    }

    public static function featured(int $limit = 5): array
    {
        $m = new static();
        $w = ['is_featured' => 1];
        if ($m->scopeActive) {
            $w['is_active'] = 1;
        }
        return static::list($w, [], [], $limit);
    }

    /** COUNT — koşullu. */
    public static function countWhere(array $where = [], array $search = []): int
    {
        $m = new static();
        [$w, $s] = static::applyScopes($where, $search);
        $where = $w; $search = $s;
        [$whereSql, $bindings] = static::buildWhere($where, $search);
        $sql = "SELECT COUNT(*) FROM {$m->table}" . ($whereSql ? " WHERE {$whereSql}" : '');
        return (int) Database::value($sql, $bindings, 0);
    }

    // ------------------------------------------------------------ sayfalama

    /**
     * @return Paginator
     */
    public static function paginate(
        array $where = [],
        array $search = [],
        array $order = [],
        int $page = 1,
        ?int $perPage = null,
        array $query = []
    ): Paginator {
        $m = new static();
        $perPage ??= (int) Config::get('app.per_page', 9);

        [$w, $s] = static::applyScopes($where, $search);
        [$whereSql, $bindings] = static::buildWhere($w, $s);

        $countSql = "SELECT COUNT(*) FROM {$m->table}" . ($whereSql ? " WHERE {$whereSql}" : '');
        $total = (int) Database::value($countSql, $bindings, 0);

        $paginator = new Paginator();
        $paginator->perPage     = max(1, min(100, $perPage));
        $paginator->total       = $total;
        $paginator->lastPage    = max(1, (int) ceil($total / $paginator->perPage));
        $paginator->currentPage = max(1, min($page, $paginator->lastPage));
        $paginator->query       = $query;
        $paginator->items       = static::list($where, $search, $order, $paginator->perPage, ($paginator->currentPage - 1) * $paginator->perPage);

        return $paginator;
    }

    // ------------------------------------------------------------ yazma

    /**
     * Yeni kayıt ekle. $data beyaz listeye göre filtrelenir.
     */
    public static function create(array $data): int
    {
        $m = new static();
        $data = $m->filterFillable($data);
        $data = $m->applyTimestamps($data, true);
        $data = $m->beforeSave($data, null);
        // beforeSave yeni alan üretebilir (slug, SEO) — $fillable SON FİLTRE
        // olarak yeniden uygulanır, böylece şemada olmayan sütun yazılamaz.
        $data = $m->filterFillable($data);

        $cols   = array_keys($data);
        $place  = array_map(static fn (string $c): string => ':' . $c, $cols);
        $sql    = 'INSERT INTO ' . $m->table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $place) . ')';

        return Database::insert($sql, $data);
    }

    public static function update(int $id, array $data): int
    {
        $m = new static();
        $data = $m->filterFillable($data);
        $data = $m->applyTimestamps($data, false);
        $data = $m->beforeSave($data, $id);
        $data = $m->filterFillable($data);

        if ($data === []) {
            return 0;
        }

        $sets   = array_map(static fn (string $c): string => "{$c} = :{$c}", array_keys($data));
        $sql    = 'UPDATE ' . $m->table . ' SET ' . implode(', ', $sets) . " WHERE {$m->primaryKey} = :__id";
        $data['__id'] = $id;

        return Database::execute($sql, $data);
    }

    /** Aktif/pasif anahtarı. */
    public static function setActive(int $id, bool $active): int
    {
        $m = new static();
        return Database::execute(
            'UPDATE ' . $m->table . " SET is_active = :a, {$m->updatedAtField} = :u WHERE {$m->primaryKey} = :id",
            ['a' => $active ? 1 : 0, 'u' => now(), 'id' => $id]
        );
    }

    public static function setFeatured(int $id, bool $featured): int
    {
        $m = new static();
        if (!self::columnExists($m->table, 'is_featured')) {
            return 0;
        }
        return Database::execute(
            'UPDATE ' . $m->table . " SET is_featured = :f, {$m->updatedAtField} = :u WHERE {$m->primaryKey} = :id",
            ['f' => $featured ? 1 : 0, 'u' => now(), 'id' => $id]
        );
    }

    public static function delete(int $id): int
    {
        $m = new static();
        if ($m->softDeletes) {
            return Database::execute(
                'UPDATE ' . $m->table . " SET deleted_at = :d WHERE {$m->primaryKey} = :id",
                ['d' => now(), 'id' => $id]
            );
        }
        return Database::execute(
            'DELETE FROM ' . $m->table . " WHERE {$m->primaryKey} = :id",
            ['id' => $id]
        );
    }

    public static function deleteMany(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return 0;
        }
        $m = new static();
        $ph = implode(',', array_fill(0, count($ids), '?'));

        if ($m->softDeletes) {
            return Database::execute(
                "UPDATE {$m->table} SET deleted_at = ? WHERE {$m->primaryKey} IN ({$ph})",
                array_merge([now()], $ids)
            );
        }
        return Database::execute(
            "DELETE FROM {$m->table} WHERE {$m->primaryKey} IN ({$ph})",
            $ids
        );
    }

    public static function increment(string $column, int $id, int $by = 1): void
    {
        $m = new static();
        // Sütun adı geliştirici kontrolünde sabit bir isimdir
        if (!self::columnExists($m->table, $column)) {
            return;
        }
        Database::execute(
            "UPDATE {$m->table} SET {$column} = COALESCE({$column}, 0) + :by WHERE {$m->primaryKey} = :id",
            ['by' => $by, 'id' => $id]
        );
    }

    /** Benzersiz slug üretir. Yumuşak silinen kayıtlar slug'ı bloke etmez. */
    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $m = new static();
        $slug = Str::slug($base);
        if ($slug === '') {
            $slug = 'kayit';
        }

        // Yumuşak silme kapsamı: silinmiş kayıt slug'ı serbest bırakır,
        // aksi halde sayaç sonsuza dek artar ve URL'ler şişer.
        $scope = $m->softDeletes ? " AND {$m->table}.deleted_at IS NULL" : '';

        $candidate = $slug;
        $i = 2;
        while (true) {
            $sql    = "SELECT COUNT(*) FROM {$m->table} WHERE slug = :s{$scope}";
            $binds  = ['s' => $candidate];
            if ($ignoreId !== null) {
                $sql .= " AND {$m->primaryKey} <> :id";
                $binds['id'] = $ignoreId;
            }
            if ((int) Database::value($sql, $binds, 0) === 0) {
                return $candidate;
            }
            $candidate = $slug . '-' . $i;
            $i++;
            if ($i > 9999) {
                return $slug . '-' . Str::random(6);
            }
        }
    }

    // ------------------------------------------------------------ sorgu kurulumu

    protected static function applyScopes(array $where, array $search): array
    {
        $m = new static();
        if ($m->scopeActive) {
            $where['is_active'] = $where['is_active'] ?? 1;
        }
        if ($m->softDeletes) {
            $where['deleted_at'] = $where['deleted_at'] ?? null;
        }
        return [$where, $search];
    }

    protected static function buildQuery(array $where, array $search, array $order, int $limit, int $offset): array
    {
        $m = new static();
        [$w, $s] = static::applyScopes($where, $search);
        $where = $w; $search = $s;
        [$whereSql, $bindings] = static::buildWhere($where, $search);

        $sql = "SELECT * FROM {$m->table}" . ($whereSql ? " WHERE {$whereSql}" : '');
        $sql .= static::buildOrder($order, $m->homeOrder);

        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return [$sql, $bindings];
    }

    /**
     * @return array{0:string,1:array} whereSql, bindings
     */
    protected static function buildWhere(array $where, array $search): array
    {
        $m = new static();
        $clauses = [];
        $binds   = [];

        foreach ($where as $key => $value) {
            // Ham koşul kaçışı: '@' ile başlayan anahtarlar geliştirici
            // tarafından yazılmış SQL ifadesidir. DEĞERİ placeholder ile
            // bağlanır, ifadenin kendisi asla kullanıcı girdisinden üretilmez.
            if (is_string($key) && str_starts_with($key, '@')) {
                $clause = substr($key, 1);
                if (preg_match('/^[A-Za-z0-9_(),\s\.\*\+\-\/]+$/', $clause)) {
                    $clauses[] = "({$clause}) = :w_raw" . count($clauses);
                    $binds['w_raw' . count($clauses)] = $value;
                }
                continue;
            }

            // "col >" / "col <=" gibi karşılaştırma anahtarları
            if (preg_match('/^([a-z_]+)\s*(>=|<=|>|<|!=|=)$/', (string) $key, $mch)) {
                $col = $mch[1];
                $op  = $mch[2];
                if (!self::columnExists($m->table, $col)) {
                    continue;
                }
                $clauses[] = "{$col} {$op} :w_{$col}_" . preg_replace('/\W/', '', $op);
                $binds['w_' . $col . '_' . preg_replace('/\W/', '', $op)] = $value;
                continue;
            }

            if (!self::columnExists($m->table, (string) $key)) {
                continue;
            }

            if ($value === null) {
                $clauses[] = "{$key} IS NULL";
                continue;
            }
            if (is_array($value)) {
                if ($value === []) {
                    $clauses[] = '1=0';
                    continue;
                }
                $ph = implode(',', array_fill(0, count($value), '?'));
                $clauses[] = "{$key} IN ({$ph})";
                foreach ($value as $v) {
                    $binds[] = $v;
                }
                continue;
            }

            $clauses[] = "{$key} = :w_{$key}";
            $binds['w_' . $key] = $value;
        }

        // Arama — LIKE, sütunlar beyaz listeden gelir
        if ($search !== []) {
            $parts = [];
            $i = 0;
            foreach ($search as $column => $spec) {
                if (!self::columnExists($m->table, (string) $column)) {
                    continue;
                }
                $value  = is_array($spec) ? ($spec['value'] ?? '') : $spec;
                $op     = is_array($spec) ? ($spec['op'] ?? 'like') : 'like';
                if (!is_scalar($value) || (string) $value === '') {
                    continue;
                }
                $needle = '%' . str_replace(['%', '_'], ['\%', '\_'], (string) $value) . '%';
                $key    = "s_{$column}_{$i}";
                $i++;

                if ($op === 'like') {
                    $parts[]  = "{$column} LIKE :{$key} ESCAPE '\\'";
                    $binds[$key] = $needle;
                } elseif ($op === 'raw') {
                    $parts[]  = "{$column} = :{$key}";
                    $binds[$key] = (string) $value;
                }
            }
            if ($parts !== []) {
                $clauses[] = '(' . implode(' OR ', $parts) . ')';
            }
        }

        return [$clauses === [] ? '' : implode(' AND ', $clauses), $binds];
    }

    /**
     * Sıralama — sütun ve yön beyaz listeden geçer.
     * SQL enjeksiyonu yalnızca buradan geçebileceği için katı kontrol.
     */
    protected static function buildOrder(array $order, string $fallback): string
    {
        if ($order === []) {
            return ' ORDER BY ' . $fallback;
        }

        $parts = [];
        foreach ($order as $column => $direction) {
            if (!is_string($column) || !self::columnExists((new static())->table, $column)) {
                continue;
            }
            $dir = Security::safeDirection((string) $direction);
            $parts[] = "{$column} {$dir}";
        }

        return $parts === [] ? ' ORDER BY ' . $fallback : ' ORDER BY ' . implode(', ', $parts);
    }

    protected function filterFillable(array $data): array
    {
        if ($this->fillable === []) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function applyTimestamps(array $data, bool $isCreate): array
    {
        if ($isCreate && !isset($data[$this->createdAtField])) {
            $data[$this->createdAtField] = now();
        }
        $data[$this->updatedAtField] = now();
        return $data;
    }

    /**
     * Kaydetme öncesi son dokunuş noktası. Alt sınıflar slug üretimi,
     * SEO türetimi gibi işlemleri burada yapar.
     */
    protected function beforeSave(array $data, ?int $id): array
    {
        return $data;
    }

    /**
     * Sütun var mı — her sorguda PRAGMA'ya basmak pahalı olurdu,
     * bu yüzden istek başına bir kez önbelleklenir.
     */
    protected static array $columnCache = [];

    public static function columnExists(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (isset(self::$columnCache[$key])) {
            return self::$columnCache[$key];
        }
        if (!isset(self::$columnCache[$table])) {
            try {
                $cols = Database::select('PRAGMA table_info(' . preg_replace('/\W/', '', $table) . ')');
                $set = [];
                foreach ($cols as $c) {
                    $set[(string) $c['name']] = true;
                }
                self::$columnCache[$table] = $set;
            } catch (\Throwable) {
                self::$columnCache[$table] = [];
            }
        }
        return self::$columnCache[$table][$column] ?? false;
    }

    public static function flushColumnCache(): void
    {
        self::$columnCache = [];
    }
}
