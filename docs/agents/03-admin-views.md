# Ajan 03 — Yönetim Görünümleri (Admin Templates Engineer)

**Görev:** `app/Views/layouts/admin.php`, `app/Views/admin/**/*.php` dosyalarını yaz.
Controller, model, CSS veya PHP çekirdeğine **dokunma**.

## Yüklenmesi zorunlu
- `docs/agents/01-design-system.md` (`.admin*` sınıfları dahil tüm katalog)
- `docs/agents/03-view-contracts.md`

## MUTLAK KURALLAR
1. Her form `method="post"` + `csrf_field()`. Silme/işlem `method="POST"`
   (yoksa `method_field('DELETE')`).
2. `auth` middleware zaten var; ek kontrol gerekmez ama `authUser` değişkenini kullan.
3. Tüm metin `e()` ile. Ham HTML yok.
4. Silme düğmelerinde `data-confirm="..."` (JS `confirm()` gösterir).
5. **Liste sayfası her zaman**: arama kutusu, durum filtresi, modül filtreleri,
   sıralama başlıkları, toplu seçim kutusu, sayfalama, boş durum.
6. Boş durum `.empty-state` + "Ekle" butonu.
7. Form grid: `$field['col']` değerine göre `style` YAZMA —
   `class="col-<?= (int)$field['col'] ?>"` kullan, CSS'te 12 kolon ızgara tanımlı.
8. Doğrulama hatası: `error_for('alan')` → `.field__error`.
   Eski değer: `old('alan', $row['alan'] ?? '')`.
9. `richtext` alanları: `<textarea class="textarea textarea--rich" rows="12">`
   (JS ileki WYSIWYG YOK; düz HTML editörü + `safe_html` beyaz listesi).
10. Mobil: tablo yatay kaydırmalı `.table-scroll`; `.admin__sidebar`
    mobilde gizlenir, `.sidebarToggle()` ile açılır.

## DOSYALAR
### layouts/admin.php
- `admin` grid: `.admin__sidebar` + `.admin__main` (`.admin__topbar` + `.admin__content`)
- Sidebar: logo, `Module::navigable()` DEĞİL — `admin` modül kataloğundan
  (view'a `navAdmin` dizisi **yok**; bunu `core` yerine burada sabit dizi olarak yaz:
  Panel · Hizmetler · İşler · Sertifikalar · Galeri · Videolar · Haberler ·
  Profiller · Referanslar · SSS · Sosyal · Sayfalar · Yorumlar · Mesajlar ·
  Modüller · Ayarlar · Hesap)
  Rozetler: `Message::badge()` → `message.badge['messages']` ve `['comments']`
- Topbar: sayfa başlığı (`$title`), siteyi görüntüle, çıkış formu (POST + csrf)
- `flashes()` render'ı
- `<script src="assets/js/admin.js" defer>`
- meta: `csrf_meta()`, `robots` noindex

### admin/partials/sidebar.php, topbar.php, flash.php, pagination.php
### admin/partials/field.php — **JENERİK ALAN RENDERER**
```php
$name  = $fieldKey;  $def = $fieldDef;  $row = $currentRow; $options = $extraOptions;
$value = old($name, $row[$name] ?? ($def['default'] ?? ''));
```
`$def['type']` switch:
- `text|url|email|tel|number|date|color` → `.field` > `.field__label` + `.input`
- `textarea` → `.textarea` rows=4
- `richtext` → `.textarea.textarea--rich` rows=12
- `select` → `.select`, `$def['options']` (dizi: `['value'=>'Label']` veya `[['key'=>..,'label'=>..]]`)
- `checkbox` → `.checkbox` (gizli `0` + `1` kutu)
- `image` → önizleme (`upload_url($row[$name])`) + `<input type="file" class="input">`
  + mevcut dosyayı silmek için `sil_<name>` kutusu
- `file` → aynı ama PDF
- `tags` → `<input class="input" value="virgüllü metin">` + `.field__hint`
- `slug` → `.input` + slug öneri kutusu (`data-slug-from="title_tr"`)
- `hidden` → `<input type="hidden">`
Her alan: `<?= csrf değil ?>`, `name` = alan adı, `id` = alan adı.
`lang => true` ise **iki kolon** (TR/EN) bas.

### admin/{module}/index.php — **JENERİK LİSTE**
Değişkenler: `$paginator`, `$rows`, `$module`, `$moduleName`, `$term`, `$filter`,
`$sortCol`, `$sortDir`, `$filters`, `$columns`, `$stats`, `$singular`, `$plural`
- `.stat-grid` — toplam / aktif / pasif / son 7 gün (+ varsa `expiring`, `avg`)
- `.toolbar` — arama (`q`), durum select (`durum`), modül filtreleri, "Ekle" butonu
- `.table` — `$columns` dizisini döngüye al; sütun `type`:
  - `image` → `<img class="thumb">`
  - `avatar` → `.avatar`
  - `title` → başlık + alt satır `slug`
  - `text` → düz
  - `date` → `format_date()`
  - `number` → `number_format`
  - `order` → `sort_order`
  - `toggle` → tek satır **form** (POST `.../islem/{id}`, `islem=aktif|pasif`,
    CSRF) içinde buton
  - `check` / `badge` / `rating` → rozet
- `.row-actions` — düzenle / öne çıkar / sil (her biri kendi formunda POST)
- Sıralanabilir başlıklar: `sirala` + `yon` linkleri
- Toplu seçim: `<form>` + `[name="ids[]"]` kutuları + `islem=sil-toplu`
- `<?= $paginator->links('admin.partials.pagination') ?>`

### admin/{module}/form.php — **JENERİK FORM**
Değişkenler: `$module`, `$moduleName`, `$row`, `$form`, `$action`, `$method`, `$title`
- `<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data">`
- `csrf_field()`
- `.panel` > `.panel__head` (`$title`) + `.panel__body` (12 kolon grid)
- `$form` dizisini döngüye al → `partials/field.php`
- Bilingual ipucu: `t('admin.bilingual_hint')`
- `.form-actions` — Kaydet (`.btn--primary`) + İptal (liste linki)
- Proje galeri özel alanı: `$module === 'projects'` ise
  `name="gallery"` gizli input + mevcut görselleri önizle + "Görsel Ekle"
  (`gallery_one` dosya inputu)
- Video özel alanı: `$module === 'videos'` ise `video_url` alanını
  `data-slug-from` yerine normal `text` olarak bas (controller ayrıştırıyor)

### admin/dashboard.php — `$badge`, `$counts`, `$modules`, `$recentNews`, `$recentMsg`,
###   `$siteVersion`, `$phpVersion`, `$totalViews`
### admin/auth/login.php — `$authUser` yok; sade form, ortalanmış `.panel`,
###   parola göster/gizle düğmesi (`data-password-toggle`)
### admin/messages/index.php — `$paginator`, `$rows`, `$filter`, `$term`, `$badge`, `$counts`
### admin/messages/show.php — `$row`, `$badge` (mesaj + not formu + eylem düğmeleri)
### admin/comments/index.php — `$paginator`, `$rows`, `$filter`, `$badge`, `$counts`
### admin/modules.php — `$catalog`, `$groups`
   her modül için `.module-card` + `POST /admin/moduller/islem` + `slug` + `islem=aktif|pasif`
   + `sirala` inputu. `messages`/`comments`/`search` kilitli (kapatılamaz).
### admin/settings.php — `$schema`, `$values`
   `SettingController::SCHEMA` gruplarını sekme/panel olarak bas.
   `lang => true` alanlar için iki kolon (TR/EN).
   `type=image` → önizleme + yükleme + sil kutusu.
   Tek form, `method=post` → `/admin/ayarlar`, `csrf_field()`.
### admin/user/edit.php — `$user` (profil formu + parola formu)

## BOŞ DURUM / HATA
Hiçbir `$rows` boş geldiğinde PHP uyarısı üretme — `.empty-state` bas.
