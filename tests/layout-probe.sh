#!/usr/bin/env bash
# Sunucunun ayakta olduğundan emin ol, yerel ölçümü yap, sunucuyu açık bırak.
set -u
cd "$(dirname "$0")/.."
PORT=5050
B="http://127.0.0.1:$PORT"
CHROME=$(command -v chromium || command -v google-chrome-stable)

ensure() {
  if curl -s -m 2 -o /dev/null "$B/"; then return 0; fi
  setsid nohup php -S 0.0.0.0:$PORT -t public public/router.php \
      > /tmp/tsn-$PORT.log 2>&1 < /dev/null &
  for _ in 1 2 3 4 5 6 7 8; do sleep 1; curl -s -m 2 -o /dev/null "$B/" && return 0; done
  return 1
}

ensure || { echo "  ✗ sunucu başlatılamadı"; exit 1; }
echo "  ✓ sunucu ayakta ($B)"

cat > public/__s.html <<'HTML'
<!doctype html><meta charset="utf-8"><body><pre id="o">x</pre>
<iframe id="f" src="/" style="width:1440px;height:1200px;border:0"></iframe>
<script>
document.getElementById('f').onload=function(){setTimeout(function(){
 var d=this.contentDocument,L=[];
 [].slice.call(d.querySelectorAll('section')).forEach(function(s,i){
   var h=Math.round(s.getBoundingClientRect().height);
   var t=(s.querySelector('h2')||{}).textContent||'(cağrı)';
   L.push((i+1)+'. '+String(t).trim().slice(0,30)+'  h='+h+'px');
 });
 L.push('--- toplam sayfa '+d.documentElement.scrollHeight+'px');
 var sc=d.querySelector('.showcase');
 L.push('showcase  : '+(sc?getComputedStyle(sc).gridTemplateColumns:'YOK'));
 var q=d.querySelector('.quote-grid');
 L.push('quote-grid: '+(q?getComputedStyle(q).gridTemplateColumns:'YOK'));
 var li=d.querySelector('.line-list__item');
 L.push('line-list : '+(li?getComputedStyle(li).gridTemplateColumns:'YOK'));
 L.push('görsel    : '+d.querySelectorAll('img').length+' adet · yüklenen '+d.querySelectorAll('img[src^="/uploads"]').length);
 var ov=d.documentElement.scrollWidth-d.documentElement.clientWidth;
 L.push('yatay taşma: '+ov+'px');
 document.getElementById('o').textContent=L.join('\n');
},1200);};
</script>
HTML

"$CHROME" --headless=new --disable-gpu --no-sandbox --window-size=1500,1400 \
  --virtual-time-budget=9000 --dump-dom "$B/__s.html" 2>/dev/null \
  | sed -n '/<pre id="o">/,/<\/pre>/p' | sed 's/<[^>]*>//g' | grep -vE '^\s*$' | sed 's/^/  /'
rm -f public/__s.html
