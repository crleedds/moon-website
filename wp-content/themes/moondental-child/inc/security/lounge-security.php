<?php
/**
 * v8.0 · 직원 라운지 보안 — 탭별 접근 권한 · 환자 정보 화면 이메일 인증 · 기기 관리 · 미니차트 열람 기록
 * (원장 지시 2026-10-06)
 *
 *  1) 탭 권한: 계정마다 라운지 탭(앱)을 켜고 끈다. 원장 계정(moondentalmanager · 워드프레스 관리자)은 늘 전부.
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

/** 이메일 인증이 필요한 화면 */
function md_sec_sensitive() { return array( 'minichart', 'survey_result', 'brief', 'survey' ); }

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

/** 원장 계정 (늘 모든 탭 · 다른 사람 기기 끊기 · 권한 정하기) */
function md_sec_is_owner_user( $u ) {
	if ( ! $u || ! $u->exists() ) { return false; }
	if ( user_can( $u, 'manage_options' ) ) { return true; }
	return $u->user_login === ( defined( 'MD_SUP_MANAGER_LOGIN' ) ? MD_SUP_MANAGER_LOGIN : 'moondentalmanager' );
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
		if ( in_array( $k, array( 'me', 'access' ), true ) ) { continue; }
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
		if ( is_array( $set ) ) { if ( in_array( $k, $set, true ) ) { $out[] = $k; } continue; }
		/* 정하지 않았으면 예전 그대로 — 미니차트는 라운지 관리자만 */
		if ( 'minichart' === $k && ! $mgr ) { continue; }
		$out[] = $k;
	}
	return $out;
}

function md_sec_can_tab( $k, $u = null ) {
	$u = $u ? $u : wp_get_current_user();
	if ( in_array( $k, array( 'me' ), true ) ) { return true; }
	if ( 'access' === $k ) { return md_sec_is_owner_user( $u ); }
	$t = md_sec_user_tabs( $u );
	return null === $t || in_array( $k, $t, true );
}

/** 라운지 앱 목록 거르기 — 못 여는 탭은 빼고(첫 화면 타일 · 주소 둘 다), 환자 정보 탭에는 🔒 */
function md_sec_filter_apps( $apps ) {
	global $md_sec_raw_apps;
	if ( isset( $apps['minichart'] ) ) { unset( $apps['minichart']['manage'] ); } /* 미니차트는 이제 탭 권한으로 */
	$apps['access'] = array( 'label' => '접근 권한 · 보안', 'icon' => '🛡️', 'desc' => '탭 권한 · 병원 인터넷 · 기기 · 미니차트 열람 기록', 'manage' => true );
	$md_sec_raw_apps = $apps;
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
		"직원 라운지 인증 코드: " . $code . "\n\n10분 안에 화면에 넣어 주세요.\n기기: " . md_sec_device_label() . ' · ' . md_sec_ip() . "\n\n본인이 요청하지 않았다면 이 메일은 무시하고 원장님께 알려 주세요.\n— 한아의료재단 문치과병원" );
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
			$go( is_wp_error( $r ) ? $r->get_error_message() : '코드를 보냈습니다. 메일함(스팸함 포함)을 확인해 주세요.', ! is_wp_error( $r ) );
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
		case 'owner_drop': /* 원장 — 다른 사람 기기 모두 끊기 */
			if ( ! md_sec_is_owner() ) { $go( '원장 계정만 할 수 있습니다.' ); }
			$t = (int) ( $_POST['uid'] ?? 0 );
			if ( $t && $t !== $uid ) { md_sec_drop_all( $t ); }
			$go( '그 계정의 기기를 모두 끊었습니다.', true );
			break;
		case 'tabs':
			if ( ! md_sec_is_owner() ) { $go( '원장 계정만 할 수 있습니다.' ); }
			$sent = isset( $_POST['tabs'] ) && is_array( $_POST['tabs'] ) ? wp_unslash( $_POST['tabs'] ) : array();
			$keys = array_keys( md_sec_tab_list() );
			foreach ( array_map( 'intval', (array) ( $_POST['uids'] ?? array() ) ) as $t ) {
				$tu = get_userdata( $t );
				if ( ! $tu || md_sec_is_owner_user( $tu ) ) { continue; }
				$want = array_values( array_intersect( array_map( 'sanitize_key', (array) ( $sent[ $t ] ?? array() ) ), $keys ) );
				update_user_meta( $t, 'md_lounge_tabs', $want );
			}
			$go( '탭 권한을 저장했습니다.', true );
			break;
		case 'ips':
			if ( ! md_sec_is_owner() ) { $go( '원장 계정만 할 수 있습니다.' ); }
			$S = md_sec_settings();
			$list = preg_split( '/[\s,]+/', (string) wp_unslash( $_POST['ips'] ?? '' ) );
			if ( ! empty( $_POST['add_mine'] ) ) { $list[] = md_sec_ip(); }
			$S['hospital_ips'] = array_values( array_unique( array_filter( array_map( 'trim', $list ), function ( $p ) { return (bool) preg_match( '/^[0-9a-fA-F:.]+\*?$/', $p ); } ) ) );
			update_option( 'md_sec_settings', $S, false );
			$go( '병원 인터넷 주소를 저장했습니다.', true );
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
	if ( 'access' === $app ) { md_sec_render_access(); return true; }
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
				<span>받는 곳: <b><?php echo esc_html( md_sec_mask_email( $u->user_email ) ); ?></b></span>
				<button type="submit" class="mds-btn<?php echo $sent ? '' : ' mds-btn--fill'; ?>"><?php echo $sent ? '코드 다시 보내기' : '코드 보내기'; ?></button>
			</form>
			<form method="post" class="mdsec-code"><?php md_sec_hidden( 'verify' ); ?>
				<label class="mds-field"><span>인증 코드 6자리</span><input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required<?php echo $sent ? ' autofocus' : ''; ?>></label>
				<label class="mdsec-keep"><input type="checkbox" name="keep" value="1"> 이 기기에서 로그인 상태 유지 <small>(<?php echo (int) $S['keep_days']; ?>일 · <?php echo (int) $S['idle_days']; ?>일 동안 안 쓰면 다시 확인 · 병원 공용 PC에서는 고르지 마세요)</small></label>
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
		if ( md_sec_is_owner_user( $u ) ) { continue; }
		$out[] = $u;
	}
	return $out;
}

function md_sec_render_access() {
	if ( ! md_sec_is_owner() ) { echo '<div class="mds-notice mds-notice--warn">원장 계정만 볼 수 있습니다.</div>'; return; }
	global $wpdb;
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'tabs';
	$S   = md_sec_settings();
	$nav = array( 'tabs' => '탭 권한', 'devices' => '기기', 'net' => '병원 인터넷', 'views' => '미니차트 열람 기록' );
	echo '<div class="mdsec">';
	md_sec_flash();
	echo '<nav class="mdsec-nav">';
	foreach ( $nav as $k => $l ) { echo '<a class="mds-tab' . ( $tab === $k ? ' is-on' : '' ) . '" href="' . esc_url( md_sup_url( array( 'app' => 'access', 'tab' => $k ) ) ) . '">' . esc_html( $l ) . '</a>'; }
	echo '</nav>';

	if ( 'tabs' === $tab ) {
		$tabs = md_sec_tab_list();
		$accs = md_sec_accounts();
		?>
		<form method="post" class="mds-card mdsec-card"><?php md_sec_hidden( 'tabs' ); ?>
			<p class="mds-hint">계정마다 열 수 있는 탭을 고릅니다. 🔒 는 병원 밖에서 열 때 이메일 인증을 하는 환자 정보 탭입니다. 회색 칸은 라운지 관리자 권한이 있어야 줄 수 있습니다(직원 정보에서). 원장 계정은 늘 모든 탭을 엽니다.</p>
			<div class="mdsec-matrix-wrap"><table class="mds-table mdsec-matrix">
				<thead><tr><th>계정</th><?php foreach ( $tabs as $k => $a ) : ?><th><?php echo esc_html( ( $a['icon'] ?? '' ) . ' ' . $a['label'] . ( in_array( $k, md_sec_sensitive(), true ) ? ' 🔒' : '' ) ); ?></th><?php endforeach; ?></tr></thead>
				<tbody>
				<?php foreach ( $accs as $u ) :
					$have = (array) md_sec_user_tabs( $u ); $mgr = user_can( $u, 'md_supply_manage' );
					$shared = function_exists( 'md_acc_shared_logins' ) && in_array( $u->user_login, md_acc_shared_logins(), true ); ?>
					<tr><th><input type="hidden" name="uids[]" value="<?php echo (int) $u->ID; ?>"><?php echo esc_html( '' !== trim( $u->display_name ) ? $u->display_name : $u->user_login ); ?> <small><?php echo $shared ? '공용 계정' : ( $mgr ? '라운지 관리자' : '' ); ?></small></th>
					<?php foreach ( $tabs as $k => $a ) : $lock = ! empty( $a['manage'] ) && ! $mgr; ?>
						<td><label class="mdsec-cell<?php echo $lock ? ' is-lock' : ''; ?>"><input type="checkbox" name="tabs[<?php echo (int) $u->ID; ?>][]" value="<?php echo esc_attr( $k ); ?>"<?php checked( in_array( $k, $have, true ) ); disabled( $lock ); ?> aria-label="<?php echo esc_attr( $u->display_name . ' ' . $a['label'] ); ?>"></label></td>
					<?php endforeach; ?></tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
			<button class="mds-btn mds-btn--fill">저장</button>
		</form>
		<?php
	} elseif ( 'devices' === $tab ) {
		$any = false;
		echo '<section class="mds-card mdsec-card"><p class="mds-hint">계정마다 로그인 중인 기기와 이메일 인증한 기기입니다. 「모두 끊기」를 누르면 그 사람은 모든 기기에서 로그아웃되고 다음에 다시 인증합니다. 사용 중지 · 퇴사 처리하면 자동으로 끊깁니다.</p><table class="mds-table mdsec-devtable"><thead><tr><th>계정</th><th>로그인 기기</th><th>인증 기기</th><th></th></tr></thead><tbody>';
		foreach ( md_sec_accounts() as $u ) {
			$sess = array_filter( (array) get_user_meta( $u->ID, 'session_tokens', true ), function ( $s ) { return is_array( $s ) && ( $s['expiration'] ?? 0 ) > time(); } );
			$devs = array_filter( md_sec_devices( $u->ID ), function ( $d ) { return $d['expires'] > time(); } );
			if ( ! $sess && ! $devs ) { continue; }
			$any = true;
			$sl = implode( '<br>', array_map( function ( $s ) { return esc_html( md_sec_ua_label( $s['ua'] ?? '' ) . ' · ' . wp_date( 'n/j', (int) ( $s['login'] ?? 0 ) ) ); }, $sess ) );
			$dl = implode( '<br>', array_map( function ( $d ) { return esc_html( $d['label'] . ' · ' . wp_date( 'n/j', (int) $d['last'] ) ); }, $devs ) );
			echo '<tr><th>' . esc_html( $u->display_name ) . '</th><td>' . $sl . '</td><td>' . ( $dl ? $dl : '—' ) . '</td><td><form method="post" onsubmit="return confirm(\'' . esc_js( $u->display_name ) . ' 님의 모든 기기를 끊을까요?\');">'; // phpcs:ignore
			md_sec_hidden( 'owner_drop' );
			echo '<input type="hidden" name="uid" value="' . (int) $u->ID . '"><button class="mds-btn">모두 끊기</button></form></td></tr>';
		}
		echo '</tbody></table>' . ( $any ? '' : '<p class="mds-hint">지금 로그인 중인 직원 기기가 없습니다.</p>' );
		echo '</section>';
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
		<?php
	} else {
		$q  = isset( $_GET['vq'] ) ? sanitize_text_field( wp_unslash( $_GET['vq'] ) ) : '';
		$t  = $wpdb->prefix . 'md_mc_view';
		$w  = '' !== $q ? $wpdb->prepare( ' WHERE chart_no LIKE %s OR pname LIKE %s OR who LIKE %s', '%' . $wpdb->esc_like( $q ) . '%', '%' . $wpdb->esc_like( $q ) . '%', '%' . $wpdb->esc_like( $q ) . '%' ) : '';
		$rows = $wpdb->get_results( "SELECT * FROM $t$w ORDER BY id DESC LIMIT 300" ); // phpcs:ignore
		?>
		<section class="mds-card mdsec-card">
			<form method="get" class="mdsec-row"><input type="hidden" name="app" value="access"><input type="hidden" name="tab" value="views">
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
