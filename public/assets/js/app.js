/* Hallmark · TapsinNet · public etkileşim katmanı
 * ---------------------------------------------------------------------------
 *  Sıfır bağımlılık. `defer` ile yüklenir. CSP uyumlu:
 *  innerHTML ile veri yazılmaz, eval/new Function yok, style özniteliği ile
 *  dinamik değer yazılmaz (yalnızca sayısal indeksler setProperty ile).
 * ------------------------------------------------------------------------- */
(function (window, document) {
  'use strict';

  var reduceMotion = window.matchMedia
    ? window.matchMedia('(prefers-reduced-motion: reduce)')
    : { matches: false };

  function on(el, evt, handler) {
    if (el) { el.addEventListener(evt, handler); }
  }

  function all(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }
  function one(sel, ctx) { return (ctx || document).querySelector(sel); }

  function focusables(root) {
    return all('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]),' +
               ' textarea:not([disabled]), [tabindex]:not([tabindex="-1"])', root)
      .filter(function (el) { return el.offsetParent !== null; });
  }

  /* =====================================================================
   *  Odak tuzağı + ESC — modal ortak davranışı
   * =================================================================== */
  function trapFocus(modal) {
    var lastFocused = document.activeElement;

    function onKey(e) {
      if (e.key === 'Escape') { close(); return; }
      if (e.key !== 'Tab') { return; }

      var list = focusables(modal);
      if (!list.length) { return; }
      var first = list[0];
      var last = list[list.length - 1];

      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }

    function close() {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.removeEventListener('keydown', onKey);
      document.body.classList.remove('no-scroll');
      if (lastFocused && lastFocused.focus) { lastFocused.focus(); }
    }

    on(modal, 'click', function (e) {
      if (e.target === modal || e.target.closest('[data-close]')) { close(); }
    });

    document.addEventListener('keydown', onKey);
    document.body.classList.add('no-scroll');
    modal.setAttribute('aria-hidden', 'false');

    var initial = one('[autofocus]', modal) || focusables(modal)[0];
    if (initial) { initial.focus(); }

    return close;
  }

  /* =====================================================================
   *  1b · Header açılır menüleri — dış tıklama / ESC ile kapat
   * =================================================================== */
  function dropdowns() {
    var list = all('.dropdown');
    if (!list.length) { return; }

    function closeAll(except) {
      list.forEach(function (d) { if (d !== except) { d.open = false; } });
    }

    list.forEach(function (d) {
      var summary = one('summary', d);
      on(summary, 'click', function () { closeAll(d); });

      // Menüde bir linke tıklanınca kapansın
      all('a', d).forEach(function (a) {
        on(a, 'click', function () { d.open = false; });
      });
    });

    on(document, 'click', function (e) {
      list.forEach(function (d) {
        if (d.open && !d.contains(e.target)) { d.open = false; }
      });
    });

    on(document, 'keydown', function (e) {
      if (e.key !== 'Escape') { return; }
      var open = list.filter(function (d) { return d.open; })[0];
      if (!open) { return; }
      open.open = false;
      var s = one('summary', open);
      if (s) { s.focus(); }
    });
  }

  /* =====================================================================
   *  1c · Form koruması — sistem içi soruyu yenile
   * =================================================================== */
  function captchaRefresh() {
    all('[data-captcha-reload]').forEach(function (btn) {
      on(btn, 'click', function () {
        var wrap = btn.closest('.captcha');
        var q = wrap ? one('.captcha__q', wrap) : null;
        if (!q) { return; }

        btn.classList.add('is-loading');
        fetch(window.location.origin + '/captcha/yenile', {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin'
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.text) {
              q.textContent = data.text;
              var input = one('.captcha__input', wrap);
              // eski cevap yeni soruya ait olmaz → temizle
              if (input) { input.value = ''; input.focus(); }
            }
          })
          .catch(function () { /* soru eskisiyle kalır, kullanıcı çözer */ })
          .then(function () { btn.classList.remove('is-loading'); });
      });
    });
  }

  /* =====================================================================
   *  1 · Mobil menü
   * =================================================================== */
  function mobileMenu() {
    var masthead = one('.masthead');
    var burger = one('.masthead__burger');
    if (!masthead || !burger) { return; }
    var panel = one('#' + burger.getAttribute('aria-controls'), masthead);

    function setOpen(next) {
      burger.setAttribute('aria-expanded', next ? 'true' : 'false');
      masthead.classList.toggle('is-open', next);
      // hidden attribute'i de senkron tut: CSS .is-open güveniyor, HTML
      // hidden güveniyor — ikisi de tutmazsa menü görünmez/görünür kalır.
      if (panel) { panel.hidden = !next; }
    }

    on(burger, 'click', function () {
      setOpen(burger.getAttribute('aria-expanded') !== 'true');
    });

    // Menüde bir linke tıklanınca kapansın
    all('.masthead__mobile a', masthead).forEach(function (link) {
      on(link, 'click', function () { setOpen(false); });
    });

    on(document, 'keydown', function (e) {
      if (e.key === 'Escape' && masthead.classList.contains('is-open')) {
        setOpen(false);
        burger.focus();
      }
    });

    // Görünüm genişliğine dönüşte menüyü sıfırla
    var mq = window.matchMedia('(min-width: 861px)');
    var onChange = function (e) {
      if (e.matches) { setOpen(false); }
    };
    if (mq.addEventListener) { mq.addEventListener('change', onChange); }
    else if (mq.addListener) { mq.addListener(onChange); }
  }

  /* =====================================================================
   *  2 · Lightbox
   * =================================================================== */
  function lightbox() {
    var box = one('.lightbox');
    if (!box) { return; }

    var img = one('.lightbox__img', box);
    var cap = one('.lightbox__caption', box);
    var close = null;

    function open(src, caption) {
      if (!img || !src) { return; }
      img.setAttribute('src', src);
      img.setAttribute('alt', caption || '');
      if (cap) { cap.textContent = caption || ''; }
      box.classList.add('is-open');
      close = trapFocus(box);
    }

    // Tıklanabilir görseller: data-lightbox-src
    all('[data-lightbox-src]').forEach(function (trigger) {
      on(trigger, 'click', function (e) {
        e.preventDefault();
        open(trigger.getAttribute('data-lightbox-src'), trigger.getAttribute('data-lightbox-caption') || '');
      });
      if (trigger.tagName !== 'A' && !trigger.hasAttribute('tabindex')) {
        trigger.setAttribute('tabindex', '0');
        trigger.setAttribute('role', 'button');
        on(trigger, 'keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); trigger.click(); }
        });
      }
    });

    box.__open = open;
  }

  /* =====================================================================
   *  3 · Video modal — iframe createElement ile kurulur
   * =================================================================== */
  function videoModal() {
    var box = one('.video-modal');
    if (!box) { return; }

    var frame = one('.video-modal__frame', box);
    var title = one('.video-modal__title', box);
    var close = null;

    function open(embedUrl, videoTitle) {
      if (!frame || !embedUrl) { return; }

      // Eski iframe'i temizle — bellek sızıntısını önler
      while (frame.firstChild) { frame.removeChild(frame.firstChild); }

      var iframe = document.createElement('iframe');
      iframe.setAttribute('src', embedUrl);
      iframe.setAttribute('title', videoTitle || 'Video');
      iframe.setAttribute('allow', 'accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
      iframe.setAttribute('allowfullscreen', '');
      iframe.setAttribute('loading', 'lazy');
      frame.appendChild(iframe);

      if (title) { title.textContent = videoTitle || ''; }

      box.classList.add('is-open');
      close = trapFocus(box);
    }

    function destroy() {
      if (frame) { while (frame.firstChild) { frame.removeChild(frame.firstChild); } }
    }

    all('[data-video-embed]').forEach(function (trigger) {
      on(trigger, 'click', function (e) {
        e.preventDefault();
        open(trigger.getAttribute('data-video-embed'), trigger.getAttribute('data-video-title') || '');
      });
    });

    on(box, 'click', function (e) {
      if (e.target === box || e.target.closest('[data-close]')) {
        if (close) { close(); }
        box.classList.remove('is-open');
        destroy();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && box.classList.contains('is-open')) {
        if (close) { close(); }
        box.classList.remove('is-open');
        destroy();
      }
    });
  }

  /* =====================================================================
   *  4 · SSS akordeon — tümünü aç / kapat
   * =================================================================== */
  function accordion() {
    all('[data-accordion-all]').forEach(function (btn) {
      on(btn, 'click', function () {
        var targetId = btn.getAttribute('data-accordion-all');
        var scope = targetId ? document.getElementById(targetId) : document;
        var items = all('details', scope);
        var shouldOpen = items.some(function (d) { return !d.open; });

        items.forEach(function (d) { d.open = shouldOpen; });
        btn.textContent = shouldOpen
          ? (btn.getAttribute('data-label-close') || btn.textContent)
          : (btn.getAttribute('data-label-open') || btn.textContent);
      });
    });

    // <details> açıldığında aria-expanded senkronla
    all('details').forEach(function (d) {
      on(d, 'toggle', function () {
        var s = d.querySelector('summary');
        if (s) { s.setAttribute('aria-expanded', d.open ? 'true' : 'false'); }
      });
      var s = d.querySelector('summary');
      if (s) { s.setAttribute('aria-expanded', d.open ? 'true' : 'false'); }
    });
  }

  /* =====================================================================
   *  5 · Bağlantı kopyala — sessiz başarı (toast yok)
   * =================================================================== */
  function copyLink() {
    all('[data-copy]').forEach(function (btn) {
      var original = btn.getAttribute('data-copy-label') || btn.textContent;
      var okLabel = btn.getAttribute('data-copied-label') || '✓';

      on(btn, 'click', function () {
        var value = btn.getAttribute('data-copy') || window.location.href;
        var done = function () {
          btn.textContent = okLabel;
          window.setTimeout(function () { btn.textContent = original; }, 1800);
        };

        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(value).then(done).catch(function () { fallback(value, done); });
        } else {
          fallback(value, done);
        }
      });
    });

    function fallback(value, done) {
      var ta = document.createElement('textarea');
      ta.value = value;
      ta.setAttribute('readonly', '');
      ta.className = 'honeypot';
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); done(); } catch (e) { /* sessiz */ }
      document.body.removeChild(ta);
    }
  }

  /* =====================================================================
   *  6 · Galeri filtresi — anlık, ağ isteği yok
   * =================================================================== */
  function galleryFilter() {
    var root = one('[data-gallery]');
    if (!root) { return; }

    var items = all('[data-tags]', root);
    var countEl = one('[data-gallery-count]');

    function apply(tag) {
      var shown = 0;
      items.forEach(function (item) {
        var tags = (item.getAttribute('data-tags') || '').split(' ');
        var match = tag === '*' || tags.indexOf(tag) !== -1;
        item.hidden = !match;
        if (match) { shown++; }
      });
      if (countEl) { countEl.textContent = String(shown); }
    }

    all('[data-filter]', root).forEach(function (btn) {
      on(btn, 'click', function () {
        all('[data-filter]', root).forEach(function (b) {
          b.classList.remove('is-active');
          b.setAttribute('aria-pressed', 'false');
        });
        btn.classList.add('is-active');
        btn.setAttribute('aria-pressed', 'true');
        apply(btn.getAttribute('data-filter'));
      });
    });
  }

  /* =====================================================================
   *  7 · Masthead gölge — scroll dinle, transform kullan
   * =================================================================== */
  function headerElevation() {
    var masthead = one('.masthead');
    if (!masthead) { return; }

    var ticking = false;
    function update() {
      masthead.classList.toggle('is-scrolled', window.scrollY > 8);
      ticking = false;
    }
    on(window, 'scroll', function () {
      if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
    }, { passive: true });
    update();
  }

  /* =====================================================================
   *  8 · Arama aç/kapa
   * =================================================================== */
  function searchToggle() {
    var btn = one('[data-search-toggle]');
    var box = one('[data-search-box]');
    if (!btn || !box) { return; }

    function close() {
      box.hidden = true;
      btn.setAttribute('aria-expanded', 'false');
    }

    on(btn, 'click', function () {
      var open = box.hidden;
      box.hidden = !open;
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) {
        var input = one('input', box);
        if (input) { input.focus(); }
      }
    });

    on(document, 'keydown', function (e) {
      if (e.key === 'Escape' && !box.hidden) { close(); }
    });

    on(document, 'click', function (e) {
      if (!box.hidden && !box.contains(e.target) && !btn.contains(e.target)) { close(); }
    });
  }

  /* =====================================================================
   *  9 · Dil tercihini çerezde tazele
   * =================================================================== */
  function langPersist() {
    all('[data-lang]').forEach(function (link) {
      on(link, 'click', function () {
        try {
          document.cookie = 'tapsinnet_lang=' + link.getAttribute('data-lang') +
            ';path=/;max-age=31536000;samesite=Lax';
        } catch (e) { /* çerez yoksa sorun değil */ }
      });
    });
  }

  /* =====================================================================
   *  10 · Dış bağlantılarda güvenlik
   * =================================================================== */
  function externalLinks() {
    all('a[href^="http"]').forEach(function (a) {
      if (a.hostname && a.hostname !== window.location.hostname) {
        a.setAttribute('rel', 'noopener noreferrer');
        a.setAttribute('target', '_blank');
      }
    });
  }

  /* =====================================================================
   *  11 · Görsel lazy-load yedeği (IntersectionObserver yoksa)
   * =================================================================== */
  function lazyFallback() {
    if ('loading' in HTMLImageElement.prototype || !('IntersectionObserver' in window)) { return; }
    var imgs = all('img[loading="lazy"]');
    if (!imgs.length) { return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        var img = entry.target;
        if (img.getAttribute('data-src')) { img.setAttribute('src', img.getAttribute('data-src')); }
        io.unobserve(img);
      });
    }, { rootMargin: '200px' });
    imgs.forEach(function (i) { io.observe(i); });
  }

  /* =====================================================================
   *  11 · Form gönderimi — çift gönderimi engelle
   * =================================================================== */
  function submitGuard() {
    all('form').forEach(function (form) {
      var btn = one('[data-submit-btn]', form);
      if (!btn || btn.tagName !== 'BUTTON') { return; }

      on(form, 'submit', function () {
        // Tarayıcı yerleşik doğrulaması geçmiyorsa dokunma: kullanıcı
        // hatayı görüp düzeltsin, buton kilitli kalmasın.
        if (form.noValidate === false && !form.checkValidity()) { return; }
        btn.setAttribute('aria-busy', 'true');
        btn.disabled = true;
      });

      // Geri gelindiğinde (bfcache) buton kilitli kalmasın.
      on(window, 'pageshow', function () {
        btn.removeAttribute('aria-busy');
        btn.disabled = false;
      });
    });
  }

  /* =====================================================================
   *  12 · ORYZO slayt rayı — tam ekran bölümlerde klavye ve nokta gezinmesi
   * =================================================================== */
  function editorialRail() {
    var rail = one('[data-slide-rail]');
    if (!rail || !('IntersectionObserver' in window)) { return; }
    var links = all('[data-slide-link]', rail);
    var sections = all('main > section');
    if (!links.length || !sections.length) { return; }

    links.forEach(function (link) {
      on(link, 'click', function (event) {
        var target = document.querySelector(link.getAttribute('href'));
        if (!target) { return; }
        event.preventDefault();
        target.closest('section').scrollIntoView({ behavior: reduceMotion.matches ? 'auto' : 'smooth' });
      });
    });

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting || entry.intersectionRatio < .55) { return; }
        var index = sections.indexOf(entry.target);
        links.forEach(function (link, linkIndex) {
          var active = linkIndex === index;
          link.setAttribute('aria-current', active ? 'true' : 'false');
          link.classList.toggle('is-active', active);
        });
        entry.target.classList.add('is-visible');
      });
    }, { threshold: [.55, .8] });
    sections.forEach(function (section) { observer.observe(section); });
  }

  function testimonialSlider() {
    all('[data-testimonial-slider]').forEach(function (slider) {
      var slides = all('[data-testimonial-slide]', slider), dots = all('[data-testimonial-dot]', slider), current = 0, timer;
      if (slides.length < 2) { return; }
      function show(index) { current = (index + slides.length) % slides.length; slides.forEach(function (s, i) { s.classList.toggle('is-active', i === current); }); dots.forEach(function (d, i) { d.classList.toggle('is-active', i === current); d.setAttribute('aria-pressed', i === current ? 'true' : 'false'); }); }
      function restart() { window.clearInterval(timer); if (!reduceMotion.matches) { timer = window.setInterval(function () { show(current + 1); }, 6500); } }
      dots.forEach(function (dot, i) { on(dot, 'click', function () { show(i); restart(); }); });
      on(slider, 'mouseenter', function () { window.clearInterval(timer); }); on(slider, 'mouseleave', restart); on(slider, 'focusin', function () { window.clearInterval(timer); }); on(slider, 'focusout', restart); restart();
    });
  }

  /* =====================================================================
   *  BAŞLAT
   * =================================================================== */
  var booted = false;

  function init() {
    if (booted) { return; }
    booted = true;

    mobileMenu();
    dropdowns();
    captchaRefresh();
    lightbox();
    videoModal();
    accordion();
    copyLink();
    galleryFilter();
    headerElevation();
    searchToggle();
    langPersist();
    externalLinks();
    lazyFallback();
    submitGuard();
    editorialRail();
    testimonialSlider();
  }

  window.__TSN = { init: init, trapFocus: trapFocus, reduceMotion: reduceMotion };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
