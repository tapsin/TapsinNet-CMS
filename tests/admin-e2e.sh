#!/usr/bin/env bash
# Uçtan uca yönetim paneli testi
set -u
# Taban adres: önce BASE_URL, yoksa .env'deki APP_URL, yoksa 5050
B="${BASE_URL:-$(grep -E '^APP_URL=' .env 2>/dev/null | cut -d= -f2- | tr -d '"' || true)}"
B="${B:-http://127.0.0.1:5050}"
J=$(mktemp)
PASS=0; FAIL=0

ok()   { PASS=$((PASS+1)); printf '  \033[32m✓\033[0m %s\n' "$1"; }
bad()  { FAIL=$((FAIL+1)); printf '  \033[31m✗\033[0m %s  %s\n' "$1" "${2:-}"; }
code() { curl -s -b "$J" -c "$J" -o /dev/null -w '%{http_code}' "$@"; }
# Sayfadan CSRF tokenini al (çerez kavanozunu günceller)
token() { curl -s -b "$J" -c "$J" "$1" | grep -oP 'name="_token" value="\K[^"]+' | head -1; }
db()    { php -r "require 'bootstrap/app.php'; $1"; }

echo
echo "▸ Oturum"
T=$(token "$B/admin/giris")
if [ -n "$T" ]; then ok "CSRF token alındı (${#T} karakter)"; else bad "CSRF token alınamadı"; fi

R=$(curl -s -b "$J" -c "$J" -o /dev/null -w '%{http_code}' -X POST "$B/admin/giris" \
    -d "_token=$T" -d "login=admin" -d "password=admin123")
[ "$R" = "302" ] && ok "Giriş başarılı (302)" || bad "Giriş başarısız" "HTTP $R"

R=$(curl -s -b "$J" -c "$J" -o /dev/null -w '%{http_code}' "$B/admin")
[ "$R" = "200" ] && ok "Panel açılıyor (200)" || bad "Panel açılmıyor" "HTTP $R"

# Önceki koşulardan kalan test kayıtlarını temizle (yumuşak silinen satırlar
# slug'i ve id aramasını kirletir)
db 'foreach (Core\Database::select("SELECT id FROM services WHERE title_tr LIKE :p", ["p"=>"Sistem Test%"]) as $r) { Core\Database::execute("DELETE FROM services WHERE id = :id", ["id"=>(int)$r["id"]]); }' >/dev/null

echo
echo "▸ CSRF ve yetki"
R=$(curl -s -b "$J" -c "$J" -o /dev/null -w '%{http_code}' -X POST "$B/admin/services/islem/1" -d "islem=sil")
[ "$R" = "419" ] && ok "CSRF'siz yazma reddedildi (419)" || bad "CSRF koruması çalışmıyor" "HTTP $R"

R=$(curl -s -o /dev/null -w '%{http_code}' "$B/admin/services")
[ "$R" = "302" ] && ok "Oturumsuz erişim yönlendiriliyor (302)" || bad "Yetkisiz erişime açık" "HTTP $R"

echo
echo "▸ Oluşturma"
T=$(token "$B/admin/services/ekle")
R=$(curl -s -b "$J" -c "$J" -o /dev/null -w '%{http_code}' -X POST "$B/admin/services/ekle" \
    --data-urlencode "_token=$T" \
    --data-urlencode "title_tr=Sistem Testi Hizmeti" \
    --data-urlencode "excerpt_tr=Kısa açıklama metni" \
    --data-urlencode "icon=◆" --data-urlencode "is_active=1" \
    --data-urlencode "sort_order=99" --data-urlencode "deliverables_tr=Tasarım, Geliştirme")
[ "$R" = "302" ] && ok "Servis oluşturuldu (302)" || bad "Oluşturma başarısız" "HTTP $R"

NEW=$(db 'echo (int) Core\Database::value("SELECT id FROM services WHERE title_tr=:t AND deleted_at IS NULL", ["t"=>"Sistem Testi Hizmeti"], 0);')
[ "$NEW" -gt 0 ] && ok "Kayıt veritabanında (id=$NEW)" || bad "Kayıt oluşmadı"

SLUG=$(db 'echo (string) Core\Database::value("SELECT slug FROM services WHERE title_tr=:t AND deleted_at IS NULL", ["t"=>"Sistem Testi Hizmeti"], "");')
[ -n "$SLUG" ] && ok "Slug otomatik üretildi: $SLUG" || bad "Slug boş"

SEO=$(db 'echo (string) Core\Database::value("SELECT meta_title_tr FROM services WHERE title_tr=:t AND deleted_at IS NULL", ["t"=>"Sistem Testi Hizmeti"], "");')
[ -n "$SEO" ] && ok "SEO alanı türetildi" || bad "SEO alanı türetilmedi"

DEL=$(db 'echo (string) Core\Database::value("SELECT deliverables_tr FROM services WHERE title_tr=:t AND deleted_at IS NULL", ["t"=>"Sistem Testi Hizmeti"], "");')
[ "$DEL" = "Tasarım, Geliştirme" ] && ok "Teslim kalemleri kaydedildi" || bad "Teslim kalemleri bozuk" "$DEL"

echo
echo "▸ Doğrulama"
T=$(token "$B/admin/services/ekle")
curl -s -b "$J" -c "$J" -o /dev/null -X POST "$B/admin/services/ekle" \
     -d "_token=$T" -d "title_tr=" -d "is_active=1" >/dev/null
BOZUK=$(db 'echo (int) Core\Database::value("SELECT COUNT(*) FROM services WHERE title_tr = :t", ["t"=>""], 0);')
[ "$BOZUK" = "0" ] && ok "Boş zorunlu alan kaydedilmedi" || bad "Doğrulama çalışmıyor"

echo
echo "▸ Aktif / pasif"
R=$(curl -s -b "$J" -c "$J" -o /dev/null -w '%{http_code}' -X POST "$B/admin/services/islem/$NEW" \
    -d "_token=$(token "$B/admin/services")" -d "islem=pasif")
ACTIVE=$(db "echo (int) Core\Database::value(\"SELECT is_active FROM services WHERE id=$NEW\", [], 1);")
[ "$ACTIVE" = "0" ] && ok "Pasife alındı" || bad "Pasif alma çalışmıyor" "is_active=$ACTIVE"

GONE=$(curl -s "$B/hizmetler?page=2" | grep -c "Sistem Testi Hizmeti")
[ "$GONE" = "0" ] && ok "Pasif kayıt public listede görünmüyor" || bad "Pasif kayıt sızdı" "$GONE kez"

curl -s -b "$J" -c "$J" -o /dev/null -X POST "$B/admin/services/islem/$NEW" \
     -d "_token=$(token "$B/admin/services")" -d "islem=aktif" >/dev/null
# sort_order=99 → son sayfada; toplam aktif hizmet sayısına göre hesapla
TOTAL=$(db 'echo (int) Core\Database::value("SELECT COUNT(*) FROM services WHERE deleted_at IS NULL AND is_active=1", [], 0);')
LAST_PAGE=$(( (TOTAL + 8) / 9 ))
SHOWN=$(curl -s "$B/hizmetler?page=$LAST_PAGE" | grep -c "Sistem Testi Hizmeti")
[ "$SHOWN" -ge 1 ] && ok "Aktif kayıt public listede görünüyor" || bad "Aktif kayıt görünmüyor"

echo
echo "▸ Güncelleme"
T=$(token "$B/admin/services/duzenle/$NEW")
R=$(curl -s -b "$J" -c "$J" -o /dev/null -w '%{http_code}' -X POST "$B/admin/services/duzenle/$NEW" \
    -d "_token=$T" -d "title_tr=Sistem Testi Hizmeti v2" -d "is_active=1" -d "icon=●")
NEW_TITLE=$(db 'echo (string) Core\Database::value("SELECT title_tr FROM services WHERE id='"$NEW"' AND deleted_at IS NULL", [], "");')
[ "$NEW_TITLE" = "Sistem Testi Hizmeti v2" ] && ok "Başlık güncellendi" || bad "Güncelleme çalışmıyor" "$NEW_TITLE"

echo
echo "▸ Silme (yumuşak)"
curl -s -b "$J" -c "$J" -o /dev/null -X POST "$B/admin/services/islem/$NEW" \
     -d "_token=$(token "$B/admin/services")" -d "islem=sil" >/dev/null
DEAD=$(db "echo (int) Core\Database::value(\"SELECT COUNT(*) FROM services WHERE id=$NEW AND deleted_at IS NOT NULL\", [], 0);")
LIVE=$(db "echo (int) Core\Database::value(\"SELECT COUNT(*) FROM services WHERE id=$NEW AND deleted_at IS NULL\", [], 0);")
[ "$DEAD" = "1" ] && ok "Kayıt yumuşak silindi" || bad "Silme çalışmıyor"
[ "$LIVE" = "0" ] && ok "Silinen kayıt listeden düştü" || bad "Silinen kayıt hâlâ listeleniyor"

echo
echo "▸ Modül anahtarı"
T=$(token "$B/admin/moduller")
curl -s -b "$J" -c "$J" -o /dev/null -X POST "$B/admin/moduller/islem" -d "_token=$T" -d "slug=gallery" -d "islem=pasif" >/dev/null
H=$(curl -s -o /dev/null -w '%{http_code}' "$B/galeri")
[ "$H" = "404" ] && ok "Pasif modül public route'u 404 dönüyor" || bad "Pasif modül hâlâ açık" "HTTP $H"

NAVMENU=$(curl -s "$B/" | grep -c 'href="[^"]*/galeri"')
[ "$NAVMENU" = "0" ] && ok "Pasif modül menüden düştü" || bad "Pasif modül menüde kaldı" "$NAVMENU kez"

curl -s -b "$J" -c "$J" -o /dev/null -X POST "$B/admin/moduller/islem" -d "_token=$(token "$B/admin/moduller")" -d "slug=gallery" -d "islem=aktif" >/dev/null
H=$(curl -s -o /dev/null -w '%{http_code}' "$B/galeri")
[ "$H" = "200" ] && ok "Modül geri açıldı (200)" || bad "Modül geri açılmadı" "HTTP $H"

echo
echo "▸ Ayarlar"
ORIG=$(db 'echo (string) Core\Database::value("SELECT value_tr FROM settings WHERE key=:k", ["k"=>"site_tagline"], "");')
T=$(token "$B/admin/ayarlar")
curl -s -b "$J" -c "$J" -o /dev/null -X POST "$B/admin/ayarlar" \
     --data-urlencode "_token=$T" --data-urlencode "site_name=TapsinNet" \
     --data-urlencode "site_tagline_tr=Yeni slogan" --data-urlencode "show_index=1" >/dev/null
SLUG=$(db 'echo (string) Core\Database::value("SELECT value_tr FROM settings WHERE key=:k", ["k"=>"site_tagline"], "");')
[ "$SLUG" = "Yeni slogan" ] && ok "Ayar kaydedildi" || bad "Ayar kaydedilmedi" "$SLUG"

# Test ayarı geri yükler — test, üretim verisini bozmamalı
db "Core\Database::execute('UPDATE settings SET value_tr = :v WHERE key = :k', ['v' => '$ORIG', 'k' => 'site_tagline']);" >/dev/null
BACK=$(db 'echo (string) Core\Database::value("SELECT value_tr FROM settings WHERE key=:k", ["k"=>"site_tagline"], "");')
[ "$BACK" = "$ORIG" ] && ok "Ayar test sonrası geri yüklendi" || bad "Ayar geri yüklenemedi" "$BACK"

# Kısmi gönderim mevcut değerleri silmemeli
T=$(token "$B/admin/ayarlar")
curl -s -b "$J" -c "$J" -o /dev/null -X POST "$B/admin/ayarlar" -d "_token=$T" -d "site_name=TapsinNet" >/dev/null
KEPT=$(db 'echo (string) Core\Database::value("SELECT value_tr FROM settings WHERE key=:k", ["k"=>"site_tagline"], "");')
[ "$KEPT" = "$ORIG" ] && ok "Kısmi gönderim mevcut ayarı silmedi" || bad "Kısmi gönderim ayarı sildi" "$KEPT"

echo
echo "▸ Çıkış"
curl -s -b "$J" -c "$J" -o /dev/null -X POST "$B/admin/cikis" -d "_token=$(token "$B/admin")" >/dev/null
R=$(curl -s -b "$J" -c "$J" -o /dev/null -w '%{http_code}' "$B/admin")
[ "$R" = "302" ] && ok "Çıkış sonrası erişim engellendi" || bad "Çıkış çalışmıyor" "HTTP $R"

rm -f "$J"
echo
echo "────────────────────────────────────────"
if [ "$FAIL" -eq 0 ]; then
  printf '\033[32m  ✓ %d test geçti, 0 başarısız\033[0m\n' "$PASS"
else
  printf '\033[31m  ✗ %d geçti, %d BAŞARISIZ\033[0m\n' "$PASS" "$FAIL"
fi
exit $FAIL
