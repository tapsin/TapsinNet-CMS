<?php
declare(strict_types=1);

namespace Core;

use RuntimeException;

/**
 * HTTP durum kodları için özel istisna.
 */
final class HttpException extends RuntimeException
{
    private int $statusCode;

    public function __construct(int $statusCode, string $message = '', ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message !== '' ? $message : self::defaultTitle($statusCode), $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public static function defaultTitle(int $status): string
    {
        return Translator::t('errors.' . $status, [], match ($status) {
            400 => 'Geçersiz istek',
            401 => 'Yetkilendirme gerekli',
            403 => 'Erişim reddedildi',
            404 => 'Sayfa bulunamadı',
            419 => 'Oturum anahtarı geçersiz',
            422 => 'Doğrulama hatası',
            429 => 'Çok fazla istek',
            500 => 'Sunucu hatası',
            503 => 'Servis geçici olarak kullanılamıyor',
            default => 'Bir şeyler ters gitti',
        });
    }
}
