<?php
/**
 * v6.0 · 선납 개선 (원장 지시 2026-10-05)
 *
 *   1  단가 0원 선납 품목 — 입고를 막고(무상 체크 제외) 선납 화면에 경고
 *   2  업체별 통장식 거래 내역 — 입금 · 입고 · 반품 · 조정을 날짜순으로, 잔액을 이어서
 *   3  업체 잔액 대조 — 업체가 알려 준 잔액과 우리 장부의 차이 · 원인 후보 · 조정 한 줄
 *   4  잔액 부족 알림(업체마다 기준) · 주문할 때 「주문 뒤 쓸 수 있는 잔액」
 *   5  선납 적립 — 입금(실제 낸 돈)과 쓸 수 있는 금액(적립 포함)을 따로
 *   6  소진 예상 — 최근 90일 차감 속도
 *   7  무상 입고는 사유 필수
 *   8  LOT · 차트번호 — 입고 때 LOT, 출고 때 차트번호(+LOT, 비우면 먼저 들어온 LOT)
 *
 * 잔액 = 쓸 수 있는 금액 합계(입금 · 적립 · 조정) − 입고 금액(무상 제외) + 반품 금액
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 1 · 단가 0원 선납 품목
 * ============================================================ */

function md_inv_is_prepaid_vendor( $vendor_id ) {
	$v = $vendor_id ? md_inv_vendor( $vendor_id ) : null;
	return $v && (int) $v->prepaid;
}

/** 선납 업체 품목인데 단가가 0원인 것 (숨김 제외) */
function md_inv_prepaid_zero_items( $vendor_id = 0 ) {
	global $wpdb;
	$t   = md_inv_t();
	$ids = $vendor_id ? array( (int) $vendor_id ) : md_inv_prepaid_vendor_ids();
	if ( ! $ids ) { return array(); }
	return (array) $wpdb->get_results( "SELECT id, name, vendor_id, unit FROM {$t['item']} WHERE active = 1 AND price = 0 AND vendor_id IN (" . implode( ',', array_map( 'intval', $ids ) ) . ') ORDER BY name' );
}

/* ============================================================
 * 5 · 입금 · 적립
 * ============================================================ */

/** 업체의 기본 적립률로 쓸 수 있는 금액을 계산 (돌려받은 돈은 그대로) */
function md_inv_credit_for( $vendor_id, $amount ) {
	$v   = md_inv_vendor( $vendor_id );
	$pct = $v ? (float) $v->pp_bonus : 0.0;
	return $amount > 0 ? (int) round( $amount * ( 1 + $pct / 100 ) ) : (int) $amount;
}

/** 옛 입금 기록(적립 칸이 없던 때) · 옛 백업을 되돌린 뒤 — 쓸 수 있는 금액을 입금액으로 채운다 */
function md_inv_deposit_fix_credit() {
	global $wpdb;
	$t = md_inv_t( 'deposit' );
	$cols = $wpdb->get_col( "SHOW COLUMNS FROM $t" );
	if ( ! in_array( 'credit', (array) $cols, true ) ) { return; }
	$wpdb->query( "UPDATE $t SET credit = amount WHERE credit = 0 AND amount <> 0 AND (kind = '' OR kind = 'pay')" );
	$wpdb->query( "UPDATE $t SET kind = 'pay' WHERE kind = ''" );
}

/* ============================================================
 * 2 · 통장식 거래 내역
 * ============================================================ */

/**
 * @return array [ 'open' => 이전 잔액, 'rows' => [ (object) date, kind, label, item, qty, plus, minus, bal, person, note, ref ], 'close' => 끝 잔액 ]
 */
function md_inv_passbook( $vendor_id, $from = '', $to = '' ) {
	global $wpdb;
	$t   = md_inv_t();
	$vid = (int) $vendor_id;
	$ev  = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t['deposit']} WHERE vendor_id = %d", $vid ) ) as $d ) {
		$adj   = 'adjust' === $d->kind;
		$bonus = ! $adj && (int) $d->credit !== (int) $d->amount ? (int) $d->credit - (int) $d->amount : 0;
		$ev[]  = (object) array(
			'at' => $d->paid_on . ' ' . substr( (string) $d->created_at, 11, 8 ), 'date' => $d->paid_on,
			'kind' => $adj ? 'adjust' : ( (int) $d->amount < 0 ? 'refund' : 'deposit' ),
			'label' => $adj ? '잔액 조정' : ( (int) $d->amount < 0 ? '돌려받음' : '입금' ),
			'item' => '', 'qty' => 0, 'lot' => '',
			'delta' => (int) $d->credit,
			'note' => trim( ( $bonus ? '입금 ' . md_inv_num( $d->amount ) . ' + 적립 ' . md_inv_num( $bonus ) . ' · ' : '' ) . (string) $d->note, ' ·' ),
			'person' => $d->person, 'ref' => 'd' . $d->id,
		);
	}
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT l.*, COALESCE(i.name,'') AS item_name, COALESCE(i.unit,'') AS unit FROM {$t['ledger']} l LEFT JOIN {$t['item']} i ON i.id = l.item_id
		 WHERE l.vendor_id = %d AND l.voided = 0 AND l.type IN ('in','return')", $vid ) );
	foreach ( (array) $rows as $l ) {
		$amt  = abs( (int) $l->qty ) * (int) $l->price;
		$ev[] = (object) array(
			'at' => $l->created_at, 'date' => substr( $l->created_at, 0, 10 ),
			'kind' => 'in' === $l->type ? ( $l->free ? 'free' : 'in' ) : 'return',
			'label' => 'in' === $l->type ? ( $l->free ? '무상 입고' : '입고' ) : '반품',
			'item' => $l->item_name, 'qty' => abs( (int) $l->qty ), 'lot' => (string) $l->lot,
			'delta' => $l->free ? 0 : ( 'in' === $l->type ? -$amt : $amt ),
			'note' => trim( (string) $l->note ), 'person' => $l->person, 'ref' => 'l' . $l->id, 'price' => (int) $l->price, 'unit' => $l->unit,
		);
	}
	usort( $ev, function ( $a, $b ) { return strcmp( $a->at . $a->ref, $b->at . $b->ref ); } );
	$bal = 0; $open = 0; $out = array();
	foreach ( $ev as $e ) {
		if ( '' !== $from && $e->date < $from ) { $bal += $e->delta; $open = $bal; continue; }
		if ( '' !== $to && $e->date > $to ) { continue; }
		$bal += $e->delta;
		$e->plus  = $e->delta > 0 ? $e->delta : 0;
		$e->minus = $e->delta < 0 ? -$e->delta : 0;
		$e->bal   = $bal;
		$out[] = $e;
	}
	return array( 'open' => $open, 'rows' => $out, 'close' => $bal );
}

/** 그날 끝 잔액 */
function md_inv_balance_at( $vendor_id, $date ) {
	$pb = md_inv_passbook( $vendor_id, '', $date );
	return (int) $pb['close'];
}

/* ============================================================
 * 3 · 업체 잔액 대조
 * ============================================================ */

function md_inv_recons( $vendor_id ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . md_inv_t( 'recon' ) . ' WHERE vendor_id = %d ORDER BY as_of DESC, id DESC LIMIT 24', (int) $vendor_id ) );
}

function md_inv_recon_add( $vendor_id, $as_of, $vendor_bal, $note = '' ) {
	global $wpdb;
	if ( ! md_inv_is_prepaid_vendor( $vendor_id ) ) { return new WP_Error( 'vendor', '선납 업체를 골라 주세요.' ); }
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $as_of ) ) { $as_of = current_time( 'Y-m-d' ); }
	if ( $as_of > current_time( 'Y-m-d' ) ) { return new WP_Error( 'date', '오늘 이후 날짜로는 대조할 수 없습니다.' ); }
	if ( '' === trim( (string) $vendor_bal ) ) { return new WP_Error( 'bal', '업체가 알려 준 잔액을 적어 주세요.' ); }
	$vb  = md_inv_int( $vendor_bal );
	$our = md_inv_balance_at( $vendor_id, $as_of );
	$wpdb->insert( md_inv_t( 'recon' ), array(
		'vendor_id' => (int) $vendor_id, 'as_of' => $as_of, 'vendor_bal' => $vb, 'our_bal' => $our,
		'note' => md_inv_txt( $note, 255 ), 'person' => md_inv_me(), 'user_id' => get_current_user_id(), 'created_at' => current_time( 'mysql' ),
	) );
	$id = (int) $wpdb->insert_id; /* 작업 기록을 남기면 insert_id 가 바뀐다 — 먼저 잡아 둔다 */
	md_inv_log( '선납 잔액 대조', md_inv_vendor_name( $vendor_id ) . ' ' . $as_of . ' 업체 ' . md_inv_won( $vb ) . ' · 장부 ' . md_inv_won( $our ) . ( $vb !== $our ? ' · 차이 ' . md_inv_won( $vb - $our ) : ' · 일치' ) );
	return $id;
}

/** 차이를 「잔액 조정」 한 줄로 맞춘다 */
function md_inv_recon_adjust( $recon_id ) {
	global $wpdb;
	$r = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_inv_t( 'recon' ) . ' WHERE id = %d', (int) $recon_id ) );
	if ( ! $r ) { return new WP_Error( 'gone', '대조 기록을 찾을 수 없습니다.' ); }
	if ( (int) $r->adj_id ) { return new WP_Error( 'done', '이미 조정했습니다.' ); }
	/* 대조한 뒤 그날 이전 기록이 바뀌었을 수 있으니 지금 다시 잰다 */
	$diff = (int) $r->vendor_bal - md_inv_balance_at( $r->vendor_id, $r->as_of );
	if ( 0 === $diff ) { return new WP_Error( 'zero', '지금은 차이가 없습니다 — 조정할 것이 없습니다.' ); }
	$wpdb->insert( md_inv_t( 'deposit' ), array(
		'vendor_id' => (int) $r->vendor_id, 'paid_on' => $r->as_of, 'amount' => 0, 'credit' => $diff, 'kind' => 'adjust',
		'note' => '업체 잔액 대조 조정 (' . $r->as_of . ' 업체 ' . md_inv_num( $r->vendor_bal ) . ')', 'person' => md_inv_me(), 'user_id' => get_current_user_id(),
		'created_at' => current_time( 'mysql' ),
	) );
	$adj = (int) $wpdb->insert_id;
	$wpdb->update( md_inv_t( 'recon' ), array( 'adj_id' => $adj ), array( 'id' => (int) $r->id ) );
	md_inv_log( '선납 잔액 조정', md_inv_vendor_name( $r->vendor_id ) . ' ' . ( $diff > 0 ? '+' : '' ) . md_inv_won( $diff ) );
	return $adj;
}

/** 차이의 원인 후보 — 지난 대조 뒤의 기록 중에서 */
function md_inv_recon_hints( $vendor_id, $as_of, $diff ) {
	if ( 0 === (int) $diff ) { return array(); }
	$since = '';
	/* 지난번 대조(그때 맞았거나 조정한 것) 다음 날부터 본다 */
	foreach ( md_inv_recons( $vendor_id ) as $r ) {
		if ( $r->as_of < $as_of && ( (int) $r->vendor_bal === (int) $r->our_bal || (int) $r->adj_id ) ) { $since = date( 'Y-m-d', strtotime( $r->as_of . ' +1 day' ) ); break; }
	}
	$pb   = md_inv_passbook( $vendor_id, $since, $as_of );
	$abs  = abs( (int) $diff );
	$out  = array();
	foreach ( $pb['rows'] as $e ) {
		$amt = $e->plus + $e->minus;
		if ( $amt === $abs ) { $out[] = '금액이 차이와 똑같은 기록: ' . $e->date . ' ' . $e->label . ' ' . trim( $e->item . ' ' . ( $e->qty ? $e->qty . '개' : '' ) ) . ' (' . md_inv_num( $amt ) . ') — 업체 쪽에 빠졌거나 두 번 들어갔을 수 있습니다.'; }
		if ( 'in' === $e->kind && isset( $e->price ) && 0 === (int) $e->price ) { $out[] = $e->date . ' 「' . $e->item . '」 단가 0원 입고 — 업체는 금액을 뺐을 수 있습니다.'; }
		if ( 'free' === $e->kind ) {
			$it = md_inv_item_by_name_vendor( $e->item, $vendor_id );
			if ( $it && $it->price * $e->qty === $abs ) { $out[] = $e->date . ' 「' . $e->item . '」 무상 입고 — 업체는 유상으로 처리했을 수 있습니다.'; }
		}
	}
	foreach ( md_inv_ords( array( 'vendor_id' => $vendor_id, 'status' => 'ordered', 'limit' => 50 ) ) as $o ) {
		$left = (int) round( (float) $o->amount * ( (int) $o->qty - (int) $o->recv_qty ) / max( 1, (int) $o->qty ) );
		if ( $left === $abs && $diff < 0 ) { $out[] = '아직 입고 안 한 주문 「' . $o->item_name . '」(' . md_inv_num( $left ) . ') — 업체는 이미 출고하며 뺐을 수 있습니다. 물건이 왔다면 입고해 주세요.'; }
	}
	return array_slice( array_values( array_unique( $out ) ), 0, 8 );
}

function md_inv_item_by_name_vendor( $name, $vendor_id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_inv_t( 'item' ) . ' WHERE name = %s AND vendor_id = %d LIMIT 1', $name, (int) $vendor_id ) );
}

/* ============================================================
 * 4 · 6 · 잔액 부족 · 소진 예상 (md_inv_prepaid_summary 가 채운다)
 * ============================================================ */

/** 잔액 부족 업체 */
function md_inv_prepaid_alerts() {
	$out = array();
	foreach ( md_inv_prepaid_summary() as $p ) { if ( $p->alert ) { $out[] = $p; } }
	return $out;
}

/** 이 품목을 이 금액만큼 주문하면 — 선납 업체가 아니면 null */
function md_inv_prepaid_after_order( $item_id, $amount ) {
	$it = md_inv_item( $item_id );
	if ( ! $it || ! md_inv_is_prepaid_vendor( $it->vendor_id ) ) { return null; }
	$s = md_inv_prepaid_summary();
	if ( ! isset( $s[ (int) $it->vendor_id ] ) ) { return null; }
	$p = $s[ (int) $it->vendor_id ];
	return (object) array( 'vendor' => $p->vendor->name, 'available' => $p->available, 'after' => $p->available - (int) $amount );
}

/* ============================================================
 * 8 · LOT · 차트번호
 * ============================================================ */

/** 품목의 LOT 별 남은 수량 — 먼저 들어온 LOT 부터 */
function md_inv_lot_stock( $item_id ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare(
		'SELECT lot, SUM(qty) AS q, MIN(created_at) AS first_in FROM ' . md_inv_t( 'ledger' ) . " WHERE item_id = %d AND voided = 0 AND lot <> '' GROUP BY lot ORDER BY first_in, lot", (int) $item_id ) );
	$out = array();
	foreach ( (array) $rows as $r ) { if ( (int) $r->q > 0 ) { $out[ $r->lot ] = (int) $r->q; } }
	return $out;
}

/** 출고 때 LOT 를 비우면 — 남은 것 중 먼저 들어온 LOT */
function md_inv_lot_fifo( $item_id ) {
	$l = md_inv_lot_stock( $item_id );
	return $l ? (string) key( $l ) : '';
}

function md_inv_lot_txt( $v ) {
	return mb_substr( preg_replace( '/\s+/u', ' ', trim( sanitize_text_field( (string) $v ) ) ), 0, 80 );
}

function md_inv_chart_txt( $v ) {
	return mb_substr( preg_replace( '/[^0-9A-Za-z\-]/', '', (string) $v ), 0, 40 );
}

/** LOT 또는 차트번호로 찾기 */
function md_inv_lot_search( $q, $limit = 300 ) {
	global $wpdb;
	$t = md_inv_t();
	$q = trim( (string) $q );
	if ( '' === $q ) {
		$where = "l.voided = 0 AND (l.lot <> '' OR l.chart <> '')";
		$args  = array();
	} else {
		$like  = '%' . $wpdb->esc_like( $q ) . '%';
		$where = 'l.voided = 0 AND (l.lot LIKE %s OR l.chart = %s)';
		$args  = array( $like, md_inv_chart_txt( $q ) );
	}
	$sql = "SELECT l.*, COALESCE(i.name,'') AS item_name, COALESCE(i.unit,'') AS unit FROM {$t['ledger']} l LEFT JOIN {$t['item']} i ON i.id = l.item_id WHERE $where ORDER BY l.created_at DESC, l.id DESC LIMIT " . (int) $limit;
	return (array) $wpdb->get_results( $args ? $wpdb->prepare( $sql, $args ) : $sql );
}

/** 업체 품목의 LOT 추적 켜고 끄기 (선납 화면에서 한꺼번에) */
function md_inv_lot_track_save( $vendor_id, $on_ids ) {
	global $wpdb;
	$t   = md_inv_t( 'item' );
	$on  = array_map( 'intval', (array) $on_ids );
	$all = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $t WHERE vendor_id = %d", (int) $vendor_id ) );
	$n = 0;
	foreach ( $all as $id ) {
		$want = in_array( (int) $id, $on, true ) ? 1 : 0;
		$n   += (int) $wpdb->query( $wpdb->prepare( "UPDATE $t SET track_lot = %d WHERE id = %d AND track_lot <> %d", $want, (int) $id, $want ) );
	}
	md_inv_log( 'LOT 추적 설정', md_inv_vendor_name( $vendor_id ) . ' · ' . count( $on ) . '개 품목' );
	return $n;
}

/* ============================================================
 * 처리
 * ============================================================ */

function md_inv_act_recon_add() {
	$vid = (int) md_inv_p( 'vendor_id' );
	$r   = md_inv_recon_add( $vid, (string) md_inv_p( 'as_of' ), (string) md_inv_p( 'vendor_bal' ), (string) md_inv_p( 'note' ) );
	if ( is_wp_error( $r ) ) { md_inv_go( 'err', $r->get_error_message() ); }
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_inv_t( 'recon' ) . ' WHERE id = %d', $r ) );
	$d   = (int) $row->vendor_bal - (int) $row->our_bal;
	$to  = md_inv_url( array( 'iv' => 'prepaid', 'ivd' => $vid ) ) . '#iv-recon';
	if ( 0 === $d ) { md_inv_go( 'ok', '업체 잔액과 장부가 맞습니다 (' . md_inv_won( $row->our_bal ) . ').', $to ); }
	md_inv_go( 'warn', '업체 잔액이 장부보다 ' . md_inv_won( abs( $d ) ) . ( $d > 0 ? ' 많습니다' : ' 적습니다' ) . '. 아래 「원인 후보」를 확인하고, 원인을 찾아 고치거나 「조정으로 맞추기」를 눌러 주세요.', $to );
}

function md_inv_act_recon_adjust() {
	$r = md_inv_recon_adjust( (int) md_inv_p( 'id' ) );
	md_inv_done( $r, '차이를 「잔액 조정」으로 맞췄습니다. 통장식 내역에 한 줄로 남았습니다.' );
}

function md_inv_act_lot_track() {
	$vid = (int) md_inv_p( 'vendor_id' );
	md_inv_lot_track_save( $vid, (array) md_inv_p( 'track', array() ) );
	md_inv_go( 'ok', 'LOT · 차트번호 추적 품목을 저장했습니다.', md_inv_url( array( 'iv' => 'prepaid', 'ivd' => $vid ) ) . '#iv-lottrack' );
}

/* ============================================================
 * 화면 조각
 * ============================================================ */

/** 선납 화면 맨 위 — 경고 (단가 0원 · 잔액 부족) */
function md_inv_prepaid_warnings( $sum ) {
	$zero = md_inv_prepaid_zero_items();
	if ( $zero ) {
		$names = array_map( function ( $z ) { return $z->name; }, array_slice( $zero, 0, 6 ) );
		echo '<div class="iv-flash iv-flash--warn iv-ppwarn" id="iv-ppzero"><b>단가 없는 선납 품목 ' . count( $zero ) . '개</b> — 입고해도 잔액에서 빠지지 않아 입고를 막아 두었습니다. 단가를 넣어 주세요: '
			. esc_html( implode( ', ', $names ) . ( count( $zero ) > 6 ? ' 외 ' . ( count( $zero ) - 6 ) . '개' : '' ) )
			. ' <a class="iv-link" href="' . esc_url( md_inv_url( array( 'iv' => 'fix', 'ik' => 'noprice', 'ivd' => (int) $zero[0]->vendor_id ) ) ) . '">단가 넣으러 가기 →</a></div>';
	}
	foreach ( $sum as $p ) {
		if ( ! $p->alert ) { continue; }
		echo '<div class="iv-flash iv-flash--warn iv-ppwarn"><b>' . esc_html( $p->vendor->name ) . ' 잔액 부족</b> — 쓸 수 있는 잔액 ' . esc_html( md_inv_won( $p->available ) ) . ' (알림 기준 ' . esc_html( md_inv_won( $p->vendor->pp_alert ) ) . ')' . ( $p->months_left !== null ? ' · 지금 속도면 약 ' . esc_html( md_inv_months_txt( $p->months_left ) ) . ' 뒤 소진' : '' ) . '. 재입금을 준비해 주세요.</div>';
	}
}

function md_inv_months_txt( $m ) {
	if ( null === $m ) { return ''; }
	if ( $m <= 0 ) { return '이미 소진'; }
	if ( $m < 1 ) { return max( 1, (int) round( $m * 30 ) ) . '일'; }
	return ( $m < 10 ? number_format( $m, 1 ) : (string) (int) round( $m ) ) . '개월';
}

/** 업체 상세 — 통장식 내역 */
function md_inv_prepaid_passbook_section( $vid ) {
	$df = md_inv_get_date( 'df', date( 'Y-m-d', current_time( 'timestamp' ) - 180 * DAY_IN_SECONDS ) );
	$dt = md_inv_get_date( 'dt', current_time( 'Y-m-d' ) );
	$pb = md_inv_passbook( $vid, $df, $dt );
	?>
	<section class="iv-panel" id="iv-passbook">
		<h3 class="iv-h3">통장식 거래 내역</h3>
		<form class="iv-filter" method="get">
			<input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="prepaid"><input type="hidden" name="ivd" value="<?php echo (int) $vid; ?>">
			<label class="iv-f"><span>부터</span><input class="iv-input" type="date" name="df" value="<?php echo esc_attr( $df ); ?>"></label>
			<label class="iv-f"><span>까지</span><input class="iv-input" type="date" name="dt" value="<?php echo esc_attr( $dt ); ?>"></label>
			<button class="iv-btn iv-btn--ghost">보기</button>
			<?php md_inv_dl_buttons( 'passbook', array( 'ivd' => $vid, 'df' => $df, 'dt' => $dt ), '엑셀' ); ?>
		</form>
		<div class="iv-table-wrap"><table class="iv-table iv-table--pb"><thead><tr><th>날짜</th><th>내용</th><th class="r">들어옴</th><th class="r">나감</th><th class="r">잔액</th><th>메모 · 처리</th></tr></thead><tbody>
			<tr class="iv-pb-open"><td data-l="날짜"><?php echo esc_html( $df ); ?></td><td data-l="내용">이전 잔액</td><td></td><td></td><td data-l="잔액" class="r"><b><?php echo esc_html( md_inv_num( $pb['open'] ) ); ?></b></td><td></td></tr>
			<?php foreach ( $pb['rows'] as $e ) : ?>
				<tr class="iv-pb iv-pb--<?php echo esc_attr( $e->kind ); ?>">
					<td data-l="날짜"><?php echo esc_html( $e->date ); ?></td>
					<td data-l="내용"><b><?php echo esc_html( $e->label ); ?></b><?php echo '' !== $e->item ? ' · ' . esc_html( $e->item . ' ' . $e->qty . ( isset( $e->unit ) && $e->unit ? $e->unit : '개' ) ) : ''; ?><?php echo '' !== $e->lot ? ' <small>LOT ' . esc_html( $e->lot ) . '</small>' : ''; ?></td>
					<td data-l="들어옴" class="r"><?php echo $e->plus ? esc_html( md_inv_num( $e->plus ) ) : ''; ?></td>
					<td data-l="나감" class="r"><?php echo $e->minus ? esc_html( md_inv_num( $e->minus ) ) : ( 'free' === $e->kind ? '<small>무상</small>' : '' ); ?></td>
					<td data-l="잔액" class="r"><b><?php echo esc_html( md_inv_num( $e->bal ) ); ?></b></td>
					<td data-l="메모 · 처리"><small><?php echo esc_html( trim( $e->note . ' · ' . $e->person, ' ·' ) ); ?></small></td>
				</tr>
			<?php endforeach; ?>
			<?php if ( ! $pb['rows'] ) : ?><tr><td colspan="6" class="iv-muted">이 기간에는 기록이 없습니다.</td></tr><?php endif; ?>
		</tbody></table></div>
		<p class="iv-help">업체가 보내 준 거래 명세서와 날짜 · 금액을 한 줄씩 맞춰 보세요. 무상 입고는 잔액에 영향이 없어 「무상」으로만 보입니다.</p>
	</section>
	<?php
}

/** 업체 상세 — 잔액 대조 */
function md_inv_prepaid_recon_section( $vid ) {
	$list = md_inv_recons( $vid );
	?>
	<section class="iv-panel" id="iv-recon">
		<h3 class="iv-h3">업체 잔액 대조</h3>
		<p class="iv-help">업체가 알려 준 잔액(명세서 · 잔액 확인서)을 넣으면 그날 장부 잔액과 비교합니다. 매달 한 번 권합니다.</p>
		<form method="post" class="iv-inline-form iv-recon-form">
			<?php md_inv_hidden( 'recon_add' ); ?><input type="hidden" name="vendor_id" value="<?php echo (int) $vid; ?>">
			<label class="iv-f"><span>기준일</span><input class="iv-input" type="date" name="as_of" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" max="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required></label>
			<label class="iv-f"><span>업체가 알려 준 잔액 (원)</span><input class="iv-input" name="vendor_bal" inputmode="numeric" required></label>
			<label class="iv-f iv-f--grow"><span>메모</span><input class="iv-input" name="note" maxlength="200" placeholder="예: 9월 거래명세서"></label>
			<button class="iv-btn iv-btn--primary">대조</button>
		</form>
		<?php if ( $list ) : ?>
		<div class="iv-table-wrap"><table class="iv-table"><thead><tr><th>기준일</th><th class="r">업체</th><th class="r">장부</th><th class="r">차이</th><th>결과</th><th></th></tr></thead><tbody>
			<?php foreach ( $list as $i => $r ) : $d = (int) $r->vendor_bal - (int) $r->our_bal; ?>
				<tr class="<?php echo $d && ! (int) $r->adj_id ? 'iv-recon--diff' : ''; ?>">
					<td data-l="기준일"><?php echo esc_html( $r->as_of ); ?></td>
					<td data-l="업체" class="r"><?php echo esc_html( md_inv_num( $r->vendor_bal ) ); ?></td>
					<td data-l="장부" class="r"><?php echo esc_html( md_inv_num( $r->our_bal ) ); ?></td>
					<td data-l="차이" class="r"><b><?php echo $d ? esc_html( ( $d > 0 ? '+' : '' ) . md_inv_num( $d ) ) : '0'; ?></b></td>
					<td data-l="결과"><?php echo ! $d ? '✓ 일치' : ( (int) $r->adj_id ? '조정함' : '차이 있음' ); ?><small> · <?php echo esc_html( trim( $r->person . ' ' . $r->note ) ); ?></small></td>
					<td class="iv-td-act">
						<?php if ( $d && ! (int) $r->adj_id ) : ?>
							<form method="post" class="iv-inline-form" data-confirm="<?php echo esc_attr( '차이 ' . md_inv_num( $d ) . '원을 「잔액 조정」 한 줄로 맞출까요? 원인을 찾아 고칠 수 있으면 그게 먼저입니다.' ); ?>"><?php md_inv_hidden( 'recon_adjust' ); ?><input type="hidden" name="id" value="<?php echo (int) $r->id; ?>"><button class="iv-btn iv-btn--ghost iv-btn--xs">조정으로 맞추기</button></form>
						<?php endif; ?>
					</td>
				</tr>
				<?php if ( 0 === $i && $d && ! (int) $r->adj_id ) : $hints = md_inv_recon_hints( $vid, $r->as_of, $d ); ?>
					<tr class="iv-recon-hints"><td colspan="6"><b>원인 후보</b><?php if ( $hints ) : ?><ul><?php foreach ( $hints as $h ) : ?><li><?php echo esc_html( $h ); ?></li><?php endforeach; ?></ul><?php else : ?> — 금액이 딱 맞는 기록은 없습니다. 위 통장식 내역을 업체 명세서와 한 줄씩 맞춰 보세요.<?php endif; ?></td></tr>
				<?php endif; ?>
			<?php endforeach; ?>
		</tbody></table></div>
		<?php endif; ?>
	</section>
	<?php
}

/** 업체 상세 — LOT 추적 품목 고르기 */
function md_inv_prepaid_lot_section( $vid ) {
	$items = md_inv_items( array( 'vendor' => $vid ) );
	if ( ! $items ) { return; }
	$on = 0; foreach ( $items as $it ) { if ( (int) $it->track_lot ) { $on++; } }
	?>
	<section class="iv-panel" id="iv-lottrack">
		<details<?php echo $on ? '' : ' open'; ?>><summary class="iv-h3">LOT · 차트번호 추적 품목 <small><?php echo (int) $on; ?> / <?php echo count( $items ); ?>개</small></summary>
			<p class="iv-help">체크한 품목은 입고 때 LOT 번호를, 출고 때 환자 차트번호를 남깁니다(둘 다 비워도 저장은 됩니다 · 출고 LOT 를 비우면 먼저 들어온 LOT). 리콜 · 불량 때 「LOT 찾기」로 어느 환자에게 들어갔는지 바로 찾습니다. 임플란트 픽스처처럼 몸에 들어가는 품목을 고르세요.</p>
			<form method="post">
				<?php md_inv_hidden( 'lot_track' ); ?><input type="hidden" name="vendor_id" value="<?php echo (int) $vid; ?>">
				<div class="iv-lotgrid">
					<?php foreach ( $items as $it ) : ?>
						<label class="iv-check"><input type="checkbox" name="track[]" value="<?php echo (int) $it->id; ?>"<?php checked( (int) $it->track_lot, 1 ); ?>> <?php echo esc_html( $it->name ); ?></label>
					<?php endforeach; ?>
				</div>
				<div class="iv-toolbar"><label class="iv-check"><input type="checkbox" data-checkall="track[]"> 모두</label><button class="iv-btn iv-btn--primary iv-btn--sm">저장</button><a class="iv-btn iv-btn--ghost iv-btn--sm" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'lot' ) ) ); ?>">LOT · 차트번호 찾기 →</a></div>
			</form>
		</details>
	</section>
	<?php
}

/** 화면 · LOT · 차트번호 찾기 */
function md_inv_view_lot() {
	$q    = md_inv_get( 'iq' );
	$rows = md_inv_lot_search( $q );
	?>
	<p class="iv-crumb"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'prepaid' ) ) ); ?>">← 선납</a></p>
	<h2 class="iv-h2">LOT · 차트번호 찾기</h2>
	<p class="iv-help">리콜 · 불량 공지가 오면 LOT 번호를, 환자에게 들어간 제품이 궁금하면 차트번호를 넣으세요. 바코드로 LOT 를 찍어도 됩니다.</p>
	<form class="iv-filter" method="get">
		<input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="lot">
		<label class="iv-f iv-f--grow"><span>LOT 또는 차트번호</span><span class="iv-inline"><input class="iv-input" type="search" name="iq" value="<?php echo esc_attr( $q ); ?>" placeholder="예: 2405A1 · 12345" autofocus><button type="button" class="iv-btn iv-btn--icon" data-scanto="iq" aria-label="바코드 스캔"><?php echo md_inv_icon( 'scan', 18 ); // phpcs:ignore ?></button></span></label>
		<button class="iv-btn iv-btn--primary">찾기</button>
		<?php md_inv_dl_buttons( 'lots', array( 'iq' => $q ), '엑셀' ); ?>
	</form>
	<?php
	if ( ! $rows ) { md_inv_empty( '' === $q ? 'LOT · 차트번호가 남은 기록이 아직 없습니다. 선납 › 업체 › 「LOT · 차트번호 추적 품목」에서 품목을 고르면 입고 · 출고 때 남길 수 있습니다.' : '「' . $q . '」에 해당하는 기록이 없습니다.' ); return; }
	$charts = array();
	foreach ( $rows as $l ) { if ( 'out' === $l->type && '' !== (string) $l->chart ) { $charts[ $l->chart ] = 1; } }
	if ( '' !== $q && $charts ) { echo '<div class="iv-sent">이 검색에 해당하는 환자 차트번호 <b>' . count( $charts ) . '명</b>: ' . esc_html( implode( ', ', array_keys( $charts ) ) ) . '</div>'; }
	echo '<div class="iv-table-wrap"><table class="iv-table iv-table--lot"><thead><tr><th>일시</th><th>구분</th><th>품목</th><th class="r">수량</th><th>LOT</th><th>차트번호</th><th>팀 · 처리</th></tr></thead><tbody>';
	foreach ( $rows as $l ) {
		echo '<tr><td data-l="일시">' . esc_html( md_inv_date( $l->created_at, 'y.n.j H:i' ) ) . '</td><td data-l="구분">' . esc_html( md_inv_type_label( $l->type ) ) . '</td>'
			. '<td data-l="품목"><a href="' . esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $l->item_id ) ) ) . '">' . esc_html( $l->item_name ) . '</a></td>'
			. '<td data-l="수량" class="r">' . ( $l->qty > 0 ? '+' : '' ) . (int) $l->qty . '</td>'
			. '<td data-l="LOT"><code>' . esc_html( (string) $l->lot ) . '</code></td><td data-l="차트번호"><b>' . esc_html( (string) $l->chart ) . '</b></td>'
			. '<td data-l="팀 · 처리">' . esc_html( trim( md_inv_team_name( $l->team_id ) . ' ' . $l->person . ( '' !== (string) $l->receiver ? ' · 받음 ' . $l->receiver : '' ) ) ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/** 품목 화면 — LOT 별 남은 수량 */
function md_inv_item_lot_panel( $it ) {
	if ( ! (int) $it->track_lot ) { return; }
	$lots = md_inv_lot_stock( $it->id );
	echo '<section class="iv-panel"><h3 class="iv-h3">LOT 별 남은 수량</h3>';
	if ( ! $lots ) { echo '<p class="iv-help">LOT 를 남긴 입고가 아직 없습니다.</p>'; }
	else {
		echo '<div class="iv-pills">';
		foreach ( $lots as $lot => $q ) { echo '<a class="iv-pill" href="' . esc_url( md_inv_url( array( 'iv' => 'lot', 'iq' => $lot ) ) ) . '">LOT ' . esc_html( $lot ) . ' · ' . (int) $q . '</a>'; }
		echo '</div><p class="iv-help">출고 때 LOT 를 비우면 맨 앞(먼저 들어온) LOT 로 기록합니다.</p>';
	}
	echo '</section>';
}

/** 출고 · 입고 대화상자에 붙는 LOT · 차트번호 칸 (추적 품목일 때만 보임 — data-if="track") */
function md_inv_lot_fields( $mode ) {
	echo '<div class="iv-grid2 iv-lotfields" data-if="track" hidden>';
	if ( 'out' === $mode ) {
		echo '<label class="iv-f"><span>환자 차트번호</span><input class="iv-input" name="chart" inputmode="numeric" maxlength="40" placeholder="예: 12345"></label>';
		echo '<label class="iv-f"><span>LOT <small>(비우면 먼저 들어온 LOT)</small></span><input class="iv-input" name="lot" maxlength="80" list="iv-lot-dl" data-lots></label>';
	} else {
		echo '<label class="iv-f"><span>LOT 번호</span><span class="iv-inline"><input class="iv-input" name="lot" maxlength="80" placeholder="상자 · 라벨의 LOT"><button type="button" class="iv-btn iv-btn--icon" data-scanto="lot" aria-label="LOT 바코드 스캔">' . md_inv_icon( 'scan', 18 ) . '</button></span></label>';
		echo '<p class="iv-help">LOT 가 여러 개 섞여 왔으면 LOT 마다 나눠서 입고해 주세요.</p>';
	}
	echo '</div>';
	static $dl = false;
	if ( ! $dl ) { $dl = true; echo '<datalist id="iv-lot-dl"></datalist>'; }
}

/** 추적 품목 → 남은 LOT 목록 (대화상자 JS 가 품목을 고르면 LOT · 차트 칸을 보이고 LOT 목록을 채운다) */
function md_inv_track_map_script() {
	static $done = false;
	if ( $done ) { return; }
	$done = true;
	global $wpdb;
	$map = array();
	foreach ( (array) $wpdb->get_col( 'SELECT id FROM ' . md_inv_t( 'item' ) . ' WHERE track_lot = 1' ) as $id ) {
		$l = array();
		foreach ( md_inv_lot_stock( (int) $id ) as $lot => $q ) { $l[] = array( $lot, $q ); }
		$map[ (int) $id ] = $l;
	}
	echo '<script type="application/json" id="iv-track-map">' . wp_json_encode( (object) $map, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
}
