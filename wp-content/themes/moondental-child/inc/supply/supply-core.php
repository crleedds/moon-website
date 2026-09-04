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

	/* 부족한 것만.
	 * v3.63 까지는 HAVING 을 붙였는데 이 질의에는 GROUP BY 가 없다.
	 * MySQL 이 관대해 지금은 돌지만 ONLY_FULL_GROUP_BY 가 켜진 서버로 옮기면
	 * 입고 화면이 통째로 죽는다. 파생표의 열을 WHERE 에서 직접 본다. */
	if ( $a['low_only'] ) { $where[] = 'i.min_stock > 0 AND COALESCE(l.stock, 0) <= i.min_stock'; }

	$sql = "SELECT i.*, COALESCE(l.stock, 0) AS stock
	        FROM {$t['items']} i
	        LEFT JOIN (
	            SELECT item_id, SUM(qty) AS stock FROM {$t['ledger']} GROUP BY item_id
	        ) l ON l.item_id = i.id
	        WHERE " . implode( ' AND ', $where );

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

	/* 담당자에게 알린다. 줄을 다 넣은 뒤라야 메일에 품목이 담긴다.
	 * 메일이 실패해도 신청은 이미 저장돼 있으므로 결과를 보지 않는다. */
	md_sup_notify_new_request( $req_id );

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
 * 두 사람이 동시에 눌러도 한 번만 나간다
 *   v3.63 까지는 status 를 읽어 확인한 뒤 나중에 따로 고쳐 썼다.
 *   그 사이에 다른 담당자가 같은 신청을 처리하면 원장에 두 번 기록됐다.
 *   이제는 「pending 인 것을 done 으로 바꾼다」는 한 문장으로 선점하고,
 *   바뀐 행이 없으면 남이 먼저 가져간 것이니 아무것도 하지 않는다.
 *
 * 재고보다 많이 나가면 막지 않고 알린다
 *   막으면 실제로는 건네줬는데 기록만 못 하는 상태가 되어, 장부가 현실과
 *   더 멀어진다. 그대로 기록하되 어떤 품목이 모자랐는지 돌려주고,
 *   화면에서 「실사로 맞춰 주세요」라고 안내한다.
 *
 * @param array $qty_map [ line_id => qty_out ]
 * @return array|WP_Error array( 'short' => [ 품목명 => 모자란 수량 ] )
 */
function md_sup_release_request( $req_id, $qty_map ) {
	global $wpdb;
	$t      = md_sup_tables();
	$req_id = (int) $req_id;

	$req = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['req']} WHERE id = %d", $req_id ) );
	if ( ! $req ) { return new WP_Error( 'not_found', '신청서를 찾을 수 없습니다.' ); }
	if ( 'pending' !== $req->status ) {
		return new WP_Error( 'done', '이미 처리된 신청입니다 — ' . md_sup_status_label( $req->status ) );
	}

	$wpdb->query( 'START TRANSACTION' );

	/* 선점 — pending 일 때만 바뀐다 */
	$claimed = $wpdb->query( $wpdb->prepare(
		"UPDATE {$t['req']} SET status = 'done', done_at = %s, done_by = %d
		 WHERE id = %d AND status = 'pending'",
		current_time( 'mysql' ), get_current_user_id(), $req_id
	) );

	if ( ! $claimed ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'race', '다른 담당자가 방금 이 신청을 처리했습니다. 새로고침해 확인해 주세요.' );
	}

	$lines = md_sup_request_lines( $req_id );
	$ids   = array();
	foreach ( $lines as $ln ) { if ( (int) $ln->item_id > 0 ) { $ids[] = (int) $ln->item_id; } }
	$stock = md_sup_stock_map( $ids );

	$short = array();

	foreach ( $lines as $ln ) {
		$qty = isset( $qty_map[ $ln->id ] ) ? (int) $qty_map[ $ln->id ] : 0;
		/* 직접 적은 품목(item_id 0)은 아직 등록된 품목이 없어 출고할 수 없다.
		 * 담당자가 「품목·팀」에서 등록한 뒤 다시 신청받는다. */
		if ( $qty <= 0 || 0 === (int) $ln->item_id ) { continue; }

		$have = isset( $stock[ (int) $ln->item_id ] ) ? (int) $stock[ (int) $ln->item_id ] : 0;
		if ( $qty > $have ) { $short[ $ln->name ] = $qty - $have; }

		md_sup_move( $ln->item_id, -$qty, 'out', $req->team_id, $req_id, '신청 #' . $req_id . ' 출고' );
		$wpdb->update( $t['line'], array( 'qty_out' => $qty ), array( 'id' => $ln->id ), array( '%d' ), array( '%d' ) );
	}

	$wpdb->query( 'COMMIT' );

	return array( 'short' => $short );
}

/**
 * 신청 반려.
 *
 * 사유는 reject_reason 에 따로 적는다 — note 는 신청자가 적은 글이라
 * 덮어쓰면 「무엇을 왜 요청했는데 왜 반려됐나」를 맞춰볼 수 없게 된다.
 */
function md_sup_reject_request( $req_id, $reason = '' ) {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->query( $wpdb->prepare(
		"UPDATE {$t['req']} SET status = 'rejected', reject_reason = %s, done_at = %s, done_by = %d
		 WHERE id = %d AND status = 'pending'",
		mb_substr( (string) $reason, 0, 500 ),
		current_time( 'mysql' ),
		get_current_user_id(),
		(int) $req_id
	) );
}

/**
 * 신청 취소 — 신청한 팀이 스스로 물린다.
 *
 * 아직 출고 전(pending)일 때만, 그리고 같은 팀에서만 된다.
 * 수량을 잘못 적었다고 담당자에게 전화하게 만들 이유가 없다.
 */
function md_sup_cancel_request( $req_id, $team_id ) {
	global $wpdb;
	$t      = md_sup_tables();
	$req_id = (int) $req_id;

	$req = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['req']} WHERE id = %d", $req_id ) );
	if ( ! $req ) { return new WP_Error( 'not_found', '신청서를 찾을 수 없습니다.' ); }

	/* 담당자는 어느 팀 것이든 물릴 수 있다 */
	if ( ! md_sup_can_manage() && (int) $req->team_id !== (int) $team_id ) {
		return new WP_Error( 'other_team', '다른 팀의 신청은 취소할 수 없습니다.' );
	}
	if ( 'pending' !== $req->status ) {
		return new WP_Error( 'done', '이미 처리된 신청은 취소할 수 없습니다 — ' . md_sup_status_label( $req->status ) );
	}

	$n = $wpdb->query( $wpdb->prepare(
		"UPDATE {$t['req']} SET status = 'cancelled', done_at = %s, done_by = %d
		 WHERE id = %d AND status = 'pending'",
		current_time( 'mysql' ), get_current_user_id(), $req_id
	) );

	return $n ? true : new WP_Error( 'race', '방금 담당자가 이 신청을 처리했습니다.' );
}

/** 대기 중인 신청 건수 — 탭 배지와 브라우저 제목에 쓴다 */
function md_sup_pending_count() {
	global $wpdb;
	$t = md_sup_tables();
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['req']} WHERE status = 'pending'" );
}

/* ============================================================
 * 표시 도우미
 * ============================================================ */

function md_sup_won( $n ) {
	return number_format( (int) $n ) . '원';
}

function md_sup_status_label( $status ) {
	$map = array(
		'pending'   => '신청됨',
		'done'      => '출고 완료',
		'rejected'  => '반려',
		'cancelled' => '취소함',
	);
	return isset( $map[ $status ] ) ? $map[ $status ] : $status;
}

/** 발주 상태 이름 */
function md_sup_po_status_label( $status ) {
	$map = array(
		'draft'     => '작성 중',
		'ordered'   => '주문함',
		'received'  => '입고 완료',
		'cancelled' => '취소함',
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

/**
 * 팀 저장.
 *
 * @param int      $id      0 이면 새로 만든다
 * @param string   $name    팀 이름
 * @param int|null $sort_no 표시 순서. null 이면 지금 값을 건드리지 않는다.
 *
 * sort_no 를 null 로 둔 이유
 *   v3.63 까지 기본값이 0 이었고 수정할 때 항상 함께 써 넣었다.
 *   「품목·팀」에서 팀 저장을 한 번 누르면 15개 팀의 순서가 모두 0 이 되어,
 *   층 배치를 그대로 옮긴 3행 × 5열 그리드가 id 순으로 흐트러졌다.
 *   이제 순서를 넘기지 않으면 이름·사용 여부만 바뀐다.
 */
function md_sup_team_save( $id, $name, $sort_no = null ) {
	global $wpdb;
	$t    = md_sup_tables();
	$name = sanitize_text_field( $name );
	if ( '' === trim( $name ) ) { return new WP_Error( 'empty', '팀 이름을 적어 주세요.' ); }

	if ( $id > 0 ) {
		$row = array( 'name' => $name );
		$fmt = array( '%s' );
		if ( null !== $sort_no ) { $row['sort_no'] = (int) $sort_no; $fmt[] = '%d'; }
		$wpdb->update( $t['teams'], $row, array( 'id' => (int) $id ), $fmt, array( '%d' ) );
		return (int) $id;
	}
	$dup = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['teams']} WHERE name = %s", $name ) );
	if ( $dup ) { return new WP_Error( 'dup', '같은 이름의 팀이 이미 있습니다.' ); }

	/* 새 팀은 순서를 안 주면 맨 뒤로 */
	$sort = ( null === $sort_no ) ? 9999 : (int) $sort_no;
	$wpdb->insert( $t['teams'], array( 'name' => $name, 'sort_no' => $sort, 'active' => 1 ), array( '%s', '%d', '%d' ) );
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

/* ============================================================
 * v3.64 · 여러 품목을 한 번에 (N+1 질의 없애기)
 * ============================================================ */

/**
 * 품목 여러 개의 현재고를 한 번에.
 *
 * 반출관리 화면은 대기 신청 30건 × 줄마다 md_sup_stock() 을 불렀다.
 * 신청 한 건이 20줄이면 600질의다. 한 번에 받아 온다.
 *
 * @param array $ids 품목 id 목록
 * @return array [ item_id => 현재고 ] — 기록이 없는 품목은 0
 */
function md_sup_stock_map( $ids = array() ) {
	global $wpdb;
	$t = md_sup_tables();

	$ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $ids ) ) ) );
	if ( empty( $ids ) ) { return array(); }

	$in   = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, SUM(qty) AS stock FROM {$t['ledger']} WHERE item_id IN ($in) GROUP BY item_id",
		$ids
	) );

	$map = array_fill_keys( $ids, 0 );
	foreach ( $rows as $r ) { $map[ (int) $r->item_id ] = (int) $r->stock; }
	return $map;
}

/**
 * 신청서 여러 건의 품목 줄을 한 번에.
 *
 * @param array $req_ids 신청 id 목록
 * @return array [ req_id => 줄 목록 ]
 */
function md_sup_lines_for_requests( $req_ids ) {
	global $wpdb;
	$t = md_sup_tables();

	$req_ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $req_ids ) ) ) );
	if ( empty( $req_ids ) ) { return array(); }

	$in   = implode( ',', array_fill( 0, count( $req_ids ), '%d' ) );
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT ln.*,
		        COALESCE(NULLIF(i.name,''), ln.custom_name) AS name,
		        COALESCE(i.unit,'')  AS unit,
		        COALESCE(i.price,0)  AS price,
		        COALESCE(i.code,'')  AS code
		 FROM {$t['line']} ln LEFT JOIN {$t['items']} i ON i.id = ln.item_id
		 WHERE ln.req_id IN ($in) ORDER BY ln.req_id ASC, ln.id ASC",
		$req_ids
	) );

	$map = array_fill_keys( $req_ids, array() );
	foreach ( $rows as $r ) { $map[ (int) $r->req_id ][] = $r; }
	return $map;
}

/* ============================================================
 * v3.64 · 원장 조회 — 「왜 줄었는지」를 실제로 볼 수 있게
 *
 * 현재고를 저장하지 않고 원장 합계로 읽는 것이 이 시스템의 설계인데,
 * v3.63 까지 그 원장을 보는 화면이 없었다. 설계의 값어치가 화면에
 * 드러나지 않으면 그냥 느린 재고표일 뿐이다.
 * ============================================================ */

/** 입출고 사유 이름 */
function md_sup_reason_label( $reason ) {
	$map = array(
		'in'      => '입고',
		'out'     => '출고',
		'dispose' => '폐기',
		'adjust'  => '실사 조정',
	);
	return isset( $map[ $reason ] ) ? $map[ $reason ] : $reason;
}

/** 원장 조회 조건 기본값 */
function md_sup_ledger_args( $args ) {
	return wp_parse_args( $args, array(
		'item_id' => 0,
		'team_id' => 0,
		'reason'  => '',
		'ym'      => '',
		'search'  => '',
		'limit'   => 100,
		'offset'  => 0,
	) );
}

/** 조회 조건을 WHERE 절과 값으로 바꾼다 (목록·건수가 함께 쓴다) */
function md_sup_ledger_where( $a ) {
	global $wpdb;

	$where  = array( '1=1' );
	$params = array();

	if ( $a['item_id'] > 0 )   { $where[] = 'l.item_id = %d'; $params[] = (int) $a['item_id']; }
	if ( $a['team_id'] > 0 )   { $where[] = 'l.team_id = %d'; $params[] = (int) $a['team_id']; }
	if ( '' !== $a['reason'] ) { $where[] = 'l.reason = %s';  $params[] = $a['reason']; }
	if ( '' !== $a['ym'] )     { $where[] = 'l.ym = %s';      $params[] = $a['ym']; }
	if ( '' !== $a['search'] ) {
		$like     = '%' . $wpdb->esc_like( $a['search'] ) . '%';
		$where[]  = '(i.name LIKE %s OR i.code LIKE %s)';
		$params[] = $like;
		$params[] = $like;
	}

	return array( implode( ' AND ', $where ), $params );
}

/**
 * 입출고 기록 목록. 최신 순.
 * 품목·팀·처리한 사람 이름을 함께 붙여 준다.
 */
function md_sup_ledger( $args = array() ) {
	global $wpdb;
	$t = md_sup_tables();
	$a = md_sup_ledger_args( $args );

	list( $where, $params ) = md_sup_ledger_where( $a );
	$params[] = (int) $a['limit'];
	$params[] = (int) $a['offset'];

	return $wpdb->get_results( $wpdb->prepare(
		"SELECT l.*,
		        COALESCE(i.name,'') AS item_name, COALESCE(i.code,'') AS item_code,
		        COALESCE(i.unit,'') AS unit,      COALESCE(i.price,0) AS price,
		        COALESCE(tm.name,'') AS team_name,
		        COALESCE(u.display_name,'') AS user_name
		 FROM {$t['ledger']} l
		 LEFT JOIN {$t['items']} i  ON i.id  = l.item_id
		 LEFT JOIN {$t['teams']} tm ON tm.id = l.team_id
		 LEFT JOIN {$wpdb->users} u ON u.ID  = l.user_id
		 WHERE $where
		 ORDER BY l.id DESC
		 LIMIT %d OFFSET %d",
		$params
	) );
}

/** 같은 조건의 전체 건수 — 페이지 넘김에 쓴다 */
function md_sup_ledger_count( $args = array() ) {
	global $wpdb;
	$t = md_sup_tables();
	$a = md_sup_ledger_args( $args );

	list( $where, $params ) = md_sup_ledger_where( $a );

	$sql = "SELECT COUNT(*) FROM {$t['ledger']} l
	        LEFT JOIN {$t['items']} i ON i.id = l.item_id
	        WHERE $where";

	if ( $params ) { $sql = $wpdb->prepare( $sql, $params ); }
	return (int) $wpdb->get_var( $sql );
}

/**
 * 한 품목을 팀별로 얼마나 받아갔는가 (최근 N개월).
 * 품목 이력 화면에서 「어느 팀이 많이 쓰나」를 보여준다.
 */
function md_sup_item_team_usage( $item_id, $months = 3 ) {
	global $wpdb;
	$t     = md_sup_tables();
	$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . (int) $months . ' months', current_time( 'timestamp' ) ) );

	return $wpdb->get_results( $wpdb->prepare(
		"SELECT l.team_id, COALESCE(tm.name,'') AS team_name, SUM(-l.qty) AS qty
		 FROM {$t['ledger']} l LEFT JOIN {$t['teams']} tm ON tm.id = l.team_id
		 WHERE l.item_id = %d AND l.reason = 'out' AND l.created_at >= %s
		 GROUP BY l.team_id, tm.name
		 ORDER BY qty DESC",
		(int) $item_id, $since
	) );
}

/* ============================================================
 * v3.64 · 적정재고 제안
 *
 * min_stock 을 손으로 넣게 해 두면 대부분 0 으로 남는다. 0 이면
 * 「부족」 표시가 영영 뜨지 않아 입고 화면이 빈 채로 있게 된다.
 * 이미 계산하고 있는 사용량으로 제안값을 만들어 채워 넣는다.
 * ============================================================ */

/** 전 팀 합계 기준 품목별 월평균 사용량 [ item_id => 월평균 ] */
function md_sup_avg_map_all( $months = 3 ) {
	global $wpdb;
	$t     = md_sup_tables();
	$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . (int) $months . ' months', current_time( 'timestamp' ) ) );

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT item_id, SUM(-qty) AS total FROM {$t['ledger']}
		 WHERE reason = 'out' AND created_at >= %s
		 GROUP BY item_id",
		$since
	) );

	$map = array();
	foreach ( $rows as $r ) {
		$map[ (int) $r->item_id ] = $months > 0 ? round( (int) $r->total / $months, 1 ) : 0;
	}
	return $map;
}

/**
 * 제안 적정재고 = 월평균 × 여유 개월, 올림.
 * 여유 개월은 「주문해서 들어오기까지 + 그동안 쓸 만큼」이다.
 */
function md_sup_suggest_min( $avg, $lead = 1.5 ) {
	$avg  = (float) $avg;
	$lead = (float) $lead;
	if ( $avg <= 0 || $lead <= 0 ) { return 0; }
	return (int) max( 1, ceil( $avg * $lead ) );
}

/**
 * 제안값을 적정재고에 실제로 써 넣는다.
 *
 * @param float $lead      여유 개월
 * @param bool  $only_zero 참이면 아직 0 인 품목만 (담당자가 손으로 정한 값은 건드리지 않는다)
 * @return int 바뀐 품목 수
 */
function md_sup_apply_min_suggestions( $lead = 1.5, $only_zero = true ) {
	global $wpdb;
	$t   = md_sup_tables();
	$avg = md_sup_avg_map_all( 3 );
	if ( empty( $avg ) ) { return 0; }

	$n = 0;
	foreach ( md_sup_items() as $it ) {
		if ( $only_zero && (int) $it->min_stock > 0 ) { continue; }
		$want = md_sup_suggest_min( isset( $avg[ $it->id ] ) ? $avg[ $it->id ] : 0, $lead );
		if ( $want <= 0 || $want === (int) $it->min_stock ) { continue; }
		$wpdb->update( $t['items'], array( 'min_stock' => $want ), array( 'id' => (int) $it->id ), array( '%d' ), array( '%d' ) );
		$n++;
	}
	return $n;
}

/* ============================================================
 * v3.64 · 대기 신청 알림
 *
 * 담당자가 화면에 들어와야만 대기 건을 알 수 있었다.
 * 긴급 신청이 오후 내내 방치되는 일을 메일 한 통으로 막는다.
 * ============================================================ */

/** 알림 받을 주소. 비워 두면 사이트 관리자 메일. 'off' 면 보내지 않는다. */
function md_sup_notify_emails() {
	$raw = trim( (string) get_option( 'md_sup_notify_emails', '' ) );
	if ( 'off' === strtolower( $raw ) ) { return array(); }

	$out = array();
	foreach ( explode( ',', $raw ) as $e ) {
		$e = trim( $e );
		if ( is_email( $e ) ) { $out[] = $e; }
	}

	if ( empty( $out ) ) {
		$admin = get_option( 'admin_email' );
		if ( is_email( $admin ) ) { $out[] = $admin; }
	}
	return array_values( array_unique( $out ) );
}

/**
 * 새 신청이 들어왔음을 알린다.
 * 메일이 막힌 호스팅에서도 신청 자체는 이미 저장된 뒤이므로 조용히 넘어간다.
 */
function md_sup_notify_new_request( $req_id ) {
	$to = md_sup_notify_emails();
	if ( empty( $to ) ) { return false; }

	$r = md_sup_request_summary( $req_id );
	if ( ! $r ) { return false; }

	$lines   = md_sup_request_lines( $req_id );
	$urgent  = (int) $r->urgent;
	$subject = ( $urgent ? '[긴급] ' : '' ) . '재료실 신청 — ' . $r->team_name . ' · ' . (int) $r->line_count . '개 품목';

	$body  = $r->team_name . ' 에서 재료를 신청했습니다.' . "\n\n";
	$body .= '신청 시각 · ' . mysql2date( 'Y-m-d H:i', $r->created_at ) . "\n";
	$body .= '품목 수 · ' . (int) $r->line_count . "\n";
	$body .= '예상 금액 · ' . md_sup_won( (int) $r->amount ) . "\n";
	if ( $urgent ) { $body .= "\n※ 오늘 필요한 긴급 신청입니다.\n"; }
	if ( '' !== trim( (string) $r->note ) ) { $body .= "\n메모 · " . $r->note . "\n"; }

	$body .= "\n— 신청 품목 —\n";
	foreach ( $lines as $ln ) {
		$body .= '· ' . $ln->name . '  ' . (int) $ln->qty_req . ( $ln->unit ? ' ' . $ln->unit : '' ) . "\n";
	}

	$url = function_exists( 'md_sup_url' )
		? md_sup_url( array( 'app' => 'stock', 'tab' => 'manage' ) )
		: home_url( '/직원/' );
	$body .= "\n반출관리에서 처리해 주세요 · " . $url . "\n";

	/* 실패해도 신청은 이미 저장돼 있다 — 화면을 막지 않는다 */
	return wp_mail( $to, $subject, $body );
}
