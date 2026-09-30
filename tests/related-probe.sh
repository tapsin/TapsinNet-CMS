#!/usr/bin/env bash
# "İlgili içerikler" bölümünü denetler: başlık doğru mu, mevcut sayfanın
# kartı kendisini içeriyor mu, kategori filtresi uygulanmış mı.
set -u
cd "$(dirname "$0")/.."
PORT=5054
B="http://127.0.0.1:$PORT"
PAGE="${1:-/isler/e-ticaret-katalog-uygulamasi}"
CHROME=$(command -v chromium || command -v google-chrome-stable)
W="${2:-1440}"

if ! curl -s -m 2 -o /dev/null "$B/"; then
  setsid nohup php -S 0.0.0.0:$PORT -t public public/router.php \
      > /tmp/tsn-$PORT.log 2>&1 < /dev/null &
  for _ in 1 2 3 4 5 6 7 8; do sleep 1; curl -s -m 2 -o /dev/null "$B/" && break; done
fi
curl -s -m 3 -o /dev/null "$B/" || { echo "  x sunucu yok"; exit 1; }
echo "  ok $PAGE"

PROBE=$(cat <<'JS'
(function(){
  var L=[],d=document,W=window;
  function push(s){L.push(s);}
  var h1=d.querySelector('h1');
  push('sayfa        : '+window.location.pathname);
  push('h1          : "'+(h1?h1.textContent.trim().slice(0,40):'YOK')+'"');
  // "İlgili içerikler" bölümünü bul.
  // DİKKAT: JS regex 'i' flag'i "İ" ile "i"yi eşleştirmez ve
  // "İ".toLowerCase() iki kodepoint verir ("i"+U+0307). Bu yüzden
  // 'lgili' parçası aranır — 'i' harfi hiç geçmez, sorun yaşanmaz.
  var secs=Array.prototype.slice.call(d.querySelectorAll('section'));
  var rel=null;
  secs.forEach(function(s){
    var t=s.querySelector('.section__title');
    if(!t){return;}
    var txt=t.textContent.toLowerCase();
    if(txt.indexOf('lgili')>=0||txt.indexOf('elated')>=0){rel=s;}
  });
  if(!rel){push('HATA: ilgili icerikler bolumu YOK');}
  else{
    var head=rel.querySelector('.section__head');
    var title=rel.querySelector('.section__title');
    var link=head?head.querySelector('a.btn--quiet'):null;
    push('--- ILGILI ICERIKLER ---');
    push('baslik     : "'+title.textContent.trim()+'"');
    push('baslik uzunluk: '+title.textContent.trim().length);
    push('buton      : '+(link?'"'+link.textContent.trim()+'" -> '+link.getAttribute('href'):'YOK'));
    push('baslik==buton: '+(link&&title.textContent.trim()===link.textContent.trim()?'EVET - KARIŞIK':'hayir - dogru'));
    var cards=Array.prototype.slice.call(rel.querySelectorAll('.card, article, .card-grid > *'));
    push('kart sayisi: '+cards.length);
    var here=window.location.pathname;
    cards.forEach(function(c,i){
      var a=c.querySelector('a[href]');
      var href=a?a.getAttribute('href'):'';
      var same=href.replace(/^https?:\/\/[^/]+/,'').replace(/\/$/,'')===here.replace(/\/$/,'');
      push('  '+(i+1)+') "'+(a?a.textContent.trim().slice(0,28):'?')+'" -> '+href+(same?'   <== KENDISI (HATA)':''));
    });
  }
  // onceki/sonraki nav etiketi
  var nav=d.querySelector('nav.hairline-stack');
  if(nav){push('--- ONCEKI/SONRAKI ---');push('aria-label : "'+nav.getAttribute('aria-label')+'"');}
  // kart izgaralari: kac kolon? kartlar sikismis mi?
  push('--- KART IZGARALARI ---');
  var grids=Array.prototype.slice.call(d.querySelectorAll('.card-grid'));
  grids.forEach(function(g,i){
    var cols=W.getComputedStyle(g).gridTemplateColumns.split(' ').length;
    var kids=Array.prototype.slice.call(g.children);
    var gwid=Math.round(g.getBoundingClientRect().width);
    var kidw=kids.length?Math.round(kids[0].getBoundingClientRect().width):0;
    push('  ['+i+'] "'+g.className+'"');
    push('       kolon='+cols+' kart='+kids.length+' gridW='+gwid+'px kartW='+kidw+'px');
    if(kidw>0&&kidw<160){push('       !! kartlar COK DAR (<160px) - HATA');}
  });
  var o=d.createElement('pre');
  o.style.cssText='position:fixed;left:0;top:0;z-index:99999;background:#fff;color:#000;font:12px monospace;padding:10px;white-space:pre-wrap;max-width:100%';
  o.textContent=L.join('\n');
  d.body.appendChild(o);
})();
JS
)

{
  echo '<!doctype html><meta charset="utf-8"><body style="margin:0">'
  curl -s "$B$PAGE"
  echo "<script>window.addEventListener('load',function(){setTimeout(function(){"
  echo "$PROBE"
  echo "},800)});</script>"
} > public/__probe.html

"$CHROME" --headless=new --disable-gpu --no-sandbox --window-size=${W},1200 \
  --virtual-time-budget=15000 --dump-dom "$B/__probe.html" 2>/dev/null \
  | sed -n '/font:12px monospace/,/<\/pre>/p' | sed 's/<[^>]*>//g' | grep -vE '^\s*$' | sed 's/^/  /'
rm -f public/__probe.html
