<?php
/**
 * v6.5 · 금액을 빈틈없이 (원장 지시 2026-10-06)
 *
 *   입고 한 건 = 원장 한 줄
 *     qty       들어온 총 수량 (무상 포함)
 *     free_qty  그중 무상 수량
 *     price     개당 단가 (유상분)
 *     amount    실제로 낸 금액 — 거래명세서 합계 (배송비 등 포함)
 *     extra     배송비 등 = amount − (qty − free_qty) × price  (할인이면 −)
 *   반품 · 교환 줄의 amount = 그 입고에서 돌려받은(옮겨 간) 금액. 출고 · 실사 · 처음 수량 줄은 0.
 *
 *   선납 잔액 · 업체 정산 · 통계 · 엑셀은 모두 원장의 amount 를 더한다.
 *   「수량 × 단가」를 나중에 다시 계산하지 않으므로 나누어떨어지지 않는 합계 · 배송비 · 할인이 그대로 남는다.
 *
 *   주문 한 건: qty(유상 주문 수량) · free_qty(받기로 한 무상) · price · amount(주문 합계, 배송비 포함) · extra
 *   주문 중 금액 = 주문 합계 − 이 주문으로 이미 받은 금액 (0 아래로는 안 내려감)
 *   주문 입고 수량(recv_qty)은 유상 수량만 센다 — 무상으로 더 온 것 때문에 주문이 일찍 닫히지 않게.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 금액 읽기 — 원 단위 정수만. 소수점 · 글자는 고치지 않고 되묻는다
 * ============================================================ */

/**
 * @return int|null|WP_Error  비었으면 null
 */
function md_inv_money_in( $v, $label = '금액', $allow_neg = false ) {
	$s = trim( (string) $v );
	if ( '' === $s ) { return null; }
	$s = preg_replace( '/[\s,원₩]/u', '', $s );
	$s = str_replace( array( '−', '–' ), '-', $s );
	if ( preg_match( '/^-?\d+\.0+$/', $s ) ) { $s = substr( $s, 0, strpos( $s, '.' ) ); }
	if ( ! preg_match( '/^-?\d+$/', $s ) ) {
		return new WP_Error( 'money', '「' . $label . '」은 원 단위 숫자로만 적어 주세요 (소수점 · 글자 없이). 지금 적은 값: ' . trim( (string) $v ) );
	}
	$n = (int) $s;
	if ( $n < 0 && ! $allow_neg ) { return new WP_Error( 'money', '「' . $label . '」은 0 이상이어야 합니다.' ); }
	if ( abs( $n ) > 99999999999 ) { return new WP_Error( 'money', '「' . $label . '」이 너무 큽니다.' ); }
	return $n;
}

/**
 * 거래명세서 한 줄을 계산한다.
 * @param int        $qty      총 수량 (무상 포함)
 * @param int        $free_qty 그중 무상
 * @param int        $price    개당 단가
 * @param int|null   $total    실제로 낸 금액 (null 이면 유상 수량 × 단가)
 * @return array|WP_Error [ qty, free_qty, paid_qty, price, goods, total, extra ]
 */
function md_inv_receipt_calc( $qty, $free_qty, $price, $total = null ) {
	$qty  = (int) $qty;
	$free = max( 0, (int) $free_qty );
	if ( $qty < 1 ) { return new WP_Error( 'qty', '들어온 수량을 적어 주세요.' ); }
	if ( $free > $qty ) { return new WP_Error( 'free', '무상 수량(' . $free . ')이 들어온 총 수량(' . $qty . ')보다 많습니다.' ); }
	$price = max( 0, (int) $price );
	$paid  = $qty - $free;
	$goods = $paid * $price;
	$total = null === $total ? $goods : (int) $total;
	if ( $total < 0 ) { return new WP_Error( 'total', '낸 금액은 0 이상이어야 합니다.' ); }
	return array( 'qty' => $qty, 'free_qty' => $free, 'paid_qty' => $paid, 'price' => $price, 'goods' => $goods, 'total' => $total, 'extra' => $total - $goods );
}

/** 폼에서 받은 입고 칸들 → 계산 결과 (free=1 이면 전부 무상 — 예전 폼 호환) */
function md_inv_receipt_from_post( $qty, $d ) {
	$free = isset( $d['free_qty'] ) && '' !== trim( (string) $d['free_qty'] ) ? (int) $d['free_qty'] : ( isset( $d['bonus_qty'] ) ? (int) $d['bonus_qty'] : 0 );
	if ( ! empty( $d['free'] ) ) { $free = (int) $qty; }
	$price = md_inv_money_in( isset( $d['price'] ) ? $d['price'] : '', '개당 단가' );
	if ( is_wp_error( $price ) ) { return $price; }
	$total = md_inv_money_in( isset( $d['total'] ) ? $d['total'] : '', '실제로 낸 금액' );
	if ( is_wp_error( $total ) ) { return $total; }
	return array( 'free' => $free, 'price' => $price, 'total' => $total );
}

/* ============================================================
 * 입고 한 줄의 돈 — 낸 금액에서 환불 · 정정 · 반품으로 돌려받은 것을 뺀 나머지
 * ============================================================ */

/** @return array [ amount, adj, back, net, paid_units(정정 뒤 유상 수량), goods(정정 뒤 물품 금액), units_left(반품 가능 수량) ] */
function md_inv_in_money( $l ) {
	global $wpdb;
	$amount = (int) $l->amount;
	$adj = 0; $adj_goods = 0; $adj_free = 0;
	if ( function_exists( 'md_inv_adj_map' ) ) {
		$m = md_inv_adj_map( array( (int) $l->id ) );
		foreach ( isset( $m[ (int) $l->id ] ) ? $m[ (int) $l->id ] : array() as $a ) {
			$adj += (int) $a->amount;
			if ( in_array( $a->kind, array( 'price', 'bonus' ), true ) ) { $adj_goods += (int) $a->amount; }
			$adj_free += (int) $a->qty;
		}
	}
	$back = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(amount),0) FROM ' . md_inv_t( 'ledger' ) . " WHERE ref_id = %d AND type = 'return' AND voided = 0", (int) $l->id ) );
	$qty  = abs( (int) $l->qty );
	$paid = max( 0, $qty - (int) $l->free_qty - $adj_free );
	return array(
		'amount'     => $amount,
		'adj'        => $adj,
		'back'       => $back,
		'net'        => $amount - $adj - $back,
		'paid_units' => $paid,
		'goods'      => $amount - (int) $l->extra - $adj_goods,
		'units_left' => function_exists( 'md_inv_returnable' ) ? md_inv_returnable( (int) $l->id ) : $qty,
	);
}

/**
 * 반품(교환) n 개에 돌려받는 금액 — 남은 금액을 남은 수량으로 나눠서. 마지막에는 남은 금액 전부(1원도 남지 않게).
 */
function md_inv_return_value( $l, $n ) {
	$mm = md_inv_in_money( $l );
	$n  = (int) $n;
	if ( $n <= 0 || $mm['units_left'] <= 0 || $mm['net'] <= 0 ) { return 0; }
	if ( $n >= $mm['units_left'] ) { return $mm['net']; }
	return (int) round( $mm['net'] * $n / $mm['units_left'] );
}

/* ============================================================
 * 주문 — 이미 받은 금액 · 아직 남은 금액
 * ============================================================ */

/** [ord_id => 이 주문으로 받은(입고한) 금액] */
function md_inv_ord_received_amounts( $ids = null ) {
	global $wpdb;
	$t = md_inv_t();
	$w = "type = 'in' AND voided = 0 AND ord_id > 0 AND ref_id = 0";
	if ( is_array( $ids ) ) {
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		if ( ! $ids ) { return array(); }
		$w .= ' AND ord_id IN (' . implode( ',', $ids ) . ')';
	}
	$out = array();
	foreach ( (array) $wpdb->get_results( "SELECT ord_id, SUM(amount) AS s FROM {$t['ledger']} WHERE $w GROUP BY ord_id" ) as $r ) { $out[ (int) $r->ord_id ] = (int) $r->s; }
	return $out;
}

/** 주문 중 금액 (아직 안 들어온 만큼) */
function md_inv_ord_amount_left( $o, $received = null ) {
	if ( 'ordered' !== $o->status ) { return 0; }
	if ( null === $received ) { $m = md_inv_ord_received_amounts( array( (int) $o->id ) ); $received = isset( $m[ (int) $o->id ] ) ? $m[ (int) $o->id ] : 0; }
	return max( 0, (int) $o->amount - (int) $received );
}

/* ============================================================
 * 설치 · 되돌린 백업 보정 — amount 가 없던 기록은 예전 방식(수량 × 단가, 무상 0)으로 채운다
 * ============================================================ */

function md_inv_money_fix() {
	global $wpdb;
	$t = md_inv_t();
	if ( (int) get_option( 'md_inv_schema', 0 ) < 4 ) { return; }
	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$t['ledger']}" );
	if ( ! is_array( $cols ) || ! in_array( 'amount', $cols, true ) ) { return; }
	/* 무상 줄: 무상 수량 = 수량, 금액 0 */
	$wpdb->query( "UPDATE {$t['ledger']} SET free_qty = ABS(qty) WHERE type = 'in' AND free = 1 AND free_qty = 0" );
	/* 금액이 비어 있는 입고 · 반품: 수량 × 단가 (무상 제외) */
	$wpdb->query( "UPDATE {$t['ledger']} SET amount = ABS(qty) * price WHERE type IN ('in','return') AND free = 0 AND amount = 0 AND price > 0 AND money_set = 0" );
	$wpdb->query( "UPDATE {$t['ledger']} SET money_set = 1 WHERE money_set = 0" );
	/* 주문: 무상 · 배송비 칸이 생기기 전 주문은 배송비 = 합계 − 수량 × 단가 */
	$wpdb->query( "UPDATE {$t['ord']} SET extra = amount - qty * price WHERE extra = 0 AND amount <> qty * price" );
}

/* ============================================================
 * 화면 조각 — 거래명세서처럼 네 칸 + 자동 계산 줄
 * ============================================================ */

/**
 * @param array $o  mode(receive|in|order), price, total_label
 */
function md_inv_receipt_fields( $o = array() ) {
	$o     = wp_parse_args( $o, array( 'mode' => 'receive', 'values' => array() ) );
	$order = 'order' === $o['mode'];
	$val   = function ( $k ) use ( $o ) { return isset( $o['values'][ $k ] ) && '' !== (string) $o['values'][ $k ] ? ' value="' . esc_attr( $o['values'][ $k ] ) . '"' : ''; };
	echo '<div class="iv-rcpt" data-rcpt="' . esc_attr( $o['mode'] ) . '">';
	echo '<div class="iv-grid2">';
	echo '<label class="iv-f"><span>' . ( $order ? '주문 수량 (유상)' : '들어온 총 수량 <small>(무상 포함)</small>' ) . ' <em>*</em></span><input class="iv-input" type="number" inputmode="numeric" name="qty" min="1" required data-r="qty"' . $val( 'qty' ) . '></label>';
	echo '<label class="iv-f"><span>' . ( $order ? '받기로 한 무상 수량' : '그중 무상 수량' ) . ' <small>(없으면 0)</small></span><input class="iv-input" type="number" inputmode="numeric" name="free_qty" min="0" placeholder="0" data-r="free"' . $val( 'free_qty' ) . '></label>';
	echo '<label class="iv-f"><span>개당 단가 (원)</span><input class="iv-input" name="price" inputmode="numeric" autocomplete="off" data-r="price"' . $val( 'price' ) . '></label>';
	echo '<label class="iv-f"><span>' . ( $order ? '주문 합계 (원)' : '실제로 낸 금액 (원)' ) . ' <small>' . ( $order ? '배송비 · 할인 포함' : '거래명세서 합계 · 배송비 포함' ) . '</small></span><input class="iv-input" name="total" inputmode="numeric" autocomplete="off" data-r="total"' . $val( 'total' ) . '></label>';
	echo '</div>';
	echo '<p class="iv-rcpt__sum" data-r="sum" aria-live="polite"></p>';
	echo '</div>';
}
