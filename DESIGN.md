---
name: TapsinNet
description: Freelance web & yazılım portföy vitrini
colors:
  vitrin-kagidi: "oklch(97% 0.008 70)"
  vitrin-kagidi-2: "oklch(94.5% 0.010 70)"
  vitrin-kagidi-3: "oklch(91% 0.012 68)"
  balmumu: "oklch(56% 0.145 48)"
  balmumu-murekkep: "oklch(44% 0.130 45)"
  balmumu-derin: "oklch(34% 0.105 42)"
  balmumu-soluk: "oklch(92% 0.038 62)"
  balmumu-yikama: "oklch(96.5% 0.018 66)"
  vitrin-murekkep: "oklch(21% 0.014 55)"
  vitrin-murekkep-yumusak: "oklch(33% 0.012 58)"
  vitrin-murekkep-soluk: "oklch(44% 0.010 62)"
  vitrin-murekkep-silik: "oklch(56% 0.009 64)"
  vitrin-cizgi: "oklch(85% 0.010 70)"
  vitrin-cizgi-koyu: "oklch(77% 0.013 66)"
  vitrin-cizgi-silik: "oklch(90% 0.008 72)"
  vitrin-gece: "oklch(19% 0.014 55)"
typography:
  display:
    fontFamily: "Fraunces, Iowan Old Style, Palatino Linotype, Georgia, serif"
    fontSize: "clamp(2.75rem, 5vw + 1rem, 5.25rem)"
    fontWeight: 600
    lineHeight: 1.08
    letterSpacing: "-0.022em"
  headline:
    fontFamily: "Fraunces, Iowan Old Style, Palatino Linotype, Georgia, serif"
    fontSize: "2.1973rem"
    fontWeight: 600
    lineHeight: 1.28
    letterSpacing: "-0.022em"
  title:
    fontFamily: "IBM Plex Sans, ui-sans-serif, system-ui, -apple-system, sans-serif"
    fontSize: "1.4063rem"
    fontWeight: 700
    lineHeight: 1.28
    letterSpacing: "-0.022em"
  body:
    fontFamily: "IBM Plex Sans, ui-sans-serif, system-ui, -apple-system, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.62
    letterSpacing: "normal"
  label:
    fontFamily: "JetBrains Mono, ui-monospace, SFMono-Regular, Menlo, monospace"
    fontSize: "0.8rem"
    fontWeight: 500
    lineHeight: 1.28
    letterSpacing: "0.12em"
rounded:
  xs: "2px"
  sm: "3px"
  md: "4px"
  lg: "6px"
  pill: "999px"
spacing:
  "3xs": "0.25rem"
  "2xs": "0.5rem"
  xs: "0.75rem"
  sm: "1rem"
  md: "1.5rem"
  lg: "2rem"
  xl: "3rem"
  "2xl": "4rem"
  "3xl": "6rem"
  "4xl": "8rem"
components:
  button-primary:
    backgroundColor: "{colors.vitrin-murekkep}"
    textColor: "{colors.vitrin-kagidi}"
    rounded: "{rounded.sm}"
    padding: "0.5rem 1rem"
  button-primary-hover:
    backgroundColor: "{colors.balmumu-murekkep}"
    textColor: "{colors.vitrin-kagidi}"
  button-ghost:
    backgroundColor: "none"
    textColor: "{colors.vitrin-murekkep}"
    rounded: "{rounded.sm}"
    padding: "0.5rem 1rem"
  button-quiet:
    backgroundColor: "none"
    textColor: "{colors.balmumu-murekkep}"
    rounded: "0"
    padding: "2px 0"
  nav-link:
    backgroundColor: "none"
    textColor: "{colors.vitrin-murekkep-yumusak}"
    rounded: "{rounded.sm}"
    padding: "0.25rem 0.75rem"
  input:
    backgroundColor: "{colors.vitrin-kagidi}"
    textColor: "{colors.vitrin-murekkep}"
    rounded: "{rounded.sm}"
    height: "2.5rem"
  card:
    backgroundColor: "{colors.vitrin-kagidi}"
    textColor: "{colors.vitrin-murekkep}"
    rounded: "0"
---

# Design System: TapsinNet

## Overview

**Creative North Star: "Dükkân Vitrini"**

Bu bir atölye dükkanının vitrini. Camın arkasında çalışan biri var; vitrin onu özlemiyor, işini gösteriyor. Krem kâğıt zemin, sıcak mürekkep yazı, tek bir balmumu vurgu. Vitrinin işi müşteriyi içeri almak olduğu için her karar "bu işi yapacak kişiye güvenebilir miyim" sorusuna hizmet eder.

Duygu: sıcak, güvenilir, mesafeli değil ama iddialı da değil. Bir katalog değil bir teklif masası. Boş vitrin de dürüst bir vitrindir — doldurulmamış alan, doldurulmuş gibi gösterilmez; sistemin kendi içeriğinin eksikliği kadar, sistemin doğruluğunun da kanıtıdır.

Yoğunluk: orta. Kart ızgaraları 3 kolon, form alanları çift kolon, mobilde tek kolona düşer. Nefes payı var ama hava değil — her boşluk bir kararın sonucu.

**Key Characteristics:**

- Sıcak krem kâğıt zemin, asla saf beyaz değil
- Tek vurgu rengi (balmumu), seyrek ve kasıtlı
- Keskin köşeler (2-6px), yuvarlatılmış "blob" yok
- Gölge neredeyse yok; derinlik çizgi ve ton farkıyla kurulur
- Üç yazı tipi, monospace yalnızca etiketlerde
- Mobil önce: her sayfa ilk ekranda anlaşılır

## Colors

Palet sıcak bir nötr aile üzerine kurulu; tek doygun renk balmumudur.

### Primary

- **Balmumu** (`oklch(56% 0.145 48)`): Tek vurgu rengi. Eylem butonlarının hover zemini, aktif menü öğesi, link altı çizgileri, seçili durumlar. Viewport'ta **%3'ü geçmez** — seyrekliği değeridir.
- **Balmumu Mürekkep** (`oklch(44% 0.130 45)`): Kâğıt üstünde metin için kullanılan koyu balmumu varyantı (kontrast ~4.9:1). Buton hover, aktif nav metni, sakin linkler.
- **Balmumu Derin** (`oklch(34% 0.105 42)`): Koyu zemin üstünde balmumu metni.
- **Balmumu Soluk / Yıkama** (`oklch(92% 0.038 62)` / `oklch(96.5% 0.018 66)`): Seçili arka planlar. Dolu balmumu yerine — dolgu kullanmak sistemi boğar.

### Neutral

- **Vitrin Kâğıdı** (`oklch(97% 0.008 70)`): Ana zemin. Sıcak krem, saf beyaz değil.
- **Vitrin Kâğıdı 2 / 3** (`oklch(94.5% 0.010 70)` / `oklch(91% 0.012 68)`): İkincil dolgular, hover zemini, görsel yer tutucu.
- **Vitrin Mürekkep** (`oklch(21% 0.014 55)`): Birincil metin ve buton zemini.
- **Vitrin Mürekkep Yumuşak / Soluk / Silik** (`oklch(33%…)` / `oklch(44%…)` / `oklch(56%…)`): Üç kademe metin tonu.
- **Vitrin Çizgi** (`oklch(85% 0.010 70)`): Kenarlıklar, ayırıcılar.
- **Vitrin Gece** (`oklch(19% 0.014 55)`): Hero, footer, modal gibi koyu yüzeyler. Tema tek moddur; koyu sadece bu bileşenlerde.

### Named Rules

**The Vitrin Kuralı.** Vurgu rengi bir ekranın **%3'ünü geçmez**. Balmumu az görünür; her kullanımı bir şey söylemek içindir, süslemek için değil.

**The Boşluk Dürüstlüğü Kuralı.** Bir veri yoksa alan render edilmez. Yer tutucu görsel, sahte testimonial veya uydurma sayı hiçbir zaman gösterilmez. Boş kutu bir hatadır, tasarım onu saklamaz.

## Typography

**Display Font:** Fraunces (Iowan Old Style, Palatino Linotype, Georgia, serif)
**Body Font:** IBM Plex Sans (ui-sans-serif, system-ui, -apple-system, sans-serif)
**Label/Mono Font:** JetBrains Mono (ui-monospace, SFMono-Regular, Menlo, monospace)

**Character:** Üç aile sınırlıdır ve üçünün de bir işi vardır. Fraunces vitrinin tabelasıdır — sıcak, geleneksel, otoriter. IBM Plex Sans içerik taşıyıcıdır; nötr, okunaklı, ekranda uzun metin taşıyacak tek yüz. JetBrains Mono yalnızca iki yerde: `.masthead__tag` ve `.hero__stat-value`. Başka hiçbir yerde.

### Hierarchy

- **Display** (600, `clamp(2.75rem, 5vw+1rem, 5.25rem)`, lh 1.08, ls -0.022em): Yalnızca hero başlığı.
- **Headline** (600, 2.1973rem, lh 1.28): Bölüm başlıkları (`h2`).
- **Title** (700, 1.4063rem, lh 1.28): Kart başlıkları, form grupları.
- **Body** (400, 1rem, lh 1.62): Gövde metni. Satır uzunluğu `65ch` (`--measure`).
- **Label** (500, 0.8rem, ls 0.12em, uppercase): Üst etiketler, alan etiketleri, menü bölümü adları.

### Named Rules

**The İki Kod Bir Alan Kuralı.** Başlık gövdeden en az 300 birim ağırdır (400 → 700). Ara ton yasaktır; bu, hiyerarşiyi yanlış okunmaz kılar.

**The Mono İkinci Kuralı.** JetBrains Mono yalnızca `.masthead__tag` ve `.hero__stat-value` üzerinde. Üçüncü bir kullanım bu sözleşmeyi bozar.

## Layout

Izgara tabanlı, 12 kolon. Konteyner 76rem (1216px), `clamp(1.25rem, 4vw, 2.5rem)` kenar boşluğu.

**Masthead ızgarası:** `1fr auto 1fr` — marka sola, gezinme tam merkeze, eylemler sağa. Marka `min-width: 0` + ellipsis ile daralır. 861px altında iki kolona iner ve gezinme burger'a döner.

**Kart ızgaraları:** `card-grid--2 / --3 / --4` her biri `repeat(auto-fill, minmax(0, calc(...)))` ile açık sütun sayısı verir. Taban `minmax(0, 1fr)` **kullanılmaz** — alt sınır 0, `auto-fill` ile birleştiğinde tarayıcı 46 kolon üretip kartları 1 piksele sıkıştırır. Taban yerine `minmax(16rem, 1fr)` gerekir.

**Form ızgarası:** 12 kolon; alanlar `col-6` (çift) ve `col-12` (tam). 768px altında tümü `1 / -1`.

**Form alanı genişliği kuralı:** `form-grid` içindeki her `.field` mutlaka bir `col-*` sınıfı taşır. Taşımayan alan, 12 kolonlu ızgarada otomatik olarak **1 kolon** (1/12) genişlik alır ve kullanılamaz.

**Ritim:** 4pt tabanlı ölçek (`--space-3xs` … `--space-4xl`). Bölümler arası en az `--space-2xl`.

## Elevation & Depth

Bu sistem **gölge kullanmaz** — neredeyse. Derinlik iki araçla kurulur: **ton farkı** (kâğıt → kâğıt-2 → kâğıt-3) ve **1px çizgi** (`--color-rule`).

Tek istisna: yüzen yüzeyler. Açılır menü (`--shadow-md`) ve modal (`--shadow-lg`) gölge alır, çünkü bunlar gerçekten zeminin üstüne çıkar.

### Shadow Vocabulary

- **Yüzey gölgesi** (`0 6px 20px oklch(21% 0.014 55 / 0.08), 0 2px 6px ... / 0.04`): Açılır menü, yalnızca.
- **Modal gölgesi** (`0 18px 48px ... / 0.12, 0 4px 12px ... / 0.05`): Lightbox, video modal.

### Named Rules

**The Düz Varsayılan Kuralı.** Yüzeyler dinlenme hâlinde düzdür. Gölge yalnızca gerçek yükselmeye yanıt verir — hover, açılır menü, modal. Kart, buton, input asla gölgeli değildir.

## Shapes

Köşe stratejisi **keskin**. Ölçek 2 / 3 / 4 / 6px, tamamen kısıtlı. 999px pill yalnızca gerçekten hap şeklinde olan bileşenlerde.

Kenarlık her zaman 1px ve `--color-rule`. Kartlarda köşe yuvarlatması **yok** (0) — kart kâğıt üzerine yerleşen bir kağıt parçasıdır, bir kutu değil. Yuvarlak köşe, malzeme hissini zayıflatır.

**The Keskin Köşe Kuralı.** Buton, input ve dil seçicide köşe 3px'te durur. Kart ve bölüm köşesizdir. Yuvarlak köşe (`>= 8px`) bu sistemde yabancı bir sinyal yayar.

## Components

### Buttons

Tone: güven veren sıcak. Belirgin dolgu, sıkı metin, 1px koyu kenarlık.

- **Shape:** 3px köşe (`--radius-sm`).
- **Primary:** `--color-ink` zemin, `--color-paper` metin. Padding `0.5rem 1rem`, 600 ağırlık, 0.875rem punto.
- **Hover:** zemin `--color-accent-ink`'e döner (okşer), metin açık kalır.
- **Active:** `translateY(1px)` — fiziksel geri bildirim.
- **Ghost:** kenarlık ve dolgu yok, metin `--color-ink`.
- **Quiet:** tamamen çıplak; `--color-accent-ink` metin + altı çizgi. Minimum 24px dokunma hedefi (WCAG 2.5.8), görsel görünüm değişmez.
- **Busy:** `aria-busy="true"` metni saydamlaştırıp spinner gösterir. `prefers-reduced-motion` altında animasyon kapanır.

### Chips

- **Style:** 1px `--color-rule` kenarlık, 3px köşe, `--color-paper-2` hover.
- **State:** seçili `--color-ink` zemin + krem metin. Sayı rozeti ayrı bir token.

### Cards / Containers

- **Corner Style:** 0 — köşesiz.
- **Background:** `--color-paper`; görsel alan `--color-paper-2`.
- **Shadow Strategy:** yok (Elevation bkz.).
- **Border:** görsel alanda 1px `--color-rule`; kart gövdesi çerçevesiz.
- **Internal Padding:** gövde üstte `--space-sm`; görsel `aspect-ratio: 4/3`.

### Inputs / Fields

- **Style:** 1px `--color-rule` kenarlık, 3px köşe, `--color-paper` zemin, 8px/12px dolgu.
- **Focus:** kenarlık `--color-ink`'e döner, halka yok — ton değişimi yeterli.
- **Error:** `--color-danger` kenarlık; mesaj `--color-danger` metin + `⚠` öneki, `role="alert"`.
- **Checkbox:** 1.1rem kutu, `accent-color: --color-accent-ink`; etiket yanında, `grid` ile hizalı.

### Navigation

- **Style:** 3px köşeli buton bağlantıları, `min-height: 2rem`, şeffaf zemin.
- **Hover:** `--color-paper-2` zemin + 1px kenarlık.
- **Active:** `--color-accent-soft` zemin + `--color-accent` kenarlık + `--color-accent-ink` metin.
- **Overflow:** beşten fazla modül `<details>` tabanlı "Daha fazla" açılır menüsüne girer. JS gerektirmez; dış tıklama ve ESC ile kapanır.
- **Mobile:** 861px altında tamamen gizlenir, burger (3 çizgi → X) panel açar.

## Do's and Don'ts

### Do:

- **Do** Her form alanına `col-*` sınıfı ver; `form-grid` 12 kolonludur ve sınıfsız alan 1/12 genişlik alır.
- **Do** Kart ızgarası için `card-grid--N` kullan; temel `.card-grid` yalnızca modifiyesiz kullanımdır.
- **Do** Yeni renk eklerken önce `tokens.css`'e isimli token olarak yaz, sonra `var()` ile referans ver. Ham OKLCH/hex yazma.
- **Do** Yeni bileşende önce mevcut token'ı ara; yeni token ancak gerçekten yeni bir kavram varsa.
- **Do** Boş veri alanını hiç render etme.

### Don't:

- **Don't** Vurgu rengini geniş yüzeye yayma. Balmumu kenarlık, işaret ve tek satırlık metin içindir.
- **Don't** Kartlara, butonlara veya inputlara gölge ekle. Gölge yalnızca açılır menü ve modalda.
- **Don't** Yeni yazı tipi ailesi ekle. Üç aile sabit; JetBrains Mono iki yerde sınırlı.
- **Don't** Yuvarlatmayı 8px üstüne çıkar.
- **Don't** Testimonial, müşteri logosu, başarı sayısı veya fiyat uydurma.
- **Don't** HTML'de kullanılan sınıfı CSS'te tanımlamayı atlama. Tanımsız sınıf (`.masthead__links`, `.card-grid--3`, `.masthead__search-btn` gibi) tarayıcı varsayılanına düşer ve düzeni bozar.
