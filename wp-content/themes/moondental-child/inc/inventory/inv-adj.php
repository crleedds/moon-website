<?php
/**
 * v6.3 · 입고 뒤에 생기는 여러 경우 (원장 지시 2026-10-05)
 *
 *   환불 · 정정 (물건은 그대로) — 입고 기록 하나에 붙인다 (wp_md_inv_adj)
 *     refund  일부 · 전액 환불          금액만큼 돌려받음
 *     price   단가 정정                 (원래 단가 − 맞는 단가) × 수량 — 더 냈으면 돌려받고, 덜 냈으면 더 냄(−)
 *     bonus   알고 보니 무상이었음       그 개수만큼 돌려받음
 *   v6.5 · 모든 금액은 그 입고에서 「실제로 낸 금액」(배송비 포함)에서 이미 돌려받은 것(환불 · 정정 · 반품)을 뺀 나머지 안에서만.
 *          단가 정정은 지금(정정 뒤) 물품 금액 기준이라 두 번 고쳐도 차액이 겹치지 않는다.
 *     돌려받는 곳: 선납 잔액(balance) — 선납 업체 / 돈(money) — 계좌 · 카드 · 현금
 *   교환 — 같은 물건 또는 다른 물건으로 바꿔 받음 (반품 한 줄 + 입고 한 줄, 돈은 그대로)
 *   덤 — 입고할 때 「그중 덤(무상) 수량」을 적으면 유상 · 무상 두 줄로 나눠 기록 (입고 창에서)
 *   보상 · 리베이트 — 돈을 내지 않고 선납 잔액만 늘어남 (입금 창 「종류」)
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function md_inv_adj_kinds() {
	return array( 'refund' => '환불', 'price' => '단가 정정', 'bonus' => '무상 정정' );
}

/** 입고 기록 → 붙은 환불 · 정정 [ledger_id => [rows]] */
function md_inv_adj_map( $ledger_ids ) {
	global $wpdb;
	$ids = array_values( array_filter( array_map( 'intval', (array) $ledger_ids ) ) );
	if ( ! $ids || (int) get_option( 'md_inv_schema', 0 ) < 4 ) { return array(); }
	$out = array();
	foreach ( (array) $wpdb->get_results( 'SELECT * FROM ' . md_inv_t( 'adj' ) . ' WHERE voided = 0 AND ledger_id IN (' . implode( ',', $ids ) . ') ORDER BY id' ) as $a ) { $out[ (int) $a->ledger_id ][] = $a; }
	return $out;
}

/** 이 입고에서 지금까지 돌려받은 금액 · 덤으로 바꾼 개수 */
function md_inv_adj_done( $ledger_id ) {
	$m = md_inv_adj_map( array( $ledger_id ) );
	$s = array( 'amount' => 0, 'bonus_qty' => 0 );
	foreach ( isset( $m[ $ledger_id ] ) ? $m[ $ledger_id ] : array() as $a ) { $s['amount'] += (int) $a->amount; $s['bonus_qty'] += (int) $a->qty; }
	return $s;
}

/**
 * 환불 · 정정 기록
 * @param array $d kind, amount(환불), price(정정 단가), qty(무상 개수), dest(balance|money), note, item_price(단가 정정 때 품목 단가도)
 */
function md_inv_adj_add( $ledger_id, $d ) {
	global $wpdb;
	$l = md_inv_ledger_row( $ledger_id );
	if ( ! $l || 'in' !== $l->type || (int) $l->voided ) { return new WP_Error( 'src', '입고 기록을 찾을 수 없습니다.' ); }
	$kind = isset( $d['kind'] ) ? (string) $d['kind'] : 'refund';
	if ( ! isset( md_inv_adj_kinds()[ $kind ] ) ) { return new WP_Error( 'kind', '종류를 골라 주세요.' ); }
	$mm   = md_inv_in_money( $l );
	$left = $mm['net'];  /* 아직 돌려받을 수 있는 금액 (낸 금액 − 환불 · 정정 − 반품) */
	if ( (int) $mm['amount'] <= 0 && 'price' !== $kind ) { return new WP_Error( 'free', '이 입고는 낸 금액이 0원이라 환불 · 정정할 금액이 없습니다.' ); }
	$qty = 0; $np = 0;
	switch ( $kind ) {
		case 'refund':
			if ( 'all' === ( $d['amount'] ?? '' ) ) { $amount = $left; }
			else {
				$amount = md_inv_money_in( $d['amount'] ?? '', '돌려받은 금액' );
				if ( is_wp_error( $amount ) ) { return $amount; }
				$amount = (int) $amount;
			}
			if ( $amount <= 0 ) { return new WP_Error( 'amount', '돌려받은 금액을 적어 주세요.' ); }
			if ( $amount > $left ) { return new WP_Error( 'amount', '이 입고에서 아직 돌려받지 않은 금액(' . md_inv_num( $left ) . '원)보다 많이 돌려받을 수 없습니다.' ); }
			break;
		case 'price':
			$np = md_inv_money_in( $d['price'] ?? '', '맞는 단가' );
			if ( is_wp_error( $np ) ) { return $np; }
			if ( null === $np ) { return new WP_Error( 'price', '맞는 단가를 적어 주세요.' ); }
			if ( $mm['paid_units'] < 1 ) { return new WP_Error( 'qty', '유상으로 들어온 수량이 없어 단가를 정정할 수 없습니다.' ); }
			$now_price = (int) round( $mm['goods'] / $mm['paid_units'] );
			$amount    = $mm['goods'] - $np * $mm['paid_units']; /* 지금 물품 금액 − 맞는 물품 금액 : + 돌려받음 · − 더 냄 */
			if ( 0 === $amount ) { return new WP_Error( 'same', '지금 단가와 같습니다.' ); }
			if ( $amount > $left ) { return new WP_Error( 'amount', '이미 돌려받은 금액이 있어 이만큼 정정할 수 없습니다 (남은 금액 ' . md_inv_num( $left ) . '원).' ); }
			$d['note'] = trim( '단가 ' . md_inv_num( $now_price ) . ' → ' . md_inv_num( $np ) . ' (' . $mm['paid_units'] . '개) · ' . ( $d['note'] ?? '' ), ' ·' );
			break;
		case 'bonus':
			$qty = (int) ( $d['qty'] ?? 0 );
			if ( $qty < 1 ) { return new WP_Error( 'qty', '무상이었던 개수를 적어 주세요.' ); }
			if ( $qty > $mm['paid_units'] ) { return new WP_Error( 'qty', '이 입고에서 유상으로 남은 것은 ' . $mm['paid_units'] . '개라 그보다 많이 무상으로 바꿀 수 없습니다.' ); }
			$amount = $qty >= $mm['paid_units'] ? $mm['goods'] : (int) round( $mm['goods'] * $qty / $mm['paid_units'] );
			if ( $amount > $left ) { return new WP_Error( 'amount', '남은 금액(' . md_inv_num( $left ) . '원)보다 큽니다.' ); }
			break;
	}
	/* 두 번 눌러 같은 기록이 두 번 들어가는 것을 막는다 (같은 입고 · 같은 종류 · 같은 금액을 20초 안에) */
	$dup = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . md_inv_t( 'adj' ) . ' WHERE ledger_id = %d AND kind = %s AND amount = %d AND voided = 0 AND created_at >= %s LIMIT 1', (int) $l->id, $kind, (int) $amount, date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 20 ) ) );
	if ( $dup ) { return new WP_Error( 'dup', '방금 같은 환불 · 정정을 기록했습니다. 두 번 누르지 않았는지 확인해 주세요.' ); }
	$dest = 'money' === ( $d['dest'] ?? '' ) ? 'money' : 'balance';
	if ( 'balance' === $dest && ! md_inv_is_prepaid_vendor( $l->vendor_id ) ) { $dest = 'money'; } /* 선납 업체가 아니면 돈으로 */
	$wpdb->insert( md_inv_t( 'adj' ), array(
		'ledger_id' => (int) $l->id, 'item_id' => (int) $l->item_id, 'vendor_id' => (int) $l->vendor_id, 'kind' => $kind,
		'amount' => (int) $amount, 'qty' => $qty, 'dest' => $dest, 'note' => md_inv_txt( $d['note'] ?? '', 300 ),
		'person' => md_inv_me(), 'user_id' => get_current_user_id(), 'created_at' => current_time( 'mysql' ),
	) );
	$id = (int) $wpdb->insert_id;
	if ( 'price' === $kind && ! empty( $d['item_price'] ) ) {
		$it = md_inv_item( $l->item_id );
		if ( $it && (int) $it->price !== $np ) {
			$wpdb->update( md_inv_t( 'item' ), array( 'price' => $np ), array( 'id' => (int) $it->id ) );
			md_inv_price_log( $it->id, (int) $it->price, $np, '정정', true );
		}
	}
	$it = md_inv_item( $l->item_id );
	md_inv_log( md_inv_adj_kinds()[ $kind ], ( $it ? $it->name : '' ) . ' · ' . ( $amount >= 0 ? '돌려받음 ' : '더 냄 ' ) . md_inv_won( abs( $amount ) ) . ' · ' . ( 'balance' === $dest ? '선납 잔액으로' : '돈으로' ) . ( $qty ? ' · 무상 ' . $qty . '개' : '' ) );
	return $id;
}

function md_inv_adj_void( $id, $why = '' ) {
	global $wpdb;
	$n = $wpdb->update( md_inv_t( 'adj' ), array( 'voided' => 1, 'void_note' => md_inv_txt( $why, 200 ) ), array( 'id' => (int) $id, 'voided' => 0 ) );
	if ( ! $n ) { return new WP_Error( 'gone', '이미 취소했거나 없는 기록입니다.' ); }
	md_inv_log( '환불 · 정정 취소', '#' . (int) $id . ( $why ? ' · ' . $why : '' ) );
	return true;
}

/** 선납 잔액에 들어간 환불 · 정정 합계 [vendor_id => 금액] */
function md_inv_adj_balance_sums( $since = '' ) {
	global $wpdb;
	if ( (int) get_option( 'md_inv_schema', 0 ) < 4 ) { return array(); }
	$w = "voided = 0 AND dest = 'balance'" . ( '' !== $since ? $wpdb->prepare( ' AND created_at >= %s', $since ) : '' );
	$out = array();
	foreach ( (array) $wpdb->get_results( 'SELECT vendor_id, SUM(amount) AS s FROM ' . md_inv_t( 'adj' ) . " WHERE $w GROUP BY vendor_id" ) as $r ) { $out[ (int) $r->vendor_id ] = (int) $r->s; }
	return $out;
}

function md_inv_adjs( $a = array() ) {
	global $wpdb;
	if ( (int) get_option( 'md_inv_schema', 0 ) < 4 ) { return array(); }
	$t = md_inv_t();
	$w = array( 'a.voided = 0' ); $v = array();
	if ( ! empty( $a['vendor_id'] ) ) { $w[] = 'a.vendor_id = %d'; $v[] = (int) $a['vendor_id']; }
	if ( ! empty( $a['from'] ) ) { $w[] = 'a.created_at >= %s'; $v[] = $a['from'] . ' 00:00:00'; }
	if ( ! empty( $a['to'] ) ) { $w[] = 'a.created_at <= %s'; $v[] = $a['to'] . ' 23:59:59'; }
	$sql = "SELECT a.*, COALESCE(i.name,'') AS item_name, COALESCE(i.unit,'') AS unit, l.qty AS in_qty, l.price AS in_price, l.created_at AS in_at FROM {$t['adj']} a LEFT JOIN {$t['item']} i ON i.id = a.item_id LEFT JOIN {$t['ledger']} l ON l.id = a.ledger_id WHERE " . implode( ' AND ', $w ) . ' ORDER BY a.created_at, a.id';
	return (array) $wpdb->get_results( $v ? $wpdb->prepare( $sql, $v ) : $sql );
}

/* ============================================================
 * 교환 — 반품 한 줄 + 입고 한 줄 (돈은 그대로)
 * ============================================================ */

function md_inv_exchange( $in_id, $qty, $to_item = 0, $lot = '', $note = '' ) {
	$l = md_inv_ledger_row( $in_id );
	if ( ! $l || 'in' !== $l->type || (int) $l->voided ) { return new WP_Error( 'src', '교환할 입고 기록을 찾을 수 없습니다.' ); }
	$qty = (int) $qty;
	$can = md_inv_returnable( $in_id );
	if ( $qty < 1 || $qty > $can ) { return new WP_Error( 'qty', '이 입고에서 교환할 수 있는 수량은 ' . $can . '개입니다.' ); }
	$to  = $to_item ? md_inv_item( (int) $to_item ) : md_inv_item( $l->item_id );
	if ( ! $to ) { return new WP_Error( 'item', '받은 품목을 다시 골라 주세요.' ); }
	$from = md_inv_item( $l->item_id );
	$why  = '교환' . ( '' !== trim( (string) $note ) ? ' · ' . md_inv_txt( $note, 200 ) : '' );
	/* v6.5 · 보낸 것의 값(실제로 낸 금액 기준)이 그대로 받은 것으로 옮겨 간다 — 업체도 원래 입고의 업체, 주문 수량과는 무관 */
	$val = md_inv_return_value( $l, $qty );
	md_inv_lock();
	md_inv_begin();
	$r1 = md_inv_ledger_add( 'return', $l->item_id, $qty, array( 'ref_id' => $l->id, 'price' => $l->price, 'vendor_id' => (int) $l->vendor_id, 'amount' => $val, 'free' => 0 === $val, 'note' => $why . ' (보냄)' ) );
	if ( is_wp_error( $r1 ) ) { md_inv_rollback(); md_inv_unlock(); return $r1; }
	$r2 = md_inv_ledger_add( 'in', $to->id, $qty, array( 'ref_id' => $l->id, 'price' => (int) round( $val / max( 1, $qty ) ), 'vendor_id' => (int) $l->vendor_id, 'amount' => $val, 'free_qty' => 0 === $val ? $qty : 0, 'lot' => $lot, 'note' => $why . ' (받음' . ( (int) $to->id !== (int) $l->item_id ? ' · ' . $from->name . ' 대신' : '' ) . ')' ) );
	if ( is_wp_error( $r2 ) ) { md_inv_rollback(); md_inv_unlock(); return $r2; }
	md_inv_commit();
	md_inv_unlock();
	md_inv_log( '교환', $from->name . ' ' . $qty . ' → ' . $to->name . ( $note ? ' · ' . $note : '' ) );
	return $r2;
}

/* ============================================================
 * 덤 — 입고할 때 유상 · 무상 두 줄로
 * ============================================================ */

/** 입고 수량 중 덤(무상) 수량을 떼어 무상 한 줄로 남긴다 — 덤 줄 id 또는 0 */
function md_inv_in_bonus_split( $item_id, $bonus_qty, $o ) {
	$bonus_qty = (int) $bonus_qty;
	if ( $bonus_qty < 1 ) { return 0; }
	$o['free'] = 1;
	$o['note'] = trim( '덤 · ' . ( isset( $o['note'] ) ? (string) $o['note'] : '' ), ' ·' );
	$r = md_inv_ledger_add( 'in', $item_id, $bonus_qty, $o );
	return is_wp_error( $r ) ? 0 : (int) $r;
}

/* ============================================================
 * 처리
 * ============================================================ */

function md_inv_act_adj_add() {
	$r = md_inv_adj_add( (int) md_inv_p( 'in_id' ), array(
		'kind' => md_inv_p( 'kind' ), 'amount' => md_inv_p( 'all' ) ? 'all' : md_inv_p( 'amount' ), 'price' => md_inv_p( 'price' ), 'qty' => md_inv_p( 'bqty' ),
		'dest' => md_inv_p( 'dest' ), 'note' => md_inv_p( 'note' ), 'item_price' => md_inv_p( 'item_price' ),
	) );
	md_inv_done( $r, '환불 · 정정을 기록했습니다. 물건(재고)은 그대로이고, 금액만 바뀌었습니다.' );
}

function md_inv_act_adj_void() {
	md_inv_done( md_inv_adj_void( (int) md_inv_p( 'id' ), (string) md_inv_p( 'why' ) ), '환불 · 정정 기록을 취소했습니다.' );
}

function md_inv_act_exchange() {
	/* 보이는 칸(품목 이름 #번호)만 믿는다 — 숨은 칸은 지난번 값이 남아 있을 수 있다. 비우면 같은 품목 */
	$to = preg_match( '/#(\d+)\s*$/', trim( (string) md_inv_p( 'to_item_pick' ) ), $m ) ? (int) $m[1] : 0;
	md_inv_done( md_inv_exchange( (int) md_inv_p( 'in_id' ), (int) md_inv_p( 'qty' ), $to, (string) md_inv_p( 'lot' ), (string) md_inv_p( 'note' ) ), '교환을 기록했습니다 — 보낸 것은 반품, 받은 것은 입고로 남았고 금액은 그대로입니다.' );
}

/* ============================================================
 * 화면 조각
 * ============================================================ */

/** 입출고 표의 입고 줄 아래 — 붙은 환불 · 정정 */
function md_inv_adj_lines_html( $list ) {
	if ( ! $list ) { return ''; }
	$k = md_inv_adj_kinds();
	$h = '';
	foreach ( $list as $a ) {
		$h .= '<br><small class="iv-adjline">' . esc_html( $k[ $a->kind ] . ' ' . ( (int) $a->amount >= 0 ? '돌려받음 ' : '더 냄 ' ) . md_inv_num( abs( (int) $a->amount ) ) . '원' . ( (int) $a->qty ? ' (무상 ' . (int) $a->qty . '개)' : '' ) . ' · ' . ( 'balance' === $a->dest ? '선납 잔액으로' : '돈으로' ) . ( '' !== (string) $a->note ? ' · ' . $a->note : '' ) . ' · ' . $a->person ) . '</small>'
			. ' <form method="post" class="iv-inline-form" data-confirm="이 환불 · 정정 기록을 취소할까요?">' . md_inv_hidden_html( 'adj_void' ) . '<input type="hidden" name="id" value="' . (int) $a->id . '"><button class="iv-link iv-link--xs">취소</button></form>';
	}
	return $h;
}

function md_inv_hidden_html( $action ) {
	ob_start(); md_inv_hidden( $action ); return ob_get_clean();
}

/** 대화상자 — 환불 · 정정 / 교환 */
function md_inv_adj_dialogs() {
	static $done = false;
	if ( $done ) { return; }
	$done = true;
	md_inv_dlg_open( 'dlg-adj', '환불 · 정정 (물건은 그대로)', 'adj_add' );
	echo '<input type="hidden" name="in_id" value="" data-fill>';
	echo '<fieldset class="iv-f iv-adjkind"><span>어떤 경우인가요</span>';
	echo '<label class="iv-check"><input type="radio" name="kind" value="refund" checked> 일부 · 전액 환불 받음 (물건은 그냥 씀)</label>';
	echo '<label class="iv-check"><input type="radio" name="kind" value="price"> 단가가 틀렸음 (더 냈거나 덜 냄)</label>';
	echo '<label class="iv-check"><input type="radio" name="kind" value="bonus"> 일부가 무상이었음</label></fieldset>';
	echo '<div data-adj="refund"><div class="iv-grid2"><label class="iv-f"><span>돌려받은 금액 (원)</span><input class="iv-input" name="amount" inputmode="numeric"></label>';
	echo '<label class="iv-check iv-adj-all"><input type="checkbox" name="all" value="1"> 전액 (남은 금액 모두)</label></div></div>';
	echo '<div data-adj="price" hidden><div class="iv-grid2"><label class="iv-f"><span>맞는 단가 (원)</span><input class="iv-input" name="price" inputmode="numeric"></label>';
	echo '<label class="iv-check"><input type="checkbox" name="item_price" value="1" checked> 품목 단가도 이 값으로</label></div></div>';
	echo '<div data-adj="bonus" hidden><label class="iv-f"><span>무상이었던 개수</span><input class="iv-input" name="bqty" type="number" inputmode="numeric" min="1"></label></div>';
	echo '<fieldset class="iv-f iv-adjdest" data-ifset="pp"><span>돌려받은 곳</span>';
	echo '<label class="iv-check"><input type="radio" name="dest" value="balance" checked> 선납 잔액으로 (업체가 잔액에 다시 넣어 줌)</label>';
	echo '<label class="iv-check"><input type="radio" name="dest" value="money"> 돈으로 (계좌 · 카드 취소 · 현금)</label></fieldset>';
	echo '<p class="iv-help" data-t="hint"></p>';
	echo '<label class="iv-f"><span>메모</span><input class="iv-input" name="note" maxlength="300" placeholder="예: 포장 파손 보상 · 단가 인하 소급 · 업체 착오"></label>';
	md_inv_dlg_close( '기록' );

	md_inv_dlg_open( 'dlg-exch', '교환', 'exchange' );
	echo '<input type="hidden" name="in_id" value="" data-fill>';
	echo '<p class="iv-help">불량 · 사이즈 착오 등으로 바꿔 받은 경우. 보낸 것은 「반품」, 받은 것은 「입고」로 남고 보낸 것의 값이 그대로 옮겨 갑니다(차액은 「환불 · 정정」으로).</p>';
	echo '<div class="iv-grid2"><label class="iv-f"><span>바꾼 수량</span><input class="iv-input" type="number" inputmode="numeric" name="qty" min="1" required></label>';
	echo '<label class="iv-f"><span>새 LOT (추적 품목)</span><input class="iv-input" name="lot" maxlength="80"></label></div>';
	echo '<label class="iv-f"><span>받은 품목 <small>(같은 품목이면 비워 두세요)</small></span>'; md_inv_item_picker( 'to_item', false ); echo '</label>';
	echo '<label class="iv-f"><span>메모</span><input class="iv-input" name="note" maxlength="200" placeholder="예: 불량 · 사이즈 착오"></label>';
	md_inv_dlg_close( '교환 기록' );
}

/** 입고 창 — v6.5 · 「그중 무상 수량」 (예전 이름: 덤). 새 입고 창은 md_inv_receipt_fields() 를 쓴다 */
function md_inv_bonus_field() {
	echo '<label class="iv-f iv-bonusf"><span>그중 무상 수량 <small>(예: 10개 사고 1개 더 받음 → 11개 중 1)</small></span><input class="iv-input" name="free_qty" type="number" inputmode="numeric" min="0" placeholder="0"></label>';
}
