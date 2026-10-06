<?php
/**
 * v7.8 · 덴트웹 연동 프로그램 자동 업데이트 창구
 *
 *  서버 PC 의 sync.ps1 · brief.ps1 을 사람이 들고 가서 바꾸지 않아도 되게 한다.
 *   - 원장 PC 의 올리기.ps1 이 파일을 RSA 비밀 열쇠로 서명해서 POST /md-bridge/v1/publish 로 올린다.
 *   - 서버 PC 의 sync.ps1 이 5분마다 GET /md-bridge/v1/manifest 로 지문(sha256)을 보고,
 *     달라진 파일만 GET /md-bridge/v1/file 로 받아 **공개 열쇠로 서명을 확인한 뒤에만** 바꾼다.
 *     → 홈페이지가 뚫려도 서명 없는 프로그램은 서버 PC 에서 돌지 않는다.
 *  모든 요청은 만족도 연동 키(X-MD-Survey-Key) 필요. 저장: 옵션 md_bridge_files, md_bridge_seen(서버 PC 마지막 연결).
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function md_bridge_permission( $request ) {
	$key  = (string) $request->get_header( 'x-md-survey-key' );
	$want = (string) get_option( 'md_survey_api_key' );
	return '' !== $want && '' !== $key && hash_equals( $want, $key );
}

function md_bridge_files() { $f = get_option( 'md_bridge_files' ); return is_array( $f ) ? $f : array(); }

function md_bridge_name_ok( $n ) { return is_string( $n ) && preg_match( '/^[a-z0-9_-]{1,40}\.ps1$/', $n ); }

add_action( 'rest_api_init', function () {
	register_rest_route( 'md-bridge/v1', '/publish', array(
		'methods'             => 'POST',
		'permission_callback' => 'md_bridge_permission',
		'callback'            => function ( $request ) {
			$b     = $request->get_json_params();
			$files = md_bridge_files();
			$done  = array();
			foreach ( (array) ( $b['files'] ?? array() ) as $f ) {
				$name = $f['name'] ?? '';
				$raw  = base64_decode( (string) ( $f['b64'] ?? '' ), true );
				$sig  = (string) ( $f['sig'] ?? '' );
				if ( ! md_bridge_name_ok( $name ) || false === $raw || '' === $raw || ! preg_match( '/^[A-Za-z0-9+\/=]+$/', $sig ) ) { continue; }
				$files[ $name ] = array( 'sha' => hash( 'sha256', $raw ), 'b64' => base64_encode( $raw ), 'sig' => $sig, 'at' => current_time( 'mysql' ) );
				$done[] = $name;
			}
			update_option( 'md_bridge_files', $files, false );
			return rest_ensure_response( array( 'saved' => $done ) );
		},
	) );
	register_rest_route( 'md-bridge/v1', '/manifest', array(
		'methods'             => 'GET',
		'permission_callback' => 'md_bridge_permission',
		'callback'            => function ( $request ) {
			$have = $request->get_param( 'have' ); /* 서버 PC 가 지금 가진 지문 (이름=sha 쉼표) — 화면 표시용 */
			update_option( 'md_bridge_seen', array( 'at' => current_time( 'mysql' ), 'have' => sanitize_text_field( (string) $have ) ), false );
			$out = array();
			foreach ( md_bridge_files() as $n => $f ) { $out[] = array( 'name' => $n, 'sha' => $f['sha'] ); }
			return rest_ensure_response( array( 'files' => $out ) );
		},
	) );
	register_rest_route( 'md-bridge/v1', '/file', array(
		'methods'             => 'GET',
		'permission_callback' => 'md_bridge_permission',
		'callback'            => function ( $request ) {
			$n = (string) $request->get_param( 'name' );
			$f = md_bridge_files()[ $n ] ?? null;
			if ( ! md_bridge_name_ok( $n ) || ! $f ) { return new WP_Error( 'md_bridge', '없는 파일입니다.', array( 'status' => 404 ) ); }
			return rest_ensure_response( array( 'name' => $n, 'sha' => $f['sha'], 'b64' => $f['b64'], 'sig' => $f['sig'] ) );
		},
	) );
} );

/** 서버 PC 상태 한 줄 — 경영 브리핑 「받는 사람」 탭 아래에 */
function md_bridge_status_html() {
	$seen  = get_option( 'md_bridge_seen' );
	$files = md_bridge_files();
	if ( ! $files && ! $seen ) { return ''; }
	$h = '<p class="mds-hint" style="margin-top:18px;border-top:1px solid #eee;padding-top:12px">🖥 <b>서버 PC</b> · ';
	if ( is_array( $seen ) && ! empty( $seen['at'] ) ) {
		$mins = (int) floor( ( current_time( 'timestamp' ) - strtotime( $seen['at'] ) ) / 60 );
		$h .= '마지막 연결 ' . esc_html( substr( $seen['at'], 5, 11 ) ) . ( $mins > 30 ? ' <b style="color:#c62828">(' . $mins . '분째 연결 없음 — 서버 PC 가 꺼져 있는지 확인)</b>' : '' );
		$have = array();
		foreach ( explode( ',', (string) $seen['have'] ) as $p ) { $kv = explode( '=', $p, 2 ); if ( 2 === count( $kv ) ) { $have[ $kv[0] ] = $kv[1]; } }
		$old = array();
		foreach ( $files as $n => $f ) { if ( ( $have[ $n ] ?? '' ) !== $f['sha'] ) { $old[] = $n; } }
		$h .= $old ? ' · 바꿀 파일: ' . esc_html( implode( ', ', $old ) ) . ' (5분 안에 자동으로 바뀜)' : ' · 프로그램 최신';
	} else {
		$h .= '아직 연결한 적 없음';
	}
	return $h . '</p>';
}
