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

	// 빈 값·자기 자신과 동일한 항목은 치환 대상에서 제외
	foreach ( $map as $ko => $tr ) {
		if ( ! is_string( $tr ) || $tr === '' || $tr === $ko ) unset( $map[ $ko ] );
	}

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
function moondental_translate_html( $html, $map ) {
	if ( empty( $map ) || $html === '' ) return $html;

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
	$out = '';
	foreach ( $parts as $part ) {
		if ( $part === '' ) continue;
		if ( $part[0] === '<' || ! preg_match( '/[가-힣]/u', $part ) ) {
			$out .= $part;
			continue;
		}

		$trimmed = trim( $part );
		if ( $trimmed !== '' && isset( $map[ $trimmed ] ) ) {
			// 앞뒤 공백(들여쓰기·줄바꿈)은 그대로 보존해 마크업이 흐트러지지 않게 한다
			$lead  = substr( $part, 0, strpos( $part, $trimmed ) );
			$trail = substr( $part, strpos( $part, $trimmed ) + strlen( $trimmed ) );
			$out  .= $lead . $map[ $trimmed ] . $trail;
			continue;
		}

		$out .= $part;
	}

	// 3단계 · 빼두었던 블록 복원
	return $vault ? strtr( $out, $vault ) : $out;
}

/**
 * 비한국어 페이지에서 출력 버퍼를 잡아 번역 적용.
 *   template_redirect 에서 시작해 shutdown 에서 정리한다.
 */
add_action( 'template_redirect', function () {
	if ( is_admin() || is_feed() || is_robots() ) return;
	if ( ! function_exists( 'moondental_current_language' ) ) return;

	$lang = moondental_current_language();
	if ( $lang === 'ko' || $lang === '' ) return;

	$map = moondental_phrase_map( $lang );
	if ( empty( $map ) ) return;

	ob_start( function ( $html ) use ( $map ) {
		// HTML 문서가 아니면 손대지 않는다
		if ( stripos( $html, '<html' ) === false ) return $html;
		return moondental_translate_html( $html, $map );
	} );
}, 5 );

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
