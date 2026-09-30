#!/usr/bin/env bash
# Belirli bir sayfanın masthead'ini ölç. Sayfayı doğrudan yükler, sonra
# chromium'un --dump-dom yerine sayfa içine ölçüm script'i enjekte eder.
# GEREKÇE: php -S tek iş parçacıklı. iframe ve fetch+document.write
# teknikleri CSS'i zamanında yükleyemiyor, ölçüm "CSS uygulanmadı" diye
# yalan söylüyor. Bu yöntem sayfayı normal yükler, CSS kesin uygulanır.
set -u
cd "$(dirname "$0")/.."
PORT=5052
B="http://127.0.0.1:$PORT"
PAGE="${1:-/}"
W="${2:-1440}"
CHROME=$(command -v chromium || command -v google-chrome-stable)

if ! curl -s -m 2 -o /dev/null "$B/"; then
  setsid nohup php -S 0.0.0.0:$PORT -t public public/router.php \
      > /tmp/tsn-$PORT.log 2>&1 < /dev/null &
  for _ in 1 2 3 4 5 6 7 8; do sleep 1; curl -s -m 2 -o /dev/null "$B/" && break; done
fi
curl -s -m 3 -o /dev/null "$B/" || { echo "  x sunucu yok"; exit 1; }
echo "  ok $PAGE @ ${W}px"

PROBE=$(cat <<'JS'
(function(){
  var L=[],d=document,W=window;
  function push(s){L.push(s);}
  var ul=d.querySelector('.masthead__links');
  push('body font    : '+W.getComputedStyle(d.body).fontFamily.slice(0,30));
  var mh=d.querySelector('.masthead');
  push('masthead pos : '+(mh?W.getComputedStyle(mh).position:'YOK'));
  push('--- MENU ---');
  if(!ul){push('HATA: .masthead__links yok');}
  else{
    var cs=W.getComputedStyle(ul);
    push('ul display   : '+cs.display);
    push('list-style   : '+cs.listStyleType);
    var links=Array.prototype.slice.call(ul.querySelectorAll('a'));
    var vis=links.filter(function(a){return a.getClientRects().length>0;});
    push('link toplam  : '+links.length+'  gorunur: '+vis.length);
    var tops=vis.map(function(a){return Math.round(a.getBoundingClientRect().top);});
    vis.forEach(function(a,i){
      var r=a.getBoundingClientRect();
      push('  '+(i+1)+') "'+a.textContent.trim()+'" x='+Math.round(r.left)+' y='+Math.round(r.top)+' w='+Math.round(r.width)+' h='+Math.round(r.height));
    });
    push('tek satir    : '+(tops.length&&tops.every(function(t){return t===tops[0];})?'EVET':'HAYIR'));
    var act=d.querySelector('.nav-link.is-active');
    if(act){var as=W.getComputedStyle(act);
      push('--- AKTIF: "'+act.textContent.trim()+'" ---');
      push('  bg        : '+as.backgroundColor);
      push('  color     : '+as.color);
      push('  border    : '+as.borderTopWidth+' '+as.borderTopColor);}
    else{push('aktif link   : YOK');}
    var sum=d.querySelector('.dropdown__trigger');
    if(sum){var ss=W.getComputedStyle(sum), sr=sum.getBoundingClientRect();
      push('tetik        : "'+sum.textContent.trim()+'" '+Math.round(sr.width)+'x'+Math.round(sr.height)+' '+ss.display);}
    var dd=d.querySelector('.dropdown');
    if(dd){push('dropdown acik: '+dd.open);
      dd.open=true;
      var dm=d.querySelector('.dropdown__menu'), dr=dm.getBoundingClientRect();
      push('  menu      : '+dm.querySelectorAll('a').length+' kalem '+Math.round(dr.width)+'x'+Math.round(dr.height));
      push('  tasma     : '+(d.documentElement.scrollWidth-d.documentElement.clientWidth)+'px');
      dd.open=false;}
  }
  push('taşma        : '+(d.documentElement.scrollWidth-d.documentElement.clientWidth)+'px');
  var o=d.createElement('pre');
  o.style.cssText='position:fixed;left:0;top:0;z-index:99999;background:#fff;color:#000;font:12px monospace;padding:10px;white-space:pre-wrap;max-width:100%';
  o.textContent=L.join('\n');
  d.body.appendChild(o);
})();
JS
)

# chromium --dump-dom sayfayı yükler ama script enjekte edemez.
# Bunun yerine site üzerinden geçici bir ölçüm uç noktası sunarız: router'a
# girmeden, yalnızca /__probe.html dosyası olarak. Dosya kendi kopyasını alır.
{
  echo '<!doctype html><meta charset="utf-8"><body style="margin:0">'
  curl -s "$B$PAGE"
  echo "<script>window.addEventListener('load',function(){setTimeout(function(){"
  echo "$PROBE"
  echo "},800)});</script>"
} > public/__probe.html

"$CHROME" --headless=new --disable-gpu --no-sandbox --window-size=$W,900 \
  --virtual-time-budget=15000 --dump-dom "$B/__probe.html" 2>/dev/null \
  | sed -n '/font:12px monospace/,/<\/pre>/p' | sed 's/<[^>]*>//g' | grep -vE '^\s*$' | sed 's/^/  /'
rm -f public/__probe.html
