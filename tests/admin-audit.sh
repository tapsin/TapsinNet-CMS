#!/usr/bin/env bash
# Yönetim paneli denetimi — üç alan, gerçek HTTP + gerçek veritabanı:
#   1) Ayarlar : her sekme, her alan tipi, kısmi/kaydet/sil senaryoları
#   2) Yorumlar: onayla, onay kaldır, spam, spam değil, not, sil
#   3) Mesajlar: okundu, okunmadı, yıldız, arşiv, not, sil, toplu okundu
#
# Her adım bir kayıt oluşturur, durumu doğrular ve kaydı siler.
set -u
cd /home/tapsin/Masaüstü/Projeler/tapsinnet
PORT="${PORT:-5050}"
B="http://127.0.0.1:$PORT"
DB=storage/database/app.sqlite
CJ=/tmp/adm-audit
FAIL=0

curl -s -m 3 -o /dev/null "$B/" || { echo "  x sunucu $PORT yanit vermiyor"; exit 1; }
echo "  sunucu $PORT hazir"

tok() { curl -s -b "$CJ" -c "$CJ" "$B$1" | sed -n 's|.*name="_token" value="\([^"]*\)".*|\1|p' | head -1; }
post() { # $1=adres  $2=token  $3...=veri
  local path="$1" t="$2"; shift 2
  curl -s -b "$CJ" -c "$CJ" -o /dev/null -w "%{http_code}" -X POST "$B$path" -d "_token=$t" "$@"
}
ok() { printf "  %-44s %s  %s\n" "$1" "$2" "$3"; }
pass() { ok "$1" "$2" "OK"; }
fail() { ok "$1" "$2" "HATA"; FAIL=$((FAIL+1)); }

# --- giriş -----------------------------------------------------------------
rm -f "$CJ"
T=$(tok /admin/giris)
curl -s -b "$CJ" -c "$CJ" -o /dev/null -X POST "$B/admin/giris" \
  -d "_token=$T" -d "login=tapsin" -d "password=TapsinNet!2026"
if [ "$(curl -s -b "$CJ" -o /dev/null -w '%{http_code}' "$B/admin")" = "200" ]; then
  pass "yonetim girisi" "200"
else
  fail "yonetim girisi" "?"; exit 1
fi

# =============================================================================
echo
echo "=== 1) AYARLAR ==="
for grp in general home contact seo captcha; do
  code=$(curl -s -b "$CJ" -o /dev/null -w "%{http_code}" "$B/admin/ayarlar?grup=$grp")
  n=$(curl -s -b "$CJ" "$B/admin/ayarlar?grup=$grp" | grep -c 'name="s-')
  if [ "$code" = "200" ]; then pass "sekme $grp" "$code ($n alan)"
  else fail "sekme $grp" "$code"; fi
done

# Bilinmeyen sekme — 500 vermemeli
code=$(curl -s -b "$CJ" -o /dev/null -w "%{http_code}" "$B/admin/ayarlar?grup=olmayan")
[ "$code" = "200" ] && pass "bilinmeyen sekme (geri duser)" "$code" \
                  || fail "bilinmeyen sekme" "$code"

# --- text alanı: gerçekten kaydediliyor mu ---
# NOT: site_tagline iki dilli bir alan (lang => true), HTML'de adı
# site_tagline_tr / site_tagline_en olarak gelir. Tek dilli alan örneği
# site_name kullanilir.
OLD=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='site_tagline';")
T=$(tok /admin/ayarlar)
NEWVAL="Denetim Sorusu $(date +%s)"
post /admin/ayarlar "$T" -d "site_name=TapsinNet" -d "site_tagline_tr=$NEWVAL" -d "site_tagline_en=$NEWVAL" >/dev/null
GOT=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='site_tagline';")
if [ "$GOT" = "$NEWVAL" ]; then pass "text alani kaydedildi (iki dilli)" "302"
else fail "text alani kaydedilmedi" "[$GOT]"; fi
sqlite3 "$DB" "UPDATE settings SET value_tr='$OLD' WHERE key='site_tagline';"

# alan sayimi: <section> basina "id=s-" degil, name/id s-<anahtar> kaliplarini say
for grp in general home contact seo captcha; do
  n=$(curl -s -b "$CJ" "$B/admin/ayarlar?grp=$grp" | grep -coE 'id="s-[a-z_]+"')
  [ "$n" -gt 0 ] || fail "sekme $grp alan basmadi" "0 alan"
done

# --- bool alanı: işaretlenince 1, işaretlenmezse 0 ---
OLDIDX=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='show_index';")
T=$(tok /admin/ayarlar)
post /admin/ayarlar "$T" -d "show_index=0" -d "google_analytics=" >/dev/null
GOT=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='show_index';")
[ "$GOT" = "0" ] && pass "bool alani kapatildi" "[$GOT]" || fail "bool alani" "[$GOT]"
T=$(tok /admin/ayarlar)
post /admin/ayarlar "$T" -d "show_index=1" -d "google_analytics=" >/dev/null
GOT=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='show_index';")
[ "$GOT" = "1" ] && pass "bool alani acildi" "[$GOT]" || fail "bool alani" "[$GOT]"
sqlite3 "$DB" "UPDATE settings SET value_tr='$OLDIDX' WHERE key='show_index';"

# --- select: izinli deger + disindaki deger reddi ---
OLDM=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='captcha_mode';")
T=$(tok /admin/ayarlar)
post /admin/ayarlar "$T" -d "captcha_mode=builtin" >/dev/null
GOT=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='captcha_mode';")
[ "$GOT" = "builtin" ] && pass "select gecerli deger" "[$GOT]" || fail "select gecerli deger" "[$GOT]"
T=$(tok /admin/ayarlar)
post /admin/ayarlar "$T" -d "captcha_mode=hacker" >/dev/null
GOT=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='captcha_mode';")
[ "$GOT" = "off" ] && pass "select gecersiz deger reddedildi" "[$GOT]" \
                || fail "select gecersiz deger KABUL EDILDI" "[$GOT]"
sqlite3 "$DB" "UPDATE settings SET value_tr='$OLDM' WHERE key='captcha_mode';"

# --- secret: maskeli, boş gonderim korur, sil isareti siler ---
SECRET="Denetim-$(date +%s)"
T=$(tok /admin/ayarlar)
post /admin/ayarlar "$T" -d "captcha_recaptcha_secret=$SECRET" >/dev/null
GOT=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='captcha_recaptcha_secret';")
[ "$GOT" = "$SECRET" ] && pass "secret yazildi" "302" || fail "secret yazilmadi" "[$GOT]"
LEAK=$(curl -s -b "$CJ" "$B/admin/ayarlar?grup=captcha" | grep -c "$SECRET")
[ "$LEAK" = "0" ] && pass "secret panelde sizmiyor" "0 eslesme" \
                 || fail "secret SIZDI" "$LEAK eslesme"
T=$(tok /admin/ayarlar)
post /admin/ayarlar "$T" -d "captcha_recaptcha_secret=********" >/dev/null
GOT=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='captcha_recaptcha_secret';")
[ "$GOT" = "$SECRET" ] && pass "secret maskeyi dokununca korundu" "302" \
                        || fail "secret maske ezdi" "[$GOT]"
T=$(tok /admin/ayarlar)
post /admin/ayarlar "$T" -d "captcha_recaptcha_secret=********" -d "sil_captcha_recaptcha_secret=1" >/dev/null
GOT=$(sqlite3 "$DB" "SELECT COALESCE(value_tr,'(silindi)') FROM settings WHERE key='captcha_recaptcha_secret';")
[ "$GOT" = "(silindi)" ] && pass "secret silme isareti" "302" || fail "secret silinmedi" "[$GOT]"

# --- kısmi gönderim: eksik alanlar silinmemeli ---
OLDL=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='contact_kvkk_text';")
if [ -n "$OLDL" ]; then
  T=$(tok /admin/ayarlar)
  post /admin/ayarlar "$T" -d "captcha_mode=off" >/dev/null
  STILL=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='contact_kvkk_text';")
  [ "$STILL" = "$OLDL" ] && pass "kismi gonderim alan silmedi" "302" \
                        || fail "kismi gonderim ALANI SILDI" "[$STILL]"
fi

# --- hatali deger: e-posta biçimi ---
OLDE=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='email';")
T=$(tok /admin/ayarlar)
post /admin/ayarlar "$T" -d "email=gecersiz-eposta" >/dev/null
GOT=$(sqlite3 "$DB" "SELECT value_tr FROM settings WHERE key='email';")
[ "$GOT" = "$OLDE" ] && pass "gecersiz e-posta kaydedilmedi" "302" \
                      || fail "gecersiz e-posta KAYDEDILDI" "[$GOT]"

# =============================================================================
echo
echo "=== 2) YORUMLAR ==="
code=$(curl -s -b "$CJ" -o /dev/null -w "%{http_code}" "$B/admin/yorumlar")
[ "$code" = "200" ] && pass "yorum listesi" "$code" || fail "yorum listesi" "$code"

mk_comment() {  # $1 = onayli mi (1/0)
  sqlite3 "$DB" "INSERT INTO comments (content_type,entity_id,author_name,author_email,body,is_approved)
                VALUES ('project',1,'Denetim Yorumu','denetim-yorum@ornek.com',
                        'Yorum denetimi icin gecici kayit metni.',$1);
               SELECT last_insert_rowid();"
}
rm_comment() { sqlite3 "$DB" "DELETE FROM comments WHERE author_email='denetim-yorum@ornek.com';"; }
field() { sqlite3 "$DB" "SELECT COALESCE(is_approved,-1) || '/' || COALESCE(is_spam,-1) FROM comments WHERE id=$1;"; }

for CASE in "onayla:1/0" "onaykaldir:0/0" "spam:0/1" "spamdegil:0/0"; do
  OP="${CASE%%:*}"; EXPECT="${CASE##*:}"
  ID=$(mk_comment 0)
  T=$(tok /admin/yorumlar)
  code=$(post "/admin/yorumlar/islem/$ID" "$T" -d "islem=$OP")
  GOT=$(field "$ID")
  if [ "$code" = "302" ] && [ "$GOT" = "$EXPECT" ]; then
    pass "islem: $OP" "$code ($GOT)"
  else
    fail "islem: $OP" "kod=$code alan=$GOT bek=$EXPECT"
  fi
  rm_comment
done

# not kaydi (yorumlarda admin_note sutunu var mi?)
# admin_note sutunu 1.1.0 ile eklendi
COLS=$(sqlite3 "$DB" "SELECT COUNT(*) FROM pragma_table_info('comments') WHERE name='admin_note';")
if [ "$COLS" = "1" ]; then
  ID=$(mk_comment 0)
  T=$(tok /admin/yorumlar)
  post "/admin/yorumlar/islem/$ID" "$T" -d "islem=not" -d "admin_note=Yonetici notu" >/dev/null
  NOTE=$(sqlite3 "$DB" "SELECT COALESCE(admin_note,'') FROM comments WHERE id=$ID;")
  [ "$NOTE" = "Yonetici notu" ] && pass "islem: not" "302" || fail "islem: not" "[$NOTE]"
  rm_comment
else
  fail "islem: not" "admin_note sutunu YOK - sisma uygulanmali"
fi

# Yorumlar YUMUSAK silinir: kayit durur, deleted_at dolar.
ID=$(mk_comment 1)
T=$(tok /admin/yorumlar)
code=$(post "/admin/yorumlar/islem/$ID" "$T" -d "islem=sil")
DEL=$(sqlite3 "$DB" "SELECT COUNT(*) FROM comments WHERE id=$ID AND deleted_at IS NOT NULL;")
[ "$code" = "302" ] && [ "$DEL" = "1" ] && pass "islem: sil (yumsak)" "$code (deleted_at dolu)" \
                           || fail "islem: sil" "kod=$code isaretli=$DEL"
# silinen yorum listede gorunmemeli
if curl -s -b "$CJ" "$B/admin/yorumlar" | grep -q "denetim-yorum@ornek.com"; then
  fail "silinen yorum listede kaldi" "gorunuyor"
else
  pass "silinen yorum listeden cikti" "0"
fi
sqlite3 "$DB" "DELETE FROM comments WHERE id=$ID;"

# bilinmeyen islem
ID=$(mk_comment 0)
T=$(tok /admin/yorumlar)
code=$(post "/admin/yorumlar/islem/$ID" "$T" -d "islem=olmayan")
[ "$code" = "302" ] && pass "bilinmeyen islem reddedildi" "$code" || fail "bilinmeyen islem" "$code"
rm_comment

# olmayan kayit
T=$(tok /admin/yorumlar)
code=$(post "/admin/yorumlar/islem/999999" "$T" -d "islem=onayla")
[ "$code" = "302" ] && pass "olmayan kayit 500 vermedi" "$code" || fail "olmayan kayit" "$code"

# =============================================================================
echo
echo "=== 3) MESAJLAR ==="
code=$(curl -s -b "$CJ" -o /dev/null -w "%{http_code}" "$B/admin/mesajlar")
[ "$code" = "200" ] && pass "mesaj listesi" "$code" || fail "mesaj listesi" "$code"

mk_msg() {  # $1 = okundu (1/0)
  sqlite3 "$DB" "INSERT INTO messages (name,email,message,is_read) VALUES
                ('Denetim Mesaji','denetim-mesaj@ornek.com','Mesaj denetimi icin gecici kayit.',$1);
               SELECT last_insert_rowid();"
}
rm_msg() { sqlite3 "$DB" "DELETE FROM messages WHERE email='denetim-mesaj@ornek.com';"; }
mfield() { sqlite3 "$DB" "SELECT COALESCE(is_read,-1) || '/' || COALESCE(is_starred,-1) || '/' || COALESCE(is_archived,-1) FROM messages WHERE id=$1;"; }

for CASE in "okundu:1/0/0" "okunmadi:0/0/0" "yildiz:0/1/0" "arsiv:0/0/1" "arsivden-cikar:0/0/0"; do
  OP="${CASE%%:*}"; EXPECT="${CASE##*:}"
  ID=$(mk_msg 0)
  T=$(tok /admin/mesajlar)
  code=$(post "/admin/mesajlar/islem/$ID" "$T" -d "islem=$OP")
  GOT=$(mfield "$ID")
  if [ "$code" = "302" ] && [ "$GOT" = "$EXPECT" ]; then
    pass "islem: $OP" "$code ($GOT)"
  else
    fail "islem: $OP" "kod=$code alan=$GOT bek=$EXPECT"
  fi
  rm_msg
done

# not
COLS=$(sqlite3 "$DB" "SELECT COUNT(*) FROM pragma_table_info('messages') WHERE name='admin_note';")
if [ "$COLS" = "1" ]; then
  ID=$(mk_msg 0); T=$(tok /admin/mesajlar)
  post "/admin/mesajlar/islem/$ID" "$T" -d "islem=not" -d "admin_note=Yonetici notu" >/dev/null
  NOTE=$(sqlite3 "$DB" "SELECT COALESCE(admin_note,'') FROM messages WHERE id=$ID;")
  [ "$NOTE" = "Yonetici notu" ] && pass "islem: not" "302" || fail "islem: not" "[$NOTE]"
  rm_msg
else
  ok "islem: not" "-" "messages tablosunda admin_note sutunu YOK"
fi

# sil
ID=$(mk_msg 0); T=$(tok /admin/mesajlar)
code=$(post "/admin/mesajlar/islem/$ID" "$T" -d "islem=sil")
GONE=$(sqlite3 "$DB" "SELECT COUNT(*) FROM messages WHERE id=$ID;")
[ "$code" = "302" ] && [ "$GONE" = "0" ] && pass "islem: sil" "$code" \
                     || fail "islem: sil" "kod=$code kalan=$GONE"
rm_msg

# detay sayfasi
ID=$(mk_msg 1)
code=$(curl -s -b "$CJ" -o /dev/null -w "%{http_code}" "$B/admin/mesajlar/$ID")
[ "$code" = "200" ] && pass "mesaj detayi" "$code" || fail "mesaj detayi" "$code"
rm_msg

# toplu okundu
ID=$(mk_msg 0)
T=$(tok /admin/mesajlar)
code=$(post "/admin/mesajlar/tumunu-okundu" "$T")
LEFT=$(sqlite3 "$DB" "SELECT COUNT(*) FROM messages WHERE id=$ID AND is_read=0;")
[ "$code" = "302" ] && [ "$LEFT" = "0" ] && pass "toplu okundu" "$code" \
                     || fail "toplu okundu" "kod=$code okunmamis=$LEFT"
rm_msg

# =============================================================================
echo
echo "=== 4) HIZ SINIRLARI (yanlis CSRF) ==="
code=$(curl -s -b "$CJ" -c "$CJ" -o /dev/null -w "%{http_code}" -X POST "$B/admin/ayarlar" -d "_token=yanlis" -d "site_name=X")
[ "$code" = "419" ] || [ "$code" = "302" ] && pass "gecersiz CSRF reddedildi" "$code" \
                                          || fail "gecersiz CSRF" "$code"

echo
echo "=== 5) KALINTI ==="
LEFT_C=$(sqlite3 "$DB" "SELECT COUNT(*) FROM comments WHERE author_email LIKE 'denetim-%' OR author_email LIKE 'captcha-%' OR author_email LIKE 'zaman-%' OR author_email LIKE 'probe-%';")
LEFT_M=$(sqlite3 "$DB" "SELECT COUNT(*) FROM messages WHERE email LIKE 'denetim-%' OR email LIKE 'probe-%';")
printf "  denetim kaydi: %s yorum, %s mesaj %s\n" "$LEFT_C" "$LEFT_M" \
  "$([ "$LEFT_C" = "0" ] && [ "$LEFT_M" = "0" ] && echo 'OK (temiz)' || echo 'KALINTI VAR')"
[ "$LEFT_C" = "0" ] && [ "$LEFT_M" = "0" ] || FAIL=$((FAIL+1))

echo
if [ "$FAIL" -eq 0 ]; then echo "SONUC: yonetim paneli calisiyor"
else echo "SONUC: $FAIL sorun var"; fi
exit "$FAIL"
