# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Birincil ziyaretçi **iş verenler / potansiyel müşteriler**. Sitenin amacı onları ikna edip iletişim formunu doldurtmaya götürmek; ikincil ziyaretçi yok.

Ziyaretçi genellikle mobil telefondan gelir, iş ihtiyacını kısaca anlamak ister, ve birkaç saniyede "bunu yapan kişi işimi çözebilir mi" sorusunu cevaplamak ister.

## Product Purpose

TapsinNet, bağımsız bir yazılımcının (Tapsin) web ve yazılım hizmetlerini sunduğu portföy ve teklif sitesidir. Aynı zamanda bu işi kendi yazdığı, modül tabanlı bir PHP CMS üzerinden yürütür: içerik, modüller, ayarlar ve formlar admin panelinden yönetilir.

Başarı ölçütü: ziyaretçi iş ihtiyacını doğruladıktan sonra iletişim formunu doldurup gönderir.

## Positioning

**Uçtan uca teslim.** TapsinNet tasarım, yazılım ve yayınlama aşamalarının tamamını tek kişide birleştirir. Müşteri farklı firmalarla koordine etmek zorunda kalmaz; işi tanımlar, teslim alır.

Konumlandırma bir teknoloji seçimi değil, bir çalışma biçimi vaadidir: "fikirden yayına kadar tek elden".

## Operating Context

- **Yayın durumu:** Site henüz yayında değil ve içerik kısmen eksik. Plan, önce siteyi bu haliyle yayına almak, ilk müşteriler geldikten sonra gerçek referansları eklemektir.
- **İçerik kaynağı:** İşler, hizmetler, sertifikalar, galeri, videolar, profiller, referanslar, SSS ve haberler — hepsi `config/modules.php` üzerinden tanımlı modüllerden gelir. Admin panelinden modül açılıp kapatılabilir; kapalı modül menüden düşer ve 404 döner.
- **Çok dilli:** Türkçe ve İngilizce. Dili URL segmenti (`/en/...`) ya da çerez belirler; rotalar dilden bağımsızdır.
- **Çalışma dili:** Tüm arayüz metinleri ve içerikler Türkçe; kod yorumları ve dokümantasyon Türkçe.

## Capabilities and Constraints

**Mevcut yetenekler**

- Modül tabanlı içerik yönetimi: her modülün listeleme, detay ve filtreleme sayfası config'den otomatik üretilir.
- Yönetim paneli: içerik CRUD, modül açma/kapama, ayarlar, kullanıcı yönetimi.
- İletişim formu: CSRF + IP hız sınırı + honeypot + zaman tuzağı + sunucu doğrulaması.
- Yorum sistemi: iş, haber ve hizmet detaylarında; onaylı yorumlar gösterilir.
- SEO: her sayfa için meta, canonical, hreflang, Open Graph; `sitemap.xml` ve `robots.txt` otomatik.
- Arama, filtre, sayfalama; galeri lightbox, video modal, SSS akordeon.
- Medya yükleme, yazı taslakları, yumuşak silme.

**Kısıtlar**

- PHP 8 + SQLite, sunucu bağımlılığı olmadan çalışır; hosting veya CDN zorunlu değildir.
- Frontend derleme adımı yoktur. CSS ve JS doğrudan `public/assets` altında, elle düzenlenir. Tasarım değişikliği = dosya düzenlemesi, derleme yok.
- Yönetim arayüzü de aynı PHP uygulamasının içindedir, ayrı bir uygulama değildir.

**Açık kararlar**

- Gerçek müşteri referansı ve testimonial içeriği: yayın sonrası doldurulacak. O güne kadar bu alanlara uydurma veri girilmeyecek.

## Brand Commitments

- **Ad:** TapsinNet
- **Slogan:** "Freelance web & yazılım" (settings tablosundan, panelden değiştirilebilir)
- **Birincil eylem metinleri (panelden yönetilir, sabit kabul edilir):** "Projeni anlat" ve "İşlerime bak". Ziyaretçinin bu ikisini görmesi beklenir.

## Evidence on Hand

- **Portföy verisi gerçek:** veritabanında 6 proje kaydı (web, uygulama, marka, SEO kategorilerinde) bulunuyor. Bunlar gerçek çalışmalardır ve gösterilebilir.
- **Eğitim/sertifika modülü mevcut**, ancak kayıt sayısı doğrulanmadı; panelden kontrol edilmeden sayı veya rozet gösterilmemeli.
- **Referanslar:** modül kodda mevcut, içerik girilmemiş. Uydurma referans, müşteri logosu, başarı sayısı veya fiyat kesinlikle üretilmemeli.
- **Görsel varlıklar:** `public/assets/img/` altında yalnızca favicon ve placeholder var. Gerçek proje kapak görselleri boş; bu durum boş kutu olarak değil, dürüst bir boşluk olarak ele alınmalı.

## Product Principles

1. **Boş alanı uydurmayla doldurma.** Ziyaretçi karşısında gerçek olmayan hiçbir şey gösterilmez. Referans yoksa alan da yoktur; alan var ama içi boşsa tasarım bunu bir hata gibi değil, dürüst bir durum gibi sunar.
2. **İki eylem her zaman görünür.** "Projeni anlat" ve "İşlerime bak" sitenin varoluş nedenidir; bunlar hiçbir sayfada gömülü kalmaz.
3. **Tek elden teslim, tek bakışta anlaşılır.** Ziyaretçi kapsamı ilk ekranda görür; derinleşme isteyen ziyaretçi işlere iner.
4. **Mobil önce.** Ziyaretçi büyük olasılıkla telefondan geliyor; her sayfa ilk ekranı anlamaya yetmeli.
5. **Derleme adımı yoktur.** Her görsel karar doğrudan CSS'te yaşar; tasarım sistemi dosyalardan okunabilir olmalı.

## Accessibility & Inclusion

- Dil ve içerik Türkçe; `lang` niteliği doğru ayarlanır, İngilizce sürüm `lang="en"` sunar.
- Formlarda etiket `for`/`id` ile bağlı; hatalar `role="alert"` ile duyurulur; KVKK onayı zorunludur.
- Hareket azaltma tercihi (`prefers-reduced-motion`) CSS'te desteklenir.
- Odak halkaları `focus-visible` ile tanımlıdır.
