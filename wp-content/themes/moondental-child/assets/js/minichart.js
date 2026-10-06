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

/* v8.1 · 새 환자 — 차트번호만 넣으면 덴트웹에서 성명 · 지역 · 담당의를 채우고 연락처 · 주소 · 성별 · 나이를 보여 준다.
   아직 받아 둔 게 없으면 병원 PC 가 1분 안에 찾아 온다 (5초마다 다시 물음). 번호를 바꾸면 자동으로 채운 값(손대지 않은 것)만 지운다. */
(function () {
  'use strict';
  var form = document.querySelector('form.mc-form[data-dw]');
  if (!form) return;
  var chart = form.querySelector('[name=chart_no]');
  if (!chart) return;
  var hint = document.createElement('p');
  hint.className = 'mc-dw-hint'; hint.hidden = true;
  chart.parentNode.appendChild(hint);
  var prev = form.querySelector('.mc-dw-prev');
  var last = '', auto = {}, timer = null, tries = 0;
  function esc(s) { return String(s || '').replace(/[&<>"]/g, function (m) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[m]; }); }
  function undo() {
    Object.keys(auto).forEach(function (n) {
      var el = form.querySelector('[name=' + n + ']');
      if (el && el.value === auto[n]) { el.value = ''; el.dispatchEvent(new Event('input', { bubbles: true })); }
    });
    auto = {};
    if (prev) { prev.hidden = true; prev.innerHTML = ''; }
  }
  function fill(name, val) {
    var el = form.querySelector('[name=' + name + ']');
    if (!el || !val || el.value.trim() !== '') return false;
    el.value = val; auto[name] = el.value; el.dispatchEvent(new Event('input', { bubbles: true }));
    return true;
  }
  function show(d) {
    var done = [];
    if (fill('pname', d.name)) done.push('성명');
    if (fill('addr', d.region || d.addr)) done.push('지역');
    var sel = form.querySelector('[name=dr_main]');
    if (sel && !sel.value && d.doctor) {
      Array.prototype.some.call(sel.options, function (o) { if (o.value && (o.value === d.doctor || o.textContent.indexOf(d.doctor) > -1)) { sel.value = o.value; auto.dr_main = o.value; done.push('담당의'); return true; } return false; });
    }
    hint.textContent = '덴트웹: ' + d.name + (done.length ? ' — ' + done.join(' · ') + ' 채움' : '');
    hint.hidden = false;
    if (prev) {
      var age = (d.age !== '' && d.age != null) ? ' ' + d.age + '세' : '';
      var rows = [['성명', d.name], ['성별 · 나이', (d.sex === 'M' ? '남' : d.sex === 'F' ? '여' : '') + age], ['연락처', d.phone], ['주소', d.addr], ['담당의', d.doctor], ['첫 등록', d.first], ['최근 내원', d.last]];
      prev.innerHTML = '<b class="mc-dw-prev__h">덴트웹에서 가져온 정보</b><dl>' + rows.filter(function (r) { return r[1] && String(r[1]).trim(); }).map(function (r) { return '<div><dt>' + r[0] + '</dt><dd>' + esc(r[1]) + '</dd></div>'; }).join('') + '</dl>';
      prev.hidden = false;
    }
  }
  /* v8.4 · 같은 차트번호가 이미 있으면 저장 전에 크게 알림 */
  var dupBox = document.createElement('div');
  dupBox.className = 'mds-notice mds-notice--warn mc-dup'; dupBox.hidden = true;
  chart.parentNode.appendChild(dupBox);
  function dupShow(x) {
    hint.hidden = true;
    dupBox.innerHTML = '이미 미니차트에 있는 환자입니다 — <b>' + esc(x.chart) + ' ' + esc(x.name) + '</b> <a class="mds-btn" href="' + esc(x.url) + '">그 차트 열기</a>';
    dupBox.hidden = false;
  }
  function ask(c) {
    var u = form.getAttribute('data-dw');
    fetch(u + (u.indexOf('?') > -1 ? '&' : '?') + 'md_mc_dw=' + encodeURIComponent(c), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (chart.value.trim() !== c) return;
        if (d && d.dup) { dupShow(d.dup); return; }
        if (d && d.ok) { show(d); return; }
        if (d && d.pending && tries < 24) {
          tries++; hint.textContent = '덴트웹에서 찾는 중… (병원 PC 가 1분 안에 가져옵니다)'; hint.hidden = false;
          timer = setTimeout(function () { ask(c); }, 5000); return;
        }
        hint.textContent = '덴트웹에서 이 차트번호를 찾지 못했습니다.'; hint.hidden = false;
      })
      .catch(function () {});
  }
  function look() {
    var c = chart.value.trim();
    if (c === last) return;
    last = c; undo(); clearTimeout(timer); tries = 0; hint.hidden = true; hint.textContent = ''; dupBox.hidden = true;
    if (c) ask(c);
  }
  chart.addEventListener('change', look);
  chart.addEventListener('blur', look);
  if (chart.value.trim()) look();
})();

/* v8.1 · 병력 칩 — 누르면 넣고 다시 누르면 뺀다 · 「해당없음」 체크는 풀림 */
(function () {
  'use strict';
  Array.prototype.forEach.call(document.querySelectorAll('[data-mc-chips]'), function (box) {
    var name = box.getAttribute('data-mc-chips');
    var ta = document.querySelector('form.mc-form [name=' + name + ']');
    if (!ta) return;
    function parts() { return ta.value.split(/\s*[,，\n]\s*/).map(function (x) { return x.trim(); }).filter(Boolean); }
    function sync() {
      var ps = parts();
      Array.prototype.forEach.call(box.querySelectorAll('[data-chip]'), function (b) {
        var c = b.getAttribute('data-chip');
        b.classList.toggle('is-on', ps.some(function (p) { return p.indexOf(c) === 0; }));
      });
    }
    box.addEventListener('click', function (e) {
      var b = e.target.closest('[data-chip]');
      if (!b) return;
      var c = b.getAttribute('data-chip'), ps = parts();
      var i = -1;
      ps.forEach(function (p, k) { if (i < 0 && p.indexOf(c) === 0) i = k; });
      var pre = c.slice(-1) === ':';
      if (i > -1) ps.splice(i, 1); else ps.push(pre ? c + ' ' : c);
      ps = ps.filter(function (p) { return p !== '해당없음' && p !== '.'; });
      var na = document.querySelector('[name="na[' + name + ']"]');
      if (na && na.checked) { na.click(); }
      ta.value = ps.join(', ') + (pre && i < 0 ? ' ' : '');
      ta.value = ta.value.replace(/: {2}$/, ': ');
      ta.dispatchEvent(new Event('input', { bubbles: true }));
      sync();
      if (pre && i < 0) { ta.focus(); var n = ta.value.length; try { ta.setSelectionRange(n, n); } catch (x) {} }
    });
    ta.addEventListener('input', sync);
    sync();
  });
})();
