#!/usr/bin/env bash
# Form koruması (captcha) — üç modun tamamını gerçek HTTP ile test eder.
#   1. modül kapalıyken      → form korumasız, alan basılmaz
#   2. builtin              → soru basılır, yanlış cevap reddedilir,
#                             doğru cevap kaydeder
#   3. recaptcha (anahtarsız) → sessizce kapanır, form kırılmaz
set -u
cd "$(dirname "$0")/.."
PORT="${PORT:-5050}"
B="http://127.0.0.1:$PORT"
DB=storage/database/app.sqlite
FAIL=0

curl -s -m 3 -o /dev/null "$B/" || { echo "  x sunucu $PORT yanit vermiyor"; exit 1; }
echo "  sunucu $PORT hazir"

set_mode() { sqlite3 "$DB" "UPDATE settings SET value_tr='$1' WHERE key='captcha_mode';"; }
set_active() { sqlite3 "$DB" "UPDATE modules SET is_active=$1 WHERE slug='captcha';"; }
clear_limits() { rm -f storage/cache/ratelimit/*.json; }
set_keys() {
  sqlite3 "$DB" "UPDATE settings SET value_tr='$1' WHERE key='captcha_recaptcha_site';
                  UPDATE settings SET value_tr='$2' WHERE key='captcha_recaptcha_secret';"
}

# Yorum gonderimi: soruyu oku, cevabi hesapla, gonder.
# $1 = beklenen sonuc (ok | hata) - $2 = "auto" | "0" | sabit cevap - $3 = etiket
post_comment() {
  local expect="$1" answer_mode="$2" cj="/tmp/cap-cj"
  rm -f "$cj"
  local slug page
  slug=$(curl -s "$B/isler" | sed -n 's|.*href="\([^"]*\)".*|\1|p' \
         | awk -F'/isler/' '/\/isler\// && !/\/isler$/ {print "/isler/" $2}' | sort -u | head -1)
  page=$(curl -s -c "$cj" -b "$cj" "$B$slug")
  local tok ent typ q sum
  tok=$(printf '%s' "$page" | sed -n 's|.*name="_token" value="\([^"]*\)".*|\1|p' | head -1)
  ent=$(printf '%s' "$page" | sed -n 's|.*name="entity_id" value="\([0-9]*\)".*|\1|p' | head -1)
  typ=$(printf '%s' "$page" | sed -n 's|.*name="content_type" value="\([a-z]*\)".*|\1|p' | head -1)
  q=$(printf '%s' "$page" | sed -n 's|.*class="captcha__q">\([^<]*\)</span>.*|\1|p' | head -1 | tr -d ' ')

  # "auto" -> sorudan hesapla; degilse verilen degeri gonder.
  if [ "$answer_mode" = "auto" ]; then
    # Soru metni "3 + 8 =" seklinde; HTML ayristirmasinda bosluklar
  # kaybolmus olabilir ("3+8="). Once rakam/isaret disi her seyi at.
  sum=$(printf '%s' "$q" | tr -d ' ' | python3 -c "
import re, sys
raw = sys.stdin.read().replace('=', '')
m = re.match(r'.*?([0-9]+)([+-])([0-9]+).*', raw)
print(int(m.group(1)) + int(m.group(3)) if m.group(2) == '+'
      else int(m.group(1)) - int(m.group(3)) if m else '')
")
    # Zaman tuzagi: sunucu soru gosterildikten 2 sn sonra kabul ediyor.
    [ -n "$sum" ] && sleep 3
  else
    sum="$answer_mode"
  fi

  local em="captcha-$(date +%s%N)@ornek.com"
  local code
  code=$(curl -s -b "$cj" -c "$cj" -o /dev/null -w "%{http_code}" -X POST "$B/yorumlar" \
    -d "_token=$tok" -d "content_type=$typ" -d "entity_id=$ent" \
    -d "author_name=Captcha Testi" -d "author_email=$em" \
    -d "body=Form korumasi uctan uca dogrulama metni, yeterli uzunlukta." \
    -d "captcha_answer=$sum" -d "captcha_hp=")
  local row
  row=$(sqlite3 "$DB" "SELECT id FROM comments WHERE author_email='$em';")
  local got="hata"
  [ -n "$row" ] && got="ok"
  sqlite3 "$DB" "DELETE FROM comments WHERE author_email='$em';"

  printf "  %-34s kod=%s bekle=%-5s gercek=%-5s %s\n" "$3" "$code" "$expect" "$got" \
    "$([ "$got" = "$expect" ] && echo OK || { echo HATA; FAIL=$((FAIL+1)); })"
  [ "$expect" = "ok" ] && [ -n "$q" ] || true
}

echo
echo "=== 1) MODUL KAPALI ==="
set_active 0; set_mode builtin; clear_limits
html=$(curl -s "$B/iletisim")
n=$(printf '%s' "$html" | grep -c "captcha_answer")
printf "  iletisimde captcha alani   : %s  %s\n" "$n" \
  "$([ "$n" = "0" ] && echo 'OK (koruma yok)' || echo 'HATA - alan basildi')"
[ "$n" = "0" ] || FAIL=$((FAIL+1))
post_comment ok 0 "modul kapali -> yorum yazilmali"

echo
echo "=== 2) SISTEM ICI (builtin) ==="
set_active 1; set_mode builtin; clear_limits
html=$(curl -s "$B/iletisim")
q=$(printf '%s' "$html" | sed -n 's|.*class="captcha__q">\([^<]*\)</span>.*|\1|p' | head -1)
n=$(printf '%s' "$html" | grep -c "captcha_answer")
printf "  iletisimde captcha alani   : %s  %s\n" "$n" \
  "$([ "$n" -gt 0 ] && echo 'OK (alan basildi)' || echo 'HATA')"
printf "  soru                      : %s\n" "${q:-YOK}"
[ -n "$q" ] || FAIL=$((FAIL+1))
# Soru yalnızca rakam/operatör içermeli; LC_ALL=C ile ASCII kontrolü yapılır
# (grep -P bu ortamda yok).
if printf '%s' "$q" | LC_ALL=C grep -q '[^0-9 +-=]'; then
  printf '  soru karakterleri         : HATA — beklenmeyen karakter var\n'
  FAIL=$((FAIL+1))
else
  printf '  soru karakterleri         : OK (yalnizca rakam ve isaret)\n'
fi

html=$(curl -s "$B/isler/e-ticaret-katalog-uygulamasi")
dq=$(printf '%s' "$html" | sed -n 's|.*class="captcha__q">\([^<]*\)</span>.*|\1|p' | head -1)
printf "  yorumda captcha alani     : %s  soru: %s\n" \
  "$(printf '%s' "$html" | grep -c "captcha_answer")" "${dq:-YOK}"

# zaman tuzağı testi: soru gösterildikten hemen sonra gönderim reddedilmeli
rm -f /tmp/cap-t
P=$(curl -s -c /tmp/cap-t -b /tmp/cap-t "$B/isler/e-ticaret-katalog-uygulamasi")
TK=$(printf '%s' "$P" | sed -n 's|.*name="_token" value="\([^"]*\)".*|\1|p' | head -1)
EN=$(printf '%s' "$P" | sed -n 's|.*name="entity_id" value="\([0-9]*\)".*|\1|p' | head -1)
TY=$(printf '%s' "$P" | sed -n 's|.*name="content_type" value="\([a-z]*\)".*|\1|p' | head -1)
Q=$(printf '%s' "$P" | sed -n 's|.*class="captcha__q">\([^<]*\)</span>.*|\1|p' | head -1 | tr -d ' ')
ANS=$(python3 -c "
import sys
q = '''$Q'''
try:
    a, op, b = q.replace('=','').split()
    print(int(a) + int(b) if op == '+' else int(a) - int(b))
except Exception:
    print('')
")
EM="zaman-$(date +%s%N)@ornek.com"
curl -s -b /tmp/cap-t -c /tmp/cap-t -o /dev/null -X POST "$B/yorumlar" \
  -d "_token=$TK" -d "content_type=$TY" -d "entity_id=$EN" \
  -d "author_name=Zaman Testi" -d "author_email=$EM" \
  -d "body=Zaman tuzagi testi, yeterli uzunlukta metin." \
  -d "captcha_answer=$ANS" -d "captcha_hp="
row=$(sqlite3 "$DB" "SELECT id FROM comments WHERE author_email='$EM';")
sqlite3 "$DB" "DELETE FROM comments WHERE author_email='$EM';"
printf "  dogru cevap + 0 saniye     : %s  %s\n" \
  "$([ -z "$row" ] && echo reddedildi || echo KABUL-EDILDI)" \
  "$([ -z "$row" ] && echo 'OK (zaman tuzagi calisiyor)' || echo 'HATA - tuzak yok')"
[ -z "$row" ] || FAIL=$((FAIL+1))

post_comment hata "999999" "yanlis cevap -> reddedilmeli"
post_comment ok auto "dogru cevap -> yazilmali"

echo
echo "=== 3) REKAPTCHA (anahtar YOK) ==="
set_mode recaptcha; set_keys "" ""; clear_limits
html=$(curl -s "$B/iletisim")
n=$(printf '%s' "$html" | grep -c "g-recaptcha")
php_out=$(php -r 'require "bootstrap/app.php"; echo \Core\Captcha::mode();')
printf "  anahtarsiz reCaptcha modu  : %s  %s\n" "$php_out" \
  "$([ "$php_out" = "off" ] && echo 'OK (sessizce kapali, form kirilmaz)' || echo 'HATA')"
printf "  iletisimde recaptcha alani: %s  %s\n" "$n" \
  "$([ "$n" = "0" ] && echo 'OK (alan basilmadi)' || echo 'HATA')"
[ "$php_out" = "off" ] || FAIL=$((FAIL+1))
[ "$n" = "0" ] || FAIL=$((FAIL+1))
post_comment ok 0 "anahtarsiz recaptcha -> yazilmali"

echo
echo "=== 4) REKAPTCHA (anahtarli, alan basilmali) ==="
set_keys "6LcTestSiteKey1234567890" "6LcTestSecret1234567890"
php_out=$(php -r 'require "bootstrap/app.php"; echo \Core\Captcha::mode();')
printf "  anahtarli mod              : %s  %s\n" "$php_out" \
  "$([ "$php_out" = "recaptcha" ] && echo 'OK' || echo 'HATA')"
[ "$php_out" = "recaptcha" ] || FAIL=$((FAIL+1))
html=$(curl -s "$B/iletisim")
n=$(printf '%s' "$html" | grep -c "g-recaptcha")
k=$(printf '%s' "$html" | grep -c "6LcTestSiteKey")
printf "  recaptcha kutusu          : %s  sitekey: %s  %s\n" "$n" "$k" \
  "$([ "$n" -gt 0 ] && [ "$k" -gt 0 ] && echo 'OK' || echo 'HATA')"
[ "$n" -gt 0 ] && [ "$k" -gt 0 ] || FAIL=$((FAIL+1))
# jeton olmadan gonderim reddedilmeli
rm -f /tmp/cap-r
P=$(curl -s -c /tmp/cap-r -b /tmp/cap-r "$B/iletisim")
TK=$(printf '%s' "$P" | sed -n 's|.*name="_token" value="\([^"]*\)".*|\1|p' | head -1)
EM="recap-$(date +%s%N)@ornek.com"
curl -s -b /tmp/cap-r -c /tmp/cap-r -o /dev/null -X POST "$B/iletisim" \
  -d "_token=$TK" -d "name=Captcha Testi" -d "email=$EM" \
  -d "message=reCAPTCHA jetonsuz gonderim denemesi, yeterli uzunlukta." -d "kvkk=1"
row=$(sqlite3 "$DB" "SELECT id FROM messages WHERE email='$EM';")
sqlite3 "$DB" "DELETE FROM messages WHERE email='$EM';"
printf "  jetonsuz gonderim         : %s  %s\n" \
  "$([ -z "$row" ] && echo reddedildi || echo KABUL-EDILDI)" \
  "$([ -z "$row" ] && echo 'OK' || echo 'HATA')"
[ -z "$row" ] || FAIL=$((FAIL+1))

echo
echo "=== 5) YENILEME UCU ==="
set_active 1; set_mode builtin
out=$(curl -s -c /tmp/cap-n -b /tmp/cap-n "$B/captcha/yenile")
echo "  yanit                     : $out"
if printf '%s' "$out" | grep -q '"mode":"builtin"' && printf '%s' "$out" | grep -q '"text":"[0-9]'; then
  echo "  mod + yeni soru donuyor   : OK"
else
  echo "  mod + yeni soru donuyor   : HATA"; FAIL=$((FAIL+1))
fi

echo
echo "=== GERI AL ==="
set_active 0; set_mode off; set_keys "" ""
php -r 'require "bootstrap/app.php"; echo "  mod: " . \Core\Captcha::mode() . " (modul kapali = off)\n";'

echo
if [ "$FAIL" -eq 0 ]; then echo "SONUC: captcha butun modlarda calisiyor"
else echo "SONUC: $FAIL sorun var"; fi
exit "$FAIL"
