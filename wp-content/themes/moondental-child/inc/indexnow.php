<?php
/**
 * v3.94 · IndexNow 연동
 *
 *  글·페이지·백과사전 항목이 발행·수정될 때 IndexNow(api.indexnow.org)에 URL 을 알린다.
 *  Bing(→ ChatGPT 검색·Copilot 의 색인 원천)·Naver·Yandex 등이 같은 엔드포인트를 공유하므로
 *  한 번 전송으로 여러 검색엔진에 새 주소가 전달된다. 구글은 IndexNow 를 쓰지 않으므로
 *  Search Console 사이트맵 제출이 별도로 필요하다.
 *
 *  키 파일: /{key}.txt 를 동적으로 응답한다 (루트에 파일을 두지 않아도 됨).
 *  최초 1회: 공개된 백과사전·페이지·글 전체를 일괄 제출한다 (요청당 최대 10,000 URL).
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** 사이트 고유 키 (32자 hex) — 옵션에 저장, 없으면 생성 */
function moondental_indexnow_key() {
	// v3.94.1 · random_bytes 로 32자 hex 생성 (이전 방식은 대부분 0 으로 채워진 약한 키를 만들었다)
	$key = get_option( 'md_indexnow_key_v2', '' );
	if ( ! is_string( $key ) || ! preg_match( '/^[a-f0-9]{32}$/', $key ) ) {
		try { $key = bin2hex( random_bytes( 16 ) ); } catch ( \Exception $e ) { $key = md5( uniqid( (string) mt_rand(), true ) ); }
		update_option( 'md_indexnow_key_v2', $key, false );
		update_option( 'md_indexnow_key_created', time(), false );
	}
	return $key;
}

/** /{key}.txt 응답 */
add_action( 'init', function () {
	$path = (string) strtok( (string) ( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '' ), '?' );
	$key  = moondental_indexnow_key();
	if ( trim( $path, '/' ) !== $key . '.txt' ) return;
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo $key;
	exit;
}, 1 );

/** URL 묶음을 IndexNow 로 전송 (비차단) */
function moondental_indexnow_submit( array $urls ) {
	$urls = array_values( array_unique( array_filter( array_map( 'strval', $urls ) ) ) );
	if ( ! $urls ) return false;
	$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	$key  = moondental_indexnow_key();
	$body = array(
		'host'        => $host,
		'key'         => $key,
		'keyLocation' => home_url( '/' . $key . '.txt' ),
		'urlList'     => array_slice( $urls, 0, 10000 ),
	);
	$res = wp_remote_post( 'https://api.indexnow.org/indexnow', array(
		'timeout'  => 8,
		'blocking' => true,
		'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
		'body'     => wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
	) );
	$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	update_option( 'md_indexnow_last', array( 'time' => time(), 'count' => count( $urls ), 'code' => $code ), false );
	return $code >= 200 && $code < 300;
}

/** 발행·수정 시 큐에 담았다가 요청 끝에 한 번에 전송 */
$GLOBALS['md_indexnow_queue'] = array();
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( ! in_array( $post->post_type, array( 'md_term', 'post', 'page' ), true ) ) return;
	if ( $new !== 'publish' ) return;
	$url = get_permalink( $post );
	if ( $url ) $GLOBALS['md_indexnow_queue'][] = $url;
}, 10, 3 );
add_action( 'shutdown', function () {
	if ( empty( $GLOBALS['md_indexnow_queue'] ) ) return;
	moondental_indexnow_submit( $GLOBALS['md_indexnow_queue'] );
}, 99 );

/** 최초 1회 · 공개 URL 전체 일괄 제출 (백과사전 851 + 페이지 + 글) */
add_action( 'wp_loaded', function () {
	if ( get_option( 'md_indexnow_bulk_v3941' ) === 'done' ) return;
	// 키 파일이 먼저 공개되어 있어야 검증을 통과한다 — 키 생성 직후 같은 요청에서는 보내지 않는다
	moondental_indexnow_key();
	if ( time() - (int) get_option( 'md_indexnow_key_created', 0 ) < 60 ) return;
	if ( ! add_option( 'md_indexnow_bulk_v3941_lock', (string) time(), '', 'no' ) ) return;

	$urls = array( home_url( '/' ) );
	$ids  = get_posts( array(
		'post_type'      => array( 'md_term', 'page', 'post' ),
		'post_status'    => 'publish',
		'posts_per_page' => 5000,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	foreach ( $ids as $id ) {
		$slug = (string) get_post_field( 'post_name', $id );
		if ( $slug === '병원소개' ) continue; // noindex 페이지
		$u = get_permalink( $id );
		if ( $u ) $urls[] = $u;
	}
	$ok = moondental_indexnow_submit( $urls );
	update_option( 'md_indexnow_bulk_v3941', 'done' );
	update_option( 'md_indexnow_bulk_v3941_result', array( 'ok' => $ok, 'count' => count( $urls ), 'time' => time() ), false );
	delete_option( 'md_indexnow_bulk_v3941_lock' );
}, 50 );

/** 상태 확인 · /?md_indexnow_status=1 (키 위치·마지막 전송 결과 — 키는 공개 값) */
add_action( 'init', function () {
	if ( ! isset( $_GET['md_indexnow_status'] ) ) return;
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo wp_json_encode( array(
		'keyLocation' => home_url( '/' . moondental_indexnow_key() . '.txt' ),
		'bulk'        => get_option( 'md_indexnow_bulk_v3941_result', null ),
		'last'        => get_option( 'md_indexnow_last', null ),
	), JSON_UNESCAPED_SLASHES );
	exit;
}, 2 );
