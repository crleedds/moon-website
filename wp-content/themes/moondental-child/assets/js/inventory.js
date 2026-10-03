/* 재고관리 v5 — 직원 라운지
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
    if (t.matches('[data-checkall]')) {
      var sec = t.closest('.iv-sec') || document;
      $$('input[type=checkbox][name="' + t.dataset.checkall + '"]', sec).forEach(function (c) { c.checked = t.checked; });
      needCheck();
    }
    if (t.matches('input[type=checkbox][name="ids[]"]')) needCheck();
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
  var scanner = { stream: null, timer: null, zx: null, cb: null };
  function stopScan() {
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
  function startScan(cb) {
    var dlg = openDlg('iv-scan-dlg');
    if (!dlg) { var v = window.prompt('바코드 숫자를 입력하세요'); if (v) cb(v.trim()); return; }
    scanner.cb = cb;
    var video = $('#iv-scan-video'), msg = $('#iv-scan-msg'), manual = $('#iv-scan-manual');
    manual.value = '';
    var done = function (code) { if (!code) return; stopScan(); closeDlg(dlg); if (navigator.vibrate) navigator.vibrate(60); cb(String(code).trim()); };
    manual.onkeydown = function (e) { if (e.key === 'Enter') { e.preventDefault(); done(manual.value); } };
    if (!dlg.dataset.bound) { dlg.dataset.bound = '1'; dlg.addEventListener('close', stopScan); }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { msg.textContent = '이 브라우저는 카메라를 쓸 수 없습니다. 아래에 숫자를 직접 입력해 주세요.'; return; }
    msg.textContent = '바코드를 네모 안에 맞춰 주세요.';
    if ('BarcodeDetector' in window) {
      navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (st) {
        scanner.stream = st; video.srcObject = st; video.play();
        var det = new window.BarcodeDetector();
        scanner.timer = setInterval(function () {
          if (video.readyState < 2) return;
          det.detect(video).then(function (r) { if (r && r.length) done(r[0].rawValue); }).catch(function () {});
        }, 250);
      }).catch(function () { msg.textContent = '카메라를 열지 못했습니다. 권한을 허용하거나 숫자를 직접 입력해 주세요.'; });
    } else {
      loadZXing().then(function (ZX) {
        var reader = new ZX.BrowserMultiFormatReader();
        reader.decodeFromVideoDevice(undefined, video, function (r) { if (r) done(r.getText()); }).then(function (ctrl) { scanner.zx = ctrl; }).catch(function () { msg.textContent = '카메라를 열지 못했습니다. 숫자를 직접 입력해 주세요.'; });
      }).catch(function () { msg.textContent = '스캐너를 불러오지 못했습니다. 숫자를 직접 입력해 주세요.'; });
    }
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
    startScan(function (code) { if (inp) inp.value = code; if (host) openDlg(host.id); });
  });

  /* ---------------------------------------------------------
   * 요청 화면
   * ------------------------------------------------------- */
  var root = $('#iv-req');
  var dataEl = $('#iv-req-data');
  if (root && dataEl) reqApp(JSON.parse(dataEl.textContent));

  function reqApp(D) {
    var KEY = 'md_inv_cart_v1', WHO = 'md_inv_who_v1';
    var O = D.opt;
    var byId = {}, catName = {}, catSort = {}, vendors = D.vendors || {};
    D.items.forEach(function (it) { byId[it.i] = it; });
    var catParent = {};
    D.cats.forEach(function (c, i) { catName[c.id] = c.n; catSort[c.id] = i; catParent[c.id] = c.p; });
    var cart = store.get(KEY, null);
    if (!cart || !Array.isArray(cart.lines)) cart = { lines: [], tok: '' };
    /* 목록에서 사라진 품목은 장바구니에서 뺀다 */
    cart.lines = cart.lines.filter(function (l) { return l.custom || byId[l.id]; });
    var who = O.remember ? store.get(WHO, {}) : {};
    var state = { q: '', c1: 0, team: Number(who.team || 0) };

    var list = $('#iv-list'), q = $('#iv-q'), team = $('#iv-team'), chips = $('#iv-c1');
    if (state.team && team.querySelector('option[value="' + state.team + '"]')) team.value = String(state.team); else state.team = 0;

    function save() { store.set(KEY, cart); renderBar(); }
    function lineOf(id) { for (var i = 0; i < cart.lines.length; i++) if (!cart.lines[i].custom && cart.lines[i].id === id) return cart.lines[i]; return null; }
    function qtyOf(id) { var l = lineOf(id); return l ? l.qty : 0; }
    function add(id, n) {
      var it = byId[id]; if (!it) return;
      var l = lineOf(id);
      if (!l) { l = { id: id, qty: 0 }; cart.lines.push(l); }
      l.qty = Math.max(0, Math.min(O.max, l.qty + n));
      if (!l.qty) cart.lines.splice(cart.lines.indexOf(l), 1);
      cart.tok = '';
      save(); updateRow(id);
    }

    /* 결제 방식 칩 */
    var c1s = D.cats.filter(function (c) { return c.l === 1; });
    var chipHtml = '<button type="button" class="iv-chipbtn is-on" data-c1="0">전체</button>';
    c1s.forEach(function (c) { if (D.items.some(function (it) { return it.c1 === c.id; })) chipHtml += '<button type="button" class="iv-chipbtn" data-c1="' + c.id + '">' + esc(c.n) + '</button>'; });
    chips.innerHTML = chipHtml;
    chips.hidden = c1s.length < 2;
    chips.addEventListener('click', function (e) {
      var b = e.target.closest('[data-c1]'); if (!b) return;
      state.c1 = Number(b.dataset.c1);
      $$('[data-c1]', chips).forEach(function (x) { x.classList.toggle('is-on', x === b); });
      render();
    });

    function stockHtml(it) {
      if (!O.stock || it.s === null) return '';
      if (it.s <= 0) return '<span class="iv-badge iv-badge--out">품절' + (it.o ? ' · 주문 중' : '') + '</span>';
      if (it.m && it.s < it.m) return '<span class="iv-badge iv-badge--low">재고 ' + num(it.s) + (it.o ? ' · 주문 중' : '') + '</span>';
      return '<span class="iv-badge iv-badge--ok">재고 ' + num(it.s) + '</span>';
    }
    function rowHtml(it) {
      var v = vendors[it.v] ? vendors[it.v].n : '';
      var pend = state.team && D.pending[state.team] && D.pending[state.team][it.i];
      var sub = [v, it.u, (O.price && it.p ? num(it.p) + '원' : '')].filter(Boolean).join(' · ');
      return '<div class="iv-row" data-id="' + it.i + '">' +
        '<button type="button" class="iv-row__main" data-add="' + it.i + '"><span class="iv-row__name">' + esc(it.n) + '</span><span class="iv-row__sub">' + esc(sub) + '</span>' +
        (pend ? '<span class="iv-row__dup">우리 팀이 이미 ' + pend + '개 요청해 둠</span>' : '') + '</button>' +
        '<span class="iv-row__stock">' + stockHtml(it) + '</span>' +
        '<span class="iv-row__ctl">' + ctlHtml(it.i) + '</span></div>';
    }
    function ctlHtml(id) {
      var n = qtyOf(id);
      if (!n) return '<button type="button" class="iv-btn iv-btn--add" data-add="' + id + '" aria-label="담기">담기</button>';
      return '<span class="iv-step"><button type="button" data-dec="' + id + '" aria-label="하나 빼기">−</button><input type="number" inputmode="numeric" min="0" max="' + O.max + '" value="' + n + '" data-qty="' + id + '" aria-label="수량"><button type="button" data-inc="' + id + '" aria-label="하나 더">+</button></span>';
    }
    function updateRow(id) {
      $$('.iv-row[data-id="' + id + '"]', list).forEach(function (r) {
        r.classList.toggle('is-in', qtyOf(id) > 0);
        var c = r.querySelector('.iv-row__ctl');
        var focused = document.activeElement && c.contains(document.activeElement) && document.activeElement.tagName === 'INPUT';
        if (!focused) c.innerHTML = ctlHtml(id);
      });
    }

    function match(it, words) {
      var hay = (it.n + ' ' + (vendors[it.v] ? vendors[it.v].n : '') + ' ' + (it.b || '') + ' ' + (catName[it.c2] || '') + ' ' + (catName[it.c3] || '')).toLowerCase();
      for (var i = 0; i < words.length; i++) if (hay.indexOf(words[i]) < 0) return false;
      return true;
    }

    var openGroups = store.get('md_inv_open_v1', {});
    function render() {
      var items = D.items.filter(function (it) { return !state.c1 || it.c1 === state.c1; });
      var html = '';
      if (state.q) {
        var words = state.q.toLowerCase().split(/\s+/).filter(Boolean);
        var hit = items.filter(function (it) { return match(it, words); });
        html += '<p class="iv-list__count">「' + esc(state.q) + '」 ' + hit.length + '개</p>';
        hit.slice(0, 200).forEach(function (it) { html += rowHtml(it); });
        if (!hit.length) html += '<div class="iv-empty">찾는 품목이 없습니다.' + (O.custom ? ' 아래 「목록에 없는 품목 요청」으로 보내 주세요.' : '') + '</div>';
        list.innerHTML = html;
        $$('.iv-row', list).forEach(function (r) { r.classList.toggle('is-in', qtyOf(Number(r.dataset.id)) > 0); });
        return;
      }
      /* 담은 품목 · 우리 팀 최근 품목 · 품목군별 */
      var inCart = cart.lines.filter(function (l) { return !l.custom && byId[l.id] && (!state.c1 || byId[l.id].c1 === state.c1); }).map(function (l) { return byId[l.id]; });
      var recent = state.team && D.recent[state.team] ? D.recent[state.team].map(function (id) { return byId[id]; }).filter(function (it) { return it && (!state.c1 || it.c1 === state.c1); }) : [];
      var groups = {}, order = [];
      items.forEach(function (it) {
        var g = it.c2 || 0;
        if (!groups[g]) { groups[g] = []; order.push(g); }
        groups[g].push(it);
      });
      /* 결제 방식 순서 → 그 안의 품목군 순서 */
      order.sort(function (a, b) { if (!a) return 1; if (!b) return -1; var pa = catSort[catParent[a]] || 0, pb = catSort[catParent[b]] || 0; return pa !== pb ? pa - pb : (catSort[a] || 0) - (catSort[b] || 0); });
      function group(key, title, arr, open, cls) {
        if (!arr.length) return '';
        var isOpen = open || openGroups[key];
        var h = '<details class="iv-grp ' + (cls || '') + '" data-g="' + key + '"' + (isOpen ? ' open' : '') + '><summary><span>' + title + '</span><b>' + arr.length + '</b></summary><div class="iv-grp__body">';
        if (isOpen) arr.forEach(function (it) { h += rowHtml(it); });
        return h + '</div></details>';
      }
      if (!state.team) html += '<div class="iv-hint">' + '위에서 <b>우리 팀</b>을 고르면 우리 팀이 자주 요청한 품목이 맨 위에 모입니다.</div>';
      html += group('cart', '🛒 담은 품목', inCart, true, 'iv-grp--cart');
      html += group('recent', '⭐ 우리 팀이 최근 요청한 품목', recent, true, 'iv-grp--star');
      order.forEach(function (g) { html += group('c' + g, esc(g ? (catName[g] || '기타') : '분류 없음'), groups[g], false); });
      if (!items.length) html += '<div class="iv-empty">품목이 없습니다.</div>';
      list.innerHTML = html;
      $$('.iv-row', list).forEach(function (r) { r.classList.toggle('is-in', qtyOf(Number(r.dataset.id)) > 0); });
    }
    /* 그룹을 열 때 그 안을 그린다 (처음부터 600줄을 다 그리지 않는다) */
    list.addEventListener('toggle', function (e) {
      var d = e.target;
      if (!d.matches || !d.matches('details.iv-grp')) return;
      var k = d.dataset.g;
      if (k !== 'cart' && k !== 'recent') { openGroups[k] = d.open ? 1 : 0; store.set('md_inv_open_v1', openGroups); }
      var body = d.querySelector('.iv-grp__body');
      if (d.open && !body.children.length) {
        var g = Number(k.slice(1));
        var h = '';
        D.items.forEach(function (it) { if ((it.c2 || 0) === g && (!state.c1 || it.c1 === state.c1)) h += rowHtml(it); });
        body.innerHTML = h;
        $$('.iv-row', body).forEach(function (r) { r.classList.toggle('is-in', qtyOf(Number(r.dataset.id)) > 0); });
      }
    }, true);

    list.addEventListener('click', function (e) {
      var a = e.target.closest('[data-add]'); if (a) { add(Number(a.dataset.add), 1); return; }
      var i = e.target.closest('[data-inc]'); if (i) { add(Number(i.dataset.inc), 1); return; }
      var d = e.target.closest('[data-dec]'); if (d) { add(Number(d.dataset.dec), -1); return; }
    });
    list.addEventListener('change', function (e) {
      var t = e.target; if (!t.matches('[data-qty]')) return;
      var id = Number(t.dataset.qty), l = lineOf(id);
      var v = Math.max(0, Math.min(O.max, parseInt(t.value, 10) || 0));
      if (l) { l.qty = v; if (!v) cart.lines.splice(cart.lines.indexOf(l), 1); cart.tok = ''; save(); }
      updateRow(id);
    });

    var tmr = null;
    q.addEventListener('input', function () { clearTimeout(tmr); tmr = setTimeout(function () { state.q = q.value.trim(); render(); }, 120); });
    q.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      var v = q.value.trim(); if (!v) return;
      /* 바코드 스캐너(키보드처럼 입력 + 엔터)로 찍으면 바로 담는다 */
      var hit = D.items.filter(function (it) { return it.b && it.b === v; });
      if (hit.length === 1) { add(hit[0].i, 1); q.value = ''; state.q = ''; render(); toast('「' + hit[0].n + '」 담았습니다'); }
    });
    team.addEventListener('change', function () {
      state.team = Number(team.value) || 0;
      if (O.remember) { who.team = state.team; store.set(WHO, who); }
      render();
    });
    $('#iv-scan').addEventListener('click', function () {
      startScan(function (code) {
        var hit = D.items.filter(function (it) { return it.b && it.b === code; });
        if (hit.length) { add(hit[0].i, 1); toast('「' + hit[0].n + '」 담았습니다'); }
        else { q.value = code; state.q = code; render(); toast('등록되지 않은 바코드입니다: ' + code); }
      });
    });

    /* 장바구니 바 */
    var bar = $('#iv-cartbar');
    function renderBar() {
      var n = cart.lines.length, total = 0;
      cart.lines.forEach(function (l) { total += l.qty; });
      bar.hidden = n === 0;
      $('#iv-cart-n').textContent = n;
      $('#iv-cart-txt').textContent = n ? n + '개 품목 · ' + total + '개' : '담은 품목';
      document.body.classList.toggle('iv-has-cart', n > 0);
      var tab = $('.iv-tabbar__a.is-on'); if (tab) tab.dataset.n = n || '';
    }

    /* 장바구니 대화상자 */
    var dlg = $('#iv-cart'), lines = $('#iv-cart-lines'), form = $('#iv-cart-form'), err = $('#iv-cart-err');
    function renderCart() {
      if (!cart.lines.length) { lines.innerHTML = '<div class="iv-empty">담은 품목이 없습니다.</div>'; return; }
      var tm = Number($('#iv-cart-team').value) || 0;
      var h = '';
      cart.lines.forEach(function (l, idx) {
        var it = l.custom ? null : byId[l.id];
        var name = it ? it.n : l.custom.name;
        var warn = [];
        if (it && O.stock && it.s !== null && l.qty > it.s) warn.push(it.s <= 0 ? '지금 재고가 없어요 — 주문 후 출고됩니다' : '재고(' + it.s + ')보다 많아요 — 있는 만큼 먼저 나갈 수 있어요');
        if (it && tm && D.pending[tm] && D.pending[tm][it.i]) warn.push('우리 팀이 이미 ' + D.pending[tm][it.i] + '개 요청해 둔 품목이에요');
        var sub = it ? [vendors[it.v] ? vendors[it.v].n : '', it.u].filter(Boolean).join(' · ') : ['목록에 없는 품목', l.custom.vendor, l.custom.unit].filter(Boolean).join(' · ');
        h += '<div class="iv-cline" data-idx="' + idx + '"><div class="iv-cline__main"><b>' + esc(name) + '</b><small>' + esc(sub) + '</small>' +
          warn.map(function (w) { return '<span class="iv-cline__warn">' + esc(w) + '</span>'; }).join('') +
          '<input class="iv-input iv-input--sm" maxlength="200" placeholder="이 품목 메모 (선택)" value="' + esc(l.note || '') + '" data-lnote="' + idx + '"></div>' +
          '<span class="iv-step"><button type="button" data-ldec="' + idx + '" aria-label="하나 빼기">−</button><input type="number" inputmode="numeric" min="1" max="' + O.max + '" value="' + l.qty + '" data-lqty="' + idx + '" aria-label="수량"><button type="button" data-linc="' + idx + '" aria-label="하나 더">+</button></span>' +
          '<button type="button" class="iv-x" data-ldel="' + idx + '" aria-label="빼기">' + '×' + '</button></div>';
      });
      lines.innerHTML = h;
      $('#iv-cart-send').textContent = '요청 보내기 (' + cart.lines.length + '건)';
    }
    function lineChanged(idx) { var l = cart.lines[idx]; cart.tok = ''; save(); if (l && !l.custom) updateRow(l.id); }
    lines.addEventListener('click', function (e) {
      var t = e.target, idx;
      if ((idx = t.dataset.linc) !== undefined) { cart.lines[idx].qty = Math.min(O.max, cart.lines[idx].qty + 1); lineChanged(idx); renderCart(); }
      else if ((idx = t.dataset.ldec) !== undefined) { cart.lines[idx].qty = Math.max(1, cart.lines[idx].qty - 1); lineChanged(idx); renderCart(); }
      else if ((idx = t.dataset.ldel) !== undefined) { var l = cart.lines.splice(Number(idx), 1)[0]; cart.tok = ''; save(); if (l && !l.custom) updateRow(l.id); renderCart(); render(); }
    });
    lines.addEventListener('change', function (e) {
      var t = e.target;
      if (t.dataset.lqty !== undefined) { cart.lines[t.dataset.lqty].qty = Math.max(1, Math.min(O.max, parseInt(t.value, 10) || 1)); lineChanged(t.dataset.lqty); renderCart(); }
      if (t.dataset.lnote !== undefined) { cart.lines[t.dataset.lnote].note = t.value.slice(0, 200); save(); }
    });
    $('#iv-cart-team').addEventListener('change', renderCart);
    dlg.addEventListener('close', function () { render(); });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('[data-dlg="iv-cart"]')) return;
      var ct = $('#iv-cart-team'), cn = $('#iv-cart-name');
      if (!ct.value && state.team) ct.value = String(state.team);
      if (!cn.value && who.name) cn.value = who.name;
      err.hidden = true;
      renderCart();
    });
    $('#iv-cart-clear').addEventListener('click', function () {
      if (!cart.lines.length || !window.confirm('담은 품목을 모두 비울까요?')) return;
      cart.lines = []; cart.tok = ''; save(); renderCart(); render();
    });
    form.addEventListener('submit', function (e) {
      err.hidden = true;
      var tm = $('#iv-cart-team'), nm = $('#iv-cart-name');
      var problem = '';
      if (!cart.lines.length) problem = '담은 품목이 없습니다.';
      else if (!tm.value) problem = '팀을 골라 주세요.';
      else if (O.needName && !nm.value.trim()) problem = '요청자 이름을 적어 주세요.';
      else if (!O.over && cart.lines.some(function (l) { var it = byId[l.id]; return it && it.s !== null && l.qty > it.s; })) problem = '재고보다 많이 담은 품목이 있습니다. 수량을 줄여 주세요.';
      if (problem) { e.preventDefault(); e.stopImmediatePropagation(); err.textContent = problem; err.hidden = false; return; }
      if (!cart.tok) { cart.tok = (Date.now().toString(36) + Math.random().toString(36).slice(2, 10)).replace(/[^a-z0-9]/gi, ''); store.set(KEY, cart); }
      $('#iv-cart-tok').value = cart.tok;
      $('#iv-cart-json').value = JSON.stringify(cart.lines.map(function (l) { return l.custom ? { custom: l.custom, qty: l.qty, note: l.note || '' } : { id: l.id, qty: l.qty, note: l.note || '' }; }));
      if (O.remember) { who.team = Number(tm.value); who.name = nm.value.trim(); store.set(WHO, who); }
    });

    /* 목록에 없는 품목 */
    var cf = $('#iv-custom-form');
    if (cf) cf.addEventListener('submit', function (e) {
      e.preventDefault();
      var g = function (n) { var el = cf.querySelector('[name="' + n + '"]'); return el ? el.value.trim() : ''; };
      if (!g('name')) return;
      cart.lines.push({ custom: { name: g('name'), vendor: g('vendor'), unit: g('unit'), price: g('price'), link: g('link') }, qty: Math.max(1, Math.min(O.max, parseInt(g('qty'), 10) || 1)), note: g('note') });
      cart.tok = ''; save(); cf.reset(); closeDlg(cf.closest('dialog'));
      toast('장바구니에 담았습니다'); render();
    });

    render(); renderBar();
  }

  /* 잠깐 뜨는 알림 */
  var toastEl = null, toastT = null;
  function toast(msg) {
    if (!toastEl) { toastEl = document.createElement('div'); toastEl.className = 'iv-toast'; toastEl.setAttribute('role', 'status'); document.body.appendChild(toastEl); }
    toastEl.textContent = msg; toastEl.classList.add('is-on');
    clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('is-on'); }, 2200);
  }

  /* 결과 문구는 몇 초 뒤 옅어진다 (성공일 때만) */
  var fl = $('.iv-flash--ok');
  if (fl) setTimeout(function () { fl.classList.add('is-fade'); }, 6000);
})();
