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
    var rows = document.querySelectorAll('.mds-table--items tbody tr[data-search]');
    if (!box || !rows.length) return;

    if (wrap) { wrap.hidden = false; }   // JS 가 있을 때만 보여준다

    var divider = document.querySelector('.mds-table--items tbody tr.mds-divider');
    var timer;

    function apply() {
      var q = box.value.trim().toLowerCase();
      var shown = 0;

      Array.prototype.forEach.call(rows, function (row) {
        var hit = !q || row.getAttribute('data-search').indexOf(q) !== -1;
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
