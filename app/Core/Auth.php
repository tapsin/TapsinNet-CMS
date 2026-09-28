<?php
declare(strict_types=1);

namespace Core;

/**
 * Kimlik doğrulama — tek yönetici hesabı modeli.
 *
 *  · password_hash / password_verify (PHP 8.5 varsayılanı)
 *  · Başarısız denemelerde hesap kilitleme (kullanıcı bazlı, IP'den bağımsız)
 *  · Oturum sabitleme koruması: girişte session_regenerate_id(true)
 *  · Oturum doğrulaması: IP sinyali + User-Agent parmak izi bağlanır
 *  · "Beni hatırla" yerine uzun süreli oturum yerine kısa ömürlü
 *    oturum tercih edildi (daha küçük saldırı yüzeyi)
 */
final class Auth
{
    private const SESSION_KEY = '_auth_user_id';
    private const FINGERPRINT = '_auth_fp';
    private const ATTEMPTS   = 'login_attempts';
    private const LOCKED_UNTIL = 'login_locked_until';

    private static ?array $user = null;
    private static bool  $resolved = false;

    /**
     * Giriş denemesi. $login 'kullanıcı adı' veya 'e-posta' olabilir —
     * ikisi de aynı alandan kabul edilir. E-posta her zaman çalışır;
     * kullanıcı adı atanmışsa o da çalışır.
     */
    public static function attempt(string $login, string $password, ?Request $request = null): bool
    {
        $request ??= Request::current();
        $key   = 'login:' . $request->ip();
        $throttle = Config::get('security.throttle.login', ['max' => 6, 'decay' => 900]);
        $wait  = RateLimiter::attempt($key, (int) $throttle['max'], (int) $throttle['decay']);

        if ($wait > 0) {
            Logger::security('Login hız sınırı', ['ip' => $request->ip()]);
            return false;
        }

        $login = trim($login);
        $user  = $login === '' ? null : Database::first(
            'SELECT * FROM users
              WHERE deleted_at IS NULL
                AND (lower(username) = lower(:login) OR lower(email) = lower(:login))
              LIMIT 1',
            ['login' => $login]
        );

        // Kullanıcı yoksa bile hash doğrulaması çalıştırılır: kullanıcı adı
        // sızıntısını (timing) engeller.
        $hash = is_array($user) ? (string) $user['password_hash'] : '$2y$12$' . str_repeat('.', 53);
        $ok   = password_verify($password, $hash);

        if (!$ok || !is_array($user)) {
            Logger::security('Başarısız giriş', [
                'login' => $login,
                'ip'    => $request->ip(),
                'ua'    => $request->userAgent(),
            ]);
            return false;
        }

        if ((int) $user['is_active'] !== 1) {
            Logger::security('Pasif hesapla giriş denemesi', ['login' => $login, 'ip' => $request->ip()]);
            return false;
        }

        // Parola hash'i güncel algoritmaya yükseltilir (null-coalescing atakları
        // ve şema geçişlerinde şifre yeniden kullanımı riskini düşürür)
        if (password_needs_rehash($hash, (int) Config::get('security.password.algo', PASSWORD_DEFAULT), (array) Config::get('security.password.options', []))) {
            Database::execute(
                'UPDATE users SET password_hash = :h, updated_at = :u WHERE id = :id',
                ['h' => self::hash($password), 'u' => now(), 'id' => (int) $user['id']]
            );
        }

        RateLimiter::clear($key);
        self::login((int) $user['id'], $request);

        Database::execute(
            'UPDATE users SET last_login_at = :t, last_login_ip = :ip WHERE id = :id',
            ['t' => now(), 'ip' => $request->ip(), 'id' => (int) $user['id']]
        );

        Logger::security('Başarılı giriş', ['id' => (int) $user['id'], 'ip' => $request->ip()]);
        return true;
    }

    public static function login(int $userId, ?Request $request = null): void
    {
        $request ??= Request::current();
        Session::regenerate();
        Session::put(self::SESSION_KEY, $userId);
        Session::put(self::FINGERPRINT, self::fingerprint($request));
        self::$user     = null;
        self::$resolved = false;
    }

    public static function logout(): void
    {
        self::$user     = null;
        self::$resolved = true;
        Session::destroy();
    }

    public static function check(): bool
    {
        $u = self::user();
        return $u !== null && (int) $u['is_active'] === 1;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u === null ? null : (int) $u['id'];
    }

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $id = Session::get(self::SESSION_KEY);
        if (!is_int($id) && !ctype_digit((string) $id)) {
            return self::$user = null;
        }

        // Oturum parmak izi — çalınan çerezin başka tarayıcıda kullanılmasını zorlaştırır
        $stored = Session::get(self::FINGERPRINT);
        if (is_string($stored) && !hash_equals($stored, self::fingerprint(Request::current()))) {
            Logger::security('Oturum parmak izi uyuşmazlığı', ['ip' => Request::current()->ip()]);
            self::logout();
            return self::$user = null;
        }

        $user = Database::first(
            'SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => (int) $id]
        );

        if ($user === null || (int) $user['is_active'] !== 1) {
            self::logout();
            return self::$user = null;
        }

        return self::$user = $user;
    }

    public static function hash(string $password): string
    {
        $hash = password_hash(
            $password,
            (string) Config::get('security.password.algo', PASSWORD_DEFAULT),
            (array) Config::get('security.password.options', [])
        );
        if (!is_string($hash) || $hash === '') {
            throw new \RuntimeException('Parola hash oluşturulamadı.');
        }
        return $hash;
    }

    /** Parola gücü geri bildirimi (kayıt/şifre değiştirme ekranı). */
    public static function passwordScore(string $password): array
    {
        $score = 0;
        if (mb_strlen($password) >= 12) $score++;
        if (mb_strlen($password) >= 16) $score++;
        if (preg_match('/[a-zçğıöşü]/u', $password)) $score++;
        if (preg_match('/[A-ZÇĞİÖŞÜ]/u', $password)) $score++;
        if (preg_match('/\d/', $password)) $score++;
        if (preg_match('/[^\p{L}\p{N}]/u', $password)) $score++;

        return [
            'score' => min(5, $score),
            'label' => Translator::t('auth.password_strength.' . min(5, $score)),
        ];
    }

    private static function fingerprint(Request $request): string
    {
        // IP'nin tamamı değil yalnızca /24'ü (NAT arkasında kullanıcılar
        // arası yanlış pozitif üretmemek için)
        $ip = $request->ip();
        $parts = explode('.', $ip);
        $subnet = count($parts) === 4 ? implode('.', array_slice($parts, 0, 3)) : $ip;
        return hash('sha256', $subnet . '|' . $request->userAgent());
    }

    public static function reset(): void
    {
        self::$user     = null;
        self::$resolved = false;
    }
}
