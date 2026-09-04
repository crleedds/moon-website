/* =============================================================
   직원 전용 재고관리 · v3.55
   화면은 서버가 다 그려서 보낸다. 이 파일은 거들기만 한다 —
   JS 가 없어도 신청·출고·입고는 그대로 동작한다.

   하는 일
     ① 수량을 적으면 화면 아래 고정 바에 개수·금액 합계를 띄운다.
        568줄을 스크롤해 내려가 제출 버튼을 찾지 않아도 되게.
     ② 평균보다 많이 적으면 그 자리에서 알려주고 사유 칸을 연다.
        막지는 않는다 — 막으면 사람들이 시스템을 우회한다.
     ③ +/− 버튼. 태블릿에서 숫자 키보드를 띄우지 않고 조절.
   ============================================================= */
(function () {
  'use strict';

  var OVER = 2;  // 평균의 몇 배부터 사유를 물을지

  var form, bar, barCount, barTotal, inputs;

  function won(n) {
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  /* +/− 버튼을 만들어 수량 칸을 감싼다 (v3.66)
     서버가 568줄에 미리 박아 보내면 그것만으로 HTML 이 200KB 넘게 불어난다.
     어차피 JS 없이는 눌러도 아무 일도 안 하는 버튼이라 여기서 만든다. */
  function mkStep(step, label, text) {
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'mds-step';
    b.setAttribute('data-step', String(step));
    b.setAttribute('aria-label', label);
    b.tabIndex = -1;
    b.textContent = text;
    return b;
  }

  function buildSteppers(list) {
    Array.prototype.forEach.call(list, function (input) {
      var p = input.parentNode;
      if (!p || (p.classList && p.classList.contains('mds-stepper'))) return;

      var wrap = document.createElement('span');
      wrap.className = 'mds-stepper';
      p.insertBefore(wrap, input);
      wrap.appendChild(mkStep(-1, '수량 줄이기', '−'));
      wrap.appendChild(input);
      wrap.appendChild(mkStep(1, '수량 늘리기', '+'));
    });
  }

  /* 초과 사유 칸도 필요해질 때 만든다 — 실제로 쓰이는 줄은 몇 개뿐이다 */
  function makeReason(input) {
    var td = input.parentNode;
    while (td && td.tagName !== 'TD') { td = td.parentNode; }
    if (!td) return null;

    var el = document.createElement('input');
    el.type = 'text';
    el.className = 'mds-reason';
    el.name = 'reason[' + (input.getAttribute('data-item') || '') + ']';
    el.placeholder = '평균보다 많은 이유';
    el.setAttribute('aria-label', (input.getAttribute('aria-label') || '').replace(/신청 수량$/, '초과 신청 사유'));
    td.appendChild(el);
    return el;
  }

  /* 한 줄 상태 — 초과 표시와 행 강조 */
  function checkRow(input) {
    var avg  = parseFloat(input.getAttribute('data-avg') || '0');
    var qty  = parseInt(input.value, 10);
    var has  = qty > 0;
    var over = avg > 0 && has && qty > avg * OVER;
    var row  = input.closest ? input.closest('tr') : null;

    input.classList.toggle('is-over', over);
    if (row) { row.classList.toggle('is-picked', has); }

    var reason = row ? row.querySelector('.mds-reason') : null;
    if (over && !reason) { reason = makeReason(input); }
    if (reason) {
      reason.hidden = !over;
      if (!over) { reason.value = ''; }
    }
  }

  /* 전체 합계 — 고정 바 갱신 */
  function updateBar() {
    if (!bar) return;
    var count = 0, total = 0;

    Array.prototype.forEach.call(inputs, function (i) {
      var q = parseInt(i.value, 10);
      if (!(q > 0)) return;
      count++;
      total += q * (parseInt(i.getAttribute('data-price'), 10) || 0);
    });

    bar.hidden = (count === 0);
    if (barCount) { barCount.textContent = count; }
    if (barTotal) { barTotal.textContent = won(total); }
    document.body.classList.toggle('mds-has-cart', count > 0);
  }

  function onChange(e) {
    var t = e.target;
    if (!t || !t.classList || !t.classList.contains('mds-qty')) return;
    checkRow(t);
    updateBar();
  }

  /* +/− 버튼 */
  function onStep(e) {
    var btn = e.target.closest ? e.target.closest('.mds-step') : null;
    if (!btn) return;
    e.preventDefault();

    var wrap  = btn.parentNode;
    var input = wrap ? wrap.querySelector('.mds-qty') : null;
    if (!input) return;

    var step = parseInt(btn.getAttribute('data-step'), 10) || 0;
    var cur  = parseInt(input.value, 10);
    if (isNaN(cur)) { cur = 0; }

    var next = cur + step;
    if (next < 0) { next = 0; }
    input.value = next > 0 ? next : '';

    checkRow(input);
    updateBar();
  }

  function init() {
    inputs = document.querySelectorAll('.mds-qty[data-price]');
    if (!inputs.length) return;

    form = inputs[0].form;
    if (!form) return;

    bar      = document.getElementById('mds-cart');
    barCount = document.getElementById('mds-cart-count');
    barTotal = document.getElementById('mds-cart-total');

    buildSteppers(inputs);

    form.addEventListener('input', onChange);
    form.addEventListener('change', onChange);
    form.addEventListener('click', onStep);

    Array.prototype.forEach.call(inputs, checkRow);
    updateBar();

    /* 사유가 비어 있으면 한 번 물어본다.
       required 를 쓰지 않는 이유 — 숨겨진 필드에 걸리면 브라우저가
       "보이지 않는 요소는 검증할 수 없다"며 제출 자체를 막아버린다. */
    form.addEventListener('submit', function (e) {
      var blanks = [];
      Array.prototype.forEach.call(form.querySelectorAll('.mds-reason'), function (r) {
        if (!r.hidden && !r.value.trim()) { blanks.push(r); }
      });
      if (!blanks.length) return;

      if (!window.confirm('평균보다 많이 신청한 품목의 사유가 비어 있습니다.\n그대로 신청하시겠습니까?')) {
        e.preventDefault();
        blanks[0].focus();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/* =============================================================
   v3.60 · 즉시 검색
   568줄이 이미 화면에 있으니 서버에 다시 묻지 않고 그 자리에서 거른다.
   서버 검색(위 「찾기」)은 그대로 남겨 둔다 — JS 가 없어도 찾을 수 있게.
   ============================================================= */
(function () {
  'use strict';

  function init() {
    var box  = document.getElementById('mds-quick');
    var wrap = box ? box.parentNode : null;
    var cnt  = document.getElementById('mds-quick-count');
    /* 품목 줄에는 id="i<품목번호>" 가 붙어 있다. 구분선 줄에는 없다. */
    var rows = document.querySelectorAll('.mds-table--items tbody tr[id]');
    if (!box || !rows.length) return;

    if (wrap) { wrap.hidden = false; }   // JS 가 있을 때만 보여준다

    var divider = document.querySelector('.mds-table--items tbody tr.mds-divider');
    var timer;

    /* 훑을 문자열은 줄의 글자에서 직접 만든다 (v3.68).
       서버가 data-search 로 실어 보내던 것을 뺐다 — 이름·코드·거래처는
       이미 그 줄에 적혀 있는데 같은 내용을 한 번 더 보내느라
       568줄이면 30KB 가 더 오갔다. 여기서 한 번만 만들어 들고 있는다. */
    var hay = Array.prototype.map.call(rows, function (row) {
      return (row.textContent || '').toLowerCase().replace(/\s+/g, ' ');
    });

    function apply() {
      var q = box.value.trim().toLowerCase();
      var shown = 0;

      Array.prototype.forEach.call(rows, function (row, i) {
        var hit = !q || hay[i].indexOf(q) !== -1;
        row.hidden = !hit;
        if (hit) shown++;
      });

      /* 걸러내는 중에는 "여기부터 안 쓰던 품목" 구분선이 의미가 없다 */
      if (divider) { divider.hidden = !!q; }

      if (cnt) { cnt.textContent = q ? (shown + '개') : ''; }
    }

    box.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(apply, 60);   // 한글 조합 중 과도한 실행을 막는다
    });
    box.addEventListener('search', apply);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/* =============================================================
   v3.64 · 즐겨찾기를 그 자리에서

   별표는 원래 링크였다. 하나 누를 때마다 서버에 갔다가 568줄을 통째로
   다시 그려 돌아왔다 — 즐겨찾기 다섯 개를 고르면 다섯 번 새로고침이다.
   여기서는 그 링크를 가로채 별표만 바꾼다.

   링크는 그대로 둔다
     JS 가 없거나 fetch 를 모르는 태블릿에서는 손대지 않고 지나가므로
     예전처럼 새로고침으로 동작한다. 요청이 실패해도 원래 링크로 넘긴다.

   정렬은 새로고침 전까지 그대로다
     누르자마자 줄이 맨 위로 튀어 올라가면 지금 보던 자리를 잃는다.
     다음에 화면을 열 때 위로 모이는 편이 낫다.
   ============================================================= */
(function () {
  'use strict';

  function setFav(a, on) {
    var name = a.getAttribute('aria-label') || '';
    name = name.replace(/\s*즐겨찾기(\s*해제)?$/, '');

    a.classList.toggle('is-on', on);
    a.setAttribute('data-on', on ? '1' : '0');
    a.textContent = on ? '★' : '☆';
    a.title = on ? '즐겨찾기에서 빼기' : '즐겨찾기에 넣기';
    a.setAttribute('aria-label', name + (on ? ' 즐겨찾기 해제' : ' 즐겨찾기'));
  }

  function init() {
    if (!window.fetch) return;   // 모르는 브라우저는 링크 그대로

    var table = document.querySelector('.mds-table--items');
    if (!table || !table.addEventListener) return;

    table.addEventListener('click', function (e) {
      var a = e.target.closest ? e.target.closest('.mds-fav') : null;
      if (!a || !a.getAttribute('data-fav')) return;

      e.preventDefault();
      if (a.getAttribute('data-busy')) return;
      a.setAttribute('data-busy', '1');

      var href = a.href;
      var url  = href + (href.indexOf('?') === -1 ? '?' : '&') + 'ajax=1';

      fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (d) {
          a.removeAttribute('data-busy');
          if (!d || !d.ok) { window.location.href = href; return; }
          setFav(a, !!d.on);
        })
        .catch(function () {
          a.removeAttribute('data-busy');
          window.location.href = href;   // 실패하면 예전 방식으로
        });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/* =============================================================
   v3.68 · 신청 팀 고르기

   고르는 칸은 신청 폼 안에 있다 — 그대로 제출되므로 JS 가 없어도
   팀이 실려 간다. JS 가 있으면 고르는 순간 그 팀 기준으로 화면을 다시
   불러 「우리 팀 월평균」·「최근 수령」·즐겨찾기를 채운다.

   적어 둔 수량이 있으면 먼저 물어본다 — 화면을 다시 부르면 지워진다.
   ============================================================= */
(function () {
  'use strict';

  function typedSomething() {
    var qs = document.querySelectorAll('.mds-qty[data-price]');
    for (var i = 0; i < qs.length; i++) {
      if (parseInt(qs[i].value, 10) > 0) return true;
    }
    return false;
  }

  function init() {
    var sel = document.getElementById('mds-team');
    if (!sel) return;

    var base = sel.getAttribute('data-base');
    if (!base) return;

    var before = sel.value;

    sel.addEventListener('change', function () {
      var v = parseInt(sel.value, 10);
      if (!v) { before = sel.value; return; }

      if (typedSomething() &&
          !window.confirm('팀을 바꾸면 적어 두신 수량이 지워집니다.\n그래도 바꾸시겠습니까?')) {
        sel.value = before;
        return;
      }

      before = sel.value;
      window.location.href = base + (base.indexOf('?') === -1 ? '?' : '&') + 'team=' + v;
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
