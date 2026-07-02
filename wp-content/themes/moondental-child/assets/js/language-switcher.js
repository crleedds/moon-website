/*!
 * moondental language switcher — Google Translate 기반 6개국어 드롭다운
 * 지원: ko(기본) / en / zh-CN / vi / ru / mn
 */
(function () {
	'use strict';

	var LANG_LABELS = {
		'ko':    '한국어',
		'en':    'English',
		'zh-CN': '中文',
		'vi':    'Tiếng Việt',
		'ru':    'Русский',
		'mn':    'Монгол'
	};
	var STORAGE_KEY = 'md_lang';
	var GT_LOAD_TIMEOUT_MS = 15000;

	// ── 1) Google Translate 초기화 콜백 ──────────
	window.googleTranslateElementInit = function () {
		try {
			new google.translate.TranslateElement({
				pageLanguage: 'ko',
				includedLanguages: 'ko,en,zh-CN,vi,ru,mn',
				autoDisplay: false,
				layout: google.translate.TranslateElement.InlineLayout.SIMPLE
			}, 'google_translate_element');
		} catch (e) { /* Google Translate 로드 실패 시 조용히 무시 */ }
	};

	// ── 2) Google Translate 상단 배너 강제 숨김 ──
	function suppressGoogleBar() {
		if (document.body) {
			document.body.style.top = '0px';
			document.body.style.position = 'static';
		}
		var iframe = document.querySelector('iframe.goog-te-banner-frame');
		if (iframe) iframe.style.display = 'none';
	}
	// 페이지 로드 후 반복 억제 (Google이 다시 삽입할 수 있어서)
	setInterval(suppressGoogleBar, 1000);

	// ── 3) 드롭다운 동작 ────────────────────────
	document.addEventListener('DOMContentLoaded', function () {
		var switcher = document.getElementById('md-lang-switcher');
		if (!switcher) return;
		var btn     = switcher.querySelector('.md-lang-switcher__btn');
		var menu    = switcher.querySelector('.md-lang-switcher__menu');
		var current = switcher.querySelector('.md-lang-switcher__current');
		if (!btn || !menu || !current) return;

		function openMenu() {
			btn.setAttribute('aria-expanded', 'true');
			menu.hidden = false;
			switcher.classList.add('is-open');
		}
		function closeMenu() {
			btn.setAttribute('aria-expanded', 'false');
			menu.hidden = true;
			switcher.classList.remove('is-open');
		}

		btn.addEventListener('click', function (e) {
			e.stopPropagation();
			if (btn.getAttribute('aria-expanded') === 'true') closeMenu();
			else openMenu();
		});

		document.addEventListener('click', function (e) {
			if (!switcher.contains(e.target)) closeMenu();
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') closeMenu();
		});

		function selectLanguage(lang) {
			if (!LANG_LABELS[lang]) return;
			current.textContent = LANG_LABELS[lang];
			closeMenu();

			// localStorage 저장 (다음 방문 시 복원)
			try { localStorage.setItem(STORAGE_KEY, lang); } catch (e) {}

			// Google Translate 콤보에 값 세팅 + change 이벤트 발생
			var combo = document.querySelector('.goog-te-combo');
			if (combo) {
				combo.value = (lang === 'ko') ? '' : lang;
				var ev;
				try { ev = new Event('change', { bubbles: true }); }
				catch (e) { ev = document.createEvent('HTMLEvents'); ev.initEvent('change', true, true); }
				combo.dispatchEvent(ev);
			} else {
				// 아직 로드 안 됐으면 폴링해서 재시도
				waitForGoogleTranslate(function (c) {
					c.value = (lang === 'ko') ? '' : lang;
					var ev2;
					try { ev2 = new Event('change', { bubbles: true }); }
					catch (e) { ev2 = document.createEvent('HTMLEvents'); ev2.initEvent('change', true, true); }
					c.dispatchEvent(ev2);
				});
			}
		}

		menu.addEventListener('click', function (e) {
			var li = e.target.closest('.md-lang-switcher__item');
			if (!li) return;
			selectLanguage(li.getAttribute('data-lang'));
		});
		menu.addEventListener('keydown', function (e) {
			if (e.key !== 'Enter' && e.key !== ' ') return;
			var li = e.target.closest('.md-lang-switcher__item');
			if (!li) return;
			e.preventDefault();
			selectLanguage(li.getAttribute('data-lang'));
		});

		// ── 4) 저장된 언어 복원 ─────────────────
		var saved = null;
		try { saved = localStorage.getItem(STORAGE_KEY); } catch (e) {}
		if (saved && saved !== 'ko' && LANG_LABELS[saved]) {
			current.textContent = LANG_LABELS[saved];
			waitForGoogleTranslate(function (combo) {
				combo.value = saved;
				var ev;
				try { ev = new Event('change', { bubbles: true }); }
				catch (e) { ev = document.createEvent('HTMLEvents'); ev.initEvent('change', true, true); }
				combo.dispatchEvent(ev);
			});
		}
	});

	function waitForGoogleTranslate(cb) {
		var start = Date.now();
		var iv = setInterval(function () {
			var combo = document.querySelector('.goog-te-combo');
			if (combo) { clearInterval(iv); cb(combo); }
			else if (Date.now() - start > GT_LOAD_TIMEOUT_MS) clearInterval(iv);
		}, 200);
	}
})();
