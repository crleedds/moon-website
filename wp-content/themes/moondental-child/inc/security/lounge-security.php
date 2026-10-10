<?php
/**
 * v8.0 · 직원 라운지 보안 — 탭별 접근 권한 · 환자 정보 화면 이메일 인증 · 기기 관리 · 미니차트 열람 기록
 * (원장 지시 2026-10-06)
 *
 *  1) 탭 권한: 계정마다 라운지 탭(앱)을 켜고 끈다. 홈페이지 관리자(md_supply_owner · v8.2)는 늘 전부. 직원 정보 › 권한 탭.
 *     설정은 「접근 권한 · 보안」 탭(원장만) — 사용자 메타 md_lounge_tabs. 정하지 않은 계정은 예전 그대로
 *     (관리 전용 탭은 라운지 관리자, 미니차트는 라운지 관리자만).
 *  2) 환자 정보 화면(미니차트 · 만족도 결과 · 경영 브리핑 · 접수수납목록)은 병원 밖에서 열 때 이메일 코드 6자리.
 *     「로그인 상태 유지」를 고르면 그 기기는 120일 — 그래도 30일 동안 안 쓰면 다시 묻는다. 병원 인터넷(IP)에서는 묻지 않는다.
 *  3) 기기: 내 정보에서 내 기기(로그인 · 인증)를 보고 끊는다. 원장 계정은 모든 직원 기기를 끊는다.
 *     사용 중지 · 퇴사 · 삭제 때는 자동으로 모두 끊긴다.
 *  4) 미니차트 열람 기록: 누가 언제 어떤 차트를 열었는지 — 2년 보관.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_SEC_SCHEMA', 1 );

/* ============================================================
 * 설정
 * ============================================================ */

function md_sec_settings() {
	$d = array( 'hospital_ips' => array( '211.195.235.185' ), 'keep_days' => 120, 'idle_days' => 30 );
	$o = get_option( 'md_sec_settings', array() );
	return is_array( $o ) ? array_merge( $d, $o ) : $d;
}

/** 이메일 인증이 필요한 화면 — v9.17 · 홈페이지 관리자가 직원 정보 › 병원 인터넷에서 고른다 (원장 지시). 안 골랐으면 기본값 */
function md_sec_sensitive_default() { return array( 'minichart', 'survey_result', 'brief', 'survey', 'recall' ); }
function md_sec_sensitive() {
	$o = get_option( 'md_sec_settings', array() );
	$t = is_array( $o ) && isset( $o['sensitive_tabs'] ) && is_array( $o['sensitive_tabs'] ) ? array_values( array_map( 'sanitize_key', $o['sensitive_tabs'] ) ) : md_sec_sensitive_default();
	if ( in_array( 'brief', $t, true ) && ! in_array( 'recall', $t, true ) ) { $t[] = 'recall'; } /* v9.38 · 리콜 명단(환자 이름)은 경영 브리핑과 같이 */
	return $t;
}

function md_sec_ip() {
	if ( function_exists( 'md_acc_ip' ) ) { return md_acc_ip(); }
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/** 병원 인터넷에서 접속했나 (IP 그대로 또는 끝을 * 로 — 예: 211.195.235.*) */
function md_sec_is_hospital( $ip = null ) {
	$ip = null === $ip ? md_sec_ip() : $ip;
	if ( '' === $ip ) { return false; }
	foreach ( (array) md_sec_settings()['hospital_ips'] as $p ) {
		$p = trim( (string) $p );
		if ( '' === $p ) { continue; }
		if ( $p === $ip ) { return true; }
		if ( '*' === substr( $p, -1 ) && 0 === strpos( $ip, rtrim( $p, '*' ) ) ) { return true; }
	}
	return false;
}

function md_sec_device_label() {
	if ( function_exists( 'md_acc_device' ) ) { return md_acc_device(); }
	return mb_substr( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 60 );
}

/** 홈페이지 관리자 (v8.2 · md_supply_owner — 늘 모든 탭 · 관리자 등급 정하기 · 모든 기기 끊기). 홈페이지 관리자도 같은 대우 */
function md_sec_is_owner_user( $u ) {
	if ( ! $u || ! $u->exists() ) { return false; }
	return user_can( $u, 'md_supply_owner' ) || user_can( $u, 'manage_options' );
}
function md_sec_is_mgr_user( $u ) { return $u && $u->exists() && ( user_can( $u, 'md_supply_manage' ) || md_sec_is_owner_user( $u ) ); }
function md_sec_grade( $u ) { return user_can( $u, 'md_supply_owner' ) ? 'owner' : ( user_can( $u, 'md_supply_manage' ) ? 'mgr' : 'staff' ); }
function md_sec_grade_label( $g ) { return array( 'owner' => '홈페이지 관리자', 'mgr' => '라운지 관리자', 'staff' => '직원' )[ $g ]; }
function md_sec_is_shared_staff( $u ) { return $u && defined( 'MD_SUP_STAFF_LOGIN' ) && MD_SUP_STAFF_LOGIN === $u->user_login; }

/** 지금 사람이 그 계정의 권한을 바꿀 수 있나 — 총괄: 다른 총괄 빼고 다 / 라운지 관리자: 일반 직원만 / 내 계정은 못 바꿈 */
function md_sec_can_edit_user( $t ) {
	$me = wp_get_current_user();
	if ( ! $t || (int) $t->ID === (int) $me->ID ) { return false; }
	if ( user_can( $t, 'manage_options' ) ) { return false; }
	if ( current_user_can( 'manage_options' ) ) { return true; }
	if ( user_can( $me, 'md_supply_owner' ) ) { return ! user_can( $t, 'md_supply_owner' ); }
	if ( user_can( $me, 'md_supply_manage' ) ) { return ! md_sec_is_mgr_user( $t ); }
	return false;
}
function md_sec_is_owner() { return is_user_logged_in() && md_sec_is_owner_user( wp_get_current_user() ); }

/* ============================================================
 * 탭 권한
 * ============================================================ */

/** 원래 앱 목록 (필터 전) */
function md_sec_raw_apps() {
	global $md_sec_raw_apps;
	if ( ! is_array( $md_sec_raw_apps ) && function_exists( 'md_sup_apps' ) ) { md_sup_apps(); }
	return is_array( $md_sec_raw_apps ) ? $md_sec_raw_apps : array();
}

/** 권한을 정할 수 있는 탭 (내 정보 · 접근 권한 제외) */
function md_sec_tab_list() {
	$out = array();
	foreach ( md_sec_raw_apps() as $k => $a ) {
		if ( in_array( $k, array( 'me', 'access', 'survey', 'calendar' ), true ) ) { continue; } /* v9.49 · 달력은 모든 계정에 늘 열림 — 권한 표에서 뺌 (원장 지시) */ /* v9.16 · 접수수납목록은 타일을 없앴으니(v7.9) 권한 표에서도 뺌 — 관리자 비상용 주소만 (원장 지적) */
		$out[ $k ] = $a;
	}
	return $out;
}

/** 이 계정이 열 수 있는 탭 — null 이면 전부 */
function md_sec_user_tabs( $u ) {
	if ( md_sec_is_owner_user( $u ) ) { return null; }
	$mgr = user_can( $u, 'md_supply_manage' );
	$set = get_user_meta( $u->ID, 'md_lounge_tabs', true );
	$out = array();
	foreach ( md_sec_tab_list() as $k => $a ) {
		/* 관리 전용 탭(직원 정보 · 만족도 결과 · 경영 브리핑)은 라운지 관리자만 가질 수 있다 */
		if ( ! empty( $a['manage'] ) && ! $mgr ) { continue; }
		if ( 'recall' === $k && $mgr ) { $out[] = $k; continue; } /* v9.38 · 리콜 명단은 라운지 관리자에게 늘 열림(경영 브리핑에도 있으니) */
		if ( is_array( $set ) ) { if ( in_array( $k, $set, true ) ) { $out[] = $k; } continue; }
		/* 정하지 않았으면 예전 그대로 — 미니차트 · 리콜은 직원에게 정해 줘야 열린다 */
		if ( in_array( $k, array( 'minichart', 'recall' ), true ) && ! $mgr ) { continue; }
		$out[] = $k;
	}
	return $out;
}

function md_sec_can_tab( $k, $u = null ) {
	$u = $u ? $u : wp_get_current_user();
	if ( in_array( $k, array( 'me', 'calendar' ), true ) ) { return true; } /* v9.49 · 달력은 누구나 */
	$t = md_sec_user_tabs( $u );
	return null === $t || in_array( $k, $t, true );
}

/** 라운지 앱 목록 거르기 — 못 여는 탭은 빼고(첫 화면 타일 · 주소 둘 다), 환자 정보 탭에는 🔒 */
function md_sec_filter_apps( $apps ) {
	global $md_sec_raw_apps;
	if ( isset( $apps['minichart'] ) ) { unset( $apps['minichart']['manage'] ); } /* 미니차트는 이제 탭 권한으로 */
	$md_sec_raw_apps = $apps; /* v8.2 · 「접근 권한 · 보안」은 직원 정보 안으로 합침 */
	if ( ! is_user_logged_in() ) { return $apps; }
	$u = wp_get_current_user();
	foreach ( $apps as $k => $a ) {
		if ( ! md_sec_can_tab( $k, $u ) ) { unset( $apps[ $k ] ); continue; }
		if ( in_array( $k, md_sec_sensitive(), true ) ) {
			$apps[ $k ]['desc'] = '🔒 병원 밖에서는 이메일 인증 · ' . ( $a['desc'] ?? '' );
			$apps[ $k ]['label'] = $a['label'] . ' 🔒';
		}
	}
	return $apps;
}
add_filter( 'md_sup_apps', 'md_sec_filter_apps', 5 );

/* ============================================================
 * 기기 · 인증 기록 (사용자 메타 md_sec_devices = [ 해시 => 기록 ])
 * ============================================================ */

function md_sec_devices( $uid ) {
	$d = get_user_meta( $uid, 'md_sec_devices', true );
	return is_array( $d ) ? $d : array();
}

function md_sec_cookie() { return isset( $_COOKIE['md_sec_dev'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', (string) $_COOKIE['md_sec_dev'] ) : ''; }

/** 이 기기가 인증돼 있나 (병원 인터넷이면 늘 그렇다) */
function md_sec_verified() {
	if ( ! is_user_logged_in() ) { return false; }
	if ( md_sec_is_hospital() ) { return true; }
	$tok = md_sec_cookie();
	if ( strlen( $tok ) < 32 ) { return false; }
	$uid = get_current_user_id();
	$all = md_sec_devices( $uid );
	$h   = hash( 'sha256', $tok );
	if ( ! isset( $all[ $h ] ) ) { return false; }
	$d   = $all[ $h ]; $now = time(); $S = md_sec_settings();
	$ok  = $d['expires'] > $now && ( $now - (int) $d['last'] ) < (int) $S['idle_days'] * DAY_IN_SECONDS;
	if ( ! $ok ) { unset( $all[ $h ] ); update_user_meta( $uid, 'md_sec_devices', $all ); return false; }
	if ( $now - (int) $d['last'] > HOUR_IN_SECONDS ) {
		$all[ $h ]['last'] = $now; $all[ $h ]['ip'] = md_sec_ip();
		update_user_meta( $uid, 'md_sec_devices', $all );
	}
	return true;
}

function md_sec_trust_this_device( $keep ) {
	$uid = get_current_user_id();
	$S   = md_sec_settings();
	$tok = wp_generate_password( 40, false, false );
	$now = time();
	$all = md_sec_devices( $uid );
	/* 오래된 것 정리 */
	foreach ( $all as $h => $d ) { if ( $d['expires'] < $now ) { unset( $all[ $h ] ); } }
	$all[ hash( 'sha256', $tok ) ] = array( 'label' => md_sec_device_label(), 'ip' => md_sec_ip(), 'at' => $now, 'last' => $now, 'keep' => $keep ? 1 : 0, 'expires' => $now + ( $keep ? (int) $S['keep_days'] * DAY_IN_SECONDS : 12 * HOUR_IN_SECONDS ) );
	update_user_meta( $uid, 'md_sec_devices', $all );
	setcookie( 'md_sec_dev', $tok, array( 'expires' => $keep ? $now + (int) $S['keep_days'] * DAY_IN_SECONDS : 0, 'path' => COOKIEPATH ? COOKIEPATH : '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
	$_COOKIE['md_sec_dev'] = $tok;
}

/** 기기 끊기 — 인증 기록 · 로그인 세션 */
function md_sec_drop_all( $uid ) {
	delete_user_meta( $uid, 'md_sec_devices' );
	if ( class_exists( 'WP_Session_Tokens' ) ) { WP_Session_Tokens::get_instance( $uid )->destroy_all(); }
}

/* 사용 중지 · 퇴사 → 자동으로 끊기 */
add_action( 'set_user_role', function ( $uid, $role ) { if ( 'md_lounge_off' === $role || 'md_lounge_pending' === $role ) { md_sec_drop_all( $uid ); } }, 10, 2 );

/* 로그인 상태 유지 = 120일 (라운지 계정) */
add_filter( 'auth_cookie_expiration', function ( $len, $uid, $remember ) {
	if ( ! $remember ) { return $len; }
	$u = get_userdata( $uid );
	if ( $u && array_intersect( (array) $u->roles, array( 'md_stock_staff', 'md_stock_manager', 'administrator' ) ) ) { return (int) md_sec_settings()['keep_days'] * DAY_IN_SECONDS; }
	return $len;
}, 20, 3 );

/* ============================================================
 * 이메일 코드
 * ============================================================ */

function md_sec_mask_email( $e ) {
	if ( ! is_email( $e ) ) { return ''; }
	list( $a, $b ) = explode( '@', $e, 2 );
	return mb_substr( $a, 0, 2 ) . str_repeat( '•', max( 2, mb_strlen( $a ) - 2 ) ) . '@' . $b;
}

function md_sec_send_code() {
	$u = wp_get_current_user();
	if ( ! is_email( $u->user_email ) ) { return new WP_Error( 'mail', '계정에 이메일이 없습니다. 「내 정보」에서 이메일을 넣어 주세요.' ); }
	$o = get_user_meta( $u->ID, 'md_sec_otp', true );
	$o = is_array( $o ) ? $o : array();
	$now = time();
	if ( ! empty( $o['sent'] ) && $now - (int) $o['sent'] < 60 ) { return new WP_Error( 'wait', '방금 보냈습니다. 1분 뒤에 다시 보낼 수 있습니다.' ); }
	$hist = array_values( array_filter( (array) ( $o['hist'] ?? array() ), function ( $t ) use ( $now ) { return $now - (int) $t < HOUR_IN_SECONDS; } ) );
	if ( count( $hist ) >= 6 ) { return new WP_Error( 'many', '한 시간에 6번까지 보낼 수 있습니다. 잠시 뒤에 다시 해 주세요.' ); }
	$code = (string) wp_rand( 100000, 999999 );
	$hist[] = $now;
	update_user_meta( $u->ID, 'md_sec_otp', array( 'hash' => wp_hash_password( $code ), 'exp' => $now + 10 * MINUTE_IN_SECONDS, 'tries' => 0, 'sent' => $now, 'hist' => $hist ) );
	$ok = wp_mail( $u->user_email, '[문치과병원] 직원 라운지 인증 코드 ' . $code,
		"직원 라운지 인증 코드: " . $code . "\n\n10분 안에 화면에 넣어 주세요.\n기기: " . md_sec_device_label() . ' · ' . md_sec_ip() . "\n\n본인이 요청하지 않았다면 이 메일은 무시하고 경영지원실로 알려 주세요.\n— 한아의료재단 문치과병원" );
	return $ok ? true : new WP_Error( 'send', '메일을 보내지 못했습니다. 잠시 뒤에 다시 해 주세요.' );
}

function md_sec_check_code( $code ) {
	$uid = get_current_user_id();
	$o   = get_user_meta( $uid, 'md_sec_otp', true );
	if ( ! is_array( $o ) || empty( $o['hash'] ) ) { return new WP_Error( 'none', '먼저 「코드 보내기」를 눌러 주세요.' ); }
	if ( time() > (int) $o['exp'] ) { return new WP_Error( 'exp', '코드 시간이 지났습니다. 다시 보내 주세요.' ); }
	if ( (int) $o['tries'] >= 5 ) { return new WP_Error( 'tries', '5번 틀렸습니다. 코드를 다시 보내 주세요.' ); }
	if ( ! wp_check_password( preg_replace( '/\D/', '', (string) $code ), $o['hash'] ) ) {
		$o['tries'] = (int) $o['tries'] + 1; update_user_meta( $uid, 'md_sec_otp', $o );
		return new WP_Error( 'bad', '코드가 맞지 않습니다. (' . ( 5 - (int) $o['tries'] ) . '번 남음)' );
	}
	delete_user_meta( $uid, 'md_sec_otp' );
	return true;
}

/* ============================================================
 * 문 지키기 — 탭 권한 · 이메일 인증 (모든 라운지 요청의 맨 앞)
 * ============================================================ */

function md_sec_req_app() { return isset( $_GET['app'] ) ? sanitize_key( wp_unslash( $_GET['app'] ) ) : ''; }

function md_sec_guard() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() || ! is_user_logged_in() ) { return; }
	$app = md_sec_req_app();
	$is_post = 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' );
	/* v8.2 · moondentalmanager 는 홈페이지 관리 전용 — 라운지에서는 쓰지 않는다 (원장 지시) */
	if ( defined( 'MD_SUP_MANAGER_LOGIN' ) && wp_get_current_user()->user_login === MD_SUP_MANAGER_LOGIN && ! get_option( 'md_sec_allow_manager_lounge' ) && get_users( array( 'capability' => 'md_supply_owner', 'number' => 1, 'fields' => 'ID' ) ) ) { /* 로컬 시험 환경만 옵션으로 허용 · 홈페이지 관리자가 한 명도 없으면 막지 않는다(잠김 방지) */
		wp_die( '<p>이 계정(moondentalmanager)은 홈페이지 관리 전용이라 직원 라운지에서는 쓰지 않습니다.</p><p>본인 계정으로 로그인해 주세요. <a href="' . esc_url( wp_logout_url( home_url( '/직원/' ) ) ) . '">로그아웃</a></p>', '직원 라운지', array( 'response' => 403 ) );
	}
	if ( 'access' === $app ) { wp_safe_redirect( md_sup_url( array( 'app' => 'staff', 'tab' => 'perm' ) ) ); exit; }

	/* 이 화면의 코드 보내기 · 확인 */
	if ( $is_post && isset( $_POST['md_sec'] ) ) { md_sec_handle_post(); return; }

	if ( '' === $app ) { return; }
	$raw = md_sec_raw_apps();
	if ( ! isset( $raw[ $app ] ) ) { return; }
	if ( ! md_sec_can_tab( $app ) ) {
		if ( $is_post || count( $_GET ) > 1 ) { wp_die( '이 화면을 쓸 권한이 없습니다. 원장님께 말씀해 주세요.', '권한 없음', array( 'response' => 403 ) ); }
		wp_safe_redirect( md_sup_url( array( 'app' => '', 'noperm' => 1 ) ) ); exit;
	}
	if ( in_array( $app, md_sec_sensitive(), true ) && ! md_sec_verified() ) {
		/* 화면을 여는 것만 허락 (그 자리에 인증 화면을 그린다) — 저장(POST) · 내려받기 · 조회(md_ 로 시작하는 주소 값)는 막는다 */
		$data = $is_post;
		foreach ( array_keys( $_GET ) as $k ) { if ( 0 === strpos( (string) $k, 'md_' ) || '_n' === $k ) { $data = true; } }
		if ( $data ) {
			if ( isset( $_GET['md_mc_dw'] ) ) { wp_send_json( array( 'ok' => false, 'auth' => true ), 403 ); }
			wp_die( '병원 밖에서는 이메일 인증을 먼저 해 주세요. 화면을 새로고침하면 인증 화면이 나옵니다.', '인증 필요', array( 'response' => 403 ) );
		}
	}
}
add_action( 'template_redirect', 'md_sec_guard', -5 );

function md_sec_handle_post() {
	$act = sanitize_key( wp_unslash( $_POST['md_sec'] ) );
	if ( ! isset( $_POST['md_sec_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_sec_nonce'] ), 'md_sec_' . $act ) ) { wp_die( '요청이 만료되었습니다. 새로고침한 뒤 다시 해 주세요.' ); }
	$back = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : md_sup_url( array( 'app' => '' ) );
	$go = function ( $msg, $ok = false ) use ( $back ) { wp_safe_redirect( add_query_arg( $ok ? 'secok' : 'secerr', rawurlencode( $msg ), $back ) ); exit; };
	$uid = get_current_user_id();
	switch ( $act ) {
		case 'send':
			$r = md_sec_send_code();
			$go( is_wp_error( $r ) ? $r->get_error_message() : '인증 코드를 메일로 보냈습니다. 메일이 안 보이면 스팸메일함도 확인해 주세요.', ! is_wp_error( $r ) );
			break;
		case 'verify':
			$r = md_sec_check_code( wp_unslash( $_POST['code'] ?? '' ) );
			if ( is_wp_error( $r ) ) { $go( $r->get_error_message() ); }
			md_sec_trust_this_device( ! empty( $_POST['keep'] ) );
			wp_safe_redirect( remove_query_arg( array( 'secok', 'secerr' ), $back ) ); exit;
		case 'drop': /* 내 기기 하나 끊기 */
			$all = md_sec_devices( $uid ); $h = sanitize_key( wp_unslash( $_POST['dev'] ?? '' ) );
			unset( $all[ $h ] ); update_user_meta( $uid, 'md_sec_devices', $all );
			$go( '그 기기의 인증을 끊었습니다.', true );
			break;
		case 'drop_session': /* 내 로그인 하나 끊기 (세션 표의 키 = 토큰 해시) */
			$v = sanitize_key( wp_unslash( $_POST['sess'] ?? '' ) );
			$all = get_user_meta( $uid, 'session_tokens', true );
			if ( $v && is_array( $all ) && isset( $all[ $v ] ) ) { unset( $all[ $v ] ); update_user_meta( $uid, 'session_tokens', $all ); }
			$go( '그 기기를 로그아웃했습니다.', true );
			break;
		case 'drop_others':
			delete_user_meta( $uid, 'md_sec_devices' );
			WP_Session_Tokens::get_instance( $uid )->destroy_others( wp_get_session_token() );
			$go( '이 기기 말고 모든 기기를 끊었습니다.', true );
			break;
		case 'owner_drop': /* 다른 사람 기기 모두 끊기 — 총괄: 다른 총괄 빼고, 라운지 관리자: 일반 직원만 */
			$t = get_userdata( (int) ( $_POST['uid'] ?? 0 ) );
			if ( ! $t || ! md_sec_can_edit_user( $t ) ) { $go( '그 계정의 기기는 끊을 수 없습니다.' ); }
			md_sec_drop_all( (int) $t->ID );
			$go( $t->display_name . ' 님의 기기를 모두 끊었습니다.', true );
			break;
		case 'perms':
			if ( ! md_sup_can_manage() ) { $go( '관리자만 할 수 있습니다.' ); }
			$keys = array_keys( md_sec_tab_list() );
			$tabs = isset( $_POST['tabs'] ) && is_array( $_POST['tabs'] ) ? wp_unslash( $_POST['tabs'] ) : array();
			$grd  = isset( $_POST['grade'] ) && is_array( $_POST['grade'] ) ? wp_unslash( $_POST['grade'] ) : array();
			$inv  = isset( $_POST['inv'] ) && is_array( $_POST['inv'] ) ? wp_unslash( $_POST['inv'] ) : array();
			$owner = md_sec_is_owner();
			$n = 0;
			foreach ( array_map( 'intval', (array) ( $_POST['uids'] ?? array() ) ) as $t ) {
				$tu = get_userdata( $t );
				if ( ! $tu || ! md_sec_can_edit_user( $tu ) ) { continue; }
				/* 등급 — 총괄만, 공용 계정은 늘 직원 */
				$was = md_sec_grade( $tu ); $up = false;
				if ( $owner && isset( $grd[ $t ] ) ) {
					$g = sanitize_key( $grd[ $t ] );
					$up = 'staff' === $was && in_array( $g, array( 'mgr', 'owner' ), true );
					if ( 'mgr' === $g ) { $tu->remove_cap( 'md_supply_owner' ); $tu->add_cap( 'md_supply_manage' ); }
					elseif ( 'staff' === $g ) { $tu->remove_cap( 'md_supply_owner' ); $tu->remove_cap( 'md_supply_manage' ); }
					$tu = get_userdata( $t );
				}
				if ( ! empty( $inv[ $t ] ) ) { $tu->add_cap( 'md_inv_manage' ); } else { $tu->remove_cap( 'md_inv_manage' ); }
				$want = array_values( array_intersect( array_map( 'sanitize_key', (array) ( $tabs[ $t ] ?? array() ) ), $keys ) );
				/* 관리자로 올리면 관리 전용 탭(직원 정보 등)도 같이 켠다 — 직원일 땐 칸이 잠겨 있었으니까 */
				if ( $up ) { foreach ( md_sec_tab_list() as $k => $a ) { if ( ! empty( $a['manage'] ) && ! in_array( $k, $want, true ) ) { $want[] = $k; } } }
				update_user_meta( $t, 'md_lounge_tabs', $want );
				$n++;
			}
			if ( function_exists( 'md_acc_log' ) ) { md_acc_log( '권한 변경', $n . '개 계정 (권한 탭)' ); }
			$go( '권한을 저장했습니다.', true );
			break;
		case 'ips':
			if ( ! md_sec_is_owner() ) { $go( '홈페이지 관리자만 할 수 있습니다.' ); }
			$S = md_sec_settings();
			$list = preg_split( '/[\s,]+/', (string) wp_unslash( $_POST['ips'] ?? '' ) );
			if ( ! empty( $_POST['add_mine'] ) ) { $list[] = md_sec_ip(); }
			$S['hospital_ips'] = array_values( array_unique( array_filter( array_map( 'trim', $list ), function ( $p ) { return (bool) preg_match( '/^[0-9a-fA-F:.]+\*?$/', $p ); } ) ) );
			update_option( 'md_sec_settings', $S, false );
			$go( '병원 인터넷 주소를 저장했습니다.', true );
			break;
		case 'sensitive': /* v9.17 · 🔒 이메일 인증 탭 고르기 (홈페이지 관리자) */
			if ( ! md_sec_is_owner() ) { $go( '홈페이지 관리자만 할 수 있습니다.' ); }
			$S = md_sec_settings();
			$ok = array_keys( md_sec_raw_apps() );
			$pick = array();
			foreach ( (array) ( $_POST['sens'] ?? array() ) as $k ) { $k = sanitize_key( wp_unslash( $k ) ); if ( in_array( $k, $ok, true ) ) { $pick[] = $k; } }
			if ( in_array( 'survey_result', $pick, true ) || in_array( 'survey', $pick, true ) ) { $pick[] = 'survey'; } /* 접수수납목록(숨김)은 만족도 결과와 같이 */
			$S['sensitive_tabs'] = array_values( array_unique( $pick ) );
			update_option( 'md_sec_settings', $S, false );
			if ( function_exists( 'md_acc_log' ) ) { md_acc_log( '보안 설정', '이메일 인증 탭: ' . ( $pick ? implode( ', ', $pick ) : '없음' ) ); }
			$go( '이메일 인증이 필요한 탭을 저장했습니다' . ( $pick ? '.' : ' — 이제 병원 밖에서도 인증 없이 열립니다.' ), true );
			break;
	}
	$go( '알 수 없는 요청입니다.' );
}

function md_sec_hidden( $act, $back = '' ) {
	echo '<input type="hidden" name="md_sec" value="' . esc_attr( $act ) . '">';
	wp_nonce_field( 'md_sec_' . $act, 'md_sec_nonce', false );
	echo '<input type="hidden" name="back" value="' . esc_attr( '' !== $back ? $back : ( is_ssl() ? 'https://' : 'http://' ) . ( $_SERVER['HTTP_HOST'] ?? '' ) . remove_query_arg( array( 'secok', 'secerr' ), $_SERVER['REQUEST_URI'] ?? '/' ) ) . '">';
}

function md_sec_flash() {
	foreach ( array( 'secok' => 'ok', 'secerr' => 'warn' ) as $k => $c ) {
		if ( ! empty( $_GET[ $k ] ) ) { echo '<div class="mds-notice mds-notice--' . $c . '">' . esc_html( wp_unslash( $_GET[ $k ] ) ) . '</div>'; }
	}
}

/* ============================================================
 * 화면 — 라운지 디스패치 맨 앞에서 부른다 (true = 여기서 그렸다)
 * ============================================================ */

function md_sec_render_gate( $app ) {
	if ( isset( $_GET['noperm'] ) && '' === $app ) { echo '<div class="mds-notice mds-notice--warn">그 화면을 쓸 권한이 없습니다. 필요하면 원장님께 말씀해 주세요.</div>'; }
	if ( 'staff' === $app && function_exists( 'md_sup_can_manage' ) && md_sup_can_manage() ) {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		md_sec_render_staff_nav( $tab );
		if ( in_array( $tab, array( 'perm', 'devices', 'net', 'views' ), true ) ) { md_sec_render_access( $tab ); return true; }
		return false; /* 직원 명단은 원래 화면 (inc/staff) */
	}
	if ( '' !== $app && in_array( $app, md_sec_sensitive(), true ) && ! md_sec_verified() ) { md_sec_render_otp( $app ); return true; }
	return false;
}

function md_sec_render_otp( $app ) {
	$u = wp_get_current_user();
	$S = md_sec_settings();
	$o = get_user_meta( $u->ID, 'md_sec_otp', true );
	$sent = is_array( $o ) && ! empty( $o['exp'] ) && time() < (int) $o['exp'];
	?>
	<section class="mds-card mdsec-otp">
		<?php md_sec_flash(); ?>
		<h2>🔒 이메일 인증</h2>
		<p class="mds-hint">환자 정보가 있는 화면이라, 병원 밖에서 열 때는 이메일로 받은 코드 6자리를 한 번 더 넣습니다. 병원 인터넷에서는 묻지 않습니다.</p>
		<?php if ( ! is_email( $u->user_email ) ) : ?>
			<div class="mds-notice mds-notice--warn">이 계정에는 이메일이 없어 병원 밖에서는 열 수 없습니다. <a href="<?php echo esc_url( md_sup_url( array( 'app' => 'me' ) ) ); ?>">내 정보</a>에서 이메일을 넣거나, 병원에서 열어 주세요.</div>
		<?php else : ?>
			<form method="post" class="mdsec-row"><?php md_sec_hidden( 'send' ); ?>
				<span>받는 곳: <b><?php echo esc_html( md_sec_mask_email( $u->user_email ) ); /* v10.2 · 공용 계정도 가려서 (원장 지시) */ ?></b></span>
				<button type="submit" class="mds-btn<?php echo $sent ? '' : ' mds-btn--fill'; ?>"><?php echo $sent ? '코드 다시 보내기' : '코드 보내기'; ?></button>
			</form>
			<form method="post" class="mdsec-code"><?php md_sec_hidden( 'verify' ); ?>
				<label class="mds-field"><span>인증 코드 6자리</span><input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required<?php echo $sent ? ' autofocus' : ''; ?>></label>
				<?php if ( $sent ) : ?><p class="mds-hint mdsec-spam">메일이 1~2분 안에 안 오면 <b>스팸메일함</b>도 확인해 주세요. 코드는 10분 동안 쓸 수 있습니다.</p><?php endif; /* v9.11 · 원장 지시 */ ?>
				<label class="mdsec-keep"><input type="checkbox" name="keep" value="1"> 이 기기에서 로그인 상태 유지</label>
				<button type="submit" class="mds-btn mds-btn--fill">확인</button>
			</form>
		<?php endif; ?>
	</section>
	<?php
}

/** 내 정보 — 내 기기 */
function md_sec_render_my_devices( $u ) {
	$cur  = wp_get_session_token() ? hash( 'sha256', wp_get_session_token() ) : '';
	$devs = md_sec_devices( $u->ID );
	$mine_dev = md_sec_cookie() ? hash( 'sha256', md_sec_cookie() ) : '';
	?>
	<section class="mds-card mda-card mdsec-dev" id="mdsec-dev">
		<h3 class="mda-h3">로그인한 기기</h3>
		<?php md_sec_flash(); ?>
		<ul class="mdsec-list">
		<?php
		$raw = get_user_meta( $u->ID, 'session_tokens', true );
		foreach ( (array) $raw as $verifier => $s ) :
			if ( ! is_array( $s ) || ( $s['expiration'] ?? 0 ) < time() ) { continue; }
			$is = $verifier === $cur; ?>
			<li><b><?php echo esc_html( md_sec_ua_label( $s['ua'] ?? '' ) ); ?></b><?php echo $is ? ' <span class="mdsec-tag">지금 이 기기</span>' : ''; ?>
				<small>로그인 <?php echo esc_html( wp_date( 'n/j H:i', (int) ( $s['login'] ?? 0 ) ) ); ?> · <?php echo esc_html( $s['ip'] ?? '' ); ?> · <?php echo esc_html( wp_date( 'n/j', (int) $s['expiration'] ) ); ?>까지</small>
				<?php if ( ! $is ) : ?><form method="post"><?php md_sec_hidden( 'drop_session' ); ?><input type="hidden" name="sess" value="<?php echo esc_attr( $verifier ); ?>"><button class="mds-btn mds-btn--ghost">로그아웃</button></form><?php endif; ?>
			</li>
		<?php endforeach; ?>
		</ul>
		<?php if ( $devs ) : ?>
		<h3 class="mda-h3">환자 정보 화면 인증 (이메일)</h3>
		<ul class="mdsec-list">
			<?php foreach ( $devs as $h => $d ) : if ( $d['expires'] < time() ) { continue; } ?>
			<li><b><?php echo esc_html( $d['label'] ); ?></b><?php echo $h === $mine_dev ? ' <span class="mdsec-tag">지금 이 기기</span>' : ''; ?>
				<small>인증 <?php echo esc_html( wp_date( 'n/j H:i', (int) $d['at'] ) ); ?> · 마지막 <?php echo esc_html( wp_date( 'n/j', (int) $d['last'] ) ); ?> · <?php echo $d['keep'] ? esc_html( wp_date( 'n/j', (int) $d['expires'] ) ) . '까지' : '오늘만'; ?></small>
				<form method="post"><?php md_sec_hidden( 'drop' ); ?><input type="hidden" name="dev" value="<?php echo esc_attr( $h ); ?>"><button class="mds-btn mds-btn--ghost">끊기</button></form>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
		<form method="post" onsubmit="return confirm('이 기기 말고 다른 기기를 모두 로그아웃할까요?');"><?php md_sec_hidden( 'drop_others' ); ?><button class="mds-btn">이 기기 말고 모두 끊기</button></form>
		<p class="mds-hint">휴대폰을 잃어버렸거나 모르는 기기가 있으면 끊고 비밀번호를 바꾸세요.</p>
	</section>
	<?php
}
add_action( 'md_acc_me_after', 'md_sec_render_my_devices' );

function md_sec_ua_label( $ua ) {
	$kind = preg_match( '/iPad|Tablet/i', $ua ) ? '태블릿' : ( preg_match( '/Mobile|Android|iPhone/i', $ua ) ? '휴대폰' : 'PC' );
	$os = preg_match( '/iPhone|iPad/', $ua ) ? 'iOS' : ( false !== stripos( $ua, 'Android' ) ? '안드로이드' : ( false !== stripos( $ua, 'Windows' ) ? '윈도우' : ( false !== stripos( $ua, 'Mac OS X' ) ? '맥' : '' ) ) );
	$br = false !== stripos( $ua, 'KAKAOTALK' ) ? '카카오톡' : ( false !== stripos( $ua, 'SamsungBrowser' ) ? '삼성 인터넷' : ( false !== stripos( $ua, 'Edg/' ) ? '엣지' : ( false !== stripos( $ua, 'Chrome' ) || false !== stripos( $ua, 'CriOS' ) ? '크롬' : ( false !== stripos( $ua, 'Safari' ) ? '사파리' : '' ) ) ) );
	return implode( ' · ', array_filter( array( $kind, $os, $br ) ) );
}

/* ============================================================
 * 미니차트 열람 기록 (2년)
 * ============================================================ */

function md_sec_install() {
	if ( (int) get_option( 'md_sec_schema', 0 ) >= MD_SEC_SCHEMA ) { return; }
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( 'CREATE TABLE ' . $wpdb->prefix . "md_mc_view (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		rec_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		chart_no VARCHAR(60) NOT NULL DEFAULT '',
		pname VARCHAR(120) NOT NULL DEFAULT '',
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		who VARCHAR(100) NOT NULL DEFAULT '',
		ip VARCHAR(45) NOT NULL DEFAULT '',
		device VARCHAR(80) NOT NULL DEFAULT '',
		at DATETIME NULL,
		PRIMARY KEY  (id),
		KEY rec_id (rec_id),
		KEY user_id (user_id),
		KEY at (at)
	) " . $wpdb->get_charset_collate() . ';' );
	update_option( 'md_sec_schema', MD_SEC_SCHEMA, false );
}
add_action( 'init', 'md_sec_install', 22 );

function md_sec_log_view( $r ) {
	global $wpdb;
	$u = wp_get_current_user();
	$wpdb->insert( $wpdb->prefix . 'md_mc_view', array(
		'rec_id' => (int) $r->id, 'chart_no' => mb_substr( (string) $r->chart_no, 0, 60 ), 'pname' => mb_substr( (string) $r->pname, 0, 120 ),
		'user_id' => (int) $u->ID, 'who' => mb_substr( '' !== trim( $u->display_name ) ? $u->display_name : $u->user_login, 0, 100 ),
		'ip' => md_sec_ip(), 'device' => md_sec_device_label(), 'at' => current_time( 'mysql' ),
	) );
	/* 2년 지난 기록 정리 — 하루에 한 번 */
	if ( ! get_transient( 'md_sec_view_prune' ) ) {
		set_transient( 'md_sec_view_prune', 1, DAY_IN_SECONDS );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'md_mc_view WHERE at < %s', date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 2 * YEAR_IN_SECONDS ) ) );
	}
}
add_action( 'md_mc_viewed', 'md_sec_log_view' );

/* ============================================================
 * 「접근 권한 · 보안」 탭 (원장 계정만)
 * ============================================================ */

function md_sec_accounts() {
	$out = array();
	foreach ( get_users( array( 'role__in' => array( 'md_stock_staff', 'md_stock_manager' ), 'orderby' => 'display_name' ) ) as $u ) {
		if ( user_can( $u, 'manage_options' ) ) { continue; }
		if ( defined( 'MD_SUP_MANAGER_LOGIN' ) && MD_SUP_MANAGER_LOGIN === $u->user_login ) { continue; }
		$out[] = $u;
	}
	/* 총괄 → 관리자 → 직원 → 공용 순 */
	usort( $out, function ( $a, $b ) {
		$r = function ( $u ) { return md_sec_is_shared_staff( $u ) ? 3 : array( 'owner' => 0, 'mgr' => 1, 'staff' => 2 )[ md_sec_grade( $u ) ]; };
		return $r( $a ) - $r( $b ) ?: strcmp( $a->display_name, $b->display_name );
	} );
	return $out;
}

function md_sec_render_staff_nav( $tab ) {
	$nav = array( '' => '직원 명단', 'perm' => '권한', 'devices' => '기기' );
	if ( md_sec_is_owner() ) { $nav['net'] = '병원 인터넷'; $nav['views'] = '미니차트 열람 기록'; }
	echo '<nav class="mdsec-nav mdsec-nav--staff">';
	foreach ( $nav as $k => $l ) { echo '<a class="mds-tab' . ( $tab === $k ? ' is-on' : '' ) . '" href="' . esc_url( md_sup_url( array( 'app' => 'staff', 'tab' => $k ) ) ) . '">' . esc_html( $l ) . '</a>'; }
	echo '</nav>';
}

function md_sec_render_access( $tab ) {
	global $wpdb;
	if ( in_array( $tab, array( 'net', 'views' ), true ) && ! md_sec_is_owner() ) { echo '<div class="mds-notice mds-notice--warn">홈페이지 관리자만 볼 수 있습니다.</div>'; return; }
	$S = md_sec_settings();
	echo '<div class="mdsec">';
	md_sec_flash();

	if ( 'perm' === $tab ) {
		$tabs  = md_sec_tab_list();
		$owner = md_sec_is_owner();
		?>
		<form method="post" class="mds-card mdsec-card"><?php md_sec_hidden( 'perms' ); ?>
			<p class="mds-hint"><b>등급</b> — 홈페이지 관리자(한 명): 라운지의 모든 것 · 라운지 관리자를 주고 뺌 / 라운지 관리자: 직원 정보 · 계정 승인 · 관리 화면 (일반 직원의 권한만 바꿈) / 직원. 등급은 홈페이지 관리자만 바꿉니다. 홈페이지 관리자는 다른 계정에 줄 수 없습니다.<br><b>재료실 관리</b> — 출고 · 입고 · 주문 · 품목 · 재료실 설정. <b>탭</b> — 열 수 있는 라운지 탭 (🔒 = 병원 밖에서 열 때 이메일 인증). 회색 칸은 라운지 관리자 이상만.</p>
			<div class="mdsec-matrix-wrap"><table class="mds-table mdsec-matrix">
				<thead><tr><th>계정</th><th>등급</th><th>재료실<br>관리</th><?php foreach ( $tabs as $k => $a ) : ?><th><?php echo esc_html( ( $a['icon'] ?? '' ) . ' ' . preg_replace( '/\s*·\s*권한$/u', '', $a['label'] ) . ( in_array( $k, md_sec_sensitive(), true ) ? ' 🔒' : '' ) ); ?></th><?php endforeach; ?></tr></thead>
				<tbody>
				<?php foreach ( md_sec_accounts() as $u ) :
					$edit = md_sec_can_edit_user( $u ); $g = md_sec_grade( $u ); $shared = md_sec_is_shared_staff( $u );
					$have = md_sec_is_owner_user( $u ) ? array_keys( $tabs ) : (array) md_sec_user_tabs( $u ); $mgr = md_sec_is_mgr_user( $u ); ?>
					<tr class="<?php echo $edit ? '' : 'is-lock'; ?>"><th><?php if ( $edit ) : ?><input type="hidden" name="uids[]" value="<?php echo (int) $u->ID; ?>"><?php endif; ?><?php echo esc_html( '' !== trim( $u->display_name ) ? $u->display_name : $u->user_login ); ?><?php echo (int) $u->ID === get_current_user_id() ? ' <small>(나)</small>' : ''; ?><?php echo $shared ? ' <small>공용 계정</small>' : ''; ?></th>
						<td><?php if ( $edit && $owner ) : /* v8.3 · 직원공용도 등급을 고를 수 있다 (원장 지시) */ ?>
							<select name="grade[<?php echo (int) $u->ID; ?>]"><?php foreach ( array( 'staff', 'mgr' ) as $o ) : /* v8.3 · 홈페이지 관리자는 한 명뿐 — 다른 직원은 지정할 수 없다 */ ?><option value="<?php echo esc_attr( $o ); ?>"<?php selected( $g, $o ); ?>><?php echo esc_html( md_sec_grade_label( $o ) ); ?></option><?php endforeach; ?></select>
						<?php else : ?><span class="mdsec-grade mdsec-grade--<?php echo esc_attr( $g ); ?>"><?php echo esc_html( md_sec_grade_label( $g ) ); ?></span><?php endif; ?></td>
						<td><label class="mdsec-cell"><input type="checkbox" name="inv[<?php echo (int) $u->ID; ?>]" value="1"<?php checked( user_can( $u, 'md_inv_manage' ) || md_sec_is_owner_user( $u ) ); disabled( ! $edit || md_sec_is_owner_user( $u ) ); ?>></label></td>
						<?php foreach ( $tabs as $k => $a ) : $lock = ! $edit || ( ! empty( $a['manage'] ) && ! $mgr ) || md_sec_is_owner_user( $u ); ?>
						<td><label class="mdsec-cell<?php echo ( ! empty( $a['manage'] ) && ! $mgr ) ? ' is-lock' : ''; ?>"><input type="checkbox" name="tabs[<?php echo (int) $u->ID; ?>][]" value="<?php echo esc_attr( $k ); ?>"<?php checked( in_array( $k, $have, true ) ); disabled( $lock ); ?> aria-label="<?php echo esc_attr( $u->display_name . ' ' . $a['label'] ); ?>"></label></td>
						<?php endforeach; ?></tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
			<button class="mds-btn mds-btn--fill">저장</button>
		</form>
		<?php
	} elseif ( 'devices' === $tab ) {
		$any = false;
		echo '<section class="mds-card mdsec-card"><p class="mds-hint">계정마다 로그인 중인 기기와 이메일 인증한 기기입니다. 「모두 끊기」를 누르면 그 사람은 모든 기기에서 로그아웃되고 다음에 다시 인증합니다. 사용 중지 · 퇴사 처리하면 자동으로 끊깁니다. 홈페이지 관리자는 다른 홈페이지 관리자 말고 모두, 라운지 관리자는 일반 직원만 끊을 수 있습니다.</p><table class="mds-table mdsec-devtable"><thead><tr><th>계정</th><th>로그인 기기</th><th>인증 기기</th><th></th></tr></thead><tbody>';
		foreach ( md_sec_accounts() as $u ) {
			$sess = array_filter( (array) get_user_meta( $u->ID, 'session_tokens', true ), function ( $x ) { return is_array( $x ) && ( $x['expiration'] ?? 0 ) > time(); } );
			$devs = array_filter( md_sec_devices( $u->ID ), function ( $d ) { return $d['expires'] > time(); } );
			if ( ! $sess && ! $devs ) { continue; }
			$any = true;
			$sl = implode( '<br>', array_map( function ( $x ) { return esc_html( md_sec_ua_label( $x['ua'] ?? '' ) . ' · ' . wp_date( 'n/j', (int) ( $x['login'] ?? 0 ) ) ); }, $sess ) );
			$dl = implode( '<br>', array_map( function ( $d ) { return esc_html( $d['label'] . ' · ' . wp_date( 'n/j', (int) $d['last'] ) ); }, $devs ) );
			echo '<tr><th>' . esc_html( $u->display_name ) . '</th><td>' . $sl . '</td><td>' . ( $dl ? $dl : '—' ) . '</td><td>'; // phpcs:ignore
			if ( md_sec_can_edit_user( $u ) ) {
				echo '<form method="post" onsubmit="return confirm(\'' . esc_js( $u->display_name ) . ' 님의 모든 기기를 끊을까요?\');">';
				md_sec_hidden( 'owner_drop' );
				echo '<input type="hidden" name="uid" value="' . (int) $u->ID . '"><button class="mds-btn">모두 끊기</button></form>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>' . ( $any ? '' : '<p class="mds-hint">지금 로그인 중인 기기가 없습니다.</p>' ) . '</section>';
	} elseif ( 'net' === $tab ) {
		$me = md_sec_ip();
		?>
		<form method="post" class="mds-card mdsec-card"><?php md_sec_hidden( 'ips' ); ?>
			<p class="mds-hint">여기 적은 인터넷 주소(IP)에서 접속하면 환자 정보 화면도 이메일 코드 없이 열립니다. 병원 인터넷 주소만 적으세요. 끝을 * 로 적으면 그 대역 전체(예: 211.195.235.*).</p>
			<p>지금 접속한 주소: <b><?php echo esc_html( $me ); ?></b> — <?php echo md_sec_is_hospital( $me ) ? '병원 인터넷으로 등록돼 있습니다' : '등록돼 있지 않습니다'; ?></p>
			<label class="mds-field"><span>병원 인터넷 주소 (한 줄에 하나)</span><textarea name="ips" rows="4"><?php echo esc_textarea( implode( "\n", (array) $S['hospital_ips'] ) ); ?></textarea></label>
			<label class="mdsec-keep"><input type="checkbox" name="add_mine" value="1"> 지금 접속한 주소(<?php echo esc_html( $me ); ?>)도 추가 — 병원에서 접속했을 때만</label>
			<button class="mds-btn mds-btn--fill">저장</button>
		</form>
		<?php /* v9.17 · 🔒 이메일 인증이 필요한 탭 고르기 (원장 지시) */ $sens = md_sec_sensitive(); ?>
		<form method="post" class="mds-card mdsec-card"><?php md_sec_hidden( 'sensitive' ); ?>
			<h3 class="mda-h3">🔒 병원 밖에서 열 때 이메일 인증이 필요한 탭</h3>
			<p class="mds-hint">체크한 탭은 병원 인터넷 밖에서 열 때 이메일로 받은 코드를 한 번 더 넣어야 합니다(기기마다 <?php echo (int) $S['keep_days']; ?>일 기억). 환자 정보 · 매출이 있는 탭에 켜 두세요. 저장하면 바로 적용됩니다.</p>
			<div class="mdsec-sens">
			<?php foreach ( md_sec_tab_list() as $k => $a ) : ?>
				<label class="mdsec-keep"><input type="checkbox" name="sens[]" value="<?php echo esc_attr( $k ); ?>"<?php checked( in_array( $k, $sens, true ) ); ?>> <?php echo esc_html( ( $a['icon'] ?? '' ) . ' ' . str_replace( ' 🔒', '', (string) ( $a['label'] ?? $k ) ) ); ?></label>
			<?php endforeach; ?>
			</div>
			<button class="mds-btn mds-btn--fill"<?php echo md_sec_is_owner() ? '' : ' disabled'; ?>>저장</button><?php echo md_sec_is_owner() ? '' : ' <span class="mds-hint">홈페이지 관리자만 바꿀 수 있습니다.</span>'; ?>
		</form>
		<?php
	} else {
		$q  = isset( $_GET['vq'] ) ? sanitize_text_field( wp_unslash( $_GET['vq'] ) ) : '';
		$t  = $wpdb->prefix . 'md_mc_view';
		$w  = '' !== $q ? $wpdb->prepare( ' WHERE chart_no LIKE %s OR pname LIKE %s OR who LIKE %s', '%' . $wpdb->esc_like( $q ) . '%', '%' . $wpdb->esc_like( $q ) . '%', '%' . $wpdb->esc_like( $q ) . '%' ) : '';
		$rows = $wpdb->get_results( "SELECT * FROM $t$w ORDER BY id DESC LIMIT 300" ); // phpcs:ignore
		?>
		<section class="mds-card mdsec-card">
			<form method="get" class="mdsec-row"><input type="hidden" name="app" value="staff"><input type="hidden" name="tab" value="views">
				<input name="vq" value="<?php echo esc_attr( $q ); ?>" placeholder="차트번호 · 환자 이름 · 본 사람"><button class="mds-btn">찾기</button></form>
			<p class="mds-hint">누가 언제 어떤 미니차트를 열었는지 2년 동안 남깁니다 (최근 300건).</p>
			<div class="mdsec-matrix-wrap"><table class="mds-table"><thead><tr><th>일시</th><th>본 사람</th><th>차트</th><th>기기 · 주소</th></tr></thead><tbody>
			<?php foreach ( $rows as $r ) : ?>
				<tr><td><?php echo esc_html( mysql2date( 'y.n.j H:i', $r->at ) ); ?></td><td><?php echo esc_html( $r->who ); ?></td><td><?php echo esc_html( $r->chart_no . ' ' . $r->pname ); ?></td><td><small><?php echo esc_html( $r->device . ' · ' . $r->ip . ( md_sec_is_hospital( $r->ip ) ? ' (병원)' : '' ) ); ?></small></td></tr>
			<?php endforeach; ?>
			<?php if ( ! $rows ) : ?><tr><td colspan="4">기록이 없습니다.</td></tr><?php endif; ?>
			</tbody></table></div>
		</section>
		<?php
	}
	echo '</div>';
}

function md_sec_enqueue() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	$f = '/assets/css/lounge-security.css';
	if ( file_exists( get_stylesheet_directory() . $f ) ) {
		wp_enqueue_style( 'md-lounge-security', get_stylesheet_directory_uri() . $f, array( 'moondental-supply' ), filemtime( get_stylesheet_directory() . $f ) );
	}
}
add_action( 'wp_enqueue_scripts', 'md_sec_enqueue', 42 );

/* v8.3 · 직원공용 계정 이메일 (원장 지시 2026-10-07) — 한 번만 */
add_action( 'init', function () {
	if ( get_option( 'md_sec_staffmail_v2' ) || ! defined( 'MD_SUP_STAFF_LOGIN' ) ) { return; }
	$u = get_user_by( 'login', MD_SUP_STAFF_LOGIN );
	if ( ! $u ) { return; }
	$mail = 'moondental1995@naver.com'; /* v8.8 · 원장 지시로 바꿈 (전: moondentalhospital@gmail.com) */
	$other = email_exists( $mail );
	if ( ! $other || (int) $other === (int) $u->ID ) { wp_update_user( array( 'ID' => $u->ID, 'user_email' => $mail ) ); }
	update_option( 'md_sec_staffmail_v2', $other && (int) $other !== (int) $u->ID ? 'taken #' . $other : 'done', false );
}, 30 );

/* v10.1 · 직원공용 이메일은 늘 이 주소 (원장 지시) — 다른 계정(예: 홈페이지 관리자)이 같은 주소를 쓰고 있으면
   워드프레스가 바꾸기를 거절해서 v8.8 이 건너뛰었다. 공용 계정만 DB 에 직접 넣는다 (로그인은 이름 「직원공용」이라 상관없음) */
function md_sec_staff_mail() { return 'moondental1995@naver.com'; }
add_action( 'init', function () {
	if ( ! defined( 'MD_SUP_STAFF_LOGIN' ) ) { return; }
	$u = get_user_by( 'login', MD_SUP_STAFF_LOGIN );
	if ( ! $u || md_sec_staff_mail() === $u->user_email ) { return; }
	global $wpdb;
	$other = email_exists( md_sec_staff_mail() );
	$wpdb->update( $wpdb->users, array( 'user_email' => md_sec_staff_mail() ), array( 'ID' => (int) $u->ID ) );
	clean_user_cache( $u->ID );
	update_option( 'md_sec_staffmail_v3', current_time( 'mysql' ) . ' · 전: ' . $u->user_email . ( $other && (int) $other !== (int) $u->ID ? ' · 같은 주소 계정 #' . $other : '' ), false );
}, 31 );

/* v9.47 · 공용 계정 이름 「문치과병원」으로 잘못 만들어진 개인 계정(운영 #187 staff426650, 2026-10-09) 지우기 — 원장 지시 「공용 계정이 아닌 것은 없애줘」.
   공용 계정(staffcommon) · 관리자 계정은 건드리지 않고, 공용 이름과 같은 이름의 개인 계정만. 한 번만, 결과는 옵션 md_sec_dupshared_v1 */
add_action( 'init', function () {
	if ( get_option( 'md_sec_dupshared_v1' ) || ! defined( 'MD_SUP_STAFF_LOGIN' ) || ! function_exists( 'md_acc_users_by_name' ) || ! function_exists( 'md_acc_is_shared_name' ) ) { return; }
	$su = get_user_by( 'login', MD_SUP_STAFF_LOGIN );
	if ( ! $su ) { return; }
	require_once ABSPATH . 'wp-admin/includes/user.php';
	$log = array();
	foreach ( array_unique( array( $su->display_name, '문치과병원' ) ) as $nm ) {
		if ( ! md_acc_is_shared_name( $nm ) ) { continue; }
		foreach ( md_acc_users_by_name( $nm, $su->ID ) as $u ) {
			if ( user_can( $u, 'manage_options' ) || user_can( $u, 'md_supply_manage' ) ) { $log[] = '#' . $u->ID . ' ' . $u->user_login . ' (관리자 등급이라 그대로)'; continue; }
			$log[] = '#' . $u->ID . ' ' . $u->user_login . ' · ' . $u->user_email . ' · 가입 ' . $u->user_registered;
			wp_delete_user( $u->ID );
		}
	}
	if ( function_exists( 'md_acc_log' ) && $log ) { md_acc_log( '중복 계정 삭제', implode( ' / ', $log ) ); }
	update_option( 'md_sec_dupshared_v1', current_time( 'mysql' ) . ' · ' . ( $log ? implode( ' / ', $log ) : '해당 없음' ), false );
}, 41 );

/* v9.41 · 「문치과병원」 계정(예전 공용 계정 moondentalhospital · 이름이 「문치과병원」인 계정)의 이메일도 moondental1995@naver.com (원장 지시 2026-10-09).
   직원공용과 같은 주소라 워드프레스가 바꾸기를 거절하므로 DB 에 직접 넣는다. 홈페이지 관리자(manage_options) 계정은 건드리지 않는다. 전 주소는 옵션에 남김 */
add_action( 'init', function () {
	if ( get_option( 'md_sec_hospmail_v1' ) ) { return; }
	global $wpdb;
	$ids = array();
	if ( $u = get_user_by( 'login', 'moondentalhospital' ) ) { $ids[] = (int) $u->ID; }
	foreach ( (array) $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE display_name = %s", '문치과병원' ) ) as $id ) { $ids[] = (int) $id; }
	$log = array();
	foreach ( array_unique( $ids ) as $id ) {
		$u = get_userdata( $id );
		if ( ! $u || user_can( $u, 'manage_options' ) ) { if ( $u ) { $log[] = '#' . $id . ' ' . $u->user_login . ' (홈페이지 관리자라 그대로)'; } continue; }
		if ( md_sec_staff_mail() === $u->user_email ) { $log[] = '#' . $id . ' ' . $u->user_login . ' (이미 같음)'; continue; }
		$wpdb->update( $wpdb->users, array( 'user_email' => md_sec_staff_mail() ), array( 'ID' => $id ) );
		clean_user_cache( $id );
		$log[] = '#' . $id . ' ' . $u->user_login . ' · 전: ' . $u->user_email;
	}
	update_option( 'md_sec_hospmail_v1', current_time( 'mysql' ) . ' · ' . ( $log ? implode( ' / ', $log ) : '해당 계정 없음' ), false );
}, 32 );
