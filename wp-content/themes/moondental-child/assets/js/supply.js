/* =============================================================
   직원 전용 재고관리 · v3.51
   화면은 서버가 다 그려서 보낸다. 이 파일은 거들기만 한다 —
   JS 가 없어도 신청·출고·입고는 그대로 동작한다.

   하는 일 하나 · 평균보다 많이 적으면 그 자리에서 알려주고 사유 칸을 연다.
   막지는 않는다. 실제로 필요한 경우가 있고, 막으면 사람들이 시스템을 우회한다.
   ============================================================= */
(function () {
  'use strict';

  var OVER = 2;  // 평균의 몇 배부터 사유를 물을지

  function wire(input) {
    var avg = parseFloat(input.getAttribute('data-avg') || '0');
    var row = input.closest ? input.closest('tr') : null;
    if (!row) return;
    var reason = row.querySelector('.mds-reason');

    function check() {
      var qty  = parseInt(input.value, 10);
      var over = avg > 0 && qty > 0 && qty > avg * OVER;

      input.classList.toggle('is-over', over);
      if (reason) {
        reason.hidden = !over;
        if (!over) { reason.value = ''; }
      }
    }

    input.addEventListener('input', check);
    input.addEventListener('blur', check);
    check();
  }

  function init() {
    var inputs = document.querySelectorAll('.mds-qty[data-avg]');
    Array.prototype.forEach.call(inputs, wire);

    /* 신청 버튼을 눌렀는데 사유 칸이 비어 있으면 한 번 물어본다.
       required 를 쓰지 않는 이유 — 숨겨진 필드에 걸리면 브라우저가
       "보이지 않는 요소는 검증할 수 없다"며 제출 자체를 막아버린다. */
    var form = document.querySelector('form input[value="request"]');
    form = form ? form.form : null;
    if (!form) return;

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
