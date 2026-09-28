/* Hallmark · TapsinNet · yönetim paneli etkileşim katmanı
 * ---------------------------------------------------------------------------
 *  Sıfır bağımlılık, `defer` ile yüklenir. CSP uyumlu: innerHTML ile veri
 *  yazılmaz, eval yok, style özniteliği ile dinamik değer yazılmaz.
 * ------------------------------------------------------------------------- */
(function (window, document) {
  'use strict';

  function on(el, evt, handler) { if (el) { el.addEventListener(evt, handler); } }
  function all(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }
  function one(sel, ctx) { return (ctx || document).querySelector(sel); }
  function debounce(fn, wait) {
    var t;
    return function () {
      var args = arguments, ctx = this;
      window.clearTimeout(t);
      t = window.setTimeout(function () { fn.apply(ctx, args); }, wait);
    };
  }

  /* ═══════════════════════════════════════════════════════════════
   *  1 · Silme onayı
   * ═══════════════════════════════════════════════════════════ */
  function confirmAction() {
    on(document, 'submit', function (e) {
      var form = e.target;
      if (!(form instanceof HTMLFormElement)) { return; }
      var msg = form.getAttribute('data-confirm');
      if (msg && !window.confirm(msg)) {
        e.preventDefault();
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════════
   *  2 · Toplu seçim
   * ═══════════════════════════════════════════════════════════ */
  function bulkSelect() {
    var allBox = one('[data-bulk-all]');
    if (!allBox) { return; }

    var form = allBox.closest('form');
    var boxes = all('[data-bulk-item]');
    var count = one('[data-bulk-count]');
    var submit = one('[data-bulk-submit]');

    function sync() {
      var n = boxes.filter(function (b) { return b.checked; }).length;
      if (count) { count.textContent = n + ' ' + (count.getAttribute('data-label') || 'seçili'); }
      if (submit) { submit.disabled = n === 0; }
      allBox.checked = n > 0 && n === boxes.length;
      allBox.indeterminate = n > 0 && n < boxes.length;
    }

    on(allBox, 'change', function () {
      boxes.forEach(function (b) { b.checked = allBox.checked; });
      sync();
    });
    boxes.forEach(function (b) { on(b, 'change', sync); });
    on(form, 'submit', function (e) {
      if (boxes.some(function (b) { return b.checked; })) { return; }
      e.preventDefault();
      window.alert('Önce en az bir kayıt seçin.');
    });

    sync();
  }

  /* ═══════════════════════════════════════════════════════════════
   *  3 · Slug önerisi
   * ═══════════════════════════════════════════════════════════ */
  function slugPreview() {
    var slugInput = one('[data-slug-from]');
    if (!slugInput) { return; }

    var sourceName = slugInput.getAttribute('data-slug-from');
    var source = document.querySelector('[name="' + sourceName + '"]');
    if (!source) { return; }

    var hint = one('[data-slug-preview]');
    var TR = { 'ç': 'c', 'ğ': 'g', 'ı': 'i', 'İ': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u', 'Ç': 'c', 'Ğ': 'g', 'Ö': 'o', 'Ş': 's', 'Ü': 'u' };

    function slugify(v) {
      var s = String(v || '');
      for (var k in TR) { if (TR.hasOwnProperty(k)) { s = s.split(k).join(TR[k]); } }
      s = s.toLowerCase()
           .replace(/[^a-z0-9]+/g, '-')
           .replace(/^-+|-+$/g, '')
           .replace(/-{2,}/g, '-');
      return s;
    }

    var update = function () {
      if (slugInput.value.trim() !== '') { if (hint) { hint.hidden = true; } return; }
      var s = slugify(source.value);
      if (!s) { if (hint) { hint.hidden = true; } return; }
      slugInput.placeholder = s;
      if (hint) {
        hint.hidden = false;
        hint.textContent = '/' + s;
      }
    };

    on(source, 'input', update);
    on(slugInput, 'input', function () { if (hint) { hint.hidden = true; } });
    update();
  }

  /* ═══════════════════════════════════════════════════════════════
   *  4 · Görsel önizleme (object URL — ağ isteği yok)
   * ═══════════════════════════════════════════════════════════ */
  function imagePreview() {
    all('[data-preview]').forEach(function (input) {
      var current = null;
      on(input, 'change', function () {
        var file = input.files && input.files[0];
        var wrap = input.parentNode;
        if (!wrap) { return; }

        var prev = wrap.querySelector('[data-preview-box]');
        if (prev && prev.parentNode) { prev.parentNode.removeChild(prev); }
        if (!file) { return; }

        if (current) { window.URL.revokeObjectURL(current); }

        var img = document.createElement('img');
        img.alt = '';
        img.loading = 'lazy';
        img.className = 'thumb thumb--48';
        img.setAttribute('data-preview-box', '');

        current = window.URL.createObjectURL(file);
        img.src = current;

        var name = document.createElement('p');
        name.className = 'file-preview__name';
        name.setAttribute('data-preview-box', '');
        name.textContent = file.name + ' · ' + Math.round(file.size / 1024) + ' KB';

        wrap.insertBefore(img, input);
        wrap.insertBefore(name, input);
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════════
   *  5 · Parola göster / gizle
   * ═══════════════════════════════════════════════════════════ */
  function passwordToggle() {
    all('[data-password-toggle]').forEach(function (btn) {
      on(btn, 'click', function () {
        var field = document.getElementById(btn.getAttribute('data-target')) || btn.parentNode.querySelector('input');
        if (!field) { return; }
        var show = field.type === 'password';
        field.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show
          ? (btn.getAttribute('data-label-hide') || 'Gizle')
          : (btn.getAttribute('data-label-show') || 'Göster'));
        btn.textContent = show ? (btn.getAttribute('data-label-hide') || 'GİZLE') : (btn.getAttribute('data-label-show') || 'GÖSTER');
        field.focus();
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════════
   *  6 · Parola gücü ölçer
   * ═══════════════════════════════════════════════════════════ */
  function passwordMeter() {
    var input = one('[data-password-meter]');
    if (!input) { return; }

    var meter = document.getElementById('pwMeter');
    var label = document.getElementById('pwLabel');
    if (!meter) { return; }

    var bars = all('.password-meter__bar', meter);
    var lower = /[a-zçğıöşü]/, upper = /[A-ZÇĞİÖŞÜ]/, digit = /\d/, sym = /[^\p{L}\p{N}]/u;
    var common = /^(?:password|12345678|qwerty|111111|admin|letmein|iloveyou|parola|sifre)$/i;

    function score(v) {
      var n = 0;
      if (v.length >= 12) { n++; }
      if (v.length >= 16) { n++; }
      if (lower.test(v)) { n++; }
      if (upper.test(v)) { n++; }
      if (digit.test(v)) { n++; }
      if (sym.test(v)) { n++; }
      if (common.test(v)) { n = 0; }
      return Math.max(0, Math.min(5, n));
    }

    on(input, 'input', function () {
      var v = input.value;
      var s = v === '' ? -1 : score(v);
      bars.forEach(function (b, i) {
        b.className = 'password-meter__bar' + (i < s ? ' is-on-' + s : '');
      });
      if (label) {
        label.textContent = s < 0 ? '' : (s <= 1 ? 'Çok zayıf' : s === 2 ? 'Zayıf' : s === 3 ? 'Orta' : s === 4 ? 'Güçlü' : 'Çok güçlü');
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════════
   *  7 · Bildirimleri otomatik kapat
   * ═══════════════════════════════════════════════════════════ */
  function autoDismiss() {
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    all('.flash').forEach(function (el) {
      window.setTimeout(function () {
        if (reduce) {
          el.style.opacity = '0';
          window.setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 150);
          return;
        }
        el.style.transition = 'opacity 220ms ease-out, transform 220ms ease-out';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-4px)';
        window.setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 240);
      }, 6000);
    });
  }

  /* ═══════════════════════════════════════════════════════════════
   *  8 · Kenar çubuğu (mobil)
   * ═══════════════════════════════════════════════════════════ */
  function sidebarToggle() {
    var btn = one('[data-sidebar-toggle]');
    var sidebar = one('.admin__sidebar');
    if (!btn || !sidebar) { return; }

    function close() {
      sidebar.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');
    }

    on(btn, 'click', function () {
      var open = !sidebar.classList.contains('is-open');
      sidebar.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    on(document, 'click', function (e) {
      if (window.innerWidth > 1024) { return; }
      if (sidebar.classList.contains('is-open') && !sidebar.contains(e.target) && !btn.contains(e.target)) {
        close();
      }
    });

    on(document, 'keydown', function (e) { if (e.key === 'Escape') { close(); } });
    on(window, 'resize', debounce(function () { if (window.innerWidth > 1024) { close(); } }, 150));
  }

  /* ═══════════════════════════════════════════════════════════════
   *  9 · Arama kutusu — otomatik gönderim
   * ═══════════════════════════════════════════════════════════ */
  function qSave() {
    all('[data-search]').forEach(function (input) {
      var form = input.form || input.closest('form');
      if (!form) { return; }
      on(input, 'input', debounce(function () { form.submit(); }, 500));
    });
  }

  /* ═══════════════════════════════════════════════════════════════
   *  10 · Modül anahtarı — otomatik kaydet
   * ═══════════════════════════════════════════════════════════ */
  function moduleToggle() {
    all('[data-module-toggle]').forEach(function (box) {
      var form = box.closest('form');
      var text = box.parentNode && box.parentNode.querySelector('.module-toggle__text');
      if (!form) { return; }
      on(box, 'change', function () {
        if (text) { text.textContent = box.checked ? 'Aktif' : 'Pasif'; }
        var btn = form.querySelector('button[name="islem"]');
        if (btn) { btn.value = box.checked ? 'aktif' : 'pasif'; btn.click(); }
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════════
   *  11 · Proje galerisi — görsel kaldırma
   * ═══════════════════════════════════════════════════════════ */
  function galleryRemove() {
    var hidden = document.getElementById('galleryValue');
    var grid = document.getElementById('galleryPreview');
    if (!hidden || !grid) { return; }

    all('[data-gallery-remove]', grid).forEach(function (btn) {
      on(btn, 'click', function () {
        var idx = parseInt(btn.getAttribute('data-gallery-remove'), 10);
        var list = [];
        try { list = JSON.parse(hidden.value) || []; } catch (err) { list = []; }
        list.splice(idx, 1);
        hidden.value = JSON.stringify(list);

        var fig = btn.closest('figure');
        if (fig && fig.parentNode) { fig.parentNode.removeChild(fig); }
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════════
   *  12 · Klavye navigasyonu (tab listeleri)
   * ═══════════════════════════════════════════════════════════ */
  function tabKeys() {
    all('[role="tablist"]').forEach(function (list) {
      on(list, 'keydown', function (e) {
        var keys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'];
        if (keys.indexOf(e.key) === -1) { return; }
        var tabs = all('[role="tab"]', list);
        if (tabs.length === 0) { return; }
        var idx = tabs.indexOf(document.activeElement);
        if (idx === -1) { return; }
        e.preventDefault();

        if (e.key === 'Home') { idx = 0; }
        else if (e.key === 'End') { idx = tabs.length - 1; }
        else if (e.key === 'ArrowRight') { idx = (idx + 1) % tabs.length; }
        else { idx = (idx - 1 + tabs.length) % tabs.length; }
        tabs[idx].focus();
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════════
   *  BAŞLAT
   * ═══════════════════════════════════════════════════════════ */
  var booted = false;

  function init() {
    if (booted) { return; }
    booted = true;

    confirmAction();
    bulkSelect();
    slugPreview();
    imagePreview();
    passwordToggle();
    passwordMeter();
    autoDismiss();
    sidebarToggle();
    qSave();
    moduleToggle();
    galleryRemove();
    tabKeys();
  }

  window.__ADMIN = { init: init, slugify: function (v) { return v; } };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
