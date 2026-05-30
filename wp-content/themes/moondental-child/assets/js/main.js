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

    // 1. Mobile nav toggle
    var toggle = document.querySelector('.md-header__nav-toggle');
    var nav    = document.getElementById('md-primary-menu');
    if (toggle && nav) {
      toggle.addEventListener('click', function () {
        var expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!expanded));
        nav.classList.toggle('is-open', !expanded);
        toggle.setAttribute('aria-label', expanded ? '메뉴 열기' : '메뉴 닫기');
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

    // 9. 의료진 페이지 — 층별 필터 (전체/9F/10F/11F)
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

    // 6. IntersectionObserver-based reveal
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
        }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });
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
})();
