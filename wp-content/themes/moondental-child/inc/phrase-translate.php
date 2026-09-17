<?php
/**
 * v3.44.217 · 문구 기반 번역 레이어
 *
 *  배경
 *   기존 번역은 md_content( 'key', '원문' ) 를 거치는 문구만 사전에서 찾아 바꾼다.
 *   그런데 층별 안내·진료 카드·발자취·후기처럼 화면에 크게 보이는 문구는
 *   PHP 배열이나 템플릿에 한글이 직접 들어 있어 번역 대상 자체가 아니었다.
 *   실측 결과 /en/ 페이지의 84%가 한글 그대로였다.
 *
 *  방식
 *   비한국어 페이지에서 출력 버퍼를 잡아, 알려진 한글 문구를 번역문으로 치환한다.
 *   키를 몰라도 되고 템플릿을 고치지 않아도 되므로 하드코딩 문구까지 덮는다.
 *
 *  안전장치
 *   - 태그 바깥(텍스트 노드)만 치환한다. 속성값·클래스명은 건드리지 않는다.
 *   - script·style 안은 통째로 건너뛴다 (JSON-LD·JS 문자열 보호).
 *   - 긴 문구부터 치환해 부분 겹침을 막는다.
 *   - 사전에 없는 문구는 원문 그대로 (안전한 fallback).
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 언어별 문구 사전 로드 · languages/md_phrases_{lang}.php
 *
 * @param string $lang
 * @return array 원문(한국어) => 번역문
 */
function moondental_phrase_map( $lang ) {
	static $cache = array();
	if ( isset( $cache[ $lang ] ) ) return $cache[ $lang ];

	$file = defined( 'MOONDENTAL_DIR' )
		? MOONDENTAL_DIR . '/languages/md_phrases_' . $lang . '.php'
		: '';

	$map = array();
	if ( $file && file_exists( $file ) ) {
		$data = include $file;
		if ( is_array( $data ) ) $map = $data;
	}
	// v3.88 · 언어 공통 사전 (md_phrases_common.php · 인명 로마자 등) — 언어별 사전이 우선
	$common = defined( 'MOONDENTAL_DIR' ) ? MOONDENTAL_DIR . '/languages/md_phrases_common.php' : '';
	if ( $common && file_exists( $common ) ) {
		$shared = include $common;
		if ( is_array( $shared ) ) $map = array_merge( $shared, $map );
	}
	// v3.87 · 추가 사전 병합 · md_phrases_{lang}_*.php (extra · doctors · …) · 뒤에 읽는 파일이 우선
	if ( defined( 'MOONDENTAL_DIR' ) ) {
		$extras = glob( MOONDENTAL_DIR . '/languages/md_phrases_' . $lang . '_*.php' );
		if ( is_array( $extras ) ) {
			sort( $extras );
			foreach ( $extras as $extra ) {
				$more = include $extra;
				if ( is_array( $more ) ) $map = array_merge( $map, $more );
			}
		}
	}

	// 빈 값·자기 자신과 동일한 항목은 치환 대상에서 제외
	// v3.89 · 키의 내부 공백(줄바꿈·탭 들여쓰기)을 한 칸으로 정규화 — 템플릿 줄바꿈과 무관하게 일치
	$norm = array();
	foreach ( $map as $ko => $tr ) {
		if ( ! is_string( $tr ) || $tr === '' || $tr === $ko ) continue;
		$norm[ preg_replace( '/\s+/u', ' ', trim( $ko ) ) ] = $tr;
	}
	$map = $norm;

	// 긴 문구 우선 — '임플란트센터' 가 '임플란트' 로 먼저 잘리는 것을 막는다
	uksort( $map, function ( $a, $b ) {
		return mb_strlen( $b ) <=> mb_strlen( $a );
	} );

	return $cache[ $lang ] = $map;
}

/**
 * HTML 의 텍스트 노드에만 문구 치환 적용.
 *
 * @param string $html
 * @param array  $map
 * @return string
 */
function moondental_translate_html( $html, $map, $lang = '' ) {
	if ( empty( $map ) || $html === '' ) return $html;
	/* v3.89.2 · 띄어쓰기 보정 대상 언어 — 한국어는 조사가 태그 뒤에 바로 붙지만(예: </strong>가)
	 *   영어·베트남어·러시아어·몽골어는 단어 사이에 공백이 필요하다. 일본어·중국어는 제외. */
	$space_lang = $lang !== '' && ! in_array( $lang, array( 'ja', 'zh' ), true );

	/* 1단계 · script·style·textarea 블록을 통째로 빼내 자리표시자로 치환한다.
	 *   JS 안의 `i < 10` 같은 부등호가 태그로 오인되어 이후 파싱이
	 *   통째로 어긋나는 것을 막는다. (실제로 이 문제로 치환이 거의 되지 않았다) */
	$vault = array();
	$html  = preg_replace_callback(
		'#<(script|style|textarea)\b[^>]*>.*?</\1\s*>#is',
		function ( $m ) use ( &$vault ) {
			$token           = "\x02MDPT" . count( $vault ) . "\x03";
			$vault[ $token ] = $m[0];
			return $token;
		},
		$html
	);
	if ( $html === null ) return $html;

	/* 2단계 · 남은 마크업을 태그와 텍스트로 나눠 텍스트만 치환.
	 *   속성값(aria-label·alt 등)은 태그 안이므로 건드리지 않는다. */
	$parts = preg_split( '/(<[^>]*>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! is_array( $parts ) ) {
		return strtr( $html, $vault );
	}

	/* 텍스트 노드 '전체'가 사전과 정확히 일치할 때만 치환한다.
	 *
	 *   부분 치환(strtr)을 쓰면 번역되지 않은 문장 안의 낱말만 바뀌어
	 *   '원장·Departments 협진 시스템' 같은 한영 혼용 문장이 만들어진다.
	 *   이는 한국어 원문보다 읽기 나쁘다. 그래서 한 덩어리 전체가
	 *   사전에 있을 때만 바꾸고, 아니면 원문 그대로 둔다.
	 *   결과적으로 각 문장은 '완전한 번역' 또는 '완전한 원문' 둘 중 하나가 된다. */
	$out     = '';
	$ends_ws = true; // 직전에 출력한 텍스트가 공백으로 끝났는가 (태그는 무시)
	foreach ( $parts as $part ) {
		if ( $part === '' ) continue;
		if ( $part[0] === '<' ) {
			$out .= $part;
			// 블록 경계에서는 공백 여부를 초기화 — 인라인 태그(strong·em·span·a·b·i)만 이어진 텍스트로 본다
			if ( ! preg_match( '#^</?(strong|em|span|a|b|i|mark|small|sup|sub)\b#i', $part ) ) $ends_ws = true;
			continue;
		}
		if ( ! preg_match( '/[가-힣]/u', $part ) ) {
			$out    .= $part;
			$ends_ws = (bool) preg_match( '/\s$/u', $part );
			continue;
		}

		$trimmed = trim( $part );
		$key     = $trimmed === '' ? '' : preg_replace( '/\s+/u', ' ', $trimmed ); // v3.89 · 내부 공백 정규화
		if ( $key !== '' && isset( $map[ $key ] ) ) {
			// 앞뒤 공백(들여쓰기·줄바꿈)은 그대로 보존해 마크업이 흐트러지지 않게 한다
			$lead  = substr( $part, 0, strpos( $part, $trimmed ) );
			$trail = substr( $part, strpos( $part, $trimmed ) + strlen( $trimmed ) );
			$tr    = $map[ $key ];
			// v3.89.2 · '경우</strong>가' → 'bone</strong>are' 처럼 붙어 버리는 것을 막는다
			if ( $space_lang && $lead === '' && ! $ends_ws && preg_match( '/^[\p{L}\p{N}—–(«“"]/u', $tr ) ) $lead = ' ';
			$out    .= $lead . $tr . $trail;
			$ends_ws = $trail !== '' || (bool) preg_match( '/\s$/u', $tr );
			continue;
		}

		$out    .= $part;
		$ends_ws = (bool) preg_match( '/\s$/u', $part );
	}

	// 3단계 · 빼두었던 블록 복원
	return $vault ? strtr( $out, $vault ) : $out;
}

/**
 * 비한국어 페이지에서 출력 버퍼를 잡아 번역 적용.
 *   template_redirect 에서 시작해 shutdown 에서 정리한다.
 */
/**
 * v3.44.218 · 번역에서 제외할 화면인가
 *
 *   치과 백과사전은 652개 항목 각각이 정의·분류표·임상 주의사항까지 담은
 *   긴 학술 문서다. 일부만 번역되면 오히려 읽기 나빠지고, 전부 번역하는 것은
 *   현실적이지 않다. 원문(한국어) 그대로 두는 편이 정확하다.
 *
 * @return bool
 */
function moondental_skip_translation() {
	// 백과사전 개별 항목 · 목록 · 분야별 아카이브
	if ( is_singular( 'md_term' ) )            return true;
	if ( is_post_type_archive( 'md_term' ) )   return true;
	if ( is_tax( 'md_term_category' ) )        return true;

	// rewrite 가 아직 반영되지 않은 경우까지 대비해 경로로도 확인
	$path = isset( $_SERVER['REQUEST_URI'] )
		? urldecode( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) )
		: '';
	if ( $path !== '' && strpos( $path, '/치과사전' ) !== false ) return true;

	return (bool) apply_filters( 'moondental_skip_translation', false );
}

add_action( 'template_redirect', function () {
	if ( is_admin() || is_feed() || is_robots() ) return;
	if ( ! function_exists( 'moondental_current_language' ) ) return;

	$lang = moondental_current_language();
	if ( $lang === 'ko' || $lang === '' ) return;

	// v3.44.218 · 백과사전은 번역 대상에서 제외
	if ( moondental_skip_translation() ) return;

	$map = moondental_phrase_map( $lang );
	if ( empty( $map ) ) return;

	ob_start( function ( $html ) use ( $map, $lang ) {
		// HTML 문서가 아니면 손대지 않는다
		if ( stripos( $html, '<html' ) === false ) return $html;
		$html = moondental_translate_html( $html, $map, $lang );
		// v3.88 · 내부 링크에 언어 접두어 유지
		return moondental_localize_internal_links( $html, $lang );
	} );
}, 5 );

/**
 * v3.88 · 번역 페이지의 내부 링크에 언어 접두어(/en/ 등)를 붙인다.
 *
 *   /en/ 홈의 링크 97개 중 95개가 접두어 없는 한국어 주소였다 — 메뉴·카드를 누르는 순간
 *   한국어 사이트로 돌아가 "번역이 안 된다"고 느끼는 주된 원인이었다.
 *   템플릿을 일일이 고치는 대신 출력 단계에서 href 만 바꾼다.
 *
 *   제외: 이미 접두어가 있는 링크 · 파일/피드/REST · 종합안내서(/guide/) · 치과사전(번역 제외 화면)
 *
 * @param string $html
 * @param string $lang
 * @return string
 */
function moondental_localize_internal_links( $html, $lang ) {
	if ( ! preg_match( '/^(en|ja|zh|vi|ru|mn)$/', $lang ) ) return $html;
	$home = home_url( '/' );
	$quoted = preg_quote( $home, '#' );
	return preg_replace_callback(
		'#\bhref="(' . $quoted . ')([^"]*)"#u',
		function ( $m ) use ( $lang ) {
			$path = $m[2];
			if ( $path === '' ) return 'href="' . $m[1] . $lang . '/"';
			if ( preg_match( '#^(en|ja|zh|vi|ru|mn)(/|$)#', $path ) ) return $m[0];
			if ( $path[0] === '#' || $path[0] === '?' ) return $m[0];
			// 파일 · 시스템 경로
			if ( preg_match( '#^(wp-|feed|comments|xmlrpc|guide/|가이드/|%EA%B0%80%EC%9D%B4%EB%93%9C/|치과사전|%EC%B9%98%EA%B3%BC%EC%82%AC%EC%A0%84)#u', $path ) ) return $m[0];
			if ( preg_match( '#\.[a-z0-9]{2,5}([?\#]|$)#i', $path ) ) return $m[0];
			return 'href="' . $m[1] . $lang . '/' . $path . '"';
		},
		$html
	);
}

/**
 * 관리자 도구 · 번역 커버리지 확인
 *   현재 페이지 사전이 몇 개인지, 미번역 문구가 무엇인지 빠르게 본다.
 *   /?md_phrase_report=1&lang=en 으로 호출 (관리자만).
 */
add_action( 'init', function () {
	if ( ! isset( $_GET['md_phrase_report'] ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	$lang = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : 'en';
	$map  = moondental_phrase_map( $lang );

	header( 'Content-Type: text/plain; charset=utf-8' );
	echo "언어: {$lang}\n";
	echo "사전 항목: " . count( $map ) . "개\n";
	exit;
}, 20 );
