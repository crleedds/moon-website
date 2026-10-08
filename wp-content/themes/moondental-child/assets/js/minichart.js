/* 직원 라운지 · 미니차트 (v6.2 · v6.8) — 없어도 화면은 모두 동작한다. 바로 찾기 · 더 보기 · 칸 늘리기 · 복사 · 담당의 확인
   v10.3 · 화면마다 붙이는 코드는 window.mcInits 에 넣는다 — 목록 ↔ 차트를 페이지를 다시 불러오지 않고 바꿀 때(맨 아래) 다시 돌린다 */
window.mcInits = window.mcInits || [];
window.mcGo = window.mcGo || function (url) { location.href = url; };
window.mcInits.push(function () {
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
      deepFind(q ? q.value.trim() : '', v);
    };
    /* v9.10 · 글자를 치면 내용(병력 · 치료계획 · 진료기록 · 참고사항 …)에서도 같이 찾아 아래에 붙인다 (원장 지시) */
    var deep = document.getElementById('mc-deep'), deepT = null, deepLast = '', deepSeq = 0;
    var esc = function (t) { return String(t == null ? '' : t).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
    var deepFind = function (raw, v) {
      if (!deep) { return; }
      clearTimeout(deepT);
      var cho = /^[\u3131-\u314E\s]+$/.test(raw);
      if (!v || v.length < 2 || cho || (serverQ && v === norm(serverQ))) { deep.hidden = true; deepLast = ''; return; }
      if (raw === deepLast) { return; }
      deepT = setTimeout(function () {
        var seq = ++deepSeq, u = new URL(location.href);
        u.search = ''; u.searchParams.set('app', 'minichart'); u.searchParams.set('md_mc_find', raw); u.searchParams.set('deep', '1');
        fetch(u.toString(), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (res) {
          if (seq !== deepSeq || !res || !res.ok) { return; }
          deepLast = raw;
          var rows = res.rows || [];
          deep.hidden = !rows.length;
          if (nohit && rows.length) { nohit.hidden = true; }
          deep.querySelector('.mc-deep__h').textContent = '내용에서 찾음 ' + rows.length + '명' + (rows.length >= 30 ? ' (30명까지)' : '');
          deep.querySelector('ul').innerHTML = rows.map(function (r) {
            return '<li><a class="mc-row" href="' + esc(r.url) + '"><span class="mc-row__no">' + esc(r.chart) + '</span><span class="mc-row__name">' + (r.pin ? '<span class="mc-row__pin">📌</span>' : '') + '<span class="mc-row__nm">' + esc(r.name) + '</span></span><span class="mc-row__last mc-row__hit">' + esc(r.hit) + '</span><span class="mc-row__upd">' + esc(r.last) + '</span></a></li>';
          }).join('');
        }).catch(function () {});
      }, 280);
    };
    if (q) {
      q.addEventListener('input', apply);
      /* 하나만 남았을 때 Enter → 바로 열기 (내용까지 찾기는 버튼으로) */
      q.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' || !norm(q.value) || norm(q.value) === norm(serverQ)) { return; }
        var vis = items.filter(function (li) { return !li.classList.contains('is-hidden'); });
        if (vis.length === 1) { e.preventDefault(); window.mcGo(vis[0].querySelector('a').href); }
      });
    }
    if (moreBtn) { moreBtn.addEventListener('click', function () { shown += 100; apply(); }); }
    apply();
  }

  /* 「/」 키 → 찾기 칸 (한 번만 걸고, 그때그때 화면의 찾기 칸) */
  if (!window.mcSlashKey) {
    window.mcSlashKey = true;
    document.addEventListener('keydown', function (e) {
      var qq = document.getElementById('mc-q');
      if (e.key === '/' && qq && document.activeElement !== qq && !/INPUT|TEXTAREA|SELECT/.test((document.activeElement || {}).tagName)) {
        e.preventDefault(); qq.focus(); qq.select();
      }
    });
  }

  /* 글 칸은 내용만큼 늘어난다 */
  var grow = function (t) { t.style.height = 'auto'; t.style.height = (t.scrollHeight + 4) + 'px'; };
  document.querySelectorAll('textarea[data-grow], .mc-add__text').forEach(function (t) {
    if (t.hasAttribute('data-grow')) { grow(t); }
    t.addEventListener('input', function () { grow(t); });
  });
  /* 한 줄 추가 칸: Ctrl/⌘+Enter 로 바로 추가 */
  document.querySelectorAll('.mc-add__text').forEach(function (t) {
    t.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey) && t.value.trim()) { e.preventDefault(); if (t.form.requestSubmit) { t.form.requestSubmit(); } else { t.form.submit(); } }
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
});

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
      var rows = [['성명', d.name], ['성별 · 나이', (d.sex === 'M' ? '남' : d.sex === 'F' ? '여' : '') + age], ['주소', d.addr], ['담당의', d.doctor], ['첫 등록', d.first], ['최근 내원', d.last]];
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

/* v8.1 · 병력 칩 — 누르면 넣고 다시 누르면 뺀다 · 「해당없음」 체크는 풀림
   v9.27 · 문서에 한 번만 거는 방식으로 — 목록에서 차트로 (페이지를 다시 불러오지 않고) 넘어온 뒤에도 칩이 눌리게 (원장 지적) */
(function () {
  'use strict';
  function taFor(box) {
    var name = box.getAttribute('data-mc-chips');
    var own = box.closest('form'), ta = own ? own.querySelector('[name=' + name + ']') : null;
    return ta || document.querySelector('form.mc-form [name=' + name + ']');
  }
  function parts(ta) { return ta.value.split(/\s*[,，\n]\s*/).map(function (x) { return x.trim(); }).filter(Boolean); }
  function sync(box, ta) {
    var ps = parts(ta);
    Array.prototype.forEach.call(box.querySelectorAll('[data-chip]'), function (b) {
      var c = b.getAttribute('data-chip');
      b.classList.toggle('is-on', ps.some(function (p) { return p.indexOf(c) === 0; }));
    });
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-mc-chips] [data-chip]');
    if (!b) return;
    var box = b.closest('[data-mc-chips]'), ta = taFor(box);
    if (!ta) return;
    e.preventDefault();
    var name = box.getAttribute('data-mc-chips'), c = b.getAttribute('data-chip'), ps = parts(ta);
    var i = -1;
    ps.forEach(function (p, k) { if (i < 0 && p.indexOf(c) === 0) i = k; });
    var pre = c.slice(-1) === ':';
    if (i > -1) ps.splice(i, 1); else ps.push(pre ? c + ' ' : c);
    ps = ps.filter(function (p) { return p !== '해당없음' && p !== '.'; });
    var na = (box.closest('form') || document).querySelector('[name="na[' + name + ']"]');
    if (na && na.checked) { na.click(); }
    ta.disabled = false;
    ta.value = ps.join(', ') + (pre && i < 0 ? ' ' : '');
    ta.value = ta.value.replace(/: {2}$/, ': ');
    ta.dispatchEvent(new Event('input', { bubbles: true }));
    sync(box, ta);
    if (pre && i < 0) { ta.focus(); var n = ta.value.length; try { ta.setSelectionRange(n, n); } catch (x) {} }
  });
  document.addEventListener('input', function (e) {
    var t = e.target;
    if (!t || !t.name) return;
    Array.prototype.forEach.call(document.querySelectorAll('[data-mc-chips="' + t.name + '"]'), function (box) { if (taFor(box) === t) sync(box, t); });
  });
  function syncAll() { Array.prototype.forEach.call(document.querySelectorAll('[data-mc-chips]'), function (box) { var ta = taFor(box); if (ta) sync(box, ta); }); }
  syncAll();
  window.mcInits.push(syncAll);
})();

/* v8.6 · 치료계획 — 차트 화면에서 바로 고치고, 바뀌면 「저장 · 되돌리기」가 나타남 */
window.mcInits.push(function () {
  'use strict';
  var ta = document.querySelector('textarea[data-mc-plan]');
  if (!ta) return;
  var bar = ta.form.querySelector('.mc-plan__bar'), orig = ta.value;
  function grow() { ta.style.height = 'auto'; ta.style.height = Math.max(ta.scrollHeight, 36) + 'px'; }
  ta.addEventListener('input', function () { bar.hidden = ta.value === orig; grow(); });
  ta.form.querySelector('[data-mc-plan-undo]').addEventListener('click', function () { ta.value = orig; bar.hidden = true; grow(); });
  ta.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); if (ta.form.requestSubmit) ta.form.requestSubmit(); else ta.form.submit(); } });
  /* v10.3 · 지금 화면의 치료계획만 본다 (다른 차트로 바꾼 뒤 남은 옛 칸은 무시) */
  window.addEventListener('beforeunload', function (e) { if (document.contains(ta) && ta.value !== orig && !ta.form.dataset.sending) { e.preventDefault(); e.returnValue = ''; } });
  ta.mcDirty = function () { return ta.value !== orig && !ta.form.dataset.sending; };
  ta.form.addEventListener('mc:plan-saved', function () { orig = ta.value; bar.hidden = true; });
  grow();
});

/* v8.7 · 덴트웹 진료기록 한 줄 고치기 — ✎ 누르면 그 자리에 고치는 칸 */
document.addEventListener('click', function (e) {
  var b = e.target.closest && e.target.closest('[data-mc-dwedit]');
  if (b) {
    var li = b.closest('.mc-tl__ownw') || b.closest('li'), f = li && li.querySelector('.mc-tl__edit'); /* v9.22 · 직접 적은 줄은 제 칸 안의 폼 */
    if (f) { f.hidden = false; var t = f.querySelector('textarea'); t.focus(); t.setSelectionRange(t.value.length, t.value.length); }
    return;
  }
  var c = e.target.closest && e.target.closest('[data-mc-dwedit-cancel]');
  if (c) { c.closest('.mc-tl__edit').hidden = true; }
});

/* v9.2 · 빠른 저장 — 진료기록 지우기 · 고치기 · 다시 보이기 · 줄 추가 · 치료계획을 페이지를 다시 불러오지 않고 바로 반영.
   화면은 먼저 바꾸고(지우기는 즉시 사라짐) 서버 대답이 오면 그 내용으로 맞춘다. 실패하면 예전처럼 보통 저장으로. */
(function () {
  'use strict';
  if (!window.fetch || !window.FormData) return;
  function box(name) {
    if (name === 'body') return document.querySelector('.mc-note__body'); /* 팀 노트 본문 */
    var s = document.getElementById('f-' + name); if (s) return s.querySelector('.mc-block__b');
    var f = document.querySelector('form.mc-edit[data-mc-field="' + name + '"]'); return f && f.querySelector('.mc-edit__view'); /* v9.24 · 머리의 호칭처럼 id 없는 칸 */
  }
  function toast(msg, bad) {
    var t = document.querySelector('.mc-toast');
    if (!t) { t = document.createElement('div'); t.className = 'mc-toast'; t.setAttribute('role', 'status'); document.body.appendChild(t); }
    t.textContent = msg; t.classList.toggle('is-bad', !!bad); t.classList.add('is-on');
    clearTimeout(t._h); t._h = setTimeout(function () { t.classList.remove('is-on'); }, bad ? 4000 : 1600);
  }
  function syncRev(rev) { if (!rev) return; Array.prototype.forEach.call(document.querySelectorAll('input[name=rev]'), function (i) { i.value = rev; }); }
  function send(form, extra) {
    var fd = new FormData(form);
    fd.append('md_fast', '1');
    if (extra) { for (var k in extra) fd.append(k, extra[k]); }
    return fetch(form.action || location.href, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.json(); });
  }
  function apply(res) {
    if (res.html != null && res.target) {
      var b = box(res.target);
      if (b) {
        var more = b.querySelector('.mc-tl__more'), hid = b.querySelector('.mc-tl__hidden');
        var openMore = more && more.open, openHid = hid && hid.open;
        b.innerHTML = res.html;
        if (openMore && b.querySelector('.mc-tl__more')) b.querySelector('.mc-tl__more').open = true;
        if (openHid && b.querySelector('.mc-tl__hidden')) b.querySelector('.mc-tl__hidden').open = true;
      }
    }
    syncRev(res.rev);
  }
  /* v9.10 · 서버가 저장했는지 알 수 없을 때는 폼을 다시 보내지 않는다(두 번 저장 방지) — 화면을 새로 불러와 실제 상태를 보여 준다 */
  function recover(msg) { toast(msg || '저장 결과를 확인합니다…', true); setTimeout(function () { location.reload(); }, 900); }
  var busy = false;
  /* v9.25 · 담당의 칸의 「덴트웹 ○○○」 ✕ — 단추만 있고(칸이 폼이라 폼을 겹칠 수 없음) 여기서 바로 보낸다 */
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-mc-dwhidedr]');
    if (!b) return;
    if (!window.confirm('덴트웹 담당의 표시를 이 차트에서 지울까요? (덴트웹 원본은 그대로)')) return;
    var line = b.closest('.mc-dr__dw'); if (line) line.remove();
    var fd = new FormData(); fd.append('md_mc_action', 'dwhidedr'); fd.append('md_mc_nonce', b.getAttribute('data-mc-dwhidedr')); fd.append('mid', b.getAttribute('data-mid')); fd.append('md_fast', '1');
    fetch(b.getAttribute('data-url') || location.href, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.json(); })
      .then(function (res) { if (!res.ok) { toast(res.msg || '지우지 못했습니다.', true); return; } apply(res); toast('지웠습니다'); })
      .catch(function () { recover('저장 결과를 확인합니다…'); });
  });
  /* v9.19 · 눌러서 바로 고치기 — 보기 부분을 누르면 고치기 폼이 열리고, 취소하면 원래대로 */
  function closeEdit(form) { var box = form.querySelector('.mc-edit__form'); if (box) { box.hidden = true; } form.classList.remove('is-editing'); }
  document.addEventListener('click', function (e) {
    var c = e.target.closest && e.target.closest('[data-mc-edit-cancel]');
    if (c) { var f0 = c.closest('form'); f0.reset(); Array.prototype.forEach.call(f0.querySelectorAll('[data-na-for]'), function (k) { var t = document.getElementById(k.getAttribute('data-na-for')); if (t) t.disabled = k.checked; }); closeEdit(f0); return; }
    var od = e.target.closest && e.target.closest('[data-mc-open-dr]');
    var v = od ? document.querySelector('#f-dr [data-mc-edit-open]') : (e.target.closest && e.target.closest('[data-mc-edit-open]'));
    if (!v || (!od && e.target.closest('a, button'))) return;
    var form = v.closest('form.mc-edit'), box = form && form.querySelector('.mc-edit__form');
    if (!box) return;
    box.hidden = false; form.classList.add('is-editing');
    var t = box.querySelector('textarea:not([disabled]), input[type=text], select'); if (t) { t.focus(); if (t.setSelectionRange && t.value) { try { t.setSelectionRange(t.value.length, t.value.length); } catch (er) {} } }
    Array.prototype.forEach.call(box.querySelectorAll('textarea[data-grow]'), function (x) { x.style.height = 'auto'; x.style.height = (x.scrollHeight + 4) + 'px'; });
  });

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.dataset.mcSlow) return;
    var kind = form.getAttribute('data-mc-fast');
    var act = form.querySelector('input[name=md_mc_action]');
    act = act ? act.value : '';
    if (!kind && !(act === 'add' && form.classList.contains('mc-add')) && act !== 'plan') return;
    if (!kind) kind = act;
    var fieldName = kind === 'field' ? form.getAttribute('data-mc-field') : '';
    var sub = e.submitter, extra = null;
    if (kind === 'dwhide' && !window.confirm('이 덴트웹 진료기록을 미니차트에서 지울까요? (덴트웹 원본은 그대로)')) { e.preventDefault(); return; }
    if (kind === 'owndel' && !window.confirm('이 기록 한 줄을 지울까요?')) { e.preventDefault(); return; }
    if (kind === 'dwunhide') {
      if (sub && sub.name === 'all') extra = { all: '1' };
      else if (!form.querySelector('input[name="dwkeys[]"]:checked')) { e.preventDefault(); toast('다시 보이게 할 기록을 골라 주세요.', true); return; }
    }
    e.preventDefault();
    if (busy) { return; } /* 더블클릭 · Enter 연타로 두 번 보내지 않게 */
    busy = true;
    var li = form.closest('li');
    /* 먼저 화면에서 바로 */
    if (kind === 'dwhide' && li) {
      var dw = li.querySelector('.mc-tl__dw'); if (dw) dw.remove();
      var ed = li.querySelector('.mc-tl__edit'); if (ed) ed.remove();
      if (!li.querySelector('.mc-tl__own')) li.classList.add('is-gone');
    } else if (kind === 'dwedit' && li) {
      var t = form.querySelector('textarea').value.trim();
      var dw2 = li.querySelector('.mc-tl__dw'); if (dw2) dw2.remove();
      form.hidden = true;
      if (t) { var o = document.createElement('span'); o.className = 'mc-tl__own is-pending'; o.textContent = '✎ ' + t; li.querySelector('.mc-tl__tx').appendChild(o); }
    } else if (kind === 'dwunhide') {
      Array.prototype.forEach.call(form.querySelectorAll(extra ? 'li' : 'input[name="dwkeys[]"]:checked'), function (x) { (x.closest('li') || x).classList.add('is-gone'); });
    } else if (kind === 'owndel' || kind === 'ownedit') {
      /* v9.22 · 직접 적은 줄 — 지우면 바로 사라지고, 고치면 바로 바뀐다 (서버 대답이 오면 전체를 다시 맞춘다) */
      var w = form.closest('.mc-tl__ownw');
      if (w && kind === 'owndel') { w.classList.add('is-gone'); }
      else if (w) { var nt = form.querySelector('textarea').value.trim(); var sp = w.querySelector('.mc-tl__own'); form.hidden = true; if (sp) { sp.classList.add('is-pending'); sp.childNodes[0].nodeValue = (sp.childNodes[0].nodeValue.indexOf('✎') === 0 ? '✎ ' : '') + nt + ' '; } }
    }
    var btns = form.querySelectorAll('button[type=submit], button:not([type])');
    Array.prototype.forEach.call(btns, function (b) { b.disabled = true; });
    var addText = kind === 'add' ? form.querySelector('textarea[name=text]') : null;
    send(form, extra).then(function (res) {
      if (res.redirect) { form.dataset.sending = '1'; location.href = res.redirect; return; }
      if (res.reload && kind === 'field' && res.ok) { toast('저장했습니다'); form.dataset.sending = '1'; setTimeout(function () { location.reload(); }, 400); return; }
      if (res.reload) { recover(''); return; }
      if (!res.ok) { toast(res.msg || '저장하지 못했습니다.', true); if (kind === 'plan') { Array.prototype.forEach.call(btns, function (b) { b.disabled = false; }); } return; }
      apply(res);
      if (kind === 'add' && res.target && res.value != null) { /* v9.20 · 같은 칸의 「눌러서 고치기」 폼 값도 최신으로 */
        var sec = document.getElementById('f-' + res.target), ef = sec && sec.querySelector('form.mc-edit textarea[name=' + res.target + ']');
        if (ef) { ef.value = res.value; ef.defaultValue = res.value; }
      }
      if (kind === 'field') {
        /* v9.19 · 보기 부분 · 머리말 · 칸 값을 맞추고 고치기 폼을 닫는다 */
        var head = form.querySelector('.mc-edit__head'); if (head && res.head != null) head.textContent = res.head;
        form.classList.toggle('mc-block--alert', res.cls === 'mc-block--alert');
        var ta2 = form.querySelector('textarea[name=' + fieldName + '], input[name=' + fieldName + ']');
        if (ta2 && res.value != null) { ta2.value = res.value; ta2.defaultValue = res.value; ta2.disabled = !!res.na; }
        var na = form.querySelector('input[name="na[' + fieldName + ']"]'); if (na) { na.checked = !!res.na; na.defaultChecked = !!res.na; }
        closeEdit(form);
      }
      if (kind === 'add' && addText) { addText.value = ''; addText.style.height = ''; }
      if (kind === 'plan') {
        var ta = form.querySelector('textarea[data-mc-plan]'); if (ta) ta.defaultValue = ta.value;
        form.dispatchEvent(new CustomEvent('mc:plan-saved'));
      }
      toast({ dwhide: '지웠습니다', dwedit: '고쳤습니다', dwunhide: '다시 보이게 했습니다', add: '추가했습니다', plan: '저장했습니다', field: '저장했습니다', ownedit: '고쳤습니다', owndel: '지웠습니다', dwhidedr: '지웠습니다' }[kind] || '저장했습니다');
    }).catch(function () {
      recover('저장 결과를 확인합니다…');
    }).then(function () {
      busy = false;
      Array.prototype.forEach.call(btns, function (b) { b.disabled = false; });
    });
  }, true);
})();

/* v9.8 · 차트를 보다가 다른 환자 찾기 — 치는 대로 아래에 12명까지, ↑↓ 로 고르고 Enter 로 열기.
   고른 게 없으면 Enter 는 목록에서 찾기(내용까지). Esc 는 닫기 */
window.mcInits.push(function () {
  'use strict';
  var box = document.querySelector('[data-mc-jump]');
  if (!box) return;
  var input = box.querySelector('input[name=mq]');
  var ul = box.querySelector('.mc-jump__list');
  var base = box.getAttribute('data-mc-jump');
  var timer = null, seq = 0, rows = [], sel = -1;
  function esc(s) { return String(s || '').replace(/[&<>"]/g, function (m) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[m]; }); }
  function close() { ul.hidden = true; sel = -1; input.setAttribute('aria-expanded', 'false'); }
  function mark() {
    Array.prototype.forEach.call(ul.children, function (li, i) { li.classList.toggle('is-sel', i === sel); li.setAttribute('aria-selected', i === sel ? 'true' : 'false'); });
  }
  function draw(v) {
    if (!rows.length) {
      ul.innerHTML = '<li class="mc-jump__none">「' + esc(v) + '」 — 이름·차트번호로는 없습니다. Enter 를 누르면 내용까지 찾습니다.</li>';
    } else {
      ul.innerHTML = rows.map(function (r) {
        return '<li role="option"><a class="mc-jump__a" href="' + esc(r.url) + '">'
          + '<span class="mc-jump__no">' + esc(r.chart) + '</span>'
          + '<span class="mc-jump__nm">' + (r.pin ? '📌 ' : '') + esc(r.name)
          + (r.alert ? ' <span class="mc-warn">⚠ ' + esc(r.alert) + '</span>' : '') + '</span>'
          + '<span class="mc-jump__upd" title="최근 내원">' + esc(r.last) + '</span></a></li>';
      }).join('');
    }
    sel = rows.length === 1 ? 0 : -1;
    mark();
    ul.hidden = false;
    input.setAttribute('aria-expanded', 'true');
  }
  function find() {
    var v = input.value.trim();
    if (!v) { rows = []; close(); return; }
    var my = ++seq;
    var u = new URL(base, location.href);
    u.searchParams.set('md_mc_find', v);
    fetch(u.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (res) { return res.json(); })
      .then(function (j) {
        if (my !== seq || !j || !j.ok) return;
        rows = j.rows || [];
        draw(v);
      })
      .catch(function () {});
  }
  input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(find, 150); });
  input.addEventListener('focus', function () { if (input.value.trim() && ul.children.length) { ul.hidden = false; } });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      if (!rows.length || ul.hidden) return;
      e.preventDefault();
      sel = e.key === 'ArrowDown' ? Math.min(rows.length - 1, sel + 1) : Math.max(-1, sel - 1);
      mark();
    } else if (e.key === 'Enter') {
      if (sel >= 0 && rows[sel] && !ul.hidden) { e.preventDefault(); window.mcGo(rows[sel].url); }
    } else if (e.key === 'Escape') {
      close();
    }
  });
  document.addEventListener('click', function (e) { if (document.contains(box) && !box.contains(e.target)) close(); });
});

/* v10.0 · 홈 화면에 「미니차트」 아이콘 — 브라우저가 설치를 받아 주면 버튼, 아니면 휴대폰별 안내. 앱으로 열었거나 ✕ 로 닫았으면 숨김 */
window.mcInits.push(function () {
  'use strict';
  var box = document.getElementById('mc-install');
  if (!box) return;
  var KEY = 'md_mc_install_x';
  var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
  var closed = false; try { closed = localStorage.getItem(KEY) === '1'; } catch (e) {}
  if (standalone || closed) return;
  var ua = navigator.userAgent || '';
  var ios = /iPhone|iPad|iPod/i.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
  var mobile = ios || /Android|Mobile/i.test(ua);
  var btn = document.getElementById('mc-install-btn');
  var prompt = null;
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault(); prompt = e;
    box.hidden = false; btn.hidden = false;
    box.querySelector('.mc-install__and').hidden = true;
  });
  if (mobile) { box.hidden = false; box.querySelector(ios ? '.mc-install__ios' : '.mc-install__and').hidden = false; }
  btn.addEventListener('click', function () {
    if (!prompt) return;
    prompt.prompt();
    prompt.userChoice.then(function (c) { if (c && c.outcome === 'accepted') { box.hidden = true; } }, function () {});
    prompt = null; btn.hidden = true;
  });
  window.addEventListener('appinstalled', function () { box.hidden = true; });
  document.getElementById('mc-install-x').addEventListener('click', function () {
    box.hidden = true; try { localStorage.setItem(KEY, '1'); } catch (e) {}
  });
});

/* v10.3 · 목록 ↔ 차트를 페이지를 다시 불러오지 않고 바꾼다 (원장 「미니차트가 너무 느려」).
   운영 서버는 화면 하나에 서버만 0.7초 — 목록으로 돌아올 때는 들고 있던 목록을 그대로(스크롤 위치까지) 다시 붙이고,
   차트는 손가락 · 마우스를 누르는 순간 받아 오기 시작해 그 부분(.mc)만 바꾼다. 안 되면 예전처럼 페이지 이동. */
(function () {
  'use strict';
  var runAll = function () { window.mcInits.forEach(function (f) { try { f(); } catch (er) { if (window.console) console.error(er); } }); };
  var first = document.querySelector('.mc');
  runAll();
  if (!first || !window.fetch || !window.DOMParser || !history.pushState || !window.URL) return;
  first.mcReady = true;

  var cache = {}, pending = {};
  function u(h) { try { return new URL(h, location.href); } catch (e) { return null; } }
  function key(x) { return x.pathname + x.search; }
  /* 주소의 한글 경로는 %EC · %ec 가 섞여 나오므로 풀어서 비교 */
  function path(p) { try { return decodeURIComponent(p); } catch (e) { return p; } }
  function ours(x) { return x && x.origin === location.origin && path(x.pathname) === path(location.pathname) && x.searchParams.get('app') === 'minichart'; }
  function isChart(x) { return ours(x) && x.searchParams.get('mv') === 'p' && !!x.searchParams.get('mid'); }
  function isList(x) { return ours(x) && !x.searchParams.get('mv') && !x.searchParams.get('mid'); }
  function dirty() {
    var ta = document.querySelector('textarea[data-mc-plan]');
    if (ta && ta.mcDirty && ta.mcDirty()) return true;
    return Array.prototype.some.call(document.querySelectorAll('.mc .mc-add__text, .mc .mc-tl__edit:not([hidden]) textarea, .mc .mc-edit__form:not([hidden]) textarea'), function (t) { return t.value.trim() !== '' && t.value !== t.defaultValue; });
  }
  function load(x) {
    var k = key(x);
    if (!pending[k]) {
      pending[k] = fetch(x.toString(), { credentials: 'same-origin', headers: { 'X-MD-MC-Nav': '1' } })
        .then(function (r) { if (!r.ok || !ours(u(r.url))) throw new Error('nav'); return r.text(); })
        .then(function (t) {
          var d = new DOMParser().parseFromString(t, 'text/html');
          var mc = d.querySelector('.mc');
          if (!mc) throw new Error('nav');
          return { mc: mc, title: d.title };
        });
      pending[k].catch(function () {}).then(function () { setTimeout(function () { delete pending[k]; }, 3000); });
    }
    return pending[k];
  }
  function busy(on) { document.documentElement.classList.toggle('mc-loading', !!on); }
  /* 지금 붙어 있는 화면의 주소 — 뒤로 가기에서는 location 이 먼저 바뀌므로 따로 들고 있는다 */
  var shown = u(location.href), seq = 0;
  function swap(node, title, y, to) {
    var cur = document.querySelector('.mc');
    if (!cur) return false;
    if (isList(shown)) { cache[key(shown)] = { node: cur, y: window.pageYOffset, title: document.title }; }
    cur.replaceWith(node);
    shown = to;
    if (title) document.title = title;
    if (!node.mcReady) { node.mcReady = true; runAll(); }
    window.scrollTo(0, y || 0);
    return true;
  }
  /* 목록으로 돌아왔을 때 — 「최근 본 순」이면 방금 본 환자를 고정 아래 맨 위로 */
  function bump(listNode, mid, x) {
    if (!mid || x.searchParams.get('ms')) return;
    var a = listNode.querySelector('#mc-list a.mc-row[href*="mid=' + mid + '&"], #mc-list a.mc-row[href$="mid=' + mid + '"]');
    var li = a && a.closest('li');
    if (!li || li.querySelector('.mc-row__pin')) return;
    var ul = li.parentNode, pins = ul.querySelectorAll('li .mc-row__pin'), after = pins.length ? pins[pins.length - 1].closest('li') : null;
    ul.insertBefore(li, after ? after.nextSibling : ul.firstChild);
  }
  var lastMid = '';
  function go(x, push) {
    var k = key(x), my = ++seq;
    if (isList(x) && cache[k]) {
      var c = cache[k]; delete cache[k];
      bump(c.node, lastMid, x);
      swap(c.node, c.title, c.y, x);
      if (push) history.pushState({ mc: 1 }, '', x.toString());
      return;
    }
    busy(true);
    load(x).then(function (res) {
      if (my !== seq) return; /* 그 사이 다른 화면으로 갔다 */
      busy(false);
      if (isChart(x)) lastMid = x.searchParams.get('mid');
      swap(document.importNode(res.mc, true), res.title, 0, x);
      if (push) history.pushState({ mc: 1 }, '', x.toString());
    }).catch(function () { if (my === seq) { busy(false); location.href = x.toString(); } });
  }
  window.mcGo = function (h) {
    var x = u(h);
    if (!(isChart(x) || isList(x))) { location.href = h; return; }
    if (dirty() && !window.confirm('저장하지 않은 내용이 있습니다. 이 화면을 떠날까요?')) return;
    go(x, true);
  };
  history.replaceState({ mc: 1 }, '', location.href);
  if ('scrollRestoration' in history) history.scrollRestoration = 'manual';

  function linkOf(e) {
    var a = e.target.closest && e.target.closest('a[href]');
    if (!a || !a.closest('.mc') || a.target || a.hasAttribute('download')) return null;
    var x = u(a.getAttribute('href'));
    return (isChart(x) || isList(x)) ? x : null;
  }
  /* 누르는 순간 미리 받기 (차트만 — 마우스를 올리기만 한 환자를 「봤다」고 남기지 않도록) */
  /* 휴대폰은 목록을 밀어 올리려고 닿기만 해도 pointerdown 이 오므로 마우스 · 펜만 */
  var early = function (e) { if (e.button > 0 || e.pointerType === 'touch') return; var x = linkOf(e); if (x && isChart(x)) load(x); };
  document.addEventListener('pointerdown', early, true);
  document.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    var x = linkOf(e);
    if (!x) return;
    e.preventDefault();
    window.mcGo(x.toString());
  });
  window.addEventListener('popstate', function (e) {
    if (!e.state || !e.state.mc) return;
    var x = u(location.href);
    if (isChart(x) || isList(x)) go(x, false); else location.reload();
  });
})();
