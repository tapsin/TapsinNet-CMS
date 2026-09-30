<?php
/**
 * Veritabanı ayarları — SQLite.
 *
 * Veritabanı dosyası web kökünün DIŞINDA (storage/database/) tutulur.
 * Böylece .sqlite dosyasına doğrudan HTTP isteğiyle erişilemez.
 */
return [
    'default' => 'sqlite',

    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE', dirname(__DIR__) . '/storage/database/app.sqlite'),
            'prefix'   => '',
            'foreign_key_constraints' => env_bool('DB_FOREIGN_KEYS', true),
            'busy_timeout' => (int) env('DB_BUSY_TIMEOUT', 5000),
            'journal_mode' => env('DB_JOURNAL_MODE', 'WAL'),
            'synchronous'  => env('DB_SYNCHRONOUS', 'NORMAL'),
            'cache' => 'file',
        ],
    ],
];
