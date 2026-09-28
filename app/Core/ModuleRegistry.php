<?php
declare(strict_types=1);

namespace Core;

/**
 * Modül kayıt defteri.
 *
 * Tanım config/modules.php'den, durum `modules` tablosundan gelir.
 * Durum tek sorguda okunup istek boyunca bellekte tutulur — menü
 * denetimi için sorgu/istek oranı 1:1 kalır.
 */
final class ModuleRegistry
{
    /** @var array<string,array>|null */
    private static ?array $definitions = null;
    /** @var array<string,bool>|null */
    private static ?array $states = null;
    /** @var array<string,array>|null */
    private static ?array $order = null;

    public static function flush(): void
    {
        self::$states = null;
        self::$order  = null;
    }

    /** @return array<string,array> */
    public static function definitions(): array
    {
        if (self::$definitions === null) {
            $defs = Config::get('modules', []);
            self::$definitions = is_array($defs) ? $defs : [];
        }
        return self::$definitions;
    }

    public static function exists(string $slug): bool
    {
        return isset(self::definitions()[$slug]);
    }

    public static function isActive(string $slug): bool
    {
        return self::states()[$slug] ?? false;
    }

    /** Modül aktif değilse isteği 404 ile sonlandırır. */
    public static function requireActive(string $slug): void
    {
        if (!self::isActive($slug)) {
            Application::abort(404);
        }
    }

    /** @return array<string,bool> */
    public static function states(): array
    {
        if (self::$states !== null) {
            return self::$states;
        }

        $known  = array_keys(self::definitions());
        $states = array_fill_keys($known, false);
        $order  = [];

        try {
            $rows = Database::select('SELECT slug, is_active, sort_order FROM modules ORDER BY sort_order ASC');
        } catch (\Throwable) {
            // Tablo henüz yoksa (kurulum öncesi) tanımdaki her şey aktif kabul edilir
            self::$states = array_fill_keys($known, true);
            return self::$states;
        }

        foreach ($rows as $row) {
            $slug = (string) $row['slug'];
            if (!isset($states[$slug])) {
                continue;
            }
            $states[$slug] = (int) $row['is_active'] === 1;
            $order[$slug]   = (int) $row['sort_order'];
        }

        self::$states = $states;
        self::$order  = $order;

        return $states;
    }

    public static function name(string $slug, ?string $lang = null): string
    {
        $def = self::definitions()[$slug] ?? null;
        if ($def === null) {
            return $slug;
        }
        $lang = $lang ?? Translator::lang();
        return (string) ($def['name'][$lang] ?? $def['name'][Translator::fallbackLang()] ?? $slug);
    }

    public static function desc(string $slug, ?string $lang = null): string
    {
        $def = self::definitions()[$slug] ?? null;
        if ($def === null) {
            return '';
        }
        $lang = $lang ?? Translator::lang();
        return (string) ($def['desc'][$lang] ?? $def['desc'][Translator::fallbackLang()] ?? '');
    }

    public static function route(string $slug): ?string
    {
        return self::definitions()[$slug]['route'] ?? null;
    }

    public static function adminRoute(string $slug): ?string
    {
        return self::definitions()[$slug]['admin'] ?? null;
    }

    /**
     * Modülün yönetim panelindeki GERÇEK yolu (öneki olmadan).
     *
     * Neden ayrı: 'admin' değeri bir ROTA ADIDIR (admin.comments.index) ve
     * noktaları eğrice çevirilerek yol tahmin ediliyordu. Ama comments,
     * messages ve search rotaları routes/admin.php'te elle, Türkçe yolla
     * kayıtlı (/admin/yorumlar, /admin/mesajlar, /admin/ara). Tahmin
     * /admin/comments üretti ve menü 404'e düştü.
     *
     * 'admin_path' tanımlıysa o esas alınır; yoksa geriye dönük uyum
     * için slug kullanılır (dinamik üretilen içerik rotaları İngilizce slug
     * taşır ve zaten tutarlıdır).
     */
    public static function adminPath(string $slug): ?string
    {
        $def = self::definitions()[$slug] ?? null;
        if ($def === null) {
            return null;
        }
        $path = $def['admin_path'] ?? $slug;
        return $path !== '' ? (string) $path : null;
    }

    public static function table(string $slug): ?string
    {
        return self::definitions()[$slug]['table'] ?? null;
    }

    public static function glyph(string $slug): string
    {
        return (string) (self::definitions()[$slug]['icon_glyph'] ?? '●');
    }

    public static function group(string $slug): string
    {
        return (string) (self::definitions()[$slug]['group'] ?? 'content');
    }

    public static function isAdminOnly(string $slug): bool
    {
        return (bool) (self::definitions()[$slug]['admin_only'] ?? false);
    }

    public static function showOnHome(string $slug): bool
    {
        return (bool) (self::definitions()[$slug]['show_home'] ?? false);
    }

    public static function railLimit(string $slug): int
    {
        $def = self::definitions()[$slug] ?? [];
        $limit = $def['rail_limit'] ?? null;
        if ($limit === null) {
            return (int) Config::get('app.home_rail_limit', 5);
        }
        return (int) $limit;
    }

    /**
     * Ana sayfada sırayla gösterilecek aktif modüller.
     * @return array<int,string>
     */
    public static function homeModules(): array
    {
        $out = [];
        foreach (self::definitions() as $slug => $def) {
            if (self::isActive($slug) && ($def['show_home'] ?? false)) {
                $out[] = $slug;
            }
        }
        return $out;
    }

    /**
     * Menüde gösterilecek aktif modüller (public route'u olanlar).
     * @return array<int,string>
     */
    public static function navigable(): array
    {
        $out = [];
        foreach (self::definitions() as $slug => $def) {
            if (self::isActive($slug) && !empty($def['route']) && empty($def['admin_only'])) {
                $out[] = $slug;
            }
        }
        return $out;
    }

    /**
     * Admin menüsü grupları.
     * @return array<string,array<int,string>>
     */
    public static function adminGroups(): array
    {
        $groups = ['content' => [], 'social' => [], 'system' => []];
        foreach (self::definitions() as $slug => $def) {
            $groups[$def['group'] ?? 'content'][] = $slug;
        }
        return array_filter($groups);
    }

    /** Modülü aktif/pasif yap. */
    public static function toggle(string $slug, bool $active): bool
    {
        if (!self::exists($slug)) {
            return false;
        }
        Database::execute(
            'UPDATE modules SET is_active = :a, updated_at = :u WHERE slug = :s',
            ['a' => $active ? 1 : 0, 'u' => now(), 's' => $slug]
        );
        self::flush();
        return true;
    }
}
