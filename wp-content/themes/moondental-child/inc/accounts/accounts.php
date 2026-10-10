<?php
/**
 * v5.8 · 직원 라운지 계정 — 회원가입 신청 · 승인 · 권한 · 내 정보 (원장 지시 2026-10-04)
 *
 *  직원이 라운지 로그인 화면의 「회원가입 신청」으로 이름 · 생년월일 · 입사일(선택) · 이메일 ·
 *  전화번호 · 아이디 · 비밀번호를 넣는다 → 승인 대기 계정이 생긴다(로그인 불가).
 *  라운지 관리자가 「직원 정보」에서 명단과 연결하고 권한을 골라 승인한다.
 *
 *  권한은 기본 「직원」(라운지 이용) 위에 체크로 더한다.
 *    md_inv_manage    재료실 관리 (출고 · 입고 · 주문 · 품목 · 재료실 설정)
 *    md_supply_manage 라운지 관리자 (직원 정보 · 계정 승인 · 모든 관리 화면) — 원장(워드프레스 관리자)만 줄 수 있다
 *
 *  계정과 직원 명단은 사용자 메타 md_staff_id 로 잇는다 (md_staff 표 구조는 그대로).
 *  공용 계정(moondentalmanager · moondentalhospital)과 워드프레스 관리자는 여기서 건드리지 않는다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/accounts-log.php'; /* v5.9 · 로그인 기록 · 잠금 */

/* ============================================================
 * 기본
 * ============================================================ */

function md_acc_perms() {
	return array(
		'md_inv_manage'    => array( '재료실 관리', '출고 · 입고 · 주문 · 품목 · 재료실 설정' ),
		'md_supply_manage' => array( '라운지 관리자', '직원 정보 · 계정 승인 · 모든 관리 화면' ),
	);
}

function md_acc_shared_logins() {
	return array( defined( 'MD_SUP_MANAGER_LOGIN' ) ? MD_SUP_MANAGER_LOGIN : 'moondentalmanager', defined( 'MD_SUP_STAFF_LOGIN' ) ? MD_SUP_STAFF_LOGIN : 'moondentalhospital' );
}

/** 계정을 관리(승인 · 권한 · 비밀번호)할 수 있는 사람 */
function md_acc_can_manage() {
	return function_exists( 'md_sup_can_manage' ) && md_sup_can_manage();
}

/** 「라운지 관리자」 권한을 주고 뺄 수 있는 사람 — 원장(워드프레스 관리자)만 */
function md_acc_can_grant_admin() {
	return current_user_can( 'md_supply_owner' ) || current_user_can( 'manage_options' ); /* v8.2 · 홈페이지 관리자 */
}

/** 여기서 다룰 수 있는 개인 계정인가 */
function md_acc_is_personal_user( $u ) {
	if ( ! $u || ! $u->exists() ) { return false; }
	if ( in_array( $u->user_login, md_acc_shared_logins(), true ) ) { return false; }
	if ( user_can( $u, 'manage_options' ) || user_can( $u, 'edit_posts' ) ) { return false; }
	return (bool) array_intersect( (array) $u->roles, array( 'md_stock_staff', 'md_stock_manager', 'md_lounge_pending', 'md_lounge_off' ) );
}

function md_acc_status( $u ) {
	$roles = (array) $u->roles;
	if ( in_array( 'md_lounge_pending', $roles, true ) ) { return 'pending'; }
	if ( in_array( 'md_lounge_off', $roles, true ) ) { return 'off'; }
	return 'active';
}

/** 승인 대기 · 사용 중지 역할 — 아무 권한도 없다 */
function md_acc_roles() {
	if ( ! get_role( 'md_lounge_pending' ) ) { add_role( 'md_lounge_pending', '라운지 · 승인 대기', array() ); }
	if ( ! get_role( 'md_lounge_off' ) ) { add_role( 'md_lounge_off', '라운지 · 사용 중지', array() ); }
	if ( function_exists( 'md_sup_add_roles' ) && ! get_role( 'md_stock_staff' ) ) { md_sup_add_roles(); }
}
add_action( 'init', 'md_acc_roles', 6 );

/** 그 계정이 가진 추가 권한 */
function md_acc_user_perms( $u ) {
	$out = array();
	foreach ( array_keys( md_acc_perms() ) as $cap ) { if ( user_can( $u, $cap ) ) { $out[] = $cap; } }
	return $out;
}

function md_acc_perm_label( $u ) {
	if ( 'pending' === md_acc_status( $u ) ) { return '승인 대기'; }
	if ( 'off' === md_acc_status( $u ) ) { return '사용 중지'; }
	$p = md_acc_user_perms( $u );
	if ( user_can( $u, 'md_supply_owner' ) ) { return '홈페이지 관리자'; } /* v8.2 */
	if ( in_array( 'md_supply_manage', $p, true ) ) { return '라운지 관리자'; }
	if ( in_array( 'md_inv_manage', $p, true ) ) { return '직원 + 재료실 관리'; }
	return '직원';
}

/** 권한 정하기 — 기본 역할은 직원, 추가 권한은 사용자 단위로 */
function md_acc_apply_perms( $u, $perms ) {
	$perms = array_intersect( (array) $perms, array_keys( md_acc_perms() ) );
	$had_admin = user_can( $u, 'md_supply_manage' );
	if ( ! md_acc_can_grant_admin() ) {
		/* 라운지 관리자 권한은 원장만 바꾼다 — 그 밖의 사람이 저장하면 원래 상태를 지킨다 */
		$perms = array_diff( $perms, array( 'md_supply_manage' ) );
		if ( $had_admin ) { $perms[] = 'md_supply_manage'; }
	}
	$u->set_role( 'md_stock_staff' );
	foreach ( array_keys( md_acc_perms() ) as $cap ) {
		if ( in_array( $cap, $perms, true ) ) { $u->add_cap( $cap ); } else { $u->remove_cap( $cap ); }
	}
}

function md_acc_staff_row( $id ) {
	global $wpdb;
	if ( ! $id || ! function_exists( 'md_staff_table' ) ) { return null; }
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_staff_table() . ' WHERE id = %d', (int) $id ) );
}

/** 직원 명단 id → 계정 */
function md_acc_staff_user_map() {
	$map = array();
	foreach ( md_acc_users() as $u ) {
		$sid = (int) get_user_meta( $u->ID, 'md_staff_id', true );
		if ( $sid && ! isset( $map[ $sid ] ) ) { $map[ $sid ] = $u; }
	}
	return $map;
}

/** 라운지 개인 계정 전체 (공용 · 관리자 제외) */
function md_acc_users( $status = '' ) {
	static $cache = null;
	if ( null === $cache ) {
		$cache = array();
		foreach ( get_users( array( 'role__in' => array( 'md_stock_staff', 'md_stock_manager', 'md_lounge_pending', 'md_lounge_off' ), 'orderby' => 'registered', 'order' => 'ASC' ) ) as $u ) {
			if ( md_acc_is_personal_user( $u ) ) { $cache[] = $u; }
		}
	}
	if ( '' === $status ) { return $cache; }
	return array_values( array_filter( $cache, function ( $u ) use ( $status ) { return md_acc_status( $u ) === $status; } ) );
}

function md_acc_pending_count() {
	return count( md_acc_users( 'pending' ) );
}

function md_acc_norm_phone( $v ) {
	$d = preg_replace( '/\D/', '', (string) $v );
	if ( strlen( $d ) < 9 || strlen( $d ) > 11 ) { return ''; }
	if ( 11 === strlen( $d ) ) { return substr( $d, 0, 3 ) . '-' . substr( $d, 3, 4 ) . '-' . substr( $d, 7 ); }
	if ( 0 === strpos( $d, '02' ) ) { return 9 === strlen( $d ) ? '02-' . substr( $d, 2, 3 ) . '-' . substr( $d, 5 ) : '02-' . substr( $d, 2, 4 ) . '-' . substr( $d, 6 ); }
	return 10 === strlen( $d ) ? substr( $d, 0, 3 ) . '-' . substr( $d, 3, 3 ) . '-' . substr( $d, 6 ) : $d;
}

function md_acc_norm_date( $v ) {
	$v = trim( (string) $v );
	if ( preg_match( '/^(\d{4})(\d{2})(\d{2})$/', $v, $m ) ) { $v = $m[1] . '-' . $m[2] . '-' . $m[3]; }
	if ( function_exists( 'md_staff_norm_date' ) ) { return md_staff_norm_date( $v ); }
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : null;
}

/** 비밀번호 규칙 — 8자 이상, 글자와 숫자를 함께 */
/** v7.1 · 비밀번호는 숫자 6자리 (원장 지시) */
function md_acc_pass_ok( $p ) {
	if ( ! preg_match( '/^\d{6}$/', (string) $p ) ) { return '비밀번호는 숫자 6자리입니다.'; }
	return '';
}

/* ============================================================
 * v7.1 · 이름으로 로그인 (원장 지시) — 아이디는 자동으로 만들고 직원은 이름 + 숫자 6자리만 쓴다
 *   공용 계정(moondentalhospital · moondentalmanager)은 지금처럼 아이디로.
 * ============================================================ */

function md_acc_name_key( $n ) {
	return preg_replace( '/\s+/u', '', (string) $n );
}

/** 이 이름(띄어쓰기 무시)의 개인 계정들 */
function md_acc_users_by_name( $name, $exclude = 0 ) {
	global $wpdb;
	$key = md_acc_name_key( $name );
	if ( '' === $key ) { return array(); }
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE REPLACE(display_name, ' ', '') = %s", $key ) );
	$out = array();
	foreach ( $ids as $id ) {
		if ( (int) $id === (int) $exclude ) { continue; }
		$u = get_userdata( (int) $id );
		if ( $u && md_acc_is_personal_user( $u ) ) { $out[] = $u; }
	}
	return $out;
}

/** 로그인 칸에 이름을 적었으면 그 계정의 아이디로 바꿔 넣는다 (같은 이름이 둘이면 바꾸지 않는다) */
function md_acc_name_login( &$username, &$password ) {
	$n = trim( (string) $username );
	if ( '' === $n || is_email( $n ) || username_exists( $n ) ) { return; }
	/* v9.42 · 공용 계정은 이름 「문치과병원」으로 — 같은 이름의 다른 계정(예전 moondentalhospital 등)보다 먼저 (원장 지시) */
	if ( defined( 'MD_SUP_STAFF_LOGIN' ) && ( $su = get_user_by( 'login', MD_SUP_STAFF_LOGIN ) ) ) {
		$k = md_acc_name_key( $n );
		global $wpdb;
		$rn = function_exists( 'md_staff_shared_sid' ) && md_staff_shared_sid() ? (string) $wpdb->get_var( $wpdb->prepare( 'SELECT name FROM ' . md_staff_table() . ' WHERE id = %d', md_staff_shared_sid() ) ) : ''; /* 직원 명단에서 공용 줄 이름만 바꾼 경우도 */
		if ( $k === md_acc_name_key( $su->display_name ) || ( defined( 'MD_SUP_STAFF_NAME' ) && $k === md_acc_name_key( MD_SUP_STAFF_NAME ) ) || ( '' !== $rn && $k === md_acc_name_key( $rn ) ) ) { $username = MD_SUP_STAFF_LOGIN; return; }
	}
	$us = md_acc_users_by_name( $n );
	if ( 1 === count( $us ) ) { $username = $us[0]->user_login; return; }
	/* v8.2 · 직원 공용 계정은 이름 「문치과병원」으로 */
	if ( defined( 'MD_SUP_STAFF_NAME' ) && md_acc_name_key( $n ) === md_acc_name_key( MD_SUP_STAFF_NAME ) && defined( 'MD_SUP_STAFF_LOGIN' ) ) { $username = MD_SUP_STAFF_LOGIN; }
}
add_action( 'wp_authenticate', 'md_acc_name_login', 1, 2 );

/** v7.4 · 화면에 보일 계정 이름 — 개인 계정은 이름(자동 아이디 staffNNNNNN 은 보이지 않게), 공용 계정은 아이디 */
function md_acc_who( $u ) {
	if ( ! $u ) { return ''; }
	return ( md_acc_is_personal_user( $u ) && '' !== trim( $u->display_name ) ) ? $u->display_name : $u->user_login;
}

/** 자동 아이디 — 직원은 볼 일이 없다 */
function md_acc_auto_login() {
	do { $l = 'staff' . random_int( 100000, 999999 ); } while ( username_exists( $l ) );
	return $l;
}

function md_acc_name_taken_msg( $name ) {
	return '「' . $name . '」 이름의 계정이 이미 있습니다. 이름으로 로그인하므로 「' . $name . 'B」처럼 구분해 주세요.';
}

/** 임시 비밀번호 — v7.1 · 숫자 6자리 */
function md_acc_temp_pass() {
	$s = '';
	for ( $i = 0; $i < 6; $i++ ) { $s .= (string) random_int( 0, 9 ); }
	return $s;
}

function md_acc_notify_to() {
	$v = (string) get_option( 'md_acc_notify', '' );
	if ( '' === $v && function_exists( 'md_inv_set' ) ) { $v = (string) md_inv_set( 'report_to' ); }
	return '' !== $v ? $v : get_option( 'admin_email' );
}

function md_acc_lounge_url( $args = array() ) {
	return function_exists( 'md_sup_url' ) ? md_sup_url( $args ) : add_query_arg( $args, home_url( '/직원/' ) );
}

function md_acc_log( $what, $detail ) {
	if ( function_exists( 'md_inv_log' ) ) { md_inv_log( $what, $detail ); }
}

/** 한 번만 보여 줄 알림 (임시 비밀번호 포함) — 처리한 사람에게만 */
function md_acc_flash( $type, $msg, $extra = array() ) {
	set_transient( 'md_acc_flash_' . get_current_user_id(), array_merge( array( 't' => $type, 'm' => $msg ), $extra ), 10 * MINUTE_IN_SECONDS );
}
function md_acc_take_flash() {
	$k = 'md_acc_flash_' . get_current_user_id();
	$f = get_transient( $k );
	if ( $f ) { delete_transient( $k ); }
	return $f ? $f : null;
}
function md_acc_render_flash() {
	$f = md_acc_take_flash();
	if ( ! $f ) { return; }
	echo '<div class="mds-notice mds-notice--' . ( 'ok' === $f['t'] ? 'ok' : 'warn' ) . ' mda-flash">' . esc_html( $f['m'] );
	if ( ! empty( $f['pass'] ) ) {
		echo '<div class="mda-temp"><span>로그인 이름</span><code>' . esc_html( $f['login'] ) . '</code><span>임시 비밀번호</span><code class="mda-temp__pw">' . esc_html( $f['pass'] ) . '</code></div>';
		echo '<small>이 화면을 벗어나면 다시 볼 수 없습니다. 본인에게 전해 주세요 — 처음 로그인하면 새 비밀번호로 바꾸게 됩니다.</small>';
	}
	echo '</div>';
}

/* ============================================================
 * 로그인 — 승인 대기 · 사용 중지 계정은 막는다
 * ============================================================ */

function md_acc_authenticate( $user ) {
	if ( $user instanceof WP_User ) {
		$roles = (array) $user->roles;
		if ( in_array( 'md_lounge_pending', $roles, true ) ) { return new WP_Error( 'md_acc_pending', '가입 신청을 확인하고 있습니다. 승인되면 이메일로 알려 드립니다.' ); }
		if ( in_array( 'md_lounge_off', $roles, true ) ) { return new WP_Error( 'md_acc_off', '사용이 중지된 계정입니다. 경영지원실에 문의해 주세요.' ); }
	}
	return $user;
}
add_filter( 'authenticate', 'md_acc_authenticate', 99 );

/** 라운지 폼에서 로그인에 실패하면 wp-login.php 대신 라운지로 돌아와 이유를 보여 준다 */
function md_acc_login_failed( $username, $error = null ) {
	if ( empty( $_POST['md_lounge'] ) ) { return; }
	$code = ( $error instanceof WP_Error ) ? $error->get_error_code() : 'bad';
	$code = in_array( $code, array( 'md_acc_pending', 'md_acc_off', 'md_acc_locked' ), true ) ? $code : 'bad';
	wp_safe_redirect( md_acc_lounge_url( array( 'md_le' => $code ) ) );
	exit;
}
add_action( 'wp_login_failed', 'md_acc_login_failed', 10, 2 );

function md_acc_login_top( $html ) {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() || empty( $_GET['md_le'] ) ) { return $html; }
	$m = array(
		'md_acc_pending' => '가입 신청을 확인하고 있습니다. 승인되면 이메일로 알려 드립니다.',
		'md_acc_off'     => '사용이 중지된 계정입니다. 경영지원실에 문의해 주세요.',
		'md_acc_locked'  => '비밀번호를 여러 번 틀려 ' . MD_ACC_LOCK_MIN . '분 동안 잠겼습니다. 잠시 뒤에 다시 하거나, 급하면 경영지원실에 잠금 풀기를 부탁해 주세요.',
		'bad'            => '이름이나 비밀번호(숫자 6자리)가 맞지 않습니다.',
	);
	$k = sanitize_key( wp_unslash( $_GET['md_le'] ) );
	return $html . '<p class="mda-login-err" role="alert">' . esc_html( isset( $m[ $k ] ) ? $m[ $k ] : $m['bad'] ) . '</p>';
}
add_filter( 'login_form_top', 'md_acc_login_top' );

/** 로그인 폼 아래 — 회원가입 신청 · 비밀번호 찾기 */
function md_acc_login_links( $html ) {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return $html; }
	return $html . '<div class="mda-login-links">'
		. '<a class="mda-join-btn" href="' . esc_url( md_acc_lounge_url( array( 'md_join' => 1 ) ) ) . '">처음이신가요? <b>회원가입 신청</b></a>'
		. '<a class="mda-lost" href="' . esc_url( wp_lostpassword_url( md_acc_lounge_url() ) ) . '">비밀번호를 잊으셨나요?</a>'
		. '</div>'
		/* v9.46 · 매번 보는 안내는 부드럽게 (원장 지시) */
		. '<p class="mda-login-note">직원 라운지에는 환자분들의 소중한 개인정보와 병원의 내부 자료가 담겨 있습니다.<br>열람하신 내용은 업무에만 활용해 주시고, 외부로 공유되지 않도록 각별히 유의해 주시기 바랍니다.<br>감사합니다.</p>'; /* v9.46.1 · 더 정중하게 (원장 지시) */
}

/* ============================================================
 * v9.46 · 비밀유지 서약 (회원가입 필수 동의, 원장 지시)
 *  문구를 고치면 MD_ACC_SECRET_VER 를 올린다 — 누가 어느 판에 동의했는지 md_acc_secret 사용자 메타에 남는다
 * ============================================================ */
define( 'MD_ACC_SECRET_VER', '2026-10-10c' ); /* v9.46.2 · 문구 고침 */
function md_acc_secret_text() {
	/* v9.46.2 · 원장이 고친 문구 그대로 */
	return '<div class="mda-secret__body">'
		. '<p>직원 라운지에는 환자분들의 개인정보와 병원 내부 자료가 함께 담겨 있어 법적 책임이 수반됩니다.<br>아래 내용을 함께 지켜 주세요.</p>'
		. '<ol>'
		. '<li>업무 중 알게 된 환자분의 정보는 업무에만 사용하고, 다른 사람에게 이야기하지 않습니다.</li>'
		. '<li>라운지의 자료는 병원 밖으로 공유하지 않습니다.</li>'
		. '<li>캡처, 사진, 파일, 메신저, SNS 등으로 외부에 옮기지 않습니다.</li>'
		. '<li>계정을 병원 외부에 공유하지 않습니다.</li>'
		. '</ol>'
		. '</div>';
}
add_filter( 'login_form_bottom', 'md_acc_login_links', 20 );

/** 비밀번호 찾기 메일로 새로 정하면 처음 바꾸기 표시를 지운다 */
add_action( 'after_password_reset', function ( $user ) { delete_user_meta( $user->ID, 'md_acc_must_change' ); } );

/* ============================================================
 * 회원가입 신청 (로그아웃 상태)
 * ============================================================ */

function md_acc_join_fields() {
	return array( 'jname', 'birthday', 'hired', 'email', 'phone', 'dept' ); /* 'name' 은 워드프레스 공개 쿼리 변수라 쓰면 404 가 된다 · v6.10 아이디 칸 없앰 */
}

function md_acc_join_submit() {
	$f = array();
	foreach ( md_acc_join_fields() as $k ) { $f[ $k ] = isset( $_POST[ $k ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) ) : ''; }
	$pass  = isset( $_POST['pass'] ) ? (string) wp_unslash( $_POST['pass'] ) : '';
	$pass2 = isset( $_POST['pass2'] ) ? (string) wp_unslash( $_POST['pass2'] ) : '';
	$err   = array();

	/* 자동 가입 막기 — 숨은 칸 · 너무 빠른 제출 · 같은 곳에서 여러 번 */
	if ( ! empty( $_POST['website'] ) ) { return array( 'errors' => array( '_' => '신청을 받지 못했습니다. 다시 시도해 주세요.' ), 'f' => $f ); }
	$ts  = isset( $_POST['ts'] ) ? (string) wp_unslash( $_POST['ts'] ) : '';
	$p   = explode( '.', $ts );
	$age = ( 2 === count( $p ) && hash_equals( substr( wp_hash( 'md_acc_join' . $p[0] ), 0, 16 ), $p[1] ) ) ? time() - (int) $p[0] : -1;
	if ( $age < 3 || $age > 3 * HOUR_IN_SECONDS ) { return array( 'errors' => array( '_' => '신청서가 오래되었거나 너무 빨리 보내졌습니다. 한 번 더 「신청하기」를 눌러 주세요.' ), 'f' => $f ); }
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$rk   = 'md_acc_rl_' . md5( $ip );
	$hits = (int) get_transient( $rk );
	if ( $hits >= 5 ) { return array( 'errors' => array( '_' => '신청이 너무 많습니다. 한 시간 뒤에 다시 해 주세요.' ), 'f' => $f ); }

	$name = mb_substr( $f['jname'], 0, 20 );
	if ( mb_strlen( $name ) < 2 ) { $err['name'] = '이름을 적어 주세요.'; }
	elseif ( md_acc_users_by_name( $name ) ) { $err['name'] = md_acc_name_taken_msg( $name ); }
	/* 생년월일 · 입사일은 덴트웹 직원정보에서 연동되므로 가입 때 받지 않는다 (inc/staff · md_staff_dw_sync) */
	$bd = ''; $hired = '';
	$email = sanitize_email( $f['email'] );
	if ( ! is_email( $email ) ) { $err['email'] = '이메일 주소를 확인해 주세요.'; }
	elseif ( email_exists( $email ) ) { $err['email'] = '이미 가입했거나 신청한 이메일입니다. 승인을 기다리는 중이면 조금만 기다려 주세요.'; }
	$phone = md_acc_norm_phone( $f['phone'] ); /* 덴트웹 직원정보엔 전화번호가 없어서 가입 때 받는다 */
	if ( '' === $phone ) { $err['phone'] = '휴대전화 번호를 확인해 주세요 (예: 010-1234-5678).'; }
	$login = md_acc_auto_login();
	$pe = md_acc_pass_ok( $pass );
	if ( $pe ) { $err['pass'] = $pe; }
	elseif ( $pass !== $pass2 ) { $err['pass2'] = '비밀번호 확인이 다릅니다.'; }
	if ( empty( $_POST['agree'] ) ) { $err['agree'] = '개인정보 수집 · 이용에 동의해 주세요.'; }
	if ( empty( $_POST['secret'] ) ) { $err['secret'] = '비밀유지 서약에 동의해 주세요.'; } /* v9.46 · 원장 지시 */
	$dept = '';
	if ( '' !== $f['dept'] && function_exists( 'md_staff_depts' ) && in_array( $f['dept'], md_staff_depts(), true ) ) { $dept = $f['dept']; }

	if ( $err ) { return array( 'errors' => $err, 'f' => $f ); }

	md_acc_roles();
	$uid = wp_insert_user( array(
		'user_login'           => $login,
		'user_pass'            => $pass,
		'user_email'           => $email,
		'display_name'         => $name,
		'nickname'             => $name,
		'first_name'           => $name,
		'role'                 => 'md_lounge_pending',
		'show_admin_bar_front' => 'false',
	) );
	if ( is_wp_error( $uid ) ) { return array( 'errors' => array( '_' => '신청을 저장하지 못했습니다: ' . $uid->get_error_message() ), 'f' => $f ); }
	update_user_meta( $uid, 'md_acc_app', array( 'birthday' => $bd, 'hired' => $hired, 'phone' => $phone, 'dept' => $dept, 'at' => current_time( 'mysql' ) ) );
	/* v9.46 · 비밀유지 서약 동의 기록 — 시각 · 문구 판 · 접속 IP (나중에 근거) */
	update_user_meta( $uid, 'md_acc_secret', array( 'at' => current_time( 'mysql' ), 'ver' => MD_ACC_SECRET_VER, 'ip' => sanitize_text_field( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ) ) );
	set_transient( $rk, $hits + 1, HOUR_IN_SECONDS );
	md_acc_log( '회원가입 신청', $name . ' (' . $login . ')' );

	/* 관리자에게 알림 — 생년월일 · 전화번호 같은 개인정보는 메일에 넣지 않는다 */
	$to = md_acc_notify_to();
	if ( $to ) {
		wp_mail( $to, '[문치과병원 직원 라운지] 회원가입 신청 — ' . $name,
			$name . ' 님이 직원 라운지 회원가입을 신청했습니다' . ( $dept ? ' (' . $dept . ')' : '' ) . ".\n\n"
			. "승인하러 가기: " . md_acc_lounge_url( array( 'app' => 'staff' ) ) . "#mda-pending\n\n"
			. '직원 라운지 › 직원 정보 맨 위 「가입 신청」에서 명단과 연결하고 권한을 골라 승인해 주세요.' );
	}
	return array( 'ok' => true );
}

function md_acc_render_join() {
	if ( isset( $_GET['md_join'] ) && 'done' === $_GET['md_join'] ) { ?>
		<div class="mds-gate"><div class="mds-gate__box mda-join mda-join--done">
			<span class="mds-gate__eyebrow">회원가입 신청</span>
			<h1>신청을 받았습니다 🙌</h1>
			<p>관리자가 확인하고 승인하면 적어 주신 이메일로 알려 드립니다. 승인 전에는 로그인할 수 없어요.</p>
			<a class="mds-btn mds-btn--fill mda-wide" href="<?php echo esc_url( md_acc_lounge_url() ); ?>">로그인 화면으로</a>
		</div></div>
		<?php return;
	}
	$res = isset( $GLOBALS['md_acc_join_result'] ) ? $GLOBALS['md_acc_join_result'] : array();
	$e   = isset( $res['errors'] ) ? $res['errors'] : array();
	$f   = isset( $res['f'] ) ? $res['f'] : array();
	$v   = function ( $k ) use ( $f ) { return isset( $f[ $k ] ) ? $f[ $k ] : ''; };
	$er  = function ( $k ) use ( $e ) { return isset( $e[ $k ] ) ? '<em class="mda-err" role="alert">' . esc_html( $e[ $k ] ) . '</em>' : ''; };
	$t   = time();
	?>
	<div class="mds-gate"><div class="mds-gate__box mda-join">
		<span class="mds-gate__eyebrow">한아의료재단 문치과병원 · 직원 라운지</span>
		<h1>회원가입 신청</h1>
		<p>본인 계정을 만들면 신청 · 요청에 이름이 자동으로 남습니다. 관리자가 승인하면 로그인할 수 있어요.</p>
		<?php if ( isset( $e['_'] ) ) : ?><div class="mds-notice mds-notice--warn"><?php echo esc_html( $e['_'] ); ?></div><?php endif; ?>
		<?php if ( $e && ! isset( $e['_'] ) ) : ?><div class="mds-notice mds-notice--warn">빨간 글씨로 표시한 칸을 확인해 주세요.</div><?php endif; ?>
		<form method="post" class="mda-form" novalidate>
			<input type="hidden" name="md_acc" value="join">
			<?php wp_nonce_field( 'md_acc_join', 'md_acc_nonce' ); ?>
			<input type="hidden" name="ts" value="<?php echo esc_attr( $t . '.' . substr( wp_hash( 'md_acc_join' . $t ), 0, 16 ) ); ?>">
			<div class="mda-hp" aria-hidden="true"><label>홈페이지 <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

			<fieldset class="mda-set"><legend>내 정보</legend>
				<label class="mda-f"><span>이름 <b>*</b></span><input type="text" name="jname" required maxlength="20" autocomplete="name" value="<?php echo esc_attr( $v( 'jname' ) ); ?>" placeholder="홍길동"><?php echo $er( 'name' ); // phpcs:ignore ?></label>
				<?php /* v9.32 · 가입 때 부서는 묻지 않는다 (원장 지시) — 승인할 때 직원 명단과 맞추면서 관리자가 정한다 */ ?>
				<label class="mda-f"><span>이메일 <b>*</b></span><input type="email" name="email" required maxlength="120" autocomplete="email" inputmode="email" value="<?php echo esc_attr( $v( 'email' ) ); ?>" placeholder="name@example.com"><?php echo $er( 'email' ); // phpcs:ignore ?></label>
				<label class="mda-f"><span>휴대전화 <b>*</b></span><input type="tel" name="phone" required maxlength="20" autocomplete="tel" inputmode="numeric" value="<?php echo esc_attr( $v( 'phone' ) ); ?>" placeholder="010-1234-5678" data-mda-phone><?php echo $er( 'phone' ); // phpcs:ignore ?></label>
			</fieldset>

			<fieldset class="mda-set"><legend>로그인 정보</legend>
				<p class="mda-note">로그인할 때는 위의 <b>이름</b>과 아래 <b>숫자 6자리</b>를 씁니다.</p>
				<label class="mda-f"><span>비밀번호 <b>*</b> <small>숫자 6자리</small></span><input type="password" name="pass" required minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="new-password"><?php echo $er( 'pass' ); // phpcs:ignore ?></label>
				<label class="mda-f"><span>비밀번호 확인 <b>*</b></span><input type="password" name="pass2" required minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="new-password"><?php echo $er( 'pass2' ); // phpcs:ignore ?></label>
			</fieldset>

			<details class="mda-privacy"><summary>개인정보 수집 · 이용 안내</summary>
				<p><b>수집 항목</b> 이름 · 이메일 · 휴대전화 — 생일 · 입사일은 병원 전산(덴트웹) 직원정보에서 가져옵니다<br><b>목적</b> 직원 라운지 계정 관리 · 직원 명단 · 라운지 달력의 생일 · 입사 기념일 표시 · 업무 연락<br><b>보관</b> 재직 기간 동안 (퇴사하면 지웁니다)<br>동의하지 않으면 계정을 만들 수 없고, 병원 공용 계정으로 이용할 수 있습니다.</p>
			</details>
			<label class="mda-agree mds-check"><input type="checkbox" name="agree" value="1" required <?php checked( ! empty( $_POST['agree'] ) ); ?>> 개인정보 수집 · 이용에 동의합니다 <b>*</b></label><?php echo $er( 'agree' ); // phpcs:ignore ?>

			<?php /* v9.46 · 비밀유지 서약 (원장 지시) */ ?>
			<details class="mda-privacy mda-secret" open><summary>비밀유지 서약</summary><?php echo md_acc_secret_text(); // phpcs:ignore -- 고정 문구 ?></details>
			<label class="mda-agree mds-check"><input type="checkbox" name="secret" value="1" required <?php checked( ! empty( $_POST['secret'] ) ); ?>> 위 비밀유지 서약을 읽었고, 지키겠습니다 <b>*</b></label><?php echo $er( 'secret' ); // phpcs:ignore ?>

			<button type="submit" class="mds-btn mds-btn--fill mda-wide">신청하기</button>
			<a class="mda-back" href="<?php echo esc_url( md_acc_lounge_url() ); ?>">← 로그인 화면으로</a>
		</form>
	</div></div>
	<?php
}

/* ============================================================
 * 처리 (POST)
 * ============================================================ */

function md_acc_handle() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }

	/* 임시 비밀번호로 들어온 사람은 먼저 새 비밀번호를 정한다 */
	if ( is_user_logged_in() && 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && get_user_meta( get_current_user_id(), 'md_acc_must_change', true ) ) {
		$app = isset( $_GET['app'] ) ? sanitize_key( wp_unslash( $_GET['app'] ) ) : '';
		if ( 'me' !== $app ) { wp_safe_redirect( md_acc_lounge_url( array( 'app' => 'me', 'first' => 1 ) ) ); exit; }
	}

	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['md_acc'] ) ) { return; }
	$act = sanitize_key( wp_unslash( $_POST['md_acc'] ) );
	if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }

	if ( 'join' === $act ) {
		if ( is_user_logged_in() ) { wp_safe_redirect( md_acc_lounge_url() ); exit; }
		if ( ! isset( $_POST['md_acc_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_acc_nonce'] ), 'md_acc_join' ) ) {
			$GLOBALS['md_acc_join_result'] = array( 'errors' => array( '_' => '신청서가 오래되었습니다. 다시 적어 주세요.' ) );
			return;
		}
		$r = md_acc_join_submit();
		if ( ! empty( $r['ok'] ) ) { wp_safe_redirect( md_acc_lounge_url( array( 'md_join' => 'done' ) ) ); exit; }
		$GLOBALS['md_acc_join_result'] = $r; /* 같은 화면에 오류와 적은 값을 그대로 보여 준다 */
		return;
	}

	if ( ! is_user_logged_in() ) { return; }
	if ( ! isset( $_POST['md_acc_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_acc_nonce'] ), 'md_acc_' . $act ) ) {
		wp_die( '요청이 만료되었습니다. 뒤로 가서 다시 시도해 주세요.' );
	}

	if ( 'me' === $act ) { md_acc_me_save(); return; }

	if ( ! md_acc_can_manage() ) { wp_die( '권한이 없습니다.' ); }
	$back = md_acc_lounge_url( array( 'app' => 'staff' ) );
	$uid  = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	$u    = $uid ? get_userdata( $uid ) : null;
	$sid  = isset( $_POST['sid'] ) ? (int) $_POST['sid'] : 0;
	$self = $uid && $uid === get_current_user_id();
	$need_user = array( 'approve', 'reject', 'perms', 'reset', 'off', 'on', 'delete', 'link', 'unlink' );
	if ( in_array( $act, $need_user, true ) && ( ! $u || ! md_acc_is_personal_user( $u ) ) ) {
		md_acc_flash( 'err', '그 계정은 여기서 바꿀 수 없습니다.' ); wp_safe_redirect( $back ); exit;
	}
	if ( $self && in_array( $act, array( 'perms', 'off', 'delete', 'reject' ), true ) ) {
		md_acc_flash( 'err', '내 계정의 권한 · 사용 중지 · 삭제는 다른 관리자가 해야 합니다.' ); wp_safe_redirect( $back ); exit;
	}
	/* v8.2 · 홈페이지 관리자 계정은 홈페이지 관리자만 손댄다 */
	if ( $u && ! $self && user_can( $u, 'md_supply_owner' ) && ! current_user_can( 'manage_options' ) && in_array( $act, array( 'perms', 'reset', 'off', 'delete' ), true ) ) {
		md_acc_flash( 'err', '홈페이지 관리자 계정은 바꿀 수 없습니다.' ); wp_safe_redirect( $back ); exit;
	}
	/* 라운지 관리자 계정은 홈페이지 관리자만 손댄다 */
	if ( $u && user_can( $u, 'md_supply_manage' ) && ! md_acc_can_grant_admin() && in_array( $act, array( 'perms', 'reset', 'off', 'delete' ), true ) ) {
		md_acc_flash( 'err', '라운지 관리자 계정은 원장님만 바꿀 수 있습니다.' ); wp_safe_redirect( $back ); exit;
	}
	$perms = isset( $_POST['perms'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['perms'] ) ) : array();

	switch ( $act ) {
		case 'approve':
			if ( 'pending' !== md_acc_status( $u ) ) { md_acc_flash( 'err', '이미 처리된 신청입니다.' ); break; }
			$app  = (array) get_user_meta( $uid, 'md_acc_app', true );
			$dept = isset( $_POST['dept'] ) ? sanitize_text_field( wp_unslash( $_POST['dept'] ) ) : '';
			$link = isset( $_POST['link'] ) ? sanitize_text_field( wp_unslash( $_POST['link'] ) ) : 'new';
			$map  = md_acc_staff_user_map();
			if ( 'new' === $link ) {
				$sid = function_exists( 'md_staff_save' ) ? md_staff_save( array( 'name' => $u->display_name, 'dept' => $dept, 'birthday' => $app['birthday'] ?? '', 'hired' => $app['hired'] ?? '', 'phone' => $app['phone'] ?? '', 'email' => $u->user_email ) ) : 0;
				if ( is_wp_error( $sid ) ) { md_acc_flash( 'err', $sid->get_error_message() ); break; }
			} else {
				$sid = (int) $link;
				$row = md_acc_staff_row( $sid );
				if ( ! $row ) { md_acc_flash( 'err', '연결할 직원을 다시 골라 주세요.' ); break; }
				if ( isset( $map[ $sid ] ) ) { md_acc_flash( 'err', $row->name . ' 님은 이미 다른 계정(' . md_acc_who( $map[ $sid ] ) . ')과 연결되어 있습니다.' ); break; }
				md_acc_staff_fill( $sid, array( 'birthday' => $app['birthday'] ?? '', 'hired' => $app['hired'] ?? '', 'phone' => $app['phone'] ?? '', 'email' => $u->user_email, 'dept' => $dept ) );
			}
			if ( $sid ) { update_user_meta( $uid, 'md_staff_id', (int) $sid ); }
			md_acc_apply_perms( $u, $perms );
			update_user_meta( $uid, 'md_acc_approved', current_time( 'mysql' ) . ' · ' . md_acc_me_name() );
			if ( function_exists( 'md_staff_sync_site' ) ) { md_staff_sync_site(); }
			md_acc_log( '가입 승인', $u->display_name . ' · ' . md_acc_perm_label( get_userdata( $uid ) ) );
			wp_mail( $u->user_email, '[문치과병원 직원 라운지] 가입이 승인되었습니다',
				$u->display_name . " 님, 직원 라운지 가입이 승인되었습니다.\n\n로그인: " . md_acc_lounge_url() . "\n이름 「" . $u->display_name . "」과 가입할 때 정한 숫자 6자리로 로그인합니다.\n\n비밀번호를 잊으면 경영지원실에 임시 비밀번호를 부탁해 주세요." );
			md_acc_flash( 'ok', $u->display_name . ' 님을 승인했습니다 (' . md_acc_perm_label( get_userdata( $uid ) ) . '). 승인 메일을 보냈습니다.' );
			$back .= '#s' . (int) $sid;
			break;

		case 'reject':
			if ( 'pending' !== md_acc_status( $u ) ) { md_acc_flash( 'err', '이미 처리된 신청입니다.' ); break; }
			$why = isset( $_POST['why'] ) ? sanitize_text_field( wp_unslash( $_POST['why'] ) ) : '';
			$name = $u->display_name; $mail = $u->user_email;
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $uid );
			md_acc_log( '가입 거절', $name . ( $why ? ' · ' . $why : '' ) );
			if ( $mail ) { wp_mail( $mail, '[문치과병원 직원 라운지] 가입 신청 결과', $name . " 님의 직원 라운지 가입 신청은 승인되지 않았습니다." . ( $why ? "\n사유: " . $why : '' ) . "\n\n궁금한 점은 경영지원실에 문의해 주세요." ); }
			md_acc_flash( 'ok', $name . ' 님의 신청을 거절했습니다. 신청 정보는 지웠습니다.' );
			$back .= '#mda-pending';
			break;

		case 'perms':
			md_acc_apply_perms( $u, $perms );
			md_acc_log( '권한 변경', $u->display_name . ' → ' . md_acc_perm_label( get_userdata( $uid ) ) );
			md_acc_flash( 'ok', $u->display_name . ' 님 권한: ' . md_acc_perm_label( get_userdata( $uid ) ) );
			$back .= md_acc_anchor( $uid );
			break;

		case 'reset':
			$tp = md_acc_temp_pass();
			wp_set_password( $tp, $uid );
			update_user_meta( $uid, 'md_acc_must_change', 1 );
			md_acc_log( '비밀번호 초기화', $u->display_name );
			md_acc_flash( 'ok', $u->display_name . ' 님 비밀번호를 임시 비밀번호로 바꿨습니다.', array( 'pass' => $tp, 'login' => $u->display_name ) );
			$back .= md_acc_anchor( $uid );
			break;

		case 'off':
			md_acc_turn_off( $u );
			md_acc_log( '계정 사용 중지', $u->display_name );
			md_acc_flash( 'ok', $u->display_name . ' 님 계정을 사용 중지했습니다. 로그인돼 있던 기기에서도 바로 나가집니다.' );
			$back .= md_acc_anchor( $uid );
			break;

		case 'on':
			md_acc_turn_on( $u );
			md_acc_log( '계정 다시 사용', $u->display_name );
			md_acc_flash( 'ok', $u->display_name . ' 님 계정을 다시 쓸 수 있게 했습니다.' );
			$back .= md_acc_anchor( $uid );
			break;

		case 'delete':
			$name = $u->display_name;
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $uid );
			md_acc_log( '계정 삭제', $name );
			md_acc_flash( 'ok', $name . ' 님 계정을 지웠습니다. 직원 명단과 지난 기록의 이름은 그대로 남습니다.' );
			break;

		case 'link':
			$row = md_acc_staff_row( $sid );
			$map = md_acc_staff_user_map();
			if ( ! $row ) { md_acc_flash( 'err', '연결할 직원을 골라 주세요.' ); break; }
			if ( isset( $map[ $sid ] ) && (int) $map[ $sid ]->ID !== $uid ) { md_acc_flash( 'err', $row->name . ' 님은 이미 다른 계정과 연결되어 있습니다.' ); break; }
			update_user_meta( $uid, 'md_staff_id', $sid );
			md_acc_flash( 'ok', md_acc_who( $u ) . ' 계정을 ' . $row->name . ' 님과 연결했습니다.' );
			$back .= '#s' . $sid;
			break;

		case 'unlink':
			delete_user_meta( $uid, 'md_staff_id' );
			md_acc_flash( 'ok', md_acc_who( $u ) . ' 계정과 명단의 연결을 끊었습니다.' );
			$back .= '#mda-loose';
			break;

		case 'create':
			$row = md_acc_staff_row( $sid );
			$map = md_acc_staff_user_map();
			$login = md_acc_auto_login(); /* v7.1 · 아이디는 자동, 로그인은 이름으로 */
			if ( ! $row ) { md_acc_flash( 'err', '직원을 다시 골라 주세요.' ); break; }
			if ( isset( $map[ $sid ] ) ) { md_acc_flash( 'err', $row->name . ' 님은 이미 계정이 있습니다.' ); break; }
			if ( md_acc_users_by_name( $row->name ) ) { md_acc_flash( 'err', md_acc_name_taken_msg( $row->name ) . ' (직원 정보에서 이름을 고친 뒤 만들어 주세요)' ); $back .= '#s' . $sid; break; }
			$email = ( $row->email && is_email( $row->email ) && ! email_exists( $row->email ) ) ? $row->email : '';
			$tp  = md_acc_temp_pass();
			md_acc_roles();
			$nid = wp_insert_user( array( 'user_login' => $login, 'user_pass' => $tp, 'user_email' => $email, 'display_name' => $row->name, 'nickname' => $row->name, 'first_name' => $row->name, 'role' => 'md_stock_staff', 'show_admin_bar_front' => 'false' ) );
			if ( is_wp_error( $nid ) ) { md_acc_flash( 'err', $nid->get_error_message() ); break; }
			$nu = get_userdata( $nid );
			md_acc_apply_perms( $nu, $perms );
			update_user_meta( $nid, 'md_staff_id', $sid );
			update_user_meta( $nid, 'md_acc_must_change', 1 );
			md_acc_log( '계정 만들기', $row->name . ' (' . $login . ') · ' . md_acc_perm_label( get_userdata( $nid ) ) );
			md_acc_flash( 'ok', $row->name . ' 님 계정을 만들었습니다.', array( 'pass' => $tp, 'login' => $row->name ) );
			$back .= '#s' . $sid;
			break;

		case 'unlock':
			$lk = isset( $_POST['lk_login'] ) ? sanitize_text_field( wp_unslash( $_POST['lk_login'] ) ) : '';
			$li = isset( $_POST['lk_ip'] ) ? sanitize_text_field( wp_unslash( $_POST['lk_ip'] ) ) : '';
			md_acc_unlock( $lk, $li );
			md_acc_flash( 'ok', $lk . ' 잠금을 풀었습니다.' );
			$back .= '#mda-log';
			break;

		case 'retire':
		case 'rehire':
		case 'purge':
			$row = md_acc_staff_row( $sid );
			if ( ! $row ) { md_acc_flash( 'err', '직원을 다시 골라 주세요.' ); break; }
			$map = md_acc_staff_user_map();
			$acc = isset( $map[ $sid ] ) ? $map[ $sid ] : null;
			if ( $acc && (int) $acc->ID === get_current_user_id() ) { md_acc_flash( 'err', '내 퇴사 · 삭제는 다른 관리자가 해야 합니다.' ); break; }
			if ( $acc && user_can( $acc, 'md_supply_manage' ) && ! md_acc_can_grant_admin() ) { md_acc_flash( 'err', '라운지 관리자 계정이 있는 직원은 원장님만 퇴사 · 삭제할 수 있습니다.' ); break; }
			global $wpdb;
			$left = (array) get_option( 'md_staff_left', array() );
			if ( 'retire' === $act ) {
				$d = md_acc_norm_date( isset( $_POST['left_on'] ) ? wp_unslash( $_POST['left_on'] ) : '' );
				$left[ $sid ] = $d ? $d : current_time( 'Y-m-d' );
				update_option( 'md_staff_left', $left, false );
				$wpdb->update( md_staff_table(), array( 'active' => 0, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $sid ) );
				if ( $acc && 'active' === md_acc_status( $acc ) ) { md_acc_turn_off( $acc ); }
				if ( function_exists( 'md_staff_sync_site' ) ) { md_staff_sync_site(); }
				md_acc_log( '퇴사 처리', $row->name . ' · ' . $left[ $sid ] . ( $acc ? ' · 계정 중지' : '' ) );
				md_acc_flash( 'ok', $row->name . ' 님을 퇴사 처리했습니다 — 홈페이지 명단 · 달력 · 만족도 조사 담당자 목록에서 빠지고' . ( $acc ? ', 계정은 사용 중지(로그인돼 있던 기기도 나가짐)' : '' ) . '했습니다. 아래 「퇴사한 직원」에서 복직 · 완전 삭제할 수 있습니다.' );
				$back .= '#mda-retired';
			} elseif ( 'rehire' === $act ) {
				unset( $left[ $sid ] );
				update_option( 'md_staff_left', $left, false );
				$wpdb->update( md_staff_table(), array( 'active' => 1, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $sid ) );
				if ( $acc && 'off' === md_acc_status( $acc ) ) { md_acc_turn_on( $acc ); }
				if ( function_exists( 'md_staff_sync_site' ) ) { md_staff_sync_site(); }
				md_acc_log( '복직', $row->name );
				md_acc_flash( 'ok', $row->name . ' 님을 다시 명단에 넣었습니다' . ( $acc ? ' (계정도 예전 권한으로 다시 사용)' : '' ) . '.' );
				$back .= '#s' . $sid;
			} else {
				if ( (int) $row->active ) { md_acc_flash( 'err', '퇴사 처리한 직원만 완전히 지울 수 있습니다.' ); break; }
				if ( $acc ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $acc->ID ); }
				if ( function_exists( 'md_staff_delete' ) ) { md_staff_delete( $sid ); }
				unset( $left[ $sid ] );
				update_option( 'md_staff_left', $left, false );
				if ( function_exists( 'md_staff_sync_site' ) ) { md_staff_sync_site(); }
				md_acc_log( '퇴사자 개인정보 삭제', $row->name );
				md_acc_flash( 'ok', $row->name . ' 님의 명단 · 연락처 · 사진' . ( $acc ? ' · 계정' : '' ) . '을 모두 지웠습니다. (재료실 · 요청 기록의 이름은 남습니다)' );
				$back .= '#mda-retired';
			}
			break;

		case 'notify':
			$to = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';
			$ok = array();
			foreach ( preg_split( '/[\s,;]+/', $to ) as $m ) { if ( is_email( $m ) ) { $ok[] = sanitize_email( $m ); } }
			update_option( 'md_acc_notify', implode( ', ', $ok ), false );
			md_acc_flash( 'ok', $ok ? '가입 신청 알림을 ' . implode( ', ', $ok ) . ' 로 보냅니다.' : '알림 주소를 비웠습니다 — 재료실 보고서 주소로 보냅니다.' );
			$back .= '#mda-pending';
			break;
	}
	wp_safe_redirect( $back );
	exit;
}
add_action( 'template_redirect', 'md_acc_handle', 0 );

function md_acc_anchor( $uid ) {
	$sid = (int) get_user_meta( $uid, 'md_staff_id', true );
	return $sid && md_acc_staff_row( $sid ) ? '#s' . $sid : '#mda-loose';
}

/** 개인 계정이면 그 사람 이름, 공용 · 관리자 계정이면 '' — 다른 라운지 기능의 「작성자」 자동 입력용 */
function md_acc_my_name() {
	$u = wp_get_current_user();
	if ( ! md_acc_is_personal_user( $u ) || 'active' !== md_acc_status( $u ) ) { return ''; }
	return '' !== trim( $u->display_name ) ? $u->display_name : $u->user_login;
}

function md_acc_my_email() {
	return '' !== md_acc_my_name() ? (string) wp_get_current_user()->user_email : '';
}

/** 계정 끄기 / 켜기 — 권한은 따로 보관했다가 그대로 돌려준다 */
function md_acc_turn_off( $u ) {
	update_user_meta( $u->ID, 'md_acc_perms_off', md_acc_user_perms( $u ) );
	foreach ( array_keys( md_acc_perms() ) as $cap ) { $u->remove_cap( $cap ); }
	$u->set_role( 'md_lounge_off' );
	WP_Session_Tokens::get_instance( $u->ID )->destroy_all();
}
function md_acc_turn_on( $u ) {
	md_acc_apply_perms( $u, (array) get_user_meta( $u->ID, 'md_acc_perms_off', true ) );
	delete_user_meta( $u->ID, 'md_acc_perms_off' );
}

function md_acc_left_on( $sid ) {
	$l = (array) get_option( 'md_staff_left', array() );
	return isset( $l[ (int) $sid ] ) ? (string) $l[ (int) $sid ] : '';
}

function md_acc_me_name() {
	$u = wp_get_current_user();
	return '' !== trim( $u->display_name ) ? $u->display_name : $u->user_login;
}

/** 명단 칸 채우기 — 신청서 값이 있으면 그 값으로, 없으면 원래 값 그대로 */
function md_acc_staff_fill( $sid, $vals ) {
	global $wpdb;
	$set = array();
	foreach ( array( 'birthday', 'hired', 'phone', 'email', 'dept' ) as $k ) {
		$v = isset( $vals[ $k ] ) ? trim( (string) $vals[ $k ] ) : '';
		if ( '' === $v ) { continue; }
		$set[ $k ] = $v;
	}
	if ( ! $set || ! function_exists( 'md_staff_table' ) ) { return; }
	$set['updated_at'] = current_time( 'mysql' );
	$wpdb->update( md_staff_table(), $set, array( 'id' => (int) $sid ) );
}

/* ============================================================
 * 내 정보 (app=me)
 * ============================================================ */

function md_acc_me_save() {
	$u    = wp_get_current_user();
	$back = md_acc_lounge_url( array( 'app' => 'me' ) );
	if ( ! md_acc_is_personal_user( $u ) ) { md_acc_flash( 'err', '공용 계정은 여기서 바꿀 수 없습니다.' ); wp_safe_redirect( $back ); exit; }
	$what = isset( $_POST['what'] ) ? sanitize_key( wp_unslash( $_POST['what'] ) ) : '';
	if ( 'pass' === $what ) {
		$cur  = isset( $_POST['cur'] ) ? (string) wp_unslash( $_POST['cur'] ) : '';
		$new  = isset( $_POST['pass'] ) ? (string) wp_unslash( $_POST['pass'] ) : '';
		$new2 = isset( $_POST['pass2'] ) ? (string) wp_unslash( $_POST['pass2'] ) : '';
		if ( ! wp_check_password( $cur, $u->user_pass, $u->ID ) ) { md_acc_flash( 'err', '지금 비밀번호가 맞지 않습니다.' ); wp_safe_redirect( add_query_arg( 'first', get_user_meta( $u->ID, 'md_acc_must_change', true ) ? 1 : false, $back ) . '#mda-pass' ); exit; }
		$pe = md_acc_pass_ok( $new );
		if ( ! $pe && $new !== $new2 ) { $pe = '새 비밀번호 확인이 다릅니다.'; }
		if ( ! $pe && $new === $cur ) { $pe = '지금과 다른 비밀번호로 정해 주세요.'; }
		if ( $pe ) { md_acc_flash( 'err', $pe ); wp_safe_redirect( add_query_arg( 'first', get_user_meta( $u->ID, 'md_acc_must_change', true ) ? 1 : false, $back ) . '#mda-pass' ); exit; }
		wp_set_password( $new, $u->ID );
		delete_user_meta( $u->ID, 'md_acc_must_change' );
		/* wp_set_password 는 로그인을 끊는다 — 이 기기는 바로 다시 로그인시킨다 */
		wp_set_auth_cookie( $u->ID, true, is_ssl() );
		md_acc_flash( 'ok', '비밀번호를 바꿨습니다. 다른 기기에서는 새 비밀번호로 다시 로그인해 주세요.' );
		wp_safe_redirect( $back );
		exit;
	}
	/* 연락처 */
	$phone = md_acc_norm_phone( isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '' );
	$email = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' );
	if ( '' === $phone ) { md_acc_flash( 'err', '전화번호를 확인해 주세요.' ); wp_safe_redirect( $back ); exit; }
	if ( ! is_email( $email ) ) { md_acc_flash( 'err', '이메일 주소를 확인해 주세요.' ); wp_safe_redirect( $back ); exit; }
	$owner = email_exists( $email );
	if ( $owner && (int) $owner !== (int) $u->ID ) { md_acc_flash( 'err', '다른 계정이 쓰는 이메일입니다.' ); wp_safe_redirect( $back ); exit; }
	if ( $email !== $u->user_email ) { wp_update_user( array( 'ID' => $u->ID, 'user_email' => $email ) ); }
	$sid = (int) get_user_meta( $u->ID, 'md_staff_id', true );
	if ( $sid && md_acc_staff_row( $sid ) ) { md_acc_staff_fill( $sid, array( 'phone' => $phone, 'email' => $email ) ); }
	else { $app = (array) get_user_meta( $u->ID, 'md_acc_app', true ); $app['phone'] = $phone; update_user_meta( $u->ID, 'md_acc_app', $app ); }
	md_acc_flash( 'ok', '연락처를 저장했습니다.' );
	wp_safe_redirect( $back );
	exit;
}

function md_acc_render_me() {
	$u     = wp_get_current_user();
	$mine  = md_acc_is_personal_user( $u );
	$sid   = (int) get_user_meta( $u->ID, 'md_staff_id', true );
	$row   = $sid ? md_acc_staff_row( $sid ) : null;
	$app   = (array) get_user_meta( $u->ID, 'md_acc_app', true );
	$first = $mine && get_user_meta( $u->ID, 'md_acc_must_change', true );
	$phone = $row ? $row->phone : ( $app['phone'] ?? '' );
	?>
	<div class="mda-me">
		<?php md_acc_render_flash(); ?>
		<?php if ( $first ) : ?><div class="mds-notice mds-notice--warn">임시 비밀번호로 로그인했습니다. 먼저 <b>새 비밀번호</b>를 정해 주세요.</div><?php endif; ?>
		<section class="mds-card mda-card">
			<h2 class="mda-h">👤 <?php echo esc_html( '' !== trim( $u->display_name ) ? $u->display_name : $u->user_login ); ?></h2>
			<dl class="mda-dl">
				<dt>로그인</dt><dd>이름 「<?php echo esc_html( $u->display_name ); ?>」 + 숫자 6자리</dd>
				<?php if ( $mine ) : ?>
					<dt>권한</dt><dd><?php echo esc_html( md_acc_perm_label( $u ) ); ?></dd>
					<?php if ( $row ) : ?>
						<dt>부서</dt><dd><?php echo esc_html( $row->dept ?: '—' ); ?><?php echo $row->position ? ' · ' . esc_html( $row->position ) : ''; ?></dd>
						<dt>생일</dt><dd><?php echo $row->birthday ? esc_html( $row->birthday ) : '—'; ?></dd>
						<dt>입사일</dt><dd><?php echo $row->hired ? esc_html( $row->hired ) : '—'; ?></dd>
					<?php endif; ?>
				<?php elseif ( user_can( $u, 'manage_options' ) ) : ?>
					<dt>종류</dt><dd>홈페이지 관리자 (원장)</dd>
				<?php else : ?>
					<dt>종류</dt><dd>병원 공용 계정</dd>
				<?php endif; ?>
			</dl>
			<?php if ( $mine ) : ?><p class="mds-hint">이름 · 부서 · 생일 · 입사일이 틀렸으면 경영지원실에 말해 주세요.</p><?php endif; ?>
		</section>

		<?php if ( ! $mine && user_can( $u, 'manage_options' ) ) : ?>
			<section class="mds-card mda-card">
				<p class="mds-hint" style="margin:0">홈페이지 관리자 계정입니다. 비밀번호는 워드프레스 관리 화면(사용자 › 프로필)에서 바꿉니다. 직원 계정 승인 · 권한은 <a href="<?php echo esc_url( md_acc_lounge_url( array( 'app' => 'staff' ) ) ); ?>">직원 정보</a>에서 합니다.</p>
			</section>
		<?php elseif ( ! $mine ) : ?>
			<section class="mds-card mda-card">
				<p class="mds-hint" style="margin:0">여럿이 함께 쓰는 공용 계정입니다. 신청 · 요청에 내 이름이 자동으로 남게 하려면 로그아웃한 뒤 로그인 화면의 <b>「회원가입 신청」</b>으로 내 계정을 만들어 주세요.</p>
			</section>
		<?php else : ?>
			<?php /* v7.3 · 비밀번호 바꾸기를 맨 위로 (원장 지시) */ ?>
			<form method="post" class="mds-card mda-card mda-form" id="mda-pass">
				<h3 class="mda-h3"><?php echo $first ? '새 비밀번호 정하기' : '비밀번호 바꾸기'; ?></h3>
				<input type="hidden" name="md_acc" value="me"><input type="hidden" name="what" value="pass">
				<?php wp_nonce_field( 'md_acc_me', 'md_acc_nonce' ); ?>
				<input type="text" name="username" value="<?php echo esc_attr( $u->user_login ); ?>" autocomplete="username" hidden>
				<label class="mda-f"><span><?php echo $first ? '받은 임시 비밀번호' : '지금 비밀번호'; ?></span><input type="password" name="cur" required autocomplete="current-password"></label>
				<label class="mda-f"><span>새 비밀번호 <small>숫자 6자리</small></span><input type="password" name="pass" required minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="new-password"></label>
				<label class="mda-f"><span>새 비밀번호 확인</span><input type="password" name="pass2" required minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="new-password"></label>
				<button type="submit" class="mds-btn mds-btn--fill">비밀번호 바꾸기</button>
			</form>
			<?php if ( ! $first ) : ?>
			<form method="post" class="mds-card mda-card mda-form">
				<h3 class="mda-h3">연락처</h3>
				<input type="hidden" name="md_acc" value="me"><input type="hidden" name="what" value="contact">
				<?php wp_nonce_field( 'md_acc_me', 'md_acc_nonce' ); ?>
				<label class="mda-f"><span>전화번호</span><input type="tel" name="phone" required inputmode="numeric" maxlength="20" value="<?php echo esc_attr( $phone ); ?>" data-mda-phone></label>
				<label class="mda-f"><span>이메일</span><input type="email" name="email" required maxlength="120" value="<?php echo esc_attr( $u->user_email ); ?>"></label>
				<button type="submit" class="mds-btn mds-btn--fill">저장</button>
			</form>
			<?php endif; ?>
			<?php if ( ! $first ) : ?>
			<section class="mds-card mda-card">
				<h3 class="mda-h3">최근 로그인</h3>
				<p class="mds-hint">내가 모르는 기기 · 시간이 있으면 비밀번호를 바꾸고 경영지원실에 알려 주세요.</p>
				<?php md_acc_render_log_table( md_acc_log_rows( array( 'user_id' => $u->ID, 'limit' => 8 ) ), false ); ?>
			</section>
			<?php endif; ?>
		<?php endif; ?>
		<?php do_action( 'md_acc_me_after', $u ); /* v8.0 · 로그인한 기기 (inc/security) */ ?>
	</div>
	<?php
}

/* ============================================================
 * 직원 정보 화면에 붙는 부분 (관리자)
 * ============================================================ */

function md_acc_perm_checks( $u = null, $name_prefix = 'perms' ) {
	/* v8.2 · 권한은 직원 정보 › 「권한」 탭에서 (원장 지시 — 여기서는 고르지 않는다) */
	echo '<span class="mda-perms__note">권한은 위 「권한」 탭에서 정합니다</span>';
	return;
	$have = $u ? md_acc_user_perms( $u ) : array();
	foreach ( md_acc_perms() as $cap => $p ) {
		$lock = 'md_supply_manage' === $cap && ! md_acc_can_grant_admin();
		echo '<label class="mda-perm mds-check' . ( $lock ? ' is-lock' : '' ) . '"' . ( $lock ? ' title="원장님만 줄 수 있습니다"' : '' ) . '><input type="checkbox" name="' . esc_attr( $name_prefix ) . '[]" value="' . esc_attr( $cap ) . '"' . checked( in_array( $cap, $have, true ), true, false ) . disabled( $lock, true, false ) . '> <span><b>' . esc_html( $p[0] ) . '</b> <small>' . esc_html( $p[1] ) . '</small></span></label>';
	}
}

function md_acc_hidden( $act ) {
	echo '<input type="hidden" name="md_acc" value="' . esc_attr( $act ) . '">';
	wp_nonce_field( 'md_acc_' . $act, 'md_acc_nonce', false );
}

/** 직원 정보 맨 위 — 가입 신청 · 명단에 없는 계정 */
function md_acc_render_staff_panel() {
	if ( ! md_acc_can_manage() ) { return; }
	md_acc_render_flash();
	$pending = md_acc_users( 'pending' );
	$rows    = function_exists( 'md_staff_all' ) ? md_staff_all() : array();
	$map     = md_acc_staff_user_map();
	$depts   = function_exists( 'md_staff_depts' ) ? md_staff_depts() : array();
	?>
	<section class="mds-card mda-pending" id="mda-pending">
		<h2 class="mdst-title">가입 신청 <?php echo $pending ? '<span class="mda-badge">' . count( $pending ) . '</span>' : '<small>없음</small>'; ?></h2>
		<?php if ( ! $pending ) : ?>
			<p class="mds-hint" style="margin:0">직원이 라운지 로그인 화면의 「회원가입 신청」으로 신청하면 여기에 나옵니다. 신청이 오면 <b><?php echo esc_html( md_acc_notify_to() ); ?></b> 로 알림 메일이 갑니다.</p>
		<?php endif; ?>
		<?php foreach ( $pending as $u ) :
			$app  = (array) get_user_meta( $u->ID, 'md_acc_app', true );
			$best = 0;
			foreach ( $rows as $r ) {
				if ( isset( $map[ $r->id ] ) ) { continue; }
				if ( $r->name === $u->display_name ) {
					if ( ! $best || ( $r->birthday && $r->birthday === ( $app['birthday'] ?? '' ) ) ) { $best = (int) $r->id; }
				}
			} ?>
			<div class="mda-app" data-uid="<?php echo (int) $u->ID; ?>">
				<div class="mda-app__who">
					<b><?php echo esc_html( $u->display_name ); ?></b>
					<span class="mda-app__when"><?php echo esc_html( substr( (string) ( $app['at'] ?? $u->user_registered ), 0, 16 ) ); ?> 신청</span>
				</div>
				<dl class="mda-dl mda-dl--app">
					<?php if ( ! empty( $app['birthday'] ) ) : ?><dt>생년월일</dt><dd><?php echo esc_html( $app['birthday'] ); ?></dd><?php endif; /* 가입 때 더 받지 않음 — 덴트웹 연동 */ ?>
					<dt>입사일</dt><dd><?php echo esc_html( ( $app['hired'] ?? '' ) ?: '—' ); ?></dd>
					<dt>전화</dt><dd><?php echo esc_html( ( $app['phone'] ?? '' ) ?: '—' ); ?></dd>
					<dt>이메일</dt><dd><?php echo esc_html( $u->user_email ); ?></dd>
					<dt>부서</dt><dd><?php echo esc_html( ( $app['dept'] ?? '' ) ?: '—' ); ?></dd>
					<?php $sc = get_user_meta( $u->ID, 'md_acc_secret', true ); /* v9.46 */ ?><dt>비밀유지 서약</dt><dd><?php echo is_array( $sc ) && ! empty( $sc['at'] ) ? esc_html( '동의 ' . substr( $sc['at'], 0, 16 ) ) : '— (서약 전 가입)'; ?></dd>
				</dl>
				<form method="post" class="mda-approve">
					<?php md_acc_hidden( 'approve' ); ?><input type="hidden" name="uid" value="<?php echo (int) $u->ID; ?>">
					<label class="mda-f"><span>직원 명단과 연결</span>
						<select name="link">
							<option value="new">명단에 새로 추가</option>
							<?php foreach ( $rows as $r ) : if ( isset( $map[ $r->id ] ) || ( function_exists( 'md_staff_is_shared' ) && md_staff_is_shared( $r ) ) ) continue; ?>
								<option value="<?php echo (int) $r->id; ?>" <?php selected( $best, (int) $r->id ); ?>><?php echo esc_html( $r->name . ( $r->dept ? ' · ' . $r->dept : '' ) . ( $r->birthday ? ' · ' . substr( $r->birthday, 5 ) : '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="mda-f"><span>부서</span>
						<select name="dept"><option value="">그대로 / 나중에</option><?php foreach ( $depts as $d ) : ?><option value="<?php echo esc_attr( $d ); ?>" <?php selected( $d, $app['dept'] ?? '' ); ?>><?php echo esc_html( $d ); ?></option><?php endforeach; ?></select>
					</label>
					<div class="mda-perms"><span class="mda-perms__base">✓ 직원 (라운지 이용 · 재료실 신청)</span><?php md_acc_perm_checks(); ?></div>
					<div class="mda-actions">
						<button type="submit" class="mds-btn mds-btn--fill">승인</button>
					</div>
				</form>
				<details class="mda-reject"><summary>거절</summary>
					<form method="post" onsubmit="return confirm('<?php echo esc_js( $u->display_name ); ?> 님의 신청을 거절하고 지울까요?');">
						<?php md_acc_hidden( 'reject' ); ?><input type="hidden" name="uid" value="<?php echo (int) $u->ID; ?>">
						<label class="mda-f"><span>사유 (메일로 전달 · 선택)</span><input type="text" name="why" maxlength="200" placeholder="예: 우리 병원 직원이 아님"></label>
						<button type="submit" class="mds-btn mda-btn-danger">거절하고 지우기</button>
					</form>
				</details>
			</div>
		<?php endforeach; ?>
		<details class="mda-notify"><summary>알림 메일 주소</summary>
			<form method="post" class="mda-inline">
				<?php md_acc_hidden( 'notify' ); ?>
				<input type="text" name="to" value="<?php echo esc_attr( (string) get_option( 'md_acc_notify', '' ) ); ?>" placeholder="<?php echo esc_attr( md_acc_notify_to() ); ?>">
				<button type="submit" class="mds-btn">저장</button>
			</form>
		</details>
	</section>
	<?php
	/* 명단과 연결되지 않은 계정 (예: 재료실 설정에서 만든 옛 개인 계정) */
	$loose = array();
	foreach ( md_acc_users() as $u ) {
		if ( 'pending' === md_acc_status( $u ) ) { continue; }
		$sid = (int) get_user_meta( $u->ID, 'md_staff_id', true );
		if ( ! $sid || ! md_acc_staff_row( $sid ) || ( isset( $map[ $sid ] ) && (int) $map[ $sid ]->ID !== (int) $u->ID ) ) { $loose[] = $u; }
	}
	if ( $loose ) : ?>
		<section class="mds-card mda-loose" id="mda-loose">
			<h2 class="mdst-title">명단과 연결 안 된 계정 <small><?php echo count( $loose ); ?></small></h2>
			<?php foreach ( $loose as $u ) : ?>
				<div class="mda-acc mda-acc--loose">
					<div class="mda-acc__line"><b><?php echo esc_html( $u->display_name ); ?></b> <span class="mda-chip mda-chip--<?php echo esc_attr( md_acc_status( $u ) ); ?>"><?php echo esc_html( md_acc_perm_label( $u ) ); ?></span></div>
					<form method="post" class="mda-inline">
						<?php md_acc_hidden( 'link' ); ?><input type="hidden" name="uid" value="<?php echo (int) $u->ID; ?>">
						<select name="sid" required><option value="">명단에서 고르기</option><?php foreach ( $rows as $r ) : if ( isset( $map[ $r->id ] ) ) continue; ?><option value="<?php echo (int) $r->id; ?>" <?php selected( $r->name, $u->display_name ); ?>><?php echo esc_html( $r->name . ( $r->dept ? ' · ' . $r->dept : '' ) ); ?></option><?php endforeach; ?></select>
						<button type="submit" class="mds-btn">연결</button>
					</form>
					<?php md_acc_render_manage( $u ); ?>
				</div>
			<?php endforeach; ?>
		</section>
	<?php endif;
	md_acc_render_log_panel();
}

/** 직원 정보 맨 아래 — 퇴사한 직원 (복직 · 완전 삭제) */
function md_acc_render_retired( $rows ) {
	if ( ! md_acc_can_manage() || ! $rows ) { return; }
	$map = md_acc_staff_user_map();
	?>
	<section class="mds-card mda-retired" id="mda-retired">
		<details><summary class="mdst-title">퇴사한 직원 <small><?php echo count( $rows ); ?>명 — 홈페이지 · 달력 · 만족도 목록에 나오지 않음</small></summary>
			<p class="mds-hint">회원가입 때 「퇴사하면 개인정보를 지운다」고 안내했습니다. 정리가 끝나면 「완전 삭제」로 연락처 · 생일 · 사진 · 계정을 지워 주세요.</p>
			<?php foreach ( $rows as $r ) : $acc = isset( $map[ $r->id ] ) ? $map[ $r->id ] : null; $lo = md_acc_left_on( $r->id ); ?>
				<div class="mda-ret" id="s<?php echo (int) $r->id; ?>" data-sid="<?php echo (int) $r->id; ?>">
					<div class="mda-ret__who"><b><?php echo esc_html( $r->name ); ?></b> <span><?php echo esc_html( trim( $r->dept . ' ' . $r->position ) ); ?></span> <small><?php echo $lo ? esc_html( $lo ) . ' 퇴사' : '퇴사'; ?><?php echo $acc ? ' · 계정 있음 (' . esc_html( md_acc_perm_label( $acc ) ) . ')' : ''; ?></small></div>
					<div class="mda-btnrow">
						<form method="post"><?php md_acc_hidden( 'rehire' ); ?><input type="hidden" name="sid" value="<?php echo (int) $r->id; ?>"><button type="submit" class="mds-btn">복직</button></form>
						<form method="post" data-mda-confirm="<?php echo esc_attr( $r->name . ' 님의 명단 · 연락처 · 생일 · 사진' . ( $acc ? ' · 계정' : '' ) . '을 완전히 지울까요? 되돌릴 수 없습니다.' ); ?>"><?php md_acc_hidden( 'purge' ); ?><input type="hidden" name="sid" value="<?php echo (int) $r->id; ?>"><button type="submit" class="mds-btn mda-btn-danger">완전 삭제</button></form>
					</div>
				</div>
			<?php endforeach; ?>
		</details>
	</section>
	<?php
}

/** 직원 한 사람 줄 아래 — 계정 칸 */
function md_acc_render_row( $r ) {
	if ( ! md_acc_can_manage() || empty( $r->id ) ) { return; }
	/* v10.0 · 직원 공용 계정 줄 — 계정 만들기 · 퇴사 처리 없이 안내만 (권한은 「권한」 탭) */
	if ( function_exists( 'md_staff_is_shared' ) && md_staff_is_shared( $r ) ) {
		$su = defined( 'MD_SUP_STAFF_LOGIN' ) ? get_user_by( 'login', MD_SUP_STAFF_LOGIN ) : null;
		echo '<div class="mda-acc" data-sid="' . (int) $r->id . '"><div class="mda-acc__line"><span class="mda-acc__ic" aria-hidden="true">👥</span>직원 공용 계정'
			. ( $su ? ' <span class="mda-chip mda-chip--active">' . esc_html( md_acc_perm_label( $su ) ) . '</span> <small class="mda-last">' . esc_html( $su->user_email ) . ' · 마지막 로그인 ' . esc_html( md_acc_when( md_acc_last_login( $su->ID ) ) ) . '</small>' : ' <small>— 계정이 없습니다</small>' )
			. '</div><p class="mda-note">로그인 칸에 「' . esc_html( $r->name ) . '」. 권한은 위 「권한」 탭에서 정합니다. 홈페이지 명단 · 달력 · 만족도 담당자에는 나오지 않습니다.</p></div>';
		return;
	}
	static $map = null;
	if ( null === $map ) { $map = md_acc_staff_user_map(); }
	$u = isset( $map[ $r->id ] ) ? $map[ $r->id ] : null;
	if ( $u && 'pending' === md_acc_status( $u ) ) { $u = null; }
	echo '<div class="mda-acc" data-sid="' . (int) $r->id . '">';
	if ( $u ) {
		echo '<div class="mda-acc__line"><span class="mda-acc__ic" aria-hidden="true">👤</span>계정 <span class="mda-chip mda-chip--' . esc_attr( md_acc_status( $u ) ) . '">' . esc_html( md_acc_perm_label( $u ) ) . '</span>'
			. ( (int) $u->ID === get_current_user_id() ? ' <span class="mda-chip">나</span>' : '' )
			. ' <small class="mda-last">마지막 로그인 ' . esc_html( md_acc_when( md_acc_last_login( $u->ID ) ) ) . '</small></div>';
		md_acc_render_manage( $u );
	} else { ?>
		<div class="mda-acc__line mda-acc__line--none"><span class="mda-acc__ic" aria-hidden="true">👤</span>계정 없음 <small>— 본인이 회원가입을 신청하거나, 여기서 바로 만들어 줄 수 있습니다</small></div>
		<details class="mda-manage"><summary>계정 만들어 주기</summary>
			<form method="post" class="mda-form mda-form--compact">
				<?php md_acc_hidden( 'create' ); ?><input type="hidden" name="sid" value="<?php echo (int) $r->id; ?>">
				<p class="mda-note">로그인은 이름 「<?php echo esc_html( $r->name ); ?>」 + 임시 비밀번호(숫자 6자리)로 합니다.</p>
				<div class="mda-perms"><span class="mda-perms__base">✓ 직원</span><?php md_acc_perm_checks(); ?></div>
				<button type="submit" class="mds-btn mds-btn--fill">만들기 (임시 비밀번호 발급)</button>
			</form>
		</details>
	<?php }
	/* v9.32 · 「퇴사 처리」 접이식은 없앰 (원장: 명단의 휴지통이면 충분) — 휴지통으로 지우면 라운지 계정도 같이 중지 (inc/staff delete). 덴트웹 퇴사일 연동 · 「퇴사한 직원」 복직 · 완전 삭제는 그대로 */
	echo '</div>';
}

/** 계정 관리 버튼 — 권한 · 비밀번호 초기화 · 사용 중지 · 삭제 */
function md_acc_render_manage( $u ) {
	$self  = (int) $u->ID === get_current_user_id();
	$lock  = user_can( $u, 'md_supply_manage' ) && ! md_acc_can_grant_admin();
	$st    = md_acc_status( $u );
	if ( $lock ) { echo '<p class="mda-note">관리자 계정은 홈페이지 관리자만 바꿀 수 있습니다.</p>'; return; }
	?>
	<details class="mda-manage"><summary>계정 관리</summary>
		<p class="mda-note">권한(등급 · 재료실 관리 · 탭)은 위 「권한」 탭에서 정합니다.</p>
		<div class="mda-btnrow">
			<?php if ( 'active' === $st ) : ?>
				<form method="post" onsubmit="return confirm('<?php echo esc_js( $u->display_name ); ?> 님 비밀번호를 임시 비밀번호로 바꿀까요?');"><?php md_acc_hidden( 'reset' ); ?><input type="hidden" name="uid" value="<?php echo (int) $u->ID; ?>"><button type="submit" class="mds-btn">비밀번호 초기화</button></form>
			<?php endif; ?>
			<?php if ( ! $self ) : ?>
				<?php if ( 'off' === $st ) : ?>
					<form method="post"><?php md_acc_hidden( 'on' ); ?><input type="hidden" name="uid" value="<?php echo (int) $u->ID; ?>"><button type="submit" class="mds-btn">다시 사용</button></form>
				<?php else : ?>
					<form method="post" onsubmit="return confirm('<?php echo esc_js( $u->display_name ); ?> 님 계정을 사용 중지할까요? (퇴사 · 휴직 때)');"><?php md_acc_hidden( 'off' ); ?><input type="hidden" name="uid" value="<?php echo (int) $u->ID; ?>"><button type="submit" class="mds-btn">사용 중지</button></form>
				<?php endif; ?>
				<form method="post" onsubmit="return confirm('<?php echo esc_js( $u->display_name ); ?> 님 계정을 지울까요? 직원 명단과 지난 기록은 남습니다.');"><?php md_acc_hidden( 'delete' ); ?><input type="hidden" name="uid" value="<?php echo (int) $u->ID; ?>"><button type="submit" class="mds-btn mda-btn-danger">계정 삭제</button></form>
			<?php endif; ?>
			<?php if ( (int) get_user_meta( $u->ID, 'md_staff_id', true ) ) : ?>
				<form method="post"><?php md_acc_hidden( 'unlink' ); ?><input type="hidden" name="uid" value="<?php echo (int) $u->ID; ?>"><button type="submit" class="mds-btn mds-btn--ghost">명단 연결 끊기</button></form>
			<?php endif; ?>
		</div>
	</details>
	<?php
}

/* ============================================================
 * 화면 자원
 * ============================================================ */

function md_acc_enqueue() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	$css = '/assets/css/accounts.css';
	$js  = '/assets/js/accounts.js';
	if ( file_exists( $dir . $css ) ) { wp_enqueue_style( 'md-accounts', $uri . $css, array( 'moondental-supply' ), filemtime( $dir . $css ) ); }
	if ( file_exists( $dir . $js ) ) { wp_enqueue_script( 'md-accounts', $uri . $js, array(), filemtime( $dir . $js ), true ); }
}
add_action( 'wp_enqueue_scripts', 'md_acc_enqueue', 41 );
