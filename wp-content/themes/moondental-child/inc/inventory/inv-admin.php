<?php
/**
 * 재고관리 v5 — 기준 정보 (팀 · 업체 · 분류 · 계정)
 *
 * 지울 때의 원칙: 기록이 딸려 있으면 지우지 않고 「사용 안 함」으로 돌린다.
 * 지나간 출고·통계가 그 이름을 가리키고 있기 때문이다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 순서 바꾸기 — 같은 묶음 안에서 바로 위/아래와 자리를 바꾼다
 * ============================================================ */

function md_inv_reorder( $table, $id, $dir, $scope_sql = '' ) {
	global $wpdb;
	$rows = $wpdb->get_results( "SELECT id, sort_no FROM $table " . ( $scope_sql ? "WHERE $scope_sql " : '' ) . 'ORDER BY sort_no ASC, id ASC' );
	/* 순서 번호를 10, 20, 30 … 으로 다시 매긴 뒤 자리를 바꾼다 (같은 번호가 섞여 있어도 확실하게) */
	$ids = array();
	foreach ( $rows as $r ) { $ids[] = (int) $r->id; }
	$pos = array_search( (int) $id, $ids, true );
	if ( false === $pos ) { return false; }
	$swap = 'up' === $dir ? $pos - 1 : $pos + 1;
	if ( $swap < 0 || $swap >= count( $ids ) ) { return false; }
	$tmp = $ids[ $pos ]; $ids[ $pos ] = $ids[ $swap ]; $ids[ $swap ] = $tmp;
	foreach ( $ids as $i => $rid ) {
		$wpdb->update( $table, array( 'sort_no' => ( $i + 1 ) * 10 ), array( 'id' => $rid ) );
	}
	return true;
}

/* ============================================================
 * 팀
 * ============================================================ */

function md_inv_team_save( $id, $d ) {
	global $wpdb;
	$t    = md_inv_t( 'team' );
	$name = md_inv_txt( isset( $d['name'] ) ? $d['name'] : '', 100 );
	if ( '' === $name ) { return new WP_Error( 'name', '팀 이름을 적어 주세요.' ); }
	$dup = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE name = %s AND id <> %d", $name, (int) $id ) );
	if ( $dup ) { return new WP_Error( 'dup', '같은 이름의 팀이 이미 있습니다.' ); }
	$row = array(
		'name'     => $name,
		'in_stats' => ! empty( $d['in_stats'] ) ? 1 : 0,
		'active'   => isset( $d['active'] ) ? ( $d['active'] ? 1 : 0 ) : 1,
	);
	if ( $id ) {
		$wpdb->update( $t, $row, array( 'id' => (int) $id ) );
		md_inv_log( '팀 수정', $name );
		return (int) $id;
	}
	$row['sort_no'] = (int) $wpdb->get_var( "SELECT COALESCE(MAX(sort_no),0) FROM $t" ) + 10;
	$wpdb->insert( $t, $row );
	md_inv_log( '팀 추가', $name );
	return (int) $wpdb->insert_id;
}

function md_inv_team_delete( $id ) {
	global $wpdb;
	$t  = md_inv_t();
	$tm = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['team']} WHERE id = %d", (int) $id ) );
	if ( ! $tm ) { return new WP_Error( 'gone', '팀을 찾을 수 없습니다.' ); }
	$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['ledger']} WHERE team_id = %d", (int) $id ) )
	   + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['req']} WHERE team_id = %d", (int) $id ) );
	if ( $n > 0 ) {
		$wpdb->update( $t['team'], array( 'active' => 0 ), array( 'id' => (int) $id ) );
		md_inv_log( '팀 사용 안 함', $tm->name );
		return 'hidden';
	}
	$wpdb->delete( $t['team'], array( 'id' => (int) $id ) );
	md_inv_log( '팀 삭제', $tm->name );
	return 'deleted';
}

/* ============================================================
 * 업체
 * ============================================================ */

function md_inv_vendor_save( $id, $d ) {
	global $wpdb;
	$t    = md_inv_t( 'vendor' );
	$name = md_inv_txt( isset( $d['name'] ) ? $d['name'] : '', 150 );
	if ( '' === $name ) { return new WP_Error( 'name', '업체 이름을 적어 주세요.' ); }
	$dup = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE name = %s AND id <> %d", $name, (int) $id ) );
	if ( $dup ) { return new WP_Error( 'dup', '같은 이름의 업체가 이미 있습니다.' ); }
	$row = array(
		'name'    => $name,
		'contact' => md_inv_mtxt( isset( $d['contact'] ) ? $d['contact'] : '', 255 ),
		'phone'   => md_inv_mtxt( isset( $d['phone'] ) ? $d['phone'] : '', 255 ),
		'email'   => md_inv_mtxt( isset( $d['email'] ) ? $d['email'] : '', 255 ),
		'shop_info'  => md_inv_mtxt( isset( $d['shop_info'] ) ? $d['shop_info'] : '', 2000 ),
		'goods' => md_inv_mtxt( isset( $d['goods'] ) ? $d['goods'] : '', 2000 ),
		'note'    => md_inv_mtxt( isset( $d['note'] ) ? $d['note'] : '', 4000 ),
		'prepaid' => ! empty( $d['prepaid'] ) ? 1 : 0,
		'active'  => isset( $d['active'] ) ? ( $d['active'] ? 1 : 0 ) : 1,
	);
	/* v6.0 · 선납 적립률(%) · 잔액 알림 기준(원) — 칸이 있을 때만 */
	if ( isset( $d['pp_bonus'] ) && null !== $d['pp_bonus'] ) { $row['pp_bonus'] = max( 0, min( 100, round( (float) str_replace( array( ',', '%' ), '', (string) $d['pp_bonus'] ), 2 ) ) ); }
	if ( isset( $d['pp_alert'] ) && null !== $d['pp_alert'] ) { $row['pp_alert'] = max( 0, md_inv_int( $d['pp_alert'] ) ); }
	if ( $id ) {
		$wpdb->update( $t, $row, array( 'id' => (int) $id ) );
		md_inv_log( '업체 수정', $name );
		return (int) $id;
	}
	$row['sort_no'] = (int) $wpdb->get_var( "SELECT COALESCE(MAX(sort_no),0) FROM $t" ) + 10;
	$wpdb->insert( $t, $row );
	md_inv_log( '업체 추가', $name );
	return (int) $wpdb->insert_id;
}

function md_inv_vendor_delete( $id ) {
	global $wpdb;
	$t = md_inv_t();
	$v = md_inv_vendor( $id );
	if ( ! $v ) { return new WP_Error( 'gone', '업체를 찾을 수 없습니다.' ); }
	$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['item']} WHERE vendor_id = %d", (int) $id ) )
	   + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['ledger']} WHERE vendor_id = %d", (int) $id ) )
	   + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['ord']} WHERE vendor_id = %d", (int) $id ) )
	   + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['deposit']} WHERE vendor_id = %d", (int) $id ) );
	if ( $n > 0 ) {
		$wpdb->update( $t['vendor'], array( 'active' => 0 ), array( 'id' => (int) $id ) );
		md_inv_log( '업체 사용 안 함', $v->name );
		return 'hidden';
	}
	$wpdb->delete( $t['vendor'], array( 'id' => (int) $id ) );
	md_inv_log( '업체 삭제', $v->name );
	return 'deleted';
}

/* ============================================================
 * 분류 3단계
 * ============================================================ */

function md_inv_cat_save( $id, $d ) {
	global $wpdb;
	$t     = md_inv_t( 'cat' );
	$name  = md_inv_txt( isset( $d['name'] ) ? $d['name'] : '', 100 );
	$level = max( 1, min( 3, (int) ( isset( $d['level'] ) ? $d['level'] : 1 ) ) );
	$par   = (int) ( isset( $d['parent_id'] ) ? $d['parent_id'] : 0 );
	if ( '' === $name ) { return new WP_Error( 'name', '분류 이름을 적어 주세요.' ); }
	if ( $id ) {
		$cur = md_inv_cat( $id );
		if ( ! $cur ) { return new WP_Error( 'gone', '분류를 찾을 수 없습니다.' ); }
		$level = (int) $cur->level;
		$par   = (int) $cur->parent_id;
	}
	if ( $level > 1 ) {
		$p = md_inv_cat( $par );
		if ( ! $p || (int) $p->level !== $level - 1 ) { return new WP_Error( 'parent', '위 단계 분류를 골라 주세요.' ); }
	} else { $par = 0; }
	$dup = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE name = %s AND level = %d AND parent_id = %d AND id <> %d", $name, $level, $par, (int) $id ) );
	if ( $dup ) { return new WP_Error( 'dup', '같은 자리에 같은 이름의 분류가 있습니다.' ); }
	$row = array( 'name' => $name, 'note' => md_inv_txt( isset( $d['note'] ) ? $d['note'] : '', 255 ), 'active' => isset( $d['active'] ) ? ( $d['active'] ? 1 : 0 ) : 1 );
	if ( $id ) {
		$wpdb->update( $t, $row, array( 'id' => (int) $id ) );
		md_inv_log( '분류 수정', $name );
		return (int) $id;
	}
	$row['level']     = $level;
	$row['parent_id'] = $par;
	$row['sort_no']   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(sort_no),0) FROM $t WHERE level = %d AND parent_id = %d", $level, $par ) ) + 10;
	$wpdb->insert( $t, $row );
	md_inv_log( '분류 추가', $name );
	return (int) $wpdb->insert_id;
}

/** 분류를 지우면 그 아래 분류도 지우고, 그 분류를 쓰던 품목은 그 칸이 비워진다 */
function md_inv_cat_delete( $id ) {
	global $wpdb;
	$t = md_inv_t();
	$c = md_inv_cat( $id );
	if ( ! $c ) { return new WP_Error( 'gone', '분류를 찾을 수 없습니다.' ); }
	$ids = array( (int) $c->id );
	foreach ( md_inv_cats() as $x ) { if ( (int) $x->parent_id === (int) $c->id ) { $ids[] = (int) $x->id; } }
	foreach ( md_inv_cats() as $x ) { if ( in_array( (int) $x->parent_id, $ids, true ) && ! in_array( (int) $x->id, $ids, true ) ) { $ids[] = (int) $x->id; } }
	$in = implode( ',', $ids );
	$wpdb->query( "UPDATE {$t['item']} SET cat1 = 0 WHERE cat1 IN ($in)" );
	$wpdb->query( "UPDATE {$t['item']} SET cat2 = 0 WHERE cat2 IN ($in)" );
	$wpdb->query( "UPDATE {$t['item']} SET cat3 = 0 WHERE cat3 IN ($in)" );
	$wpdb->query( "DELETE FROM {$t['cat']} WHERE id IN ($in)" );
	md_inv_log( '분류 삭제', $c->name . ( count( $ids ) > 1 ? ' (아래 분류 ' . ( count( $ids ) - 1 ) . '개 포함)' : '' ) );
	return true;
}

/** 이 분류를 쓰는 품목 수 */
function md_inv_cat_usage() {
	global $wpdb;
	$t = md_inv_t( 'item' );
	$m = array();
	foreach ( array( 'cat1', 'cat2', 'cat3' ) as $col ) {
		foreach ( $wpdb->get_results( "SELECT $col AS k, COUNT(*) AS n FROM $t WHERE $col > 0 GROUP BY $col" ) as $r ) {
			$m[ (int) $r->k ] = ( isset( $m[ (int) $r->k ] ) ? $m[ (int) $r->k ] : 0 ) + (int) $r->n;
		}
	}
	return $m;
}

/* ============================================================
 * 계정 — 라운지 계정 (직원 / 재고 담당자)
 *   워드프레스 관리자(administrator)는 여기서 바꾸지 않는다.
 * ============================================================ */

/**
 * 재고관리에서 만든 라운지 계정(직원 · 재고 담당자 역할)도 두 공용 계정과 똑같이
 * 로그인하면 라운지로 가고, 워드프레스 관리 화면에는 들어가지 않는다.
 */
function md_inv_is_lounge_only( $user ) {
	if ( ! $user || ! isset( $user->roles ) ) { return false; }
	if ( user_can( $user, 'manage_options' ) || user_can( $user, 'edit_posts' ) ) { return false; }
	return (bool) array_intersect( (array) $user->roles, array( 'md_stock_staff', 'md_stock_manager' ) );
}
add_filter( 'login_redirect', function ( $to, $req, $user ) {
	return ( ! is_wp_error( $user ) && md_inv_is_lounge_only( $user ) ) ? home_url( '/직원/' ) : $to;
}, 20, 3 );
/* admin_init 보다 앞(init)에서 돌려보낸다 — 관리 화면이 메뉴를 만들며 내는 PHP 알림까지 막는다 */
add_action( 'init', function () {
	if ( ! is_admin() || wp_doing_ajax() || wp_doing_cron() || ! is_user_logged_in() ) { return; }
	if ( isset( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( (string) $_SERVER['SCRIPT_NAME'], 'admin-post.php' ) ) { return; }
	if ( md_inv_is_lounge_only( wp_get_current_user() ) ) { wp_safe_redirect( home_url( '/직원/' ) ); exit; }
}, 1 );
add_filter( 'show_admin_bar', function ( $show ) {
	return ( is_user_logged_in() && md_inv_is_lounge_only( wp_get_current_user() ) ) ? false : $show;
}, 30 );

function md_inv_can_accounts() {
	return md_inv_is_admin() && current_user_can( 'create_users' );
}

function md_inv_accounts() {
	$users = get_users( array( 'role__in' => array( 'md_stock_staff', 'md_stock_manager', 'administrator' ), 'orderby' => 'login' ) );
	$out = array();
	foreach ( $users as $u ) {
		$role = in_array( 'administrator', (array) $u->roles, true ) ? 'administrator' : ( in_array( 'md_stock_manager', (array) $u->roles, true ) ? 'md_stock_manager' : 'md_stock_staff' );
		$out[] = (object) array( 'id' => $u->ID, 'login' => $u->user_login, 'name' => $u->display_name, 'role' => $role, 'registered' => $u->user_registered );
	}
	return $out;
}

function md_inv_role_label( $role ) {
	$m = array( 'administrator' => '홈페이지 관리자', 'md_stock_manager' => '관리자', 'md_stock_staff' => '직원' );
	return isset( $m[ $role ] ) ? $m[ $role ] : $role;
}

function md_inv_account_create( $login, $name, $pass, $role ) {
	if ( ! md_inv_can_accounts() ) { return new WP_Error( 'perm', '계정을 만들 권한이 없습니다.' ); }
	$login = sanitize_user( (string) $login, true );
	if ( strlen( $login ) < 3 ) { return new WP_Error( 'login', '아이디는 영문·숫자 3자 이상으로 적어 주세요.' ); }
	if ( username_exists( $login ) ) { return new WP_Error( 'dup', '이미 있는 아이디입니다.' ); }
	if ( strlen( (string) $pass ) < 8 ) { return new WP_Error( 'pass', '비밀번호는 8자 이상으로 정해 주세요.' ); }
	if ( ! in_array( $role, array( 'md_stock_staff', 'md_stock_manager' ), true ) ) { $role = 'md_stock_staff'; }
	if ( function_exists( 'md_sup_add_roles' ) ) { md_sup_add_roles(); }
	$id = wp_insert_user( array(
		'user_login' => $login, 'user_pass' => (string) $pass, 'display_name' => md_inv_txt( $name ? $name : $login, 60 ),
		'role' => $role, 'show_admin_bar_front' => 'false',
		'user_email' => '',
	) );
	if ( is_wp_error( $id ) ) { return $id; }
	md_inv_log( '계정 만들기', $login . ' · ' . md_inv_role_label( $role ) );
	return $id;
}

/** 바꿀 수 있는 계정인가 — 라운지 계정만, 자기 자신의 역할은 못 바꾼다 */
function md_inv_account_editable( $user_id ) {
	$u = get_userdata( (int) $user_id );
	if ( ! $u || in_array( 'administrator', (array) $u->roles, true ) ) { return null; }
	if ( ! array_intersect( (array) $u->roles, array( 'md_stock_staff', 'md_stock_manager' ) ) ) { return null; }
	return $u;
}

function md_inv_account_password( $user_id, $pass ) {
	if ( ! md_inv_can_accounts() ) { return new WP_Error( 'perm', '권한이 없습니다.' ); }
	$u = md_inv_account_editable( $user_id );
	if ( ! $u ) { return new WP_Error( 'user', '이 계정은 여기서 바꿀 수 없습니다. 워드프레스 관리 화면에서 바꿔 주세요.' ); }
	if ( strlen( (string) $pass ) < 8 ) { return new WP_Error( 'pass', '비밀번호는 8자 이상으로 정해 주세요.' ); }
	wp_set_password( (string) $pass, $u->ID );
	md_inv_log( '비밀번호 변경', $u->user_login );
	return true;
}

function md_inv_account_role( $user_id, $role ) {
	if ( ! md_inv_can_accounts() ) { return new WP_Error( 'perm', '권한이 없습니다.' ); }
	$u = md_inv_account_editable( $user_id );
	if ( ! $u ) { return new WP_Error( 'user', '이 계정의 권한은 여기서 바꿀 수 없습니다.' ); }
	if ( (int) $u->ID === get_current_user_id() ) { return new WP_Error( 'self', '지금 로그인한 계정의 권한은 바꿀 수 없습니다.' ); }
	if ( ! in_array( $role, array( 'md_stock_staff', 'md_stock_manager' ), true ) ) { return new WP_Error( 'role', '권한을 골라 주세요.' ); }
	$u->set_role( $role );
	md_inv_log( '권한 변경', $u->user_login . ' → ' . md_inv_role_label( $role ) );
	return true;
}

function md_inv_account_delete( $user_id ) {
	if ( ! md_inv_can_accounts() ) { return new WP_Error( 'perm', '권한이 없습니다.' ); }
	$u = md_inv_account_editable( $user_id );
	if ( ! $u ) { return new WP_Error( 'user', '이 계정은 여기서 지울 수 없습니다.' ); }
	if ( (int) $u->ID === get_current_user_id() ) { return new WP_Error( 'self', '지금 로그인한 계정은 지울 수 없습니다.' ); }
	if ( function_exists( 'md_sup_account_rows' ) && in_array( $u->user_login, array( MD_SUP_STAFF_LOGIN, MD_SUP_MANAGER_LOGIN ), true ) ) {
		return new WP_Error( 'core', '직원 공용 계정은 지울 수 없습니다. 비밀번호만 바꿔 주세요.' );
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( $u->ID );
	md_inv_log( '계정 삭제', $u->user_login );
	return true;
}


/* ============================================================
 * 시험 계정 moondentaltesting (2026-10-05 원장 지시 — v6.1 운영 재시험)
 *   직원 + 「재료실 관리」 권한 · 워드프레스 관리 권한 없음 · 이메일 없음(알림 메일이 나가지 않게).
 *   비밀번호는 저장소에 없다 — 해시만 둔다.
 *   create  → 계정을 만든다
 *   restore → 「운영 시험 전」 백업으로 한 번 되돌린다 (백업 뒤 시험 계정 말고 다른 사람 기록이 있으면 되돌리지 않는다)
 *            되돌리기는 원장만 할 수 있어 시험 계정이 직접 못 하므로 서버가 한 번만 한다.
 *   remove  → 계정을 지운다
 * ============================================================ */
define( 'MD_INV_TESTACCT_MODE', 'create' );
function md_inv_test_account() {
	$login = 'moondentaltesting';
	$u     = get_user_by( 'login', $login );
	if ( 'remove' === MD_INV_TESTACCT_MODE ) {
		if ( $u && ! user_can( $u, 'manage_options' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $u->ID );
		}
		delete_option( 'md_inv_testacct_v61' );
		return;
	}
	if ( 'restore' === MD_INV_TESTACCT_MODE ) {
		if ( ! $u || get_option( 'md_inv_testacct_v61_restored' ) || ! function_exists( 'md_inv_backup_restore' ) ) { return; }
		if ( ! add_option( 'md_inv_testacct_v61_restored', 'working ' . current_time( 'mysql' ), '', 'no' ) ) { return; } /* 한 번만 */
		global $wpdb;
		$bk = $wpdb->get_row( "SELECT id, note, created_at FROM " . md_inv_t( 'backup' ) . " WHERE note LIKE '운영 시험 전 %' ORDER BY id DESC LIMIT 1" );
		if ( ! $bk ) { update_option( 'md_inv_testacct_v61_restored', 'no backup' ); return; }
		$others = (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . md_inv_t( 'log' ) . " WHERE created_at >= %s AND user_id <> %d AND action NOT IN ('백업', '보고서 메일', '엑셀 내려받기', '백업 내려받기')",
			$bk->created_at, (int) $u->ID ) );
		if ( $others ) { update_option( 'md_inv_testacct_v61_restored', 'skipped: ' . $others . ' other entries' ); md_inv_log( '시험 되돌리기 건너뜀', '시험 중 다른 사람 기록 ' . $others . '건' ); return; }
		$d = md_inv_backup_parse( md_inv_backup_blob( (int) $bk->id ) );
		if ( is_wp_error( $d ) ) { update_option( 'md_inv_testacct_v61_restored', 'parse error' ); return; }
		$n = md_inv_backup_restore( $d );
		update_option( 'md_inv_testacct_v61_restored', is_wp_error( $n ) ? 'error: ' . $n->get_error_message() : 'ok ' . (int) $n . ' rows from #' . $bk->id );
		return;
	}
	if ( get_option( 'md_inv_testacct_v61' ) || $u ) { return; }
	if ( function_exists( 'md_sup_add_roles' ) ) { md_sup_add_roles(); }
	$id = wp_insert_user( array( 'user_login' => $login, 'user_pass' => wp_generate_password( 32 ), 'display_name' => '시험계정', 'role' => 'md_stock_staff', 'show_admin_bar_front' => 'false' ) );
	if ( is_wp_error( $id ) ) { return; }
	global $wpdb;
	$wpdb->update( $wpdb->users, array( 'user_pass' => '$P$B2CVq4VaHu0uvmsDWYXhrP7f5amEV11' ), array( 'ID' => (int) $id ) );
	clean_user_cache( $id );
	get_userdata( $id )->add_cap( 'md_inv_manage' );
	delete_option( 'md_inv_testacct_v61_restored' );
	update_option( 'md_inv_testacct_v61', current_time( 'mysql' ), false );
}
add_action( 'init', 'md_inv_test_account', 20 );
