/* 품목신청 (재고관리 v5) — 직원 라운지
 *
 * 요청 화면: 품목 목록 · 검색 · 장바구니(이 기기 localStorage) · 바코드 스캔
 * 공통: 대화상자(data-dlg / data-set), 확인창(data-confirm), 품목 고르기(datalist),
 *       분류 3단계 좁히기, 필터 자동 제출, 두 번 누르기 방지
 */
(function () {
  'use strict';

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var num = function (n) { return Number(n || 0).toLocaleString('ko-KR'); };
  var store = {
    get: function (k, d) { try { var v = window.localStorage.getItem(k); return v ? JSON.parse(v) : d; } catch (e) { return d; } },
    set: function (k, v) { try { window.localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
    del: function (k) { try { window.localStorage.removeItem(k); } catch (e) {} }
  };

  /* ---------------------------------------------------------
   * 대화상자
   * ------------------------------------------------------- */
  function openDlg(id, set) {
    var d = document.getElementById(id);
    if (!d) return null;
    if (set) fillDlg(d, set);
    if (typeof d.showModal === 'function') { if (!d.open) d.showModal(); } else { d.setAttribute('open', ''); }
    var f = d.querySelector('input:not([type=hidden]):not([readonly]), select, textarea');
    if (f && window.matchMedia('(pointer:fine)').matches) { setTimeout(function () { try { f.focus(); if (f.select) f.select(); } catch (e) {} }, 30); }
    return d;
  }
  function closeDlg(d) { if (!d) return; if (typeof d.close === 'function') d.close(); else d.removeAttribute('open'); }

  function fillDlg(d, set) {
    var form = d.querySelector('form');
    if (form && form.dataset.keep !== '1') {
      $$('input:not([type=hidden]):not([type=checkbox]):not([type=file]), textarea', form).forEach(function (el) { if (!el.hasAttribute('data-keepval')) el.value = el.defaultValue; });
      $$('input[type=checkbox]', form).forEach(function (el) { el.checked = el.defaultChecked; });
      $$('select', form).forEach(function (el) { var d = $$('option', el).filter(function (o) { return o.defaultSelected; })[0]; el.value = d ? d.value : (el.options[0] ? el.options[0].value : ''); if (el.dataset.level) el.dataset.value = el.value; });
      $$('input[type=hidden][name=id], input[type=hidden][name=item_id], input[type=hidden][name=in_id]', form).forEach(function (el) { el.value = ''; });
    }
    $$('[data-t]', d).forEach(function (el) { el.textContent = ''; });
    Object.keys(set).forEach(function (k) {
      var v = set[k];
      if (k.indexOf('@') > 0) { var p = k.split('@'); $$('[name="' + p[0] + '"]', d).forEach(function (el) { el.setAttribute(p[1], v); }); return; }
      $$('[data-t="' + k + '"]', d).forEach(function (el) { el.textContent = v; });
      $$('[name="' + k + '"]', d).forEach(function (el) {
        if (el.type === 'checkbox') { el.checked = !!Number(v); }
        else if (el.tagName === 'SELECT') { el.value = String(v); if (el.dataset.level) el.dataset.value = String(v); }
        else if (el.type !== 'hidden' || ['id', 'item_id', 'in_id', 'vendor_id', 'level', 'parent_id'].indexOf(k) >= 0 || el.hasAttribute('data-fill')) { el.value = v; }
      });
    });
    /* 품목 번호만 받았으면 고르는 칸의 이름을 목록에서 찾아 채운다 (화면을 가볍게) */
    if (set.item_id && set.item_id_pick === undefined) {
      var pick = d.querySelector('[data-pick="item_id"]'), dl = document.getElementById('iv-items-dl');
      if (pick && dl) {
        var suf = ' #' + set.item_id;
        var o = $$('option', dl).filter(function (x) { return x.value.slice(-suf.length) === suf; })[0];
        if (o) { pick.value = o.value; pick.setCustomValidity(''); }
      }
    }
    /* 새로 만들 때만 보이는 칸 */
    var idf = d.querySelector('input[type=hidden][name=id]');
    $$('[data-newonly]', d).forEach(function (el) { el.hidden = !!(idf && idf.value); });
    $$('.iv-catsel', d).forEach(catselInit);
    $$('[data-calc]', d).length && calcInit(d);
    lotSync(d);
    var pin = d.querySelector('input[name=price]'); if (pin) { pin.dataset.auto = set.price !== undefined && set.price !== '' ? '1' : ''; }
    priceSync(d);
    $$('.iv-rcpt', d).forEach(function (r) { rcptInit(r, true); }); /* v6.5 · 거래명세서 칸 */
    retInit(d);
    /* v6.3 · 값이 있을 때만 보이는 칸 (예: 선납 업체일 때만 「돌려받은 곳」) */
    $$('[data-ifset]', d).forEach(function (el) { el.hidden = !set[el.dataset.ifset]; });
    adjKind(d);
    $$('[data-verify-msg]', d).forEach(function (m) { m.textContent = ''; m.className = 'iv-verify__msg'; });
    if (form) form.dataset.verifyBad = '';
  }

  /* v6.3 · 환불 · 정정 — 종류마다 칸 · 「전액」이면 금액 칸 잠그기 */
  function adjKind(d) {
    if (!d || d.id !== 'dlg-adj') return;
    var k = (d.querySelector('[name=kind]:checked') || {}).value || 'refund';
    $$('[data-adj]', d).forEach(function (el) { el.hidden = el.dataset.adj !== k; });
    var all = d.querySelector('[name=all]'), amt = d.querySelector('[name=amount]');
    if (all && amt) { amt.disabled = all.checked; if (all.checked) amt.value = ''; }
  }
  document.addEventListener('change', function (e) {
    var d = e.target.closest && e.target.closest('#dlg-adj');
    if (d) adjKind(d);
    var dk = e.target.closest && e.target.closest('#dlg-dep');
    if (dk && e.target.name === 'dkind') {
      var credit = e.target.value === 'credit';
      var am = dk.querySelector('[name=amount]'); if (am) { am.required = !credit; am.closest('label').hidden = credit; if (credit) am.value = ''; }
      var cr = dk.querySelector('[name=credit]'); if (cr) { cr.required = credit; cr.placeholder = credit ? '업체가 넣어 준 금액' : '비우면 업체 적립률로 계산'; }
    }
  });

  /* v6.2 · 분류 정리 도우미 — 묶음 「모두」 · 분류를 고르면 그 줄 체크 */
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (t.matches && t.matches('[data-grpall]')) { var g = t.closest('details'); if (g) $$('input[name="on[]"]', g).forEach(function (c) { c.checked = t.checked; }); }
    if (t.matches && t.matches('[data-catsub]')) { var r = t.closest('.iv-catrow'), c = r && r.querySelector('input[name="on[]"]'); if (c) c.checked = !!t.value; }
  });

  /* v6.0 · LOT · 차트번호 — 고른 품목이 추적 품목이면 칸을 보이고 남은 LOT 를 목록에 (먼저 들어온 것부터) */
  var trackMap = null;
  function lotSync(d) {
    var box = d && $$('[data-if="track"]', d);
    if (!box || !box.length) return;
    if (trackMap === null) { var el = document.getElementById('iv-track-map'); try { trackMap = el ? JSON.parse(el.textContent) : {}; } catch (x) { trackMap = {}; } }
    var id = '', ti = d.querySelector('[name="track_item"]'), hi = d.querySelector('input[type=hidden][name="item_id"]'), pk = d.querySelector('[data-pick="item_id"]');
    if (ti && ti.value) id = ti.value;
    else if (hi && hi.value) id = hi.value;
    else if (pk) { var m = /#(\d+)\s*$/.exec(pk.value || ''); if (m) id = m[1]; }
    var lots = id && trackMap[id];
    box.forEach(function (b) { b.hidden = !lots; });
    var dl = document.getElementById('iv-lot-dl');
    if (dl && lots) dl.innerHTML = lots.map(function (l) { return '<option value="' + String(l[0]).replace(/"/g, '&quot;') + '">남은 ' + l[1] + '</option>'; }).join('');
    var li = d.querySelector('[data-lots]');
    if (li && lots) li.placeholder = lots.length ? '비우면 ' + lots[0][0] : '남은 LOT 없음';
  }
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (t.matches && t.matches('[data-pick="item_id"]')) { var d = t.closest('dialog'); if (d) { lotSync(d); priceSync(d); } }
  });
  document.addEventListener('input', function (e) {
    var t = e.target;
    if (t.matches && t.matches('[data-pick="item_id"]')) { var d = t.closest('dialog'); if (d) { lotSync(d); priceSync(d); } }
    /* 사람이 단가를 직접 고치면 그다음엔 자동으로 덮어쓰지 않는다 */
    if (t.matches && t.matches('input[name=price]') && e.isTrusted) { t.dataset.auto = ''; var h = t.closest('label') && t.closest('label').querySelector('.iv-pricehint'); if (h) h.textContent = ''; }
  });

  /* v6.4 · 주문 · 입고 창 — 품목을 고르면 그 품목 단가를 넣는다 (비어 있거나 자동으로 넣은 값일 때만 · 고칠 수 있음) */
  var priceMap = null;
  function priceSync(d) {
    var pin = d && d.querySelector('input[name=price]');
    if (!pin || !d.querySelector('[data-pick="item_id"]')) return;
    if (priceMap === null) { var el = document.getElementById('iv-price-map'); try { priceMap = el ? JSON.parse(el.textContent) : {}; } catch (x) { priceMap = {}; } }
    var pk = d.querySelector('[data-pick="item_id"]'), m = /#(\d+)\s*$/.exec(pk.value || ''), id = m ? m[1] : '';
    var info = id && priceMap[id];
    var lab = pin.closest('label'), hint = lab && lab.querySelector('.iv-pricehint');
    if (lab && !hint) { hint = document.createElement('small'); hint.className = 'iv-pricehint'; lab.appendChild(hint); }
    if (!info) { if (hint) hint.textContent = ''; return; }
    if (pin.value === '' || pin.dataset.auto === '1') {
      pin.value = info[0] ? String(info[0]) : '';
      pin.dataset.auto = '1';
      pin.dispatchEvent(new Event('input', { bubbles: false }));
    }
    if (hint) hint.textContent = info[0] ? '품목 단가 ' + num(info[0]) + '원' + (info[1] ? ' / ' + info[1] : '') + ' — 다르면 고쳐 주세요' : '이 품목은 단가가 없습니다 — 적어 주세요';
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-dlg]');
    if (b) {
      e.preventDefault();
      var set = null;
      if (b.dataset.set) { try { set = JSON.parse(b.dataset.set); } catch (x) { set = null; } }
      var more = b.closest('details.iv-more-menu'); if (more) more.open = false;
      openDlg(b.dataset.dlg, set || {});
      return;
    }
    var c = e.target.closest('[data-close]');
    if (c) { e.preventDefault(); closeDlg(c.closest('dialog')); return; }
    var x = e.target.closest('[data-dismiss]');
    if (x) { var box = x.parentNode; box.parentNode.removeChild(box); return; }
    if (e.target.tagName === 'DIALOG' && e.target.classList.contains('iv-dlg')) {
      /* 바깥(어두운 곳)을 누르면 닫는다 — 입력 중인 칸이 있으면 닫지 않는다 */
      var r = e.target.getBoundingClientRect();
      if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) {
        var dirty = $$('input:not([type=hidden]), textarea', e.target).some(function (el) { return el.type !== 'checkbox' && el.value !== el.defaultValue && el.value !== ''; });
        if (!dirty) closeDlg(e.target);
      }
    }
    /* 더보기 메뉴 바깥을 누르면 닫는다 */
    $$('details.iv-more-menu[open]').forEach(function (m) { if (!m.contains(e.target)) m.open = false; });
  });

  /* ---------------------------------------------------------
   * 폼: 확인 · 두 번 누르기 방지 · 체크 묶음
   * ------------------------------------------------------- */
  /* 확인창 — 다른 처리보다 먼저(capture) */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!(f instanceof HTMLFormElement) || f.method.toLowerCase() !== 'post') return;
    if (f.dataset.sending === '1') { e.preventDefault(); e.stopImmediatePropagation(); return; }
    var btn = e.submitter || f.querySelector('button:not([type=button])');
    var msg = f.dataset.confirm || (btn && btn.dataset.confirmBtn);
    if (msg && !window.confirm(msg)) { e.preventDefault(); e.stopImmediatePropagation(); }
  }, true);
  /* 두 번 누르기 방지 — 폼 자신의 검사가 끝난 뒤(bubble) 실제로 보낼 때만 잠근다 */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!(f instanceof HTMLFormElement) || f.method.toLowerCase() !== 'post' || e.defaultPrevented) return;
    var btn = e.submitter || f.querySelector('button:not([type=button])');
    f.dataset.sending = '1';
    f.dataset.dirty = '';
    if (btn) { btn.classList.add('is-busy'); setTimeout(function () { btn.disabled = true; }, 0); }
    /* 뒤로 가기로 돌아왔거나 응답이 늦을 때 다시 누를 수 있게 */
    setTimeout(function () { f.dataset.sending = ''; if (btn) { btn.disabled = false; btn.classList.remove('is-busy'); } }, 8000);
  });
  window.addEventListener('pageshow', function (e) {
    if (!e.persisted) return;
    $$('form[data-sending="1"]').forEach(function (f) { f.dataset.sending = ''; });
    $$('button.is-busy').forEach(function (b) { b.disabled = false; b.classList.remove('is-busy'); });
  });

  document.addEventListener('change', function (e) {
    var t = e.target;
    if (t.matches && t.matches('[data-checkall]')) {
      var sec = t.closest('.iv-sec') || document;
      $$('input[type=checkbox][name="' + t.dataset.checkall + '"]', sec).forEach(function (c) { c.checked = t.checked; });
      needCheck();
    }
    if (t.matches && t.matches('input[type=checkbox][name$="ids[]"]')) needCheck(); /* v9.29 · ids[] · oids[] · rids[] */
    if (t.form && t.form.hasAttribute('data-autosubmit') && t.tagName === 'SELECT') {
      if (t.matches('[data-toggle-custom]')) {
        var cus = t.value === 'c';
        $$('[data-custom]', t.form).forEach(function (el) { el.hidden = !cus; });
        if (cus) return;
      }
      t.form.submit();
    }
    if (t.form && t.form.hasAttribute('data-dirtywarn')) t.form.dataset.dirty = '1';
  });
  function needCheck() {
    $$('[data-needcheck]').forEach(function (b) {
      var sec = b.closest('.iv-sec') || document;
      var n = $$('input[type=checkbox][name="' + b.dataset.needcheck + '"]:checked', sec).length;
      b.disabled = n === 0;
      b.textContent = b.textContent.replace(/\s*\(\d+\)$/, '') + (n ? ' (' + n + ')' : '');
    });
  }
  needCheck();
  window.addEventListener('beforeunload', function (e) {
    if ($$('form[data-dirtywarn]').some(function (f) { return f.dataset.dirty === '1'; })) { e.preventDefault(); e.returnValue = ''; }
  });
  document.addEventListener('input', function (e) {
    var t = e.target;
    if (t.form && t.form.hasAttribute('data-dirtywarn')) t.form.dataset.dirty = '1';
    if (t.matches('[data-book]')) countSum();
  });

  /* 실사 모드 — 장부와 다른 칸 수 */
  function countSum() {
    var el = $('#iv-count-sum'); if (!el) return;
    var diff = 0, filled = 0;
    $$('[data-book]').forEach(function (i) {
      if (i.value === '') { i.classList.remove('is-diff'); return; }
      filled++;
      var d = Number(i.value) !== Number(i.dataset.book);
      i.classList.toggle('is-diff', d);
      if (d) diff++;
    });
    el.textContent = filled ? ('적은 ' + filled + '개 중 장부와 다른 것 ' + diff + '개') : '';
  }

  /* ---------------------------------------------------------
   * 품목 고르기 (datalist → 숨은 번호 칸)
   * ------------------------------------------------------- */
  document.addEventListener('input', function (e) {
    var t = e.target;
    if (!t.matches('[data-pick]')) return;
    var hid = t.form && t.form.querySelector('input[type=hidden][name="' + t.dataset.pick + '"]');
    if (!hid) return;
    var m = /#(\d+)\s*$/.exec(t.value);
    var ok = false;
    if (m) { var dl = document.getElementById(t.getAttribute('list')); ok = !!(dl && $$('option', dl).some(function (o) { return o.value === t.value; })); }
    hid.value = ok ? m[1] : '';
    t.setCustomValidity(ok || (!t.required && t.value === '') ? '' : '목록에서 품목을 골라 주세요');
  });

  /* ---------------------------------------------------------
   * 분류 3단계 — 위를 고르면 아래가 좁혀진다
   * ------------------------------------------------------- */
  function catselInit(box) {
    var sel = {};
    $$('select[data-level]', box).forEach(function (s) { sel[s.dataset.level] = s; });
    function filter(level) {
      var s = sel[level]; if (!s) return;
      var parent = sel[level - 1] ? Number(sel[level - 1].value) : 0;
      $$('option', s).forEach(function (o) {
        if (!o.dataset.p) return;
        var show = !parent || Number(o.dataset.p) === parent;
        o.hidden = !show; o.disabled = !show;
      });
      var cur = s.selectedOptions[0];
      if (cur && cur.disabled) s.value = '0';
    }
    if (!box.dataset.bound) {
      box.dataset.bound = '1';
      [1, 2].forEach(function (lv) { if (sel[lv]) sel[lv].addEventListener('change', function () { filter(lv + 1); filter(lv + 2); }); });
      /* 아래를 먼저 고르면 위를 맞춘다 */
      [3, 2].forEach(function (lv) {
        if (!sel[lv]) return;
        sel[lv].addEventListener('change', function () {
          var o = sel[lv].selectedOptions[0];
          if (o && o.dataset.p && sel[lv - 1]) { sel[lv - 1].value = o.dataset.p; if (lv === 3 && sel[2].selectedOptions[0] && sel[1]) sel[1].value = sel[2].selectedOptions[0].dataset.p || sel[1].value; }
        });
      });
    }
    [1, 2, 3].forEach(function (lv) { if (sel[lv] && sel[lv].dataset.value) sel[lv].value = sel[lv].dataset.value; });
    filter(2); filter(3);
  }
  $$('.iv-catsel').forEach(catselInit);

  /* 주문: 수량 × 단가 = 금액 안내 */
  function calcInit(d) {
    var q = d.querySelector('[data-calc=qty]'), p = d.querySelector('[data-calc=price]'), a = d.querySelector('[data-calc=amount]');
    if (!q || !p || !a) return;
    var upd = function () {
      var v = (Number(q.value) || 0) * (Number(String(p.value).replace(/[^\d]/g, '')) || 0);
      a.placeholder = v ? num(v) + ' (수량 × 단가)' : '';
    };
    if (!d.dataset.calcBound) { d.dataset.calcBound = '1'; q.addEventListener('input', upd); p.addEventListener('input', upd); }
    upd();
  }

  /* v6.5 · 거래명세서 칸 — 총 수량 · 무상 · 단가 · 실제로 낸 금액(주문은 합계).
   * 낸 금액은 손대기 전까지 자동(유상 × 단가, 주문의 남은 수량이 다 오면 주문 합계의 남은 금액), 배송비 등 · 실제 개당 금액을 바로 보여 준다.
   * 주문보다 많이(무상 빼고) 들어오면 보내기 전에 한 번 묻는다. */
  function moneyVal(v) { var s = String(v == null ? '' : v).replace(/[,\s원₩]/g, ''); if (s === '') return null; return /^-?\d+$/.test(s) ? Number(s) : NaN; }
  function rcptInit(box, reset) {
    var f = box.closest('form'); if (!f) return;
    var g = function (k) { return box.querySelector('[data-r=' + k + ']'); };
    var q = g('qty'), fr = g('free'), p = g('price'), t = g('total'), sum = g('sum');
    if (!q || !fr || !p || !t || !sum) return;
    var mode = box.dataset.rcpt;
    var hv = function (n) { var el = f.querySelector('[name="' + n + '"]'); return el && el.value !== '' ? Number(el.value) : null; };
    var ordInfo = function () {
      if (mode === 'order') return null;
      var r = f.querySelector('input[name=ord_id]:checked');
      if (r && r.dataset.left !== undefined) return r.value === '0' ? null : { left: Number(r.dataset.left), price: Number(r.dataset.price), amt: r.dataset.amtLeft === '' ? null : Number(r.dataset.amtLeft) };
      var l = hv('ord_left'); if (l === null) return null;
      return { left: l, price: hv('ord_price'), amt: hv('ord_amt_left') };
    };
    if (reset) {
      t.dataset.touched = ''; t.dataset.extra0 = '';
      if (mode === 'order') { var p0 = moneyVal(p.value), t0 = moneyVal(t.value); if (p0 !== null && !isNaN(p0) && t0 !== null && !isNaN(t0)) t.dataset.extra0 = String(t0 - (Number(q.value) || 0) * p0); }
      else if (t.value !== '') t.dataset.touched = '1';
    }
    var calc = function () {
      var qty = Number(q.value) || 0, free = Math.min(qty, Math.max(0, Number(fr.value) || 0));
      var paid = mode === 'order' ? qty : qty - free;
      var price = moneyVal(p.value);
      if (price !== null && isNaN(price)) { sum.textContent = '단가는 원 단위 숫자로만 적어 주세요 (소수점 · 글자 없이).'; sum.className = 'iv-rcpt__sum is-bad'; return; }
      price = price || 0;
      var goods = paid * price, o = ordInfo(), auto;
      if (mode === 'order') auto = goods + (Number(t.dataset.extra0) || 0);
      else if (o && paid === o.left && price === o.price && o.amt !== null) auto = o.amt;
      else auto = goods;
      if (!t.dataset.touched) t.value = qty ? String(auto) : '';
      var total = moneyVal(t.value);
      if (total !== null && isNaN(total)) { sum.textContent = '금액은 원 단위 숫자로만 적어 주세요 (소수점 · 글자 없이).'; sum.className = 'iv-rcpt__sum is-bad'; return; }
      if (total === null) total = auto;
      var extra = total - goods, parts = [];
      if (!qty) { sum.textContent = ''; return; }
      if (mode === 'order') {
        parts.push(qty + '개 × ' + num(price) + '원 = ' + num(goods) + '원');
        if (free) parts.push('무상 ' + free + '개 더 받기로');
        if (extra) parts.push((extra > 0 ? '배송비 등 +' : '할인 −') + num(Math.abs(extra)) + '원');
        parts.push('주문 합계 ' + num(total) + '원');
      } else {
        parts.push(paid ? '유상 ' + paid + '개 × ' + num(price) + '원 = ' + num(goods) + '원' : '전부 무상');
        if (free && paid) parts.push('무상 ' + free + '개');
        if (extra) parts.push((extra > 0 ? '배송비 등 +' : '할인 −') + num(Math.abs(extra)) + '원');
        parts.push('낸 금액 ' + num(total) + '원');
        parts.push('실제 개당 ' + num(Math.round(total / qty)) + '원' + (free ? ' (무상 포함 ' + qty + '개로 나눔)' : ''));
        if (o && paid > o.left) parts.push('⚠ 주문보다 ' + (paid - o.left) + '개 많음');
      }
      sum.textContent = parts.join(' · ');
      sum.className = 'iv-rcpt__sum' + (o && paid > o.left ? ' is-warn' : '');
    };
    if (!box.dataset.bound) {
      box.dataset.bound = '1';
      t.addEventListener('input', function () { t.dataset.touched = t.value.trim() === '' ? '' : '1'; calc(); });
      [q, fr, p].forEach(function (el) { el.addEventListener('input', calc); el.addEventListener('change', calc); });
      $$('input[name=ord_id]', f).forEach(function (r) {
        r.addEventListener('change', function () {
          if (r.value !== '0') { q.value = String(Number(r.dataset.left) + (Number(r.dataset.free) || 0)); p.value = r.dataset.price; fr.value = r.dataset.free || ''; }
          else { var ip = f.querySelector('[name=item_price]'); if (ip) p.value = ip.value; fr.value = ''; }
          t.dataset.touched = ''; calc();
        });
      });
      f.addEventListener('submit', function (e) {
        var am = f.querySelector('[name=allow_more]'); if (am) am.value = '';
        var o = ordInfo(); if (!o) return;
        var qty = Number(q.value) || 0, paid = qty - Math.min(qty, Math.max(0, Number(fr.value) || 0));
        if (paid > o.left) {
          if (!window.confirm('주문보다 ' + (paid - o.left) + '개 많이 들어왔습니다 (무상 빼고). 그대로 입고할까요?')) { e.preventDefault(); e.stopImmediatePropagation(); return; }
          if (am) am.value = '1';
        }
      }, true);
    }
    calc();
  }
  $$('.iv-rcpt').forEach(function (r) { if (!r.closest('dialog')) rcptInit(r, true); });

  /* v6.5 · 반품 — 돌려받는 금액은 손대기 전까지 (남은 금액 ÷ 남은 수량) × 반품 수량, 마지막이면 남은 금액 전부 */
  function retInit(d) {
    var q = d.querySelector('[data-ret=qty]'), a = d.querySelector('[data-ret=amount]');
    if (!q || !a) return;
    var f = q.form, hv = function (n) { var el = f.querySelector('[name="' + n + '"]'); return el && el.value !== '' ? Number(el.value) : null; };
    a.dataset.touched = '';
    var calc = function () {
      if (a.dataset.touched) return;
      var n = Number(q.value) || 0, N = hv('ret_net'), U = hv('ret_units');
      if (N === null || !U) return;
      a.value = String(n >= U ? N : Math.round(N * n / U));
    };
    if (!d.dataset.retBound) { d.dataset.retBound = '1'; q.addEventListener('input', calc); a.addEventListener('input', function () { a.dataset.touched = a.value.trim() === '' ? '' : '1'; }); }
    calc();
  }

  /* ---------------------------------------------------------
   * 필터: 검색어는 엔터, 날짜는 바뀌면 바로
   * ------------------------------------------------------- */
  $$('form[data-autosubmit] input[type=date]').forEach(function (i) { i.addEventListener('change', function () { i.form.submit(); }); });

  /* 내역: 방금 보낸 장바구니는 비운다 (서버가 받았다고 확인한 뒤에만) */
  var sent = $('#iv-sent-ok');
  if (sent) {
    var cart = store.get('md_inv_cart_v1', null);
    if (cart && cart.tok === sent.dataset.tok) store.del('md_inv_cart_v1');
  }

  /* ---------------------------------------------------------
   * 바코드 스캔 (카메라) — BarcodeDetector, 없으면 ZXing 을 불러 쓴다
   * ------------------------------------------------------- */
  var scanner = { stream: null, timer: null, zx: null, cb: null, run: 0 };
  function stopScan() {
    scanner.run++; /* 늦게 도착한 카메라는 받자마자 끈다 */
    if (scanner.timer) { clearInterval(scanner.timer); scanner.timer = null; }
    if (scanner.zx) { try { scanner.zx.stop(); } catch (e) {} scanner.zx = null; }
    if (scanner.stream) { scanner.stream.getTracks().forEach(function (t) { t.stop(); }); scanner.stream = null; }
  }
  function loadZXing() {
    return new Promise(function (res, rej) {
      if (window.ZXingBrowser) return res(window.ZXingBrowser);
      var s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/@zxing/browser@0.1.5/umd/zxing-browser.min.js';
      s.onload = function () { window.ZXingBrowser ? res(window.ZXingBrowser) : rej(); };
      s.onerror = rej;
      document.head.appendChild(s);
    });
  }
  /* 카메라가 안 열린 이유를 사람이 알아들을 말로 */
  function camBlockedByPage() {
    try {
      var pp = document.permissionsPolicy || document.featurePolicy;
      return !!(pp && pp.allowsFeature && !pp.allowsFeature('camera'));
    } catch (e) { return false; }
  }
  function camError(err) {
    var n = err && err.name ? err.name : '';
    if (!window.isSecureContext) return '보안 연결(https)에서만 카메라를 쓸 수 있습니다. 아래에 숫자를 직접 입력해 주세요.';
    if (camBlockedByPage()) return '홈페이지 보안 설정이 카메라를 막고 있습니다 (관리자에게 알려 주세요). 아래에 숫자를 직접 입력해 주세요.';
    if (n === 'NotAllowedError' || n === 'SecurityError') return '카메라 권한이 꺼져 있습니다. 주소창 왼쪽 🔒(또는 ⓘ) › 권한 › 카메라를 「허용」으로 바꾼 뒤 다시 눌러 주세요. 아래에 숫자를 직접 입력해도 됩니다.';
    if (n === 'NotFoundError' || n === 'OverconstrainedError') return '이 기기에서 카메라를 찾지 못했습니다. 아래에 숫자를 직접 입력해 주세요.';
    if (n === 'NotReadableError' || n === 'AbortError') return '다른 앱이 카메라를 쓰고 있습니다. 카메라 앱 · 화상 통화를 닫고 다시 눌러 주세요.';
    return '카메라를 열지 못했습니다. 아래에 숫자를 직접 입력해 주세요.';
  }
  function getCam() {
    /* 뒤 카메라를 먼저, 안 되면 아무 카메라나 */
    var want = { video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false };
    return navigator.mediaDevices.getUserMedia(want).catch(function (e) {
      if (e && (e.name === 'OverconstrainedError' || e.name === 'NotFoundError')) return navigator.mediaDevices.getUserMedia({ video: true, audio: false });
      throw e;
    });
  }
  function nativeDetector() {
    /* 안드로이드 크롬에도 BarcodeDetector 만 있고 읽을 수 있는 형식이 없는 기기가 있다 → 그땐 ZXing */
    if (!('BarcodeDetector' in window) || !window.BarcodeDetector.getSupportedFormats) return Promise.resolve(null);
    return window.BarcodeDetector.getSupportedFormats().then(function (f) {
      var want = ['ean_13', 'ean_8', 'code_128', 'code_39', 'upc_a', 'upc_e', 'itf', 'qr_code', 'data_matrix', 'codabar'].filter(function (x) { return f.indexOf(x) >= 0; });
      return want.length ? new window.BarcodeDetector({ formats: want }) : null;
    }).catch(function () { return null; });
  }
  function startScan(cb) {
    var dlg = openDlg('iv-scan-dlg');
    if (!dlg) { var v = window.prompt('바코드 숫자를 입력하세요'); if (v) cb(v.trim()); return; }
    stopScan();
    var run = scanner.run;
    scanner.cb = cb;
    var video = $('#iv-scan-video'), msg = $('#iv-scan-msg'), manual = $('#iv-scan-manual');
    manual.value = '';
    dlg.classList.remove('is-live');
    var done = function (code) { if (!code) return; stopScan(); closeDlg(dlg); if (navigator.vibrate) navigator.vibrate(60); cb(String(code).trim()); };
    manual.onkeydown = function (e) { if (e.key === 'Enter') { e.preventDefault(); done(manual.value); } };
    var okb = $('#iv-scan-ok'); if (okb) okb.onclick = function () { if (manual.value.trim()) done(manual.value); else manual.focus(); };
    if (!dlg.dataset.bound) { dlg.dataset.bound = '1'; dlg.addEventListener('close', stopScan); }
    var fail = function (e) { if (run !== scanner.run) return; msg.textContent = camError(e); msg.classList.add('is-err'); dlg.dataset.camErr = (e && e.name) || 'x'; try { manual.focus(); } catch (x) {} };
    msg.classList.remove('is-err'); delete dlg.dataset.camErr;
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { fail({ name: window.isSecureContext ? 'NotFoundError' : 'SecurityError' }); return; }
    if (camBlockedByPage()) { fail({ name: 'SecurityError' }); return; }
    msg.textContent = '카메라를 여는 중…';
    var live = function () { if (run !== scanner.run) return; dlg.classList.add('is-live'); msg.textContent = '바코드를 네모 안에 맞춰 주세요. 잘 안 읽히면 조금 떨어뜨려 보세요.'; };
    nativeDetector().then(function (det) {
      if (run !== scanner.run) return;
      if (det) {
        getCam().then(function (st) {
          if (run !== scanner.run) { st.getTracks().forEach(function (t) { t.stop(); }); return; }
          scanner.stream = st; video.srcObject = st;
          var pl = video.play(); if (pl && pl.catch) pl.catch(function () {});
          live();
          var busy = false;
          scanner.timer = setInterval(function () {
            if (busy || video.readyState < 2) return;
            busy = true;
            det.detect(video).then(function (r) { busy = false; if (r && r.length) done(r[0].rawValue); }).catch(function () { busy = false; });
          }, 200);
        }).catch(fail);
        return;
      }
      msg.textContent = '스캐너를 불러오는 중…';
      loadZXing().then(function (ZX) {
        if (run !== scanner.run) return;
        var reader = new ZX.BrowserMultiFormatReader();
        reader.decodeFromConstraints({ video: { facingMode: { ideal: 'environment' } }, audio: false }, video, function (r) { if (r) done(r.getText()); })
          .then(function (ctrl) { if (run !== scanner.run) { ctrl.stop(); return; } scanner.zx = ctrl; live(); })
          .catch(fail);
      }).catch(function () { if (run === scanner.run) { msg.textContent = '스캐너를 불러오지 못했습니다 (인터넷 연결 확인). 아래에 숫자를 직접 입력해 주세요.'; msg.classList.add('is-err'); } });
    });
  }
  /* 품목 양식의 바코드 칸 옆 스캔 버튼 */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-scanto]');
    if (!b) return;
    e.preventDefault();
    var inp = b.closest('form').querySelector('[name="' + b.dataset.scanto + '"]');
    if (!$('#iv-scan-dlg')) { var v = window.prompt('바코드 숫자를 입력하세요'); if (v && inp) inp.value = v.trim(); return; }
    var host = b.closest('dialog');
    if (host) closeDlg(host);
    startScan(function (code) {
      if (inp) {
        /* LOT 칸에 GS1 바코드(상자의 2D 코드)를 찍으면 LOT 만 꺼내 넣는다 */
        var g = inp.name === 'lot' ? gs1(code) : null;
        inp.value = g && g['10'] ? g['10'] : code;
        if (g && host) verifyShow(host, g, code);
      }
      if (host) openDlg(host.id);
    });
  });

  /* ---------------------------------------------------------
   * v6.1 · GS1 바코드 읽기 — (01) 제품번호 · (10) LOT · (17) 유효기간 · (21) 일련번호
   *   2D 코드는 칸 사이를 GS(\x1d) 로 나눈다. 사람이 읽는 (01)…(10)… 꼴도 받는다.
   * ------------------------------------------------------- */
  function gs1(raw) {
    var s = String(raw || '').replace(/^\][A-Za-z]\d/, '').replace(/\u001d$/, '');
    var out = {}, m;
    if (/\(\d{2,4}\)/.test(s)) {
      var re = /\((\d{2,4})\)([^(]*)/g;
      while ((m = re.exec(s))) out[m[1]] = m[2].replace(/\u001d/g, '').trim();
      return out['01'] || out['10'] ? out : null;
    }
    if (!/^(01|02)\d{14}/.test(s)) return null;
    var fixed = { '00': 18, '01': 14, '02': 14, '11': 6, '12': 6, '13': 6, '15': 6, '16': 6, '17': 6, '20': 2 };
    var vari = { '10': 1, '21': 1, '22': 1, '30': 1, '37': 1, '90': 1, '91': 1, '92': 1 };
    var i = 0, guard = 0;
    while (i < s.length && guard++ < 20) {
      if (s.charAt(i) === '\u001d') { i++; continue; }
      var ai = s.substr(i, 2);
      if (fixed[ai]) { out[ai] = s.substr(i + 2, fixed[ai]); i += 2 + fixed[ai]; continue; }
      if (vari[ai]) { var j = s.indexOf('\u001d', i + 2); if (j < 0) j = s.length; out[ai] = s.substring(i + 2, j); i = j; continue; }
      if (/^24[01]$/.test(s.substr(i, 3))) { var k = s.indexOf('\u001d', i + 3); if (k < 0) k = s.length; out[s.substr(i, 3)] = s.substring(i + 3, k); i = k; continue; }
      break;
    }
    return out['01'] ? out : null;
  }
  window.mdInvGs1 = gs1; /* 시험용 */
  function bcSame(want, code, g) {
    var w = String(want || '').replace(/\s/g, ''), c = String(code || '').replace(/\s/g, '');
    if (!w) return null;
    if (w === c) return true;
    var gt = g && g['01'] ? g['01'] : '';
    if (gt) { if (gt === w || gt.slice(1) === w || gt.slice(-13) === w.slice(-13) || gt === ('0' + w)) return true; }
    if (/^\d+$/.test(c) && /^\d+$/.test(w) && c.length >= 8 && (c.slice(-13) === w.slice(-13))) return true;
    return false;
  }
  var bcMap = null;
  function dlgItem(d) {
    var ref = d.querySelector('[name="item_ref"]'), hi = d.querySelector('input[type=hidden][name="item_id"]'), pk = d.querySelector('[data-pick="item_id"]');
    if (ref && ref.value) return ref.value;
    if (hi && hi.value) return hi.value;
    if (pk) { var m = /#(\d+)\s*$/.exec(pk.value || ''); if (m) return m[1]; }
    return '';
  }
  function bcOwner(code, g) {
    var name = '';
    Object.keys(bcMap || {}).some(function (k) { if (bcSame(bcMap[k][0], code, g)) { name = bcMap[k][1]; return true; } return false; });
    return name;
  }
  function verifyShow(d, g, code) {
    var msg = d.querySelector('[data-verify-msg]');
    if (!msg) return;
    if (bcMap === null) { var el = document.getElementById('iv-bc-map'); try { bcMap = el ? JSON.parse(el.textContent) : {}; } catch (x) { bcMap = {}; } }
    var id = dlgItem(d), want = id && bcMap[id] ? bcMap[id][0] : '';
    var ok = bcSame(want, code, g);
    var extra = [];
    if (g && g['10']) {
      var lot = d.querySelector('[name="lot"]');
      if (lot && !lot.value) lot.value = g['10'];
      extra.push('LOT ' + g['10']);
    }
    if (g && g['17']) extra.push('유효기간 20' + g['17'].slice(0, 2) + '-' + g['17'].slice(2, 4) + (g['17'].slice(4) !== '00' ? '-' + g['17'].slice(4) : ''));
    var form = d.querySelector('form');
    var shown = g && g['01'] ? g['01'] : code;
    if (ok === null) {
      /* 이 품목엔 바코드가 등록돼 있지 않다 — 다른 품목 바코드인지라도 본다 */
      var other = bcOwner(code, g);
      msg.className = 'iv-verify__msg ' + (other ? 'is-bad' : 'is-warn');
      msg.textContent = other ? '✕ 다른 품목 바코드입니다: ' + other + ' — 물건을 다시 확인해 주세요.' : '이 품목엔 바코드가 등록돼 있지 않아 확인할 수 없습니다 (찍은 값 ' + shown + ')' + (extra.length ? ' · ' + extra.join(' · ') : '');
      if (form) form.dataset.verifyBad = other ? '1' : '';
      return;
    }
    if (ok) {
      msg.className = 'iv-verify__msg is-ok';
      msg.textContent = '✓ 맞는 품목입니다' + (extra.length ? ' · ' + extra.join(' · ') : '');
      if (form) form.dataset.verifyBad = '';
    } else {
      var name = bcOwner(code, g);
      msg.className = 'iv-verify__msg is-bad';
      msg.textContent = '✕ 다른 품목입니다' + (name ? ': ' + name : ' (찍은 값 ' + shown + ')') + ' — 물건을 다시 확인해 주세요.';
      if (form) form.dataset.verifyBad = '1';
    }
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-verify]');
    if (!b) return;
    e.preventDefault();
    var host = b.closest('dialog');
    if (!$('#iv-scan-dlg')) { var v = window.prompt('바코드를 찍거나 숫자를 입력하세요'); if (v && host) verifyShow(host, gs1(v), v.trim()); return; }
    if (host) closeDlg(host);
    startScan(function (code) { if (host) { openDlg(host.id); verifyShow(host, gs1(code), code); } });
  });
  /* 다른 품목으로 확인된 채 처리하려 하면 한 번 더 묻는다 */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f && f.dataset && f.dataset.verifyBad === '1' && !window.confirm('바코드가 다른 품목으로 확인됐습니다. 그래도 처리할까요?')) { e.preventDefault(); e.stopImmediatePropagation(); }
  }, true);

  /* ---------------------------------------------------------
   * 품목신청 화면
   * ------------------------------------------------------- */
  var root = $('#iv-req');
  var dataEl = $('#iv-req-data');
  if (root && dataEl) reqApp(JSON.parse(dataEl.textContent));

  function reqApp(D) {
    var KEY = 'md_inv_cart_v1', WHO = 'md_inv_who_v1', PAGE = 150;
    var O = D.opt;
    var byId = {}, catName = {}, catSort = {}, catParent = {}, vendors = D.vendors || {}, teamName = {};
    D.items.forEach(function (it) { byId[it.i] = it; });
    D.cats.forEach(function (c, i) { catName[c.id] = c.n; catSort[c.id] = i; catParent[c.id] = c.p; });
    D.teams.forEach(function (t) { teamName[t.id] = t.n; });
    var cart = store.get(KEY, null);
    if (!cart || !Array.isArray(cart.lines)) cart = { lines: [], tok: '' };
    cart.lines = cart.lines.filter(function (l) { return l.custom || byId[l.id]; });
    var who = O.remember ? store.get(WHO, {}) : {};
    var state = { q: '', chip: '', sub: '', all: false, path: [], showAll: false, team: teamName[who.team] ? Number(who.team) : (teamName[D.myTeam] ? Number(D.myTeam) : 0), shown: PAGE };
    var favs = D.favs || {};
    function favList() { return state.team && favs[state.team] ? favs[state.team] : []; }
    function isFav(id) { return favList().indexOf(id) >= 0; }

    var list = $('#iv-list'), q = $('#iv-q'), chipsEl = $('#iv-chips'), subEl = $('#iv-subchips');

    function save() { store.set(KEY, cart); renderBar(); steps(); }
    function lineOf(id) { for (var i = 0; i < cart.lines.length; i++) if (!cart.lines[i].custom && cart.lines[i].id === id) return cart.lines[i]; return null; }
    function qtyOf(id) { var l = lineOf(id); return l ? l.qty : 0; }
    function add(id, n) {
      if (!byId[id]) return;
      var l = lineOf(id);
      if (!l) { l = { id: id, qty: 0 }; cart.lines.push(l); }
      l.qty = Math.max(0, Math.min(O.max, l.qty + n));
      if (!l.qty) cart.lines.splice(cart.lines.indexOf(l), 1);
      cart.tok = '';
      save(); updateRow(id); renderChips();
    }

    /* 1 · 팀 */
    function setTeam(id, silent) {
      state.team = teamName[id] ? Number(id) : 0;
      $('#iv-team').value = state.team || '';
      $('#iv-team-label').textContent = state.team ? teamName[state.team] : '팀을 골라 주세요';
      $('#iv-teambtn').classList.toggle('is-empty', !state.team);
      $$('#iv-teamgrid [data-team]').forEach(function (b) { b.classList.toggle('is-on', Number(b.dataset.team) === state.team); });
      var ct = $('#iv-cart-team'); if (ct && state.team) ct.value = String(state.team);
      if (O.remember) { who.team = state.team; store.set(WHO, who); }
      if (!silent) { if (favList().length) state.chip = 'fav'; else if (state.chip === 'fav') state.chip = 'browse'; state.showAll = false; state.shown = PAGE; renderChips(); render(); }
      steps();
    }
    $('#iv-teamgrid').addEventListener('click', function (e) {
      var b = e.target.closest('[data-team]'); if (!b) return;
      setTeam(Number(b.dataset.team));
      $('#iv-teambtn').classList.remove('is-pulse');
      closeDlg($('#iv-team-dlg'));
      toast('「' + teamName[state.team] + '」 팀으로 신청합니다');
    });

    function steps() {
      var s1 = !!state.team, s2 = cart.lines.length > 0;
      $('#iv-step1').className = s1 ? 'is-done' : 'is-now';
      $('#iv-step2').className = s2 ? 'is-done' : (s1 ? 'is-now' : '');
      $('#iv-step3').className = s1 && s2 ? 'is-now' : '';
    }

    /* 2 · 칩 — 「우리 팀 즐겨찾기 · 분류별로 고르기 · 전체」만 (v6.5 · 원장 지시)
     *   분류별로 고르기: 결제 방식(건별결제 · 선납차감) → 품목군 → 세부 분류 → 품목. 빈 분류도 보여 준다 (0개). */
    function recentOf() {
      return state.team && D.recent[state.team] ? D.recent[state.team].map(function (id) { return byId[id]; }).filter(Boolean) : [];
    }
    var kids = {};
    D.cats.forEach(function (c) { (kids[c.p] = kids[c.p] || []).push(c.id); });
    var LV = ['c1', 'c2', 'c3'];
    /* 지금 경로(state.path = [결제 방식, 품목군, 세부 분류] — 값은 분류 id 또는 'none')에 드는 품목 */
    function pathItems(path) {
      return D.items.filter(function (it) {
        for (var i = 0; i < path.length; i++) {
          var v = it[LV[i]], want = path[i];
          if (want === 'none') { if (v && catName[v]) return false; }
          else if (Number(v) !== Number(want)) return false;
        }
        return true;
      });
    }
    function nodeName(v) { return v === 'none' ? '기타 (분류 없음)' : (catName[v] || '기타'); }
    function isGroup() { return false; } /* 옛 품목군 칩 — 이제 없음 */
    function scoped() { return state.chip === 'browse' && state.path.length > 0 && !state.all; }
    function scopeLabel() { return state.path.map(nodeName).join(' › '); }
    /* 이 경로 아래 고를 타일 — 하위 분류(빈 것 포함) + 하위 분류가 없는 품목이 있으면 「기타」 */
    function tilesAt(path) {
      var lv = path.length;
      if (lv >= 3) return [];
      var parent = lv ? path[lv - 1] : 0;
      if (parent === 'none') return [];
      var base = lv ? pathItems(path) : D.items;
      var ids = (kids[parent] || []).filter(function (id) { return (D.cats.filter(function (c) { return c.id === id; })[0] || {}).l === lv + 1; });
      var out = ids.map(function (id) { return { v: id, n: catName[id], items: base.filter(function (it) { return Number(it[LV[lv]]) === id; }) }; });
      var none = base.filter(function (it) { var v = it[LV[lv]]; return !v || !catName[v]; });
      if (none.length && out.length) out.push({ v: 'none', n: '기타 (분류 없음)', items: none });
      return out;
    }
    function itemsOfChip(chip) {
      if (chip === 'fav') return favList().map(function (id) { return byId[id]; }).filter(Boolean);
      if (chip === 'browse') return state.path.length ? pathItems(state.path) : D.items;
      return D.items;
    }
    function chipBtn(key, label, n) {
      var on = (!state.q || scoped()) && state.chip === key;
      return '<button type="button" class="iv-chipbtn' + (on ? ' is-on' : '') + '" role="tab" aria-selected="' + on + '" data-chip="' + key + '">' + esc(label) + (n !== '' ? ' <small>' + n + '</small>' : '') + '</button>';
    }
    function renderChips() {
      var h = '';
      var fc = favList().filter(function (id) { return byId[id]; }).length;
      h += chipBtn('fav', '★ 우리 팀 즐겨찾기', state.team ? fc : '');
      h += chipBtn('browse', '📂 분류별로 고르기', '');
      h += chipBtn('all', '전체', D.items.length);
      chipsEl.innerHTML = h;
      if (subEl) { subEl.innerHTML = ''; subEl.hidden = true; }
    }
    function savePath() { store.set('md_inv_path_v1', state.path); }
    chipsEl.addEventListener('click', function (e) {
      var b = e.target.closest('[data-chip]'); if (!b) return;
      if (b.dataset.chip === 'fav' && !state.team) { openDlg('iv-team-dlg'); toast('즐겨찾기는 팀마다 따로 있어요. 먼저 우리 팀을 골라 주세요'); return; }
      /* 「분류별로 고르기」를 다시 누르면 맨 처음 단계로 */
      if (b.dataset.chip === 'browse' && state.chip === 'browse') { state.path = []; savePath(); }
      state.chip = b.dataset.chip; state.all = false; state.showAll = false; state.q = ''; q.value = ''; state.shown = PAGE;
      store.set('md_inv_chip_v1', state.chip);
      renderChips(); render();
      var on = chipsEl.querySelector('.is-on'); if (on && on.scrollIntoView) on.scrollIntoView({ block: 'nearest', inline: 'center' });
    });

    function stockHtml(it) {
      if (!O.stock || it.s === null) return '';
      if (it.s <= 0) return '<span class="iv-badge iv-badge--out">품절' + (it.o ? ' · 주문 중' : '') + '</span>';
      if (it.m && it.s < it.m) return '<span class="iv-badge iv-badge--low">재고 ' + num(it.s) + (it.o ? ' · 주문 중' : '') + '</span>';
      return '<span class="iv-badge iv-badge--ok">재고 ' + num(it.s) + '</span>';
    }
    function hl(text) {
      var out = esc(text);
      if (!state.q) return out;
      state.q.split(/\s+/).filter(Boolean).forEach(function (w) {
        var pat = esc(w).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        out = out.replace(new RegExp('(' + pat + ')', 'ig'), '<mark>$1</mark>');
      });
      return out;
    }
    function rowHtml(it) {
      var v = vendors[it.v] ? vendors[it.v].n : '';
      var pend = state.team && D.pending[state.team] && D.pending[state.team][it.i];
      var showCat = (state.q || state.chip === 'all' || state.chip === 'fav') && it.c2;
      var showSub = state.chip === 'browse' && state.path.length < 3 && it.c3 && catName[it.c3];
      var sub = [v, it.u, (O.price && it.p ? num(it.p) + '원' : ''), showCat ? catName[it.c2] + (it.c3 && catName[it.c3] ? ' › ' + catName[it.c3] : '') : (showSub ? catName[it.c3] : ''), it.l ? '📍' + it.l : ''].filter(Boolean).join(' · ');
      return '<div class="iv-row' + (qtyOf(it.i) ? ' is-in' : '') + '" data-id="' + it.i + '">' +
        '<button type="button" class="iv-row__main" data-add="' + it.i + '"><span class="iv-row__name">' + hl(it.n) + '</span><span class="iv-row__sub">' + esc(sub) + '</span>' +
        (pend ? '<span class="iv-row__dup">우리 팀이 이미 ' + pend + '개 신청해 둠</span>' : '') + '</button>' +
        '<span class="iv-row__stock">' + stockHtml(it) + '</span>' +
        '<span class="iv-row__ctl">' + favBtn(it.i) + ctlHtml(it.i) + '</span></div>';
    }
    function favBtn(id) {
      var on = isFav(id);
      return '<button type="button" class="iv-star' + (on ? ' is-on' : '') + '" data-fav="' + id + '" aria-pressed="' + on + '" aria-label="' + (on ? '즐겨찾기에서 빼기' : '우리 팀 즐겨찾기에 넣기') + '" title="' + (on ? '즐겨찾기에서 빼기' : '우리 팀 즐겨찾기') + '">' + (on ? '★' : '☆') + '</button>';
    }
    function ctlHtml(id) {
      var n = qtyOf(id);
      if (!n) return '<button type="button" class="iv-btn iv-btn--add" data-add="' + id + '">＋ 담기</button>';
      return '<span class="iv-step"><button type="button" data-dec="' + id + '" aria-label="하나 빼기">−</button><input type="number" inputmode="numeric" min="0" max="' + O.max + '" value="' + n + '" data-qty="' + id + '" aria-label="수량"><button type="button" data-inc="' + id + '" aria-label="하나 더">+</button></span>';
    }
    function updateRow(id) {
      $$('.iv-row[data-id="' + id + '"]', list).forEach(function (r) {
        r.classList.toggle('is-in', qtyOf(id) > 0);
        var c = r.querySelector('.iv-row__ctl');
        var focused = document.activeElement && c.contains(document.activeElement) && document.activeElement.tagName === 'INPUT';
        if (!focused) c.innerHTML = favBtn(id) + ctlHtml(id);
      });
    }
    /* 초성: 「ㅇㅋㅅ」 → 「알콜솜」 */
    var CHO = 'ㄱㄲㄴㄷㄸㄹㅁㅂㅃㅅㅆㅇㅈㅉㅊㅋㅌㅍㅎ';
    function cho(s) {
      var o = '';
      for (var i = 0; i < s.length; i++) { var c = s.charCodeAt(i); o += (c >= 0xAC00 && c <= 0xD7A3) ? CHO.charAt(Math.floor((c - 0xAC00) / 588)) : s.charAt(i).toLowerCase(); }
      return o;
    }
    var choCache = {};
    function match(it, words) {
      var hay = (it.n + ' ' + (vendors[it.v] ? vendors[it.v].n : '') + ' ' + (it.b || '') + ' ' + (catName[it.c2] || '') + ' ' + (catName[it.c3] || '')).toLowerCase();
      for (var i = 0; i < words.length; i++) {
        var w = words[i];
        if (/^[ㄱ-ㅎ]+$/.test(w)) {
          var ch = choCache[it.i] || (choCache[it.i] = cho(it.n).replace(/\s+/g, ''));
          if (ch.indexOf(w) < 0) return false;
        } else if (hay.indexOf(w) < 0) return false;
      }
      return true;
    }

    function render() {
      var arr, head = '';
      if (state.q) {
        var words = state.q.toLowerCase().split(/\s+/).filter(Boolean);
        var everywhere = D.items.filter(function (it) { return match(it, words); });
        if (scoped()) {
          arr = pathItems(state.path).filter(function (it) { return match(it, words); });
          head = '<p class="iv-list__count">「' + esc(state.q) + '」 <b>' + esc(scopeLabel()) + '</b> 안에서 <b>' + arr.length + '</b>개' +
            (everywhere.length > arr.length ? ' · <button type="button" class="iv-link" data-scope="all">전체에서 찾기 (' + everywhere.length + '개)</button>' : '') + '</p>';
          if (!arr.length && everywhere.length) {
            list.innerHTML = head + '<div class="iv-nohit"><b>' + esc(scopeLabel()) + ' 안에는 없어요.</b><span>다른 분류에 ' + everywhere.length + '개가 있습니다.</span><button type="button" class="iv-btn iv-btn--primary" data-scope="all">전체에서 찾기 (' + everywhere.length + '개)</button></div>';
            return;
          }
        } else {
          arr = everywhere;
          head = '<p class="iv-list__count">「' + esc(state.q) + '」 찾은 품목 <b>' + arr.length + '</b>개' + (state.chip === 'browse' && state.path.length ? ' · <button type="button" class="iv-link" data-scope="cat">' + esc(scopeLabel()) + ' 안에서만</button>' : '') + '</p>';
        }
        if (!arr.length) {
          list.innerHTML = head + '<div class="iv-nohit"><b>목록에서 찾지 못했어요.</b><span>이름을 줄여 다시 찾아보거나, 목록에 없는 품목으로 신청하세요.</span>' +
            (O.custom ? '<button type="button" class="iv-btn iv-btn--primary" data-custom-from-q>「' + esc(state.q) + '」 목록에 없는 품목으로 신청</button>' : '') + '</div>';
          return;
        }
      } else if (state.chip === 'browse') {
        var tiles = state.showAll ? [] : tilesAt(state.path);
        if (tiles.length) {
          /* 타일 단계 — 결제 방식 → 품목군 → 세부 분류 */
          var what = [D.L.c1 || '결제 방식', D.L.c2 || '품목군', D.L.c3 || '세부 분류'][state.path.length];
          var th = crumbs(what + '을(를) 고르세요') + '<div class="iv-tiles">';
          tiles.forEach(function (t) {
            var n = t.items.length, sub = state.path.length < 2 && t.v !== 'none' ? tilesAt(state.path.concat([t.v])).length : 0;
            th += '<button type="button" class="iv-tile' + (n ? '' : ' iv-tile--empty') + '" data-tile="' + t.v + '"><b>' + esc(t.n) + '</b><small>' + (n ? n + '개' : '아직 품목 없음') + (sub ? ' · ' + sub + '가지' : '') + '</small></button>';
          });
          if (state.path.length) { var allN = pathItems(state.path).length; if (allN) th += '<button type="button" class="iv-tile iv-tile--all" data-tile-all><b>모두 보기</b><small>' + allN + '개</small></button>'; }
          list.innerHTML = th + '</div>';
          return;
        }
        arr = pathItems(state.path);
        head = crumbs('');
        if (!arr.length) {
          list.innerHTML = head + '<div class="iv-nohit"><b>이 분류에는 아직 품목이 없어요.</b><span>필요한 품목은 목록에 없는 품목으로 신청하세요.</span>' +
            (O.custom ? '<button type="button" class="iv-btn iv-btn--primary" data-dlg="iv-custom">＋ 목록에 없는 품목 신청</button>' : '') + '</div>';
          return;
        }
      } else {
        arr = itemsOfChip(state.chip || 'all');
        if (state.chip === 'fav') {
          if (!state.team) head = '<div class="iv-hint">즐겨찾기는 팀마다 따로 있어요. 먼저 <button type="button" class="iv-link" data-dlg="iv-team-dlg">우리 팀</button>을 골라 주세요.</div>';
          else if (!arr.length) head = '<div class="iv-hint">아직 우리 팀 즐겨찾기가 없어요. 품목 줄의 ☆ 를 누르면 여기에 모입니다. 「📂 분류별로 고르기」로 찾아 보세요.</div>';
        }
        if (!state.team && state.chip === 'all') head = '<div class="iv-hint">먼저 <button type="button" class="iv-link" data-dlg="iv-team-dlg">우리 팀</button>을 골라 주세요.</div>';
      }
      var h = head;
      arr.slice(0, state.shown).forEach(function (it) { h += rowHtml(it); });
      if (arr.length > state.shown) h += '<button type="button" class="iv-btn iv-btn--ghost iv-more-btn" data-more>' + (arr.length - state.shown) + '개 더 보기</button>';
      if (!arr.length && state.chip !== 'fav') h += '<div class="iv-empty">품목이 없습니다.</div>';
      list.innerHTML = h;
    }

    /* 지금 어디인지 — 누르면 그 단계로 돌아감 */
    function crumbs(ask) {
      var h = '<p class="iv-list__count iv-crumbs">';
      h += state.path.length ? '<button type="button" class="iv-link" data-crumb="0">분류별로</button>' : '<b>분류별로 고르기</b>';
      state.path.forEach(function (v, i) {
        var last = i === state.path.length - 1 && !state.showAll;
        h += ' › ' + (last ? '<b>' + esc(nodeName(v)) + '</b>' : '<button type="button" class="iv-link" data-crumb="' + (i + 1) + '">' + esc(nodeName(v)) + '</button>');
      });
      if (state.showAll) h += ' › <b>모두</b>';
      if (ask) h += ' — ' + esc(ask);
      return h + '</p>';
    }

    var nudged = false;
    function nudgeTeam() { if (nudged) return; nudged = true; toast('담았습니다. 신청 전에 우리 팀을 골라 주세요'); $('#iv-teambtn').classList.add('is-pulse'); }
    var favBusy = false;
    list.addEventListener('click', function (e) {
      var tl = e.target.closest('[data-tile]');
      if (tl) { var v = tl.dataset.tile; state.path = state.path.concat([v === 'none' ? 'none' : Number(v)]); state.showAll = false; state.shown = PAGE; savePath(); renderChips(); render(); list.scrollIntoView({ block: 'start' }); return; }
      if (e.target.closest('[data-tile-all]')) { state.showAll = true; state.shown = PAGE; renderChips(); render(); return; }
      var cr = e.target.closest('[data-crumb]');
      if (cr) { state.path = state.path.slice(0, Number(cr.dataset.crumb)); state.showAll = false; state.shown = PAGE; savePath(); renderChips(); render(); return; }
      var sc = e.target.closest('[data-scope]');
      if (sc) { state.all = sc.dataset.scope === 'all'; state.shown = PAGE; renderChips(); render(); return; }
      var fb = e.target.closest('[data-fav]');
      if (fb) {
        if (!state.team) { openDlg('iv-team-dlg'); toast('즐겨찾기는 팀마다 따로 있어요. 먼저 우리 팀을 골라 주세요'); return; }
        if (favBusy) return; favBusy = true;
        var id = Number(fb.dataset.fav), fd = new FormData();
        fd.append('md_inv', 'fav_toggle'); fd.append('_mdinv', D.favNonce); fd.append('ajax', '1');
        fd.append('team_id', state.team); fd.append('item_id', id); fd.append('back', location.href);
        fetch(location.href, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
          favBusy = false;
          if (!j || !j.ok) { toast((j && j.msg) || '즐겨찾기를 바꾸지 못했습니다'); return; }
          var l = favs[state.team] = favs[state.team] || [];
          var k = l.indexOf(id);
          if (j.on && k < 0) l.push(id);
          if (!j.on && k >= 0) l.splice(k, 1);
          toast(j.on ? '★ 우리 팀 즐겨찾기에 넣었습니다' : '즐겨찾기에서 뺐습니다');
          renderChips();
          if (state.chip === 'fav' && !j.on) render(); else updateRow(id);
        }).catch(function () { favBusy = false; toast('연결이 끊겼습니다. 다시 눌러 주세요'); });
        return;
      }
      var a = e.target.closest('[data-add]'); if (a) { add(Number(a.dataset.add), 1); if (!state.team) nudgeTeam(); return; }
      var i = e.target.closest('[data-inc]'); if (i) { add(Number(i.dataset.inc), 1); return; }
      var d = e.target.closest('[data-dec]'); if (d) { add(Number(d.dataset.dec), -1); return; }
      if (e.target.closest('[data-more]')) { state.shown += PAGE; render(); return; }
      if (e.target.closest('[data-custom-from-q]')) {
        var dlg = openDlg('iv-custom', {});
        if (dlg) dlg.querySelector('[name=name]').value = state.q;
      }
    });
    list.addEventListener('change', function (e) {
      var t = e.target; if (!t.matches('[data-qty]')) return;
      var id = Number(t.dataset.qty), l = lineOf(id);
      var v = Math.max(0, Math.min(O.max, parseInt(t.value, 10) || 0));
      if (l) { l.qty = v; if (!v) cart.lines.splice(cart.lines.indexOf(l), 1); cart.tok = ''; save(); }
      updateRow(id); renderChips();
    });

    var tmr = null;
    q.addEventListener('input', function () { clearTimeout(tmr); tmr = setTimeout(function () { state.q = q.value.trim(); state.shown = PAGE; renderChips(); render(); }, 120); });
    q.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      var v = q.value.trim(); if (!v) return;
      var hit = D.items.filter(function (it) { return it.b && it.b === v; });
      if (hit.length === 1) { add(hit[0].i, 1); q.value = ''; state.q = ''; renderChips(); render(); toast('「' + hit[0].n + '」 담았습니다'); }
      else q.blur();
    });
    $('#iv-scan').addEventListener('click', function () {
      startScan(function (code) {
        var hit = D.items.filter(function (it) { return it.b && it.b === code; });
        if (hit.length) { add(hit[0].i, 1); toast('「' + hit[0].n + '」 담았습니다'); }
        else { q.value = code; state.q = code; renderChips(); render(); toast('등록되지 않은 바코드입니다: ' + code); }
      });
    });

    /* 3 · 장바구니 */
    var bar = $('#iv-cartbar');
    function renderBar() {
      var n = cart.lines.length, total = 0;
      cart.lines.forEach(function (l) { total += l.qty; });
      bar.hidden = n === 0;
      $('#iv-cart-n').textContent = n;
      $('#iv-cart-txt').textContent = n ? n + '개 품목 · 모두 ' + total + '개' : '담은 품목';
      document.body.classList.toggle('iv-has-cart', n > 0);
    }
    var dlg = $('#iv-cart'), lines = $('#iv-cart-lines'), form = $('#iv-cart-form'), err = $('#iv-cart-err');
    function renderCart() {
      if (!cart.lines.length) { lines.innerHTML = '<div class="iv-empty">담은 품목이 없습니다.</div>'; $('#iv-cart-send').textContent = '신청하기'; return; }
      var tm = Number($('#iv-cart-team').value) || 0;
      var h = '';
      cart.lines.forEach(function (l, idx) {
        var it = l.custom ? null : byId[l.id];
        var name = it ? it.n : l.custom.name;
        var warn = [];
        if (it && O.stock && it.s !== null && l.qty > it.s) warn.push(it.s <= 0 ? '지금 재고가 없어요 — 주문 후 나갑니다' : '재고(' + it.s + ')보다 많아요 — 있는 만큼 먼저 나갈 수 있어요');
        if (it && tm && D.pending[tm] && D.pending[tm][it.i]) warn.push('우리 팀이 이미 ' + D.pending[tm][it.i] + '개 신청해 둔 품목이에요');
        var sub = it ? [vendors[it.v] ? vendors[it.v].n : '', it.u].filter(Boolean).join(' · ') : ['목록에 없는 품목', l.custom.vendor, l.custom.unit].filter(Boolean).join(' · ');
        h += '<div class="iv-cline" data-idx="' + idx + '"><div class="iv-cline__main"><b>' + esc(name) + '</b><small>' + esc(sub) + '</small>' +
          warn.map(function (w) { return '<span class="iv-cline__warn">' + esc(w) + '</span>'; }).join('') +
          '<input class="iv-input iv-input--sm" maxlength="200" placeholder="이 품목 메모 (선택)" value="' + esc(l.note || '') + '" data-lnote="' + idx + '">' + photoRow(l, idx) + '</div>' +
          '<span class="iv-step"><button type="button" data-ldec="' + idx + '" aria-label="하나 빼기">−</button><input type="number" inputmode="numeric" min="1" max="' + O.max + '" value="' + l.qty + '" data-lqty="' + idx + '" aria-label="수량"><button type="button" data-linc="' + idx + '" aria-label="하나 더">+</button></span>' +
          '<button type="button" class="iv-x" data-ldel="' + idx + '" aria-label="빼기">×</button></div>';
      });
      lines.innerHTML = h;
      $('#iv-cart-send').textContent = cart.lines.length + '건 신청하기';
    }
    /* v9.3 · 사진 붙이기 — 폰에서 긴 변 1600px JPEG 로 줄여 올림 (보통 200~400KB) */
    function photoRow(l, idx) {
      if (!O.ph) return '';
      var ph = l.ph || [], h = '<div class="iv-cline__ph">';
      ph.forEach(function (p, j) { h += '<span class="iv-ph"><img src="' + esc(p.u) + '" alt=""><button type="button" class="iv-ph__x" data-phdel="' + idx + ':' + j + '" aria-label="사진 빼기">×</button></span>'; });
      if (ph.length < O.ph.max) h += '<label class="iv-ph__add"><input type="file" accept="image/*" data-phadd="' + idx + '" hidden>📷 사진' + (ph.length ? ' 더' : '') + '</label>';
      return h + (l.phBusy ? '<span class="iv-ph__busy">올리는 중…</span>' : '') + '</div>';
    }
    function shrink(file) {
      return new Promise(function (ok) {
        if (!/^image\//.test(file.type) || !window.URL || !document.createElement('canvas').toBlob) return ok(file);
        var url = URL.createObjectURL(file), img = new Image();
        img.onload = function () {
          var w = img.naturalWidth, h = img.naturalHeight, k = Math.min(1, 1600 / Math.max(w, h));
          var c = document.createElement('canvas'); c.width = Math.round(w * k); c.height = Math.round(h * k);
          c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
          URL.revokeObjectURL(url);
          c.toBlob(function (b) { ok(b && b.size < file.size ? b : file); }, 'image/jpeg', 0.8);
        };
        img.onerror = function () { URL.revokeObjectURL(url); ok(file); };
        img.src = url;
      });
    }
    function upPhoto(idx, file) {
      var l = cart.lines[idx]; if (!l) return;
      if (file.size > 30 * 1048576) { alert('사진이 너무 큽니다.'); return; }
      l.phBusy = 1; renderCart();
      shrink(file).then(function (b) {
        if (b.size > 8 * 1048576) throw new Error('사진이 너무 큽니다 (8MB 까지).');
        var f = new FormData(); f.append('action', 'md_inv_photo_up'); f.append('nonce', O.ph.nonce); f.append('photo', b, 'photo.jpg');
        return fetch(O.ph.ajax, { method: 'POST', body: f, credentials: 'same-origin' }).then(function (r) { return r.json(); });
      }).then(function (res) {
        if (!res || !res.ok) throw new Error(res && res.msg || '사진을 올리지 못했습니다.');
        l.ph = (l.ph || []).concat([{ n: res.n, u: res.u }]).slice(0, O.ph.max); cart.tok = '';
      }).catch(function (e) { alert(e.message || '사진을 올리지 못했습니다.'); })
        .then(function () { delete l.phBusy; save(); renderCart(); });
    }
    lines.addEventListener('change', function (e) {
      var t = e.target;
      if (t.dataset.phadd !== undefined && t.files && t.files[0]) upPhoto(Number(t.dataset.phadd), t.files[0]);
    });
    lines.addEventListener('click', function (e) {
      var b = e.target.closest('[data-phdel]'); if (!b) return;
      var a = b.dataset.phdel.split(':'), l = cart.lines[a[0]]; if (!l || !l.ph) return;
      var p = l.ph.splice(Number(a[1]), 1)[0]; cart.tok = ''; save(); renderCart();
      if (p) { var f = new FormData(); f.append('action', 'md_inv_photo_del'); f.append('nonce', O.ph.nonce); f.append('n', p.n); fetch(O.ph.ajax, { method: 'POST', body: f, credentials: 'same-origin' }); }
    });
    function lineChanged(idx) { var l = cart.lines[idx]; cart.tok = ''; save(); if (l && !l.custom) updateRow(l.id); }
    lines.addEventListener('click', function (e) {
      var t = e.target, idx;
      if ((idx = t.dataset.linc) !== undefined) { cart.lines[idx].qty = Math.min(O.max, cart.lines[idx].qty + 1); lineChanged(idx); renderCart(); }
      else if ((idx = t.dataset.ldec) !== undefined) { cart.lines[idx].qty = Math.max(1, cart.lines[idx].qty - 1); lineChanged(idx); renderCart(); }
      else if ((idx = t.dataset.ldel) !== undefined) { var l = cart.lines.splice(Number(idx), 1)[0]; cart.tok = ''; save(); if (l && !l.custom) updateRow(l.id); renderCart(); renderChips(); }
    });
    lines.addEventListener('change', function (e) {
      var t = e.target;
      if (t.dataset.lqty !== undefined) { cart.lines[t.dataset.lqty].qty = Math.max(1, Math.min(O.max, parseInt(t.value, 10) || 1)); lineChanged(t.dataset.lqty); renderCart(); }
      if (t.dataset.lnote !== undefined) { cart.lines[t.dataset.lnote].note = t.value.slice(0, 200); save(); }
    });
    $('#iv-cart-team').addEventListener('change', function () { renderCart(); var v = Number(this.value); if (v && v !== state.team) setTeam(v, true); });
    dlg.addEventListener('close', function () { renderChips(); render(); });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('[data-dlg="iv-cart"]')) return;
      var ct = $('#iv-cart-team'), cn = $('#iv-cart-name');
      if (state.team) ct.value = String(state.team);
      if (D.me) cn.value = D.me; else if (!cn.value && who.name) cn.value = who.name;
      err.hidden = true;
      renderCart();
    });
    $('#iv-cart-clear').addEventListener('click', function () {
      if (!cart.lines.length || !window.confirm('담은 품목을 모두 비울까요?')) return;
      cart.lines = []; cart.tok = ''; save(); renderCart(); renderChips(); render();
    });
    form.addEventListener('submit', function (e) {
      err.hidden = true;
      var tm = $('#iv-cart-team'), nm = $('#iv-cart-name');
      var problem = '';
      if (!cart.lines.length) problem = '담은 품목이 없습니다.';
      else if (!tm.value) problem = '팀을 골라 주세요.';
      else if (O.needName && !nm.value.trim()) problem = '신청자 이름을 적어 주세요.';
      else if (!O.over && cart.lines.some(function (l) { var it = byId[l.id]; return it && it.s !== null && l.qty > it.s; })) problem = '재고보다 많이 담은 품목이 있습니다. 수량을 줄여 주세요.';
      if (problem) { e.preventDefault(); e.stopImmediatePropagation(); err.textContent = problem; err.hidden = false; (tm.value ? nm : tm).focus(); return; }
      if (!cart.tok) { cart.tok = (Date.now().toString(36) + Math.random().toString(36).slice(2, 10)).replace(/[^a-z0-9]/gi, ''); store.set(KEY, cart); }
      $('#iv-cart-tok').value = cart.tok;
      $('#iv-cart-json').value = JSON.stringify(cart.lines.map(function (l) { var o = l.custom ? { custom: l.custom, qty: l.qty, note: l.note || '' } : { id: l.id, qty: l.qty, note: l.note || '' }; if (l.ph && l.ph.length) o.ph = l.ph.map(function (p) { return p.n; }); return o; }));
      if (O.remember) { who.team = Number(tm.value); if (!D.me) who.name = nm.value.trim(); store.set(WHO, who); }
    });

    var cf = $('#iv-custom-form');
    if (cf) cf.addEventListener('submit', function (e) {
      e.preventDefault();
      var g = function (n) { var el = cf.querySelector('[name="' + n + '"]'); return el ? el.value.trim() : ''; };
      if (!g('name')) return;
      cart.lines.push({ custom: { name: g('name'), vendor: g('vendor'), unit: g('unit'), price: g('price'), link: g('link') }, qty: Math.max(1, Math.min(O.max, parseInt(g('qty'), 10) || 1)), note: g('note') });
      cart.tok = ''; save(); cf.reset(); closeDlg(cf.closest('dialog'));
      toast('담았습니다. 아래 「신청하기」를 눌러 보내세요');
    });

    /* 내역 화면의 「다시 담기」로 넘어온 품목 */
    var re = store.get('md_inv_readd_v1', null);
    if (re && Array.isArray(re)) {
      re.forEach(function (r) { if (byId[r.id]) { var l = lineOf(r.id); if (l) l.qty = Math.min(O.max, l.qty + r.qty); else cart.lines.push({ id: r.id, qty: Math.min(O.max, Math.max(1, r.qty)) }); } });
      store.del('md_inv_readd_v1'); cart.tok = ''; store.set(KEY, cart);
      if (re.length) toast('다시 담았습니다. 수량을 확인하고 신청하세요');
    }

    setTeam(state.team, true);
    var savedChip = store.get('md_inv_chip_v1', 'browse');
    state.chip = favList().length ? 'fav' : (savedChip === 'all' ? 'all' : 'browse');
    /* 지난번에 보던 분류 경로 — 지금도 있는 분류만 */
    state.path = (store.get('md_inv_path_v1', []) || []).filter(function (v, i, arr) { return i < 3 && (v === 'none' || catName[v]); }).slice(0, 3);
    renderChips(); render(); renderBar(); steps();
    /* 팀을 한 번도 고르지 않은 기기면 먼저 묻는다 */
    if (!state.team && D.teams.length) setTimeout(function () { openDlg('iv-team-dlg'); }, 250);
  }

  /* 내역: 「다시 담기」 — 품목신청 화면으로 넘어가 장바구니에 넣는다 */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-readd]');
    if (!b) return;
    var cur = store.get('md_inv_readd_v1', []) || [];
    try { cur.push(JSON.parse(b.dataset.readd)); } catch (x) { return; }
    store.set('md_inv_readd_v1', cur);
    window.location.href = b.dataset.href;
  });

  /* 잠깐 뜨는 알림 */
  var toastEl = null, toastT = null;
  function toast(msg) {
    if (!toastEl) { toastEl = document.createElement('div'); toastEl.className = 'iv-toast'; toastEl.setAttribute('role', 'status'); document.body.appendChild(toastEl); }
    toastEl.textContent = msg; toastEl.classList.add('is-on');
    clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('is-on'); }, 2200);
  }

  /* ---------------------------------------------------------
   * v5.5 · 도구 화면
   * ------------------------------------------------------- */
  /* 인쇄 — data-print="구역 id" 면 그 구역만 */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-print]');
    if (!b) return;
    e.preventDefault();
    var id = b.dataset.print;
    if (id) {
      var el = document.getElementById(id);
      if (el) { document.body.classList.add('iv-print-one'); el.classList.add('is-print'); }
    }
    window.print();
    setTimeout(function () {
      document.body.classList.remove('iv-print-one');
      $$('.is-print').forEach(function (x) { x.classList.remove('is-print'); });
    }, 500);
  });

  /* 찍으면 그 바코드로 화면 이동 (바코드 등록 · 휴대폰 실사) */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-scan-go]');
    if (!b) return;
    e.preventDefault();
    var base = b.dataset.scanGo;
    startScan(function (code) {
      if (!code) return;
      var u = new URL(base, window.location.href);
      u.searchParams.set('bc', code);
      u.searchParams.delete('id');
      window.location.href = u.toString();
    });
  });

  /* 정리할 품목 — 고친 줄 표시 · 개수 */
  var fixForm = $('#iv-fix-form');
  if (fixForm) {
    var mark = function () {
      var n = 0;
      $$('tr[data-fixrow]', fixForm).forEach(function (tr) {
        var ch = $$('[data-orig]', tr).some(function (i) { return String(i.value) !== String(i.dataset.orig); });
        tr.classList.toggle('is-changed', ch);
        if (ch) n++;
      });
      var el = $('#iv-fix-n'); if (el) el.textContent = n ? '고친 줄 ' + n + '개' : '';
    };
    fixForm.addEventListener('input', mark);
    fixForm.addEventListener('change', mark);
    /* 안 고친 줄은 보내지 않는다 (가볍게 · 실수로 덮어쓰지 않게) */
    fixForm.addEventListener('submit', function () {
      $$('tr[data-fixrow]', fixForm).forEach(function (tr) {
        if (!tr.classList.contains('is-changed')) $$('[data-orig]', tr).forEach(function (i) { i.disabled = true; });
      });
    });
  }

  /* 휴대폰 실사: 숫자 칸에 바로 커서 */
  var qn = $('.iv-qc-card__num');
  if (qn) setTimeout(function () { try { qn.focus(); } catch (x) {} }, 100);

  /* ---------------------------------------------------------
   * v5.6 · 처리된 신청 표시 (메뉴 「내역」 옆 숫자 · 내역의 「새로 처리됨」)
   * ------------------------------------------------------- */
  (function () {
    var el = $('#iv-done-feed'); if (!el) return;
    var F; try { F = JSON.parse(el.textContent); } catch (x) { return; }
    var who = store.get('md_inv_who_v1', {}) || {};
    var team = Number(who.team || 0); if (!team) return;
    var seen = store.get('md_inv_seen_v1', {}) || {};
    if (!seen[team]) { seen[team] = F.now; store.set('md_inv_seen_v1', seen); return; }
    var since = seen[team];
    var fresh = F.list.filter(function (r) { return r.t === team && r.a > since; });
    var onMine = !!$('.iv-view--mine');
    if (onMine) {
      $$('.iv-card--req[data-done]').forEach(function (c) {
        if (Number(c.dataset.team) === team && c.dataset.done && c.dataset.done > since) {
          c.classList.add('is-fresh');
          var t = c.querySelector('.iv-card__title');
          if (t && !t.querySelector('.iv-tag--fresh')) t.insertAdjacentHTML('beforeend', ' <span class="iv-tag iv-tag--fresh">새로 처리됨</span>');
        }
      });
      seen[team] = F.now; store.set('md_inv_seen_v1', seen);
      return;
    }
    if (!fresh.length) return;
    $$('a.iv-nav__a[href*="iv=mine"], a.iv-tabbar__a[href*="iv=mine"]').forEach(function (a) {
      if (a.querySelector('.iv-nav__badge')) return;
      a.insertAdjacentHTML('beforeend', '<b class="iv-nav__badge iv-nav__badge--ok" title="처리된 신청">' + fresh.length + '</b>');
    });
  })();

  /* ---------------------------------------------------------
   * v5.6 · 홈 화면에 추가 안내 (신청 화면)
   * ------------------------------------------------------- */
  (function () {
    var box = $('#iv-install'); if (!box) return;
    var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    if (standalone || store.get('md_inv_install_x', 0)) return;
    var ua = navigator.userAgent || '';
    var ios = /iPhone|iPad|iPod/i.test(ua), mobile = /Android|iPhone|iPad|iPod|Mobile/i.test(ua);
    var prompt = null;
    window.addEventListener('beforeinstallprompt', function (e) {
      e.preventDefault(); prompt = e;
      box.hidden = false; $('#iv-install-btn').hidden = false; $('.iv-install__and', box).hidden = true;
    });
    if (mobile) { box.hidden = false; (ios ? $('.iv-install__ios', box) : $('.iv-install__and', box)).hidden = false; }
    $('#iv-install-btn').addEventListener('click', function () { if (prompt) { prompt.prompt(); prompt = null; box.hidden = true; } });
    $('#iv-install-x').addEventListener('click', function () { box.hidden = true; store.set('md_inv_install_x', 1); });
  })();

  /* 바코드 입고: 주문을 고르면 남은 수량으로 */
  $$('.iv-ordpick input[type=radio]').forEach(function (r) {
    r.addEventListener('change', function () {
      var q = r.form.querySelector('[name=qty]');
      if (q && r.dataset.left) q.value = r.dataset.left;
    });
  });

  /* 결과 문구는 몇 초 뒤 옅어진다 (성공일 때만) */
  var fl = $('.iv-flash--ok');
  if (fl) setTimeout(function () { fl.classList.add('is-fade'); }, 6000);
})();

/* v6.6.1 · 분류 단계 — 다시 불러오지 않고 바로 (재고 · 실사 모드 · 휴대폰 실사). 묶음 머리줄(소계 · 접기 기억)도 여기서 그린다.
   운영 서버는 화면 한 번 여는 데 0.8초 — 단계 버튼마다 새로 불러오면 느려서 (원장 「좀 느린데?」) */
(function () {
  'use strict';
  var cfgEl = document.getElementById('iv-catnav-data');
  if (!cfgEl) return;
  var C; try { C = JSON.parse(cfgEl.textContent); } catch (e) { return; }
  var KEY = 'md_inv_grp_closed_v2', closed = {};
  try { closed = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { closed = {}; }
  var byId = {};
  C.cats.forEach(function (c) { byId[c[0]] = c; });
  function name(id) { return byId[id] ? byId[id][3] : ''; }
  function kids(lv, parent) { return C.cats.filter(function (c) { return c[1] === lv && (lv === 1 || c[2] === parent); }); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (m) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]; }); }
  function won(n) { return Math.round(n).toLocaleString('en-US') + '원'; }
  var isList = C.scope === 'qcount';
  var items = Array.prototype.map.call(document.querySelectorAll(isList ? '#iv-qcbrowse [data-c]' : '.iv-table [data-c]'), function (el) {
    return { el: el, c: el.getAttribute('data-c').split(',').map(Number), v: +(el.getAttribute('data-v') || 0), low: el.getAttribute('data-low') === '1', done: el.getAttribute('data-done') === '1', g: null };
  });
  var P = C.path.slice(), iall = !!C.iall;
  function inPath(c, p) {
    if (p[0] && c[0] !== p[0]) return false;
    if (p[1] && c[1] !== p[1]) return false;
    if (p[2] === -1) return !c[2];
    if (p[2] && c[2] !== p[2]) return false;
    return true;
  }
  function count(p) {
    var r = { n: 0, low: 0, done: 0 };
    items.forEach(function (it) { if (inPath(it.c, p)) { r.n++; if (it.low) r.low++; if (it.done) r.done++; } });
    return r;
  }
  function url(p, extra) {
    var u = new URL(location.href);
    ['pg', 'iall', 'id', 'bc', 'id_pick'].forEach(function (k) { u.searchParams.delete(k); });
    u.searchParams.set('ic1', p[0]); u.searchParams.set('ic2', p[1]); u.searchParams.set('ic3', p[2]);
    Object.keys(extra || {}).forEach(function (k) { u.searchParams.set(k, extra[k]); });
    u.hash = '';
    return u.toString();
  }
  function setLinks(p) {
    Array.prototype.forEach.call(document.querySelectorAll('[data-catnav-link]'), function (a) {
      var u = new URL(a.href, location.href);
      u.searchParams.set('ic1', p[0]); u.searchParams.set('ic2', p[1]); u.searchParams.set('ic3', p[2]);
      a.href = u.toString();
    });
    Array.prototype.forEach.call(document.querySelectorAll('form.iv-filter'), function (f) {
      ['ic1', 'ic2', 'ic3'].forEach(function (k, i) { if (f.elements[k]) f.elements[k].value = p[i]; });
    });
  }

  /* 단계 버튼 줄 (서버 md_inv_cat_bar 와 같은 모양) */
  function btn(label, on, p, c) {
    return '<a class="iv-catbtn' + (on ? ' is-on' : '') + (c.n ? '' : ' is-empty') + '" href="' + esc(url(p)) + '" data-p="' + p.join(',') + '"' + (on ? ' aria-current="true"' : '') + '>' + esc(label) + ' <small>' + c.n + '</small>' + (c.low ? ' <em class="iv-catbtn__low" title="부족 · 품절">부족 ' + c.low + '</em>' : '') + '</a>';
  }
  function bar(p) {
    var nav = document.querySelector('.iv-catbar');
    if (!nav) return;
    var h = '<div class="iv-catbar__row"><span class="iv-catbar__lab">' + esc(C.lab[0]) + '</span>' + btn('전체', !p[0], [0, 0, 0], count([0, 0, 0]));
    kids(1).forEach(function (c) { h += btn(c[3], p[0] === c[0], [c[0], 0, 0], count([c[0], 0, 0])); });
    h += '</div>';
    if (p[0]) {
      h += '<div class="iv-catbar__row"><span class="iv-catbar__lab">' + esc(C.lab[1]) + '</span>' + btn('전체', !p[1], [p[0], 0, 0], count([p[0], 0, 0]));
      kids(2, p[0]).forEach(function (c) { h += btn(c[3], p[1] === c[0], [p[0], c[0], 0], count([p[0], c[0], 0])); });
      h += '</div>';
    }
    var subs = p[1] ? kids(3, p[1]) : [];
    if (subs.length) {
      h += '<div class="iv-catbar__row"><span class="iv-catbar__lab">' + esc(C.lab[2]) + '</span>' + btn('전체', !p[2], [p[0], p[1], 0], count([p[0], p[1], 0]));
      subs.forEach(function (c) { h += btn(c[3], p[2] === c[0], [p[0], p[1], c[0]], count([p[0], p[1], c[0]])); });
      var none = count([p[0], p[1], -1]);
      if (none.n) h += btn('기타', p[2] === -1, [p[0], p[1], -1], none);
      h += '</div>';
    }
    nav.innerHTML = h;
  }

  /* 표 — 경로 밖 줄 숨김 · 묶음 머리줄(소계 · 접기) */
  function gkey(c, p) {
    if (p[2]) return '';
    if (p[1]) return c[2] ? name(c[2]) : '';
    if (p[0]) return c[1] ? name(c[1]) : '분류 없음';
    return (c[0] ? name(c[0]) : '분류 없음') + (c[1] ? ' › ' + name(c[1]) : '');
  }
  function table(p) {
    Array.prototype.forEach.call(document.querySelectorAll('tr.iv-grp'), function (r) { r.parentNode.removeChild(r); });
    var vis = [], sums = {}, keys = [], value = 0;
    items.forEach(function (it) {
      if (!inPath(it.c, p)) { it.el.hidden = true; it.g = null; it.el.removeAttribute('data-g'); return; }
      var k = it.g = gkey(it.c, p);
      if (!sums[k]) { sums[k] = { n: 0, v: 0, low: 0 }; keys.push(k); }
      sums[k].n++; sums[k].v += it.v; if (it.low) sums[k].low++;
      value += it.v;
      vis.push(it);
    });
    var heads = !p[2] && (keys.length > 1 || (keys.length === 1 && keys[0] !== '')), prev = null;
    vis.forEach(function (it) {
      var k = it.g, shut = heads && !!closed[k];
      it.el.setAttribute('data-g', k);
      it.el.hidden = shut;
      if (heads && k !== prev) {
        var s = sums[k], tr = document.createElement('tr');
        tr.className = 'iv-grp' + (shut ? ' is-closed' : '');
        tr.setAttribute('data-grp', k);
        tr.innerHTML = '<th colspan="' + (C.cols || 8) + '"><button type="button" class="iv-grp__btn" data-grp-toggle aria-expanded="' + (shut ? 'false' : 'true') + '"><span class="iv-grp__arrow" aria-hidden="true">▾</span> <b>' + esc(k === '' ? '기타 (세부 분류 없음)' : k) + '</b></button> <span class="iv-grp__sum">' + s.n + '개' + (C.admin ? ' · 재고 금액 ' + won(s.v) : '') + (s.low ? ' · <em>부족 ' + s.low + '</em>' : '') + '</span></th>';
        it.el.parentNode.insertBefore(tr, it.el);
      }
      prev = k;
    });
    var n = document.getElementById('iv-catnav-n');
    if (n) n.textContent = vis.length + '개 품목' + (C.admin ? ' · 재고 금액 ' + won(value) : '');
    var em = document.querySelector('.iv-catnav-empty');
    if (em) em.hidden = vis.length > 0;
  }

  /* 휴대폰 실사 — 경로 · 타일 · 목록 */
  function plink(p, label) { return '<a class="iv-link" href="' + esc(url(p)) + '" data-p="' + p.join(',') + '">' + esc(label) + '</a>'; }
  function list(p) {
    var box = document.getElementById('iv-qcbrowse');
    if (!box) return;
    var crumb = '<b>분류별로 세기</b>';
    if (p[0]) {
      crumb = plink([0, 0, 0], '분류별로') + ' › ' + (p[1] ? plink([p[0], 0, 0], name(p[0])) : '<b>' + esc(name(p[0])) + '</b>');
      if (p[1]) crumb += ' › ' + (p[2] ? plink([p[0], p[1], 0], name(p[1])) : '<b>' + esc(name(p[1])) + '</b>');
      if (p[2]) crumb += ' › <b>' + esc(p[2] === -1 ? '기타' : name(p[2])) + '</b>';
    }
    box.querySelector('.iv-crumbs').innerHTML = crumb;
    var tiles = [];
    if (!p[0]) kids(1).forEach(function (c) { tiles.push([c[3], [c[0], 0, 0]]); });
    else if (!p[1]) kids(2, p[0]).forEach(function (c) { tiles.push([c[3], [p[0], c[0], 0]]); });
    else if (!p[2] && kids(3, p[1]).length) {
      kids(3, p[1]).forEach(function (c) { tiles.push([c[3], [p[0], p[1], c[0]]]); });
      tiles.push(['기타', [p[0], p[1], -1]]);
    }
    var th = '';
    tiles.forEach(function (t) {
      var c = count(t[1]);
      if (t[0] === '기타' && !c.n) return;
      th += '<a class="iv-tile' + (c.n ? '' : ' iv-tile--empty') + '" href="' + esc(url(t[1])) + '" data-p="' + t[1].join(',') + '"><b>' + esc(t[0]) + '</b><small>' + (c.n ? c.n + '개' + (c.done ? ' · 오늘 ' + c.done + '개 셈' : '') : '아직 품목 없음') + '</small></a>';
    });
    if (tiles.length && p[0]) {
      var all = count([p[0], p[1], 0]);
      if (all.n) th += '<a class="iv-tile iv-tile--all" href="' + esc(url([p[0], p[1], 0], { iall: 1 })) + '" data-p="' + p[0] + ',' + p[1] + ',0" data-iall><b>모두 보기</b><small>' + all.n + '개</small></a>';
    }
    var tl = box.querySelector('.iv-tiles');
    tl.innerHTML = th; tl.hidden = !tiles.length;
    var showList = !tiles.length || iall, c = count(p), help = box.querySelector('.iv-qcbrowse__help'), em = box.querySelector('.iv-qcbrowse__empty');
    var back = url(p, iall ? { iall: 1 } : {});
    items.forEach(function (it) {
      var on = showList && inPath(it.c, p);
      it.el.hidden = !on;
      if (on) { var u = new URL(back); u.searchParams.set('id', it.el.getAttribute('data-id')); u.hash = 'iv-qc-card'; it.el.href = u.toString(); }
    });
    help.hidden = !showList || !c.n;
    em.hidden = !showList || !!c.n;
    help.innerHTML = c.n + '개 중 오늘 센 것 <b>' + c.done + '</b>개 — 품목을 누르면 센 수량을 넣고, 저장하면 이 목록으로 돌아옵니다.';
  }

  function render() { if (isList) { list(P); } else { bar(P); table(P); setLinks(P); } }
  var saveT;
  function save() {
    clearTimeout(saveT);
    saveT = setTimeout(function () {
      var f = new FormData();
      f.append('action', 'md_inv_path'); f.append('nonce', C.nonce); f.append('scope', C.scope);
      f.append('ic1', P[0]); f.append('ic2', P[1]); f.append('ic3', P[2]);
      try { if (!(navigator.sendBeacon && navigator.sendBeacon(C.ajax, f))) fetch(C.ajax, { method: 'POST', body: f, credentials: 'same-origin', keepalive: true }); } catch (e) {}
    }, 150);
  }
  function go(p, all) {
    P = p; iall = !!all;
    render();
    try { history.pushState({ ivp: P, iall: iall }, '', url(P, iall ? { iall: 1 } : {}) + (isList ? '#iv-qcbrowse' : '')); } catch (e) {}
    save();
  }
  document.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.button || e.ctrlKey || e.metaKey || e.shiftKey) return;
    var t = e.target.closest && e.target.closest('.iv-catbar [data-p], #iv-qcbrowse [data-p]');
    if (t) { e.preventDefault(); go(t.getAttribute('data-p').split(',').map(Number), t.hasAttribute('data-iall')); return; }
    var b = e.target.closest && e.target.closest('[data-grp-toggle]');
    if (!b) return;
    var row = b.closest('tr.iv-grp'), g = row.getAttribute('data-grp'), shut = !row.classList.contains('is-closed');
    row.classList.toggle('is-closed', shut);
    b.setAttribute('aria-expanded', shut ? 'false' : 'true');
    items.forEach(function (it) { if (it.g === g) it.el.hidden = shut; });
    if (shut) closed[g] = 1; else delete closed[g];
    try { localStorage.setItem(KEY, JSON.stringify(closed)); } catch (x) {}
  });
  window.addEventListener('popstate', function (e) {
    var st = e.state;
    if (st && st.ivp) { P = st.ivp; iall = !!st.iall; }
    else {
      var u = new URL(location.href);
      P = [+(u.searchParams.get('ic1') || 0), +(u.searchParams.get('ic2') || 0), +(u.searchParams.get('ic3') || 0)];
      iall = !!u.searchParams.get('iall');
    }
    render(); save();
  });
  try { history.replaceState({ ivp: P, iall: iall }, ''); } catch (e) {}
  render();
})();

/* v9.29 · 할 일 › 출고 대기 — 위에서 걸러 보기 (선납품 · 출고 가능 · 주문 필요 · 팀 · 요청자). 페이지는 그대로, 숨기기만 */
(function () {
  'use strict';
  var bar = document.querySelector('[data-todo-filter]');
  if (!bar) return;
  var kind = 'all';
  var team = bar.querySelector('[data-tf-team]'), who = bar.querySelector('[data-tf-who]'), cnt = bar.querySelector('[data-tf-count]');
  function apply() {
    var n = 0;
    Array.prototype.forEach.call(document.querySelectorAll('#iv-sec-req .iv-card--todo'), function (c) {
      var ok = true;
      if (kind === 'pay' || kind === 'pre') ok = c.getAttribute('data-pm') === kind; /* v9.30.1 · 결제 방식 — 선납차감품목 아니면 건별결제품목 */
      if (ok && team && team.value) ok = c.getAttribute('data-team') === team.value;
      if (ok && who && who.value) ok = c.getAttribute('data-who') === who.value;
      c.hidden = !ok;
      if (!ok) { var ch = c.querySelector('input[type=checkbox]'); if (ch) ch.checked = false; }
      if (ok) n++;
    });
    Array.prototype.forEach.call(document.querySelectorAll('#iv-sec-req .iv-group'), function (g) {
      g.hidden = !g.querySelector('.iv-card--todo:not([hidden])');
    });
    if (cnt) cnt.textContent = (kind === 'all' && !(team && team.value) && !(who && who.value)) ? '' : n + '건 보임';
    var any = document.querySelector('#iv-sec-req input[type=checkbox][name$="ids[]"]');
    if (any) any.dispatchEvent(new Event('change', { bubbles: true }));
  }
  bar.addEventListener('click', function (e) {
    var b = e.target.closest('[data-tf]'); if (!b) return;
    kind = b.getAttribute('data-tf');
    Array.prototype.forEach.call(bar.querySelectorAll('[data-tf]'), function (x) { x.classList.toggle('is-on', x === b); });
    apply();
  });
  if (team) team.addEventListener('change', apply);
  if (who) who.addEventListener('change', apply);
  /* 「모두 고르기」는 보이는 카드만 */
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (!t.matches || !t.matches('#iv-sec-req [data-checkall]')) return;
    Array.prototype.forEach.call(document.querySelectorAll('#iv-sec-req .iv-card--todo[hidden] input[type=checkbox]'), function (c) { c.checked = false; });
  }, true);
})();

/* v9.30 · F장부 — 개수를 적으면 최근 입고 단가로 금액을 채운다 (비어 있거나 자동으로 채운 값일 때만) */
(function () {
  'use strict';
  var f = document.querySelector('.iv-fail-form');
  if (!f) return;
  var price = parseInt(f.getAttribute('data-fail-price'), 10) || 0, q = f.querySelector('[data-fail-qty]'), c = f.querySelector('[data-fail-credit]');
  if (!price || !q || !c) return;
  var auto = true;
  c.addEventListener('input', function () { auto = c.value.trim() === ''; });
  q.addEventListener('input', function () { if (auto) { var n = parseInt(q.value, 10) || 0; c.value = n > 0 ? String(n * price) : ''; } });
})();
