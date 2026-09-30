<?php
/**
 * Güvenlik politikası.
 *
 * Buradaki değerler güvenli varsayılandır; .env ile gevşetilmemelidir.
 * Production'da APP_DEBUG=false ve APP_ENV=production zorunludur.
 */
return [
    // --- Oturum ---
    'session' => [
        'name'            => env('SESSION_NAME', 'tapsinnet_sid'),
        'lifetime'        => (int) env('SESSION_LIFETIME', 7200),   // 2 saat
        'regenerate_every'=> 300,                                    // 5 dakikada bir kimlik yenile
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => env_bool('SESSION_SECURE', false),      // HTTPS'te true yapılmalı
        'use_strict_mode' => true,
    ],

    // --- CSRF ---
    'csrf' => [
        'token_name'   => '_token',
        'header_name'  => 'X-CSRF-Token',
        'lifetime'     => 7200,
    ],

    // --- Parola ---
    'password' => [
        'algo'            => PASSWORD_DEFAULT,   // PHP 8.5 -> bcrypt/argon2 seçimi
        'options'         => ['cost' => 12],
        'min_length'      => 12,
        'max_length'      => 200,
        'max_failed_attempts' => 6,
        'lockout_seconds' => 900,                // 15 dk
    ],

    // --- Yükleme ---
    'upload' => [
        'max_image_size' => 5 * 1024 * 1024,     // 5 MB
        'max_file_size'  => 10 * 1024 * 1024,    // 10 MB (sertifika PDF vb.)
        'image_mimes'    => ['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif'],
        'file_mimes'     => ['application/pdf'],
        'image_ext'      => ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'],
        'file_ext'       => ['pdf'],
        'random_name'    => true,                // kullanıcı dosya adı ASLA kullanılmaz
        'strip_metadata' => true,                // EXIF temizliği
    ],

    // --- Hız sınırı (rate limit) ---
    'throttle' => [
        'contact_form'  => ['max' => 3,  'decay' => 600],
        'comment_form'  => ['max' => 5,  'decay' => 600],
        'login'         => ['max' => 6,  'decay' => 900],
    ],

    // --- Güvenlik başlıkları ---
    'headers' => [
        'X-Content-Type-Options'  => 'nosniff',
        'X-Frame-Options'         => 'SAMEORIGIN',
        'Referrer-Policy'         => 'strict-origin-when-cross-origin',
        'Permissions-Policy'      => 'geolocation=(), microphone=(), camera=()',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        'Strict-Transport-Security' => env('HSTS', ''), // HTTPS'te 'max-age=31536000; includeSubDomains'
    ],

    // --- CSP ---
    // 'unsafe-inline' YOK: tüm script'ler harici dosyadan, stiller tokens.css'ten.
    'csp' => [
        'enabled'    => true,
        'report_only'=> false,
        'directives' => [
            'default-src'    => ["'self'"],
            'base-uri'       => ["'self'"],
            'form-action'    => ["'self'"],
            'frame-ancestors' => ["'self'"],
            'object-src'     => ["'none'"],
            'script-src'     => ["'self'"],
            'style-src'      => ["'self'"],
            'img-src'        => ["'self'", 'data:', 'blob:', 'https://i.ytimg.com', 'https://i.vimeocdn.com'],
            'font-src'       => ["'self'"],
            'media-src'      => ["'self'", 'https://www.youtube.com', 'https://player.vimeo.com'],
            'frame-src'      => ['https://www.youtube-nocookie.com', 'https://player.vimeo.com', 'https://www.youtube.com'],
            'connect-src'    => ["'self'"],
            'upgrade-insecure-requests' => env_bool('CSP_UPGRADE', false),
        ],
    ],

    // --- Trust proxy (yalnızca gerçekten ters proxy arkasındaysanız açın) ---
    'trust_proxy' => env_bool('TRUST_PROXY', false),
];
