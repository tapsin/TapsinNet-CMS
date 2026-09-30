<?php
/**
 * Uygulama ayarları.
 * Tüm değerler .env üzerinden ezilebilir; ortam değişikliği bu dosyayı
 * değiştirmeden yönetir.
 */
return [
    'name'      => env('APP_NAME', 'TapsinNet'),
    'env'       => env('APP_ENV', 'production'),
    'debug'     => env_bool('APP_DEBUG', false),
    'url'       => rtrim(env('APP_URL', 'http://localhost:8000'), '/'),
    'timezone'  => env('APP_TIMEZONE', 'Europe/Istanbul'),
    'locale'    => env('APP_LOCALE', 'tr'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'tr'),
    'key'       => env('APP_KEY'),

    // Site kökü altındaki yükleme klasörü (web kökünden bağıl)
    'upload_path' => 'uploads',

    'per_page' => (int) env('APP_PER_PAGE', 9),
    'admin_per_page' => (int) env('ADMIN_PER_PAGE', 20),

    // Ana sayfada her rayda gösterilecek kayıt sayısı
    'home_rail_limit' => (int) env('HOME_RAIL_LIMIT', 5),

    'date_format' => 'd F Y',
];
