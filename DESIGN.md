---
name: TapsinNet / ORYZO Editorial Slides
description: Sıcak karanlık zemin, krem tipografi, turuncu editoryal vurgu ve tam ekran slayt akışı
source: local reference Yapıştırılan metin.txt
---

# ORYZO Editorial Design System

## Direction

TapsinNet artık ürün editoryali gibi sunulur: sıcak walnut zemin üzerinde krem tipografi, turuncu vurgu ve Instagram proje görsellerinden oluşan yüzen obje kompozisyonu. Her ana bölüm kendi ekranını kaplayan bir slayttır; sayfa dikey kaydırmada `scroll-snap` ile bölümden bölüme ilerler.

## Colors

```css
--color-void: #100904;
--color-bone-white: #ffedd7;
--color-dark: #382416;
--color-dark-2: #40372e;
--color-ash-gray: #c2af99;
--color-dark-muted: #6c5f51;
--color-saffron-spark: #dc5000;
```

Walnut Shadow sayfa zemini, Bark Brown kart/CTA yüzeyi, Cork Border saç teli ayraç ve Ember Accent yalnızca kısa etiket/link vurgusu için kullanılır. Saf siyah arka plan ve saf beyaz metin kullanılmaz.

## Typography

Tüm site Google/Roboto ailesini kullanır. Başlık, menü, etiket ve linkler uppercase weight 500 karakterindedir. Açıklama metni mixed-case weight 400 olarak daha sakin okunur. Display satır yüksekliği yaklaşık `.9`, harf aralığı normaldir.

## Slide layout

- `html` dikey scroll-snap kullanır.
- Hero, öne çıkan işler, hizmetler, referanslar, haber/SSS ve CTA bölümleri minimum `100svh` yüksekliğindedir.
- Hero iki kolonludur: solda büyük başlık ve tek ana CTA, sağda Instagram proje görselleri obje kompozisyonu.
- Bölümler arasında yalnızca ince/dashed yapısal ayrımlar bulunur.
- CMS içeriği ve linkleri korunur; yalnızca sunum katmanı slayt düzenine geçirilir.

## Components

- Primary CTA: Bark Brown dolgulu, krem yazılı pill buton.
- Ghost action: çerçeveli/altı çizili krem link, dolgu yok.
- Project card: 12px radius, Bark Brown yüzey, krem başlık.
- Input: mümkün olduğunca underline-only görünüm.
- Navigation: transparan/fixed, kısa uppercase linkler, aktif öğede dashed underline.

## Motion and accessibility

Instagram obje görselleri hafifçe yüzer; parçacık alanı sıcak krem/turuncu tonlarında çalışır. `prefers-reduced-motion` etkinse hareketler kapanır. Dekoratif canvas ve görseller yardımcı teknolojilerden ayrılır; CMS formlarındaki etiket, hata ve CSRF sözleşmeleri korunur.
