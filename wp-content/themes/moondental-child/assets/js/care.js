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


/* v4.2 · 관리자 편집 — 업로드(끌어다 놓기) · 삭제 · 순서 · 제목 */
(function () {
  'use strict';
  var topic = document.querySelector('[data-care-topic][data-care-nonce]');
  if (!topic) return;
  var ajax = topic.getAttribute('data-care-ajax'), nonce = topic.getAttribute('data-care-nonce'), slug = topic.getAttribute('data-slug');
  var toggle = topic.querySelector('[data-care-edit-toggle]'), bar = topic.querySelector('[data-care-editbar]');
  var editing = false;

  function setEditing(on) {
    editing = on;
    topic.classList.toggle('is-editing', on);
    if (toggle) { toggle.setAttribute('aria-pressed', on ? 'true' : 'false'); toggle.textContent = on ? '✓ 편집 끝' : '✏️ 편집'; }
    if (bar) bar.hidden = !on;
    topic.querySelectorAll('[data-care-drop]').forEach(function (d) { d.hidden = !on; });
    topic.querySelectorAll('[data-care-caption]').forEach(function (c) { c.contentEditable = on ? 'true' : 'false'; });
  }
  if (toggle) toggle.addEventListener('click', function () { setEditing(!editing); });

  function post(action, data, onProgress) {
    return new Promise(function (resolve, reject) {
      var fd = data instanceof FormData ? data : new FormData();
      if (!(data instanceof FormData)) Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
      fd.append('action', action); fd.append('nonce', nonce); fd.append('slug', slug);
      var x = new XMLHttpRequest(); x.open('POST', ajax);
      if (onProgress) x.upload.addEventListener('progress', function (e) { if (e.lengthComputable) onProgress(Math.round(e.loaded / e.total * 100)); });
      x.onload = function () { try { var r = JSON.parse(x.responseText); r.success ? resolve(r.data) : reject((r.data && r.data.message) || '실패'); } catch (e) { reject('서버 응답 오류 (' + x.status + ')'); } };
      x.onerror = function () { reject('네트워크 오류'); };
      x.send(fd);
    });
  }
  function toast(msg) {
    var t = document.createElement('div'); t.className = 'mdc-toast'; t.textContent = msg; document.body.appendChild(t);
    requestAnimationFrame(function () { t.classList.add('is-on'); }); setTimeout(function () { t.remove(); }, 2600);
  }
  function reload(tab) { location.hash = tab; location.reload(); }

  /* 삭제 · 순서 · 제목 */
  topic.addEventListener('click', function (e) {
    if (!editing) return;
    var card = e.target.closest('[data-care-item]'); if (!card) return;
    var kind = card.getAttribute('data-care-item'), index = parseInt(card.getAttribute('data-index'), 10);
    if (e.target.closest('[data-care-del]')) {
      e.preventDefault();
      if (!confirm('이 ' + (kind === 'video' ? '영상' : '사진') + '을 삭제할까요? 파일도 함께 지워집니다.')) return;
      post('md_care_delete', { kind: kind, index: index }).then(function () { card.remove(); renumber(kind); toast('삭제했습니다'); }, function (m) { alert(m); });
    } else if (e.target.closest('[data-care-move]')) {
      e.preventDefault();
      var dir = parseInt(e.target.closest('[data-care-move]').getAttribute('data-care-move'), 10);
      var to = index + dir; var grid = card.parentNode; var n = grid.children.length;
      if (to < 0 || to >= n) return;
      post('md_care_move', { kind: kind, from: index, to: to }).then(function () {
        var sib = dir < 0 ? card.previousElementSibling : card.nextElementSibling;
        if (sib) { dir < 0 ? grid.insertBefore(card, sib) : grid.insertBefore(sib, card); }
        renumber(kind);
      }, function (m) { alert(m); });
    } else if (e.target.closest('[data-care-caption]') || e.target.closest('a[data-care-zoom]')) {
      if (e.target.closest('a[data-care-zoom]')) e.preventDefault(); // 편집 중에는 확대 대신 제목 편집
      var cap = card.querySelector('[data-care-caption]'); if (cap) { cap.focus(); }
    }
  }, true);
  topic.addEventListener('focusout', function (e) {
    var cap = e.target.closest && e.target.closest('[data-care-caption]');
    if (!cap || !editing) return;
    var card = cap.closest('[data-care-item]'); var kind = card.getAttribute('data-care-item'), index = parseInt(card.getAttribute('data-index'), 10);
    var text = cap.textContent.trim();
    if (text === (card.getAttribute('data-caption') || cap.getAttribute('data-orig') || '')) return;
    post('md_care_caption', { kind: kind, index: index, text: text }).then(function (d) { cap.textContent = d.text; card.setAttribute('data-caption', d.text); toast('제목을 저장했습니다'); }, function (m) { alert(m); });
  });
  topic.addEventListener('keydown', function (e) { if (e.target.closest && e.target.closest('[data-care-caption]') && e.key === 'Enter') { e.preventDefault(); e.target.blur(); } });
  function renumber(kind) {
    var cards = topic.querySelectorAll('[data-care-item="' + kind + '"]');
    cards.forEach(function (c, i) { c.setAttribute('data-index', i); var z = c.querySelector('[data-care-zoom]'); if (z) z.setAttribute('data-care-zoom', i); });
    var cnt = topic.querySelector('[data-care-count="' + kind + '"]'); if (cnt) cnt.textContent = cards.length;
  }

  /* 업로드 */
  function posterFromVideo(file) {
    return new Promise(function (resolve) {
      var url = URL.createObjectURL(file); var v = document.createElement('video'); v.muted = true; v.playsInline = true; v.preload = 'auto'; v.src = url;
      var done = false; function finish(blob) { if (done) return; done = true; URL.revokeObjectURL(url); resolve(blob); }
      v.addEventListener('loadeddata', function () { try { v.currentTime = Math.min(1, (v.duration || 2) / 2); } catch (e) { finish(null); } });
      v.addEventListener('seeked', function () {
        try { var c = document.createElement('canvas'); var w = 640, h = Math.round(640 * v.videoHeight / v.videoWidth) || 360; c.width = w; c.height = h; c.getContext('2d').drawImage(v, 0, 0, w, h); c.toBlob(function (b) { finish(b); }, 'image/jpeg', 0.82); } catch (e) { finish(null); }
      });
      v.addEventListener('error', function () { finish(null); });
      setTimeout(function () { finish(null); }, 8000);
    });
  }
  function upload(kind, files) {
    var drop = topic.querySelector('[data-care-drop="' + kind + '"]'); var prog = drop.querySelector('[data-care-prog]');
    var list = Array.prototype.slice.call(files); if (!list.length) return;
    prog.hidden = false; prog.textContent = '준비 중…'; drop.classList.add('is-busy');
    var chain = Promise.resolve();
    list.forEach(function (file, i) {
      chain = chain.then(function () {
        var fd = new FormData(); fd.append('kind', kind); fd.append('file', file, file.name);
        var p = kind === 'video' ? posterFromVideo(file).then(function (b) { if (b) fd.append('poster', b, 'poster.jpg'); }) : Promise.resolve();
        return p.then(function () {
          return post('md_care_upload', fd, function (pct) { prog.textContent = (i + 1) + '/' + list.length + ' · ' + file.name + ' · ' + pct + '%'; });
        }).then(function (d) { if (d.errors && d.errors.length) alert(d.errors.join('\n')); });
      });
    });
    chain.then(function () { prog.textContent = '완료 — 화면을 새로 고칩니다'; reload(kind === 'video' ? 'videos' : 'photos'); }, function (m) { alert(m); prog.hidden = true; drop.classList.remove('is-busy'); });
  }
  topic.querySelectorAll('[data-care-file]').forEach(function (inp) {
    inp.addEventListener('change', function () { upload(inp.getAttribute('data-care-file'), inp.files); inp.value = ''; });
  });
  topic.querySelectorAll('[data-care-drop]').forEach(function (d) {
    ['dragenter', 'dragover'].forEach(function (ev) { d.addEventListener(ev, function (e) { e.preventDefault(); d.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { d.addEventListener(ev, function (e) { e.preventDefault(); d.classList.remove('is-over'); }); });
    d.addEventListener('drop', function (e) { upload(d.getAttribute('data-care-drop'), e.dataTransfer.files); });
  });
  var reset = topic.querySelector('[data-care-reset]');
  if (reset) reset.addEventListener('click', function () {
    if (!confirm('편집 내용을 버리고 원래 목록으로 되돌릴까요? (올린 파일은 지워지지 않습니다)')) return;
    post('md_care_reset', {}).then(function () { reload('photos'); }, function (m) { alert(m); });
  });
})();
