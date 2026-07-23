/**
 * 상담예약 — 3-step wizard.
 *
 * - 단계 전환·검증
 * - 요일별 시간 옵션 활성/비활성 (목 ~18:30, 토 ~14:00, 일·공휴일 휴진)
 * - AJAX 제출
 * - v3.38.0 · 모든 사용자 노출 문자열은 MoondentalRes.msg (Customizer)에서 주입
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('md-reservation-form');
    if (!form) return;

    // v3.38.0 · MoondentalRes.msg fallback (localize가 안 됐거나 특정 키 누락 시)
    var M = (window.MoondentalRes && MoondentalRes.msg) || {};
    function T(key, fallback) { return M[key] || fallback; }

    // v3.37.0 · 허니팟
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
          alert(T('alertSvc', '진료항목을 선택해주세요.'));
          return false;
        }
      } else if (n === 2) {
        if (!dateEl.value) { alert(T('alertDate', '희망 날짜를 선택해주세요.')); return false; }
        if (!form.querySelector('input[name="time"]:checked')) {
          alert(T('alertTime', '희망 시간을 선택해주세요.'));
          return false;
        }
      } else if (n === 3) {
        var name = form.querySelector('input[name="name"]').value.trim();
        var phone = form.querySelector('input[name="phone"]').value.trim();
        var agree = form.querySelector('input[name="agree_privacy"]').checked;
        if (!name) { alert(T('alertName', '성함을 입력해주세요.')); return false; }
        if (!phone) { alert(T('alertPhone', '연락처를 입력해주세요.')); return false; }
        if (!/^[0-9]{2,4}-?[0-9]{3,4}-?[0-9]{4}$/.test(phone.replace(/[^0-9-]/g, ''))) {
          alert(T('alertPhoneFmt', '연락처 형식이 올바르지 않습니다. (예: 010-1234-5678)'));
          return false;
        }
        if (!agree) { alert(T('alertPrivacy', '개인정보 처리방침 동의가 필요합니다.')); return false; }
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

    steps.forEach(function (s) {
      s.addEventListener('click', function () {
        var k = parseInt(s.dataset.step, 10);
        if (k < current) go(k);
      });
    });

    // 요일별 시간 슬롯 필터
    var DAY_LIMITS = {
      0: { closed: true },          // 일요일 휴진
      1: { until: '20:30' },        // 월
      2: { until: '20:30' },        // 화
      3: { until: '20:30' },        // 수
      4: { until: '18:30' },        // 목
      5: { until: '20:30' },        // 금
      6: { until: '14:00' }         // 토
    };

    function applyDayFilter() {
      var v = dateEl.value;
      if (!v) {
        timeGrid.querySelectorAll('.md-time-grid__item').forEach(function (it) { it.classList.remove('is-disabled'); it.querySelector('input').disabled = false; });
        dateHint.textContent = T('hintDefault', '선택하신 요일에 따라 가능한 시간이 표시됩니다.');
        return;
      }
      var d = new Date(v + 'T00:00');
      var dow = d.getDay();
      var rule = DAY_LIMITS[dow] || { until: '20:30' };

      if (rule.closed) {
        timeGrid.querySelectorAll('.md-time-grid__item').forEach(function (it) {
          it.classList.add('is-disabled');
          it.querySelector('input').disabled = true;
          it.querySelector('input').checked = false;
        });
        dateHint.textContent = T('hintClosed', '⚠️ 선택하신 날짜는 휴진일입니다. 다른 날짜를 선택해주세요.');
        dateHint.style.color = '#c66b5e';
        return;
      }

      dateHint.style.color = '';
      var dayKor = ['일','월','화','수','목','금','토'][dow];
      dateHint.textContent = T('hintOpen', '✓ {day}요일 진료 가능 시간: 09:00 – {until}')
        .replace('{day}', dayKor).replace('{until}', rule.until);

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
      fd.append('md_form_render_time', MoondentalRes.renderTime || '');

      submitBtn.disabled = true;
      submitBtn.textContent = T('btnSending', '전송 중...');

      fetch(MoondentalRes.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.success) {
            renderSuccess(data.data);
          } else {
            var msg = (data && data.data && data.data.message) || T('alertFail', '예약 전송에 실패했습니다.');
            alert(msg);
            submitBtn.disabled = false;
            submitBtn.textContent = T('btnSubmit', '예약 신청');
          }
        })
        .catch(function () {
          alert(T('alertNetwork', '네트워크 오류 — 잠시 후 다시 시도해주세요.'));
          submitBtn.disabled = false;
          submitBtn.textContent = T('btnSubmit', '예약 신청');
        });
    });

    function renderSuccess(d) {
      var kakaoUrl = T('kakaoUrl', 'http://pf.kakao.com/_VTcgE/chat');
      var homeUrl = T('homeUrl', '/');
      var html = ''
        + '<div class="md-res-success">'
        +   '<div class="md-res-success__icon" aria-hidden="true">✓</div>'
        +   '<h2 class="md-res-success__title">' + esc(T('successTitle', '예약 신청이 완료되었습니다!')) + '</h2>'
        +   '<p class="md-res-success__lead">' + esc(T('successLead', '담당자가 확인 후 빠른 시간 내에 연락드리겠습니다.')) + '</p>'
        +   '<dl class="md-res-success__detail">'
        +     '<dt>' + esc(T('successLblNo', '예약번호')) + '</dt><dd>' + esc(d.res_no) + '</dd>'
        +     '<dt>' + esc(T('successLblSvc', '진료항목')) + '</dt><dd>' + esc(d.service) + '</dd>'
        +     '<dt>' + esc(T('successLblDt', '희망일시')) + '</dt><dd>' + esc(d.datetime) + '</dd>'
        +     '<dt>' + esc(T('successLblName', '예약자')) + '</dt><dd>' + esc(d.name) + '</dd>'
        +   '</dl>'
        +   '<p class="md-res-success__hint">' + esc(T('successHint', '예약 확정 전 변경이 필요하시면 전화 또는 카카오톡으로 연락주세요.')) + '</p>'
        +   '<div class="md-btn-group" style="justify-content:center; display:flex; margin-top:16px; flex-wrap:wrap; gap:10px;">'
        +     '<a class="md-btn md-btn-primary" href="' + esc(kakaoUrl) + '" target="_blank" rel="noopener" data-track="cta-success-kakao">' + esc(T('successBtnKakao', '💬 카카오톡 친구 추가')) + '</a>'
        +     '<a class="md-btn md-btn-ghost" href="' + esc(homeUrl) + '">' + esc(T('successBtnHome', '홈으로')) + '</a>'
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
