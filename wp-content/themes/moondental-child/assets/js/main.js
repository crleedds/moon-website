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

    // scroll-to-top 클릭 — smooth scroll, reduced-motion 사용자에겐 즉시 이동
    if (toTop) {
      toTop.addEventListener('click', function () {
        var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: prefersReduced ? 'auto' : 'smooth' });
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
