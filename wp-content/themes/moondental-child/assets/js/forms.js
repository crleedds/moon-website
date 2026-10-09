/* 직원 라운지 · 양식 (v7.6) — 찾기 · 분류 칩(다시 불러오지 않음) · 올리기/고치기 열고 닫기 · 지우기 확인 */
(function () {
  'use strict';
  var root = document.querySelector('.mdf');
  if (!root) return;
  var q = root.querySelector('[data-mdf-q]'), cat = '';
  var on = root.querySelector('.mdf-chips .is-on');
  if (on) cat = on.getAttribute('data-mdf-cat') || '';

  function apply() {
    var s = q ? q.value.trim().toLowerCase() : '', seen = {}, any = false;
    Array.prototype.forEach.call(root.querySelectorAll('[data-mdf-item]'), function (el) {
      var ok = (!cat || el.getAttribute('data-cat') === cat) && (!s || el.getAttribute('data-text').indexOf(s) > -1);
      el.hidden = !ok;
      if (ok) { seen[el.getAttribute('data-cat')] = 1; any = true; }
    });
    Array.prototype.forEach.call(root.querySelectorAll('[data-mdf-head]'), function (h) { h.hidden = !seen[h.getAttribute('data-mdf-head')]; });
    var none = root.querySelector('[data-mdf-none]');
    if (none) none.hidden = any;
  }
  if (q) q.addEventListener('input', apply);

  root.addEventListener('click', function (e) {
    var c = e.target.closest('[data-mdf-cat]');
    if (c && !e.ctrlKey && !e.metaKey) {
      e.preventDefault();
      cat = c.getAttribute('data-mdf-cat') || '';
      Array.prototype.forEach.call(root.querySelectorAll('[data-mdf-cat]'), function (a) { a.classList.toggle('is-on', a === c); });
      try { history.replaceState(null, '', c.href); } catch (x) {}
      apply();
      return;
    }
    var o = e.target.closest('[data-mdf-open]');
    if (o) {
      var box = document.getElementById(o.getAttribute('data-mdf-open'));
      if (box) { box.hidden = !box.hidden; if (!box.hidden) { var f = box.querySelector('input:not([type=hidden])'); if (f && f.type !== 'file') f.focus(); } }
      return;
    }
    var x = e.target.closest('[data-mdf-close]');
    if (x) { var b = document.getElementById(x.getAttribute('data-mdf-close')); if (b) b.hidden = true; }
  });

  root.addEventListener('submit', function (e) {
    var f = e.target.closest('[data-mdf-confirm]');
    if (f && !window.confirm(f.getAttribute('data-mdf-confirm'))) { e.preventDefault(); return; }
    var btn = e.target.querySelector('button:not([type=button])');
    if (btn) { btn.disabled = true; btn.textContent = '저장 중…'; }
  });

  /* 고른 파일 이름 보여 주기 · 여러 개면 「이름」 칸은 쓰지 않음 */
  var up = root.querySelector('#mdf-up input[type=file]');
  if (up) up.addEventListener('change', function () {
    var names = Array.prototype.map.call(up.files, function (f) { return f.name; });
    var out = root.querySelector('[data-mdf-names]');
    if (out) out.textContent = names.length ? names.length + '개: ' + names.join(', ') : '';
    var t = root.querySelector('#mdf-up [name=f_title]');
    if (t) { t.disabled = names.length > 1; if (names.length > 1) t.value = ''; }
  });
})();
