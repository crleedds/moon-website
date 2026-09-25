/* v4.1 · Moon Dental Care — 주제 찾기 · 탭 · 사진 크게 보기 · 설명 모드 */
(function () {
  'use strict';

  /* 주제 찾기 */
  var filter = document.querySelector('[data-care-filter]');
  if (filter) {
    filter.addEventListener('input', function () {
      var q = filter.value.trim().toLowerCase();
      document.querySelectorAll('.mdc-tile').forEach(function (t) {
        t.classList.toggle('is-hidden', q !== '' && (t.getAttribute('data-care-name') || '').toLowerCase().indexOf(q) === -1);
      });
    });
  }

  var topic = document.querySelector('[data-care-topic]');
  if (!topic) return;

  /* 탭 */
  var tabs = topic.querySelectorAll('[data-care-tab]');
  var panes = topic.querySelectorAll('[data-care-pane]');
  function showTab(name) {
    tabs.forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-care-tab') === name); });
    panes.forEach(function (p) {
      var on = p.getAttribute('data-care-pane') === name;
      p.classList.toggle('is-on', on);
      if (!on) p.querySelectorAll('video').forEach(function (v) { v.pause(); });
    });
    try { history.replaceState(null, '', '#' + name); } catch (e) {}
  }
  tabs.forEach(function (b) { b.addEventListener('click', function () { showTab(b.getAttribute('data-care-tab')); }); });
  var want = (location.hash || '').replace('#', '');
  var first = tabs.length ? tabs[0].getAttribute('data-care-tab') : '';
  if (want && topic.querySelector('[data-care-pane="' + want + '"]')) showTab(want); else if (first) showTab(first);

  /* 사진 크게 보기 (이전·다음) */
  var box = document.querySelector('[data-care-lightbox]');
  var photos = Array.prototype.slice.call(topic.querySelectorAll('[data-care-item="photo"]'));
  var zi = 0;
  function zoomShow(i) {
    if (!photos.length) return;
    zi = (i + photos.length) % photos.length;
    box.querySelector('img').src = photos[zi].getAttribute('data-src');
    box.querySelector('.mdc-zoom__cap').textContent = photos[zi].getAttribute('data-caption') || '';
    box.hidden = false;
  }
  function zoomClose() { box.hidden = true; box.querySelector('img').src = ''; }
  if (box) {
    document.addEventListener('click', function (e) {
      var a = e.target.closest('[data-care-zoom]');
      if (a) { e.preventDefault(); zoomShow(parseInt(a.getAttribute('data-care-zoom'), 10) || 0); return; }
      if (box.hidden) return;
      if (e.target.closest('[data-care-lightbox-prev]')) zoomShow(zi - 1);
      else if (e.target.closest('[data-care-lightbox-next]')) zoomShow(zi + 1);
      else if (e.target === box || e.target.closest('[data-care-lightbox-close]')) zoomClose();
    });
  }

  /* 설명 모드: 사진 → 영상 순서로 한 화면에 하나씩 */
  var stage = document.querySelector('[data-care-stage]');
  if (!stage) return;
  var body = stage.querySelector('[data-care-stage-body]');
  var cap = stage.querySelector('[data-care-cap]');
  var count = stage.querySelector('[data-care-count]');
  var items = [];
  photos.forEach(function (p) { items.push({ type: 'photo', src: p.getAttribute('data-src'), caption: p.getAttribute('data-caption') || '' }); });
  Array.prototype.slice.call(topic.querySelectorAll('[data-care-item="video"]')).forEach(function (v) {
    var player = v.querySelector('.mdc-card__player');
    items.push({ type: 'video', html: player ? player.innerHTML : '', caption: (v.querySelector('figcaption') || {}).textContent || '' });
  });
  var idx = 0, on = false;
  function show(i) {
    if (!items.length) return;
    idx = Math.max(0, Math.min(items.length - 1, i));
    var it = items[idx];
    body.innerHTML = '';
    if (it.type === 'photo') { var im = document.createElement('img'); im.src = it.src; im.alt = it.caption; body.appendChild(im); }
    else { body.innerHTML = it.html.replace('preload="metadata"', 'preload="auto"'); var vd = body.querySelector('video'); if (vd) { vd.play().catch(function () {}); } }
    cap.textContent = it.caption;
    count.textContent = (idx + 1) + ' / ' + items.length;
  }
  function open() { on = true; stage.hidden = false; document.body.classList.add('mdc-presenting'); var f = stage.getAttribute('data-first'); show(f === 'videos' ? photos.length : 0); }
  function close() { on = false; body.innerHTML = ''; stage.hidden = true; document.body.classList.remove('mdc-presenting'); }
  var btn = topic.querySelector('[data-care-present]');
  if (btn) btn.addEventListener('click', open);
  stage.querySelector('[data-care-prev]').addEventListener('click', function () { show(idx - 1); });
  stage.querySelector('[data-care-next]').addEventListener('click', function () { show(idx + 1); });
  stage.querySelector('[data-care-exit]').addEventListener('click', close);
  document.addEventListener('keydown', function (e) {
    if (box && !box.hidden) { if (e.key === 'ArrowRight') zoomShow(zi + 1); else if (e.key === 'ArrowLeft') zoomShow(zi - 1); else if (e.key === 'Escape') zoomClose(); return; }
    if (!on) return;
    if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'PageDown') { e.preventDefault(); show(idx + 1); }
    else if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); show(idx - 1); }
    else if (e.key === 'Escape') close();
  });
  var x0 = null;
  stage.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
  stage.addEventListener('touchend', function (e) {
    if (x0 === null) return;
    var dx = e.changedTouches[0].clientX - x0; x0 = null;
    if (Math.abs(dx) > 60) show(dx < 0 ? idx + 1 : idx - 1);
  }, { passive: true });
})();
