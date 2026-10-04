<?php
/**
 * 재고관리 v5 — 설정 · 권한 · 기준 정보 · 기록
 *
 * 화면은 이 파일의 함수만 부른다. 수량이 움직이는 모든 일은
 * md_inv_ledger_add() 한 곳을 지난다 (부호를 여기서만 정한다).
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 설정 — 관리자가 「설정 › 운영 설정」에서 바꾼다
 * ============================================================ */

function md_inv_setting_defaults() {
	return array(
		'label_cat1'          => '결제 방식',
		'label_cat2'          => '품목군',
		'label_cat3'          => '세부 분류',
		'req_need_name'       => 1,   // 요청자 이름 필수
		'req_allow_custom'    => 1,   // 「목록에 없는 품목」 요청 허용
		'req_allow_over'      => 1,   // 재고보다 많이 요청 허용 (경고만)
		'req_remember'        => 1,   // 이 기기에서 마지막 팀·이름 기억
		'req_max_qty'         => 999, // 한 번에 요청할 수 있는 최대 수량
		'recent_days'         => 60,  // 「우리 팀이 최근 요청한 품목」 기준 일수
		'staff_see_stock'     => 1,   // 직원에게 현재고 숫자 보이기
		'staff_see_price'     => 0,   // 직원에게 단가 보이기
		'staff_see_all_teams' => 1,   // 직원이 다른 팀 요청 내역도 보기
		'staff_cancel'        => 1,   // 직원이 대기 중 요청 취소
		'staff_stats'         => 1,   // 직원에게 통계 탭
		'staff_stock_tab'     => 0,   // 직원에게 재고 조회 탭
		'history_days'        => 90,  // 요청 내역 기본 기간
		'out_allow_negative'  => 0,   // 재고보다 많이 출고 허용
		'adjust_need_note'    => 1,   // 실사 조정에 사유 필수
		'order_need_recent'   => 12,  // 「주문 필요」: 최근 N주 출고된 품목만 (0 = 전체)
		'stats_weeks'         => 12,
		'stats_months'        => 12,
		'stats_include_prepaid' => 0, // 사용금액 통계에 선납 업체 품목 포함
		'min_cover_weeks'     => 4,   // 안전재고 제안: 몇 주 쓸 만큼
		'notify_new'          => 0,   // 새 요청 메일
		'notify_to'           => '',
		'report_on'           => 1,   // 정기 엑셀 보고서 메일
		'report_freq'         => 'weekly', // daily | weekly | monthly
		'report_dow'          => 1,   // 요일 (1=월 … 7=일)
		'report_dom'          => 1,   // 날짜 (매월)
		'report_hour'         => 8,
		'report_to'           => 'moondentaldigital@gmail.com',
		'report_backup'       => 1,   // 보고서에 백업 파일도 첨부
		'backup_on'           => 1,   // 매일 자동 백업
		'backup_keep'         => 30,  // 보관 개수
		'me_default'          => '관리자',
	);
}

function md_inv_settings() {
	static $cache = null;
	if ( null !== $cache ) { return $cache; }
	$s = get_option( 'md_inv_settings', array() );
	$cache = wp_parse_args( is_array( $s ) ? $s : array(), md_inv_setting_defaults() );
	return $cache;
}

function md_inv_set( $key ) {
	$s = md_inv_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : null;
}

/** 설정 저장 — 알려진 키만, 기본값의 형(정수/문자)에 맞춰 */
function md_inv_settings_save( $in ) {
	$def = md_inv_setting_defaults();
	$cur = md_inv_settings();
	$out = $cur;
	foreach ( $def as $k => $d ) {
		if ( ! array_key_exists( $k, $in ) ) {
			/* 체크박스는 꺼지면 아예 오지 않는다 → 0 */
			if ( is_int( $d ) && in_array( $d, array( 0, 1 ), true ) && isset( $in['__checkboxes'] ) && in_array( $k, (array) $in['__checkboxes'], true ) ) { $out[ $k ] = 0; }
			continue;
		}
		$v = $in[ $k ];
		if ( is_int( $d ) ) { $out[ $k ] = (int) $v; }
		else { $out[ $k ] = mb_substr( sanitize_text_field( (string) $v ), 0, 300 ); }
	}
	/* 범위 */
	$out['req_max_qty']   = max( 1, min( 99999, (int) $out['req_max_qty'] ) );
	$out['recent_days']   = max( 1, min( 365, (int) $out['recent_days'] ) );
	$out['history_days']  = max( 7, min( 3650, (int) $out['history_days'] ) );
	$out['order_need_recent'] = max( 0, min( 104, (int) $out['order_need_recent'] ) );
	$out['stats_weeks']   = max( 4, min( 52, (int) $out['stats_weeks'] ) );
	$out['stats_months']  = max( 3, min( 36, (int) $out['stats_months'] ) );
	$out['report_dow']    = max( 1, min( 7, (int) $out['report_dow'] ) );
	$out['report_dom']    = max( 1, min( 28, (int) $out['report_dom'] ) );
	$out['report_hour']   = max( 0, min( 23, (int) $out['report_hour'] ) );
	$out['backup_keep']   = max( 3, min( 365, (int) $out['backup_keep'] ) );
	$out['min_cover_weeks'] = max( 1, min( 26, (int) $out['min_cover_weeks'] ) );
	if ( ! in_array( $out['report_freq'], array( 'daily', 'weekly', 'monthly' ), true ) ) { $out['report_freq'] = 'weekly'; }
	foreach ( array( 'label_cat1', 'label_cat2', 'label_cat3' ) as $k ) {
		if ( '' === trim( $out[ $k ] ) ) { $out[ $k ] = $def[ $k ]; }
	}
	$out['report_to'] = md_inv_clean_emails( $out['report_to'] );
	$out['notify_to'] = md_inv_clean_emails( $out['notify_to'] );
	unset( $out['__checkboxes'] );
	update_option( 'md_inv_settings', $out, false );
	return $out;
}

/** 쉼표로 이은 주소 중 올바른 것만 */
function md_inv_clean_emails( $raw ) {
	$ok = array();
	foreach ( preg_split( '/[\s,;]+/', (string) $raw ) as $e ) {
		$e = sanitize_email( $e );
		if ( $e && is_email( $e ) ) { $ok[] = $e; }
	}
	return implode( ', ', array_unique( $ok ) );
}

/* ============================================================
 * 권한 — 라운지 공통 권한을 그대로 쓴다
 *   직원 = md_sup_can_use (공용 계정 moondentalhospital)
 *   관리자 = md_sup_can_manage (moondentalmanager · 재고 담당자 역할)
 * ============================================================ */

function md_inv_can_use() {
	return function_exists( 'md_sup_can_use' ) ? md_sup_can_use() : current_user_can( 'manage_options' );
}

function md_inv_is_admin() {
	return function_exists( 'md_sup_can_manage' ) ? md_sup_can_manage() : current_user_can( 'manage_options' );
}

/** 지금 처리하는 사람 이름 — 관리자는 이 기기에 기억한 이름, 없으면 설정의 기본 이름 */
function md_inv_me() {
	/* 개인 계정(공용 두 계정이 아닌 것)은 계정 이름이 곧 기록에 남는 이름이다 */
	if ( md_inv_is_personal() ) {
		$u = wp_get_current_user();
		return mb_substr( '' !== trim( $u->display_name ) ? $u->display_name : $u->user_login, 0, 40 );
	}
	$c = isset( $_COOKIE['md_inv_me'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['md_inv_me'] ) ) : '';
	$c = trim( mb_substr( $c, 0, 40 ) );
	if ( '' !== $c ) { return $c; }
	$d = trim( (string) md_inv_set( 'me_default' ) );
	return '' !== $d ? $d : wp_get_current_user()->user_login;
}

/** 여럿이 함께 쓰는 공용 계정이 아닌 개인 계정인가 */
function md_inv_is_personal() {
	if ( ! is_user_logged_in() ) { return false; }
	$login  = wp_get_current_user()->user_login;
	$shared = array( defined( 'MD_SUP_MANAGER_LOGIN' ) ? MD_SUP_MANAGER_LOGIN : 'moondentalmanager', defined( 'MD_SUP_STAFF_LOGIN' ) ? MD_SUP_STAFF_LOGIN : 'moondentalhospital' );
	return ! in_array( $login, $shared, true );
}

/* ============================================================
 * 초성 검색 — 「ㅇㅋㅅ」 → 「알콜솜」
 * ============================================================ */

function md_inv_choseong( $s ) {
	static $cho = array( 'ㄱ', 'ㄲ', 'ㄴ', 'ㄷ', 'ㄸ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅃ', 'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅉ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ' );
	$out = '';
	foreach ( preg_split( '//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY ) as $ch ) {
		$c = mb_ord( $ch, 'UTF-8' );
		if ( $c >= 0xAC00 && $c <= 0xD7A3 ) { $out .= $cho[ intdiv( $c - 0xAC00, 588 ) ]; }
		else { $out .= mb_strtolower( $ch, 'UTF-8' ); }
	}
	return $out;
}

/** 검색어가 초성(ㄱ~ㅎ)만으로 되어 있나 */
function md_inv_is_choseong_query( $q ) {
	return (bool) preg_match( '/^[\x{3131}-\x{314E}\s]+$/u', (string) $q );
}

/* ============================================================
 * 작업 기록 · 잠금
 * ============================================================ */

function md_inv_log( $action, $detail = '' ) {
	global $wpdb;
	$wpdb->insert( md_inv_t( 'log' ), array(
		'user_id'    => get_current_user_id(),
		'person'     => mb_substr( is_user_logged_in() ? md_inv_me() : '시스템', 0, 100 ),
		'action'     => mb_substr( (string) $action, 0, 40 ),
		'detail'     => mb_substr( (string) $detail, 0, 1000 ),
		'created_at' => current_time( 'mysql' ),
	) );
}

/** MySQL 인가 (로컬 시험은 SQLite) */
function md_inv_is_mysql() {
	global $wpdb;
	return ! ( $wpdb instanceof WP_SQLite_DB ) && 'WP_SQLite_DB' !== get_class( $wpdb );
}

/**
 * 재고를 움직이는 일은 한 번에 하나씩.
 * 두 담당자가 같은 품목을 동시에 출고하면 둘 다 「재고 있음」을 보고 지나가
 * 음수가 될 수 있다. MySQL 이름 잠금으로 줄을 세운다.
 */
function md_inv_lock() {
	global $wpdb;
	if ( ! md_inv_is_mysql() ) { return true; }
	return '1' === (string) $wpdb->get_var( "SELECT GET_LOCK('md_inv_stock', 10)" );
}

function md_inv_unlock() {
	global $wpdb;
	if ( ! md_inv_is_mysql() ) { return; }
	$wpdb->query( "SELECT RELEASE_LOCK('md_inv_stock')" );
}

function md_inv_begin()    { global $wpdb; $wpdb->query( 'START TRANSACTION' ); }
function md_inv_commit()   { global $wpdb; $wpdb->query( 'COMMIT' ); }
function md_inv_rollback() { global $wpdb; $wpdb->query( 'ROLLBACK' ); }

/* ============================================================
 * 표시 도우미
 * ============================================================ */

function md_inv_won( $n ) { return number_format( (int) round( (float) $n ) ) . '원'; }
function md_inv_num( $n ) { return number_format( (int) $n ); }

function md_inv_date( $dt, $fmt = 'n/j H:i' ) {
	if ( empty( $dt ) ) { return ''; }
	$ts = strtotime( (string) $dt );
	return $ts ? date( $fmt, $ts ) : '';
}

function md_inv_type_label( $type ) {
	$m = array( 'open' => '기초', 'in' => '입고', 'out' => '출고', 'return' => '반품', 'adjust' => '실사' );
	return isset( $m[ $type ] ) ? $m[ $type ] : $type;
}

function md_inv_req_status_label( $s ) {
	$m = array( 'pending' => '대기', 'done' => '출고 완료', 'rejected' => '반려', 'cancelled' => '취소' );
	return isset( $m[ $s ] ) ? $m[ $s ] : $s;
}

function md_inv_ord_status_label( $s ) {
	$m = array( 'ordered' => '주문함', 'received' => '입고 완료', 'cancelled' => '취소' );
	return isset( $m[ $s ] ) ? $m[ $s ] : $s;
}

/** 한 줄 문자열 정리 */
function md_inv_txt( $v, $max = 255 ) {
	return mb_substr( trim( sanitize_text_field( (string) $v ) ), 0, $max );
}

/** 여러 줄 문자열 정리 */
function md_inv_mtxt( $v, $max = 2000 ) {
	return mb_substr( trim( sanitize_textarea_field( (string) $v ) ), 0, $max );
}

/** 정수 (콤마·원·공백 허용) */
function md_inv_int( $v ) {
	$v = preg_replace( '/[^\d\-]/', '', (string) $v );
	if ( '' === $v || '-' === $v ) { return 0; }
	return (int) $v;
}

/* ============================================================
 * 기준 정보 — 팀 · 업체 · 분류
 * ============================================================ */

function md_inv_teams( $only_active = true ) {
	global $wpdb;
	static $cache = array();
	$k = $only_active ? 1 : 0;
	if ( isset( $cache[ $k ] ) ) { return $cache[ $k ]; }
	$w = $only_active ? 'WHERE active = 1' : '';
	$cache[ $k ] = $wpdb->get_results( 'SELECT * FROM ' . md_inv_t( 'team' ) . " $w ORDER BY sort_no ASC, id ASC" );
	return $cache[ $k ];
}

function md_inv_team_name( $id ) {
	foreach ( md_inv_teams( false ) as $t ) { if ( (int) $t->id === (int) $id ) { return $t->name; } }
	return $id ? '(삭제된 팀)' : '';
}

function md_inv_vendors( $only_active = true ) {
	global $wpdb;
	static $cache = array();
	$k = $only_active ? 1 : 0;
	if ( isset( $cache[ $k ] ) ) { return $cache[ $k ]; }
	$w = $only_active ? 'WHERE active = 1' : '';
	$cache[ $k ] = $wpdb->get_results( 'SELECT * FROM ' . md_inv_t( 'vendor' ) . " $w ORDER BY name ASC" );
	return $cache[ $k ];
}

function md_inv_vendor( $id ) {
	foreach ( md_inv_vendors( false ) as $v ) { if ( (int) $v->id === (int) $id ) { return $v; } }
	return null;
}

function md_inv_vendor_name( $id ) {
	$v = md_inv_vendor( $id );
	return $v ? $v->name : '';
}

/** 분류 전체 (단계 · 순서대로) */
function md_inv_cats( $only_active = false ) {
	global $wpdb;
	static $cache = array();
	$k = $only_active ? 1 : 0;
	if ( isset( $cache[ $k ] ) ) { return $cache[ $k ]; }
	$w = $only_active ? 'WHERE active = 1' : '';
	$cache[ $k ] = $wpdb->get_results( 'SELECT * FROM ' . md_inv_t( 'cat' ) . " $w ORDER BY level ASC, sort_no ASC, id ASC" );
	return $cache[ $k ];
}

function md_inv_cat( $id ) {
	foreach ( md_inv_cats() as $c ) { if ( (int) $c->id === (int) $id ) { return $c; } }
	return null;
}

function md_inv_cat_name( $id ) {
	$c = md_inv_cat( $id );
	return $c ? $c->name : '';
}

/** 한 단계의 분류 (상위로 좁히기) */
function md_inv_cats_of( $level, $parent = null, $only_active = true ) {
	$out = array();
	foreach ( md_inv_cats( $only_active ) as $c ) {
		if ( (int) $c->level !== (int) $level ) { continue; }
		if ( null !== $parent && (int) $c->parent_id !== (int) $parent ) { continue; }
		$out[] = $c;
	}
	return $out;
}

/* ============================================================
 * 품목
 * ============================================================ */

/**
 * 품목 목록 + 현재고 · 대기 요청 · 주문 중 수량.
 * 한 번의 질의로 가져온다 (품목마다 따로 세지 않는다).
 *
 * @param array $a search, vendor, cat1, cat2, cat3, active (1|0|-1 전체), low (bool), ids (array)
 */
function md_inv_items( $a = array() ) {
	global $wpdb;
	$t = md_inv_t();
	$a = wp_parse_args( $a, array( 'search' => '', 'vendor' => 0, 'cat1' => 0, 'cat2' => 0, 'cat3' => 0, 'active' => 1, 'ids' => null, 'barcode' => '' ) );

	$w = array( '1=1' );
	$p = array();
	if ( 1 === (int) $a['active'] ) { $w[] = 'i.active = 1'; }
	elseif ( 0 === (int) $a['active'] ) { $w[] = 'i.active = 0'; }
	$cho_q = '';
	if ( '' !== $a['search'] && md_inv_is_choseong_query( $a['search'] ) ) {
		$cho_q = preg_replace( '/\s+/u', '', $a['search'] ); /* 초성은 PHP 에서 거른다 */
	} elseif ( '' !== $a['search'] ) {
		$like = '%' . $wpdb->esc_like( $a['search'] ) . '%';
		$w[] = '(i.name LIKE %s OR i.code LIKE %s OR i.barcode LIKE %s)';
		array_push( $p, $like, $like, $like );
	}
	if ( '' !== $a['barcode'] ) { $w[] = 'i.barcode = %s'; $p[] = $a['barcode']; }
	foreach ( array( 'vendor' => 'vendor_id', 'cat1' => 'cat1', 'cat2' => 'cat2', 'cat3' => 'cat3' ) as $k => $col ) {
		if ( (int) $a[ $k ] > 0 ) { $w[] = "i.$col = %d"; $p[] = (int) $a[ $k ]; }
	}
	if ( is_array( $a['ids'] ) ) {
		$ids = array_values( array_filter( array_map( 'intval', $a['ids'] ) ) );
		if ( ! $ids ) { return array(); }
		$w[] = 'i.id IN (' . implode( ',', $ids ) . ')';
	}

	$sql = "SELECT i.*,
	               COALESCE(l.stock,0) AS stock,
	               COALESCE(r.pend,0)  AS pend,
	               COALESCE(o.onord,0) AS onord
	        FROM {$t['item']} i
	        LEFT JOIN (SELECT item_id, SUM(qty) AS stock FROM {$t['ledger']} WHERE voided = 0 GROUP BY item_id) l ON l.item_id = i.id
	        LEFT JOIN (SELECT item_id, SUM(qty) AS pend FROM {$t['req']} WHERE status = 'pending' AND item_id > 0 GROUP BY item_id) r ON r.item_id = i.id
	        LEFT JOIN (SELECT item_id, SUM(qty - recv_qty) AS onord FROM {$t['ord']} WHERE status = 'ordered' GROUP BY item_id) o ON o.item_id = i.id
	        WHERE " . implode( ' AND ', $w ) . '
	        ORDER BY i.name ASC, i.id ASC';
	if ( $p ) { $sql = $wpdb->prepare( $sql, $p ); }
	$rows = $wpdb->get_results( $sql );
	foreach ( $rows as $r ) {
		$r->stock = (int) $r->stock; $r->pend = (int) $r->pend; $r->onord = (int) $r->onord;
		$r->price = (int) $r->price; $r->min_stock = (int) $r->min_stock;
	}
	if ( '' !== $cho_q ) {
		$rows = array_values( array_filter( $rows, function ( $r ) use ( $cho_q ) {
			return false !== mb_strpos( preg_replace( '/\s+/u', '', md_inv_choseong( $r->name ) ), $cho_q );
		} ) );
	}
	return $rows;
}

/* ============================================================
 * 6 · 안전재고 제안 — 최근 N주 출고량으로
 * ============================================================ */

/**
 * @return array [ item_id => (object) { weekly, suggest } ] 최근 12주에 출고된 품목만
 */
function md_inv_min_suggestions() {
	global $wpdb;
	$weeks = 12;
	$cover = max( 1, (int) md_inv_set( 'min_cover_weeks' ) );
	$since = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $weeks * WEEK_IN_SECONDS );
	$rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT item_id, SUM(-qty) AS q FROM ' . md_inv_t( 'ledger' ) . " WHERE type = 'out' AND voided = 0 AND created_at >= %s GROUP BY item_id", $since ) );
	$out = array();
	foreach ( $rows as $r ) {
		$wk = (int) $r->q / $weeks;
		if ( $wk <= 0 ) { continue; }
		$out[ (int) $r->item_id ] = (object) array( 'weekly' => round( $wk, 2 ), 'used' => (int) $r->q, 'suggest' => (int) max( 1, ceil( $wk * $cover ) ) );
	}
	return $out;
}

/* ============================================================
 * 7 · 중복 품목 — 같은 업체 · 같은 이름
 * ============================================================ */

function md_inv_duplicate_groups() {
	$g = array();
	foreach ( md_inv_items( array( 'active' => -1 ) ) as $it ) {
		$k = (int) $it->vendor_id . '|' . mb_strtolower( preg_replace( '/\s+/u', ' ', trim( $it->name ) ) );
		$g[ $k ][] = $it;
	}
	return array_values( array_filter( $g, function ( $x ) { return count( $x ) > 1; } ) );
}

/**
 * 품목 합치기 — $drop 의 기록(원장 · 신청 · 주문)을 $keep 으로 옮기고 $drop 을 지운다.
 * 비어 있는 칸(단가 · 단위 · 바코드 · 안전재고 · 분류)은 지울 품목의 값으로 채운다.
 */
function md_inv_item_merge( $keep, $drop ) {
	global $wpdb;
	$t = md_inv_t();
	$k = md_inv_item( $keep );
	$d = md_inv_item( $drop );
	if ( ! $k || ! $d || (int) $k->id === (int) $d->id ) { return new WP_Error( 'item', '합칠 두 품목을 골라 주세요.' ); }
	md_inv_lock();
	md_inv_begin();
	foreach ( array( 'ledger', 'req', 'ord' ) as $tb ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$t[$tb]} SET item_id = %d WHERE item_id = %d", (int) $k->id, (int) $d->id ) );
	}
	$fill = array();
	if ( ! $k->price && $d->price ) { $fill['price'] = (int) $d->price; }
	if ( '' === trim( (string) $k->unit ) && '' !== trim( (string) $d->unit ) ) { $fill['unit'] = $d->unit; }
	if ( ! $k->min_stock && $d->min_stock ) { $fill['min_stock'] = (int) $d->min_stock; }
	if ( ! (int) $k->cat2 && (int) $d->cat2 ) { $fill['cat1'] = (int) $d->cat1; $fill['cat2'] = (int) $d->cat2; $fill['cat3'] = (int) $d->cat3; }
	if ( '' === $k->barcode && '' !== $d->barcode ) { $wpdb->update( $t['item'], array( 'barcode' => '' ), array( 'id' => (int) $d->id ) ); $fill['barcode'] = $d->barcode; }
	if ( '' !== trim( (string) $d->note ) ) { $fill['note'] = trim( (string) $k->note . "\n" . $d->note ); }
	if ( ! (int) $k->active && (int) $d->active ) { $fill['active'] = 1; }
	if ( $fill ) { $fill['updated_at'] = current_time( 'mysql' ); $wpdb->update( $t['item'], $fill, array( 'id' => (int) $k->id ) ); }
	$wpdb->delete( $t['item'], array( 'id' => (int) $d->id ) );
	md_inv_commit();
	md_inv_unlock();
	md_inv_log( '품목 합치기', $d->name . ' (#' . $d->id . ') → #' . $k->id );
	return true;
}

/* ============================================================
 * 5 · 월말 업체 정산 — 한 달 입고 · 반품 내역
 * ============================================================ */

/** @return array [ vendor_id => [ 줄 … ] ] 줄 = 원장 행 (입고 · 반품, 취소 아님) */
function md_inv_settlement( $ym ) {
	if ( ! preg_match( '/^\d{4}-\d{2}$/', (string) $ym ) ) { $ym = date( 'Y-m', strtotime( '-1 month', current_time( 'timestamp' ) ) ); }
	$from = $ym . '-01';
	$to   = date( 'Y-m-t', strtotime( $from ) );
	$rows = md_inv_ledger( array( 'type' => 'in,return', 'from' => $from, 'to' => $to, 'limit' => 0, 'with_void' => 0 ) );
	$out  = array();
	foreach ( array_reverse( $rows ) as $l ) { $out[ (int) $l->vendor_id ][] = $l; }
	uksort( $out, function ( $a, $b ) { return strcmp( md_inv_vendor_name( $a ), md_inv_vendor_name( $b ) ); } );
	return $out;
}

function md_inv_item( $id ) {
	$r = md_inv_items( array( 'ids' => array( (int) $id ), 'active' => -1 ) );
	return $r ? $r[0] : null;
}

function md_inv_stock( $item_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(qty),0) FROM ' . md_inv_t( 'ledger' ) . ' WHERE item_id = %d AND voided = 0', (int) $item_id ) );
}

/** 재고 상태: out 품절 · low 부족 · ok */
function md_inv_stock_state( $it ) {
	if ( (int) $it->stock <= 0 ) { return 'out'; }
	if ( (int) $it->min_stock > 0 && (int) $it->stock < (int) $it->min_stock ) { return 'low'; }
	return 'ok';
}

/** 품목이 선납 업체 것인가 */
function md_inv_item_prepaid( $it ) {
	$v = md_inv_vendor( $it->vendor_id );
	return $v && (int) $v->prepaid === 1;
}

/** 분류 경로 「품목군 › 세부 분류」 */
function md_inv_item_catpath( $it, $with1 = false ) {
	$parts = array();
	if ( $with1 && $it->cat1 ) { $parts[] = md_inv_cat_name( $it->cat1 ); }
	if ( $it->cat2 ) { $parts[] = md_inv_cat_name( $it->cat2 ); }
	if ( $it->cat3 ) { $parts[] = md_inv_cat_name( $it->cat3 ); }
	return implode( ' › ', array_filter( $parts ) );
}

/** 다음 품목 코드 (M0723 …) */
function md_inv_next_code() {
	global $wpdb;
	$codes = $wpdb->get_col( 'SELECT code FROM ' . md_inv_t( 'item' ) . " WHERE code LIKE 'M%'" );
	$max = 0;
	foreach ( (array) $codes as $c ) { if ( preg_match( '/^M(\d{1,6})$/', $c, $m ) ) { $max = max( $max, (int) $m[1] ); } }
	return 'M' . str_pad( (string) ( $max + 1 ), 4, '0', STR_PAD_LEFT );
}

/**
 * 품목 저장. $id 0 이면 새로.
 * 같은 업체 안에 같은 이름이 있으면 막는다 (AppSheet 93차 규칙).
 */
function md_inv_item_save( $id, $d ) {
	global $wpdb;
	$t   = md_inv_t( 'item' );
	$id  = (int) $id;
	$row = array(
		'name'      => md_inv_txt( isset( $d['name'] ) ? $d['name'] : '', 255 ),
		'vendor_id' => (int) ( isset( $d['vendor_id'] ) ? $d['vendor_id'] : 0 ),
		'unit'      => md_inv_txt( isset( $d['unit'] ) ? $d['unit'] : '', 30 ),
		'price'     => max( 0, md_inv_int( isset( $d['price'] ) ? $d['price'] : 0 ) ),
		'cat1'      => (int) ( isset( $d['cat1'] ) ? $d['cat1'] : 0 ),
		'cat2'      => (int) ( isset( $d['cat2'] ) ? $d['cat2'] : 0 ),
		'cat3'      => (int) ( isset( $d['cat3'] ) ? $d['cat3'] : 0 ),
		'min_stock' => max( 0, md_inv_int( isset( $d['min_stock'] ) ? $d['min_stock'] : 0 ) ),
		'barcode'   => md_inv_txt( isset( $d['barcode'] ) ? $d['barcode'] : '', 100 ),
		'note'      => md_inv_mtxt( isset( $d['note'] ) ? $d['note'] : '', 2000 ),
		'updated_at'=> current_time( 'mysql' ),
	);
	if ( '' === $row['name'] ) { return new WP_Error( 'name', '품목명을 적어 주세요.' ); }
	if ( $row['vendor_id'] && ! md_inv_vendor( $row['vendor_id'] ) ) { return new WP_Error( 'vendor', '업체를 다시 골라 주세요.' ); }

	/* 분류는 위아래가 맞아야 한다 — 세부 분류가 다른 품목군 것이면 비운다 */
	$c3 = $row['cat3'] ? md_inv_cat( $row['cat3'] ) : null;
	if ( $c3 ) { $row['cat2'] = (int) $c3->parent_id; } elseif ( $row['cat3'] ) { $row['cat3'] = 0; }
	$c2 = $row['cat2'] ? md_inv_cat( $row['cat2'] ) : null;
	if ( $c2 ) { $row['cat1'] = (int) $c2->parent_id; } elseif ( $row['cat2'] ) { $row['cat2'] = 0; }
	if ( $row['cat1'] && ! md_inv_cat( $row['cat1'] ) ) { $row['cat1'] = 0; }

	/* 이름 · 업체를 그대로 두고 고칠 때는 검사하지 않는다 — AppSheet 에서 넘어온 중복 품목도 단가·바코드를 고칠 수 있게 (94차 규칙) */
	$before = $id > 0 ? md_inv_item( $id ) : null;
	$same   = $before && $before->name === $row['name'] && (int) $before->vendor_id === $row['vendor_id'];
	$dup    = $same ? 0 : $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE name = %s AND vendor_id = %d AND id <> %d AND active = 1 LIMIT 1", $row['name'], $row['vendor_id'], $id ) );
	if ( $dup ) { return new WP_Error( 'dup', '같은 업체에 같은 이름의 품목이 이미 있습니다 — ' . $row['name'] ); }

	if ( '' !== $row['barcode'] ) {
		$bdup = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $t WHERE barcode = %s AND id <> %d LIMIT 1", $row['barcode'], $id ) );
		if ( $bdup ) { return new WP_Error( 'barcode', '이 바코드는 이미 「' . $bdup . '」에 등록돼 있습니다.' ); }
	}

	if ( $id > 0 ) {
		if ( ! md_inv_item( $id ) ) { return new WP_Error( 'gone', '품목을 찾을 수 없습니다.' ); }
		$wpdb->update( $t, $row, array( 'id' => $id ) );
		md_inv_log( '품목 수정', $row['name'] );
		return $id;
	}

	$row['code']       = md_inv_txt( isset( $d['code'] ) && '' !== trim( (string) $d['code'] ) ? $d['code'] : md_inv_next_code(), 40 );
	$row['active']     = 1;
	$row['created_at'] = current_time( 'mysql' );
	$row['created_by'] = get_current_user_id();
	$wpdb->insert( $t, $row );
	$new = (int) $wpdb->insert_id;
	if ( ! $new ) { return new WP_Error( 'db', '품목을 저장하지 못했습니다.' ); }
	md_inv_log( '품목 등록', $row['name'] );

	/* 처음 수량을 같이 적었으면 원장에 「기초」로 남긴다 */
	$open = isset( $d['open_qty'] ) ? md_inv_int( $d['open_qty'] ) : 0;
	if ( $open > 0 ) { md_inv_ledger_add( 'open', $new, $open, array( 'note' => '처음 수량' ) ); }
	return $new;
}

/** 숨기기 / 다시 보이기 — 기록은 그대로 남는다 */
function md_inv_item_set_active( $id, $on ) {
	global $wpdb;
	$it = md_inv_item( $id );
	if ( ! $it ) { return new WP_Error( 'gone', '품목을 찾을 수 없습니다.' ); }
	$wpdb->update( md_inv_t( 'item' ), array( 'active' => $on ? 1 : 0, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	md_inv_log( $on ? '품목 다시 보이기' : '품목 숨기기', $it->name );
	return true;
}

/** 품목에 딸린 기록 수 */
function md_inv_item_refs( $id ) {
	global $wpdb;
	$t = md_inv_t();
	$id = (int) $id;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['ledger']} WHERE item_id = %d", $id ) )
		+ (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['req']} WHERE item_id = %d", $id ) )
		+ (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['ord']} WHERE item_id = %d", $id ) );
}

/** 기록이 하나도 없는 품목만 완전히 지운다. 있으면 숨기기를 안내한다. */
function md_inv_item_delete( $id ) {
	global $wpdb;
	$it = md_inv_item( $id );
	if ( ! $it ) { return new WP_Error( 'gone', '품목을 찾을 수 없습니다.' ); }
	$n = md_inv_item_refs( $id );
	if ( $n > 0 ) { return new WP_Error( 'refs', '이 품목에는 기록이 ' . $n . '건 있어 지울 수 없습니다. 대신 「숨기기」를 써 주세요.' ); }
	$wpdb->delete( md_inv_t( 'item' ), array( 'id' => (int) $id ) );
	md_inv_log( '품목 삭제', $it->name );
	return true;
}

/* ============================================================
 * 원장 — 모든 수량 변화는 여기를 지난다
 * ============================================================ */

/**
 * 원장에 한 줄.
 *
 * @param string $type  open | in | out | return | adjust
 * @param int    $item_id
 * @param int    $qty   open·in·out·return 은 양수로 받는다 (부호는 여기서 붙인다).
 *                      adjust 는 「센 수량」을 받아 차이를 계산한다.
 * @param array  $o     team_id, req_id, ord_id, ref_id, free, person, note, price, created_at
 * @return int|WP_Error 기록 id
 */
function md_inv_ledger_add( $type, $item_id, $qty, $o = array() ) {
	global $wpdb;
	$types = array( 'open', 'in', 'out', 'return', 'adjust' );
	if ( ! in_array( $type, $types, true ) ) { return new WP_Error( 'type', '알 수 없는 기록 종류입니다.' ); }
	$it = md_inv_item( $item_id );
	if ( ! $it ) { return new WP_Error( 'item', '품목을 찾을 수 없습니다.' ); }

	$qty = (int) $qty;
	$counted = null;
	if ( 'adjust' === $type ) {
		if ( $qty < 0 ) { return new WP_Error( 'qty', '센 수량은 0 이상이어야 합니다.' ); }
		$counted = $qty;
		$signed  = $qty - md_inv_stock( $item_id );
	} else {
		if ( $qty <= 0 ) { return new WP_Error( 'qty', '수량은 1 이상이어야 합니다.' ); }
		$signed = in_array( $type, array( 'out', 'return' ), true ) ? -$qty : $qty;
	}

	/* 단가 칸을 비워 두면 품목 단가 (빈 문자열을 0원으로 읽으면 선납 차감·통계가 0이 된다) */
	$price = ( isset( $o['price'] ) && '' !== trim( (string) $o['price'] ) ) ? max( 0, md_inv_int( $o['price'] ) ) : (int) $it->price;
	$ok = $wpdb->insert( md_inv_t( 'ledger' ), array(
		'item_id'    => (int) $item_id,
		'type'       => $type,
		'qty'        => $signed,
		'price'      => $price,
		'vendor_id'  => (int) $it->vendor_id,
		'team_id'    => isset( $o['team_id'] ) ? (int) $o['team_id'] : 0,
		'req_id'     => isset( $o['req_id'] ) ? (int) $o['req_id'] : 0,
		'ord_id'     => isset( $o['ord_id'] ) ? (int) $o['ord_id'] : 0,
		'ref_id'     => isset( $o['ref_id'] ) ? (int) $o['ref_id'] : 0,
		'counted'    => $counted,
		'free'       => ! empty( $o['free'] ) ? 1 : 0,
		'person'     => mb_substr( isset( $o['person'] ) ? (string) $o['person'] : md_inv_me(), 0, 100 ),
		'note'       => mb_substr( isset( $o['note'] ) ? (string) $o['note'] : '', 0, 500 ),
		'user_id'    => get_current_user_id(),
		'created_at' => isset( $o['created_at'] ) ? $o['created_at'] : current_time( 'mysql' ),
	) );
	if ( ! $ok ) { return new WP_Error( 'db', '기록을 저장하지 못했습니다.' ); }
	return (int) $wpdb->insert_id;
}

/** 원장 한 줄 */
function md_inv_ledger_row( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_inv_t( 'ledger' ) . ' WHERE id = %d', (int) $id ) );
}

/**
 * 원장 조회.
 * @param array $a item_id, team_id, vendor_id, type, from, to, search, limit, offset, with_void
 */
function md_inv_ledger( $a = array(), $count_only = false ) {
	global $wpdb;
	$t = md_inv_t();
	$a = wp_parse_args( $a, array( 'item_id' => 0, 'team_id' => 0, 'vendor_id' => 0, 'type' => '', 'from' => '', 'to' => '', 'search' => '', 'limit' => 100, 'offset' => 0, 'with_void' => 1, 'ord_id' => 0, 'req_id' => 0 ) );
	$w = array( '1=1' );
	$p = array();
	if ( ! $a['with_void'] ) { $w[] = 'l.voided = 0'; }
	foreach ( array( 'item_id', 'team_id', 'vendor_id', 'ord_id', 'req_id' ) as $k ) {
		if ( (int) $a[ $k ] > 0 ) { $w[] = "l.$k = %d"; $p[] = (int) $a[ $k ]; }
	}
	if ( '' !== $a['type'] ) {
		$types = array_intersect( explode( ',', $a['type'] ), array( 'open', 'in', 'out', 'return', 'adjust' ) );
		if ( $types ) { $w[] = "l.type IN ('" . implode( "','", $types ) . "')"; }
	}
	if ( '' !== $a['from'] ) { $w[] = 'l.created_at >= %s'; $p[] = $a['from'] . ' 00:00:00'; }
	if ( '' !== $a['to'] )   { $w[] = 'l.created_at <= %s'; $p[] = $a['to'] . ' 23:59:59'; }
	if ( '' !== $a['search'] ) {
		$like = '%' . $wpdb->esc_like( $a['search'] ) . '%';
		$w[] = '(i.name LIKE %s OR l.note LIKE %s OR l.person LIKE %s)';
		array_push( $p, $like, $like, $like );
	}
	$where = implode( ' AND ', $w );
	if ( $count_only ) {
		$sql = "SELECT COUNT(*) FROM {$t['ledger']} l LEFT JOIN {$t['item']} i ON i.id = l.item_id WHERE $where";
		return (int) $wpdb->get_var( $p ? $wpdb->prepare( $sql, $p ) : $sql );
	}
	$sql = "SELECT l.*, COALESCE(i.name,'') AS item_name, COALESCE(i.unit,'') AS unit, COALESCE(i.code,'') AS item_code
	        FROM {$t['ledger']} l LEFT JOIN {$t['item']} i ON i.id = l.item_id
	        WHERE $where ORDER BY l.created_at DESC, l.id DESC";
	if ( (int) $a['limit'] > 0 ) { $sql .= ' LIMIT ' . (int) $a['limit'] . ' OFFSET ' . max( 0, (int) $a['offset'] ); }
	return $wpdb->get_results( $p ? $wpdb->prepare( $sql, $p ) : $sql );
}

/**
 * 기록 취소 — 지우지 않고 「취소됨」으로 표시해 합계에서 뺀다.
 * 출고 기록을 취소하면 연결된 요청은 다시 대기로, 입고 기록이면 주문 입고 수량을 되돌린다.
 */
function md_inv_ledger_void( $id, $why = '' ) {
	global $wpdb;
	$t = md_inv_t();
	$l = md_inv_ledger_row( $id );
	if ( ! $l ) { return new WP_Error( 'gone', '기록을 찾을 수 없습니다.' ); }
	if ( (int) $l->voided ) { return new WP_Error( 'done', '이미 취소된 기록입니다.' ); }

	/* 이 입고를 근거로 한 반품이 살아 있으면 먼저 그 반품을 취소해야 한다 */
	if ( 'in' === $l->type ) {
		$ret = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['ledger']} WHERE ref_id = %d AND type = 'return' AND voided = 0", $l->id ) );
		if ( $ret ) { return new WP_Error( 'ret', '이 입고에 연결된 반품 기록이 있습니다. 반품을 먼저 취소해 주세요.' ); }
		/* 취소하면 재고가 음수가 되는가 */
		if ( ! md_inv_set( 'out_allow_negative' ) && md_inv_stock( $l->item_id ) - (int) $l->qty < 0 ) {
			return new WP_Error( 'neg', '이 입고를 취소하면 재고가 0 아래로 내려갑니다. 그 사이 출고된 기록을 먼저 확인해 주세요.' );
		}
	}

	md_inv_lock();
	md_inv_begin();
	$n = $wpdb->query( $wpdb->prepare( "UPDATE {$t['ledger']} SET voided = 1, void_note = %s WHERE id = %d AND voided = 0", mb_substr( md_inv_me() . ' · ' . $why, 0, 255 ), $l->id ) );
	if ( ! $n ) { md_inv_rollback(); md_inv_unlock(); return new WP_Error( 'race', '이미 처리된 기록입니다.' ); }

	if ( 'out' === $l->type && (int) $l->req_id ) {
		/* 그 요청을 다시 대기로 — 같은 요청의 다른 출고가 남아 있지 않을 때만 */
		$left = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['ledger']} WHERE req_id = %d AND type = 'out' AND voided = 0", $l->req_id ) );
		if ( 0 === $left ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$t['req']} SET status = 'pending', qty_out = 0, done_at = NULL, done_by = '' WHERE id = %d AND status = 'done'", $l->req_id ) );
		}
	}
	if ( 'in' === $l->type && (int) $l->ord_id ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$t['ord']} SET recv_qty = CASE WHEN recv_qty > %d THEN recv_qty - %d ELSE 0 END, status = 'ordered', received_at = NULL WHERE id = %d AND status <> 'cancelled'", (int) $l->qty, (int) $l->qty, $l->ord_id ) );
	}
	md_inv_commit();
	md_inv_unlock();
	$it = md_inv_item( $l->item_id );
	md_inv_log( '기록 취소', md_inv_type_label( $l->type ) . ' · ' . ( $it ? $it->name : '#' . $l->item_id ) . ' · ' . $l->qty . ( '' !== $why ? ' · ' . $why : '' ) );
	return true;
}

/** 입고 기록 → 반품할 수 있는 수량 (원 입고 − 이미 반품) */
function md_inv_returnable( $in_id ) {
	global $wpdb;
	$l = md_inv_ledger_row( $in_id );
	if ( ! $l || 'in' !== $l->type || (int) $l->voided ) { return 0; }
	$done = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(-qty),0) FROM ' . md_inv_t( 'ledger' ) . " WHERE ref_id = %d AND type = 'return' AND voided = 0", (int) $in_id ) );
	return max( 0, (int) $l->qty - $done );
}

/* ---- 재고를 움직이는 일 (관리자) ---------------------------- */

/** 입고 (주문 없이) */
function md_inv_do_in( $item_id, $qty, $o = array() ) {
	md_inv_lock();
	$r = md_inv_ledger_add( 'in', $item_id, $qty, $o );
	md_inv_unlock();
	if ( ! is_wp_error( $r ) ) { $it = md_inv_item( $item_id ); md_inv_log( '입고', $it->name . ' +' . (int) $qty ); }
	return $r;
}

/** 출고 (요청 없이 바로 — 예: 직접 건네줌) */
function md_inv_do_out( $item_id, $qty, $o = array() ) {
	if ( empty( $o['team_id'] ) ) { return new WP_Error( 'team', '어느 팀에 출고하는지 골라 주세요.' ); }
	md_inv_lock();
	$stock = md_inv_stock( $item_id );
	if ( ! md_inv_set( 'out_allow_negative' ) && (int) $qty > $stock ) {
		md_inv_unlock();
		return new WP_Error( 'short', '재고가 ' . $stock . '개뿐이라 ' . (int) $qty . '개를 출고할 수 없습니다.' );
	}
	$r = md_inv_ledger_add( 'out', $item_id, $qty, $o );
	md_inv_unlock();
	if ( ! is_wp_error( $r ) ) { $it = md_inv_item( $item_id ); md_inv_log( '출고', $it->name . ' −' . (int) $qty . ' · ' . md_inv_team_name( $o['team_id'] ) ); }
	return $r;
}

/** 실사 — 센 수량으로 맞춘다 */
function md_inv_do_adjust( $item_id, $counted, $note = '' ) {
	if ( md_inv_set( 'adjust_need_note' ) && '' === trim( (string) $note ) ) {
		return new WP_Error( 'note', '실사 사유(예: 정기 실사, 파손)를 적어 주세요.' );
	}
	md_inv_lock();
	$before = md_inv_stock( $item_id );
	$r = md_inv_ledger_add( 'adjust', $item_id, (int) $counted, array( 'note' => $note ) );
	md_inv_unlock();
	if ( ! is_wp_error( $r ) ) { $it = md_inv_item( $item_id ); md_inv_log( '실사', $it->name . ' ' . $before . ' → ' . (int) $counted ); }
	return $r;
}

/** 반품 (업체로 돌려보냄) — 원 입고 기록 기준 */
function md_inv_do_return( $in_id, $qty, $note = '', $free = null ) {
	$l = md_inv_ledger_row( $in_id );
	if ( ! $l || 'in' !== $l->type || (int) $l->voided ) { return new WP_Error( 'src', '반품할 입고 기록을 찾을 수 없습니다.' ); }
	if ( '' === trim( (string) $note ) ) { return new WP_Error( 'note', '반품 사유를 적어 주세요.' ); }
	md_inv_lock();
	$can   = md_inv_returnable( $in_id );
	$stock = md_inv_stock( $l->item_id );
	if ( (int) $qty > $can ) { md_inv_unlock(); return new WP_Error( 'qty', '이 입고에서 반품할 수 있는 수량은 ' . $can . '개입니다.' ); }
	if ( (int) $qty > $stock ) { md_inv_unlock(); return new WP_Error( 'stock', '지금 재고가 ' . $stock . '개라 ' . (int) $qty . '개를 반품할 수 없습니다.' ); }
	$r = md_inv_ledger_add( 'return', $l->item_id, $qty, array(
		'ref_id' => $l->id, 'ord_id' => $l->ord_id, 'price' => $l->price,
		'free'   => null === $free ? (int) $l->free : ( $free ? 1 : 0 ),
		'note'   => $note,
	) );
	md_inv_unlock();
	if ( ! is_wp_error( $r ) ) { $it = md_inv_item( $l->item_id ); md_inv_log( '반품', $it->name . ' −' . (int) $qty . ' · ' . $note ); }
	return $r;
}

/* ============================================================
 * 요청 — 1건 = 품목 1개
 * ============================================================ */

/**
 * 장바구니를 보낸다.
 * @param array $lines [ ['item_id'=>, 'qty'=>, 'note'=>] | ['custom'=>[name,vendor,unit,price,link], 'qty'=>, 'note'=>] ]
 * @return array|WP_Error 만든 요청 id 목록
 */
function md_inv_req_create( $team_id, $requester, $lines, $note = '', $urgent = 0 ) {
	global $wpdb;
	$team_id   = (int) $team_id;
	$requester = md_inv_txt( $requester, 100 );
	$valid_team = false;
	foreach ( md_inv_teams() as $tm ) { if ( (int) $tm->id === $team_id ) { $valid_team = true; } }
	if ( ! $valid_team ) { return new WP_Error( 'team', '팀을 골라 주세요.' ); }
	if ( md_inv_set( 'req_need_name' ) && '' === $requester ) { return new WP_Error( 'name', '요청자 이름을 적어 주세요.' ); }
	if ( ! is_array( $lines ) || ! $lines ) { return new WP_Error( 'empty', '담긴 품목이 없습니다.' ); }
	if ( count( $lines ) > 100 ) { return new WP_Error( 'many', '한 번에 100개 품목까지 보낼 수 있습니다.' ); }

	$max   = (int) md_inv_set( 'req_max_qty' );
	$rows  = array();
	$seen  = array();
	foreach ( $lines as $ln ) {
		$qty = isset( $ln['qty'] ) ? (int) $ln['qty'] : 0;
		if ( $qty < 1 ) { return new WP_Error( 'qty', '수량은 1 이상이어야 합니다.' ); }
		if ( $qty > $max ) { return new WP_Error( 'qty', '한 품목은 ' . $max . '개까지 요청할 수 있습니다.' ); }
		$r = array( 'qty' => $qty, 'note' => md_inv_txt( isset( $ln['note'] ) ? $ln['note'] : '', 500 ) );
		if ( ! empty( $ln['item_id'] ) ) {
			$it = md_inv_item( (int) $ln['item_id'] );
			if ( ! $it || ! (int) $it->active ) { return new WP_Error( 'item', '목록에서 사라진 품목이 담겨 있습니다. 장바구니를 확인해 주세요.' ); }
			if ( isset( $seen[ $it->id ] ) ) { $rows[ $seen[ $it->id ] ]['qty'] += $qty; continue; }
			$r['item_id'] = (int) $it->id;
			$seen[ $it->id ] = count( $rows );
		} else {
			if ( ! md_inv_set( 'req_allow_custom' ) ) { return new WP_Error( 'custom', '목록에 없는 품목 요청은 꺼져 있습니다.' ); }
			$c = isset( $ln['custom'] ) && is_array( $ln['custom'] ) ? $ln['custom'] : array();
			$r['item_id']       = 0;
			$r['custom_name']   = md_inv_txt( isset( $c['name'] ) ? $c['name'] : '', 255 );
			$r['custom_vendor'] = md_inv_txt( isset( $c['vendor'] ) ? $c['vendor'] : '', 150 );
			$r['custom_unit']   = md_inv_txt( isset( $c['unit'] ) ? $c['unit'] : '', 30 );
			$r['custom_price']  = max( 0, md_inv_int( isset( $c['price'] ) ? $c['price'] : 0 ) );
			$r['custom_link']   = mb_substr( esc_url_raw( isset( $c['link'] ) ? trim( (string) $c['link'] ) : '' ), 0, 500 );
			if ( '' === $r['custom_name'] ) { return new WP_Error( 'cname', '목록에 없는 품목의 이름을 적어 주세요.' ); }
		}
		$rows[] = $r;
	}

	$batch = substr( md5( uniqid( '', true ) ), 0, 10 );
	$now   = current_time( 'mysql' );
	$ids   = array();
	md_inv_begin();
	foreach ( $rows as $r ) {
		$ok = $wpdb->insert( md_inv_t( 'req' ), array_merge( array(
			'batch' => $batch, 'team_id' => $team_id, 'requester' => $requester,
			'status' => 'pending', 'urgent' => $urgent ? 1 : 0,
			'note' => '' !== $r['note'] ? $r['note'] : md_inv_txt( $note, 500 ),
			'user_id' => get_current_user_id(), 'created_at' => $now,
		), $r ) );
		if ( ! $ok ) { md_inv_rollback(); return new WP_Error( 'db', '요청을 저장하지 못했습니다. 다시 시도해 주세요.' ); }
		$ids[] = (int) $wpdb->insert_id;
	}
	md_inv_commit();
	md_inv_log( '요청', md_inv_team_name( $team_id ) . ' · ' . $requester . ' · ' . count( $ids ) . '건' );
	if ( function_exists( 'md_inv_notify_new' ) ) { md_inv_notify_new( $ids ); }
	return $ids;
}

function md_inv_req( $id ) {
	$r = md_inv_reqs( array( 'ids' => array( (int) $id ), 'limit' => 1, 'days' => 0 ) );
	return $r ? $r[0] : null;
}

/**
 * 요청 목록.
 * @param array $a status (쉼표), team_id, item_id, days, search, ids, limit, offset
 */
function md_inv_reqs( $a = array(), $count_only = false ) {
	global $wpdb;
	$t = md_inv_t();
	$a = wp_parse_args( $a, array( 'status' => '', 'team_id' => 0, 'item_id' => 0, 'days' => 0, 'search' => '', 'ids' => null, 'limit' => 200, 'offset' => 0, 'from' => '', 'to' => '', 'order' => 'new' ) );
	$w = array( '1=1' );
	$p = array();
	if ( '' !== $a['status'] ) {
		$st = array_intersect( explode( ',', $a['status'] ), array( 'pending', 'done', 'rejected', 'cancelled' ) );
		if ( $st ) { $w[] = "r.status IN ('" . implode( "','", $st ) . "')"; }
	}
	if ( (int) $a['team_id'] > 0 ) { $w[] = 'r.team_id = %d'; $p[] = (int) $a['team_id']; }
	if ( (int) $a['item_id'] > 0 ) { $w[] = 'r.item_id = %d'; $p[] = (int) $a['item_id']; }
	if ( (int) $a['days'] > 0 ) { $w[] = 'r.created_at >= %s'; $p[] = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - (int) $a['days'] * DAY_IN_SECONDS ); }
	if ( '' !== $a['from'] ) { $w[] = 'r.created_at >= %s'; $p[] = $a['from'] . ' 00:00:00'; }
	if ( '' !== $a['to'] )   { $w[] = 'r.created_at <= %s'; $p[] = $a['to'] . ' 23:59:59'; }
	if ( '' !== $a['search'] ) {
		$like = '%' . $wpdb->esc_like( $a['search'] ) . '%';
		$w[] = '(i.name LIKE %s OR r.custom_name LIKE %s OR r.requester LIKE %s OR r.note LIKE %s)';
		array_push( $p, $like, $like, $like, $like );
	}
	if ( is_array( $a['ids'] ) ) {
		$ids = array_values( array_filter( array_map( 'intval', $a['ids'] ) ) );
		if ( ! $ids ) { return $count_only ? 0 : array(); }
		$w[] = 'r.id IN (' . implode( ',', $ids ) . ')';
	}
	$where = implode( ' AND ', $w );
	if ( $count_only ) {
		$sql = "SELECT COUNT(*) FROM {$t['req']} r LEFT JOIN {$t['item']} i ON i.id = r.item_id WHERE $where";
		return (int) $wpdb->get_var( $p ? $wpdb->prepare( $sql, $p ) : $sql );
	}
	$order = 'old' === $a['order'] ? 'r.urgent DESC, r.created_at ASC, r.id ASC' : 'r.created_at DESC, r.id DESC';
	$sql = "SELECT r.*, COALESCE(i.name,'') AS item_name, COALESCE(i.unit,'') AS unit, COALESCE(i.price,0) AS price,
	               COALESCE(i.vendor_id,0) AS vendor_id, COALESCE(i.active,1) AS item_active,
	               COALESCE(l.stock,0) AS stock,
	               COALESCE(o.onord,0) AS onord, COALESCE(o.ord_id,0) AS ord_id
	        FROM {$t['req']} r
	        LEFT JOIN {$t['item']} i ON i.id = r.item_id
	        LEFT JOIN (SELECT item_id, SUM(qty) AS stock FROM {$t['ledger']} WHERE voided = 0 GROUP BY item_id) l ON l.item_id = r.item_id
	        LEFT JOIN (SELECT req_id, SUM(qty - recv_qty) AS onord, MAX(id) AS ord_id FROM {$t['ord']} WHERE status = 'ordered' AND req_id > 0 GROUP BY req_id) o ON o.req_id = r.id
	        WHERE $where ORDER BY $order";
	if ( (int) $a['limit'] > 0 ) { $sql .= ' LIMIT ' . (int) $a['limit'] . ' OFFSET ' . max( 0, (int) $a['offset'] ); }
	$rows = $wpdb->get_results( $p ? $wpdb->prepare( $sql, $p ) : $sql );
	foreach ( $rows as $r ) {
		$r->name  = (int) $r->item_id ? $r->item_name : $r->custom_name;
		$r->stock = (int) $r->stock;
		$r->qty   = (int) $r->qty;
		$r->qty_out = (int) $r->qty_out;
		$r->price = (int) $r->item_id ? (int) $r->price : (int) $r->custom_price;
		if ( ! (int) $r->item_id ) { $r->unit = $r->custom_unit; }
	}
	return $rows;
}

/**
 * 출고 처리.
 *
 * 두 사람이 동시에 눌러도 한 번만 나간다 — 「대기인 것을 완료로」 한 문장으로 선점한다.
 * 재고보다 많이 내줄 수는 없다(설정으로 허용 가능). 일부만 있으면 $split 로
 * 「있는 만큼 출고」하고 남은 수량은 새 대기 요청으로 남긴다 (AppSheet v3.3).
 *
 * @param int  $qty_out 실제로 내주는 수량
 * @param bool $split   요청보다 적게 내줄 때 남은 수량을 대기로 남길지
 */
function md_inv_req_release( $id, $qty_out, $split = true, $note = '' ) {
	global $wpdb;
	$t   = md_inv_t();
	$req = md_inv_req( $id );
	if ( ! $req ) { return new WP_Error( 'gone', '요청을 찾을 수 없습니다.' ); }
	if ( 'pending' !== $req->status ) { return new WP_Error( 'done', '이미 처리된 요청입니다 — ' . md_inv_req_status_label( $req->status ) ); }
	if ( ! (int) $req->item_id ) { return new WP_Error( 'custom', '목록에 없는 품목은 먼저 품목으로 등록하거나 「처리 완료」를 눌러 주세요.' ); }
	$qty_out = (int) $qty_out;
	if ( $qty_out < 1 ) { return new WP_Error( 'qty', '출고 수량은 1 이상이어야 합니다. 내줄 수 없으면 「반려」를 눌러 주세요.' ); }
	if ( $qty_out > $req->qty ) { return new WP_Error( 'qty', '요청 수량(' . $req->qty . ')보다 많이 출고할 수 없습니다. 수량을 늘리려면 요청 수량을 먼저 고쳐 주세요.' ); }

	if ( ! md_inv_lock() ) { return new WP_Error( 'busy', '다른 출고를 처리하는 중입니다. 잠시 뒤 다시 눌러 주세요.' ); }
	$stock = md_inv_stock( $req->item_id );
	if ( ! md_inv_set( 'out_allow_negative' ) && $qty_out > $stock ) {
		md_inv_unlock();
		return new WP_Error( 'short', '재고가 ' . $stock . '개뿐입니다. ' . ( $stock > 0 ? '「있는 만큼 출고」로 ' . $stock . '개만 내주거나 ' : '' ) . '주문해 주세요.' );
	}

	md_inv_begin();
	$me = md_inv_me();
	$claimed = $wpdb->query( $wpdb->prepare(
		"UPDATE {$t['req']} SET status = 'done', qty_out = %d, done_at = %s, done_by = %s, admin_note = %s WHERE id = %d AND status = 'pending'",
		$qty_out, current_time( 'mysql' ), $me, mb_substr( trim( $note ), 0, 500 ), (int) $id
	) );
	if ( ! $claimed ) { md_inv_rollback(); md_inv_unlock(); return new WP_Error( 'race', '방금 다른 곳에서 이 요청을 처리했습니다. 새로고침해 확인해 주세요.' ); }

	$lid = md_inv_ledger_add( 'out', $req->item_id, $qty_out, array(
		'team_id' => $req->team_id, 'req_id' => $req->id, 'person' => $me,
		'note'    => '요청 출고 · ' . $req->requester,
	) );
	if ( is_wp_error( $lid ) ) { md_inv_rollback(); md_inv_unlock(); return $lid; }

	$rest_id = 0;
	if ( $split && $qty_out < $req->qty ) {
		$wpdb->insert( $t['req'], array(
			'batch' => $req->batch, 'team_id' => $req->team_id, 'requester' => $req->requester,
			'item_id' => $req->item_id, 'qty' => $req->qty - $qty_out, 'status' => 'pending',
			'urgent' => $req->urgent, 'note' => $req->note, 'parent_id' => $req->id,
			'admin_note' => '남은 수량 (요청 ' . $req->qty . '개 중 ' . $qty_out . '개 먼저 출고)',
			'user_id' => $req->user_id, 'created_at' => $req->created_at,
		) );
		$rest_id = (int) $wpdb->insert_id;
	}
	md_inv_commit();
	md_inv_unlock();
	md_inv_log( '출고', $req->name . ' ' . $qty_out . ( $qty_out < $req->qty ? '/' . $req->qty : '' ) . ' · ' . md_inv_team_name( $req->team_id ) . ' · ' . $req->requester );
	return array( 'ledger' => $lid, 'rest' => $rest_id );
}

/** 목록에 없는 품목 요청 — 재고 기록 없이 「처리 완료」 (구매해서 바로 전달한 경우) */
function md_inv_req_complete_custom( $id, $note = '' ) {
	global $wpdb;
	$req = md_inv_req( $id );
	if ( ! $req || 'pending' !== $req->status ) { return new WP_Error( 'done', '대기 중인 요청이 아닙니다.' ); }
	$n = $wpdb->query( $wpdb->prepare(
		'UPDATE ' . md_inv_t( 'req' ) . " SET status = 'done', qty_out = qty, done_at = %s, done_by = %s, admin_note = %s WHERE id = %d AND status = 'pending'",
		current_time( 'mysql' ), md_inv_me(), mb_substr( trim( $note ), 0, 500 ), (int) $id
	) );
	if ( ! $n ) { return new WP_Error( 'race', '방금 처리된 요청입니다.' ); }
	md_inv_log( '처리 완료', $req->name . ' · ' . md_inv_team_name( $req->team_id ) );
	return true;
}

function md_inv_req_reject( $id, $reason ) {
	global $wpdb;
	$reason = md_inv_txt( $reason, 500 );
	if ( '' === $reason ) { return new WP_Error( 'reason', '반려 사유를 적어 주세요. 요청한 팀에 그대로 보입니다.' ); }
	$req = md_inv_req( $id );
	if ( ! $req ) { return new WP_Error( 'gone', '요청을 찾을 수 없습니다.' ); }
	$n = $wpdb->query( $wpdb->prepare(
		'UPDATE ' . md_inv_t( 'req' ) . " SET status = 'rejected', admin_note = %s, done_at = %s, done_by = %s WHERE id = %d AND status = 'pending'",
		$reason, current_time( 'mysql' ), md_inv_me(), (int) $id
	) );
	if ( ! $n ) { return new WP_Error( 'race', '이미 처리된 요청입니다.' ); }
	md_inv_log( '반려', $req->name . ' · ' . md_inv_team_name( $req->team_id ) . ' · ' . $reason );
	return true;
}

/** 취소 — 대기일 때만. 직원은 설정이 켜져 있을 때만. */
function md_inv_req_cancel( $id ) {
	global $wpdb;
	if ( ! md_inv_is_admin() && ! md_inv_set( 'staff_cancel' ) ) { return new WP_Error( 'perm', '요청 취소는 관리자에게 부탁해 주세요.' ); }
	$req = md_inv_req( $id );
	if ( ! $req ) { return new WP_Error( 'gone', '요청을 찾을 수 없습니다.' ); }
	$n = $wpdb->query( $wpdb->prepare(
		'UPDATE ' . md_inv_t( 'req' ) . " SET status = 'cancelled', done_at = %s, done_by = %s WHERE id = %d AND status = 'pending'",
		current_time( 'mysql' ), md_inv_is_admin() ? md_inv_me() : $req->requester, (int) $id
	) );
	if ( ! $n ) { return new WP_Error( 'race', '이미 처리된 요청은 취소할 수 없습니다 — ' . md_inv_req_status_label( $req->status ) ); }
	md_inv_log( '요청 취소', $req->name . ' · ' . md_inv_team_name( $req->team_id ) );
	return true;
}

/** 대기 중 요청 고치기 (관리자) — 수량 · 품목 연결 · 메모 */
function md_inv_req_update( $id, $d ) {
	global $wpdb;
	$req = md_inv_req( $id );
	if ( ! $req || 'pending' !== $req->status ) { return new WP_Error( 'done', '대기 중인 요청만 고칠 수 있습니다.' ); }
	$row = array();
	if ( isset( $d['qty'] ) ) {
		$q = (int) $d['qty'];
		if ( $q < 1 || $q > 99999 ) { return new WP_Error( 'qty', '수량을 확인해 주세요.' ); }
		$row['qty'] = $q;
	}
	if ( isset( $d['item_id'] ) && (int) $d['item_id'] > 0 ) {
		$it = md_inv_item( (int) $d['item_id'] );
		if ( ! $it ) { return new WP_Error( 'item', '품목을 찾을 수 없습니다.' ); }
		$row['item_id'] = (int) $it->id;
	}
	if ( isset( $d['team_id'] ) && (int) $d['team_id'] > 0 ) { $row['team_id'] = (int) $d['team_id']; }
	if ( isset( $d['admin_note'] ) ) { $row['admin_note'] = md_inv_txt( $d['admin_note'], 500 ); }
	if ( ! $row ) { return true; }
	$wpdb->update( md_inv_t( 'req' ), $row, array( 'id' => (int) $id, 'status' => 'pending' ) );
	md_inv_log( '요청 수정', $req->name . ' · ' . wp_json_encode( $row, JSON_UNESCAPED_UNICODE ) );
	return true;
}

/** 같은 팀 · 같은 품목의 대기 수량 (중복 요청 안내) [item_id => qty] */
function md_inv_pending_map_for_team( $team_id ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT item_id, SUM(qty) AS q FROM ' . md_inv_t( 'req' ) . " WHERE status = 'pending' AND team_id = %d AND item_id > 0 GROUP BY item_id", (int) $team_id ) );
	$m = array();
	foreach ( $rows as $r ) { $m[ (int) $r->item_id ] = (int) $r->q; }
	return $m;
}

/** 팀별 최근 요청 품목 [team_id => [item_id, …]] — 「우리 팀이 최근 요청한 품목」 */
function md_inv_recent_by_team( $days ) {
	global $wpdb;
	$since = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - (int) $days * DAY_IN_SECONDS );
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT team_id, item_id, COUNT(*) AS n FROM ' . md_inv_t( 'req' ) . " WHERE item_id > 0 AND status IN ('pending','done') AND created_at >= %s GROUP BY team_id, item_id ORDER BY n DESC", $since ) );
	$m = array();
	foreach ( $rows as $r ) { $m[ (int) $r->team_id ][] = (int) $r->item_id; }
	return $m;
}

/* ============================================================
 * 주문 — 1건 = 품목 1개
 * ============================================================ */

function md_inv_ord_create( $item_id, $qty, $d = array() ) {
	global $wpdb;
	$it = md_inv_item( $item_id );
	if ( ! $it ) { return new WP_Error( 'item', '품목을 찾을 수 없습니다.' ); }
	$qty = (int) $qty;
	if ( $qty < 1 || $qty > 99999 ) { return new WP_Error( 'qty', '주문 수량을 확인해 주세요.' ); }
	$price  = isset( $d['price'] ) && '' !== (string) $d['price'] ? max( 0, md_inv_int( $d['price'] ) ) : (int) $it->price;
	$amount = isset( $d['amount'] ) && '' !== (string) $d['amount'] ? max( 0, md_inv_int( $d['amount'] ) ) : $price * $qty;
	$req_id = isset( $d['req_id'] ) ? (int) $d['req_id'] : 0;

	/* 같은 요청으로 이미 주문해 둔 것이 있으면 또 만들지 않는다 (AppSheet 에서 한 요청에 주문 4건이 생긴 일) */
	if ( $req_id ) {
		$dup = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . md_inv_t( 'ord' ) . " WHERE req_id = %d AND status = 'ordered' LIMIT 1", $req_id ) );
		if ( $dup ) { return new WP_Error( 'dup', '이 요청으로 이미 주문해 둔 건이 있습니다 (주문 #' . $dup . ').' ); }
	}

	$ok = $wpdb->insert( md_inv_t( 'ord' ), array(
		'item_id' => (int) $it->id, 'vendor_id' => (int) $it->vendor_id, 'qty' => $qty, 'recv_qty' => 0,
		'price' => $price, 'amount' => $amount, 'status' => 'ordered', 'req_id' => $req_id,
		'note' => md_inv_txt( isset( $d['note'] ) ? $d['note'] : '', 500 ),
		'person' => md_inv_me(), 'user_id' => get_current_user_id(), 'created_at' => current_time( 'mysql' ),
	) );
	if ( ! $ok ) { return new WP_Error( 'db', '주문을 저장하지 못했습니다.' ); }
	$id = (int) $wpdb->insert_id;
	md_inv_log( '주문', $it->name . ' ' . $qty . ' · ' . md_inv_vendor_name( $it->vendor_id ) . ' · ' . md_inv_won( $amount ) );
	return $id;
}

function md_inv_ord( $id ) {
	$r = md_inv_ords( array( 'ids' => array( (int) $id ), 'limit' => 1 ) );
	return $r ? $r[0] : null;
}

function md_inv_ords( $a = array(), $count_only = false ) {
	global $wpdb;
	$t = md_inv_t();
	$a = wp_parse_args( $a, array( 'status' => '', 'vendor_id' => 0, 'item_id' => 0, 'ids' => null, 'search' => '', 'from' => '', 'to' => '', 'limit' => 200, 'offset' => 0 ) );
	$w = array( '1=1' );
	$p = array();
	if ( '' !== $a['status'] ) {
		$st = array_intersect( explode( ',', $a['status'] ), array( 'ordered', 'received', 'cancelled' ) );
		if ( $st ) { $w[] = "o.status IN ('" . implode( "','", $st ) . "')"; }
	}
	if ( (int) $a['vendor_id'] > 0 ) { $w[] = 'o.vendor_id = %d'; $p[] = (int) $a['vendor_id']; }
	if ( (int) $a['item_id'] > 0 ) { $w[] = 'o.item_id = %d'; $p[] = (int) $a['item_id']; }
	if ( '' !== $a['from'] ) { $w[] = 'o.created_at >= %s'; $p[] = $a['from'] . ' 00:00:00'; }
	if ( '' !== $a['to'] )   { $w[] = 'o.created_at <= %s'; $p[] = $a['to'] . ' 23:59:59'; }
	if ( '' !== $a['search'] ) {
		$like = '%' . $wpdb->esc_like( $a['search'] ) . '%';
		$w[] = '(i.name LIKE %s OR o.note LIKE %s)';
		array_push( $p, $like, $like );
	}
	if ( is_array( $a['ids'] ) ) {
		$ids = array_values( array_filter( array_map( 'intval', $a['ids'] ) ) );
		if ( ! $ids ) { return $count_only ? 0 : array(); }
		$w[] = 'o.id IN (' . implode( ',', $ids ) . ')';
	}
	$where = implode( ' AND ', $w );
	if ( $count_only ) {
		$sql = "SELECT COUNT(*) FROM {$t['ord']} o LEFT JOIN {$t['item']} i ON i.id = o.item_id WHERE $where";
		return (int) $wpdb->get_var( $p ? $wpdb->prepare( $sql, $p ) : $sql );
	}
	$sql = "SELECT o.*, COALESCE(i.name,'') AS item_name, COALESCE(i.unit,'') AS unit, COALESCE(l.stock,0) AS stock
	        FROM {$t['ord']} o
	        LEFT JOIN {$t['item']} i ON i.id = o.item_id
	        LEFT JOIN (SELECT item_id, SUM(qty) AS stock FROM {$t['ledger']} WHERE voided = 0 GROUP BY item_id) l ON l.item_id = o.item_id
	        WHERE $where ORDER BY (o.status = 'ordered') DESC, o.created_at DESC, o.id DESC";
	if ( (int) $a['limit'] > 0 ) { $sql .= ' LIMIT ' . (int) $a['limit'] . ' OFFSET ' . max( 0, (int) $a['offset'] ); }
	return $wpdb->get_results( $p ? $wpdb->prepare( $sql, $p ) : $sql );
}

/**
 * 입고 — 주문한 것이 도착했다.
 * 주문 수량보다 적게 오면 주문은 「주문함」으로 남고 남은 수량을 계속 기다린다.
 * 더 이상 오지 않을 거면 $close 로 닫는다.
 */
function md_inv_ord_receive( $id, $qty, $d = array() ) {
	global $wpdb;
	$t   = md_inv_t();
	$ord = md_inv_ord( $id );
	if ( ! $ord ) { return new WP_Error( 'gone', '주문을 찾을 수 없습니다.' ); }
	if ( 'ordered' !== $ord->status ) { return new WP_Error( 'done', '이미 ' . md_inv_ord_status_label( $ord->status ) . ' 된 주문입니다.' ); }
	$qty = (int) $qty;
	if ( $qty < 1 ) { return new WP_Error( 'qty', '들어온 수량을 적어 주세요.' ); }
	$left = (int) $ord->qty - (int) $ord->recv_qty;
	if ( $qty > $left && empty( $d['allow_more'] ) ) {
		return new WP_Error( 'more', '주문한 것보다 많습니다 (남은 수량 ' . $left . '개). 더 들어왔으면 「주문보다 많이 들어옴」을 체크해 주세요.' );
	}
	$price = isset( $d['price'] ) && '' !== (string) $d['price'] ? max( 0, md_inv_int( $d['price'] ) ) : (int) $ord->price;
	$free  = ! empty( $d['free'] );

	md_inv_lock();
	md_inv_begin();
	$new_recv = (int) $ord->recv_qty + $qty;
	$done     = $new_recv >= (int) $ord->qty || ! empty( $d['close'] );
	$n = $wpdb->query( $wpdb->prepare(
		"UPDATE {$t['ord']} SET recv_qty = %d, status = %s, received_at = %s WHERE id = %d AND status = 'ordered' AND recv_qty = %d",
		$new_recv, $done ? 'received' : 'ordered', current_time( 'mysql' ), (int) $id, (int) $ord->recv_qty
	) );
	if ( ! $n ) { md_inv_rollback(); md_inv_unlock(); return new WP_Error( 'race', '방금 다른 곳에서 이 주문을 처리했습니다. 새로고침해 주세요.' ); }
	$lid = md_inv_ledger_add( 'in', $ord->item_id, $qty, array(
		'ord_id' => $ord->id, 'price' => $price, 'free' => $free,
		'note'   => trim( '주문 입고' . ( ! empty( $d['note'] ) ? ' · ' . md_inv_txt( $d['note'], 300 ) : '' ) ),
	) );
	if ( is_wp_error( $lid ) ) { md_inv_rollback(); md_inv_unlock(); return $lid; }
	md_inv_commit();
	md_inv_unlock();
	md_inv_log( '입고', $ord->item_name . ' +' . $qty . ' (주문 #' . $ord->id . ( $free ? ', 무상' : '' ) . ')' );
	return $lid;
}

function md_inv_ord_cancel( $id, $why = '' ) {
	global $wpdb;
	$ord = md_inv_ord( $id );
	if ( ! $ord ) { return new WP_Error( 'gone', '주문을 찾을 수 없습니다.' ); }
	if ( 'ordered' !== $ord->status ) { return new WP_Error( 'done', '주문함 상태만 취소할 수 있습니다.' ); }
	/* 일부라도 들어왔으면 「취소」가 아니라 「여기서 마감」이다 */
	$status = (int) $ord->recv_qty > 0 ? 'received' : 'cancelled';
	$n = $wpdb->query( $wpdb->prepare(
		'UPDATE ' . md_inv_t( 'ord' ) . " SET status = %s, cancel_note = %s WHERE id = %d AND status = 'ordered'",
		$status, md_inv_txt( $why, 255 ), (int) $id
	) );
	if ( ! $n ) { return new WP_Error( 'race', '방금 처리된 주문입니다.' ); }
	md_inv_log( 'received' === $status ? '주문 마감' : '주문 취소', $ord->item_name . ' · ' . $why );
	return $status;
}

function md_inv_ord_update( $id, $d ) {
	global $wpdb;
	$ord = md_inv_ord( $id );
	if ( ! $ord || 'ordered' !== $ord->status ) { return new WP_Error( 'done', '주문함 상태만 고칠 수 있습니다.' ); }
	$row = array();
	if ( isset( $d['qty'] ) ) {
		$q = (int) $d['qty'];
		if ( $q < max( 1, (int) $ord->recv_qty ) ) { return new WP_Error( 'qty', '주문 수량은 이미 들어온 수량(' . (int) $ord->recv_qty . ')보다 적을 수 없습니다.' ); }
		$row['qty'] = $q;
	}
	if ( isset( $d['price'] ) )  { $row['price'] = max( 0, md_inv_int( $d['price'] ) ); }
	if ( isset( $d['amount'] ) ) { $row['amount'] = max( 0, md_inv_int( $d['amount'] ) ); }
	if ( isset( $d['note'] ) )   { $row['note'] = md_inv_txt( $d['note'], 500 ); }
	if ( $row ) { $wpdb->update( md_inv_t( 'ord' ), $row, array( 'id' => (int) $id ) ); md_inv_log( '주문 수정', $ord->item_name ); }
	return true;
}

/* ============================================================
 * 선납 (선불) 업체
 * ============================================================ */

function md_inv_deposit_add( $vendor_id, $amount, $paid_on, $note = '' ) {
	global $wpdb;
	$v = md_inv_vendor( $vendor_id );
	if ( ! $v || ! (int) $v->prepaid ) { return new WP_Error( 'vendor', '선납 업체를 골라 주세요.' ); }
	$amount = md_inv_int( $amount );
	if ( 0 === $amount ) { return new WP_Error( 'amount', '금액을 적어 주세요. 돌려받은 돈은 앞에 − 를 붙입니다.' ); }
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $paid_on ) ) { $paid_on = current_time( 'Y-m-d' ); }
	$wpdb->insert( md_inv_t( 'deposit' ), array(
		'vendor_id' => (int) $v->id, 'paid_on' => $paid_on, 'amount' => $amount,
		'note' => md_inv_txt( $note, 255 ), 'person' => md_inv_me(), 'user_id' => get_current_user_id(),
		'created_at' => current_time( 'mysql' ),
	) );
	md_inv_log( '선납 입금', $v->name . ' ' . md_inv_won( $amount ) );
	return (int) $wpdb->insert_id;
}

function md_inv_deposit_delete( $id ) {
	global $wpdb;
	$d = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_inv_t( 'deposit' ) . ' WHERE id = %d', (int) $id ) );
	if ( ! $d ) { return new WP_Error( 'gone', '입금 기록을 찾을 수 없습니다.' ); }
	$wpdb->delete( md_inv_t( 'deposit' ), array( 'id' => (int) $id ) );
	md_inv_log( '선납 입금 삭제', md_inv_vendor_name( $d->vendor_id ) . ' ' . md_inv_won( $d->amount ) );
	return true;
}

function md_inv_deposits( $vendor_id = 0 ) {
	global $wpdb;
	$sql = 'SELECT * FROM ' . md_inv_t( 'deposit' ) . ( $vendor_id ? $wpdb->prepare( ' WHERE vendor_id = %d', (int) $vendor_id ) : '' ) . ' ORDER BY paid_on DESC, id DESC';
	return $wpdb->get_results( $sql );
}

/**
 * 선납 현황 — 업체마다
 *   입금 · 차감(입고 금액, 무상 제외) · 환원(반품 금액, 무상 제외) · 잔액 · 주문 중 · 쓸 수 있는 잔액
 */
function md_inv_prepaid_summary() {
	global $wpdb;
	$t = md_inv_t();
	$out = array();
	foreach ( md_inv_vendors( false ) as $v ) {
		if ( ! (int) $v->prepaid ) { continue; }
		$out[ (int) $v->id ] = (object) array( 'vendor' => $v, 'deposit' => 0, 'spent' => 0, 'returned' => 0, 'pending' => 0, 'last_in' => '' );
	}
	if ( ! $out ) { return array(); }
	$in = implode( ',', array_keys( $out ) );
	foreach ( $wpdb->get_results( "SELECT vendor_id, SUM(amount) AS s FROM {$t['deposit']} WHERE vendor_id IN ($in) GROUP BY vendor_id" ) as $r ) {
		$out[ (int) $r->vendor_id ]->deposit = (int) $r->s;
	}
	foreach ( $wpdb->get_results( "SELECT vendor_id, type, SUM(ABS(qty) * price) AS s, MAX(created_at) AS last FROM {$t['ledger']}
	                               WHERE vendor_id IN ($in) AND voided = 0 AND free = 0 AND type IN ('in','return') GROUP BY vendor_id, type" ) as $r ) {
		if ( 'in' === $r->type ) { $out[ (int) $r->vendor_id ]->spent = (int) $r->s; $out[ (int) $r->vendor_id ]->last_in = $r->last; }
		else { $out[ (int) $r->vendor_id ]->returned = (int) $r->s; }
	}
	foreach ( $wpdb->get_results( "SELECT vendor_id, SUM(CASE WHEN qty > 0 THEN amount * (qty - recv_qty) / qty ELSE 0 END) AS s FROM {$t['ord']}
	                               WHERE vendor_id IN ($in) AND status = 'ordered' GROUP BY vendor_id" ) as $r ) {
		$out[ (int) $r->vendor_id ]->pending = (int) round( (float) $r->s );
	}
	foreach ( $out as $o ) {
		$o->balance   = $o->deposit - $o->spent + $o->returned;
		$o->available = $o->balance - $o->pending;
	}
	return $out;
}

/* ============================================================
 * 할 일 · 배지
 * ============================================================ */

function md_inv_counts() {
	global $wpdb;
	static $c = null;
	if ( null !== $c ) { return $c; }
	$t = md_inv_t();
	$c = array(
		'pending' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['req']} WHERE status = 'pending'" ),
		'ordered' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['ord']} WHERE status = 'ordered'" ),
		'need'    => count( md_inv_need_order() ),
	);
	return $c;
}

/**
 * 주문이 필요한 품목 — 현재고 < 안전재고, 주문 중인 것이 없고,
 * (설정) 최근 N주 안에 출고된 적이 있는 것. AppSheet v3.1 에서 339건 → 10건으로 줄인 규칙.
 */
function md_inv_need_order( $all = false ) {
	global $wpdb;
	$weeks  = (int) md_inv_set( 'order_need_recent' );
	$recent = array();
	if ( $weeks > 0 && ! $all ) {
		$since = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $weeks * WEEK_IN_SECONDS );
		$recent = array_flip( array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT item_id FROM ' . md_inv_t( 'ledger' ) . " WHERE type = 'out' AND voided = 0 AND created_at >= %s", $since ) ) ) );
	}
	$out = array();
	foreach ( md_inv_items() as $it ) {
		if ( $it->min_stock <= 0 || $it->stock >= $it->min_stock ) { continue; }
		if ( $it->onord > 0 ) { continue; }
		if ( $weeks > 0 && ! $all && ! isset( $recent[ (int) $it->id ] ) ) { continue; }
		$out[] = $it;
	}
	return $out;
}

/* ============================================================
 * 통계
 * ============================================================ */

/** 선납 업체 id 목록 */
function md_inv_prepaid_vendor_ids() {
	$ids = array();
	foreach ( md_inv_vendors( false ) as $v ) { if ( (int) $v->prepaid ) { $ids[] = (int) $v->id; } }
	return $ids;
}

/** 사용금액 통계용 WHERE (출고 · 취소 아님 · 선납 제외 설정) */
function md_inv_usage_where( $from, $to ) {
	global $wpdb;
	$w = $wpdb->prepare( "l.type = 'out' AND l.voided = 0 AND l.created_at >= %s AND l.created_at <= %s", $from . ' 00:00:00', $to . ' 23:59:59' );
	if ( ! md_inv_set( 'stats_include_prepaid' ) ) {
		$pv = md_inv_prepaid_vendor_ids();
		if ( $pv ) { $w .= ' AND l.vendor_id NOT IN (' . implode( ',', $pv ) . ')'; }
	}
	return $w;
}

/**
 * 사용금액 묶음 합계.
 * @param string $by team | month | week | cat2 | item | vendor
 */
function md_inv_usage( $by, $from, $to, $team_id = 0, $limit = 0 ) {
	global $wpdb;
	$t = md_inv_t();
	$w = md_inv_usage_where( $from, $to );
	if ( $team_id ) { $w .= $wpdb->prepare( ' AND l.team_id = %d', (int) $team_id ); }
	switch ( $by ) {
		case 'team':   $g = 'l.team_id'; break;
		case 'month':  $g = "SUBSTR(l.created_at, 1, 7)"; break;
		case 'day':    $g = "SUBSTR(l.created_at, 1, 10)"; break;
		case 'cat2':   $g = 'i.cat2'; break;
		case 'vendor': $g = 'l.vendor_id'; break;
		default:       $g = 'l.item_id'; break;
	}
	$sql = "SELECT $g AS k, SUM(-l.qty) AS qty, SUM(-l.qty * l.price) AS amount, COUNT(*) AS n
	        FROM {$t['ledger']} l LEFT JOIN {$t['item']} i ON i.id = l.item_id
	        WHERE $w GROUP BY $g ORDER BY amount DESC";
	if ( $limit ) { $sql .= ' LIMIT ' . (int) $limit; }
	return $wpdb->get_results( $sql );
}

/** 주별 합계 (월요일 시작) — 최근 N주 [ 'Y-m-d(월)' => amount ] */
function md_inv_usage_weekly( $weeks, $team_id = 0 ) {
	$now   = current_time( 'timestamp' );
	$dow   = (int) date( 'N', $now );
	$mon   = strtotime( date( 'Y-m-d', $now ) ) - ( $dow - 1 ) * DAY_IN_SECONDS;
	$start = $mon - ( $weeks - 1 ) * WEEK_IN_SECONDS;
	$days  = md_inv_usage( 'day', date( 'Y-m-d', $start ), date( 'Y-m-d', $now ), $team_id );
	$out   = array();
	for ( $i = 0; $i < $weeks; $i++ ) { $out[ date( 'Y-m-d', $start + $i * WEEK_IN_SECONDS ) ] = 0; }
	foreach ( $days as $d ) {
		$ts = strtotime( $d->k );
		$wk = date( 'Y-m-d', $ts - ( (int) date( 'N', $ts ) - 1 ) * DAY_IN_SECONDS );
		if ( isset( $out[ $wk ] ) ) { $out[ $wk ] += (int) $d->amount; }
	}
	return $out;
}

/** 월별 합계 — 최근 N개월 [ 'Y-m' => amount ] (값 없는 달도 0 으로) */
function md_inv_usage_monthly( $months, $team_id = 0 ) {
	$now  = current_time( 'timestamp' );
	$first = strtotime( date( 'Y-m-01', $now ) );
	$out  = array();
	for ( $i = $months - 1; $i >= 0; $i-- ) { $out[ date( 'Y-m', strtotime( "-$i months", $first ) ) ] = 0; }
	$from = array_keys( $out )[0] . '-01';
	foreach ( md_inv_usage( 'month', $from, date( 'Y-m-d', $now ), $team_id ) as $r ) {
		if ( isset( $out[ $r->k ] ) ) { $out[ $r->k ] = (int) $r->amount; }
	}
	return $out;
}

/** 구매(입고) 금액 — 업체별 (무상 제외, 반품 차감) */
function md_inv_purchase_by_vendor( $from, $to ) {
	global $wpdb;
	$t = md_inv_t();
	return $wpdb->get_results( $wpdb->prepare(
		"SELECT l.vendor_id AS k,
		        SUM(CASE WHEN l.type = 'in' THEN l.qty * l.price ELSE 0 END) AS bought,
		        SUM(CASE WHEN l.type = 'return' THEN -l.qty * l.price ELSE 0 END) AS returned
		 FROM {$t['ledger']} l
		 WHERE l.voided = 0 AND l.free = 0 AND l.type IN ('in','return') AND l.created_at >= %s AND l.created_at <= %s
		 GROUP BY l.vendor_id ORDER BY bought DESC",
		$from . ' 00:00:00', $to . ' 23:59:59'
	) );
}

/** 재고 금액 합계 (현재고 × 단가) */
function md_inv_stock_value( $items = null ) {
	if ( null === $items ) { $items = md_inv_items(); }
	$s = 0;
	foreach ( $items as $it ) { if ( $it->stock > 0 ) { $s += $it->stock * $it->price; } }
	return $s;
}
