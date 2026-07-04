/*!
 * moondental language switcher — Google Translate 기반 6개국어 드롭다운
 * 지원: ko(기본) / en / zh-CN / vi / ru / mn
 * v3.25.5 — MutationObserver로 GT 준비 감지 + 즉시 반응 + 트리플 이벤트 발생
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
	var LOG_PREFIX = '[MD-LangSwitch]';
	var pendingLang = null;
	var gtReady = false;
	var comboEl = null;

	function log() {
		if (window.console && console.log) {
			var args = Array.prototype.slice.call(arguments);
			args.unshift(LOG_PREFIX);
			console.log.apply(console, args);
		}
	}
	function warn() {
		if (window.console && console.warn) {
			var args = Array.prototype.slice.call(arguments);
			args.unshift(LOG_PREFIX);
			console.warn.apply(console, args);
		}
	}

	log('스크립트 로드됨');

	// ── 1) Google Translate 초기화 콜백 ──────────
	window.googleTranslateElementInit = function () {
		log('googleTranslateElementInit 콜백 실행');
		try {
			if (!window.google || !window.google.translate) {
				warn('window.google.translate 없음');
				return;
			}
			new google.translate.TranslateElement({
				pageLanguage: 'ko',
				includedLanguages: 'ko,en,zh-CN,vi,ru,mn',
				autoDisplay: false,
				layout: google.translate.TranslateElement.InlineLayout.SIMPLE
			}, 'google_translate_element');
			log('TranslateElement 인스턴스 생성 완료');
			// MutationObserver로 combo 실제 생성 시점 감지
			observeForCombo();
		} catch (e) {
			warn('TranslateElement 실패:', e && e.message);
		}
	};

	// ── 2) 상단 Google 배너 강제 숨김 ────────────
	function suppressGoogleBar() {
		if (document.body) {
			document.body.style.top = '0px';
			document.body.style.position = 'static';
		}
		var iframe = document.querySelector('iframe.goog-te-banner-frame');
		if (iframe) iframe.style.display = 'none';
	}
	setInterval(suppressGoogleBar, 1000);

	// ── 3) MutationObserver — combo가 DOM에 나타나면 즉시 반응 ──
	function observeForCombo() {
		var check = document.querySelector('.goog-te-combo');
		if (check) {
			comboEl = check;
			gtReady = true;
			log('.goog-te-combo 발견 (즉시)');
			flushPending();
			return;
		}
		if (typeof MutationObserver === 'undefined') {
			// 폴백: 폴링
			var start = Date.now();
			var iv = setInterval(function () {
				var c = document.querySelector('.goog-te-combo');
				if (c) {
					clearInterval(iv);
					comboEl = c;
					gtReady = true;
					log('.goog-te-combo 발견 (폴링)');
					flushPending();
				} else if (Date.now() - start > 30000) {
					clearInterval(iv);
					warn('.goog-te-combo 30초 안에 못 찾음');
				}
			}, 100);
			return;
		}
		var target = document.getElementById('google_translate_element') || document.body;
		var mo = new MutationObserver(function () {
			var c = document.querySelector('.goog-te-combo');
			if (c) {
				mo.disconnect();
				comboEl = c;
				gtReady = true;
				log('.goog-te-combo 발견 (MutationObserver)');
				flushPending();
			}
		});
		mo.observe(target, { childList: true, subtree: true });
		// 30초 안전 타이머
		setTimeout(function () { mo.disconnect(); }, 30000);
	}

	function flushPending() {
		if (pendingLang) {
			log('대기 중 언어 즉시 적용:', pendingLang);
			var pl = pendingLang;
			pendingLang = null;
			applyLanguage(pl);
		}
	}

	// ── 4) 언어 적용 (트리플 이벤트로 신뢰성 향상) ──
	function applyLanguage(lang) {
		if (!LANG_LABELS[lang]) return;
		if (!gtReady || !comboEl) {
			log('GT 준비 전. 대기열 등록:', lang);
			pendingLang = lang;
			return;
		}
		comboEl.value = (lang === 'ko') ? '' : lang;
		var fire = function () {
			var ev;
			try { ev = new Event('change', { bubbles: true }); }
			catch (e) { ev = document.createEvent('HTMLEvents'); ev.initEvent('change', true, true); }
			comboEl.dispatchEvent(ev);
		};
		fire();
		setTimeout(fire, 30);
		setTimeout(fire, 150);
		setTimeout(fire, 400);
		log('언어 변경 적용:', lang);
	}

	// ── 5) 드롭다운 UI ──────────────────────────
	document.addEventListener('DOMContentLoaded', function () {
		log('DOMContentLoaded');
		// Google Translate CDN 로드 (테마 head에서 이미 붙는 경우 중복 방지)
		if (!window.__mdGTCDNLoaded) {
			window.__mdGTCDNLoaded = true;
			var s = document.createElement('script');
			s.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
			s.async = true;
			s.onerror = function () {
				warn('메인 CDN 실패. 폴백 시도');
				var s2 = document.createElement('script');
				s2.src = 'https://translate.googleapis.com/translate_a/element.js?cb=googleTranslateElementInit';
				s2.async = true;
				document.head.appendChild(s2);
			};
			document.head.appendChild(s);
		}

		var switcher = document.getElementById('md-lang-switcher');
		if (!switcher) { warn('#md-lang-switcher 없음'); return; }
		var btn     = switcher.querySelector('.md-lang-switcher__btn');
		var menu    = switcher.querySelector('.md-lang-switcher__menu');
		var current = switcher.querySelector('.md-lang-switcher__current');
		if (!btn || !menu || !current) { warn('스위처 내부 요소 누락'); return; }

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
			log('사용자 선택:', lang);
			current.textContent = LANG_LABELS[lang];
			closeMenu();
			try { localStorage.setItem(STORAGE_KEY, lang); } catch (e) {}
			applyLanguage(lang);
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

		// 저장된 언어 자동 복원
		var saved = null;
		try { saved = localStorage.getItem(STORAGE_KEY); } catch (e) {}
		if (saved && saved !== 'ko' && LANG_LABELS[saved]) {
			log('저장된 언어 복원:', saved);
			current.textContent = LANG_LABELS[saved];
			applyLanguage(saved);
		}
	});
})();
