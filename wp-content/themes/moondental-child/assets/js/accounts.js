/* v5.8 · 직원 라운지 계정 — 전화번호 자동 하이픈 · 아이디 소문자 */
(function () {
  'use strict';
  function fmt(v) {
    var d = String(v || '').replace(/\D/g, '').slice(0, 11);
    if (d.indexOf('02') === 0) {
      if (d.length > 9) return d.slice(0, 2) + '-' + d.slice(2, 6) + '-' + d.slice(6, 10);
      if (d.length > 5) return d.slice(0, 2) + '-' + d.slice(2, 5) + '-' + d.slice(5);
      if (d.length > 2) return d.slice(0, 2) + '-' + d.slice(2);
      return d;
    }
    if (d.length > 10) return d.slice(0, 3) + '-' + d.slice(3, 7) + '-' + d.slice(7);
    if (d.length > 6) return d.slice(0, 3) + '-' + d.slice(3, 6) + '-' + d.slice(6);
    if (d.length > 3) return d.slice(0, 3) + '-' + d.slice(3);
    return d;
  }
  document.addEventListener('input', function (e) {
    var t = e.target;
    if (t.matches && t.matches('[data-mda-phone]')) {
      var end = t.selectionEnd === t.value.length;
      t.value = fmt(t.value);
      if (end) t.setSelectionRange(t.value.length, t.value.length);
    }
    if (t.name === 'login' && t.closest && t.closest('.mda-form')) {
      var lo = t.value.toLowerCase().replace(/\s/g, '');
      if (lo !== t.value) t.value = lo;
    }
  });
  /* 되돌리기 어려운 처리는 한 번 묻는다 (문구는 data-mda-confirm) */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f && f.dataset && f.dataset.mdaConfirm && !window.confirm(f.dataset.mdaConfirm)) e.preventDefault();
  }, true);
  function openHash() {
    var id = (location.hash || '').slice(1);
    var el = id && document.getElementById(id);
    if (!el) return;
    var d = el.tagName === 'SECTION' ? el.querySelector('details') : el.closest('details');
    for (; d; d = d.parentElement && d.parentElement.closest('details')) d.open = true;
    el.scrollIntoView({ block: 'start' });
  }
  openHash();
  window.addEventListener('hashchange', openHash);
  /* 오류가 있으면 첫 오류 칸으로 */
  var firstErr = document.querySelector('.mda-err');
  if (firstErr) {
    var f = firstErr.parentNode.querySelector('input, select');
    if (f) { f.scrollIntoView({ block: 'center' }); try { f.focus({ preventScroll: true }); } catch (x) { f.focus(); } }
  }
})();

/* v9.54 · 직원 명단 — 휴대폰에서 한 사람을 누르면 고치기 칸이 열린다. 저장 뒤 돌아온 사람(#s123)은 열린 채로 */
(function () {
  'use strict';
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('.mdst-sum');
    if (!b) return;
    var box = b.parentNode, on = !box.classList.contains('is-open');
    box.classList.toggle('is-open', on);
    b.setAttribute('aria-expanded', on ? 'true' : 'false');
  });
  var h = location.hash && document.getElementById(location.hash.slice(1));
  var item = h && h.closest && h.closest('.mdst-item, .mdst-new');
  if (item) { item.classList.add('is-open'); var s = item.querySelector('.mdst-sum'); if (s) s.setAttribute('aria-expanded', 'true'); }
  /* 입력 오류가 난 칸이 접힌 사람 안에 있으면 연다 */
  Array.prototype.forEach.call(document.querySelectorAll('.mdst-item, .mdst-new'), function (it) {
    it.addEventListener('invalid', function () { it.classList.add('is-open'); }, true);
  });
})();
