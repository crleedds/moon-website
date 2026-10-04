/* 직원 라운지 · 미니차트 (v6.2 · v6.8) — 없어도 화면은 모두 동작한다. 바로 찾기 · 더 보기 · 칸 늘리기 · 복사 · 담당의 확인 */
(function () {
  'use strict';
  var PAGE = 60;

  function norm(s) { return (s || '').toLowerCase().replace(/\s+/g, ''); }

  /* 환자 목록 — 차트번호 · 이름 · 초성으로 바로 걸러내기 */
  var list = document.getElementById('mc-list');
  var q = document.getElementById('mc-q');
  if (list) {
    var items = Array.prototype.slice.call(list.children);
    var moreWrap = document.querySelector('.mc-more-wrap');
    var moreBtn = document.querySelector('.mc-more');
    var nohit = document.querySelector('.mc-nohit');
    var shown = PAGE;
    var serverQ = q ? q.value.trim() : '';

    var apply = function () {
      var v = q ? norm(q.value) : '';
      /* 서버에서 이미 「내용까지 찾기」로 거른 목록이면 같은 검색어일 때는 그대로 둔다 */
      if (serverQ && v === norm(serverQ)) { v = ''; }
      var n = 0, hits = 0;
      items.forEach(function (li) {
        var ok = !v || li.getAttribute('data-k').indexOf(v) !== -1;
        if (ok) { hits++; }
        var show = ok && (v ? hits <= 300 : n < shown);
        if (ok) { n++; }
        li.classList.toggle('is-hidden', !show);
      });
      if (moreWrap) { moreWrap.hidden = !!v || items.length <= shown; }
      if (nohit) { nohit.hidden = !v || hits > 0; }
    };
    if (q) {
      q.addEventListener('input', apply);
      /* 하나만 남았을 때 Enter → 바로 열기 (내용까지 찾기는 버튼으로) */
      q.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' || !norm(q.value) || norm(q.value) === norm(serverQ)) { return; }
        var vis = items.filter(function (li) { return !li.classList.contains('is-hidden'); });
        if (vis.length === 1) { e.preventDefault(); location.href = vis[0].querySelector('a').href; }
      });
    }
    if (moreBtn) { moreBtn.addEventListener('click', function () { shown += 100; apply(); }); }
    apply();
  }

  /* 「/」 키 → 찾기 칸 */
  document.addEventListener('keydown', function (e) {
    if (e.key === '/' && q && document.activeElement !== q && !/INPUT|TEXTAREA|SELECT/.test((document.activeElement || {}).tagName)) {
      e.preventDefault(); q.focus(); q.select();
    }
  });

  /* 글 칸은 내용만큼 늘어난다 */
  var grow = function (t) { t.style.height = 'auto'; t.style.height = (t.scrollHeight + 4) + 'px'; };
  document.querySelectorAll('textarea[data-grow], .mc-add__text').forEach(function (t) {
    if (t.hasAttribute('data-grow')) { grow(t); }
    t.addEventListener('input', function () { grow(t); });
  });
  /* 한 줄 추가 칸: Ctrl/⌘+Enter 로 바로 추가 */
  document.querySelectorAll('.mc-add__text').forEach(function (t) {
    t.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey) && t.value.trim()) { e.preventDefault(); t.form.submit(); }
    });
  });

  /* 차트번호 복사 */
  document.querySelectorAll('.mc-copy').forEach(function (b) {
    b.addEventListener('click', function () {
      var v = b.getAttribute('data-copy');
      var done = function () { b.classList.add('is-copied'); setTimeout(function () { b.classList.remove('is-copied'); }, 1500); };
      if (navigator.clipboard) { navigator.clipboard.writeText(v).then(done, function () {}); }
      else { var i = document.createElement('input'); i.value = v; document.body.appendChild(i); i.select(); try { document.execCommand('copy'); done(); } catch (er) {} i.remove(); }
    });
  });

  /* 저장 전에 나가려 하면 묻기 */
  var form = document.querySelector('.mc-form');
  if (form) {
    var dirty = false;
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  }

  /* v6.8 · 담당의 — 「＋ 담당의 추가」로 과 · 담당의 줄, ✕ 로 빼기. 저장 전 한 분 이상 확인 */
  var drBox = document.querySelector('[data-mc-dr]');
  if (drBox) {
    var drList = drBox.querySelector('[data-mc-dr-list]');
    var drTpl = drBox.querySelector('template[data-mc-dr-tpl]');
    var addBtn = drBox.querySelector('[data-mc-dr-add]');
    if (addBtn && drList && drTpl) {
      addBtn.addEventListener('click', function () {
        drList.appendChild(drTpl.content.cloneNode(true));
        var sels = drList.querySelectorAll('.mc-dr__pair:last-child select');
        if (sels[0]) { sels[0].focus(); }
      });
      drList.addEventListener('click', function (e) {
        var b = e.target.closest('[data-mc-dr-rm]');
        if (b) { b.closest('.mc-dr__pair').remove(); }
      });
    }
    var drForm = drBox.closest('form');
    if (drForm) {
      drForm.addEventListener('submit', function (e) {
        var any = Array.prototype.some.call(drBox.querySelectorAll('select[name="dr_main"], select[name="dr_doc[]"], textarea[name="dr_extra"]'), function (el) { return el.value.trim() !== ''; });
        if (!any) {
          e.preventDefault(); e.stopImmediatePropagation();
          alert('담당의를 한 분 이상 골라 주세요.');
          var sel = drBox.querySelector('select[name="dr_main"]'); if (sel) { sel.focus(); }
        }
      }, true);
    }
  }

  /* v6.8 · 「해당없음」 체크하면 그 칸은 잠근다 */
  document.querySelectorAll('[data-na-for]').forEach(function (c) {
    var t = document.getElementById(c.getAttribute('data-na-for'));
    if (!t) { return; }
    var sync = function () { t.disabled = c.checked; if (!c.checked) { t.focus(); } };
    c.addEventListener('change', sync);
  });
})();
