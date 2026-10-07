<?php
/**
 * 재고관리 v5 — 폼 처리
 *
 * 모든 버튼은 POST → 처리 → 원래 화면으로 리다이렉트(PRG).
 * 새로고침해도 두 번 처리되지 않고, 결과 문구는 한 번만 보인다.
 *
 * 지키는 것
 *   · nonce — 다른 사이트에서 버튼을 눌리게 하는 위조 방지
 *   · 권한 — 직원이 할 수 있는 일은 목록(md_inv_staff_actions)에 있는 것뿐. 나머지는 관리자.
 *   · 한 번만 — 요청 보내기는 일회용 표(tok)로 두 번 눌러도 한 번만 들어간다
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** 직원(공용 계정)도 할 수 있는 일 */
function md_inv_staff_actions() {
	return array( 'req_send', 'req_cancel', 'fav_toggle', 'mail_pref' );
}

/** 결과 문구를 한 번만 보이게 넘긴다 */
function md_inv_flash( $type, $text ) {
	$key = wp_generate_password( 10, false );
	set_transient( 'md_inv_flash_' . $key, array( $type, (string) $text ), 5 * MINUTE_IN_SECONDS );
	return $key;
}

function md_inv_flash_take() {
	if ( empty( $_GET['mf'] ) ) { return null; }
	$key = preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_GET['mf'] ) );
	$f   = get_transient( 'md_inv_flash_' . $key );
	if ( $f ) { delete_transient( 'md_inv_flash_' . $key ); }
	return is_array( $f ) ? $f : null;
}

/** 돌아갈 주소 — 이 사이트 주소만 */
function md_inv_back_url() {
	$b = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : '';
	$b = wp_validate_redirect( $b, '' );
	if ( '' === $b ) { $b = md_inv_url(); }
	return remove_query_arg( array( 'mf' ), $b );
}

function md_inv_go( $type, $text, $url = '' ) {
	$url = '' !== $url ? $url : md_inv_back_url();
	wp_safe_redirect( add_query_arg( 'mf', md_inv_flash( $type, $text ), $url ) );
	exit;
}

function md_inv_p( $k, $default = '' ) {
	return isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : $default;
}

/** 결과 → 문구 */
function md_inv_done( $res, $ok_text, $url = '' ) {
	if ( is_wp_error( $res ) ) { md_inv_go( 'err', $res->get_error_message(), $url ); }
	md_inv_go( 'ok', $ok_text, $url );
}

function md_inv_handle_post() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { return; }
	if ( empty( $_POST['md_inv'] ) ) { return; }
	if ( ! is_user_logged_in() ) { wp_safe_redirect( home_url( '/직원/' ) ); exit; }
	if ( ! md_inv_can_use() ) { wp_die( '재고관리 권한이 없습니다.', '', array( 'response' => 403 ) ); }
	if ( ! isset( $_POST['_mdinv'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_mdinv'] ) ), 'md_inv_post' ) ) {
		md_inv_go( 'err', '화면이 오래되어 처리하지 않았습니다. 새로고침한 뒤 다시 눌러 주세요.' );
	}
	md_inv_migrate();
	$a = sanitize_key( wp_unslash( $_POST['md_inv'] ) );
	if ( ! in_array( $a, md_inv_staff_actions(), true ) && ! md_inv_is_admin() ) {
		md_inv_go( 'err', '관리자만 할 수 있는 일입니다.' );
	}
	$fn = 'md_inv_act_' . $a;
	if ( ! function_exists( $fn ) ) { md_inv_go( 'err', '알 수 없는 동작입니다.' ); }
	call_user_func( $fn );
	md_inv_go( 'ok', '처리했습니다.' );
}
add_action( 'template_redirect', 'md_inv_handle_post', 1 );

/* ============================================================
 * 요청 (직원도)
 * ============================================================ */

function md_inv_act_req_send() {
	$tok = preg_replace( '/[^A-Za-z0-9]/', '', (string) md_inv_p( 'tok' ) );
	$hist = md_inv_url( array( 'iv' => 'mine' ) );
	if ( '' !== $tok && get_transient( 'md_inv_tok_' . $tok ) ) {
		/* 같은 장바구니를 두 번 보냈다 — 두 번째는 넣지 않는다 */
		md_inv_go( 'ok', '신청은 이미 들어가 있습니다.', add_query_arg( 'sent', $tok, $hist ) );
	}
	$raw   = json_decode( (string) md_inv_p( 'cart' ), true );
	$lines = array();
	if ( is_array( $raw ) ) {
		foreach ( $raw as $ln ) {
			if ( ! is_array( $ln ) ) { continue; }
			$lines[] = array(
				'item_id' => isset( $ln['id'] ) ? (int) $ln['id'] : 0,
				'qty'     => isset( $ln['qty'] ) ? (int) $ln['qty'] : 0,
				'note'    => isset( $ln['note'] ) ? (string) $ln['note'] : '',
				'custom'  => isset( $ln['custom'] ) && is_array( $ln['custom'] ) ? $ln['custom'] : array(),
				'photos'  => isset( $ln['ph'] ) && is_array( $ln['ph'] ) ? array_map( 'strval', $ln['ph'] ) : array(), /* v9.3 */
			);
		}
	}
	/* v5.8 · 개인 계정은 신청자 이름을 계정 이름으로 못박는다 — 누가 신청했는지 확실하게 */
	$who = md_inv_is_personal() ? md_inv_me() : (string) md_inv_p( 'requester' );
	$res = md_inv_req_create( (int) md_inv_p( 'team_id' ), $who, $lines, (string) md_inv_p( 'note' ), md_inv_p( 'urgent' ) ? 1 : 0 );
	if ( is_wp_error( $res ) ) { md_inv_go( 'err', $res->get_error_message(), md_inv_url( array( 'iv' => 'req' ) ) ); }
	if ( md_inv_is_personal() ) { update_user_meta( get_current_user_id(), 'md_inv_team', (int) md_inv_p( 'team_id' ) ); }
	if ( '' !== $tok ) { set_transient( 'md_inv_tok_' . $tok, 1, DAY_IN_SECONDS ); }
	$first = md_inv_req( $res[0] );
	md_inv_go( 'ok', '신청 ' . count( $res ) . '건을 보냈습니다. 처리되면 이 화면에서 확인할 수 있습니다.', add_query_arg( array( 'sent' => $tok, 'nb' => $first ? $first->batch : '', 'it' => (int) md_inv_p( 'team_id' ) ), $hist ) );
}

/** 팀 즐겨찾기 켜고 끄기 — 화면은 그대로 두고 결과만 돌려준다 */
function md_inv_act_fav_toggle() {
	$r = md_inv_fav_toggle( (int) md_inv_p( 'team_id' ), (int) md_inv_p( 'item_id' ) );
	if ( md_inv_p( 'ajax' ) ) {
		if ( is_wp_error( $r ) ) { wp_send_json( array( 'ok' => false, 'msg' => $r->get_error_message() ) ); }
		wp_send_json( array( 'ok' => true, 'on' => (bool) $r ) );
	}
	md_inv_done( $r, $r ? '즐겨찾기에 넣었습니다.' : '즐겨찾기에서 뺐습니다.' );
}

function md_inv_act_req_cancel() {
	md_inv_done( md_inv_req_cancel( (int) md_inv_p( 'id' ) ), '신청을 취소했습니다.' );
}

/* ============================================================
 * 요청 처리 (관리자)
 * ============================================================ */

function md_inv_act_req_release() {
	$res = md_inv_req_release( (int) md_inv_p( 'id' ), (int) md_inv_p( 'qty' ), true, (string) md_inv_p( 'note' ), (string) md_inv_p( 'receiver' ), array( 'lot' => md_inv_p( 'lot' ), 'chart' => md_inv_p( 'chart' ) ) );
	if ( is_wp_error( $res ) ) { md_inv_go( 'err', $res->get_error_message() ); }
	md_inv_go( 'ok', $res['rest'] ? '출고했습니다. 남은 수량은 새 대기 요청으로 남겨 두었습니다.' : '출고했습니다. 재고에서 뺐습니다.' );
}

/** 선택한 요청 한꺼번에 출고 (요청 수량 그대로) */
function md_inv_act_req_release_many() {
	$ids = array_filter( array_map( 'intval', (array) md_inv_p( 'ids', array() ) ) );
	if ( ! $ids ) { md_inv_go( 'err', '출고할 요청을 골라 주세요.' ); }
	$ok = 0; $fail = array();
	foreach ( $ids as $id ) {
		$r = md_inv_req( $id );
		if ( ! $r ) { continue; }
		$res = md_inv_req_release( $id, $r->qty, true, '', $r->requester ); /* 한꺼번에 출고: 받은 사람 = 신청자 */
		if ( is_wp_error( $res ) ) { $fail[] = $r->name . ': ' . $res->get_error_message(); } else { $ok++; }
	}
	if ( $fail ) { md_inv_go( $ok ? 'warn' : 'err', $ok . '건 출고 · ' . count( $fail ) . '건 못 함 — ' . implode( ' / ', array_slice( $fail, 0, 5 ) ) ); }
	md_inv_go( 'ok', $ok . '건을 출고했습니다.' );
}

function md_inv_act_req_reject() {
	md_inv_done( md_inv_req_reject( (int) md_inv_p( 'id' ), (string) md_inv_p( 'reason' ) ), '반려했습니다. 사유가 요청한 팀 내역에 보입니다.' );
}

function md_inv_act_req_admin_cancel() {
	md_inv_done( md_inv_req_cancel( (int) md_inv_p( 'id' ) ), '요청을 취소했습니다.' );
}

function md_inv_act_req_update() {
	$d = array( 'qty' => (int) md_inv_p( 'qty' ), 'admin_note' => (string) md_inv_p( 'admin_note' ) );
	if ( (int) md_inv_p( 'item_id' ) ) { $d['item_id'] = (int) md_inv_p( 'item_id' ); }
	if ( (int) md_inv_p( 'team_id' ) ) { $d['team_id'] = (int) md_inv_p( 'team_id' ); }
	md_inv_done( md_inv_req_update( (int) md_inv_p( 'id' ), $d ), '요청을 고쳤습니다.' );
}

function md_inv_act_req_complete() {
	md_inv_done( md_inv_req_complete_custom( (int) md_inv_p( 'id' ), (string) md_inv_p( 'note' ) ), '처리 완료로 바꿨습니다 (재고 기록 없음).' );
}

/** 목록에 없는 품목 요청 → 품목으로 등록하고 요청에 연결 */
function md_inv_act_req_register() {
	$req = md_inv_req( (int) md_inv_p( 'id' ) );
	if ( ! $req || 'pending' !== $req->status || $req->item_id ) { md_inv_go( 'err', '대기 중인 「목록에 없는 품목」 요청이 아닙니다.' ); }
	$new = md_inv_item_save( 0, md_inv_item_fields_from_post() );
	if ( is_wp_error( $new ) ) { md_inv_go( 'err', $new->get_error_message() ); }
	md_inv_req_update( $req->id, array( 'item_id' => $new ) );
	md_inv_go( 'ok', '품목으로 등록하고 요청에 연결했습니다. 이제 출고·주문할 수 있습니다.' );
}

/** 요청에서 바로 주문 */
function md_inv_act_req_order() {
	$req = md_inv_req( (int) md_inv_p( 'id' ) );
	if ( ! $req || ! $req->item_id ) { md_inv_go( 'err', '품목이 연결된 요청만 주문할 수 있습니다. 먼저 품목으로 등록해 주세요.' ); }
	md_inv_ord_done( md_inv_ord_create( $req->item_id, (int) md_inv_p( 'qty' ), array( 'price' => md_inv_p( 'price' ), 'total' => md_inv_ord_total_p(), 'free_qty' => md_inv_p( 'free_qty' ), 'note' => (string) md_inv_p( 'note' ), 'req_id' => $req->id ) ), '주문을 넣었습니다. 「할 일 › 입고 대기」에 있습니다.' );
}

/** v6.0 · 선납 업체 주문이면 「주문 뒤 쓸 수 있는 잔액」을 알리고, 모자라거나 알림 기준 아래면 경고 */
function md_inv_ord_done( $res, $ok_text ) {
	if ( is_wp_error( $res ) ) { md_inv_go( 'err', $res->get_error_message() ); }
	$o = md_inv_ord( (int) $res );
	$v = $o ? md_inv_vendor( $o->vendor_id ) : null;
	if ( $v && (int) $v->prepaid ) {
		$s = md_inv_prepaid_summary();
		$p = isset( $s[ (int) $v->id ] ) ? $s[ (int) $v->id ] : null;
		if ( $p ) {
			$txt = $ok_text . ' ' . $v->name . ' 선납 — 이 주문까지 잡으면 쓸 수 있는 잔액 ' . md_inv_won( $p->available ) . '.';
			if ( $p->available < 0 ) { md_inv_go( 'warn', $txt . ' 잔액보다 많이 주문했습니다. 입금을 기록하거나 업체에 확인해 주세요.' ); }
			if ( $p->alert ) { md_inv_go( 'warn', $txt . ' 알림 기준(' . md_inv_won( $v->pp_alert ) . ')보다 적습니다 — 재입금을 준비해 주세요.' ); }
			md_inv_go( 'ok', $txt );
		}
	}
	md_inv_go( 'ok', $ok_text );
}

/* ============================================================
 * 품목 · 재고
 * ============================================================ */

function md_inv_item_fields_from_post() {
	return array(
		'name' => md_inv_p( 'name' ), 'vendor_id' => (int) md_inv_p( 'vendor_id' ), 'unit' => md_inv_p( 'unit' ),
		'price' => md_inv_p( 'price' ), 'cat1' => (int) md_inv_p( 'cat1' ), 'cat2' => (int) md_inv_p( 'cat2' ), 'cat3' => (int) md_inv_p( 'cat3' ),
		'min_stock' => md_inv_p( 'min_stock' ), 'barcode' => md_inv_p( 'barcode' ), 'note' => md_inv_p( 'note' ), 'location' => md_inv_p( 'location' ),
		'code' => md_inv_p( 'code' ), 'open_qty' => md_inv_p( 'open_qty' ),
		'track_lot' => isset( $_POST['track_lot'] ) ? md_inv_p( 'track_lot' ) : '',
	);
}

function md_inv_act_item_save() {
	$id  = (int) md_inv_p( 'id' );
	$res = md_inv_item_save( $id, md_inv_item_fields_from_post() );
	if ( is_wp_error( $res ) ) { md_inv_go( 'err', $res->get_error_message() ); }
	md_inv_go( 'ok', $id ? '품목을 고쳤습니다.' : '품목을 등록했습니다.', $id ? '' : md_inv_url( array( 'iv' => 'item', 'id' => $res ) ) );
}

function md_inv_act_item_active() {
	md_inv_done( md_inv_item_set_active( (int) md_inv_p( 'id' ), (int) md_inv_p( 'on' ) ), md_inv_p( 'on' ) ? '다시 목록에 보이게 했습니다.' : '숨겼습니다. 재고 › 숨긴 품목에서 되돌릴 수 있습니다.' );
}

function md_inv_act_item_delete() {
	md_inv_done( md_inv_item_delete( (int) md_inv_p( 'id' ) ), '품목을 지웠습니다.', md_inv_url( array( 'iv' => 'stock' ) ) );
}

function md_inv_act_stock_in() {
	md_inv_done( md_inv_do_in( (int) md_inv_p( 'item_id' ), (int) md_inv_p( 'qty' ), array( 'price' => md_inv_p( 'price' ), 'free' => md_inv_p( 'free' ) ? 1 : 0, 'free_qty' => md_inv_p( 'free_qty' ), 'bonus_qty' => md_inv_p( 'bonus_qty' ), 'total' => md_inv_p( 'total' ), 'note' => md_inv_txt( md_inv_p( 'note' ), 500 ), 'lot' => md_inv_p( 'lot' ) ) ), '입고를 기록했습니다.' );
}

function md_inv_act_stock_out() {
	if ( md_inv_set( 'out_need_receiver' ) && '' === trim( (string) md_inv_p( 'receiver' ) ) ) { md_inv_go( 'err', '받은 사람 이름을 적어 주세요.' ); }
	md_inv_done( md_inv_do_out( (int) md_inv_p( 'item_id' ), (int) md_inv_p( 'qty' ), array( 'team_id' => (int) md_inv_p( 'team_id' ), 'receiver' => md_inv_p( 'receiver' ), 'note' => md_inv_txt( md_inv_p( 'note' ), 500 ), 'lot' => md_inv_p( 'lot' ), 'chart' => md_inv_p( 'chart' ) ) ), '출고를 기록했습니다.' );
}

function md_inv_act_stock_adjust() {
	$c = md_inv_p( 'counted' );
	if ( '' === trim( (string) $c ) ) { md_inv_go( 'err', '센 수량을 적어 주세요.' ); }
	md_inv_done( md_inv_do_adjust( (int) md_inv_p( 'item_id' ), md_inv_int( $c ), md_inv_txt( md_inv_p( 'note' ), 500 ) ), '실사 수량으로 맞췄습니다.' );
}

/** 실사 모드 — 여러 품목을 한 번에 */
function md_inv_act_stock_count() {
	$counts = (array) md_inv_p( 'cnt', array() );
	$note   = md_inv_txt( md_inv_p( 'note' ), 300 );
	if ( '' === $note ) { $note = '정기 실사 ' . current_time( 'Y-m-d' ); }
	$n = 0; $same = 0; $err = array();
	foreach ( $counts as $id => $v ) {
		if ( '' === trim( (string) $v ) ) { continue; }
		$id = (int) $id;
		$v  = md_inv_int( $v );
		if ( $v === md_inv_stock( $id ) ) { $same++; continue; }
		$r = md_inv_do_adjust( $id, $v, $note );
		if ( is_wp_error( $r ) ) { $err[] = $r->get_error_message(); } else { $n++; }
	}
	if ( $err ) { md_inv_go( 'warn', $n . '개 품목을 맞췄습니다. 못 한 것: ' . implode( ' / ', array_slice( array_unique( $err ), 0, 3 ) ) ); }
	md_inv_go( 'ok', $n . '개 품목의 재고를 센 수량으로 맞췄습니다.' . ( $same ? ' (' . $same . '개는 장부와 같아 그대로)' : '' ) );
}

function md_inv_act_ledger_void() {
	md_inv_done( md_inv_ledger_void( (int) md_inv_p( 'id' ), md_inv_txt( md_inv_p( 'why' ), 200 ) ), '기록을 취소했습니다. 재고와 통계에서 빠집니다.' );
}

function md_inv_act_stock_return() {
	$free = md_inv_p( 'free', null );
	/* v6.5 · 돌려받는 금액 (비우면 그 입고에서 실제로 낸 금액의 남은 몫) */
	$amt = md_inv_money_in( md_inv_p( 'amount' ), '돌려받는 금액' );
	if ( is_wp_error( $amt ) ) { md_inv_go( 'err', $amt->get_error_message() ); }
	md_inv_done( md_inv_do_return( (int) md_inv_p( 'in_id' ), (int) md_inv_p( 'qty' ), md_inv_txt( md_inv_p( 'note' ), 500 ), null === $free ? null : (bool) $free, $amt ), '반품을 기록했습니다.' );
}

/* ============================================================
 * 주문
 * ============================================================ */

function md_inv_act_ord_create() {
	md_inv_ord_done( md_inv_ord_create( (int) md_inv_p( 'item_id' ), (int) md_inv_p( 'qty' ), array( 'price' => md_inv_p( 'price' ), 'total' => md_inv_ord_total_p(), 'free_qty' => md_inv_p( 'free_qty' ), 'note' => md_inv_p( 'note' ) ) ), '주문을 넣었습니다.' );
}

/** 주문 필요 품목 한꺼번에 주문 */
function md_inv_act_ord_many() {
	$ids  = array_filter( array_map( 'intval', (array) md_inv_p( 'ids', array() ) ) );
	$qtys = (array) md_inv_p( 'q', array() );
	if ( ! $ids ) { md_inv_go( 'err', '주문할 품목을 골라 주세요.' ); }
	$ok = 0; $fail = array();
	foreach ( $ids as $id ) {
		$q = isset( $qtys[ $id ] ) ? (int) $qtys[ $id ] : 0;
		$r = md_inv_ord_create( $id, $q );
		if ( is_wp_error( $r ) ) { $fail[] = $r->get_error_message(); } else { $ok++; }
	}
	if ( $fail ) { md_inv_go( $ok ? 'warn' : 'err', $ok . '건 주문 · 못 한 것: ' . implode( ' / ', array_slice( $fail, 0, 3 ) ) ); }
	md_inv_go( 'ok', $ok . '건을 주문했습니다. 들어오면 「입고 대기」에서 입고를 누르세요.' );
}

function md_inv_act_ord_receive() {
	md_inv_done( md_inv_ord_receive( (int) md_inv_p( 'id' ), (int) md_inv_p( 'qty' ), array(
		'price' => md_inv_p( 'price' ), 'free' => md_inv_p( 'free' ) ? 1 : 0, 'close' => md_inv_p( 'close' ) ? 1 : 0,
		'allow_more' => md_inv_p( 'allow_more' ) ? 1 : 0, 'note' => md_inv_p( 'note' ), 'lot' => md_inv_p( 'lot' ),
		'free_qty' => md_inv_p( 'free_qty' ), 'bonus_qty' => md_inv_p( 'bonus_qty' ), 'total' => md_inv_p( 'total' ), /* v6.5 */
	) ), '입고를 기록했습니다. 재고에 더했습니다.' );
}

function md_inv_act_ord_cancel() {
	$r = md_inv_ord_cancel( (int) md_inv_p( 'id' ), (string) md_inv_p( 'why' ) );
	if ( is_wp_error( $r ) ) { md_inv_go( 'err', $r->get_error_message() ); }
	md_inv_go( 'ok', 'received' === $r ? '일부만 들어온 주문을 여기서 마감했습니다.' : '주문을 취소했습니다.' );
}

function md_inv_act_ord_update() {
	md_inv_done( md_inv_ord_update( (int) md_inv_p( 'id' ), array( 'qty' => md_inv_p( 'qty' ), 'price' => md_inv_p( 'price' ), 'total' => md_inv_ord_total_p(), 'free_qty' => md_inv_p( 'free_qty' ), 'note' => md_inv_p( 'note' ) ) ), '주문을 고쳤습니다.' );
}

/* ============================================================
 * 선납
 * ============================================================ */

function md_inv_act_dep_add() {
	md_inv_done( md_inv_deposit_add( (int) md_inv_p( 'vendor_id' ), md_inv_p( 'amount' ), (string) md_inv_p( 'paid_on' ), (string) md_inv_p( 'note' ), (string) md_inv_p( 'credit' ), 'credit' === md_inv_p( 'dkind' ) ? 'credit' : 'pay' ), 'credit' === md_inv_p( 'dkind' ) ? '업체 보상 · 리베이트를 기록했습니다 (쓸 수 있는 잔액만 늘어남).' : '입금을 기록했습니다.' );
}

function md_inv_act_dep_delete() {
	md_inv_done( md_inv_deposit_delete( (int) md_inv_p( 'id' ), (string) md_inv_p( 'why' ) ), '입금 기록을 취소했습니다 (잔액에서 빠졌고, 기록은 「취소됨」으로 남습니다).' );
}

/* ============================================================
 * 기준 정보
 * ============================================================ */

function md_inv_act_team_save() {
	$res = md_inv_team_save( (int) md_inv_p( 'id' ), array( 'name' => md_inv_p( 'name' ), 'in_stats' => md_inv_p( 'in_stats' ), 'active' => md_inv_p( 'active', 1 ) ) );
	md_inv_done( $res, '팀을 저장했습니다.' );
}

function md_inv_act_team_move() {
	md_inv_reorder( md_inv_t( 'team' ), (int) md_inv_p( 'id' ), 'up' === md_inv_p( 'dir' ) ? 'up' : 'down' );
	md_inv_go( 'ok', '순서를 바꿨습니다.' );
}

function md_inv_act_team_delete() {
	$r = md_inv_team_delete( (int) md_inv_p( 'id' ) );
	if ( is_wp_error( $r ) ) { md_inv_go( 'err', $r->get_error_message() ); }
	md_inv_go( 'ok', 'hidden' === $r ? '기록이 있는 팀이라 지우지 않고 「사용 안 함」으로 돌렸습니다. 요청 화면에서 사라집니다.' : '팀을 지웠습니다.' );
}

function md_inv_act_vendor_save() {
	$id  = (int) md_inv_p( 'id' );
	$res = md_inv_vendor_save( $id, array(
		'name' => md_inv_p( 'name' ), 'contact' => md_inv_p( 'contact' ), 'phone' => md_inv_p( 'phone' ), 'email' => md_inv_p( 'email' ),
		'shop_info' => md_inv_p( 'shop_info' ), 'goods' => md_inv_p( 'goods' ), 'note' => md_inv_p( 'note' ), 'prepaid' => md_inv_p( 'prepaid' ), 'active' => md_inv_p( 'active', 1 ),
		'pp_bonus' => md_inv_p( 'pp_bonus' ), 'pp_alert' => md_inv_p( 'pp_alert' ),
	) );
	md_inv_done( $res, '업체를 저장했습니다.' );
}

function md_inv_act_vendor_delete() {
	$r = md_inv_vendor_delete( (int) md_inv_p( 'id' ) );
	if ( is_wp_error( $r ) ) { md_inv_go( 'err', $r->get_error_message() ); }
	md_inv_go( 'ok', 'hidden' === $r ? '품목·기록이 딸린 업체라 지우지 않고 「사용 안 함」으로 돌렸습니다.' : '업체를 지웠습니다.' );
}

function md_inv_act_cat_save() {
	md_inv_done( md_inv_cat_save( (int) md_inv_p( 'id' ), array( 'name' => md_inv_p( 'name' ), 'note' => md_inv_p( 'note' ), 'level' => (int) md_inv_p( 'level' ), 'parent_id' => (int) md_inv_p( 'parent_id' ), 'active' => md_inv_p( 'active', 1 ) ) ), '분류를 저장했습니다.' );
}

function md_inv_act_cat_move() {
	$c = md_inv_cat( (int) md_inv_p( 'id' ) );
	if ( $c ) { md_inv_reorder( md_inv_t( 'cat' ), $c->id, 'up' === md_inv_p( 'dir' ) ? 'up' : 'down', 'level = ' . (int) $c->level . ' AND parent_id = ' . (int) $c->parent_id ); }
	md_inv_go( 'ok', '순서를 바꿨습니다.' );
}

function md_inv_act_cat_delete() {
	md_inv_done( md_inv_cat_delete( (int) md_inv_p( 'id' ) ), '분류를 지웠습니다. 그 분류를 쓰던 품목은 분류 칸이 비었습니다.' );
}

/* ============================================================
 * 계정
 * ============================================================ */

function md_inv_act_acc_create() {
	md_inv_done( md_inv_account_create( (string) md_inv_p( 'login' ), (string) md_inv_p( 'name' ), (string) md_inv_p( 'pass' ), (string) md_inv_p( 'role' ) ), '계정을 만들었습니다.' );
}

function md_inv_act_acc_pass() {
	md_inv_done( md_inv_account_password( (int) md_inv_p( 'id' ), (string) md_inv_p( 'pass' ) ), '비밀번호를 바꿨습니다. 그 계정으로 로그인해 있던 기기는 다시 로그인해야 합니다.' );
}

function md_inv_act_acc_role() {
	md_inv_done( md_inv_account_role( (int) md_inv_p( 'id' ), (string) md_inv_p( 'role' ) ), '권한을 바꿨습니다.' );
}

function md_inv_act_acc_delete() {
	md_inv_done( md_inv_account_delete( (int) md_inv_p( 'id' ) ), '계정을 지웠습니다.' );
}

/* ============================================================
 * 설정 · 메일 · 백업 · 가져오기
 * ============================================================ */

function md_inv_act_settings() {
	$in = array();
	foreach ( md_inv_setting_defaults() as $k => $d ) {
		if ( isset( $_POST[ 's_' . $k ] ) ) { $in[ $k ] = wp_unslash( $_POST[ 's_' . $k ] ); }
	}
	$in['__checkboxes'] = array_map( 'sanitize_key', (array) md_inv_p( 'checkboxes', array() ) );
	md_inv_settings_save( $in );
	md_inv_log( '설정 저장', implode( ', ', array_keys( $in ) ) );
	md_inv_go( 'ok', '설정을 저장했습니다.' );
}

function md_inv_act_me() {
	$name = md_inv_txt( md_inv_p( 'me' ), 40 );
	setcookie( 'md_inv_me', $name, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	md_inv_go( 'ok', '' !== $name ? '이 기기에서는 「' . $name . '」 이름으로 기록합니다.' : '이름을 지웠습니다. 기본 이름으로 기록합니다.' );
}

function md_inv_act_report_now() {
	$r = md_inv_report_send( md_inv_p( 'test' ) ? 'test' : 'manual', (string) md_inv_p( 'to' ) );
	md_inv_done( $r, '보고서 메일을 보냈습니다. 받은편지함(또는 스팸함)을 확인해 주세요.' );
}

function md_inv_act_backup_now() {
	md_inv_done( md_inv_backup_make( 'manual', md_inv_txt( md_inv_p( 'note' ), 200 ) ), '지금 상태를 백업했습니다.' );
}

function md_inv_act_backup_restore() {
	if ( ! md_inv_is_owner() ) { md_inv_go( 'err', '백업 되돌리기는 원장 계정만 할 수 있습니다.' ); }
	if ( 'RESTORE' !== strtoupper( trim( (string) md_inv_p( 'confirm' ) ) ) && '되돌리기' !== trim( (string) md_inv_p( 'confirm' ) ) ) {
		md_inv_go( 'err', '확인 칸에 「되돌리기」를 적어야 실행됩니다.' );
	}
	$raw = '';
	if ( (int) md_inv_p( 'id' ) ) {
		$raw = (string) md_inv_backup_blob( (int) md_inv_p( 'id' ) );
	} elseif ( ! empty( $_FILES['file']['tmp_name'] ) && is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
		if ( (int) $_FILES['file']['size'] > 50 * MB_IN_BYTES ) { md_inv_go( 'err', '파일이 너무 큽니다.' ); }
		$raw = (string) file_get_contents( $_FILES['file']['tmp_name'] );
	}
	$d = md_inv_backup_parse( $raw );
	if ( is_wp_error( $d ) ) { md_inv_go( 'err', $d->get_error_message() ); }
	$n = md_inv_backup_restore( $d );
	md_inv_done( $n, '백업 시점으로 되돌렸습니다 (' . ( is_wp_error( $n ) ? 0 : (int) $n ) . '줄). 직전 상태도 백업해 두었습니다.' );
}

function md_inv_act_backup_delete() {
	global $wpdb;
	$wpdb->delete( md_inv_t( 'backup' ), array( 'id' => (int) md_inv_p( 'id' ) ) );
	md_inv_log( '백업 삭제', '#' . (int) md_inv_p( 'id' ) );
	md_inv_go( 'ok', '백업을 지웠습니다.' );
}

function md_inv_act_import() {
	if ( ! md_inv_is_owner() ) { md_inv_go( 'err', '가져오기(전부 지우고 다시)는 원장 계정만 할 수 있습니다.' ); }
	$files = array();
	foreach ( array( 'team', 'vendor', 'item', 'io' ) as $k ) {
		if ( ! empty( $_FILES[ $k ]['tmp_name'] ) && is_uploaded_file( $_FILES[ $k ]['tmp_name'] ) ) {
			if ( (int) $_FILES[ $k ]['size'] > 30 * MB_IN_BYTES ) { md_inv_go( 'err', '파일이 너무 큽니다.' ); }
			$files[ $k ] = $_FILES[ $k ]['tmp_name'];
		}
	}
	$dry = ! md_inv_p( 'commit' );
	if ( ! $dry && '가져오기' !== trim( (string) md_inv_p( 'confirm' ) ) ) {
		md_inv_go( 'err', '확인 칸에 「가져오기」를 적어야 실행됩니다. 지금 재고관리 데이터를 모두 바꾸는 일이라서입니다.' );
	}
	$r = md_inv_import_appsheet( $files, array( 'dry' => $dry, 'history' => (bool) md_inv_p( 'history' ) ) );
	if ( is_wp_error( $r ) ) { md_inv_go( 'err', $r->get_error_message() ); }
	$txt = '팀 ' . $r['teams'] . ' · 분류 ' . $r['cat1'] . '/' . $r['cat2'] . '/' . $r['cat3'] . ' · 업체 ' . $r['vendors'] . '(선납 ' . $r['prepaid'] . ') · 선납 입금 ' . $r['deposits']
		. '건 · 품목 ' . $r['items'] . '(숨김 ' . $r['hidden'] . ', 재고 있음 ' . $r['stock_pos'] . ') · 입출고 행 ' . $r['io'];
	if ( $r['stock_neg'] ) { $txt .= ' · 재고가 음수인 품목 ' . count( $r['stock_neg'] ) . '개: ' . implode( ', ', array_slice( $r['stock_neg'], 0, 8 ) ) . ' (그대로 옮겨지니 실사로 바로잡아 주세요)'; }
	md_inv_go( $dry ? 'warn' : 'ok', ( $dry ? '미리보기 — 아직 아무것도 바꾸지 않았습니다. ' : '가져오기를 마쳤습니다. ' ) . $txt );
}

/* ============================================================
 * 내려받기 (GET) — 엑셀 · 백업 파일
 * ============================================================ */

function md_inv_handle_download() {
	if ( empty( $_GET['md_inv_dl'] ) ) { return; }
	if ( ! is_user_logged_in() || ! md_inv_can_use() ) { wp_die( '권한이 없습니다.', '', array( 'response' => 403 ) ); }
	/* v6.8 · 전체 엑셀은 즐겨찾기 주소로 쓰게 표식 없이도 받는다 (읽기만 하는 내려받기) · v9.5 직원도 받는다 (원장 지시) */
	$is_all = 'all' === sanitize_key( wp_unslash( $_GET['md_inv_dl'] ) );
	if ( ! $is_all && ( ! isset( $_GET['_mdinv'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_mdinv'] ) ), 'md_inv_dl' ) ) ) { wp_die( '링크가 오래되었습니다. 새로고침한 뒤 다시 눌러 주세요.' ); }
	md_inv_migrate();
	$what = sanitize_key( wp_unslash( $_GET['md_inv_dl'] ) );
	$from = isset( $_GET['df'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_GET['df'] ) ? $_GET['df'] : date( 'Y-m-d', current_time( 'timestamp' ) - 30 * DAY_IN_SECONDS );
	$to   = isset( $_GET['dt'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_GET['dt'] ) ? $_GET['dt'] : current_time( 'Y-m-d' );

	/* 직원이 내려받을 수 있는 것: 통계 · 자기 화면의 요청 내역 */
	$staff_ok = array();
	if ( md_inv_set( 'staff_stats' ) ) { $staff_ok[] = 'usage'; $staff_ok[] = 'monthly'; }
	if ( md_inv_set( 'staff_see_all_teams' ) ) { $staff_ok[] = 'requests'; }
	if ( ! md_inv_is_admin() && ! in_array( $what, array_merge( $staff_ok, array( 'xlsx', 'all' ) ), true ) ) { wp_die( '관리자만 내려받을 수 있습니다.', '', array( 'response' => 403 ) ); }

	if ( 'xlsx' === $what ) {
		md_inv_log( '엑셀 내려받기', $from . '~' . $to );
		if ( md_inv_is_admin() ) {
			md_inv_send_xlsx( md_inv_report_xlsx( $from, $to ), '문치과병원 재료실 보고서 ' . current_time( 'Y-m-d' ) . '.xlsx' );
		}
		/* 직원: 볼 수 있는 표만 묶는다 */
		$keys = array_values( array_intersect( array( 'requests', 'usage', 'monthly' ), $staff_ok ) );
		if ( ! $keys ) { wp_die( '내려받을 수 있는 표가 없습니다.', '', array( 'response' => 403 ) ); }
		md_inv_send_xlsx( md_inv_report_xlsx( $from, $to, $keys ), '문치과병원 재료실 ' . current_time( 'Y-m-d' ) . '.xlsx' );
	}
	if ( 'all' === $what ) {
		md_inv_log( '엑셀 내려받기', '전체 (모든 기록)' );
		md_inv_send_xlsx( md_inv_all_xlsx(), '문치과병원 재료실 전체 ' . current_time( 'Y-m-d' ) . '.xlsx' );
	}
	if ( 'backup' === $what ) {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		if ( $id ) { $bin = md_inv_backup_blob( $id ); } else { list( $json, $n ) = md_inv_backup_build(); $bin = gzencode( $json, 6 ); }
		if ( ! $bin ) { wp_die( '백업을 찾을 수 없습니다.' ); }
		md_inv_log( '백업 내려받기', $id ? '#' . $id : '지금 상태' );
		nocache_headers();
		header( 'Content-Type: application/gzip' );
		header( 'Content-Disposition: attachment; filename="moondental-inventory-backup-' . current_time( 'Ymd-Hi' ) . '.json.gz"' );
		header( 'Content-Length: ' . strlen( $bin ) );
		echo $bin; // phpcs:ignore
		exit;
	}
	if ( 'settle' === $what ) {
		$ym = isset( $_GET['im'] ) && preg_match( '/^\d{4}-\d{2}$/', $_GET['im'] ) ? $_GET['im'] : date( 'Y-m', strtotime( '-1 month', current_time( 'timestamp' ) ) );
		md_inv_log( '엑셀 내려받기', $ym . ' 업체 정산' );
		md_inv_send_xlsx( md_inv_settle_xlsx( $ym ), '문치과병원 업체 정산 ' . $ym . '.xlsx' );
	}
	if ( 'passbook' === $what || 'lots' === $what ) {
		$ds = md_inv_dataset( $what, array( 'vendor' => isset( $_GET['ivd'] ) ? (int) $_GET['ivd'] : 0, 'from' => isset( $_GET['df'] ) ? $from : '', 'to' => $to, 'q' => isset( $_GET['iq'] ) ? sanitize_text_field( wp_unslash( $_GET['iq'] ) ) : '' ) );
		md_inv_log( '엑셀 내려받기', $ds['title'] );
		md_inv_send_xlsx( md_inv_xlsx( array( $ds ) ), '문치과병원 ' . $ds['title'] . ' ' . current_time( 'Y-m-d' ) . '.xlsx' );
	}
	if ( 'po' === $what ) {
		$vid = isset( $_GET['ivd'] ) ? (int) $_GET['ivd'] : 0;
		$ds  = md_inv_dataset( 'po', array( 'vendor' => $vid ) );
		md_inv_log( '엑셀 내려받기', $ds['title'] );
		md_inv_send_xlsx( md_inv_xlsx( array( $ds ) ), '문치과병원 ' . $ds['title'] . ' ' . current_time( 'Y-m-d' ) . '.xlsx' );
	}
	if ( isset( md_inv_dataset_names()[ $what ] ) ) {
		/* 표 하나 = 시트 하나짜리 엑셀 (CSV 는 v4.23 에서 없앰 — 원장 지시: 엑셀 하나로) */
		$ds = md_inv_dataset( $what, array( 'from' => $from, 'to' => $to ) );
		md_inv_send_xlsx( md_inv_xlsx( array( $ds ) ), '문치과병원 ' . $ds['title'] . ' ' . current_time( 'Y-m-d' ) . '.xlsx' );
	}
	wp_die( '알 수 없는 내려받기입니다.' );
}
add_action( 'template_redirect', 'md_inv_handle_download', 2 );

function md_inv_dl_url( $what, $args = array() ) {
	return add_query_arg( array_merge( array( 'md_inv_dl' => $what, '_mdinv' => wp_create_nonce( 'md_inv_dl' ) ), $args ), md_inv_url() );
}

/** v6.5 · 주문 합계 칸 — 새 이름 total, 예전 이름 amount 도 받는다 */
function md_inv_ord_total_p() {
	$t = (string) md_inv_p( 'total' );
	return '' !== trim( $t ) ? $t : (string) md_inv_p( 'amount' );
}
