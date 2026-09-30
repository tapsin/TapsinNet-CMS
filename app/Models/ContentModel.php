<?php
declare(strict_types=1);

namespace Models;

use Core\Model;
use Core\Translator;

/**
 * İçerik modellerinin ortak davranışı.
 *
 *  · slug üretimi (TR karakter korumalı, benzersiz)
 *  · iki dilli alanların beyaz listesi
 *  · SEO alanlarının otomatik türetilmesi
 *  · arama için varsayılan sütun listesi
 */
abstract class ContentModel extends Model
{
    protected bool $scopeActive = true;
    protected bool $softDeletes = true;

    /** Arama yapılacak metin sütunları. */
    protected array $searchColumns = ['title_tr', 'title_en', 'excerpt_tr', 'excerpt_en'];

    /** Slug üretilecek başlık sütunu. */
    protected string $slugSource = 'title_tr';

    /** Bu modelin karşılık geldiği modül slug'ı. */
    protected string $moduleSlug = '';

    /** Ana sayfa rayı sırası. */
    protected string $homeOrder = 'created_at DESC, id DESC';

    public static function moduleSlug(): string
    {
        return (new static())->moduleSlug;
    }

    /**
     * Form verisinden kayıt oluşturur/günceller — slug ve SEO otomatik.
     */
    public static function save(array $data, ?int $id = null): int
    {
        $m = new static();

        // Slug
        if (empty($data['slug'])) {
            $source = (string) ($data[$m->slugSource] ?? $data['title_tr'] ?? '');
            $data['slug'] = self::uniqueSlug($source, $id);
        } else {
            $data['slug'] = self::uniqueSlug((string) $data['slug'], $id);
        }

        // SEO alanları boşsa başlıktan türet
        $data = $m->deriveSeo($data);

        if ($id === null) {
            return static::create($data);
        }

        static::update($id, $data);
        return $id;
    }

    /**
     * Tüm yazma yolları (create/update) buradan geçer — slug ve SEO
     * üretimi bu yüzden isteğe bağlı değil, garanti edilir.
     */
    protected function beforeSave(array $data, ?int $id): array
    {
        if (self::columnExists($this->table, 'slug')) {
            $source = (string) ($data[$this->slugSource] ?? $data['title_tr'] ?? $data['name'] ?? '');
            $data['slug'] = self::uniqueSlug(
                (string) ($data['slug'] ?? '') !== '' ? (string) $data['slug'] : $source,
                $id
            );
        }
        return $this->deriveSeo($data);
    }

    protected function deriveSeo(array $data): array
    {
        if (empty($data['meta_title_tr']) && !empty($data['title_tr'])) {
            $data['meta_title_tr'] = \Core\Str::limit((string) $data['title_tr'], 60);
        }
        if (empty($data['meta_title_en']) && !empty($data['title_en'])) {
            $data['meta_title_en'] = \Core\Str::limit((string) $data['title_en'], 60);
        }

        foreach ([['meta_desc_tr', 'excerpt_tr', 'title_tr'], ['meta_desc_en', 'excerpt_en', 'title_en']] as [$metaKey, $sourceKey, $fallbackKey]) {
            if (empty($data[$metaKey])) {
                $source = (string) ($data[$sourceKey] ?? $data[$fallbackKey] ?? '');
                if ($source !== '') {
                    $data[$metaKey] = \Core\Str::limit($source, 160);
                }
            }
        }

        return $data;
    }

    /** Arama terimini beyaz listeye göre hazırlar. */
    public static function searchFor(string $term): array
    {
        $m = new static();
        $search = [];
        foreach ($m->searchColumns as $column) {
            if (self::columnExists($m->table, $column)) {
                $search[$column] = ['op' => 'like', 'value' => $term];
            }
        }
        return $search;
    }

    /** Aktif kayıt arama sayfası için. */
    public static function search(string $term, int $page = 1, ?int $perPage = null, array $extraWhere = []): \Core\Paginator
    {
        return static::paginate(
            $extraWhere,
            self::searchFor($term),
            [static::defaultOrderColumn() => 'DESC'],
            $page,
            $perPage,
            ['q' => $term]
        );
    }

    protected static function defaultOrderColumn(): string
    {
        return 'created_at';
    }

    /** Ana sayfa rayı — modül aktif değilse boş dizi. */
    public static function homeRail(int $limit): array
    {
        $module = static::moduleSlug();
        if ($module !== '' && !\Core\ModuleRegistry::isActive($module)) {
            return [];
        }
        return static::list([], [], [], $limit);
    }

    /** Kategori seçenekleri (varsa). */
    public static function categories(): array
    {
        return [];
    }

    public static function activeCategories(): array
    {
        return static::categories();
    }

    /** Etiket listesini virgüllü metinden üretir. */
    public static function tagList(?string $tags): array
    {
        if ($tags === null || trim($tags) === '') {
            return [];
        }
        $parts = array_map('trim', explode(',', $tags));
        return array_values(array_filter($parts, static fn (string $t): bool => $t !== ''));
    }

    /** Görsel alanından türetilen açıklama metni. */
    public static function seoTitle(array $row): string
    {
        $title = loc($row, 'meta_title') ?: loc($row, 'title');
        $site  = \Core\Config::get('app.name');
        return trim($title) . ' · ' . $site;
    }

    public static function seoDescription(array $row): string
    {
        $desc = loc($row, 'meta_desc') ?: loc($row, 'excerpt') ?: loc($row, 'summary');
        return \Core\Str::limit((string) $desc, 160);
    }

    /** Etiket çevirisi. */
    public static function typeLabel(string $key): string
    {
        return Translator::t('profile_types.' . $key, [], ucfirst(str_replace('_', ' ', $key)));
    }
}
