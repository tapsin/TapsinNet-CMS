#!/usr/bin/env bash
# Her form adresini ve her public GET rotasını canlı HTTP ile test eder.
# Rotaları tahmin etmez: gerçek istek atar, durum kodunu okur.
set -u
cd /home/tapsin/Masaüstü/Projeler/tapsinnet
PORT="${PORT:-5050}"
B="http://127.0.0.1:$PORT"

curl -s -m 3 -o /dev/null "$B/" || { echo "  x sunucu $PORT yanit vermiyor"; exit 1; }
echo "  sunucu $PORT hazir"
echo

echo "=== 1) PUBLIC GET ROTALARI ==="
FAIL=0
# /moduller yalnızca yönetim panelinde vardır (routes/admin.php) — public'te
# 404 vermesi DOĞRU davranıştır, kırık rota sayılmaz.
for p in / /anasayfa /iletisim /ara /sosyal /sayfa/kvkk /sitemap.xml /robots.txt \
         /hizmetler /isler /haberler /galeri /videolar /sertifikalar \
         /profiller /referanslar /sss /moduller /en /en/iletisim; do
  code=$(curl -s -m 5 -o /dev/null -w "%{http_code}" "$B$p")
  if [ "$p" = "/moduller" ]; then
    printf "  %-22s %s  %s\n" "$p" "$code" \
      "$([ "$code" = "404" ] && echo 'OK (admin-only; publicte 404 dogru)' || echo 'beklenmeyen')"
    [ "$code" = "404" ] || FAIL=$((FAIL+1))
    continue
  fi
  case "$code" in
    200|301|302) printf "  %-22s %s  OK\n" "$p" "$code" ;;
    404) printf "  %-22s %s  404 — rota kapali veya modul pasif\n" "$p" "$code"; FAIL=$((FAIL+1)) ;;
    *)   printf "  %-22s %s  HATA\n" "$p" "$code"; FAIL=$((FAIL+1)) ;;
  esac
done

echo
echo "=== 2) DETAY SAYFALARI (yorum formu burada) ==="
# Liste sayfasından detay yollarını topla.
# DİKKAT: bu ortamdaki grep -oE sola-yönlü (leftmost-shortest) eşleşiyor ve
# slug'ları 25 karakterde kesiyor. sed'in ".*" öneki de greedy olduğu için
# href'in SONUNA kadar yiyor. Çözüm: slug'ı yakalayan grup, sonra
# kalanını ayrı bir desenle temizle.
SLUGS=$(curl -s -m 5 "$B/isler" \
        | sed -n 's|.*href="\([^"]*\)".*|\1|p' \
        | awk -F'/isler/' '/\/isler\// && !/\/isler$/ {print "/isler/" $2}' \
        | sort -u | head -2)
if [ -z "$SLUGS" ]; then
  echo "  (/isler sayfasinda detay linki bulunamadi)"
else
  for slug in $SLUGS; do
    code=$(curl -s -m 5 -o /dev/null -w "%{http_code}" "$B$slug")
    has=$(curl -s -m 5 "$B$slug" | grep -c 'name="content_type"')
    printf "  %-46s %s  yorum formu: %s\n" "$slug" "$code" \
      "$([ "$has" -gt 0 ] && echo VAR || echo YOK)"
    [ "$code" = "200" ] || FAIL=$((FAIL+1))
    [ "$has" -gt 0 ] || FAIL=$((FAIL+1))
  done
fi

echo
echo "=== 3) YÖNETIM FORMLARI — gerçek gönderim ==="
login() {
  rm -f /tmp/fa-cookie
  local t
  t=$(curl -s -c /tmp/fa-cookie "$B/admin/giris" | grep -oE 'name="_token" value="[^"]+"' | head -1 | sed 's/.*value="//;s/"//')
  curl -s -b /tmp/fa-cookie -c /tmp/fa-cookie -o /dev/null \
    -X POST "$B/admin/giris" -d "_token=$t" -d "login=tapsin" -d "password=TapsinNet!2026"
}
login && echo "  admin girisi OK"

# form_page: CSRF alınacak GET sayfa · post_path: gönderilecek adres
post_check() {
  local label="$1" form_page="$2" post_path="$3"; shift 3
  local t code
  t=$(curl -s -b /tmp/fa-cookie -c /tmp/fa-cookie "$B$form_page" \
      | grep -oE 'name="_token" value="[^"]+"' | head -1 | sed 's/.*value="//;s/"//')
  if [ -z "$t" ]; then
    printf "  %-26s CSRF ALINAMADI (%s)\n" "$label" "$form_page"; FAIL=$((FAIL+1)); return
  fi
  code=$(curl -s -b /tmp/fa-cookie -c /tmp/fa-cookie -o /dev/null -w "%{http_code}" \
         -X POST "$B$post_path" -d "_token=$t" "$@")
  case "$code" in
    302) printf "  %-26s %s  OK\n" "$label" "$code" ;;
    500) printf "  %-26s %s  SUNUCU HATASI\n" "$label" "$code"; FAIL=$((FAIL+1)) ;;
    *)   printf "  %-26s %s  BEKLENMEYEN\n" "$label" "$code"; FAIL=$((FAIL+1)) ;;
  esac
}

post_check "hesap (profil)"  "/admin/hesap"  "/admin/hesap" \
  -d "name=Yönetici" -d "username=tapsin" -d "email=admin@tapsinnet.local"
post_check "hesap/parola"    "/admin/hesap"  "/admin/hesap/parola" \
  -d "current_password=bilerek-yanlis" -d "password=YeniParola123!" -d "password_confirmation=YeniParola123!"
post_check "ayarlar"         "/admin/ayarlar" "/admin/ayarlar"
post_check "moduller/islem"  "/admin/moduller" "/admin/moduller/islem"

# yorum onay akışı — gerçek kayıt üzerinde
CID=$(sqlite3 storage/database/app.sqlite \
      "INSERT INTO comments (content_type,entity_id,author_name,author_email,body)
       VALUES ('project',1,'Denetim Testi','denetim@ornek.com','Denetim icin gecici kayit.');
       SELECT last_insert_rowid();")
T=$(curl -s -b /tmp/fa-cookie -c /tmp/fa-cookie "$B/admin/yorumlar" \
    | grep -oE 'name="_token" value="[^"]+"' | head -1 | sed 's/.*value="//;s/"//')
code=$(curl -s -b /tmp/fa-cookie -c /tmp/fa-cookie -o /dev/null -w "%{http_code}" \
  -X POST "$B/admin/yorumlar/islem/$CID" -d "_token=$T" -d "islem=onayla")
APPROVED=$(sqlite3 storage/database/app.sqlite "SELECT is_approved FROM comments WHERE id=$CID;")
printf "  %-26s %s  onay=%s %s\n" "yorum onayla" "$code" "$APPROVED" \
  "$([ "$code" = "302" ] && [ "$APPROVED" = "1" ] && echo OK || echo HATA)"
{ [ "$code" = "302" ] && [ "$APPROVED" = "1" ]; } || FAIL=$((FAIL+1))
sqlite3 storage/database/app.sqlite "DELETE FROM comments WHERE id=$CID;"

echo
echo "=== 4) YORUM GONDERIMI (gercek DB yazimi) ==="
# DİKKAT: CSRF belirteci oturuma bağlıdır. Belirteci alan istek ile gönderen
# istek AYNI çerez kavanozunu kullanmalı; ayrı curl'lerde çerez taşınmaz ve
# istek "CSRF doğrulama başarısız" ile reddedilir.
rm -f /tmp/fa-c2
SLUG=$(curl -s "$B/isler" | sed -n 's|.*href="\([^"]*\)".*|\1|p' \
       | awk -F'/isler/' '/\/isler\// && !/\/isler$/ {print "/isler/" $2}' \
       | sort -u | head -1)
PAGE=$(curl -s -c /tmp/fa-c2 -b /tmp/fa-c2 "$B$SLUG")
CT=$(printf '%s' "$PAGE" | sed -n 's|.*name="_token" value="\([^"]*\)".*|\1|p' | head -1)
ENTITY=$(printf '%s' "$PAGE" | sed -n 's|.*name="entity_id" value="\([0-9]*\)".*|\1|p' | head -1)
TYPE=$(printf '%s' "$PAGE" | sed -n 's|.*name="content_type" value="\([a-z]*\)".*|\1|p' | head -1)
if [ -z "$CT" ] || [ -z "$ENTITY" ]; then
  echo "  belirte/entity alinamadi"; FAIL=$((FAIL+1))
fi
EMAIL="probe-$(date +%s)@ornek.com"
code=$(curl -s -b /tmp/fa-c2 -c /tmp/fa-c2 -o /dev/null -w "%{http_code}" \
  -X POST "$B/yorumlar" -d "_token=$CT" -d "content_type=$TYPE" -d "entity_id=$ENTITY" \
  -d "author_name=Form Testi" -d "author_email=$EMAIL" \
  -d "body=Yorum formu uctan uca dogrulama metni, yeterli uzunlukta.")
printf "  POST /yorumlar  %s  (type=%s entity=%s)\n" "$code" "$TYPE" "$ENTITY"
ROW=$(sqlite3 storage/database/app.sqlite "SELECT id FROM comments WHERE author_email='$EMAIL';")
if [ -n "$ROW" ]; then
  echo "  veritabani yazimi   OK (id=$ROW)"
  sqlite3 storage/database/app.sqlite "DELETE FROM comments WHERE author_email='$EMAIL';"
  echo "  test kaydi temizlendi"
else
  echo "  veritabani yazimi   HATA — kayit yok"; FAIL=$((FAIL+1))
fi

echo
echo "=== 5) ILETISIM FORMU (gercek DB yazimi) ==="
# Hız sınırı: contact_form 3 gönderim / 10 dakika. Denetimi tekrarlarken bu
# sınır dolmuş olabilir; önbelleği temizleyip sıfırdan deniyoruz.
rm -f storage/cache/ratelimit/*.json
rm -f /tmp/fa-c3
PAGE=$(curl -s -c /tmp/fa-c3 -b /tmp/fa-c3 "$B/iletisim")
CT=$(printf '%s' "$PAGE" | sed -n 's|.*name="_token" value="\([^"]*\)".*|\1|p' | head -1)
EM="probe-$(date +%s)@ornek.com"
code=$(curl -s -b /tmp/fa-c3 -c /tmp/fa-c3 -o /dev/null -w "%{http_code}" \
  -X POST "$B/iletisim" -d "_token=$CT" -d "name=Form Testi" -d "email=$EM" \
  -d "message=Iletisim formu uctan uca dogrulama mesaji, yeterli uzunlukta.")
printf "  POST /iletisim %s\n" "$code"
ROW=$(sqlite3 storage/database/app.sqlite "SELECT id FROM messages WHERE email='$EM';")
if [ -n "$ROW" ]; then
  echo "  veritabani yazimi   OK (id=$ROW)"
  sqlite3 storage/database/app.sqlite "DELETE FROM messages WHERE email='$EM';"
  echo "  test kaydi temizlendi"
else
  echo "  veritabani yazimi   HATA — kayit yok"; FAIL=$((FAIL+1))
fi

echo
echo "=== 6) 404 SAYFASI VE HATA DURUMU ==="
code=$(curl -s -m 5 -o /dev/null -w "%{http_code}" "$B/olmayan-sayfa-xyz")
printf "  /olmayan-sayfa-xyz  %s %s\n" "$code" "$([ "$code" = "404" ] && echo 'OK (dogru 404)' || echo HATA)"

echo
if [ "$FAIL" -eq 0 ]; then
  echo "SONUC: tum form ve rota uclari calisiyor"
else
  echo "SONUC: $FAIL sorun var"
fi
exit "$FAIL"
