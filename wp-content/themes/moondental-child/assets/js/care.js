/* v4.0 · Moon Dental Care — 주제 찾기 · 설명 모드(슬라이드) */
(function () {
  'use strict';

  /* 허브: 주제 찾기 */
  var filter = document.querySelector('[data-care-filter]');
  if (filter) {
    filter.addEventListener('input', function () {
      var q = filter.value.trim().toLowerCase();
      document.querySelectorAll('.mdc-card').forEach(function (card) {
        var name = (card.getAttribute('data-care-name') || '').toLowerCase();
        card.classList.toggle('is-hidden', q !== '' && name.indexOf(q) === -1);
      });
      document.querySelectorAll('.mdc-hub__group').forEach(function (h) {
        var grid = h.nextElementSibling;
        var visible = grid && grid.querySelector('.mdc-card:not(.is-hidden)');
        h.style.display = visible ? '' : 'none';
        if (grid) grid.style.display = visible ? '' : 'none';
      });
    });
  }

  /* 주제 페이지: 설명 모드 */
  var topic = document.querySelector('[data-care-topic]');
  var stage = document.querySelector('[data-care-stage]');
  if (!topic || !stage) return;

  var slides = Array.prototype.slice.call(topic.querySelectorAll('[data-care-slide]'));
  var body = stage.querySelector('[data-care-stage-body]');
  var count = stage.querySelector('[data-care-count]');
  var idx = 0, on = false;

  function show(i) {
    if (!slides.length) return;
    idx = Math.max(0, Math.min(slides.length - 1, i));
    body.innerHTML = '';
    var clone = slides[idx].cloneNode(true);
    clone.querySelectorAll('details').forEach(function (d) { d.open = true; });
    body.appendChild(clone);
    body.scrollTop = 0;
    count.textContent = (idx + 1) + ' / ' + slides.length;
  }
  function open() { on = true; stage.hidden = false; document.body.classList.add('mdc-presenting'); show(0); }
  function close() { on = false; stage.hidden = true; document.body.classList.remove('mdc-presenting'); }

  topic.querySelector('[data-care-present]').addEventListener('click', open);
  stage.querySelector('[data-care-prev]').addEventListener('click', function () { show(idx - 1); });
  stage.querySelector('[data-care-next]').addEventListener('click', function () { show(idx + 1); });
  stage.querySelector('[data-care-exit]').addEventListener('click', close);

  document.addEventListener('keydown', function (e) {
    if (!on) return;
    if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'PageDown') { e.preventDefault(); show(idx + 1); }
    else if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); show(idx - 1); }
    else if (e.key === 'Escape') { close(); }
  });

  /* 터치 스와이프 */
  var x0 = null;
  stage.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
  stage.addEventListener('touchend', function (e) {
    if (x0 === null) return;
    var dx = e.changedTouches[0].clientX - x0; x0 = null;
    if (Math.abs(dx) > 60) show(dx < 0 ? idx + 1 : idx - 1);
  }, { passive: true });
})();

/* v4.0 · 사진 크게 보기 */
(function () {
  'use strict';
  var box = document.querySelector('[data-care-lightbox]');
  if (!box) return;
  var img = box.querySelector('img'), cap = box.querySelector('.mdc-zoom__cap');
  function close() { box.hidden = true; img.src = ''; }
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-care-zoom]');
    if (a) { e.preventDefault(); img.src = a.getAttribute('href'); cap.textContent = a.getAttribute('title') || ''; box.hidden = false; return; }
    if (!box.hidden && (e.target === box || e.target.closest('[data-care-lightbox-close]'))) close();
  });
  document.addEventListener('keydown', function (e) { if (!box.hidden && e.key === 'Escape') close(); });
})();
