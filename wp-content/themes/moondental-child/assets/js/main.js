/**
 * Moon Dental — frontend interactions
 *
 * - Mobile nav toggle
 * - Sticky header shadow on scroll
 * - Service card whole-card click delegation
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

    // 2. Sticky header shadow on scroll
    var header = document.querySelector('.md-site-header');
    if (header) {
      var onScroll = function () {
        if (window.scrollY > 12) {
          header.classList.add('is-scrolled');
        } else {
          header.classList.remove('is-scrolled');
        }
      };
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
    }

  });
})();
