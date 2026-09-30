<?php
declare(strict_types=1);

namespace Core;

/**
 * HTML temizleyici — beyaz liste tabanlı.
 *
 * Ziyaretçi girişi olan hiçbir yerde ham HTML kabul edilmez.
 * Rich text alanları (haber gövdesi, sayfa içeriği, hizmet açıklaması)
 * buradan geçirilir: <script>, on* olayları, javascript: URL'leri,
 * data: URI'leri, <iframe>, <object>, stil ifadeleri temizlenir.
 *
 * Harici bağımlılık (HTMLPurifier) olmadan, yeterince katı bir
 * varsayılan sağlar.
 */
final class Sanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'mark', 'small',
        'h2', 'h3', 'h4', 'h5', 'blockquote', 'pre', 'code',
        'ul', 'ol', 'li', 'dl', 'dt', 'dd',
        'a', 'img', 'figure', 'figcaption',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'hr', 'span', 'div', 'abbr', 'time', 'sup', 'sub',
    ];

    private const ALLOWED_ATTRS = [
        'a'      => ['href', 'title', 'target', 'rel'],
        'img'    => ['src', 'alt', 'width', 'height', 'loading'],
        'td'     => ['colspan', 'rowspan'],
        'th'     => ['colspan', 'rowspan', 'scope'],
        'time'   => ['datetime'],
        'abbr'   => ['title'],
        'span'   => ['class'],
        'div'    => ['class'],
        'p'      => ['class'],
        'h2'     => ['id'], 'h3' => ['id'], 'h4' => ['id'],
    ];

    private const ALLOWED_CLASSES = [
        'text-muted', 'text-center', 'text-right', 'lead', 'note', 'caption',
    ];

    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /** İçerik kaynağı yalnızca site içi olabilir (gallery, uploads). */
    public static function html(?string $dirty, bool $allowImages = true): string
    {
        $dirty = (string) $dirty;
        if ($dirty === '') {
            return '';
        }

        // 1) Tehlikeli içerikleri önce tamamen sil (beyaz liste yetmezse)
        $dirty = (string) preg_replace('#<\s*(script|style|iframe|object|embed|form|input|button|link|meta|base|svg|math|noscript|template)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $dirty);
        $dirty = (string) preg_replace('#<\s*(script|style|iframe|object|embed|form|input|button|link|meta|base|svg|math|noscript|template)\b[^>]*/?>#is', '', $dirty);
        // HTML yorumları
        $dirty = (string) preg_replace('/<!--.*?-->/s', '', $dirty);
        // PHP ve conditional comment kaçışları
        $dirty = str_replace(['<?', '?>'], '', $dirty);

        $tags = self::ALLOWED_TAGS;
        if (!$allowImages) {
            $tags = array_values(array_diff($tags, ['img', 'figure', 'figcaption']));
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);

        $wrapped = '<?xml encoding="UTF-8"><div id="__root__">' . $dirty . '</div>';
        $loaded = $doc->loadHTML($wrapped, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (!$loaded) {
            // DOM başarısız: düz metin kaçışı (veri kaybı değil, güvenlik öncelikli)
            return nl2br(Str::escape(strip_tags($dirty)));
        }

        $root = $doc->getElementById('__root__');
        if ($root === null) {
            return nl2br(Str::escape(strip_tags($dirty)));
        }

        self::walk($root, $tags, 0);

        $html = '';
        foreach ($root->childNodes as $child) {
            $html .= $doc->saveHTML($child);
        }

        return trim($html);
    }

    private static function walk(\DOMNode $node, array $allowedTags, int $depth): void
    {
        if ($depth > 25) {
            return;
        }

        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMComment) {
                $child->parentNode?->removeChild($child);
                continue;
            }

            if ($child instanceof \DOMText) {
                continue;
            }

            if (!$child instanceof \DOMElement) {
                $child->parentNode?->removeChild($child);
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (!in_array($tag, $allowedTags, true)) {
                // İçeriği koru, etiketi at (unwrap)
                self::walk($child, $allowedTags, $depth + 1);
                while ($child->firstChild !== null) {
                    $child->parentNode?->insertBefore($child->firstChild, $child);
                }
                $child->parentNode?->removeChild($child);
                continue;
            }

            // Nitelikleri temizle
            self::cleanAttributes($child, $tag);

            // Boş etiketler
            if (in_array($tag, ['p', 'h2', 'h3', 'h4', 'h5', 'blockquote', 'li', 'div'], true)
                && !$child->hasChildNodes()
                && !in_array($tag, ['div'], true)) {
                continue;
            }

            self::walk($child, $allowedTags, $depth + 1);
        }
    }

    private static function cleanAttributes(\DOMElement $el, string $tag): void
    {
        $allowed = self::ALLOWED_ATTRS[$tag] ?? [];

        foreach (iterator_to_array($el->attributes ?? []) as $attr) {
            /** @var \DOMAttr $attr */
            $name = strtolower($attr->name);
            $val  = trim($attr->value);

            if (!in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
                continue;
            }

            // javascript: / data: / vbscript: şemaları
            if (in_array($name, ['href', 'src'], true)) {
                $scheme = strtolower((string) parse_url($val, PHP_URL_SCHEME));
                $isRelative = !str_contains($val, ':') || str_starts_with($val, '/') || str_starts_with($val, '#') || str_starts_with($val, 'uploads/');
                $isMail = str_starts_with(strtolower($val), 'mailto:');
                $isTel  = str_starts_with(strtolower($val), 'tel:');

                if (!$isRelative && !$isMail && !$isTel && !in_array($scheme, self::ALLOWED_SCHEMES, true)) {
                    $el->removeAttribute($attr->name);
                    continue;
                }
                // protocol-relative //evil.com engellenir
                if (str_starts_with($val, '//')) {
                    $el->removeAttribute($attr->name);
                }
            }

            if ($name === 'target') {
                $el->setAttribute('rel', 'noopener noreferrer nofollow');
                $el->setAttribute('target', '_blank');
            }

            if ($name === 'class') {
                $classes = array_filter(
                    preg_split('/\s+/', $val) ?: [],
                    static fn (string $c): bool => in_array($c, self::ALLOWED_CLASSES, true)
                );
                if ($classes === []) {
                    $el->removeAttribute('class');
                } else {
                    $el->setAttribute('class', implode(' ', $classes));
                }
            }

            if ($name === 'loading') {
                $el->setAttribute('loading', 'lazy');
            }
        }

        // <img> için src local olmalı
        if ($tag === 'img') {
            $src = (string) $el->getAttribute('src');
            if ($src === '' || preg_match('#^(https?:)?//#i', $src)) {
                $el->parentNode?->removeChild($el);
            }
        }
    }

    /** Düz metin — HTML tamamen atılır. */
    public static function plain(?string $value): string
    {
        return trim(strip_tags((string) $value));
    }

    /** Satır sonu koruyan basit metin. */
    public static function textarea(?string $value): string
    {
        $v = (string) $value;
        $v = str_replace(["\r\n", "\r"], "\n", $v);
        $v = (string) preg_replace('/\n{3,}/', "\n\n", $v);
        return trim($v);
    }

    /** Benzersiz HTML id üretir. */
    public static function uniqueId(string $prefix = 'id'): string
    {
        return $prefix . '-' . substr(Str::random(8), 0, 8);
    }
}
