<?php
/**
 * 재료실 — 탭 · 폼 처리 · 진입점
 *
 * 서버에서 그려 보낸다. 자바스크립트가 없어도 전부 동작한다.
 * 진료실 PC·태블릿 사정이 제각각이라, 화면이 안 뜨는 것보다 느린 편이 낫다.
 *
 * 폼은 POST → 처리 → 리다이렉트(PRG). 새로고침해도 두 번 신청되지 않는다.
 *
 * 이 파일에는 화면을 그리는 코드가 거의 없다
 *   v3.63 까지 1,263줄 한 파일에 폼 처리와 여섯 화면이 뒤섞여 있었다.
 *   탭 하나를 고치려면 매번 전체를 훑어야 했다. 이제 탭마다 파일이 따로 있고
 *   여기에는 「무엇을 받아 어디로 보낼지」만 남는다.
 *     supply-request.php   요청
 *     supply-stats.php     통계
 *     supply-manage.php    반출관리
 *     supply-po.php        발주
 *     supply-stockroom.php 입고 · 재고
 *     supply-history.php   입출고 이력
 *     supply-items.php     품목 · 팀
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 탭 · URL
 * ============================================================ */

function md_sup_tabs() {
	return array(
		'request'   => array( 'label' => '요청',     'icon' => '📤', 'manage' => false ),
		'stats'     => array( 'label' => '통계',     'icon' => '📊', 'manage' => false ),
		'manage'    => array( 'label' => '반출관리', 'icon' => '📋', 'manage' => true ),
		'order'     => array( 'label' => '발주',     'icon' => '🧾', 'manage' => true ),
		'inbound'   => array( 'label' => '입고',     'icon' => '📥', 'manage' => true ),
		'inventory' => array( 'label' => '재고',     'icon' => '📦', 'manage' => true ),
		'history'   => array( 'label' => '이력',     'icon' => '🕘', 'manage' => true ),
		'items'     => array( 'label' => '품목·팀', 'icon' => '⚙️', 'manage' => true ),
	);
}

function md_sup_current_tab() {
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'request';
	$tabs = md_sup_tabs();
	if ( ! isset( $tabs[ $tab ] ) ) { $tab = 'request'; }
	if ( $tabs[ $tab ]['manage'] && ! md_sup_can_manage() ) { $tab = 'request'; }
	return $tab;
}

/**
 * 직원 전용은 여러 도구가 들어설 자리다.
 * 들어오면 무엇을 할지 먼저 고르고, 고른 뒤 그 도구로 들어간다.
 * 도구가 늘면 여기에 한 줄만 더하면 된다.
 */
function md_sup_apps() {
	return array(
		'stock' => array(
			'label' => '재료실',
			'icon'  => '📦',
			'desc'  => '재료 신청 · 우리 팀 사용량과 비용 · 입출고 관리',
		),
	);
}

function md_sup_current_app() {
	$app  = isset( $_GET['app'] ) ? sanitize_key( wp_unslash( $_GET['app'] ) ) : '';
	$apps = md_sup_apps();
	return isset( $apps[ $app ] ) ? $app : '';
}

/**
 * 페이지 주소를 만든다.
 *
 * app 을 넘기지 않으면 지금 보고 있는 도구를 그대로 유지한다.
 * 화면마다 'app' => 'stock' 을 일일이 붙이지 않아도 되게 하기 위함이다.
 * 첫 화면으로 나가려면 'app' => '' 을 명시한다.
 */
function md_sup_url( $args = array() ) {
	$base = get_permalink();
	if ( ! $base ) { $base = home_url( '/직원/' ); }

	if ( ! array_key_exists( 'app', $args ) ) {
		$cur = md_sup_current_app();
		if ( $cur ) { $args['app'] = $cur; }
	} elseif ( '' === $args['app'] ) {
		unset( $args['app'] );
	}

	return add_query_arg( $args, $base );
}

/** 재고관리 페이지인가 — 슬러그 또는 템플릿 지정 둘 다 인정 */
function md_sup_is_page() {
	if ( ! is_page() ) { return false; }
	if ( is_page_template( 'page-templates/page-supply.php' ) ) { return true; }
	$slug = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
	return in_array( $slug, array( '재료실', '직원', 'staff', 'supply', '재고관리' ), true );
}

/** 이 페이지에서만 CSS·JS 를 싣는다 */
function md_sup_enqueue() {
	if ( ! md_sup_is_page() ) { return; }

	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();

	$css = '/assets/css/supply.css';
	if ( file_exists( $dir . $css ) ) {
		wp_enqueue_style( 'moondental-supply', $uri . $css, array( 'moondental-child-style' ), filemtime( $dir . $css ) );
	}
	$js = '/assets/js/supply.js';
	if ( file_exists( $dir . $js ) ) {
		wp_enqueue_script( 'moondental-supply', $uri . $js, array(), filemtime( $dir . $js ), true );
	}
}
add_action( 'wp_enqueue_scripts', 'md_sup_enqueue', 30 );

/**
 * 환자용 떠다니는 버튼을 이 페이지에서만 걷어낸다.
 *
 * 오시는 길·전화 상담·네이버 예약·카카오톡·언어 선택은 환자분을 위한 것이라
 * 직원 재고 화면에서는 쓸 일이 없고, 화면 아래 합계 바를 가려서 방해가 된다.
 */
function md_sup_strip_patient_chrome() {
	if ( ! md_sup_is_page() ) { return; }
	remove_action( 'wp_footer', 'moondental_floating_actions', 5 );
}
add_action( 'wp', 'md_sup_strip_patient_chrome', 20 );

/** body 에 표시를 남겨, 헤더·푸터의 환자용 요소를 CSS 로 감춘다 */
function md_sup_body_class( $classes ) {
	if ( md_sup_is_page() ) { $classes[] = 'mds-page-body'; }
	return $classes;
}
add_filter( 'body_class', 'md_sup_body_class' );

/**
 * 대기 건수를 브라우저 제목에 붙인다 — 「(3) 직원 전용 …」.
 *
 * 담당자가 이 탭을 열어 두고 다른 일을 하다가도 눈에 들어온다.
 * 메일 알림과 함께, 신청이 오후 내내 방치되는 일을 줄이는 두 번째 장치다.
 */
function md_sup_title_badge( $parts ) {
	if ( ! md_sup_is_page() || ! md_sup_can_manage() ) { return $parts; }
	$n = md_sup_pending_count();
	if ( $n > 0 && isset( $parts['title'] ) ) {
		$parts['title'] = '(' . $n . ') ' . $parts['title'];
	}
	return $parts;
}
add_filter( 'document_title_parts', 'md_sup_title_badge' );

/* ============================================================
 * 폼 처리 — template_redirect 에서 먼저 받는다
 * ============================================================ */

function md_sup_handle_post() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { return; }
	if ( ! isset( $_POST['md_sup_action'] ) ) { return; }
	if ( ! md_sup_can_use() ) { return; }

	$action = sanitize_key( wp_unslash( $_POST['md_sup_action'] ) );
	if ( ! isset( $_POST['md_sup_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_sup_nonce'] ), 'md_sup_' . $action ) ) {
		wp_die( '요청이 만료되었습니다. 뒤로 가서 다시 시도해 주세요.' );
	}

	$redirect = md_sup_url( array( 'tab' => md_sup_current_tab() ) );

	switch ( $action ) {

		/* 재료 신청 */
		case 'request':
			$team_id = isset( $_POST['team_id'] ) ? (int) $_POST['team_id'] : 0;
			$qtys    = isset( $_POST['qty'] ) && is_array( $_POST['qty'] ) ? wp_unslash( $_POST['qty'] ) : array();
			$reasons = isset( $_POST['reason'] ) && is_array( $_POST['reason'] ) ? wp_unslash( $_POST['reason'] ) : array();

			$lines = array();
			foreach ( $qtys as $item_id => $q ) {
				$q = (int) $q;
				if ( $q <= 0 ) { continue; }
				$lines[ (int) $item_id ] = array(
					'qty'         => $q,
					'over_reason' => isset( $reasons[ $item_id ] ) ? sanitize_text_field( $reasons[ $item_id ] ) : '',
				);
			}

			/* 목록에 없어 직접 적은 품목 */
			$customs = array();
			$cn      = isset( $_POST['custom_name'] ) && is_array( $_POST['custom_name'] ) ? wp_unslash( $_POST['custom_name'] ) : array();
			$cq      = isset( $_POST['custom_qty'] ) && is_array( $_POST['custom_qty'] ) ? wp_unslash( $_POST['custom_qty'] ) : array();
			foreach ( $cn as $k => $nm ) {
				$nm = trim( (string) $nm );
				if ( '' === $nm ) { continue; }
				$q         = isset( $cq[ $k ] ) ? (int) $cq[ $k ] : 0;
				$customs[] = array( 'name' => $nm, 'qty' => $q > 0 ? $q : 1 );
			}

			if ( empty( $lines ) && empty( $customs ) ) {
				$redirect = add_query_arg( 'msg', 'empty', $redirect );
				break;
			}

			$res = md_sup_create_request(
				$team_id,
				$lines,
				isset( $_POST['urgent'] ) ? 1 : 0,
				isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '',
				$customs
			);
			$redirect = add_query_arg( array( 'team' => $team_id, 'msg' => is_wp_error( $res ) ? 'error' : 'sent', 'req' => is_wp_error( $res ) ? 0 : (int) $res ), $redirect );
			break;

		/* 신청 취소 — 신청한 팀이 스스로 물린다 */
		case 'cancel':
			$req_id  = isset( $_POST['req_id'] ) ? (int) $_POST['req_id'] : 0;
			$team_id = isset( $_POST['team_id'] ) ? (int) $_POST['team_id'] : md_sup_my_team_id();
			$res     = md_sup_cancel_request( $req_id, $team_id );
			$redirect = is_wp_error( $res )
				? add_query_arg( array( 'tab' => 'request', 'team' => $team_id, 'err' => $res->get_error_message() ), md_sup_url() )
				: add_query_arg( array( 'tab' => 'request', 'team' => $team_id, 'msg' => 'cancelled' ), md_sup_url() );
			break;

		/* 출고 처리 */
		case 'release':
			if ( ! md_sup_can_manage() ) { break; }
			$req_id  = isset( $_POST['req_id'] ) ? (int) $_POST['req_id'] : 0;
			$outs    = isset( $_POST['out'] ) && is_array( $_POST['out'] ) ? wp_unslash( $_POST['out'] ) : array();
			$qty_map = array();
			foreach ( $outs as $line_id => $q ) { $qty_map[ (int) $line_id ] = (int) $q; }

			$res = md_sup_release_request( $req_id, $qty_map );
			if ( is_wp_error( $res ) ) {
				$redirect = add_query_arg( array( 'tab' => 'manage', 'err' => $res->get_error_message() ), md_sup_url() );
			} else {
				$args = array( 'tab' => 'manage', 'msg' => 'released' );
				/* 창고 재고보다 많이 나간 품목이 있으면 몇 개인지 알린다 */
				if ( ! empty( $res['short'] ) ) { $args['short'] = count( $res['short'] ); }
				$redirect = add_query_arg( $args, md_sup_url() );
			}
			break;

		/* 반려 */
		case 'reject':
			if ( ! md_sup_can_manage() ) { break; }
			$n = md_sup_reject_request(
				isset( $_POST['req_id'] ) ? (int) $_POST['req_id'] : 0,
				isset( $_POST['reject_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reject_reason'] ) ) : ''
			);
			/* 바뀐 행이 없으면 그 사이 누군가 이미 처리한 것이다 —
			 * "반려했습니다" 라고 알려 놓고 실제로는 출고돼 있으면 곤란하다. */
			$redirect = $n
				? add_query_arg( array( 'tab' => 'manage', 'msg' => 'rejected' ), md_sup_url() )
				: add_query_arg( array( 'tab' => 'manage', 'err' => '이미 처리된 신청입니다. 새로고침해 확인해 주세요.' ), md_sup_url() );
			break;

		/* 입고 등록 */
		case 'inbound':
			if ( ! md_sup_can_manage() ) { break; }
			$qtys = isset( $_POST['inqty'] ) && is_array( $_POST['inqty'] ) ? wp_unslash( $_POST['inqty'] ) : array();
			$n    = 0;
			foreach ( $qtys as $item_id => $q ) {
				$q = (int) $q;
				if ( 0 === $q ) { continue; }
				md_sup_move( (int) $item_id, abs( $q ), 'in', 0, 0, '입고' );
				$n++;
			}
			$redirect = add_query_arg( array( 'tab' => 'inbound', 'msg' => $n ? 'inbound' : 'empty' ), md_sup_url() );
			break;

		/* 실사 조정 — 센 수량과 장부가 다를 때 그 차이를 원장에 남긴다 */
		case 'adjust':
			if ( ! md_sup_can_manage() ) { break; }
			$counts = isset( $_POST['count'] ) && is_array( $_POST['count'] ) ? wp_unslash( $_POST['count'] ) : array();
			$n      = 0;
			foreach ( $counts as $item_id => $c ) {
				if ( '' === trim( (string) $c ) ) { continue; }
				$item_id = (int) $item_id;
				$diff    = (int) $c - md_sup_stock( $item_id );
				if ( 0 === $diff ) { continue; }
				md_sup_move( $item_id, $diff, 'adjust', 0, 0, '실사 조정' );
				$n++;
			}
			$redirect = add_query_arg( array( 'tab' => 'inventory', 'msg' => $n ? 'adjusted' : 'empty' ), md_sup_url() );
			break;

		/* 적정재고에 제안값 채우기 */
		case 'minstock':
			if ( ! md_sup_can_manage() ) { break; }
			$lead = isset( $_POST['lead'] ) ? (float) $_POST['lead'] : 1.5;
			if ( $lead <= 0 || $lead > 12 ) { $lead = 1.5; }
			$n = md_sup_apply_min_suggestions( $lead, true );
			$redirect = add_query_arg( array( 'tab' => 'inventory', 'msg' => 'minstock', 'n' => $n ), md_sup_url() );
			break;

		/* 발주서 만들기 — 부족 품목 화면에서 거래처 단위로 */
		case 'po_create':
			if ( ! md_sup_can_manage() ) { break; }
			$qtys = isset( $_POST['poqty'] ) && is_array( $_POST['poqty'] ) ? wp_unslash( $_POST['poqty'] ) : array();
			$res  = md_sup_po_create(
				isset( $_POST['vendor'] ) ? wp_unslash( $_POST['vendor'] ) : '',
				$qtys,
				isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : ''
			);
			$redirect = is_wp_error( $res )
				? add_query_arg( array( 'tab' => 'order', 'err' => $res->get_error_message() ), md_sup_url() )
				: add_query_arg( array( 'tab' => 'order', 'po' => (int) $res, 'msg' => 'po_made' ), md_sup_url() );
			break;

		/* 작성 중인 발주서 수량 저장 */
		case 'po_save':
			if ( ! md_sup_can_manage() ) { break; }
			$po_id = isset( $_POST['po_id'] ) ? (int) $_POST['po_id'] : 0;
			$qtys  = isset( $_POST['poqty'] ) && is_array( $_POST['poqty'] ) ? wp_unslash( $_POST['poqty'] ) : array();
			$res   = md_sup_po_save_lines( $po_id, $qtys );
			$redirect = is_wp_error( $res )
				? add_query_arg( array( 'tab' => 'order', 'po' => $po_id, 'err' => $res->get_error_message() ), md_sup_url() )
				: add_query_arg( array( 'tab' => 'order', 'po' => $po_id, 'msg' => 'po_saved' ), md_sup_url() );
			break;

		/* 주문 확정 */
		case 'po_order':
			if ( ! md_sup_can_manage() ) { break; }
			$po_id = isset( $_POST['po_id'] ) ? (int) $_POST['po_id'] : 0;
			$res   = md_sup_po_mark_ordered( $po_id );
			$redirect = is_wp_error( $res )
				? add_query_arg( array( 'tab' => 'order', 'po' => $po_id, 'err' => $res->get_error_message() ), md_sup_url() )
				: add_query_arg( array( 'tab' => 'order', 'po' => $po_id, 'msg' => 'po_ordered' ), md_sup_url() );
			break;

		/* 발주 입고 처리 */
		case 'po_receive':
			if ( ! md_sup_can_manage() ) { break; }
			$po_id = isset( $_POST['po_id'] ) ? (int) $_POST['po_id'] : 0;
			$recv  = isset( $_POST['recv'] ) && is_array( $_POST['recv'] ) ? wp_unslash( $_POST['recv'] ) : array();
			$map   = array();
			foreach ( $recv as $line_id => $q ) { $map[ (int) $line_id ] = (int) $q; }

			$res = md_sup_po_receive( $po_id, $map );
			$redirect = is_wp_error( $res )
				? add_query_arg( array( 'tab' => 'order', 'po' => $po_id, 'err' => $res->get_error_message() ), md_sup_url() )
				: add_query_arg( array( 'tab' => 'order', 'po' => $po_id, 'msg' => $res ? 'po_recv' : 'empty' ), md_sup_url() );
			break;

		/* 발주 취소 · 지우기 */
		case 'po_cancel':
			if ( ! md_sup_can_manage() ) { break; }
			$po_id = isset( $_POST['po_id'] ) ? (int) $_POST['po_id'] : 0;
			$res   = md_sup_po_cancel( $po_id );
			$redirect = is_wp_error( $res )
				? add_query_arg( array( 'tab' => 'order', 'po' => $po_id, 'err' => $res->get_error_message() ), md_sup_url() )
				: add_query_arg( array( 'tab' => 'order', 'msg' => 'po_cancelled' ), md_sup_url() );
			break;

		/* 품목 등록 · 수정 */
		case 'item':
			if ( ! md_sup_can_manage() ) { break; }
			$res = md_sup_item_save(
				isset( $_POST['item_id'] ) ? (int) $_POST['item_id'] : 0,
				array(
					'name'      => isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '',
					'vendor'    => isset( $_POST['vendor'] ) ? wp_unslash( $_POST['vendor'] ) : '',
					'unit'      => isset( $_POST['unit'] ) ? wp_unslash( $_POST['unit'] ) : '',
					'category'  => isset( $_POST['category'] ) ? wp_unslash( $_POST['category'] ) : '',
					'price'     => isset( $_POST['price'] ) ? $_POST['price'] : 0,
					'min_stock' => isset( $_POST['min_stock'] ) ? $_POST['min_stock'] : 0,
				)
			);
			$redirect = is_wp_error( $res )
				? add_query_arg( array( 'tab' => 'items', 'err' => $res->get_error_message() ), md_sup_url() )
				: add_query_arg( array( 'tab' => 'items', 'msg' => 'item_saved' ), md_sup_url() );
			break;

		/* 팀 이름·사용 여부·순서 저장 + 새 팀 추가 */
		case 'team':
			if ( ! md_sup_can_manage() ) { break; }
			$names = isset( $_POST['team_name'] ) && is_array( $_POST['team_name'] ) ? wp_unslash( $_POST['team_name'] ) : array();
			$ons   = isset( $_POST['team_on'] ) && is_array( $_POST['team_on'] ) ? wp_unslash( $_POST['team_on'] ) : array();
			$sorts = isset( $_POST['team_sort'] ) && is_array( $_POST['team_sort'] ) ? wp_unslash( $_POST['team_sort'] ) : array();

			foreach ( $names as $tid => $nm ) {
				$tid = (int) $tid;
				/* 순서 칸이 비어 있으면 지금 값을 그대로 둔다 — null 을 넘긴다.
				 * v3.63 까지는 늘 0 을 써 넣어, 저장 한 번에 층별 배치가 흐트러졌다. */
				$sort = ( isset( $sorts[ $tid ] ) && '' !== trim( (string) $sorts[ $tid ] ) ) ? (int) $sorts[ $tid ] : null;
				md_sup_team_save( $tid, $nm, $sort );
				md_sup_team_archive( $tid, isset( $ons[ $tid ] ) );
			}
			$new = isset( $_POST['team_new'] ) ? trim( (string) wp_unslash( $_POST['team_new'] ) ) : '';
			if ( '' !== $new ) { md_sup_team_save( 0, $new ); }

			$redirect = add_query_arg( array( 'tab' => 'items', 'msg' => 'team_saved' ), md_sup_url() );
			break;

		/**
		 * 직원이 목록에 없는 품목을 정식으로 등록한다 (v3.65).
		 *
		 * 담당자만 등록할 수 있게 두면, 필요한 물건이 목록에 없을 때 직원은
		 * 이름만 적어 두고 담당자가 옮겨 적어 주기를 기다려야 했다.
		 * 이제 직원이 그 자리에서 등록하고 바로 신청까지 담는다.
		 *
		 * 카탈로그가 지저분해지지 않게 하는 장치
		 *   · 같은 이름이 이미 있으면 만들지 않고 그 품목으로 안내한다
		 *   · 분류·거래처는 담당자가 만든 목록에서만 고른다 (새 값은 못 만든다)
		 *   · 단가·적정재고는 비워 둔다 — 담당자가 채울 몫이고,
		 *     created_by 로 「직원 등록」 표시가 붙어 품목 관리에서 눈에 띈다
		 */
		case 'newitem':
			$team_id = isset( $_POST['team_id'] ) ? (int) $_POST['team_id'] : 0;
			$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$qty     = isset( $_POST['qty_new'] ) ? max( 1, (int) $_POST['qty_new'] ) : 1;
			$base    = array( 'tab' => 'request', 'team' => $team_id );

			if ( '' === trim( $name ) ) {
				$redirect = add_query_arg( array_merge( $base, array( 'err' => '품목명을 적어 주세요.' ) ), md_sup_url() );
				break;
			}

			/* 이미 있는 품목이면 새로 만들지 않고 그리로 데려간다 */
			$exists = md_sup_item_by_name( $name );
			if ( $exists ) {
				$redirect = add_query_arg( array_merge( $base, array(
					'newitem' => (int) $exists->id,
					'nqty'    => $qty,
					'msg'     => 'newitem_dup',
				) ), md_sup_url() );
				break;
			}

			/* 담당자가 만든 목록에 있는 값만 받는다 */
			$v_in = isset( $_POST['vendor'] ) ? sanitize_text_field( wp_unslash( $_POST['vendor'] ) ) : '';
			$c_in = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
			$v_ok = in_array( $v_in, md_sup_vendors(), true ) ? $v_in : '';
			$c_ok = in_array( $c_in, md_sup_categories(), true ) ? $c_in : '';

			$res = md_sup_item_save( 0, array(
				'name'      => $name,
				'vendor'    => $v_ok,
				'unit'      => isset( $_POST['unit'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['unit'] ) ), 0, 20 ) : '',
				'category'  => $c_ok,
				'price'     => 0,
				'min_stock' => 0,
			) );

			$redirect = is_wp_error( $res )
				? add_query_arg( array_merge( $base, array( 'err' => $res->get_error_message() ) ), md_sup_url() )
				: add_query_arg( array_merge( $base, array( 'newitem' => (int) $res, 'nqty' => $qty, 'msg' => 'newitem' ) ), md_sup_url() );
			break;

		/* 분류 · 거래처 목록 저장 (이름·순서·사용 여부 + 새로 추가) */
		case 'taxo':
			if ( ! md_sup_can_manage() ) { break; }
			$err = '';

			foreach ( array( 'category', 'vendor' ) as $kind ) {
				$names = isset( $_POST[ $kind . '_name' ] ) && is_array( $_POST[ $kind . '_name' ] ) ? wp_unslash( $_POST[ $kind . '_name' ] ) : array();
				$sorts = isset( $_POST[ $kind . '_sort' ] ) && is_array( $_POST[ $kind . '_sort' ] ) ? wp_unslash( $_POST[ $kind . '_sort' ] ) : array();
				$ons   = isset( $_POST[ $kind . '_on' ] ) && is_array( $_POST[ $kind . '_on' ] ) ? wp_unslash( $_POST[ $kind . '_on' ] ) : array();

				foreach ( $names as $tid => $nm ) {
					$tid  = (int) $tid;
					$sort = ( isset( $sorts[ $tid ] ) && '' !== trim( (string) $sorts[ $tid ] ) ) ? (int) $sorts[ $tid ] : null;
					$r    = md_sup_taxo_save( $kind, $tid, $nm, $sort );
					if ( is_wp_error( $r ) && '' === $err ) { $err = $r->get_error_message(); }
					md_sup_taxo_archive( $kind, $tid, isset( $ons[ $tid ] ) );
				}

				$new = isset( $_POST[ $kind . '_new' ] ) ? trim( (string) wp_unslash( $_POST[ $kind . '_new' ] ) ) : '';
				if ( '' !== $new ) {
					$r = md_sup_taxo_save( $kind, 0, $new );
					if ( is_wp_error( $r ) && '' === $err ) { $err = $r->get_error_message(); }
				}
			}

			$redirect = ( '' !== $err )
				? add_query_arg( array( 'tab' => 'items', 'err' => $err ), md_sup_url() )
				: add_query_arg( array( 'tab' => 'items', 'msg' => 'taxo_saved' ), md_sup_url() );
			break;

		/* 알림 받을 메일 주소 */
		case 'notify':
			if ( ! md_sup_can_manage() ) { break; }
			$raw = isset( $_POST['emails'] ) ? sanitize_text_field( wp_unslash( $_POST['emails'] ) ) : '';
			update_option( 'md_sup_notify_emails', mb_substr( $raw, 0, 500 ) );
			$redirect = add_query_arg( array( 'tab' => 'items', 'msg' => 'notify_saved' ), md_sup_url() );
			break;
	}

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'template_redirect', 'md_sup_handle_post', 1 );

/**
 * 즐겨찾기 토글 · 삭제 · CSV 내보내기 — 화면을 그리기 전에 처리한다.
 * 모두 GET 이라 여기서 받는다. 상태를 바꾸는 것은 nonce 로 위조를 막는다.
 */
function md_sup_handle_get() {
	if ( ! md_sup_is_page() || ! md_sup_can_use() ) { return; }

	/* 즐겨찾기 */
	if ( isset( $_GET['fav'] ) && isset( $_GET['_wpnonce'] ) ) {
		$item = (int) $_GET['fav'];
		$team = isset( $_GET['team'] ) ? (int) $_GET['team'] : md_sup_my_team_id();
		$ok   = wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'md_sup_fav_' . $item );
		$on   = false;
		if ( $ok ) { $on = md_sup_fav_toggle( $team, $item ); }

		/* JS 가 가로챈 경우 — 568줄을 다시 그리지 않고 별표만 바꾼다 (v3.64) */
		if ( isset( $_GET['ajax'] ) ) {
			wp_send_json( array( 'ok' => (bool) $ok, 'on' => (bool) $on ) );
		}

		$back = remove_query_arg( array( 'fav', '_wpnonce', 'ajax' ) );
		wp_safe_redirect( $back . '#i' . $item );
		exit;
	}

	/* 품목 감추기 · 되살리기 */
	if ( isset( $_GET['toggle'] ) && isset( $_GET['_wpnonce'] ) && md_sup_can_manage() ) {
		$id = (int) $_GET['toggle'];
		if ( wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'md_sup_toggle_' . $id ) ) {
			$it = md_sup_item( $id );
			if ( $it ) { md_sup_item_archive( $id, ! (int) $it->active ); }
		}
		wp_safe_redirect( remove_query_arg( array( 'toggle', '_wpnonce' ) ) );
		exit;
	}

	/* 품목 삭제 — 기록이 있으면 한 번 되돌려 보내고, 다시 누르면 지운다 */
	if ( isset( $_GET['delitem'] ) && isset( $_GET['_wpnonce'] ) && md_sup_can_manage() ) {
		$id = (int) $_GET['delitem'];
		if ( wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'md_sup_delitem_' . $id ) ) {
			$force = isset( $_GET['force'] ) && '1' === $_GET['force'];
			$res   = md_sup_item_delete( $id, $force );
			$back  = remove_query_arg( array( 'delitem', '_wpnonce', 'force' ) );
			if ( is_wp_error( $res ) ) {
				$back = add_query_arg( array( 'confirm' => 'item', 'cid' => $id, 'cmsg' => rawurlencode( $res->get_error_message() ) ), $back );
			} else {
				$back = add_query_arg( 'msg', 'deleted', $back );
			}
			wp_safe_redirect( $back );
			exit;
		}
	}

	/* 팀 삭제 — 같은 방식 */
	if ( isset( $_GET['delteam'] ) && isset( $_GET['_wpnonce'] ) && md_sup_can_manage() ) {
		$id = (int) $_GET['delteam'];
		if ( wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'md_sup_delteam_' . $id ) ) {
			$force = isset( $_GET['force'] ) && '1' === $_GET['force'];
			$res   = md_sup_team_delete( $id, $force );
			$back  = remove_query_arg( array( 'delteam', '_wpnonce', 'force' ) );
			if ( is_wp_error( $res ) ) {
				$back = add_query_arg( array( 'confirm' => 'team', 'cid' => $id, 'cmsg' => rawurlencode( $res->get_error_message() ) ), $back );
			} else {
				$back = add_query_arg( 'msg', 'deleted', $back );
			}
			wp_safe_redirect( $back );
			exit;
		}
	}

	/* 분류 · 거래처 삭제 — 품목·팀과 같은 두 단계 확인 */
	if ( isset( $_GET['deltaxo'] ) && isset( $_GET['_wpnonce'] ) && md_sup_can_manage() ) {
		$id   = (int) $_GET['deltaxo'];
		$kind = ( isset( $_GET['taxokind'] ) && 'vendor' === $_GET['taxokind'] ) ? 'vendor' : 'category';
		if ( wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'md_sup_deltaxo_' . $kind . '_' . $id ) ) {
			$force = isset( $_GET['force'] ) && '1' === $_GET['force'];
			$res   = md_sup_taxo_delete( $kind, $id, $force );
			$back  = remove_query_arg( array( 'deltaxo', 'taxokind', '_wpnonce', 'force' ) );
			if ( is_wp_error( $res ) ) {
				$back = add_query_arg( array( 'confirm' => $kind, 'cid' => $id, 'cmsg' => rawurlencode( $res->get_error_message() ) ), $back );
			} else {
				$back = add_query_arg( 'msg', 'deleted', $back );
			}
			wp_safe_redirect( $back );
			exit;
		}
	}

	/* CSV 내보내기 */
	if ( isset( $_GET['export'] ) ) {
		$what = sanitize_key( wp_unslash( $_GET['export'] ) );
		if ( 'usage' === $what ) { md_sup_export_usage(); }
		if ( 'stock' === $what && md_sup_can_manage() ) { md_sup_export_stock(); }
		if ( 'ledger' === $what && md_sup_can_manage() ) { md_sup_export_ledger(); }
		if ( 'po' === $what && md_sup_can_manage() ) { md_sup_export_po( isset( $_GET['po'] ) ? (int) $_GET['po'] : 0 ); }
	}
}
add_action( 'template_redirect', 'md_sup_handle_get', 2 );

/** CSV 한 줄 — 엑셀이 한글을 깨뜨리지 않게 BOM 을 앞에 붙인다 */
function md_sup_csv_start( $filename ) {
	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	echo "\xEF\xBB\xBF"; // UTF-8 BOM · 없으면 엑셀에서 한글이 깨진다
}

function md_sup_csv_row( $cols ) {
	$out = array();
	foreach ( $cols as $c ) {
		$c = (string) $c;
		$out[] = '"' . str_replace( '"', '""', $c ) . '"';
	}
	echo implode( ',', $out ) . "\r\n";
}

/** 사용량 CSV — 통계 화면에서 내려받는다 */
function md_sup_export_usage() {
	$ym   = isset( $_GET['ym'] ) ? sanitize_text_field( wp_unslash( $_GET['ym'] ) ) : current_time( 'Y-m' );
	$view = isset( $_GET['team'] ) ? (int) $_GET['team'] : 0;

	md_sup_csv_start( 'moondental-usage-' . $ym . '.csv' );
	md_sup_csv_row( array( '문치과병원 재료 사용 내역', $ym, $view ? md_sup_team_name( $view ) : '전체 팀' ) );
	md_sup_csv_row( array() );

	md_sup_csv_row( array( '팀', '수량', '금액(원)' ) );
	foreach ( md_sup_team_usage( $ym ) as $u ) {
		md_sup_csv_row( array( $u->team_name, (int) $u->qty, (int) $u->amount ) );
	}

	md_sup_csv_row( array() );
	md_sup_csv_row( array( '품목', '단위', '단가', '수량', '금액(원)' ) );
	foreach ( md_sup_top_items( $ym, $view, 500 ) as $r ) {
		md_sup_csv_row( array( $r->name, $r->unit, (int) $r->price, (int) $r->qty, (int) $r->amount ) );
	}
	exit;
}

/** 재고 CSV — 실사용 인쇄나 엑셀 정리에 쓴다 */
function md_sup_export_stock() {
	md_sup_csv_start( 'moondental-stock-' . current_time( 'Y-m-d' ) . '.csv' );
	md_sup_csv_row( array( '품목코드', '품목명', '거래처', '단위', '단가', '현재고', '적정재고', '부족' ) );
	foreach ( md_sup_items() as $it ) {
		$low = ( $it->min_stock > 0 && $it->stock <= $it->min_stock ) ? 'O' : '';
		md_sup_csv_row( array(
			$it->code, $it->name, $it->vendor, $it->unit,
			(int) $it->price, (int) $it->stock, (int) $it->min_stock, $low,
		) );
	}
	exit;
}

/* v3.65 · 고른 팀을 쿠키에 기억하던 기능을 뺐다.
 *
 * 공용 계정이라 편하라고 넣었는데, 편한 만큼 위험했다 —
 * 앞사람이 고른 팀이 그대로 남아 있으면 다음 사람이 팀을 확인하지 않고
 * 신청해 버리고, 그 사용량은 엉뚱한 팀으로 잡힌다. 잘못 잡힌 사용량은
 * 나중에 원장을 뒤져 한 줄씩 되돌려야 한다.
 * 매번 고르는 3초가 그보다 싸다. 계정에 소속 팀이 지정돼 있으면
 * 그 팀이 그대로 기본값이 되므로, 개인 계정 쪽은 달라지는 게 없다.
 */

/* ============================================================
 * 알림 문구
 * ============================================================ */

function md_sup_notice( $code, $n = 0 ) {
	$map = array(
		'sent'         => array( 'ok',   '신청이 접수되었습니다.' ),
		'empty'        => array( 'warn', '수량을 입력한 품목이 없습니다.' ),
		'error'        => array( 'warn', '저장하지 못했습니다. 팀이 지정되어 있는지 확인해 주세요.' ),
		'cancelled'    => array( 'ok',   '신청을 취소했습니다.' ),
		'released'     => array( 'ok',   '출고 처리했습니다. 재고에 반영되었습니다.' ),
		'rejected'     => array( 'ok',   '반려 처리했습니다. 반려 사유가 신청한 팀에 보입니다.' ),
		'inbound'      => array( 'ok',   '입고를 기록했습니다.' ),
		'adjusted'     => array( 'ok',   '실사 결과를 반영했습니다.' ),
		'item_saved'   => array( 'ok',   '품목을 저장했습니다.' ),
		'team_saved'   => array( 'ok',   '팀을 저장했습니다.' ),
		'notify_saved' => array( 'ok',   '알림 받을 주소를 저장했습니다.' ),
		'taxo_saved'   => array( 'ok',   '분류·거래처를 저장했습니다.' ),
		'newitem'      => array( 'ok',   '품목을 등록했습니다. 표 맨 위에 있으니 수량을 확인하고 신청해 주세요. 단가와 적정재고는 담당자가 채웁니다.' ),
		'newitem_dup'  => array( 'warn', '같은 이름의 품목이 이미 있어 새로 만들지 않았습니다. 표 맨 위에 그 품목을 올려 두었습니다.' ),
		'deleted'      => array( 'ok',   '삭제했습니다.' ),
		'po_made'      => array( 'ok',   '발주서를 만들었습니다. 수량을 확인하고 주문 확정을 눌러 주세요.' ),
		'po_saved'     => array( 'ok',   '발주 수량을 저장했습니다.' ),
		'po_ordered'   => array( 'ok',   '주문 확정했습니다. 이제 「주문 중」으로 잡힙니다.' ),
		'po_recv'      => array( 'ok',   '발주 입고를 기록했습니다.' ),
		'po_cancelled' => array( 'ok',   '발주를 취소했습니다.' ),
		'minstock'     => array( 'ok',   '적정재고에 제안값을 넣었습니다 — %d개 품목.' ),
	);
	if ( ! isset( $map[ $code ] ) ) { return ''; }
	list( $type, $text ) = $map[ $code ];
	if ( false !== strpos( $text, '%d' ) ) { $text = sprintf( $text, (int) $n ); }
	return '<div class="mds-notice mds-notice--' . esc_attr( $type ) . '">' . esc_html( $text ) . '</div>';
}

/* ============================================================
 * 화면 — 문지기와 뼈대
 * ============================================================ */

/** 로그인 안 된 경우 */
function md_sup_render_login() {
	?>
	<div class="mds-gate">
		<div class="mds-gate__box">
			<span class="mds-gate__eyebrow">한아의료재단 문치과병원</span>
			<h1>직원 전용</h1>
			<p>병원에서 발급받은 계정으로 로그인해 주세요.</p>
			<?php
			wp_login_form( array(
				'redirect'       => md_sup_url(),
				'label_username' => '아이디',
				'label_password' => '비밀번호',
				'label_log_in'   => '로그인',
				'remember'       => true,
				'label_remember' => '로그인 상태 유지',
			) );
			?>
			<?php /* 「비밀번호를 잊으셨나요」 링크는 두지 않는다.
			         이 두 계정은 재설정 메일을 받을 수 없게 막아 두었으므로
			         눌러도 아무 일도 일어나지 않는다. 안내 문구로 대신한다. */ ?>
			<p class="mds-gate__help">
				아이디나 비밀번호를 모르시면 경영지원실에 문의해 주세요.
			</p>
		</div>
	</div>
	<?php
}

/** 로그인은 됐지만 재고 권한이 없는 경우 */
function md_sup_render_denied() {
	?>
	<div class="mds-gate">
		<div class="mds-gate__box">
			<span class="mds-gate__eyebrow">접근 권한 없음</span>
			<h1>직원 전용 페이지 이용 권한이 없습니다</h1>
			<p>
				<?php echo esc_html( wp_get_current_user()->display_name ); ?> 님 계정에는 이 페이지 이용 권한이 없습니다.
				경영지원실에 권한 부여를 요청해 주세요.
			</p>
			<p class="mds-gate__help"><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">로그아웃</a></p>
		</div>
	</div>
	<?php
}

/** 헤더 — 첫 화면이면 제목만, 도구 안이면 돌아가기와 탭까지 */
function md_sup_render_header( $app, $tab ) {
	$user    = wp_get_current_user();
	$apps    = md_sup_apps();
	$pending = md_sup_can_manage() ? md_sup_pending_count() : 0;
	?>
	<div class="mds-head">
		<div class="mds-head__top">
			<div>
				<span class="mds-head__eyebrow">
					<?php if ( $app ) : ?>
						<a class="mds-head__back" href="<?php echo esc_url( md_sup_url( array( 'app' => '' ) ) ); ?>">← 직원 전용</a>
					<?php else : ?>
						한아의료재단 문치과병원
					<?php endif; ?>
				</span>
				<h1><?php echo esc_html( $app ? $apps[ $app ]['label'] : '직원 전용' ); ?></h1>
			</div>
			<div class="mds-head__me">
				<span class="mds-head__name"><?php echo esc_html( $user->display_name ); ?></span>
				<a class="mds-head__out" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">로그아웃</a>
			</div>
		</div>

		<?php if ( 'stock' === $app ) : ?>
			<nav class="mds-tabs" aria-label="재료실 메뉴">
				<?php foreach ( md_sup_tabs() as $key => $t ) :
					if ( $t['manage'] && ! md_sup_can_manage() ) { continue; } ?>
					<a class="mds-tab<?php echo $tab === $key ? ' is-on' : ''; ?>"
					   href="<?php echo esc_url( md_sup_url( array( 'app' => 'stock', 'tab' => $key ) ) ); ?>"
					   <?php echo $tab === $key ? 'aria-current="page"' : ''; ?>>
						<span aria-hidden="true"><?php echo esc_html( $t['icon'] ); ?></span><?php echo esc_html( $t['label'] ); ?>
						<?php if ( 'manage' === $key && $pending ) : ?>
							<span class="mds-tab__badge"><?php echo (int) $pending; ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>
	<?php
}

/** 첫 화면 — 어떤 도구로 들어갈지 고른다 */
function md_sup_render_hub() {
	$pending = md_sup_can_manage() ? md_sup_pending_count() : 0;
	?>
	<div class="mds-apps">
		<?php foreach ( md_sup_apps() as $key => $a ) : ?>
			<a class="mds-app" href="<?php echo esc_url( md_sup_url( array( 'app' => $key ) ) ); ?>">
				<span class="mds-app__icon" aria-hidden="true"><?php echo esc_html( $a['icon'] ); ?></span>
				<span class="mds-app__body">
					<span class="mds-app__label">
						<?php echo esc_html( $a['label'] ); ?>
						<?php if ( 'stock' === $key && $pending ) : ?>
							<span class="mds-app__badge"><?php echo (int) $pending; ?>건 대기</span>
						<?php endif; ?>
					</span>
					<span class="mds-app__desc"><?php echo esc_html( $a['desc'] ); ?></span>
				</span>
				<span class="mds-app__go" aria-hidden="true">→</span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}

/** 전체 페이지 진입점 */
function md_sup_render_page() {
	if ( ! is_user_logged_in() ) { md_sup_render_login(); return; }
	if ( ! md_sup_can_use() )    { md_sup_render_denied(); return; }

	$app = md_sup_current_app();
	$tab = md_sup_current_tab();
	md_sup_render_header( $app, $tab );

	if ( isset( $_GET['msg'] ) ) {
		$n = isset( $_GET['n'] ) ? (int) $_GET['n'] : 0;
		echo md_sup_notice( sanitize_key( wp_unslash( $_GET['msg'] ) ), $n ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	if ( isset( $_GET['err'] ) ) {
		echo '<div class="mds-notice mds-notice--warn">' . esc_html( wp_unslash( $_GET['err'] ) ) . '</div>';
	}
	/* 창고 재고보다 많이 나간 품목이 있었다 — 장부를 실사로 맞추라고 알린다 */
	if ( isset( $_GET['short'] ) && (int) $_GET['short'] > 0 ) {
		echo '<div class="mds-notice mds-notice--warn">'
			. esc_html( sprintf(
				'%d개 품목이 창고 재고보다 많이 나갔습니다. 출고는 그대로 기록했으니, 「재고」 화면에서 실사로 장부를 맞춰 주세요.',
				(int) $_GET['short']
			) )
			. '</div>';
	}

	if ( 'stock' !== $app ) {
		md_sup_render_hub();
	} else {
		switch ( $tab ) {
			case 'stats':     md_sup_render_stats();     break;
			case 'manage':    md_sup_render_manage();    break;
			case 'order':     md_sup_render_order();     break;
			case 'inbound':   md_sup_render_inbound();   break;
			case 'inventory': md_sup_render_inventory(); break;
			case 'history':   md_sup_render_history();   break;
			case 'items':     md_sup_render_items();     break;
			default:          md_sup_render_request();   break;
		}
	}

	/* 사이트 푸터를 감췄으니 필요한 안내만 여기서 */
	?>
	<div class="mds-foot">
		<span>한아의료재단 문치과병원 · 직원 전용</span>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">병원 홈페이지</a>
		<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">로그아웃</a>
	</div>
	<?php
}
