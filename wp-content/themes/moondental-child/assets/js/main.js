/**
 * Moon Dental — frontend interactions
 *
 * - Mobile nav toggle
 * - Sticky header shadow on scroll
 * - Scroll-reveal fade-up (IntersectionObserver, prefers-reduced-motion safe)
 * - Number counter animation for .md-trust__value
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    // v3.43.2 · 언어 스위처 드롭다운 토글 · 헤더/FAB 양쪽 대응
    document.querySelectorAll('[data-md-lang]').forEach(function (lang) {
      var btn  = lang.querySelector('.md-lang-fab__toggle, .md-lang__toggle');
      var menu = lang.querySelector('.md-lang-fab__menu, .md-lang__menu');
      if (!btn || !menu) return;
      function open() {
        menu.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
        lang.classList.add('is-open');
      }
      function close() {
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        lang.classList.remove('is-open');
      }
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (menu.hidden) open(); else close();
      });
      document.addEventListener('click', function (e) {
        if (!lang.contains(e.target)) close();
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
      });
    });

    // 1. Mobile nav toggle
    var toggle = document.querySelector('.md-header__nav-toggle');
    var nav    = document.getElementById('md-primary-menu');
    if (toggle && nav) {
      toggle.addEventListener('click', function () {
        var expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!expanded));
        nav.classList.toggle('is-open', !expanded);
        // v3.38.2 · aria 라벨은 헤더 templates에서 data-open-label / data-close-label로 주입 (Customizer 편집)
        toggle.setAttribute('aria-label', expanded
          ? (toggle.dataset.openLabel || '메뉴 열기')
          : (toggle.dataset.closeLabel || '메뉴 닫기'));
      });

      // Close on outside click
      document.addEventListener('click', function (e) {
        if (!nav.classList.contains('is-open')) return;
        if (toggle.contains(e.target) || nav.contains(e.target)) return;
        toggle.setAttribute('aria-expanded', 'false');
        nav.classList.remove('is-open');
      });
    }

    // 2. Sticky header shadow + scroll-to-top button visibility
    var header = document.querySelector('.md-site-header');
    var toTop  = document.querySelector('.md-totop');
    if (toTop) toTop.removeAttribute('hidden'); // CSS로 visibility 제어
    var showAt = 320; // px

    var onScroll = function () {
      var y = window.scrollY;
      if (header) header.classList.toggle('is-scrolled', y > 12);
      if (toTop)  toTop.classList.toggle('is-visible', y > showAt);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // 2b. Header CTA — scroll-cycled label + colors
    var cta = document.querySelector('.md-header__cta-btn[data-md-cta-cycle]');
    if (cta) {
      var ctaVariants = null;
      try { ctaVariants = JSON.parse(cta.getAttribute('data-md-cta-cycle')); }
      catch (e) { ctaVariants = null; }

      if (ctaVariants && ctaVariants.length > 1) {
        var ctaLabel = cta.querySelector('[data-md-cta-text]') || cta;
        var ctaLastIdx = 0;
        var ctaTicking = false;
        var ctaReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        // v3.30.0 · 감소 모션 사용자는 첫 variant로 고정 (scroll cycle 미노출)
        if (ctaReduced) {
          var v0 = ctaVariants[0];
          if (v0.bg)     cta.style.setProperty('--cta-bg',     v0.bg);
          if (v0.fg)     cta.style.setProperty('--cta-fg',     v0.fg);
          if (v0.shadow) cta.style.setProperty('--cta-shadow', v0.shadow);
          if (v0.label && ctaLabel) ctaLabel.textContent = v0.label;
          return;
        }

        var ctaPickIdx = function () {
          var docH = document.documentElement.scrollHeight - window.innerHeight;
          if (docH < 1) return 0;
          var p = Math.min(1, Math.max(0, window.scrollY / docH));
          // 마지막 변형도 끝까지 보이도록 length로 나눔
          var idx = Math.floor(p * ctaVariants.length);
          if (idx >= ctaVariants.length) idx = ctaVariants.length - 1;
          return idx;
        };

        var ctaApply = function (idx) {
          if (idx === ctaLastIdx) return;
          ctaLastIdx = idx;
          var v = ctaVariants[idx];
          if (!v) return;
          if (v.bg)     cta.style.setProperty('--cta-bg',     v.bg);
          if (v.fg)     cta.style.setProperty('--cta-fg',     v.fg);
          if (v.shadow) cta.style.setProperty('--cta-shadow', v.shadow);
          if (v.label && ctaLabel) {
            if (ctaReduced) {
              ctaLabel.textContent = v.label;
            } else {
              ctaLabel.classList.add('is-changing');
              setTimeout(function () {
                ctaLabel.textContent = v.label;
                ctaLabel.classList.remove('is-changing');
              }, 180);
            }
          }
        };

        var onCtaScroll = function () {
          if (ctaTicking) return;
          ctaTicking = true;
          requestAnimationFrame(function () {
            ctaApply(ctaPickIdx());
            ctaTicking = false;
          });
        };
        window.addEventListener('scroll', onCtaScroll, { passive: true });
        window.addEventListener('resize', onCtaScroll, { passive: true });
      }
    }

    // scroll-to-top 클릭 — smooth scroll, reduced-motion 사용자에겐 즉시 이동
    if (toTop) {
      toTop.addEventListener('click', function () {
        var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: prefersReduced ? 'auto' : 'smooth' });
      });
    }

    // 7b. 구강 자가진단 봇 · v3.40.0 · chief 분기 + urgent 부스트 + contra 안전 체크
    (function initDentalBot() {
      var bot = document.querySelector('[data-md-bot]');
      if (!bot) return;
      var data;
      try { data = JSON.parse(bot.getAttribute('data-md-bot-json') || '{}'); }
      catch (e) { return; }
      if (!data.questions || !data.questions.length || !data.depts) return;

      var AllQs = data.questions;         // 전체 질문
      var Qs = AllQs;                     // 현재 활성 질문 (chief 필터 적용)
      var depts = data.depts;
      var chiefOptions = data.chiefOptions || [];
      var contraMsgs = data.contraMsgs || {};
      var screens = {
        intro:  bot.querySelector('[data-md-bot-screen="intro"]'),
        chief:  bot.querySelector('[data-md-bot-screen="chief"]'),
        quiz:   bot.querySelector('[data-md-bot-screen="quiz"]'),
        safety: bot.querySelector('[data-md-bot-screen="safety"]'),
        result: bot.querySelector('[data-md-bot-screen="result"]')
      };
      var idxEl   = bot.querySelector('[data-md-bot-idx]');
      var totalEl = bot.querySelector('[data-md-bot-total]');
      var fillEl  = bot.querySelector('[data-md-bot-fill]');
      var catEl   = bot.querySelector('[data-md-bot-cat]');
      var qEl     = bot.querySelector('[data-md-bot-q]');
      var backEl  = bot.querySelector('[data-md-bot-back]');
      var resultsEl = bot.querySelector('[data-md-bot-results]');
      var resultLeadEl = bot.querySelector('[data-md-bot-result-lead]');
      var safetyListEl = bot.querySelector('[data-md-bot-safety-list]');

      var state = { idx: 0, answers: [], chief: 'all' };

      // chief 태그가 없는 (구 데이터) 또는 all 포함하면 모든 chief 에 매칭
      function questionMatchesChief(q, chief) {
        if (!q.chief || !q.chief.length) return true;
        if (chief === 'all') return true;
        return q.chief.indexOf(chief) !== -1 || q.chief.indexOf('all') !== -1;
      }
      function filterQuestions(chief) {
        return AllQs.filter(function (q) { return questionMatchesChief(q, chief); });
      }

      function show(name) {
        Object.keys(screens).forEach(function (k) {
          if (!screens[k]) return;
          if (k === name) screens[k].removeAttribute('hidden');
          else screens[k].setAttribute('hidden', '');
        });
      }

      function renderQ() {
        var q = Qs[state.idx];
        if (!q) return;
        if (idxEl)   idxEl.textContent   = String(state.idx + 1);
        if (totalEl) totalEl.textContent = String(Qs.length);
        if (fillEl)  fillEl.style.width  = ((state.idx) / Qs.length * 100).toFixed(1) + '%';
        if (catEl)   catEl.textContent   = q.cat || '';
        if (qEl)     qEl.textContent     = q.q || '';
        if (backEl)  backEl.hidden       = state.idx === 0;
      }

      function answer(isYes) {
        state.answers[state.idx] = !!isYes;
        state.idx++;
        if (state.idx >= Qs.length) {
          if (fillEl) fillEl.style.width = '100%';
          finalizeAndShow(); // v3.40.0 · contra 있으면 safety 먼저
        } else {
          renderQ();
        }
      }

      /**
       * v3.40.0 · 봇 점수 로직 · v3.39.0 개선 + urgent 부스트 + contra 수집
       *  1) raw score: 각 진료과의 원점수 합산
       *  2) hit count: 매칭 질문 개수 (신뢰도)
       *  3) urgent boost: urgent 태그 있는 Yes는 관련 진료과에 ×1.5
       *  4) 최종 score = (raw + urgentBoost) × log(1 + hits)
       *  5) contras 수집: 답이 Yes인 질문의 contra 태그 모두
       */
      function computeScores() {
        var raw = {};
        var hits = {};
        var maxPossible = {};
        var urgentBonus = {};   // urgent 태그로 boost된 만큼
        var contras = {};       // contra 태그 → true 집합
        var urgentDepts = {};   // urgent 진료과 flag
        for (var i = 0; i < Qs.length; i++) {
          var qi = Qs[i];
          var w = qi.yes || {};
          for (var k0 in w) {
            if (Object.prototype.hasOwnProperty.call(w, k0)) {
              maxPossible[k0] = (maxPossible[k0] || 0) + Number(w[k0] || 0);
            }
          }
          if (!state.answers[i]) continue;
          // 이 질문에 Yes → contra 태그 수집
          if (qi.contras && qi.contras.length) {
            for (var ci = 0; ci < qi.contras.length; ci++) contras[qi.contras[ci]] = true;
          }
          for (var k in w) {
            if (Object.prototype.hasOwnProperty.call(w, k)) {
              var v = Number(w[k] || 0);
              raw[k]  = (raw[k]  || 0) + v;
              hits[k] = (hits[k] || 0) + 1;
              if (qi.urgent) {
                // urgent · 0.5 배 추가 (=총 1.5배)
                urgentBonus[k] = (urgentBonus[k] || 0) + v * 0.5;
                urgentDepts[k] = true;
              }
            }
          }
        }
        var final = {};
        for (var key in raw) {
          if (Object.prototype.hasOwnProperty.call(raw, key)) {
            var boost = Math.log(1 + hits[key]);
            final[key] = (raw[key] + (urgentBonus[key] || 0)) * boost;
          }
        }
        return {
          scores: final, raw: raw, hits: hits, maxPossible: maxPossible,
          contras: Object.keys(contras), urgentDepts: urgentDepts
        };
      }

      // scoreData 캐시 (safety → result 전환용)
      var lastScoreData = null;

      /** v3.40.0 · 결과 진행: 우선 contra 체크 → 있으면 safety 화면, 없으면 바로 result */
      function finalizeAndShow() {
        lastScoreData = computeScores();
        // contra 있으면 안전 화면 먼저
        if (lastScoreData.contras && lastScoreData.contras.length && safetyListEl && screens.safety) {
          renderSafety(lastScoreData.contras);
          show('safety');
          return;
        }
        renderResult();
        show('result');
      }

      function renderSafety(contraKeys) {
        safetyListEl.innerHTML = '';
        for (var i = 0; i < contraKeys.length; i++) {
          var key = contraKeys[i];
          var msg = contraMsgs[key];
          if (!msg) continue;
          var li = document.createElement('li');
          li.className = 'md-bot__safety-item';
          li.innerHTML =
            '<span class="md-bot__safety-item-icon" aria-hidden="true">⚠️</span>' +
            '<span class="md-bot__safety-item-msg">' + escapeHtml(msg) + '</span>';
          safetyListEl.appendChild(li);
        }
      }

      function renderResult() {
        var scoreData = lastScoreData || computeScores();
        var scores = scoreData.scores;
        var maxPossible = scoreData.maxPossible;
        var urgentDepts = scoreData.urgentDepts || {};
        var keys = Object.keys(scores).sort(function (a, b) { return scores[b] - scores[a]; });
        keys = keys.filter(function (k) { return scores[k] > 0; });

        if (!resultsEl) return;
        resultsEl.innerHTML = '';

        var BOT = (window.MoondentalMain && MoondentalMain.bot) || {};
        if (!keys.length) {
          if (resultLeadEl) resultLeadEl.textContent = BOT.noneMatch || '특별한 증상은 없으신 것 같습니다. 정기 검진·스케일링을 권해드립니다.';
          var d = depts['일반-검진'];
          if (d) keys = ['일반-검진'];
        } else if (resultLeadEl) {
          var n = Math.min(keys.length, 3);
          resultLeadEl.textContent = keys.length === 1
            ? (BOT.singleBest || '아래 진료과가 가장 적합합니다.')
            : (BOT.multipleTpl || '아래 {n}개 진료과를 우선순위로 추천드립니다.').replace('{n}', n);
        }

        var topMax = Math.min(keys.length, 3);
        var urgentLabel = BOT.urgentLabel || '⚡ 우선 상담 권장';
        for (var i = 0; i < topMax; i++) {
          var key = keys[i];
          var d = depts[key];
          if (!d) continue;
          var mp = maxPossible[key] || 1;
          var pct = Math.round((scoreData.raw[key] / mp) * 100);
          if (pct > 100) pct = 100;
          if (pct < 20)  pct = 20;
          var rank = i + 1;
          var isUrgent = !!urgentDepts[key];
          var card = document.createElement('a');
          card.className = 'md-bot-card md-bot-card--rank-' + rank + (isUrgent ? ' md-bot-card--urgent' : '');
          card.setAttribute('href', d.url);
          card.setAttribute('role', 'listitem');
          card.setAttribute('data-track', 'cta-bot-dept-' + key);
          card.innerHTML =
            '<div class="md-bot-card__rank">#' + rank + '</div>' +
            '<div class="md-bot-card__body">' +
              (isUrgent ? '<span class="md-bot-card__urgent-tag">' + escapeHtml(urgentLabel) + '</span>' : '') +
              '<div class="md-bot-card__name">' + escapeHtml(d.name) + '</div>' +
              '<div class="md-bot-card__sub">' + escapeHtml(d.sub || '') + '</div>' +
              '<p class="md-bot-card__summary">' + escapeHtml(d.summary || '') + '</p>' +
              '<div class="md-bot-card__match" aria-label="' + escapeHtml(BOT.matchAria || '적합도') + '">' +
                '<div class="md-bot-card__match-bar"><span style="width:' + pct + '%"></span></div>' +
                '<span class="md-bot-card__match-text">' + escapeHtml((BOT.matchTpl || '적합도 {pct}%').replace('{pct}', pct)) + '</span>' +
              '</div>' +
            '</div>' +
            '<div class="md-bot-card__arrow" aria-hidden="true">→</div>';
          resultsEl.appendChild(card);
        }
      }

      function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
          return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
        });
      }

      // v3.40.0 · 시작 → chief 화면 (chief 옵션 없으면 바로 quiz)
      bot.querySelectorAll('[data-md-bot-start]').forEach(function (b) {
        b.addEventListener('click', function () {
          state = { idx: 0, answers: [], chief: 'all' };
          lastScoreData = null;
          if (fillEl) fillEl.style.width = '0%';
          if (chiefOptions.length && screens.chief) {
            show('chief');
          } else {
            Qs = AllQs;
            show('quiz');
            renderQ();
          }
        });
      });
      // v3.40.0 · chief 선택 → 필터 적용 후 quiz
      bot.querySelectorAll('[data-md-bot-chief]').forEach(function (b) {
        b.addEventListener('click', function () {
          state.chief = b.getAttribute('data-md-bot-chief') || 'all';
          Qs = filterQuestions(state.chief);
          if (!Qs.length) Qs = AllQs; // 필터 결과 0개면 전체로 fallback
          state.idx = 0; state.answers = [];
          show('quiz');
          renderQ();
        });
      });
      bot.querySelectorAll('[data-md-bot-answer]').forEach(function (b) {
        b.addEventListener('click', function () {
          answer(b.getAttribute('data-md-bot-answer') === 'yes');
        });
      });
      if (backEl) backEl.addEventListener('click', function () {
        if (state.idx > 0) {
          state.idx--;
          state.answers.pop();
          renderQ();
        }
      });
      // v3.40.0 · safety 화면 확인 후 결과로
      bot.querySelectorAll('[data-md-bot-safety-continue]').forEach(function (b) {
        b.addEventListener('click', function () {
          renderResult();
          show('result');
        });
      });
      bot.querySelectorAll('[data-md-bot-restart]').forEach(function (b) {
        b.addEventListener('click', function () {
          state = { idx: 0, answers: [], chief: 'all' };
          lastScoreData = null;
          Qs = AllQs;
          if (fillEl) fillEl.style.width = '0%';
          show('intro');
        });
      });
    })();

    // 8. 비용 안내 페이지 — 치료별 비용 탭 전환
    document.querySelectorAll('[data-pricetab]').forEach(function (root) {
      var btns   = root.querySelectorAll('[data-pricetab-target]');
      var panels = root.querySelectorAll('[data-pricetab-panel]');
      btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var target = btn.getAttribute('data-pricetab-target');
          btns.forEach(function (b) {
            var on = b === btn;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
          });
          panels.forEach(function (p) {
            p.classList.toggle('is-active', p.getAttribute('data-pricetab-panel') === target);
          });
        });
      });
    });

    // 9. 의료진 페이지 — 진료 분야별 필터
    var docFilterBtns = document.querySelectorAll('[data-doc-filter]');
    var docCards      = document.querySelectorAll('[data-doc-group]');
    if (docFilterBtns.length && docCards.length) {
      docFilterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var target = btn.getAttribute('data-doc-filter');
          docFilterBtns.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
          docCards.forEach(function (card) {
            var show = (target === 'all') || (card.getAttribute('data-doc-group') === target);
            card.classList.toggle('is-hidden', !show);
          });
        });
      });
    }

    // 3. Reduced motion respect — skip everything below
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // 4. Auto-apply .md-reveal to common elements that should fade in.
    // Templates can already set .md-reveal manually; we add it to sensible defaults.
    var autoRevealSelectors = [
      '.md-section-head',
      '.md-card',
      '.md-service-card',
      '.md-team-card',
      '.md-testimonial',
      '.md-info-block',
      '.md-news-card',
      '.md-doc-row',
      '.md-cta-banner',
      '.md-trust__item'
    ];
    autoRevealSelectors.forEach(function (sel) {
      document.querySelectorAll(sel).forEach(function (el) {
        if (!el.classList.contains('md-reveal')) el.classList.add('md-reveal');
      });
    });

    // 5. Stagger reveal-delay for items within a grid (siblings share a parent)
    // Group by closest .md-service-grid / .md-team-grid / .md-trust__grid / .md-testimonials
    var staggerParents = document.querySelectorAll(
      '.md-service-grid, .md-team-grid, .md-trust__grid, .md-testimonials, .md-news-grid, .md-info-grid'
    );
    staggerParents.forEach(function (parent) {
      Array.prototype.slice.call(parent.children).forEach(function (child, idx) {
        if (idx > 0 && idx <= 7 && child.classList.contains('md-reveal')) {
          child.setAttribute('data-reveal-delay', String(idx));
        }
      });
    });

    // 6. IntersectionObserver-based reveal · v3.41.0 · 자동 부여 확장
    // 페이지 로드 시 프론트 콘텐츠 요소에 자동으로 .md-reveal 클래스 부여.
    // 카드·섹션 헤더·주요 블록이 스크롤 시 부드럽게 fade-up.
    (function autoAssignReveal() {
      var selectors = [
        // 주요 섹션 컴포넌트 (첫 히어로 제외)
        '.md-section-head',
        '.md-hero-combined__stat',
        '.md-hero-combined__quote',
        '.md-hero-combined__certs > li',
        // 카드 그룹
        '.md-service-card', '.md-service-grid > article',
        '.md-news-card',
        '.md-doccard', '.md-doc-row',
        '.md-strength-card',
        '.md-preservation-card',
        '.md-region-card', '.md-region-pill',
        '.md-testimonial',
        '.md-enc-card',
        '.md-bot-card',
        '.md-facility-card',
        '.md-priceX-card',
        '.md-team-card',
        // 예방클리닉 신규 블록
        '.md-spa-why', '.md-spa-oral__item', '.md-spa-who', '.md-spa-after',
        '.md-spa-flow__item', '.md-spa-test__item',
        // 자가진단봇 intro 요소
        '.md-bot__intro-step', '.md-bot__intro-chips',
        '.md-bot__chief-btn',
        // CTA 배너
        '.md-cta-banner',
      ];
      var alreadyClass = ':not(.md-reveal)';
      var enhanceable = document.querySelectorAll(
        selectors.map(function (s) { return s + alreadyClass; }).join(',')
      );
      enhanceable.forEach(function (el, i) {
        el.classList.add('md-reveal');
        // 형제 카드/스텝은 stagger delay 부여 (같은 부모 내 몇 번째인지)
        if (!el.hasAttribute('data-reveal-delay')) {
          var siblingIndex = 0;
          var prev = el.previousElementSibling;
          while (prev) {
            if (prev.classList && prev.classList.contains('md-reveal')) siblingIndex++;
            prev = prev.previousElementSibling;
          }
          if (siblingIndex > 0 && siblingIndex < 7) {
            el.setAttribute('data-reveal-delay', String(siblingIndex));
          }
        }
      });
    })();

    var revealEls = document.querySelectorAll('.md-reveal');
    if (revealEls.length) {
      if (reducedMotion || !('IntersectionObserver' in window)) {
        revealEls.forEach(function (el) { el.classList.add('is-revealed'); });
      } else {
        var io = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-revealed');
              io.unobserve(entry.target);
            }
          });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        revealEls.forEach(function (el) { io.observe(el); });
      }
    }

    // 7. Number counter animation for trust band
    // Reads data-count-to. Numeric values animate from 0→target; non-numeric (e.g. "1:1") just reveal.
    var counters = document.querySelectorAll('.md-trust__value[data-count-to]');
    if (counters.length) {
      var animateCount = function (el, target, duration) {
        var startTime = null;
        var initial   = 0;
        var step = function (ts) {
          if (!startTime) startTime = ts;
          var progress = Math.min((ts - startTime) / duration, 1);
          // ease-out cubic
          var eased = 1 - Math.pow(1 - progress, 3);
          var current = Math.floor(initial + (target - initial) * eased);
          el.textContent = String(current);
          if (progress < 1) requestAnimationFrame(step);
          else el.textContent = String(target);
        };
        requestAnimationFrame(step);
      };

      if (reducedMotion || !('IntersectionObserver' in window)) {
        // Leave values as-is.
      } else {
        var countIO = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var el  = entry.target;
            var raw = el.getAttribute('data-count-to') || '';
            var num = parseInt(raw, 10);
            if (!isNaN(num) && /^\d+$/.test(raw.trim())) {
              animateCount(el, num, 1400);
            }
            countIO.unobserve(el);
          });
        }, { threshold: 0.5 });
        counters.forEach(function (el) {
          var raw = el.getAttribute('data-count-to') || '';
          if (/^\d+$/.test(raw.trim())) {
            el.textContent = '0';
          }
          countIO.observe(el);
        });
      }
    }

  });

  /* v3.44.154 · 헤더 nav 오버플로우 동적 감지
     실제 nav 가 header 안에 안 들어가면 <html> 에 is-mobile-menu 부여 → 햄버거
     들어가면 클래스 제거 → 데스크탑 nav 표시 */
  (function () {
    var html = document.documentElement;
    function measure() {
      // ≤1080 은 이미 CSS 미디어쿼리로 햄버거 → JS 처리 스킵
      if ( window.innerWidth <= 1080 ) {
        html.classList.remove('is-mobile-menu');
        return;
      }
      var inner = document.querySelector('.md-header__inner');
      var nav   = document.querySelector('.md-header__nav');
      var brand = document.querySelector('.md-header__brand');
      var aside = document.querySelector('.md-header__aside');
      if ( ! inner || ! nav || ! brand || ! aside ) return;

      // 측정 전 · 데스크탑 상태로 만들기 (햄버거 상태에서 측정하면 nav 가 fixed 라 정확한 크기 안 나옴)
      var wasMobile = html.classList.contains('is-mobile-menu');
      if ( wasMobile ) html.classList.remove('is-mobile-menu');

      // 레이아웃 반영 대기
      requestAnimationFrame(function () {
        var innerW = inner.clientWidth;
        var brandW = brand.offsetWidth;
        var asideW = aside.offsetWidth;
        var ul = nav.querySelector('ul');
        var navContent = ul ? ul.scrollWidth : 0;
        // grid 는 [brand]gap[nav]gap[aside] · gap 은 clamp(24,2.5vw,56) · padding 도 있음
        var reservedGap = 80;    // 2 gaps
        var padding = 40;
        var available = innerW - brandW - asideW - reservedGap - padding;
        // 30px 안전 마진 (실제로 조금이라도 겹칠 가능성 방지)
        if ( navContent > available - 30 ) {
          html.classList.add('is-mobile-menu');
        } else {
          html.classList.remove('is-mobile-menu');
        }
      });
    }

    var resizeTimer = null;
    function debounceMeasure() {
      clearTimeout( resizeTimer );
      resizeTimer = setTimeout( measure, 80 );
    }

    if ( document.readyState === 'loading' ) {
      document.addEventListener( 'DOMContentLoaded', measure );
    } else {
      measure();
    }
    window.addEventListener( 'resize', debounceMeasure );
    window.addEventListener( 'load', measure );
    if ( document.fonts && document.fonts.ready ) {
      document.fonts.ready.then( measure );
    }
  })();
})();
