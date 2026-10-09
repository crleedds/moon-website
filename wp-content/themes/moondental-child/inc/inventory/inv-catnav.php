<?php
/**
 * v6.6 · 재고 · 실사 화면 분류 단계 (원장 지시 2026-10-06)
 *
 *   결제 방식 › 품목군 › 세부 분류 — 단계 버튼 줄(품목 수 · 부족 수), 표 안 분류별 묶음 머리줄(소계 · 접기 — inventory.js),
 *   휴대폰 실사의 분류 타일, 화면마다 마지막에 보던 분류 기억(계정마다).
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * 지금 경로 [c1, c2, c3] — 주소에 있으면 그것(기억), 없으면 지난번 것.
 * c3 는 분류 id 또는 -1(「기타」 — 세부 분류 없음).
 */
function md_inv_cat_path( $scope ) {
	$key = 'md_inv_path_' . sanitize_key( $scope );
	$has = isset( $_GET['ic1'] ) || isset( $_GET['ic2'] ) || isset( $_GET['ic3'] );
	if ( $has ) {
		$p = array( (int) md_inv_get( 'ic1', 0 ), (int) md_inv_get( 'ic2', 0 ), (int) md_inv_get( 'ic3', 0 ) );
	} else {
		$p = array_map( 'intval', (array) get_user_meta( get_current_user_id(), $key, true ) ) + array( 0, 0, 0 );
	}
	/* 위아래가 맞는 분류만 */
	$c1 = $p[0] && md_inv_cat( $p[0] ) && 1 === (int) md_inv_cat( $p[0] )->level ? $p[0] : 0;
	$c2 = 0;
	if ( $p[1] ) { $x = md_inv_cat( $p[1] ); if ( $x && 2 === (int) $x->level ) { $c2 = $p[1]; $c1 = (int) $x->parent_id; } }
	$c3 = 0;
	if ( $c2 && -1 === $p[2] ) { $c3 = -1; }
	elseif ( $c2 && $p[2] ) { $x = md_inv_cat( $p[2] ); if ( $x && 3 === (int) $x->level && (int) $x->parent_id === $c2 ) { $c3 = $p[2]; } }
	if ( $has && is_user_logged_in() ) { update_user_meta( get_current_user_id(), $key, array( $c1, $c2, $c3 ) ); }
	return array( $c1, $c2, $c3 );
}

/** 품목이 경로 안인가 */
function md_inv_in_path( $it, $c1, $c2, $c3 ) {
	if ( $c1 && (int) $it->cat1 !== $c1 ) { return false; }
	if ( $c2 && (int) $it->cat2 !== $c2 ) { return false; }
	if ( -1 === $c3 ) { return ! (int) $it->cat3; }
	if ( $c3 && (int) $it->cat3 !== $c3 ) { return false; }
	return true;
}

/** 단계 버튼 줄. $items = 분류 말고 다른 거르기(찾기 · 업체 · 보기)를 거친 품목 */
function md_inv_cat_bar( $items, $c1, $c2, $c3, $args ) {
	$S   = md_inv_settings();
	$cnt = function ( $f ) use ( $items ) {
		$n = 0; $low = 0;
		foreach ( $items as $it ) { if ( $f( $it ) ) { $n++; if ( 'ok' !== md_inv_stock_state( $it ) ) { $low++; } } }
		return array( $n, $low );
	};
	$btn = function ( $label, $on, $url, $c, $empty_ok = true ) {
		list( $n, $low ) = $c;
		return '<a class="iv-catbtn' . ( $on ? ' is-on' : '' ) . ( $n ? '' : ' is-empty' ) . '" href="' . esc_url( $url ) . '"' . ( $on ? ' aria-current="true"' : '' ) . '>' . esc_html( $label ) . ' <small>' . (int) $n . '</small>' . ( $low ? ' <em class="iv-catbtn__low" title="부족 · 품절">부족 ' . (int) $low . '</em>' : '' ) . '</a>';
	};
	$u = function ( $p ) use ( $args ) { return md_inv_url( array_merge( $args, array( 'ic1' => $p[0], 'ic2' => $p[1], 'ic3' => $p[2], 'pg' => '' ) ) ); };
	$h = '<nav class="iv-catbar" aria-label="분류">';
	/* 1 · 결제 방식 */
	$h .= '<div class="iv-catbar__row"><span class="iv-catbar__lab">' . esc_html( $S['label_cat1'] ) . '</span>';
	$h .= $btn( '전체', ! $c1, $u( array( 0, 0, 0 ) ), $cnt( function () { return true; } ) );
	foreach ( md_inv_cats_of( 1 ) as $c ) {
		$id = (int) $c->id;
		$h .= $btn( $c->name, $c1 === $id, $u( array( $id, 0, 0 ) ), $cnt( function ( $it ) use ( $id ) { return (int) $it->cat1 === $id; } ) );
	}
	$h .= '</div>';
	/* 2 · 품목군 */
	if ( $c1 ) {
		$h .= '<div class="iv-catbar__row"><span class="iv-catbar__lab">' . esc_html( $S['label_cat2'] ) . '</span>';
		$h .= $btn( '전체', ! $c2, $u( array( $c1, 0, 0 ) ), $cnt( function ( $it ) use ( $c1 ) { return (int) $it->cat1 === $c1; } ) );
		foreach ( md_inv_cats_of( 2, $c1 ) as $c ) {
			$id = (int) $c->id;
			$h .= $btn( $c->name, $c2 === $id, $u( array( $c1, $id, 0 ) ), $cnt( function ( $it ) use ( $id ) { return (int) $it->cat2 === $id; } ) );
		}
		$h .= '</div>';
	}
	/* 3 · 세부 분류 (있을 때만) */
	$subs = $c2 ? md_inv_cats_of( 3, $c2 ) : array();
	if ( $subs ) {
		$h .= '<div class="iv-catbar__row"><span class="iv-catbar__lab">' . esc_html( $S['label_cat3'] ) . '</span>';
		$h .= $btn( '전체', ! $c3, $u( array( $c1, $c2, 0 ) ), $cnt( function ( $it ) use ( $c2 ) { return (int) $it->cat2 === $c2; } ) );
		foreach ( $subs as $c ) {
			$id = (int) $c->id;
			$h .= $btn( $c->name, $c3 === $id, $u( array( $c1, $c2, $id ) ), $cnt( function ( $it ) use ( $id ) { return (int) $it->cat3 === $id; } ) );
		}
		$none = $cnt( function ( $it ) use ( $c2 ) { return (int) $it->cat2 === $c2 && ! (int) $it->cat3; } );
		if ( $none[0] ) { $h .= $btn( '기타', -1 === $c3, $u( array( $c1, $c2, -1 ) ), $none ); }
		$h .= '</div>';
	}
	return $h . '</nav>';
}

/** 분류 순서(설정 › 분류의 순서) → 이름 순으로 정렬 */
function md_inv_sort_by_cat( $rows ) {
	$ord = array();
	foreach ( md_inv_cats() as $i => $c ) { $ord[ (int) $c->id ] = $i; }
	$k = function ( $id ) use ( $ord ) { return $id && isset( $ord[ $id ] ) ? $ord[ $id ] : 99999; };
	usort( $rows, function ( $a, $b ) use ( $k ) {
		foreach ( array( 'cat1', 'cat2', 'cat3' ) as $f ) { $d = $k( (int) $a->$f ) - $k( (int) $b->$f ); if ( $d ) { return $d; } }
		return strnatcasecmp( $a->name, $b->name );
	} );
	return $rows;
}

/**
 * v6.6.1 · 분류 단계는 다시 불러오지 않고 브라우저에서 바로 바꾼다 (원장 「좀 느린데?」 — 운영 서버는 화면 한 번에 0.8초).
 * 표에는 거르기(찾기 · 업체 · 보기)를 거친 품목을 모두 싣고, 줄마다 분류 · 재고 금액 · 부족 표시를 붙인다.
 * 지금 경로 밖의 줄은 hidden — 묶음 머리줄 · 단계 버튼 · 품목 수는 inventory.js 가 그린다.
 */
function md_inv_row_attr( $it, $c1, $c2, $c3 ) {
	return ' data-c="' . (int) $it->cat1 . ',' . (int) $it->cat2 . ',' . (int) $it->cat3 . '" data-v="' . ( max( 0, (int) $it->stock ) * (int) $it->price ) . '" data-low="' . ( 'ok' !== md_inv_stock_state( $it ) ? 1 : 0 ) . '"'
		. ( md_inv_in_path( $it, $c1, $c2, $c3 ) ? '' : ' hidden' );
}

/** 브라우저가 쓸 분류 목록 · 지금 경로 */
function md_inv_catnav_data( $scope, $c1, $c2, $c3, $extra = array() ) {
	$S    = md_inv_settings();
	$cats = array();
	foreach ( md_inv_cats( true ) as $c ) { $cats[] = array( (int) $c->id, (int) $c->level, (int) $c->parent_id, (string) $c->name ); }
	$d = array_merge( array(
		'scope' => $scope,
		'path'  => array( (int) $c1, (int) $c2, (int) $c3 ),
		'cats'  => $cats,
		'lab'   => array( $S['label_cat1'], $S['label_cat2'], $S['label_cat3'] ),
		'admin' => md_inv_is_admin() ? 1 : 0,
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'md_inv_path' ),
	), $extra );
	return '<script type="application/json" id="iv-catnav-data">' . wp_json_encode( $d, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
}

/** 경로 기억 (브라우저가 단계 버튼을 누를 때 뒤에서 보냄) */
function md_inv_ajax_path() {
	if ( ! is_user_logged_in() || ! check_ajax_referer( 'md_inv_path', 'nonce', false ) ) { wp_die( '0', '', array( 'response' => 403 ) ); }
	$scope = sanitize_key( wp_unslash( isset( $_POST['scope'] ) ? $_POST['scope'] : '' ) );
	if ( ! in_array( $scope, array( 'stock', 'count', 'qcount' ), true ) ) { wp_die( '0', '', array( 'response' => 400 ) ); }
	foreach ( array( 'ic1', 'ic2', 'ic3' ) as $k ) { $_GET[ $k ] = isset( $_POST[ $k ] ) ? (int) $_POST[ $k ] : 0; }
	md_inv_cat_path( $scope );
	wp_die( '1' );
}
add_action( 'wp_ajax_md_inv_path', 'md_inv_ajax_path' );

/** 휴대폰 실사 — 분류 타일 → 품목 목록 (오늘 센 것 ✓). 타일 · 경로 · 목록 거르기는 inventory.js (다시 불러오지 않음) */
function md_inv_qcount_browse() {
	list( $c1, $c2, $c3 ) = md_inv_cat_path( 'qcount' );
	$items = md_inv_sort_by_cat( md_inv_items() );
	$today = array();
	foreach ( md_inv_ledger( array( 'type' => 'adjust', 'from' => current_time( 'Y-m-d' ), 'to' => current_time( 'Y-m-d' ), 'limit' => 0, 'with_void' => 0 ) ) as $l ) { $today[ (int) $l->item_id ] = (int) $l->counted; }
	$here = md_inv_url( array( 'iv' => 'qcount', 'ic1' => $c1, 'ic2' => $c2, 'ic3' => $c3 ) );
	echo md_inv_catnav_data( 'qcount', $c1, $c2, $c3, array( 'iall' => md_inv_get( 'iall' ) ? 1 : 0 ) ); // phpcs:ignore
	echo '<section class="iv-panel iv-qcbrowse" id="iv-qcbrowse"><h3 class="iv-h3">분류별로 세기</h3><p class="iv-list__count iv-crumbs"><b>분류별로 세기</b></p><div class="iv-tiles"></div>'
		. '<p class="iv-help iv-qcbrowse__help" hidden></p><p class="iv-help iv-qcbrowse__empty" hidden>이 분류에는 아직 품목이 없습니다.</p><div class="iv-qclist">'; // phpcs:ignore
	foreach ( $items as $it ) {
		$d = isset( $today[ (int) $it->id ] );
		echo '<a class="iv-qcitem' . ( $d ? ' is-done' : '' ) . '" data-c="' . (int) $it->cat1 . ',' . (int) $it->cat2 . ',' . (int) $it->cat3 . '" data-id="' . (int) $it->id . '"' . ( $d ? ' data-done="1"' : '' ) . ' hidden href="' . esc_url( add_query_arg( 'id', (int) $it->id, $here ) . '#iv-qc-card' ) . '"><span class="iv-qcitem__name">' . ( $d ? '✓ ' : '' ) . esc_html( $it->name ) . '</span><span class="iv-qcitem__book">장부 ' . (int) $it->stock . ( $d ? ' · 센 ' . (int) $today[ (int) $it->id ] : '' ) . '</span>' . ( '' !== (string) $it->location ? '<small>📍' . esc_html( $it->location ) . '</small>' : '' ) . '</a>'; // phpcs:ignore
	}
	echo '</div></section>';
}
