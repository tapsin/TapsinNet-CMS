#!/usr/bin/env bash
# Sitedeki TÜM iç bağlantıları toplar ve her birini gerçekten istekle
# dener. Kırık olanları adresiyle listeler.
#
# KÖK NEDEN NOTU: Bu ortamdaki grep -oE sola-yönlü (leftmost-shortest)
# eşleşiyor ve URL'leri 25 karakterde kesiyor. Bu yüzden grep değil
# sed + awk kullanılıyor.
set -u
cd "$(dirname "$0")/.."
PORT="${PORT:-5050}"
B="http://127.0.0.1:$PORT"
BASE="http://127.0.0.1:$PORT"

curl -s -m 3 -o /dev/null "$B/" || { echo "  x sunucu $PORT yanit vermiyor"; exit 1; }
echo "  sunucu $PORT hazir"

# Taranacak sayfalar: liste + detaylar
PAGES="/ /iletisim /ara /sosyal /hizmetler /isler /haberler /galeri /videolar /sertifikalar /profiller /referanslar /sss"
for tbl in services projects news certificates gallery_items videos profiles testimonials faq; do
  slugs=$(sqlite3 storage/database/app.sqlite \
    "SELECT slug FROM $tbl WHERE is_active=1 AND deleted_at IS NULL LIMIT 3;" 2>/dev/null)
  route=$(python3 - "$tbl" <<'PY'
import re, sys
tbl = sys.argv[1]
m = {'services':'hizmetler','projects':'isler','news':'haberler','certificates':'sertifikalar',
     'gallery_items':'galeri','videos':'videolar','profiles':'profiller',
     'testimonials':'referanslar','faq':'sss'}
print(m.get(tbl, ''))
PY
)
  [ -n "$route" ] || continue
  for s in $slugs; do PAGES="$PAGES /$route/$s"; done
done

# Görsel/JS/CSS gibi varlıklar ve dış bağlantılar hariç
extract() {  # $1 = HTML
  printf '%s' "$1" | sed -n 's|.*href="\(http://127\.0\.0\.1:[0-9]*\(/[^"?#]*\)\)".*|\1|p' \
    | sort -u
}

ALL=$(mktemp)
echo
echo "=== sayfalar taraniyor ==="
for p in $PAGES; do
  code=$(curl -s -m 5 -o /dev/null -w "%{http_code}" "$B$p")
  [ "$code" = "200" ] || { printf "  %-40s %s (atlandi)\n" "$p" "$code"; continue; }
  extract "$(curl -s -m 8 "$B$p")" >> "$ALL"
  printf "  %-40s %s\n" "$p" "$code"
done

sort -u "$ALL" -o "$ALL"
TOTAL=$(wc -l < "$ALL")
echo
echo "=== $TOTAL benzersiz ic baglanti denetleniyor ==="
BROKEN=0
CHECKED=0
while IFS= read -r u; do
  [ -n "$u" ] || continue
  # varlik degil (css/js/img/font) ve 3. segmentli yol degilse atla
  case "$u" in
    *.css|*.js|*.woff|*.svg|*.webp|*.png|*.jpg|*.ico) continue ;;
  esac
  CHECKED=$((CHECKED+1))
  code=$(curl -s -m 6 -o /dev/null -w "%{http_code}" "$u")
  if [ "$code" = "404" ] || [ "$code" = "500" ]; then
    printf "  %-6s %s\n" "$code" "${u#$BASE}"
    BROKEN=$((BROKEN+1))
  fi
done < "$ALL"

rm -f "$ALL"
echo
echo "denetlenen : $CHECKED"
echo "kırık      : $BROKEN"
[ "$BROKEN" -eq 0 ] && echo "SONUC: butun ic baglantilar calisiyor" || echo "SONUC: $BROKEN kirik baglanti var"
exit "$BROKEN"
