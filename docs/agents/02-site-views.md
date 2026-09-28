# Ajan 02 — Site Görünümleri (Public Templates Engineer)

**Görev:** `app/Views/layouts/*.php` ve `app/Views/site/**/*.php` dosyalarını yaz.
Controller, model, CSS veya PHP çekirdeğine **dokunma**.

## Yüklenmesi zorunlu
- `docs/agents/01-design-system.md` (sınıf kataloğu — BUNLARI KULLAN)
- `docs/agents/03-view-contracts.md` (değişken sözleşmesi — BUNLARA UY)
- `/home/tapsin/.agents/skills/hallmark/references/responsive.md`

## MUTLAK KURALLAR
1. **Çıktı kaçışı:** Metin her zaman `e($x)` veya `<?= e($x) ?>`. Ham HTML yalnızca
   `<?= safe_html($row['body_tr']) ?>` gibi **sunucuda sanitize edilmiş** alanlarda.
   Ham `$row['body_tr']` asla doğrudan basılmaz.
2. **URL:** `url('/yol')`, `asset('...')`, `upload_url($row['cover_image'])`.
   Asla elle birleştirme.
3. **Modül kapısı:** Bir bölümü basmadan önce `module('services', 'active')`
   kontrolü yap. Ana sayfada `$rails` dizisi zaten filtrelenmiştir — onu kullan.
4. **Sayfalama:** `<?= $paginator->links('site.partials.pagination') ?>`.
5. **Erişilebilirlik:** `alt` metni olmayan `<img>` yok, `<button>` için `type`,
   modal için `role="dialog" aria-modal="true"`, accordion için `aria-expanded`,
   sayfalamada `aria-current="page"`.
6. **Boş durum:** Her liste boş dönebilir → `.empty-state` bloğu yaz.
7. Mobil: `.card-grid` ve tüm ızgalar `minmax(0,1fr)` kullanır (CSS zaten).
8. Görsel `loading="lazy" decoding="async"`, ilk ekrandaki kapak görseli
   `loading="eager" fetchpriority="high"`.
9. Türkçe karakterler UTF-8. Kırılabilen uzun kelimelerde `overflow-wrap:anywhere`
   CSS'te var — ek sınıf gerekmez.

## DOSYALAR VE ALACAKLARI DEĞİŞKENLER
`tümü için ortak`: `$locale`, `$siteName`, `$navModules`, `$currentRoute`,
`$currentPath`, `$activeModules`, `$authUser`

### layouts/site.php
- `<!doctype html>`, `lang="<?= e($locale) ?>"`, `dir="ltr"`
- `<head>`: charset, viewport, `csrf_meta()`, `tokens.css`, `theme.css`,
  `<link rel="preload" as="style">` + `fonts.css` + 3 woff2 preload (latin-ext önce),
  `<title>`, `meta description`, canonical, OG etiketleri, `theme-color`
- favicon: `asset('assets/img/favicon.svg')`
- Hero bölümü KOYU (`on-dark`) — CSS sınıfı `hero`

### layouts/minimal.php
- Hata sayfaları için: sadece tokens + theme, masthead yok.

### site/home.php — `$rails`, `$hero`, `$stats`, `$featured`, `$about`, `$footerPages`, `$siteName`
Sıra:
1. `.hero` (koyu) — eyebrow, title (`$hero['title']`), text, 2 buton
   (`$hero['title']` boşsa `t('home.default_title')`), `$stats` (3 adet, `.hero__stat`)
2. **Hakkımda şeridi** — `$about['text']` boşsa bu blok **render edilmez**
3. **Raylar** — `foreach ($rails as $rail)`. Her ray:
   - `.rail__head` içinde `.rail__title` + `.rail__count` + `.rail__more`
     (`href="<?= e($rail['url']) ?>"`)
   - `.rail__track` içinde modüle göre kart seç:
     `services`→`partials/card/service`, `projects`→`card/project`,
     `certificates`→`card/certificate`, `gallery`→`card/gallery`,
     `videos`→`card/video`, `news`→`card/news`, `profiles`→`card/profile`,
     `testimonials`→`card/testimonial`, `faq`→`card/faq`, `social`→`card/social`
   - `$rail['items']` boşsa `t('home.empty')`
4. `$featured` boş değilse öne çıkan işler şeridi
5. CTA şeridi — `t('home.cta_title')` + `/iletisim` butonu
6. Footer

### site/catalog/index.php — `$module`, `$moduleName`, `$moduleDesc`, `$rows`,
###   `$paginator`, `$term`, `$filters`, `$activeFilter`, `$glyph`
- N6 tarzı basit başlık: `.section__eyebrow` = `$glyph . ' ' . $moduleName`,
  `.section__title` = `$moduleName`, açıklama = `$moduleDesc`
- Arama kutusu (`$term`) + `$filters` (her anahtar için `.filters__group` +
  `.filter-chip`; `is-active` seçili olana)
- `$activeFilter` varsa "Filtreleri temizle" düğmesi
- Sonuç sayısı: `t('listing.showing', ['from'=>$paginator->firstItem(),
  'to'=>$paginator->lastItem(), 'total'=>$paginator->total])`
- Izgara `.card-grid` + modüle göre kart partial'ı
- Boşsa `.empty-state`
- Sayfalama

### site/catalog/show.php — `$module`, `$row`, `$related`, `$comments`,
###   `$commentCount`, `$canComment`, `$prev`, `$next`, `$metaTitle`, `$metaDesc`
- `case $module` ile gövde değişir:
  - `projects`: kapak, meta satırı (müşteri/kategori/tarih/teknolojiler/etiketler),
    `safe_html($row['body_tr'])`, galeri (`.media__ratio` 16/9 grid, tıklanınca
    `.lightbox` — `data-lightbox-src`), önceki/sonraki gezinme
  - `news`: başlık, yayın tarihi + okuma süresi + görüntülenme, `safe_html` gövde
  - `services`: hizmet başlığı, süre/fiyat, teslim kalemleri (`.tag`), `safe_html`
  - `certificates`: kurum, tarih, belge no, puan, PDF indirme (`.btn--ghost`,
    `download` özniteliği), doğrulama bağlantısı
  - `gallery`: tam boy görsel + `.lightbox`
  - `videos`: kapak + **"Videoyu izle"** butonu → `data-video-id` ve
    `data-video-embed` (sunucu `video_embed_url($row)` ile hesaplar, şablonda
    hesap YAPMA). Embed modal içinde açılır.
  - `profiles`: avatar, rol, şirket, bio, `Profile::socials($row)` linkleri
  - `testimonials`: alıntı + isim/ünvan/şirket + `.rating`
  - `faq`: soru-cevap listesi
  - `social`: permalink + caption + embed
- `$prev`/`$next` varsa `.breadcrumb` benzeri önceki/sonrı gezinme
- `$related` boş değilse "İlgili içerikler" ızgarası
- `$canComment` ise `partials/comment/section` (yorum listesi + form)

### site/contact.php — `$services`, `$projects`
- Sol: form (`partials/forms/contact`), sağ: doğrudan iletişim bilgileri
  (`setting('email')`, `setting('phone')`, `setting('whatsapp')`,
  `setting('address')`, `setting('working_hours')`)
- Form alanları: ad, e-posta, telefon, konu, mesaj, KVKK onay kutusu,
  `csrf_field()`, **honeypot**: `<input name="website" class="honeypot" tabindex="-1" autocomplete="off">`,
  gizli `source` alanı
- `$services` boş değilse hizmet seçici (select)
- `$projects` boş değilse "son işler" listesi

### site/search.php — `$term`, `$groups`, `$total`
- Büyük arama alanı, `$term` boşsa öneri
- `$groups` her biri: modül adı + sonuç listesi + "Bu bölümde tümünü gör"
- Sonuç metni zaten `<mark>` ile vurgulanmıştır → basmadan önce `e()` uygula

### site/page.php — `$row`, `site/partials/prose.php`
### site/social.php — `$rows`, `$paginator`

### partials
- `site/partials/card/{service,project,certificate,gallery,video,news,profile,testimonial,faq,social}.php`
  — her biri tek bir kart; `$row` alır; `<?php $title = loc($row, 'title') ?: ... ?>`
- `site/partials/pagination.php` — `$paginator`
- `site/partials/head-meta.php` — `$metaTitle`, `$metaDesc`
- `site/partials/flash.php` — `flashes()`
- `site/partials/masthead.php` — N6 (site adı, tagline, `navModules` linkleri,
  dil değiştirici, arama düğmesi, iletişim butonu, burger)
- `site/partials/footer.php` — Ft1 (`$footerPages`, sosyal linkler, `siteName`)
- `site/partials/prose.php` — `safe_html()` çıktısı için `.prose` sarmalayıcı
- `site/partials/comment/section.php` + `form.php`
- `site/partials/forms/contact.php`
- `site/partials/lightbox.php` — bir kez, sayfa sonunda
- `site/partials/video-modal.php` — bir kez, sayfa sonunda

## GÖRSEL YER TUTUCULAR
Gerçek görsel yoksa **uydurma fotoğraf basma**. Şunları kullan:
- `settings.image_placeholder` (public/assets/img/placeholder.svg) `upload_url()` ile
- Varsa `partials/card/_no-image.php` → `.card__media` içinde `.card__glyph` (modül glifi)
- `aspect-ratio` + `object-fit: cover` boşluğu bozmaz

## JavaScript
Sayfa sonunda:
`<script src="<?= asset('assets/js/app.js') ?>" defer></script>`
`data-lightbox-src`, `data-video-id`, `data-video-embed` özniteliklerini kullan;
JS bunları dinler. Şablon içinde `<script>` bloğu YAZMA.
