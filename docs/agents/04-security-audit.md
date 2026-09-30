# Ajan 04 — Güvenlik ve Performans Denetimi (Security & Performance Auditor)

**Görev:** Kodu DEĞİŞTİRME. Bulgu listesi üret.

## Kapsam
- `app/Core/` (Database, Csrf, Auth, Uploader, Sanitizer, Session, Security, Router, Validator, RateLimiter)
- `app/Controllers/` (Site + Admin)
- `app/Models/`
- `public/index.php`, `routes/`, `config/`
- `public/.htaccess`, `public/uploads/.htaccess`
- SQL şeması ve indeksler

## Denetim listesi — her madde için "geçti / sorun / kanıt satırı"
1. **SQL enjeksiyonu:** Her sorgu hazırlanmış ifade mi? Ham string birleştirme var mı?
   `buildOrder`, `buildWhere`, `@` kaçışı gerçekten kullanıcı girdisinden mi korunuyor?
2. **XSS:** Ham çıktı var mı? `Sanitizer::html` beyaz listesi yeterli mi?
   `Safe_html()` yalnızca sanitize alanda mı? CSP `unsafe-inline` içeriyor mu?
3. **CSRF:** Tüm durum değiştiren POST rotaları `csrf` kontrolü yapıyor mu?
   Token `hash_equals` ile karşılaştırılıyor mu? Oturum yenileme eşleşiyor mu?
4. **Kimlik doğrulama:** Parola hash'leme, brute-force kilidi, oturum sabitleme,
   oturum parmak izi, çıkışta oturum yok etme.
5. **Yükleme:** MIME doğrulama, boyut, uzantı beyaz listesi, yeniden adlandırma,
   dizin sertleştirme, GD yoksa polyglot kırpma doğru mu, path traversal.
6. **Yönlendirme:** Açık yönlendirme (open redirect) — `Security::safeRedirect`
   her çıkış noktasında kullanılıyor mu?
7. **Yetki:** Admin rotaları `auth` ile korunuyor mu? `index.php` modül kapısı
   pasif modülü engelliyor mu? Yorumlar/mesajlar sadece admin'de mi?
8. **Sızıntı:** Hata mesajlarında stack trace/path var mı (`APP_DEBUG=false` iken)?
   Log'lara parola/hash yazılıyor mu?
9. **Başlıklar:** CSP, HSTS, nosniff, Referrer-Policy, Permissions-Policy.
   `frame-ancestors` var mı? `frame-src` yalnızca izinli sağlayıcılar mı?
10. **Oturum:** `httponly`, `samesite`, `secure`, `use_strict_mode`,
    `use_only_cookies`, `session.use_trans_sid=0`.
11. **DoS:** `LIMIT` zorlaması var mı? Sorgular `perPage` ile sınırlı mı?
    Regex'ler ReDoS'a açık mı? `Validator` regex kuralları?
12. **İkili dil:** `loc()` her yerde mi? Ham `_en`/`_tr` sızıntısı var mı?
13. **Performans:** N+1 sorgu var mı? Eksik indeks (`WHERE is_active=1
    AND deleted_at IS NULL ORDER BY ...` için)? Modül durumu istek başına
    tek sorguda mı? Sayfalama `COUNT(*)` ile mi?
14. **Erişilebilirlik kontrolü (kısa):** Form etiketleri, `aria-*`, odak sırası,
    `prefers-reduced-motion` desteği bekleniyor.

## Çıktı biçimi
Markdown tablo: `| # | Bulgu | Önem (kritik/yüksek/orta/düşük) | Dosya:satır | Kanıt | Öneri |`
Ardından "Düzeltilmesi gerekenler" — en fazla 12 madde, önem sırasına göre.
Somut kod yazma; yol + neden + öneri ver.
