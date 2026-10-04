<?php
/**
 * 재고관리 v5 — 화면 뼈대 · 공통 부품
 *
 * 주소: /직원/?app=stock&iv=<화면>
 *   iv 는 워드프레스 공개 질의 변수와 겹치지 않는 이름이다 (s · m · p · page · order 등은 쓰면 안 된다).
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** 화면 목록 — admin: 관리자만 · staff_opt: 직원은 설정이 켜져 있을 때만 */
function md_inv_views() {
	return array(
		'req'      => array( 'label' => '신청',   'icon' => 'cart',    'admin' => false ),
		'mine'     => array( 'label' => '내역',   'icon' => 'list',    'admin' => false ),
		'todo'     => array( 'label' => '할 일',  'icon' => 'check',   'admin' => true ),
		'stock'    => array( 'label' => '재고',   'icon' => 'box',     'admin' => true, 'staff_opt' => 'staff_stock_tab' ),
		'orders'   => array( 'label' => '주문',   'icon' => 'truck',   'admin' => true ),
		'prepaid'  => array( 'label' => '선납',   'icon' => 'wallet',  'admin' => true ),
		'ledger'   => array( 'label' => '입출고', 'icon' => 'swap',    'admin' => true ),
		'stats'    => array( 'label' => '통계',   'icon' => 'chart',   'admin' => false, 'staff_opt' => 'staff_stats' ),
		'settings' => array( 'label' => '설정',   'icon' => 'gear',    'admin' => true ),
		/* 메뉴에 없는 화면 */
		'item'     => array( 'label' => '품목',   'icon' => 'box',     'admin' => true, 'hidden' => true ),
		'count'    => array( 'label' => '실사',   'icon' => 'box',     'admin' => true, 'hidden' => true ),
		'qcount'   => array( 'label' => '휴대폰 실사', 'icon' => 'box', 'admin' => true, 'hidden' => true ),
		'barcode'  => array( 'label' => '바코드 등록', 'icon' => 'scan', 'admin' => true, 'hidden' => true ),
		'fix'      => array( 'label' => '정리할 품목', 'icon' => 'edit', 'admin' => true, 'hidden' => true ),
		'pick'     => array( 'label' => '출고 준비 목록', 'icon' => 'list', 'admin' => true, 'hidden' => true ),
		'po'       => array( 'label' => '업체별 발주서', 'icon' => 'truck', 'admin' => true, 'hidden' => true ),
		'receive'  => array( 'label' => '바코드 입고', 'icon' => 'scan', 'admin' => true, 'hidden' => true ),
		'settle'   => array( 'label' => '월말 업체 정산', 'icon' => 'list', 'admin' => true, 'hidden' => true ),
	);
}

function md_inv_view_allowed( $key ) {
	$v = md_inv_views();
	if ( ! isset( $v[ $key ] ) ) { return false; }
	if ( md_inv_is_admin() ) { return true; }
	if ( $v[ $key ]['admin'] && empty( $v[ $key ]['staff_opt'] ) ) { return false; }
	if ( ! empty( $v[ $key ]['staff_opt'] ) ) { return (bool) md_inv_set( $v[ $key ]['staff_opt'] ); }
	return true;
}

function md_inv_current_view() {
	$k = isset( $_GET['iv'] ) ? sanitize_key( wp_unslash( $_GET['iv'] ) ) : 'req';
	return md_inv_view_allowed( $k ) ? $k : 'req';
}

function md_inv_url( $args = array() ) {
	$base = function_exists( 'md_sup_url' ) ? md_sup_url( array( 'app' => 'stock' ) ) : home_url( '/직원/?app=stock' );
	return add_query_arg( $args, $base );
}

/** 지금 보고 있는 주소 (결과 문구 표식 빼고) */
function md_inv_here() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	return remove_query_arg( array( 'mf', 'sent' ), home_url( $uri ) );
}

/** 폼 머리 — 동작 이름 · nonce · 돌아갈 곳 */
function md_inv_hidden( $action, $back = '' ) {
	echo '<input type="hidden" name="md_inv" value="' . esc_attr( $action ) . '">';
	echo '<input type="hidden" name="_mdinv" value="' . esc_attr( wp_create_nonce( 'md_inv_post' ) ) . '">';
	echo '<input type="hidden" name="back" value="' . esc_attr( '' !== $back ? $back : md_inv_here() ) . '">';
}

/** 아이콘 (선 아이콘 · 글자색을 따른다) */
function md_inv_icon( $name, $size = 20 ) {
	$p = array(
		'cart'   => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3.5h2.6l2.3 11.2a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.1L21 7.5H6"/>',
		'list'   => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="3.5" cy="6" r="1"/><circle cx="3.5" cy="12" r="1"/><circle cx="3.5" cy="18" r="1"/>',
		'check'  => '<rect x="3" y="3" width="18" height="18" rx="4"/><path d="m8 12.5 2.7 2.7L16.5 9"/>',
		'box'    => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
		'truck'  => '<path d="M3 6h11v10H3zM14 9h4l3 3.5V16h-7"/><circle cx="7" cy="18" r="1.8"/><circle cx="17.5" cy="18" r="1.8"/>',
		'wallet' => '<rect x="3" y="6" width="18" height="14" rx="3"/><path d="M16 13h2M3 9h15a3 3 0 0 0 0 0V6.5A2.5 2.5 0 0 0 15.5 4H6a3 3 0 0 0-3 3"/>',
		'swap'   => '<path d="M7 4 3 8l4 4M3 8h14M17 20l4-4-4-4M21 16H7"/>',
		'chart'  => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
		'gear'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
		'more'   => '<circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/>',
		'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'scan'   => '<path d="M3 8V5a2 2 0 0 1 2-2h3M16 3h3a2 2 0 0 1 2 2v3M21 16v3a2 2 0 0 1-2 2h-3M8 21H5a2 2 0 0 1-2-2v-3M7 8v8M10.5 8v8M14 8v8M17 8v8"/>',
		'plus'   => '<path d="M12 5v14M5 12h14"/>',
		'minus'  => '<path d="M5 12h14"/>',
		'x'      => '<path d="M6 6l12 12M18 6 6 18"/>',
		'down'   => '<path d="M12 4v13M6 12l6 6 6-6M4 21h16"/>',
		'up'     => '<path d="m6 15 6-6 6 6"/>',
		'dn'     => '<path d="m6 9 6 6 6-6"/>',
		'edit'   => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/>',
		'star'   => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
		'alert'  => '<path d="M12 3 2 20h20zM12 10v4M12 17.5v.5"/>',
	);
	$d = isset( $p[ $name ] ) ? $p[ $name ] : $p['box'];
	return '<svg class="iv-ico" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/** 재고 배지 */
function md_inv_stock_badge( $it ) {
	$st = md_inv_stock_state( $it );
	$tx = 'out' === $st ? '품절' : ( 'low' === $st ? '부족 ' . md_inv_num( $it->stock ) : md_inv_num( $it->stock ) );
	if ( 'out' === $st && (int) $it->stock < 0 ) { $tx = '품절 (' . (int) $it->stock . ')'; }
	return '<span class="iv-badge iv-badge--' . $st . '">' . esc_html( $tx ) . '</span>';
}

function md_inv_req_badge( $r ) {
	$m = array( 'pending' => 'wait', 'done' => 'ok', 'rejected' => 'no', 'cancelled' => 'off' );
	$tx = md_inv_req_status_label( $r->status );
	if ( 'done' === $r->status && $r->qty_out && $r->qty_out < $r->qty ) { $tx = $r->qty_out . '개 출고'; }
	return '<span class="iv-chip iv-chip--' . ( isset( $m[ $r->status ] ) ? $m[ $r->status ] : 'off' ) . '">' . esc_html( $tx ) . '</span>';
}

function md_inv_ord_badge( $o ) {
	$m = array( 'ordered' => 'wait', 'received' => 'ok', 'cancelled' => 'off' );
	$tx = md_inv_ord_status_label( $o->status );
	if ( 'ordered' === $o->status && (int) $o->recv_qty > 0 ) { $tx = '일부 입고 ' . (int) $o->recv_qty . '/' . (int) $o->qty; }
	return '<span class="iv-chip iv-chip--' . $m[ $o->status ] . '">' . esc_html( $tx ) . '</span>';
}

/** 「3분 전 · 어제 · 9/28」 */
function md_inv_ago( $dt ) {
	if ( ! $dt ) { return ''; }
	$ts  = strtotime( $dt );
	$now = current_time( 'timestamp' );
	$d   = $now - $ts;
	if ( $d < 60 ) { return '방금'; }
	if ( $d < 3600 ) { return floor( $d / 60 ) . '분 전'; }
	if ( date( 'Y-m-d', $ts ) === date( 'Y-m-d', $now ) ) { return date( 'H:i', $ts ); }
	if ( date( 'Y-m-d', $ts ) === date( 'Y-m-d', $now - DAY_IN_SECONDS ) ) { return '어제 ' . date( 'H:i', $ts ); }
	if ( date( 'Y', $ts ) === date( 'Y', $now ) ) { return date( 'n/j H:i', $ts ); }
	return date( 'Y.n.j', $ts );
}

/** 품목 고르는 칸 (검색해서 고르기) — 목록은 화면에 한 번만 싣는다 */
function md_inv_item_picker( $name = 'item_id', $required = true, $value = 0, $placeholder = '품목 이름을 입력해 고르세요' ) {
	static $printed = false;
	if ( ! $printed ) {
		$printed = true;
		echo '<datalist id="iv-items-dl">';
		foreach ( md_inv_items( array( 'active' => -1 ) ) as $it ) {
			echo '<option value="' . esc_attr( md_inv_pick_label( $it ) ) . '"></option>';
		}
		echo '</datalist>';
	}
	$it = $value ? md_inv_item( $value ) : null;
	echo '<input type="text" class="iv-input" list="iv-items-dl" name="' . esc_attr( $name ) . '_pick" data-pick="' . esc_attr( $name ) . '" autocomplete="off" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $it ? md_inv_pick_label( $it ) : '' ) . '"' . ( $required ? ' required' : '' ) . '>';
	echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . (int) $value . '">';
}

function md_inv_pick_label( $it ) {
	$v = md_inv_vendor_name( $it->vendor_id );
	return $it->name . ( $v ? ' · ' . $v : '' ) . ( $it->active ? '' : ' (숨김)' ) . ' #' . $it->id;
}

/** 「품목명 · 업체 #12」 처럼 적힌 칸에서 품목 번호를 꺼낸다 (스크립트가 못 채웠을 때) */
add_action( 'template_redirect', function () {
	if ( empty( $_POST['md_inv'] ) ) { return; }
	foreach ( $_POST as $k => $v ) {
		if ( ! is_string( $v ) || '_pick' !== substr( $k, -5 ) ) { continue; }
		$field = substr( $k, 0, -5 );
		if ( ! empty( $_POST[ $field ] ) ) { continue; }
		if ( preg_match( '/#(\d+)\s*$/', wp_unslash( $v ), $m ) ) { $_POST[ $field ] = $m[1]; }
	}
}, 0 );

/** 팀 고르는 칸 */
function md_inv_team_select( $name = 'team_id', $value = 0, $required = true, $empty = '팀을 고르세요', $all = false ) {
	echo '<select class="iv-input" name="' . esc_attr( $name ) . '"' . ( $required ? ' required' : '' ) . '>';
	echo '<option value="">' . esc_html( $empty ) . '</option>';
	foreach ( md_inv_teams( ! $all ) as $tm ) {
		echo '<option value="' . (int) $tm->id . '"' . selected( (int) $value, (int) $tm->id, false ) . '>' . esc_html( $tm->name . ( $tm->active ? '' : ' (사용 안 함)' ) ) . '</option>';
	}
	echo '</select>';
}

function md_inv_vendor_select( $name = 'vendor_id', $value = 0, $required = false, $empty = '— 업체 —', $only_prepaid = false ) {
	echo '<select class="iv-input" name="' . esc_attr( $name ) . '"' . ( $required ? ' required' : '' ) . '>';
	echo '<option value="">' . esc_html( $empty ) . '</option>';
	foreach ( md_inv_vendors( false ) as $v ) {
		if ( $only_prepaid && ! (int) $v->prepaid ) { continue; }
		if ( ! (int) $v->active && (int) $v->id !== (int) $value ) { continue; }
		echo '<option value="' . (int) $v->id . '"' . selected( (int) $value, (int) $v->id, false ) . '>' . esc_html( $v->name . ( $v->prepaid ? ' (선납)' : '' ) ) . '</option>';
	}
	echo '</select>';
}

/** 분류 3단계 고르기 — 위를 고르면 아래가 좁혀진다 (스크립트) */
function md_inv_cat_selects( $c1 = 0, $c2 = 0, $c3 = 0 ) {
	$L = md_inv_settings();
	$cats = array();
	foreach ( md_inv_cats() as $c ) { $cats[] = array( 'id' => (int) $c->id, 'p' => (int) $c->parent_id, 'l' => (int) $c->level, 'n' => $c->name, 'a' => (int) $c->active ); }
	echo '<div class="iv-catsel" data-cats="' . esc_attr( wp_json_encode( $cats ) ) . '">';
	foreach ( array( 1 => $c1, 2 => $c2, 3 => $c3 ) as $lv => $val ) {
		echo '<label class="iv-f"><span>' . esc_html( $L[ 'label_cat' . $lv ] ) . '</span><select class="iv-input" name="cat' . $lv . '" data-level="' . $lv . '" data-value="' . (int) $val . '">';
		echo '<option value="0">— 없음 —</option>';
		foreach ( md_inv_cats() as $c ) {
			if ( (int) $c->level !== $lv ) { continue; }
			if ( ! (int) $c->active && (int) $c->id !== (int) $val ) { continue; }
			echo '<option value="' . (int) $c->id . '" data-p="' . (int) $c->parent_id . '"' . selected( (int) $val, (int) $c->id, false ) . '>' . esc_html( $c->name ) . '</option>';
		}
		echo '</select></label>';
	}
	echo '</div>';
}

/** 내려받기 버튼 묶음 */
function md_inv_dl_buttons( $key, $args = array(), $label = '엑셀' ) {
	echo '<a class="iv-btn iv-btn--ghost iv-btn--sm" href="' . esc_url( md_inv_dl_url( $key, $args ) ) . '">' . md_inv_icon( 'down', 16 ) . esc_html( $label ) . '</a>';
}

/** 빈 화면 */
function md_inv_empty( $text ) {
	echo '<div class="iv-empty">' . esc_html( $text ) . '</div>';
}

/** 페이지 넘김 */
function md_inv_pager( $total, $per, $page ) {
	$pages = (int) ceil( $total / max( 1, $per ) );
	if ( $pages <= 1 ) { return; }
	echo '<nav class="iv-pager">';
	if ( $page > 1 ) { echo '<a class="iv-btn iv-btn--ghost iv-btn--sm" href="' . esc_url( add_query_arg( 'pg', $page - 1, md_inv_here() ) ) . '">← 이전</a>'; }
	echo '<span>' . (int) $page . ' / ' . $pages . '</span>';
	if ( $page < $pages ) { echo '<a class="iv-btn iv-btn--ghost iv-btn--sm" href="' . esc_url( add_query_arg( 'pg', $page + 1, md_inv_here() ) ) . '">다음 →</a>'; }
	echo '</nav>';
}

function md_inv_get( $k, $default = '' ) {
	return isset( $_GET[ $k ] ) ? sanitize_text_field( wp_unslash( $_GET[ $k ] ) ) : $default;
}

function md_inv_get_date( $k, $default ) {
	$v = md_inv_get( $k );
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : $default;
}

/* ============================================================
 * 진입점
 * ============================================================ */

function md_inv_render() {
	md_inv_migrate();
	$view  = md_inv_current_view();
	$admin = md_inv_is_admin();
	$c     = $admin ? md_inv_counts() : array( 'pending' => 0, 'ordered' => 0, 'need' => 0 );

	echo '<div class="iv-app" data-admin="' . ( $admin ? '1' : '0' ) . '">';

	/* 메뉴 — 데스크톱은 위 탭, 휴대폰은 아래 고정 메뉴 */
	$items = array();
	foreach ( md_inv_views() as $k => $v ) {
		if ( ! empty( $v['hidden'] ) || ! md_inv_view_allowed( $k ) ) { continue; }
		$badge = 0;
		if ( 'todo' === $k ) { $badge = $c['pending']; }
		if ( 'orders' === $k ) { $badge = $c['ordered']; }
		$items[ $k ] = array( 'label' => $v['label'], 'icon' => $v['icon'], 'badge' => $badge );
	}
	$parent = array( 'item' => 'stock', 'count' => 'stock', 'qcount' => 'stock', 'barcode' => 'stock', 'fix' => 'stock', 'pick' => 'todo', 'po' => 'orders', 'receive' => 'todo', 'settle' => 'orders' );
	$on = isset( $parent[ $view ] ) ? $parent[ $view ] : $view;
	echo '<nav class="iv-nav" aria-label="품목신청 메뉴">';
	foreach ( $items as $k => $v ) {
		echo '<a class="iv-nav__a' . ( $on === $k ? ' is-on' : '' ) . '" href="' . esc_url( md_inv_url( array( 'iv' => $k ) ) ) . '"' . ( $on === $k ? ' aria-current="page"' : '' ) . '>'
			. md_inv_icon( $v['icon'], 18 ) . '<span>' . esc_html( $v['label'] ) . '</span>'
			. ( $v['badge'] ? '<b class="iv-nav__badge">' . (int) $v['badge'] . '</b>' : '' ) . '</a>';
	}
	echo '<a class="iv-nav__a iv-nav__xl" href="' . esc_url( md_inv_dl_url( 'xlsx' ) ) . '" title="' . esc_attr( $admin ? '전체 보고서 엑셀 (최근 30일 기록 · 지금 재고 · 선납 · 업체)' : '신청 내역 · 통계 엑셀' ) . '">' . md_inv_icon( 'down', 18 ) . '<span>엑셀</span></a>';
	echo '</nav>';

	/* 휴대폰 아래 메뉴: 앞 4개 + 더보기 */
	$primary = $admin ? array( 'req', 'todo', 'stock', 'stats' ) : array_slice( array_keys( $items ), 0, 4 );
	$rest    = array_diff( array_keys( $items ), $primary );
	echo '<nav class="iv-tabbar" aria-label="품목신청 메뉴 (휴대폰)">';
	foreach ( $primary as $k ) {
		if ( ! isset( $items[ $k ] ) ) { continue; }
		$v = $items[ $k ];
		echo '<a class="iv-tabbar__a' . ( $on === $k ? ' is-on' : '' ) . '" href="' . esc_url( md_inv_url( array( 'iv' => $k ) ) ) . '">' . md_inv_icon( $v['icon'], 22 ) . '<span>' . esc_html( $v['label'] ) . '</span>' . ( $v['badge'] ? '<b class="iv-nav__badge">' . (int) $v['badge'] . '</b>' : '' ) . '</a>';
	}
	if ( true ) {
		$rest_on = in_array( $on, $rest, true );
		$rb = 0; foreach ( $rest as $k ) { $rb += $items[ $k ]['badge']; }
		echo '<button type="button" class="iv-tabbar__a' . ( $rest_on ? ' is-on' : '' ) . '" data-dlg="iv-more">' . md_inv_icon( 'more', 22 ) . '<span>' . ( $rest_on ? esc_html( $items[ $on ]['label'] ) : '더보기' ) . '</span>' . ( $rb ? '<b class="iv-nav__badge">' . (int) $rb . '</b>' : '' ) . '</button>';
	}
	echo '</nav>';
	if ( true ) {
		echo '<dialog class="iv-dlg iv-dlg--sheet" id="iv-more"><div class="iv-dlg__head"><b>메뉴</b><button type="button" class="iv-x" data-close>' . md_inv_icon( 'x' ) . '</button></div><div class="iv-more">';
		foreach ( $rest as $k ) {
			$v = $items[ $k ];
			echo '<a class="iv-more__a' . ( $on === $k ? ' is-on' : '' ) . '" href="' . esc_url( md_inv_url( array( 'iv' => $k ) ) ) . '">' . md_inv_icon( $v['icon'], 22 ) . '<span>' . esc_html( $v['label'] ) . '</span>' . ( $v['badge'] ? '<b class="iv-nav__badge">' . (int) $v['badge'] . '</b>' : '' ) . '</a>';
		}
		echo '<a class="iv-more__a" href="' . esc_url( md_inv_dl_url( 'xlsx' ) ) . '">' . md_inv_icon( 'down', 22 ) . '<span>엑셀 받기</span></a>';
		echo '</div></dialog>';
	}

	$f = md_inv_flash_take();
	/* 신청을 막 보낸 내역 화면은 아래 초록 안내 하나로 충분하다 */
	if ( $f && 'ok' === $f[0] && 'mine' === $view && '' !== md_inv_get( 'nb' ) ) { $f = null; }
	if ( $f ) {
		$cls = 'ok' === $f[0] ? 'ok' : ( 'warn' === $f[0] ? 'warn' : 'err' );
		echo '<div class="iv-flash iv-flash--' . $cls . '" role="' . ( 'ok' === $cls ? 'status' : 'alert' ) . '">' . esc_html( $f[1] ) . '<button type="button" class="iv-x" data-dismiss aria-label="닫기">' . md_inv_icon( 'x', 16 ) . '</button></div>';
	}

	echo '<script type="application/json" id="iv-done-feed">' . wp_json_encode( md_inv_done_feed(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>';
	echo '<div class="iv-view iv-view--' . esc_attr( $view ) . '">';
	switch ( $view ) {
		case 'mine':     md_inv_view_mine(); break;
		case 'todo':     md_inv_view_todo(); break;
		case 'stock':    md_inv_view_stock(); break;
		case 'item':     md_inv_view_item(); break;
		case 'count':    md_inv_view_count(); break;
		case 'qcount':   md_inv_view_qcount(); break;
		case 'barcode':  md_inv_view_barcode(); break;
		case 'fix':      md_inv_view_fix(); break;
		case 'pick':     md_inv_view_pick(); break;
		case 'po':       md_inv_view_po(); break;
		case 'receive':  md_inv_view_receive(); break;
		case 'settle':   md_inv_view_settle(); break;
		case 'orders':   md_inv_view_orders(); break;
		case 'prepaid':  md_inv_view_prepaid(); break;
		case 'ledger':   md_inv_view_ledger(); break;
		case 'stats':    md_inv_view_stats(); break;
		case 'settings': md_inv_view_settings(); break;
		default:         md_inv_view_req(); break;
	}
	echo '</div>';

	/* 관리자 — 기록에 남길 이름 */
	if ( $admin && md_inv_is_personal() ) {
		echo '<p class="iv-me">기록에 남는 이름: <b>' . esc_html( md_inv_me() ) . '</b> (개인 계정)</p>';
	} elseif ( $admin ) {
		echo '<p class="iv-me">기록에 남는 이름: <b>' . esc_html( md_inv_me() ) . '</b> <button type="button" class="iv-link" data-dlg="iv-me-dlg">바꾸기</button></p>';
		echo '<dialog class="iv-dlg" id="iv-me-dlg"><form method="post">';
		md_inv_hidden( 'me' );
		echo '<div class="iv-dlg__head"><b>이 기기에서 쓸 이름</b><button type="button" class="iv-x" data-close>' . md_inv_icon( 'x' ) . '</button></div>';
		echo '<p class="iv-help">관리자 계정을 여럿이 함께 쓰므로, 출고·입고 기록에 누가 했는지 남기려고 이 기기에 이름을 기억합니다.</p>';
		echo '<label class="iv-f"><span>이름</span><input class="iv-input" name="me" maxlength="40" value="' . esc_attr( isset( $_COOKIE['md_inv_me'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['md_inv_me'] ) ) : '' ) . '" placeholder="' . esc_attr( md_inv_set( 'me_default' ) ) . '"></label>';
		echo '<div class="iv-dlg__foot"><button class="iv-btn iv-btn--primary">저장</button></div></form></dialog>';
	}
	echo '</div>';
}
