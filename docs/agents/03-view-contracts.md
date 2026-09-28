# Görünüm Sözleşmeleri — Kesin Veri Sözleşmesi

Controller'lar bu değişkenleri **tam olarak** bu isimlerle geçirir.
Görünümler başka bir şey bekleyemez.

## Ortak (public/index.php her istekte paylaşır)
| Değişken | Tip | Açıklama |
|---|---|---|
| `$locale` | `string` | `'tr'` \| `'en'` |
| `$siteName` | `string` | `Settings::get('site_name')` |
| `$currentRoute` | `string` | eşleşen rota adı (`projects`, `projects.show`, `home`) |
| `$currentPath` | `string` | dil öneği düşürülmüş yol |
| `$navModules` | `string[]` | aktif + public route'u olan modül slug'ları |
| `$homeModules` | `string[]` | ana sayfada gösterilecek modüller |
| `$authUser` | `?array` | admin kullanıcı satırı veya null |
| `$isAdmin` | `bool` | |
| `$activeModules` | `array<string,bool>` | slug => aktif |

## Yardımcı fonksiyonlar (şablonlarda kullanılabilir)
| Fonksiyon | Döner |
|---|---|
| `e($x)` | kaçışlı string |
| `safe_html($x, $images = true)` | beyaz listeli HTML |
| `t($key, ['a'=>1])` | çeviri |
| `url('/yol', ['q'=>1])` | mutlak URL |
| `asset('assets/css/x.css')` | sürümlü statik URL |
| `upload_url($path)` | yüklenen dosya URL'i veya null |
| `module_url($slug, ['q'=>1])` | modül liste URL'i veya null |
| `module($slug, 'active')` | bool |
| `setting($key, $default)` | site ayarı |
| `loc($row, 'title')` | aktif dile göre `title_tr`/`title_en` (boşsa fallback, sonra `''`) |
| `format_date($iso)` | `d F Y` (yerelleştirilmiş değil, `str_limit` ile kısaltılabilir) |
| `time_ago($iso)` | göreli zaman |
| `str_limit($html, 120)` | düz metin kısaltma |
| `excerpt($html, 150)` | düz metin özet |
| `flashes()` | `[['type'=>'success','message'=>'…'], …]` |
| `old($key, $default)` | form eski değeri |
| `error_for($key)` | hata mesajı veya null |
| `all_errors()` | `['field' => 'mesaj']` |
| `csrf_field()` / `csrf_token()` / `csrf_meta()` | CSRF |
| `method_field('DELETE')` | `_method` gizli alan |
| `youtube_id($url)` / `vimeo_id($url)` | video kimliği veya null |
| `video_embed_url($row)` | nocookie embed URL veya null |
| `video_watch_url($row)` | izleme URL'i veya null |

## Site görünümleri
### site/home.php
```
$rails  : [['slug','title','items'(array<row>),'url','glyph','count'(int)], …]
$hero   : ['eyebrow','title','text']
$stats  : [['value','label'], …]          // gerçek sayımlar
$featured: row[]                            // Project::featured(3)
$about  : ['title','text','cv']
$footerPages: row[]                         // Page
```

### site/catalog/index.php
```
$module      : string   ('projects'|'news'|'services'|…)
$moduleName  : string
$moduleDesc  : string
$glyph       : string
$rows        : row[]
$paginator   : Paginator  (items, currentPage, lastPage, total, perPage, hasPages())
$term        : string
$filters     : ['kategori' => ['label'=>string,'options'=>[['key','label','count'],…]], …]
$activeFilter: ?string
$pageTitle   : string
$metaTitle   : string
$metaDesc    : string
```

### site/catalog/show.php
```
$module, $moduleName : string
$row        : row       (tüm tablo sütunları + _tr/_en çiftleri)
$related    : row[]
$comments   : row[]
$commentCount: int
$canComment : bool
$prev, $next : ?row
$metaTitle, $metaDesc : string
```

### site/contact.php
```
$services : row[]      (hizmet modülü aktifse)
$projects : row[]
```

### site/search.php
```
$term   : string
$groups : [['module','name','url','items'=>[['title','url','excerpt'(<mark> içerir),'image'?]], …]]
$total  : int
```

### site/social.php
```
$rows : row[]      ·   $paginator : Paginator
```

### site/page.php
```
$row : row        (title_tr/en, body_tr/en, meta_*)
```

## Admin görünümleri
### admin/{module}/index.php
```
$paginator : Paginator
$rows      : row[]
$module    : string        $moduleName : string
$term, $filter : string
$sortCol, $sortDir : string
$filters   : ['kategori' => [['key','label','count'], …], …]   // anahtarlar: query parametresi
$columns   : ['alan' => ['type'=>'image|title|text|date|number|order|toggle|avatar|rating|check|badge',
                         'label'=>string, 'width'=>?int], …]
$stats     : ['total','active','passive','week', …opsiyonel 'expiring','avg']
$singular, $plural : string
```

### admin/{module}/form.php
```
$row    : row|blank   (alanlar form tanımıyla eşleşir)
$form   : ['alan' => [
             'type'   => text|url|email|tel|number|date|time|color|textarea|richtext|
                        select|checkbox|image|file|tags|slug|hidden|multitext,
             'label'  => string,
             'rules'  => ?string,
             'col'    => int  (1-12 ızgara genişliği),
             'hint'   => ?string,
             'options'=> ?array   // select için
           ], …]
$options : array  (select alanları için dış kaynak — yukarıdakiyle aynı)
$action  : string  (form POST hedefi)
$method  : 'POST'
$title   : string
$module, $moduleName, $singular : string
```

### Diğer admin görünümleri
```
admin/dashboard.php   : $badge['messages'|'comments'], $counts[slug=>int], $modules[] (katalog),
                       $recentNews row[], $recentMsg row[], $siteVersion, $phpVersion, $totalViews
admin/messages/index.php : $paginator, $rows, $filter, $term, $badge, $counts[all|unread|starred|archived]
admin/messages/show.php  : $row (name,email,phone,subject,message,ip,user_agent,is_read,
                                 is_starred,is_archived,admin_note,created_at), $badge
admin/comments/index.php : $paginator, $rows, $filter, $badge, $counts[pending|approved|spam]
admin/modules.php         : $catalog[] (slug,name[tr|en],desc,icon_glyph,group,route,admin,
                                       show_home,admin_only,is_active,sort_order,count),
                           $groups[groupKey=>label]
admin/settings.php        : $schema[group => ['label','icon','fields'=>[key=>[
                                       type,label,rules?,col?,hint?,lang?]]]],
                           $values[key => row]
admin/user/edit.php       : $user row
admin/auth/login.php      : (veri yok)
```

## Kart partial'ları
Her biri **tek kart** render eder, değişken adı: `$row`.
`site/partials/card/{service,project,certificate,gallery,video,news,profile,testimonial,faq,social}.php`
Başlık çözümlemesi:
```php
$title = loc($row, 'title') ?: ($row['name'] ?? $row['author_name'] ?? $row['question_tr'] ?? $row['caption_tr'] ?? '');
```

## Paginator
`$paginator` nesnesi: `items`(dizi), `currentPage`, `lastPage`, `total`, `perPage`,
`hasPages()`, `isEmpty()`, `url(int)`, `window(int)`, `firstItem()`, `lastItem()`,
`count()`, ve `foreach` ile gezilebilir (`IteratorAggregate`).
`links($view)` çağrısı `$view` dosyasını partial olarak render eder.
