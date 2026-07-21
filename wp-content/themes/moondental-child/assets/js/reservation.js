/**
 * 상담예약 — 3-step wizard.
 *
 * - 단계 전환·검증
 * - 요일별 시간 옵션 활성/비활성 (목 ~18:30, 토 ~14:00, 일·공휴일 휴진)
 * - AJAX 제출
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('md-reservation-form');
    if (!form) return;

    // v3.37.0 · 허니팟 (봇이 채우면 서버에서 조용히 거절)
    // 화면 밖 · 자동완성 off · label 없음
    var hp = document.createElement('input');
    hp.type = 'text';
    hp.name = 'md_hp_website';
    hp.tabIndex = -1;
    hp.autocomplete = 'off';
    hp.setAttribute('aria-hidden', 'true');
    hp.style.cssText = 'position:absolute!important;left:-10000px!important;top:auto!important;width:1px!important;height:1px!important;overflow:hidden!important;';
    form.appendChild(hp);

    var steps = form.querySelectorAll('.md-step');
    var panels = form.querySelectorAll('.md-step-panel');
    var nextBtns = form.querySelectorAll('.md-step-next');
    var prevBtns = form.querySelectorAll('.md-step-prev');
    var dateEl = form.querySelector('#md-res-date');
    var timeGrid = form.querySelector('#md-res-time-grid');
    var dateHint = form.querySelector('#md-res-date-hint');
    var result = document.getElementById('md-res-result');
    var submitBtn = document.getElementById('md-res-submit');

    var current = 1;

    function go(n) {
      if (n < 1 || n > panels.length) return;
      current = n;
      steps.forEach(function (s) {
        var k = parseInt(s.dataset.step, 10);
        s.classList.toggle('is-active', k === n);
        s.classList.toggle('is-done', k < n);
      });
      panels.forEach(function (p) {
        var k = parseInt(p.dataset.panel, 10);
        p.classList.toggle('is-active', k === n);
      });
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function validateStep(n) {
      if (n === 1) {
        if (!form.querySelector('input[name="service"]:checked')) {
          alert('진료항목을 선택해주세요.');
          return false;
        }
      } else if (n === 2) {
        if (!dateEl.value) { alert('희망 날짜를 선택해주세요.'); return false; }
        if (!form.querySelector('input[name="time"]:checked')) {
          alert('희망 시간을 선택해주세요.');
          return false;
        }
      } else if (n === 3) {
        var name = form.querySelector('input[name="name"]').value.trim();
        var phone = form.querySelector('input[name="phone"]').value.trim();
        var agree = form.querySelector('input[name="agree_privacy"]').checked;
        if (!name) { alert('성함을 입력해주세요.'); return false; }
        if (!phone) { alert('연락처를 입력해주세요.'); return false; }
        if (!/^[0-9]{2,4}-?[0-9]{3,4}-?[0-9]{4}$/.test(phone.replace(/[^0-9-]/g, ''))) {
          alert('연락처 형식이 올바르지 않습니다. (예: 010-1234-5678)');
          return false;
        }
        if (!agree) { alert('개인정보 처리방침 동의가 필요합니다.'); return false; }
      }
      return true;
    }

    nextBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        if (!validateStep(current)) return;
        go(current + 1);
      });
    });
    prevBtns.forEach(function (b) {
      b.addEventListener('click', function () { go(current - 1); });
    });

    // step indicator 클릭으로 이동 (이전 단계로만)
    steps.forEach(function (s) {
      s.addEventListener('click', function () {
        var k = parseInt(s.dataset.step, 10);
        if (k < current) go(k);
      });
    });

    // 요일별 시간 슬롯 필터
    var DAY_LIMITS = {
      // dayOfWeek (0=일) → { closed: true } | { until: 'HH:MM' }
      0: { closed: true },          // 일요일 휴진
      1: { until: '20:30' },        // 월
      2: { until: '20:30' },        // 화
      3: { until: '20:30' },        // 수
      4: { until: '18:30' },        // 목 (야간진료 없음)
      5: { until: '20:30' },        // 금
      6: { until: '14:00' }         // 토
    };

    function applyDayFilter() {
      var v = dateEl.value;
      if (!v) {
        timeGrid.querySelectorAll('.md-time-grid__item').forEach(function (it) { it.classList.remove('is-disabled'); it.querySelector('input').disabled = false; });
        dateHint.textContent = '선택하신 요일에 따라 가능한 시간이 표시됩니다.';
        return;
      }
      var d = new Date(v + 'T00:00');
      var dow = d.getDay();
      var rule = DAY_LIMITS[dow] || { until: '20:30' };
      var checkedRadio = form.querySelector('input[name="time"]:checked');

      if (rule.closed) {
        timeGrid.querySelectorAll('.md-time-grid__item').forEach(function (it) {
          it.classList.add('is-disabled');
          it.querySelector('input').disabled = true;
          it.querySelector('input').checked = false;
        });
        dateHint.textContent = '⚠️ 선택하신 날짜는 휴진일입니다. 다른 날짜를 선택해주세요.';
        dateHint.style.color = '#c66b5e';
        return;
      }

      dateHint.style.color = '';
      var dayKor = ['일','월','화','수','목','금','토'][dow];
      dateHint.textContent = '✓ ' + dayKor + '요일 진료 가능 시간: 09:00 – ' + rule.until;

      var limitHour = parseInt(rule.until.split(':')[0], 10);
      var limitMin = parseInt(rule.until.split(':')[1], 10);
      timeGrid.querySelectorAll('.md-time-grid__item').forEach(function (it) {
        var t = it.dataset.time;
        var h = parseInt(t.split(':')[0], 10);
        var m = parseInt(t.split(':')[1], 10);
        var over = (h > limitHour) || (h === limitHour && m > limitMin);
        it.classList.toggle('is-disabled', over);
        it.querySelector('input').disabled = over;
        if (over && it.querySelector('input').checked) it.querySelector('input').checked = false;
      });
    }
    dateEl.addEventListener('change', applyDayFilter);

    // 전화번호 자동 하이픈
    var phoneEl = form.querySelector('input[name="phone"]');
    phoneEl.addEventListener('input', function () {
      var v = phoneEl.value.replace(/[^0-9]/g, '');
      if (v.length > 11) v = v.slice(0, 11);
      if (v.length >= 7) v = v.slice(0,3) + '-' + v.slice(3,7) + '-' + v.slice(7);
      else if (v.length >= 4) v = v.slice(0,3) + '-' + v.slice(3);
      phoneEl.value = v;
    });

    // 폼 제출
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!validateStep(3)) return;

      var fd = new FormData(form);
      fd.append('action', 'moondental_reservation');
      fd.append('_nonce', MoondentalRes.nonce);
      // v3.37.0 · 봇 감지용 폼 렌더 시각 (2초 미만 제출 차단)
      fd.append('md_form_render_time', MoondentalRes.renderTime || '');

      submitBtn.disabled = true;
      submitBtn.textContent = '전송 중...';

      fetch(MoondentalRes.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.success) {
            renderSuccess(data.data);
          } else {
            var msg = (data && data.data && data.data.message) || '예약 전송에 실패했습니다.';
            alert(msg);
            submitBtn.disabled = false;
            submitBtn.textContent = '예약 신청';
          }
        })
        .catch(function () {
          alert('네트워크 오류 — 잠시 후 다시 시도해주세요.');
          submitBtn.disabled = false;
          submitBtn.textContent = '예약 신청';
        });
    });

    function renderSuccess(d) {
      var html = ''
        + '<div class="md-res-success">'
        +   '<div class="md-res-success__icon" aria-hidden="true">✓</div>'
        +   '<h2 class="md-res-success__title">예약 신청이 완료되었습니다!</h2>'
        +   '<p class="md-res-success__lead">담당자가 확인 후 빠른 시간 내에 연락드리겠습니다.</p>'
        +   '<dl class="md-res-success__detail">'
        +     '<dt>예약번호</dt><dd>' + esc(d.res_no) + '</dd>'
        +     '<dt>진료항목</dt><dd>' + esc(d.service) + '</dd>'
        +     '<dt>희망일시</dt><dd>' + esc(d.datetime) + '</dd>'
        +     '<dt>예약자</dt><dd>' + esc(d.name) + '</dd>'
        +   '</dl>'
        +   '<p class="md-res-success__hint">예약 확정 전 변경이 필요하시면 전화 또는 카카오톡으로 연락주세요.</p>'
        +   '<div class="md-btn-group" style="justify-content:center; display:flex; margin-top:16px; flex-wrap:wrap; gap:10px;">'
        +     '<a class="md-btn md-btn-primary" href="http://pf.kakao.com/_VTcgE/chat" target="_blank" rel="noopener" data-track="cta-success-kakao">💬 카카오톡 친구 추가</a>'
        +     '<a class="md-btn md-btn-ghost" href="/">홈으로</a>'
        +   '</div>'
        + '</div>';
      result.innerHTML = html;
      result.hidden = false;
      form.querySelectorAll('.md-step-panel, .md-steps').forEach(function (el) { el.style.display = 'none'; });
      result.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function esc(s) {
      var div = document.createElement('div');
      div.textContent = s == null ? '' : String(s);
      return div.innerHTML;
    }
  });
})();
