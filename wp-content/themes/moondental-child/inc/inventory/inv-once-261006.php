<?php
/**
 * 2026-10-06 · 1회 작업 — 시험 기록 초기화 + 「261006 문치과병원 재고관리」 시트(마지막 장) 반영 (원장 지시)
 *
 *   원장 결정: 지금까지는 모두 시험 — 기록 · 입금은 전부 지우고, 선납(임플란트) 품목 목록만 그대로 둔다.
 *     1) 지금 상태를 수동 백업(설정 › 백업에서 되돌릴 수 있음)
 *     2) 모든 입출고 · 요청 · 주문 · 선납 입금 · 정정 · 단가 기록 · 업체 대조 · 활동 기록 지우기
 *     3) 시트 품목은 이름으로 찾아 업체 · 규격 · 단가 · 기준 재고를 맞추고(바코드 · 위치 · 세부 분류는 그대로), 없으면 새로
 *     4) 시트에 없는 일반 품목은 숨김
 *     5) 시트의 현 재고량을 재료실 재고 수량으로 (원장 기록 「재고 수량」 한 줄)
 *   데이터는 FTP 로 올린 uploads/md-inv-seed-<무작위>/reset261006.json (.htaccess 403). 한 번만 돈다(옵션 md_inv_once_261006).
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function md_inv_once_261006_norm( $s ) { return mb_strtolower( preg_replace( '/\s+/u', '', (string) $s ) ); }

function md_inv_once_261006_run( $file ) {
	global $wpdb;
	$d = json_decode( (string) file_get_contents( $file ), true );
	if ( ! is_array( $d ) || empty( $d['items'] ) ) { return array( 'error' => 'data' ); }
	$t   = array(); foreach ( array( 'item', 'vendor', 'ledger', 'req', 'ord', 'adj', 'price', 'recon', 'cat', 'deposit', 'log' ) as $k ) { $t[ $k ] = md_inv_t( $k ); }
	$now = current_time( 'mysql' );
	$rep = array( 'started' => $now );

	/* 분류 */
	$c1 = 0; $c2 = array();
	foreach ( md_inv_cats() as $c ) { if ( 1 === (int) $c->level && '건별결제품목' === $c->name ) { $c1 = (int) $c->id; } }
	foreach ( md_inv_cats() as $c ) { if ( 2 === (int) $c->level && (int) $c->parent_id === $c1 ) { $c2[ $c->name ] = (int) $c->id; } }
	if ( ! $c1 || empty( $c2['치과재료'] ) ) { return array( 'error' => 'cat' ); }

	/* 업체 */
	$vid = array(); $pp = array();
	foreach ( $wpdb->get_results( "SELECT id, name, prepaid, phone, goods, note FROM {$t['vendor']}" ) as $v ) {
		$vid[ md_inv_once_261006_norm( $v->name ) ] = (int) $v->id;
		if ( (int) $v->prepaid ) { $pp[] = (int) $v->id; }
	}
	foreach ( $d['items'] as $r ) {
		$k = md_inv_once_261006_norm( $r['vendor'] );
		if ( ! isset( $vid[ $k ] ) ) {
			$wpdb->insert( $t['vendor'], array( 'name' => $r['vendor'], 'active' => 1 ) );
			$vid[ $k ] = (int) $wpdb->insert_id;
			$rep['vendor_new'][] = $r['vendor'];
		}
	}

	/* 1) 백업 */
	$rep['backup'] = md_inv_backup_make( 'manual', '261006 재고관리 시트 반영 직전 (시험 기록 초기화 전)' );
	if ( is_wp_error( $rep['backup'] ) ) { return array( 'error' => 'backup' ); }

	/* 2) 기록 · 입금 모두 지우기 (품목 · 업체 · 팀 · 분류 · 즐겨찾기 · 백업은 그대로) */
	$ppin  = $pp ? implode( ',', array_map( 'intval', $pp ) ) : '0';
	$gen   = array_map( 'intval', $wpdb->get_col( "SELECT id FROM {$t['item']} WHERE vendor_id NOT IN ($ppin)" ) );
	$rep['del'] = array();
	foreach ( array( 'ledger', 'req', 'ord', 'deposit', 'adj', 'price', 'recon', 'log' ) as $k ) { $rep['del'][ $k ] = $wpdb->query( "DELETE FROM {$t[ $k ]}" ); }

	/* 3) 품목 맞추기 */
	$byname = array();
	foreach ( $wpdb->get_results( "SELECT id, name, vendor_id, unit, price, min_stock, cat2 FROM {$t['item']} WHERE vendor_id NOT IN ($ppin) ORDER BY active DESC, id ASC" ) as $it ) {
		$byname[ md_inv_once_261006_norm( $it->name ) ][] = $it;
	}
	$used = array(); $rep['matched'] = 0; $rep['new'] = 0; $open = 0; $open_sum = 0;
	foreach ( $d['items'] as $r ) {
		$v   = $vid[ md_inv_once_261006_norm( $r['vendor'] ) ];
		$cat = isset( $c2[ $r['kind'] ] ) ? $c2[ $r['kind'] ] : $c2['치과재료'];
		$hit = null;
		$k   = md_inv_once_261006_norm( '' !== (string) $r['match'] ? $r['match'] : $r['name'] );
		foreach ( array( $k, md_inv_once_261006_norm( $r['name'] ) ) as $kk ) {
			if ( $hit || empty( $byname[ $kk ] ) ) { continue; }
			foreach ( $byname[ $kk ] as $cand ) { if ( ! isset( $used[ (int) $cand->id ] ) && (int) $cand->vendor_id === $v ) { $hit = $cand; break; } }
			if ( ! $hit ) { foreach ( $byname[ $kk ] as $cand ) { if ( ! isset( $used[ (int) $cand->id ] ) ) { $hit = $cand; break; } } }
		}
		$f = array( 'name' => mb_substr( $r['name'], 0, 255 ), 'vendor_id' => $v, 'cat1' => $c1, 'cat2' => $cat, 'active' => 1, 'updated_at' => $now );
		if ( '' !== (string) $r['unit'] ) { $f['unit'] = mb_substr( $r['unit'], 0, 30 ); }
		if ( null !== $r['price'] ) { $f['price'] = (int) $r['price']; }
		if ( null !== $r['min'] ) { $f['min_stock'] = (int) $r['min']; }
		if ( $hit ) {
			if ( null === $r['price'] || ( 0 === (int) $r['price'] && (int) $hit->price > 0 ) ) { unset( $f['price'] ); } /* 시트에 단가가 없으면 지금 단가 그대로 */
			if ( (int) $hit->cat2 !== $cat && isset( $hit->cat2 ) ) { $f['cat3'] = 0; }
			$wpdb->update( $t['item'], $f, array( 'id' => (int) $hit->id ) );
			$iid = (int) $hit->id; $used[ $iid ] = 1; $rep['matched']++;
		} else {
			$f += array( 'unit' => '', 'price' => 0, 'min_stock' => 0, 'created_at' => $now, 'created_by' => get_current_user_id() );
			$wpdb->insert( $t['item'], $f );
			$iid = (int) $wpdb->insert_id; $used[ $iid ] = 1; $rep['new']++;
		}
		/* 5) 시트의 현 재고량 → 재고 수량 */
		$q = (int) $r['stock'];
		if ( 0 !== $q ) {
			$it = md_inv_item( $iid );
			$wpdb->insert( $t['ledger'], array(
				'item_id' => $iid, 'type' => 'open', 'qty' => $q, 'price' => $it ? (int) $it->price : 0, 'vendor_id' => $v,
				'person' => '재고관리 시트', 'note' => '재고 수량 (261006 문치과병원 재고관리 시트)', 'user_id' => get_current_user_id(), 'created_at' => $now,
			) );
			$open++; $open_sum += $q;
		}
	}
	/* 4) 시트에 없는 일반 품목은 숨김 */
	$hide = array_diff( $gen, array_keys( $used ) );
	$rep['hidden'] = $hide ? $wpdb->query( "UPDATE {$t['item']} SET active = 0, updated_at = '" . esc_sql( $now ) . "' WHERE id IN (" . implode( ',', array_map( 'intval', $hide ) ) . ') AND active = 1' ) : 0;
	$rep['open_lines'] = $open; $rep['open_sum'] = $open_sum;

	/* 업체 연락처 · 품목 · 처리 순서 (비어 있을 때만) */
	$rep['vendor_info'] = 0;
	foreach ( (array) $d['vendors'] as $vv ) {
		$k = md_inv_once_261006_norm( $vv['name'] );
		if ( ! isset( $vid[ $k ] ) ) { continue; }
		$cur = $wpdb->get_row( $wpdb->prepare( "SELECT phone, goods, note FROM {$t['vendor']} WHERE id = %d", $vid[ $k ] ) );
		$u = array();
		if ( '' === trim( (string) $cur->phone ) ) { $u['phone'] = $vv['phone']; }
		if ( '' === trim( (string) $cur->goods ) ) { $u['goods'] = $vv['goods']; }
		if ( '' === trim( (string) $cur->note ) && ! empty( $vv['steps'] ) ) { $u['note'] = '처리 순서: ' . implode( ' → ', $vv['steps'] ); }
		if ( $u ) { $wpdb->update( $t['vendor'], $u, array( 'id' => $vid[ $k ] ) ); $rep['vendor_info']++; }
	}

	/* 확인용 합계 */
	$rep['active_general'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['item']} WHERE active = 1 AND vendor_id NOT IN ($ppin)" );
	$rep['stock_sum_general'] = (int) $wpdb->get_var( "SELECT COALESCE(SUM(l.qty),0) FROM {$t['ledger']} l JOIN {$t['item']} i ON i.id = l.item_id WHERE l.voided = 0 AND i.vendor_id NOT IN ($ppin)" );
	$rep['prepaid_items'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['item']} WHERE vendor_id IN ($ppin)" );
	$rep['finished'] = current_time( 'mysql' );
	if ( function_exists( 'md_inv_log' ) ) { md_inv_log( '초기화', '시험 기록 초기화 · 261006 재고관리 시트 반영 — 품목 ' . ( $rep['matched'] + $rep['new'] ) . '개(새 ' . $rep['new'] . ') · 숨김 ' . $rep['hidden'] . ' · 재고 수량 ' . $open . '개 품목 · 기록 · 입금 초기화 · 백업 #' . $rep['backup'] ); }
	return $rep;
}

function md_inv_once_261006() {
	if ( get_option( 'md_inv_once_261006' ) ) { return; }
	if ( ! function_exists( 'md_inv_seed_dir' ) ) { return; }
	$dir = md_inv_seed_dir();
	if ( '' === $dir || ! is_readable( $dir . '/reset261006.json' ) ) { return; }
	if ( (int) get_option( 'md_inv_schema', 0 ) < MD_INV_SCHEMA ) { return; }
	if ( ! add_option( 'md_inv_once_261006', 'running ' . current_time( 'mysql' ), '', 'no' ) ) { return; } /* 동시에 두 번 돌지 않게 */
	@set_time_limit( 300 );
	$rep = md_inv_once_261006_run( $dir . '/reset261006.json' );
	update_option( 'md_inv_once_261006', wp_json_encode( $rep, JSON_UNESCAPED_UNICODE ), false );
	@file_put_contents( $dir . '/reset261006.result.json', wp_json_encode( $rep, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
}
add_action( 'wp_loaded', 'md_inv_once_261006', 20 );
