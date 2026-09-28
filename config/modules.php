<?php
/**
 * MODÜL KATALOĞU — sistemin tek gerçek kaynağı.
 *
 * Her içerik türü bir "modüldür". Admin panelindeki /admin/moduller
 * sayfasından aktif/pasif yapılır. Pasif bir modül:
 *   · menüden düşer
 *   · public route'u 404 döner
 *   · admin liste rotaları 404 döner
 *   · ana sayfadaki rayı render edilmez
 * Aktif/pasif durumu `modules` tablosunda saklanır; buradaki tanım
 * yalnızca "modülün ne olduğu" bilgisini taşır.
 *
 * show_home  : ana sayfada "son eklenenler" rayı gösterilsin mi
 * rail_limit : o rayda kaç kayıt gösterilsin (null => config home_rail_limit)
 */
return [

    'services' => [
        'name' => ['tr' => 'Hizmetler', 'en' => 'Services'],
        'desc' => ['tr' => 'Verilen hizmet kalemleri', 'en' => 'Service offerings'],
        'icon'  => 'layers',
        'admin' => 'admin.services.index',
        'route' => 'hizmetler',   // public URL — Türkçe tutulur
        'table' => 'services',
        'show_home' => true,
        'rail_limit' => 6,
        'group' => 'content',
        'icon_glyph' => '◆',
    ],

    'projects' => [
        'name' => ['tr' => 'İşler', 'en' => 'Projects'],
        'desc' => ['tr' => 'Tamamlanan projeler / portföy', 'en' => 'Completed work / portfolio'],
        'icon'  => 'grid',
        'admin' => 'admin.projects.index',
        'route' => 'isler',
        'table' => 'projects',
        'show_home' => true,
        'rail_limit' => null, // ana sayfa: son 5 iş
        'group' => 'content',
        'icon_glyph' => '■',
    ],

    'certificates' => [
        'name' => ['tr' => 'Sertifikalar', 'en' => 'Certificates'],
        'desc' => ['tr' => 'Alınan belgeler ve sertifikalar', 'en' => 'Documents and certificates'],
        'icon'  => 'award',
        'admin' => 'admin.certificates.index',
        'route' => 'sertifikalar',
        'table' => 'certificates',
        'show_home' => true,
        'rail_limit' => 4,
        'group' => 'content',
        'icon_glyph' => '✦',
    ],

    'gallery' => [
        'name' => ['tr' => 'Galeri', 'en' => 'Gallery'],
        'desc' => ['tr' => 'Görsel galeri (lightbox destekli)', 'en' => 'Image gallery with lightbox'],
        'icon'  => 'image',
        'admin' => 'admin.gallery.index',
        'route' => 'galeri',
        'table' => 'gallery_items',
        'show_home' => true,
        'rail_limit' => 5,
        'group' => 'content',
        'icon_glyph' => '▦',
    ],

    'videos' => [
        'name' => ['tr' => 'Video Galeri', 'en' => 'Video Gallery'],
        'desc' => ['tr' => 'YouTube / Vimeo videoları', 'en' => 'YouTube / Vimeo videos'],
        'icon'  => 'play',
        'admin' => 'admin.videos.index',
        'route' => 'videolar',
        'table' => 'videos',
        'show_home' => true,
        'rail_limit' => 4,
        'group' => 'content',
        'icon_glyph' => '▶',
    ],

    'news' => [
        'name' => ['tr' => 'Haberler', 'en' => 'News'],
        'desc' => ['tr' => 'Blog / duyuru yazıları', 'en' => 'Blog and announcements'],
        'icon'  => 'news',
        'admin' => 'admin.news.index',
        'route' => 'haberler',
        'table' => 'news',
        'show_home' => true,
        'rail_limit' => 3,
        'group' => 'content',
        'icon_glyph' => '✎',
    ],

    'profiles' => [
        'name' => ['tr' => 'Profiller', 'en' => 'Profiles'],
        'desc' => ['tr' => 'Ekip, iş ortakları ve müşteri profilleri', 'en' => 'Team, partners and client profiles'],
        'icon'  => 'users',
        'admin' => 'admin.profiles.index',
        'route' => 'profiller',
        'table' => 'profiles',
        'show_home' => true,
        'rail_limit' => 5,
        'group' => 'content',
        'icon_glyph' => '●',
    ],

    'testimonials' => [
        'name' => ['tr' => 'Referanslar', 'en' => 'Testimonials'],
        'desc' => ['tr' => 'Müşteri yorumları ve referanslar', 'en' => 'Client quotes and references'],
        'icon'  => 'quote',
        'admin' => 'admin.testimonials.index',
        'route' => 'referanslar',
        'table' => 'testimonials',
        'show_home' => true,
        'rail_limit' => 3,
        'group' => 'social',
        'icon_glyph' => '”',
    ],

    'comments' => [
        'name' => ['tr' => 'Yorumlar', 'en' => 'Comments'],
        'desc' => ['tr' => 'Ziyaretçi yorumları (onaylı yayınlanır)', 'en' => 'Visitor comments (moderated)'],
        'icon'  => 'comment',
        'admin' => 'admin.comments.index',
        'route' => null,            // public route yok, herhangi bir içerik altında
        'table' => 'comments',
        'show_home' => false,
        'rail_limit' => null,
        'group' => 'social',
        'admin_only' => true,
        'icon_glyph' => '✱',
    ],

    'faq' => [
        'name' => ['tr' => 'SSS', 'en' => 'FAQ'],
        'desc' => ['tr' => 'Sıkça sorulan sorular', 'en' => 'Frequently asked questions'],
        'icon'  => 'help',
        'admin' => 'admin.faq.index',
        'route' => 'sss',
        'table' => 'faq_items',
        'show_home' => true,
        'rail_limit' => 6,
        'group' => 'content',
        'icon_glyph' => '?',
    ],

    'social' => [
        'name' => ['tr' => 'Sosyal Medya', 'en' => 'Social Feed'],
        'desc' => ['tr' => 'Instagram / YouTube gömülü akış', 'en' => 'Embedded Instagram / YouTube feed'],
        'icon'  => 'share',
        'admin' => 'admin.social.index',
        'route' => null,
        'table' => 'social_posts',
        'show_home' => true,
        'rail_limit' => 6,
        'group' => 'social',
        'icon_glyph' => '◈',
    ],

    'pages' => [
        'name' => ['tr' => 'Sayfalar', 'en' => 'Pages'],
        'desc' => ['tr' => 'KVKK, gizlilik, çerez politikası vb.', 'en' => 'Privacy, KVKK, cookie policy, etc.'],
        'icon'  => 'page',
        'admin' => 'admin.pages.index',
        'route' => 'sayfa',
        'table' => 'pages',
        'show_home' => false,
        'rail_limit' => null,
        'group' => 'system',
        'icon_glyph' => '¶',
    ],

    'messages' => [
        'name' => ['tr' => 'Mesajlar', 'en' => 'Messages'],
        'desc' => ['tr' => 'İletişim formundan gelen mesajlar', 'en' => 'Contact form inbox'],
        'icon'  => 'inbox',
        'admin' => 'admin.messages.index',
        'route' => null,
        'table' => 'messages',
        'show_home' => false,
        'rail_limit' => null,
        'group' => 'system',
        'admin_only' => true,
        'icon_glyph' => '✉',
    ],

    'search' => [
        'name' => ['tr' => 'Arama', 'en' => 'Search'],
        'desc' => ['tr' => 'Site geneli içerik araması', 'en' => 'Site-wide content search'],
        'icon'  => 'search',
        'admin' => 'admin.search.index',
        'route' => 'ara',
        'table' => null,
        'show_home' => false,
        'rail_limit' => null,
        'group' => 'system',
        'icon_glyph' => '⌕',
    ],
];
