#!/usr/bin/env bash
# /iletisim form ölçümü: alan genişlikleri, hizalama, taşma, hata durumu.
# php -S tek iş parçacıklı olduğu için iframe CSS'i zamanında alamıyor ve
# ölçüm yalan söylüyor. Sayfa doğrudan yüklenir, CSS kesin uygulanır.
set -u
cd /home/tapsin/Masaüstü/Projeler/tapsinnet
PORT=5053
B="http://127.0.0.1:$PORT"
PAGE="${1:-/iletisim}"
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
  push('body font : '+W.getComputedStyle(d.body).fontFamily.slice(0,30));
  var fg=d.querySelector('.form-grid');
  if(!fg){push('HATA: .form-grid yok');}
  else{
    var fcs=W.getComputedStyle(fg);
    push('--- FORM-GRID ---');
    push('display    : '+fcs.display);
    push('columns    : '+fcs.gridTemplateColumns);
    var fields=Array.prototype.slice.call(fg.querySelectorAll('.field'));
    var gridW=fg.getBoundingClientRect().width;
    push('grid genisl: '+Math.round(gridW)+'px, alan sayisi '+fields.length);
    fields.forEach(function(f,i){
      var r=f.getBoundingClientRect();
      var inp=f.querySelector('input,select,textarea');
      var ir=inp?inp.getBoundingClientRect():{width:0,height:0};
      var col=W.getComputedStyle(f).gridColumn;
      push('  '+(i+1)+') "'+(f.querySelector('.field__label')||{textContent:'?'}).textContent.trim().slice(0,18)+'"'+
           ' x='+Math.round(r.left)+' w='+Math.round(r.width)+
           ' col='+col+
           ' inputW='+Math.round(ir.width)+' h='+Math.round(ir.height));
    });
    var six=fields.filter(function(f){return /col-6/.test(f.className);}).length;
    var tops=fields.map(function(f){return Math.round(f.getBoundingClientRect().top);});
    var uniq=[];tops.forEach(function(t){if(uniq.indexOf(t)===-1)uniq.push(t);});
    // Beklenen satir sayisi: masaustunde col-6 ikili + col-12 tek basina.
    // Mobilde medya kurali `.form-grid > [class*="col-"] { grid-column: 1/-1 }`
    // devreye girer, yani butun alanlar tek tek tam satir olur -> alan sayisi.
    var full=fields.filter(function(f){
      return f.getBoundingClientRect().width>=gridW-2;});
    var expect=full.length===fields.length
        ? fields.length
        : Math.ceil(six/2)+(fields.length-six);
    push('satir sayisi : '+uniq.length+'  (beklenen '+expect+')'+
         (uniq.length===expect?'  ESIT - dogru':'  FARKLI - kontrol et')+
         (full.length===fields.length?'  [mobil: alanlar tam satir]':''));
    var narrow=fields.filter(function(f){return f.getBoundingClientRect().width<40;});
    push('taslak alan  : '+narrow.length+(narrow.length?'  HATA (cok dar)':'  yok - dogru'));
    push('alan/genislik: '+(fields[0]?Math.round(fields[0].getBoundingClientRect().width/gridW*100)+'%':'?'));
  }
  push('--- ALAN STILLERI ---');
  var lbl=d.querySelector('.field__label');
  if(lbl){var ls=W.getComputedStyle(lbl);
    push('label font : '+ls.fontSize+'/'+ls.fontWeight+' '+ls.color);}
  var inp=d.querySelector('.input');
  if(inp){var is=W.getComputedStyle(inp);
    push('input font : '+is.fontSize+'/'+is.lineHeight);
    push('input pad  : '+is.padding);
    push('input bord : '+is.borderTopWidth+' '+is.borderTopColor);
    push('input rad  : '+is.borderRadius);}
  var ta=d.querySelector('.textarea');
  if(ta){var ts=W.getComputedStyle(ta);
    push('textarea   : h='+Math.round(ta.getBoundingClientRect().height)+' minH='+ts.minHeight);}
  var sel=d.querySelector('.select');
  push('select     : '+(sel?'VAR':'YOK'));
  push('--- KVKK CHECKBOX ---');
  var cb=d.querySelector('.field--checkbox');
  if(cb){
    var cr=cb.getBoundingClientRect();
    var cbi=cb.querySelector('input');
    var cbl=cb.querySelector('label');
    var ir2=cbi?cbi.getBoundingClientRect():{left:-1,top:-1,width:0,height:0};
    var lr=cbl?cbl.getBoundingClientRect():{left:-1,top:-1,width:0,height:0};
    push('field disp : '+W.getComputedStyle(cb).display);
    push('cb x='+Math.round(ir2.left)+' y='+Math.round(ir2.top)+' '+Math.round(ir2.width)+'x'+Math.round(ir2.height));
    push('lb x='+Math.round(lr.left)+' y='+Math.round(lr.top)+' '+Math.round(lr.width)+'x'+Math.round(lr.height));
    push('yan yana mi : '+(Math.abs(ir2.top-lr.top)<12?'EVET':'HAYIR - alt alta'));
  }
  push('--- BUTON ---');
  var sub=d.querySelector('[data-submit-btn]');
  if(sub){
    var ss=W.getComputedStyle(sub), sr=sub.getBoundingClientRect();
    push('submit     : '+Math.round(sr.width)+'x'+Math.round(sr.height)+' display='+ss.display);
    push('min yuksek : '+ss.minHeight);
  }
  push('--- GONDERIM KORUMASI ---');
  push('app.js yuklu: '+(W.__TSN?'EVET':'HAYIR'));
  push('js hatalari : '+(W.__errs.length?W.__errs.join(' | '):'yok'));
  // DİKKAT: sayfada header arama formu da var. querySelector('form') onu seçer.
  // Doğru formu submit butonundan türet.
  var b=d.querySelector('[data-submit-btn]');
  if(b){
    var f=b.form||b.closest('form');
    push('form       : '+(f?f.getAttribute('action'):'YOK'));
    push('once     : busy='+b.getAttribute('aria-busy')+' disabled='+b.disabled);
    f.dispatchEvent(new Event('submit',{cancelable:true,bubbles:true}));
    push('sonra    : busy='+b.getAttribute('aria-busy')+' disabled='+b.disabled+
         (b.getAttribute('aria-busy')==='true'&&b.disabled?'  dogru':'  HATA'));
    var bs2=W.getComputedStyle(b,'::after');
    push('spinner  : content='+bs2.content+' anim='+bs2.animationName);
  }
  push('--- KUTU ---');
  var fa=d.querySelector('.form-actions');
  if(fa){push('actions mt : '+W.getComputedStyle(fa).marginTop);}
  var aside=d.querySelector('aside .dl');
  push('dl var     : '+(aside?'VAR':'YOK'));
  var kv=d.querySelector('.faq-card');
  push('kvkk det   : '+(kv?'VAR '+kv.getBoundingClientRect().width+'px':'YOK'));
  push('tasma      : '+(d.documentElement.scrollWidth-d.documentElement.clientWidth)+'px');
  var o=d.createElement('pre');
  o.style.cssText='position:fixed;left:0;top:0;z-index:99999;background:#fff;color:#000;font:12px monospace;padding:10px;white-space:pre-wrap;max-width:100%';
  o.textContent=L.join('\n');
  d.body.appendChild(o);
})();
JS
)

{
  echo '<!doctype html><meta charset="utf-8"><body style="margin:0">'
  echo '<script>window.__errs=[];window.addEventListener("error",function(e){window.__errs.push(e.message+" @"+(e.filename||"").split("/").pop()+":"+e.lineno);});</script>'
  curl -s "$B$PAGE"
  echo "<script>window.addEventListener('load',function(){setTimeout(function(){"
  echo "$PROBE"
  echo "},800)});</script>"
} > public/__probe.html

"$CHROME" --headless=new --disable-gpu --no-sandbox --window-size=$W,1200 \
  --virtual-time-budget=15000 --dump-dom "$B/__probe.html" 2>/dev/null \
  | sed -n '/font:12px monospace/,/<\/pre>/p' | sed 's/<[^>]*>//g' | grep -vE '^\s*$' | sed 's/^/  /'
rm -f public/__probe.html
