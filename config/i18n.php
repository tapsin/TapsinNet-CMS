<?php
/**
 * Çoklu dil yapılandırması.
 *
 * İçerik tablolarında her metin alanı `<alan>_tr` / `<alan>_en` çiftiyle
 * tutulur. Bu, admin formlarını basit iki kolonlu tutar ve sorguların
 * indeksli kalmasını sağlar (EAV çözümü N+1 ve karmaşıklık getirir).
 */
return [
    'default'  => env('APP_LOCALE', 'tr'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'tr'),
    'available'=> ['tr', 'en'],

    'cookie'   => env('LOCALE_COOKIE', 'tapsinnet_lang'),
    'session_key' => 'locale',

    'url_prefix' => false,   // /en/hizmetler biçiminde önek kullanılmasın
    'segment'     => 'lang',

    // Dil değiştiricide sunulacak adlar
    'names' => [
        'tr' => 'Türkçe',
        'en' => 'English',
    ],

    // hreflang karşılıkları
    'hreflang' => [
        'tr' => 'tr',
        'en' => 'en',
    ],
];
