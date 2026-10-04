/* 문치과병원 v5 · 세로로 긴 소식 사진은 위쪽(제목)이 보이게
 * (지연 로딩 플러그인이 src 를 나중에 바꾸므로 load 마다 다시 확인) */
(function () {
  function mark(img) {
    if (img.naturalWidth > 2 && img.naturalHeight > img.naturalWidth * 1.1) img.classList.add('is-tall');
  }
  document.querySelectorAll('.v5-feat__ph img').forEach(function (img) {
    img.addEventListener('load', function () { mark(img); });
    if (img.complete) mark(img);
  });
})();
