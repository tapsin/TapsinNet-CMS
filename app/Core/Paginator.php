<?php
declare(strict_types=1);

namespace Core;

/**
 * Sayfalayıcı — toplam sayı hesabı veritabanında yapılır (COUNT(*)),
 * tüm kayıtlar PHP tarafına çekilmez.
 */
final class Paginator implements \IteratorAggregate, \Countable
{
    public int $currentPage;
    public int $perPage;
    public int $total;
    public int $lastPage;
    public array $items = [];
    public array $query = [];

    public function __construct()
    {
    }

    /**
     * @param array $query URL koruması için izinli sıralama vb.
     */
    public static function make(
        string $sql,
        array $bindings,
        int $page = 1,
        int $perPage = 12,
        array $query = []
    ): self {
        $p = new self();
        $p->query   = $query;
        $p->perPage = max(1, min(100, $perPage));
        $p->total   = (int) Database::value($sql, $bindings, 0);
        $p->lastPage = max(1, (int) ceil($p->total / $p->perPage));
        $p->currentPage = max(1, min($page, $p->lastPage));

        $offset = ($p->currentPage - 1) * $p->perPage;
        $p->items = Database::select($sql . ' LIMIT ' . $p->perPage . ' OFFSET ' . $offset, $bindings);

        return $p;
    }

    /** Model katmanından sarmalayıcı. */
    public static function fromModel(
        string $modelClass,
        array $where,
        string $selectSql,
        array $bindings,
        int $page,
        int $perPage,
        array $query = []
    ): self {
        $p = new self();
        $p->query   = $query;
        $p->perPage = max(1, min(100, $perPage));
        $p->total   = (int) Database::value($selectSql, $bindings, 0);
        $p->lastPage = max(1, (int) ceil($p->total / $p->perPage));
        $p->currentPage = max(1, min($page, $p->lastPage));

        $offset   = ($p->currentPage - 1) * $p->perPage;
        $p->items = call_user_func([$modelClass, 'paginateRaw'], $where, $offset, $p->perPage);

        return $p;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function hasPages(): bool
    {
        return $this->lastPage > 1;
    }

    /** Sayfa aralığı: "1–9 / 42" */
    public function rangeLabel(): string
    {
        if ($this->total === 0) {
            return '0';
        }
        $from = ($this->currentPage - 1) * $this->perPage + 1;
        $to   = min($this->currentPage * $this->perPage, $this->total);
        return $from . '–' . $to;
    }

    /**
     * Sayfa bağlantıları — mevcut sorgu dizesini korur.
     * Erişilebilirlik: aria-current, aria-label, gövde metni.
     */
    public function links(string $view = 'site.partials.pagination'): string
    {
        if (!$this->hasPages()) {
            return '';
        }
        $html = View::partial($view, ['paginator' => $this]);
        return $html;
    }

    /** Sayfa URL'i üretir. */
    public function url(int $page): string
    {
        $query = $this->query;
        if ($page <= 1) {
            unset($query['page']);
        } else {
            $query['page'] = $page;
        }
        $qs = http_build_query($query);
        $current = strtok(Request::current()->url(), '?') ?: '/';
        return $qs === '' ? $current : $current . '?' . $qs;
    }

    /** Görüntülenecek sayfa numaraları (… ile kısaltılmış). */
    public function window(int $each = 2): array
    {
        $pages = [];
        $last  = $this->lastPage;
        $cur   = $this->currentPage;

        for ($i = 1; $i <= $last; $i++) {
            if ($i === 1 || $i === $last || abs($i - $cur) <= $each) {
                $pages[] = $i;
            } elseif (end($pages) !== '…') {
                $pages[] = '…';
            }
        }
        return $pages;
    }

    public function firstItem(): int
    {
        return $this->total === 0 ? 0 : ($this->currentPage - 1) * $this->perPage + 1;
    }

    public function lastItem(): int
    {
        return min($this->currentPage * $this->perPage, $this->total);
    }
}
