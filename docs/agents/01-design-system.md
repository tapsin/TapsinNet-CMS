# Ajan 01 — Tasarım Sistemi (Design System Engineer)

**Görev:** `public/assets/css/theme.css`, `public/assets/css/admin.css`,
`public/assets/js/app.js`, `public/assets/js/admin.js` dosyalarını yaz.
Şablonlara **hiç dokunma**. Token sözleşmesi değişmez.

## Yüklenmesi zorunlu
- `/home/tapsin/.agents/skills/hallmark/references/responsive.md`
- `/home/tapsin/.agents/skills/hallmark/references/motion.md`
- `/home/tapsin/.agents/skills/hallmark/references/microinteractions.md`
- `/home/tapsin/.agents/skills/hallmark/references/layout-and-space.md`
- `/home/tapsin/.agents/skills/hallmark/references/interaction-and-states.md`
- `/home/tapsin/.agents/skills/hallmark/references/anti-patterns.md`
- `/home/tapsin/.agents/skills/hallmark/references/slop-test.md` (en sona bırak)

## KİMLİK (bunları değiştirme)
- Tema: **Atelier** — sıcak krem kağıt, sıcak mürekkep, oker/kiremit vurgu
- Makro-yapı: **Ecosystem Index** (çoklu keşif rayı)
- Nav: **N6 masthead** (gazete başlığı) · Footer: **Ft1**
- Karakter: editorial, sakin, tipografi odaklı. Ziyaretçi önce GÖRSÜN, sonra okusun.

## MUTLAK KURALLAR
1. `tokens.css` içindeki her değeri `var(--token)` ile kullan. Ham OKLCH/hex yazma.
   Yeni değere ihtiyaç varsa **tokens.css'e ekle** ve oradan referans ver.
2. Yalnızca 3 yazı tipi: `--font-display`, `--font-body`, `--font-mono`.
   `--font-mono` YALNIZCA `.masthead__tag` ve `.hero__stat-value` içinde.
3. Başlıklar asla italik değil (`font-style: normal`).
4. Yuvarlak hap buton + gradient YASAK. Butonlar köşeli (2–3px).
5. Kart içinde kart (card-in-card) YASAK. Ayraç: hairline (1px `--color-rule`).
6. Hareket yalnızca `transform` ve `opacity`. `transition-property` listesinde
   asla `width/height/top/left/box-shadow` yok.
7. `--ease-out / --ease-in / --ease-in-out` dışında easing yok. `ease` yok.
8. `@media (prefers-reduced-motion: reduce)` bloğu ZORUNLU.
9. `:focus-visible` her etkileşimli öğede, 2px `--color-focus` halka,
   `outline-offset: 2px`, gecikmeli animasyon YOK.
10. Inline `style="..."` ve `<style>` blokları yazma.

## MOBİL — ZORUNLU ZORUNLULUK (320 / 375 / 414 / 768 px)
- `html, body { overflow-x: clip; }`  (`hidden` DEĞİL)
- Görsel içeren ızgara izleri `minmax(0, 1fr)` (çıplak `1fr` YASAK)
- Başlıklarda `overflow-wrap: anywhere; min-width: 0`
- Tıklanabilir metin ASLA iki satıra taşmasın (buton/nav/footer/breadcrumb)
- Bölüm başlıkları mobilde tek sütuna düşsün
- 8 durum: default · hover · focus-visible · active · disabled · loading · error · success
  (buton, input, select, tab, modal için CSS'te karşılıkları yaz:
   `:disabled`, `[aria-busy="true"]`, `[data-state="error"]`, `[data-state="success"]`)

## theme.css — SINIF KATALOĞU
Aşağıdaki sınıfların **tamamı** tanımlanmalı; şablonlar bunları kullanacak.

Temel: `.wrap` (container) · `.section` · `.section__head` · `.section__eyebrow`
· `.section__title` · `.section__text` · `.rule` · `.rule--thick` · `.hairline-stack`

Masthead (N6): `.masthead` · `.masthead__inner` · `.masthead__wordmark`
· `.masthead__tag` (MONO) · `.masthead__issue` · `.masthead__nav` · `.masthead__link`
· `.masthead__actions` · `.masthead__burger` · `.masthead.is-open`
· `.masthead__mobile` · `.nav-link` · `.nav-link.is-active` · `.lang-switch` · `.lang-switch__item`

Hero: `.hero` · `.hero__grid` · `.hero__eyebrow` · `.hero__title` · `.hero__text`
· `.hero__actions` · `.hero__stats` · `.hero__stat` · `.hero__stat-value` (MONO)
· `.hero__stat-label` · `.hero__side` · `.hero__marker`

Ray (Ecosystem): `.rail` · `.rail__head` · `.rail__title` · `.rail__count`
· `.rail__more` · `.rail__track` · `.rail__empty`

Kartlar: `.card` · `.card__media` · `.card__body` · `.card__title` · `.card__text`
· `.card__meta` · `.card__foot` · `.card-grid` · `.card--wide` · `.card--tall`
· `.project-card` · `.service-card` · `.news-card` · `.profile-card`
· `.certificate-card` · `.video-card` · `.testimonial-card` · `.gallery-card`
· `.faq-card`

Form: `.field` · `.field__label` · `.field__hint` · `.field__error` · `.input`
· `.textarea` · `.select` · `.checkbox` · `.radio` · `.form` · `.form-grid`
· `.form-actions` · `.honeypot` (görünmez, `position:absolute;left:-9999px`)

Buton: `.btn` · `.btn--primary` · `.btn--ghost` · `.btn--quiet` · `.btn--danger`
· `.btn--sm` · `.btn--lg` · `.btn--block` · `.btn-row`

Etiket/rozet: `.tag` · `.tag--accent` · `.tag--outline` · `.badge`
· `.badge--success` · `.badge--warning` · `.badge--danger` · `.rating`

Geri bildirim: `.flash` · `.flash--success` · `.flash--error` · `.flash--warning`
· `.flash--info` · `.flash-stack`

Gezinme yardımcıları: `.pagination` · `.pagination__link` · `.pagination__link.is-current`
· `.pagination__gap` · `.breadcrumb` · `.breadcrumb__item`

İçerik: `.prose` (uzun metin) · `.prose--narrow` · `.lead` · `.pullquote`
· `.filters` · `.filters__group` · `.filter-chip` · `.filter-chip.is-active`
· `.result-count` · `.empty-state` · `.empty-state__title` · `.meta-list`
· `.dl` · `.dl__row` · `.dl__key` · `.dl__value`

Medya: `.media` · `.media__img` · `.media__ratio` (4/3, 16/9, 1/1 varyantları)
· `.thumb` · `.avatar` · `.avatar--lg`

Görsel koyu bloklar: `.on-dark` zaten tokens.css'te var.

Lightbox/modal: `.lightbox` · `.lightbox__dialog` · `.lightbox__img`
· `.lightbox__caption` · `.lightbox__close` · `.video-modal` · `.modal`

Footer (Ft1): `.footer` · `.footer__inner` · `.footer__wordmark` · `.footer__tagline`
· `.footer__cols` · `.footer__col` · `.footer__heading` · `.footer__links`
· `.footer__bar` · `.footer__legal` · `.footer__social`

Yardımcı: `.u-center` · `.u-between` · `.u-mono` · `.u-muted` · `.u-nowrap`
· `.u-hide` · `.u-only-mobile` · `.u-only-desktop` · `.visually-hidden`
· `.skip-link` · `.no-scroll`

## app.js (public)
- **SIFIR bağımlılık**, tek dosya, `defer` yüklenir, `window.__TSN` altında
  modül olarak açılır.
- Bileşenler: `mobileMenu()` · `lightbox()` · `videoModal()` · `faqAccordion()`
  (`<details>` kullan, JS sadece "hepsini aç/kapat") · `copyLink()` ·
  `galleryFilter()` · `headerElevation()` (scroll'da masthead kuralı)
  · `searchToggle()` · `langPersist()`.
- `window.__TSN.init()` sayfa yüklendiğinde bir kez çalışır.
- **CSP uyumlu**: `innerHTML` ile kullanıcı/veri yazma, `eval` YOK,
  `style` özniteliği ile dinamik değer yazma YOK (CSS custom property
  istisnası: `--i` gibi sayısal indeksler `setProperty` ile).
- `prefers-reduced-motion` kontrolü JS tarafında da yapılır.

## admin.css
Aynı kurallar (tokens, mobil, 8 durum, hareket) + admin kodu:
`.admin` · `.admin__sidebar` · `.admin__nav` · `.admin__nav-link` · `.admin__main`
· `.admin__topbar` · `.admin__search` · `.admin__user` · `.admin__content`
· `.panel` · `.panel__head` · `.panel__body` · `.panel__foot`
· `.stat` · `.stat__value` · `.stat__label` · `.stat-grid`
· `.table` · `.table__head` · `.table__row` · `.table__cell` · `.table--sortable`
· `.row-actions` · `.toolbar` · `.toolbar__group` · `.checkbox-cell`
· `.dropzone` · `.file-preview` · `.i18n-tabs` · `.tabs` · `.tab`
· `.module-card` · `.module-toggle` · `.inbox-list` · `.inbox-item`
· `.inbox-item.is-unread` · `.message-body` · `.side-meta`
· `.kbd` · `.pill-count`

## admin.js
- `confirmAction(form)` — `[data-confirm]` özniteliği olan formlarda
  `confirm()` (CSP: modal kullanma, `window.confirm` sorunsuz).
- `bulkSelect()` — tablo başlığındaki kutu ile satır kutularını toplu seçer,
  seçili sayısını gösterir, "Sil" butonunu etkinleştirir.
- `slugPreview()` — başlıktan slug türetir, alan boşken canlı öneri gösterir.
- `imagePreview()` — dosya seçilince `URL.createObjectURL` ile önizleme.
- `passwordToggle()` · `passwordMeter()` (0–5) · `autoDismiss()` (flash 6 sn)
  · `sidebarToggle()` (mobilde) · `qSave()` (arama kutusu 400 ms debounce).
- Tümü `window.__ADMIN` altında, `defer`, bağımlılıksız.

## KABUL
- `php -l` yok (CSS/JS) ama dosyalar parse edilebilir olmalı.
- `theme.css` içinde `var(--` ile başlamayan renk/boyut tanımı **sıfır**.
- Dosyaların başına tek satır damga:
  `/* Hallmark · genre: editorial · macrostructure: 20 Ecosystem Index · theme: Atelier · nav: N6 · footer: Ft1 */`
