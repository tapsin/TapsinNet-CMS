<?php
declare(strict_types=1);

namespace Controllers\Site;

use Controllers\Controller;
use Core\Captcha;
use Core\Config;
use Core\HttpException;
use Core\Logger;
use Core\RateLimiter;
use Core\Sanitizer;
use Core\Session;
use Core\Str;
use Core\Translator;
use Core\Validator;
use Core\Response;
use Models\{Message, Settings, Service, Project};
use Core\ModuleRegistry;

/**
 * İletişim formu.
 *
 * SAVUNMA: CSRF + hız sınırı (IP bazlı) + honeypot + zaman tuzağı +
 * doğrulama. Ham HTML asla saklanmaz.
 */
final class ContactController extends Controller
{
    protected string $viewPrefix = 'site';

    public function show(): Response
    {
        return $this->view('contact', [
            'services' => ModuleRegistry::isActive('services') ? Service::list([], [], ['sort_order' => 'ASC'], 12) : [],
            'projects' => ModuleRegistry::isActive('projects') ? Project::list([], [], ['created_at' => 'DESC'], 6) : [],
            'metaTitle' => t('contact.title'),
            'metaDesc'  => Str::limit(Settings::get('contact_intro', t('contact.intro')), 160),
        ], 'layouts.site');
    }

    /**
     * Form koruması sorusunu yeniler (sistem içi mod).
     * JSON döner; sayfa yenilenmeden yeni soru gelir.
     */
    public function captcha(): Response
    {
        $mode = Captcha::mode();
        if ($mode === Captcha::MODE_BUILTIN) {
            $q = Captcha::builtinQuestion();
            return $this->json([
                'mode' => Captcha::MODE_BUILTIN,
                'text' => $q['text'],
            ]);
        }
        // reCAPTCHA'da yenilemeye gerek yok; kapalıysa da yanıt boş.
        return $this->json(['mode' => $mode, 'text' => '']);
    }

    public function submit(): Response
    {        \Core\Csrf::verifyOrFail($this->request);

        $data = [
            'name'    => mb_substr(trim((string) $this->request->post('name', '')), 0, 160),
            'email'   => mb_substr(mb_strtolower(trim((string) $this->request->post('email', ''))), 0, 190),
            'phone'   => mb_substr(trim((string) $this->request->post('phone', '')), 0, 40),
            'subject' => mb_substr(Sanitizer::plain((string) $this->request->post('subject', '')), 0, 200),
            'message' => mb_substr(Sanitizer::textarea((string) $this->request->post('message', '')), 0, 4000),
            'kvkk'    => $this->request->bool('kvkk') ? '1' : '0',
        ];

        // --- Bot tuzakları -------------------------------------------------
        // 1) Honeypot: gizli alan doldurulmuşsa bot
        if (trim((string) $this->request->post('website', '')) !== '') {
            Logger::security('Kontak formu honeypot', ['ip' => $this->request->ip()]);
            return $this->success();      // bota başarı görüntüsü ver, kaydetme
        }

        // 2) Zaman tuzağı: form 2 saniyeden hızlı gönderilmişse insan değil
        $renderedAt = (int) Session::get('contact_form_ts', 0);
        if ($renderedAt > 0 && (time() - $renderedAt) < 2) {
            Logger::security('Kontak formu zaman tuzağı', ['ip' => $this->request->ip()]);
            return $this->success();
        }
        Session::forget('contact_form_ts');

        // --- Hız sınırı -----------------------------------------------------
        $throttle = RateLimiter::throttle('contact:' . $this->request->ip(), 'contact_form');
        if (!$throttle['allowed']) {
            $wait = (int) ceil($throttle['wait'] / 60);
            return $this->withErrors(
                $this->request->all(),
                ['message' => t('contact.too_many', ['minutes' => max(1, $wait)])]
            );
        }

        // --- Doğrulama ------------------------------------------------------
        $v = Validator::make($data, [
            'name'    => 'required|string|min:2|max:160',
            'email'   => 'required|email|max:190',
            'phone'   => 'nullable|max:40',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|min:10|max:4000',
            'kvkk'    => 'required',
        ], [
            'name'    => t('contact.field.name'),
            'email'   => t('contact.field.email'),
            'phone'   => t('contact.field.phone'),
            'subject' => t('contact.field.subject'),
            'message' => t('contact.field.message'),
            'kvkk'    => t('contact.field.kvkk'),
        ]);

        if ($v->fails()) {
            return $this->withErrors($this->request->all(), $v->flatErrors());
        }

        // --- Form koruması --------------------------------------------------
        // Alan doğrulamasından SONRA çalışır: kullanıcı önce kendi hatasını
        // görsün, sonra captcha'yı doldursun. Aksi hâlde eksik alanı giderip
        // captcha'yı tekrar çözmek zorunda kalırdı.
        if (Captcha::verify($this->request) !== null) {
            return $this->withErrors($this->request->all(), ['captcha' => t('captcha.error')]);
        }

        // --- Kayıt ----------------------------------------------------------
        $id = Message::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'] !== '' ? $data['phone'] : null,
            'subject'     => $data['subject'] !== '' ? $data['subject'] : null,
            'message'     => $data['message'],
            'source_page' => mb_substr((string) $this->request->post('source', $this->request->wantsBack('/iletisim')), 0, 300),
            'module'      => mb_substr((string) $this->request->post('module', ''), 0, 40) ?: null,
            'locale'      => Translator::lang(),
            'ip'          => $this->request->ip(),
            'user_agent'  => mb_substr($this->request->userAgent(), 0, 255),
        ]);

        Logger::info('İletişim mesajı alındı', ['id' => $id]);

        // --- Bildirim -------------------------------------------------------
        $this->notify($id, $data);

        return $this->success();
    }

    private function success(): Response
    {
        Session::forget('_old');
        Session::flash('success', Settings::get('contact_success_text', t('contact.success')));
        return $this->redirect('/iletisim');
    }

    private function notify(int $id, array $data): void
    {
        $to = Settings::get('contact_notify_email', '') ?: Settings::get('email', '');
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $site = Settings::get('site_name', (string) Config::get('app.name'));
        $subject = '[' . $site . '] Yeni mesaj #' . $id . ' — ' . Str::limit($data['name'], 60);

        $body = "Yeni iletişim formu mesajı\n\n"
              . "Ad Soyad  : {$data['name']}\n"
              . "E-posta   : {$data['email']}\n"
              . ($data['phone'] !== '' ? "Telefon   : {$data['phone']}\n" : '')
              . "Konu      : " . ($data['subject'] !== '' ? $data['subject'] : '—') . "\n\n"
              . "Mesaj:\n{$data['message']}\n\n"
              . '--- ' . date('d.m.Y H:i') . ' — ' . $this->request->ip();

        // Başlık enjeksiyonu koruması: satır sonu temizlenir
        $subject = str_replace(["\r", "\n"], ' ', $subject);

        @mail(
            $to,
            '=?UTF-8?B?' . base64_encode($subject) . '?=',
            str_replace("\n", "\r\n", $body),
            implode("\r\n", [
                'From: ' . Settings::get('site_name', 'TapsinNet') . ' <no-reply@' . preg_replace('#^https?://#', '', (string) Config::get('app.url')) . '>',
                'Content-Type: text/plain; charset=UTF-8',
                'MIME-Version: 1.0',
            ])
        );
    }
}
