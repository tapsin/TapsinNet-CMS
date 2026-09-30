<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Csrf;
use Core\Database;
use Core\Response;
use Models\Settings;

/**
 * Site ayarları.
 *
 * Ayarlar gruplar hâlinde düzenlenir. Şema gruplar:
 *   general  · Kimlik, iletişim, sosyal bağlantılar
 *   seo      · Meta varsayılanları, doğrulama
 *   home     · Ana sayfa metinleri (hero, CTA)
 *   contact  · İletişim formu ve KVKK metinleri
 */
final class SettingController extends Controller
{
    protected string $viewPrefix = 'admin';
    protected string $indexRoute = '/admin/ayarlar';

    /** Ayar şeması — anahtar, tip, etiket, ipucu. */
    public const SCHEMA = [
        'general' => [
            'label' => 'admin.settings.general',
            'icon'  => '◉',
            'fields' => [
                'site_name'        => ['type' => 'text',     'label' => 'Site adı', 'rules' => 'required|string|max:120'],
                'site_tagline'     => ['type' => 'text',     'label' => 'Slogan (kısa)', 'lang' => true, 'rules' => 'nullable|string|max:180'],
                'site_description' => ['type' => 'textarea', 'label' => 'Site açıklaması', 'lang' => true, 'rules' => 'nullable|string|max:320'],
                'logo_image'       => ['type' => 'image',    'label' => 'Logo'],
                'favicon_image'    => ['type' => 'image',    'label' => 'Favicon'],
                'email'            => ['type' => 'email',    'label' => 'İletişim e-postası', 'rules' => 'nullable|email|max:190'],
                'phone'            => ['type' => 'tel',      'label' => 'Telefon', 'rules' => 'nullable|max:40'],
                'whatsapp'         => ['type' => 'text',     'label' => 'WhatsApp numarası', 'hint' => '+90 5xx xxx xx xx', 'rules' => 'nullable|max:40'],
                'address'          => ['type' => 'text',     'label' => 'Adres', 'rules' => 'nullable|max:240'],
                'working_hours'    => ['type' => 'text',     'label' => 'Çalışma saatleri', 'rules' => 'nullable|max:120'],
                'linkedin'         => ['type' => 'text',     'label' => 'LinkedIn', 'rules' => 'nullable|url|max:300'],
                'github'           => ['type' => 'text',     'label' => 'GitHub', 'rules' => 'nullable|url|max:300'],
                'instagram'        => ['type' => 'text',     'label' => 'Instagram', 'rules' => 'nullable|url|max:300'],
                'twitter'          => ['type' => 'text',     'label' => 'X (Twitter)', 'rules' => 'nullable|url|max:300'],
                'youtube'          => ['type' => 'text',     'label' => 'YouTube', 'rules' => 'nullable|url|max:300'],
                'dribbble'         => ['type' => 'text',     'label' => 'Dribbble', 'rules' => 'nullable|url|max:300'],
            ],
        ],

        'home' => [
            'label' => 'admin.settings.home',
            'icon'  => '▦',
            'fields' => [
                'home_hero_eyebrow' => ['type' => 'text',     'label' => 'Hero üst etiket', 'lang' => true, 'rules' => 'nullable|string|max:80'],
                'home_hero_title'   => ['type' => 'text',     'label' => 'Hero başlık', 'lang' => true, 'rules' => 'nullable|string|max:120'],
                'home_hero_text'    => ['type' => 'textarea', 'label' => 'Hero açıklama', 'lang' => true, 'rules' => 'nullable|string|max:320'],
                'home_cta_text'     => ['type' => 'text',     'label' => 'Ana buton metni', 'lang' => true, 'rules' => 'nullable|string|max:40'],
                'home_cta2_text'    => ['type' => 'text',     'label' => 'İkinci buton metni', 'lang' => true, 'rules' => 'nullable|string|max:40'],
                'home_about_title'  => ['type' => 'text',     'label' => 'Hakkımda başlığı', 'lang' => true, 'rules' => 'nullable|string|max:120'],
                'home_about_text'   => ['type' => 'textarea', 'label' => 'Hakkımda metni', 'lang' => true, 'rules' => 'nullable|string|max:1200'],
                'home_cv_link'      => ['type' => 'text',     'label' => 'CV indirme bağlantısı', 'rules' => 'nullable|max:300'],
            ],
        ],

        'gallery' => [
            'label' => 'Galeri',
            'icon'  => '▦',
            'fields' => [
                'gallery_watermark' => [
                    'type'  => 'image',
                    'label' => 'Galeri watermark resmi',
                    'hint'  => 'Galeri görsellerinin ortasına çapraz ve düşük opaklıkla uygulanır.',
                ],
            ],
        ],

        'contact' => [
            'label' => 'admin.settings.contact',
            'icon'  => '✉',
            'fields' => [
                'contact_intro'        => ['type' => 'textarea', 'label' => 'İletişim sayfası girişi', 'lang' => true, 'rules' => 'nullable|string|max:600'],
                'contact_kvkk_text'    => ['type' => 'textarea', 'label' => 'KVKK aydınlatma metni', 'lang' => true, 'rules' => 'nullable|string|max:2000'],
                'contact_success_text'  => ['type' => 'textarea', 'label' => 'Başarılı gönderim mesajı', 'lang' => true, 'rules' => 'nullable|string|max:400'],
                'contact_notify_email'  => ['type' => 'email',    'label' => 'Bildirim e-postası (boş = yönetici e-postası)', 'rules' => 'nullable|email|max:190'],
            ],
        ],

        'captcha' => [
            'label' => 'admin.settings.captcha',
            'icon'  => '◈',
            'fields' => [
                'captcha_mode' => [
                    'type'    => 'select',
                    'label'   => 'Koruma modu',
                    'hint'    => 'Sistem içi: sunucuda basit bir soru sorar, reCAPTCHA gerektirmez. ' .
                                 'reCAPTCHA: Google anahtarlarınızı girin, çok daha güçlü koruma sağlar. ' .
                                 'Modülün kendisi /admin/moduller sayfasından da kapatılabilir.',
                    'rules'   => 'nullable|string|max:20',
                    'options' => [
                        'off'       => 'Kapalı',
                        'builtin'   => 'Sistem içi soru',
                        'recaptcha' => 'Google reCAPTCHA v2',
                    ],
                ],
                'captcha_recaptcha_site' => [
                    'type'  => 'text',
                    'label' => 'reCAPTCHA site anahtarı',
                    'hint'  => 'google.com/recaptcha/admin — "Site anahtarı" değeri (6Lc… ile başlar).',
                    'rules' => 'nullable|max:200',
                ],
                'captcha_recaptcha_secret' => [
                    'type'  => 'secret',
                    'label' => 'reCAPTCHA gizli anahtar',
                    'hint'  => 'Aynı sayfadaki "Gizli anahtar". Formlarda ASLA gösterilmez.',
                    'rules' => 'nullable|max:200',
                ],
            ],
        ],

        'seo' => [
            'label' => 'admin.settings.seo',
            'icon'  => '◇',
            'fields' => [
                'meta_title'       => ['type' => 'text',     'label' => 'Varsayılan sayfa başlığı', 'rules' => 'nullable|string|max:160'],
                'meta_description' => ['type' => 'textarea', 'label' => 'Varsayılan meta açıklama', 'rules' => 'nullable|string|max:320'],
                'meta_keywords'    => ['type' => 'text',     'label' => 'Anahtar kelimeler', 'hint' => 'Virgülle ayır', 'rules' => 'nullable|string|max:300'],
                'og_image'         => ['type' => 'image',    'label' => 'Paylaşım görseli (OG)'],
                'google_analytics' => ['type' => 'text',     'label' => 'Google Analytics kimliği', 'hint' => 'G-XXXXXXXXXX', 'rules' => 'nullable|max:40'],
                'show_index'       => ['type' => 'bool',     'label' => 'Arama motorlarına izin ver', 'hint' => 'Kapalıysa sayfa dizine eklenmez'],
            ],
        ],
    ];

    public function index(): Response
    {
        return $this->view('settings', [
            'schema' => self::SCHEMA,
            'values' => Settings::all(),
        ], 'layouts.admin');
    }

    public function save(): Response
    {
        Csrf::verifyOrFail($this->request);

        $input   = $this->request->all();
        $uploader = new \Core\Uploader();
        $saved   = 0;
        $failed  = [];

        foreach (self::SCHEMA as $group => $definition) {
            foreach ($definition['fields'] as $key => $field) {
                $type = (string) $field['type'];

                // Formda o alan hiç basılmadıysa (kısmi gönderim) mevcut
                // değeri SİLME — yoksa eksik bir POST ayarları sessizce boşaltır.
                $hasValue = array_key_exists($key, $input)
                    || array_key_exists($key . '_tr', $input)
                    || ($type === 'image' && $this->request->file($key) !== null)
                    || $this->request->bool('sil_' . $key);
                if (!$hasValue) {
                    continue;
                }

                if ($type === 'bool') {
                    Settings::set($key, $this->request->bool($key) ? '1' : '0', null, 'bool', $group);
                    $saved++;
                    continue;
                }

                if ($type === 'image') {
                    $uploaded = $uploader->image($key, 'settings', $this->request);
                    if ($uploaded !== null) {
                        Settings::set($key, $uploaded, null, 'image', $group);
                        $saved++;
                    } elseif ($this->request->bool('sil_' . $key)) {
                        $old = Settings::get($key);
                        \Core\Uploader::delete($old !== '' ? $old : null);
                        Settings::set($key, null, null, 'image', $group);
                        $saved++;
                    }
                    continue;
                }

                if ($type === 'secret') {
                    // Form maskeli değer gönderir ("********") veya boş bırakır.
                    // · maske geldiyse  → mevcut anahtar KORUNUR, kullanıcı yeni
                    //   yazmadı demektir
                    // · "sil" işaretliyse → anahtar silinir
                    // · gerçek değer    → kaydedilir
                    $incoming = trim((string) ($input[$key] ?? ''));
                    $old      = (string) Settings::get($key, '');

                    if ($this->request->bool('sil_' . $key)) {
                        Settings::set($key, null, null, 'secret', $group);
                        $saved++;
                        continue;
                    }
                    if ($incoming === '' || $incoming === '********') {
                        if ($old !== '') {
                            $saved++;   // dokunulmadı, sayım için
                        }
                        continue;
                    }
                    Settings::set($key, $incoming, null, 'secret', $group);
                    $saved++;
                    continue;
                }

                if ($type === 'select') {
                    $allowed = array_map('strval', array_keys((array) ($field['options'] ?? [])));
                    $picked  = trim((string) ($input[$key] ?? 'off'));
                    if (!in_array($picked, $allowed, true)) {
                        $picked = 'off';
                    }
                    Settings::set($key, $picked, null, 'select', $group);
                    $saved++;
                    continue;
                }

                // İki dilli alanlar `ad_tr` / `ad_en` olarak gelir; tek dilli
                // alanlar doğrudan `ad` altında post edilir.
                $isBilingual = (bool) ($field['lang'] ?? false);
                $tr = trim((string) ($isBilingual
                    ? ($input[$key . '_tr'] ?? '')
                    : ($input[$key] ?? '')));
                $en = trim((string) ($isBilingual ? ($input[$key . '_en'] ?? '') : ''));

                $max = match ($type) {
                    'textarea' => 4000,
                    'text'     => 400,
                    default    => 1000,
                };
                $tr = mb_substr($tr, 0, $max);
                $en = mb_substr($en, 0, $max);

                // e-posta alanlarında biçim kontrolü
                if ($type === 'email' && $tr !== '' && !filter_var($tr, FILTER_VALIDATE_EMAIL)) {
                    $failed[$key] = t('validation.email');
                    continue;
                }
                if ($type === 'text' && isset($field['rules']) && str_contains($field['rules'], 'url') && $tr !== ''
                    && !filter_var($tr, FILTER_VALIDATE_URL)) {
                    $failed[$key] = t('validation.url');
                    continue;
                }

                Settings::set($key, $tr, $en, $type, $group);
                $saved++;
            }
        }

        $uploadErrors = $uploader->errors();
        if ($uploadErrors !== []) {
            $failed = array_merge($failed, $uploadErrors);
        }

        if ($failed !== []) {
            return $this->withErrors([], $failed);
        }

        return $this->ok(t('admin.settings_saved', ['n' => $saved]));
    }

    public function delete(): Response
    {
        Csrf::verifyOrFail($this->request);
        $key = (string) $this->request->post('key', '');

        if (preg_match('/^[a-z0-9_]{2,60}$/', $key)) {
            $old = Settings::get($key);
            \Core\Uploader::delete($old !== '' ? $old : null);
            Settings::delete($key);
            return $this->ok(t('admin.settings_deleted'));
        }

        return $this->fail(t('admin.unknown_action'));
    }
}
