<?php
/**
 * v6.6 · 재고 · 실사 화면 분류 단계 (원장 지시 2026-10-06)
 *
 *   결제 방식 › 품목군 › 세부 분류 — 단계 버튼 줄(품목 수 · 부족 수), 표 안 분류별 묶음 머리줄(소계 · 접기),
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

/** 표 묶음 — 지금 경로 바로 아래 단계로 묶을 이름 */
function md_inv_group_key( $it, $c1, $c2, $c3 ) {
	if ( $c3 ) { return ''; }
	if ( $c2 ) { return (int) $it->cat3 ? md_inv_cat_name( $it->cat3 ) : ''; }
	if ( $c1 ) { return (int) $it->cat2 ? md_inv_cat_name( $it->cat2 ) : '분류 없음'; }
	$a = (int) $it->cat1 ? md_inv_cat_name( $it->cat1 ) : '분류 없음';
	return $a . ( (int) $it->cat2 ? ' › ' . md_inv_cat_name( $it->cat2 ) : '' );
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

/** 묶음 소계 [이름 => [n, 재고 금액, 부족]] */
function md_inv_group_sums( $rows, $c1, $c2, $c3 ) {
	$out = array();
	foreach ( $rows as $it ) {
		$g = md_inv_group_key( $it, $c1, $c2, $c3 );
		if ( ! isset( $out[ $g ] ) ) { $out[ $g ] = array( 0, 0, 0 ); }
		$out[ $g ][0]++;
		$out[ $g ][1] += max( 0, (int) $it->stock ) * (int) $it->price;
		if ( 'ok' !== md_inv_stock_state( $it ) ) { $out[ $g ][2]++; }
	}
	return $out;
}

/** 묶음 머리줄 (표 한 줄) */
function md_inv_group_row( $g, $sum, $cols, $admin, $cont = false ) {
	$label = '' === $g ? '기타 (세부 분류 없음)' : $g;
	return '<tr class="iv-grp" data-grp="' . esc_attr( md5( $g ) ) . '"><th colspan="' . (int) $cols . '"><button type="button" class="iv-grp__btn" data-grp-toggle aria-expanded="true"><span class="iv-grp__arrow" aria-hidden="true">▾</span> <b>' . esc_html( $label ) . '</b>' . ( $cont ? ' <small>(앞 쪽에서 이어서)</small>' : '' ) . '</button>'
		. ' <span class="iv-grp__sum">' . (int) $sum[0] . '개' . ( $admin ? ' · 재고 금액 ' . esc_html( md_inv_won( $sum[1] ) ) : '' ) . ( $sum[2] ? ' · <em>부족 ' . (int) $sum[2] . '</em>' : '' ) . '</span></th></tr>';
}

/** 휴대폰 실사 — 분류 타일 → 품목 목록 (오늘 센 것 ✓) */
function md_inv_qcount_browse() {
	list( $c1, $c2, $c3 ) = md_inv_cat_path( 'qcount' );
	$S     = md_inv_settings();
	$items = md_inv_items();
	$here  = function ( $p ) { return md_inv_url( array( 'iv' => 'qcount', 'ic1' => $p[0], 'ic2' => $p[1], 'ic3' => $p[2] ) ); };
	$today = array();
	foreach ( md_inv_ledger( array( 'type' => 'adjust', 'from' => current_time( 'Y-m-d' ), 'to' => current_time( 'Y-m-d' ), 'limit' => 0, 'with_void' => 0 ) ) as $l ) { $today[ (int) $l->item_id ] = (int) $l->counted; }
	$in   = function ( $p ) use ( $items ) { $r = array(); foreach ( $items as $it ) { if ( md_inv_in_path( $it, $p[0], $p[1], $p[2] ) ) { $r[] = $it; } } return $r; };
	/* 경로 */
	$crumb = '<p class="iv-list__count iv-crumbs">' . ( $c1 ? '<a class="iv-link" href="' . esc_url( $here( array( 0, 0, 0 ) ) ) . '">분류별로</a>' : '<b>분류별로 세기</b>' );
	if ( $c1 ) { $crumb .= ' › ' . ( $c2 ? '<a class="iv-link" href="' . esc_url( $here( array( $c1, 0, 0 ) ) ) . '">' . esc_html( md_inv_cat_name( $c1 ) ) . '</a>' : '<b>' . esc_html( md_inv_cat_name( $c1 ) ) . '</b>' ); }
	if ( $c2 ) { $crumb .= ' › ' . ( $c3 ? '<a class="iv-link" href="' . esc_url( $here( array( $c1, $c2, 0 ) ) ) . '">' . esc_html( md_inv_cat_name( $c2 ) ) . '</a>' : '<b>' . esc_html( md_inv_cat_name( $c2 ) ) . '</b>' ); }
	if ( $c3 ) { $crumb .= ' › <b>' . esc_html( -1 === $c3 ? '기타' : md_inv_cat_name( $c3 ) ) . '</b>'; }
	$crumb .= '</p>';
	/* 타일 단계 */
	$tiles = array();
	if ( ! $c1 ) { foreach ( md_inv_cats_of( 1 ) as $c ) { $tiles[] = array( $c->name, array( (int) $c->id, 0, 0 ) ); } }
	elseif ( ! $c2 ) { foreach ( md_inv_cats_of( 2, $c1 ) as $c ) { $tiles[] = array( $c->name, array( $c1, (int) $c->id, 0 ) ); } }
	elseif ( ! $c3 && md_inv_cats_of( 3, $c2 ) ) {
		foreach ( md_inv_cats_of( 3, $c2 ) as $c ) { $tiles[] = array( $c->name, array( $c1, $c2, (int) $c->id ) ); }
		$tiles[] = array( '기타', array( $c1, $c2, -1 ) );
	}
	echo '<section class="iv-panel iv-qcbrowse" id="iv-qcbrowse"><h3 class="iv-h3">분류별로 세기</h3>' . $crumb; // phpcs:ignore
	if ( $tiles ) {
		echo '<div class="iv-tiles">';
		foreach ( $tiles as $t ) {
			$list = $in( $t[1] );
			if ( '기타' === $t[0] && ! $list ) { continue; }
			$done = 0; foreach ( $list as $it ) { if ( isset( $today[ (int) $it->id ] ) ) { $done++; } }
			echo '<a class="iv-tile' . ( $list ? '' : ' iv-tile--empty' ) . '" href="' . esc_url( $here( $t[1] ) ) . '"><b>' . esc_html( $t[0] ) . '</b><small>' . ( $list ? count( $list ) . '개' . ( $done ? ' · 오늘 ' . $done . '개 셈' : '' ) : '아직 품목 없음' ) . '</small></a>';
		}
		if ( $c1 ) { $all = $in( array( $c1, $c2, 0 ) ); if ( $all ) { echo '<a class="iv-tile iv-tile--all" href="' . esc_url( add_query_arg( 'iall', 1, $here( array( $c1, $c2, 0 ) ) ) ) . '"><b>모두 보기</b><small>' . count( $all ) . '개</small></a>'; } }
		echo '</div>';
		if ( ! md_inv_get( 'iall' ) ) { echo '</section>'; return; }
	}
	$list = $in( array( $c1, $c2, $c3 ) );
	$list = md_inv_sort_by_cat( $list );
	if ( ! $list ) { echo '<p class="iv-help">이 분류에는 아직 품목이 없습니다.</p></section>'; return; }
	$n_done = 0; foreach ( $list as $it ) { if ( isset( $today[ (int) $it->id ] ) ) { $n_done++; } }
	echo '<p class="iv-help">' . count( $list ) . '개 중 오늘 센 것 <b>' . (int) $n_done . '</b>개 — 품목을 누르면 센 수량을 넣고, 저장하면 이 목록으로 돌아옵니다.</p><div class="iv-qclist">';
	foreach ( $list as $it ) {
		$d = isset( $today[ (int) $it->id ] );
		echo '<a class="iv-qcitem' . ( $d ? ' is-done' : '' ) . '" href="' . esc_url( add_query_arg( 'id', (int) $it->id, $here( array( $c1, $c2, $c3 ) ) ) . '#iv-qc-card' ) . '"><span class="iv-qcitem__name">' . ( $d ? '✓ ' : '' ) . esc_html( $it->name ) . '</span><span class="iv-qcitem__book">장부 ' . (int) $it->stock . ( $d ? ' · 센 ' . (int) $today[ (int) $it->id ] : '' ) . '</span>' . ( '' !== (string) $it->location ? '<small>📍' . esc_html( $it->location ) . '</small>' : '' ) . '</a>';
	}
	echo '</div></section>';
}
