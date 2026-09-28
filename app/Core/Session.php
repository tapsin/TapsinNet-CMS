<?php
declare(strict_types=1);

namespace Core;

/**
 * Oturum yönetimi — sıkı güvenlik varsayılanlarıyla.
 *
 *  · use_strict_mode : tanımsız oturum kimliği reddedilir (session fixation)
 *  · httponly        : JS oturum çerezine erişemez
 *  · samesite=Lax    : CSRF'e karşı temel koruma
 *  · periyodik kimlik yenileme
 *  · session.use_strict_mode + GC probabilistik temizlik
 */
final class Session
{
    private static bool $started = false;
    private static int $lastRegenerate = 0;

    public static function start(): void
    {
        if (self::$started || PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $cfg   = Config::get('security.session');
        $isHttps = Request::current()->isSecure();

        ini_set('session.use_strict_mode', $cfg['use_strict_mode'] ? '1' : '0');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
        ini_set('session.gc_maxlifetime', (string) $cfg['lifetime']);
        ini_set('session.serializer', 'php_serialize');

        session_name((string) $cfg['name']);
        session_set_cookie_params([
            'lifetime' => 0,                 // tarayıcı oturumu
            'path'     => '/',
            'domain'   => '',
            'secure'   => (bool) ($cfg['cookie_secure'] || $isHttps),
            'httponly' => (bool) $cfg['cookie_httponly'],
            'samesite' => (string) $cfg['cookie_samesite'],
        ]);

        session_start();
        self::$started   = true;
        self::$lastRegenerate = time();

        // Aylık oturum çerezi yenilemesi
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $v = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $v;
    }

    public static function all(): array
    {
        return $_SESSION ?? [];
    }

    public static function regenerate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        session_regenerate_id(true);
        self::$lastRegenerate = time();
    }

    /** Periyodik kimlik yenileme — oturum sabitleme penceresini daraltır. */
    public static function maybeRegenerate(): void
    {
        $every = (int) Config::get('security.session.regenerate_every', 300);
        if ($every > 0 && (time() - self::$lastRegenerate) > $every) {
            self::regenerate();
        }
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
        self::$started = false;
    }

    /** Flash mesajlar. */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function flashes(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return is_array($f) ? $f : [];
    }
}
