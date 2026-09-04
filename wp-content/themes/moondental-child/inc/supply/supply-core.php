<?php
/**
 * 재고관리 — 조회와 기록
 *
 * 규칙 하나 · 재고 수량을 직접 쓰는 함수는 없다.
 * 무엇이 움직이든 md_sup_move() 로 원장에 한 줄 남기고, 현재고는 합계로 읽는다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 권한
 * ============================================================ */

/** 재고 페이지를 볼 수 있는가 (로그인한 직원) */
function md_sup_can_use() {
	return is_user_logged_in() && ( current_user_can( 'md_supply_use' ) || current_user_can( 'manage_options' ) );
}

/** 반출 처리·입고·품목 관리를 할 수 있는가 (재고 담당자) */
function md_sup_can_manage() {
	return is_user_logged_in() && ( current_user_can( 'md_supply_manage' ) || current_user_can( 'manage_options' ) );
}

/** 로그인한 사용자의 소속 팀 id (사용자 메타에 저장) */
function md_sup_my_team_id() {
	$id = (int) get_user_meta( get_current_user_id(), 'md_sup_team_id', true );
	return $id > 0 ? $id : 0;
}

/* ============================================================
 * 팀 · 품목
 * ============================================================ */

function md_sup_teams( $only_active = true ) {
	global $wpdb;
	$t     = md_sup_tables();
	$where = $only_active ? 'WHERE active = 1' : '';
	return $wpdb->get_results( "SELECT * FROM {$t['teams']} $where ORDER BY sort_no ASC, id ASC" );
}

function md_sup_team_name( $team_id ) {
	foreach ( md_sup_teams( false ) as $tm ) {
		if ( (int) $tm->id === (int) $team_id ) { return $tm->name; }
	}
	return '';
}

/**
 * 품목 목록. 검색어·카테고리·거래처로 좁힐 수 있다.
 * 현재고는 여기서 원장 합계를 붙여 한 번에 가져온다(품목마다 따로 세지 않는다).
 */
function md_sup_items( $args = array() ) {
	global $wpdb;
	$t = md_sup_tables();

	$a = wp_parse_args( $args, array(
		'search'   => '',
		'category' => '',
		'vendor'   => '',
		'low_only' => false,
		'limit'    => 0,
	) );

	$where  = array( 'i.active = 1' );
	$params = array();

	if ( '' !== $a['search'] ) {
		$like     = '%' . $wpdb->esc_like( $a['search'] ) . '%';
		$where[]  = '(i.name LIKE %s OR i.code LIKE %s OR i.vendor LIKE %s)';
		$params[] = $like; $params[] = $like; $params[] = $like;
	}
	if ( '' !== $a['category'] ) { $where[] = 'i.category = %s'; $params[] = $a['category']; }
	if ( '' !== $a['vendor'] )   { $where[] = 'i.vendor = %s';   $params[] = $a['vendor']; }

	$sql = "SELECT i.*, COALESCE(l.stock, 0) AS stock
	        FROM {$t['items']} i
	        LEFT JOIN (
	            SELECT item_id, SUM(qty) AS stock FROM {$t['ledger']} GROUP BY item_id
	        ) l ON l.item_id = i.id
	        WHERE " . implode( ' AND ', $where );

	if ( $a['low_only'] ) { $sql .= ' HAVING stock <= i.min_stock AND i.min_stock > 0'; }
	$sql .= ' ORDER BY i.sort_no ASC, i.id ASC';
	if ( $a['limit'] > 0 ) { $sql .= ' LIMIT ' . (int) $a['limit']; }

	if ( $params ) { $sql = $wpdb->prepare( $sql, $params ); }
	return $wpdb->get_results( $sql );
}

function md_sup_item( $item_id ) {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->get_row( $wpdb->prepare(
		"SELECT i.*, COALESCE((SELECT SUM(qty) FROM {$t['ledger']} WHERE item_id = i.id), 0) AS stock
		 FROM {$t['items']} i WHERE i.id = %d", $item_id
	) );
}

function md_sup_categories() {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->get_col( "SELECT DISTINCT category FROM {$t['items']} WHERE active = 1 AND category <> '' ORDER BY category" );
}

function md_sup_vendors() {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->get_col( "SELECT DISTINCT vendor FROM {$t['items']} WHERE active = 1 AND vendor <> '' ORDER BY vendor" );
}

/** 등록된 품목 수 (활성) */
function md_sup_item_count() {
	global $wpdb;
	$t = md_sup_tables();
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['items']} WHERE active = 1" );
}

/* ============================================================
 * 원장 — 모든 수량 변화는 여기를 지난다
 * ============================================================ */

/**
 * 원장에 한 줄 기록한다.
 *
 * @param int    $item_id 품목
 * @param int    $qty     부호 있는 수량. 입고 +, 출고 −
 * @param string $reason  in | out | dispose | adjust
 * @param int    $team_id 출고 대상 팀 (입고·조정이면 0)
 * @param int    $ref_id  연결된 신청 id 등
 * @param string $note    메모
 * @return int|false 기록 id
 */
function md_sup_move( $item_id, $qty, $reason = 'out', $team_id = 0, $ref_id = 0, $note = '' ) {
	global $wpdb;
	$t   = md_sup_tables();
	$qty = (int) $qty;
	if ( ! $item_id || 0 === $qty ) { return false; }

	$allowed = array( 'in', 'out', 'dispose', 'adjust' );
	if ( ! in_array( $reason, $allowed, true ) ) { $reason = 'out'; }

	$now = current_time( 'mysql' );
	$ok  = $wpdb->insert( $t['ledger'], array(
		'item_id'    => (int) $item_id,
		'team_id'    => (int) $team_id,
		'qty'        => $qty,
		'reason'     => $reason,
		'ref_id'     => (int) $ref_id,
		'note'       => mb_substr( (string) $note, 0, 255 ),
		'user_id'    => get_current_user_id(),
		'ym'         => substr( $now, 0, 7 ),
		'created_at' => $now,
	), array( '%d', '%d', '%d', '%s', '%d', '%s', '%d', '%s', '%s' ) );

	return $ok ? (int) $wpdb->insert_id : false;
}

/** 품목 현재고 */
function md_sup_stock( $item_id ) {
	global $wpdb;
	$t = md_sup_tables();
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COALESCE(SUM(qty),0) FROM {$t['ledger']} WHERE item_id = %d", $item_id
	) );
}

/* ============================================================
 * 사용량 — 낭비를 줄이는 장치의 근거
 * ============================================================ */

/**
 * 특정 팀이 특정 품목을 최근 몇 달간 월평균 얼마나 받아갔는가.
 * 신청 화면에서 수량 칸 옆에 바로 보여주는 값이다.
 *
 * @return float 월평균 수량
 */
function md_sup_avg_monthly( $item_id, $team_id, $months = 3 ) {
	global $wpdb;
	$t     = md_sup_tables();
	$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . (int) $months . ' months', current_time( 'timestamp' ) ) );

	$total = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COALESCE(SUM(-qty),0) FROM {$t['ledger']}
		 WHERE item_id = %d AND team_id = %d AND reason = 'out' AND created_at >= %s",
		$item_id, $team_id, $since
	) );
	return $months > 0 ? round( $total / $months, 1 ) : 0;
}

/**
 * 한 팀의 전 품목 월평균을 한 번에 가져온다.
 *
 * 품목마다 md_sup_avg_monthly() 를 부르면 568개 × 2질의 = 1136질의가 되어
 * 화면이 열리지 않는다. 그래서 목록 화면에서는 이 함수로 통째로 받아 쓴다.
 *
 * @return array [ item_id => 월평균 ]
 */
function md_sup_avg_map( $team_id, $months = 3 ) {
	global $wpdb;
	$t     = md_sup_tables();
	$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . (int) $months . ' months', current_time( 'timestamp' ) ) );

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, SUM(-qty) AS total FROM {$t['ledger']}
		 WHERE team_id = %d AND reason = 'out' AND created_at >= %s
		 GROUP BY item_id",
		$team_id, $since
	) );

	$map = array();
	foreach ( $rows as $r ) {
		$map[ (int) $r->item_id ] = $months > 0 ? round( (int) $r->total / $months, 1 ) : 0;
	}
	return $map;
}

/**
 * 한 팀의 전 품목 「마지막 수령」을 한 번에.
 *
 * @return array [ item_id => (object) { created_at, qty } ]
 */
function md_sup_last_map( $team_id ) {
	global $wpdb;
	$t = md_sup_tables();

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT l.item_id, l.created_at, -l.qty AS qty
		 FROM {$t['ledger']} l
		 INNER JOIN (
		     SELECT item_id, MAX(id) AS last_id FROM {$t['ledger']}
		     WHERE team_id = %d AND reason = 'out' GROUP BY item_id
		 ) m ON m.last_id = l.id",
		$team_id
	) );

	$map = array();
	foreach ( $rows as $r ) { $map[ (int) $r->item_id ] = $r; }
	return $map;
}

/** 팀이 그 품목을 마지막으로 받아간 날짜와 수량 */
function md_sup_last_out( $item_id, $team_id ) {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->get_row( $wpdb->prepare(
		"SELECT created_at, -qty AS qty FROM {$t['ledger']}
		 WHERE item_id = %d AND team_id = %d AND reason = 'out'
		 ORDER BY created_at DESC LIMIT 1",
		$item_id, $team_id
	) );
}

/**
 * 팀별 월 사용 금액. 통계 화면의 기본 표.
 * 금액은 원장 수량 × 품목 단가로 계산한다.
 */
function md_sup_team_usage( $ym ) {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->get_results( $wpdb->prepare(
		"SELECT l.team_id, tm.name AS team_name,
		        SUM(-l.qty) AS qty,
		        SUM(-l.qty * i.price) AS amount
		 FROM {$t['ledger']} l
		 INNER JOIN {$t['items']} i ON i.id = l.item_id
		 LEFT JOIN {$t['teams']} tm ON tm.id = l.team_id
		 WHERE l.reason = 'out' AND l.ym = %s
		 GROUP BY l.team_id, tm.name
		 ORDER BY amount DESC",
		$ym
	) );
}

/** 한 달간 가장 많이 나간 품목 (금액 기준) */
function md_sup_top_items( $ym, $team_id = 0, $limit = 10 ) {
	global $wpdb;
	$t = md_sup_tables();

	$where  = "l.reason = 'out' AND l.ym = %s";
	$params = array( $ym );
	if ( $team_id > 0 ) { $where .= ' AND l.team_id = %d'; $params[] = $team_id; }
	$params[] = (int) $limit;

	return $wpdb->get_results( $wpdb->prepare(
		"SELECT i.id, i.name, i.unit, i.price,
		        SUM(-l.qty) AS qty, SUM(-l.qty * i.price) AS amount
		 FROM {$t['ledger']} l
		 INNER JOIN {$t['items']} i ON i.id = l.item_id
		 WHERE $where
		 GROUP BY i.id, i.name, i.unit, i.price
		 ORDER BY amount DESC LIMIT %d",
		$params
	) );
}

/** 최근 N개월의 월별 사용 금액 (팀 지정 시 그 팀만) */
function md_sup_monthly_trend( $team_id = 0, $months = 6 ) {
	global $wpdb;
	$t      = md_sup_tables();
	$yms    = array();
	$ts     = current_time( 'timestamp' );
	for ( $i = $months - 1; $i >= 0; $i-- ) {
		$yms[] = gmdate( 'Y-m', strtotime( "-$i months", $ts ) );
	}
	$in     = implode( ',', array_fill( 0, count( $yms ), '%s' ) );
	$params = $yms;

	$where = "l.reason = 'out' AND l.ym IN ($in)";
	if ( $team_id > 0 ) { $where .= ' AND l.team_id = %d'; $params[] = $team_id; }

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT l.ym, SUM(-l.qty * i.price) AS amount
		 FROM {$t['ledger']} l INNER JOIN {$t['items']} i ON i.id = l.item_id
		 WHERE $where GROUP BY l.ym",
		$params
	) );

	$map = array();
	foreach ( $rows as $r ) { $map[ $r->ym ] = (int) $r->amount; }

	$out = array();
	foreach ( $yms as $ym ) { $out[ $ym ] = isset( $map[ $ym ] ) ? $map[ $ym ] : 0; }
	return $out;
}

/* ============================================================
 * 신청
 * ============================================================ */

/**
 * 신청서를 만든다.
 *
 * @param array $lines [ item_id => array( qty, over_reason ) ]
 * @return int|WP_Error 신청 id
 */
function md_sup_create_request( $team_id, $lines, $urgent = 0, $note = '', $customs = array() ) {
	global $wpdb;
	$t = md_sup_tables();

	$team_id = (int) $team_id;
	if ( $team_id <= 0 ) { return new WP_Error( 'no_team', '팀이 지정되지 않았습니다.' ); }
	if ( empty( $lines ) && empty( $customs ) ) { return new WP_Error( 'empty', '신청할 품목이 없습니다.' ); }

	$now = current_time( 'mysql' );
	$ok  = $wpdb->insert( $t['req'], array(
		'team_id'    => $team_id,
		'user_id'    => get_current_user_id(),
		'status'     => 'pending',
		'urgent'     => $urgent ? 1 : 0,
		'note'       => mb_substr( (string) $note, 0, 500 ),
		'created_at' => $now,
	), array( '%d', '%d', '%s', '%d', '%s', '%s' ) );

	if ( ! $ok ) { return new WP_Error( 'db', '신청서를 저장하지 못했습니다.' ); }
	$req_id = (int) $wpdb->insert_id;

	foreach ( $lines as $item_id => $ln ) {
		$qty = isset( $ln['qty'] ) ? (int) $ln['qty'] : 0;
		if ( $qty <= 0 ) { continue; }
		$wpdb->insert( $t['line'], array(
			'req_id'      => $req_id,
			'item_id'     => (int) $item_id,
			'qty_req'     => $qty,
			'qty_out'     => 0,
			'over_reason' => isset( $ln['over_reason'] ) ? mb_substr( (string) $ln['over_reason'], 0, 255 ) : '',
		), array( '%d', '%d', '%d', '%d', '%s' ) );
	}

	/* 목록에 없어 직접 적은 품목 — item_id 0 에 이름만 남긴다.
	 * 담당자가 「품목·팀」에서 등록한 뒤 반출관리에서 연결한다. */
	foreach ( $customs as $c ) {
		$nm = sanitize_text_field( isset( $c['name'] ) ? $c['name'] : '' );
		$q  = isset( $c['qty'] ) ? (int) $c['qty'] : 0;
		if ( '' === trim( $nm ) || $q <= 0 ) { continue; }
		$wpdb->insert( $t['line'], array(
			'req_id'      => $req_id,
			'item_id'     => 0,
			'custom_name' => mb_substr( $nm, 0, 255 ),
			'qty_req'     => $q,
			'qty_out'     => 0,
			'over_reason' => '',
		), array( '%d', '%d', '%s', '%d', '%d', '%s' ) );
	}

	return $req_id;
}

/** 신청 목록. 팀을 지정하면 그 팀 것만. */
function md_sup_requests( $args = array() ) {
	global $wpdb;
	$t = md_sup_tables();
	$a = wp_parse_args( $args, array( 'team_id' => 0, 'status' => '', 'limit' => 50 ) );

	$where  = array( '1=1' );
	$params = array();
	if ( $a['team_id'] > 0 ) { $where[] = 'r.team_id = %d'; $params[] = (int) $a['team_id']; }
	if ( '' !== $a['status'] ) { $where[] = 'r.status = %s'; $params[] = $a['status']; }
	$params[] = (int) $a['limit'];

	return $wpdb->get_results( $wpdb->prepare(
		"SELECT r.*, tm.name AS team_name,
		        (SELECT COUNT(*) FROM {$t['line']} WHERE req_id = r.id) AS line_count
		 FROM {$t['req']} r LEFT JOIN {$t['teams']} tm ON tm.id = r.team_id
		 WHERE " . implode( ' AND ', $where ) . "
		 ORDER BY r.urgent DESC, r.created_at DESC LIMIT %d",
		$params
	) );
}

/** 신청서에 달린 품목 줄 */
function md_sup_request_lines( $req_id ) {
	global $wpdb;
	$t = md_sup_tables();
	/* LEFT JOIN 인 이유 — 직접 적은 품목은 item_id 가 0 이라
	 * INNER JOIN 이면 그 줄이 통째로 사라진다. */
	return $wpdb->get_results( $wpdb->prepare(
		"SELECT ln.*,
		        COALESCE(NULLIF(i.name,''), ln.custom_name) AS name,
		        COALESCE(i.unit,'')  AS unit,
		        COALESCE(i.price,0)  AS price,
		        COALESCE(i.code,'')  AS code
		 FROM {$t['line']} ln LEFT JOIN {$t['items']} i ON i.id = ln.item_id
		 WHERE ln.req_id = %d ORDER BY ln.id ASC",
		$req_id
	) );
}

/**
 * 신청을 출고 처리한다. 줄마다 실제 나간 수량을 받아 원장에 기록한다.
 *
 * @param array $qty_map [ line_id => qty_out ]
 */
function md_sup_release_request( $req_id, $qty_map ) {
	global $wpdb;
	$t   = md_sup_tables();
	$req = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['req']} WHERE id = %d", $req_id ) );
	if ( ! $req ) { return new WP_Error( 'not_found', '신청서를 찾을 수 없습니다.' ); }
	if ( 'done' === $req->status ) { return new WP_Error( 'done', '이미 처리된 신청입니다.' ); }

	foreach ( md_sup_request_lines( $req_id ) as $ln ) {
		$qty = isset( $qty_map[ $ln->id ] ) ? (int) $qty_map[ $ln->id ] : 0;
		/* 직접 적은 품목(item_id 0)은 아직 등록된 품목이 없어 출고할 수 없다.
		 * 담당자가 「품목·팀」에서 등록한 뒤 다시 신청받는다. */
		if ( $qty <= 0 || 0 === (int) $ln->item_id ) { continue; }
		md_sup_move( $ln->item_id, -$qty, 'out', $req->team_id, $req_id, '신청 #' . $req_id . ' 출고' );
		$wpdb->update( $t['line'], array( 'qty_out' => $qty ), array( 'id' => $ln->id ), array( '%d' ), array( '%d' ) );
	}

	$wpdb->update( $t['req'], array(
		'status'  => 'done',
		'done_at' => current_time( 'mysql' ),
		'done_by' => get_current_user_id(),
	), array( 'id' => $req_id ), array( '%s', '%s', '%d' ), array( '%d' ) );

	return true;
}

/** 신청 반려 */
function md_sup_reject_request( $req_id, $reason = '' ) {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->update( $t['req'], array(
		'status'  => 'rejected',
		'note'    => mb_substr( (string) $reason, 0, 500 ),
		'done_at' => current_time( 'mysql' ),
		'done_by' => get_current_user_id(),
	), array( 'id' => (int) $req_id ), array( '%s', '%s', '%s', '%d' ), array( '%d' ) );
}

/* ============================================================
 * 표시 도우미
 * ============================================================ */

function md_sup_won( $n ) {
	return number_format( (int) $n ) . '원';
}

function md_sup_status_label( $status ) {
	$map = array(
		'pending'  => '신청됨',
		'done'     => '출고 완료',
		'rejected' => '반려',
	);
	return isset( $map[ $status ] ) ? $map[ $status ] : $status;
}

/* ============================================================
 * v3.60 · 즐겨찾기 · 지난 신청 · 내보내기
 * ============================================================ */

/** 팀의 즐겨찾기 품목 id 목록 */
function md_sup_fav_ids( $team_id ) {
	global $wpdb;
	$t   = md_sup_tables();
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT item_id FROM {$t['fav']} WHERE team_id = %d", (int) $team_id ) );
	return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
}

/** 즐겨찾기 켜고 끄기 */
function md_sup_fav_toggle( $team_id, $item_id ) {
	global $wpdb;
	$t       = md_sup_tables();
	$team_id = (int) $team_id;
	$item_id = (int) $item_id;
	if ( $team_id <= 0 || $item_id <= 0 ) { return false; }

	$has = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$t['fav']} WHERE team_id = %d AND item_id = %d", $team_id, $item_id
	) );

	if ( $has ) {
		$wpdb->delete( $t['fav'], array( 'id' => (int) $has ), array( '%d' ) );
		return false;
	}
	$wpdb->insert( $t['fav'], array( 'team_id' => $team_id, 'item_id' => $item_id ), array( '%d', '%d' ) );
	return true;
}

/**
 * 지난 신청의 품목·수량. "이대로 다시 담기" 에 쓴다.
 * @return array [ item_id => qty ]
 */
function md_sup_request_qty_map( $req_id ) {
	global $wpdb;
	$t   = md_sup_tables();
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, qty_req FROM {$t['line']} WHERE req_id = %d", (int) $req_id
	) );
	$map = array();
	foreach ( $rows as $r ) { $map[ (int) $r->item_id ] = (int) $r->qty_req; }
	return $map;
}

/** 신청서 한 건의 요약 — 제출 직후 확인용 */
function md_sup_request_summary( $req_id ) {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->get_row( $wpdb->prepare(
		"SELECT r.*, tm.name AS team_name,
		        (SELECT COUNT(*) FROM {$t['line']} WHERE req_id = r.id) AS line_count,
		        (SELECT COALESCE(SUM(ln.qty_req * i.price),0)
		           FROM {$t['line']} ln INNER JOIN {$t['items']} i ON i.id = ln.item_id
		          WHERE ln.req_id = r.id) AS amount
		 FROM {$t['req']} r LEFT JOIN {$t['teams']} tm ON tm.id = r.team_id
		 WHERE r.id = %d",
		(int) $req_id
	) );
}

/** 팀별 전월 대비 증감(%) — 통계 표에 붙인다 */
function md_sup_team_delta( $ym ) {
	global $wpdb;
	$t    = md_sup_tables();
	$prev = gmdate( 'Y-m', strtotime( $ym . '-01 -1 month' ) );

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT l.team_id, l.ym, SUM(-l.qty * i.price) AS amount
		 FROM {$t['ledger']} l INNER JOIN {$t['items']} i ON i.id = l.item_id
		 WHERE l.reason = 'out' AND l.ym IN (%s, %s)
		 GROUP BY l.team_id, l.ym",
		$ym, $prev
	) );

	$now = array(); $was = array();
	foreach ( $rows as $r ) {
		if ( $r->ym === $ym ) { $now[ (int) $r->team_id ] = (int) $r->amount; }
		else { $was[ (int) $r->team_id ] = (int) $r->amount; }
	}

	$out = array();
	foreach ( $now as $team => $amt ) {
		$p = isset( $was[ $team ] ) ? $was[ $team ] : 0;
		$out[ $team ] = ( $p > 0 ) ? (int) round( ( $amt - $p ) / $p * 100 ) : null;
	}
	return $out;
}

/* ============================================================
 * v3.61 · 품목 · 팀 관리 (재고 담당자)
 * ============================================================ */

/** 다음 품목 코드 — M0569 처럼 이어서 매긴다 */
function md_sup_next_code() {
	global $wpdb;
	$t   = md_sup_tables();
	$max = (string) $wpdb->get_var( "SELECT code FROM {$t['items']} WHERE code REGEXP '^M[0-9]+$' ORDER BY CAST(SUBSTRING(code,2) AS UNSIGNED) DESC LIMIT 1" );
	$n   = $max ? (int) substr( $max, 1 ) : 0;
	return 'M' . str_pad( (string) ( $n + 1 ), 4, '0', STR_PAD_LEFT );
}

/**
 * 품목 저장. id 가 0 이면 새로 만든다.
 * 코드를 비워 두면 자동으로 매긴다.
 */
function md_sup_item_save( $id, $d ) {
	global $wpdb;
	$t = md_sup_tables();

	$name = sanitize_text_field( isset( $d['name'] ) ? $d['name'] : '' );
	if ( '' === trim( $name ) ) { return new WP_Error( 'empty', '품목명을 적어 주세요.' ); }

	$row = array(
		'name'      => mb_substr( $name, 0, 255 ),
		'vendor'    => sanitize_text_field( isset( $d['vendor'] ) ? $d['vendor'] : '' ),
		'unit'      => sanitize_text_field( isset( $d['unit'] ) ? $d['unit'] : '' ),
		'category'  => sanitize_text_field( isset( $d['category'] ) ? $d['category'] : '' ),
		'price'     => max( 0, (int) ( isset( $d['price'] ) ? $d['price'] : 0 ) ),
		'min_stock' => max( 0, (int) ( isset( $d['min_stock'] ) ? $d['min_stock'] : 0 ) ),
	);

	if ( $id > 0 ) {
		$wpdb->update( $t['items'], $row, array( 'id' => (int) $id ), array( '%s', '%s', '%s', '%s', '%d', '%d' ), array( '%d' ) );
		return (int) $id;
	}

	$code = sanitize_text_field( isset( $d['code'] ) ? $d['code'] : '' );
	if ( '' === trim( $code ) ) { $code = md_sup_next_code(); }

	$dup = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['items']} WHERE code = %s", $code ) );
	if ( $dup ) { return new WP_Error( 'dup', '이미 쓰고 있는 품목코드입니다 — ' . $code ); }

	$row['code']    = $code;
	$row['active']  = 1;
	$row['sort_no'] = 9999;
	$wpdb->insert( $t['items'], $row, array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d' ) );
	return (int) $wpdb->insert_id;
}

/**
 * 품목 감추기 / 되살리기.
 *
 * 진짜로 지우지 않는다 — 지나간 입출고 기록이 그 품목을 가리키고 있어서,
 * 삭제하면 과거 사용량 통계가 "이름 없는 품목" 이 되어 버린다.
 * active 를 0 으로 내리면 목록·신청에서 사라지고 기록은 그대로 남는다.
 */
function md_sup_item_archive( $id, $on = false ) {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->update( $t['items'], array( 'active' => $on ? 1 : 0 ), array( 'id' => (int) $id ), array( '%d' ), array( '%d' ) );
}

/** 기록이 전혀 없는 품목만 진짜로 지운다 */
function md_sup_item_delete( $id, $force = false ) {
	global $wpdb;
	$t  = md_sup_tables();
	$id = (int) $id;
	if ( $id <= 0 ) { return new WP_Error( 'bad', '품목을 찾을 수 없습니다.' ); }

	$n = md_sup_item_records( $id );
	if ( $n > 0 && ! $force ) {
		return new WP_Error( 'records', '이 품목에 입출고·신청 기록 ' . number_format( $n ) . '건이 남아 있습니다.' );
	}

	$wpdb->delete( $t['fav'], array( 'item_id' => $id ), array( '%d' ) );
	$wpdb->delete( $t['items'], array( 'id' => $id ), array( '%d' ) );
	return true;
}

/** 감춘 것까지 포함해 품목을 가져온다 (품목 관리 화면용) */
function md_sup_items_all( $search = '', $show_hidden = false ) {
	global $wpdb;
	$t = md_sup_tables();

	$where  = $show_hidden ? array( '1=1' ) : array( 'i.active = 1' );
	$params = array();
	if ( '' !== $search ) {
		$like    = '%' . $wpdb->esc_like( $search ) . '%';
		$where[] = '(i.name LIKE %s OR i.code LIKE %s OR i.vendor LIKE %s)';
		$params[] = $like; $params[] = $like; $params[] = $like;
	}

	$sql = "SELECT i.*, COALESCE(l.stock,0) AS stock
	        FROM {$t['items']} i
	        LEFT JOIN (SELECT item_id, SUM(qty) AS stock FROM {$t['ledger']} GROUP BY item_id) l ON l.item_id = i.id
	        WHERE " . implode( ' AND ', $where ) . '
	        ORDER BY i.active DESC, i.sort_no ASC, i.id ASC';

	if ( $params ) { $sql = $wpdb->prepare( $sql, $params ); }
	return $wpdb->get_results( $sql );
}

/* --- 팀 --------------------------------------------------- */

function md_sup_team_save( $id, $name, $sort_no = 0 ) {
	global $wpdb;
	$t    = md_sup_tables();
	$name = sanitize_text_field( $name );
	if ( '' === trim( $name ) ) { return new WP_Error( 'empty', '팀 이름을 적어 주세요.' ); }

	if ( $id > 0 ) {
		$wpdb->update( $t['teams'], array( 'name' => $name, 'sort_no' => (int) $sort_no ), array( 'id' => (int) $id ), array( '%s', '%d' ), array( '%d' ) );
		return (int) $id;
	}
	$dup = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['teams']} WHERE name = %s", $name ) );
	if ( $dup ) { return new WP_Error( 'dup', '같은 이름의 팀이 이미 있습니다.' ); }

	$wpdb->insert( $t['teams'], array( 'name' => $name, 'sort_no' => (int) $sort_no, 'active' => 1 ), array( '%s', '%d', '%d' ) );
	return (int) $wpdb->insert_id;
}

/** 팀도 지우지 않고 감춘다 — 과거 사용량이 그 팀을 가리키고 있다 */
function md_sup_team_archive( $id, $on = false ) {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->update( $t['teams'], array( 'active' => $on ? 1 : 0 ), array( 'id' => (int) $id ), array( '%d' ), array( '%d' ) );
}

/* ============================================================
 * v3.63 · 삭제 — 담당자가 직접 지울 수 있게
 *
 * 지우면 되돌릴 수 없으므로 두 단계로 나눈다.
 *   1) 처음 누르면 "기록 N건이 딸려 있습니다" 하고 되돌려 보낸다.
 *   2) 그래도 지우겠다고 다시 누르면 force 로 지운다.
 * 자바스크립트가 없어도 확인 절차가 그대로 작동한다.
 * ============================================================ */

/** 이 품목에 딸린 기록 수 (입출고 + 신청) */
function md_sup_item_records( $id ) {
	global $wpdb;
	$t  = md_sup_tables();
	$id = (int) $id;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['ledger']} WHERE item_id = %d", $id ) )
	     + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['line']} WHERE item_id = %d", $id ) );
}

/** 이 팀에 딸린 기록 수 (입출고 + 신청서) */
function md_sup_team_records( $id ) {
	global $wpdb;
	$t  = md_sup_tables();
	$id = (int) $id;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['ledger']} WHERE team_id = %d", $id ) )
	     + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['req']} WHERE team_id = %d", $id ) );
}

/**
 * 팀 삭제.
 *
 * 기록이 딸려 있으면 기본적으로 막고, force 를 주면 지운다.
 * 지운 뒤에도 입출고 원장은 그대로 남는다 — 통계에서는 「(삭제된 팀)」으로 보인다.
 * 원장을 같이 지우면 그 달 전체 사용액이 어긋나므로 건드리지 않는다.
 */
function md_sup_team_delete( $id, $force = false ) {
	global $wpdb;
	$t  = md_sup_tables();
	$id = (int) $id;
	if ( $id <= 0 ) { return new WP_Error( 'bad', '팀을 찾을 수 없습니다.' ); }

	$n = md_sup_team_records( $id );
	if ( $n > 0 && ! $force ) {
		return new WP_Error( 'records', '이 팀에 입출고·신청 기록 ' . number_format( $n ) . '건이 남아 있습니다.' );
	}

	$wpdb->delete( $t['fav'], array( 'team_id' => $id ), array( '%d' ) );
	$wpdb->delete( $t['teams'], array( 'id' => $id ), array( '%d' ) );
	return true;
}
