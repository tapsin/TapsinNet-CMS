<?php
declare(strict_types=1);

namespace Core;

/**
 * CSRF koruması — senkronizasyon jetonu deseni.
 *
 *  · Token oturumda saklanır, forma gizli alan olarak basılır
 *  · Karşılaştırma hash_equals() ile zamanlama saldırısına dayanıksızdır
 *  · AJAX isteklerinde X-CSRF-Token başlığı da kabul edilir
 */
final class Csrf
{
    private const KEY = '_csrf_token';
    private const ISSUED = '_csrf_issued';

    public static function token(): string
    {
        $token = Session::get(self::KEY);
        $issued = (int) Session::get(self::ISSUED, 0);
        $lifetime = (int) Config::get('security.csrf.lifetime', 7200);

        if (!is_string($token) || $token === '' || (time() - $issued) > $lifetime) {
            $token = bin2hex(random_bytes(32));
            Session::put(self::KEY, $token);
            Session::put(self::ISSUED, time());
        }
        return $token;
    }

    public static function field(): string
    {
        $name = (string) Config::get('security.csrf.token_name', '_token');
        return '<input type="hidden" name="' . e($name) . '" value="' . e(self::token()) . '">';
    }

    public static function check(?Request $request = null): bool
    {
        $request ??= Request::current();
        $name  = (string) Config::get('security.csrf.token_name', '_token');
        $store = Session::get(self::KEY);
        if (!is_string($store) || $store === '') {
            return false;
        }

        $given = $request->post($name);
        if (!is_string($given) || $given === '') {
            $given = $request->header((string) Config::get('security.csrf.header_name', 'X-CSRF-Token'));
        }

        return is_string($given) && $given !== '' && hash_equals($store, $given);
    }

    public static function verifyOrFail(?Request $request = null): void
    {
        $request ??= Request::current();
        if (self::check($request)) {
            return;
        }

        Logger::security('CSRF doğrulama başarısız', [
            'ip'   => $request->ip(),
            'path' => $request->path(),
            'ua'   => $request->userAgent(),
        ]);

        if ($request->expectsJson()) {
            Response::json(['error' => 'Oturum anahtarı geçersiz. Sayfayı yenileyip tekrar deneyin.'], 419)
                ->send();
        } else {
            Response::html(
                '<!doctype html><meta charset="utf-8"><title>419</title>'
                . '<p style="font:16px system-ui;padding:2rem">Oturum anahtarı geçersiz. '
                . '<a href="' . e($request->wantsBack('/')) . '">Geri dön</a></p>',
                419
            )->send();
        }
        exit;
    }
}
