<?php
declare(strict_types=1);

namespace Core;

use Models\Settings;

/**
 * Form koruması — iki moddan biri:
 *
 *  · 'off'       → kapalı (modül pasif veya anahtar boşsa)
 *  · 'builtin'   → sistem içi matematik sorusu. Sunucu tarafında üretilir,
 *                  cevap oturumda tutulur, gönderimde karşılaştırılır.
 *  · 'recaptcha' → Google reCAPTCHA v2. Site + gizli anahtar ayarlardan.
 *
 * Neden builtin mod bir resim değil: sunucuda GD yok, resim üretilemez.
 * Soru metin olarak sorulur; bu erişilebilirlik açısından da üstündür (ekran
 * okuyucu soruyu okur, kullanıcı cevabı yazar). Zayıflığı bellidir: basit
 * otomasyonlar aritmetiği çözebilir. Gerçek koruma için reCAPTCHA modunu
 * öneriyoruz.
 *
 * Mod seçimi modül durumundan gelir: config/modules.php'deki 'captcha'
 * modülü admin panelinden kapatılırsa tüm koruma kapanır.
 */
final class Captcha
{
    public const MODE_OFF       = 'off';
    public const MODE_BUILTIN   = 'builtin';
    public const MODE_RECAPTCHA = 'recaptcha';

    private const SESSION_ANSWER = '_captcha_answer';
    private const SESSION_ISSUED = '_captcha_issued';
    private const SESSION_RECAP  = '_captcha_recaptcha';

    /** Yanıt üretiminden sonra geçmesi gereken en kısa süre (saniye). */
    private const MIN_SECONDS = 2;

    /** Ayarlardan etkin mod. Modül kapalıysa veya anahtar yoksa 'off'. */
    public static function mode(): string
    {
        if (!ModuleRegistry::isActive('captcha')) {
            return self::MODE_OFF;
        }
        $mode = strtolower(trim((string) Settings::get('captcha_mode', self::MODE_OFF)));
        if (!in_array($mode, [self::MODE_BUILTIN, self::MODE_RECAPTCHA], true)) {
            return self::MODE_OFF;
        }
        if ($mode === self::MODE_RECAPTCHA) {
            // Anahtarlar yoksa mod sessizce devre dışı kalır; form kırılmaz.
            $site = trim((string) Settings::get('captcha_recaptcha_site', ''));
            $secret = trim((string) Settings::get('captcha_recaptcha_secret', ''));
            if ($site === '' || $secret === '') {
                return self::MODE_OFF;
            }
        }
        return $mode;
    }

    public static function enabled(): bool
    {
        return self::mode() !== self::MODE_OFF;
    }

    // =====================================================================
    //  BUILTIN — görünüm
    // =====================================================================

    /**
     * Soruyu üretir ve oturuma yazar. Yeniden yüklemede soru değişir,
     * eski cevap oturumdan silinir.
     *
     * @return array{a: int, b: int, op: string, text: string}
     */
    public static function builtinQuestion(): array
    {
        $a = random_int(1, 9);
        $b = random_int(2, 9);
        // İki işlemi de sunucu doğrular; istemci sadece metni görür.
        $op = random_int(0, 1) === 0 ? '+' : '-';

        if ($op === '-') {
            // Negatife düşmemesi için büyük sayı büyük olsun.
            $small = min($a, $b);
            $big   = max($a, $b);
            $a = $big;
            $b = $small;
            $answer = $a - $b;
        } else {
            $answer = $a + $b;
        }

        $q = ['a' => $a, 'b' => $b, 'op' => $op, 'answer' => $answer];
        Session::put(self::SESSION_ANSWER, $answer);
        Session::put(self::SESSION_ISSUED, time());

        return [
            'a'    => $a,
            'b'    => $b,
            'op'   => $op,
            'text' => $a . ' ' . $op . ' ' . $b . ' =',
        ];
    }

    /** Form üzerinde yeniden basılacak ölü kutu — gönderilmez. */
    public static function builtinDecoy(): string
    {
        return (string) random_int(10000, 99999);
    }

    // =====================================================================
    //  DOĞRULAMA
    // =====================================================================

    /**
     * Gelen isteği doğrular. Mod kapalıysa her zaman geçer.
     * Hata durumunda anahtar hata etiketidir; çağıran bunu
     * Validator/withErrors içine koyar.
     *
     * @return string|null Hata yoksa null, varsa hata anahtarı
     */
    public static function verify(Request $request, string $field = 'captcha'): ?string
    {
        $mode = self::mode();
        if ($mode === self::MODE_OFF) {
            return null;
        }

        return $mode === self::MODE_BUILTIN
            ? self::verifyBuiltin($request, $field)
            : self::verifyRecaptcha($request, $field);
    }

    private static function verifyBuiltin(Request $request, string $field): ?string
    {
        // 1) Gizli tuzak alanı doluysa bot.
        if (trim((string) $request->post('captcha_hp', '')) !== '') {
            Logger::security('Captcha tuzak alanı doldu');
            return $field;
        }

        // 2) Soru üretilmiş mi?
        $expected = Session::get(self::SESSION_ANSWER);
        if ($expected === null) {
            Logger::security('Captcha sorusu yok — form yenilenmemiş');
            return $field;
        }

        // 3) Zaman tuzağı: soru gösterildikten hemen sonra gönderim bot işareti.
        $issued = (int) Session::get(self::SESSION_ISSUED, 0);
        if ($issued > 0 && (time() - $issued) < self::MIN_SECONDS) {
            Session::forget(self::SESSION_ANSWER);
            Session::forget(self::SESSION_ISSUED);
            return $field;
        }

        // 4) Kullanıcının cevabı (tek kullanımlık olsun diye önce sil).
        $given = trim((string) $request->post('captcha_answer', ''));
        Session::forget(self::SESSION_ANSWER);
        Session::forget(self::SESSION_ISSUED);

        if ($given === '' || !is_numeric($given)) {
            return $field;
        }
        if ((int) $given !== (int) $expected) {
            Logger::security('Captcha cevabı yanlış', ['ip' => $request->ip()]);
            return $field;
        }

        return null;
    }

    private static function verifyRecaptcha(Request $request, string $field): ?string
    {
        $secret = trim((string) Settings::get('captcha_recaptcha_secret', ''));
        $token  = trim((string) $request->post('g-recaptcha-response', ''));

        if ($token === '') {
            return $field;
        }

        $verdict = self::recaptchaVerify($secret, $token, $request->ip());
        // Doğrulama yapılamadıysa (ağ hatası) formu kilitlemiyoruz —
        // kullanıcı geçici bir ağ sorunu yüzünden mesajını kaybeder.
        if ($verdict === null) {
            Logger::error('reCAPTCHA doğrulaması yapılamadı');
            return null;
        }
        if ($verdict !== true) {
            Logger::security('reCAPTCHA reddetti', ['ip' => $request->ip()]);
            return $field;
        }

        Session::forget(self::SESSION_RECAP);
        return null;
    }

    /**
     * Google'a sunucu tarafı doğrulama.
     *
     * @return bool|null true=geçti false=reddedildi null=doğrulanamadı
     */
    public static function recaptchaVerify(string $secret, string $token, string $ip = ''): ?bool
    {
        $payload = ['secret' => $secret, 'response' => $token];
        if ($ip !== '') {
            $payload['remoteip'] = $ip;
        }

        $json = null;
        if (function_exists('curl_init')) {
            $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $raw = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            if ($raw === false || $code < 200 || $code >= 300) {
                return null;
            }
            $json = json_decode((string) $raw, true);
        } else {
            $ctx  = stream_context_create(['http' => [
                'method'        => 'POST',
                'header'        => 'Content-Type: application/x-www-form-urlencoded',
                'content'       => http_build_query($payload),
                'timeout'       => 8,
                'ignore_errors' => true,
            ]]);
            $raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
            if ($raw === false) {
                return null;
            }
            $json = json_decode((string) $raw, true);
        }

        if (!is_array($json)) {
            return null;
        }
        // Başarı alanı yoksa hata nesnesidir (timeout, bad-request…):
        // doğrulama yapılamadı, reddetme sayma.
        if (!array_key_exists('success', $json)) {
            return null;
        }
        return $json['success'] === true;
    }

    public static function siteKey(): string
    {
        return trim((string) Settings::get('captcha_recaptcha_site', ''));
    }
}
