/*!
 * moondental language switcher — Google Translate 기반 6개국어 드롭다운
 * 지원: ko(기본) / en / zh-CN / vi / ru / mn
 * v3.25.3 — 진단 로그 + 재시도 + 폴백 CDN 추가
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
	var GT_LOAD_TIMEOUT_MS = 20000;
	var LOG_PREFIX = '[MD-LangSwitch]';
	var pendingLang = null; // 사용자가 선택했는데 GT가 아직 안 로드된 경우 저장

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

	log('스크립트 로드됨. Google Translate CDN 대기 중...');

	// ── 1) Google Translate 초기화 콜백 (전역) ──────────
	window.googleTranslateElementInit = function () {
		log('googleTranslateElementInit 콜백 실행');
		try {
			if (!window.google || !window.google.translate) {
				warn('window.google.translate 객체 없음');
				return;
			}
			new google.translate.TranslateElement({
				pageLanguage: 'ko',
				includedLanguages: 'ko,en,zh-CN,vi,ru,mn',
				autoDisplay: false,
				layout: google.translate.TranslateElement.InlineLayout.SIMPLE
			}, 'google_translate_element');
			log('TranslateElement 인스턴스 생성 완료');

			// 초기화 완료 후 pending 언어가 있으면 즉시 적용
			if (pendingLang) {
				log('pendingLang 적용 시도: ' + pendingLang);
				applyLanguageToCombo(pendingLang);
			}
		} catch (e) {
			warn('TranslateElement 생성 실패:', e && e.message);
		}
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
	setInterval(suppressGoogleBar, 1000);

	// ── 3) 콤보에 언어 적용 ──────────────────────
	function applyLanguageToCombo(lang) {
		var combo = document.querySelector('.goog-te-combo');
		if (!combo) {
			log('.goog-te-combo 아직 없음. 폴링 시작...');
			pendingLang = lang;
			waitForGoogleTranslate(function (c) {
				log('.goog-te-combo 발견. 언어 적용: ' + lang);
				c.value = (lang === 'ko') ? '' : lang;
				fireChange(c);
			});
			return;
		}
		log('.goog-te-combo 즉시 발견. 언어 적용: ' + lang);
		combo.value = (lang === 'ko') ? '' : lang;
		fireChange(combo);
	}

	function fireChange(el) {
		var ev;
		try { ev = new Event('change', { bubbles: true }); }
		catch (e) { ev = document.createEvent('HTMLEvents'); ev.initEvent('change', true, true); }
		el.dispatchEvent(ev);
	}

	function waitForGoogleTranslate(cb) {
		var start = Date.now();
		var iv = setInterval(function () {
			var combo = document.querySelector('.goog-te-combo');
			if (combo) {
				clearInterval(iv);
				cb(combo);
			} else if (Date.now() - start > GT_LOAD_TIMEOUT_MS) {
				clearInterval(iv);
				warn('Google Translate 로드 타임아웃(' + GT_LOAD_TIMEOUT_MS + 'ms). ' +
					'가능 원인: 네트워크 차단·광고차단 확장·CSP·CDN 문제');
			}
		}, 200);
	}

	// ── 4) 폴백 CDN 로드 (main CDN 실패 대비) ────
	function ensureGoogleTranslateScript() {
		var t = setTimeout(function () {
			if (!window.google || !window.google.translate) {
				warn('20초 후에도 window.google.translate 없음. 폴백 CDN 시도...');
				var script = document.createElement('script');
				script.src = 'https://translate.googleapis.com/translate_a/element.js?cb=googleTranslateElementInit';
				script.async = true;
				script.onerror = function () { warn('폴백 CDN(googleapis)도 로드 실패'); };
				document.head.appendChild(script);
			}
		}, 5000);
		// 로드되면 타이머 취소
		var iv = setInterval(function () {
			if (window.google && window.google.translate) {
				clearTimeout(t);
				clearInterval(iv);
				log('메인 CDN(translate.google.com) 로드 성공');
			}
		}, 500);
	}

	// ── 5) 드롭다운 UI 동작 ────────────────────
	document.addEventListener('DOMContentLoaded', function () {
		log('DOMContentLoaded');
		ensureGoogleTranslateScript();

		var switcher = document.getElementById('md-lang-switcher');
		if (!switcher) {
			warn('#md-lang-switcher DOM 요소 없음');
			return;
		}
		var btn     = switcher.querySelector('.md-lang-switcher__btn');
		var menu    = switcher.querySelector('.md-lang-switcher__menu');
		var current = switcher.querySelector('.md-lang-switcher__current');
		if (!btn || !menu || !current) {
			warn('스위처 내부 요소 누락 (btn/menu/current)');
			return;
		}
		log('스위처 DOM 요소 확인 완료');

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
			log('언어 선택: ' + lang);
			current.textContent = LANG_LABELS[lang];
			closeMenu();

			try { localStorage.setItem(STORAGE_KEY, lang); } catch (e) {}

			applyLanguageToCombo(lang);
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

		// ── 6) 저장된 언어 자동 복원 ────────────
		var saved = null;
		try { saved = localStorage.getItem(STORAGE_KEY); } catch (e) {}
		if (saved && saved !== 'ko' && LANG_LABELS[saved]) {
			log('저장된 언어 복원: ' + saved);
			current.textContent = LANG_LABELS[saved];
			applyLanguageToCombo(saved);
		}
	});
})();
