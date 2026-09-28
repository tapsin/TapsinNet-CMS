# TapsinNet

**Freelance portfolyo ve içerik yönetim sistemi.**
PHP 8.1+ · SQLite · Composer yok · derleme adımı yok · harici bağımlılık yok.

**Yazar / Geliştirici:** Sercan TAPSIN
**Sürüm:** 1.0.0

---

## Doğrulanmış ortam

| Bileşen | Sürüm |
|---|---|
| PHP | 8.5.10 |
| SQLite | 3.53.4 |
| Otomatik test | 150 test, 0 başarısız |
| Ölçek | ~16.700 satır PHP · 14 modül · 10 font dosyası |

### Proje yapısı

```
app/Core/          Çerçeve (23 dosya)
app/Models/        İçerik modelleri (17)
app/Controllers/   Site + Admin (30)
app/Views/         Şablonlar ve partial'lar (68)
config/            Uygulama, modül, veritabanı ayarları
database/          schema.sql
lang/              tr.php · en.php
public/            Belge kökü: index.php, assets/, uploads/
routes/            web.php · admin.php
storage/           Veritabanı, log, önbellek (depoya girmez)
tests/             smoke.php ve ölçüm araçları
bin/               console · serve · demo-images
```

### Gelen modüller

`services` · `projects` · `certificates` · `gallery` · `videos` · `news` ·
`profiles` · `testimonials` · `comments` · `faq` · `social` · `pages` ·
`messages` · `search`

### Güvenlik özeti

| Konu | Uygulama |
|---|---|
| Parolalar | `password_hash` / `password_verify`, otomatik rehash |
| Giriş | Kullanıcı adı **veya** e-posta; IP başına 6 deneme / 15 dk |
| Kullanıcı sızıntısı | Kullanıcı yoksa bile hash doğrulaması çalışır (timing) |
| CSRF | Tüm POST formlarında zorunlu token |
| Form bot tuzağı | Honeypot alanı + 2 saniyelik zaman tuzağı |
| SQL | Tüm sorgular hazır ifade; kullanıcı girdisi yalnızca bağlanır |
| Çıktı | `e()` varsayılan; `safe_html()` yalnızca ayarlanabilir alanlarda |
| Depoya girmez | `.env`, `storage/database/*.sqlite`, yedekler, `*.zip` |

### Tasarım sistemi

Tasarım kararları tek yerde yaşar: `public/assets/css/tokens.css`. Yeni renk,
gölge veya ölçü önce oraya isimli token olarak yazılır, sonra `var(--token)`
ile referans verilir; ham OKLCH/hex şablonlarda bulunmaz.

- **Renk** — OKLCH tabanlı sıcak krem zemin, tek okşer vurgu
- **Tipografi** — üç aile: Fraunces, IBM Plex Sans, JetBrains Mono
- **Gölge** — yalnızca açılır menü ve modal; kart/buton/input asla gölgeli değil
- **Izgara** — 12 kolon; `form-grid` alanları `col-*` taşımak zorunda
- **Derleme yok** — `asset()` `filemtime` ile önbellek kırar

### Testleri çalıştırma

```sh
php tests/smoke.php            # 150 test — sözleşme, güvenlik, bütünlük
bash tests/header-probe.sh     # headless tarayıcıda header ölçümü
bash tests/contact-probe.sh    # iletişim ve yorum formu ölçümü
bash tests/related-probe.sh    # ilgili içerikler bölümü denetimi
```

`smoke.php` veritabanını değiştirmez; çalıştırmak güvenlidir.

### Paylaşıma hazır paket

Depodaki `TapsinNet-CMS-1.0.0.zip` dosyası `storage/database/*.sqlite` ve
`.env` dosyalarını **içermez**. Açtıktan sonra kurulumu kendi adresinizle
çalıştırın:

```sh
php bin/console install "https://siteniz.com" "e-posta@site.com" "Parolaniz"
```

---

## Bu ne işe yarar

Bir freelance web geliştiricinin (ya da tasarımcının, danışmanın) kendi işlerini
sergilediği, işleri, hizmetleri, sertifikaları, galeriyi, videoları, haberleri,
referanslarını ve profillerini **tek panelden** yönettiği bir sitedir.

Her içerik türü bir **modüldür**; panelden açılıp kapatılır. Kapatılan modül
menüden düşer, sayfaları 404 döner, ana sayfada görünmez — ama içerikleri
**silinmez**, tekrar açıldığında yerinde durur.

---

## Hızlı başlangıç

```bash
# 1) Kurulum (şema + örnek içerik + yönetici hesabı)
php bin/console install "http://localhost:8000" "ornek@site.com" "GucluBirParola123"

# 2) Geliştirme sunucusu
php -S 127.0.0.1:8000 -t public public/router.php

# 3) Tarayıcı
#    Site   → http://127.0.0.1:8000
#    Panel  → http://127.0.0.1:8000/admin
```

**Üretim kurulumu:** web kökünü `public/` olarak ayarlayın. `public/.htaccess`
Apache için hazırdır; nginx'te `try_files $uri /index.php?$query_string;` yeterlidir.

---

## Komutlar

| Komut | Ne yapar |
|---|---|
| `php bin/console install [url] [email] [parola]` | Kurulum + örnek içerik + yönetici hesabı |
| `php bin/console migrate [--fresh]` | Şemayı uygular (idempotent) |
| `php bin/console seed` | Örnek içerik yükler (var olanlara dokunmaz) |
| `php bin/console admin:create <email> <parola>` | Yeni yönetici |
| `php bin/console modules [--on=slug\|--off=slug]` | Modül durumunu gör / değiştir |
| `php bin/console optimize` | SQLite `VACUUM` + `ANALYZE` |
| `php bin/console backup` | `storage/database/backup-<tarih>.sqlite` |
| `php bin/console check` | **Sistem kontrolü** — ortam, izin, yapılandırma, indeksler |

### Testler

```bash
php tests/smoke.php        # 150 test — çekirdek, model, güvenlik, i18n
bash tests/admin-e2e.sh    # 24 test  — panel CRUD, CSRF, yetki, modül anahtarı
```

`admin-e2e.sh` çalışırken bir sunucu açık olmalı (`BASE_URL` ile ayarlanabilir).
Test, kendi verisini oluşturur ve temizler; ayarları geri yükler.

---

## Dizin yapısı

```
├── app/
│   ├── Core/            Çekirdek: Router, Database, Auth, Csrf, View, Uploader…
│   ├── Controllers/
│   │   ├── Site/        Genel ziyaretçi sayfaları
│   │   └── Admin/       Panel — ResourceController ile jenerik CRUD
│   ├── Models/          16 tablo, ortak Model katmanı
│   ├── Views/           Saf PHP şablonlar (derleme yok)
│   ├── Modules/         Modül kataloğu tek kaynağı: config/modules.php
│   └── Support/         Yardımcı fonksiyonlar (e(), t(), url(), loc()…)
├── bootstrap/app.php    Önyükleyici: otomatik yükleyici + config
├── config/              app · database · security · i18n · modules
├── database/            schema.sql
├── lang/                tr.php · en.php
├── public/              ← WEB KÖKÜ
│   ├── index.php        Ön denetleyici
│   ├── router.php       Yalnızca geliştirme sunucusu için
│   ├── assets/css/      tokens.css · theme.css · admin.css
│   ├── assets/js/       app.js · admin.js  (sıfır bağımlılık)
│   ├── assets/fonts/    Self-host woff2 — üçüncü taraf isteği YOK
│   └── uploads/         Yükleme klasörü — PHP yürütmesi kapalı
├── routes/              web.php · admin.php
├── storage/             Veritabanı, log, önbellek (web kökü DIŞINDA)
├── tests/               smoke.php · admin-e2e.sh
├── bin/console          Konsol aracı
└── docs/agents/         Ajan rol tanımları ve görünüm sözleşmesi
```

---

## Modüller

`config/modules.php` sistemin **tek gerçek kaynağıdır**. Tanım, public rota,
admin rotası, ana sayfada gösterilip gösterilmeyeceği ve ray limiti orada yazılıdır.
Durum (`aktif` / `pasif`) `modules` tablosunda tutulur ve panelden değiştirilir.

| Modül | Public yol | Panel | Ana sayfa |
|---|---|---|---|
| Hizmetler | `/hizmetler` | `/admin/services` | son 6 |
| İşler | `/isler` | `/admin/projects` | son 5 |
| Sertifikalar | `/sertifikalar` | `/admin/certificates` | son 4 |
| Galeri | `/galeri` | `/admin/gallery` | son 5 |
| Video Galeri | `/videolar` | `/admin/videos` | son 4 |
| Haberler | `/haberler` | `/admin/news` | son 3 |
| Profiller | `/profiller` | `/admin/profiles` | son 5 |
| Referanslar | `/referanslar` | `/admin/testimonials` | son 3 |
| SSS | `/sss` | `/admin/faq` | son 6 |
| Sosyal Medya | `/sosyal` | `/admin/social` | son 6 |
| Sayfalar | `/sayfa/{slug}` | `/admin/pages` | — |
| Yorumlar | — | `/admin/comments` | onaylı yayınlanır |
| Mesajlar | — | `/admin/messages` | — |

### Yeni bir modül eklemek

1. `config/modules.php` içine bir tanım ekleyin.
2. Tabloyu `database/schema.sql` içine ekleyin, `php bin/console migrate` çalıştırın.
3. `app/Models/YeniModul.php` — `ContentModel`'i genişletin, `$table`, `$fillable`,
   `$searchColumns` yazın.
4. `app/Controllers/Admin/YeniModulController.php` — `ResourceController`'i
   genişletin, `$model`, `$moduleSlug`, `$columns`, `$form` tanımlayın.
5. `ResourceController::CONTROLLERS` haritasına ekleyin.
6. `app/Views/admin/yenimodul/{index,form}.php` — jenerik şablonları kopyalayın.
7. `card_for()` yardımcısına kart partial'ını bağlayın (`app/Support/helpers.php`).

Rotalar, menü ve ana sayfa rayları otomatik gelir. Başka hiçbir dosyaya dokunmanız gerekmez.

### Bir modülü kapatmak

`/admin/moduller` → anahtarı kapatın. Ya da:

```bash
php bin/console modules --off=galeri
```

---

## İçerik modeli

* Her metin alanı `<alan>_tr` / `<alan>_en` çiftiyle tutulur. Panelde iki kolon
  görünür; biri boşsa diğeri gösterilir (`loc($row, 'title')`).
* `slug` otomatik üretilir (Türkçe karakter korumalı, benzersiz).
* SEO alanları boş bırakılırsa başlıktan türetilir.
* Silme **yumuşaktır** (`deleted_at`). Silinen kaydın slug'ı serbest kalır, böylece
  aynı adres yeniden kullanılabilir.
* `is_active` her içerik tablosundadır; `is_featured` ana sayfa vurgusu içindir.
* Listelemeler `COUNT(*)` ile sayfalanır, indekslerle desteklenir, her sayfa
  için tüm kayıtlar belleğe alınmaz.

---

## Güvenlik

Saldırı yüzeyi küçük, her katman birden fazla koruma içerir.

**Giriş ve oturum**
* `password_hash` / `password_verify` (PHP 8.5 varsayılan algoritması)
* Başarısız denemelerde IP + hesap bazlı kilit (`security.throttle.login`)
* Girişte `session_regenerate_id(true)` — oturum sabitleme koruması
* Oturuma IP/24 + User-Agent parmak izi bağlanır
* Çerez bayrakları: `HttpOnly`, `SameSite=Lax`, HTTPS'te `Secure`, `use_strict_mode`

**Girdi**
* Her sorgu **hazırlanmış ifade**; `PDO::ATTR_EMULATE_PREPARES = false`
* Sıralama ve filtre sütunları beyaz listeden geçer; `@` ile başlayan ham
  koşullar yalnızca geliştirici tarafından yazılır
* Tüm çıktı `e()` ile kaçışlanır; ham HTML yalnızca `safe_html()`'dan (beyaz
  liste) gelir
* Zengin metin alanları `Sanitizer` sınıfından geçer: `<script>`, `on*`,
  `javascript:`, `data:`, `<iframe>`, `<style>` ve HTML yorumları temizlenir

**CSRF**
* Tüm durum değiştiren uçlar doğrulanır; karşılaştırma `hash_equals` ile
* AJAX için `X-CSRF-Token` başlığı da kabul edilir

**Yükleme**
* Sunucu tarafı boyut sınırı + uygulama katmanı sınır
* Gerçek MIME tespiti (`getimagesize` + `finfo`) — istemci başlığına güvenilmez
* Uzantı beyaz listesi, MIME ile çapraz doğrulanır
* Rastgele dosya adı (32 bayt) — kullanıcı adı dosya sistemine yansımaz
* **Polyglot temizliği:** GD varsa tam yeniden kodlama. GD yoksa dosya, formatın
  gerçek bitiş işaretine (JPEG `FFD9` · PNG `IEND` · WEBP `RIFF` boyutu ·
  GIF blok zinciri · AVIF üst düzey kutu) kadar **yapısal olarak kırpılır** —
  "görsel + PHP" birleşik dosyaların tamamı etkisiz hâle gelir
* `uploads/.htaccess` yürütmeyi ve dizin listelemeyi kapatır

**Diğer**
* Açık yönlendirme koruması: yalnızca site içi göreli yollar
* Hata sayfaları stack trace sızdırmaz; log'a referans kodu yazılır
* Veritabanı web kökünün **dışında** (`storage/database/`)
* `config/`, `lang/`, `storage/`, `.env` kök `.htaccess` ile engellenir
* CSP: `script-src 'self'` (satır içi script yok), `frame-ancestors 'self'`,
  `frame-src` yalnızca izinli video sağlayıcıları

**Yayına almadan önce:** `php bin/console check` çalıştırın ve
`APP_DEBUG=false`, `SESSION_SECURE=true` (HTTPS), `HSTS=...` olduğundan emin olun.

---

## Performans

* **Bağımlılık yok** — Composer, npm, derleme adımı, CDN yok
* **Yazı tipleri self-host** — üç aile, 376 KB, `latin-ext` alt kümesi dahil
  (Türkçe `ğ ş ı İ ç ö ü`), üçüncü taraf isteği yok
* **Sıfır JS bağımlılığı** — `app.js` / `admin.js` tek dosya, `defer`
* SQLite **WAL** modu, `busy_timeout`, `PRAGMA optimize` yolunda
* 41 indeks; her listeleme için `(is_active, deleted_at, …)` bileşik indeksler
* Modül durumu istek başına **tek sorguda** okunur, bellekte tutulur
* Statik varlıklarda `ETag`/`Last-Modified` + uzun ömürlü önbellek
* Görsellerde `loading="lazy" decoding="async"`, ilk ekranda `fetchpriority="high"`

---

## Tasarım

* **Tema:** Atelier — sıcak krem kağıt, sıcak mürekkep, oker/kiremit vurgu
* **Yapı:** Ecosystem Index — ana sayfa bir dizi keşif rayından oluşur
* **Yazı tipleri:** Fraunces (başlık) · IBM Plex Sans (gövde) · JetBrains Mono
  (yalnızca iki yerde: masthead etiketi ve hero rakamı)
* Tüm renk/ölçü/boşluk `public/assets/css/tokens.css` içinde; başka hiçbir
  dosyada ham değer yoktur
* Mobil: 320 / 375 / 414 / 768 px ölçülmüş — yatay taşma yok, tıklanabilir
  metin iki satıra taşmıyor, dokunma hedefleri ≥ 24 px
* `prefers-reduced-motion` desteklenir; hareket yalnızca `transform` / `opacity`

Tasarımı değiştirmek için `tokens.css` ve `theme.css` yeterlidir; şablonlara
veya PHP'ye dokunmanız gerekmez.

---

## Çoklu dil

`lang/tr.php` ve `lang/en.php`. Varsayılan `tr`, URL öneki desteklenir:
`/en/isler` aynı sayfanın İngilizcesidir. Dil çerez + oturumda saklanır.

Arayüz metinleri çeviri anahtarlarıyla gelir (`t('nav.projects')`).
İçerik metinleri veritabanında `_tr` / `_en` sütun çiftleridir.

Üçüncü bir dil eklemek: `lang/<kod>.php` + `config/i18n.php` içine
`available` listesine ekleyin + gerekirse tablo sütunları.

---

## Yedekleme

```bash
php bin/console backup      # VACUUM INTO ile tutarlı kopya
```

`storage/database/` klasörünü düzenli olarak yedekleyin. Tek dosyalık SQLite
veritabanı için dosya kopyalamak da yeterlidir (WAL dosyaları varsa
`storage/database/*.sqlite-wal` ve `*.sqlite-shm` dosyalarını da alın).

---

## Bilinen sınırlamalar

* **Video yükleme yok** — galeri YouTube/Vimeo **embed** kullanır. Sunucuya video
  yüklemek disk, bant genişliği ve dönüştürme maliyeti getirir; gerekirse
  `VideoController` genişletilebilir.
* **Yalnızca SQLite** — `config/database.php` tek bağlantı tanımlar.
  MySQL'e geçmek `Database` sınıfının sürücü kısmını değiştirmeyi gerektirir.
* **Tek yönetici modeli hedefleniyor** — `users` tablosu çoklu kullanıcıyı ve
  `role` sütununu destekler, ancak panelde rol bazlı yetkilendirme (RBAC) yoktur.
* **Zengin metin editörü yok** — düz HTML textarea + beyaz liste temizleyici.
  WYSIWYG eklemek isterseniz `partials/field.php` içindeki `richtext` dalı tek
  noktadır.
* **Ortam eşzamanlı yazma** — SQLite `busy_timeout` ile 5 saniyeye kadar
  bekler; yüksek eşzamanlı yazma için MySQL/PostgreSQL önerilir.

---

## Lisans

Bu kod size ait. Kullanın, değiştirin, satın.

---

## DONATE

<table border="1">
<tr><td>USDT TRC20</td><td><code>TYCK6ZyMS6UDt787foPH2QwFuvkdqMw1Jv</code></td></tr>
<tr><td>USDT BSC20</td><td><code>0x15aac92a1945ddbe5c79304bfa388d6be99b26a3</code></td></tr>
</table>

Created by TAPSIN
