<?php
/**
 * v3.97.2 · 옛 주소 정리 — 백과사전 301/410 · 언어 접두어 404 · 옛 그누보드 경로
 *
 *  Search Console 기준(2026-09-24) 404 2,192 · Soft 404 263 · 미색인 584.
 *  - /{lang}/치과사전/… : 백과사전은 한국어 전용인데 언어 접두어 링크가 생성돼 전부 404 였다 → 한국어 주소로 301
 *  - 옛 시드 제목 슬러그 : 확실히 대응되는 것은 301 (encyclopedia-redirect-map.php), 나머지는 410 (encyclopedia-gone-list.php)
 *  - /치과사전-분야/옛분야/ : 백과사전 홈으로 301
 *  - /bbs/·/skin/ : 옛 그누보드 경로 355개 → 410
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function moondental_enc_redirect_map() {
	static $map = null;
	if ( $map === null ) {
		$f   = MOONDENTAL_DIR . '/inc/encyclopedia-redirect-map.php';
		$map = file_exists( $f ) ? (array) include $f : array();
	}
	return $map;
}

function moondental_enc_gone_list() {
	static $list = null;
	if ( $list === null ) {
		$f    = MOONDENTAL_DIR . '/inc/encyclopedia-gone-list.php';
		$list = file_exists( $f ) ? (array) include $f : array();
	}
	return $list;
}

/** 410 Gone 응답 — 검색엔진이 404 보다 빨리 색인에서 지운다 */
function moondental_send_410( $back_url = '', $back_label = '치과 백과사전으로 이동' ) {
	status_header( 410 );
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true );
	header( 'Content-Type: text/html; charset=UTF-8' );
	$back = $back_url ? '<p><a href="' . esc_url( $back_url ) . '">' . esc_html( $back_label ) . '</a> · <a href="' . esc_url( home_url( '/' ) ) . '">홈으로 이동</a></p>' : '<p><a href="' . esc_url( home_url( '/' ) ) . '">홈으로 이동</a></p>';
	echo '<!doctype html><html lang="ko"><head><meta charset="UTF-8"><title>410 Gone · 삭제된 페이지</title><meta name="robots" content="noindex,nofollow,noarchive,nosnippet"></head><body style="font-family:sans-serif;text-align:center;padding:60px 20px;"><h1>410 Gone</h1><p>이 페이지는 영구적으로 삭제되었습니다.</p>' . $back . '</body></html>';
	exit;
}

/** 백과사전 슬러그 하나를 현재 주소로 — 맵에 있으면 새 슬러그, 없으면 그대로 */
function moondental_enc_resolve_slug( $slug ) {
	$slug = strtolower( rtrim( (string) $slug, '/' ) );
	$map  = moondental_enc_redirect_map();
	return isset( $map[ $slug ] ) ? $map[ $slug ] : $slug;
}

add_action( 'template_redirect', function () {
	if ( is_admin() ) return;
	$path = urldecode( (string) strtok( isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '', '?' ) );

	// 1) 옛 그누보드 경로 → 410 (404 여부와 무관)
	if ( preg_match( '#^/(bbs|skin)/#', $path ) ) {
		moondental_send_410( home_url( '/' ), '홈으로 이동' );
	}

	if ( ! is_404() ) return;

	// 2) 언어 접두어 아래 404 → 한국어 경로로 301 (백과사전 옛 슬러그면 최종 목적지로 바로)
	if ( preg_match( '#^/(en|ja|zh|vi|ru|mn)(/.*)$#u', $path, $lm ) ) {
		$rest = $lm[2];
		if ( preg_match( '#^/치과사전/([^/]+)/?$#u', $rest, $sm ) ) {
			$rest = '/치과사전/' . moondental_enc_resolve_slug( $sm[1] ) . '/';
		} elseif ( preg_match( '#^/치과사전-분야/#u', $rest ) ) {
			$rest = '/치과사전/';
		}
		wp_safe_redirect( home_url( $rest ), 301 );
		exit;
	}

	// 3) 옛 백과사전 슬러그 → 301 (맵) / 410 (삭제 목록)
	if ( preg_match( '#^/치과사전/([^/]+)/?$#u', $path, $m ) ) {
		$slug = strtolower( rtrim( $m[1], '/' ) );
		$new  = moondental_enc_resolve_slug( $slug );
		if ( $new !== $slug ) {
			wp_safe_redirect( home_url( '/치과사전/' . $new . '/' ), 301 );
			exit;
		}
		$gone = moondental_enc_gone_list();
		if ( isset( $gone[ $slug ] ) ) {
			moondental_send_410( home_url( '/치과사전/' ) );
		}
		return;
	}

	// 4) 옛 분야 슬러그 → 백과사전 홈
	if ( preg_match( '#^/치과사전-분야/#u', $path ) ) {
		wp_safe_redirect( home_url( '/치과사전/' ), 301 );
		exit;
	}
}, 0 ); // 테마의 다른 template_redirect(1·2) 보다 먼저
